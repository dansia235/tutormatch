<?php
/**
 * Vue pour afficher les documents liés à une affectation
 */

// Initialiser les variables
$pageTitle = 'Documents de l\'affectation';
$currentPage = 'assignments';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../../includes/init.php';

// Vérifier les permissions
requireRole(['admin', 'coordinator']);

// Vérifier l'ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage('error', 'ID d\'affectation invalide');
    redirect('/tutoring/views/admin/assignments/index.php');
}

// Instancier le contrôleur
$documentController = new DocumentController($db);

// Afficher les documents liés à l'affectation
$documentController->assignmentDocuments($_GET['id']);
?>

<?php require_once __DIR__ . '/../../common/header.php'; ?>

<div class="container-fluid">
    <!-- En-tête de page avec actions -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-0">
                <i class="bi bi-file-earmark-text me-2"></i>Documents de l'affectation #<?php echo $assignment['id']; ?>
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/tutoring/views/admin/dashboard.php">Tableau de bord</a></li>
                    <li class="breadcrumb-item"><a href="/tutoring/views/admin/assignments/index.php">Affectations</a></li>
                    <li class="breadcrumb-item"><a href="/tutoring/views/admin/assignments/show.php?id=<?php echo $assignment['id']; ?>">Affectation #<?php echo $assignment['id']; ?></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Documents</li>
                </ol>
            </nav>
        </div>
        
        <div class="btn-group" role="group">
            <a href="/tutoring/views/admin/documents/create.php?related_id=<?php echo $assignment['id']; ?>&related_type=assignment" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i>Ajouter un document
            </a>
            <a href="/tutoring/views/admin/assignments/show.php?id=<?php echo $assignment['id']; ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Retour à l'affectation
            </a>
        </div>
    </div>
    
    <!-- Informations sur l'affectation -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Informations sur l'affectation</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <h6 class="fw-bold">Étudiant</h6>
                    <p class="mb-1">
                        <?php echo h($assignment['student_first_name'] . ' ' . $assignment['student_last_name']); ?>
                    </p>
                    <p class="text-muted small mb-0"><?php echo h($assignment['student_program'] ?? ''); ?></p>
                </div>
                <div class="col-md-4">
                    <h6 class="fw-bold">Tuteur</h6>
                    <p class="mb-1">
                        <?php echo h($assignment['teacher_first_name'] . ' ' . $assignment['teacher_last_name']); ?>
                    </p>
                    <p class="text-muted small mb-0"><?php echo h($assignment['teacher_specialty'] ?? ''); ?></p>
                </div>
                <div class="col-md-4">
                    <h6 class="fw-bold">Stage</h6>
                    <p class="mb-1">
                        <?php echo h($assignment['internship_title']); ?>
                    </p>
                    <p class="text-muted small mb-0"><?php echo h($assignment['company_name']); ?></p>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-4">
                    <h6 class="fw-bold">Statut</h6>
                    <p class="mb-0">
                        <?php
                        $statusBadge = [
                            'pending' => '<span class="badge bg-warning">En attente</span>',
                            'confirmed' => '<span class="badge bg-success">Confirmée</span>',
                            'rejected' => '<span class="badge bg-danger">Rejetée</span>',
                            'completed' => '<span class="badge bg-info">Terminée</span>'
                        ];
                        echo $statusBadge[$assignment['status']] ?? '<span class="badge bg-secondary">Inconnue</span>';
                        ?>
                    </p>
                </div>
                <div class="col-md-4">
                    <h6 class="fw-bold">Date d'affectation</h6>
                    <p class="mb-0"><?php echo formatDate($assignment['assignment_date']); ?></p>
                </div>
                <div class="col-md-4">
                    <h6 class="fw-bold">Score de compatibilité</h6>
                    <div class="d-flex align-items-center">
                        <div class="progress flex-grow-1 me-2" style="height: 8px;">
                            <?php
                            $compatibilityScore = $assignment['compatibility_score'] ?? 0;
                            $compatibilityClass = 'bg-danger';
                            
                            if ($compatibilityScore >= 7) {
                                $compatibilityClass = 'bg-success';
                            } elseif ($compatibilityScore >= 4) {
                                $compatibilityClass = 'bg-warning';
                            }
                            ?>
                            <div class="progress-bar <?php echo $compatibilityClass; ?>" role="progressbar"
                                style="width: <?php echo ($compatibilityScore * 10); ?>%"
                                aria-valuenow="<?php echo $compatibilityScore; ?>"
                                aria-valuemin="0" aria-valuemax="10">
                            </div>
                        </div>
                        <span class="small"><?php echo number_format($compatibilityScore, 1); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Liste des documents -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Documents associés</h5>
        </div>
        <div class="card-body">
            <?php if (empty($documents)): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>Aucun document n'est associé à cette affectation.
                <a href="/tutoring/views/admin/documents/create.php?related_id=<?php echo $assignment['id']; ?>&related_type=assignment" class="alert-link ms-2">Ajouter un document</a>
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
                                <br>
                                <i class="bi bi-person me-1"></i><?php echo h($document['first_name'] . ' ' . $document['last_name']); ?>
                            </div>
                            
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