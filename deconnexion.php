<?php
/**
 * Page de déconnexion
 * 
 * Déconnecte l'utilisateur et le redirige vers la page d'accueil
 */

// Démarrer la session
session_start();

// Détruire toutes les variables de session
$_SESSION = array();

// Détruire la session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// Définir un message de confirmation
session_start();
$_SESSION['message'] = "Vous avez été déconnecté avec succès.";
$_SESSION['type_message'] = "success";

// Rediriger vers la page d'accueil
header("Location: index.php");
exit();
?>
