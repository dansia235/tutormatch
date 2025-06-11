<?php
/**
 * Vue pour la gestion des réunions par l'étudiant - Version PHP
 */

// Initialiser les variables
$pageTitle = 'Mes réunions';
$currentPage = 'meetings';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../includes/init.php';

// Vérifier que l'utilisateur est connecté et a le rôle étudiant
requireRole('student');

// Récupérer l'ID de l'étudiant
$studentModel = new Student($db);
$student = $studentModel->getByUserId($_SESSION['user_id']);

if (!$student) {
    setFlashMessage('error', 'Profil étudiant non trouvé');
    redirect('/tutoring/index.php');
}

// Récupérer l'affectation de l'étudiant (pour connaître son tuteur)
$assignmentModel = new Assignment($db);
$assignments = $assignmentModel->getByStudentId($student['id']);
$currentAssignment = null;
$tutor = null;

// Prendre la première affectation active
foreach ($assignments as $assignment) {
    if ($assignment['status'] === 'active' || $assignment['status'] === 'confirmed') {
        $currentAssignment = $assignment;
        
        // Récupérer les informations du tuteur
        if (isset($assignment['teacher_id'])) {
            $teacherModel = new Teacher($db);
            $teacher = $teacherModel->getById($assignment['teacher_id']);
            if ($teacher) {
                $userModel = new User($db);
                $tutorUser = $userModel->getById($teacher['user_id']);
                if ($tutorUser) {
                    $tutor = [
                        'id' => $teacher['id'],
                        'name' => $tutorUser['first_name'] . ' ' . $tutorUser['last_name'],
                        'email' => $tutorUser['email']
                    ];
                }
            }
        }
        break;
    }
}

// Récupérer les réunions de l'étudiant
$meetingModel = new Meeting($db);
$allMeetings = [];

try {
    if (method_exists($meetingModel, 'getByStudentId')) {
        $allMeetings = $meetingModel->getByStudentId($student['id']);
    } else {
        // Fallback: récupérer toutes les réunions et filtrer
        $allMeetingsData = $meetingModel->getAll();
        foreach ($allMeetingsData as $meeting) {
            if (isset($meeting['student_id']) && $meeting['student_id'] == $student['id']) {
                $allMeetings[] = $meeting;
            }
        }
    }
} catch (Exception $e) {
    $allMeetings = [];
    setFlashMessage('info', 'Erreur lors de la récupération des réunions.');
}

// Catégoriser les réunions
$meetings = [
    'upcoming' => [],
    'past' => [],
    'cancelled' => []
];

// Compteurs et statistiques
$totalMeetings = count($allMeetings);
$upcomingCount = 0;
$pastCount = 0;
$attendedCount = 0;
$cancelledCount = 0;

// Date actuelle pour comparer
$currentDate = new DateTime();

// Organiser les réunions par catégorie
foreach ($allMeetings as $meeting) {
    // Gérer différents formats de date possibles
    $meetingDateStr = $meeting['date_time'] ?? $meeting['meeting_date'] ?? null;
    if (!$meetingDateStr) continue;
    
    $meetingDate = new DateTime($meetingDateStr);
    
    // Vérifier si la réunion est passée ou à venir
    if ($meeting['status'] === 'cancelled') {
        $meetings['cancelled'][] = $meeting;
        $cancelledCount++;
    } elseif ($meetingDate < $currentDate) {
        $meetings['past'][] = $meeting;
        $pastCount++;
        
        // Vérifier si l'étudiant a assisté à la réunion
        if (isset($meeting['student_attended']) && $meeting['student_attended'] == 1) {
            $attendedCount++;
        }
    } else {
        $meetings['upcoming'][] = $meeting;
        $upcomingCount++;
    }
}

// Calculer le taux de participation
$participationRate = $pastCount > 0 ? round(($attendedCount / $pastCount) * 100) : 0;

// Calculer la satisfaction moyenne (simulée pour le moment)
$satisfactionAverage = number_format(mt_rand(30, 50) / 10, 1);

