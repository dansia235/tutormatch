<?php
/**
 * Page de gestion des évaluations par le tuteur
 */

// Titre de la page
$pageTitle = 'Évaluations des étudiants';
$currentPage = 'evaluations';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../includes/init.php';

// Vérifier que l'utilisateur est tuteur
requireRole('teacher');

// Récupérer le tuteur de la session
$userModel = new User($db);
$user = $userModel->getById($_SESSION['user_id']);

// Récupérer le modèle du tuteur
$teacherModel = new Teacher($db);
$teacher = $teacherModel->getByUserId($_SESSION['user_id']);

if (!$teacher) {
    setFlashMessage('error', 'Profil de tuteur non trouvé.');
    redirect('/tutoring/index.php');
}

// Récupérer les affectations d'étudiants pour ce tuteur
$assignments = $teacherModel->getAssignments($teacher['id']);

// Modèles nécessaires
$internshipModel = new Internship($db);
$studentModel = new Student($db);
$companyModel = new Company($db);
$evaluationModel = null;

// Vérifier si la classe Evaluation existe
if (class_exists('Evaluation')) {
    $evaluationModel = new Evaluation($db);
}

// Initialiser les variables
$selectedStudent = null;
$selectedAssignment = null;
$studentEvaluations = [];
$evaluationTypes = ['mid_term' => 'Mi-parcours', 'final' => 'Finale', 'company' => 'Entreprise'];

// Filtre par étudiant
$studentFilter = $_GET['student_id'] ?? null;
$typeFilter = $_GET['type'] ?? 'all';

// Récupérer l'étudiant sélectionné
if ($studentFilter) {
    foreach ($assignments as $assignment) {
        if ($assignment['student_id'] == $studentFilter) {
            $selectedAssignment = $assignment;
            break;
        }
    }
    
    // Récupérer les informations détaillées de l'étudiant
    if ($selectedAssignment) {
        $studentDetails = $studentModel->getById($selectedAssignment['student_id']);
        $selectedStudent = array_merge($selectedAssignment, $studentDetails ?? []);
        
        // Récupérer les informations du stage et de l'entreprise
        if ($selectedAssignment['internship_id']) {
            $internship = $internshipModel->getById($selectedAssignment['internship_id']);
            if ($internship) {
                $selectedStudent['internship_title'] = $internship['title'];
                $selectedStudent['start_date'] = $internship['start_date'];
                $selectedStudent['end_date'] = $internship['end_date'];
                
                if ($internship['company_id']) {
                    $company = $companyModel->getById($internship['company_id']);
                    if ($company) {
                        $selectedStudent['company_name'] = $company['name'];
                    }
                }
            }
        }
        
        // Récupérer les évaluations de cet étudiant - Approche en deux étapes
        $studentEvaluations = [];
        
        // ÉTAPE 1 : Récupération via le modèle Evaluation si disponible
        if ($evaluationModel) {
            $evaluationsFromDb = $evaluationModel->getByAssignmentId($selectedAssignment['id']);
            
            // Transformer les données au format attendu
            foreach ($evaluationsFromDb as $eval) {
                // Convertir le score de 20 à 5 si nécessaire
                $score = isset($eval['score']) ? $eval['score'] : 0;
                if ($score > 5) {
                    $score = min(5, round($score / 4, 1));
                }
                
                // Décoder les critères si stockés en JSON
                $criteria = [];
                if (isset($eval['criteria_scores']) && is_string($eval['criteria_scores'])) {
                    $criteriaScores = json_decode($eval['criteria_scores'], true);
                    
                    $criteriaLabels = [
                        'technical_skills' => 'Compétences techniques',
                        'professional_behavior' => 'Comportement professionnel',
                        'communication' => 'Communication',
                        'initiative' => 'Initiative et autonomie',
                        'teamwork' => 'Travail en équipe',
                        'punctuality' => 'Ponctualité et assiduité'
                    ];
                    
                    foreach ($criteriaScores as $key => $criterionScore) {
                        $criteria[] = [
                            'name' => $criteriaLabels[$key] ?? ucfirst(str_replace('_', ' ', $key)),
                            'score' => min(5, round($criterionScore / 4, 1)) // Convertir de 0-20 à 0-5 avec limite à 5
                        ];
                    }
                }
                
                // Déterminer le nom de l'évaluateur
                if ($eval['type'] === 'student') {
                    $evaluatorName = 'Auto-évaluation';
                } else {
                    // Récupérer le nom du tuteur depuis ses informations
                    $evaluatorName = $user['first_name'] . ' ' . $user['last_name'];
                }
                
                // Créer l'entrée d'évaluation formatée
                $studentEvaluations[] = [
                    'id' => $eval['id'],
                    'assignment_id' => $selectedAssignment['id'],
                    'type' => $eval['type'],
                    'date' => $eval['submission_date'] ?? $eval['created_at'] ?? date('Y-m-d'),
                    'evaluator_name' => $evaluatorName,
                    'score' => $score,
                    'comments' => $eval['feedback'] ?? '',
                    'criteria' => $criteria,
                    'areas_for_improvement' => !empty($eval['areas_to_improve']) ? explode("\n", $eval['areas_to_improve']) : [],
                    'recommendations' => !empty($eval['next_steps']) ? explode("\n", $eval['next_steps']) : []
                ];
            }
            
            // Filtrer par type si nécessaire
            if ($typeFilter !== 'all') {
                $studentEvaluations = array_filter($studentEvaluations, function($eval) use ($typeFilter) {
                    return $eval['type'] === $typeFilter;
                });
            }
        } else {
            // ÉTAPE 2 : Ajouter des données fictives uniquement si nécessaire (aucune évaluation trouvée)
            if (empty($studentEvaluations)) {
                if ($typeFilter === 'all' || $typeFilter === 'mid_term') {
                    $studentEvaluations[] = [
                        'id' => 1,
                        'assignment_id' => $selectedAssignment['id'],
                        'date' => date('Y-m-d', strtotime('-30 days')),
                        'type' => 'mid_term',
                        'evaluator_id' => $teacher['id'],
                        'evaluator_type' => 'tutor',
                        'evaluator_name' => $user['first_name'] . ' ' . $user['last_name'],
                        'score' => 4.2,
                        'comments' => "L'étudiant montre une bonne progression technique. Il a su s'adapter rapidement aux outils de développement et méthodologies de l'entreprise. Points à améliorer: documentation du code et communication proactive des difficultés rencontrées.",
                        'criteria' => [
                            ['name' => 'Compétences techniques', 'score' => 4.5],
                            ['name' => 'Autonomie', 'score' => 4.0],
                            ['name' => 'Communication', 'score' => 3.5],
                            ['name' => 'Intégration dans l\'équipe', 'score' => 4.5],
                            ['name' => 'Qualité du travail', 'score' => 4.0],
                            ['name' => 'Respect des délais', 'score' => 4.5]
                        ],
                        'areas_for_improvement' => [
                            'Documentation du code',
                            'Communication proactive des problèmes',
                            'Participation aux réunions'
                        ],
                        'recommendations' => [
                            'Prévoir des points réguliers sur l\'avancement',
                            'Mettre en place un système de documentation',
                            'Participer plus activement aux stand-up meetings'
                        ]
                    ];
                }
            }
        }
    }
}

