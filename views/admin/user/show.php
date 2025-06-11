<?php
/**
 * Vue pour afficher les détails d'un utilisateur
 */

// Initialiser les variables
$pageTitle = 'Détails de l\'utilisateur';
$currentPage = 'users';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../../includes/init.php';

// Vérifier les permissions
requireRole(['admin', 'coordinator']);

// Vérifier si l'ID est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setFlashMessage('error', 'ID utilisateur non spécifié');
    redirect('/tutoring/views/admin/users.php');
}

$userId = $_GET['id'];

// Instancier le modèle utilisateur
$userModel = new User($db);

// Récupérer les données de l'utilisateur
$user = $userModel->getById($userId);

// Vérifier si l'utilisateur existe
if (!$user) {
    setFlashMessage('error', 'Utilisateur non trouvé');
    redirect('/tutoring/views/admin/users.php');
}

// Récupérer les informations spécifiques au rôle
$studentInfo = null;
$teacherInfo = null;
$assignments = [];

if ($user['role'] === 'student') {
    // Instancier le modèle étudiant
    $studentModel = new Student($db);
    $studentInfo = $studentModel->getByUserId($userId);
    
    // Récupérer les affectations de l'étudiant
    $assignmentModel = new Assignment($db);
    $assignments = $assignmentModel->getByStudentId($studentInfo['id'] ?? 0);
} elseif ($user['role'] === 'teacher') {
    // Instancier le modèle tuteur
    $teacherModel = new Teacher($db);
    $teacherInfo = $teacherModel->getByUserId($userId);
    
    // Récupérer les affectations du tuteur
    $assignmentModel = new Assignment($db);
    $assignments = $assignmentModel->getByTeacherId($teacherInfo['id'] ?? 0);
}

// Récupérer les documents de l'utilisateur
$documentModel = new Document($db);
$documents = $documentModel->getByUserId($userId);

