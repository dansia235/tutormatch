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

// Debug: Log the number of assignments
error_log("DEBUG evaluations.php - Teacher ID: " . $teacher['id'] . ", Number of assignments: " . count($assignments));
foreach ($assignments as $idx => $assignment) {
    error_log("DEBUG evaluations.php - Assignment $idx: ID=" . $assignment['id'] . ", Student=" . $assignment['student_first_name'] . " " . $assignment['student_last_name']);
}

// Modèles nécessaires
$internshipModel = new Internship($db);
$studentModel = new Student($db);
$companyModel = new Company($db);
$evaluationModel = null;

// Vérifier si la classe Evaluation existe
if (class_exists('Evaluation')) {
    $evaluationModel = new Evaluation($db);
}

// Fonction d'aide pour normaliser les types d'évaluation
function normalizeEvaluationType($type) {
    switch (strtolower($type)) {
        case 'mid-term':
        case 'midterm':
        case 'mid_term':
            return 'mid_term';
        case 'self':
        case 'self_evaluation':
        case 'student':
            return 'student';
        default:
            return $type;
    }
}

// Initialiser les variables
$selectedStudent = null;
$selectedAssignment = null;
$studentEvaluations = [];
// Types d'évaluation - inclure toutes les variations possibles
$evaluationTypes = [
    'mid_term' => 'Mi-parcours', 
    'mid-term' => 'Mi-parcours', 
    'midterm' => 'Mi-parcours',
    'final' => 'Finale', 
    'student' => 'Auto-évaluation',
    'self' => 'Auto-évaluation'
];

