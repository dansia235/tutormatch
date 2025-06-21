<?php
/**
 * Script pour restaurer et corriger les modals de suppression
 */

// Définir le chemin racine du système
define('ROOT_PATH', dirname(__FILE__));

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Restauration et correction simple des modals</h1>";

// Restauration de la page documents.php à partir d'une sauvegarde avant nos modifications
$documentsPath = ROOT_PATH . '/views/admin/documents.php';
$backupFiles = glob(ROOT_PATH . '/views/admin/documents.php.backup.*');

if (!empty($backupFiles)) {
    // Trier les fichiers par date pour obtenir une sauvegarde avant nos modifications
    usort($backupFiles, function($a, $b) {
        return filemtime($a) - filemtime($b);
    });
    
    // Prendre la première sauvegarde (la plus ancienne)
    $backupToRestore = $backupFiles[0];
    
    // Restaurer à partir de la sauvegarde
    if (copy($backupToRestore, $documentsPath)) {
        echo "<p style='color:green;'>Fichier documents.php restauré à partir de la sauvegarde originale: $backupToRestore</p>";
    } else {
        echo "<p style='color:red;'>Impossible de restaurer le fichier documents.php à partir de la sauvegarde</p>";
        exit;
    }
} else {
    echo "<p style='color:red;'>Aucune sauvegarde trouvée pour documents.php</p>";
    exit;
}

// Nettoyer les fichiers JS et CSS inutiles
$filesToClean = [
    ROOT_PATH . '/assets/js/modal-bootstrap-fix.js',
    ROOT_PATH . '/assets/js/modal-fix.js'
];

foreach ($filesToClean as $file) {
    if (file_exists($file) && unlink($file)) {
        echo "<p style='color:green;'>Fichier nettoyé: $file</p>";
    }
}

// Créer un script JavaScript inline simple qui fonctionne en toute certitude
$inlineScript = <<<'EOT'
<script>
// Script simple pour corriger les modals de suppression
document.addEventListener('DOMContentLoaded', function() {
    // Fonction pour vérifier si Bootstrap est chargé
    function isBootstrapLoaded() {
        return typeof bootstrap !== 'undefined' && typeof bootstrap.Modal !== 'undefined';
    }
    
    // Fonction pour initialiser correctement les modals
    function initDeleteModals() {
        if (!isBootstrapLoaded()) {
            // Si Bootstrap n'est pas encore chargé, réessayer dans 100ms
            setTimeout(initDeleteModals, 100);
            return;
        }
        
        // Obtenir tous les boutons de suppression
        const deleteButtons = document.querySelectorAll('[data-bs-toggle="modal"][data-bs-target^="#deleteModal"]');
        
        // Pour chaque bouton de suppression
        deleteButtons.forEach(function(button) {
            // Retirer les gestionnaires d'événements existants
            const newButton = button.cloneNode(true);
            button.parentNode.replaceChild(newButton, button);
            
            // Ajouter un nouveau gestionnaire d'événements
            newButton.addEventListener('click', function(e) {
                // Empêcher le comportement par défaut
                e.preventDefault();
                e.stopPropagation();
                
                // Obtenir l'ID du modal
                const modalId = this.getAttribute('data-bs-target');
                const modalElement = document.querySelector(modalId);
                
                if (!modalElement) return;
                
                // Initialiser et afficher le modal
                try {
                    const modal = new bootstrap.Modal(modalElement);
                    modal.show();
                    console.log('Modal shown:', modalId);
                } catch (err) {
                    console.error('Error showing modal:', err);
                }
            });
        });
        
        console.log('Delete modals initialized');
    }
    
    // Initialiser les modals
    initDeleteModals();
});
</script>
EOT;

// Ajouter le script inline à la fin du fichier documents.php
$documentsContent = file_get_contents($documentsPath);
if ($documentsContent !== false) {
    // Ajouter notre script juste avant la balise de fermeture </body>
    $newContent = str_replace('<?php require_once __DIR__ . \'/../../common/footer.php\'; ?>', $inlineScript . "\n\n<?php require_once __DIR__ . '/../../common/footer.php'; ?>", $documentsContent);
    
    // Enregistrer les modifications
    if (file_put_contents($documentsPath, $newContent) !== false) {
        echo "<p style='color:green;'>Script simple de correction ajouté au fichier documents.php</p>";
    } else {
        echo "<p style='color:red;'>Impossible d'ajouter le script de correction</p>";
    }
}

// Instructions pour tester les modifications
echo "<h2>Prochaines étapes</h2>";
echo "<p>Nous avons adopté une approche minimaliste pour corriger les modals de suppression:</p>";
echo "<ul>";
echo "<li>Restauration de la page documents.php à son état d'origine</li>";
echo "<li>Nettoyage des scripts de correction précédents qui causaient des problèmes</li>";
echo "<li>Ajout d'un script simple et direct qui réinitialise les boutons de suppression</li>";
echo "</ul>";
echo "<p>Cette solution devrait résoudre le problème sans introduire de complexité supplémentaire.</p>";
echo "<p><a href='/tutoring/views/admin/documents.php' class='btn btn-primary'>Tester la page des documents</a></p>";
?>