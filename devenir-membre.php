<?php
/**
 * Page d'inscription pour devenir membre du club
 * 
 * Formulaire permettant aux visiteurs de candidater pour rejoindre le club
 */

// Inclure les fichiers nécessaires
require_once 'includes/db.php';
require_once 'includes/fonctions.php';

// Démarrer la session
session_start();

// Rediriger si l'utilisateur est déjà connecté
if (estConnecte()) {
    // Message d'information
    $_SESSION['message'] = "Vous êtes déjà membre du club.";
    $_SESSION['type_message'] = "info";
    
    // Rediriger vers la page d'accueil
    rediriger('index.php');
}

// Initialiser les variables
$nom = $prenom = $email = $telephone = $classe = $serie = '';
$erreurs = [];

// Traitement du formulaire si soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer et nettoyer les données du formulaire
    $nom = nettoyer($_POST['nom'] ?? '');
    $prenom = nettoyer($_POST['prenom'] ?? '');
    $email = nettoyer($_POST['email'] ?? '');
    $telephone = nettoyer($_POST['telephone'] ?? '');
    $classe = nettoyer($_POST['classe'] ?? '');
    $serie = nettoyer($_POST['serie'] ?? '');
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';
    $confirmation_mot_de_passe = $_POST['confirmation_mot_de_passe'] ?? '';
    
    // Validation
    if (empty($nom)) {
        $erreurs['nom'] = "Le nom est obligatoire";
    } elseif (!preg_match("/^[a-zA-ZÀ-ÿ\-\s]+$/", $nom)) {
        $erreurs['nom'] = "Le nom contient des caractères non autorisés";
    }
    
    if (empty($prenom)) {
        $erreurs['prenom'] = "Le prénom est obligatoire";
    } elseif (!preg_match("/^[a-zA-ZÀ-ÿ\-\s]+$/", $prenom)) {
        $erreurs['prenom'] = "Le prénom contient des caractères non autorisés";
    }
    
    if (empty($telephone)) {
        $erreurs['telephone'] = "Le numéro de téléphone est obligatoire";
    } elseif (!estTelephoneValide($telephone)) {
        $erreurs['telephone'] = "Le numéro de téléphone n'est pas valide";
    }
    
    if (empty($classe)) {
        $erreurs['classe'] = "La classe est obligatoire";
    }
    
    if (empty($serie)) {
        $erreurs['serie'] = "La série est obligatoire";
    }
    
    if (empty($mot_de_passe)) {
        $erreurs['mot_de_passe'] = "Le mot de passe est obligatoire";
    } elseif (strlen($mot_de_passe) < 8) {
        $erreurs['mot_de_passe'] = "Le mot de passe doit contenir au moins 8 caractères";
    }
    
    if ($mot_de_passe !== $confirmation_mot_de_passe) {
        $erreurs['confirmation_mot_de_passe'] = "Les mots de passe ne correspondent pas";
    }
    
    // Email facultatif
    if (!empty($email) && !estEmailValide($email)) {
        $erreurs['email'] = "L'email n'est pas valide";
    }
    
    // Si pas d'erreurs, enregistrer dans la base de données
    if (empty($erreurs)) {
        // Vérifier si l'email existe déjà
        if (!empty($email)) {
            $stmt = $mysqli->prepare("SELECT id FROM membres WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $erreurs['email'] = "Cette adresse email est déjà utilisée";
            }
        }

        // Vérifier si le numéro de téléphone existe déjà
        $stmt = $mysqli->prepare("SELECT id FROM membres WHERE telephone = ?");
        $stmt->bind_param("s", $telephone);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $erreurs['telephone'] = "Ce numéro de téléphone est déjà utilisé";
        }

        // Si toujours pas d'erreurs, procéder à l'insertion
        if (empty($erreurs)) {
            // Hacher le mot de passe
            $mot_de_passe_hache = password_hash($mot_de_passe, PASSWORD_DEFAULT);
            
            try {
                // Préparer et exécuter la requête d'insertion
                $stmt = $mysqli->prepare("
                    INSERT INTO membres (nom, prenom, email, telephone, classe, serie, mot_de_passe, statut, date_candidature)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'en_attente', NOW())
                ");
                
                $stmt->bind_param("sssssss", $nom, $prenom, $email, $telephone, $classe, $serie, $mot_de_passe_hache);
                $stmt->execute();
                
                // Message de succès
                $_SESSION['message'] = "Votre candidature a été enregistrée avec succès. Nous l'examinerons dans les plus brefs délais.<br><strong>Pour devenir membre à part entière, vous devez régler 500 F CFA auprès du trésorier du club (78 406 53 00 ou 76 863 96 18 ou 76 554 49 94).</strong>";
                $_SESSION['type_message'] = "success";
                
                // Rediriger vers la page d'accueil
                rediriger('index.php');
            } catch (Exception $e) {
                $erreurs['general'] = "Une erreur est survenue lors de l'enregistrement. Veuillez réessayer.";
                // En développement, on pourrait afficher : $e->getMessage()
            }
        }
    }
}

