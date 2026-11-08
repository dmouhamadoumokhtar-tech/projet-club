<?php
/**
 * Modification d'un article d'information existant
 * 
 * Permet aux administrateurs de modifier un article d'information
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

// Vérifier si un ID est fourni
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    // Message d'erreur
    $_SESSION['message'] = "ID d'article invalide.";
    $_SESSION['type_message'] = "error";
    
    // Rediriger vers la liste des informations
    rediriger('gestion-informations.php');
}

// Initialiser les variables
$titre = $contenu = $document_url = '';
$erreurs = [];

// Récupérer les données de l'article
try {
    $stmt = $mysqli->prepare("SELECT * FROM informations WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $information = $result->fetch_assoc();
    
    if (!$information) {
        // Si l'article n'existe pas
        $_SESSION['message'] = "L'article demandé n'existe pas.";
        $_SESSION['type_message'] = "error";
        
        // Rediriger vers la liste des informations
        rediriger('gestion-informations.php');
    }
    
    // Pré-remplir les champs avec les données existantes
    $titre = $information['titre'];
    $contenu = $information['contenu'];
    $document_url = $information['document_url'];
    
} catch (Exception $e) {
    $_SESSION['message'] = "Une erreur est survenue lors de la récupération des données de l'article.";
    $_SESSION['type_message'] = "error";
    
    // Rediriger vers la liste des informations
    rediriger('gestion-informations.php');
}

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
    
    // Initialiser la variable image avec l'ancienne valeur
    $image_nom = $information['image'] ?? null;

    // Traitement de la nouvelle image (optionnel)
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
            
            if (move_uploaded_file($file_tmp, $destination)) {
                // Supprimer l'ancienne image si elle existe
                if ($image_nom && file_exists($upload_dir . $image_nom)) {
                    unlink($upload_dir . $image_nom);
                }
                $image_nom = $new_name;
            } else {
                $erreurs['image'] = "Erreur lors du téléchargement de l'image.";
            }
        } else {
            $erreurs['image'] = "Type de fichier non autorisé. (JPG, PNG, GIF, WEBP uniquement)";
        }
    }

    // Si pas d'erreurs, mettre à jour dans la base de données
    if (empty($erreurs)) {
        try {
            // Préparer et exécuter la requête de mise à jour
            $stmt = $mysqli->prepare("
                UPDATE informations 
                SET titre = ?, contenu = ?, document_url = ?, image = ? 
                WHERE id = ?
            ");
            
            $stmt->bind_param("ssssi", $titre, $contenu, $document_url, $image_nom, $id);
            $stmt->execute();
            
            // Message de succès
            $_SESSION['message'] = "L'article a été mis à jour avec succès.";
            $_SESSION['type_message'] = "success";
            
            // Rediriger vers la liste des informations
            rediriger('gestion-informations.php');
        } catch (Exception $e) {
            $erreurs['general'] = "Une erreur est survenue lors de la mise à jour de l'article. Veuillez réessayer.";
            // En environnement de développement, on pourrait afficher : $e->getMessage()
        }
    }
}

// Titre de la page pour l'affichage dans l'en-tête
$titre_page = "Modifier une information";

// Inclure l'en-tête
require_once '../includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h2 mb-0">Modifier un article d'information</h1>
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
                <?php if (!empty($information['image'])): ?>
                    <div class="mb-2">
                        <img src="<?php echo htmlspecialchars(media_url('informations', $information['image'], '../')); ?>" alt="Image actuelle" class="img-thumbnail" style="max-height: 150px;">
                        <div class="form-text text-muted">Laissez vide pour conserver l'image actuelle.</div>
                    </div>
                <?php endif; ?>
                <input type="file" id="image" name="image" class="form-control <?php echo isset($erreurs['image']) ? 'is-invalid' : ''; ?>" accept="image/*">
                <div class="form-text">Formats acceptés : JPG, PNG, GIF, WEBP.</div>
                <?php if (isset($erreurs['image'])): ?>
                    <div class="invalid-feedback">
                        <?php echo $erreurs['image']; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <div class="form-text">* Champs obligatoires</div>
            </div>
            
            <div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Enregistrer les modifications
                </button>
            </div>
        </form>
    </div>
</div>

<?php
// Inclure le pied de page
require_once '../includes/footer.php';
?>
