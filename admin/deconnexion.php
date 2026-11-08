<?php
require_once __DIR__ . '/../includes/db_connexion.php';
require_once __DIR__ . '/../includes/session_db.php';
session_start();
// Détruire toutes les variables de session
$_SESSION = [];
// Détruire la session
session_destroy();
// Rediriger vers la page de connexion admin
header('Location: connexion.php');
exit;
?>
