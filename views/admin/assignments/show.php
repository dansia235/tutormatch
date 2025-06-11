<?php
/**
 * Vue pour afficher les détails d'une affectation
 */

// Initialiser les variables
$pageTitle = 'Détails de l\'affectation';
$currentPage = 'assignments';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../../includes/init.php';

// Vérifier les permissions
requireRole(['admin', 'coordinator']);

// Vérifier l'ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage('error', 'ID d\'affectation invalide');
    redirect('/tutoring/views/admin/assignments.php');
}

// S'assurer que la connexion à la base de données est disponible
if (!isset($db) || $db === null) {
    $db = getDBConnection();
}

// Instancier le contrôleur (pour référence future)
$assignmentController = new AssignmentController($db);

// Récupérer l'affectation et les données associées directement
$assignmentModel = new Assignment($db);
$assignment = $assignmentModel->getById($_GET['id']);

if (!$assignment) {
    setFlashMessage('error', 'Affectation non trouvée');
    redirect('/tutoring/views/admin/assignments.php');
}

// Récupérer les informations complémentaires
$studentModel = new Student($db);
$student = $studentModel->getById($assignment['student_id']);

$teacherModel = new Teacher($db);
$teacher = $teacherModel->getById($assignment['teacher_id']);
$teacherAssignmentCount = $assignmentModel->countByTeacherId($assignment['teacher_id']);

$internshipModel = new Internship($db);
$internship = $internshipModel->getById($assignment['internship_id']);

// Récupérer les évaluations si nécessaire
$evaluationModel = new Evaluation($db);
$evaluations = $evaluationModel->getByAssignmentId($assignment['id']);
?>

<?php require_once __DIR__ . '/../../common/header.php'; ?>

