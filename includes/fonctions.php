<?php
/**
 * Fichier contenant les fonctions utilitaires pour l'application
 * 
 * Ces fonctions sont utilisées dans l'ensemble du site
 */

/**
 * Nettoie et valide une chaîne de caractères
 * 
 * @param string $donnee La chaîne à nettoyer
 * @return string La chaîne nettoyée
 */
function nettoyer($donnee) {
    $donnee = trim($donnee);
    $donnee = stripslashes($donnee);
    $donnee = htmlspecialchars($donnee, ENT_QUOTES, 'UTF-8');
    return $donnee;
}

/**
 * Vérifie si un utilisateur est connecté
 * 
 * @return bool Vrai si l'utilisateur est connecté, faux sinon
 */
function estConnecte() {
    return isset($_SESSION['utilisateur_id']) && !empty($_SESSION['utilisateur_id']);
}

/**
 * Vérifie si l'utilisateur est un administrateur
 * 
 * @return bool Vrai si l'utilisateur est administrateur, faux sinon
 */
function estAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Redirige vers une autre page
 * 
 * @param string $url L'URL de destination
 * @return void
 */
function rediriger($url) {
    header("Location: $url");
    exit();
}

/**
 * Génère une classe CSS pour les messages d'alerte Bootstrap
 * 
 * @param string $type Le type de message (success, error, info, warning)
 * @return string La classe CSS Bootstrap correspondante
 */
function getClasseAlerte($type) {
    switch ($type) {
        case 'success':
            return 'alert-success';
        case 'error':
            return 'alert-danger';
        case 'info':
            return 'alert-info';
        case 'warning':
            return 'alert-warning';
        default:
            return 'alert-info';
    }
}

/**
 * Formate une date au format français
 * 
 * @param string $date La date à formater (au format MySQL)
 * @return string La date formatée
 */
function formaterDate($date) {
    $timestamp = strtotime($date);
    return date('d/m/Y', $timestamp);
}

/**
 * Génère un slug à partir d'une chaîne de caractères
 * 
 * @param string $texte Le texte à transformer en slug
 * @return string Le slug généré
 */
function genererSlug($texte) {
    // Conversion en minuscules et remplacement des caractères spéciaux
    $texte = mb_strtolower($texte, 'UTF-8');
    $texte = str_replace(
        ['à','á','â','ã','ä','ç','è','é','ê','ë','ì','í','î','ï','ñ','ò','ó','ô','õ','ö','ù','ú','û','ü','ý','ÿ'],
        ['a','a','a','a','a','c','e','e','e','e','i','i','i','i','n','o','o','o','o','o','u','u','u','u','y','y'],
        $texte
    );
    
    // Remplacement des espaces par des tirets et suppression des caractères non alphanumériques
    $texte = preg_replace('/[^a-z0-9\-]/', '-', $texte);
    $texte = preg_replace('/-+/', '-', $texte);
    
    // Suppression des tirets en début et fin de chaîne
    return trim($texte, '-');
}

/**
 * Vérifier si une adresse email est valide
 * 
 * @param string $email L'adresse email à vérifier
 * @return bool Vrai si l'email est valide, faux sinon
 */
function estEmailValide($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Vérifier si un numéro de téléphone français est valide
 * 
 * @param string $telephone Le numéro à vérifier
 * @return bool Vrai si le téléphone est valide, faux sinon
 */
function estTelephoneValide($telephone) {
    // Supprimer les espaces et les éventuels +221
    $telephone = str_replace([' ', '+221'], '', $telephone);
    // Doit commencer par 7X et avoir 9 chiffres
    return preg_match('/^(77|78|76|70|75)[0-9]{7}$/', $telephone);
}

/**
 * Obtient le nombre de membres ayant un statut spécifique
 * 
 * @param mysqli $mysqli Instance de connexion MySQLi
 * @param string $statut Le statut recherché
 * @return int Le nombre de membres
 */
function getNombreMembresParStatut($mysqli, $statut) {
    $stmt = $mysqli->prepare("SELECT COUNT(*) FROM membres WHERE statut = ?");
    $stmt->bind_param("s", $statut);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_row()[0];
}

/**
 * Tronque un texte à une longueur donnée
 * 
 * @param string $texte Le texte à tronquer
 * @param int $longueur La longueur maximale
 * @return string Le texte tronqué
 */
function tronquerTexte($texte, $longueur = 150) {
    if (strlen($texte) <= $longueur) {
        return $texte;
    }
    
    $texte = substr($texte, 0, $longueur);
    $position = strrpos($texte, ' ');
    
    if ($position !== false) {
        $texte = substr($texte, 0, $position);
    }
    
    return $texte . '...';
}
?>
