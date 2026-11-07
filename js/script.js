/**
 * Script principal pour le site Club Scientifique
 * Gère les interactions générales de l'interface utilisateur
 */

// Attendre que le DOM soit complètement chargé
document.addEventListener('DOMContentLoaded', function() {
    // Configuration des tooltips Bootstrap
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Configuration des popovers Bootstrap
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Faire disparaître les messages d'alerte après un certain temps
    
    
    // Ajout de la fonction de confirmation pour les actions de suppression
    const boutonsSupprimer = document.querySelectorAll('.btn-delete');
    
    if (boutonsSupprimer.length > 0) {
        boutonsSupprimer.forEach(function(bouton) {
            bouton.addEventListener('click', function(e) {
                if (!confirm('Êtes-vous sûr de vouloir supprimer cet élément ? Cette action est irréversible.')) {
                    e.preventDefault();
                }
            });
        });
    }
    
    // Comportement pour la galerie d'images
    setupGallery();
});

/**
 * Configure la galerie d'images et la lightbox
 */
function setupGallery() {
    // Récupérer tous les éléments de la galerie
    const galleryItems = document.querySelectorAll('.gallery-item');
    const lightbox = document.getElementById('lightbox');
    
    if (!galleryItems.length || !lightbox) return;
    
    // Récupérer les éléments de la lightbox
    const lightboxImg = document.getElementById('lightbox-img');
    const lightboxCaption = document.getElementById('lightbox-caption');
    const lightboxClose = document.getElementById('lightbox-close');
    
    // Ajouter les gestionnaires d'événements pour chaque élément de la galerie
    galleryItems.forEach(item => {
        item.addEventListener('click', function() {
            const imgSrc = this.getAttribute('data-img-src');
            const imgCaption = this.getAttribute('data-caption');
            
            // Définir l'image et la légende dans la lightbox
            lightboxImg.src = imgSrc;
            lightboxCaption.textContent = imgCaption;
            
            // Afficher la lightbox
            lightbox.style.display = 'flex';
            document.body.style.overflow = 'hidden'; // Empêcher le défilement du body
        });
    });
    
    // Fermer la lightbox au clic sur le bouton de fermeture
    if (lightboxClose) {
        lightboxClose.addEventListener('click', function() {
            lightbox.style.display = 'none';
            document.body.style.overflow = ''; // Rétablir le défilement
        });
    }
    
    // Fermer la lightbox au clic en dehors de l'image
    lightbox.addEventListener('click', function(e) {
        if (e.target === lightbox) {
            lightbox.style.display = 'none';
            document.body.style.overflow = ''; // Rétablir le défilement
        }
    });
}
