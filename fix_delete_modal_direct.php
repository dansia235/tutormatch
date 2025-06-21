<?php
/**
 * Script pour corriger directement le problème de clignotement des modals de suppression
 */

// Définir le chemin racine du système
define('ROOT_PATH', dirname(__FILE__));

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Correction directe du problème de clignotement des modals de suppression</h1>";

// Modifions directement la page documents.php
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
    
    // Remplacer la structure du modal de suppression
    $oldModalStructure = <<<'EOT'
                                <!-- Modal de confirmation de suppression -->
                                <div class="modal fade" id="deleteModal<?php echo $document['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $document['id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="deleteModalLabel<?php echo $document['id']; ?>">Confirmer la suppression</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Êtes-vous sûr de vouloir supprimer le document <strong><?php echo h($document['title'] ?? ''); ?></strong> ?</p>
                                                <p class="text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Cette action est irréversible et supprimera définitivement le fichier.</p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                <form action="/tutoring/views/admin/documents/delete.php" method="POST">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                                    <input type="hidden" name="id" value="<?php echo $document['id']; ?>">
                                                    <button type="submit" class="btn btn-danger">Supprimer</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
EOT;

    $newModalStructure = <<<'EOT'
                                <!-- Modal de confirmation de suppression (version stable) -->
                                <div class="modal modal-delete" id="deleteModal<?php echo $document['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $document['id']; ?>" aria-hidden="true" data-doc-id="<?php echo $document['id']; ?>">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="deleteModalLabel<?php echo $document['id']; ?>">Confirmer la suppression</h5>
                                                <button type="button" class="btn-close modal-close" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Êtes-vous sûr de vouloir supprimer le document <strong><?php echo h($document['title'] ?? ''); ?></strong> ?</p>
                                                <p class="text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Cette action est irréversible et supprimera définitivement le fichier.</p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary modal-close">Annuler</button>
                                                <form action="/tutoring/views/admin/documents/delete.php" method="POST" class="delete-form">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                                    <input type="hidden" name="id" value="<?php echo $document['id']; ?>">
                                                    <button type="submit" class="btn btn-danger">Supprimer</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
EOT;

    // Remplacer la structure du bouton de suppression
    $oldButtonStructure = <<<'EOT'
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $document['id']; ?>" title="Supprimer">
                                        <i class="bi bi-trash"></i>
                                    </button>
EOT;

    $newButtonStructure = <<<'EOT'
                                    <button type="button" class="btn btn-sm btn-outline-danger delete-btn" data-doc-id="<?php echo $document['id']; ?>" title="Supprimer">
                                        <i class="bi bi-trash"></i>
                                    </button>
EOT;

    // Faire les remplacements
    $newContent = str_replace($oldModalStructure, $newModalStructure, $content);
    $newContent = str_replace($oldButtonStructure, $newButtonStructure, $newContent);
    
    // Ajouter le CSS nécessaire dans l'en-tête
    $cssStyle = <<<'EOT'
    <style>
        /* Styles pour les modals de suppression stables */
        .modal-delete {
            display: none;
            position: fixed;
            z-index: 1050;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        
        .modal-delete.show {
            display: block;
            animation: fadeIn 0.3s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal-delete .modal-dialog {
            margin: 10% auto;
            max-width: 500px;
        }
        
        body.modal-open {
            overflow: hidden;
        }
    </style>
EOT;

    // Ajouter le CSS à l'en-tête
    $newContent = str_replace('<?php require_once __DIR__ . \'/../../common/header.php\'; ?>', '<?php require_once __DIR__ . \'/../../common/header.php\'; ?>' . "\n" . $cssStyle, $newContent);
    
    // Ajouter le JavaScript nécessaire à la fin du fichier
    $jsScript = <<<'EOT'

<script>
// Gestionnaire pour les modals de suppression stables
document.addEventListener('DOMContentLoaded', function() {
    // Fonction pour ouvrir un modal spécifique
    function openModal(docId) {
        const modal = document.getElementById('deleteModal' + docId);
        if (modal) {
            modal.classList.add('show');
            document.body.classList.add('modal-open');
            
            // Désactiver les interactions avec les éléments sous le modal
            document.body.style.overflow = 'hidden';
        }
    }
    
    // Fonction pour fermer un modal
    function closeModal(modal) {
        if (modal) {
            modal.classList.remove('show');
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
        }
    }
    
    // Attacher les gestionnaires d'événements aux boutons de suppression
    document.querySelectorAll('.delete-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const docId = this.getAttribute('data-doc-id');
            openModal(docId);
        });
    });
    
    // Attacher les gestionnaires d'événements aux boutons de fermeture des modals
    document.querySelectorAll('.modal-close').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const modal = this.closest('.modal-delete');
            closeModal(modal);
        });
    });
    
    // Fermer le modal quand on clique en dehors
    document.querySelectorAll('.modal-delete').forEach(function(modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal(this);
            }
        });
    });
    
    // Fermer le modal avec la touche ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const openModals = document.querySelectorAll('.modal-delete.show');
            openModals.forEach(function(modal) {
                closeModal(modal);
            });
        }
    });
});
</script>
EOT;

    // Ajouter le JavaScript à la fin du fichier
    $newContent = str_replace('<?php require_once __DIR__ . \'/../../common/footer.php\'; ?>', '<?php require_once __DIR__ . \'/../../common/footer.php\'; ?>' . "\n" . $jsScript, $newContent);
    
    // Enregistrer les modifications
    if (file_put_contents($documentsPath, $newContent) === false) {
        echo "<p style='color:red;'>Impossible d'enregistrer les modifications pour $documentsPath</p>";
        exit;
    }
    
    echo "<p style='color:green;'>Fichier documents.php modifié avec succès avec une implémentation de modal stable</p>";
}

// Supprimer l'ancien script de correction qui n'a pas fonctionné
$oldFixPath = ROOT_PATH . '/assets/js/modal-fix.js';
if (file_exists($oldFixPath)) {
    if (unlink($oldFixPath)) {
        echo "<p style='color:green;'>Ancien script de correction supprimé: $oldFixPath</p>";
    } else {
        echo "<p style='color:orange;'>Impossible de supprimer l'ancien script de correction: $oldFixPath</p>";
    }
}

// Instructions pour tester les modifications
echo "<h2>Prochaines étapes</h2>";
echo "<p>Une implémentation complètement nouvelle des modals de suppression a été mise en place. Nous avons:</p>";
echo "<ul>";
echo "<li>Remplacé l'implémentation basée sur Bootstrap par une implémentation personnalisée et stable</li>";
echo "<li>Ajouté du CSS pour garantir un affichage correct et des animations fluides</li>";
echo "<li>Ajouté du JavaScript pour gérer correctement les événements d'ouverture et de fermeture</li>";
echo "<li>Supprimé l'ancien script de correction qui n'a pas fonctionné</li>";
echo "</ul>";
echo "<p>Veuillez tester l'interface de suppression des documents pour vérifier que le problème de clignotement a été résolu.</p>";
echo "<p><a href='/tutoring/views/admin/documents.php' class='btn btn-primary'>Tester la page des documents</a></p>";
?>