// Inclure l'en-tête
include_once __DIR__ . '/../../common/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-lg-9 mx-auto">
            <!-- En-tête de page -->
            <div class="d-flex align-items-center mb-4">
                <a href="/tutoring/views/admin/users.php" class="btn btn-outline-secondary me-3">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h2 class="mb-0"><i class="bi bi-person me-2"></i>Profil de l'utilisateur</h2>
                
                <div class="ms-auto">
                    <?php if (hasRole(['admin']) || (hasRole(['coordinator']) && $user['role'] !== 'admin')): ?>
                    <a href="/tutoring/views/admin/user/edit.php?id=<?php echo $user['id']; ?>" class="btn btn-primary">
                        <i class="bi bi-pencil me-2"></i>Modifier
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Détails de l'utilisateur -->
            <div class="row mb-4">
                <!-- Informations générales -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body text-center p-4">
                            <?php if (!empty($user['profile_image'])): ?>
                            <img src="<?php echo h($user['profile_image']); ?>" alt="Photo de profil" class="img-thumbnail rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                            <?php else: ?>
                            <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center text-white mb-3" style="width: 150px; height: 150px; margin: 0 auto; font-size: 3rem;">
                                <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                            </div>
                            <?php endif; ?>
                            
                            <h4 class="card-title mb-1"><?php echo h($user['first_name'] . ' ' . $user['last_name']); ?></h4>
                            <p class="text-muted mb-2"><?php echo h($user['username']); ?></p>
                            
                            <?php
                            $roleBadges = [
                                'admin' => '<span class="badge bg-danger">Administrateur</span>',
                                'coordinator' => '<span class="badge bg-purple">Coordinateur</span>',
                                'teacher' => '<span class="badge bg-info">Tuteur</span>',
                                'student' => '<span class="badge bg-success">Étudiant</span>'
                            ];
                            echo $roleBadges[$user['role']] ?? '<span class="badge bg-secondary">' . h($user['role']) . '</span>';
                            ?>
                            
                            <hr class="my-3">
                            
                            <div class="text-start">
                                <div class="mb-2">
                                    <i class="bi bi-envelope me-2 text-muted"></i>
                                    <a href="mailto:<?php echo h($user['email']); ?>"><?php echo h($user['email']); ?></a>
                                </div>
                                
                                <?php if (!empty($user['phone'])): ?>
                                <div class="mb-2">
                                    <i class="bi bi-telephone me-2 text-muted"></i>
                                    <a href="tel:<?php echo h($user['phone']); ?>"><?php echo h($user['phone']); ?></a>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($user['department'])): ?>
                                <div class="mb-2">
                                    <i class="bi bi-building me-2 text-muted"></i>
                                    <?php echo h($user['department']); ?>
                                </div>
                                <?php endif; ?>
                                
                                <div class="mb-2">
                                    <i class="bi bi-calendar me-2 text-muted"></i>
                                    Créé le <?php echo date('d/m/Y', strtotime($user['created_at'])); ?>
                                </div>
                                
                                <?php if (!empty($user['last_login'])): ?>
                                <div class="mb-2">
                                    <i class="bi bi-clock-history me-2 text-muted"></i>
                                    Dernière connexion le <?php echo date('d/m/Y à H:i', strtotime($user['last_login'])); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Informations spécifiques au rôle -->
                <div class="col-md-8">
                    <?php if ($user['role'] === 'student' && $studentInfo): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><i class="bi bi-mortarboard me-2"></i>Informations de l'étudiant</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted">Programme d'études</label>
                                    <div class="fw-bold"><?php echo h($studentInfo['program'] ?? 'Non spécifié'); ?></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted">Année d'études</label>
                                    <div class="fw-bold"><?php echo h($studentInfo['year'] ?? 'Non spécifié'); ?><?php echo !empty($studentInfo['year']) ? '<sup>ème</sup> année' : ''; ?></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted">Nombre d'affectations</label>
                                    <div class="fw-bold"><?php echo count($assignments); ?></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted">Statut</label>
                                    <div class="fw-bold">
                                        <?php 
                                        $statusBadge = match($studentInfo['status'] ?? 'active') {
                                            'active' => '<span class="badge bg-success">Actif</span>',
                                            'graduated' => '<span class="badge bg-info">Diplômé</span>',
                                            'suspended' => '<span class="badge bg-warning">Suspendu</span>',
                                            'inactive' => '<span class="badge bg-secondary">Inactif</span>',
                                            default => '<span class="badge bg-secondary">Non spécifié</span>'
                                        };
                                        echo $statusBadge;
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php elseif ($user['role'] === 'teacher' && $teacherInfo): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><i class="bi bi-person-workspace me-2"></i>Informations du tuteur</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted">Spécialité</label>
                                    <div class="fw-bold"><?php echo h($teacherInfo['specialty'] ?? 'Non spécifié'); ?></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted">Nombre maximum d'étudiants</label>
                                    <div class="fw-bold"><?php echo h($teacherInfo['max_students'] ?? '5'); ?></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted">Étudiants actuels</label>
                                    <div class="fw-bold"><?php echo count($assignments); ?> / <?php echo h($teacherInfo['max_students'] ?? '5'); ?></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted">Statut</label>
                                    <div class="fw-bold">
                                        <?php 
                                        $statusBadge = match($teacherInfo['status'] ?? 'active') {
                                            'active' => '<span class="badge bg-success">Actif</span>',
                                            'on_leave' => '<span class="badge bg-warning">En congé</span>',
                                            'inactive' => '<span class="badge bg-secondary">Inactif</span>',
                                            default => '<span class="badge bg-secondary">Non spécifié</span>'
                                        };
                                        echo $statusBadge;
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Affectations -->
                    <?php if (!empty($assignments) && in_array($user['role'], ['student', 'teacher'])): ?>
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0"><i class="bi bi-diagram-3 me-2"></i>Affectations</h5>
                            <span class="badge bg-primary"><?php echo count($assignments); ?></span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <?php if ($user['role'] === 'student'): ?>
                                            <th>Tuteur</th>
                                            <?php else: ?>
                                            <th>Étudiant</th>
                                            <?php endif; ?>
                                            <th>Stage</th>
                                            <th>Date</th>
                                            <th>Statut</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($assignments as $assignment): ?>
                                        <tr>
                                            <?php if ($user['role'] === 'student'): ?>
                                            <td><?php echo h($assignment['teacher_first_name'] . ' ' . $assignment['teacher_last_name']); ?></td>
                                            <?php else: ?>
                                            <td><?php echo h($assignment['student_first_name'] . ' ' . $assignment['student_last_name']); ?></td>
                                            <?php endif; ?>
                                            <td><?php echo h($assignment['internship_title']); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($assignment['assignment_date'])); ?></td>
                                            <td>
                                                <?php
                                                $statusBadges = [
                                                    'pending' => '<span class="badge bg-warning">En attente</span>',
                                                    'confirmed' => '<span class="badge bg-success">Confirmée</span>',
                                                    'rejected' => '<span class="badge bg-danger">Rejetée</span>',
                                                    'completed' => '<span class="badge bg-info">Terminée</span>'
                                                ];
                                                echo $statusBadges[$assignment['status']] ?? '<span class="badge bg-secondary">' . h($assignment['status']) . '</span>';
                                                ?>
                                            </td>
                                            <td>
                                                <a href="/tutoring/views/admin/assignments/show.php?id=<?php echo $assignment['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Documents -->
                    <?php if (!empty($documents)): ?>
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0"><i class="bi bi-file-earmark-text me-2"></i>Documents</h5>
                            <span class="badge bg-primary"><?php echo count($documents); ?></span>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                <?php foreach ($documents as $document): ?>
                                <a href="/tutoring/views/admin/documents/show.php?id=<?php echo $document['id']; ?>" class="list-group-item list-group-item-action d-flex align-items-center">
                                    <?php
                                    // Icône selon le type de document
                                    $fileIcon = match ($document['type'] ?? 'other') {
                                        'report' => '<i class="bi bi-file-earmark-text text-danger me-3 fs-4"></i>',
                                        'cv' => '<i class="bi bi-file-earmark-person text-primary me-3 fs-4"></i>',
                                        'agreement' => '<i class="bi bi-file-earmark-check text-success me-3 fs-4"></i>',
                                        'evaluation' => '<i class="bi bi-file-earmark-bar-graph text-warning me-3 fs-4"></i>',
                                        'image' => '<i class="bi bi-file-earmark-image text-info me-3 fs-4"></i>',
                                        'presentation' => '<i class="bi bi-file-earmark-slides text-purple me-3 fs-4"></i>',
                                        default => '<i class="bi bi-file-earmark text-secondary me-3 fs-4"></i>'
                                    };
                                    echo $fileIcon;
                                    ?>
                                    <div class="flex-grow-1">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo h($document['title']); ?></h6>
                                            <small><?php echo date('d/m/Y', strtotime($document['upload_date'])); ?></small>
                                        </div>
                                        <small class="text-muted"><?php echo h($document['description'] ?? ''); ?></small>
                                    </div>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Inclure le pied de page
include_once __DIR__ . '/../../common/footer.php';
?>