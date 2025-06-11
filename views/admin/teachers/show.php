<?php
/**
 * Vue pour afficher les détails d'un enseignant (tuteur)
 */

// Initialiser les variables
$pageTitle = 'Détails du tuteur';
$currentPage = 'tutors';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../../includes/init.php';

// Vérifier les permissions
requireRole(['admin', 'coordinator']);

// Vérifier l'ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage('error', 'ID de tuteur invalide');
    redirect('/tutoring/views/admin/tutors.php');
}

// Instancier le contrôleur
$teacherController = new TeacherController($db);

// Récupérer les données du tuteur
$teacher = $teacherController->getTeacherDetails($_GET['id']);

if (!$teacher) {
    setFlashMessage('error', 'Enseignant non trouvé');
    redirect('/tutoring/views/admin/tutors.php');
    exit;
}

// Récupérer les données associées
$preferences = $teacherController->getTeacherPreferences($_GET['id']);
$assignments = $teacherController->getTeacherAssignments($_GET['id']);
$students = $teacherController->getTeacherStudents($_GET['id']);

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
                <i class="bi bi-person-badge me-2"></i>Profil tuteur
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/tutoring/views/admin/dashboard.php">Tableau de bord</a></li>
                    <li class="breadcrumb-item"><a href="/tutoring/views/admin/tutors.php">Tuteurs</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?php echo h($teacher['first_name'] . ' ' . $teacher['last_name']); ?></li>
                </ol>
            </nav>
        </div>
        
        <div class="btn-group" role="group">
            <a href="/tutoring/views/admin/teachers/edit.php?id=<?php echo $teacher['id']; ?>" class="btn btn-outline-primary">
                <i class="bi bi-pencil me-2"></i>Modifier
            </a>
            <a href="/tutoring/views/admin/tutors.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Retour
            </a>
        </div>
    </div>
    
    <div class="row">
        <!-- Profil du tuteur -->
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Informations personnelles</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <?php if ($teacher['profile_image']): ?>
                        <img src="<?php echo h($teacher['profile_image']); ?>" alt="Profile" class="rounded-circle mb-3" width="150" height="150">
                        <?php else: ?>
                        <div class="avatar-xl mx-auto mb-3">
                            <?php echo strtoupper(substr($teacher['first_name'], 0, 1) . substr($teacher['last_name'], 0, 1)); ?>
                        </div>
                        <?php endif; ?>
                        <h3 class="mb-0">
                            <?php echo h(($teacher['title'] ? $teacher['title'] . ' ' : '') . $teacher['first_name'] . ' ' . $teacher['last_name']); ?>
                        </h3>
                        <p class="text-muted"><?php echo h($teacher['email']); ?></p>
                        <?php if ($teacher['available']): ?>
                        <div class="badge bg-success">Disponible</div>
                        <?php else: ?>
                        <div class="badge bg-warning">Indisponible</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <h6 class="fw-bold">Compte utilisateur</h6>
                        <div class="row">
                            <div class="col-sm-6">
                                <p class="mb-0 text-muted">Nom d'utilisateur</p>
                                <p class="fw-bold"><?php echo h($teacher['username']); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <h6 class="fw-bold">Informations professionnelles</h6>
                        <div class="row">
                            <div class="col-sm-6">
                                <p class="mb-0 text-muted">Titre</p>
                                <p class="fw-bold"><?php echo h($teacher['title'] ?: 'N/A'); ?></p>
                            </div>
                            <div class="col-sm-6">
                                <p class="mb-0 text-muted">Département</p>
                                <p class="fw-bold"><?php echo h($teacher['department']); ?></p>
                            </div>
                            <div class="col-sm-6">
                                <p class="mb-0 text-muted">Spécialité</p>
                                <p class="fw-bold"><?php echo h(cleanSpecialty($teacher['specialty']) ?: 'Non spécifiée'); ?></p>
                            </div>
                            <div class="col-sm-6">
                                <p class="mb-0 text-muted">Bureau</p>
                                <p class="fw-bold"><?php echo h($teacher['office_location'] ?: 'N/A'); ?></p>
                            </div>
                            <div class="col-sm-6">
                                <p class="mb-0 text-muted">Capacité étudiants</p>
                                <p class="fw-bold"><?php echo h($teacher['max_students']); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($teacher['expertise']): ?>
                    <div class="mb-3">
                        <h6 class="fw-bold">Expertise</h6>
                        <p><?php echo nl2br(h($teacher['expertise'])); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Informations supplémentaires -->
        <div class="col-md-8">
            <!-- Statistiques des affectations -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Statistiques d'affectation</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <h6 class="fw-bold">Capacité et affectations</h6>
                            <?php
                            // Afficher le nombre d'étudiants actuels et le maximum
                            $currentCount = count($students);
                            $maxStudents = $teacher['max_students'];
                            $ratio = $maxStudents > 0 ? $currentCount / $maxStudents : 0;
                            
                            $badgeClass = 'bg-success';
                            if ($ratio >= 0.8) {
                                $badgeClass = 'bg-danger';
                            } elseif ($ratio >= 0.5) {
                                $badgeClass = 'bg-warning';
                            }
                            ?>
                            <div class="progress mb-2" style="height: 20px;">
                                <div class="progress-bar <?php echo $badgeClass; ?>" role="progressbar" style="width: <?php echo ($ratio * 100); ?>%" aria-valuenow="<?php echo $currentCount; ?>" aria-valuemin="0" aria-valuemax="<?php echo $maxStudents; ?>">
                                    <?php echo $currentCount; ?>/<?php echo $maxStudents; ?> étudiants
                                </div>
                            </div>
                            <p class="small text-muted">
                                <?php if ($ratio < 1): ?>
                                <i class="bi bi-info-circle me-1"></i>Ce tuteur peut encore prendre <?php echo $maxStudents - $currentCount; ?> étudiant(s).
                                <?php else: ?>
                                <i class="bi bi-exclamation-circle me-1"></i>Ce tuteur a atteint sa capacité maximale d'étudiants.
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h6 class="fw-bold">Statut des affectations</h6>
                            <?php
                            // Calculer les statistiques par statut
                            $statusCounts = [
                                'pending' => 0,
                                'confirmed' => 0,
                                'rejected' => 0,
                                'completed' => 0
                            ];
                            
                            foreach ($assignments as $assignment) {
                                if (isset($statusCounts[$assignment['status']])) {
                                    $statusCounts[$assignment['status']]++;
                                }
                            }
                            
                            $total = array_sum($statusCounts);
                            ?>
                            <?php if ($total > 0): ?>
                            <div class="progress mb-2" style="height: 20px;">
                                <?php if ($statusCounts['pending'] > 0): ?>
                                <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo ($statusCounts['pending'] / $total * 100); ?>%" aria-valuenow="<?php echo $statusCounts['pending']; ?>" aria-valuemin="0" aria-valuemax="<?php echo $total; ?>">
                                    <?php echo $statusCounts['pending']; ?> en attente
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($statusCounts['confirmed'] > 0): ?>
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo ($statusCounts['confirmed'] / $total * 100); ?>%" aria-valuenow="<?php echo $statusCounts['confirmed']; ?>" aria-valuemin="0" aria-valuemax="<?php echo $total; ?>">
                                    <?php echo $statusCounts['confirmed']; ?> confirmées
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($statusCounts['rejected'] > 0): ?>
                                <div class="progress-bar bg-danger" role="progressbar" style="width: <?php echo ($statusCounts['rejected'] / $total * 100); ?>%" aria-valuenow="<?php echo $statusCounts['rejected']; ?>" aria-valuemin="0" aria-valuemax="<?php echo $total; ?>">
                                    <?php echo $statusCounts['rejected']; ?> rejetées
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($statusCounts['completed'] > 0): ?>
                                <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo ($statusCounts['completed'] / $total * 100); ?>%" aria-valuenow="<?php echo $statusCounts['completed']; ?>" aria-valuemin="0" aria-valuemax="<?php echo $total; ?>">
                                    <?php echo $statusCounts['completed']; ?> terminées
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php else: ?>
                            <p class="text-muted">Aucune affectation pour le moment.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Préférences -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Préférences du tuteur</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($preferences)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>Ce tuteur n'a pas encore défini de préférences.
                    </div>
                    <?php else: ?>
                    <div class="row">
                        <?php
                        // Organiser les préférences par type
                        $preferencesByType = [];
                        $types = [
                            'DEPARTMENT' => 'Départements',
                            'LEVEL' => 'Niveaux d\'études',
                            'PROGRAM' => 'Programmes',
                            'DOMAIN' => 'Domaines',
                            'COMPANY' => 'Entreprises'
                        ];
                        
                        foreach ($preferences as $preference) {
                            $type = $preference['preference_type'];
                            if (!isset($preferencesByType[$type])) {
                                $preferencesByType[$type] = [];
                            }
                            $preferencesByType[$type][] = $preference;
                        }
                        ?>
                        
                        <?php foreach ($types as $type => $typeLabel): ?>
                        <?php if (isset($preferencesByType[$type])): ?>
                        <div class="col-md-6 mb-3">
                            <h6 class="fw-bold"><?php echo h($typeLabel); ?></h6>
                            <ul class="list-group">
                                <?php foreach ($preferencesByType[$type] as $preference): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?php echo h($preference['preference_value']); ?>
                                    <span class="badge bg-primary rounded-pill">Priorité: <?php echo h($preference['priority_value']); ?>/10</span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Liste des étudiants -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Étudiants encadrés</h5>
                    <?php if (count($students) < $teacher['max_students']): ?>
                    <a href="/tutoring/views/admin/assignments/create.php?teacher_id=<?php echo $teacher['id']; ?>" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>Affecter un étudiant
                    </a>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($students)): ?>
                    <div class="alert alert-info m-3">
                        <i class="bi bi-info-circle me-2"></i>Ce tuteur n'encadre actuellement aucun étudiant.
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Étudiant</th>
                                    <th>Stage</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if ($student['profile_image']): ?>
                                            <img src="<?php echo h($student['profile_image']); ?>" alt="Profile" class="rounded-circle me-2" width="32" height="32">
                                            <?php else: ?>
                                            <div class="avatar-sm me-2">
                                                <?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?>
                                            </div>
                                            <?php endif; ?>
                                            <div>
                                                <div class="fw-bold"><?php echo h($student['first_name'] . ' ' . $student['last_name']); ?></div>
                                                <div class="text-muted small"><?php echo h($student['program']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php echo h($student['internship_title']); ?><br>
                                        <small class="text-muted"><?php echo h($student['company_name']); ?></small>
                                    </td>
                                    <td>
                                        <?php
                                        $statusBadge = [
                                            'pending' => '<span class="badge bg-warning">En attente</span>',
                                            'confirmed' => '<span class="badge bg-success">Confirmé</span>',
                                            'rejected' => '<span class="badge bg-danger">Rejeté</span>',
                                            'completed' => '<span class="badge bg-info">Terminé</span>'
                                        ];
                                        echo $statusBadge[$student['assignment_status']] ?? '<span class="badge bg-secondary">Inconnu</span>';
                                        ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <?php if (isset($student['id'])): ?>
                                            <a href="/tutoring/views/admin/students/show.php?id=<?php echo $student['id']; ?>" title="Voir l'étudiant" class="btn btn-sm btn-outline-info">
                                                <span class="bi bi-person"></span>
                                            </a>
                                            <?php endif; ?>
                                            <?php if (isset($student['assignment_id'])): ?>
                                            <a href="/tutoring/views/admin/assignments/show.php?id=<?php echo $student['assignment_id']; ?>" title="Voir l'affectation" class="btn btn-sm btn-outline-primary">
                                                <span class="bi bi-diagram-3"></span>
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../common/footer.php'; ?>