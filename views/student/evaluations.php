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
    
    $studentModel = new Student($db);
    $student = $studentModel->getByUserId($user_id);
    
    if ($student) {
        $student_id = $student['id'];
        // Récupérer l'affectation pour obtenir l'ID de stage
        $assignment = $studentModel->getAssignment($student_id);
        $internship_id = isset($assignment['internship_id']) ? $assignment['internship_id'] : null;
        
        // Récupérer les documents d'évaluation directement
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
        $evaluations = [];
        foreach ($evaluationDocuments as $doc) {
            // Vérifier si le document a des métadonnées
            if (!isset($doc['metadata']) || !is_array($doc['metadata'])) {
                $doc['metadata'] = [];
            }
            
            // Extraire les informations de base du document
            $evaluation = [
                'id' => $doc['id'],
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
            
            $evaluations[] = $evaluation;
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
        // Si un étudiant est sélectionné et a des évaluations, initialiser le graphique de progression
        <?php if (!empty($evaluations)): ?>
        
        // Données pour le graphique
        const evaluationData = <?php echo json_encode($evaluations); ?>;
        
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
    
    // Fonctions pour les actions d'évaluation
    function printEvaluation(index) {
        // Implémenter la fonction d'impression
        window.print();
    }
    
    function shareEvaluation(index) {
        // Implémenter la fonction de partage
        const evaluations = <?php echo json_encode($evaluations); ?>;
        const evaluation = evaluations[index];
        
        if (navigator.share) {
            navigator.share({
                title: 'Mon évaluation de stage',
                text: `Évaluation ${evaluation.type === 'self' ? 'personnelle' : 'tuteur'} - Note: ${evaluation.score}/5`,
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