// Titre de la page pour l'affichage dans l'en-tête
$titre_page = "Devenir membre";

// Inclure l'en-tête
require_once 'includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center mb-5 pb-5" style="margin-top: 50px;">
    <div class="col-lg-8">
        <div class="text-center mb-5">
            <h1 class="page-title-glass mb-3">Rejoindre le Club</h1>
            <p class="text-muted lead">Un espace d'exploration, de découverte et d'innovation</p>
        </div>

        <div class="card-glass shadow-lg border-0 overflow-hidden mb-5">
            <div class="card-body p-4 p-md-5">
                <div class="mb-5 text-center">  
                    <p class="text-white mb-4">Vous souhaitez participer à des projets scientifiques passionnants ? Complétez ce formulaire pour candidater !</p>
                    
                    <div class="alert glass-alert text-start mb-3">
                        <div class="d-flex">
                            <i class="fas fa-info-circle fs-4 me-3 mt-1 text-accent"></i>
                            <div>
                                <strong>Processus :</strong> Votre candidature sera examinée par l'administration. Vous recevrez une notification par email une fois validé.
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert glass-alert text-start" style="border-left: 4px solid #ffc107;">
                        <div class="d-flex">
                            <i class="fas fa-coins fs-4 me-3 mt-1 text-warning"></i>
                            <div>
                                <strong>Cotisation :</strong> Pour finaliser votre adhésion, une cotisation de <strong>500 F CFA</strong> est requise auprès du trésorier.<br>
                                <span class="small text-muted mt-1 d-block">Contacts Trésorerie : 78 406 53 00 / 76 863 96 18 / 76 554 49 94</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if (isset($erreurs['general'])): ?>
                    <div class="alert glass-alert text-center mb-4 text-danger border-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $erreurs['general']; ?>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="" id="formulaire-membre">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label for="prenom" class="form-label text-accent text-uppercase fw-bold">Prénom *</label>
                            <input type="text" class="form-control form-control-glass" id="prenom" name="prenom" value="<?php echo htmlspecialchars($prenom); ?>" required placeholder="Ex: Moussa">
                            <div class="invalid-feedback">
                                <?php echo $erreurs['prenom'] ?? ''; ?>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-4">
                            <label for="nom" class="form-label text-accent text-uppercase fw-bold">Nom *</label>
                            <input type="text" class="form-control form-control-glass" id="nom" name="nom" value="<?php echo htmlspecialchars($nom); ?>" required placeholder="Ex: Diop">
                            <div class="invalid-feedback">
                                <?php echo $erreurs['nom'] ?? ''; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="email" class="form-label text-accent text-uppercase fw-bold">Adresse email</label>
                        <div class="input-group">
                            <span class="input-group-text bg-transparent border-end-0 text-accent" style="border-color: rgba(255,255,255,0.1);"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control form-control-glass border-start-0 ps-0 <?php echo isset($erreurs['email']) ? 'is-invalid' : ''; ?>" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="exemple@gmail.com">
                        </div>
                        <div class="invalid-feedback d-block">
                            <?php echo $erreurs['email'] ?? ''; ?>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="telephone" class="form-label text-accent text-uppercase fw-bold">Téléphone *</label>
                        <div class="input-group">
                            <span class="input-group-text bg-transparent border-end-0 text-accent" style="border-color: rgba(255,255,255,0.1);"><i class="fas fa-phone"></i></span>
                            <input type="tel" class="form-control form-control-glass border-start-0 ps-0 <?php echo isset($erreurs['telephone']) ? 'is-invalid' : ''; ?>" id="telephone" name="telephone" value="<?php echo htmlspecialchars($telephone); ?>" placeholder="77 123 45 67" required>
                        </div>
                        <div class="invalid-feedback d-block">
                            <?php echo $erreurs['telephone'] ?? ''; ?>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label for="classe" class="form-label text-accent text-uppercase fw-bold">Classe *</label>
                            <select class="form-select form-control-glass <?php echo isset($erreurs['classe']) ? 'is-invalid' : ''; ?>" id="classe" name="classe" required>
                                <option value="" <?php echo $classe === '' ? 'selected' : ''; ?> class="bg-dark">Sélectionnez votre classe</option>
                                <option value="Seconde" <?php echo $classe === 'Seconde' ? 'selected' : ''; ?> class="bg-dark">Seconde</option>
                                <option value="Première" <?php echo $classe === 'Première' ? 'selected' : ''; ?> class="bg-dark">Première</option>
                                <option value="Terminal" <?php echo $classe === 'Terminale' ? 'selected' : ''; ?> class="bg-dark">Terminale</option>
                                <option value="Supérieur" <?php echo $classe === 'Supérieur' ? 'selected' : ''; ?> class="bg-dark">Supérieur</option>
                            </select>
                            <div class="invalid-feedback">
                                <?php echo $erreurs['classe'] ?? ''; ?>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-4">
                            <label for="serie" class="form-label text-accent text-uppercase fw-bold">Série *</label>
                            <select class="form-select form-control-glass <?php echo isset($erreurs['serie']) ? 'is-invalid' : ''; ?>" id="serie" name="serie" required>
                                <option value="" <?php echo $serie === '' ? 'selected' : ''; ?> class="bg-dark">Sélectionnez votre série</option>
                                <option value="S" <?php echo $serie === 'S' ? 'selected' : ''; ?> class="bg-dark">S</option>
                                <option value="S1" <?php echo $serie === 'S1' ? 'selected' : ''; ?> class="bg-dark">S1</option>
                                <option value="S2" <?php echo $serie === 'S2' ? 'selected' : ''; ?> class="bg-dark">S2</option>
                                <option value="Autre" <?php echo $serie === 'Autre' ? 'selected' : ''; ?> class="bg-dark">Autre</option>
                            </select>
                            <div class="invalid-feedback">
                                <?php echo $erreurs['serie'] ?? ''; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label for="mot_de_passe" class="form-label text-accent text-uppercase fw-bold">Mot de passe *</label>
                            <div class="input-group">
                                <span class="input-group-text bg-transparent border-end-0 text-accent" style="border-color: rgba(255,255,255,0.1);"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control form-control-glass border-start-0 ps-0 <?php echo isset($erreurs['mot_de_passe']) ? 'is-invalid' : ''; ?>" id="mot_de_passe" name="mot_de_passe" required placeholder="8 caractères min.">
                            </div>
                            <div class="invalid-feedback d-block">
                                <?php echo $erreurs['mot_de_passe'] ?? ''; ?>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-4">
                            <label for="confirmation_mot_de_passe" class="form-label text-accent text-uppercase fw-bold">Confirmation *</label>
                            <div class="input-group">
                                <span class="input-group-text bg-transparent border-end-0 text-accent" style="border-color: rgba(255,255,255,0.1);"><i class="fas fa-check-double"></i></span>
                                <input type="password" class="form-control form-control-glass border-start-0 ps-0 <?php echo isset($erreurs['confirmation_mot_de_passe']) ? 'is-invalid' : ''; ?>" id="confirmation_mot_de_passe" name="confirmation_mot_de_passe" required placeholder="Répétez le mot de passe">
                            </div>
                            <div class="invalid-feedback d-block">
                                <?php echo $erreurs['confirmation_mot_de_passe'] ?? ''; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-accent btn-lg rounded-pill py-3">
                            <i class="fas fa-paper-plane me-2"></i> Envoyer ma candidature
                        </button>
                    </div>
                </form>
            </div>
        </div>
</div>
</div>

<!-- Spacer pour garantir que le footer ne monte pas sur le contenu -->
<div style="height: 100px;"></div>

<?php
// Inclure le pied de page
require_once 'includes/footer.php';
?>
