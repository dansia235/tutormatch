<?php
/**
 * Script final pour corriger les problèmes de clignotement et d'interaction avec les modals
 */

// Définir le chemin racine du système
define('ROOT_PATH', dirname(__FILE__));

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Solution finale pour les modals de suppression</h1>";

// Modifier directement la page documents.php
$documentsPath = ROOT_PATH . '/views/admin/documents.php';

if (file_exists($documentsPath)) {
    // Lire le contenu du fichier
    $content = file_get_contents($documentsPath);
    if ($content === false) {
        echo "<p style='color:red;'>Impossible de lire le fichier $documentsPath</p>";
        exit;
    }
    
    // Créer une sauvegarde
    $backupPath = $documentsPath . '.backup.' . date('Ymd_His');
    if (file_put_contents($backupPath, $content) === false) {
        echo "<p style='color:red;'>Impossible de créer une sauvegarde pour $documentsPath</p>";
        exit;
    }
    
    // Remplacer la structure du bouton de suppression
    $oldButtonStructure = <<<'EOT'
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $document['id']; ?>" title="Supprimer">
                                        <i class="bi bi-trash"></i>
                                    </button>
EOT;

    $newButtonStructure = <<<'EOT'
                                    <button type="button" class="btn btn-sm btn-outline-danger delete-document-btn" data-document-id="<?php echo $document['id']; ?>" title="Supprimer">
                                        <i class="bi bi-trash"></i>
                                    </button>
EOT;

    // Faire le remplacement des boutons
    $newContent = str_replace($oldButtonStructure, $newButtonStructure, $content);
    
    // Ajouter un script JavaScript inline à la fin du document
    $inlineScript = <<<'EOT'

<div class="modal fade" id="deleteDocumentModal" tabindex="-1" aria-labelledby="deleteDocumentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteDocumentModalLabel">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer le document <strong id="documentTitleToDelete"></strong> ?</p>
                <p class="text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Cette action est irréversible et supprimera définitivement le fichier.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form id="deleteDocumentForm" action="/tutoring/views/admin/documents/delete.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" id="documentIdToDelete" name="id" value="">
                    <button type="submit" class="btn btn-danger">Supprimer</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Script de gestion centralisée de la suppression des documents
document.addEventListener('DOMContentLoaded', function() {
    // Référence au modal unique
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteDocumentModal'), {
        backdrop: 'static'
    });
    
    // Récupérer les éléments du formulaire
    const documentTitleElement = document.getElementById('documentTitleToDelete');
    const documentIdInput = document.getElementById('documentIdToDelete');
    const deleteForm = document.getElementById('deleteDocumentForm');
    
    // Mémoriser les titres des documents
    const documentTitles = {};
    <?php foreach ($documents as $document): ?>
    documentTitles[<?php echo $document['id']; ?>] = "<?php echo addslashes($document['title'] ?? ''); ?>";
    <?php endforeach; ?>
    
    // Attacher les gestionnaires d'événements aux boutons de suppression
    document.querySelectorAll('.delete-document-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const documentId = this.getAttribute('data-document-id');
            
            // Mettre à jour le formulaire
            documentIdInput.value = documentId;
            documentTitleElement.textContent = documentTitles[documentId] || '';
            
            // Afficher le modal
            deleteModal.show();
        });
    });
    
    // Gérer la soumission du formulaire
    deleteForm.addEventListener('submit', function() {
        // Cacher le modal avant soumission pour éviter double soumission
        deleteModal.hide();
    });
});
</script>
EOT;

    // Ajouter le script à la fin du fichier
    $newContent = str_replace('<?php require_once __DIR__ . \'/../../common/footer.php\'; ?>', $inlineScript . "\n\n<?php require_once __DIR__ . '/../../common/footer.php'; ?>", $newContent);
    
    // Enregistrer les modifications
    if (file_put_contents($documentsPath, $newContent) === false) {
        echo "<p style='color:red;'>Impossible d'enregistrer les modifications pour $documentsPath</p>";
        exit;
    }
    
    echo "<p style='color:green;'>Fichier documents.php modifié avec succès avec une implémentation centralisée des modals</p>";
}

// Mise à jour du CSS pour garantir le bon fonctionnement
$cssContent = <<<'EOT'
/**
 * Fix for Bootstrap modals in the document management interface
 */
.modal-backdrop {
  opacity: 0.5;
}

/* Ensure modals stay visible and on top */
.modal.fade.show {
  z-index: 1050 !important;
  display: block !important;
}

/* Ensure pointer events work correctly */
.modal-dialog {
  pointer-events: auto !important;
}

.modal-content {
  pointer-events: auto !important;
}

/* Override any conflicting CSS */
body.modal-open {
  overflow: hidden;
  padding-right: 15px;
}

/* Smooth animations */
.modal.fade .modal-dialog {
  transition: transform 0.3s ease-out !important;
}

.modal.fade {
  transition: opacity 0.2s linear !important;
}
EOT;

// Chemin du fichier CSS
$cssFilePath = ROOT_PATH . '/assets/css/modal-fixes.css';

// Écrire le fichier CSS
if (file_put_contents($cssFilePath, $cssContent) === false) {
    echo "<p style='color:red;'>Impossible de mettre à jour le fichier CSS: $cssFilePath</p>";
    exit;
}

echo "<p style='color:green;'>Fichier CSS mis à jour avec succès: $cssFilePath</p>";

// Supprimer le fichier JS précédent qui n'est plus nécessaire
$jsFilePath = ROOT_PATH . '/assets/js/modal-bootstrap-fix.js';
if (file_exists($jsFilePath) && unlink($jsFilePath)) {
    echo "<p style='color:green;'>Fichier JS précédent supprimé: $jsFilePath</p>";
} else if (file_exists($jsFilePath)) {
    echo "<p style='color:orange;'>Impossible de supprimer le fichier JS précédent: $jsFilePath</p>";
}

// Mettre à jour le footer pour supprimer la référence au script JS supprimé
$footerPath = ROOT_PATH . '/views/common/footer.php';
if (file_exists($footerPath)) {
    // Lire le contenu du fichier
    $content = file_get_contents($footerPath);
    if ($content !== false) {
        // Créer une sauvegarde
        $backupPath = $footerPath . '.backup.' . date('Ymd_His');
        if (file_put_contents($backupPath, $content) !== false) {
            // Supprimer la référence au script
            $newContent = str_replace('<script src="/tutoring/assets/js/modal-bootstrap-fix.js"></script>', '', $content);
            
            // Enregistrer les modifications
            if (file_put_contents($footerPath, $newContent) !== false) {
                echo "<p style='color:green;'>Référence au script supprimée du footer.php</p>";
            }
        }
    }
}

// Instructions pour tester les modifications
echo "<h2>Prochaines étapes</h2>";
echo "<p>Une solution complètement nouvelle a été mise en place. Nous avons:</p>";
echo "<ul>";
echo "<li>Remplacé tous les modals individuels par un modal central unique</li>";
echo "<li>Utilisé JavaScript pour mettre à jour dynamiquement le contenu du modal</li>";
echo "<li>Implémenté une gestion efficace des événements pour éviter les clignotements</li>";
echo "<li>Simplifié le CSS pour garantir l'affichage correct et stable des modals</li>";
echo "</ul>";
echo "<p>Cette solution évite complètement les problèmes de clignotement en n'utilisant qu'un seul modal pour toutes les opérations de suppression.</p>";
echo "<p><a href='/tutoring/views/admin/documents.php' class='btn btn-primary'>Tester la page des documents</a></p>";
?>