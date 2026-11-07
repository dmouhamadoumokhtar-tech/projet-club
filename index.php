<?php

// Inclure les fichiers nécessaires
require_once 'includes/db.php';
require_once 'includes/fonctions.php';

// Démarrer la session
session_start();

// --- DATA FETCHING STRATEGY ---

// 1. GALERIE (Priorité Absolue - Contenu visuel)
// Récupérer les 10 dernières images pour le carrousel 3D
$stmt = $mysqli->prepare("SELECT * FROM galerie ORDER BY date_ajout DESC LIMIT 10");
$stmt->execute();
$galerie_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// 2. PROJETS (Fallback si vide)
$stmt = $mysqli->prepare("SELECT * FROM projets ORDER BY date_creation DESC LIMIT 3");
$stmt->execute();
$projets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// 3. INFORMATIONS
$stmt = $mysqli->prepare("SELECT * FROM informations ORDER BY date_publication DESC LIMIT 3");
$stmt->execute();
$informations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$titre_page = "Accueil CSLMDB";
require_once 'includes/header.php';
?>

<!-- 1. HERO SECTION AVEC CANVAS 3D -->
<section id="hero-canvas-container">
    <canvas id="bg-canvas"></canvas>
    <div class="hero-content">
        <div class="hero-super-title text-mono">
            <i class="fas fa-atom fa-spin me-2"></i> EXPÉRIENCE SCIENTIFIQUE
        </div>
        <h1 class="hero-title">
            EXPLOREZ <br>
            <span class="text-accent">L'INCONNU</span>
        </h1>
        <p class="hero-subtitle">
            Plongez dans un univers où la curiosité rencontre l'innovation. 
            Le Club Scientifique est votre laboratoire pour façonner le futur.
        </p>
        <div class="d-flex justify-content-center gap-1 gap-md-3 flex-nowrap w-100 px-2">
            <a href="devenir-membre.php" class="btn btn-accent px-2 px-md-5 py-2 py-md-3 rounded-pill text-nowrap d-flex align-items-center justify-content-center" style="font-size: clamp(0.7rem, 2vw, 1.1rem); flex: 1.2;">
                Devenir Membre
            </a>
            <a href="galerie.php" class="btn btn-glass px-2 px-md-4 py-2 py-md-3 rounded-pill text-nowrap d-flex align-items-center justify-content-center" style="font-size: clamp(0.7rem, 2vw, 1.1rem); flex: 1;">
                Galerie
            </a>
            <a href="projets.php" class="btn btn-glass px-2 px-md-4 py-2 py-md-3 rounded-pill text-nowrap d-flex align-items-center justify-content-center" style="font-size: clamp(0.7rem, 2vw, 1.1rem); flex: 1;">
                Nos Projets
            </a>
        </div>
    </div>
</section>

<!-- 2. SECTION VALEURS (CARTES HOLOGRAPHIQUES) -->
<div class="container" style="margin-top: -80px; position: relative; z-index: 10;">
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card-glass text-center h-100">
                <div class="card-icon">
                    <i class="fas fa-flask"></i>
                </div>
                <h3 class="card-title h4">Expérimentation</h3>
                <p class="text-secondary">
                    Participez à des expériences scientifiques variées dans nos laboratoires équipés.
                </p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-glass text-center h-100">
                <div class="card-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h3 class="card-title h4">Collaboration</h3>
                <p class="text-secondary">
                    Travaillez en équipe sur des projets innovants et développez votre esprit d'équipe.
                </p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-glass text-center h-100">
                <div class="card-icon">
                    <i class="fas fa-lightbulb"></i>
                </div>
                <h3 class="card-title h4">Innovation</h3>
                <p class="text-secondary">
                    Développez votre créativité et transformez vos idées en projets concrets.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- 3. SECTION REJOINDRE (Rétablie) -->
<section class="section-padding" style="margin-top: 100px;">
    <div class="container">
        <div class="card-glass p-5 text-center position-relative overflow-hidden">
            <!-- Déco fond -->
            <div style="position: absolute; top: -50%; left: -20%; width: 500px; height: 500px; background: radial-gradient(circle, rgba(100,255,218,0.1) 0%, transparent 70%); border-radius: 50%; pointer-events: none;"></div>
            
            <div class="position-relative z-index-1">
                <h2 class="mb-4">Rejoignez notre <span class="text-accent">Club Scientifique</span></h2>
                <p class="lead text-secondary mb-4" style="max-width: 800px; margin: 0 auto;">
                    Vous êtes passionné(e) par les sciences et vous souhaitez participer à des projets concrets ?<br>
                    Le Club Scientifique est ouvert aux élèves de la seconde à la terminale.
                </p>
                <a href="devenir-membre.php" class="btn btn-accent btn-lg px-5 py-3 rounded-pill">
                    Rejoindre
                </a>
            </div>
        </div>
    </div>
</section>