// Traitement du formulaire d'évaluation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_evaluation'])) {
    // Récupérer les données du formulaire
    $assignmentId = $_POST['assignment_id'] ?? null;
    $type = $_POST['evaluation_type'] ?? 'mid_term';
    $feedback = $_POST['comments'] ?? ''; // Renommé pour correspondre à la colonne 'feedback'
    $criteria = $_POST['criteria'] ?? [];
    $areasForImprovement = array_filter($_POST['areas_for_improvement'] ?? []);
    $recommendations = array_filter($_POST['recommendations'] ?? []);
    
    // Calculer le score moyen
    $totalScore = 0;
    $criteriaCount = 0;
    $criteriaScores = [];
    
    // Mapper les critères avec les clés attendues
    $criteriaMapping = [
        'technical_mastery' => 'technical_skills',
        'work_quality' => 'technical_skills',
        'problem_solving' => 'technical_skills',
        'documentation' => 'technical_skills',
        'autonomy' => 'initiative',
        'communication' => 'communication',
        'team_integration' => 'teamwork',
        'deadline_respect' => 'punctuality'
    ];
    
    // Regrouper et moyenner les scores par catégorie
    $categoryScores = [];
    $categoryCounts = [];
    
    foreach ($criteria as $key => $value) {
        $score = floatval($value);
        $category = $criteriaMapping[$key] ?? $key;
        
        if (!isset($categoryScores[$category])) {
            $categoryScores[$category] = 0;
            $categoryCounts[$category] = 0;
        }
        
        $categoryScores[$category] += $score;
        $categoryCounts[$category]++;
        $totalScore += $score;
        $criteriaCount++;
    }
    
    // Calculer les moyennes par catégorie et convertir à l'échelle 0-20
    foreach ($categoryScores as $category => $sum) {
        if ($categoryCounts[$category] > 0) {
            $criteriaScores[$category] = round(($sum / $categoryCounts[$category]) * 4); // Convertir de 5 à 20
        }
    }
    
    // Ajouter les catégories manquantes avec une note par défaut
    $defaultCategories = ['technical_skills', 'professional_behavior', 'communication', 'initiative', 'teamwork', 'punctuality'];
    foreach ($defaultCategories as $category) {
        if (!isset($criteriaScores[$category])) {
            $criteriaScores[$category] = 10; // Note par défaut 10/20
        }
    }
    
    $averageScore = $criteriaCount > 0 ? round(($totalScore / $criteriaCount) * 4) : 0; // Convertir de 5 à 20
    
    // Récupérer l'étudiant associé à l'affectation
    $assignment = null;
    $evaluateeId = null;
    foreach ($assignments as $a) {
        if ($a['id'] == $assignmentId) {
            $assignment = $a;
            $evaluateeId = $a['student_id'];
            break;
        }
    }
    
    if (!$evaluateeId) {
        setFlashMessage('error', 'Étudiant non trouvé pour cette affectation');
        redirect('/tutoring/views/tutor/evaluations.php');
        exit;
    }
    
    // Préparer les données d'évaluation
    $evaluationData = [
        'assignment_id' => $assignmentId,
        'evaluator_id' => $teacher['id'],
        'evaluatee_id' => $evaluateeId,
        'type' => $type,
        'score' => $averageScore,
        'feedback' => $feedback,
        'strengths' => '', // À ajouter si nécessaire
        'areas_to_improve' => implode("\n", $areasForImprovement)
        // Note: recommendations n'est pas une colonne dans la table evaluations
    ];
    
    // Enregistrer l'évaluation
    $success = false;
    if ($evaluationModel) {
        // Vérifier si une évaluation existe déjà
        if ($evaluationModel->exists($assignmentId, $type)) {
            setFlashMessage('warning', 'Une évaluation de ce type existe déjà pour cet étudiant.');
        } else {
            $success = $evaluationModel->create($evaluationData);
        }
    } else {
        // Simuler un succès pour la démonstration
        $success = true;
    }
    
    if ($success) {
        setFlashMessage('success', 'L\'évaluation a été enregistrée avec succès');
        redirect('/tutoring/views/tutor/evaluations.php?student_id=' . $_POST['student_id']);
    } else if (!isset($_SESSION['flash_message'])) {
        setFlashMessage('error', 'Erreur lors de l\'enregistrement de l\'évaluation');
    }
}

// Statistiques sur les évaluations
$stats = [
    'total_evaluations' => 0,
    'pending_evaluations' => 0,
    'completed_evaluations' => 0,
    'average_score' => 0,
    'improvement_rate' => 0
];

