<?php
/**
 * Vue pour afficher les détails d'un étudiant
 */

// Initialiser les variables
$pageTitle = 'Détails de l\'étudiant';
$currentPage = 'students';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../../includes/init.php';

// Vérifier les permissions
requireRole(['admin', 'coordinator', 'teacher']);

// Vérifier l'ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage('error', 'ID d\'étudiant invalide');
    redirect('/tutoring/views/admin/students.php');
}

// Instancier le contrôleur
$studentController = new StudentController($db);

// Récupérer les données de l'étudiant
$student = $studentController->getStudentDetails($_GET['id']);

if (!$student) {
    setFlashMessage('error', 'Étudiant non trouvé');
    redirect('/tutoring/views/admin/students.php');
    exit;
}

// Récupérer les données associées
$preferences = $studentController->getStudentPreferences($_GET['id']);
$assignment = $studentController->getStudentAssignment($_GET['id']);
$documents = $studentController->getStudentDocuments($_GET['id']);
$meetings = $studentController->getStudentMeetings($_GET['id']);

// Inclure l'en-tête
require_once __DIR__ . '/../../common/header.php';
?>

<style>
    /* Styles pour les avatars */
    .avatar-xl {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        font-weight: bold;
        background-color: #4e73df;
        color: white;
    }
    
    .avatar-sm {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        font-weight: bold;
        background-color: #4e73df;
        color: white;
    }
</style>

