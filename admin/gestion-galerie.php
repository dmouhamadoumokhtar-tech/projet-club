<?php
/**
 * Gestion de la galerie d'images
 * 
 * Permet aux administrateurs de gérer les images et catégories de la galerie
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

// Traitement des actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Action: Supprimer une image
    if ($_GET['action'] === 'supprimer') {
        try {
            // Récupérer le nom du fichier avant de supprimer l'entrée
            $stmt = $mysqli->prepare("SELECT fichier FROM galerie WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $image = $result->fetch_assoc();
            
            if ($image) {
                // Supprimer le fichier physique
                $chemin_fichier = "../uploads/galerie/" . $image['fichier'];
                if (file_exists($chemin_fichier)) {
                    unlink($chemin_fichier);
                }
                
                // Supprimer l'entrée de la base de données
                $stmt = $mysqli->prepare("DELETE FROM galerie WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                
                // Message de succès
                $_SESSION['message'] = "L'image a été supprimée avec succès.";
                $_SESSION['type_message'] = "success";
            }
        } catch (Exception $e) {
            // Message d'erreur
            $_SESSION['message'] = "Une erreur est survenue lors de la suppression de l'image.";
            $_SESSION['type_message'] = "error";
        }
    }
    
    // Rediriger pour éviter les soumissions multiples
    rediriger('gestion-galerie.php');
}

// Traitement de l'ajout d'une nouvelle image
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajouter') {
    try {
        $titre = $_POST['titre'];
        $description = $_POST['description'];
        $categorie_id = intval($_POST['categorie_id']);
        $upload_dir = "../uploads/galerie/";

        // Vérifier et créer le dossier d'upload si nécessaire
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $fichiers_uploades = 0;
        $erreurs_upload = [];

        // Vérifier si des fichiers ont été uploadés
        if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
            $nombre_fichiers = count($_FILES['images']['name']);

            for ($i = 0; $i < $nombre_fichiers; $i++) {
                // Vérifier l'erreur pour chaque fichier
                if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                    $nom_fichier_original = $_FILES['images']['name'][$i];
                    $tmp_name = $_FILES['images']['tmp_name'][$i];
                    
                    // Générer un nom de fichier unique
                    $extension = strtolower(pathinfo($nom_fichier_original, PATHINFO_EXTENSION));
                    $nouveau_nom = uniqid() . '.' . $extension;
                    $chemin_fichier = $upload_dir . $nouveau_nom;
                    
                    // Vérifier le type de fichier
                    $types_images = ['jpg', 'jpeg', 'png', 'gif'];
                    $types_videos = ['mp4', 'webm'];
                    $types_autorises = array_merge($types_images, $types_videos);
                    
                    if (!in_array($extension, $types_autorises)) {
                        $erreurs_upload[] = "Le fichier '$nom_fichier_original': type non autorisé. (Images: JPG, PNG, GIF | Vidéos: MP4, WEBM)";
                        continue; // Passer au fichier suivant
                    }
                    
                    // Déterminer le type (image ou video)
                    $type = in_array($extension, $types_videos) ? 'video' : 'image';
                    
                    // Déplacer le fichier uploadé
                    if (move_uploaded_file($tmp_name, $chemin_fichier)) {
                        // Insérer dans la base de données
                        $stmt = $mysqli->prepare("INSERT INTO galerie (titre, description, categorie_id, fichier, type, date_ajout) VALUES (?, ?, ?, ?, ?, NOW())");
                        $stmt->bind_param("ssiss", $titre, $description, $categorie_id, $nouveau_nom, $type);
                        if ($stmt->execute()) {
                            $fichiers_uploades++;
                        } else {
                            $erreurs_upload[] = "Le fichier '$nom_fichier_original': erreur lors de l'enregistrement dans la base de données.";
                            // Optionnel: supprimer le fichier si l'insertion DB échoue
                            if (file_exists($chemin_fichier)) {
                                unlink($chemin_fichier);
                            }
                        }
                    } else {
                        $erreurs_upload[] = "Le fichier '$nom_fichier_original': erreur lors de l'upload.";
                    }
                } else {
                    // Gérer les erreurs d'upload spécifiques si nécessaire
                    $erreurs_upload[] = "Erreur avec le fichier '" . $_FILES['images']['name'][$i] . "': code " . $_FILES['images']['error'][$i];
                }
            }

            // Construire le message de retour
            if ($fichiers_uploades > 0 && empty($erreurs_upload)) {
                $_SESSION['message'] = "$fichiers_uploades image(s) ajoutée(s) avec succès.";
                $_SESSION['type_message'] = "success";
            } elseif ($fichiers_uploades > 0 && !empty($erreurs_upload)) {
                $_SESSION['message'] = "$fichiers_uploades image(s) ajoutée(s) avec succès. Erreurs: <br>" . implode("<br>", $erreurs_upload);
                $_SESSION['type_message'] = "warning";
            } elseif ($fichiers_uploades === 0 && !empty($erreurs_upload)) {
                $_SESSION['message'] = "Aucune image n'a pu être ajoutée. Erreurs: <br>" . implode("<br>", $erreurs_upload);
                $_SESSION['type_message'] = "error";
            } else {
                 // Ce cas ne devrait pas être atteint si la vérification initiale !empty($_FILES['images']['name'][0]) est correcte
                $_SESSION['message'] = "Aucun fichier n'a été traité.";
                $_SESSION['type_message'] = "info";
            }

        } else {
            throw new Exception("Aucun fichier n'a été uploadé ou une erreur inattendue s'est produite.");
        }
    } catch (Exception $e) {
        // Message d'erreur général
        $_SESSION['message'] = "Une erreur est survenue : " . $e->getMessage();
        $_SESSION['type_message'] = "error";
    }
    
    // Rediriger pour éviter les soumissions multiples
    rediriger('gestion-galerie.php');
}

// Traitement des actions sur les catégories
if (isset($_POST['action_categorie'])) {
    try {
        switch ($_POST['action_categorie']) {
            case 'ajouter':
                $nom = trim($_POST['nom_categorie']);
                if (empty($nom)) {
                    throw new Exception("Le nom de la catégorie ne peut pas être vide");
                }
                
                $stmt = $mysqli->prepare("INSERT INTO categories_galerie (nom) VALUES (?)");
                $stmt->bind_param("s", $nom);
                $stmt->execute();
                
                $_SESSION['message'] = "La catégorie a été ajoutée avec succès.";
                $_SESSION['type_message'] = "success";
                break;
                
            case 'modifier':
                $id = intval($_POST['categorie_id']);
                $nom = trim($_POST['nom_categorie']);
                if (empty($nom)) {
                    throw new Exception("Le nom de la catégorie ne peut pas être vide");
                }
                
                $stmt = $mysqli->prepare("UPDATE categories_galerie SET nom = ? WHERE id = ?");
                $stmt->bind_param("si", $nom, $id);
                $stmt->execute();
                
                $_SESSION['message'] = "La catégorie a été modifiée avec succès.";
                $_SESSION['type_message'] = "success";
                break;
                
            case 'supprimer':
                $id = intval($_POST['categorie_id']);
                
                // Récupérer toutes les images de la catégorie
                $stmt = $mysqli->prepare("SELECT fichier FROM galerie WHERE categorie_id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                // Supprimer les fichiers physiques
                while ($image = $result->fetch_assoc()) {
                    $chemin_fichier = "../uploads/galerie/" . $image['fichier'];
                    if (file_exists($chemin_fichier)) {
                        unlink($chemin_fichier);
                    }
                }
                
                // Supprimer les entrées de la base de données
                $stmt = $mysqli->prepare("DELETE FROM galerie WHERE categorie_id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                
                // Supprimer la catégorie
                $stmt = $mysqli->prepare("DELETE FROM categories_galerie WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                
                $_SESSION['message'] = "La catégorie et ses images ont été supprimées avec succès.";
                $_SESSION['type_message'] = "success";
                break;
        }
    } catch (Exception $e) {
        $_SESSION['message'] = "Une erreur est survenue : " . $e->getMessage();
        $_SESSION['type_message'] = "error";
    }
    
    // Rediriger pour éviter les soumissions multiples
    rediriger('gestion-galerie.php');
}

// Récupérer les catégories
try {
    $stmt = $mysqli->prepare("SELECT * FROM categories_galerie ORDER BY nom");
    $stmt->execute();
    $categories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $categories = [];
}

// Récupérer toutes les images
try {
    $stmt = $mysqli->prepare("
        SELECT g.*, c.nom as categorie_nom 
        FROM galerie g 
        JOIN categories_galerie c ON g.categorie_id = c.id 
        ORDER BY g.date_ajout DESC
    ");
    $stmt->execute();
    $images = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $images = [];
}

// Définir le titre de la page
$titre_page = "Gestion de la galerie";

// Inclure l'en-tête
require_once '../includes/header.php';
?>

<!-- Styles personnalisés -->
<style>
/* Layout principal */
.main-content {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem;
}