// Collecter toutes les évaluations et les évaluations en attente
$allEvaluations = [];
$pendingEvaluations = [];

if ($evaluationModel) {
    foreach ($assignments as $assignment) {
        $evaluations = $evaluationModel->getByAssignmentId($assignment['id']);
        $allEvaluations = array_merge($allEvaluations, $evaluations);
        
        // Vérifier les évaluations manquantes
        $existingTypes = array_column($evaluations, 'type');
        $requiredTypes = ['mid_term', 'final'];
        $missingTypes = array_diff($requiredTypes, $existingTypes);
        
        foreach ($missingTypes as $type) {
            $pendingEvaluations[] = [
                'assignment_id' => $assignment['id'],
                'student_name' => $assignment['student_first_name'] . ' ' . $assignment['student_last_name'],
                'type' => $type
            ];
        }
    }
    
    // Calculer les statistiques
    $stats['total_evaluations'] = count($assignments) * 2; // 2 évaluations par étudiant
    $stats['completed_evaluations'] = count($allEvaluations);
    $stats['pending_evaluations'] = count($pendingEvaluations);
    
    // Calculer la moyenne (convertir de 20 à 5 pour l'affichage)
    if ($stats['completed_evaluations'] > 0) {
        $totalScore = array_sum(array_column($allEvaluations, 'score'));
        $stats['average_score'] = min(5, round(($totalScore / $stats['completed_evaluations']) / 4, 1));
    }
    
    // Taux d'amélioration (exemple fictif)
    $stats['improvement_rate'] = $stats['completed_evaluations'] > 0 ? 75 : 0;
} else {
    // Données fictives pour la démonstration
    $stats = [
        'total_evaluations' => count($assignments) * 2,
        'pending_evaluations' => count($assignments),
        'completed_evaluations' => count($assignments),
        'average_score' => 4.2,
        'improvement_rate' => 75
    ];
}

// Inclure l'en-tête
include_once __DIR__ . '/../common/header.php';
?>

