<?php
/**
 * Page de réinitialisation de mot de passe
 */

require_once 'includes/db.php';
require_once 'includes/fonctions.php';

session_start();

$token = $_GET['token'] ?? '';
$erreur = '';
$succes = '';
$token_valide = false;
$email_associe = '';

// Vérification du token
if (empty($token)) {
    $erreur = "Jeton de réinitialisation manquant.";
} else {
    $stmt = $mysqli->prepare("SELECT email, created_at FROM password_resets WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $date_creation = strtotime($row['created_at']);
        $maintenant = time();
        
        // Validité de 24 heures (86400 secondes)
        if ($maintenant - $date_creation < 86400) {
            $token_valide = true;
            $email_associe = $row['email'];
        } else {
            $erreur = "Le lien de réinitialisation a expiré. Veuillez refaire une demande.";
        }
    } else {
        $erreur = "Jeton de réinitialisation invalide.";
    }
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_valide) {
    $nouveau_mdp = $_POST['nouveau_mdp'] ?? '';
    $confirmer_mdp = $_POST['confirmer_mdp'] ?? '';
    
    if (strlen($nouveau_mdp) < 8) {
        $erreur = "Le mot de passe doit contenir au moins 8 caractères.";
    } elseif ($nouveau_mdp !== $confirmer_mdp) {
        $erreur = "Les mots de passe ne correspondent pas.";
    } else {
        // Hachage
        $mdp_hash = password_hash($nouveau_mdp, PASSWORD_DEFAULT);
        
        // Mise à jour (Admin ou Membre)
        $update_ok = false;
        
        // Essai Admins
        $stmt = $mysqli->prepare("UPDATE admins SET mot_de_passe = ? WHERE email = ?");
        $stmt->bind_param("ss", $mdp_hash, $email_associe);
        $stmt->execute();
        if ($stmt->affected_rows > 0) $update_ok = true;
        
        // Essai Membres (si pas admin ou si email existe aussi chez membres - peu probable mais possible sécu)
        // On met à jour partout où l'email matche pour être sûr
        $stmt = $mysqli->prepare("UPDATE membres SET mot_de_passe = ? WHERE email = ?");
        $stmt->bind_param("ss", $mdp_hash, $email_associe);
        $stmt->execute();
        if ($stmt->affected_rows > 0) $update_ok = true;
        
        if ($update_ok) {
            // Supprimer le token
            $stmt = $mysqli->prepare("DELETE FROM password_resets WHERE email = ?");
            $stmt->bind_param("s", $email_associe);
            $stmt->execute();
            
            $succes = "Votre mot de passe a été réinitialisé avec succès !";
            $token_valide = false; // Pour cacher le formulaire
        } else {
            // Cas bizarre où l'email n'est plus dans les tables users mais était dans password_resets
            $erreur = "Impossible de mettre à jour le compte. L'adresse email n'existe plus.";
        }
    }
}

$titre_page = "Nouveau mot de passe";
require_once 'includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center mb-5 pb-5" style="margin-top: 50px;">
        <div class="col-lg-5 col-md-7">
            <div class="text-center mb-4">
                <h1 class="page-title-glass mb-3">Sécurité</h1>
                <p class="text-muted">Définissez votre nouveau mot de passe</p>
            </div>

            <div class="card-glass shadow-lg border-0 overflow-hidden">
                <div class="card-body p-4 p-md-5">
                    <?php if (!empty($erreur)): ?>
                        <div class="alert alert-danger glass-alert text-center mb-4">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $erreur; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($succes)): ?>
                        <div class="alert alert-success glass-alert text-center mb-4">
                            <i class="fas fa-check-circle me-2"></i><?php echo $succes; ?>
                        </div>
                        <div class="d-grid">
                            <a href="connexion.php" class="btn btn-accent btn-lg rounded-pill">
                                <i class="fas fa-sign-in-alt me-2"></i> Se connecter
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($token_valide && empty($succes)): ?>
                    <form method="post" action="">
                        <div class="mb-4">
                            <label for="nouveau_mdp" class="form-label text-accent text-uppercase fw-bold">Nouveau Mot de passe</label>
                            <div class="input-group">
                                <span class="input-group-text bg-transparent border-end-0 text-accent" style="border-color: rgba(255,255,255,0.1);">
                                    <i class="fas fa-key"></i>
                                </span>
                                <input type="password" class="form-control form-control-glass border-start-0 ps-0" id="nouveau_mdp" name="nouveau_mdp" required placeholder="Minimum 8 caractères">
                            </div>
                        </div>
                        
                        <div class="mb-5">
                            <label for="confirmer_mdp" class="form-label text-accent text-uppercase fw-bold">Confirmer Mot de passe</label>
                            <div class="input-group">
                                <span class="input-group-text bg-transparent border-end-0 text-accent" style="border-color: rgba(255,255,255,0.1);">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password" class="form-control form-control-glass border-start-0 ps-0" id="confirmer_mdp" name="confirmer_mdp" required placeholder="Répéter le mot de passe">
                            </div>
                        </div>
                        
                        <div class="d-grid mb-4">
                            <button type="submit" class="btn btn-accent btn-lg rounded-pill">
                                <i class="fas fa-save me-2"></i> Enregistrer
                            </button>
                        </div>
                    </form>
                    <?php endif; ?>
                    
                    <?php if (!$token_valide && empty($succes)): ?>
                        <div class="text-center mt-3">
                            <a href="mot-de-passe-oublie.php" class="text-accent fw-bold text-decoration-none">Refaire une demande</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div style="height: 100px;"></div>

<?php
require_once 'includes/footer.php';
?>