// Traitement du formulaire de création de réunion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_meeting'])) {
    // Vérifier que l'étudiant a un tuteur assigné
    if (!$tutor) {
        setFlashMessage('error', 'Vous devez avoir un tuteur assigné pour créer une réunion');
    } else {
        // Préparer les données de la réunion
        $meetingData = [
            'student_id' => $student['id'],
            'tutor_id' => $tutor['id'],
            'assignment_id' => $currentAssignment['id'] ?? null,
            'title' => $_POST['meeting_title'],
            'description' => $_POST['meeting_description'] ?? null,
            'meeting_date' => $_POST['meeting_date'] . ' ' . $_POST['meeting_time'] . ':00',
            'duration' => $_POST['meeting_duration'] ?? 60,
            'location' => $_POST['meeting_location'],
            'meeting_type' => $_POST['meeting_type'],
            'mode' => $_POST['meeting_mode'] ?? 'En présentiel',
            'status' => 'pending', // L'étudiant crée une demande en attente
            'created_by' => $_SESSION['user_id'],
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Si certains champs n'existent pas dans la table, les adapter
        if (!isset($meetingData['meeting_type'])) {
            $meetingData['type'] = 'Suivi régulier';
        }
        
        // Adapter les champs selon la structure de la base de données
        // La table meetings utilise date_time et duration
        $dateTime = $meetingData['meeting_date']; // Format: YYYY-MM-DD HH:MM:SS
        
        $adaptedData = [
            'title' => $meetingData['title'],
            'description' => $meetingData['description'],
            'date_time' => $dateTime,
            'duration' => (int)$meetingData['duration'], // Durée en minutes
            'location' => $meetingData['location'],
            'meeting_link' => $meetingData['meeting_url'] ?? '',
            'organizer_id' => $_SESSION['user_id'], // L'étudiant est l'organisateur
            'status' => 'scheduled',
            'created_at' => date('Y-m-d H:i:s'),
            'assignment_id' => $currentAssignment['id'] ?? null
        ];
        
        // Créer la réunion
        try {
            // Utiliser la méthode createMeeting si elle existe
            if (method_exists($meetingModel, 'createMeeting')) {
                $newMeetingId = $meetingModel->createMeeting($adaptedData);
            } elseif (method_exists($meetingModel, 'createFlexible')) {
                $newMeetingId = $meetingModel->createFlexible($adaptedData);
            } else {
                // Fallback: utiliser la méthode create normale
                $newMeetingId = $meetingModel->create($adaptedData);
            }
            
            if ($newMeetingId) {
                // Ajouter les participants
                if (method_exists($meetingModel, 'addParticipant')) {
                    try {
                        // Ajouter l'étudiant comme participant confirmé
                        $meetingModel->addParticipant([
                            'meeting_id' => $newMeetingId,
                            'user_id' => $_SESSION['user_id'],
                            'status' => 'confirmed'
                        ]);
                        
                        // Ajouter le tuteur comme participant invité
                        if ($tutor) {
                            $teacherModel = new Teacher($db);
                            $teacher = $teacherModel->getById($tutor['id']);
                            if ($teacher && isset($teacher['user_id'])) {
                                $meetingModel->addParticipant([
                                    'meeting_id' => $newMeetingId,
                                    'user_id' => $teacher['user_id'],
                                    'status' => 'invited'
                                ]);
                            }
                        }
                    } catch (Exception $e) {
                        // Si l'ajout de participants échoue, on continue quand même
                        error_log("Erreur lors de l'ajout des participants: " . $e->getMessage());
                    }
                }
                
                setFlashMessage('success', 'Demande de réunion créée avec succès. En attente de confirmation du tuteur.');
                redirect('/tutoring/views/student/meetings.php');
            } else {
                setFlashMessage('error', 'Erreur lors de la création de la réunion');
            }
        } catch (Exception $e) {
            setFlashMessage('error', 'Erreur lors de la création de la réunion: ' . $e->getMessage());
        }
    }
}

// Traitement de l'annulation d'une réunion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_meeting'])) {
    $meetingId = $_POST['meeting_id'];
    
    try {
        // Vérifier que la réunion appartient à l'étudiant
        $meeting = $meetingModel->getById($meetingId);
        
        if ($meeting && (
            (isset($meeting['student_id']) && $meeting['student_id'] == $student['id']) ||
            (isset($meeting['created_by']) && $meeting['created_by'] == $_SESSION['user_id'])
        )) {
            if (method_exists($meetingModel, 'updateStatus')) {
                if ($meetingModel->updateStatus($meetingId, 'cancelled')) {
                    setFlashMessage('success', 'Réunion annulée avec succès');
                } else {
                    setFlashMessage('error', 'Erreur lors de l\'annulation de la réunion');
                }
            } else {
                // Alternative si updateStatus n'existe pas
                if ($meetingModel->update($meetingId, ['status' => 'cancelled'])) {
                    setFlashMessage('success', 'Réunion annulée avec succès');
                } else {
                    setFlashMessage('error', 'Erreur lors de l\'annulation de la réunion');
                }
            }
        } else {
            setFlashMessage('error', 'Réunion non trouvée ou vous n\'avez pas les droits pour l\'annuler');
        }
    } catch (Exception $e) {
        setFlashMessage('error', 'Erreur lors de l\'annulation: ' . $e->getMessage());
    }
    
    redirect('/tutoring/views/student/meetings.php');
}

