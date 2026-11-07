<?php
/**
 * Modification d'un projet existant
 * 
 * Permet aux administrateurs de modifier les détails d'un projet scientifique
 */

// Inclure les fichiers nécessaires
require_once '../includes/db.php';
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
    $_SESSION['message'] = "ID de projet invalide.";
    $_SESSION['type_message'] = "error";
    
    // Rediriger vers la liste des projets
    rediriger('gestion-projets.php');
}

// Initialiser les variables
$titre = $description = '';
$erreurs = [];

// Récupérer les données du projet
try {
    $stmt = $mysqli->prepare("SELECT * FROM projets WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $projet = $result->fetch_assoc();
    
    if (!$projet) {
        // Si le projet n'existe pas
        $_SESSION['message'] = "Le projet demandé n'existe pas.";
        $_SESSION['type_message'] = "error";
        
        // Rediriger vers la liste des projets
        rediriger('gestion-projets.php');
    }
    
    // Pré-remplir les champs avec les données existantes
    $titre = $projet['titre'];
    $description = $projet['description'];
    
} catch (Exception $e) {
    $_SESSION['message'] = "Une erreur est survenue lors de la récupération des données du projet.";
    $_SESSION['type_message'] = "error";
    
    // Rediriger vers la liste des projets
    rediriger('gestion-projets.php');
}

// Traitement du formulaire si soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer et nettoyer les données du formulaire
    $titre = nettoyer($_POST['titre'] ?? '');
    $description = $_POST['description'] ?? ''; // Ne pas nettoyer la description pour préserver les sauts de ligne
    
    // Validation
    if (empty($titre)) {
        $erreurs['titre'] = "Le titre est obligatoire";
    }
    
    if (empty($description)) {
        $erreurs['description'] = "La description est obligatoire";
    }
    
    // Si pas d'erreurs, mettre à jour dans la base de données
    if (empty($erreurs)) {
        try {
            // Préparer et exécuter la requête de mise à jour
            $stmt = $mysqli->prepare("
                UPDATE projets 
                SET titre = ?, description = ? 
                WHERE id = ?
            ");
            
            $stmt->bind_param("ssi", $titre, $description, $id);
            $stmt->execute();
            
            // Message de succès
            $_SESSION['message'] = "Le projet a été mis à jour avec succès.";
            $_SESSION['type_message'] = "success";
            
            // Rediriger vers la liste des projets
            rediriger('gestion-projets.php');
        } catch (Exception $e) {
            $erreurs['general'] = "Une erreur est survenue lors de la mise à jour du projet. Veuillez réessayer.";
            // En environnement de développement, on pourrait afficher : $e->getMessage()
        }
    }
}

// Titre de la page pour l'affichage dans l'en-tête
$titre_page = "Modifier un projet";

// Inclure l'en-tête
require_once '../includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h2 mb-0">Modifier un projet</h1>
    <a href="gestion-projets.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> Retour à la liste des projets
    </a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <?php if (isset($erreurs['general'])): ?>
            <div class="alert alert-danger">
                <?php echo $erreurs['general']; ?>
            </div>
        <?php endif; ?>
        
        <form method="post" action="">
            <div class="mb-3">
                <label for="titre" class="form-label">Titre du projet *</label>
                <input type="text" id="titre" name="titre" value="<?php echo htmlspecialchars($titre); ?>" class="form-control <?php echo isset($erreurs['titre']) ? 'is-invalid' : ''; ?>">
                <?php if (isset($erreurs['titre'])): ?>
                    <div class="invalid-feedback">
                        <?php echo $erreurs['titre']; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="mb-3">
                <label for="description" class="form-label">Description du projet *</label>
                <textarea id="description" name="description" rows="10" class="form-control <?php echo isset($erreurs['description']) ? 'is-invalid' : ''; ?>"><?php echo htmlspecialchars($description); ?></textarea>
                <div class="form-text">Utilisez des sauts de ligne pour structurer votre texte.</div>
                <?php if (isset($erreurs['description'])): ?>
                    <div class="invalid-feedback">
                        <?php echo $erreurs['description']; ?>
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
