    </main>
    <footer class="bg-dark text-white py-4 mt-auto">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <h5>Club Scientifique</h5>
                    <p class="text-muted">Exploration, découverte et innovation</p>
                </div>
                <div class="col-md-4 mb-3">
                    <h5>Contact</h5>
                    <p><i class="fas fa-envelope me-2"></i>contact@cslmdb.com(pas encore dispo)</p>
                    <p><i class="fas fa-phone me-2"></i>+221 775889711</p>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> CSLMDB - Tous droits réservés</p>
            </div>
        </div>
    </footer>
    
    <!-- WhatsApp Floating Button -->
    <a href="https://chat.whatsapp.com/BSFSXUpOibU4TiYzw2WKTN" class="whatsapp-btn" target="_blank" title="Rejoindre le groupe WhatsApp">
        <div class="whatsapp-pulse"></div>
        <i class="fab fa-whatsapp"></i>
    </a>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Script JavaScript principal -->
    <script src="<?php echo (strpos($_SERVER['PHP_SELF'], 'admin/') !== false) ? '../' : ''; ?>js/script.js"></script>

    <?php
    // Inclusion conditionnelle du script de validation si on est sur la page devenir-membre
    if (basename($_SERVER['PHP_SELF']) === 'devenir-membre.php') {
        echo '<script src="js/validation.js"></script>';
    }
    ?>

</body>
</html>
