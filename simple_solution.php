<?php
/**
 * Solution ultime pour les modals de suppression - approche directe par HTML/JS
 */

// Définir le chemin racine du système
define('ROOT_PATH', dirname(__FILE__));

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Solution HTML/JS directe pour les modals de suppression</h1>";

// Lire le contenu du fichier
$documentsPath = ROOT_PATH . '/views/admin/documents.php';
$originalContent = file_get_contents($documentsPath);

if ($originalContent === false) {
    echo "<p style='color:red;'>Impossible de lire le fichier documents.php</p>";
    exit;
}

// Créer une sauvegarde
$backupPath = $documentsPath . '.backup.' . date('Ymd_His');
file_put_contents($backupPath, $originalContent);

// 1. Remplacer tous les modals existants pour éviter les conflits
$pattern = '/<div class="modal fade".*?<\/div>\s*<\/div>\s*<\/div>\s*<\/div>/s';
preg_match_all($pattern, $originalContent, $matches);

echo "<p>Nombre de modals trouvés: " . count($matches[0]) . "</p>";

// Supprimer tous les modals existants
$contentWithoutModals = preg_replace($pattern, '', $originalContent);

// 2. Remplacer les boutons de suppression pour éviter les conflits
$oldButtonPattern = '<button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal';
$newButtonPattern = '<button type="button" class="btn btn-sm btn-outline-danger delete-btn" data-document-id="';

$contentWithNewButtons = str_replace($oldButtonPattern, $newButtonPattern, $contentWithoutModals);

// 3. Ajouter un modal unique avec HTML/JS pur (sans dépendance à Bootstrap)
$modalCode = <<<'HTML'
<!-- Notre solution de modal personnalisé -->
<div id="customDeleteModal" style="display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5);">
    <div style="background-color: #fefefe; margin: 10% auto; padding: 20px; border: 1px solid #888; width: 50%; max-width: 500px; border-radius: 5px; box-shadow: 0 4px 8px 0 rgba(0,0,0,0.2);">
        <div style="border-bottom: 1px solid #dee2e6; padding-bottom: 10px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center;">
            <h5 style="margin: 0; font-size: 1.25rem;">Confirmer la suppression</h5>
            <button type="button" onclick="closeCustomModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        <div style="margin-bottom: 20px;">
            <p>Êtes-vous sûr de vouloir supprimer le document <strong id="documentTitleSpan"></strong>?</p>
            <p style="color: #dc3545;"><i class="bi bi-exclamation-triangle me-2"></i>Cette action est irréversible et supprimera définitivement le fichier.</p>
        </div>
        <div style="text-align: right; border-top: 1px solid #dee2e6; padding-top: 10px;">
            <button type="button" onclick="closeCustomModal()" style="background-color: #6c757d; color: white; border: none; padding: 6px 12px; margin-right: 5px; border-radius: 4px; cursor: pointer;">Annuler</button>
            <form id="deleteDocumentForm" action="/tutoring/views/admin/documents/delete.php" method="POST" style="display: inline;">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" id="documentIdInput" name="id" value="">
                <button type="submit" style="background-color: #dc3545; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer;">Supprimer</button>
            </form>
        </div>
    </div>
</div>

<script>
// Script pure JavaScript pour gérer le modal personnalisé
document.addEventListener('DOMContentLoaded', function() {
    // Stockage des titres de documents
    const documentTitles = {};
    
    // Récupérer tous les boutons de suppression
    const deleteButtons = document.querySelectorAll('.delete-btn');
    
    // Pour chaque bouton de suppression
    deleteButtons.forEach(function(button) {
        // Récupérer les données du document
        const documentId = button.getAttribute('data-document-id');
        const documentTitle = button.closest('tr').querySelector('td:first-child .fw-bold').textContent.trim();
        
        // Stocker le titre pour référence future
        documentTitles[documentId] = documentTitle;
        
        // Ajouter un gestionnaire d'événement de clic
        button.addEventListener('click', function(e) {
            e.preventDefault();
            openCustomModal(documentId, documentTitles[documentId]);
        });
    });
    
    // Ajouter un gestionnaire pour la soumission du formulaire
    document.getElementById('deleteDocumentForm').addEventListener('submit', function() {
        // Fermer le modal après soumission
        closeCustomModal();
    });
});

// Ouvrir le modal personnalisé
function openCustomModal(documentId, documentTitle) {
    // Mettre à jour les données du formulaire
    document.getElementById('documentIdInput').value = documentId;
    document.getElementById('documentTitleSpan').textContent = documentTitle;
    
    // Afficher le modal
    document.getElementById('customDeleteModal').style.display = 'block';
    
    // Désactiver le défilement du body
    document.body.style.overflow = 'hidden';
}

// Fermer le modal personnalisé
function closeCustomModal() {
    // Masquer le modal
    document.getElementById('customDeleteModal').style.display = 'none';
    
    // Réactiver le défilement du body
    document.body.style.overflow = '';
}

// Fermer le modal si l'utilisateur clique en dehors
window.addEventListener('click', function(event) {
    const modal = document.getElementById('customDeleteModal');
    if (event.target === modal) {
        closeCustomModal();
    }
});

// Fermer le modal avec la touche Échap
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeCustomModal();
    }
});
</script>
HTML;

// Ajouter notre modal personnalisé avant le footer
$finalContent = str_replace('<?php require_once __DIR__ . \'/../../common/footer.php\'; ?>', $modalCode . "\n\n<?php require_once __DIR__ . '/../../common/footer.php'; ?>", $contentWithNewButtons);

// Enregistrer les modifications
if (file_put_contents($documentsPath, $finalContent) === false) {
    echo "<p style='color:red;'>Impossible d'enregistrer les modifications</p>";
    exit;
}

echo "<p style='color:green;'>Solution HTML/JS directe implémentée avec succès!</p>";

// Supprimer le fichier CSS qui pourrait interférer
$cssFilePath = ROOT_PATH . '/assets/css/modal-fixes.css';
if (file_exists($cssFilePath)) {
    file_put_contents($cssFilePath, '/* CSS reset pour les modals */');
    echo "<p style='color:green;'>Fichier CSS réinitialisé pour éviter les interférences</p>";
}

// Instructions pour tester les modifications
echo "<h2>Prochaines étapes</h2>";
echo "<p>Une solution HTML/JS pure a été implémentée pour les modals de suppression:</p>";
echo "<ul>";
echo "<li>Tous les modals Bootstrap existants ont été supprimés pour éviter les conflits</li>";
echo "<li>Un modal HTML/CSS/JS pur a été créé sans dépendance à Bootstrap</li>";
echo "<li>Les boutons de suppression ont été modifiés pour utiliser notre modal personnalisé</li>";
echo "<li>Le CSS a été réinitialisé pour éviter les interférences</li>";
echo "</ul>";
echo "<p>Cette solution devrait fonctionner de manière fiable car elle évite complètement les conflits avec Bootstrap et autres bibliothèques.</p>";
echo "<p><a href='/tutoring/views/admin/documents.php' class='btn btn-primary'>Tester la page des documents</a></p>";
?>