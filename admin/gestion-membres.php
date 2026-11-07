<?php
/**
 * Gestion des membres du Club Scientifique
 * Permet aux administrateurs de gérer les candidatures et les membres du club
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

// Déterminer l'onglet actif par défaut ou via le paramètre GET
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'attente';

// Traitement des actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Assurez-vous que l'onglet courant est conservé pour la redirection
    $current_tab_for_redirect = isset($_GET['tab']) ? $_GET['tab'] : 'attente';

    // Action: Valider une candidature
    if ($_GET['action'] === 'valider') {
        try {
            // 1. Récupérer les infos du membre AVANT validation pour l'email
            $stmt_info = $mysqli->prepare("SELECT email, nom, prenom FROM membres WHERE id = ?");
            $stmt_info->bind_param("i", $id);
            $stmt_info->execute();
            $result_info = $stmt_info->get_result();
            $membre_info = $result_info->fetch_assoc();
            $stmt_info->close();

            // 2. Valider le compte
            $stmt = $mysqli->prepare("UPDATE membres SET statut = 'valide' WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            // 3. Envoyer l'email de notification (Via SMTP Gmail)
            if ($membre_info && !empty($membre_info['email'])) {
                require_once '../includes/SMTPMailer.php';
                
                // --- CONFIGURATION GMAIL ---
                // Mots de passe d'application Google
                $smtp_password = 'iatn ysbs umdg jeok'; 
                $smtp_user = 'abibdrame70@gmail.com';
                // ---------------------------

                // Instanciation et envoi
                $mailer = new SMTPMailer($smtp_user, $smtp_password);
                
                $to = $membre_info['email'];
                $subject = "Validation de votre compte - Club Scientifique LYCEE MABA DIAKHOU BA !";
                
                $message = "
                <html>
                <head>
                  <title>Bienvenue au Club Scientifique LYCEE MABA DIAKHOU BA !</title>
                </head>
                <body>
                  <h2 style='color: #0d6efd;'>Félicitations " . htmlspecialchars($membre_info['prenom']) . " ! 🎉</h2>
                  <p>Votre demande d'adhésion au <strong>Club Scientifique LYCEE MABA DIAKHOU BA !</strong> a été validée avec succès par l'administration.</p>
                  <p>Vous pouvez désormais vous connecter à votre espace membre, participer aux projets et accéder à toutes nos ressources.</p>
                  <p><a href='http://" . $_SERVER['HTTP_HOST'] . "/mon-espace.php' style='background-color: #0d6efd; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Accéder à mon espace</a></p>
                  <hr>
                  <p style='font-size: 12px; color: #666;'>Ceci est un message automatique, merci de ne pas y répondre.</p>
                </body>
                </html>
                ";

                // Envoi via SMTP
                $mailer->send($to, $subject, $message, [
                    'From' => 'Club Scientifique <' . $smtp_user . '>'
                ]);
            }

            $_SESSION['message'] = "Le membre a été validé et notifié par email avec succès.";
            $_SESSION['type_message'] = "success";
        } catch (Exception $e) {
            $_SESSION['message'] = "Une erreur est survenue lors de la validation du membre.";
            $_SESSION['type_message'] = "error";
        }
    }
    // Action: Refuser une candidature
    else if ($_GET['action'] === 'refuser') {
        try {
            $stmt = $mysqli->prepare("UPDATE membres SET statut = 'refuse' WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $_SESSION['message'] = "La candidature a été refusée.";
            $_SESSION['type_message'] = "success";
        } catch (Exception $e) {
            $_SESSION['message'] = "Une erreur est survenue lors du refus de la candidature.";
            $_SESSION['type_message'] = "error";
        }
    }
    // Action: Supprimer un membre ou une candidature
    else if ($_GET['action'] === 'supprimer') {
        try {
            $stmt = $mysqli->prepare("DELETE FROM membres WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $_SESSION['message'] = "Le membre a été supprimé avec succès.";
            $_SESSION['type_message'] = "success";
        } catch (Exception $e) {
            $_SESSION['message'] = "Une erreur est survenue lors de la suppression du membre.";
            $_SESSION['type_message'] = "error";
        }
    }
    // Action: Dévalider un membre (passe de 'valide' à 'en_attente')
    else if ($_GET['action'] === 'devalider') {
        try {
            $stmt = $mysqli->prepare("UPDATE membres SET statut = 'en_attente' WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            $_SESSION['message'] = "Le membre a été dévalidé et replacé en attente.";
            $_SESSION['type_message'] = "success";
        } catch (Exception $e) {
            $_SESSION['message'] = "Une erreur est survenue lors de la dévalidation du membre.";
            $_SESSION['type_message'] = "error";
        }
    }

    // Redirection vers la même page en conservant l'onglet actif
    header("Location: gestion-membres.php?tab=" . urlencode($current_tab_for_redirect));
    exit;
}

// Récupérer les membres par statut
try {
    // Candidatures en attente
    $stmt = $mysqli->prepare("SELECT * FROM membres WHERE statut = 'en_attente' ORDER BY date_candidature DESC");
    $stmt->execute();
    $membresAttente = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Membres validés
    $stmt = $mysqli->prepare("SELECT * FROM membres WHERE statut = 'valide' ORDER BY nom, prenom");
    $stmt->execute();
    $membresValides = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Candidatures refusées
    $stmt = $mysqli->prepare("SELECT * FROM membres WHERE statut = 'refuse' ORDER BY date_candidature DESC");
    $stmt->execute();
    $membresRefuses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $_SESSION['message'] = "Une erreur est survenue lors de la récupération des membres.";
    $_SESSION['type_message'] = "error";

    $membresAttente = [];
    $membresValides = [];
    $membresRefuses = [];
}

// Définir le titre de la page
$titre_page = "Gestion des membres";

// Inclure l'en-tête
require_once '../includes/header.php';
?>

<style>
/* Style pour éviter les débordements de texte dans les tableaux */
.table {
    table-layout: fixed; /* Maintient les largeurs de colonnes fixes pour éviter les débordements */
    width: 100%;
    margin-bottom: 0;
}