/* Grille de la galerie */
.gallery-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1.5rem;
    margin: 0;
    padding: 0;
}

/* Cartes d'images */
.gallery-item {
    position: relative;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s;
    height: 100%;
    display: flex;
    flex-direction: column;
}

.gallery-item img {
    width: 100%;
    height: 200px;
    object-fit: cover;
    display: block;
}

.gallery-item-info {
    padding: 1rem;
    flex: 1;
    display: flex;
    flex-direction: column;
    background: white;
}

.gallery-item-info h6 {
    font-size: 0.95rem;
    margin: 0 0 0.5rem 0;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    height: 2.8em;
}

.gallery-item-info p {
    font-size: 0.85rem;
    margin: 0;
    color: #6c757d;
}

/* Grille des catégories */
.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1rem;
    margin: 0;
    padding: 0;
}

.category-card {
    background: white;
    border-radius: 8px;
    padding: 1rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.category-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
}

.category-card-header h6 {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
}

.category-count {
    background: #e9ecef;
    padding: 0.25rem 0.5rem;
    border-radius: 20px;
    font-size: 0.85rem;
}

.category-actions {
    display: flex;
    gap: 0.5rem;
}

/* Zone d'upload */
.upload-zone {
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    padding: 1.5rem;
    text-align: center;
    background: #f8f9fa;
    margin-bottom: 1.5rem;
}

