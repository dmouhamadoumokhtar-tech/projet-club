<?php
/**
 * Routeur unique pour Vercel (runtime vercel-php).
 * Toutes les URLs .php du site (public + admin) passent par ce fichier,
 * qui exécute le script demandé exactement comme le ferait un serveur PHP classique.
 */

$racine = realpath(__DIR__ . '/..');

require_once $racine . '/includes/db_connexion.php';
require_once $racine . '/includes/session_db.php';

ini_set('session.gc_maxlifetime', '86400');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', '1');
ini_set('session.cookie_samesite', 'Lax');
session_set_save_handler(new SessionBaseDeDonnees(), true);

$chemin = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
if (substr($chemin, -1) === '/') {
    $chemin .= 'index.php';
}

$fichier = realpath($racine . $chemin);
$interdit = preg_match('#^/(includes|api|vendor)/#i', $chemin);

if ($fichier === false || $interdit || strpos($fichier, $racine . '/') !== 0 || substr($fichier, -4) !== '.php') {
    http_response_code(404);
    echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>Page introuvable</title></head>'
        . '<body style="background:#020c1b;color:#fff;font-family:sans-serif;text-align:center;padding-top:15vh">'
        . '<h1>404</h1><p>Page introuvable.</p><p><a style="color:#64ffda" href="/">Retour à l\'accueil</a></p></body></html>';
    exit;
}

$_SERVER['SCRIPT_NAME'] = $chemin;
$_SERVER['PHP_SELF'] = $chemin;
$_SERVER['SCRIPT_FILENAME'] = $fichier;

chdir(dirname($fichier));
require $fichier;
