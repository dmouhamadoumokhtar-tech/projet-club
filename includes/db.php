<?php


// Paramètres de connexion à la base de données (supporte Railway MySQL / variables d'environnement)
$mysql_url = getenv('MYSQL_URL') ?: getenv('MYSQL_PUBLIC_URL') ?: getenv('DATABASE_URL');
if ($mysql_url && ($parts = parse_url($mysql_url)) && isset($parts['host'])) {
    $hote = $parts['host'];
    $utilisateur = isset($parts['user']) ? urldecode($parts['user']) : 'root';
    $motdepasse = isset($parts['pass']) ? urldecode($parts['pass']) : '';
    $base = isset($parts['path']) ? ltrim($parts['path'], '/') : 'railway';
    $port = isset($parts['port']) ? (int)$parts['port'] : 3306;
} else {
    $hote = getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: 'localhost';          // Hôte de la base de données
    $base = getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: 'club_scientifique';  // Nom de la base de données
    $utilisateur = getenv('MYSQLUSER') ?: getenv('DB_USER') ?: 'club_sicentifique';        // Nom d'utilisateur MySQL
    $motdepasse = getenv('MYSQLPASSWORD') !== false ? getenv('MYSQLPASSWORD') : (getenv('DB_PASS') !== false ? getenv('DB_PASS') : 'A1956');           
    $port = (int)(getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: 3306);
}
// Tentative de connexion à la base de données
try {
    // Création de l'objet MySQLi pour la connexion
    $mysqli = new mysqli($hote, $utilisateur, $motdepasse, $base, $port);
    
    // Vérification des erreurs de connexion
    if ($mysqli->connect_errno) {
        throw new Exception("Erreur connexion  : " . $mysqli->connect_error);
    }
    
    // Définir le jeu de caractères à UTF-8
    $mysqli->set_charset("utf8mb4");

    // Initialisation / mise à jour automatique de la base avec le dump complet 2026-11-07
    $needsImport = false;
    $check = $mysqli->query("SHOW TABLES LIKE 'galerie'");
    if (!$check || $check->num_rows === 0) {
        $needsImport = true;
    } else {
        $countRes = $mysqli->query("SELECT COUNT(*) AS cnt FROM galerie");
        if ($countRes) {
            $row = $countRes->fetch_assoc();
            if ((int)$row['cnt'] < 50) {
                $needsImport = true;
            }
        }
    }
    if ($needsImport) {
        $sqlFile = __DIR__ . '/../sauvegarde.sql';
        if (file_exists($sqlFile)) {
            $sql = file_get_contents($sqlFile);
            if ($mysqli->multi_query($sql)) {
                do {
                    if ($res = $mysqli->store_result()) {
                        $res->free();
                    }
                } while ($mysqli->more_results() && $mysqli->next_result());
            }
        }
    }
    
} catch (Exception $e) {
    die("Erreur connexion à la base de données : " . $e->getMessage());
}
?>