/* Badges et actions */
.category-badge {
    position: absolute;
    top: 10px;
    left: 10px;
    background: rgba(0,0,0,0.7);
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.85rem;
    z-index: 2;
}

.gallery-item-actions {
    position: absolute;
    top: 10px;
    right: 10px;
    z-index: 2;
    opacity: 0;
    transition: opacity 0.2s;
}

.gallery-item:hover .gallery-item-actions {
    opacity: 1;
}

/* Responsive */
@media (max-width: 1200px) {
    .main-content {
        padding: 1.5rem;
    }
    
    .gallery-grid {
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    }
}

@media (max-width: 768px) {
    .main-content {
        padding: 1rem;
    }
    
    .gallery-grid {
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    }
    
    .categories-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    }
}

@media (max-width: 576px) {
    .gallery-grid,
    .categories-grid {
        grid-template-columns: 1fr;
    }
    
    .gallery-item img {
        height: 180px;
    }
}
</style>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2 mb-0">Gestion de la galerie</h1>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Retour au tableau de bord
        </a>
    </div>

    <!-- Formulaire d'ajout d'image -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white">
            <h5 class="card-title mb-0">Ajouter des images</h5>
        </div>
        <div class="card-body">
            <form method="post" action="gestion-galerie.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="ajouter">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="titre" class="form-label">Titre</label>
                            <input type="text" class="form-control" id="titre" name="titre" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="categorie_id" class="form-label">Catégorie</label>
                            <select class="form-select" id="categorie_id" name="categorie_id" required>
                                <option value="">Sélectionner une catégorie</option>
                                <?php foreach ($categories as $categorie): ?>
                                    <option value="<?php echo $categorie['id']; ?>"><?php echo htmlspecialchars($categorie['nom']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="upload-zone">
                            <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                            <h5>Glissez-déposez vos images ici</h5>
                            <p class="text-muted mb-3">ou</p>
                            <input type="file" class="form-control" id="image" name="images[]" accept="image/*,video/mp4,video/webm" required multiple>
                            <div class="form-text mt-2">Images (JPG, PNG) ou Vidéos (MP4, WEBM)</div>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i> Ajouter les images
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Gestion des catégories -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Catégories</h5>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#ajoutCategorieModal">
                <i class="fas fa-plus me-1"></i> Nouvelle catégorie
            </button>
        </div>
        <div class="card-body">
            <?php if (empty($categories)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Aucune catégorie n'a été créée pour le moment.
                </div>
            <?php else: ?>
                <div class="categories-grid">
                    <?php foreach ($categories as $categorie): ?>
                        <div class="category-card">
                            <div class="category-card-header">
                                <h6><?php echo htmlspecialchars($categorie['nom']); ?></h6>
                                <span class="category-count">
                                    <?php
                                    $stmt = $mysqli->prepare("SELECT COUNT(*) as total FROM galerie WHERE categorie_id = ?");
                                    $stmt->bind_param("i", $categorie['id']);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    $count = $result->fetch_assoc()['total'];
                                    echo $count . ' image(s)';
                                    ?>
                                </span>
                            </div>
                            <div class="category-actions">
                                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modifierCategorieModal<?php echo $categorie['id']; ?>">
                                    <i class="fas fa-edit me-1"></i> Modifier
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#supprimerCategorieModal<?php echo $categorie['id']; ?>">
                                    <i class="fas fa-trash-alt me-1"></i> Supprimer
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Liste des images -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white">
            <h5 class="card-title mb-0">Images de la galerie</h5>
        </div>
        <div class="card-body">
            <?php if (empty($images)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Aucune image n'a été ajoutée pour le moment.
                </div>
            <?php else: ?>
                <div class="gallery-grid">
                    <?php foreach ($images as $image): ?>
                        <div class="gallery-item">
                            <?php if ($image['type'] === 'video'): ?>
                                <video src="../uploads/galerie/<?php echo htmlspecialchars($image['fichier']); ?>" class="object-fit-cover w-100" style="height: 200px;" muted></video>
                                <div class="position-absolute top-50 start-50 translate-middle text-white">
                                    <i class="fas fa-play-circle fa-3x" style="opacity: 0.8;"></i>
                                </div>
                            <?php else: ?>
                                <img src="../uploads/galerie/<?php echo htmlspecialchars($image['fichier']); ?>" 
                                     alt="<?php echo htmlspecialchars($image['titre']); ?>">
                            <?php endif; ?>
                            
                            <span class="category-badge"><?php echo htmlspecialchars($image['categorie_nom']); ?></span>
                            <div class="gallery-item-actions">
                                <a href="gestion-galerie.php?action=supprimer&id=<?php echo $image['id']; ?>" 
                                   class="btn btn-danger btn-sm" 
                                   onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette image/vidéo ?');">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </div>
                            <div class="gallery-item-info">
                                <h6><?php echo htmlspecialchars($image['titre']); ?></h6>
                                <p><?php echo formaterDate($image['date_ajout']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Garder les modals existants -->
<?php
// Modal d'ajout de catégorie
?>
<div class="modal fade" id="ajoutCategorieModal" tabindex="-1" aria-labelledby="ajoutCategorieModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="gestion-galerie.php">
                <input type="hidden" name="action_categorie" value="ajouter">
                <div class="modal-header">
                    <h5 class="modal-title" id="ajoutCategorieModalLabel">Ajouter une catégorie</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nom_categorie" class="form-label">Nom de la catégorie</label>
                        <input type="text" class="form-control" id="nom_categorie" name="nom_categorie" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Modals de modification et suppression pour chaque catégorie
foreach ($categories as $categorie): 
?>
    <!-- Modal de modification -->
    <div class="modal fade" id="modifierCategorieModal<?php echo $categorie['id']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" action="gestion-galerie.php">
                    <input type="hidden" name="action_categorie" value="modifier">
                    <input type="hidden" name="categorie_id" value="<?php echo $categorie['id']; ?>">
                    <div class="modal-header">
                        <h5 class="modal-title">Modifier la catégorie</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nom_categorie_<?php echo $categorie['id']; ?>" class="form-label">Nom de la catégorie</label>
                            <input type="text" class="form-control" id="nom_categorie_<?php echo $categorie['id']; ?>" 
                                   name="nom_categorie" value="<?php echo htmlspecialchars($categorie['nom']); ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de suppression -->
    <div class="modal fade" id="supprimerCategorieModal<?php echo $categorie['id']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" action="gestion-galerie.php">
                    <input type="hidden" name="action_categorie" value="supprimer">
                    <input type="hidden" name="categorie_id" value="<?php echo $categorie['id']; ?>">
                    <div class="modal-header">
                        <h5 class="modal-title">Supprimer la catégorie</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <p>Êtes-vous sûr de vouloir supprimer la catégorie "<?php echo htmlspecialchars($categorie['nom']); ?>" ?</p>
                        <p class="text-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Cette action est irréversible et supprimera également toutes les images associées à cette catégorie.
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-danger">Supprimer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<script>
// Script pour le drag & drop
document.addEventListener('DOMContentLoaded', function() {
    const uploadZone = document.querySelector('.upload-zone');
    const fileInput = document.querySelector('input[type="file"]');

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        uploadZone.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        uploadZone.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        uploadZone.addEventListener(eventName, unhighlight, false);
    });

    function highlight(e) {
        uploadZone.classList.add('border-primary');
    }

    function unhighlight(e) {
        uploadZone.classList.remove('border-primary');
    }

    uploadZone.addEventListener('drop', handleDrop, false);

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        fileInput.files = files;
    }
});

// Fonction pour afficher les messages de notification
function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
    notification.style.zIndex = '9999';
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    `;
    document.body.appendChild(notification);
    
    // Supprimer la notification après 5 secondes
    setTimeout(() => {
        notification.remove();
    }, 5000);
}

// Vérifier s'il y a un message en session
<?php if (isset($_SESSION['message'])): ?>
    showNotification(<?php echo json_encode($_SESSION['message']); ?>, <?php echo json_encode($_SESSION['type_message']); ?>);
    <?php 
    // Supprimer le message de la session
    unset($_SESSION['message']);
    unset($_SESSION['type_message']);
    ?>
<?php endif; ?>

// Gestion des formulaires de suppression
document.querySelectorAll('form[action*="supprimer"]').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        if (confirm('Êtes-vous sûr de vouloir effectuer cette action ?')) {
            this.submit();
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?> 