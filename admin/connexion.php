<?php
// Page de connexion admin
require_once '../includes/db.php';
require_once '../includes/fonctions.php';
session_start();

if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    rediriger('index.php');
}

$erreur = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = nettoyer($_POST['email'] ?? '');
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';

    if (empty($email) || empty($mot_de_passe)) {
        $erreur = "Tous les champs sont obligatoires.";
    } else {
        $stmt = $mysqli->prepare("SELECT * FROM admins WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin = $result->fetch_assoc();

        if ($admin && password_verify($mot_de_passe, $admin['mot_de_passe'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['role'] = 'admin';
            rediriger('index.php');
        } else {
            $erreur = "Email ou mot de passe incorrect.";
        }
    }
}

$titre_page = "Connexion admin";
require_once '../includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-5 col-md-7">
        <div class="card shadow-sm border-0 mt-5">
            <div class="card-header bg-primary text-white">
                <h1 class="h4 mb-0">Connexion administrateur</h1>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($erreur)): ?>
                    <div class="alert alert-danger">
                        <?php echo $erreur; ?>
                    </div>
                <?php endif; ?>
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email administrateur</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="mot_de_passe" class="form-label">Mot de passe</label>
                        <input type="password" class="form-control" id="mot_de_passe" name="mot_de_passe" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Connexion admin</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?> 