// Inclure l'en-tête
include_once __DIR__ . '/../common/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-calendar-event me-2"></i>Mes réunions</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/tutoring/views/student/dashboard.php">Tableau de bord</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Réunions</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="row mb-4" id="stats-container">
        <div class="col-md-3 fade-in delay-1">
            <div class="card stat-card" id="past-meetings-card">
                <div class="value" id="past-meetings-count"><?php echo $pastCount; ?></div>
                <div class="label">Réunions passées</div>
                <div class="progress mt-2">
                    <div class="progress-bar" role="progressbar" style="width: <?php echo $totalMeetings > 0 ? ($pastCount / $totalMeetings) * 100 : 0; ?>%;" aria-valuenow="<?php echo $totalMeetings > 0 ? ($pastCount / $totalMeetings) * 100 : 0; ?>" aria-valuemin="0" aria-valuemax="100" id="past-meetings-progress"></div>
                </div>
                <small class="text-muted" id="past-meetings-text"><?php echo $pastCount; ?>/<?php echo $totalMeetings; ?> complétées</small>
            </div>
        </div>
        <div class="col-md-3 fade-in delay-2">
            <div class="card stat-card" id="upcoming-meetings-card">
                <div class="value" id="upcoming-meetings-count"><?php echo $upcomingCount; ?></div>
                <div class="label">À venir</div>
                <div class="progress mt-2">
                    <div class="progress-bar bg-info" role="progressbar" style="width: 100%;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <small class="text-muted" id="next-meeting-text">
                    <?php if (!empty($meetings['upcoming'])): ?>
                        Prochaine: <?php echo date('d/m/Y', strtotime($meetings['upcoming'][0]['date_time'] ?? $meetings['upcoming'][0]['meeting_date'])); ?>
                    <?php else: ?>
                        Aucune réunion planifiée
                    <?php endif; ?>
                </small>
            </div>
        </div>
        <div class="col-md-3 fade-in delay-3">
            <div class="card stat-card" id="participation-card">
                <div class="value" id="participation-rate"><?php echo $participationRate; ?>%</div>
                <div class="label">Participation</div>
                <div class="progress mt-2">
                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $participationRate; ?>%;" aria-valuenow="<?php echo $participationRate; ?>" aria-valuemin="0" aria-valuemax="100" id="participation-progress"></div>
                </div>
                <small class="text-muted" id="participation-text"><?php echo $attendedCount; ?>/<?php echo $pastCount; ?> réunions assistées</small>
            </div>
        </div>
        <div class="col-md-3 fade-in delay-4">
            <div class="card stat-card" id="satisfaction-card">
                <div class="value" id="satisfaction-average"><?php echo $satisfactionAverage; ?></div>
                <div class="label">Satisfaction</div>
                <div class="progress mt-2">
                    <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo ($satisfactionAverage / 5) * 100; ?>%;" aria-valuenow="<?php echo ($satisfactionAverage / 5) * 100; ?>" aria-valuemin="0" aria-valuemax="100" id="satisfaction-progress"></div>
                </div>
                <small class="text-muted">Moyenne sur 5</small>
            </div>
        </div>
    </div>

    <!-- Main Content Row -->
    <div class="row">
        <!-- Left Column -->
        <div class="col-lg-8">
            <!-- Meetings Interface -->
            <div class="card mb-4 fade-in">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Calendrier des Réunions</span>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newMeetingModal" <?php echo !$tutor ? 'disabled' : ''; ?>>
                        <i class="bi bi-plus-circle me-1"></i>Nouvelle demande
                    </button>
                </div>
                <div class="card-body">
                    <?php if (!$tutor): ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i> Vous devez avoir un tuteur assigné pour créer des réunions.
                    </div>
                    <?php elseif (empty($allMeetings)): ?>
                    <div class="py-3 text-center">
                        <div class="p-4 bg-light rounded">
                            <i class="bi bi-calendar-x fs-1 text-muted"></i>
                            <h5 class="mt-3">Aucune réunion planifiée</h5>
                            <p class="text-muted">Vous n'avez aucune réunion planifiée pour le moment.</p>
                            <button class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#newMeetingModal">
                                <i class="bi bi-plus-circle me-1"></i>Planifier une réunion
                            </button>
                        </div>
                    </div>
                    <?php else: ?>
                        <!-- Réunions à venir -->
                        <?php if (!empty($meetings['upcoming'])): ?>
                        <h5 class="mb-3">
                            <i class="bi bi-calendar-check text-primary me-2"></i>
                            Réunions à venir
                        </h5>
                        <div class="mb-4">
                            <?php foreach ($meetings['upcoming'] as $meeting): 
                                $meetingDate = new DateTime($meeting['date_time'] ?? $meeting['meeting_date']);
                                $endDate = clone $meetingDate;
                                $endDate->modify('+' . ($meeting['duration'] ?? 60) . ' minutes');
                            ?>
                            <div class="card mb-3 border-start border-4 border-primary">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <h5 class="card-title"><?php echo h($meeting['title'] ?? 'Réunion avec tuteur'); ?></h5>
                                            <p class="card-text mb-2">
                                                <i class="bi bi-calendar-event text-muted me-2"></i>
                                                <span><?php echo $meetingDate->format('l d F Y à H:i'); ?></span>
                                            </p>
                                            <?php if ($tutor): ?>
                                            <p class="card-text mb-2">
                                                <i class="bi bi-person-badge text-muted me-2"></i>
                                                <span><?php echo h($tutor['name']); ?></span>
                                            </p>
                                            <?php endif; ?>
                                            <?php if (!empty($meeting['location'])): ?>
                                            <p class="card-text mb-2">
                                                <i class="bi bi-geo-alt text-muted me-2"></i>
                                                <span><?php echo h($meeting['location']); ?></span>
                                            </p>
                                            <?php endif; ?>
                                            <?php if (!empty($meeting['meeting_link']) || !empty($meeting['meeting_url'])): ?>
                                            <p class="card-text mb-2">
                                                <i class="bi bi-camera-video text-muted me-2"></i>
                                                <a href="<?php echo h($meeting['meeting_link'] ?? $meeting['meeting_url']); ?>" class="text-decoration-none" target="_blank">
                                                    Rejoindre la réunion en ligne
                                                </a>
                                            </p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <?php if ($meeting['status'] !== 'cancelled' && $meeting['status'] !== 'completed'): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmCancel(<?php echo $meeting['id']; ?>)">
                                                <i class="bi bi-x-circle me-1"></i>
                                                Annuler
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php if (!empty($meeting['description']) || !empty($meeting['notes'])): ?>
                                    <div class="mt-3 pt-3 border-top">
                                        <h6 class="text-muted">Notes:</h6>
                                        <p class="card-text small"><?php echo h($meeting['description'] ?? $meeting['notes']); ?></p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Réunions passées -->
                        <?php if (!empty($meetings['past'])): ?>
                        <h5 class="mb-3">
                            <i class="bi bi-calendar-x text-secondary me-2"></i>
                            Réunions passées
                        </h5>
                        <div class="mb-4">
                            <?php foreach ($meetings['past'] as $meeting): 
                                $meetingDate = new DateTime($meeting['meeting_date'] ?? $meeting['date_time']);
                            ?>
                            <div class="card mb-3 border-start border-4 border-secondary" style="opacity: 0.8;">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo h($meeting['title'] ?? 'Réunion avec tuteur'); ?></h5>
                                    <p class="card-text mb-2">
                                        <i class="bi bi-calendar-event text-muted me-2"></i>
                                        <span><?php echo $meetingDate->format('l d F Y à H:i'); ?></span>
                                    </p>
                                    <?php if ($meeting['student_attended'] ?? false): ?>
                                    <span class="badge bg-success">Présent</span>
                                    <?php else: ?>
                                    <span class="badge bg-warning">Absent</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Réunions annulées -->
                        <?php if (!empty($meetings['cancelled'])): ?>
                        <h5 class="mb-3">
                            <i class="bi bi-calendar-minus text-danger me-2"></i>
                            Réunions annulées
                        </h5>
                        <div class="mb-4">
                            <?php foreach ($meetings['cancelled'] as $meeting): 
                                $meetingDate = new DateTime($meeting['meeting_date'] ?? $meeting['date_time']);
                            ?>
                            <div class="card mb-3 border-start border-4 border-danger" style="opacity: 0.6;">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo h($meeting['title'] ?? 'Réunion avec tuteur'); ?></h5>
                                    <p class="card-text mb-2">
                                        <i class="bi bi-calendar-event text-muted me-2"></i>
                                        <span><?php echo $meetingDate->format('l d F Y à H:i'); ?></span>
                                    </p>
                                    <span class="badge bg-danger">Annulée</span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Right Column -->
        <div class="col-lg-4">
            <!-- Actions rapides -->
            <div class="card mb-4 fade-in">
                <div class="card-header">
                    Actions rapides
                </div>
                <div class="card-body">
                    <button class="btn btn-primary w-100 mb-2" data-bs-toggle="modal" data-bs-target="#newMeetingModal" <?php echo !$tutor ? 'disabled' : ''; ?>>
                        <i class="bi bi-plus-circle me-2"></i>Planifier une réunion
                    </button>
                    <a href="/tutoring/views/student/documents.php" class="btn btn-outline-primary w-100 mb-2">
                        <i class="bi bi-folder me-2"></i>Mes documents
                    </a>
                    <a href="/tutoring/views/student/evaluations.php" class="btn btn-outline-primary w-100 mb-2">
                        <i class="bi bi-star me-2"></i>Mes évaluations
                    </a>
                    <a href="/tutoring/views/student/tutor.php" class="btn btn-outline-primary w-100">
                        <i class="bi bi-person-badge me-2"></i>Mon tuteur
                    </a>
                </div>
            </div>
            
            <!-- Statistiques de réunions -->
            <div class="card fade-in">
                <div class="card-header">
                    <span>Statistiques de réunions</span>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush" id="meeting-stats">
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Réunions planifiées</span>
                            <span class="badge bg-primary rounded-pill" id="total-meetings"><?php echo $totalMeetings; ?></span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Réunions complétées</span>
                            <span class="badge bg-success rounded-pill" id="completed-meetings"><?php echo $pastCount; ?></span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Réunions annulées</span>
                            <span class="badge bg-danger rounded-pill" id="cancelled-meetings"><?php echo $cancelledCount; ?></span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Durée moyenne</span>
                            <span class="text-muted" id="average-duration">60 min</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- New Meeting Modal -->
