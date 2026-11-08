<?php
/**
 * Page des informations
 * 
 * Affiche la liste des informations ou le détail d'une information spécifique
 */

// Inclure les fichiers nécessaires
require_once 'includes/db.php';
require_once 'includes/stockage.php';
require_once 'includes/fonctions.php';

// Démarrer la session
session_start();

// Vérifier si un ID est spécifié pour afficher une information en particulier
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Récupérer les détails de l'information
    $stmt = $mysqli->prepare("SELECT * FROM informations WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $information = $result->fetch_assoc();
        $titre_page = htmlspecialchars($information['titre']);
        $mode = 'detail';
    } else {
        // Si l'information n'existe pas, rediriger vers la liste des informations
        $_SESSION['message'] = "L'information demandée n'existe pas.";
        $_SESSION['type_message'] = "error";
        header("Location: informations.php");
        exit;
    }
} else {
    // Afficher la liste des informations
    $stmt = $mysqli->query("SELECT * FROM informations ORDER BY date_publication DESC");
    $informations = $stmt->fetch_all(MYSQLI_ASSOC);
    $titre_page = "Informations";
    $mode = 'liste';
}

// Inclure l'en-tête
require_once 'includes/header.php';
?>

<?php if ($mode === 'liste'): ?>
    <!-- Affichage de la liste des informations -->
    <div class="container py-5" style="margin-top: 120px;">
        <h1 class="page-title-glass mb-4">Dernières <span class="text-accent">Informations</span></h1>
        
        <div class="mb-5 d-flex justify-content-center">
            <p class="lead text-center text-secondary" style="max-width: 700px;">
                Retrouvez toutes les actualités et informations importantes concernant le Club Scientifique.
            </p>
        </div>
        
        <?php if (count($informations) > 0): ?>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <?php foreach ($informations as $info): ?>
                    <div class="col">
                        <div class="card-glass h-100 p-0 overflow-hidden d-flex flex-column group-hover-effect">
                            <div class="card-body p-4 d-flex flex-column h-100">
                                <div class="text-accent mb-3 text-mono small">
                                    <i class="fas fa-calendar-alt me-1"></i> Publié le <?php echo formaterDate($info['date_publication']); ?>
                                </div>
                                <h4 class="card-title text-white"><?php echo htmlspecialchars($info['titre']); ?></h4>
                                
                                <?php if (!empty($info['image'])): ?>
                                <div class="mb-3 rounded overflow-hidden" style="height: 200px;">
                                    <img src="<?php echo htmlspecialchars(media_url('informations', $info['image'])); ?>" alt="Illustration" class="w-100 h-100 object-fit-cover">
                                </div>
                                <?php endif; ?>

                                <p class="text-secondary flex-grow-1">
                                    <?php echo tronquerTexte(htmlspecialchars($info['contenu']), 150); ?>
                                </p>
                                <a href="informations.php?id=<?php echo $info['id']; ?>" class="btn btn-glass w-100 mt-3 align-self-end">
                                    Lire la suite <i class="fas fa-arrow-right ms-2"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert glass-alert text-center">
                <i class="fas fa-info-circle me-2"></i> Aucune information n'a été ajoutée pour le moment.
            </div>
        <?php endif; ?>
    </div>

<?php else: ?>
    <!-- Affichage du détail d'une information -->
    <div class="container py-5" style="margin-top: 100px;">
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="informations.php" class="text-accent text-decoration-none">Informations</a></li>
                <li class="breadcrumb-item active text-secondary" aria-current="page"><?php echo htmlspecialchars($information['titre']); ?></li>
            </ol>
        </nav>
        
        <div class="content-glass-box">
            <div class="d-flex align-items-center justify-content-between mb-4 border-bottom border-secondary pb-3" style="border-color: rgba(255,255,255,0.1) !important;">
                <h1 class="h2 text-white mb-0"><?php echo htmlspecialchars($information['titre']); ?></h1>
                <span class="text-accent text-mono"><i class="fas fa-calendar-alt me-2"></i><?php echo formaterDate($information['date_publication']); ?></span>
            </div>
            
            <div class="text-secondary" style="font-size: 1.1rem; line-height: 1.8;">
                <?php if (!empty($information['image'])): ?>
                <div class="mb-4 text-center">
                    <img src="<?php echo htmlspecialchars(media_url('informations', $information['image'])); ?>" alt="Illustration" class="img-fluid rounded shadow-sm" style="max-height: 400px;">
                </div>
                <?php endif; ?>

                <?php 
                $paragraphes = explode("\n", $information['contenu']);
                foreach ($paragraphes as $paragraphe) {
                    if (trim($paragraphe) !== '') {
                        echo '<p>' . htmlspecialchars($paragraphe) . '</p>';
                    }
                }
                ?>
            </div>
            
            <?php if (!empty($information['document_url'])): ?>
            <div class="mt-4 pt-4 border-top" style="border-color: rgba(255,255,255,0.1) !important;">
                <a href="<?php echo htmlspecialchars($information['document_url']); ?>" class="btn btn-accent" target="_blank">
                    <i class="fas fa-file-download me-2"></i> Télécharger le document associé
                </a>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="text-center">
            <a href="informations.php" class="btn btn-glass">
                <i class="fas fa-arrow-left me-2"></i> Retour à la liste
            </a>
        </div>
    </div>
<?php endif; ?>

<?php
// Inclure le pied de page
require_once 'includes/footer.php';
?>
