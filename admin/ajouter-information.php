<?php
/**
 * Ajout d'un nouvel article d'information
 * 
 * Permet aux administrateurs d'ajouter un nouvel article d'information
 */

// Inclure les fichiers nécessaires
require_once '../includes/db.php';
require_once '../includes/stockage.php';
require_once '../includes/fonctions.php';

// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin' || !isset($_SESSION['admin_id'])) {
    $_SESSION['message'] = "Vous devez être connecté en tant qu'administrateur pour accéder à cette page.";
    $_SESSION['type_message'] = "error";
    rediriger('connexion.php');
}

// Initialiser les variables
$titre = $contenu = $document_url = '';
$erreurs = [];

// Traitement du formulaire si soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer et nettoyer les données du formulaire
    $titre = nettoyer($_POST['titre'] ?? '');
    $contenu = $_POST['contenu'] ?? ''; // Ne pas nettoyer le contenu pour préserver les sauts de ligne
    $document_url = nettoyer($_POST['document_url'] ?? '');
    
    // Validation
    if (empty($titre)) {
        $erreurs['titre'] = "Le titre est obligatoire";
    }
    
    if (empty($contenu)) {
        $erreurs['contenu'] = "Le contenu est obligatoire";
    }
    
    // Validation de l'URL du document (si fournie)
    if (!empty($document_url) && !filter_var($document_url, FILTER_VALIDATE_URL)) {
        $erreurs['document_url'] = "L'URL du document n'est pas valide";
    }
    
    // Initialiser la variable image
    $image_nom = null;

    // Traitement de l'image (optionnel)
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/informations/';
        
        // Créer le dossier s'il n'existe pas
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_tmp = $_FILES['image']['tmp_name'];
        $file_name = basename($_FILES['image']['name']);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Extensions autorisées
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($file_ext, $allowed)) {
            // Générer un nom unique
            $new_name = uniqid('info_') . '.' . $file_ext;
            $destination = $upload_dir . $new_name;
            
            $img_stockee = $new_name;
            if (stocker_upload($file_tmp, $destination, $img_stockee)) {
                $image_nom = $img_stockee;
            } else {
                $erreurs['image'] = "Erreur lors du téléchargement de l'image.";
            }
        } else {
            $erreurs['image'] = "Type de fichier non autorisé. (JPG, PNG, GIF, WEBP uniquement)";
        }
    }

    // Si pas d'erreurs, enregistrer dans la base de données
    if (empty($erreurs)) {
        try {
            // Préparer et exécuter la requête d'insertion
            $stmt = $mysqli->prepare("
                INSERT INTO informations (titre, contenu, document_url, image, date_publication)
                VALUES (?, ?, ?, ?, NOW())
            ");
            
            $stmt->bind_param("ssss", $titre, $contenu, $document_url, $image_nom);
            $stmt->execute();
            $new_id = $mysqli->insert_id;

            // --- NOTIFICATION PAR EMAIL ---
            if (isset($_POST['notifier_membres'])) {
                require_once '../includes/config_mail.php';
                require_once '../includes/SMTPMailer.php';
                
                // 1. Récupérer les emails des membres valides
                $res = $mysqli->query("SELECT email FROM membres WHERE statut = 'valide' AND email IS NOT NULL AND email != ''");
                $emails = [];
                while ($row = $res->fetch_assoc()) {
                    $emails[] = $row['email'];
                }

                if (!empty($emails)) {
                    // 2. Configurer le mailer avec les constantes
                    $mailer = new SMTPMailer(SMTP_USER, SMTP_PASS); 

                    // 3. Contenu du mail (Template HTML Stylisé)
                    $sujet = "Nouvelle publication : " . $titre;
                    $lien_article = "https://cslmdb.cybibff.site/informations.php?id=" . $new_id;
                    
                    $message_mail = '
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <meta charset="UTF-8">
                        <meta name="viewport" content="width=device-width, initial-scale=1.0">
                        <title>Nouvelle Information - Club Scientifique</title>
                    </head>
                    <body style="margin: 0; padding: 0; font-family: \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif; background-color: #0a192f; color: #8892b0;">
                        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; margin: 0 auto; background-color: #112240; border-radius: 8px; overflow: hidden; margin-top: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.3);">
                            <!-- Header -->
                            <tr>
                                <td style="padding: 30px 40px; background-color: #0a192f; border-bottom: 2px solid #64ffda; text-align: center;">
                                    <h1 style="color: #ccd6f6; margin: 0; font-size: 24px; letter-spacing: 1px;">Club Scientifique <span style="color: #64ffda;">LMDB</span></h1>
                                </td>
                            </tr>
                            
                            <!-- Body -->
                            <tr>
                                <td style="padding: 40px;">
                                    <h2 style="color: #e6f1ff; font-size: 20px; margin-top: 0;">Nouvelle Publication ??</h2>
                                    <p style="font-size: 16px; line-height: 1.6; margin-bottom: 20px;">Bonjour,</p>
                                    <p style="font-size: 16px; line-height: 1.6; margin-bottom: 30px;">
                                        Un nouvel article d\'information vient d\'être publié sur notre plateforme. Voici un aperçu :
                                    </p>
                                    
                                    <div style="background-color: #0a192f; padding: 20px; border-left: 4px solid #64ffda; border-radius: 4px; margin-bottom: 30px;">
                                        <h3 style="color: #ccd6f6; margin-top: 0; font-size: 18px;">' . htmlspecialchars($titre) . '</h3>
                                        <p style="color: #8892b0; margin-bottom: 0; font-style: italic;">
                                            ' . nl2br(htmlspecialchars(substr($contenu, 0, 150))) . '...
                                        </p>
                                    </div>
                                    
                                    <div style="text-align: center; margin-top: 40px; margin-bottom: 20px;">
                                        <a href="' . $lien_article . '" style="background-color: transparent; color: #64ffda; border: 1px solid #64ffda; padding: 12px 25px; text-decoration: none; border-radius: 4px; font-weight: bold; display: inline-block; transition: all 0.3s;">
                                            Lire l\'article complet
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            
                            <!-- Footer -->
                            <tr>
                                <td style="padding: 30px; background-color: #0a192f; text-align: center; font-size: 12px; color: #495670; border-top: 1px solid #233554;">
                                    <p style="margin: 0 0 10px 0;">Club Scientifique Lycée Maba Diakhou Ba</p>
                                    <p style="margin: 0;">Ceci est un message automatique, merci de ne pas y répondre.</p>
                                </td>
                            </tr>
                        </table>
                    </body>
                    </html>
                    ';

                    // 4. Envoi individuel
                    $headers = [
                        'From' => SMTP_FROM . ' <' . SMTP_USER . '>'
                    ];
                    
                    foreach ($emails as $email_destinataire) {
                        $mailer->send($email_destinataire, $sujet, $message_mail, $headers);
                        usleep(100000); // 0.1s
                    }
                }
            }
            
            // Message de succès
            $_SESSION['message'] = "L'article d'information a été ajouté avec succès.";
            if (isset($_POST['notifier_membres']) && !empty($emails)) {
                 $_SESSION['message'] .= " (" . count($emails) . " membres notifiés)";
            }
            $_SESSION['type_message'] = "success";
            
            // Rediriger vers la liste des informations
            rediriger('gestion-informations.php');
        } catch (Exception $e) {
            $erreurs['general'] = "Une erreur est survenue : " . $e->getMessage();
        }
    }
}

