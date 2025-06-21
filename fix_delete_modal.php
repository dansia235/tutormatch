<?php
/**
 * Script pour corriger le problème de clignotement des modals de suppression
 */

// Définir le chemin racine du système
define('ROOT_PATH', dirname(__FILE__));

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Correction du problème de clignotement des modals de suppression</h1>";

// Créer un fichier JS spécifique pour stabiliser les modals de suppression
$jsContent = <<<'EOT'
/**
 * Fix for delete modal flickering issue
 */
document.addEventListener('DOMContentLoaded', function() {
    // When a delete button is clicked
    document.querySelectorAll('[data-bs-toggle="modal"]').forEach(function(button) {
        button.addEventListener('click', function(event) {
            // Prevent default behavior that might be causing flickering
            event.preventDefault();
            
            // Get the target modal ID
            const modalId = this.getAttribute('data-bs-target');
            const modalElement = document.querySelector(modalId);
            
            if (modalElement) {
                // Initialize Bootstrap modal if not already initialized
                let bsModal = bootstrap.Modal.getInstance(modalElement);
                if (!bsModal) {
                    bsModal = new bootstrap.Modal(modalElement, {
                        backdrop: 'static', // Prevent closing when clicking outside
                        keyboard: true       // Allow ESC key to close
                    });
                }
                
                // Ensure modal stays open with explicit call
                bsModal.show();
                
                // Prevent the modal from auto-hiding
                modalElement.addEventListener('click', function(e) {
                    if (e.target === modalElement) {
                        e.stopPropagation();
                    }
                });
            }
        });
    });
    
    // Handle delete form submission to ensure modal is closed properly
    document.querySelectorAll('.modal form').forEach(function(form) {
        form.addEventListener('submit', function() {
            // Get the modal element
            const modalElement = this.closest('.modal');
            if (modalElement) {
                // Get the Bootstrap modal instance
                const bsModal = bootstrap.Modal.getInstance(modalElement);
                if (bsModal) {
                    // Manually hide the modal before form submission
                    bsModal.hide();
                }
            }
        });
    });
});
EOT;

// Chemin du fichier JS à créer
$jsFilePath = ROOT_PATH . '/assets/js/modal-fix.js';

// Écrire le fichier JS
if (file_put_contents($jsFilePath, $jsContent) === false) {
    echo "<p style='color:red;'>Impossible de créer le fichier JS: $jsFilePath</p>";
    exit;
}

echo "<p style='color:green;'>Fichier JS créé avec succès: $jsFilePath</p>";

// Maintenant, modifier le fichier footer.php pour inclure notre nouveau script
$footerPath = ROOT_PATH . '/views/common/footer.php';

if (file_exists($footerPath)) {
    // Lire le contenu du fichier
    $content = file_get_contents($footerPath);
    if ($content === false) {
        echo "<p style='color:red;'>Impossible de lire le fichier $footerPath</p>";
        exit;
    }
    
    // Créer une sauvegarde
    $backupPath = $footerPath . '.backup.' . date('Ymd_His');
    if (file_put_contents($backupPath, $content) === false) {
        echo "<p style='color:red;'>Impossible de créer une sauvegarde pour $footerPath</p>";
        exit;
    }
    
    // Ajouter notre script après les autres scripts
    $scriptTag = '<script src="/tutoring/assets/js/modal-fix.js"></script>';
    $insertAfter = '<!-- Main application script (après Stimulus) -->
    <script type="module" src="/tutoring/assets/js/app.js"></script>
    <script src="/tutoring/assets/js/main.js"></script>';
    
    $newContent = str_replace(
        $insertAfter,
        $insertAfter . "\n    " . $scriptTag,
        $content
    );
    
    // Enregistrer les modifications
    if (file_put_contents($footerPath, $newContent) === false) {
        echo "<p style='color:red;'>Impossible d'enregistrer les modifications pour $footerPath</p>";
        exit;
    }
    
    echo "<p style='color:green;'>Fichier footer.php modifié avec succès pour inclure le script de correction</p>";
}

// Vérifier également s'il existe un contrôleur modal_controller.js potentiellement conflictuel
$modalControllerPath = ROOT_PATH . '/assets/js/controllers/modal_controller.js';

if (file_exists($modalControllerPath)) {
    echo "<p style='color:orange;'>Un contrôleur modal Stimulus a été détecté: $modalControllerPath</p>";
    echo "<p>Nous avons créé un script qui ne devrait pas interférer avec ce contrôleur, mais qui devrait corriger le problème de clignotement.</p>";
}

// Instructions pour tester les modifications
echo "<h2>Prochaines étapes</h2>";
echo "<p>Les modifications ont été appliquées. Veuillez tester l'interface de suppression des documents pour vérifier que le problème de clignotement a été résolu.</p>";
echo "<p><a href='/tutoring/views/admin/documents.php' class='btn btn-primary'>Tester la page des documents</a></p>";
?>