.table td, .table th {
    overflow: hidden; /* Cache le contenu qui dépasse */
    text-overflow: ellipsis; /* Ajoute des points de suspension (...) pour le texte caché */
    white-space: nowrap; /* Empêche le texte de passer à la ligne */
    max-width: 100%; /* Assure que le contenu ne dépasse pas la largeur de la cellule */
    padding: 0.5rem; /* Ajuste le padding pour un meilleur espacement */
}

/* Styles pour les actions - Par défaut, visible sur les grands écrans */
.actions-cell {
    text-align: center; /* Centre les boutons */
}

/* Assurer que les boutons d'action ne sont pas trop grands */
.btn-group-sm > .btn {
    padding: 0.25rem 0.5rem; /* Réduit la taille des boutons */
    font-size: 0.8rem; /* Réduit la taille de la police des boutons */
}

/* Rendre les groupes de boutons flexibles pour qu'ils puissent s'ajuster */
.btn-group-sm {
    display: flex; /* Utilise flexbox pour aligner les boutons */
    flex-wrap: nowrap; /* Empêche les boutons de passer à la ligne sur les petits écrans pour garder un design compact */
    justify-content: space-around; /* Distribue l'espace également entre les boutons */
}

/* Pour l'affichage responsive */
.table-responsive {
    overflow-x: auto; /* Permet un défilement horizontal si le contenu dépasse */
    -webkit-overflow-scrolling: touch; /* Améliore le défilement sur les appareils iOS */
    margin-bottom: 0;
}

/* Styles pour la mise en page responsive */
.container-fluid {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    padding: 0; /* Supprime le padding par défaut pour les petits écrans */
}

.row {
    flex: 1;
    margin: 0;
}

main {
    padding: 1.5rem;
    min-height: calc(100vh - 60px); /* Ajustez selon la hauteur de votre header */
    display: flex;
    flex-direction: column;
}

.card {
    flex: 1;
    display: flex;
    flex-direction: column;
    margin-bottom: 1.5rem;
}

.card-body {
    flex: 1;
    display: flex;
    flex-direction: column;
    padding: 0;
}

