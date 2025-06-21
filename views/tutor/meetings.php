<?php
/**
 * Vue pour la gestion des réunions par le tuteur
 */

// Initialiser les variables
$pageTitle = 'Gestion des réunions';
$currentPage = 'meetings';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../includes/init.php';

// Vérifier que l'utilisateur est connecté et a le rôle tuteur
requireRole('teacher');

// Récupérer l'ID du tuteur
$teacherModel = new Teacher($db);
$teacher = $teacherModel->getByUserId($_SESSION['user_id']);

if (!$teacher) {
    setFlashMessage('error', 'Profil tuteur non trouvé');
    redirect('/tutoring/index.php');
}

// Récupérer les étudiants assignés à ce tuteur
$assignments = $teacherModel->getAssignments($teacher['id']);
$studentIds = array_column($assignments, 'student_id');

// Récupérer les réunions du tuteur
$meetingModel = new Meeting($db);

// Correction: Utiliser une méthode alternative pour récupérer les réunions
$allMeetings = [];
try {
    // Essayer d'abord getByUserId (pour les réunions créées par le tuteur)
    if (method_exists($meetingModel, 'getByUserId')) {
        $userMeetings = $meetingModel->getByUserId($_SESSION['user_id']);
        $allMeetings = array_merge($allMeetings, $userMeetings);
    }
    
    // Récupérer aussi les réunions pour chaque étudiant assigné
    if (method_exists($meetingModel, 'getByStudentId')) {
        foreach ($studentIds as $studentId) {
            $studentMeetings = $meetingModel->getByStudentId($studentId);
            $allMeetings = array_merge($allMeetings, $studentMeetings);
        }
    }
    
    // Toujours utiliser getAll() et filtrer pour s'assurer de récupérer toutes les réunions
    if (method_exists($meetingModel, 'getAll')) {
        $allMeetingsData = $meetingModel->getAll();
        foreach ($allMeetingsData as $meeting) {
            // Inclure les réunions où le tuteur est impliqué
            if (isset($meeting['tutor_id']) && $meeting['tutor_id'] == $teacher['id']) {
                $allMeetings[] = $meeting;
            } elseif (isset($meeting['organizer_id']) && $meeting['organizer_id'] == $_SESSION['user_id']) {
                $allMeetings[] = $meeting;
            } elseif (isset($meeting['created_by']) && $meeting['created_by'] == $_SESSION['user_id']) {
                $allMeetings[] = $meeting;
            } elseif (isset($meeting['student_id']) && in_array($meeting['student_id'], $studentIds)) {
                $allMeetings[] = $meeting;
            } elseif (isset($meeting['assignment_id'])) {
                // Vérifier si l'assignment_id correspond à un des étudiants assignés
                foreach ($assignments as $assignment) {
                    if (isset($assignment['id']) && $assignment['id'] == $meeting['assignment_id']) {
                        $allMeetings[] = $meeting;
                        break;
                    }
                }
            }
        }
    }
    
    // Supprimer les doublons basés sur l'ID
    $uniqueMeetings = [];
    foreach ($allMeetings as $meeting) {
        if (!isset($uniqueMeetings[$meeting['id']])) {
            $uniqueMeetings[$meeting['id']] = $meeting;
        }
    }
    $allMeetings = array_values($uniqueMeetings);
    
} catch (Exception $e) {
    // En cas d'erreur, créer un tableau vide et un message d'information
    $allMeetings = [];
    setFlashMessage('info', 'Aucune réunion trouvée ou erreur de récupération des données.');
}

// Récupérer le filtre étudiant s'il existe
$studentFilter = $_GET['student_id'] ?? 'all';
$statusFilter = $_GET['status'] ?? 'all';
$dateRangeFilter = $_GET['date_range'] ?? 'all';

