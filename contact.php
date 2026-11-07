<?php
/**
 * Page de contact
 * 
 * Formulaire permettant aux visiteurs de contacter le club
 */

// Inclure les fichiers nécessaires
require_once 'includes/db.php';
require_once 'includes/fonctions.php';

// Démarrer la session
session_start();

// Initialiser les variables
$nom = $email = $objet = $message = '';
$erreurs = [];

// Traitement du formulaire si soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer et nettoyer les données du formulaire
    $nom = nettoyer($_POST['nom'] ?? '');
    $email = nettoyer($_POST['email'] ?? '');
    $objet = nettoyer($_POST['objet'] ?? '');
    $message = $_POST['message'] ?? ''; // Ne pas nettoyer le message pour préserver les sauts de ligne
    
    // Validation
    if (empty($nom)) {
        $erreurs['nom'] = "Le nom est obligatoire";
    }
    
    if (empty($email)) {
        $erreurs['email'] = "L'email est obligatoire";
    } elseif (!estEmailValide($email)) {
        $erreurs['email'] = "L'email n'est pas valide";
    }
    
    if (empty($objet)) {
        $erreurs['objet'] = "L'objet est obligatoire";
    }
    
    if (empty($message)) {
        $erreurs['message'] = "Le message est obligatoire";
    }
    
    // Si pas d'erreurs, enregistrer dans la base de données
    if (empty($erreurs)) {
        try {
            // Préparer et exécuter la requête d'insertion
            $stmt = $mysqli->prepare("
                INSERT INTO messages_contact (nom, email, objet, message, lu, date_envoi)
                VALUES (?, ?, ?, ?, 0, NOW())
            ");
            
            $stmt->bind_param("ssss", $nom, $email, $objet, $message);
            $stmt->execute();
            
            // Message de succès
            $_SESSION['message'] = "Votre message a été envoyé avec succès. Nous vous répondrons dans les plus brefs délais.";
            $_SESSION['type_message'] = "success";
            
            // Rediriger pour éviter la re-soumission du formulaire
            rediriger('contact.php');
        } catch (Exception $e) {
            $erreurs['general'] = "Une erreur est survenue lors de l'envoi du message. Veuillez réessayer.";
            // En développement, on pourrait afficher : $e->getMessage()
        }
    }
}

// Titre de la page pour l'affichage dans l'en-tête
$titre_page = "Contact";

// Inclure l'en-tête
require_once 'includes/header.php';
?>

<div class="container py-5" style="margin-top: 100px;">
    <h1 class="page-title-glass mb-5">Nous <span class="text-accent">Contacter</span></h1>

    <div class="row justify-content-center g-4">
        <div class="col-lg-5">
            <div class="card-glass h-100">
                <div class="mb-4 text-center">
                     <i class="fas fa-envelope-open-text text-accent" style="font-size: 3rem;"></i>
                </div>
                <h3 class="h4 text-white mb-4 text-center">Coordonnées</h3>
                <p class="text-secondary mb-4 text-center">Vous avez des questions sur nos activités ? Vous souhaitez en savoir plus sur le Club Scientifique ? N'hésitez pas à nous contacter !</p>
                
                <ul class="list-unstyled mb-0 px-3">
                    <li class="mb-4 d-flex align-items-center">
                        <div class="me-3 text-accent" style="font-size: 1.5rem; width: 40px; text-align: center;"><i class="fas fa-map-marker-alt"></i></div>
                        <div>
                            <strong class="text-white d-block">Adresse</strong>
                            <span class="text-secondary">Nioro du Rip / Kaolack</span>
                        </div>
                    </li>
                    <li class="mb-4 d-flex align-items-center">
                        <div class="me-3 text-accent" style="font-size: 1.5rem; width: 40px; text-align: center;"><i class="fas fa-envelope"></i></div>
                        <div>
                            <strong class="text-white d-block">Email</strong>
                            <span class="text-secondary">contact@cslmdb.sn</span>
                        </div>
                    </li>
                    <li class="d-flex align-items-center">
                        <div class="me-3 text-accent" style="font-size: 1.5rem; width: 40px; text-align: center;"><i class="fas fa-phone"></i></div>
                        <div>
                            <strong class="text-white d-block">Téléphone</strong>
                            <span class="text-secondary">+221 77 588 97 11</span>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="col-lg-7">
            <div class="card-glass h-100">
                <h3 class="h4 text-white mb-4"><i class="fas fa-paper-plane me-2 text-accent"></i>Envoyer un message</h3>
                
                <?php if (isset($erreurs['general'])): ?>
                    <div class="alert alert-danger">
                        <?php echo $erreurs['general']; ?>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nom" class="form-label text-accent text-uppercase fw-bold">Nom complet *</label>
                            <input type="text" class="form-control form-control-glass <?php echo isset($erreurs['nom']) ? 'is-invalid' : ''; ?>" id="nom" name="nom" value="<?php echo htmlspecialchars($nom); ?>" required placeholder="Votre nom">
                            <div class="invalid-feedback">
                                <?php echo $erreurs['nom'] ?? ''; ?>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label text-accent text-uppercase fw-bold">Email *</label>
                            <input type="email" class="form-control form-control-glass <?php echo isset($erreurs['email']) ? 'is-invalid' : ''; ?>" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required placeholder="votre@email.com">
                            <div class="invalid-feedback">
                                <?php echo $erreurs['email'] ?? ''; ?>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="objet" class="form-label text-accent text-uppercase fw-bold">Objet *</label>
                        <input type="text" class="form-control form-control-glass <?php echo isset($erreurs['objet']) ? 'is-invalid' : ''; ?>" id="objet" name="objet" value="<?php echo htmlspecialchars($objet); ?>" required placeholder="Sujet de votre message">
                        <div class="invalid-feedback">
                            <?php echo $erreurs['objet'] ?? ''; ?>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="message" class="form-label text-accent text-uppercase fw-bold">Message *</label>
                        <textarea class="form-control form-control-glass <?php echo isset($erreurs['message']) ? 'is-invalid' : ''; ?>" id="message" name="message" rows="5" required placeholder="Votre message ici..."><?php echo htmlspecialchars($message); ?></textarea>
                        <div class="invalid-feedback">
                            <?php echo $erreurs['message'] ?? ''; ?>
                        </div>
                    </div>
                    <p class="text-secondary small mb-4">* Champs obligatoires</p>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-accent btn-lg">Envoyer le message</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Inclure le pied de page
require_once 'includes/footer.php';
?>
