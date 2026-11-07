<?php
/**
 * Page de connexion
 * 
 * Permet aux utilisateurs de se connecter au site
 */

// Inclure les fichiers nécessaires
require_once 'includes/db.php';
require_once 'includes/fonctions.php';

// Démarrer la session
session_start();

// Rediriger si l'utilisateur est déjà connecté
if (estConnecte()) {
    rediriger('index.php');
}

// Initialiser les variables
$email = '';
$erreur = '';

// Traitement du formulaire si soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer et nettoyer les données du formulaire
    $identifiant = nettoyer($_POST['identifiant'] ?? '');
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';
    
    // Validation de base
    if (empty($identifiant) || empty($mot_de_passe)) {
        $erreur = "Tous les champs sont obligatoires.";
    } else {
        // Préparer la requête pour chercher par email OU téléphone
        $stmt = $mysqli->prepare("SELECT * FROM membres WHERE (email = ? OR telephone = ?) LIMIT 1");
        $stmt->bind_param("ss", $identifiant, $identifiant);
        $stmt->execute();
        $result = $stmt->get_result();
        $membre = $result->fetch_assoc();

        if ($membre && password_verify($mot_de_passe, $membre['mot_de_passe'])) {
            // Connexion OK
            $_SESSION['utilisateur_id'] = $membre['id'];
            $_SESSION['role'] = (isset($membre['role']) && $membre['role'] === 'admin') ? 'admin' : 'membre';
            // Rediriger selon le rôle
            rediriger('index.php');
        } else {
            $erreur_connexion = "Identifiant ou mot de passe incorrect.";
        }
    }
}

// Titre de la page pour l'affichage dans l'en-tête
$titre_page = "Connexion";

// Inclure l'en-tête
require_once 'includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center mb-5 pb-5" style="margin-top: 50px;">
    <div class="col-lg-5 col-md-7">
        <div class="text-center mb-4">
            <h1 class="page-title-glass mb-3">Connexion</h1>
            <p class="text-muted">Accédez à votre espace scientifique</p>
        </div>

        <div class="card-glass shadow-lg border-0 overflow-hidden">
            <div class="card-body p-4 p-md-5">
                <?php if (!empty($erreur)): ?>
                    <div class="alert glass-alert text-center mb-4">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $erreur; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($erreur_connexion)): ?>
                    <div class="alert glass-alert text-center mb-4">
                        <i class="fas fa-bug me-2"></i><?php echo $erreur_connexion; ?>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="">
                    <div class="mb-4">
                        <label for="identifiant" class="form-label text-accent text-uppercase fw-bold">Email ou Téléphone</label>
                        <div class="input-group">
                            <span class="input-group-text bg-transparent border-end-0 text-accent" style="border-color: rgba(255,255,255,0.1);">
                                <i class="fas fa-user-astronaut"></i>
                            </span>
                            <input type="text" class="form-control form-control-glass border-start-0 ps-0" id="identifiant" name="identifiant" required placeholder="Votre identifiant">
                        </div>
                    </div>
                    
                    <div class="mb-5">
                        <label for="mot_de_passe" class="form-label text-accent text-uppercase fw-bold">Mot de passe</label>
                        <div class="input-group">
                            <span class="input-group-text bg-transparent border-end-0 text-accent" style="border-color: rgba(255,255,255,0.1);">
                                <i class="fas fa-key"></i>
                            </span>
                            <input type="password" class="form-control form-control-glass border-start-0 ps-0" id="mot_de_passe" name="mot_de_passe" required placeholder="••••••••">
                        </div>
                        <div class="text-end mt-2">
                           <a href="mot-de-passe-oublie.php" class="small text-muted text-decoration-none">Mot de passe oublié ?</a>
                        </div>
                    </div>
                    
                    <div class="d-grid mb-4">
                        <button type="submit" class="btn btn-accent btn-lg rounded-pill">
                            <i class="fas fa-sign-in-alt me-2"></i> Se connecter
                        </button>
                    </div>
                </form>
            </div>
            <div class="card-footer bg-transparent border-top border-secondary text-center py-3">
                <p class="mb-0 text-white">Pas encore membre ? <a href="devenir-membre.php" class="text-accent fw-bold text-decoration-none">Rejoignez la mission</a></p>
            </div>
        </div>
    </div>
</div>

<!-- Spacer pour garantir que le footer ne monte pas sur le contenu sur petit écran -->
<div style="height: 100px;"></div>

<?php
// Inclure le pied de page
require_once 'includes/footer.php';
?>