<div class="container-fluid">
    <!-- En-tête de page avec actions -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-0">
                <i class="bi bi-diagram-3 me-2"></i>Détails de l'affectation
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/tutoring/views/admin/dashboard.php">Tableau de bord</a></li>
                    <li class="breadcrumb-item"><a href="/tutoring/views/admin/assignments.php">Affectations</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Affectation #<?php echo h($assignment['id']); ?></li>
                </ol>
            </nav>
        </div>
        
        <div class="btn-group" role="group">
            <a href="/tutoring/views/admin/assignments/edit.php?id=<?php echo $assignment['id']; ?>" class="btn btn-outline-primary">
                <i class="bi bi-pencil me-2"></i>Modifier
            </a>
            <a href="/tutoring/views/admin/assignments.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Retour
            </a>
        </div>
    </div>
    
    <div class="row">
        <!-- Informations sur l'affectation -->
        <div class="col-md-8 mb-4">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Informations sur l'affectation</h5>
                    <?php
                    $statusBadge = [
                        'pending' => '<span class="badge bg-warning">En attente</span>',
                        'confirmed' => '<span class="badge bg-success">Confirmée</span>',
                        'rejected' => '<span class="badge bg-danger">Rejetée</span>',
                        'completed' => '<span class="badge bg-info">Terminée</span>'
                    ];
                    echo $statusBadge[$assignment['status']] ?? '<span class="badge bg-secondary">Inconnue</span>';
                    ?>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="fw-bold">Dates importantes</h6>
                            <p class="mb-1">
                                <i class="bi bi-calendar3 me-2"></i><strong>Date d'affectation:</strong> 
                                <?php echo formatDate($assignment['assignment_date']); ?>
                            </p>
                            <?php if ($assignment['confirmation_date']): ?>
                            <p class="mb-1">
                                <i class="bi bi-calendar-check me-2"></i><strong>Date de confirmation:</strong> 
                                <?php echo formatDate($assignment['confirmation_date']); ?>
                            </p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold">Score de compatibilité</h6>
                            <div class="d-flex align-items-center">
                                <?php
                                $compatibilityScore = min(10, $assignment['compatibility_score'] ?? 0);
                                $compatibilityClass = 'bg-danger';
                                
                                if ($compatibilityScore >= 7) {
                                    $compatibilityClass = 'bg-success';
                                } elseif ($compatibilityScore >= 4) {
                                    $compatibilityClass = 'bg-warning';
                                }
                                ?>
                                <div class="progress flex-grow-1 me-2" style="height: 10px;">
                                    <div class="progress-bar <?php echo $compatibilityClass; ?>" role="progressbar" 
                                        style="width: <?php echo ($compatibilityScore * 10); ?>%" 
                                        aria-valuenow="<?php echo $compatibilityScore; ?>" 
                                        aria-valuemin="0" aria-valuemax="10">
                                    </div>
                                </div>
                                <span class="fw-bold fs-5"><?php echo number_format($compatibilityScore, 1); ?>/10</span>
                            </div>
                            
                            <?php if (isset($assignment['satisfaction_score']) && $assignment['satisfaction_score']): ?>
                            <h6 class="fw-bold mt-3">Score de satisfaction</h6>
                            <div class="d-flex align-items-center">
                                <?php
                                $satisfactionScore = min(10, $assignment['satisfaction_score']);
                                $satisfactionClass = 'bg-danger';
                                
                                if ($satisfactionScore >= 7) {
                                    $satisfactionClass = 'bg-success';
                                } elseif ($satisfactionScore >= 4) {
                                    $satisfactionClass = 'bg-warning';
                                }
                                ?>
                                <div class="progress flex-grow-1 me-2" style="height: 10px;">
                                    <div class="progress-bar <?php echo $satisfactionClass; ?>" role="progressbar" 
                                        style="width: <?php echo ($satisfactionScore * 10); ?>%" 
                                        aria-valuenow="<?php echo $satisfactionScore; ?>" 
                                        aria-valuemin="0" aria-valuemax="10">
                                    </div>
                                </div>
                                <span class="fw-bold fs-5"><?php echo number_format($satisfactionScore, 1); ?>/10</span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($assignment['notes']): ?>
                    <div class="mb-4">
                        <h6 class="fw-bold">Notes</h6>
                        <div class="card-text">
                            <?php echo nl2br(h($assignment['notes'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mb-3 text-muted">
                        <small>
                            <i class="bi bi-clock me-1"></i>Créé le <?php echo isset($assignment['created_at']) ? formatDate($assignment['created_at'], 'd/m/Y H:i') : 'N/A'; ?>
                            <?php if (isset($assignment['updated_at']) && $assignment['updated_at'] && isset($assignment['created_at']) && $assignment['updated_at'] !== $assignment['created_at']): ?>
                            <span class="mx-2">|</span>
                            <i class="bi bi-pencil me-1"></i>Mis à jour le <?php echo formatDate($assignment['updated_at'], 'd/m/Y H:i'); ?>
                            <?php endif; ?>
                        </small>
                    </div>
                </div>
            </div>
            
            <!-- Onglets pour Évaluations et Documents -->
            <ul class="nav nav-tabs mb-3" id="assignmentTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="evaluations-tab" data-bs-toggle="tab" data-bs-target="#evaluations-content" 
                            type="button" role="tab" aria-controls="evaluations-content" aria-selected="true">
                        <i class="bi bi-clipboard-check me-2"></i>Évaluations
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="documents-tab" data-bs-toggle="tab" data-bs-target="#documents-content" 
                            type="button" role="tab" aria-controls="documents-content" aria-selected="false">
                        <i class="bi bi-file-earmark-text me-2"></i>Documents
                    </button>
                </li>
            </ul>
            
            <div class="tab-content" id="assignmentTabsContent">
                <!-- Onglet Évaluations -->
                <div class="tab-pane fade show active" id="evaluations-content" role="tabpanel" aria-labelledby="evaluations-tab">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Évaluations</h5>
                            <div class="btn-group">
                                <a href="/tutoring/views/admin/assignments/evaluation_form.php?id=<?php echo $assignment['id']; ?>&type=mid_term" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-plus-circle me-1"></i>Nouvelle évaluation
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (empty($evaluations)): ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>Aucune évaluation n'a encore été soumise pour cette affectation.
                            </div>
                            
                            <div class="d-flex justify-content-center gap-3 mt-3">
                                <a href="/tutoring/views/admin/assignments/evaluation_form.php?id=<?php echo $assignment['id']; ?>&type=mid_term" class="btn btn-outline-primary">
                                    <i class="bi bi-clipboard-check me-2"></i>Créer évaluation mi-parcours
                                </a>
                                <a href="/tutoring/views/admin/assignments/evaluation_form.php?id=<?php echo $assignment['id']; ?>&type=final" class="btn btn-outline-primary">
                                    <i class="bi bi-clipboard-check me-2"></i>Créer évaluation finale
                                </a>
                            </div>
                            <?php else: ?>
                            <ul class="nav nav-tabs mb-3" id="evaluationTabs" role="tablist">
                                <?php 
                                $first = true;
                                $tabIds = [];
                                foreach ($evaluations as $i => $evaluation): 
                                    $tabId = 'evaluation-' . $evaluation['id'];
                                    $tabIds[] = $tabId;
                                ?>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link <?php echo $first ? 'active' : ''; ?>" 
                                            id="<?php echo $tabId; ?>-tab" 
                                            data-bs-toggle="tab" 
                                            data-bs-target="#<?php echo $tabId; ?>-content" 
                                            type="button" 
                                            role="tab" 
                                            aria-controls="<?php echo $tabId; ?>-content" 
                                            aria-selected="<?php echo $first ? 'true' : 'false'; ?>">
                                        <?php 
                                        $typeLabels = [
                                            'mid_term' => 'Évaluation mi-parcours',
                                            'final' => 'Évaluation finale',
                                            'student' => 'Évaluation par l\'étudiant',
                                            'teacher' => 'Évaluation par l\'enseignant'
                                        ];
                                        echo $typeLabels[$evaluation['type']] ?? 'Évaluation';
                                        ?>
                                    </button>
                                </li>
                                <?php 
                                $first = false;
                                endforeach; 
                                ?>
                                <li class="nav-item ms-auto" role="presentation">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" id="newEvaluationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-plus-circle me-1"></i>Nouvelle évaluation
                                        </button>
                                        <ul class="dropdown-menu" aria-labelledby="newEvaluationDropdown">
                                            <li><a class="dropdown-item" href="/tutoring/views/admin/assignments/evaluation_form.php?id=<?php echo $assignment['id']; ?>&type=mid_term">Évaluation mi-parcours</a></li>
                                            <li><a class="dropdown-item" href="/tutoring/views/admin/assignments/evaluation_form.php?id=<?php echo $assignment['id']; ?>&type=final">Évaluation finale</a></li>
                                        </ul>
                                    </div>
                                </li>
                            </ul>
                            
                            <div class="tab-content" id="evaluationTabsContent">
                                <?php 
                                $first = true;
                                foreach ($evaluations as $i => $evaluation): 
                                    $tabId = $tabIds[$i];
                                ?>
                                <div class="tab-pane fade <?php echo $first ? 'show active' : ''; ?>" 
                                     id="<?php echo $tabId; ?>-content" 
                                     role="tabpanel" 
                                     aria-labelledby="<?php echo $tabId; ?>-tab">
                                    
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="fw-bold mb-0">
                                            <?php echo $typeLabels[$evaluation['type']] ?? 'Évaluation'; ?>
                                            <span class="text-muted ms-2">
                                                (<?php echo isset($evaluation['created_at']) ? formatDate($evaluation['created_at'], 'd/m/Y') : formatDate($evaluation['submission_date'], 'd/m/Y'); ?>)
                                            </span>
                                        </h6>
                                        <div class="d-flex align-items-center">
                                            <div class="me-2">Score:</div>
                                            <div class="d-flex align-items-center">
                                                <?php
                                                $evalScore = $evaluation['score'];
                                                $evalClass = 'bg-danger';
                                                
                                                if ($evalScore >= 7) {
                                                    $evalClass = 'bg-success';
                                                } elseif ($evalScore >= 4) {
                                                    $evalClass = 'bg-warning';
                                                }
                                                ?>
                                                <div class="progress me-2" style="width: 100px; height: 8px;">
                                                    <div class="progress-bar <?php echo $evalClass; ?>" role="progressbar" 
                                                        style="width: <?php echo ($evalScore * 10); ?>%" 
                                                        aria-valuenow="<?php echo $evalScore; ?>" 
                                                        aria-valuemin="0" aria-valuemax="10">
                                                    </div>
                                                </div>
                                                <span class="fw-bold"><?php echo number_format($evalScore, 1); ?>/10</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="card mb-3">
                                        <div class="card-header">
                                            <h6 class="card-subtitle mb-0">Feedback général</h6>
                                        </div>
                                        <div class="card-body">
                                            <?php echo nl2br(h($evaluation['feedback'])); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <div class="card h-100">
                                                <div class="card-header">
                                                    <h6 class="card-subtitle mb-0">Points forts</h6>
                                                </div>
                                                <div class="card-body">
                                                    <?php echo $evaluation['strengths'] ? nl2br(h($evaluation['strengths'])) : 'Aucun point fort spécifié.'; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="card h-100">
                                                <div class="card-header">
                                                    <h6 class="card-subtitle mb-0">Points à améliorer</h6>
                                                </div>
                                                <div class="card-body">
                                                    <?php echo $evaluation['areas_to_improve'] ? nl2br(h($evaluation['areas_to_improve'])) : 'Aucun point à améliorer spécifié.'; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="text-end mt-3">
                                        <a href="/tutoring/views/admin/assignments/evaluation_form.php?id=<?php echo $assignment['id']; ?>&evaluation_id=<?php echo $evaluation['id']; ?>" class="btn btn-outline-secondary btn-sm">
                                            <i class="bi bi-pencil me-1"></i>Modifier l'évaluation
                                        </a>
                                    </div>
                                </div>
                                <?php 
                                $first = false;
                                endforeach; 
                                ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Onglet Documents -->
                <div class="tab-pane fade" id="documents-content" role="tabpanel" aria-labelledby="documents-tab">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Documents</h5>
                            <div class="btn-group">
                                <a href="/tutoring/views/admin/documents/create.php?related_id=<?php echo $assignment['id']; ?>&related_type=assignment" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-plus-circle me-1"></i>Ajouter un document
                                </a>
                                <a href="/tutoring/views/admin/documents/assignment-documents.php?id=<?php echo $assignment['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-list me-1"></i>Voir tous les documents
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php 
                            // Récupérer les documents de cette affectation
                            $documentModel = new Document($db);
                            $documents = $documentModel->getByAssignmentId($assignment['id']);
                            
                            if (empty($documents)):
                            ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>Aucun document n'a encore été associé à cette affectation.
                            </div>
                            
                            <div class="text-center mt-3">
                                <a href="/tutoring/views/admin/documents/create.php?related_id=<?php echo $assignment['id']; ?>&related_type=assignment" class="btn btn-primary">
                                    <i class="bi bi-plus-circle me-2"></i>Ajouter un document
                                </a>
                            </div>
                            <?php else: ?>
                            <div class="row">
                                <?php foreach ($documents as $document): ?>
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="card h-100">
                                        <div class="card-header d-flex justify-content-between align-items-center py-2">
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
                                            echo $categoryLabels[$document['category']] ?? '<span class="badge bg-secondary">Inconnu</span>';
                                            ?>
                                            <small class="text-muted"><?php echo formatDate($document['upload_date'], 'd/m/Y'); ?></small>
                                        </div>
                                        <div class="card-body">
                                            <h6 class="card-title"><?php echo h(truncate($document['title'], 40)); ?></h6>
                                            <p class="card-text small">
                                                <i class="bi bi-person me-1"></i><?php echo h($document['first_name'] . ' ' . $document['last_name']); ?>
                                            </p>
                                            <?php if ($document['description']): ?>
                                            <p class="card-text small text-muted"><?php echo h(truncate($document['description'], 60)); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="card-footer bg-transparent border-top-0">
                                            <div class="btn-group btn-group-sm w-100">
                                                <a href="/tutoring/views/admin/documents/show.php?id=<?php echo $document['id']; ?>" class="btn btn-outline-primary">
                                                    <i class="bi bi-eye me-1"></i>Voir
                                                </a>
                                                <a href="/tutoring/views/admin/documents/download.php?id=<?php echo $document['id']; ?>" class="btn btn-outline-info">
                                                    <i class="bi bi-download me-1"></i>Télécharger
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <?php if (count($documents) > 6): ?>
                            <div class="text-center mt-3">
                                <a href="/tutoring/views/admin/documents/assignment-documents.php?id=<?php echo $assignment['id']; ?>" class="btn btn-outline-primary">
                                    <i class="bi bi-arrow-right me-1"></i>Voir tous les documents (<?php echo count($documents); ?>)
                                </a>
                            </div>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Informations sur les participants et actions -->
        <div class="col-md-4 mb-4">
            <!-- Carte de l'étudiant -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Étudiant</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <?php if ($student['profile_image']): ?>
                        <img src="<?php echo h($student['profile_image']); ?>" alt="Student" class="rounded-circle me-3" width="64" height="64">
                        <?php else: ?>
                        <div class="avatar-lg me-3">
                            <?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?>
                        </div>
                        <?php endif; ?>
                        <div>
                            <h5 class="mb-0"><?php echo h($student['first_name'] . ' ' . $student['last_name']); ?></h5>
                            <p class="text-muted mb-0"><?php echo h($student['program']); ?></p>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <p class="mb-1"><strong>Email:</strong> <a href="mailto:<?php echo h($student['email']); ?>"><?php echo h($student['email']); ?></a></p>
                        <?php if (isset($student['phone']) && $student['phone']): ?>
                        <p class="mb-1"><strong>Téléphone:</strong> <a href="tel:<?php echo h($student['phone']); ?>"><?php echo h($student['phone']); ?></a></p>
                        <?php endif; ?>
                        <p class="mb-1"><strong>Niveau:</strong> <?php echo h($student['level']); ?></p>
                        <p class="mb-1"><strong>Département:</strong> <?php echo h($student['department']); ?></p>
                        <?php if ($student['average_grade']): ?>
                        <p class="mb-1"><strong>Moyenne:</strong> <?php echo number_format($student['average_grade'], 2); ?>/20</p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="text-center">
                        <a href="/tutoring/views/admin/students/show.php?id=<?php echo $student['id']; ?>" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-person me-2"></i>Voir le profil complet
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Carte de l'enseignant -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Tuteur</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <?php if ($teacher['profile_image']): ?>
                        <img src="<?php echo h($teacher['profile_image']); ?>" alt="Teacher" class="rounded-circle me-3" width="64" height="64">
                        <?php else: ?>
                        <div class="avatar-lg me-3">
                            <?php echo strtoupper(substr($teacher['first_name'], 0, 1) . substr($teacher['last_name'], 0, 1)); ?>
                        </div>
                        <?php endif; ?>
                        <div>
                            <h5 class="mb-0"><?php echo h($teacher['first_name'] . ' ' . $teacher['last_name']); ?></h5>
                            <p class="text-muted mb-0"><?php echo h(cleanSpecialty($teacher['specialty'])); ?></p>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <p class="mb-1"><strong>Email:</strong> <a href="mailto:<?php echo h($teacher['email']); ?>"><?php echo h($teacher['email']); ?></a></p>
                        <?php if (isset($teacher['phone']) && $teacher['phone']): ?>
                        <p class="mb-1"><strong>Téléphone:</strong> <a href="tel:<?php echo h($teacher['phone']); ?>"><?php echo h($teacher['phone']); ?></a></p>
                        <?php endif; ?>
                        <p class="mb-1"><strong>Département:</strong> <?php echo h($teacher['department']); ?></p>
                        <p class="mb-1">
                            <strong>Charge actuelle:</strong> 
                            <?php 
                            $currentCount = isset($teacherAssignmentCount) ? $teacherAssignmentCount : 0;
                            echo "$currentCount/{$teacher['max_students']} étudiants"; 
                            ?>
                        </p>
                    </div>
                    
                    <div class="text-center">
                        <a href="/tutoring/views/admin/teachers/show.php?id=<?php echo $teacher['id']; ?>" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-person me-2"></i>Voir le profil complet
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Carte du stage -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Stage</h5>
                </div>
                <div class="card-body">
                    <h5><?php echo h($internship['title']); ?></h5>
                    <p class="mb-2 text-muted">
                        <i class="bi bi-building me-1"></i>
                        <?php echo h($internship['company_name']); ?>
                    </p>
                    
                    <div class="mb-3">
                        <p class="mb-1">
                            <i class="bi bi-calendar3 me-2"></i>
                            <strong>Période:</strong> 
                            <?php echo formatDate($internship['start_date']); ?> au <?php echo formatDate($internship['end_date']); ?>
                        </p>
                        <p class="mb-1">
                            <i class="bi bi-geo-alt me-2"></i>
                            <strong>Lieu:</strong> 
                            <?php echo h($internship['location'] ?: 'Non spécifié'); ?>
                        </p>
                        <p class="mb-1">
                            <i class="bi bi-tag me-2"></i>
                            <strong>Domaine:</strong> 
                            <?php echo h($internship['domain']); ?>
                        </p>
                    </div>
                    
                    <div class="text-center">
                        <a href="/tutoring/views/admin/internships/show.php?id=<?php echo $internship['id']; ?>" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-briefcase me-2"></i>Voir les détails du stage
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Carte des actions -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="/tutoring/views/admin/assignments/edit.php?id=<?php echo $assignment['id']; ?>" class="btn btn-outline-primary">
                            <i class="bi bi-pencil me-2"></i>Modifier l'affectation
                        </a>
                        
                        <?php if ($assignment['status'] === 'pending'): ?>
                        <form action="/tutoring/views/admin/assignments/update-status.php" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            <input type="hidden" name="id" value="<?php echo $assignment['id']; ?>">
                            <input type="hidden" name="status" value="confirmed">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-check-circle me-2"></i>Confirmer l'affectation
                            </button>
                        </form>
                        <?php elseif ($assignment['status'] === 'confirmed'): ?>
                        <form action="/tutoring/views/admin/assignments/update-status.php" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            <input type="hidden" name="id" value="<?php echo $assignment['id']; ?>">
                            <input type="hidden" name="status" value="completed">
                            <button type="submit" class="btn btn-info w-100">
                                <i class="bi bi-check-all me-2"></i>Marquer comme terminée
                            </button>
                        </form>
                        <?php endif; ?>
                        
                        <?php if ($assignment['status'] !== 'rejected'): ?>
                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#statusModal">
                            <i class="bi bi-arrow-repeat me-2"></i>Changer le statut
                        </button>
                        <?php endif; ?>
                        
                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                            <i class="bi bi-trash me-2"></i>Supprimer l'affectation
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de changement de statut -->
<div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="statusModalLabel">Changer le statut de l'affectation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/tutoring/views/admin/assignments/update-status.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="id" value="<?php echo $assignment['id']; ?>">
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Nouveau statut</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="pending" <?php echo $assignment['status'] === 'pending' ? 'selected' : ''; ?>>En attente</option>
                            <option value="confirmed" <?php echo $assignment['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmée</option>
                            <option value="rejected" <?php echo $assignment['status'] === 'rejected' ? 'selected' : ''; ?>>Rejetée</option>
                            <option value="completed" <?php echo $assignment['status'] === 'completed' ? 'selected' : ''; ?>>Terminée</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes (optionnel)</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo h($assignment['notes']); ?></textarea>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Attention :</strong> Changer le statut enverra des notifications aux personnes concernées.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de confirmation de suppression -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer l'affectation de <strong><?php echo h($student['first_name'] . ' ' . $student['last_name']); ?></strong> au stage <strong><?php echo h($internship['title']); ?></strong> ?</p>
                <p class="text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Cette action est irréversible et libérera le stage pour d'autres affectations.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form action="/tutoring/views/admin/assignments/delete.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="id" value="<?php echo $assignment['id']; ?>">
                    <button type="submit" class="btn btn-danger">Supprimer</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../common/footer.php'; ?>