.tab-content {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.tab-pane {
    flex: 1;
    display: flex;
    flex-direction: column;
}

/* Ajustements pour les modals */
.modal-dialog {
    max-width: 500px;
    margin: 1.75rem auto;
}

.modal-content {
    max-height: 90vh;
    overflow-y: auto;
}

/* Media queries pour la responsivité */

/* Styles pour les écrans de desktop et plus grands (>= 768px) */
@media (min-width: 768px) {
    .table th:nth-child(1), .table td:nth-child(1) { /* Nom complet */
        width: 25%;
    }
    .table th:nth-child(2), .table td:nth-child(2) { /* Email */
        width: 20%;
        display: table-cell;
    }
    .table th:nth-child(3), .table td:nth-child(3) { /* Téléphone */
        width: 20%;
        display: table-cell;
    }
    .table th:nth-child(4), .table td:nth-child(4) { /* Classe */
        width: 20%;
    }
    .table th:nth-child(5), .table td:nth-child(5) { /* Date de candidature */
        width: 15%;
        display: table-cell;
    }
    .table th:nth-child(6), .table td:nth-child(6) { /* Actions */
        width: 20%;
        min-width: 140px;
    }
    .details-btn-mobile {
        display: none !important;
    }
    .btn-group-sm {
        display: flex !important;
    }
}

/* Styles pour les écrans plus petits que 768px (tablettes et mobiles) */
@media (max-width: 767.98px) {
    main {
        padding: 1rem;
    }

    .table th, .table td {
        font-size: 0.85rem;
        padding: 0.4rem;
    }

    /* Masque les colonnes Téléphone, Email et Date de candidature */
    .table th:nth-child(2), .table td:nth-child(2),
    .table th:nth-child(3), .table td:nth-child(3),
    .table th:nth-child(5), .table td:nth-child(5) {
        display: none;
    }

    /* Réajuste les largeurs des colonnes restantes */
    .table th:nth-child(1), .table td:nth-child(1) { /* Nom complet */
        width: 60%;
    }
    .table th:nth-child(4), .table td:nth-child(4) { /* Classe */
        width: 25%;
    }
    .table th:nth-child(6), .table td:nth-child(6) { /* Actions */
        width: 15%;
        min-width: 50px;
    }

    /* Masque le groupe de boutons d'action dans le tableau */
    .btn-group-sm {
        display: none !important;
    }

    /* Affiche le bouton "Détails/Actions" spécifique mobile */
    .details-btn-mobile {
        display: inline-block !important;
        padding: 0.2rem 0.4rem;
        font-size: 0.75rem;
    }
}

/* Styles pour les écrans très petits (ex: téléphones en mode portrait) */
@media (max-width: 480px) {
    .table th, .table td {
        font-size: 0.8rem;
        padding: 0.3rem;
    }
    /* Masque la colonne Classe sur les très petits écrans */
    .table th:nth-child(4), .table td:nth-child(4) {
        display: none;
    }
    .table th:nth-child(1), .table td:nth-child(1) { /* Nom complet */
        width: 80%;
    }
    .table th:nth-child(6), .table td:nth-child(6) { /* Actions */
        width: 20%;
        min-width: 50px;
    }
    .details-btn-mobile {
        font-size: 0.7rem;
        padding: 0.15rem 0.3rem;
    }
}

/* Styles pour le drawer mobile */
.mobile-drawer {
    position: fixed;
    top: 50%;
    right: -100%;
    width: 90%;
    max-width: 400px;
    max-height: 80vh;
    background: white;
    z-index: 1060;
    transition: right 0.3s ease;
    overflow-y: auto;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transform: translateY(-50%);
}

.mobile-drawer.show {
    right: 5%;
}

.drawer-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1050;
    display: none;
}

.drawer-backdrop.show {
    display: block;
}

.drawer-header {
    padding: 1rem;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8f9fa;
    border-radius: 8px 8px 0 0;
}

.drawer-body {
    padding: 1rem;
}

.drawer-footer {
    padding: 1rem;
    border-top: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    gap: 0.5rem;
    background: #f8f9fa;
    border-radius: 0 0 8px 8px;
}

