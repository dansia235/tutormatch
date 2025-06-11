<?php
/**
 * Vue pour la liste des documents de l'utilisateur
 */

// Initialiser les variables
$pageTitle = 'Mes documents';
$currentPage = 'documents';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../../includes/init.php';

// Vérifier les permissions
requireRole(['admin', 'coordinator']);

// Instancier le contrôleur
$documentController = new DocumentController($db);

// Afficher les documents de l'utilisateur
$documentController->myDocuments();
?>

<?php require_once __DIR__ . '/../../common/header.php'; ?>

<div class="container-fluid">
    <!-- En-tête de page -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2 mb-0"><i class="bi bi-file-earmark-person me-2"></i>Mes documents</h1>
        
        <div class="btn-group">
            <a href="/tutoring/views/admin/documents/create.php" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i>Ajouter un document
            </a>
            <a href="/tutoring/views/admin/documents/index.php" class="btn btn-outline-secondary">
                <i class="bi bi-list me-2"></i>Tous les documents
            </a>
        </div>
    </div>
    
    <!-- Filtres par catégorie -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Filtrer par catégorie</h5>
            </div>
            <div class="mt-3">
                <div class="btn-group" role="group">
                    <a href="?category=" class="btn btn-outline-secondary <?php echo !isset($_GET['category']) ? 'active' : ''; ?>">Tous</a>
                    <a href="?category=cv" class="btn btn-outline-primary <?php echo isset($_GET['category']) && $_GET['category'] === 'cv' ? 'active' : ''; ?>">CV</a>
                    <a href="?category=report" class="btn btn-outline-success <?php echo isset($_GET['category']) && $_GET['category'] === 'report' ? 'active' : ''; ?>">Rapports</a>
                    <a href="?category=agreement" class="btn btn-outline-info <?php echo isset($_GET['category']) && $_GET['category'] === 'agreement' ? 'active' : ''; ?>">Conventions</a>
                    <a href="?category=evaluation" class="btn btn-outline-warning <?php echo isset($_GET['category']) && $_GET['category'] === 'evaluation' ? 'active' : ''; ?>">Évaluations</a>
                    <a href="?category=image" class="btn btn-outline-danger <?php echo isset($_GET['category']) && $_GET['category'] === 'image' ? 'active' : ''; ?>">Images</a>
                    <a href="?category=presentation" class="btn btn-outline-secondary <?php echo isset($_GET['category']) && $_GET['category'] === 'presentation' ? 'active' : ''; ?>">Présentations</a>
                    <a href="?category=other" class="btn btn-outline-dark <?php echo isset($_GET['category']) && $_GET['category'] === 'other' ? 'active' : ''; ?>">Autres</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Liste des documents -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Mes documents</h5>
        </div>
        <div class="card-body">
            <?php if (empty($documents)): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>Vous n'avez pas encore ajouté de documents.
                <a href="/tutoring/views/admin/documents/create.php" class="alert-link ms-2">Ajouter un document</a>
            </div>
            <?php else: ?>
            <div class="row">
                <?php foreach ($documents as $document): ?>
                <div class="col-md-4 col-lg-3 mb-4">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <?php 
                            $categoryLabels = [
                                'cv' => '<span class="badge bg-primary">CV</span>',
                                'report' => '<span class="badge bg-success">Rapport</span>',
                                'agreement' => '<span class="badge bg-info">Convention</span>',
                                'evaluation' => '<span class="badge bg-warning">Évaluation</span>',
                                'image' => '<span class="badge bg-danger">Image</span>',
                                'presentation' => '<span class="badge bg-secondary">Présentation</span>',
                                'other' => '<span class="badge bg-dark">Autre</span>'
                            ];
                            
                            $visibilityLabels = [
                                'private' => '<span class="badge bg-danger">Privé</span>',
                                'public' => '<span class="badge bg-success">Public</span>',
                                'restricted' => '<span class="badge bg-warning">Restreint</span>'
                            ];
                            
                            $category = isset($document['category']) ? $document['category'] : (isset($document['type']) ? $document['type'] : 'other');
                            $visibility = isset($document['visibility']) ? $document['visibility'] : 'private';
                            
                            echo isset($categoryLabels[$category]) ? $categoryLabels[$category] : '<span class="badge bg-secondary">Inconnu</span>';
                            echo ' ' . (isset($visibilityLabels[$visibility]) ? $visibilityLabels[$visibility] : '');
                            ?>
                        </div>
                        
                        <?php 
                        $isImage = false;
                        if (isset($document['category']) && $document['category'] === 'image' && 
                            isset($document['file_type']) && strpos($document['file_type'], 'image') !== false) {
                            $isImage = true;
                        }
                        if ($isImage): ?>
                        <div class="card-img-top document-preview">
                            <img src="<?php echo h($document['file_path']); ?>" alt="<?php echo h($document['title']); ?>" class="img-fluid">
                        </div>
                        <?php else: ?>
                        <div class="card-img-top document-icon">
                            <?php 
                            $iconClass = 'bi-file';
                            $iconColor = 'text-secondary';
                            
                            $category = isset($document['category']) ? $document['category'] : (isset($document['type']) ? $document['type'] : 'other');
                            switch ($category) {
                                case 'cv':
                                    $iconClass = 'bi-file-person';
                                    $iconColor = 'text-primary';
                                    break;
                                case 'report':
                                    $iconClass = 'bi-file-text';
                                    $iconColor = 'text-success';
                                    break;
                                case 'agreement':
                                    $iconClass = 'bi-file-earmark-text';
                                    $iconColor = 'text-info';
                                    break;
                                case 'evaluation':
                                    $iconClass = 'bi-file-check';
                                    $iconColor = 'text-warning';
                                    break;
                                case 'image':
                                    $iconClass = 'bi-file-image';
                                    $iconColor = 'text-danger';
                                    break;
                                case 'presentation':
                                    $iconClass = 'bi-file-slides';
                                    $iconColor = 'text-secondary';
                                    break;
                                case 'other':
                                    $fileType = isset($document['file_type']) ? $document['file_type'] : '';
                                    if (strpos($fileType, 'pdf') !== false) {
                                        $iconClass = 'bi-file-pdf';
                                    } elseif (strpos($fileType, 'word') !== false) {
                                        $iconClass = 'bi-file-word';
                                    } elseif (strpos($fileType, 'excel') !== false || strpos($fileType, 'sheet') !== false) {
                                        $iconClass = 'bi-file-excel';
                                    } elseif (strpos($fileType, 'powerpoint') !== false || strpos($fileType, 'presentation') !== false) {
                                        $iconClass = 'bi-file-slides';
                                    } elseif (strpos($fileType, 'zip') !== false || strpos($fileType, 'rar') !== false) {
                                        $iconClass = 'bi-file-zip';
                                    } elseif (strpos($fileType, 'text') !== false) {
                                        $iconClass = 'bi-file-text';
                                    }
                                    $iconColor = 'text-dark';
                                    break;
                            }
                            ?>
                            <i class="bi <?php echo $iconClass; ?> <?php echo $iconColor; ?>"></i>
                        </div>
                        <?php endif; ?>
                        
                        <div class="card-body">
                            <h5 class="card-title"><?php echo h(truncate($document['title'], 40)); ?></h5>
                            
                            <?php if ($document['description']): ?>
                            <p class="card-text small text-muted"><?php echo h(truncate($document['description'], 100)); ?></p>
                            <?php endif; ?>
                            
                            <div class="small text-muted mb-2">
                                <i class="bi bi-clock me-1"></i><?php echo formatDate($document['upload_date'], 'd/m/Y'); ?>
                            </div>
                            
                            <?php if (isset($document['related_type']) && !empty($document['related_type']) && 
                                      isset($document['related_id']) && !empty($document['related_id'])): ?>
                            <div class="small mb-2">
                                <i class="bi bi-link me-1"></i>
                                <?php 
                                switch ($document['related_type']) {
                                    case 'assignment':
                                        echo 'Lié à l\'affectation #' . $document['related_id'];
                                        break;
                                    case 'internship':
                                        echo 'Lié au stage #' . $document['related_id'];
                                        break;
                                    case 'student':
                                        echo 'Lié à l\'étudiant #' . $document['related_id'];
                                        break;
                                    case 'teacher':
                                        echo 'Lié au tuteur #' . $document['related_id'];
                                        break;
                                }
                                ?>
                            </div>
                            <?php endif; ?>
                            
                            <div class="d-grid gap-2">
                                <a href="/tutoring/views/admin/documents/show.php?id=<?php echo $document['id']; ?>" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-eye me-1"></i>Voir
                                </a>
                                <div class="btn-group btn-group-sm">
                                    <a href="/tutoring/views/admin/documents/download.php?id=<?php echo $document['id']; ?>" class="btn btn-outline-info">
                                        <i class="bi bi-download me-1"></i>Télécharger
                                    </a>
                                    <a href="/tutoring/views/admin/documents/edit.php?id=<?php echo $document['id']; ?>" class="btn btn-outline-secondary">
                                        <i class="bi bi-pencil me-1"></i>Modifier
                                    </a>
                                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $document['id']; ?>">
                                        <i class="bi bi-trash me-1"></i>Supprimer
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Modal de confirmation de suppression -->
                    <div class="modal fade" id="deleteModal<?php echo $document['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $document['id']; ?>" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="deleteModalLabel<?php echo $document['id']; ?>">Confirmer la suppression</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <p>Êtes-vous sûr de vouloir supprimer le document <strong><?php echo h($document['title']); ?></strong> ?</p>
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
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.document-preview {
    height: 180px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    background-color: #f8f9fa;
}

.document-preview img {
    max-height: 180px;
    object-fit: contain;
}

.document-icon {
    height: 180px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f8f9fa;
}

.document-icon i {
    font-size: 5rem;
}
</style>

<?php require_once __DIR__ . '/../../common/footer.php'; ?>