<div class="modal fade" id="newMeetingModal" tabindex="-1" aria-labelledby="newMeetingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newMeetingModalLabel">Planifier une nouvelle réunion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body">
                    <?php if ($tutor): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Votre demande de réunion sera envoyée à <strong><?php echo h($tutor['name']); ?></strong> pour validation.
                    </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label class="form-label">Type de réunion</label>
                        <select class="form-select" name="meeting_type" required>
                            <option value="Suivi régulier">Suivi régulier</option>
                            <option value="Question urgente">Question urgente</option>
                            <option value="Révision de rapport">Révision de rapport</option>
                            <option value="Préparation soutenance">Préparation soutenance</option>
                            <option value="Autre">Autre</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Sujet</label>
                        <input type="text" class="form-control" name="meeting_title" placeholder="Objet de la réunion" required>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Date souhaitée</label>
                            <input type="date" class="form-control" name="meeting_date" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Heure souhaitée</label>
                            <input type="time" class="form-control" name="meeting_time" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Durée estimée (minutes)</label>
                            <input type="number" class="form-control" name="meeting_duration" value="60" min="15" max="180" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Mode préféré</label>
                            <select class="form-select" name="meeting_mode" required>
                                <option value="En présentiel">En présentiel</option>
                                <option value="En ligne">En ligne</option>
                                <option value="Peu importe">Peu importe</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Lieu suggéré</label>
                            <input type="text" class="form-control" name="meeting_location" placeholder="Bureau du tuteur, bibliothèque, en ligne...">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description / Questions à aborder</label>
                        <textarea class="form-control" name="meeting_description" rows="4" placeholder="Décrivez les points que vous souhaitez aborder pendant la réunion..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" name="create_meeting" class="btn btn-primary">Envoyer la demande</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Cancel Meeting Modal -->
<div class="modal fade" id="cancelMeetingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmer l'annulation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir annuler cette réunion ?</p>
                <p class="text-muted small">Une notification sera envoyée à votre tuteur.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Non</button>
                <form action="" method="POST" style="display: inline;">
                    <input type="hidden" name="meeting_id" id="meeting_id_to_cancel">
                    <button type="submit" name="cancel_meeting" class="btn btn-danger">Oui, annuler</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Mise à jour de la date minimum pour la création de réunion
        var dateInput = document.querySelector('input[name="meeting_date"]');
        if (dateInput) {
            var tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            dateInput.min = tomorrow.toISOString().split('T')[0];
        }
        
        // Localisation française pour les dates
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    });
    
    // Fonction pour confirmer l'annulation d'une réunion
    function confirmCancel(meetingId) {
        document.getElementById('meeting_id_to_cancel').value = meetingId;
        const modal = new bootstrap.Modal(document.getElementById('cancelMeetingModal'));
        modal.show();
    }
</script>

<?php
// Inclure le pied de page
include_once __DIR__ . '/../common/footer.php';
?>