// Filtrer les réunions selon les critères
$filteredMeetings = [];
foreach ($allMeetings as $meeting) {
    // Déterminer l'ID de l'étudiant pour le filtrage
    $meetingStudentId = null;
    if (isset($meeting['student_id'])) {
        $meetingStudentId = $meeting['student_id'];
    } elseif (isset($meeting['assignment_id'])) {
        // Rechercher l'étudiant par l'ID d'affectation
        foreach ($assignments as $a) {
            if (isset($a['id']) && $a['id'] == $meeting['assignment_id']) {
                $meetingStudentId = $a['student_id'];
                break;
            }
        }
    }
    
    $matchesStudent = $studentFilter === 'all' || ($meetingStudentId && $meetingStudentId == $studentFilter);
    $matchesStatus = $statusFilter === 'all' || $meeting['status'] === $statusFilter;
    
    // Déterminer la date de la réunion pour le filtrage
    $meetingDate = null;
    try {
        if (isset($meeting['meeting_date'])) {
            $meetingDate = new DateTime($meeting['meeting_date']);
        } elseif (isset($meeting['date_time'])) {
            $meetingDate = new DateTime($meeting['date_time']);
        } elseif (isset($meeting['date'])) {
            if (isset($meeting['start_time'])) {
                $meetingDate = new DateTime($meeting['date'] . ' ' . $meeting['start_time']);
            } else {
                $meetingDate = new DateTime($meeting['date']);
            }
        }
    } catch (Exception $e) {
        // Si on ne peut pas créer la date, on considère que ça ne matche pas
        $meetingDate = null;
    }
    
    // Logique de filtrage par date
    $matchesDateRange = true;
    if ($dateRangeFilter !== 'all' && $meetingDate) {
        $today = new DateTime();
        
        switch ($dateRangeFilter) {
            case 'today':
                $matchesDateRange = $meetingDate->format('Y-m-d') === $today->format('Y-m-d');
                break;
            case 'week':
                $weekStart = (clone $today)->modify('this week monday');
                $weekEnd = (clone $today)->modify('this week sunday');
                $matchesDateRange = $meetingDate >= $weekStart && $meetingDate <= $weekEnd;
                break;
            case 'month':
                $matchesDateRange = $meetingDate->format('Y-m') === $today->format('Y-m');
                break;
            case 'future':
                $matchesDateRange = $meetingDate > $today;
                break;
            case 'past':
                $matchesDateRange = $meetingDate < $today;
                break;
        }
    }
    
    if ($matchesStudent && $matchesStatus && $matchesDateRange) {
        $filteredMeetings[] = $meeting;
    }
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
    // Déterminer la date de la réunion
    $meetingDate = null;
    try {
        if (isset($meeting['meeting_date'])) {
            $meetingDate = new DateTime($meeting['meeting_date']);
        } elseif (isset($meeting['date_time'])) {
            $meetingDate = new DateTime($meeting['date_time']);
        } elseif (isset($meeting['date'])) {
            if (isset($meeting['start_time'])) {
                $meetingDate = new DateTime($meeting['date'] . ' ' . $meeting['start_time']);
            } else {
                $meetingDate = new DateTime($meeting['date']);
            }
        }
    } catch (Exception $e) {
        // Si on ne peut pas créer la date, on utilise la date actuelle
        $meetingDate = clone $currentDate;
    }
    
    // Si pas de date, utiliser la date actuelle
    if (!$meetingDate) {
        $meetingDate = clone $currentDate;
    }
    
    // Vérifier si la réunion est passée ou à venir
    if ($meeting['status'] === 'cancelled') {
        $meetings['cancelled'][] = $meeting;
        $cancelledCount++;
    } elseif ($meetingDate < $currentDate) {
        $meetings['past'][] = $meeting;
        $pastCount++;
        
        // Vérifier si l'étudiant a assisté à la réunion
        if (isset($meeting['student_attended']) && $meeting['student_attended'] === 1) {
            $attendedCount++;
        }
    } else {
        $meetings['upcoming'][] = $meeting;
        $upcomingCount++;
    }
}

// Taux de participation
$participationRate = $pastCount > 0 ? round(($attendedCount / $pastCount) * 100) : 0;