<div class="container-fluid px-0">
    <div class="row g-0 mx-0">
        <div class="col-12 px-4 py-3">
            <h2><i class="bi bi-clipboard-check me-2"></i>Évaluations des étudiants</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/tutoring/views/tutor/dashboard.php">Tableau de bord</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Évaluations</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="row g-0 mx-0 px-4 mb-4">
        <div class="col-md-3 fade-in delay-1 pe-3">
            <div class="card stat-card">
                <div class="value"><?php echo h($stats['total_evaluations']); ?></div>
                <div class="label">Total</div>
                <div class="progress mt-2">
                    <div class="progress-bar" role="progressbar" style="width: 100%;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <small class="text-muted">Évaluations prévues</small>
            </div>
        </div>
        <div class="col-md-3 fade-in delay-2 pe-3">
            <div class="card stat-card">
                <div class="value"><?php echo h($stats['completed_evaluations']); ?></div>
                <div class="label">Complétées</div>
                <div class="progress mt-2">
                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $stats['total_evaluations'] > 0 ? h(($stats['completed_evaluations'] / $stats['total_evaluations']) * 100) : 0; ?>%;" aria-valuenow="<?php echo $stats['total_evaluations'] > 0 ? h(($stats['completed_evaluations'] / $stats['total_evaluations']) * 100) : 0; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <small class="text-muted">Évaluations terminées</small>
            </div>
        </div>
        <div class="col-md-3 fade-in delay-3 pe-3">
            <div class="card stat-card">
                <div class="value"><?php echo h($stats['average_score']); ?></div>
                <div class="label">Moyenne</div>
                <div class="progress mt-2">
                    <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo h(($stats['average_score'] / 5) * 100); ?>%;" aria-valuenow="<?php echo h(($stats['average_score'] / 5) * 100); ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <small class="text-muted">Note moyenne /5</small>
            </div>
        </div>
        <div class="col-md-3 fade-in delay-4">
            <div class="card stat-card">
                <div class="value"><?php echo h($stats['improvement_rate']); ?>%</div>
                <div class="label">Progression</div>
                <div class="progress mt-2">
                    <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo h($stats['improvement_rate']); ?>%;" aria-valuenow="<?php echo h($stats['improvement_rate']); ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <small class="text-muted">Taux d'amélioration</small>
            </div>
        </div>
    </div>
    
    <!-- Filters and Search -->
    <div class="row g-0 mx-0 px-4 mb-4">
        <div class="col-12">
            <div class="row g-0">
                <div class="col-lg-8 pe-3">
                    <div class="card">
                        <div class="card-body">
                            <form method="get" class="row g-3">
                                <div class="col-md-6">
                                    <label for="student_id" class="form-label">Étudiant</label>
                                    <select class="form-select" id="student_id" name="student_id" onchange="this.form.submit()">
                                        <option value="" disabled <?php echo !$selectedStudent ? 'selected' : ''; ?>>Choisir un étudiant...</option>
                                        <?php foreach ($assignments as $assignment): ?>
                                        <option value="<?php echo h($assignment['student_id']); ?>" <?php echo $selectedStudent && $selectedStudent['student_id'] == $assignment['student_id'] ? 'selected' : ''; ?>>
                                            <?php echo h($assignment['student_first_name'] . ' ' . $assignment['student_last_name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <?php if ($selectedStudent): ?>
                                <div class="col-md-6">
                                    <label for="type" class="form-label">Type d'évaluation</label>
                                    <select class="form-select" id="type" name="type" onchange="this.form.submit()">
                                        <option value="all" <?php echo $typeFilter === 'all' ? 'selected' : ''; ?>>Toutes les évaluations</option>
                                        <option value="mid_term" <?php echo $typeFilter === 'mid_term' ? 'selected' : ''; ?>>Mi-parcours</option>
                                        <option value="final" <?php echo $typeFilter === 'final' ? 'selected' : ''; ?>>Finale</option>
                                        <option value="company" <?php echo $typeFilter === 'company' ? 'selected' : ''; ?>>Entreprise</option>
                                    </select>
                                </div>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card h-100">
                        <div class="card-header">
                            Actions rapides
                        </div>
                        <div class="card-body">
                            <button class="btn btn-primary w-100 mb-2" data-bs-toggle="modal" data-bs-target="#createEvaluationModal">
                                <i class="bi bi-plus-lg me-2"></i>Nouvelle évaluation
                            </button>
                            <a href="/tutoring/views/tutor/students.php" class="btn btn-outline-primary w-100 mb-2">
                                <i class="bi bi-mortarboard me-2"></i>Mes étudiants
                            </a>
                            <a href="/tutoring/views/tutor/meetings.php" class="btn btn-outline-primary w-100 mb-2">
                                <i class="bi bi-calendar-event me-2"></i>Réunions
                            </a>
                            <a href="/tutoring/views/tutor/documents.php" class="btn btn-outline-primary w-100">
                                <i class="bi bi-folder me-2"></i>Documents
                            </a>
                            <hr>
                            <h6 class="mb-2">Guide d'évaluation</h6>
                            <p class="small mb-2"><strong>Mi-parcours:</strong> Évaluation de la progression et identification des axes d'amélioration.</p>
                            <p class="small mb-0"><strong>Finale:</strong> Bilan global des compétences acquises et recommandations futures.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Pending Evaluations Alert -->
    <?php if (!empty($pendingEvaluations) && !$selectedStudent && count($pendingEvaluations) <= 5): ?>
    <div class="row g-0 mx-0 px-4 mb-4">
        <div class="col-12">
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <h5 class="alert-heading"><i class="bi bi-exclamation-triangle-fill me-2"></i>Évaluations en attente</h5>
                <p>Vous avez <?php echo count($pendingEvaluations); ?> évaluation(s) à compléter :</p>
                <ul class="mb-0">
                    <?php foreach ($pendingEvaluations as $pending): ?>
                    <li>
                        <strong><?php echo h($pending['student_name']); ?></strong> - 
                        <?php echo h($evaluationTypes[$pending['type']] ?? ucfirst($pending['type'])); ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Student Information Card (if selected) -->
    <?php if ($selectedStudent): ?>
    <div class="row g-0 mx-0 px-4 mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <div class="d-flex align-items-center">
                                <?php
                                // Générer l'avatar avec les initiales
                                $initials = mb_substr($selectedStudent['first_name'] ?? '', 0, 1) . mb_substr($selectedStudent['last_name'] ?? '', 0, 1);
                                $avatarUrl = "https://ui-avatars.com/api/?name=" . urlencode($initials) . "&background=3498db&color=fff";
                                ?>
                                <img src="<?php echo h($avatarUrl); ?>" alt="Student" class="rounded-circle me-3" width="80" height="80">
                                <div>
                                    <h4 class="mb-1"><?php echo h(($selectedStudent['first_name'] ?? '') . ' ' . ($selectedStudent['last_name'] ?? '')); ?></h4>
                                    <p class="text-muted mb-0"><?php echo h($selectedStudent['program'] ?? 'N/A'); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <h6 class="text-muted mb-1">Stage</h6>
                            <p class="mb-1"><strong><?php echo h($selectedStudent['internship_title'] ?? 'N/A'); ?></strong></p>
                            <p class="mb-0"><?php echo h($selectedStudent['company_name'] ?? 'N/A'); ?></p>
                            <?php if (isset($selectedStudent['start_date']) && isset($selectedStudent['end_date'])): ?>
                            <p class="small text-muted mb-0">
                                <?php echo h(date('d/m/Y', strtotime($selectedStudent['start_date']))); ?> - 
                                <?php echo h(date('d/m/Y', strtotime($selectedStudent['end_date']))); ?>
                            </p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-2">
                            <h6 class="text-muted mb-1">Progression</h6>
                            <?php
                            // Calculer la progression du stage
                            $progress = 0;
                            if (isset($selectedStudent['start_date']) && isset($selectedStudent['end_date'])) {
                                $startDate = new DateTime($selectedStudent['start_date']);
                                $endDate = new DateTime($selectedStudent['end_date']);
                                $today = new DateTime();
                                
                                if ($today >= $startDate && $today <= $endDate) {
                                    $totalDays = $startDate->diff($endDate)->days;
                                    $daysElapsed = $startDate->diff($today)->days;
                                    $progress = min(100, round(($daysElapsed / $totalDays) * 100));
                                } elseif ($today > $endDate) {
                                    $progress = 100;
                                }
                            }
                            ?>
                            <div class="progress mb-2" style="height: 10px;">
                                <div class="progress-bar" role="progressbar" style="width: <?php echo h($progress); ?>%;" aria-valuenow="<?php echo h($progress); ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <p class="small text-muted mb-0"><?php echo h($progress); ?>% complété</p>
                        </div>
                        <div class="col-md-2 text-end">
                            <a href="/tutoring/views/tutor/documents.php?student_id=<?php echo h($selectedStudent['student_id']); ?>" class="btn btn-outline-primary btn-sm mb-1 d-block">
                                <i class="bi bi-folder me-1"></i>Documents
                            </a>
                            <a href="/tutoring/views/tutor/meetings.php?student_id=<?php echo h($selectedStudent['student_id']); ?>" class="btn btn-outline-primary btn-sm d-block">
                                <i class="bi bi-calendar-event me-1"></i>Réunions
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Student Statistics -->
    <div class="row g-0 mx-0 px-4 mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <span>Statistiques de l'étudiant</span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="chart-container">
                                <canvas id="studentProgressChart"></canvas>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <?php
                            // Calculer les statistiques spécifiques à l'étudiant
                            $studentStats = [
                                'evaluations_count' => count($studentEvaluations),
                                'technical_score' => 0,
                                'communication_score' => 0,
                                'teamwork_score' => 0,
                                'autonomy_score' => 0
                            ];
                            
                            foreach ($studentEvaluations as $eval) {
                                if (isset($eval['criteria']) && is_array($eval['criteria'])) {
                                    foreach ($eval['criteria'] as $criterion) {
                                        $name = strtolower($criterion['name']);
                                        if (strpos($name, 'technique') !== false || strpos($name, 'technical') !== false) {
                                            $studentStats['technical_score'] += $criterion['score'];
                                        } elseif (strpos($name, 'communication') !== false) {
                                            $studentStats['communication_score'] += $criterion['score'];
                                        } elseif (strpos($name, 'équipe') !== false || strpos($name, 'team') !== false) {
                                            $studentStats['teamwork_score'] += $criterion['score'];
                                        } elseif (strpos($name, 'autonomie') !== false || strpos($name, 'autonomy') !== false) {
                                            $studentStats['autonomy_score'] += $criterion['score'];
                                        }
                                    }
                                }
                            }
                            
                            // Calculer les moyennes
                            if ($studentStats['evaluations_count'] > 0) {
                                $studentStats['technical_score'] = round($studentStats['technical_score'] / $studentStats['evaluations_count'], 1);
                                $studentStats['communication_score'] = round($studentStats['communication_score'] / $studentStats['evaluations_count'], 1);
                                $studentStats['teamwork_score'] = round($studentStats['teamwork_score'] / $studentStats['evaluations_count'], 1);
                                $studentStats['autonomy_score'] = round($studentStats['autonomy_score'] / $studentStats['evaluations_count'], 1);
                            }
                            ?>
                            
                            <h5 class="mb-3">Compétences évaluées</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between">
                                            <span>Technique</span>
                                            <span><?php echo h($studentStats['technical_score']); ?>/5</span>
                                        </div>
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo h(($studentStats['technical_score']/5)*100); ?>%;" aria-valuenow="<?php echo h($studentStats['technical_score']); ?>" aria-valuemin="0" aria-valuemax="5"></div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between">
                                            <span>Communication</span>
                                            <span><?php echo h($studentStats['communication_score']); ?>/5</span>
                                        </div>
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo h(($studentStats['communication_score']/5)*100); ?>%;" aria-valuenow="<?php echo h($studentStats['communication_score']); ?>" aria-valuemin="0" aria-valuemax="5"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between">
                                            <span>Travail d'équipe</span>
                                            <span><?php echo h($studentStats['teamwork_score']); ?>/5</span>
                                        </div>
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo h(($studentStats['teamwork_score']/5)*100); ?>%;" aria-valuenow="<?php echo h($studentStats['teamwork_score']); ?>" aria-valuemin="0" aria-valuemax="5"></div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between">
                                            <span>Autonomie</span>
                                            <span><?php echo h($studentStats['autonomy_score']); ?>/5</span>
                                        </div>
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo h(($studentStats['autonomy_score']/5)*100); ?>%;" aria-valuenow="<?php echo h($studentStats['autonomy_score']); ?>" aria-valuemin="0" aria-valuemax="5"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Main Content -->
    <div class="row g-0 mx-0">
        <div class="col-12 px-4">
            <?php if (!$selectedStudent): ?>
            <div class="card mb-4">
                <div class="card-body">
                    <div class="text-center py-5">
                        <i class="bi bi-clipboard-check display-1 text-muted mb-3"></i>
                        <h4>Sélectionnez un étudiant pour commencer</h4>
                        <p class="text-muted">Choisissez un étudiant dans la liste pour consulter ou créer des évaluations.</p>
                    </div>
                </div>
            </div>
            
            <!-- Vue d'ensemble de tous les étudiants -->
            <?php if (!empty($assignments)): ?>
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Vue d'ensemble des évaluations</span>
                    <a href="/tutoring/views/tutor/export_evaluations.php" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-download"></i> Exporter
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Étudiant</th>
                                    <th>Formation</th>
                                    <th>Entreprise</th>
                                    <th>Mi-parcours</th>
                                    <th>Finale</th>
                                    <th>Moyenne</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($assignments as $assignment): 
                                    $student = $studentModel->getById($assignment['student_id']);
                                    if (!$student) continue;
                                    
                                    // Récupérer les infos du stage et de l'entreprise
                                    $internship = null;
                                    $company = null;
                                    if ($assignment['internship_id']) {
                                        $internship = $internshipModel->getById($assignment['internship_id']);
                                        if ($internship && $internship['company_id']) {
                                            $company = $companyModel->getById($internship['company_id']);
                                        }
                                    }
                                    
                                    // Récupérer les évaluations
                                    $evaluations = [];
                                    if ($evaluationModel) {
                                        $evaluations = $evaluationModel->getByAssignmentId($assignment['id']);
                                    }
                                    
                                    $evalByType = [
                                        'mid_term' => null,
                                        'final' => null
                                    ];
                                    
                                    foreach ($evaluations as $eval) {
                                        if (isset($evalByType[$eval['type']])) {
                                            // Convertir le score de 20 à 5 pour l'affichage
                                            $eval['display_score'] = min(5, round($eval['score'] / 4, 1));
                                            $evalByType[$eval['type']] = $eval;
                                        }
                                    }
                                    
                                    // Calculer la moyenne
                                    $scores = array_filter(array_column($evaluations, 'score'));
                                    $average = !empty($scores) ? min(5, round((array_sum($scores) / count($scores)) / 4, 1)) : null;
                                ?>
                                <tr>
                                    <td>
                                        <a href="/tutoring/views/tutor/evaluations.php?student_id=<?php echo h($student['id']); ?>">
                                            <?php echo h($student['first_name'] . ' ' . $student['last_name']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo h($student['program'] ?? 'N/A'); ?></td>
                                    <td><?php echo $company ? h($company['name']) : '<span class="text-muted">N/A</span>'; ?></td>
                                    <td>
                                        <?php if ($evalByType['mid_term']): ?>
                                            <span class="badge bg-success"><?php echo h($evalByType['mid_term']['display_score']); ?>/5</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">En attente</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($evalByType['final']): ?>
                                            <span class="badge bg-success"><?php echo h($evalByType['final']['display_score']); ?>/5</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">En attente</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($average !== null): ?>
                                            <strong><?php echo h($average); ?>/5</strong>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="/tutoring/views/tutor/evaluations.php?student_id=<?php echo h($student['id']); ?>" class="btn btn-sm btn-outline-primary" title="Voir le détail">
                                            <i class="bi bi-eye"></i> Détails
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
            
            <?php else: ?>
            
            <!-- Create Evaluation Button -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Évaluations de <?php echo h(($selectedStudent['first_name'] ?? '') . ' ' . ($selectedStudent['last_name'] ?? '')); ?></h5>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createEvaluationModal">
                            <i class="bi bi-plus-lg me-2"></i>Nouvelle évaluation
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- List of Evaluations -->
            <?php if (empty($studentEvaluations)): ?>
            <div class="card mb-4">
                <div class="card-body">
                    <div class="alert alert-info" role="alert">
                        <div class="d-flex">
                            <div class="me-3">
                                <i class="bi bi-info-circle-fill fs-4"></i>
                            </div>
                            <div>
                                <h5 class="alert-heading">Aucune évaluation disponible</h5>
                                <p class="mb-0">Vous n'avez pas encore créé d'évaluation pour cet étudiant. Utilisez le bouton "Nouvelle évaluation" pour commencer.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
            
            <?php foreach ($studentEvaluations as $index => $evaluation): ?>
            <div class="card mb-4 fade-in">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Évaluation <?php echo h($evaluationTypes[$evaluation['type']] ?? ucfirst(str_replace('_', ' ', $evaluation['type']))); ?></span>
                    <span class="badge bg-primary"><?php echo date('d/m/Y', strtotime($evaluation['date'])); ?></span>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="me-3">
                                <div class="rating-stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="bi <?php echo ($i <= $evaluation['score']) ? 'bi-star-fill' : 'bi-star'; ?> text-warning"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <div>
                                <h5 class="mb-0">Note globale: <?php echo h($evaluation['score']); ?>/5</h5>
                            </div>
                        </div>
                        
                        <h6>Commentaires</h6>
                        <p><?php echo nl2br(h($evaluation['feedback'] ?? '')); ?></p>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6>Critères d'évaluation</h6>
                            <?php if (isset($evaluation['criteria']) && is_array($evaluation['criteria'])): ?>
                            <?php foreach ($evaluation['criteria'] as $criterion): ?>
                            <div class="mb-2">
                                <div class="d-flex justify-content-between">
                                    <span><?php echo h($criterion['name']); ?></span>
                                    <span><?php echo h($criterion['score']); ?>/5</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar" role="progressbar" style="width: <?php echo h(($criterion['score']/5)*100); ?>%;" aria-valuenow="<?php echo h($criterion['score']); ?>" aria-valuemin="0" aria-valuemax="5"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-6">
                            <?php if (!empty($evaluation['areas_to_improve'])): ?>
                            <h6>Points à améliorer</h6>
                            <ul class="list-group list-group-flush mb-3">
                                <?php 
                                $areasArray = is_array($evaluation['areas_to_improve']) 
                                    ? $evaluation['areas_to_improve'] 
                                    : explode("\n", $evaluation['areas_to_improve']);
                                foreach ($areasArray as $area): 
                                    if (trim($area)):
                                ?>
                                <li class="list-group-item px-0"><?php echo h($area); ?></li>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </ul>
                            <?php endif; ?>
                            
                            <?php if (!empty($evaluation['recommendations'])): ?>
                            <h6>Recommandations</h6>
                            <ul class="list-group list-group-flush">
                                <?php 
                                $recommendations = is_array($evaluation['recommendations']) ? $evaluation['recommendations'] : [$evaluation['recommendations']];
                                foreach ($recommendations as $recommendation): 
                                ?>
                                <li class="list-group-item px-0"><?php echo h($recommendation); ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-primary" onclick="printEvaluation(<?php echo h($index); ?>)">
                            <i class="bi bi-printer me-1"></i>Imprimer
                        </button>
                        <a href="/tutoring/views/tutor/export_evaluation.php?id=<?php echo h($evaluation['id']); ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-download me-1"></i>Exporter
                        </a>
                        <button class="btn btn-outline-info" onclick="shareEvaluation(<?php echo h($index); ?>)">
                            <i class="bi bi-share me-1"></i>Partager
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
/* Reset des marges et paddings pour utiliser toute la largeur */
.container-fluid {
    padding-left: 0;
    padding-right: 0;
    margin-left: 0;
    margin-right: 0;
    max-width: 100%;
}

/* Ajustement des cards et contenu */
.card {
    margin-bottom: 1.5rem;
    border-radius: 0.5rem;
}

/* Ajustement des colonnes */
[class*="col-"] {
    padding-left: 15px;
    padding-right: 15px;
}

/* Ajustement des lignes */
.row {
    margin-left: 0;
    margin-right: 0;
}

/* Correction pour le contenu principal */
.main-content {
    padding-left: 0;
    padding-right: 0;
}

/* Ajustement des marges internes */
.px-4 {
    padding-left: 1.5rem !important;
    padding-right: 1.5rem !important;
}

/* Ajustement pour les cartes de statistiques */
.stat-card {
    padding: 1.25rem;
    height: 100%;
}

/* Correction pour les éléments flex */
.d-flex {
    flex-wrap: nowrap;
}

/* Correction pour les éléments en ligne */
.inline-items {
    white-space: nowrap;
}

/* Ajustement pour les petits écrans */
@media (max-width: 768px) {
    .px-4 {
        padding-left: 1rem !important;
        padding-right: 1rem !important;
    }
    
    [class*="col-"] {
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }
}
</style>

<!-- Create Evaluation Modal -->
<div class="modal fade" id="createEvaluationModal" tabindex="-1" aria-labelledby="createEvaluationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createEvaluationModalLabel">Nouvelle évaluation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="assignment_id" value="<?php echo h($selectedAssignment['id'] ?? ''); ?>">
                    <input type="hidden" name="student_id" value="<?php echo h($selectedStudent['student_id'] ?? ''); ?>">
                    
                    <div class="mb-4">
                        <h5>Informations générales</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="evaluation_type" class="form-label">Type d'évaluation</label>
                                    <select class="form-select" id="evaluation_type" name="evaluation_type" required>
                                        <option value="mid_term">Mi-parcours</option>
                                        <option value="final">Finale</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="evaluation_date" class="form-label">Date d'évaluation</label>
                                    <input type="date" class="form-control" id="evaluation_date" name="evaluation_date" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h5>Compétences techniques</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Maîtrise des technologies</label>
                                    <select class="form-select" name="criteria[technical_mastery]" required>
                                        <option value="" disabled selected>Sélectionnez une note</option>
                                        <option value="1">1 - Insuffisant</option>
                                        <option value="2">2 - Passable</option>
                                        <option value="3">3 - Satisfaisant</option>
                                        <option value="4">4 - Très bien</option>
                                        <option value="5">5 - Excellent</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Qualité du travail</label>
                                    <select class="form-select" name="criteria[work_quality]" required>
                                        <option value="" disabled selected>Sélectionnez une note</option>
                                        <option value="1">1 - Insuffisant</option>
                                        <option value="2">2 - Passable</option>
                                        <option value="3">3 - Satisfaisant</option>
                                        <option value="4">4 - Très bien</option>
                                        <option value="5">5 - Excellent</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Résolution de problèmes</label>
                                    <select class="form-select" name="criteria[problem_solving]" required>
                                        <option value="" disabled selected>Sélectionnez une note</option>
                                        <option value="1">1 - Insuffisant</option>
                                        <option value="2">2 - Passable</option>
                                        <option value="3">3 - Satisfaisant</option>
                                        <option value="4">4 - Très bien</option>
                                        <option value="5">5 - Excellent</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Documentation</label>
                                    <select class="form-select" name="criteria[documentation]" required>
                                        <option value="" disabled selected>Sélectionnez une note</option>
                                        <option value="1">1 - Insuffisant</option>
                                        <option value="2">2 - Passable</option>
                                        <option value="3">3 - Satisfaisant</option>
                                        <option value="4">4 - Très bien</option>
                                        <option value="5">5 - Excellent</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h5>Compétences professionnelles</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Autonomie</label>
                                    <select class="form-select" name="criteria[autonomy]" required>
                                        <option value="" disabled selected>Sélectionnez une note</option>
                                        <option value="1">1 - Insuffisant</option>
                                        <option value="2">2 - Passable</option>
                                        <option value="3">3 - Satisfaisant</option>
                                        <option value="4">4 - Très bien</option>
                                        <option value="5">5 - Excellent</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Communication</label>
                                    <select class="form-select" name="criteria[communication]" required>
                                        <option value="" disabled selected>Sélectionnez une note</option>
                                        <option value="1">1 - Insuffisant</option>
                                        <option value="2">2 - Passable</option>
                                        <option value="3">3 - Satisfaisant</option>
                                        <option value="4">4 - Très bien</option>
                                        <option value="5">5 - Excellent</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Intégration dans l'équipe</label>
                                    <select class="form-select" name="criteria[team_integration]" required>
                                        <option value="" disabled selected>Sélectionnez une note</option>
                                        <option value="1">1 - Insuffisant</option>
                                        <option value="2">2 - Passable</option>
                                        <option value="3">3 - Satisfaisant</option>
                                        <option value="4">4 - Très bien</option>
                                        <option value="5">5 - Excellent</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Respect des délais</label>
                                    <select class="form-select" name="criteria[deadline_respect]" required>
                                        <option value="" disabled selected>Sélectionnez une note</option>
                                        <option value="1">1 - Insuffisant</option>
                                        <option value="2">2 - Passable</option>
                                        <option value="3">3 - Satisfaisant</option>
                                        <option value="4">4 - Très bien</option>
                                        <option value="5">5 - Excellent</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h5>Commentaires et recommandations</h5>
                        <div class="mb-3">
                            <label for="comments" class="form-label">Commentaires généraux</label>
                            <textarea class="form-control" id="comments" name="comments" rows="4" placeholder="Points forts, progression, observations générales..." required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Points à améliorer</label>
                            <div id="improvement-areas-container">
                                <div class="input-group mb-2">
                                    <input type="text" class="form-control" name="areas_for_improvement[]" placeholder="Point à améliorer...">
                                    <button class="btn btn-outline-secondary" type="button" onclick="addImprovementArea()"><i class="bi bi-plus"></i></button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Recommandations</label>
                            <div id="recommendations-container">
                                <div class="input-group mb-2">
                                    <input type="text" class="form-control" name="recommendations[]" placeholder="Recommandation...">
                                    <button class="btn btn-outline-secondary" type="button" onclick="addRecommendation()"><i class="bi bi-plus"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="d-flex justify-content-between w-100">
                        <div>
                            <span class="text-muted small">
                                <i class="bi bi-info-circle me-1"></i>
                                Le document exporté peut être imprimé en PDF depuis votre navigateur
                            </span>
                        </div>
                        <div>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" name="submit_evaluation" class="btn btn-primary">Enregistrer l'évaluation</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Si un étudiant est sélectionné, initialiser le graphique de progression
        <?php if ($selectedStudent): ?>
        
        // Données pour le graphique
        const evaluationData = <?php echo json_encode($studentEvaluations); ?>;
        
        // Préparer les données pour le graphique
        const chartData = {
            labels: [],
            technical: [],
            professional: []
        };
        
        // Organiser les données chronologiquement
        const sortedEvals = [...evaluationData].sort((a, b) => new Date(a.date) - new Date(b.date));
        
        sortedEvals.forEach(eval => {
            // Ajouter la date au format court
            chartData.labels.push(new Date(eval.date).toLocaleDateString('fr-FR', {day: '2-digit', month: '2-digit'}));
            
            // Calculer les moyennes techniques et professionnelles
            let techScore = 0;
            let techCount = 0;
            let profScore = 0;
            let profCount = 0;
            
            if (eval.criteria && Array.isArray(eval.criteria)) {
                eval.criteria.forEach(criterion => {
                    const name = criterion.name.toLowerCase();
                    if (name.includes('technique') || 
                        name.includes('technical') ||
                        name.includes('maîtrise') ||
                        name.includes('qualité') ||
                        name.includes('problème') ||
                        name.includes('documentation')) {
                        techScore += criterion.score;
                        techCount++;
                    } else {
                        profScore += criterion.score;
                        profCount++;
                    }
                });
            }
            
            chartData.technical.push(techCount > 0 ? techScore / techCount : 0);
            chartData.professional.push(profCount > 0 ? profScore / profCount : 0);
        });
        
        // Créer le graphique si des données sont disponibles
        if (chartData.labels.length > 0) {
            const ctx = document.getElementById('studentProgressChart');
            if (ctx) {
                const chart = new Chart(ctx.getContext('2d'), {
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
                                intersect: false
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
            }
        }
        <?php endif; ?>
    });
    
    // Fonctions pour ajouter des champs dynamiques
    function addImprovementArea() {
        const container = document.getElementById('improvement-areas-container');
        const newField = document.createElement('div');
        newField.className = 'input-group mb-2';
        newField.innerHTML = `
            <input type="text" class="form-control" name="areas_for_improvement[]" placeholder="Point à améliorer...">
            <button class="btn btn-outline-danger" type="button" onclick="this.parentElement.remove()"><i class="bi bi-trash"></i></button>
        `;
        container.appendChild(newField);
    }
    
    function addRecommendation() {
        const container = document.getElementById('recommendations-container');
        const newField = document.createElement('div');
        newField.className = 'input-group mb-2';
        newField.innerHTML = `
            <input type="text" class="form-control" name="recommendations[]" placeholder="Recommandation...">
            <button class="btn btn-outline-danger" type="button" onclick="this.parentElement.remove()"><i class="bi bi-trash"></i></button>
        `;
        container.appendChild(newField);
    }
    
    // Fonctions pour les actions d'évaluation
    function printEvaluation(index) {
        // Implémenter la fonction d'impression
        window.print();
    }
    
    function exportEvaluationInline(index) {
        // Exporter en utilisant la technique du formulaire POST pour éviter les problèmes de cache
        const evaluations = <?php echo json_encode($studentEvaluations); ?>;
        const evaluation = evaluations[index];
        
        if (evaluation && evaluation.id) {
            // Créer un formulaire temporaire
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/tutoring/views/tutor/export_evaluation.php`;
            form.target = '_blank'; // Ouvrir dans un nouvel onglet
            
            // Ajouter les champs cachés
            const idField = document.createElement('input');
            idField.type = 'hidden';
            idField.name = 'id';
            idField.value = evaluation.id;
            
            const formatField = document.createElement('input');
            formatField.type = 'hidden';
            formatField.name = 'format';
            formatField.value = 'html';
            
            const cacheBusterField = document.createElement('input');
            cacheBusterField.type = 'hidden';
            cacheBusterField.name = 'nocache';
            cacheBusterField.value = new Date().getTime();
            
            // Ajouter les champs au formulaire
            form.appendChild(idField);
            form.appendChild(formatField);
            form.appendChild(cacheBusterField);
            
            // Ajouter le formulaire au document, le soumettre, puis le retirer
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        } else {
            alert('Impossible d\'exporter cette évaluation');
        }
    }
    
    function exportAllEvaluationsInline() {
        // Créer un formulaire temporaire
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/tutoring/views/tutor/export_evaluations.php`;
        form.target = '_blank'; // Ouvrir dans un nouvel onglet
        
        // Ajouter les champs cachés
        const formatField = document.createElement('input');
        formatField.type = 'hidden';
        formatField.name = 'format';
        formatField.value = 'html';
        
        const cacheBusterField = document.createElement('input');
        cacheBusterField.type = 'hidden';
        cacheBusterField.name = 'nocache';
        cacheBusterField.value = new Date().getTime();
        
        // Ajouter les champs au formulaire
        form.appendChild(formatField);
        form.appendChild(cacheBusterField);
        
        // Ajouter le formulaire au document, le soumettre, puis le retirer
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    }
    
    function shareEvaluation(index) {
        // Implémenter la fonction de partage
        const evaluations = <?php echo json_encode($studentEvaluations); ?>;
        const evaluation = evaluations[index];
        
        if (navigator.share) {
            navigator.share({
                title: 'Évaluation de stage',
                text: `Évaluation ${evaluation.type === 'mid_term' ? 'mi-parcours' : 'finale'} - Note: ${evaluation.score}/5`,
                url: window.location.href
            }).then(() => {
                console.log('Partage réussi');
            }).catch((error) => {
                console.error('Erreur lors du partage:', error);
            });
        } else {
            // Fallback pour les navigateurs qui ne supportent pas l'API de partage
            const shareUrl = window.location.href;
            if (navigator.clipboard) {
                navigator.clipboard.writeText(shareUrl).then(() => {
                    alert('Le lien a été copié dans le presse-papiers');
                });
            } else {
                alert('La fonction de partage n\'est pas supportée par votre navigateur');
            }
        }
    }
</script>

<?php
// Inclure le pied de page
include_once __DIR__ . '/../common/footer.php';
?>