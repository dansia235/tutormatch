<?php
/**
 * Vue pour la gestion des évaluations par l'étudiant - Version corrigée
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
        
        // Récupérer les documents d'évaluation de l'étudiant
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
        $stats = [
            'total' => count($evaluations),
            'completed' => count($evaluations),
            'total_expected' => 5,
            'average' => 0,
            'technical' => 0,
            'professional' => 0
        ];
        
        // Calculer les moyennes
        $totalScore = 0;
        $totalTechnical = 0;
        $totalProfessional = 0;
        $countTechnical = 0;
        $countProfessional = 0;
        
        foreach ($evaluations as $eval) {
            $totalScore += $eval['score'];
            
            // Parcourir les critères
            if (isset($eval['criteria']) && is_array($eval['criteria'])) {
                foreach ($eval['criteria'] as $criterion) {
                    if (!isset($criterion['name']) || !isset($criterion['score'])) {
                        continue;
                    }
                    
                    $name = strtolower($criterion['name']);
                    if (strpos($name, 'technique') !== false || strpos($name, 'technical') !== false) {
                        $totalTechnical += $criterion['score'];
                        $countTechnical++;
                    } else if (strpos($name, 'professionnel') !== false || 
                            strpos($name, 'professional') !== false ||
                            strpos($name, 'intégration') !== false ||
                            strpos($name, 'integration') !== false ||
                            strpos($name, 'équipe') !== false ||
                            strpos($name, 'team') !== false) {
                        $totalProfessional += $criterion['score'];
                        $countProfessional++;
                    }
                }
            }
        }
        
        if ($stats['total'] > 0) {
            $stats['average'] = round($totalScore / $stats['total'], 1);
        }
        
        if ($countTechnical > 0) {
            $stats['technical'] = round($totalTechnical / $countTechnical, 1);
        }
        
        if ($countProfessional > 0) {
            $stats['professional'] = round($totalProfessional / $countProfessional, 1);
        }
        
        // Objectifs fictifs
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
    } else {
        $student_id = null;
        $internship_id = null;
        $evaluations = [];
        $stats = [
            'total' => 0,
            'completed' => 0,
            'total_expected' => 5,
            'average' => 0,
            'technical' => 0,
            'professional' => 0
        ];
        $objectives = [];
    }
} catch (Exception $e) {
    error_log("Erreur dans la page d'évaluations: " . $e->getMessage());
    $student_id = null;
    $internship_id = null;
    $evaluations = [];
    $stats = [
        'total' => 0,
        'completed' => 0,
        'total_expected' => 5,
        'average' => 0,
        'technical' => 0,
        'professional' => 0
    ];
    $objectives = [];
}

// Inclure l'en-tête
include_once __DIR__ . '/../common/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2>Mes évaluations</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/tutoring/views/student/dashboard.php">Tableau de bord</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Évaluations</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="row mb-4" id="stats-container">
        <div class="col-md-3 fade-in delay-1">
            <div class="card stat-card">
                <div class="value" id="average-score"><?php echo $stats['average']; ?></div>
                <div class="label">Moyenne générale</div>
                <div class="progress mt-2">
                    <div class="progress-bar" role="progressbar" style="width: <?php echo ($stats['average'] / 5) * 100; ?>%;" aria-valuenow="<?php echo ($stats['average'] / 5) * 100; ?>" aria-valuemin="0" aria-valuemax="100" id="average-progress"></div>
                </div>
                <small class="text-muted">Sur 5.0</small>
            </div>
        </div>
        <div class="col-md-3 fade-in delay-2">
            <div class="card stat-card">
                <div class="value" id="evaluations-count"><?php echo $stats['completed']; ?></div>
                <div class="label">Évaluations</div>
                <div class="progress mt-2">
                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo ($stats['completed'] / $stats['total_expected']) * 100; ?>%;" aria-valuenow="<?php echo ($stats['completed'] / $stats['total_expected']) * 100; ?>" aria-valuemin="0" aria-valuemax="100" id="evaluations-progress"></div>
                </div>
                <small class="text-muted" id="evaluations-text"><?php echo $stats['completed']; ?>/<?php echo $stats['total_expected']; ?> complétées</small>
            </div>
        </div>
        <div class="col-md-3 fade-in delay-3">
            <div class="card stat-card">
                <div class="value" id="technical-score"><?php echo $stats['technical']; ?></div>
                <div class="label">Technique</div>
                <div class="progress mt-2">
                    <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo ($stats['technical'] / 5) * 100; ?>%;" aria-valuenow="<?php echo ($stats['technical'] / 5) * 100; ?>" aria-valuemin="0" aria-valuemax="100" id="technical-progress"></div>
                </div>
                <small class="text-muted">Compétences</small>
            </div>
        </div>
        <div class="col-md-3 fade-in delay-4">
            <div class="card stat-card">
                <div class="value" id="professional-score"><?php echo $stats['professional']; ?></div>
                <div class="label">Professionnel</div>
                <div class="progress mt-2">
                    <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo ($stats['professional'] / 5) * 100; ?>%;" aria-valuenow="<?php echo ($stats['professional'] / 5) * 100; ?>" aria-valuemin="0" aria-valuemax="100" id="professional-progress"></div>
                </div>
                <small class="text-muted">Comportement</small>
            </div>
        </div>
    </div>
    
    <!-- Main Content Row -->
    <div class="row">
        <!-- Left Column -->
        <div class="col-lg-8">
            <!-- Evaluations List -->
            <div class="card mb-4 fade-in">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Mes évaluations</span>
                </div>
                <div class="card-body">
                    <?php if (empty($evaluations)): ?>
                    <div class="alert alert-info" role="alert">
                        <div class="d-flex">
                            <div class="me-3">
                                <i class="bi bi-info-circle-fill fs-4"></i>
                            </div>
                            <div>
                                <h5 class="alert-heading">Aucune évaluation disponible</h5>
                                <p class="mb-0">Vous n'avez pas encore reçu d'évaluation. Vos évaluations apparaîtront ici lorsque votre tuteur les aura soumises.</p>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    
                    <!-- Evaluations Cards -->
                    <?php foreach ($evaluations as $evaluation): ?>
                    <div class="card mb-4 fade-in">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span>Évaluation <?php echo $evaluation['type'] === 'self' ? 'Auto-évaluation' : 'du tuteur'; ?></span>
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
                                        <h5 class="mb-0">Note globale: <?php echo $evaluation['score']; ?>/5</h5>
                                        <small class="text-muted">Évaluateur: <?php echo $evaluation['evaluator_name']; ?></small>
                                    </div>
                                </div>
                                
                                <h6>Commentaires</h6>
                                <p><?php echo nl2br(htmlspecialchars($evaluation['comments'])); ?></p>
                            </div>
                            
                            <?php if (!empty($evaluation['criteria'])): ?>
                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <h6>Critères d'évaluation</h6>
                                    <div class="row">
                                        <?php foreach ($evaluation['criteria'] as $criterion): ?>
                                        <div class="col-md-6 mb-2">
                                            <div class="d-flex justify-content-between">
                                                <span><?php echo htmlspecialchars($criterion['name']); ?></span>
                                                <span><?php echo htmlspecialchars($criterion['score']); ?>/5</span>
                                            </div>
                                            <div class="progress" style="height: 6px;">
                                                <div class="progress-bar" role="progressbar" style="width: <?php echo ($criterion['score']/5)*100; ?>%;" aria-valuenow="<?php echo $criterion['score']; ?>" aria-valuemin="0" aria-valuemax="5"></div>
                                            </div>
                                            <?php if (isset($criterion['comments']) && !empty($criterion['comments'])): ?>
                                            <small class="text-muted"><?php echo htmlspecialchars($criterion['comments']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="d-flex gap-2">
                                <button class="btn btn-outline-primary" onclick="printEvaluation(this)">
                                    <i class="bi bi-printer me-1"></i>Imprimer
                                </button>
                                <button class="btn btn-outline-info" onclick="showEvaluationDetails(this)" data-evaluation-id="<?php echo $evaluation['id']; ?>">
                                    <i class="bi bi-eye me-1"></i>Détails
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
        <div class="col-lg-4">
            <!-- Quick Actions -->
            <div class="card mb-4 fade-in">
                <div class="card-header">
                    Actions rapides
                </div>
                <div class="card-body">
                    <button class="btn btn-primary w-100 mb-2" id="selfEvaluationBtn">
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
                    <?php if (empty($objectives)): ?>
                    <div class="alert alert-info m-3" role="alert">
                        <i class="bi bi-info-circle-fill me-2"></i>Aucun objectif défini pour le moment.
                    </div>
                    <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($objectives as $index => $objective): ?>
                        <li class="list-group-item p-3">
                            <div class="d-flex">
                                <div class="flex-shrink-0 bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 28px; height: 28px;">
                                    <small class="fw-bold"><?php echo $index + 1; ?></small>
                                </div>
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($objective['title']); ?></h6>
                                    <p class="mb-0 small text-muted"><?php echo htmlspecialchars($objective['description']); ?></p>
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

<!-- Self Evaluation Modal -->
<div class="modal fade" id="selfEvaluationModal" tabindex="-1" aria-labelledby="selfEvaluationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="selfEvaluationModalLabel">Auto-évaluation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    Cette fonctionnalité n'est pas encore implémentée. Pour soumettre une auto-évaluation, veuillez contacter votre tuteur.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<!-- Evaluation Details Modal -->
<div class="modal fade" id="evaluationDetailsModal" tabindex="-1" aria-labelledby="evaluationDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="evaluationDetailsModalLabel">Détails de l'évaluation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="evaluationDetailsContent">
                <!-- Le contenu sera chargé dynamiquement -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestionnaire pour le bouton d'auto-évaluation
    document.getElementById('selfEvaluationBtn').addEventListener('click', function() {
        var selfEvaluationModal = new bootstrap.Modal(document.getElementById('selfEvaluationModal'));
        selfEvaluationModal.show();
    });
    
    // Initialiser les graphiques et visualisations si nécessaire
    initializeCharts();
});

// Fonction pour imprimer une évaluation
function printEvaluation(button) {
    const evaluationCard = button.closest('.card');
    const printContent = evaluationCard.innerHTML;
    
    // Créer une fenêtre d'impression
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>Évaluation</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
            <style>
                body { padding: 20px; }
                .btn { display: none; }
                @media print {
                    body { padding: 0; }
                }
            </style>
        </head>
        <body>
            <div class="container">
                <h1 class="mb-4">Évaluation de stage</h1>
                <div class="card">
                    ${printContent}
                </div>
            </div>
        </body>
        </html>
    `);
    
    printWindow.document.close();
    setTimeout(function() {
        printWindow.print();
    }, 500);
}

// Fonction pour afficher les détails d'une évaluation
function showEvaluationDetails(button) {
    const evaluationId = button.getAttribute('data-evaluation-id');
    const evaluationCard = button.closest('.card');
    const evaluationContent = evaluationCard.querySelector('.card-body').innerHTML;
    
    // Afficher dans la modal
    document.getElementById('evaluationDetailsContent').innerHTML = evaluationContent;
    var detailsModal = new bootstrap.Modal(document.getElementById('evaluationDetailsModal'));
    detailsModal.show();
}

// Fonction pour initialiser les graphiques
function initializeCharts() {
    // Si nécessaire, ajoutez des graphiques ici
}
</script>

<?php
// Inclure le pied de page
include_once __DIR__ . '/../common/footer.php';
?>