<?php
/**
 * Page galerie d'images
 * 
 * Affiche les images du club organisées par catégories avec une navigation moderne
 */

// Inclure les fichiers nécessaires
require_once 'includes/db.php';
require_once 'includes/fonctions.php';

// Démarrer la session
session_start();

// Récupérer les catégories depuis la base de données
try {
    $stmt = $mysqli->prepare("SELECT * FROM categories_galerie ORDER BY nom");
    $stmt->execute();
    $categories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Pour chaque catégorie, récupérer ses images
    foreach ($categories as $i => $categorie) {
        $stmt = $mysqli->prepare("SELECT * FROM galerie WHERE categorie_id = ? ORDER BY date_ajout DESC");
        $stmt->bind_param("i", $categorie['id']);
        $stmt->execute();
        $categories[$i]['images'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    // En cas d'erreur, initialiser un tableau vide
    $categories = [];
}

// Titre de la page pour l'affichage dans l'en-tête
$titre_page = "Galerie";

// Inclure l'en-tête
require_once 'includes/header.php';
?>

<!-- Ajout des styles GLightbox -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css" />
<style>
.gallery-container {
    margin-top: 2rem;
}
.gallery-item {
    position: relative;
    overflow: hidden;
    border-radius: 8px;
    transition: transform 0.3s ease;
}
.gallery-item:hover {
    transform: scale(1.02);
}
.gallery-item img {
    width: 100%;
    height: 250px;
    object-fit: cover;
}
.gallery-caption {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(0,0,0,0.7);
    color: white;
    padding: 10px;
    transform: translateY(100%);
    transition: transform 0.3s ease;
}
.gallery-item:hover .gallery-caption {
    transform: translateY(0);
}
.nav-tabs .nav-link {
    color: #495057;
    border: none;
    padding: 0.5rem 1rem;
    margin-right: 0.5rem;
    border-radius: 4px;
}
.nav-tabs .nav-link.active {
    background-color: #007bff;
    color: white;
}
</style>

<div class="container py-5" style="margin-top: 60px;">
    <h1 class="page-title-glass mb-4">Galerie <span class="text-accent">Immersive</span></h1>
    <p class="lead text-center text-secondary mb-5" style="max-width: 700px; margin: 0 auto;">
        Découvrez en images les activités et projets du Club Scientifique.
    </p>

    <?php if (empty($categories)): ?>
        <div class="alert glass-alert text-center">
            <i class="fas fa-info-circle me-2"></i> Aucune image n'est disponible pour le moment.
        </div>
    <?php else: ?>
        <div class="mb-5 d-flex justify-content-center">
            <ul class="nav nav-tabs border-0 gap-3" id="galleryTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="btn btn-glass active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab">Toutes</button>
                </li>
                <?php foreach ($categories as $categorie): ?>
                    <li class="nav-item" role="presentation">
                        <button class="btn btn-glass" id="cat<?php echo $categorie['id']; ?>-tab" data-bs-toggle="tab" data-bs-target="#cat<?php echo $categorie['id']; ?>" type="button" role="tab"><?php echo htmlspecialchars($categorie['nom']); ?></button>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="tab-content" id="galleryTabContent">
            <!-- Toutes les catégories -->
            <div class="tab-pane fade show active" id="all" role="tabpanel">
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4 gallery-container">
                    <?php foreach ($categories as $categorie): ?>
                        <?php foreach ($categorie['images'] as $image): ?>
                            <div class="col">
                                <a href="uploads/galerie/<?php echo htmlspecialchars($image['fichier']); ?>" 
                                   class="card-glass d-block p-2 h-100 text-decoration-none gallery-item glightbox" 
                                   data-gallery="gallery1"
                                   data-type="<?php echo $image['type'] === 'video' ? 'video' : 'image'; ?>"
                                   data-effect="fade"
                                   data-caption="<?php echo htmlspecialchars($image['titre']); ?>">
                                    <div style="height: 250px; overflow: hidden; border-radius: 8px; position: relative;">
                                        <?php if ($image['type'] === 'video'): ?>
                                            <video src="uploads/galerie/<?php echo htmlspecialchars($image['fichier']); ?>" 
                                                   class="w-100 h-100 object-fit-cover" muted></video>
                                            <div class="position-absolute top-50 start-50 translate-middle text-white">
                                                <i class="fas fa-play-circle fa-4x" style="opacity: 0.8; text-shadow: 0 2px 4px rgba(0,0,0,0.3);"></i>
                                            </div>
                                        <?php else: ?>
                                            <img src="uploads/galerie/<?php echo htmlspecialchars($image['fichier']); ?>" 
                                                 alt="<?php echo htmlspecialchars($image['titre']); ?>"
                                                 loading="lazy"
                                                 style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s;">
                                        <?php endif; ?>
                                    </div>
                                    <div class="mt-3 text-center">
                                        <h5 class="text-white mb-1" style="font-size: 1.1rem;"><?php echo htmlspecialchars($image['titre']); ?></h5>
                                        <small class="text-accent text-mono"><?php echo htmlspecialchars($categorie['nom']); ?></small>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Catégories individuelles -->
            <?php foreach ($categories as $categorie): ?>
                <div class="tab-pane fade" id="cat<?php echo $categorie['id']; ?>" role="tabpanel">
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4 gallery-container">
                        <?php foreach ($categorie['images'] as $image): ?>
                            <div class="col">
                                <a href="uploads/galerie/<?php echo htmlspecialchars($image['fichier']); ?>" 
                                   class="card-glass d-block p-2 h-100 text-decoration-none gallery-item glightbox" 
                                   data-gallery="gallery<?php echo $categorie['id']; ?>"
                                   data-type="<?php echo $image['type'] === 'video' ? 'video' : 'image'; ?>"
                                   data-effect="fade"
                                   data-caption="<?php echo htmlspecialchars($image['titre']); ?>">
                                    <div style="height: 250px; overflow: hidden; border-radius: 8px; position: relative;">
                                        <?php if ($image['type'] === 'video'): ?>
                                            <video src="uploads/galerie/<?php echo htmlspecialchars($image['fichier']); ?>" 
                                                   class="w-100 h-100 object-fit-cover" muted></video>
                                            <div class="position-absolute top-50 start-50 translate-middle text-white">
                                                <i class="fas fa-play-circle fa-4x" style="opacity: 0.8; text-shadow: 0 2px 4px rgba(0,0,0,0.3);"></i>
                                            </div>
                                        <?php else: ?>
                                            <img src="uploads/galerie/<?php echo htmlspecialchars($image['fichier']); ?>" 
                                                 alt="<?php echo htmlspecialchars($image['titre']); ?>"
                                                 loading="lazy"
                                                 style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s;">
                                        <?php endif; ?>
                                    </div>
                                    <div class="mt-3 text-center">
                                        <h5 class="text-white mb-1" style="font-size: 1.1rem;"><?php echo htmlspecialchars($image['titre']); ?></h5>
                                        <small class="text-accent text-mono"><?php echo formaterDate($image['date_ajout']); ?></small>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Scripts GLightbox -->
<script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const lightbox = GLightbox({
        selector: '.glightbox',
        touchNavigation: true,
        loop: true,
        autoplayVideos: true,
        onOpen: () => {
            document.body.style.overflow = 'hidden';
        },
        onClose: () => {
            document.body.style.overflow = 'auto';
        }
    });
});
</script>

<?php
// Inclure le pied de page
require_once 'includes/footer.php';
?>
