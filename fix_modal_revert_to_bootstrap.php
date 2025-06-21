<?php
/**
 * Script pour revenir à l'implémentation Bootstrap des modals avec des améliorations
 */

// Définir le chemin racine du système
define('ROOT_PATH', dirname(__FILE__));

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Correction du modal de suppression - Retour à Bootstrap</h1>";

// Restauration de la page documents.php à partir de la sauvegarde la plus récente
$documentsPath = ROOT_PATH . '/views/admin/documents.php';
$backupFiles = glob(ROOT_PATH . '/views/admin/documents.php.backup.*');

if (!empty($backupFiles)) {
    // Trier les fichiers par date pour obtenir le plus récent
    usort($backupFiles, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });
    
    $latestBackup = $backupFiles[0];
    
    // Restaurer à partir de la sauvegarde
    if (copy($latestBackup, $documentsPath)) {
        echo "<p style='color:green;'>Fichier documents.php restauré à partir de la sauvegarde: $latestBackup</p>";
    } else {
        echo "<p style='color:red;'>Impossible de restaurer le fichier documents.php à partir de la sauvegarde</p>";
        exit;
    }
} else {
    echo "<p style='color:red;'>Aucune sauvegarde trouvée pour documents.php</p>";
    exit;
}

// Créer un script JavaScript amélioré pour Bootstrap
$jsContent = <<<'EOT'
/**
 * Bootstrap modal fix for document deletion
 */
document.addEventListener('DOMContentLoaded', function() {
    // Fonction pour initialiser correctement un modal Bootstrap
    function initializeModal(modalId) {
        const modalElement = document.getElementById(modalId);
        if (!modalElement) return null;
        
        return new bootstrap.Modal(modalElement, {
            backdrop: 'static',
            keyboard: true
        });
    }
    
    // Fonction pour attendre le chargement complet de Bootstrap
    function ensureBootstrapLoaded(callback) {
        if (typeof bootstrap !== 'undefined' && typeof bootstrap.Modal !== 'undefined') {
            callback();
        } else {
            setTimeout(function() {
                ensureBootstrapLoaded(callback);
            }, 100);
        }
    }
    
    // Initialiser tous les modals de suppression une fois Bootstrap chargé
    ensureBootstrapLoaded(function() {
        // Store modals in a map to prevent recreation
        const modalInstances = new Map();
        
        // Handle delete button clicks
        document.querySelectorAll('[data-bs-target^="#deleteModal"]').forEach(function(button) {
            button.addEventListener('click', function(event) {
                // Get the target modal ID
                const modalId = this.getAttribute('data-bs-target').substring(1);
                
                // Get or create the modal instance
                let modalInstance = modalInstances.get(modalId);
                if (!modalInstance) {
                    modalInstance = initializeModal(modalId);
                    if (modalInstance) {
                        modalInstances.set(modalId, modalInstance);
                    }
                }
                
                // Show the modal
                if (modalInstance) {
                    modalInstance.show();
                }
            });
        });
        
        // Handle form submissions
        document.querySelectorAll('.modal form').forEach(function(form) {
            form.addEventListener('submit', function() {
                const modalElement = this.closest('.modal');
                if (modalElement) {
                    const modalId = modalElement.id;
                    const modalInstance = modalInstances.get(modalId);
                    if (modalInstance) {
                        modalInstance.hide();
                    }
                }
            });
        });
        
        // Handle close buttons
        document.querySelectorAll('[data-bs-dismiss="modal"]').forEach(function(button) {
            button.addEventListener('click', function() {
                const modalElement = this.closest('.modal');
                if (modalElement) {
                    const modalId = modalElement.id;
                    const modalInstance = modalInstances.get(modalId);
                    if (modalInstance) {
                        modalInstance.hide();
                    }
                }
            });
        });
    });
});
EOT;

// Chemin du fichier JS à créer
$jsFilePath = ROOT_PATH . '/assets/js/modal-bootstrap-fix.js';

// Écrire le fichier JS
if (file_put_contents($jsFilePath, $jsContent) === false) {
    echo "<p style='color:red;'>Impossible de créer le fichier JS: $jsFilePath</p>";
    exit;
}

echo "<p style='color:green;'>Fichier JS créé avec succès: $jsFilePath</p>";

