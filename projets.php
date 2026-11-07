<?php
/**
 * Page des projets scientifiques
 * 
 * Affiche la liste des projets ou le détail d'un projet spécifique
 */

// Inclure les fichiers nécessaires
require_once 'includes/db.php';
require_once 'includes/fonctions.php';

// Démarrer la session
session_start();

// Vérifier si un ID est spécifié pour afficher un projet en particulier
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Récupérer les détails du projet
    $stmt = $mysqli->prepare("SELECT * FROM projets WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $projet = $result->fetch_assoc();
        $titre_page = htmlspecialchars($projet['titre']);
        $mode = 'detail';
    } else {
        // Si le projet n'existe pas, rediriger vers la liste des projets
        $_SESSION['message'] = "Le projet demandé n'existe pas.";
        $_SESSION['type_message'] = "error";
        header("Location: projets.php");
        exit;
    }
} else {
    // Afficher la liste des projets
    $stmt = $mysqli->query("SELECT * FROM projets ORDER BY date_creation DESC");
    $projets = $stmt->fetch_all(MYSQLI_ASSOC);
    $titre_page = "Nos projets scientifiques";
    $mode = 'liste';
}

// Inclure l'en-tête
require_once 'includes/header.php';
?>

<?php if ($mode === 'liste'): ?>
    <div class="container py-5" style="margin-top: 60px;">
        <h1 class="page-title-glass mb-4">Nos <span class="text-accent">Projets</span></h1>
        
        <p class="lead text-center text-secondary mb-5" style="max-width: 700px; margin: 0 auto;">
            Découvrez les projets scientifiques innovants sur lesquels travaillent nos membres.
        </p>
        
        <?php if (count($projets) > 0): ?>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <?php foreach ($projets as $projet): ?>
                    <div class="col">
                        <div class="card-glass h-100 p-0 overflow-hidden d-flex flex-column group-hover-effect">
                            <div class="card-body p-4 d-flex flex-column h-100">
                                <div class="text-accent mb-3 text-mono small">
                                    <i class="fas fa-calendar-alt me-1"></i> <?php echo formaterDate($projet['date_creation']); ?>
                                </div>
                                <h4 class="card-title text-white"><?php echo htmlspecialchars($projet['titre']); ?></h4>
                                <p class="text-secondary flex-grow-1">
                                    <?php echo tronquerTexte(htmlspecialchars($projet['description']), 150); ?>
                                </p>
                                <a href="projets.php?id=<?php echo $projet['id']; ?>" class="btn btn-glass w-100 mt-3 align-self-end">
                                    En savoir plus <i class="fas fa-arrow-right ms-2"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert glass-alert text-center">
                <i class="fas fa-rocket me-2"></i> Aucun projet n'a été ajouté pour le moment.
            </div>
        <?php endif; ?>
    </div>

<?php else: ?>
    <!-- Affichage du détail d'un projet -->
    <div class="container py-5" style="margin-top: 60px;">
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="projets.php" class="text-accent text-decoration-none">Nos projets</a></li>
                <li class="breadcrumb-item active text-secondary" aria-current="page"><?php echo htmlspecialchars($projet['titre']); ?></li>
            </ol>
        </nav>
        
        <div class="content-glass-box">
            <div class="d-flex align-items-center justify-content-between mb-4 border-bottom border-secondary pb-3" style="border-color: rgba(255,255,255,0.1) !important;">
                <h1 class="h2 text-white mb-0"><?php echo htmlspecialchars($projet['titre']); ?></h1>
                <span class="text-accent text-mono"><i class="fas fa-calendar-alt me-2"></i><?php echo formaterDate($projet['date_creation']); ?></span>
            </div>
            
            <div class="text-secondary" style="font-size: 1.1rem; line-height: 1.8;">
                <?php 
                // Afficher la description avec mise en forme des paragraphes
                $paragraphes = explode("\n", $projet['description']);
                foreach ($paragraphes as $paragraphe) {
                    if (trim($paragraphe) !== '') {
                        echo '<p>' . htmlspecialchars($paragraphe) . '</p>';
                    }
                }
                ?>
            </div>
            
            <div class="mt-5 pt-3 border-top" style="border-color: rgba(255,255,255,0.1) !important;">
                <h4 class="text-white mb-3">Participer à ce projet ?</h4>
                <a href="contact.php?sujet=Projet: <?php echo urlencode($projet['titre']); ?>" class="btn btn-accent">Nous contacter</a>
            </div>
        </div>
        
        <div class="text-center">
            <a href="projets.php" class="btn btn-glass">
                <i class="fas fa-arrow-left me-2"></i> Retour à la liste
            </a>
        </div>
    </div>
<?php endif; ?>

<?php
// Inclure le pied de page
require_once 'includes/footer.php';
?>
