<?php
// Page Mon espace - Espace personnel du membre
require_once 'includes/db.php';
require_once 'includes/fonctions.php';
session_start();

if (!estConnecte()) {
    $_SESSION['message'] = "Vous devez être connecté pour accéder à votre espace membre.";
    $_SESSION['type_message'] = "error";
    rediriger('connexion.php');
}

$utilisateur_id = $_SESSION['utilisateur_id'];

// Gestion de la modification des infos
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier_infos'])) {
    $email = nettoyer($_POST['email'] ?? '');
    $telephone = nettoyer($_POST['telephone'] ?? '');
    $classe = nettoyer($_POST['classe'] ?? '');
    $serie = nettoyer($_POST['serie'] ?? '');
    if (!estEmailValide($email)) {
        $error = "L'email n'est pas valide.";
    } elseif (!estTelephoneValide($telephone)) {
        $error = "Le téléphone n'est pas valide.";
    } elseif (empty($classe) || empty($serie)) {
        $error = "Classe et série obligatoires.";
    } else {
        $stmt = $mysqli->prepare("UPDATE membres SET email=?, telephone=?, classe=?, serie=? WHERE id=?");
        $stmt->bind_param("ssssi", $email, $telephone, $classe, $serie, $utilisateur_id);
        if ($stmt->execute()) {
            $success = "Informations mises à jour avec succès.";
        } else {
            $error = "Erreur lors de la mise à jour.";
        }
    }
}

// Gestion du changement de mot de passe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['changer_mdp'])) {
    $mdp_actuel = $_POST['mdp_actuel'] ?? '';
    $nouveau_mdp = $_POST['nouveau_mdp'] ?? '';
    $confirmer_mdp = $_POST['confirmer_mdp'] ?? '';
    $stmt = $mysqli->prepare("SELECT mot_de_passe FROM membres WHERE id = ?");
    $stmt->bind_param("i", $utilisateur_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    if (!password_verify($mdp_actuel, $row['mot_de_passe'])) {
        $error = "Mot de passe actuel incorrect.";
    } elseif (strlen($nouveau_mdp) < 8) {
        $error = "Le nouveau mot de passe doit contenir au moins 8 caractères.";
    } elseif ($nouveau_mdp !== $confirmer_mdp) {
        $error = "Les mots de passe ne correspondent pas.";
    } else {
        $mdp_hash = password_hash($nouveau_mdp, PASSWORD_DEFAULT);
        $stmt = $mysqli->prepare("UPDATE membres SET mot_de_passe=? WHERE id=?");
        $stmt->bind_param("si", $mdp_hash, $utilisateur_id);
        if ($stmt->execute()) {
            $success = "Mot de passe modifié avec succès.";
        } else {
            $error = "Erreur lors du changement de mot de passe.";
        }
    }
}

// Récupérer les infos à jour du membre
$stmt = $mysqli->prepare("SELECT nom, prenom, email, telephone, classe, serie, statut FROM membres WHERE id = ?");
$stmt->bind_param("i", $utilisateur_id);
$stmt->execute();
$result = $stmt->get_result();
$membre = $result->fetch_assoc();

$titre_page = "Mon espace";
require_once 'includes/header.php';
?>

