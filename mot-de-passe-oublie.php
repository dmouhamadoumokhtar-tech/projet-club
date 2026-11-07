<?php
/**
 * Page Mot de passe oublié
 */

require_once 'includes/db.php';
require_once 'includes/fonctions.php';
require_once 'includes/config_mail.php';
require_once 'includes/SMTPMailer.php';

session_start();

// Rediriger si déjà connecté
if (estConnecte()) {
    rediriger('index.php');
}

$email = '';
$message = '';
$type_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = nettoyer($_POST['email'] ?? '');

    if (empty($email) || !estEmailValide($email)) {
        $message = "Veuillez entrer une adresse email valide.";
        $type_message = "danger";
    } else {
        // Vérifier si l'email existe (Admin ou Membre)
        $user_found = false;
        
        // 1. Check Admins
        $stmt = $mysqli->prepare("SELECT id FROM admins WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) $user_found = true;
        
        // 2. Check Membres
        if (!$user_found) {
            $stmt = $mysqli->prepare("SELECT id FROM membres WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) $user_found = true;
        }

        if ($user_found) {
            // Générer token
            $token = bin2hex(random_bytes(32));
            $date_creation = date('Y-m-d H:i:s');
            
            // Sauvegarder dans password_resets
            // On supprime d'abord les anciens tokens pour cet email
            $stmt = $mysqli->prepare("DELETE FROM password_resets WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            
            $stmt = $mysqli->prepare("INSERT INTO password_resets (email, token, created_at) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $email, $token, $date_creation);
            
            if ($stmt->execute()) {
                // Envoyer l'email
                $resetLink = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . dirname($_SERVER['PHP_SELF']) . "/reinitialisation-mot-de-passe.php?token=$token";
                
                $sujet = "Réinitialisation de votre mot de passe";
                $corps = "
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset='UTF-8'>
                    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                    <title>Réinitialisation de mot de passe</title>
                </head>
                <body style='margin: 0; padding: 0; background-color: #020c1b; font-family: sans-serif; color: #d1d5db;'>
                    <table role='presentation' border='0' cellpadding='0' cellspacing='0' width='100%'>
                        <tr>
                            <td align='center' style='padding: 40px 0;'>
                                <table role='presentation' border='0' cellpadding='0' cellspacing='0' width='600' style='border-collapse: collapse; max-width: 600px; width: 100%;'>
                                    <!-- Header -->
                                    <tr>
                                        <td align='center' style='padding: 20px 0;'>
                                            <h1 style='color: #64ffda; margin: 0; font-size: 24px; text-transform: uppercase; letter-spacing: 2px;'>Club Scientifique LMDB</h1>
                                        </td>
                                    </tr>
                                    <!-- Card -->
                                    <tr>
                                        <td style='background-color: #112240; padding: 40px; border-radius: 10px; border: 1px solid rgba(255, 255, 255, 0.1); box-shadow: 0 4px 6px rgba(0,0,0,0.3);'>
                                            <h2 style='color: #ffffff; margin-top: 0; margin-bottom: 20px; font-size: 22px; text-align: center;'>Réinitialisation de mot de passe</h2>
                                            <p style='margin-bottom: 20px; line-height: 1.6;'>Une demande de réinitialisation de mot de passe a été effectuée pour votre compte.</p>
                                            <p style='margin-bottom: 30px; line-height: 1.6;'>Si vous êtes à l'origine de cette demande, cliquez sur le bouton ci-dessous pour sécuriser votre compte :</p>
                                            
                                            <table role='presentation' border='0' cellpadding='0' cellspacing='0' width='100%'>
                                                <tr>
                                                    <td align='center'>
                                                        <a href='$resetLink' style='background-color: #64ffda; color: #020c1b; padding: 14px 30px; text-decoration: none; border-radius: 50px; font-weight: bold; display: inline-block; text-transform: uppercase; font-size: 14px; box-shadow: 0 0 10px rgba(100, 255, 218, 0.4);'>Réinitialiser mon mot de passe</a>
                                                    </td>
                                                </tr>
                                            </table>
                                            
                                            <p style='margin-top: 30px; margin-bottom: 0; font-size: 13px; color: #8892b0; text-align: center;'>Ce lien est valide pendant 24 heures.</p>
                                        </td>
                                    </tr>
                                    <!-- Footer -->
                                    <tr>
                                        <td align='center' style='padding: 20px; color: #8892b0; font-size: 12px;'>
                                            <p style='margin: 0;'>Si vous n'avez pas demandé cette action, veuillez ignorer cet email.</p>
                                            <p style='margin: 10px 0 0;'>&copy; " . date('Y') . " Club Scientifique LMDB. Tous droits réservés.</p>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </body>
                </html>
                ";
                
                $mailer = new SMTPMailer(SMTP_USER, SMTP_PASS);
                if ($mailer->send($email, $sujet, $corps, ['From' => 'Club Scientifique <'.SMTP_USER.'>'])) {
                    $message = "Un email de réinitialisation a été envoyé à $email. Veuillez vérifier votre boîte de réception.";
                    $type_message = "success";
                } else {
                    $message = "Erreur lors de l'envoi de l'email. Veuillez réessayer plus tard.";
                    $type_message = "danger";
                }
            } else {
                $message = "Une erreur est survenue. Veuillez réessayer.";
                $type_message = "danger";
            }
        } else {
            $message = "Aucun compte n'est associé à cet email.";
            $type_message = "warning";
        }
    }
}

$titre_page = "Mot de passe oublié";
require_once 'includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center mb-5 pb-5" style="margin-top: 50px;">
        <div class="col-lg-5 col-md-7">
            <div class="text-center mb-4">
                <h1 class="page-title-glass mb-3">Récupération</h1>
                <p class="text-muted">Retrouvez l'accès à votre compte</p>
            </div>

            <div class="card-glass shadow-lg border-0 overflow-hidden">
                <div class="card-body p-4 p-md-5">
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?php echo $type_message; ?> glass-alert text-center mb-4">
                            <?php if ($type_message == 'success'): ?>
                                <i class="fas fa-check-circle me-2"></i>
                            <?php elseif ($type_message == 'danger'): ?>
                                <i class="fas fa-exclamation-circle me-2"></i>
                            <?php else: ?>
                                <i class="fas fa-info-circle me-2"></i>
                            <?php endif; ?>
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($type_message !== 'success'): ?>
                    <form method="post" action="">
                        <div class="mb-4">
                            <label for="email" class="form-label text-accent text-uppercase fw-bold">Votre Email</label>
                            <div class="input-group">
                                <span class="input-group-text bg-transparent border-end-0 text-accent" style="border-color: rgba(255,255,255,0.1);">
                                    <i class="fas fa-envelope"></i>
                                </span>
                                <input type="email" class="form-control form-control-glass border-start-0 ps-0" id="email" name="email" required placeholder="exemple@email.com" value="<?php echo htmlspecialchars($email); ?>">
                            </div>
                            <div class="form-text text-muted mt-2">Nous vous enverrons un lien sécurisé pour changer votre mot de passe.</div>
                        </div>
                        
                        <div class="d-grid mb-4">
                            <button type="submit" class="btn btn-accent btn-lg rounded-pill">
                                <i class="fas fa-paper-plane me-2"></i> Envoyer le lien
                            </button>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-transparent border-top border-secondary text-center py-3">
                    <p class="mb-0 text-white"><a href="connexion.php" class="text-accent fw-bold text-decoration-none"><i class="fas fa-arrow-left me-1"></i> Retour à la connexion</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<div style="height: 100px;"></div>

<?php
require_once 'includes/footer.php';
?>
