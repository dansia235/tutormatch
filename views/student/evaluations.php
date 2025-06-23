<?php
/**
 * Vue pour la gestion des évaluations par l'étudiant - Version améliorée
 */

// Titre de la page
$pageTitle = 'Mes évaluations';
$currentPage = 'evaluations';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../includes/init.php';

// Vérifier que l'utilisateur est étudiant
requireRole('student');

// Récupérer l'ID de l'étudiant
$user_id = $_SESSION['user_id'] ?? null;

// Initialiser les modèles nécessaires
try {
    // Utiliser la connexion à la base de données globale qui est déjà établie dans init.php
    global $db;
    
    // S'assurer que la connexion existe
    if (!isset($db) || !($db instanceof PDO)) {
        error_log("Connexion à la base de données non disponible dans evaluations.php");
        throw new Exception("Connexion à la base de données non disponible");
    }
    
    // Initialiser tous les modèles nécessaires
    $studentModel = new Student($db);
    $student = $studentModel->getByUserId($user_id);
    
    // Vérifier si la classe Evaluation existe et l'utiliser
    $evaluationModel = null;
    if (class_exists('Evaluation')) {
        $evaluationModel = new Evaluation($db);
    }
    
    if ($student) {
        $student_id = $student['id'];
        // Récupérer l'affectation pour obtenir l'ID de stage
        $assignment = $studentModel->getAssignment($student_id);
        $internship_id = isset($assignment['internship_id']) ? $assignment['internship_id'] : null;
        
        // Approche en deux étapes : d'abord tenter d'utiliser le modèle Evaluation, puis les documents
        $evaluations = [];
        
        // ÉTAPE 1 : Récupération via le modèle Evaluation si disponible
        if ($evaluationModel !== null) {
            // Récupérer l'affectation pour obtenir l'ID d'affectation
            $assignment = $studentModel->getAssignment($student_id);
            
            if (isset($assignment['id'])) {
                // Récupérer les évaluations de cette affectation
                $evalFromModel = $evaluationModel->getByAssignmentId($assignment['id']);
                
                // Transformer les données au format attendu
                foreach ($evalFromModel as $eval) {
                    // Convertir le score de 20 à 5 si nécessaire
                    $score = isset($eval['score']) && $eval['score'] > 5 ? round($eval['score'] / 4, 1) : $eval['score'];
                    
                    // Préparer les critères
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
                                'score' => round($criterionScore / 4, 1) // Convertir de 20 à 5
                            ];
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
        $documents = $studentModel->getDocuments($student_id);
        
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
        
        // Calculer les statistiques
        $totalEvaluations = count($evaluations);
        $totalScore = 0;
        $totalTechnical = 0;
        $totalProfessional = 0;
        $countTechnical = 0;
        $countProfessional = 0;
        
        foreach ($evaluations as $evaluation) {
            $totalScore += $evaluation['score'];
            
            // Parcourir les critères
            if (isset($evaluation['criteria']) && is_array($evaluation['criteria'])) {
                foreach ($evaluation['criteria'] as $criterion) {
                    if (!isset($criterion['name']) || !isset($criterion['score'])) {
                        continue;
                    }
                    
                    if (stripos($criterion['name'], 'technique') !== false || stripos($criterion['name'], 'technical') !== false) {
                        $totalTechnical += $criterion['score'];
                        $countTechnical++;
                    } else if (stripos($criterion['name'], 'professionnel') !== false || 
                             stripos($criterion['name'], 'professional') !== false ||
                             stripos($criterion['name'], 'intégration') !== false ||
                             stripos($criterion['name'], 'integration') !== false ||
                             stripos($criterion['name'], 'équipe') !== false ||
                             stripos($criterion['name'], 'team') !== false) {
                        $totalProfessional += $criterion['score'];
                        $countProfessional++;
                    }
                }
            }
        }
        
        // Calculer les moyennes
        $averageScore = $totalEvaluations > 0 ? round($totalScore / $totalEvaluations, 1) : 0;
        $technicalScore = $countTechnical > 0 ? round($totalTechnical / $countTechnical, 1) : 0;
        $professionalScore = $countProfessional > 0 ? round($totalProfessional / $countProfessional, 1) : 0;
        
        // Objectifs (fictifs pour l'exemple)
        $objectives = [
            [
                'id' => 1,
                'title' => 'Améliorer la documentation du code',
                'description' => 'À compléter pour la prochaine évaluation'
            ],
            [
                'id' => 2,
                'title' => 'Participer plus activement aux réunions',
                'description' => 'À compléter pour la prochaine évaluation'
            ],
            [
                'id' => 3,
                'title' => 'Finaliser le module API',
                'description' => 'À compléter pour la prochaine évaluation'
            ]
        ];
        
        // Statistiques
        $stats = [
            'average' => $averageScore,
            'completed' => $totalEvaluations,
            'total_expected' => 5,
            'technical' => $technicalScore,
            'professional' => $professionalScore
        ];
    } else {
        $student_id = null;
        $internship_id = null;
        $evaluations = [];
        $objectives = [];
        $stats = [
            'average' => 0,
            'completed' => 0,
            'total_expected' => 0,
            'technical' => 0,
            'professional' => 0
        ];
    }
} catch (Exception $e) {
    error_log("Erreur dans la page d'évaluations: " . $e->getMessage());
    $student_id = null;
    $internship_id = null;
    $evaluations = [];
    $objectives = [];
    $stats = [
        'average' => 0,
        'completed' => 0,
        'total_expected' => 0,
        'technical' => 0,
        'professional' => 0
    ];
}

// Inclure l'en-tête
include_once __DIR__ . '/../common/header.php';
?>

<div class="container-fluid px-0">
    <div class="row g-0 mx-0">
        <div class="col-12 px-4 py-3">
            <h2><i class="bi bi-clipboard-check me-2"></i>Mes évaluations</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/tutoring/views/student/dashboard.php">Tableau de bord</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Évaluations</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="row g-0 mx-0 px-4 mb-4">
        <div class="col-md-3 fade-in delay-1 pe-3">
            <div class="card stat-card">
                <div class="value"><?php echo h($stats['average']); ?></div>
                <div class="label">Moyenne générale</div>
                <div class="progress mt-2">
                    <div class="progress-bar" role="progressbar" style="width: <?php echo h(($stats['average'] / 5) * 100); ?>%;" aria-valuenow="<?php echo h(($stats['average'] / 5) * 100); ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <small class="text-muted">Sur 5.0</small>
            </div>
        </div>
        <div class="col-md-3 fade-in delay-2 pe-3">
            <div class="card stat-card">
                <div class="value"><?php echo h($stats['completed']); ?></div>
                <div class="label">Évaluations</div>
                <div class="progress mt-2">
                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $stats['total_expected'] > 0 ? h(($stats['completed'] / $stats['total_expected']) * 100) : 0; ?>%;" aria-valuenow="<?php echo $stats['total_expected'] > 0 ? h(($stats['completed'] / $stats['total_expected']) * 100) : 0; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <small class="text-muted"><?php echo h($stats['completed']); ?>/<?php echo h($stats['total_expected']); ?> complétées</small>
            </div>
        </div>
        <div class="col-md-3 fade-in delay-3 pe-3">
            <div class="card stat-card">
                <div class="value"><?php echo h($stats['technical']); ?></div>
                <div class="label">Technique</div>
                <div class="progress mt-2">
                    <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo h(($stats['technical'] / 5) * 100); ?>%;" aria-valuenow="<?php echo h(($stats['technical'] / 5) * 100); ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <small class="text-muted">Compétences</small>
            </div>
        </div>
        <div class="col-md-3 fade-in delay-4">
            <div class="card stat-card">
                <div class="value"><?php echo h($stats['professional']); ?></div>
                <div class="label">Professionnel</div>
                <div class="progress mt-2">
                    <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo h(($stats['professional'] / 5) * 100); ?>%;" aria-valuenow="<?php echo h(($stats['professional'] / 5) * 100); ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <small class="text-muted">Comportement</small>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="row g-0 mx-0">
        <!-- Left Column -->
        <div class="col-lg-8 px-4">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Mes évaluations</span>
                    <a href="evaluations-simple.php" class="btn btn-sm btn-outline-secondary">Version simplifiée</a>
                </div>
                <div class="card-body">
                    <?php if (empty($evaluations)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-clipboard-x display-1 text-muted mb-3"></i>
                            <h4>Aucune évaluation disponible</h4>
                            <p class="text-muted">Vous n'avez pas encore d'évaluations. Attendez que votre tuteur complète votre première évaluation ou faites votre auto-évaluation.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($evaluations as $index => $evaluation): ?>
                            <div class="card mb-4 fade-in">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <span>Évaluation <?php echo h($evaluation['type'] === 'self' ? 'Auto-évaluation' : 'Tuteur'); ?></span>
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
                                                <small class="text-muted">Évaluateur: <?php echo h($evaluation['evaluator_name']); ?></small>
                                            </div>
                                        </div>
                                        
                                        <h6>Commentaires</h6>
                                        <p><?php echo nl2br(h($evaluation['comments'] ?? '')); ?></p>
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
                                            <?php else: ?>
                                                <p class="text-muted">Aucun critère détaillé disponible</p>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <?php if (!empty($evaluation['areas_for_improvement'])): ?>
                                                <h6>Points à améliorer</h6>
                                                <ul class="list-group list-group-flush mb-3">
                                                    <?php 
                                                    $areasArray = is_array($evaluation['areas_for_improvement']) 
                                                        ? $evaluation['areas_for_improvement'] 
                                                        : explode("\n", $evaluation['areas_for_improvement']);
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
                                        <button class="btn btn-outline-info" onclick="shareEvaluation(<?php echo h($index); ?>)">
                                            <i class="bi bi-share me-1"></i>Partager
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Right Column -->
        <div class="col-lg-4 px-4">
            <!-- Quick Actions -->
            <div class="card mb-4 fade-in">
                <div class="card-header">
                    Actions rapides
                </div>
                <div class="card-body">
                    <button class="btn btn-primary w-100 mb-2" data-bs-toggle="modal" data-bs-target="#selfEvaluationModal">
                        <i class="bi bi-pencil me-2"></i>Faire mon auto-évaluation
                    </button>
                    <a href="/tutoring/views/student/documents.php" class="btn btn-outline-primary w-100 mb-2">
                        <i class="bi bi-folder me-2"></i>Mes documents
                    </a>
                    <a href="/tutoring/views/student/meetings.php" class="btn btn-outline-primary w-100 mb-2">
                        <i class="bi bi-calendar-event me-2"></i>Planifier une réunion
                    </a>
                    <a href="/tutoring/views/student/tutor.php" class="btn btn-outline-primary w-100">
                        <i class="bi bi-person-badge me-2"></i>Contacter mon tuteur
                    </a>
                </div>
            </div>
            
            <!-- Objectifs à venir -->
            <div class="card mb-4 fade-in">
                <div class="card-header">
                    Objectifs à venir
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php if (empty($objectives)): ?>
                            <div class="list-group-item p-3">
                                <p class="mb-0 text-muted">Aucun objectif défini</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($objectives as $index => $objective): ?>
                                <div class="list-group-item p-3">
                                    <div class="d-flex">
                                        <div class="flex-shrink-0 bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 28px; height: 28px;">
                                            <small class="fw-bold"><?php echo $index + 1; ?></small>
                                        </div>
                                        <div>
                                            <h6 class="mb-1"><?php echo h($objective['title']); ?></h6>
                                            <p class="mb-0 small text-muted"><?php echo h($objective['description'] ?? ''); ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Progression du stage -->
            <?php if ($internship_id): ?>
            <div class="card mb-4 fade-in">
                <div class="card-header">
                    Progression du stage
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="studentProgressChart"></canvas>
                    </div>
                    <div class="mt-3">
                        <h6>Statistiques globales</h6>
                        <div class="progress mb-2" style="height: 10px;">
                            <div class="progress-bar" role="progressbar" style="width: <?php echo h(($stats['average'] / 5) * 100); ?>%;" aria-valuenow="<?php echo h($stats['average']); ?>" aria-valuemin="0" aria-valuemax="5"></div>
                        </div>
                        <p class="small text-muted mb-0">Progression moyenne: <?php echo h($stats['average']); ?>/5</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Self Evaluation Modal -->
<div class="modal fade" id="selfEvaluationModal" tabindex="-1" aria-labelledby="selfEvaluationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="selfEvaluationModalLabel">Auto-évaluation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/tutoring/api/evaluations/submit-self-evaluation.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="student_id" value="<?php echo h($student_id); ?>">
                    
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
                        <h5>Commentaires et réflexions</h5>
                        <div class="mb-3">
                            <label for="comments" class="form-label">Commentaires généraux</label>
                            <textarea class="form-control" id="comments" name="comments" rows="4" placeholder="Points forts, difficultés rencontrées, observations personnelles..." required></textarea>
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
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" name="submit_self_evaluation" class="btn btn-primary">Soumettre mon auto-évaluation</button>
                </div>
            </form>
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

/* Animation pour les étoiles */
.rating-stars .bi-star-fill {
    color: #ffc107;
}

/* Fade-in animations */
.fade-in {
    animation: fadeIn 0.5s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.delay-1 { animation-delay: 0.1s; }
.delay-2 { animation-delay: 0.2s; }
.delay-3 { animation-delay: 0.3s; }
.delay-4 { animation-delay: 0.4s; }

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
                const evaluationData = <?php echo json_encode($evaluations); ?>;
                
                // Vérifier que les données sont valides
                if (!evaluationData || !Array.isArray(evaluationData)) {
                    throw new Error('Données d\'évaluations invalides ou manquantes');
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
                
                sortedEvals.forEach(eval => {
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
                        eval.criteria.forEach(criterion => {
                            // Vérifier que le critère est valide
                            if (!criterion || typeof criterion !== 'object' || 
                                !('name' in criterion) || !('score' in criterion)) {
                                return; // Ignorer les critères invalides
                            }
                            
                            const score = parseFloat(criterion.score);
                            if (isNaN(score)) return; // Ignorer les scores non numériques
                            
                            const name = String(criterion.name).toLowerCase();
                            if (name.includes('technique') || 
                                name.includes('technical') ||
                                name.includes('maîtrise') ||
                                name.includes('qualité') ||
                                name.includes('problème') ||
                                name.includes('documentation')) {
                                techScore += score;
                                techCount++;
                            } else {
                                profScore += score;
                                profCount++;
                            }
                        });
                    }
                    
                    // Ajouter les données avec protection contre les divisions par zéro
                    chartData.technical.push(techCount > 0 ? parseFloat((techScore / techCount).toFixed(1)) : 0);
                    chartData.professional.push(profCount > 0 ? parseFloat((profScore / profCount).toFixed(1)) : 0);
                });
                
                // Créer le graphique si des données sont disponibles
                if (chartData.labels.length > 0) {
                    // Vérifier le contexte du canvas
                    const ctx = progressChartElement.getContext('2d');
                    if (!ctx) {
                        throw new Error('Impossible d\'obtenir le contexte 2D du canvas');
                    }
                    
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
                            },
                            onError: function(err) {
                                console.error('Erreur Chart.js:', err);
                            }
                        }
                    });
                    
                    // Ajouter un gestionnaire d'erreurs global pour Chart.js
                    Chart.defaults.plugins.customFallback = {
                        beforeInit: function(chart) {
                            const originalUpdate = chart.update;
                            chart.update = function() {
                                try {
                                    return originalUpdate.apply(this, arguments);
                                } catch(err) {
                                    console.error('Erreur lors de la mise à jour du graphique:', err);
                                }
                            };
                        }
                    };
                    
                } else {
                    // Si aucune donnée, afficher un message
                    progressChartElement.parentNode.innerHTML = '<p class="text-muted text-center my-3">Pas assez de données pour afficher un graphique de progression</p>';
                }
            } catch (error) {
                console.error('Erreur lors de l\'initialisation du graphique:', error);
                progressChartElement.parentNode.innerHTML = '<div class="alert alert-warning" role="alert"><i class="bi bi-exclamation-triangle me-2"></i>Impossible de charger le graphique: ' + error.message + '</div>';
            }
        }
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
    
    // Fonctions pour les actions d'évaluation
    function printEvaluation(index) {
        try {
            const evaluations = <?php echo json_encode($evaluations); ?>;
            
            // Vérifier que l'index et le tableau d'évaluations sont valides
            if (!evaluations || !Array.isArray(evaluations) || !evaluations[index]) {
                throw new Error('Évaluation non trouvée');
            }
            
            const evaluation = evaluations[index];
            
            // Créer une fenêtre d'impression avec un contenu formaté
            const printWindow = window.open('', '_blank');
            if (!printWindow) {
                alert('Veuillez autoriser les fenêtres popup pour imprimer l\'évaluation.');
                return;
            }
            
            // Déterminer le type d'évaluation
            let evaluationType = 'Évaluation';
            if (evaluation.type) {
                switch(evaluation.type.toLowerCase()) {
                    case 'self':
                    case 'self_evaluation':
                        evaluationType = 'Auto-évaluation';
                        break;
                    case 'mid_term':
                    case 'mid-term':
                    case 'midterm':
                        evaluationType = 'Évaluation mi-parcours';
                        break;
                    case 'final':
                    case 'finale':
                        evaluationType = 'Évaluation finale';
                        break;
                    case 'company':
                    case 'enterprise':
                    case 'entreprise':
                        evaluationType = 'Évaluation entreprise';
                        break;
                    default:
                        evaluationType = 'Évaluation ' + evaluation.type;
                }
            }
            
            // Extraire le score avec validation
            const score = evaluation.score !== undefined ? 
                (isNaN(parseFloat(evaluation.score)) ? '?' : parseFloat(evaluation.score).toFixed(1)) : 
                '?';
                
            // Formater les critères
            let criteriaHtml = '';
            if (evaluation.criteria && Array.isArray(evaluation.criteria) && evaluation.criteria.length > 0) {
                criteriaHtml = '<h4>Critères d\'évaluation</h4><table style="width:100%; border-collapse: collapse; margin-bottom: 20px;">';
                criteriaHtml += '<tr><th style="text-align:left; padding: 8px; border-bottom: 1px solid #ddd;">Critère</th><th style="text-align:right; padding: 8px; border-bottom: 1px solid #ddd;">Score</th></tr>';
                
                evaluation.criteria.forEach(criterion => {
                    if (criterion && criterion.name && criterion.score !== undefined) {
                        const criterionScore = isNaN(parseFloat(criterion.score)) ? '?' : parseFloat(criterion.score).toFixed(1);
                        criteriaHtml += `<tr>
                            <td style="text-align:left; padding: 8px; border-bottom: 1px solid #eee;">${criterion.name}</td>
                            <td style="text-align:right; padding: 8px; border-bottom: 1px solid #eee;">${criterionScore}/5</td>
                        </tr>`;
                    }
                });
                
                criteriaHtml += '</table>';
            }
            
            // Formater les points à améliorer
            let improvementsHtml = '';
            if (evaluation.areas_for_improvement && (
                Array.isArray(evaluation.areas_for_improvement) && evaluation.areas_for_improvement.length > 0 || 
                typeof evaluation.areas_for_improvement === 'string' && evaluation.areas_for_improvement.trim() !== '')) {
                
                improvementsHtml = '<h4>Points à améliorer</h4><ul style="margin-bottom: 20px;">';
                
                if (Array.isArray(evaluation.areas_for_improvement)) {
                    evaluation.areas_for_improvement.forEach(area => {
                        if (area && area.trim() !== '') {
                            improvementsHtml += `<li>${area}</li>`;
                        }
                    });
                } else {
                    const areas = evaluation.areas_for_improvement.split("\n");
                    areas.forEach(area => {
                        if (area && area.trim() !== '') {
                            improvementsHtml += `<li>${area}</li>`;
                        }
                    });
                }
                
                improvementsHtml += '</ul>';
            }
            
            // Formater les recommandations
            let recommendationsHtml = '';
            if (evaluation.recommendations && (
                Array.isArray(evaluation.recommendations) && evaluation.recommendations.length > 0 || 
                typeof evaluation.recommendations === 'string' && evaluation.recommendations.trim() !== '')) {
                
                recommendationsHtml = '<h4>Recommandations</h4><ul style="margin-bottom: 20px;">';
                
                if (Array.isArray(evaluation.recommendations)) {
                    evaluation.recommendations.forEach(rec => {
                        if (rec && rec.trim() !== '') {
                            recommendationsHtml += `<li>${rec}</li>`;
                        }
                    });
                } else {
                    const recs = evaluation.recommendations.split("\n");
                    recs.forEach(rec => {
                        if (rec && rec.trim() !== '') {
                            recommendationsHtml += `<li>${rec}</li>`;
                        }
                    });
                }
                
                recommendationsHtml += '</ul>';
            }
            
            // Créer le contenu HTML
            printWindow.document.write(`
                <!DOCTYPE html>
                <html lang="fr">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>${evaluationType} - ${new Date(evaluation.date).toLocaleDateString('fr-FR')}</title>
                    <style>
                        body {
                            font-family: Arial, sans-serif;
                            line-height: 1.6;
                            color: #333;
                            padding: 20px;
                            max-width: 800px;
                            margin: 0 auto;
                        }
                        h1 {
                            color: #2c3e50;
                            margin-bottom: 20px;
                            border-bottom: 2px solid #3498db;
                            padding-bottom: 10px;
                        }
                        h2 {
                            color: #2c3e50;
                            margin-top: 30px;
                            margin-bottom: 15px;
                        }
                        h4 {
                            margin-top: 25px;
                            margin-bottom: 10px;
                            color: #2c3e50;
                            border-bottom: 1px solid #eee;
                            padding-bottom: 5px;
                        }
                        p {
                            margin-bottom: 15px;
                        }
                        .meta-info {
                            background-color: #f8f9fa;
                            padding: 15px;
                            border-radius: 5px;
                            margin-bottom: 20px;
                            border-left: 4px solid #3498db;
                        }
                        .score {
                            font-size: 24px;
                            font-weight: bold;
                            color: #3498db;
                            margin-bottom: 10px;
                        }
                        .star {
                            color: #f1c40f;
                            font-size: 20px;
                        }
                        .evaluator {
                            font-style: italic;
                            color: #7f8c8d;
                            margin-bottom: 5px;
                        }
                        .comments {
                            background-color: #f8f9fa;
                            padding: 15px;
                            border-radius: 5px;
                            margin: 20px 0;
                            white-space: pre-line;
                        }
                        .footer {
                            margin-top: 40px;
                            padding-top: 20px;
                            border-top: 1px solid #eee;
                            font-size: 0.8em;
                            color: #7f8c8d;
                            text-align: center;
                        }
                        @media print {
                            body {
                                padding: 0;
                            }
                            .no-print {
                                display: none;
                            }
                        }
                    </style>
                </head>
                <body>
                    <div class="no-print" style="text-align: right; margin-bottom: 20px;">
                        <button onclick="window.print()" style="padding: 8px 16px; background: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer;">
                            Imprimer
                        </button>
                    </div>
                    
                    <h1>${evaluationType}</h1>
                    
                    <div class="meta-info">
                        <p><strong>Date:</strong> ${new Date(evaluation.date).toLocaleDateString('fr-FR', {day: 'numeric', month: 'long', year: 'numeric'})}</p>
                        <p><strong>Évaluateur:</strong> ${evaluation.evaluator_name || 'Non spécifié'}</p>
                        <div class="score">
                            Note globale: ${score}/5
                            <span class="stars">
                                ${'★'.repeat(Math.round(parseFloat(score) || 0))}${'☆'.repeat(5 - Math.round(parseFloat(score) || 0))}
                            </span>
                        </div>
                    </div>
                    
                    <h2>Commentaires</h2>
                    <div class="comments">
                        ${evaluation.comments || 'Aucun commentaire fourni.'}
                    </div>
                    
                    ${criteriaHtml}
                    ${improvementsHtml}
                    ${recommendationsHtml}
                    
                    <div class="footer">
                        Document généré le ${new Date().toLocaleDateString('fr-FR', {day: 'numeric', month: 'long', year: 'numeric'})} à ${new Date().toLocaleTimeString('fr-FR')}
                    </div>
                </body>
                </html>
            `);
            
            printWindow.document.close();
            
            // Attendre que le contenu soit chargé avant d'imprimer
            setTimeout(() => {
                printWindow.focus();
                printWindow.print();
                // Ne pas fermer la fenêtre pour permettre des impressions multiples
            }, 1000);
            
        } catch (error) {
            console.error('Erreur lors de la préparation de l\'impression:', error);
            alert('Impossible de préparer l\'impression: ' + error.message);
            // Fallback à l'impression standard
            window.print();
        }
    }
    
    function shareEvaluation(index) {
        // Implémenter la fonction de partage avec gestion des erreurs
        try {
            const evaluations = <?php echo json_encode($evaluations); ?>;
            
            // Vérifier que l'index et le tableau d'évaluations sont valides
            if (!evaluations || !Array.isArray(evaluations) || !evaluations[index]) {
                throw new Error('Évaluation non trouvée');
            }
            
            const evaluation = evaluations[index];
            
            // Déterminer le type d'évaluation avec une meilleure gestion des cas
            let evaluationType = 'Évaluation';
            if (evaluation.type) {
                switch(evaluation.type.toLowerCase()) {
                    case 'self':
                    case 'self_evaluation':
                        evaluationType = 'Auto-évaluation';
                        break;
                    case 'mid_term':
                    case 'mid-term':
                    case 'midterm':
                        evaluationType = 'Évaluation mi-parcours';
                        break;
                    case 'final':
                    case 'finale':
                        evaluationType = 'Évaluation finale';
                        break;
                    case 'company':
                    case 'enterprise':
                    case 'entreprise':
                        evaluationType = 'Évaluation entreprise';
                        break;
                    default:
                        evaluationType = 'Évaluation ' + evaluation.type;
                }
            }
            
            // Extraire le score avec validation
            const score = evaluation.score !== undefined ? 
                (isNaN(parseFloat(evaluation.score)) ? '?' : parseFloat(evaluation.score).toFixed(1)) : 
                '?';
            
            // Préparer le contenu à partager
            const shareTitle = 'Évaluation de stage';
            const shareText = `${evaluationType} - Note: ${score}/5`;
            const shareUrl = window.location.href;
            
            // Utiliser l'API de partage si disponible
            if (navigator.share) {
                navigator.share({
                    title: shareTitle,
                    text: shareText,
                    url: shareUrl
                }).then(() => {
                    console.log('Partage réussi');
                }).catch((error) => {
                    console.error('Erreur lors du partage:', error);
                    fallbackShare(shareUrl);
                });
            } else {
                fallbackShare(shareUrl);
            }
        } catch (error) {
            console.error('Erreur lors du partage de l\'évaluation:', error);
            alert('Impossible de partager cette évaluation: ' + error.message);
        }
    }
    
    // Fonction de secours pour le partage
    function fallbackShare(url) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(url)
                .then(() => {
                    alert('Le lien a été copié dans le presse-papiers');
                })
                .catch(err => {
                    console.error('Erreur lors de la copie dans le presse-papiers:', err);
                    alert('Impossible de copier le lien. Veuillez le copier manuellement: ' + url);
                });
        } else {
            // Fallback pour les navigateurs plus anciens
            const textarea = document.createElement('textarea');
            textarea.value = url;
            textarea.style.position = 'fixed';  // Éviter de faire défiler la page
            document.body.appendChild(textarea);
            textarea.focus();
            textarea.select();
            
            try {
                const successful = document.execCommand('copy');
                if (successful) {
                    alert('Le lien a été copié dans le presse-papiers');
                } else {
                    alert('Impossible de copier le lien. Veuillez le copier manuellement: ' + url);
                }
            } catch (err) {
                console.error('Erreur lors de la copie dans le presse-papiers:', err);
                alert('Impossible de copier le lien. Veuillez le copier manuellement: ' + url);
            }
            
            document.body.removeChild(textarea);
        }
    }
</script>

<?php
// Inclure le pied de page
include_once __DIR__ . '/../common/footer.php';
?>