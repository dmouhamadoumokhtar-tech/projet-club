<?php


// Paramètres et ouverture de la connexion (Railway MySQL, MySQL local ou TiDB Cloud sur Vercel)
require_once __DIR__ . '/db_connexion.php';

// Tentative de connexion à la base de données
try {
    $mysqli = cslmdb_connexion_mysql();

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
