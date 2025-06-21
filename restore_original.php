<?php
/**
 * Script pour restaurer la version originale de documents.php et corriger les problèmes
 */

// Définir le chemin racine du système
define('ROOT_PATH', dirname(__FILE__));

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Restauration et solution simple pour les modals</h1>";

// Restaurer à partir de la toute première sauvegarde
$documentsPath = ROOT_PATH . '/views/admin/documents.php';
$backupFiles = glob(ROOT_PATH . '/views/admin/documents.php.backup.*');

if (!empty($backupFiles)) {
    // Trier les fichiers par date pour obtenir le plus ancien
    usort($backupFiles, function($a, $b) {
        return filemtime($a) - filemtime($b);
    });
    
    // Prendre la première sauvegarde (la plus ancienne)
    $originalBackup = $backupFiles[0];
    
    // Restaurer à partir de la sauvegarde originale
    if (copy($originalBackup, $documentsPath)) {
        echo "<p style='color:green;'>Fichier documents.php restauré à l'état original: $originalBackup</p>";
    } else {
        echo "<p style='color:red;'>Impossible de restaurer le fichier documents.php</p>";
        exit;
    }
} else {
    echo "<p style='color:red;'>Aucune sauvegarde trouvée pour documents.php</p>";
    exit;
}

// Corriger l'affichage des catégories (type vs category)
$content = file_get_contents($documentsPath);
if ($content !== false) {
    // Remplacer 'category' par 'type' pour l'affichage des catégories
    $newContent = str_replace(
        '$docCategory = $document[\'category\'] ?? \'\';',
        '$docCategory = $document[\'type\'] ?? \'\';',
        $content
    );
    
    // Enregistrer la modification
    if (file_put_contents($documentsPath, $newContent) !== false) {
        echo "<p style='color:green;'>Correction de l'affichage des catégories effectuée</p>";
    }
}

// Nettoyer les fichiers CSS et JS inutiles
$filesToClean = [
    ROOT_PATH . '/assets/js/modal-bootstrap-fix.js',
    ROOT_PATH . '/assets/js/modal-fix.js'
];

foreach ($filesToClean as $file) {
    if (file_exists($file) && unlink($file)) {
        echo "<p style='color:green;'>Fichier nettoyé: $file</p>";
    }
}

// Réinitialiser le CSS modal-fixes.css
$cssContent = <<<'EOT'
/* Styles pour assurer que les modals fonctionnent correctement */
.modal-backdrop {
  z-index: 1040 !important;
}

.modal {
  z-index: 1050 !important;
}

.modal-dialog {
  z-index: 1060 !important;
}

/* Assurer que les boutons sont cliquables */
.modal-content button,
.modal-content a,
.modal-content input[type="submit"] {
  z-index: 1070 !important;
  position: relative;
}

/* Styles pour les boutons de suppression */
.btn-outline-danger:focus {
  box-shadow: none !important;
}
EOT;

$cssFilePath = ROOT_PATH . '/assets/css/modal-fixes.css';
file_put_contents($cssFilePath, $cssContent);
echo "<p style='color:green;'>CSS réinitialisé pour assurer le bon fonctionnement des modals</p>";

// Mettre à jour le footer.php pour s'assurer que tous les scripts sont correctement chargés
$footerPath = ROOT_PATH . '/views/common/footer.php';
$footerContent = file_get_contents($footerPath);

if ($footerContent !== false) {
    // Supprimer les références aux scripts supprimés
    $newFooterContent = preg_replace('/<script src="\/tutoring\/assets\/js\/modal-.*?\.js"><\/script>\s*/', '', $footerContent);
    
    // Enregistrer les modifications
    if (file_put_contents($footerPath, $newFooterContent) !== false) {
        echo "<p style='color:green;'>Références aux scripts supprimés retirées du footer.php</p>";
    }
}

// Instructions pour tester les modifications
echo "<h2>Prochaines étapes</h2>";
echo "<p>Les modifications ont été appliquées:</p>";
echo "<ul>";
echo "<li>La page documents.php a été restaurée à son état original</li>";
echo "<li>La correction pour l'affichage des catégories (type vs category) a été appliquée</li>";
echo "<li>Les scripts qui causaient des conflits ont été supprimés</li>";
echo "<li>Le CSS a été simplifié pour assurer le bon fonctionnement des modals</li>";
echo "</ul>";
echo "<p>Pour terminer la correction, vous devez maintenant:</p>";
echo "<ol>";
echo "<li>Accéder à la page des documents pour vérifier que les catégories s'affichent correctement</li>";
echo "<li>Tester les modals de suppression pour voir s'ils fonctionnent correctement</li>";
echo "</ol>";
echo "<p><a href='/tutoring/views/admin/documents.php' class='btn btn-primary'>Tester la page des documents</a></p>";
?>