// Ajouter le script dans le footer
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
    
    // Vérifier si l'ancien script est déjà référencé
    if (strpos($content, 'modal-fix.js') !== false) {
        // Remplacer l'ancien script par le nouveau
        $newContent = str_replace(
            '<script src="/tutoring/assets/js/modal-fix.js"></script>',
            '<script src="/tutoring/assets/js/modal-bootstrap-fix.js"></script>',
            $content
        );
    } else {
        // Ajouter notre script après les autres scripts
        $scriptTag = '<script src="/tutoring/assets/js/modal-bootstrap-fix.js"></script>';
        $insertAfter = '<!-- Main application script (après Stimulus) -->
    <script type="module" src="/tutoring/assets/js/app.js"></script>
    <script src="/tutoring/assets/js/main.js"></script>';
        
        $newContent = str_replace(
            $insertAfter,
            $insertAfter . "\n    " . $scriptTag,
            $content
        );
    }
    
    // Enregistrer les modifications
    if (file_put_contents($footerPath, $newContent) === false) {
        echo "<p style='color:red;'>Impossible d'enregistrer les modifications pour $footerPath</p>";
        exit;
    }
    
    echo "<p style='color:green;'>Fichier footer.php modifié avec succès pour inclure le script de correction</p>";
}

// CSS spécifique pour résoudre les problèmes d'affichage
$cssContent = <<<'EOT'
/**
 * Fix for Bootstrap modals in the document management interface
 */
.modal-backdrop {
  opacity: 0.5 !important;
  transition: opacity 0.15s linear;
}

.modal.fade .modal-dialog {
  transition: transform 0.2s ease-out !important;
}

.modal.fade {
  transition: opacity 0.15s linear !important;
}

.modal.show {
  display: block !important;
  background-color: rgba(0, 0, 0, 0.5);
}

.modal-open {
  overflow: hidden;
  padding-right: 15px;
}

/* Ensure modals stay visible */
.modal.show .modal-dialog {
  transform: none !important;
}

/* Force visibility */
.modal-dialog {
  position: relative;
  width: auto;
  margin: 0.5rem;
  pointer-events: auto;
}

@media (min-width: 576px) {
  .modal-dialog {
    max-width: 500px;
    margin: 1.75rem auto;
  }
}

/* Ensure modals don't disappear */
.modal.show {
  z-index: 1050 !important;
}
EOT;

// Chemin du fichier CSS à créer
$cssFilePath = ROOT_PATH . '/assets/css/modal-fixes.css';

// Écrire le fichier CSS
if (file_put_contents($cssFilePath, $cssContent) === false) {
    echo "<p style='color:red;'>Impossible de créer le fichier CSS: $cssFilePath</p>";
    exit;
}

echo "<p style='color:green;'>Fichier CSS créé avec succès: $cssFilePath</p>";

// Ajouter le CSS dans l'en-tête
$headerPath = ROOT_PATH . '/views/common/header.php';

if (file_exists($headerPath)) {
    // Lire le contenu du fichier
    $content = file_get_contents($headerPath);
    if ($content === false) {
        echo "<p style='color:red;'>Impossible de lire le fichier $headerPath</p>";
        exit;
    }
    
    // Créer une sauvegarde
    $backupPath = $headerPath . '.backup.' . date('Ymd_His');
    if (file_put_contents($backupPath, $content) === false) {
        echo "<p style='color:red;'>Impossible de créer une sauvegarde pour $headerPath</p>";
        exit;
    }
    
    // Ajouter notre CSS après les autres CSS
    $cssLink = '    <link rel="stylesheet" href="/tutoring/assets/css/modal-fixes.css">';
    $insertAfter = '    <link rel="stylesheet" href="/tutoring/assets/css/messages.css">';
    
    $newContent = str_replace(
        $insertAfter,
        $insertAfter . "\n" . $cssLink,
        $content
    );
    
    // Enregistrer les modifications
    if (file_put_contents($headerPath, $newContent) === false) {
        echo "<p style='color:red;'>Impossible d'enregistrer les modifications pour $headerPath</p>";
        exit;
    }
    
    echo "<p style='color:green;'>Fichier header.php modifié avec succès pour inclure le CSS de correction</p>";
}

// Instructions pour tester les modifications
echo "<h2>Prochaines étapes</h2>";
echo "<p>Les modifications ont été appliquées. Nous avons:</p>";
echo "<ul>";
echo "<li>Restauré l'implémentation Bootstrap originale des modals</li>";
echo "<li>Ajouté un script JavaScript amélioré pour stabiliser les modals Bootstrap</li>";
echo "<li>Ajouté des styles CSS spécifiques pour garantir que les modals restent visibles</li>";
echo "</ul>";
echo "<p>Veuillez tester l'interface de suppression des documents pour vérifier que le problème de clignotement a été résolu.</p>";
echo "<p><a href='/tutoring/views/admin/documents.php' class='btn btn-primary'>Tester la page des documents</a></p>";
?>