<!-- 4. DERNIERS PROJETS (GRID MODERNE) -->
<section class="container mb-5" style="margin-top: 100px;">
    <div class="d-flex justify-content-between align-items-end mb-5">
        <div>
            <h2 class="mb-0">Derniers Projets</h2>
        </div>
        <a href="projets.php" class="btn btn-sm btn-glass">Voir tout <i class="fas fa-arrow-right ms-2"></i></a>
    </div>

    <?php if (count($projets) > 0): ?>
        <div class="row g-4">
            <?php foreach ($projets as $projet): ?>
                <div class="col-md-4">
                    <div class="card-glass h-100 p-0 overflow-hidden group-hover-effect">
                        <div class="card-body p-4 d-flex flex-column">
                            <div class="text-accent mb-3 text-mono small">
                                <?php echo date('d M Y', strtotime($projet['date_creation'])); ?>
                            </div>
                            <h4 class="card-title"><?php echo htmlspecialchars($projet['titre']); ?></h4>
                            <p class="text-secondary flex-grow-1">
                                <?php echo tronquerTexte(htmlspecialchars($projet['description']), 120); ?>
                            </p>
                            <a href="projets.php?id=<?php echo $projet['id']; ?>" class="text-accent mt-3 d-inline-block">
                                En savoir plus <i class="fas fa-chevron-right small ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-12">
                <div class="card-glass text-center p-5">
                    <i class="fas fa-rocket fa-3x text-secondary mb-3"></i>
                    <h4 class="text-white">Projets en cours de chargement...</h4>
                    <p class="text-secondary">L'équipe scientifique prépare de nouvelles découvertes.</p>
                </div>
            </div>
        </div>
    <?php endif; ?>
</section>

<?php require_once 'includes/footer.php'; ?>

<!-- SCRIPTS MASTERCLASS -->

<!-- 1. Swiper JS -->
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<!-- 2. Fancybox -->
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js"></script>

<script>
    // --- A. INITIALISATION SWIPER STANDARD (2D) ---
    const swiper = new Swiper('.swiper-3d-container', {
        slidesPerView: 1,
        spaceBetween: 20,
        loop: true,
        grabCursor: true,
        pagination: {
            el: '.swiper-pagination',
            clickable: true,
        },
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },
        autoplay: {
            delay: 3000,
            disableOnInteraction: false,
        },
        breakpoints: {
            640: {
                slidesPerView: 2,
                spaceBetween: 20,
            },
            768: {
                slidesPerView: 3,
                spaceBetween: 30,
            },
            1024: {
                slidesPerView: 3,
                spaceBetween: 30,
            },
        },
    });

    // --- B. INITIALISATION FANCYBOX ---
    Fancybox.bind("[data-fancybox]", {
        // Options Fancybox
    });

    // --- C. MOUSE GLOW EFFECT SUR LES CARTES ---
    document.querySelectorAll('.card-glass').forEach(card => {
        card.addEventListener('mousemove', e => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            card.style.setProperty('--mouse-x', `${x}px`);
            card.style.setProperty('--mouse-y', `${y}px`);
        });
    });

    // --- D. CANVAS CONSTELLATION SCRIPT ---
    const canvas = document.getElementById('bg-canvas');
    const ctx = canvas.getContext('2d');
    let width, height;
    let particles = [];

    // Configuration
    const particleCount = 70; // Nombre de points
    const connectionDistance = 150;
    const mouseDistance = 200;

    function resize() {
        width = canvas.width = window.innerWidth;
        height = canvas.height = document.getElementById('hero-canvas-container').offsetHeight;
    }

    class Particle {
        constructor() {
            this.x = Math.random() * width;
            this.y = Math.random() * height;
            this.vx = (Math.random() - 0.5) * 1.5; // Vitesse lente
            this.vy = (Math.random() - 0.5) * 1.5;
            this.size = Math.random() * 2 + 1;
        }

        update() {
            this.x += this.vx;
            this.y += this.vy;

            // Rebondir sur les bords
            if (this.x < 0 || this.x > width) this.vx *= -1;
            if (this.y < 0 || this.y > height) this.vy *= -1;
        }

        draw() {
            ctx.fillStyle = 'rgba(100, 255, 218, 0.7)'; // Cyan
            ctx.beginPath();
            ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
            ctx.fill();
        }
    }

    function initparticles() {
        particles = [];
        for (let i = 0; i < particleCount; i++) {
            particles.push(new Particle());
        }
    }

    // Souris interactive
    let mouse = { x: null, y: null };
    window.addEventListener('mousemove', e => {
        const rect = canvas.getBoundingClientRect();
        mouse.x = e.clientX - rect.left;
        mouse.y = e.clientY - rect.top;
    });
    window.addEventListener('mouseleave', () => { mouse.x = null; mouse.y = null; });

    function animate() {
        ctx.clearRect(0, 0, width, height);

        // Dessiner les particules
        particles.forEach(p => {
            p.update();
            p.draw();
        });

        // Dessiner les lignes
        for (let i = 0; i < particles.length; i++) {
            for (let j = i + 1; j < particles.length; j++) {
                const dx = particles[i].x - particles[j].x;
                const dy = particles[i].y - particles[j].y;
                const distance = Math.sqrt(dx * dx + dy * dy);

                if (distance < connectionDistance) {
                    ctx.strokeStyle = `rgba(100, 255, 218, ${1 - distance / connectionDistance})`;
                    ctx.lineWidth = 1;
                    ctx.beginPath();
                    ctx.moveTo(particles[i].x, particles[i].y);
                    ctx.lineTo(particles[j].x, particles[j].y);
                    ctx.stroke();
                }
            }
            
            // Connexion Souris
            if (mouse.x != null) {
                const dx = particles[i].x - mouse.x;
                const dy = particles[i].y - mouse.y;
                const distance = Math.sqrt(dx * dx + dy * dy);
                 if (distance < mouseDistance) {
                    ctx.strokeStyle = `rgba(87, 203, 255, ${1 - distance / mouseDistance})`; // Bleu plus clair vers la souris
                    ctx.lineWidth = 1.5;
                    ctx.beginPath();
                    ctx.moveTo(particles[i].x, particles[i].y);
                    ctx.lineTo(mouse.x, mouse.y);
                    ctx.stroke();
                }
            }
        }

        requestAnimationFrame(animate);
    }

    window.addEventListener('resize', () => {
        resize();
        initparticles();
    });

    // Start
    resize();
    initparticles();
    animate();

</script>