/* Cacher les modals sur mobile */
@media (max-width: 767.98px) {
    .modal {
        display: none !important;
    }
}
</style>

<div class="container-fluid">
    <div class="row">
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h1 class="h2 mb-0">Gestion des membres</h1>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Retour au tableau de bord
                </a>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <ul class="nav nav-tabs" id="membresTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?php echo ($active_tab === 'attente') ? 'active' : ''; ?>" id="attente-tab" data-bs-toggle="tab" data-bs-target="#attente" type="button" role="tab" aria-controls="attente" aria-selected="<?php echo ($active_tab === 'attente') ? 'true' : 'false'; ?>">
                                En attente <span class="badge bg-warning text-dark ms-1"><?php echo count($membresAttente); ?></span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?php echo ($active_tab === 'valides') ? 'active' : ''; ?>" id="valides-tab" data-bs-toggle="tab" data-bs-target="#valides" type="button" role="tab" aria-controls="valides" aria-selected="<?php echo ($active_tab === 'valides') ? 'true' : 'false'; ?>">
                                Membres validés <span class="badge bg-success ms-1"><?php echo count($membresValides); ?></span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?php echo ($active_tab === 'refuses') ? 'active' : ''; ?>" id="refuses-tab" data-bs-toggle="tab" data-bs-target="#refuses" type="button" role="tab" aria-controls="refuses" aria-selected="<?php echo ($active_tab === 'refuses') ? 'true' : 'false'; ?>">
                                Candidatures refusées <span class="badge bg-danger ms-1"><?php echo count($membresRefuses); ?></span>
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="membresTabContent">
                        <!-- Tableau des candidatures en attente -->
                        <div class="tab-pane fade <?php echo ($active_tab === 'attente') ? 'show active' : ''; ?>" id="attente" role="tabpanel" aria-labelledby="attente-tab">
                            <?php if (empty($membresAttente)): ?>
                                <div class="alert alert-info mt-3 mx-3">
                                    <i class="fas fa-info-circle me-2"></i> Aucune candidature en attente.
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Nom complet</th>
                                                <th>Email</th>
                                                <th>Téléphone</th>
                                                <th>Classe</th>
                                                <th>Date candidature</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($membresAttente as $membre): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($membre['nom']) . ' ' . htmlspecialchars($membre['prenom']); ?></td>
                                                    <td><?php echo htmlspecialchars($membre['email']); ?></td>
                                                    <td><?php echo htmlspecialchars($membre['telephone']); ?></td>
                                                    <td><?php echo htmlspecialchars($membre['classe']) . ' (' . htmlspecialchars($membre['serie']) . ')'; ?></td>
                                                    <td><?php echo date('d/m/Y', strtotime($membre['date_candidature'])); ?></td>
                                                    <td class="actions-cell">
                                                        <!-- Bouton pour les petits écrans -->
                                                        <button type="button" class="btn btn-sm btn-primary details-btn-mobile" 
                                                                data-id="<?php echo $membre['id']; ?>">
                                                            <i class="fas fa-ellipsis-h"></i>
                                                        </button>
                                                        
                                                        <!-- Groupe de boutons pour les écrans moyens et grands -->
                                                        <div class="btn-group btn-group-sm" role="group">
                                                            <a href="gestion-membres.php?action=valider&id=<?php echo $membre['id']; ?>&tab=attente" 
                                                               class="btn btn-success" 
                                                               onclick="return confirm('Voulez-vous vraiment valider cette candidature?')">
                                                                <i class="fas fa-check"></i>
                                                            </a>
                                                            <a href="gestion-membres.php?action=refuser&id=<?php echo $membre['id']; ?>&tab=attente" 
                                                               class="btn btn-danger" 
                                                               onclick="return confirm('Voulez-vous vraiment refuser cette candidature?')">
                                                                <i class="fas fa-times"></i>
                                                            </a>
                                                            <a href="gestion-membres.php?action=supprimer&id=<?php echo $membre['id']; ?>&tab=attente" 
                                                               class="btn btn-dark" 
                                                               onclick="return confirm('Voulez-vous vraiment supprimer définitivement cette candidature?')">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Tableau des membres validés -->
                        <div class="tab-pane fade <?php echo ($active_tab === 'valides') ? 'show active' : ''; ?>" id="valides" role="tabpanel" aria-labelledby="valides-tab">
                            <?php if (empty($membresValides)): ?>
                                <div class="alert alert-info mt-3 mx-3">
                                    <i class="fas fa-info-circle me-2"></i> Aucun membre validé.
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Nom complet</th>
                                                <th>Email</th>
                                                <th>Téléphone</th>
                                                <th>Classe</th>
                                                <th>Date candidature</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($membresValides as $membre): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($membre['nom']) . ' ' . htmlspecialchars($membre['prenom']); ?></td>
                                                    <td><?php echo htmlspecialchars($membre['email']); ?></td>
                                                    <td><?php echo htmlspecialchars($membre['telephone']); ?></td>
                                                    <td><?php echo htmlspecialchars($membre['classe']) . ' (' . htmlspecialchars($membre['serie']) . ')'; ?></td>
                                                    <td><?php echo date('d/m/Y', strtotime($membre['date_candidature'])); ?></td>
                                                    <td class="actions-cell">
                                                        <!-- Bouton pour les petits écrans -->
                                                        <button type="button" class="btn btn-sm btn-primary details-btn-mobile" 
                                                                data-id="<?php echo $membre['id']; ?>">
                                                            <i class="fas fa-ellipsis-h"></i>
                                                        </button>
                                                        
                                                        <!-- Groupe de boutons pour les écrans moyens et grands -->
                                                        <div class="btn-group btn-group-sm" role="group">
                                                            <a href="gestion-membres.php?action=devalider&id=<?php echo $membre['id']; ?>&tab=valides" 
                                                               class="btn btn-warning" 
                                                               onclick="return confirm('Voulez-vous vraiment dévalider ce membre?')">
                                                                <i class="fas fa-undo"></i>
                                                            </a>
                                                            <a href="gestion-membres.php?action=refuser&id=<?php echo $membre['id']; ?>&tab=valides" 
                                                               class="btn btn-danger" 
                                                               onclick="return confirm('Voulez-vous vraiment refuser ce membre?')">
                                                                <i class="fas fa-times"></i>
                                                            </a>
                                                            <a href="gestion-membres.php?action=supprimer&id=<?php echo $membre['id']; ?>&tab=valides" 
                                                               class="btn btn-dark" 
                                                               onclick="return confirm('Voulez-vous vraiment supprimer définitivement ce membre?')">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Tableau des candidatures refusées -->
                        <div class="tab-pane fade <?php echo ($active_tab === 'refuses') ? 'show active' : ''; ?>" id="refuses" role="tabpanel" aria-labelledby="refuses-tab">
                            <?php if (empty($membresRefuses)): ?>
                                <div class="alert alert-info mt-3 mx-3">
                                    <i class="fas fa-info-circle me-2"></i> Aucune candidature refusée.
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Nom complet</th>
                                                <th>Email</th>
                                                <th>Téléphone</th>
                                                <th>Classe</th>
                                                <th>Date candidature</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($membresRefuses as $membre): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($membre['nom']) . ' ' . htmlspecialchars($membre['prenom']); ?></td>
                                                    <td><?php echo htmlspecialchars($membre['email']); ?></td>
                                                    <td><?php echo htmlspecialchars($membre['telephone']); ?></td>
                                                    <td><?php echo htmlspecialchars($membre['classe']) . ' (' . htmlspecialchars($membre['serie']) . ')'; ?></td>
                                                    <td><?php echo date('d/m/Y', strtotime($membre['date_candidature'])); ?></td>
                                                    <td class="actions-cell">
                                                        <!-- Bouton pour les petits écrans -->
                                                        <button type="button" class="btn btn-sm btn-primary details-btn-mobile" 
                                                                data-id="<?php echo $membre['id']; ?>">
                                                            <i class="fas fa-ellipsis-h"></i>
                                                        </button>
                                                        
                                                        <!-- Groupe de boutons pour les écrans moyens et grands -->
                                                        <div class="btn-group btn-group-sm" role="group">
                                                            <button type="button" class="btn btn-info details-btn" 
                                                                    data-id="<?php echo $membre['id']; ?>">
                                                                <i class="fas fa-info-circle"></i>
                                                            </button>
                                                            <a href="gestion-membres.php?action=valider&id=<?php echo $membre['id']; ?>&tab=refuses" 
                                                               class="btn btn-success" 
                                                               onclick="return confirm('Voulez-vous vraiment valider cette candidature?')">
                                                                <i class="fas fa-check"></i>
                                                            </a>
                                                            <a href="gestion-membres.php?action=supprimer&id=<?php echo $membre['id']; ?>&tab=refuses" 
                                                               class="btn btn-dark" 
                                                               onclick="return confirm('Voulez-vous vraiment supprimer définitivement cette candidature?')">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Remplacer les modals par des drawers -->