<div class="container container-glass pt-5 pb-5">
    <div class="text-center mb-5">
        <h1 class="page-title-glass mb-3">Espace Membre</h1>
        <p class="text-muted">Gérez votre profil et suivez vos activités</p>
    </div>

    <div class="row g-4">
        <!-- Carte de Profil -->
        <div class="col-lg-4">
            <div class="card-glass h-100 position-relative animate__animated animate__fadeInLeft">
                <div class="card-body text-center">
                    <div class="position-relative d-inline-block mb-3">
                        <div class="rounded-circle border border-2 border-info p-1">
                            <img src="assets/default-avatar.png" onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($membre['prenom'].'+'.$membre['nom']); ?>&background=0D8ABC&color=fff&size=128'" alt="Avatar" class="rounded-circle" width="120" height="120">
                        </div>
                        <?php if ($membre['statut'] === 'valide'): ?>
                            <span class="position-absolute bottom-0 end-0 bg-success border border-dark rounded-circle p-2" title="Membre Vérifié">
                                <span class="visually-hidden">Vérifié</span>
                            </span>
                        <?php else: ?>
                             <span class="position-absolute bottom-0 end-0 bg-warning border border-dark rounded-circle p-2" title="En attente">
                                <span class="visually-hidden">En attente</span>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <h3 class="text-white mb-1"><?php echo htmlspecialchars($membre['prenom'] . ' ' . $membre['nom']); ?></h3>
                    <p class="text-accent mb-3"><?php echo htmlspecialchars($membre['classe'] . ' ' . $membre['serie']); ?></p>


                    
                    <?php if ($membre['statut'] === 'en_attente'): ?>
                        <div class="alert glass-alert mb-3 text-start">
                            <div class="d-flex">
                                <div class="me-2"><i class="fas fa-exclamation-triangle text-warning"></i></div>
                                <div>
                                    <h6 class="alert-heading fs-6 fw-bold mb-1">Cotisation requise</h6>
                                    <p class="mb-0 small">Réglez <strong>500 FCFA</strong> au trésorier (76 865 96 18) pour activer votre compte.</p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                     <div class="d-grid gap-2">
                        <a href="deconnexion.php" class="btn btn-outline-danger btn-sm rounded-pill"><i class="fas fa-sign-out-alt me-2"></i>Se déconnecter</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Informations & Sécurité -->
        <div class="col-lg-8">
            <div class="card-glass mb-4 animate__animated animate__fadeInRight">
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success glass-alert d-flex align-items-center mb-4">
                            <i class="fas fa-check-circle me-3 fs-4"></i>
                            <div><?php echo $success; ?></div>
                        </div>
                    <?php elseif ($error): ?>
                        <div class="alert alert-danger glass-alert d-flex align-items-center mb-4">
                            <i class="fas fa-exclamation-circle me-3 fs-4"></i>
                            <div><?php echo $error; ?></div>
                        </div>
                    <?php endif; ?>

                    <ul class="nav nav-pills nav-pills-glass mb-4" id="pills-tab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="pills-infos-tab" data-bs-toggle="pill" data-bs-target="#pills-infos" type="button" role="tab" aria-selected="true"><i class="fas fa-user-edit me-2"></i>Mes Infos</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="pills-security-tab" data-bs-toggle="pill" data-bs-target="#pills-security" type="button" role="tab" aria-selected="false"><i class="fas fa-shield-alt me-2"></i>Sécurité</button>
                        </li>

                    </ul>

                    <div class="tab-content" id="pills-tabContent">
                        
                        <!-- Onglet Infos -->
                        <div class="tab-pane fade show active" id="pills-infos" role="tabpanel">
                             <form method="post">
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label text-accent small text-uppercase fw-bold">Email</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-transparent border-end-0 text-accent" style="border-color: rgba(255,255,255,0.1);"><i class="fas fa-envelope"></i></span>
                                            <input type="email" class="form-control form-control-glass border-start-0 ps-0" name="email" value="<?php echo htmlspecialchars($membre['email']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-accent small text-uppercase fw-bold">Téléphone</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-transparent border-end-0 text-accent" style="border-color: rgba(255,255,255,0.1);"><i class="fas fa-phone"></i></span>
                                            <input type="text" class="form-control form-control-glass border-start-0 ps-0" name="telephone" value="<?php echo htmlspecialchars($membre['telephone']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-accent small text-uppercase fw-bold">Classe</label>
                                        <select class="form-select form-control-glass" name="classe" required>
                                            <option value="Seconde" <?php echo ($membre['classe'] == 'Seconde') ? 'selected' : ''; ?>>Seconde</option>
                                            <option value="Première" <?php echo ($membre['classe'] == 'Première') ? 'selected' : ''; ?>>Première</option>
                                            <option value="Terminale" <?php echo ($membre['classe'] == 'Terminale') ? 'selected' : ''; ?>>Terminale</option>
                                            <option value="Supérieur" <?php echo ($membre['classe'] == 'Supérieur') ? 'selected' : ''; ?>>Supérieur</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-accent small text-uppercase fw-bold">Série</label>
                                        <select class="form-select form-control-glass" name="serie" required>
                                            <option value="S" <?php echo ($membre['serie'] == 'S') ? 'selected' : ''; ?>>S</option>
                                            <option value="S1" <?php echo ($membre['serie'] == 'S1') ? 'selected' : ''; ?>>S1</option>
                                            <option value="S2" <?php echo ($membre['serie'] == 'S2') ? 'selected' : ''; ?>>S2</option>
                                            <option value="Autre" <?php echo ($membre['serie'] == 'Autre') ? 'selected' : ''; ?>>Autre</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <button type="submit" name="modifier_infos" class="btn btn-accent rounded-pill px-4"><i class="fas fa-save me-2"></i>Enregistrer</button>
                                </div>
                            </form>
                        </div>

                        <!-- Onglet Sécurité -->
                        <div class="tab-pane fade" id="pills-security" role="tabpanel">
                            <form method="post">
                                <div class="mb-3">
                                    <label class="form-label text-accent small text-uppercase fw-bold">Mot de passe actuel</label>
                                    <input type="password" class="form-control form-control-glass" name="mdp_actuel" required>
                                </div>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label text-accent small text-uppercase fw-bold">Nouveau mot de passe</label>
                                        <input type="password" class="form-control form-control-glass" name="nouveau_mdp" required placeholder="Min. 8 caractères">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-accent small text-uppercase fw-bold">Confirmer</label>
                                        <input type="password" class="form-control form-control-glass" name="confirmer_mdp" required>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <button type="submit" name="changer_mdp" class="btn btn-glass btn-sm rounded-pill px-4"><i class="fas fa-key me-2"></i>Mettre à jour</button>
                                </div>
                            </form>
                        </div>



                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div style="height: 100px;"></div>

<?php require_once 'includes/footer.php'; ?>