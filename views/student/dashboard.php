<?php
/**
 * Tableau de bord étudiant
 */

// Titre de la page
$pageTitle = 'Tableau de bord';
$currentPage = 'dashboard';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../includes/init.php';

// Vérifier que l'utilisateur est étudiant
requireRole('student');

// Variables pour stocker les données du tableau de bord
$student = [];
$assignment = null;
$documents = [];
$meetings = [];
$preferences = [];

// Si on est en accès direct à la page, récupérer les données
// Cette section est utile si la page est appelée directement sans passer par le contrôleur
if (!isset($student) || empty($student)) {
    try {
        // Utiliser la connexion à la base de données globale (déjà établie dans init.php)
        global $db;
        
        // Si $db n'est pas disponible, essayer d'initialiser une nouvelle connexion
        if (!isset($db) || !$db) {
            require_once __DIR__ . '/../../config/database.php';
            $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        
        // Initialiser le modèle Student
        $studentModel = new Student($db);
        
        // Récupérer l'étudiant connecté
        if (isset($_SESSION['user_id'])) {
            $student = $studentModel->getByUserId($_SESSION['user_id']);
            
            if ($student) {
                // Récupérer les informations pour le tableau de bord
                $assignment = $studentModel->getAssignment($student['id']);
                $preferences = $studentModel->getPreferences($student['id']);
                $documents = $studentModel->getDocuments($student['id']);
                $meetings = $studentModel->getMeetings($student['id']);
            }
        }
    } catch (Exception $e) {
        // Logguer l'erreur mais ne pas l'afficher pour éviter de perturber l'affichage
        error_log("Erreur dans dashboard.php: " . $e->getMessage());
    }
}

// Initialiser les variables compteurs
$documentCount = isset($documents) && is_array($documents) ? count($documents) : 0;
$meetingsCount = isset($meetings) && is_array($meetings) ? count($meetings) : 0;

// S'assurer que toutes les variables nécessaires sont définies
if (!isset($assignment) || !is_array($assignment)) {
    $assignment = null;
}

// Inclure l'en-tête
include_once __DIR__ . '/../common/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2>Tableau de bord</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item active" aria-current="page">Tableau de bord</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3 fade-in delay-1">
            <div class="card stat-card">
                <?php
                // Calculer la progression du stage
                $stageProgress = 0;
                $stageStatus = 'Stage non commencé';
                
                if (isset($assignment) && $assignment) {
                    if ($assignment['status'] == 'active') {
                        // Pour un stage actif, on peut calculer une progression basée sur différents critères
                        // Par exemple, le nombre de documents soumis, de réunions tenues, etc.
                        $docsWeight = 0.4; // 40% de la progression basée sur les documents
                        $meetingsWeight = 0.3; // 30% de la progression basée sur les réunions
                        $evalsWeight = 0.3; // 30% de la progression basée sur les évaluations
                        
                        $docsProgress = isset($documentCount) ? min(1, $documentCount / 10) : 0;
                        $meetingsProgress = isset($meetings) && is_array($meetings) ? min(1, count($meetings) / 5) : 0;
                        $evalsProgress = isset($evaluationsCount) ? min(1, $evaluationsCount / 4) : 0;
                        
                        $stageProgress = round(($docsProgress * $docsWeight + $meetingsProgress * $meetingsWeight + $evalsProgress * $evalsWeight) * 100);
                        
                        if ($stageProgress < 25) {
                            $stageStatus = 'Début du stage';
                        } else if ($stageProgress < 50) {
                            $stageStatus = 'Stage en cours';
                        } else if ($stageProgress < 75) {
                            $stageStatus = 'Stage avancé';
                        } else {
                            $stageStatus = 'Fin de stage';
                        }
                    } else if ($assignment['status'] == 'completed') {
                        $stageProgress = 100;
                        $stageStatus = 'Stage terminé';
                    } else if ($assignment['status'] == 'pending') {
                        $stageProgress = 5;
                        $stageStatus = 'Stage en attente';
                    }
                }
                ?>
                <div class="value"><?php echo $stageProgress; ?>%</div>
                <div class="label">Progression</div>
                <div class="progress mt-2">
                    <div class="progress-bar" role="progressbar" 
                         style="width: <?php echo $stageProgress; ?>%;" 
                         aria-valuenow="<?php echo $stageProgress; ?>" 
                         aria-valuemin="0" 
                         aria-valuemax="100"></div>
                </div>
                <small class="text-muted"><?php echo $stageStatus; ?></small>
            </div>
        </div>
        <div class="col-md-3 fade-in delay-2">
            <div class="card stat-card">
                <?php
                // Calculer les évaluations soumises
                $evaluationsCount = 0;
                $evaluationStatus = 'Pas d\'évaluation';
                
                // Filtrer les documents pour trouver les évaluations
                if (isset($documents) && is_array($documents)) {
                    $evaluationDocs = array_filter($documents, function($doc) {
                        return isset($doc['type']) && 
                              ($doc['type'] == 'evaluation' || 
                               $doc['type'] == 'self_evaluation' || 
                               $doc['type'] == 'mid_term' || 
                               $doc['type'] == 'final');
                    });
                    
                    $evaluationsCount = count($evaluationDocs);
                    
                    if ($evaluationsCount == 1) {
                        $evaluationStatus = 'Évaluation soumise';
                    } else if ($evaluationsCount > 1) {
                        $evaluationStatus = 'Évaluations soumises';
                    }
                }
                
                // Calculer le pourcentage pour la barre de progression (sur une base de 4 évaluations)
                $evalProgress = min(100, ($evaluationsCount / 4) * 100);
                ?>
                <div class="value"><?php echo $evaluationsCount; ?></div>
                <div class="label">Évaluation<?php echo $evaluationsCount > 1 ? 's' : ''; ?></div>
                <div class="progress mt-2">
                    <div class="progress-bar bg-success" role="progressbar" 
                         style="width: <?php echo $evalProgress; ?>%;" 
                         aria-valuenow="<?php echo $evalProgress; ?>" 
                         aria-valuemin="0" 
                         aria-valuemax="100"></div>
                </div>
                <small class="text-muted"><?php echo $evaluationStatus; ?></small>
            </div>
        </div>
        <div class="col-md-3 fade-in delay-3">
            <div class="card stat-card">
                <?php
                // Calculer les réunions à venir
                $upcomingMeetings = 0;
                $meetingStatus = 'Pas de réunion';
                
                if (isset($meetings) && is_array($meetings)) {
                    $today = date('Y-m-d');
                    $upcomingMeetings = count(array_filter($meetings, function($meeting) use ($today) {
                        // Vérifier le format de date (peut être date_time ou meeting_date)
                        $meetingDate = null;
                        if (isset($meeting['meeting_date'])) {
                            $meetingDate = $meeting['meeting_date'];
                        } elseif (isset($meeting['date_time'])) {
                            $meetingDate = date('Y-m-d', strtotime($meeting['date_time']));
                        } elseif (isset($meeting['date'])) {
                            $meetingDate = $meeting['date'];
                        }
                        
                        if (!$meetingDate) {
                            return false;
                        }
                        
                        // Vérifier le statut
                        $status = isset($meeting['status']) ? $meeting['status'] : null;
                        
                        return $meetingDate >= $today && $status == 'scheduled';
                    }));
                    
                    if ($upcomingMeetings == 1) {
                        $meetingStatus = 'Réunion à venir';
                    } else if ($upcomingMeetings > 1) {
                        $meetingStatus = 'Réunions à venir';
                    }
                }
                
                // Calculer le pourcentage pour la barre de progression (base arbitraire de 5 réunions max)
                $meetingProgress = min(100, ($upcomingMeetings / 5) * 100);
                ?>
                <div class="value"><?php echo $upcomingMeetings; ?></div>
                <div class="label">Réunion<?php echo $upcomingMeetings > 1 ? 's' : ''; ?></div>
                <div class="progress mt-2">
                    <div class="progress-bar bg-info" role="progressbar" 
                         style="width: <?php echo $meetingProgress; ?>%;" 
                         aria-valuenow="<?php echo $meetingProgress; ?>" 
                         aria-valuemin="0" 
                         aria-valuemax="100"></div>
                </div>
                <small class="text-muted"><?php echo $meetingStatus; ?></small>
            </div>
        </div>
        <div class="col-md-3 fade-in delay-4">
            <div class="card stat-card">
                <?php
                // Compter les documents
                $documentCount = isset($documents) && is_array($documents) ? count($documents) : 0;
                $documentStatus = 'Pas de document';
                
                if ($documentCount == 1) {
                    $documentStatus = 'Document soumis';
                } else if ($documentCount > 1) {
                    $documentStatus = 'Documents soumis';
                }
                
                // Calculer le pourcentage pour la barre de progression (base arbitraire de 10 documents max)
                $docProgress = min(100, ($documentCount / 10) * 100);
                ?>
                <div class="value"><?php echo $documentCount; ?></div>
                <div class="label">Document<?php echo $documentCount > 1 ? 's' : ''; ?></div>
                <div class="progress mt-2">
                    <div class="progress-bar bg-warning" role="progressbar" 
                         style="width: <?php echo $docProgress; ?>%;" 
                         aria-valuenow="<?php echo $docProgress; ?>" 
                         aria-valuemin="0" 
                         aria-valuemax="100"></div>
                </div>
                <small class="text-muted"><?php echo $documentStatus; ?></small>
            </div>
        </div>
    </div>
    
    <!-- Main Content Row -->
    <div class="row">
        <!-- Left Column -->
        <div class="col-lg-8">
            <!-- Stage Details -->
            <div class="card mb-4 fade-in">
                <div class="card-header">
                    <span>Détails du stage</span>
                    <a href="/tutoring/views/student/internship.php" class="btn btn-sm btn-outline-primary">Voir les détails</a>
                </div>
                <div class="card-body">
                    <?php if (isset($assignment) && $assignment): ?>
                        <h5 class="card-title"><?php echo htmlspecialchars($assignment['internship_title']); ?></h5>
                        <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($assignment['company_name']); ?></h6>
                        <div class="mb-3">
                            <strong>Statut:</strong> 
                            <span class="badge bg-<?php echo $assignment['status'] == 'active' ? 'success' : ($assignment['status'] == 'pending' ? 'warning' : 'secondary'); ?>">
                                <?php 
                                    if ($assignment['status'] == 'active') echo 'Actif';
                                    elseif ($assignment['status'] == 'pending') echo 'En attente';
                                    elseif ($assignment['status'] == 'completed') echo 'Terminé';
                                    else echo htmlspecialchars($assignment['status']);
                                ?>
                            </span>
                        </div>
                        <?php if (isset($assignment['assignment_date'])): ?>
                            <p><strong>Date d'affectation:</strong> <?php echo date('d/m/Y', strtotime($assignment['assignment_date'])); ?></p>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="alert alert-info" role="alert">
                            <i class="bi bi-info-circle-fill me-2"></i>Aucun stage affecté pour le moment. Contactez votre coordinateur pour plus d'informations.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Tutor Details -->
            <div class="card mb-4 fade-in">
                <div class="card-header">
                    <span>Mon tuteur académique</span>
                    <a href="/tutoring/views/student/tutor.php" class="btn btn-sm btn-outline-primary">Voir le profil</a>
                </div>
                <div class="card-body">
                    <?php if (isset($assignment) && $assignment && isset($assignment['teacher_first_name'])): ?>
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1 ms-3">
                                <h5 class="mb-1"><?php echo htmlspecialchars($assignment['teacher_first_name'] . ' ' . $assignment['teacher_last_name']); ?></h5>
                                <p class="mb-0 text-muted">Tuteur académique</p>
                                <div class="mt-3">
                                    <a href="/tutoring/views/student/messages.php" class="btn btn-sm btn-primary">
                                        <i class="bi bi-chat-left-text me-1"></i> Envoyer un message
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info" role="alert">
                            <i class="bi bi-info-circle-fill me-2"></i>Aucun tuteur affecté pour le moment. Contactez votre coordinateur pour plus d'informations.
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
                    <a href="/tutoring/views/student/documents.php" class="btn btn-outline-primary w-100 mb-2">
                        <i class="bi bi-folder me-2"></i>Soumettre un document
                    </a>
                    <a href="/tutoring/views/student/meetings.php" class="btn btn-outline-primary w-100 mb-2">
                        <i class="bi bi-calendar-event me-2"></i>Demander une réunion
                    </a>
                    <a href="/tutoring/views/student/preferences.php" class="btn btn-outline-primary w-100 mb-2">
                        <i class="bi bi-sliders me-2"></i>Définir mes préférences
                    </a>
                    <a href="/tutoring/views/student/messages.php" class="btn btn-primary w-100">
                        <i class="bi bi-chat-left-text me-2"></i>Contacter mon tuteur
                    </a>
                </div>
            </div>
            
            <!-- Upcoming Events -->
            <div class="card mb-4 fade-in">
                <div class="card-header">
                    Événements à venir
                </div>
                <div class="card-body p-0">
                    <?php
                    // Filtrer les réunions à venir
                    $upcomingEvents = [];
                    
                    if (isset($meetings) && is_array($meetings)) {
                        $today = date('Y-m-d');
                        
                        // Préparer les réunions avec un format de date normalisé
                        $normalizedMeetings = array_map(function($meeting) {
                            // Normaliser la date de réunion
                            if (isset($meeting['meeting_date'])) {
                                $meeting['normalized_date'] = $meeting['meeting_date'];
                            } elseif (isset($meeting['date_time'])) {
                                $meeting['normalized_date'] = date('Y-m-d', strtotime($meeting['date_time']));
                                $meeting['meeting_date'] = $meeting['normalized_date'];
                                $meeting['meeting_time'] = date('H:i:s', strtotime($meeting['date_time']));
                            } elseif (isset($meeting['date'])) {
                                $meeting['normalized_date'] = $meeting['date'];
                                $meeting['meeting_date'] = $meeting['date'];
                                if (isset($meeting['start_time'])) {
                                    $meeting['meeting_time'] = $meeting['start_time'];
                                }
                            } else {
                                $meeting['normalized_date'] = '1970-01-01';
                            }
                            
                            // Normaliser l'heure de la réunion
                            if (!isset($meeting['meeting_time']) && isset($meeting['date_time'])) {
                                $meeting['meeting_time'] = date('H:i:s', strtotime($meeting['date_time']));
                            }
                            
                            return $meeting;
                        }, $meetings);
                        
                        // Filtrer les réunions à venir
                        $upcomingEvents = array_filter($normalizedMeetings, function($meeting) use ($today) {
                            $status = isset($meeting['status']) ? $meeting['status'] : 'unknown';
                            return $meeting['normalized_date'] >= $today && $status == 'scheduled';
                        });
                        
                        // Trier par date (la plus proche d'abord)
                        usort($upcomingEvents, function($a, $b) {
                            $dateA = $a['normalized_date'];
                            $dateB = $b['normalized_date'];
                            
                            if ($dateA == $dateB) {
                                $timeA = isset($a['meeting_time']) ? $a['meeting_time'] : '00:00:00';
                                $timeB = isset($b['meeting_time']) ? $b['meeting_time'] : '00:00:00';
                                return strtotime($timeA) - strtotime($timeB);
                            }
                            
                            return strtotime($dateA) - strtotime($dateB);
                        });
                        
                        // Limiter à 5 événements max
                        $upcomingEvents = array_slice($upcomingEvents, 0, 5);
                    }
                    
                    if (empty($upcomingEvents)):
                    ?>
                        <div class="alert alert-info m-3" role="alert">
                            <i class="bi bi-info-circle-fill me-2"></i>Aucun événement planifié pour le moment.
                        </div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($upcomingEvents as $event): ?>
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?php echo htmlspecialchars($event['title']); ?></strong>
                                            <?php if (isset($event['meeting_date'])): ?>
                                                <div class="text-muted">
                                                    <i class="bi bi-calendar me-1"></i>
                                                    <?php echo date('d/m/Y', strtotime($event['meeting_date'])); ?>
                                                    <?php if (isset($event['meeting_time'])): ?>
                                                        à <?php echo date('H:i', strtotime($event['meeting_time'])); ?>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php 
                                            // Afficher les informations sur l'organisateur si disponibles
                                            $organizerName = '';
                                            if (isset($event['organizer_first_name']) && isset($event['organizer_last_name'])) {
                                                $organizerName = $event['organizer_first_name'] . ' ' . $event['organizer_last_name'];
                                            } elseif (isset($event['teacher_first_name']) && isset($event['teacher_last_name'])) {
                                                $organizerName = $event['teacher_first_name'] . ' ' . $event['teacher_last_name'];
                                            } elseif (isset($event['tutor_name'])) {
                                                $organizerName = $event['tutor_name'];
                                            }
                                            
                                            if (!empty($organizerName)): 
                                            ?>
                                                <div class="text-muted">
                                                    <i class="bi bi-person me-1"></i>
                                                    <?php echo htmlspecialchars($organizerName); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <a href="/tutoring/views/student/meetings.php" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Inclure le pied de page
include_once __DIR__ . '/../common/footer.php';
?>