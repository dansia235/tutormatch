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
$evaluations = [];
$stats = [
    'average' => 0,
    'completed' => 0,
    'total_expected' => 3,
    'technical' => 0,
    'professional' => 0
];

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
        
        // Initialiser les modèles nécessaires
        $studentModel = new Student($db);
        $internshipModel = new Internship($db);
        
        // Vérifier si la classe Evaluation existe et l'utiliser
        $evaluationModel = null;
        if (class_exists('Evaluation')) {
            $evaluationModel = new Evaluation($db);
        }
        
        // Récupérer l'étudiant connecté
        if (isset($_SESSION['user_id'])) {
            $student = $studentModel->getByUserId($_SESSION['user_id']);
            $user_id = $_SESSION['user_id'];
            $student_id = $student['id'] ?? null;
            
            if ($student && isset($student['id'])) {
                // Récupérer les informations pour le tableau de bord
                $assignment = $studentModel->getAssignment($student['id']);
                $preferences = $studentModel->getPreferences($student['id']);
                $documents = $studentModel->getDocuments($student['id']);
                
                // Récupérer les réunions de manière plus robuste, comme dans meetings.php
                try {
                    $meetingModel = new Meeting($db);
                    $allMeetings = [];
                    
                    // 1. Récupérer les réunions où l'étudiant est directement associé
                    if (method_exists($meetingModel, 'getByStudentId')) {
                        $studentMeetings = $meetingModel->getByStudentId($student['id']);
                        $allMeetings = array_merge($allMeetings, $studentMeetings);
                    } else {
                        // Si la méthode n'existe pas, utiliser getMeetings du modèle Student
                        $meetings = $studentModel->getMeetings($student['id']);
                        if ($meetings && is_array($meetings)) {
                            $allMeetings = array_merge($allMeetings, $meetings);
                        }
                    }
                    
                    // 2. Récupérer les réunions liées aux affectations de l'étudiant
                    if (isset($assignment) && !empty($assignment) && isset($assignment['id'])) {
                        // Requête SQL directe pour plus de fiabilité
                        $query = "SELECT m.*, 
                                  u.first_name as organizer_first_name, u.last_name as organizer_last_name
                                  FROM meetings m
                                  LEFT JOIN users u ON m.organizer_id = u.id
                                  WHERE m.assignment_id = :assignment_id";
                                  
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(':assignment_id', $assignment['id']);
                        $stmt->execute();
                        $assignmentMeetings = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        if ($assignmentMeetings && is_array($assignmentMeetings)) {
                            $allMeetings = array_merge($allMeetings, $assignmentMeetings);
                        }
                    }
                    
                    // 3. Récupérer les réunions où l'étudiant est l'organisateur
                    $query = "SELECT m.*, 
                              u.first_name as organizer_first_name, u.last_name as organizer_last_name
                              FROM meetings m
                              LEFT JOIN users u ON m.organizer_id = u.id
                              WHERE m.organizer_id = :user_id";
                              
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->execute();
                    $organizerMeetings = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if ($organizerMeetings && is_array($organizerMeetings)) {
                        $allMeetings = array_merge($allMeetings, $organizerMeetings);
                    }
                    
                    // Supprimer les doublons
                    $uniqueMeetings = [];
                    foreach ($allMeetings as $meeting) {
                        if (isset($meeting['id']) && !isset($uniqueMeetings[$meeting['id']])) {
                            $uniqueMeetings[$meeting['id']] = $meeting;
                        }
                    }
                    
                    $meetings = array_values($uniqueMeetings);
                } catch (Exception $e) {
                    error_log("Erreur lors de la récupération des réunions: " . $e->getMessage());
                    $meetings = [];
                }
                
                // Récupérer les évaluations avec l'approche robuste utilisée dans evaluations.php
                try {
                    $evaluations = [];
                    
                    // ÉTAPE 1 : Récupération via le modèle Evaluation si disponible
                    if ($evaluationModel !== null) {
                        if (isset($assignment['id'])) {
                            // Récupérer les évaluations de cette affectation
                            $evalFromModel = $evaluationModel->getByAssignmentId($assignment['id']);
                            
                            // Transformer les données au format attendu
                            foreach ($evalFromModel as $eval) {
                                // Convertir le score de 20 à 5 si nécessaire
                                $score = isset($eval['score']) && $eval['score'] > 5 ? round($eval['score'] / 4, 1) : $eval['score'];
                                
                                // Préparer les critères
                                $criteria = [];
                                if (isset($eval['criteria_scores'])) {
                                    $criteriaScores = is_string($eval['criteria_scores']) 
                                        ? json_decode($eval['criteria_scores'], true) 
                                        : $eval['criteria_scores'];
                                    
                                    if (is_array($criteriaScores)) {
                                        $criteriaLabels = [
                                            'technical_mastery' => 'Maîtrise des technologies',
                                            'work_quality' => 'Qualité du travail',
                                            'problem_solving' => 'Résolution de problèmes',
                                            'documentation' => 'Documentation',
                                            'autonomy' => 'Autonomie',
                                            'communication' => 'Communication',
                                            'team_integration' => 'Intégration dans l\'équipe',
                                            'deadline_respect' => 'Respect des délais'
                                        ];
                                        
                                        foreach ($criteriaScores as $key => $criterionData) {
                                            // Gérer le nouveau format avec score et comment
                                            $score = 0;
                                            if (is_array($criterionData) && isset($criterionData['score'])) {
                                                $score = floatval($criterionData['score']);
                                            } elseif (is_numeric($criterionData)) {
                                                $score = floatval($criterionData);
                                            }
                                            
                                            // Convertir de 20 à 5 si nécessaire
                                            if ($score > 5) {
                                                $score = round($score / 4, 1);
                                            }
                                            
                                            $criteria[] = [
                                                'name' => $criteriaLabels[$key] ?? ucfirst(str_replace('_', ' ', $key)),
                                                'score' => $score
                                            ];
                                        }
                                    }
                                }
                                
                                // Créer l'entrée d'évaluation formatée
                                $evaluations[] = [
                                    'id' => $eval['id'],
                                    'student_id' => $student_id,
                                    'type' => $eval['type'],
                                    'date' => $eval['submission_date'] ?? $eval['created_at'] ?? date('Y-m-d'),
                                    'evaluator_name' => 'Tuteur',
                                    'score' => $score,
                                    'comments' => $eval['feedback'] ?? $eval['comments'] ?? '',
                                    'criteria' => $criteria,
                                    'areas_for_improvement' => !empty($eval['areas_to_improve']) ? explode("\n", $eval['areas_to_improve']) : [],
                                    'recommendations' => !empty($eval['next_steps']) ? explode("\n", $eval['next_steps']) : []
                                ];
                            }
                        }
                    }
                    
                    // ÉTAPE 2 : Récupération via les documents (pour les auto-évaluations et les versions antérieures)
                    // Filtrer pour ne garder que les documents de type évaluation
                    $evaluationDocuments = [];
                    foreach ($documents as $doc) {
                        if (isset($doc['type']) && (
                            $doc['type'] === 'evaluation' || 
                            $doc['type'] === 'self_evaluation' || 
                            $doc['type'] === 'mid_term' || 
                            $doc['type'] === 'final')
                        ) {
                            $evaluationDocuments[] = $doc;
                        }
                    }
                    
                    // Convertir les documents en évaluations
                    foreach ($evaluationDocuments as $doc) {
                        // Vérifier si le document a des métadonnées
                        if (!isset($doc['metadata']) || !is_array($doc['metadata'])) {
                            $doc['metadata'] = [];
                        }
                        
                        // Extraire les informations de base du document
                        $evaluation = [
                            'id' => 'doc_' . $doc['id'], // Préfixer pour éviter les conflits d'ID
                            'student_id' => $doc['user_id'],
                            'type' => $doc['type'] === 'self_evaluation' ? 'self' : 'teacher',
                            'date' => $doc['upload_date'] ?? date('Y-m-d H:i:s'),
                            'evaluator_name' => isset($doc['metadata']['evaluator_name']) ? $doc['metadata']['evaluator_name'] : 'Système',
                            'score' => isset($doc['metadata']['score']) ? $doc['metadata']['score'] : 0,
                            'comments' => $doc['description'] ?? ($doc['metadata']['comments'] ?? ''),
                            'criteria' => []
                        ];
                        
                        // Extraire les critères s'ils existent
                        if (isset($doc['metadata']['criteria']) && is_array($doc['metadata']['criteria'])) {
                            $evaluation['criteria'] = $doc['metadata']['criteria'];
                        }
                        
                        // Ne pas ajouter si une évaluation avec le même type et une date proche existe déjà
                        $isDuplicate = false;
                        foreach ($evaluations as $existingEval) {
                            if ($existingEval['type'] === $evaluation['type']) {
                                $existingDate = new DateTime($existingEval['date']);
                                $newDate = new DateTime($evaluation['date']);
                                $interval = $existingDate->diff($newDate);
                                
                                // Si les dates sont à moins de 2 jours d'écart, considérer comme un doublon
                                if ($interval->days < 2) {
                                    $isDuplicate = true;
                                    break;
                                }
                            }
                        }
                        
                        if (!$isDuplicate) {
                            $evaluations[] = $evaluation;
                        }
                    }
                    
                    // Calculer les statistiques en utilisant TOUTES les évaluations (comme dans la vue tuteur)
                    $totalScore = 0;
                    $totalTechnical = 0;
                    $totalProfessional = 0;
                    $evaluationCount = 0;
                    $technicalCount = 0;
                    $professionalCount = 0;
                    
                    // Si on a des évaluations du modèle Evaluation, utiliser les moyennes pré-calculées
                    if ($evaluationModel !== null && isset($assignment['id'])) {
                        $evalFromModel = $evaluationModel->getByAssignmentId($assignment['id']);
                        
                        foreach ($evalFromModel as $eval) {
                            if (isset($eval['score']) && is_numeric($eval['score'])) {
                                $score = $eval['score'];
                                // Convertir de 20 à 5 si nécessaire
                                if ($score > 5) {
                                    $score = $score / 4;
                                }
                                $totalScore += $score;
                                $evaluationCount++;
                            }
                            
                            // Utiliser les moyennes technique et professionnelle pré-calculées
                            if (isset($eval['technical_avg']) && is_numeric($eval['technical_avg'])) {
                                $techAvg = $eval['technical_avg'];
                                // Convertir de 20 à 5 si nécessaire
                                if ($techAvg > 5) {
                                    $techAvg = $techAvg / 4;
                                }
                                $totalTechnical += $techAvg;
                                $technicalCount++;
                            }
                            
                            if (isset($eval['professional_avg']) && is_numeric($eval['professional_avg'])) {
                                $profAvg = $eval['professional_avg'];
                                // Convertir de 20 à 5 si nécessaire
                                if ($profAvg > 5) {
                                    $profAvg = $profAvg / 4;
                                }
                                $totalProfessional += $profAvg;
                                $professionalCount++;
                            }
                        }
                    }
                    
                    // Si pas de données du modèle ou pour compléter, utiliser les autres évaluations
                    foreach ($evaluations as $evaluation) {
                        // Si cette évaluation provient du modèle (ID numérique), on l'a déjà traitée
                        if (is_numeric($evaluation['id'])) {
                            continue;
                        }
                        
                        if (isset($evaluation['score']) && is_numeric($evaluation['score'])) {
                            $score = $evaluation['score'];
                            if ($score > 5) {
                                $score = $score / 4;
                            }
                            $totalScore += $score;
                            $evaluationCount++;
                        }
                    }
                    
                    // Calculer les moyennes finales
                    $stats = [
                        'average' => $evaluationCount > 0 ? round($totalScore / $evaluationCount, 1) : 0,
                        'completed' => $evaluationCount,
                        'total_expected' => 3, // Maximum de 3 évaluations comme dans la restriction
                        'technical' => $technicalCount > 0 ? round($totalTechnical / $technicalCount, 1) : 0,
                        'professional' => $professionalCount > 0 ? round($totalProfessional / $professionalCount, 1) : 0
                    ];
                    
                    
                } catch (Exception $e) {
                    error_log("Erreur lors de la récupération des évaluations: " . $e->getMessage());
                    $evaluations = [];
                }
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
                // Calculer la progression du stage basée sur les dates
                $stageProgress = 0;
                $stageStatus = 'Stage non commencé';
                
                if (isset($assignment) && $assignment) {
                    if ($assignment['status'] == 'active' || $assignment['status'] == 'confirmed') {
                        // Pour un stage actif, calculer la progression basée sur les dates
                        $internship = null;
                        if (isset($assignment['internship_id'])) {
                            $internship = $internshipModel->getById($assignment['internship_id']);
                        }
                        
                        // Vérifier d'abord si les dates sont dans l'assignment directement
                        $startDate = null;
                        $endDate = null;
                        
                        if (isset($assignment['start_date']) && isset($assignment['end_date'])) {
                            $startDate = new DateTime($assignment['start_date']);
                            $endDate = new DateTime($assignment['end_date']);
                        } elseif ($internship && isset($internship['start_date']) && isset($internship['end_date'])) {
                            $startDate = new DateTime($internship['start_date']);
                            $endDate = new DateTime($internship['end_date']);
                        }
                        
                        if ($startDate && $endDate) {
                            $today = new DateTime();
                            
                            // Vérifier si on est entre le début et la fin du stage
                            if ($today >= $startDate && $today <= $endDate) {
                                // Calculer le pourcentage de temps écoulé
                                $totalDuration = $startDate->diff($endDate)->days;
                                $elapsedDuration = $startDate->diff($today)->days;
                                
                                if ($totalDuration > 0) {
                                    $stageProgress = min(100, round(($elapsedDuration / $totalDuration) * 100));
                                }
                                
                                // Déterminer le statut en fonction de la progression
                                if ($stageProgress < 25) {
                                    $stageStatus = 'Début du stage';
                                } else if ($stageProgress < 50) {
                                    $stageStatus = 'Stage en cours';
                                } else if ($stageProgress < 75) {
                                    $stageStatus = 'Stage avancé';
                                } else {
                                    $stageStatus = 'Fin de stage';
                                }
                            } elseif ($today > $endDate) {
                                // Le stage est terminé
                                $stageProgress = 100;
                                $stageStatus = 'Stage terminé';
                            } elseif ($today < $startDate) {
                                // Le stage n'a pas encore commencé
                                $stageProgress = 0;
                                $stageStatus = 'Stage à venir';
                            }
                        } else {
                            // Pas de dates de stage définies, utiliser une approche basée sur les activités
                            $docsWeight = 0.4; // 40% de la progression basée sur les documents
                            $meetingsWeight = 0.3; // 30% de la progression basée sur les réunions
                            $evalsWeight = 0.3; // 30% de la progression basée sur les évaluations
                            
                            $docsProgress = isset($documentCount) ? min(1, $documentCount / 10) : 0;
                            $meetingsProgress = isset($meetings) && is_array($meetings) ? min(1, count($meetings) / 5) : 0;
                            $evalsProgress = isset($stats['completed']) ? min(1, $stats['completed'] / $stats['total_expected']) : 0;
                            
                            $stageProgress = round(($docsProgress * $docsWeight + $meetingsProgress * $meetingsWeight + $evalsProgress * $evalsWeight) * 100);
                            $stageStatus = 'Stage en cours';
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
                // Afficher les informations d'évaluations provenant des nouvelles variables
                $evaluationsCount = $stats['completed'];
                $evaluationStatus = 'Pas d\'évaluation';
                
                if ($evaluationsCount == 1) {
                    $evaluationStatus = 'Évaluation soumise';
                } else if ($evaluationsCount > 1) {
                    $evaluationStatus = 'Évaluations soumises';
                }
                
                // Calculer le pourcentage pour la barre de progression (sur une base de 3 évaluations max)
                $maxEvaluations = 3; // 1 mi-parcours + 1 finale + 1 auto-évaluation
                $evalProgress = min(100, ($evaluationsCount / $maxEvaluations) * 100);
                
                // Obtenir la note moyenne
                $averageScore = $stats['average'];
                ?>
                <div class="value"><?php echo number_format($averageScore, 1); ?></div>
                <div class="label">Note moyenne</div>
                <div class="progress mt-2">
                    <div class="progress-bar bg-success" role="progressbar" 
                         style="width: <?php echo ($averageScore / 5) * 100; ?>%;" 
                         aria-valuenow="<?php echo ($averageScore / 5) * 100; ?>" 
                         aria-valuemin="0" 
                         aria-valuemax="100"></div>
                </div>
                <small class="text-muted"><?php echo $evaluationsCount; ?> évaluation<?php echo $evaluationsCount > 1 ? 's' : ''; ?> sur <?php echo $maxEvaluations; ?></small>
            </div>
        </div>
        <div class="col-md-3 fade-in delay-3">
            <div class="card stat-card">
                <?php
                // Catégoriser les réunions comme dans meetings.php
                $categorizedMeetings = [
                    'upcoming' => [],
                    'past' => [],
                    'cancelled' => []
                ];
                
                // Compteurs et statistiques
                $totalMeetings = count($meetings);
                $upcomingCount = 0;
                $pastCount = 0;
                $attendedCount = 0;
                $cancelledCount = 0;
                
                // Date actuelle pour comparer
                $currentDate = new DateTime();
                
                // Organiser les réunions par catégorie
                foreach ($meetings as $meeting) {
                    // Gérer différents formats de date possibles
                    $meetingDateStr = $meeting['date_time'] ?? $meeting['meeting_date'] ?? null;
                    if (!$meetingDateStr) continue;
                    
                    $meetingDate = new DateTime($meetingDateStr);
                    
                    // Vérifier si la réunion est passée ou à venir
                    if ($meeting['status'] === 'cancelled') {
                        $categorizedMeetings['cancelled'][] = $meeting;
                        $cancelledCount++;
                    } elseif ($meetingDate < $currentDate) {
                        $categorizedMeetings['past'][] = $meeting;
                        $pastCount++;
                        
                        // Vérifier si l'étudiant a assisté à la réunion
                        if (isset($meeting['student_attended']) && $meeting['student_attended'] == 1) {
                            $attendedCount++;
                        }
                    } else {
                        $categorizedMeetings['upcoming'][] = $meeting;
                        $upcomingCount++;
                    }
                }
                
                // Calculer le taux de participation
                $participationRate = $pastCount > 0 ? round(($attendedCount / $pastCount) * 100) : 0;
                
                // Déterminer le statut des réunions
                $meetingStatus = 'Pas de réunion';
                if ($upcomingCount == 1) {
                    $meetingStatus = 'Réunion à venir';
                } else if ($upcomingCount > 1) {
                    $meetingStatus = 'Réunions à venir';
                }
                
                // Calculer le pourcentage pour la barre de progression
                $meetingProgress = min(100, ($upcomingCount / 5) * 100);
                
                // Prochaine réunion
                $nextMeetingDate = '';
                if (!empty($categorizedMeetings['upcoming'])) {
                    // Trier les réunions à venir par date
                    usort($categorizedMeetings['upcoming'], function($a, $b) {
                        $dateA = new DateTime($a['date_time'] ?? $a['meeting_date']);
                        $dateB = new DateTime($b['date_time'] ?? $b['meeting_date']);
                        return $dateA <=> $dateB;
                    });
                    
                    $nextMeeting = $categorizedMeetings['upcoming'][0];
                    $nextMeetingDate = new DateTime($nextMeeting['date_time'] ?? $nextMeeting['meeting_date']);
                    $nextMeetingDate = $nextMeetingDate->format('d/m/Y');
                }
                ?>
                <div class="value"><?php echo $upcomingCount; ?></div>
                <div class="label">Réunion<?php echo $upcomingCount > 1 ? 's' : ''; ?> à venir</div>
                <div class="progress mt-2">
                    <div class="progress-bar bg-info" role="progressbar" 
                         style="width: <?php echo $meetingProgress; ?>%;" 
                         aria-valuenow="<?php echo $meetingProgress; ?>" 
                         aria-valuemin="0" 
                         aria-valuemax="100"></div>
                </div>
                <small class="text-muted">
                    <?php if (!empty($nextMeetingDate)): ?>
                        Prochaine: <?php echo $nextMeetingDate; ?>
                    <?php else: ?>
                        Aucune réunion planifiée
                    <?php endif; ?>
                </small>
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
            
            <!-- Progression de l'étudiant avec graphique -->
            <div class="card mb-4 fade-in">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Progression et Évaluations</span>
                    <a href="/tutoring/views/student/evaluations.php" class="btn btn-sm btn-outline-primary">Voir toutes les évaluations</a>
                </div>
                <div class="card-body">
                    <?php if (!empty($evaluations)): ?>
                        <div class="row mb-4">
                            <div class="col-md-7">
                                <div class="chart-container" style="position: relative; height: 250px;">
                                    <canvas id="studentProgressChart"></canvas>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <h5 class="mb-3">Compétences</h5>
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between">
                                        <span>Technique</span>
                                        <span><?php echo h($stats['technical']); ?>/5</span>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo h(($stats['technical']/5)*100); ?>%;" aria-valuenow="<?php echo h($stats['technical']); ?>" aria-valuemin="0" aria-valuemax="5"></div>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between">
                                        <span>Professionnel</span>
                                        <span><?php echo h($stats['professional']); ?>/5</span>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo h(($stats['professional']/5)*100); ?>%;" aria-valuenow="<?php echo h($stats['professional']); ?>" aria-valuemin="0" aria-valuemax="5"></div>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between">
                                        <span>Moyenne générale</span>
                                        <span><?php echo h($stats['average']); ?>/5</span>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo h(($stats['average']/5)*100); ?>%;" aria-valuenow="<?php echo h($stats['average']); ?>" aria-valuemin="0" aria-valuemax="5"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Dernière évaluation -->
                        <?php 
                        // Trier les évaluations par date (la plus récente d'abord)
                        usort($evaluations, function($a, $b) {
                            $dateA = new DateTime($a['date']);
                            $dateB = new DateTime($b['date']);
                            return $dateB <=> $dateA;
                        });
                        
                        $latestEvaluation = $evaluations[0];
                        
                        // Déterminer le type d'évaluation
                        $evaluationType = 'Évaluation';
                        if ($latestEvaluation['type']) {
                            switch(strtolower($latestEvaluation['type'])) {
                                case 'self': 
                                case 'self_evaluation':
                                    $evaluationType = 'Auto-évaluation';
                                    break;
                                case 'mid_term':
                                case 'mid-term':
                                case 'midterm':
                                    $evaluationType = 'Évaluation mi-parcours';
                                    break;
                                case 'final':
                                case 'finale':
                                    $evaluationType = 'Évaluation finale';
                                    break;
                                case 'company':
                                case 'enterprise':
                                case 'entreprise':
                                    $evaluationType = 'Évaluation entreprise';
                                    break;
                                default:
                                    $evaluationType = 'Évaluation ' . $latestEvaluation['type'];
                            }
                        }
                        ?>
                        
                        <h6 class="mb-3">
                            <i class="bi bi-clipboard-check me-2 text-primary"></i>
                            Dernière évaluation - <?php echo $evaluationType; ?>
                        </h6>
                        
                        <div class="card mb-2 border-start border-4 border-primary">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="me-3">
                                        <div class="rating-stars">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="bi <?php echo ($i <= $latestEvaluation['score']) ? 'bi-star-fill' : 'bi-star'; ?> text-warning"></i>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">Note: <?php echo h($latestEvaluation['score']); ?>/5</h6>
                                        <small class="text-muted">
                                            <?php echo date('d/m/Y', strtotime($latestEvaluation['date'])); ?> - 
                                            <?php echo h($latestEvaluation['evaluator_name']); ?>
                                        </small>
                                    </div>
                                </div>
                                
                                <?php if (!empty($latestEvaluation['comments'])): ?>
                                <div class="mb-3">
                                    <h6>Commentaires</h6>
                                    <p class="small"><?php echo nl2br(h(substr($latestEvaluation['comments'], 0, 200))); ?>
                                    <?php if (strlen($latestEvaluation['comments']) > 200): ?>
                                        <a href="/tutoring/views/student/evaluations.php" class="text-primary">... voir plus</a>
                                    <?php endif; ?>
                                    </p>
                                </div>
                                <?php endif; ?>
                                
                                <a href="/tutoring/views/student/evaluations.php" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye me-1"></i>Voir les détails
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info" role="alert">
                            <i class="bi bi-info-circle-fill me-2"></i>Aucune évaluation disponible pour le moment.
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
                    <a href="/tutoring/views/student/evaluations.php" class="btn btn-outline-primary w-100 mb-2">
                        <i class="bi bi-clipboard-check me-2"></i>Mes évaluations
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
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Réunions à venir</span>
                    <a href="/tutoring/views/student/meetings.php" class="btn btn-sm btn-outline-primary">Voir toutes</a>
                </div>
                <div class="card-body p-0">
                    <?php
                    // Utiliser les réunions à venir déjà filtrées
                    $upcomingEvents = $categorizedMeetings['upcoming'] ?? [];
                    
                    // Trier par date (la plus proche d'abord)
                    usort($upcomingEvents, function($a, $b) {
                        $dateA = new DateTime($a['date_time'] ?? $a['meeting_date']);
                        $dateB = new DateTime($b['date_time'] ?? $b['meeting_date']);
                        return $dateA <=> $dateB;
                    });
                    
                    // Limiter à 3 événements max
                    $upcomingEvents = array_slice($upcomingEvents, 0, 3);
                    
                    if (empty($upcomingEvents)):
                    ?>
                        <div class="alert alert-info m-3" role="alert">
                            <i class="bi bi-info-circle-fill me-2"></i>Aucune réunion planifiée pour le moment.
                        </div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($upcomingEvents as $event): 
                                $meetingDate = new DateTime($event['date_time'] ?? $event['meeting_date']);
                                $formattedDate = $meetingDate->format('d/m/Y');
                                $formattedTime = isset($event['meeting_time']) ? date('H:i', strtotime($event['meeting_time'])) : $meetingDate->format('H:i');
                            ?>
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?php echo htmlspecialchars($event['title'] ?? 'Réunion de suivi'); ?></strong>
                                            <div class="text-muted">
                                                <i class="bi bi-calendar me-1"></i>
                                                <?php echo $formattedDate; ?> à <?php echo $formattedTime; ?>
                                            </div>
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
                                                <div class="text-muted small">
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
                        
                        <?php if ($upcomingCount > 3): ?>
                        <div class="text-center py-2">
                            <a href="/tutoring/views/student/meetings.php" class="btn btn-sm btn-link">
                                Voir les <?php echo $upcomingCount - 3; ?> autre<?php echo $upcomingCount - 3 > 1 ? 's' : ''; ?> réunion<?php echo $upcomingCount - 3 > 1 ? 's' : ''; ?> à venir
                            </a>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Recent Documents -->
            <div class="card mb-4 fade-in">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Documents récents</span>
                    <a href="/tutoring/views/student/documents.php" class="btn btn-sm btn-outline-primary">Voir tous</a>
                </div>
                <div class="card-body p-0">
                    <?php
                    // Récupérer les documents récents (non-évaluations)
                    $recentDocuments = [];
                    
                    if (isset($documents) && is_array($documents)) {
                        // Filtrer pour enlever les documents d'évaluation
                        $recentDocuments = array_filter($documents, function($doc) {
                            return !isset($doc['type']) || (
                                $doc['type'] !== 'evaluation' && 
                                $doc['type'] !== 'self_evaluation' && 
                                $doc['type'] !== 'mid_term' && 
                                $doc['type'] !== 'final'
                            );
                        });
                        
                        // Trier par date (le plus récent d'abord)
                        usort($recentDocuments, function($a, $b) {
                            $dateA = strtotime($a['upload_date'] ?? '1970-01-01');
                            $dateB = strtotime($b['upload_date'] ?? '1970-01-01');
                            return $dateB - $dateA;
                        });
                        
                        // Limiter à 3 documents
                        $recentDocuments = array_slice($recentDocuments, 0, 3);
                    }
                    
                    if (empty($recentDocuments)):
                    ?>
                        <div class="alert alert-info m-3" role="alert">
                            <i class="bi bi-info-circle-fill me-2"></i>Aucun document récent.
                        </div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($recentDocuments as $doc): ?>
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?php echo htmlspecialchars($doc['title'] ?? 'Document sans titre'); ?></strong>
                                            <div class="text-muted small">
                                                <i class="bi bi-calendar me-1"></i>
                                                <?php echo isset($doc['upload_date']) ? date('d/m/Y', strtotime($doc['upload_date'])) : 'Date inconnue'; ?>
                                            </div>
                                            <?php if (isset($doc['type'])): ?>
                                            <div class="text-muted small">
                                                <i class="bi bi-file-earmark me-1"></i>
                                                <?php echo htmlspecialchars(ucfirst($doc['type'])); ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <a href="/tutoring/views/student/documents.php?id=<?php echo $doc['id']; ?>" class="btn btn-sm btn-outline-primary me-1">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="/tutoring/documents/download.php?id=<?php echo $doc['id']; ?>" class="btn btn-sm btn-outline-success">
                                                <i class="bi bi-download"></i>
                                            </a>
                                        </div>
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

<!-- Script pour le graphique de progression -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Si un graphique de progression est présent
        const progressChartElement = document.getElementById('studentProgressChart');
        if (progressChartElement) {
            try {
                // Vérifier que Chart.js est chargé
                if (typeof Chart === 'undefined') {
                    throw new Error('La bibliothèque Chart.js n\'est pas chargée');
                }
                
                // Données pour le graphique
                const evaluationData = <?php echo json_encode($evaluations ?? []); ?>;
                console.log('Données d\'évaluations reçues:', evaluationData);
                
                // Vérifier que les données sont valides
                if (!evaluationData || !Array.isArray(evaluationData)) {
                    console.error('Données d\'évaluations invalides ou manquantes:', evaluationData);
                    throw new Error('Données d\'évaluations invalides ou manquantes');
                }
                
                // Si pas d'évaluations, afficher un message approprié
                if (evaluationData.length === 0) {
                    console.log('Aucune évaluation trouvée');
                    progressChartElement.parentNode.innerHTML = '<div class="alert alert-info text-center" role="alert"><i class="bi bi-info-circle me-2"></i>Aucune évaluation disponible pour le moment</div>';
                    return;
                }
                
                // Préparer les données pour le graphique
                const chartData = {
                    labels: [],
                    technical: [],
                    professional: []
                };
                
                // Organiser les données chronologiquement
                const sortedEvals = [...evaluationData].filter(eval => eval && typeof eval === 'object')
                    .sort((a, b) => {
                        // Protection contre les dates invalides
                        const dateA = a.date ? new Date(a.date) : new Date(0);
                        const dateB = b.date ? new Date(b.date) : new Date(0);
                        
                        if (isNaN(dateA.getTime())) return 1;
                        if (isNaN(dateB.getTime())) return -1;
                        
                        return dateA - dateB;
                    });
                
                // Vérifier si nous avons des évaluations triées
                if (sortedEvals.length === 0) {
                    throw new Error('Aucune évaluation valide trouvée');
                }
                
                sortedEvals.forEach((eval, index) => {
                    console.log(`Traitement évaluation ${index + 1}:`, eval);
                    
                    // Vérifier que la date est valide
                    let dateStr;
                    try {
                        const evalDate = new Date(eval.date);
                        if (isNaN(evalDate.getTime())) {
                            dateStr = 'Date inconnue';
                        } else {
                            dateStr = evalDate.toLocaleDateString('fr-FR', {day: '2-digit', month: '2-digit'});
                        }
                    } catch (e) {
                        dateStr = 'Date inconnue';
                    }
                    
                    // Ajouter la date au format court
                    chartData.labels.push(dateStr);
                    
                    // Calculer les moyennes techniques et professionnelles
                    let techScore = 0;
                    let techCount = 0;
                    let profScore = 0;
                    let profCount = 0;
                    
                    // Traiter les critères avec une gestion d'erreur robuste
                    if (eval.criteria && Array.isArray(eval.criteria)) {
                        console.log('Critères trouvés:', eval.criteria);
                        eval.criteria.forEach(criterion => {
                            // Vérifier que le critère est valide
                            if (!criterion || typeof criterion !== 'object' || 
                                !('name' in criterion) || !('score' in criterion)) {
                                console.log('Critère invalide ignoré:', criterion);
                                return; // Ignorer les critères invalides
                            }
                            
                            const score = parseFloat(criterion.score);
                            if (isNaN(score)) {
                                console.log('Score non numérique ignoré:', criterion.score);
                                return; // Ignorer les scores non numériques
                            }
                            
                            const name = String(criterion.name).toLowerCase();
                            console.log('Critère traité:', name, 'Score:', score);
                            
                            // Classification des critères techniques vs professionnels
                            if (name.includes('technique') || 
                                name.includes('technical') ||
                                name.includes('maîtrise') ||
                                name.includes('qualité') ||
                                name.includes('problème') ||
                                name.includes('résolution') ||
                                name.includes('documentation')) {
                                techScore += score;
                                techCount++;
                                console.log('Ajouté aux compétences techniques');
                            } else if (name.includes('autonomie') ||
                                       name.includes('communication') ||
                                       name.includes('intégration') ||
                                       name.includes('équipe') ||
                                       name.includes('délai') ||
                                       name.includes('respect')) {
                                profScore += score;
                                profCount++;
                                console.log('Ajouté aux compétences professionnelles');
                            } else {
                                // Par défaut, ajouter aux compétences professionnelles
                                profScore += score;
                                profCount++;
                                console.log('Ajouté aux compétences professionnelles (par défaut)');
                            }
                        });
                    } else {
                        console.log('Aucun critère trouvé pour cette évaluation');
                        // Si pas de critères détaillés, utiliser le score global s'il existe
                        if (eval.score && !isNaN(parseFloat(eval.score))) {
                            const globalScore = parseFloat(eval.score);
                            // Diviser le score global entre technique et professionnel
                            techScore = globalScore / 2;
                            profScore = globalScore / 2;
                            techCount = 1;
                            profCount = 1;
                            console.log('Utilisation du score global divisé:', globalScore);
                        }
                    }
                    
                    // Ajouter les données avec protection contre les divisions par zéro
                    const techAvg = techCount > 0 ? parseFloat((techScore / techCount).toFixed(1)) : 0;
                    const profAvg = profCount > 0 ? parseFloat((profScore / profCount).toFixed(1)) : 0;
                    
                    console.log(`Moyennes calculées - Technique: ${techAvg}, Professionnel: ${profAvg}`);
                    
                    chartData.technical.push(techAvg);
                    chartData.professional.push(profAvg);
                });
                
                console.log('Données finales du graphique:', chartData);
                
                // Créer le graphique si des données sont disponibles
                if (chartData.labels.length > 0) {
                    // Vérifier le contexte du canvas
                    const ctx = progressChartElement.getContext('2d');
                    if (!ctx) {
                        throw new Error('Impossible d\'obtenir le contexte 2D du canvas');
                    }
                    
                    console.log('Création du graphique avec Chart.js...');
                    
                    // Créer le graphique avec gestion des erreurs
                    const chart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: chartData.labels,
                            datasets: [
                                {
                                    label: 'Technique',
                                    data: chartData.technical,
                                    borderColor: '#3498db',
                                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                                    tension: 0.3,
                                    fill: true
                                },
                                {
                                    label: 'Professionnel',
                                    data: chartData.professional,
                                    borderColor: '#2ecc71',
                                    backgroundColor: 'rgba(46, 204, 113, 0.1)',
                                    tension: 0.3,
                                    fill: true
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'bottom'
                                },
                                tooltip: {
                                    mode: 'index',
                                    intersect: false,
                                    callbacks: {
                                        // Protection contre les valeurs NaN
                                        label: function(context) {
                                            const label = context.dataset.label || '';
                                            const value = context.raw !== undefined && !isNaN(context.raw) ? 
                                                context.raw.toFixed(1) : 'N/A';
                                            return `${label}: ${value}/5`;
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    min: 0,
                                    max: 5,
                                    ticks: {
                                        stepSize: 1
                                    }
                                }
                            }
                        }
                    });
                    
                    console.log('Graphique créé avec succès!');
                } else {
                    console.log('Aucune donnée disponible pour le graphique');
                    // Si aucune donnée, afficher un message
                    progressChartElement.parentNode.innerHTML = '<p class="text-muted text-center my-3">Pas assez de données pour afficher un graphique de progression</p>';
                }
            } catch (error) {
                console.error('Erreur lors de l\'initialisation du graphique:', error);
                progressChartElement.parentNode.innerHTML = '<div class="alert alert-warning" role="alert"><i class="bi bi-exclamation-triangle me-2"></i>Impossible de charger le graphique: ' + error.message + '</div>';
            }
        }
    });
</script>

<style>
/* Ajout de styles pour la section des évaluations */
.rating-stars {
    color: #ffc107;
}

.chart-container {
    height: 250px;
    max-width: 100%;
}
</style>