<?php foreach ($membresAttente as $membre): ?>
    <div class="mobile-drawer" id="drawer<?php echo $membre['id']; ?>">
        <div class="drawer-header">
            <h5 class="mb-0">Détails du membre</h5>
            <button type="button" class="btn-close" onclick="closeDrawer(<?php echo $membre['id']; ?>)"></button>
        </div>
        <div class="drawer-body">
            <p><strong>Nom complet:</strong> <?php echo htmlspecialchars($membre['nom']) . ' ' . htmlspecialchars($membre['prenom']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($membre['email']); ?></p>
            <p><strong>Téléphone:</strong> <?php echo htmlspecialchars($membre['telephone']); ?></p>
            <p><strong>Classe:</strong> <?php echo htmlspecialchars($membre['classe']) . ' (' . htmlspecialchars($membre['serie']) . ')'; ?></p>
            <p><strong>Date de candidature:</strong> <?php echo date('d/m/Y', strtotime($membre['date_candidature'])); ?></p>
        </div>
        <div class="drawer-footer">
            <a href="gestion-membres.php?action=valider&id=<?php echo $membre['id']; ?>&tab=attente" 
               class="btn btn-success" 
               onclick="return confirm('Voulez-vous vraiment valider cette candidature?')">
                <i class="fas fa-check me-1"></i> Valider
            </a>
            <a href="gestion-membres.php?action=refuser&id=<?php echo $membre['id']; ?>&tab=attente" 
               class="btn btn-danger" 
               onclick="return confirm('Voulez-vous vraiment refuser cette candidature?')">
                <i class="fas fa-times me-1"></i> Refuser
            </a>
            <a href="gestion-membres.php?action=supprimer&id=<?php echo $membre['id']; ?>&tab=attente" 
               class="btn btn-dark" 
               onclick="return confirm('Voulez-vous vraiment supprimer définitivement cette candidature?')">
                <i class="fas fa-trash-alt me-1"></i> Supprimer
            </a>
        </div>
    </div>
    <div class="drawer-backdrop" id="backdrop<?php echo $membre['id']; ?>" onclick="closeDrawer(<?php echo $membre['id']; ?>)"></div>
<?php endforeach; ?>

<?php foreach ($membresValides as $membre): ?>
    <div class="mobile-drawer" id="drawer<?php echo $membre['id']; ?>">
        <div class="drawer-header">
            <h5 class="mb-0">Détails du membre</h5>
            <button type="button" class="btn-close" onclick="closeDrawer(<?php echo $membre['id']; ?>)"></button>
        </div>
        <div class="drawer-body">
            <p><strong>Nom complet:</strong> <?php echo htmlspecialchars($membre['nom']) . ' ' . htmlspecialchars($membre['prenom']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($membre['email']); ?></p>
            <p><strong>Téléphone:</strong> <?php echo htmlspecialchars($membre['telephone']); ?></p>
            <p><strong>Classe:</strong> <?php echo htmlspecialchars($membre['classe']) . ' (' . htmlspecialchars($membre['serie']) . ')'; ?></p>
            <p><strong>Date de candidature:</strong> <?php echo date('d/m/Y', strtotime($membre['date_candidature'])); ?></p>
        </div>
        <div class="drawer-footer">
            <a href="gestion-membres.php?action=devalider&id=<?php echo $membre['id']; ?>&tab=valides" 
               class="btn btn-warning" 
               onclick="return confirm('Voulez-vous vraiment dévalider ce membre?')">
                <i class="fas fa-undo me-1"></i> Dévalider
            </a>
            <a href="gestion-membres.php?action=refuser&id=<?php echo $membre['id']; ?>&tab=valides" 
               class="btn btn-danger" 
               onclick="return confirm('Voulez-vous vraiment refuser ce membre?')">
                <i class="fas fa-times me-1"></i> Refuser
            </a>
            <a href="gestion-membres.php?action=supprimer&id=<?php echo $membre['id']; ?>&tab=valides" 
               class="btn btn-dark" 
               onclick="return confirm('Voulez-vous vraiment supprimer définitivement ce membre?')">
                <i class="fas fa-trash-alt me-1"></i> Supprimer
            </a>
        </div>
    </div>
    <div class="drawer-backdrop" id="backdrop<?php echo $membre['id']; ?>" onclick="closeDrawer(<?php echo $membre['id']; ?>)"></div>
<?php endforeach; ?>

<?php foreach ($membresRefuses as $membre): ?>
    <div class="mobile-drawer" id="drawer<?php echo $membre['id']; ?>">
        <div class="drawer-header">
            <h5 class="mb-0">Détails du membre</h5>
            <button type="button" class="btn-close" onclick="closeDrawer(<?php echo $membre['id']; ?>)"></button>
        </div>
        <div class="drawer-body">
            <p><strong>Nom complet:</strong> <?php echo htmlspecialchars($membre['nom']) . ' ' . htmlspecialchars($membre['prenom']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($membre['email']); ?></p>
            <p><strong>Téléphone:</strong> <?php echo htmlspecialchars($membre['telephone']); ?></p>
            <p><strong>Classe:</strong> <?php echo htmlspecialchars($membre['classe']) . ' (' . htmlspecialchars($membre['serie']) . ')'; ?></p>
            <p><strong>Date de candidature:</strong> <?php echo date('d/m/Y', strtotime($membre['date_candidature'])); ?></p>
        </div>
        <div class="drawer-footer">
            <a href="gestion-membres.php?action=valider&id=<?php echo $membre['id']; ?>&tab=refuses" 
               class="btn btn-success" 
               onclick="return confirm('Voulez-vous vraiment valider cette candidature?')">
                <i class="fas fa-check me-1"></i> Valider
            </a>
            <a href="gestion-membres.php?action=supprimer&id=<?php echo $membre['id']; ?>&tab=refuses" 
               class="btn btn-dark" 
               onclick="return confirm('Voulez-vous vraiment supprimer définitivement cette candidature?')">
                <i class="fas fa-trash-alt me-1"></i> Supprimer
            </a>
        </div>
    </div>
    <div class="drawer-backdrop" id="backdrop<?php echo $membre['id']; ?>" onclick="closeDrawer(<?php echo $membre['id']; ?>)"></div>
<?php endforeach; ?>

<script>
function openDrawer(id) {
    const drawer = document.getElementById('drawer' + id);
    const backdrop = document.getElementById('backdrop' + id);
    
    if (drawer && backdrop) {
        drawer.classList.add('show');
        backdrop.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
}

function closeDrawer(id) {
    const drawer = document.getElementById('drawer' + id);
    const backdrop = document.getElementById('backdrop' + id);
    
    if (drawer && backdrop) {
        drawer.classList.remove('show');
        backdrop.classList.remove('show');
        document.body.style.overflow = '';
    }
}

// Modifier les boutons pour utiliser le drawer
document.addEventListener('DOMContentLoaded', function() {
    // Gestionnaire pour les boutons mobiles
    document.querySelectorAll('.details-btn-mobile').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            if (id) {
                openDrawer(id);
            }
        });
    });

    // Gestionnaire pour les boutons de détails sur grands écrans
    document.querySelectorAll('.details-btn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            if (id) {
                openDrawer(id);
            }
        });
    });
});
</script>

<?php
// Inclure le pied de page
require_once '../includes/footer.php';
?>
