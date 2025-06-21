<?php
/**
 * Script pour corriger les problèmes d'interaction avec les modals
 */

// Définir le chemin racine du système
define('ROOT_PATH', dirname(__FILE__));

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Correction des problèmes d'interaction avec les modals</h1>";

// Modifier le CSS pour corriger les problèmes de z-index et de positionnement
$cssContent = <<<'EOT'
/**
 * Fix for Bootstrap modal interaction issues
 */
/* Ensure modals appear on top of everything */
.modal {
  z-index: 1050 !important;
}

.modal-backdrop {
  z-index: 1040 !important;
}

/* Fix pointer events to allow clicking inside modal */
.modal-dialog {
  position: relative;
  pointer-events: auto !important;
}

.modal-content {
  pointer-events: auto !important;
}

/* Fix modal buttons to be clickable */
.modal .btn {
  position: relative;
  z-index: 2000 !important;
  pointer-events: auto !important;
}

/* Ensure proper backdrop opacity */
.modal-backdrop.show {
  opacity: 0.5 !important;
}

/* Force modal to be visible */
.modal.show {
  display: block !important;
  overflow-x: hidden;
  overflow-y: auto;
}
EOT;

// Chemin du fichier CSS à mettre à jour
$cssFilePath = ROOT_PATH . '/assets/css/modal-fixes.css';

// Écrire le fichier CSS
if (file_put_contents($cssFilePath, $cssContent) === false) {
    echo "<p style='color:red;'>Impossible de mettre à jour le fichier CSS: $cssFilePath</p>";
    exit;
}

echo "<p style='color:green;'>Fichier CSS mis à jour avec succès: $cssFilePath</p>";

// Créer un nouveau script JavaScript simplifié pour les modals
$jsContent = <<<'EOT'
/**
 * Simple but effective modal fix 
 */
document.addEventListener('DOMContentLoaded', function() {
    // Simple direct approach to ensure modals work
    document.querySelectorAll('[data-bs-toggle="modal"]').forEach(function(button) {
        button.addEventListener('click', function(e) {
            // Get the target modal ID
            const modalId = this.getAttribute('data-bs-target');
            if (!modalId) return;
            
            // Find the modal element
            const modal = document.querySelector(modalId);
            if (!modal) return;
            
            // Clear any existing modal backdrops
            const existingBackdrops = document.querySelectorAll('.modal-backdrop');
            existingBackdrops.forEach(backdrop => backdrop.remove());
            
            // Create a simple backdrop if needed
            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            document.body.appendChild(backdrop);
            
            // Show the modal
            modal.classList.add('show');
            modal.style.display = 'block';
            modal.setAttribute('aria-modal', 'true');
            modal.setAttribute('role', 'dialog');
            document.body.classList.add('modal-open');
            
            // Make sure modal is on top
            modal.style.zIndex = '1050';
            backdrop.style.zIndex = '1040';
        });
    });
    
    // Handle close buttons
    document.addEventListener('click', function(e) {
        if (e.target.hasAttribute('data-bs-dismiss') && e.target.getAttribute('data-bs-dismiss') === 'modal') {
            closeModal(e.target.closest('.modal'));
        }
    });
    
    // Handle clicks on form submit buttons inside modals
    document.querySelectorAll('.modal form').forEach(function(form) {
        form.addEventListener('submit', function() {
            // Close the modal on form submission
            closeModal(this.closest('.modal'));
        });
    });
    
    // Function to close modal
    function closeModal(modal) {
        if (!modal) return;
        
        // Hide the modal
        modal.classList.remove('show');
        modal.style.display = 'none';
        modal.removeAttribute('aria-modal');
        modal.setAttribute('aria-hidden', 'true');
        
        // Remove the backdrop
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(backdrop => backdrop.remove());
        
        // Enable body scrolling
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    }
    
    // Close modal when clicking on backdrop
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal') && e.target.classList.contains('show')) {
            closeModal(e.target);
        }
    });
    
    // Close modal with ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const openModals = document.querySelectorAll('.modal.show');
            openModals.forEach(modal => closeModal(modal));
        }
    });
});
EOT;

// Chemin du fichier JS à mettre à jour
$jsFilePath = ROOT_PATH . '/assets/js/modal-bootstrap-fix.js';

// Écrire le fichier JS
if (file_put_contents($jsFilePath, $jsContent) === false) {
    echo "<p style='color:red;'>Impossible de mettre à jour le fichier JS: $jsFilePath</p>";
    exit;
}

echo "<p style='color:green;'>Fichier JS mis à jour avec succès: $jsFilePath</p>";

// Instructions pour tester les modifications
echo "<h2>Prochaines étapes</h2>";
echo "<p>Les modifications ont été appliquées. Nous avons:</p>";
echo "<ul>";
echo "<li>Mis à jour le CSS pour garantir que les modals s'affichent correctement au premier plan</li>";
echo "<li>Corrigé les problèmes de z-index et de pointer-events qui empêchaient l'interaction avec les boutons</li>";
echo "<li>Créé un gestionnaire JavaScript simplifié qui remplace complètement la gestion des modals de Bootstrap</li>";
echo "</ul>";
echo "<p>Veuillez tester l'interface de suppression des documents pour vérifier que le problème est résolu.</p>";
echo "<p><a href='/tutoring/views/admin/documents.php' class='btn btn-primary'>Tester la page des documents</a></p>";
?>