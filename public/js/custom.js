/**
 * Fichier JavaScript personnalisé pour l'application de gestion de contrats
 */

// Attendre que le DOM soit chargé
document.addEventListener('DOMContentLoaded', function() {
    // Ajouter des animations aux messages flash
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        // Ajouter une classe pour l'animation d'entrée
        alert.classList.add('animate__animated', 'animate__fadeIn');
        
        // Ajouter un bouton de fermeture si non présent
        if (!alert.querySelector('.btn-close')) {
            const closeButton = document.createElement('button');
            closeButton.className = 'btn-close';
            closeButton.setAttribute('data-bs-dismiss', 'alert');
            closeButton.setAttribute('aria-label', 'Close');
            alert.appendChild(closeButton);
        }
        
        // Faire disparaître automatiquement après 5 secondes
        setTimeout(function() {
            alert.classList.add('animate__fadeOut');
            setTimeout(function() {
                alert.remove();
            }, 1000);
        }, 5000);
    });
    
    // Ajouter des tooltips Bootstrap
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Ajouter des popovers Bootstrap
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function(popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Ajouter une confirmation avant suppression
    const deleteButtons = document.querySelectorAll('.btn-delete, [data-confirm]');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            const message = this.getAttribute('data-confirm') || 'Êtes-vous sûr de vouloir supprimer cet élément?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
    
    // Ajouter un effet de survol sur les lignes de tableau
    const tableRows = document.querySelectorAll('table tbody tr');
    tableRows.forEach(function(row) {
        row.addEventListener('mouseenter', function() {
            this.classList.add('table-hover');
        });
        row.addEventListener('mouseleave', function() {
            this.classList.remove('table-hover');
        });
    });
    
    // Ajouter une animation au logo
    const logo = document.querySelector('.company-logo');
    if (logo) {
        logo.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.1)';
            this.style.transition = 'transform 0.3s ease';
        });
        logo.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    }
    
    // Ajouter un effet de parallaxe au footer
    const footer = document.querySelector('.footer');
    if (footer) {
        window.addEventListener('scroll', function() {
            const scrollPosition = window.scrollY;
            const windowHeight = window.innerHeight;
            const documentHeight = document.body.scrollHeight;
            
            if (documentHeight > windowHeight && scrollPosition > documentHeight - windowHeight - 200) {
                const opacity = Math.min(1, (scrollPosition - (documentHeight - windowHeight - 200)) / 200);
                footer.style.opacity = opacity;
            }
        });
    }
}); 