// Filtre par étudiant
$studentFilter = $_GET['student_id'] ?? null;
$typeFilter = $_GET['type'] ?? 'all';
$normalizedTypeFilter = normalizeEvaluationType($typeFilter);
error_log("Type filter: " . $typeFilter . ", Normalized: " . $normalizedTypeFilter);

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
        
        // Récupérer les évaluations de cet étudiant en utilisant la même méthode que le dashboard
        if ($selectedStudent) {
            $studentId = isset($selectedStudent['student_id']) ? $selectedStudent['student_id'] : $selectedStudent['id'];
            
            if ($evaluationModel) {
                // Utiliser la même approche que le dashboard: récupérer par teacher_id et filtrer par étudiant
                $allEvaluations = $evaluationModel->getByTeacherId($teacher['id']);
                
                // Créer un modèle d'assignment pour les jointures
                $assignmentModel = new Assignment($db);
                
                // Filtrer pour l'étudiant sélectionné
                $studentEvaluations = [];
                foreach ($allEvaluations as $evaluation) {
                    // Récupérer l'affectation pour trouver l'étudiant
                    $assignment = $assignmentModel->getById($evaluation['assignment_id']);
                    if ($assignment && $assignment['student_id'] == $studentId) {
                        // S'assurer que le score est sur une échelle de 5
                        $scoreOn5 = $evaluation['score'];
                        if ($scoreOn5 > 5) {
                            $scoreOn5 = number_format($scoreOn5 / 4, 1);
                        }
                        
                        // Déterminer le type d'évaluation
                        $evaluationType = '';
                        switch($evaluation['type']) {
                            case 'mid_term': $evaluationType = 'Mi-parcours'; break;
                            case 'final': $evaluationType = 'Finale'; break;
                            case 'student': $evaluationType = 'Auto-évaluation'; break;
                            case 'supervisor': $evaluationType = 'Superviseur'; break;
                            case 'teacher': $evaluationType = 'Tuteur'; break;
                            default: $evaluationType = ucfirst(str_replace('_', ' ', $evaluation['type'])); break;
                        }
                        
                        // Décoder les critères si nécessaire
                        $criteriaScores = $evaluation['criteria_scores'];
                        if (is_string($criteriaScores)) {
                            $criteriaScores = json_decode($criteriaScores, true);
                        }
                        
                        // Préparer les critères pour l'affichage
                        $criteria = [];
                        if (is_array($criteriaScores)) {
                            foreach ($criteriaScores as $key => $value) {
                                $score = is_array($value) ? ($value['score'] ?? 0) : $value;
                                $criteria[] = [
                                    'name' => ucfirst(str_replace('_', ' ', $key)),
                                    'score' => $score,
                                    'comment' => is_array($value) ? ($value['comment'] ?? '') : ''
                                ];
                            }
                        }
                        
                        // Créer l'évaluation formatée
                        $formattedEvaluation = [
                            'id' => $evaluation['id'],
                            'assignment_id' => $evaluation['assignment_id'],
                            'type' => $evaluation['type'],
                            'type_name' => $evaluationType,
                            'status' => $evaluation['status'] ?? 'submitted',
                            'score' => floatval($scoreOn5),
                            'technical_avg' => floatval($evaluation['technical_avg'] ?? 0),
                            'professional_avg' => floatval($evaluation['professional_avg'] ?? 0),
                            'criteria_scores' => $criteriaScores ?? [],
                            'criteria' => $criteria,
                            'comments' => $evaluation['comments'] ?? '',
                            'strengths' => $evaluation['strengths'] ?? '',
                            'areas_for_improvement' => $evaluation['areas_for_improvement'] ?? '',
                            'next_steps' => $evaluation['next_steps'] ?? '',
                            'submission_date' => $evaluation['submission_date'] ?? $evaluation['created_at'] ?? date('Y-m-d'),
                            'date' => $evaluation['submission_date'] ?? $evaluation['created_at'] ?? date('Y-m-d'),
                            'evaluator_name' => $user['first_name'] . ' ' . $user['last_name']
                        ];
                        
                        // Filtrer par type si nécessaire et exclure les évaluations de type "company"
                        if ($evaluation['type'] !== 'company' && $evaluation['type'] !== 'enterprise' && 
                            ($typeFilter === 'all' || normalizeEvaluationType($evaluation['type']) === $normalizedTypeFilter)) {
                            $studentEvaluations[] = $formattedEvaluation;
                        }
                    }
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
    // Récupérer toutes les évaluations liées à ce tuteur
    $allEvaluations = $evaluationModel->getByTeacherId($teacher['id']);
    
    // Identifier les évaluations en attente
    foreach ($assignments as $assignment) {
        $assignmentEvaluations = array_filter($allEvaluations, function($eval) use ($assignment) {
            return $eval['assignment_id'] == $assignment['id'];
        });
        
        // Extraire les types d'évaluation existants pour cette affectation
        $existingTypes = array_column($assignmentEvaluations, 'type');
        
        // Types d'évaluation requis pour chaque étudiant
        $requiredTypes = ['mid_term', 'final'];
        
        // Trouver les types manquants
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
    $stats['total_evaluations'] = count($assignments) * 2; // 2 évaluations par étudiant (mi-parcours et finale)
    $stats['completed_evaluations'] = count($allEvaluations);
    $stats['pending_evaluations'] = count($pendingEvaluations);
    
    // Calculer la moyenne des scores (s'assurer qu'elle est sur une échelle de 5)
    if ($stats['completed_evaluations'] > 0) {
        $scores = array_column($allEvaluations, 'score');
        $totalScore = array_sum($scores);
        
        // Convertir de 20 à 5 si nécessaire
        if (max($scores) > 5) {
            $stats['average_score'] = round(($totalScore / $stats['completed_evaluations']) / 4, 1);
        } else {
            $stats['average_score'] = round($totalScore / $stats['completed_evaluations'], 1);
        }
    }
    
    // Calculer le taux d'amélioration en comparant les évaluations mi-parcours et finales
    if ($stats['completed_evaluations'] > 0) {
        $midTermScores = [];
        $finalScores = [];
        
        // Collecter les scores par affectation et type
        foreach ($allEvaluations as $eval) {
            $assignmentId = $eval['assignment_id'];
            $type = $eval['type'];
            $score = $eval['score'];
            
            // Convertir à l'échelle de 5 si nécessaire
            if ($score > 5) {
                $score = $score / 4;
            }
            
            if ($type === 'mid_term') {
                $midTermScores[$assignmentId] = $score;
            } else if ($type === 'final') {
                $finalScores[$assignmentId] = $score;
            }
        }
        
        // Calculer le taux d'amélioration pour les affectations ayant les deux types d'évaluation
        $improvementCount = 0;
        $totalComparisons = 0;
        
        foreach ($midTermScores as $assignmentId => $midTermScore) {
            if (isset($finalScores[$assignmentId])) {
                $totalComparisons++;
                if ($finalScores[$assignmentId] > $midTermScore) {
                    $improvementCount++;
                }
            }
        }
        
        // Calculer le pourcentage d'amélioration
        $stats['improvement_rate'] = $totalComparisons > 0 ? round(($improvementCount / $totalComparisons) * 100) : 0;
    } else {
        $stats['improvement_rate'] = 0;
    }
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
                                        <option value="student" <?php echo $typeFilter === 'student' ? 'selected' : ''; ?>>Auto-évaluation</option>
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
                            <p class="small mb-2"><strong>Finale:</strong> Bilan global des compétences acquises et recommandations futures.</p>
                            <p class="small mb-0 text-muted"><strong>Maximum:</strong> 2 évaluations par étudiant (1 mi-parcours + 1 finale)</p>
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
                            // Calculer les statistiques spécifiques à l'étudiant avec des compteurs pour chaque critère
                            $studentStats = [
                                'evaluations_count' => count($studentEvaluations),
                                'technical_score' => 0,
                                'technical_count' => 0,
                                'communication_score' => 0,
                                'communication_count' => 0,
                                'teamwork_score' => 0,
                                'teamwork_count' => 0,
                                'autonomy_score' => 0,
                                'autonomy_count' => 0,
                                'overall_score' => 0,
                                'overall_count' => 0
                            ];
                            
                            foreach ($studentEvaluations as $eval) {
                                // Ajouter le score global si disponible
                                if (isset($eval['score']) && is_numeric($eval['score'])) {
                                    $studentStats['overall_score'] += $eval['score'];
                                    $studentStats['overall_count']++;
                                }
                                
                                // Parcourir les critères de chaque évaluation
                                if (isset($eval['criteria']) && is_array($eval['criteria'])) {
                                    foreach ($eval['criteria'] as $criterion) {
                                        if (!isset($criterion['name']) || !isset($criterion['score']) || !is_numeric($criterion['score'])) {
                                            continue; // Ignorer les critères incomplets ou non numériques
                                        }
                                        
                                        $name = strtolower($criterion['name']);
                                        $score = (float)$criterion['score'];
                                        
                                        // Assurer que le score est sur une échelle de 5
                                        if ($score > 5) {
                                            $score = round($score / 4, 1); // Convertir de 20 à 5
                                        }
                                        
                                        if (strpos($name, 'technique') !== false || 
                                            strpos($name, 'technical') !== false ||
                                            strpos($name, 'mastery') !== false ||
                                            strpos($name, 'maîtrise') !== false) {
                                            $studentStats['technical_score'] += $score;
                                            $studentStats['technical_count']++;
                                        } 
                                        elseif (strpos($name, 'communication') !== false) {
                                            $studentStats['communication_score'] += $score;
                                            $studentStats['communication_count']++;
                                        } 
                                        elseif (strpos($name, 'équipe') !== false || 
                                               strpos($name, 'team') !== false || 
                                               strpos($name, 'integration') !== false || 
                                               strpos($name, 'intégration') !== false) {
                                            $studentStats['teamwork_score'] += $score;
                                            $studentStats['teamwork_count']++;
                                        } 
                                        elseif (strpos($name, 'autonomie') !== false || 
                                               strpos($name, 'autonomy') !== false || 
                                               strpos($name, 'initiative') !== false) {
                                            $studentStats['autonomy_score'] += $score;
                                            $studentStats['autonomy_count']++;
                                        }
                                    }
                                }
                            }
                            
                            // Calculer les moyennes en utilisant les compteurs spécifiques pour chaque critère
                            $studentStats['technical_score'] = $studentStats['technical_count'] > 0 ? 
                                round($studentStats['technical_score'] / $studentStats['technical_count'], 1) : 0;
                                
                            $studentStats['communication_score'] = $studentStats['communication_count'] > 0 ? 
                                round($studentStats['communication_score'] / $studentStats['communication_count'], 1) : 0;
                                
                            $studentStats['teamwork_score'] = $studentStats['teamwork_count'] > 0 ? 
                                round($studentStats['teamwork_score'] / $studentStats['teamwork_count'], 1) : 0;
                                
                            $studentStats['autonomy_score'] = $studentStats['autonomy_count'] > 0 ? 
                                round($studentStats['autonomy_score'] / $studentStats['autonomy_count'], 1) : 0;
                                
                            $studentStats['overall_avg'] = $studentStats['overall_count'] > 0 ? 
                                round($studentStats['overall_score'] / $studentStats['overall_count'], 1) : 0;
                                
                            // Ajouter la journalisation des compteurs pour faciliter le débogage
                            error_log("Statistiques étudiantes - Technique: " . $studentStats['technical_score'] . 
                                     " (" . $studentStats['technical_count'] . " critères), " . 
                                     "Communication: " . $studentStats['communication_score'] . 
                                     " (" . $studentStats['communication_count'] . " critères), " .
                                     "Travail d'équipe: " . $studentStats['teamwork_score'] . 
                                     " (" . $studentStats['teamwork_count'] . " critères), " .
                                     "Autonomie: " . $studentStats['autonomy_score'] . 
                                     " (" . $studentStats['autonomy_count'] . " critères)");
                            
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
                    <?php if ($selectedStudent): ?>
                    <a href="/tutoring/views/tutor/debug_evaluations.php?student_id=<?php echo h($selectedStudent['id']); ?>" class="btn btn-sm btn-outline-info" target="_blank">
                        <i class="bi bi-bug"></i> Déboguer
                    </a>
                    <?php endif; ?>
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
                                    
                                    // Récupérer les évaluations en utilisant la même méthode que le dashboard
                                    $evaluations = [];
                                    if ($evaluationModel) {
                                        // Debug: Vérifier teacher_id
                                        error_log("DEBUG Vue d'ensemble - Teacher ID: " . $teacher['id'] . ", Assignment ID: " . $assignment['id'] . ", Student: " . $student['first_name'] . " " . $student['last_name']);
                                        
                                        // Utiliser getByTeacherId comme le dashboard
                                        $allEvaluations = $evaluationModel->getByTeacherId($teacher['id']);
                                        error_log("DEBUG Vue d'ensemble - Nombre total d'évaluations pour le tuteur: " . count($allEvaluations));
                                        
                                        // Filtrer pour cet assignment
                                        foreach ($allEvaluations as $eval) {
                                            error_log("DEBUG Vue d'ensemble - Evaluation ID: " . $eval['id'] . ", Assignment ID: " . $eval['assignment_id'] . ", Type: " . $eval['type'] . ", Score: " . $eval['score']);
                                            if ($eval['assignment_id'] == $assignment['id']) {
                                                $evaluations[] = $eval;
                                                error_log("DEBUG Vue d'ensemble - Evaluation trouvée pour cet assignment!");
                                            }
                                        }
                                        
                                        error_log("DEBUG Vue d'ensemble - Nombre d'évaluations pour cet assignment: " . count($evaluations));
                                    }
                                    
                                    $evalByType = [
                                        'mid_term' => null,
                                        'final' => null
                                    ];
                                    
                                    foreach ($evaluations as $eval) {
                                        error_log("DEBUG Vue d'ensemble - Processing eval type: " . $eval['type'] . ", Score: " . $eval['score']);
                                        // Ne traiter que les types mid_term et final (exclure company et enterprise)
                                        if (($eval['type'] === 'mid_term' || $eval['type'] === 'final') && 
                                            $eval['type'] !== 'company' && $eval['type'] !== 'enterprise') {
                                            // S'assurer que le score est sur une échelle de 5
                                            $scoreOn5 = $eval['score'];
                                            if ($scoreOn5 > 5) {
                                                $scoreOn5 = number_format($scoreOn5 / 4, 1);
                                            }
                                            $eval['display_score'] = $scoreOn5;
                                            $evalByType[$eval['type']] = $eval;
                                            error_log("DEBUG Vue d'ensemble - Added eval type " . $eval['type'] . " with score " . $scoreOn5);
                                        } else {
                                            error_log("DEBUG Vue d'ensemble - Type " . $eval['type'] . " ignored (not mid_term or final)");
                                        }
                                    }
                                    
                                    error_log("DEBUG Vue d'ensemble - Final evalByType mid_term: " . ($evalByType['mid_term'] ? 'EXISTS' : 'NULL'));
                                    error_log("DEBUG Vue d'ensemble - Final evalByType final: " . ($evalByType['final'] ? 'EXISTS' : 'NULL'));
                                    
                                    // Calculer la moyenne de toutes les évaluations (comme dans la carte)
                                    $totalScore = 0;
                                    $evaluationCount = 0;
                                    
                                    foreach ($evaluations as $eval) {
                                        if (isset($eval['score']) && is_numeric($eval['score'])) {
                                            // S'assurer que le score est sur une échelle de 5
                                            $scoreOn5 = $eval['score'];
                                            if ($scoreOn5 > 5) {
                                                $scoreOn5 = $scoreOn5 / 4;
                                            }
                                            $totalScore += $scoreOn5;
                                            $evaluationCount++;
                                            error_log("DEBUG Vue d'ensemble - Adding score: " . $scoreOn5 . " (type: " . $eval['type'] . ")");
                                        }
                                    }
                                    
                                    $average = $evaluationCount > 0 ? round($totalScore / $evaluationCount, 1) : null;
                                    error_log("DEBUG Vue d'ensemble - Final average: " . ($average !== null ? $average : 'NULL') . " (total: $totalScore, count: $evaluationCount)");
                                    
                                    // Mettre à jour les scores pour les statuts
                                    $midTermScore = $evalByType['mid_term'] ? floatval($evalByType['mid_term']['display_score']) : null;
                                    $finalScore = $evalByType['final'] ? floatval($evalByType['final']['display_score']) : null;
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
                                            <span class="badge bg-success">Terminé - <?php echo h($evalByType['mid_term']['display_score']); ?>/5</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">En attente</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($evalByType['final']): ?>
                                            <span class="badge bg-success">Terminé - <?php echo h($evalByType['final']['display_score']); ?>/5</span>
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
                    <span class="badge bg-primary"><?php 
                        // Utiliser submission_date si disponible, sinon created_at, sinon date actuelle
                        $dateToUse = !empty($evaluation['submission_date']) ? $evaluation['submission_date'] : 
                                    (!empty($evaluation['created_at']) ? $evaluation['created_at'] : 
                                    (!empty($evaluation['date']) ? $evaluation['date'] : date('Y-m-d H:i:s')));
                        echo date('d/m/Y', strtotime($dateToUse)); 
                    ?></span>
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
                        <p><?php echo nl2br(h($evaluation['comments'] ?? $evaluation['feedback'] ?? '')); ?></p>
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
                            <?php if (!empty($evaluation['areas_for_improvement']) || !empty($evaluation['areas_to_improve'])): ?>
                            <h6>Points à améliorer</h6>
                            <ul class="list-group list-group-flush mb-3">
                                <?php 
                                // Priorité aux areas_for_improvement, puis areas_to_improve
                                $areasData = !empty($evaluation['areas_for_improvement']) ? $evaluation['areas_for_improvement'] : $evaluation['areas_to_improve'];
                                
                                $areasArray = is_array($areasData) 
                                    ? $areasData 
                                    : explode("\n", $areasData);
                                    
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
                            
                            <?php if (!empty($evaluation['recommendations']) || !empty($evaluation['next_steps'])): ?>
                            <h6>Recommandations</h6>
                            <ul class="list-group list-group-flush">
                                <?php 
                                // Priorité aux recommendations, puis next_steps
                                $recoData = !empty($evaluation['recommendations']) ? $evaluation['recommendations'] : $evaluation['next_steps'];
                                
                                $recommendations = is_array($recoData) ? $recoData : explode("\n", $recoData);
                                
                                foreach ($recommendations as $recommendation): 
                                    if (trim($recommendation)):
                                ?>
                                <li class="list-group-item px-0"><?php echo h($recommendation); ?></li>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
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

/* Style pour le select d'étudiant dans le modal */
#modal_student_select option {
    padding: 8px 12px;
}

#modal_student_select {
    border: 2px solid #e9ecef;
    transition: border-color 0.15s ease-in-out;
}

#modal_student_select:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
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
                    <input type="hidden" name="assignment_id" id="modal_assignment_id" value="">
                    
                    <div class="mb-4">
                        <h5>Informations générales</h5>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="modal_student_select" class="form-label">Étudiant</label>
                                    <select class="form-select" id="modal_student_select" name="student_assignment" onchange="updateAssignmentId()" required>
                                        <option value="" disabled selected>Choisir un étudiant...</option>
                                        <?php foreach ($assignments as $assignment): ?>
                                        <option value="<?php echo h($assignment['id']); ?>" 
                                                data-assignment-id="<?php echo h($assignment['id']); ?>"
                                                data-student-id="<?php echo h($assignment['student_id']); ?>"
                                                <?php echo ($selectedStudent && $selectedStudent['student_id'] == $assignment['student_id']) ? 'selected' : ''; ?>>
                                            <?php echo h($assignment['student_first_name'] . ' ' . $assignment['student_last_name']); ?>
                                            <?php if (!empty($assignment['internship_title'])): ?>
                                                - <?php echo h(substr($assignment['internship_title'], 0, 30) . (strlen($assignment['internship_title']) > 30 ? '...' : '')); ?>
                                            <?php endif; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="evaluation_type" class="form-label">Type d'évaluation</label>
                                    <select class="form-select" id="evaluation_type" name="evaluation_type" required>
                                        <option value="" disabled selected>Choisir un type...</option>
                                        <option value="mid_term">Mi-parcours</option>
                                        <option value="final">Finale</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
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
        
        // Préparer les données pour le graphique - inclure toutes les compétences
        const chartData = {
            labels: [],
            technical: [],
            communication: [],
            teamwork: [],
            autonomy: [],
            professional: []
        };
        
        // Organiser les données chronologiquement avec gestion d'erreur
        const sortedEvals = [...evaluationData].filter(eval => eval && typeof eval === 'object').sort((a, b) => {
            try {
                // Utiliser la date correcte avec fallback
                const dateA = a.submission_date || a.created_at || a.date || '2000-01-01';
                const dateB = b.submission_date || b.created_at || b.date || '2000-01-01';
                return new Date(dateA) - new Date(dateB);
            } catch (e) {
                console.error("Erreur de tri des dates:", e);
                return 0;
            }
        });
        
        // Journaliser le nombre d'évaluations pour le graphique
        console.log(`Préparation du graphique avec ${sortedEvals.length} évaluations`);
        
        sortedEvals.forEach(eval => {
            try {
                // Déterminer la date à utiliser
                const evalDate = eval.submission_date || eval.created_at || eval.date || new Date().toISOString();
                
                // Ajouter la date au format court
                let dateStr;
                try {
                    dateStr = new Date(evalDate).toLocaleDateString('fr-FR', {day: '2-digit', month: '2-digit'});
                } catch (e) {
                    console.error("Erreur de formatage de date:", e);
                    dateStr = "Date inconnue";
                }
                chartData.labels.push(dateStr);
                
                // Préparer les compteurs pour chaque catégorie
                let scores = {
                    technical: { total: 0, count: 0 },
                    communication: { total: 0, count: 0 },
                    teamwork: { total: 0, count: 0 },
                    autonomy: { total: 0, count: 0 }
                };
                
                // Ajouter le score global de l'évaluation s'il n'y a pas de critères
                if (!eval.criteria || !Array.isArray(eval.criteria) || eval.criteria.length === 0) {
                    if (typeof eval.score === 'number') {
                        // Si le score est > 5, convertir à l'échelle de 5
                        const score = eval.score > 5 ? eval.score / 4 : eval.score;
                        
                        // Utiliser le même score pour toutes les catégories
                        scores.technical.total = score;
                        scores.technical.count = 1;
                        scores.communication.total = score;
                        scores.communication.count = 1;
                        scores.teamwork.total = score;
                        scores.teamwork.count = 1;
                        scores.autonomy.total = score;
                        scores.autonomy.count = 1;
                    }
                } else {
                    // Parcourir les critères pour calculer les scores par catégorie
                    eval.criteria.forEach(criterion => {
                        if (!criterion || typeof criterion !== 'object' || !criterion.name || typeof criterion.score !== 'number') {
                            return; // Ignorer les critères invalides
                        }
                        
                        const name = criterion.name.toLowerCase();
                        const score = criterion.score > 5 ? criterion.score / 4 : criterion.score;
                        
                        if (name.includes('technique') || name.includes('technical') || 
                            name.includes('maîtrise') || name.includes('mastery') ||
                            name.includes('qualité') || name.includes('quality') ||
                            name.includes('documentation')) {
                            scores.technical.total += score;
                            scores.technical.count++;
                        } 
                        else if (name.includes('communication')) {
                            scores.communication.total += score;
                            scores.communication.count++;
                        } 
                        else if (name.includes('équipe') || name.includes('team') || 
                                name.includes('intégration') || name.includes('integration')) {
                            scores.teamwork.total += score;
                            scores.teamwork.count++;
                        } 
                        else if (name.includes('autonomie') || name.includes('autonomy') || 
                                name.includes('initiative')) {
                            scores.autonomy.total += score;
                            scores.autonomy.count++;
                        } 
                        else {
                            // Si la catégorie n'est pas reconnue, distribuer le score équitablement
                            scores.technical.total += score / 4;
                            scores.technical.count += 0.25;
                            scores.communication.total += score / 4;
                            scores.communication.count += 0.25;
                            scores.teamwork.total += score / 4;
                            scores.teamwork.count += 0.25;
                            scores.autonomy.total += score / 4;
                            scores.autonomy.count += 0.25;
                        }
                    });
                }
                
                // Calculer les moyennes et les ajouter au graphique
                const technicalAvg = scores.technical.count > 0 ? scores.technical.total / scores.technical.count : 0;
                const communicationAvg = scores.communication.count > 0 ? scores.communication.total / scores.communication.count : 0;
                const teamworkAvg = scores.teamwork.count > 0 ? scores.teamwork.total / scores.teamwork.count : 0;
                const autonomyAvg = scores.autonomy.count > 0 ? scores.autonomy.total / scores.autonomy.count : 0;
                
                chartData.technical.push(technicalAvg);
                chartData.communication.push(communicationAvg);
                chartData.teamwork.push(teamworkAvg);
                chartData.autonomy.push(autonomyAvg);
                
                // Calculer le score professionnel comme la moyenne des compétences professionnelles
                const professionalAvg = ((communicationAvg + teamworkAvg + autonomyAvg) / 3);
                chartData.professional.push(professionalAvg);
                
            } catch (e) {
                console.error("Erreur lors du traitement de l'évaluation pour le graphique:", e);
                // Ajouter des valeurs par défaut pour ne pas perturber le graphique
                chartData.labels.push("Erreur");
                chartData.technical.push(0);
                chartData.communication.push(0);
                chartData.teamwork.push(0);
                chartData.autonomy.push(0);
            }
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
                                label: 'Communication',
                                data: chartData.communication,
                                borderColor: '#2ecc71',
                                backgroundColor: 'rgba(46, 204, 113, 0.1)',
                                tension: 0.3,
                                fill: true
                            },
                            {
                                label: 'Travail en équipe',
                                data: chartData.teamwork,
                                borderColor: '#f39c12',
                                backgroundColor: 'rgba(243, 156, 18, 0.1)',
                                tension: 0.3,
                                fill: true
                            },
                            {
                                label: 'Autonomie',
                                data: chartData.autonomy,
                                borderColor: '#9b59b6',
                                backgroundColor: 'rgba(155, 89, 182, 0.1)',
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
    
    // Fonction pour mettre à jour l'assignment_id quand l'étudiant change
    function updateAssignmentId() {
        const select = document.getElementById('modal_student_select');
        const selectedOption = select.options[select.selectedIndex];
        const assignmentIdField = document.getElementById('modal_assignment_id');
        
        if (selectedOption && selectedOption.value) {
            assignmentIdField.value = selectedOption.getAttribute('data-assignment-id');
        } else {
            assignmentIdField.value = '';
        }
    }
    
    // Initialiser le modal quand il s'ouvre
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('createEvaluationModal');
        if (modal) {
            modal.addEventListener('show.bs.modal', function() {
                // Si un étudiant est déjà sélectionné sur la page, le pré-sélectionner dans le modal
                <?php if ($selectedStudent): ?>
                const studentSelect = document.getElementById('modal_student_select');
                const targetValue = '<?php echo h($selectedAssignment['id'] ?? ''); ?>';
                
                for (let i = 0; i < studentSelect.options.length; i++) {
                    if (studentSelect.options[i].value === targetValue) {
                        studentSelect.selectedIndex = i;
                        updateAssignmentId();
                        break;
                    }
                }
                <?php endif; ?>
            });
        }
    });
    
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