<div class="container-fluid">
    <!-- En-tête de page avec actions -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-0">
                <i class="bi bi-mortarboard me-2"></i>Profil étudiant
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/tutoring/views/admin/dashboard.php">Tableau de bord</a></li>
                    <li class="breadcrumb-item"><a href="/tutoring/views/admin/students.php">Étudiants</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?php echo h($student['first_name'] . ' ' . $student['last_name']); ?></li>
                </ol>
            </nav>
        </div>
        
        <div class="btn-group" role="group">
            <?php if (hasRole(['admin', 'coordinator'])): ?>
            <a href="/tutoring/views/admin/students/edit.php?id=<?php echo $student['id']; ?>" class="btn btn-outline-primary">
                <i class="bi bi-pencil me-2"></i>Modifier
            </a>
            <?php endif; ?>
            
            <a href="/tutoring/views/admin/students.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Retour
            </a>
        </div>
    </div>
    
    <div class="row">
        <!-- Profil de l'étudiant -->
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Informations personnelles</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <?php if ($student['profile_image']): ?>
                        <img src="<?php echo h($student['profile_image']); ?>" alt="Profile" class="rounded-circle mb-3" width="150" height="150">
                        <?php else: ?>
                        <div class="avatar-xl mx-auto mb-3">
                            <?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?>
                        </div>
                        <?php endif; ?>
                        <h3 class="mb-0"><?php echo h($student['first_name'] . ' ' . $student['last_name']); ?></h3>
                        <p class="text-muted"><?php echo h($student['email']); ?></p>
                        <div class="badge bg-primary"><?php echo h($student['student_number']); ?></div>
                    </div>
                    
                    <div class="mb-3">
                        <h6 class="fw-bold">Compte utilisateur</h6>
                        <div class="row">
                            <div class="col-sm-6">
                                <p class="mb-0 text-muted">Nom d'utilisateur</p>
                                <p class="fw-bold"><?php echo h($student['username']); ?></p>
                            </div>
                            <div class="col-sm-6">
                                <p class="mb-0 text-muted">Statut</p>
                                <p>
                                    <?php
                                    $statusBadge = [
                                        'active' => '<span class="badge bg-success">Actif</span>',
                                        'graduated' => '<span class="badge bg-info">Diplômé</span>',
                                        'suspended' => '<span class="badge bg-warning">Suspendu</span>'
                                    ];
                                    echo $statusBadge[$student['status']] ?? '<span class="badge bg-secondary">Inconnu</span>';
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <h6 class="fw-bold">Académique</h6>
                        <div class="row">
                            <div class="col-sm-6">
                                <p class="mb-0 text-muted">Programme</p>
                                <p class="fw-bold"><?php echo h($student['program']); ?></p>
                            </div>
                            <div class="col-sm-6">
                                <p class="mb-0 text-muted">Niveau</p>
                                <p class="fw-bold"><?php echo h($student['level']); ?></p>
                            </div>
                            <div class="col-sm-6">
                                <p class="mb-0 text-muted">Département</p>
                                <p class="fw-bold"><?php echo h($student['department']); ?></p>
                            </div>
                            <div class="col-sm-6">
                                <p class="mb-0 text-muted">Année de diplôme</p>
                                <p class="fw-bold"><?php echo h($student['graduation_year'] ?: 'N/A'); ?></p>
                            </div>
                            <div class="col-sm-6">
                                <p class="mb-0 text-muted">Moyenne</p>
                                <p class="fw-bold"><?php echo h($student['average_grade'] ? number_format($student['average_grade'], 2) : 'N/A'); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($student['cv_path']): ?>
                    <div class="mb-3">
                        <h6 class="fw-bold">Documents</h6>
                        <a href="<?php echo h($student['cv_path']); ?>" target="_blank" class="btn btn-outline-info btn-sm">
                            <i class="bi bi-file-earmark-text me-2"></i>Voir le CV
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($student['skills']): ?>
                    <div class="mb-3">
                        <h6 class="fw-bold">Compétences</h6>
                        <p><?php echo nl2br(h($student['skills'])); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Informations supplémentaires -->
        <div class="col-md-8">
            <!-- Affectation et stage -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Affectation de stage</h5>
                </div>
                <div class="card-body">
                    <?php if ($assignment): ?>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <h6 class="fw-bold">Stage</h6>
                            <p class="mb-1">
                                <strong>Titre:</strong> <?php echo h($assignment['internship_title']); ?>
                            </p>
                            <p class="mb-1">
                                <strong>Entreprise:</strong> <?php echo h($assignment['company_name']); ?>
                            </p>
                            <p class="mb-1">
                                <strong>Statut:</strong>
                                <?php
                                $assignmentStatusBadge = [
                                    'pending' => '<span class="badge bg-warning">En attente</span>',
                                    'confirmed' => '<span class="badge bg-success">Confirmé</span>',
                                    'rejected' => '<span class="badge bg-danger">Rejeté</span>',
                                    'completed' => '<span class="badge bg-info">Terminé</span>'
                                ];
                                echo $assignmentStatusBadge[$assignment['status']] ?? '<span class="badge bg-secondary">Inconnu</span>';
                                ?>
                            </p>
                            <p class="mb-1">
                                <strong>Score de compatibilité:</strong>
                                <div class="progress" style="height: 10px;" title="<?php echo $assignment['compatibility_score']; ?>/10">
                                    <div class="progress-bar" role="progressbar" style="width: <?php echo $assignment['compatibility_score'] * 10; ?>%" 
                                        aria-valuenow="<?php echo $assignment['compatibility_score']; ?>" aria-valuemin="0" aria-valuemax="10"></div>
                                </div>
                            </p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h6 class="fw-bold">Tuteur</h6>
                            <p class="mb-1">
                                <strong>Nom:</strong> <?php echo h($assignment['teacher_first_name'] . ' ' . $assignment['teacher_last_name']); ?>
                            </p>
                            <p class="mb-1">
                                <strong>Date d'affectation:</strong> <?php echo formatDate($assignment['assignment_date']); ?>
                            </p>
                            <?php if ($assignment['confirmation_date']): ?>
                            <p class="mb-1">
                                <strong>Date de confirmation:</strong> <?php echo formatDate($assignment['confirmation_date']); ?>
                            </p>
                            <?php endif; ?>
                            <?php if ($assignment['notes']): ?>
                            <p class="mb-1">
                                <strong>Notes:</strong> <?php echo nl2br(h($assignment['notes'])); ?>
                            </p>
                            <?php endif; ?>
                        </div>
                        <div class="col-12">
                            <a href="/tutoring/views/admin/assignments/show.php?id=<?php echo $assignment['id']; ?>" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-eye me-2"></i>Voir les détails de l'affectation
                            </a>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>Cet étudiant n'a pas encore d'affectation de stage.
                        <?php if (hasRole(['admin', 'coordinator'])): ?>
                        <a href="/tutoring/views/admin/assignments/create.php?student_id=<?php echo $student['id']; ?>" class="btn btn-sm btn-primary ms-3">
                            <i class="bi bi-plus-circle me-2"></i>Créer une affectation
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Préférences de stage -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Préférences de stage</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($preferences)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>Cet étudiant n'a pas encore exprimé de préférences de stage.
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Ordre</th>
                                    <th>Stage</th>
                                    <th>Entreprise</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($preferences as $preference): ?>
                                <tr>
                                    <td><span class="badge bg-primary"><?php echo h($preference['preference_order']); ?></span></td>
                                    <td><?php echo h($preference['title']); ?></td>
                                    <td><?php echo h($preference['company_name']); ?></td>
                                    <td>
                                        <a href="/tutoring/views/admin/internships/show.php?id=<?php echo $preference['internship_id']; ?>" class="btn btn-sm btn-outline-info">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Documents et réunions -->
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Documents</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($documents)): ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>Aucun document trouvé.
                            </div>
                            <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($documents as $document): ?>
                                <div class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?php echo h($document['title']); ?></h6>
                                        <small class="text-muted"><?php echo formatDate($document['upload_date']); ?></small>
                                    </div>
                                    <p class="mb-1">
                                        <?php
                                        $typeBadge = [
                                            'contract' => '<span class="badge bg-primary">Contrat</span>',
                                            'report' => '<span class="badge bg-info">Rapport</span>',
                                            'evaluation' => '<span class="badge bg-warning">Évaluation</span>',
                                            'certificate' => '<span class="badge bg-success">Certificat</span>',
                                            'other' => '<span class="badge bg-secondary">Autre</span>'
                                        ];
                                        echo $typeBadge[$document['type']] ?? '<span class="badge bg-secondary">Autre</span>';
                                        
                                        $statusBadge = [
                                            'draft' => '<span class="badge bg-secondary">Brouillon</span>',
                                            'submitted' => '<span class="badge bg-primary">Soumis</span>',
                                            'approved' => '<span class="badge bg-success">Approuvé</span>',
                                            'rejected' => '<span class="badge bg-danger">Rejeté</span>'
                                        ];
                                        echo ' ' . ($statusBadge[$document['status']] ?? '');
                                        ?>
                                    </p>
                                    <div class="d-flex justify-content-end">
                                        <a href="<?php echo h($document['file_path']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-file-earmark-text me-2"></i>Voir
                                        </a>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Réunions</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($meetings)): ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>Aucune réunion programmée.
                            </div>
                            <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($meetings as $meeting): ?>
                                <div class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?php echo h($meeting['title']); ?></h6>
                                        <small class="text-muted"><?php echo formatDate($meeting['date_time'], 'd/m/Y H:i'); ?></small>
                                    </div>
                                    <p class="mb-1">
                                        <small>Organisateur: <?php echo h($meeting['organizer_first_name'] . ' ' . $meeting['organizer_last_name']); ?></small>
                                    </p>
                                    <p class="mb-1">
                                        <?php
                                        $statusBadge = [
                                            'scheduled' => '<span class="badge bg-primary">Programmée</span>',
                                            'completed' => '<span class="badge bg-success">Terminée</span>',
                                            'cancelled' => '<span class="badge bg-danger">Annulée</span>'
                                        ];
                                        echo $statusBadge[$meeting['status']] ?? '<span class="badge bg-secondary">Inconnue</span>';
                                        
                                        $participantStatusBadge = [
                                            'invited' => '<span class="badge bg-warning">Invité</span>',
                                            'confirmed' => '<span class="badge bg-success">Confirmé</span>',
                                            'declined' => '<span class="badge bg-danger">Refusé</span>',
                                            'attended' => '<span class="badge bg-info">Présent</span>'
                                        ];
                                        echo ' ' . ($participantStatusBadge[$meeting['participant_status']] ?? '');
                                        ?>
                                    </p>
                                    <div class="d-flex justify-content-end">
                                        <a href="/tutoring/views/common/meetings/show.php?id=<?php echo $meeting['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-info-circle me-2"></i>Détails
                                        </a>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../common/footer.php'; ?>