<?php
/**
 * Stockage des fichiers envoyés (images / vidéos).
 *
 * - Sur Railway / serveur classique : fichiers enregistrés dans le dossier uploads/ (comportement d'origine).
 * - Sur Vercel : le disque est en lecture seule, les fichiers sont envoyés vers Vercel Blob
 *   (variable d'environnement BLOB_READ_WRITE_TOKEN) et la base stocke l'URL complète.
 */

if (!function_exists('blob_actif')) {

    function blob_token() {
        $t = getenv('BLOB_READ_WRITE_TOKEN');
        return $t ? $t : null;
    }

    function blob_actif() {
        return blob_token() !== null;
    }

    /** Identifiant du store Blob (partie 4 de vercel_blob_rw_<store>_<secret>). */
    function blob_store_id() {
        $parts = explode('_', (string) blob_token());
        return isset($parts[3]) ? $parts[3] : '';
    }

    /** Préfixe public des URLs de ce store (pour valider les URLs envoyées par le navigateur). */
    function blob_url_prefix() {
        return 'https://' . strtolower(blob_store_id()) . '.public.blob.vercel-storage.com/';
    }

    /**
     * Retourne l'URL publique d'un fichier, qu'il soit local (nom de fichier) ou sur Blob (URL complète).
     *
     * @param string $dossier  'galerie' ou 'informations'
     * @param string $fichier  nom du fichier ou URL complète
     * @param string $prefixe  préfixe relatif (ex. '../' depuis le dossier admin)
     */
    function media_url($dossier, $fichier, $prefixe = '') {
        if (preg_match('#^https?://#i', (string) $fichier)) {
            return $fichier;
        }
        return $prefixe . 'uploads/' . $dossier . '/' . $fichier;
    }

    /** Indique si un média existe (les URLs Blob sont considérées comme existantes). */
    function media_existe($dossier, $fichier, $racine) {
        if (empty($fichier)) {
            return false;
        }
        if (preg_match('#^https?://#i', $fichier)) {
            return true;
        }
        return file_exists(rtrim($racine, '/') . '/uploads/' . $dossier . '/' . $fichier);
    }

    /** Envoie un contenu vers Vercel Blob et retourne l'URL publique (ou null en cas d'échec). */
    function blob_envoyer($chemin_blob, $fichier_local, $content_type = 'application/octet-stream') {
        $token = blob_token();
        if (!$token) {
            return null;
        }
        $fh = fopen($fichier_local, 'rb');
        if (!$fh) {
            return null;
        }
        $ch = curl_init('https://blob.vercel-storage.com/' . ltrim($chemin_blob, '/'));
        curl_setopt_array($ch, [
            CURLOPT_PUT => true,
            CURLOPT_INFILE => $fh,
            CURLOPT_INFILESIZE => filesize($fichier_local),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'x-api-version: 7',
                'x-content-type: ' . $content_type,
                'x-add-random-suffix: 0',
            ],
        ]);
        $reponse = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fh);
        if ($code >= 200 && $code < 300) {
            $json = json_decode($reponse, true);
            return isset($json['url']) ? $json['url'] : null;
        }
        error_log('Blob upload error ' . $code . ': ' . $reponse);
        return null;
    }

    /** Supprime un fichier sur Vercel Blob. */
    function blob_supprimer($url) {
        $token = blob_token();
        if (!$token || !preg_match('#^https?://#i', (string) $url)) {
            return;
        }
        $ch = curl_init('https://blob.vercel-storage.com/delete');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['urls' => [$url]]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'x-api-version: 7',
                'Content-Type: application/json',
            ],
        ]);
        curl_exec($ch);
        curl_close($ch);
    }

    /**
     * Remplace move_uploaded_file() : enregistre le fichier envoyé localement ou sur Vercel Blob.
     * Sur Blob, $nom est remplacé par l'URL complète (c'est cette valeur qui doit aller en base).
     */
    function stocker_upload($tmp, $destination, &$nom) {
        if (!blob_actif()) {
            return move_uploaded_file($tmp, $destination);
        }
        if (!is_uploaded_file($tmp)) {
            return false;
        }
        $dossier = basename(dirname($destination));
        $type = function_exists('mime_content_type') ? (mime_content_type($tmp) ?: 'application/octet-stream') : 'application/octet-stream';
        $url = blob_envoyer($dossier . '/' . $nom, $tmp, $type);
        if ($url) {
            $nom = $url;
            return true;
        }
        return false;
    }

    /** Remplace unlink() pour un média : supprime le fichier local ou sur Blob. */
    function supprimer_media($chemin_local, $fichier) {
        if (preg_match('#^https?://#i', (string) $fichier)) {
            blob_supprimer($fichier);
        } elseif (file_exists($chemin_local)) {
            @unlink($chemin_local);
        }
    }

    /**
     * Jeton client Vercel Blob permettant au navigateur d'envoyer directement un gros fichier
     * (les fonctions Vercel limitent le corps des requêtes à 4,5 Mo).
     */
    function blob_jeton_client($chemin_blob, $taille_max = 104857600) {
        $token = blob_token();
        if (!$token) {
            return null;
        }
        $payload = base64_encode(json_encode([
            'pathname' => $chemin_blob,
            'maximumSizeInBytes' => $taille_max,
            'allowedContentTypes' => ['image/*', 'video/mp4', 'video/webm'],
            'addRandomSuffix' => false,
            'validUntil' => (int) round(microtime(true) * 1000) + 15 * 60 * 1000,
        ]));
        $signature = hash_hmac('sha256', $payload, $token);
        return 'vercel_blob_client_' . blob_store_id() . '_' . base64_encode($signature . '.' . $payload);
    }
}