// Titre de la page pour l'affichage dans l'en-tête
$titre_page = "Ajouter une information";

// Inclure l'en-tête
require_once '../includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h2 mb-0">Ajouter un article d'information</h1>
    <a href="gestion-informations.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> Retour à la liste des informations
    </a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <?php if (isset($erreurs['general'])): ?>
            <div class="alert alert-danger">
                <?php echo $erreurs['general']; ?>
            </div>
        <?php endif; ?>
        
        <form method="post" action="" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="titre" class="form-label">Titre de l'article *</label>
                <input type="text" id="titre" name="titre" value="<?php echo htmlspecialchars($titre); ?>" class="form-control <?php echo isset($erreurs['titre']) ? 'is-invalid' : ''; ?>">
                <?php if (isset($erreurs['titre'])): ?>
                    <div class="invalid-feedback">
                        <?php echo $erreurs['titre']; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="mb-3">
                <label for="contenu" class="form-label">Contenu de l'article *</label>
                <textarea id="contenu" name="contenu" rows="10" class="form-control <?php echo isset($erreurs['contenu']) ? 'is-invalid' : ''; ?>"><?php echo htmlspecialchars($contenu); ?></textarea>
                <div class="form-text">Utilisez des sauts de ligne pour structurer votre texte.</div>
                <?php if (isset($erreurs['contenu'])): ?>
                    <div class="invalid-feedback">
                        <?php echo $erreurs['contenu']; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="mb-3">
                <label for="document_url" class="form-label">URL du document (optionnel)</label>
                <input type="text" id="document_url" name="document_url" value="<?php echo htmlspecialchars($document_url); ?>" class="form-control <?php echo isset($erreurs['document_url']) ? 'is-invalid' : ''; ?>" placeholder="https://exemple.com/document.pdf">
                <div class="form-text">Lien vers un document PDF ou une ressource externe.</div>
                <?php if (isset($erreurs['document_url'])): ?>
                    <div class="invalid-feedback">
                        <?php echo $erreurs['document_url']; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="mb-3">
                <label for="image" class="form-label">Image d'illustration (optionnel)</label>
                <input type="file" id="image" name="image" class="form-control <?php echo isset($erreurs['image']) ? 'is-invalid' : ''; ?>" accept="image/*">
                <div class="form-text">Formats acceptés : JPG, PNG, GIF, WEBP.</div>
                <?php if (isset($erreurs['image'])): ?>
                    <div class="invalid-feedback">
                        <?php echo $erreurs['image']; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="notifier_membres" name="notifier_membres" checked>
                <label class="form-check-label" for="notifier_membres">
                    <i class="fas fa-bell me-1 text-warning"></i> Notifier tous les membres par email
                </label>
                <div class="form-text">Si coché, un email sera envoyé à tous les membres validés.</div>
            </div>
            
            <div class="mb-3">
                <div class="form-text">* Champs obligatoires</div>
            </div>
            
            <div>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-plus me-1"></i> Ajouter l'article
                </button>
            </div>
        </form>
    </div>
</div>

<?php
// Inclure le pied de page
require_once '../includes/footer.php';
?>
