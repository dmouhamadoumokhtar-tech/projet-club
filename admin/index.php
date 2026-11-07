<?php
/**
 * Page d'accueil de l'administration
 * 
 * Tableau de bord avec statistiques et liens rapides
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

// Récupérer les statistiques
$nombre_membres_valides = getNombreMembresParStatut($mysqli, 'valide');
$nombre_candidatures_attente = getNombreMembresParStatut($mysqli, 'en_attente');
$nombre_membres_refuses = getNombreMembresParStatut($mysqli, 'refuse');

// Compter le nombre de projets
$stmt = $mysqli->query("SELECT COUNT(*) FROM projets");
$nombre_projets = $stmt->fetch_row()[0];

// Compter le nombre d'articles d'information
$stmt = $mysqli->query("SELECT COUNT(*) FROM informations");
$nombre_informations = $stmt->fetch_row()[0];

// Compter le nombre de messages de contact non lus
$stmt = $mysqli->prepare("SELECT COUNT(*) FROM messages_contact WHERE lu = 0");
$stmt->execute();
$nombre_messages_non_lus = $stmt->get_result()->fetch_row()[0];

// Titre de la page pour l'affichage dans l'en-tête
$titre_page = "Administration";

// Inclure l'en-tête
require_once '../includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h2 mb-0">Tableau de bord d'administration</h1>
</div>

<div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4 mb-5">
    <div class="col">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="display-4 text-primary mb-2"><?php echo $nombre_membres_valides; ?></div>
                <h2 class="h5">Membres actifs</h2>
                <i class="fas fa-users fa-2x text-primary mt-2"></i>
            </div>
        </div>
    </div>
    
    <div class="col">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="display-4 text-warning mb-2"><?php echo $nombre_candidatures_attente; ?></div>
                <h2 class="h5">Candidatures en attente</h2>
                <i class="fas fa-user-clock fa-2x text-warning mt-2"></i>
            </div>
        </div>
    </div>
    
    <div class="col">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="display-4 text-success mb-2"><?php echo $nombre_projets; ?></div>
                <h2 class="h5">Projets</h2>
                <i class="fas fa-flask fa-2x text-success mt-2"></i>
            </div>
        </div>
    </div>
    
    <div class="col">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="display-4 text-info mb-2"><?php echo $nombre_informations; ?></div>
                <h2 class="h5">Articles d'information</h2>
                <i class="fas fa-newspaper fa-2x text-info mt-2"></i>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-primary text-white">
        <h2 class="h5 mb-0">Actions rapides</h2>
    </div>
    <div class="card-body">
        <div class="row g-2">
            <div class="col-md-4 mb-2">
                <a href="gestion-membres.php" class="btn btn-outline-primary d-flex align-items-center justify-content-center gap-2 w-100 py-3">
                    <i class="fas fa-users"></i> Gérer les membres
                </a>
            </div>
            <div class="col-md-4 mb-2">
                <a href="gestion-projets.php" class="btn btn-outline-primary d-flex align-items-center justify-content-center gap-2 w-100 py-3">
                    <i class="fas fa-flask"></i> Gérer les projets
                </a>
            </div>
            <div class="col-md-4 mb-2">
                <a href="gestion-informations.php" class="btn btn-outline-primary d-flex align-items-center justify-content-center gap-2 w-100 py-3">
                    <i class="fas fa-newspaper"></i> Gérer les informations
                </a>
            </div>
            <div class="col-md-4 mb-2">
                <a href="gestion-galerie.php" class="btn btn-outline-primary d-flex align-items-center justify-content-center gap-2 w-100 py-3">
                    <i class="fas fa-images"></i> Gérer la galerie
                </a>
            </div>
            <?php if ($nombre_candidatures_attente > 0): ?>
                <div class="col-md-6 mb-2">
                    <a href="gestion-membres.php?filtre=en_attente" class="btn btn-success d-flex align-items-center justify-content-center gap-2 w-100 py-3">
                        <i class="fas fa-user-check"></i> Valider les candidatures (<?php echo $nombre_candidatures_attente; ?>)
                    </a>
                </div>
            <?php endif; ?>
            <?php if ($nombre_messages_non_lus > 0): ?>
                <div class="col-md-6 mb-2">
                    <a href="gestion-messages.php" class="btn btn-info d-flex align-items-center justify-content-center gap-2 w-100 py-3 text-white">
                        <i class="fas fa-envelope"></i> Messages non lus (<?php echo $nombre_messages_non_lus; ?>)
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($nombre_candidatures_attente > 0): ?>
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-warning text-dark">
        <h2 class="h5 mb-0">Candidatures récentes en attente</h2>
    </div>
    <div class="card-body px-0 pb-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nom</th>
                        <th>Prénom</th>
                        <th>Email</th>
                        <th>Classe</th>
                        <th>Date de candidature</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Récupérer les 5 dernières candidatures en attente
                    $stmt = $mysqli->prepare("
                        SELECT * FROM membres 
                        WHERE statut = 'en_attente' 
                        ORDER BY date_candidature DESC 
                        LIMIT 5
                    ");
                    $stmt->execute();
                    $candidatures = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                    
                    foreach ($candidatures as $candidature):
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($candidature['nom']); ?></td>
                        <td><?php echo htmlspecialchars($candidature['prenom']); ?></td>
                        <td><?php echo htmlspecialchars($candidature['email']); ?></td>
                        <td><?php echo htmlspecialchars($candidature['classe']); ?></td>
                        <td><?php echo formaterDate($candidature['date_candidature']); ?></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="gestion-membres.php?action=valider&id=<?php echo $candidature['id']; ?>" class="btn btn-success">
                                    <i class="fas fa-check"></i>
                                </a>
                                <a href="gestion-membres.php?action=refuser&id=<?php echo $candidature['id']; ?>" class="btn btn-danger">
                                    <i class="fas fa-times"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if (count($candidatures) < $nombre_candidatures_attente): ?>
            <div class="text-center p-3">
                <a href="gestion-membres.php?filtre=en_attente" class="btn btn-outline-primary">Voir toutes les candidatures</a>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-success text-white">
        <h2 class="h5 mb-0">Derniers projets ajoutés</h2>
    </div>
    <div class="card-body px-0 pb-0">
        <?php
        // Récupérer les 3 derniers projets
        $stmt = $mysqli->query("SELECT * FROM projets ORDER BY date_creation DESC LIMIT 3");
        $projets = $stmt->fetch_all(MYSQLI_ASSOC);
        
        if (count($projets) > 0):
        ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Titre</th>
                        <th>Date de création</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($projets as $projet): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($projet['titre']); ?></td>
                        <td><?php echo formaterDate($projet['date_creation']); ?></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="../projets.php?id=<?php echo $projet['id']; ?>" class="btn btn-secondary" target="_blank">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="modifier-projet.php?id=<?php echo $projet['id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="gestion-projets.php?action=supprimer&id=<?php echo $projet['id']; ?>" class="btn btn-danger btn-delete">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="text-center p-3">
            <a href="gestion-projets.php" class="btn btn-outline-primary">Voir tous les projets</a>
        </div>
        <?php else: ?>
        <div class="text-center p-4">
            <p>Aucun projet n'a été ajouté pour le moment.</p>
            <a href="ajouter-projet.php" class="btn btn-success">
                <i class="fas fa-plus me-2"></i> Ajouter un nouveau projet
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Inclure le pied de page
require_once '../includes/footer.php';
?>
