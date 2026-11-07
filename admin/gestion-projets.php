<?php
/**
 * Gestion des projets du Club Scientifique
 * 
 * Permet aux administrateurs de gérer les projets (liste, ajout, modification, suppression)
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

// Traitement de la suppression d'un projet
if (isset($_GET['action']) && $_GET['action'] === 'supprimer' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    if ($id > 0) {
        try {
            // Supprimer le projet
            $stmt = $mysqli->prepare("DELETE FROM projets WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            
            // Message de succès
            $_SESSION['message'] = "Le projet a été supprimé avec succès.";
            $_SESSION['type_message'] = "success";
        } catch (Exception $e) {
            // Message d'erreur
            $_SESSION['message'] = "Une erreur est survenue lors de la suppression du projet.";
            $_SESSION['type_message'] = "error";
        }
    }
    
    // Rediriger pour éviter la ré-exécution lors d'un rafraîchissement
    rediriger('gestion-projets.php');
}

// Récupérer tous les projets
$stmt = $mysqli->query("SELECT * FROM projets ORDER BY date_creation DESC");
$projets = $stmt->fetch_all(MYSQLI_ASSOC);

// Titre de la page pour l'affichage dans l'en-tête
$titre_page = "Gestion des projets";

// Inclure l'en-tête
require_once '../includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h2 mb-0">Gestion des projets</h1>
    <div>
        <a href="index.php" class="btn btn-outline-secondary me-2">
            <i class="fas fa-arrow-left me-1"></i> Retour au tableau de bord
        </a>
        <a href="ajouter-projet.php" class="btn btn-success">
            <i class="fas fa-plus me-1"></i> Ajouter un nouveau projet
        </a>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <?php if (count($projets) > 0): ?>
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
                        <td class="fw-semibold"><?php echo htmlspecialchars($projet['titre']); ?></td>
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
        <?php else: ?>
        <div class="alert alert-info m-3">
            Aucun projet n'a été ajouté. Commencez par <a href="ajouter-projet.php" class="alert-link">créer votre premier projet</a>.
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Inclure le pied de page
require_once '../includes/footer.php';
?>
