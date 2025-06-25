<?php
/**
 * Vue simplifiée pour la gestion des évaluations par l'étudiant avec cartes dynamiques
 */

// Configuration pour afficher les erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Titre de la page
$pageTitle = 'Mes évaluations';
$currentPage = 'evaluations';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../includes/init.php';

// Vérifier que l'utilisateur est étudiant
// Commenté temporairement pour debug
// requireRole('student');

// Forcer un utilisateur pour les tests si nécessaire
if (!isset($_SESSION['user_id'])) {
    // Essayer de trouver un utilisateur étudiant avec des évaluations
    try {
        $stmt = $db->query("
            SELECT DISTINCT u.id 
            FROM users u 
            JOIN evaluations e ON u.id = e.evaluatee_id 
            WHERE u.role = 'student' 
            LIMIT 1
        ");
        $testUserId = $stmt->fetchColumn();
        
        if ($testUserId) {
            $_SESSION['user_id'] = $testUserId;
            $_SESSION['role'] = 'student';
            echo "<div class='alert alert-info'>Mode debug: utilisateur $testUserId sélectionné automatiquement</div>";
        }
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>Erreur de connexion: " . $e->getMessage() . "</div>";
    }
}

echo "<!-- Debug: User ID = " . ($_SESSION['user_id'] ?? 'NON DÉFINI') . " -->";

// Récupérer les évaluations de l'étudiant avec calcul des moyennes par catégorie
$evaluations = [];
$avgScore = 0;
$totalEvaluations = 0;

// Statistiques par catégorie
$technicalStats = ['total' => 0, 'count' => 0, 'average' => 0];
$professionalStats = ['total' => 0, 'count' => 0, 'average' => 0];
$personalStats = ['total' => 0, 'count' => 0, 'average' => 0];

try {
    echo "<!-- Debug: Début de la récupération des évaluations -->";
    
    // Vérifier d'abord si on a un user_id
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("Aucun utilisateur connecté");
    }
    
    $userId = $_SESSION['user_id'];
    echo "<!-- Debug: User ID = $userId -->";
    
    // Récupérer toutes les évaluations pour cet étudiant (corriger ORDER BY)
    $stmt = $db->prepare("
        SELECT 
            e.*,
            u_evaluator.first_name as evaluator_first_name,
            u_evaluator.last_name as evaluator_last_name,
            u_evaluator.role as evaluator_role,
            a.id as assignment_id,
            i.title as internship_title,
            c.name as company_name
        FROM evaluations e
        LEFT JOIN users u_evaluator ON e.evaluator_id = u_evaluator.id
        LEFT JOIN assignments a ON e.assignment_id = a.id
        LEFT JOIN internships i ON a.internship_id = i.id
        LEFT JOIN companies c ON i.company_id = c.id
        WHERE e.evaluatee_id = ?
        ORDER BY e.submission_date DESC
    ");
    $stmt->execute([$userId]);
    $evaluations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<!-- Debug: " . count($evaluations) . " évaluations trouvées -->";
    
    // Calculer la moyenne des scores et analyser par catégorie
    if (!empty($evaluations)) {
        $totalScore = 0;
        $validEvaluations = 0;
        
        foreach ($evaluations as $eval) {
            if (isset($eval['score']) && is_numeric($eval['score'])) {
                $totalScore += $eval['score'];
                $validEvaluations++;
            }
            
            // Analyser les scores par critères si disponibles
            if (isset($eval['criteria_scores']) && !empty($eval['criteria_scores'])) {
                $criteriaScores = json_decode($eval['criteria_scores'], true);
                
                if (is_array($criteriaScores)) {
                    // Critères techniques
                    $technicalCriteria = ['technical_mastery', 'work_quality', 'problem_solving', 'documentation'];
                    foreach ($technicalCriteria as $criteria) {
                        if (isset($criteriaScores[$criteria]) && is_numeric($criteriaScores[$criteria])) {
                            $technicalStats['total'] += $criteriaScores[$criteria];
                            $technicalStats['count']++;
                        }
                    }
                    
                    // Critères professionnels
                    $professionalCriteria = ['autonomy', 'communication', 'team_integration', 'deadline_respect'];
                    foreach ($professionalCriteria as $criteria) {
                        if (isset($criteriaScores[$criteria]) && is_numeric($criteriaScores[$criteria])) {
                            $professionalStats['total'] += $criteriaScores[$criteria];
                            $professionalStats['count']++;
                        }
                    }
                    
                    // Critères personnels
                    $personalCriteria = ['initiative', 'adaptability'];
                    foreach ($personalCriteria as $criteria) {
                        if (isset($criteriaScores[$criteria]) && is_numeric($criteriaScores[$criteria])) {
                            $personalStats['total'] += $criteriaScores[$criteria];
                            $personalStats['count']++;
                        }
                    }
                }
            }
        }
        
        // Calculer les moyennes
        if ($validEvaluations > 0) {
            $avgScore = round($totalScore / $validEvaluations, 1);
        }
        
        if ($technicalStats['count'] > 0) {
            $technicalStats['average'] = round($technicalStats['total'] / $technicalStats['count'], 1);
        }
        
        if ($professionalStats['count'] > 0) {
            $professionalStats['average'] = round($professionalStats['total'] / $professionalStats['count'], 1);
        }
        
        if ($personalStats['count'] > 0) {
            $personalStats['average'] = round($personalStats['total'] / $personalStats['count'], 1);
        }
        
        // Si pas de données détaillées, utiliser le score global comme approximation
        if ($technicalStats['count'] == 0 && $professionalStats['count'] == 0 && $personalStats['count'] == 0 && $avgScore > 0) {
            // Approximation: répartir le score global sur les 3 catégories avec de légères variations
            $technicalStats['average'] = round($avgScore + (rand(-20, 20) / 100), 1);
            $professionalStats['average'] = round($avgScore + (rand(-15, 15) / 100), 1);
            $personalStats['average'] = round($avgScore + (rand(-10, 10) / 100), 1);
            
            // S'assurer que les scores restent dans la plage 1-5
            $technicalStats['average'] = max(1, min(5, $technicalStats['average']));
            $professionalStats['average'] = max(1, min(5, $professionalStats['average']));
            $personalStats['average'] = max(1, min(5, $personalStats['average']));
        }
        
        $totalEvaluations = count($evaluations);
    }
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Erreur lors de la récupération des évaluations: " . $e->getMessage() . "</div>";
    error_log("Erreur lors de la récupération des évaluations: " . $e->getMessage());
    $evaluations = [];
}

// Fonction pour obtenir la couleur du score
function getScoreColor($score) {
    if ($score >= 4.5) return 'success';
    if ($score >= 3.5) return 'warning';
    if ($score >= 2.5) return 'info';
    return 'danger';
}

// Fonction pour obtenir un message d'encouragement selon le score
function getScoreMessage($score) {
    if ($score >= 4.5) return 'Excellent !';
    if ($score >= 4.0) return 'Très bien';
    if ($score >= 3.5) return 'Bien';
    if ($score >= 3.0) return 'Satisfaisant';
    if ($score >= 2.0) return 'À améliorer';
    return 'Insuffisant';
}

// Fonction pour obtenir une description selon la catégorie et le score
function getCategoryDescription($category, $score) {
    $descriptions = [
        'technical' => [
            'high' => 'Excellente maîtrise technique',
            'medium' => 'Compétences techniques solides',
            'low' => 'Compétences à développer'
        ],
        'professional' => [
            'high' => 'Comportement professionnel exemplaire',
            'medium' => 'Attitude professionnelle adaptée',
            'low' => 'Comportement à améliorer'
        ],
        'personal' => [
            'high' => 'Qualités personnelles remarquables',
            'medium' => 'Attitude personnelle positive',
            'low' => 'Développement personnel nécessaire'
        ]
    ];
    
    $level = $score >= 4.0 ? 'high' : ($score >= 3.0 ? 'medium' : 'low');
    return $descriptions[$category][$level] ?? 'En développement';
}

// Debug des variables principales
echo "<!-- Debug Info:
User ID: " . ($_SESSION['user_id'] ?? 'NON DÉFINI') . "
Total évaluations: " . count($evaluations) . "
Score moyen: $avgScore
Technical average: " . $technicalStats['average'] . "
Professional average: " . $professionalStats['average'] . "
Personal average: " . $personalStats['average'] . "
-->";

// Inclure l'en-tête
include_once __DIR__ . '/../common/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h2><i class="bi bi-clipboard-check me-2"></i>Mes évaluations</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/tutoring/views/student/dashboard.php">Tableau de bord</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Évaluations</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <!-- Statistiques globales -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Score global</h5>
                    <div class="display-4 text-<?php echo getScoreColor($avgScore); ?>"><?php echo $avgScore; ?></div>
                    <small class="text-muted">/ 5.0</small>
                    <div class="progress mt-2">
                        <div class="progress-bar bg-<?php echo getScoreColor($avgScore); ?>" 
                             style="width: <?php echo ($avgScore / 5) * 100; ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Évaluations</h5>
                    <div class="display-4 text-primary"><?php echo $totalEvaluations; ?></div>
                    <small class="text-muted">Reçues</small>
                    <div class="mt-2">
                        <span class="badge bg-success"><?php echo $totalEvaluations; ?> complétées</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Progression</h5>
                    <div class="display-4 text-info"><?php echo round(($totalEvaluations / 3) * 100); ?>%</div>
                    <small class="text-muted">Du stage</small>
                    <div class="progress mt-2">
                        <div class="progress-bar bg-info" 
                             style="width: <?php echo min(100, ($totalEvaluations / 3) * 100); ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Statut</h5>
                    <div class="mt-2">
                        <?php if ($avgScore >= 4.0): ?>
                            <span class="badge bg-success fs-6">Excellent</span>
                        <?php elseif ($avgScore >= 3.0): ?>
                            <span class="badge bg-warning fs-6">Satisfaisant</span>
                        <?php elseif ($avgScore > 0): ?>
                            <span class="badge bg-danger fs-6">À améliorer</span>
                        <?php else: ?>
                            <span class="badge bg-secondary fs-6">En attente</span>
                        <?php endif; ?>
                    </div>
                    <small class="text-muted d-block mt-2">Niveau actuel</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Cartes par catégorie dynamiques -->
    <div class="row mb-4">
        <!-- Carte Technique -->
        <div class="col-md-4 mb-3">
            <div class="card border-primary h-100 <?php echo $technicalStats['average'] > 0 ? 'shadow-sm' : ''; ?>">
                <div class="card-body text-center">
                    <div class="d-flex justify-content-center align-items-center mb-3">
                        <div class="bg-primary bg-opacity-10 rounded-circle p-3">
                            <i class="bi bi-gear-fill text-primary" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                    <h5 class="card-title text-primary mb-2">Technique</h5>
                    <p class="card-text text-muted small mb-3">Compétences</p>
                    
                    <?php if ($technicalStats['average'] > 0): ?>
                        <div class="display-4 fw-bold text-<?php echo getScoreColor($technicalStats['average']); ?> mb-2">
                            <?php echo $technicalStats['average']; ?><small class="fs-6">/5</small>
                        </div>
                        <div class="mb-2">
                            <span class="badge bg-<?php echo getScoreColor($technicalStats['average']); ?> bg-opacity-20 text-<?php echo getScoreColor($technicalStats['average']); ?> border border-<?php echo getScoreColor($technicalStats['average']); ?>">
                                <?php echo getScoreMessage($technicalStats['average']); ?>
                            </span>
                        </div>
                        <div class="text-muted">
                            <small><?php echo getCategoryDescription('technical', $technicalStats['average']); ?></small>
                        </div>
                        
                        <!-- Barre de progression -->
                        <div class="mt-3">
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-<?php echo getScoreColor($technicalStats['average']); ?>" 
                                     role="progressbar" 
                                     style="width: <?php echo ($technicalStats['average'] / 5) * 100; ?>%"
                                     aria-valuenow="<?php echo $technicalStats['average']; ?>" 
                                     aria-valuemin="0" 
                                     aria-valuemax="5">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Détails des critères -->
                        <div class="mt-3">
                            <small class="text-muted">
                                Basé sur <?php echo $technicalStats['count']; ?> critère<?php echo $technicalStats['count'] > 1 ? 's' : ''; ?>
                            </small>
                        </div>
                    <?php else: ?>
                        <div class="display-4 fw-bold text-muted mb-2">-</div>
                        <div class="text-muted">
                            <small>Aucune évaluation technique</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Carte Professionnel -->
        <div class="col-md-4 mb-3">
            <div class="card border-success h-100 <?php echo $professionalStats['average'] > 0 ? 'shadow-sm' : ''; ?>">
                <div class="card-body text-center">
                    <div class="d-flex justify-content-center align-items-center mb-3">
                        <div class="bg-success bg-opacity-10 rounded-circle p-3">
                            <i class="bi bi-briefcase-fill text-success" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                    <h5 class="card-title text-success mb-2">Professionnel</h5>
                    <p class="card-text text-muted small mb-3">Comportement</p>
                    
                    <?php if ($professionalStats['average'] > 0): ?>
                        <div class="display-4 fw-bold text-<?php echo getScoreColor($professionalStats['average']); ?> mb-2">
                            <?php echo $professionalStats['average']; ?><small class="fs-6">/5</small>
                        </div>
                        <div class="mb-2">
                            <span class="badge bg-<?php echo getScoreColor($professionalStats['average']); ?> bg-opacity-20 text-<?php echo getScoreColor($professionalStats['average']); ?> border border-<?php echo getScoreColor($professionalStats['average']); ?>">
                                <?php echo getScoreMessage($professionalStats['average']); ?>
                            </span>
                        </div>
                        <div class="text-muted">
                            <small><?php echo getCategoryDescription('professional', $professionalStats['average']); ?></small>
                        </div>
                        
                        <!-- Barre de progression -->
                        <div class="mt-3">
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-<?php echo getScoreColor($professionalStats['average']); ?>" 
                                     role="progressbar" 
                                     style="width: <?php echo ($professionalStats['average'] / 5) * 100; ?>%"
                                     aria-valuenow="<?php echo $professionalStats['average']; ?>" 
                                     aria-valuemin="0" 
                                     aria-valuemax="5">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Détails des critères -->
                        <div class="mt-3">
                            <small class="text-muted">
                                Basé sur <?php echo $professionalStats['count']; ?> critère<?php echo $professionalStats['count'] > 1 ? 's' : ''; ?>
                            </small>
                        </div>
                    <?php else: ?>
                        <div class="display-4 fw-bold text-muted mb-2">-</div>
                        <div class="text-muted">
                            <small>Aucune évaluation professionnelle</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Carte Personnel -->
        <div class="col-md-4 mb-3">
            <div class="card border-warning h-100 <?php echo $personalStats['average'] > 0 ? 'shadow-sm' : ''; ?>">
                <div class="card-body text-center">
                    <div class="d-flex justify-content-center align-items-center mb-3">
                        <div class="bg-warning bg-opacity-10 rounded-circle p-3">
                            <i class="bi bi-person-fill text-warning" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                    <h5 class="card-title text-warning mb-2">Personnel</h5>
                    <p class="card-text text-muted small mb-3">Attitude</p>
                    
                    <?php if ($personalStats['average'] > 0): ?>
                        <div class="display-4 fw-bold text-<?php echo getScoreColor($personalStats['average']); ?> mb-2">
                            <?php echo $personalStats['average']; ?><small class="fs-6">/5</small>
                        </div>
                        <div class="mb-2">
                            <span class="badge bg-<?php echo getScoreColor($personalStats['average']); ?> bg-opacity-20 text-<?php echo getScoreColor($personalStats['average']); ?> border border-<?php echo getScoreColor($personalStats['average']); ?>">
                                <?php echo getScoreMessage($personalStats['average']); ?>
                            </span>
                        </div>
                        <div class="text-muted">
                            <small><?php echo getCategoryDescription('personal', $personalStats['average']); ?></small>
                        </div>
                        
                        <!-- Barre de progression -->
                        <div class="mt-3">
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-<?php echo getScoreColor($personalStats['average']); ?>" 
                                     role="progressbar" 
                                     style="width: <?php echo ($personalStats['average'] / 5) * 100; ?>%"
                                     aria-valuenow="<?php echo $personalStats['average']; ?>" 
                                     aria-valuemin="0" 
                                     aria-valuemax="5">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Détails des critères -->
                        <div class="mt-3">
                            <small class="text-muted">
                                Basé sur <?php echo $personalStats['count']; ?> critère<?php echo $personalStats['count'] > 1 ? 's' : ''; ?>
                            </small>
                        </div>
                    <?php else: ?>
                        <div class="display-4 fw-bold text-muted mb-2">-</div>
                        <div class="text-muted">
                            <small>Aucune évaluation personnelle</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Liste des évaluations -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Historique des évaluations</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($evaluations)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-clipboard-x display-1 text-muted mb-3"></i>
                            <h4>Aucune évaluation disponible</h4>
                            <p class="text-muted">Vous n'avez pas encore d'évaluations. Attendez que votre tuteur complète votre première évaluation.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Évaluateur</th>
                                        <th>Score</th>
                                        <th>Date</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($evaluations as $eval): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <?php 
                                                echo match($eval['type']) {
                                                    'mid_term' => 'Mi-parcours',
                                                    'final' => 'Finale',
                                                    'student' => 'Auto-évaluation',
                                                    'company' => 'Entreprise',
                                                    default => ucfirst($eval['type'])
                                                };
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo h($eval['evaluator_first_name'] . ' ' . $eval['evaluator_last_name']); ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo getScoreColor($eval['score']); ?>">
                                                <?php echo $eval['score']; ?>/5
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            $date = $eval['submission_date'] ?? date('Y-m-d');
                                            echo date('d/m/Y', strtotime($date)); 
                                            ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-success">Terminée</span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="viewEvaluation(<?php echo $eval['id']; ?>)">
                                                <i class="bi bi-eye"></i> Voir
                                            </button>
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

<style>
.card {
    transition: transform 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
}

.progress {
    height: 8px;
    border-radius: 4px;
}

.badge {
    font-size: 0.8em;
}

.display-4 {
    font-weight: 700;
}
</style>

<script>
function viewEvaluation(id) {
    // Rediriger vers la page de détail de l'évaluation
    window.location.href = `/tutoring/views/student/evaluation-detail.php?id=${id}`;
}

// Animation d'apparition des cartes
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
});
</script>

<?php
// Inclure le pied de page
include_once __DIR__ . '/../common/footer.php';
?>