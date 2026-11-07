<?php
/**
 * En-tête MASTERCLASS du Club Scientifique
 *
 * Design premium, "Glassmorphism", et polices Tech.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('estConnecte')) {
    require_once 'includes/fonctions.php';
}

$page_active = basename($_SERVER['PHP_SELF'], '.php');

function getClasseActive($nom_page, $page_actuelle) {
    return ($nom_page === $page_actuelle) ? 'active' : '';
}

$isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
$prefixe_chemin = (strpos($_SERVER['PHP_SELF'], 'admin/') !== false) ? '../' : '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Club Scientifique<?php echo isset($titre_page) ? ' - ' . htmlspecialchars($titre_page, ENT_QUOTES, 'UTF-8') : ''; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts: Outfit (Titres modernes) & Space Mono (Tech/Code) -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&family=Space+Mono:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    
    <!-- Custom Style Masterclass (Versionned to force refresh) -->
    <link rel="stylesheet" href="<?php echo $prefixe_chemin; ?>css/style.css?v=<?php echo time(); ?>">
    
    <!-- Fail-safe styles in case CSS fails to load -->
    <style>
        img { max-width: 100%; height: auto; }
        .logo-container img { max-height: 50px; }
        body { background-color: #020c1b; color: #ccd6f6; } /* Match theme */
    </style>
</head>
</head>
<body class="d-flex flex-column min-vh-100">

    <?php if ($isAdmin): ?>
    <!-- Navbar Admin (Restée simple mais propre) -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="<?php echo $prefixe_chemin; ?>admin/index.php">
                <img src="<?php echo $prefixe_chemin; ?>logo.jpg" alt="Admin" style="height: 35px; width: 35px; border-radius: 50%; object-fit: cover;">
                <span style="font-family: 'Space Mono', monospace;">ADMIN CONSOLE</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarAdmin">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarAdmin">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link <?php echo getClasseActive('index', $page_active); ?>" href="<?php echo $prefixe_chemin; ?>admin/index.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo getClasseActive('gestion-membres', $page_active); ?>" href="<?php echo $prefixe_chemin; ?>admin/gestion-membres.php">Membres</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo getClasseActive('gestion-projets', $page_active); ?>" href="<?php echo $prefixe_chemin; ?>admin/gestion-projets.php">Projets</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo getClasseActive('gestion-galerie', $page_active); ?>" href="<?php echo $prefixe_chemin; ?>admin/gestion-galerie.php">Galerie</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo getClasseActive('gestion-informations', $page_active); ?>" href="<?php echo $prefixe_chemin; ?>admin/gestion-informations.php">Infos</a></li>
                    <li class="nav-item"><a class="nav-link text-danger" href="<?php echo $prefixe_chemin; ?>admin/deconnexion.php"><i class="fas fa-power-off"></i></a></li>
                </ul>
            </div>
        </div>
    </nav>
    <main class="container my-4"> 
    <?php else: ?>
    
    <!-- Navbar Masterclass (Glassmorphism + Sticky) -->
    <header class="fixed-top header-glass">
        <nav class="navbar navbar-expand-lg navbar-dark">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center gap-2 logotype" href="<?php echo $prefixe_chemin; ?>index.php">
                    <div class="logo-container">
                        <img src="<?php echo $prefixe_chemin; ?>logo.jpg" alt="Logo">
                    </div>
                    <span class="brand-text">CLUB<span class="text-accent">SCIENTIFIQUE</span></span>
                </a>
                
                <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <div class="collapse navbar-collapse" id="navbarMain">
                    <ul class="navbar-nav mx-auto mb-2 mb-lg-0 nav-pills-glass">
                        <li class="nav-item">
                            <a class="nav-link <?php echo getClasseActive('index', $page_active); ?>" href="<?php echo $prefixe_chemin; ?>index.php">Accueil</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo getClasseActive('projets', $page_active); ?>" href="<?php echo $prefixe_chemin; ?>projets.php">Projets</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo getClasseActive('galerie', $page_active); ?>" href="<?php echo $prefixe_chemin; ?>galerie.php">Galerie</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo getClasseActive('informations', $page_active); ?>" href="<?php echo $prefixe_chemin; ?>informations.php">Infos</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo getClasseActive('contact', $page_active); ?>" href="<?php echo $prefixe_chemin; ?>contact.php">Contact</a>
                        </li>
                    </ul>
                    
                    <div class="d-flex align-items-center gap-2">
                        <?php if (estConnecte()): ?>
                            <?php if ($isAdmin): ?>
                                <a href="<?php echo $prefixe_chemin; ?>admin/index.php" class="btn btn-sm btn-glass">
                                    <i class="fas fa-cog"></i> Admin
                                </a>
                            <?php else: ?>
                                <a href="<?php echo $prefixe_chemin; ?>mon-espace.php" class="btn btn-sm btn-glass">
                                    <i class="fas fa-user-astronaut"></i> Espace
                                </a>
                            <?php endif; ?>
                            <a href="<?php echo $prefixe_chemin; ?>deconnexion.php" class="btn btn-sm btn-outline-danger border-0">
                                <i class="fas fa-sign-out-alt"></i>
                            </a>
                        <?php else: ?>
                            <a href="<?php echo $prefixe_chemin; ?>connexion.php" class="btn btn-sm btn-glass-primary">
                                <i class="fas fa-fingerprint me-2"></i>Connexion
                            </a>
                            <a href="<?php echo $prefixe_chemin; ?>devenir-membre.php" class="btn btn-sm btn-accent">
                                Rejoindre
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </nav>
    </header>
    <!-- Spacer pour éviter que le contenu ne soit caché par la navbar fixe -->
    <div style="height: 80px;"></div> 
    
    <main class="main-content flex-grow-1">
    <?php endif; ?>
    
        <?php if (isset($_SESSION['message']) && isset($_SESSION['type_message'])): ?>
            <div class="container mt-4">
                <div class="alert <?php echo htmlspecialchars(getClasseAlerte($_SESSION['type_message'])); ?> alert-dismissible fade show glass-alert" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-info-circle me-2 fs-4"></i>
                        <div><?php echo $_SESSION['message']; ?></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                </div>
            </div>
            <?php unset($_SESSION['message']); unset($_SESSION['type_message']); ?>
        <?php endif; ?>