// Traitement du formulaire de création de réunion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_meeting'])) {
    // Vérifier que l'étudiant est assigné à ce tuteur
    $studentId = $_POST['student_id'];
    $isAssigned = false;
    
    foreach ($assignments as $assignment) {
        if ($assignment['student_id'] == $studentId) {
            $isAssigned = true;
            break;
        }
    }
    
    if (!$isAssigned) {
        setFlashMessage('error', 'Cet étudiant n\'est pas assigné à votre tutorat');
    } else {
        // Récupérer l'assignment pour cet étudiant
        $assignment = null;
        foreach ($assignments as $a) {
            if ($a['student_id'] == $studentId) {
                $assignment = $a;
                break;
            }
        }
        
        // Préparer les données de la réunion
        $meetingData = [
            'title' => $_POST['meeting_title'],
            'description' => $_POST['meeting_description'] ?? null,
            'date_time' => $_POST['meeting_date'] . ' ' . $_POST['meeting_time'] . ':00',
            'duration' => $_POST['meeting_duration'],
            'location' => $_POST['meeting_location'],
            'meeting_link' => $_POST['meeting_mode'] === 'En ligne' ? $_POST['meeting_location'] : null,
            'assignment_id' => isset($assignment['id']) ? $assignment['id'] : null,
            'status' => 'scheduled', // Statut par défaut
            'organizer_id' => $_SESSION['user_id'],
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Créer la réunion
        try {
            // Essayer d'utiliser la nouvelle méthode createMeeting si disponible
            if (method_exists($meetingModel, 'createMeeting')) {
                if ($meetingModel->createMeeting($meetingData)) {
                    setFlashMessage('success', 'Réunion créée avec succès');
                    redirect('/tutoring/views/tutor/meetings.php');
                } else {
                    setFlashMessage('error', 'Erreur lors de la création de la réunion');
                }
            } else if ($meetingModel->create($meetingData)) {
                setFlashMessage('success', 'Réunion créée avec succès');
                redirect('/tutoring/views/tutor/meetings.php');
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
        // Vérifier que la réunion appartient au tuteur
        $meeting = $meetingModel->getById($meetingId);
        
        if ($meeting && (
            (isset($meeting['tutor_id']) && $meeting['tutor_id'] == $teacher['id']) ||
            (isset($meeting['created_by']) && $meeting['created_by'] == $_SESSION['user_id']) ||
            (isset($meeting['organizer_id']) && $meeting['organizer_id'] == $_SESSION['user_id']) ||
            (isset($meeting['teacher_id']) && $meeting['teacher_id'] == $teacher['id'])
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
    
    redirect('/tutoring/views/tutor/meetings.php');
}

// Traitement de la complétion d'une réunion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_meeting'])) {
    $meetingId = $_POST['meeting_id'];
    $studentAttended = isset($_POST['student_attended']) ? 1 : 0;
    $notes = $_POST['meeting_notes'] ?? '';
    
    try {
        // Vérifier que la réunion appartient au tuteur
        $meeting = $meetingModel->getById($meetingId);
        
        if (!$meeting) {
            setFlashMessage('error', 'Réunion non trouvée');
            redirect('/tutoring/views/tutor/meetings.php');
        }
        
        // Vérifier que la réunion a déjà eu lieu
        $meetingDate = null;
        $now = new DateTime();
        
        if (isset($meeting['date_time']) && !empty($meeting['date_time'])) {
            $meetingDate = new DateTime($meeting['date_time']);
        } elseif (isset($meeting['date'])) {
            if (isset($meeting['start_time'])) {
                $meetingDate = new DateTime($meeting['date'] . ' ' . $meeting['start_time']);
            } else {
                $meetingDate = new DateTime($meeting['date']);
            }
        }
        
        if ($meetingDate && $meetingDate > $now) {
            setFlashMessage('error', 'Impossible de marquer comme terminée une réunion qui n\'a pas encore eu lieu');
            redirect('/tutoring/views/tutor/meetings.php');
        }
        
        // Vérifier les droits d'accès
        if ($meeting && (
            (isset($meeting['tutor_id']) && $meeting['tutor_id'] == $teacher['id']) ||
            (isset($meeting['created_by']) && $meeting['created_by'] == $_SESSION['user_id']) ||
            (isset($meeting['organizer_id']) && $meeting['organizer_id'] == $_SESSION['user_id']) ||
            (isset($meeting['teacher_id']) && $meeting['teacher_id'] == $teacher['id'])
        )) {
            // Données à mettre à jour
            $updateData = [
                'status' => 'completed',
                'student_attended' => $studentAttended,
                'notes' => $notes,
                'completed_at' => date('Y-m-d H:i:s')
            ];
            
            if (method_exists($meetingModel, 'complete')) {
                if ($meetingModel->complete($meetingId, $studentAttended, $notes)) {
                    setFlashMessage('success', 'Réunion marquée comme terminée');
                } else {
                    setFlashMessage('error', 'Erreur lors de la mise à jour de la réunion');
                }
            } else {
                // Alternative si la méthode complete n'existe pas
                if ($meetingModel->update($meetingId, $updateData)) {
                    setFlashMessage('success', 'Réunion marquée comme terminée');
                } else {
                    setFlashMessage('error', 'Erreur lors de la mise à jour de la réunion');
                }
            }
        } else {
            setFlashMessage('error', 'Réunion non trouvée ou vous n\'avez pas les droits pour la modifier');
        }
    } catch (Exception $e) {
        setFlashMessage('error', 'Erreur lors de la mise à jour: ' . $e->getMessage());
    }
    
    redirect('/tutoring/views/tutor/meetings.php');
}

// Inclure l'en-tête
include_once __DIR__ . '/../common/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-calendar-event me-2"></i>Gestion des réunions</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/tutoring/views/tutor/dashboard.php">Tableau de bord</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Réunions</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3 fade-in delay-1">
            <div class="card stat-card">
                <div class="value"><?php echo $pastCount; ?></div>
                <div class="label">Réunions passées</div>
                <div class="progress mt-2">
                    <div class="progress-bar" role="progressbar" style="width: <?php echo $totalMeetings > 0 ? ($pastCount / $totalMeetings) * 100 : 0; ?>%;" aria-valuenow="<?php echo $totalMeetings > 0 ? ($pastCount / $totalMeetings) * 100 : 0; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <small class="text-muted"><?php echo $pastCount; ?>/<?php echo $totalMeetings; ?> complétées</small>
            </div>
        </div>
        <div class="col-md-3 fade-in delay-2">
            <div class="card stat-card">
                <div class="value"><?php echo $upcomingCount; ?></div>
                <div class="label">À venir</div>
                <div class="progress mt-2">
                    <div class="progress-bar bg-info" role="progressbar" style="width: 100%;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <small class="text-muted">
                    <?php if (!empty($meetings['upcoming'])): ?>
                        <?php 
                        $nextMeetingDate = null;
                        if (isset($meetings['upcoming'][0]['date_time'])) {
                            $nextMeetingDate = $meetings['upcoming'][0]['date_time'];
                        } elseif (isset($meetings['upcoming'][0]['meeting_date'])) {
                            $nextMeetingDate = $meetings['upcoming'][0]['meeting_date'];
                        } elseif (isset($meetings['upcoming'][0]['date'])) {
                            $nextMeetingDate = $meetings['upcoming'][0]['date'];
                        }
                        
                        if ($nextMeetingDate) {
                            echo 'Prochaine: ' . date('d/m/Y', strtotime($nextMeetingDate));
                        } else {
                            echo 'Prochaine: Date non définie';
                        }
                        ?>
                    <?php else: ?>
                        Aucune réunion planifiée
                    <?php endif; ?>
                </small>
            </div>
        </div>
        <div class="col-md-3 fade-in delay-3">
            <div class="card stat-card">
                <div class="value"><?php echo $participationRate; ?>%</div>
                <div class="label">Participation</div>
                <div class="progress mt-2">
                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $participationRate; ?>%;" aria-valuenow="<?php echo $participationRate; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <small class="text-muted"><?php echo $attendedCount; ?>/<?php echo $pastCount; ?> réunions avec présence</small>
            </div>
        </div>
        <div class="col-md-3 fade-in delay-4">
            <div class="card stat-card">
                <div class="value"><?php echo count($studentIds); ?></div>
                <div class="label">Étudiants</div>
                <div class="progress mt-2">
                    <div class="progress-bar bg-warning" role="progressbar" style="width: 100%;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <small class="text-muted">Étudiants encadrés</small>
            </div>
        </div>
    </div>
    
    <!-- Filters Row -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="get" class="row g-3">
                        <div class="col-md-2">
                            <label for="student_id" class="form-label">Étudiant</label>
                            <select name="student_id" id="student_id" class="form-select form-select-sm">
                                <option value="all">Tous les étudiants</option>
                                <?php foreach ($assignments as $assignment): ?>
                                <option value="<?php echo h($assignment['student_id']); ?>" <?php echo $studentFilter == $assignment['student_id'] ? 'selected' : ''; ?>>
                                    <?php echo h($assignment['student_first_name'] . ' ' . $assignment['student_last_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="status" class="form-label">Statut</label>
                            <select name="status" id="status" class="form-select form-select-sm">
                                <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>Tous les statuts</option>
                                <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>En attente</option>
                                <option value="scheduled" <?php echo $statusFilter === 'scheduled' ? 'selected' : ''; ?>>Planifiée</option>
                                <option value="confirmed" <?php echo $statusFilter === 'confirmed' ? 'selected' : ''; ?>>Confirmée</option>
                                <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Terminée</option>
                                <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Annulée</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="date_range" class="form-label">Période</label>
                            <select name="date_range" id="date_range" class="form-select form-select-sm">
                                <option value="all" <?php echo $dateRangeFilter === 'all' ? 'selected' : ''; ?>>Toutes les dates</option>
                                <option value="today" <?php echo $dateRangeFilter === 'today' ? 'selected' : ''; ?>>Aujourd'hui</option>
                                <option value="week" <?php echo $dateRangeFilter === 'week' ? 'selected' : ''; ?>>Cette semaine</option>
                                <option value="month" <?php echo $dateRangeFilter === 'month' ? 'selected' : ''; ?>>Ce mois</option>
                                <option value="future" <?php echo $dateRangeFilter === 'future' ? 'selected' : ''; ?>>À venir</option>
                                <option value="past" <?php echo $dateRangeFilter === 'past' ? 'selected' : ''; ?>>Passées</option>
                            </select>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <button type="submit" class="btn btn-sm btn-primary me-2">
                                <i class="bi bi-funnel me-1"></i>Appliquer les filtres
                            </button>
                            <a href="/tutoring/views/tutor/meetings.php" class="btn btn-sm btn-outline-secondary me-3">
                                <i class="bi bi-arrow-counterclockwise me-1"></i>Réinitialiser
                            </a>
                            <button type="button" class="btn btn-success ms-auto" data-bs-toggle="modal" data-bs-target="#newMeetingModal">
                                <i class="bi bi-plus-circle me-1"></i>Nouvelle réunion
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main Content Row -->
    <div class="row">
        <!-- Left Column -->
        <div class="col-lg-8">
            <!-- Meetings List -->
            <div class="card mb-4 fade-in">
                <div class="card-header">
                    <span>Liste des réunions</span>
                </div>
                <div class="card-body">
                    <?php if (empty($filteredMeetings)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i> Aucune réunion ne correspond à vos critères de recherche. Ajustez vos filtres ou créez une nouvelle réunion.
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Étudiant</th>
                                    <th>Sujet</th>
                                    <th>Type</th>
                                    <th>Lieu</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // Trier les réunions par date (les plus récentes d'abord)
                                usort($filteredMeetings, function($a, $b) {
                                    // Déterminer la date de chaque réunion en fonction des clés disponibles
                                    $dateA = null;
                                    if (isset($a['meeting_date'])) {
                                        $dateA = $a['meeting_date'];
                                    } elseif (isset($a['date_time'])) {
                                        $dateA = $a['date_time'];
                                    } elseif (isset($a['date'])) {
                                        $dateA = $a['date'] . (isset($a['start_time']) ? ' ' . $a['start_time'] : '');
                                    } else {
                                        return 0; // Si pas de date, considérer égal
                                    }
                                    
                                    $dateB = null;
                                    if (isset($b['meeting_date'])) {
                                        $dateB = $b['meeting_date'];
                                    } elseif (isset($b['date_time'])) {
                                        $dateB = $b['date_time'];
                                    } elseif (isset($b['date'])) {
                                        $dateB = $b['date'] . (isset($b['start_time']) ? ' ' . $b['start_time'] : '');
                                    } else {
                                        return 0; // Si pas de date, considérer égal
                                    }
                                    
                                    return strtotime($dateB) - strtotime($dateA);
                                });
                                
                                foreach ($filteredMeetings as $meeting): 
                                    // Déterminer la date de la réunion
                                    if (isset($meeting['date_time'])) {
                                        $meetingDate = new DateTime($meeting['date_time']);
                                    } elseif (isset($meeting['meeting_date'])) {
                                        $meetingDate = new DateTime($meeting['meeting_date']);
                                    } elseif (isset($meeting['date'])) {
                                        // Si on a date et start_time séparés
                                        if (isset($meeting['start_time'])) {
                                            $meetingDate = new DateTime($meeting['date'] . ' ' . $meeting['start_time']);
                                        } else {
                                            $meetingDate = new DateTime($meeting['date']);
                                        }
                                    } else {
                                        // Fallback à la date actuelle si aucune date n'est disponible
                                        $meetingDate = new DateTime();
                                    }
                                    
                                    // Déterminer la durée et calculer l'heure de fin
                                    $duration = $meeting['duration'] ?? 60; // 60 minutes par défaut
                                    $endDate = clone $meetingDate;
                                    $endDate->modify("+{$duration} minutes");
                                    
                                    // Récupérer les informations de l'étudiant
                                    $studentName = 'Étudiant inconnu';
                                    
                                    // Déterminer l'ID de l'étudiant à partir des données disponibles
                                    $meetingStudentId = null;
                                    
                                    // Essayer différentes sources pour trouver l'ID de l'étudiant
                                    if (isset($meeting['student_id'])) {
                                        $meetingStudentId = $meeting['student_id'];
                                    } elseif (isset($meeting['assignment_id'])) {
                                        // Rechercher l'étudiant par l'ID d'affectation
                                        foreach ($assignments as $a) {
                                            if (isset($a['id']) && $a['id'] == $meeting['assignment_id']) {
                                                $meetingStudentId = $a['student_id'];
                                                break;
                                            }
                                        }
                                    }
                                    
                                    // Si on a trouvé un ID d'étudiant, rechercher son nom
                                    if ($meetingStudentId) {
                                        foreach ($assignments as $assignment) {
                                            if ($assignment['student_id'] == $meetingStudentId) {
                                                $studentName = $assignment['student_first_name'] . ' ' . $assignment['student_last_name'];
                                                break;
                                            }
                                        }
                                    }
                                    
                                    // Déterminer la classe CSS pour le statut
                                    $statusClass = match($meeting['status']) {
                                        'pending' => 'bg-warning',
                                        'confirmed' => 'bg-primary',
                                        'completed' => 'bg-success',
                                        'cancelled' => 'bg-danger',
                                        default => 'bg-secondary'
                                    };
                                    
                                    // Traduire le statut en français
                                    $statusLabel = match($meeting['status']) {
                                        'pending' => 'En attente',
                                        'confirmed' => 'Confirmée',
                                        'completed' => 'Terminée',
                                        'cancelled' => 'Annulée',
                                        'scheduled' => 'Planifiée',
                                        default => ucfirst($meeting['status'])
                                    };
                                    
                                    // Déterminer si c'est une réunion passée
                                    $isPast = $meetingDate < new DateTime();
                                ?>
                                <tr <?php echo $isPast ? 'style="opacity: 0.7;"' : ''; ?>>
                                    <td>
                                        <strong><?php echo $meetingDate->format('d/m/Y'); ?></strong><br>
                                        <small><?php echo $meetingDate->format('H:i') . ' - ' . $endDate->format('H:i'); ?></small>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php
                                            // Générer l'avatar avec les initiales
                                            $names = explode(' ', $studentName);
                                            $initials = '';
                                            if (count($names) >= 2) {
                                                $initials = substr($names[0], 0, 1) . substr($names[1], 0, 1);
                                            } else {
                                                $initials = substr($studentName, 0, 2);
                                            }
                                            $avatarUrl = "https://ui-avatars.com/api/?name=" . urlencode($initials) . "&background=f39c12&color=fff";
                                            ?>
                                            <img src="<?php echo h($avatarUrl); ?>" alt="Student" class="rounded-circle me-2" width="24" height="24">
                                            <?php echo h($studentName); ?>
                                        </div>
                                    </td>
                                    <td><?php echo h($meeting['title'] ?? 'Réunion'); ?></td>
                                    <td><?php echo h($meeting['meeting_type'] ?? 'Non spécifié'); ?></td>
                                    <td><?php echo h($meeting['location'] ?? 'Non spécifié'); ?></td>
                                    <td>
                                        <span class="badge <?php echo $statusClass; ?> rounded-pill">
                                            <?php echo $statusLabel; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-outline-primary view-meeting" data-meeting-id="<?php echo $meeting['id']; ?>">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <?php if ($meeting['status'] !== 'cancelled' && $meeting['status'] !== 'completed'): ?>
                                            <button class="btn btn-sm btn-outline-success" onclick="completeMeeting(<?php echo $meeting['id']; ?>)">
                                                <i class="bi bi-check2-circle"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="confirmCancel(<?php echo $meeting['id']; ?>)">
                                                <i class="bi bi-x-circle"></i>
                                            </button>
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
        
        <!-- Right Column -->
        <div class="col-lg-4">
            <!-- Quick Actions -->
            <div class="card mb-4 fade-in">
                <div class="card-header">
                    Actions rapides
                </div>
                <div class="card-body">
                    <button class="btn btn-primary w-100 mb-2" data-bs-toggle="modal" data-bs-target="#newMeetingModal">
                        <i class="bi bi-plus-circle me-2"></i>Nouvelle réunion
                    </button>
                    <a href="/tutoring/views/tutor/students.php" class="btn btn-outline-primary w-100 mb-2">
                        <i class="bi bi-people me-2"></i>Voir mes étudiants
                    </a>
                    <a href="/tutoring/views/tutor/dashboard.php" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-house me-2"></i>Retour au tableau de bord
                    </a>
                </div>
            </div>
            
            <!-- Upcoming Meetings -->
            <div class="card fade-in">
                <div class="card-header">
                    <span>Prochaines réunions</span>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php if (empty($meetings['upcoming'])): ?>
                        <div class="list-group-item p-3">
                            <p class="mb-0 text-muted">Aucune réunion planifiée</p>
                        </div>
                        <?php else: ?>
                            <?php 
                            // Afficher les 5 prochaines réunions
                            $upcomingDisplayed = 0;
                            foreach ($meetings['upcoming'] as $meeting): 
                                if ($upcomingDisplayed >= 5) break;
                                if (isset($meeting['date_time'])) {
                                    $meetingDate = new DateTime($meeting['date_time']);
                                } elseif (isset($meeting['meeting_date'])) {
                                    $meetingDate = new DateTime($meeting['meeting_date']);
                                } else {
                                    $meetingDate = new DateTime($meeting['date'] . (isset($meeting['start_time']) ? ' ' . $meeting['start_time'] : ''));
                                }
                                $upcomingDisplayed++;
                                
                                // Récupérer les informations de l'étudiant
                                $studentName = 'Étudiant inconnu';
                                foreach ($assignments as $assignment) {
                                    if ($assignment['student_id'] == $meeting['student_id']) {
                                        $studentName = $assignment['student_first_name'] . ' ' . $assignment['student_last_name'];
                                        break;
                                    }
                                }
                            ?>
                            <div class="list-group-item p-3">
                                <div class="d-flex justify-content-between">
                                    <strong><?php echo h($meeting['title'] ?? 'Réunion'); ?></strong>
                                    <span class="badge 
                                        <?php 
                                        echo match($meeting['status']) {
                                            'pending' => 'bg-warning',
                                            'confirmed' => 'bg-primary',
                                            'completed' => 'bg-success',
                                            'cancelled' => 'bg-danger',
                                            default => 'bg-secondary'
                                        };
                                        ?>">
                                        <?php 
                                        echo match($meeting['status']) {
                                            'pending' => 'En attente',
                                            'confirmed' => 'Confirmée',
                                            'completed' => 'Terminée',
                                            'cancelled' => 'Annulée',
                                            default => ucfirst($meeting['status'])
                                        };
                                        ?>
                                    </span>
                                </div>
                                <p class="mb-1 small">
                                    <?php echo $meetingDate->format('d/m/Y, H:i'); ?> - <?php echo h($meeting['location'] ?? 'Lieu non spécifié'); ?>
                                </p>
                                <small class="text-muted">
                                    Avec <?php echo h($studentName); ?>
                                </small>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
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
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Étudiant</label>
                            <select class="form-select" name="student_id" required>
                                <option value="">Sélectionnez un étudiant</option>
                                <?php foreach ($assignments as $assignment): ?>
                                <option value="<?php echo h($assignment['student_id']); ?>">
                                    <?php echo h($assignment['student_first_name'] . ' ' . $assignment['student_last_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Type de réunion</label>
                            <select class="form-select" name="meeting_type" required>
                                <option value="Suivi régulier">Suivi régulier</option>
                                <option value="Évaluation">Évaluation</option>
                                <option value="Présentation">Présentation</option>
                                <option value="Problème spécifique">Problème spécifique</option>
                                <option value="Autre">Autre</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sujet</label>
                        <input type="text" class="form-control" name="meeting_title" placeholder="Objet de la réunion" required>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Date</label>
                            <input type="date" class="form-control" name="meeting_date" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Heure</label>
                            <input type="time" class="form-control" name="meeting_time" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Durée (minutes)</label>
                            <input type="number" class="form-control" name="meeting_duration" value="60" min="15" max="180" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Mode</label>
                            <select class="form-select" name="meeting_mode" required>
                                <option value="En présentiel">En présentiel</option>
                                <option value="En ligne">En ligne</option>
                                <option value="Hybride">Hybride</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Lieu / Lien</label>
                            <input type="text" class="form-control" name="meeting_location" placeholder="Bureau, salle ou lien de visioconférence" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Agenda / Notes</label>
                        <textarea class="form-control" name="meeting_description" rows="4" placeholder="Points à aborder pendant la réunion..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" name="create_meeting" class="btn btn-primary">Créer la réunion</button>
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
                <p class="text-muted small">Une notification sera envoyée à l'étudiant concerné.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Non</button>
                <form action="" method="POST">
                    <input type="hidden" name="meeting_id" id="meeting_id_to_cancel">
                    <button type="submit" name="cancel_meeting" class="btn btn-danger">Oui, annuler</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Complete Meeting Modal -->
<div class="modal fade" id="completeMeetingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Marquer comme terminée</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="student_attended" id="student_attended" checked>
                            <label class="form-check-label" for="student_attended">
                                L'étudiant était présent à la réunion
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes de réunion</label>
                        <textarea class="form-control" name="meeting_notes" rows="5" placeholder="Résumé des points abordés, décisions prises, actions à suivre..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <input type="hidden" name="meeting_id" id="meeting_id_to_complete">
                    <button type="submit" name="complete_meeting" class="btn btn-success">Marquer comme terminée</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Meeting Details Modal -->
<div class="modal fade" id="viewMeetingModal" tabindex="-1" aria-labelledby="viewMeetingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewMeetingModalLabel">Détails de la réunion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-8">
                        <div id="meeting-details-content">
                            <div class="mb-3">
                                <h5 id="meeting-title">Chargement des détails...</h5>
                                <span class="badge" id="meeting-status"></span>
                            </div>
                            <table class="table">
                                <tbody>
                                    <tr>
                                        <th style="width: 150px;">Date et heure:</th>
                                        <td id="meeting-datetime"></td>
                                    </tr>
                                    <tr>
                                        <th>Durée:</th>
                                        <td id="meeting-duration"></td>
                                    </tr>
                                    <tr>
                                        <th>Étudiant:</th>
                                        <td id="meeting-student"></td>
                                    </tr>
                                    <tr>
                                        <th>Organisateur:</th>
                                        <td id="meeting-organizer"></td>
                                    </tr>
                                    <tr>
                                        <th>Lieu:</th>
                                        <td id="meeting-location"></td>
                                    </tr>
                                    <tr id="meeting-link-row" style="display: none;">
                                        <th>Lien:</th>
                                        <td id="meeting-link"></td>
                                    </tr>
                                    <tr>
                                        <th>Description:</th>
                                        <td id="meeting-description"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                Actions
                            </div>
                            <div class="card-body">
                                <div id="meeting-actions">
                                    <!-- Les boutons d'action seront ajoutés dynamiquement -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
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
        
        // Affichage des détails d'une réunion
        document.querySelectorAll('.view-meeting').forEach(button => {
            button.addEventListener('click', function() {
                const meetingId = this.getAttribute('data-meeting-id');
                viewMeetingDetails(meetingId);
            });
        });
    });
    
    // Fonction pour confirmer l'annulation d'une réunion
    function confirmCancel(meetingId) {
        document.getElementById('meeting_id_to_cancel').value = meetingId;
        const modal = new bootstrap.Modal(document.getElementById('cancelMeetingModal'));
        modal.show();
    }
    
    // Fonction pour marquer une réunion comme terminée
    function completeMeeting(meetingId) {
        document.getElementById('meeting_id_to_complete').value = meetingId;
        const modal = new bootstrap.Modal(document.getElementById('completeMeetingModal'));
        modal.show();
    }
    
    // Fonction pour afficher les détails d'une réunion
    function viewMeetingDetails(meetingId) {
        // Afficher le modal de chargement
        const viewModal = new bootstrap.Modal(document.getElementById('viewMeetingModal'));
        viewModal.show();
        
        // Réinitialiser le contenu
        document.getElementById('meeting-title').textContent = 'Chargement des détails...';
        document.getElementById('meeting-status').textContent = '';
        document.getElementById('meeting-datetime').textContent = '';
        document.getElementById('meeting-duration').textContent = '';
        document.getElementById('meeting-student').textContent = '';
        document.getElementById('meeting-organizer').textContent = '';
        document.getElementById('meeting-location').textContent = '';
        document.getElementById('meeting-description').textContent = '';
        document.getElementById('meeting-actions').innerHTML = '';
        
        // Masquer la ligne du lien par défaut
        document.getElementById('meeting-link-row').style.display = 'none';
        
        // Faire la requête AJAX pour récupérer les détails
        fetch(`/tutoring/views/tutor/meeting_details.php?id=${meetingId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur lors de la récupération des détails');
                }
                return response.json();
            })
            .then(data => {
                // Mettre à jour le contenu du modal avec les données
                document.getElementById('meeting-title').textContent = data.title;
                
                // Mettre à jour le statut avec la bonne classe
                const statusBadge = document.getElementById('meeting-status');
                statusBadge.textContent = data.status.label;
                statusBadge.className = `badge ${data.status.class}`;
                
                // Mettre à jour les autres informations
                document.getElementById('meeting-datetime').textContent = `${data.date}, ${data.time}`;
                document.getElementById('meeting-duration').textContent = data.duration;
                document.getElementById('meeting-student').textContent = data.student.name;
                document.getElementById('meeting-organizer').textContent = data.organizer;
                document.getElementById('meeting-location').textContent = data.location;
                document.getElementById('meeting-description').textContent = data.description || 'Aucune description';
                
                // Afficher le lien de réunion s'il existe
                if (data.meeting_link) {
                    document.getElementById('meeting-link-row').style.display = '';
                    document.getElementById('meeting-link').innerHTML = `<a href="${data.meeting_link}" target="_blank">${data.meeting_link}</a>`;
                }
                
                // Générer les boutons d'action en fonction du statut
                const actionsContainer = document.getElementById('meeting-actions');
                actionsContainer.innerHTML = '';
                
                if (data.actions.can_edit) {
                    const editButton = document.createElement('button');
                    editButton.className = 'btn btn-outline-primary w-100 mb-2';
                    editButton.innerHTML = '<i class="bi bi-pencil me-2"></i>Modifier';
                    editButton.onclick = function() {
                        // TODO: Implémenter la modification
                        alert('Fonctionnalité à implémenter');
                    };
                    actionsContainer.appendChild(editButton);
                }
                
                if (data.actions.can_complete) {
                    const completeButton = document.createElement('button');
                    completeButton.className = 'btn btn-success w-100 mb-2';
                    completeButton.innerHTML = '<i class="bi bi-check2-circle me-2"></i>Marquer comme terminée';
                    completeButton.onclick = function() {
                        // Vérifier que la réunion a déjà eu lieu
                        const now = new Date();
                        const meetingDate = new Date(data.date.split('/').reverse().join('-') + ' ' + data.time.split(' - ')[0]);
                        
                        if (meetingDate > now) {
                            alert('Impossible de marquer comme terminée une réunion qui n\'a pas encore eu lieu');
                            return;
                        }
                        
                        viewModal.hide();
                        completeMeeting(data.id);
                    };
                    actionsContainer.appendChild(completeButton);
                }
                
                if (data.actions.can_cancel) {
                    const cancelButton = document.createElement('button');
                    cancelButton.className = 'btn btn-danger w-100 mb-2';
                    cancelButton.innerHTML = '<i class="bi bi-x-circle me-2"></i>Annuler la réunion';
                    cancelButton.onclick = function() {
                        viewModal.hide();
                        confirmCancel(data.id);
                    };
                    actionsContainer.appendChild(cancelButton);
                }
                
                // Si la réunion est terminée et a des notes, les afficher
                if (data.status.value === 'completed' && data.notes) {
                    const notesSection = document.createElement('div');
                    notesSection.className = 'mt-3 border-top pt-3';
                    notesSection.innerHTML = `
                        <h6 class="fw-bold">Notes de réunion:</h6>
                        <p class="mb-0">${data.notes}</p>
                    `;
                    actionsContainer.appendChild(notesSection);
                    
                    // Afficher si l'étudiant était présent
                    const attendanceInfo = document.createElement('div');
                    attendanceInfo.className = 'mt-2';
                    attendanceInfo.innerHTML = data.student_attended 
                        ? '<span class="badge bg-success">Étudiant présent</span>'
                        : '<span class="badge bg-danger">Étudiant absent</span>';
                    actionsContainer.appendChild(attendanceInfo);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                document.getElementById('meeting-title').textContent = 'Erreur';
                document.getElementById('meeting-description').textContent = 'Impossible de charger les détails de la réunion.';
                
                // Ajouter un bouton pour réessayer
                const actionsContainer = document.getElementById('meeting-actions');
                actionsContainer.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>${error.message}
                    </div>
                    <button class="btn btn-primary w-100" onclick="viewMeetingDetails(${meetingId})">
                        <i class="bi bi-arrow-clockwise me-2"></i>Réessayer
                    </button>
                `;
            });
    }
</script>

<?php
// Inclure le pied de page
include_once __DIR__ . '/../common/footer.php';
?>