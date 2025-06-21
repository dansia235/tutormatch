<?php
/**
 * Vue simplifiée pour la gestion des évaluations par l'étudiant
 * Version de secours quand la page principale ne fonctionne pas
 */

// Titre de la page
$pageTitle = 'Mes évaluations (version simplifiée)';
$currentPage = 'evaluations';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../includes/init.php';

// Vérifier que l'utilisateur est étudiant
requireRole('student');

// Récupérer l'ID de l'utilisateur
$user_id = $_SESSION['user_id'] ?? null;

// Initialiser les variables
$student_id = null;
$evaluations = [];
$stats = [
    'average' => 0,
    'completed' => 0,
    'total_expected' => 5,
    'technical' => 0,
    'professional' => 0
];
$assignment = null;
$internship_id = null;

try {
    // Utiliser la connexion à la base de données globale
    global $db;
    
    // Récupérer l'ID de l'étudiant
    $query = "SELECT s.id FROM students s WHERE s.user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($student) {
        $student_id = $student['id'];
        
        // Récupérer l'affectation
        $query = "SELECT a.*, i.title as internship_title, i.id as internship_id 
                 FROM assignments a 
                 LEFT JOIN internships i ON a.internship_id = i.id 
                 WHERE a.student_id = :student_id 
                 ORDER BY a.assignment_date DESC 
                 LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->execute();
        $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $assignment_id = $assignment['id'] ?? null;
        $internship_id = $assignment['internship_id'] ?? null;
        
        // Récupérer les évaluations directement avec SQL
        if ($assignment_id) {
            $query = "SELECT * FROM evaluations WHERE assignment_id = :assignment_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':assignment_id', $assignment_id);
            $stmt->execute();
            $evalData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Formater les évaluations
            foreach ($evalData as $eval) {
                // Convertir le score de 20 à 5 si nécessaire
                $score = isset($eval['score']) && $eval['score'] > 5 ? round($eval['score'] / 4, 1) : $eval['score'];
                
                $evaluations[] = [
                    'id' => $eval['id'],
                    'type' => $eval['type'],
                    'date' => $eval['submission_date'] ?? date('Y-m-d'),
                    'score' => $score,
                    'comments' => $eval['comments'] ?? $eval['feedback'] ?? '',
                    'evaluator_name' => 'Tuteur',
                    'areas_for_improvement' => explode("\n", $eval['areas_to_improve'] ?? ''),
                    'criteria' => [] // On simplifie ici
                ];
            }
        }
        
        // Récupérer aussi les documents d'évaluation
        $query = "SELECT d.* FROM documents d WHERE d.user_id = :user_id AND d.type IN ('evaluation', 'self_evaluation', 'mid_term', 'final')";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $docData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Formater les documents comme des évaluations
        foreach ($docData as $doc) {
            $metadata = json_decode($doc['metadata'] ?? '{}', true) ?: [];
            
            $evaluations[] = [
                'id' => $doc['id'],
                'student_id' => $doc['user_id'],
                'type' => $doc['type'] === 'self_evaluation' ? 'self' : 'teacher',
                'date' => $doc['upload_date'] ?? date('Y-m-d'),
                'evaluator_name' => $metadata['evaluator_name'] ?? 'Système',
                'score' => $metadata['score'] ?? 0,
                'comments' => $doc['description'] ?? ($metadata['comments'] ?? ''),
                'criteria' => $metadata['criteria'] ?? [],
                'areas_for_improvement' => $metadata['areas_for_improvement'] ?? []
            ];
        }
    }
    
    // Calculer les statistiques
    if (!empty($evaluations)) {
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
        $stats['completed'] = count($evaluations);
        $stats['average'] = $stats['completed'] > 0 ? round($totalScore / $stats['completed'], 1) : 0;
        $stats['technical'] = $countTechnical > 0 ? round($totalTechnical / $countTechnical, 1) : 0;
        $stats['professional'] = $countProfessional > 0 ? round($totalProfessional / $countProfessional, 1) : 0;
    }
} catch (Exception $e) {
    $error = "Erreur: " . $e->getMessage();
}

// Inclure l'en-tête
include_once __DIR__ . '/../common/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-clipboard-check me-2"></i>Mes évaluations (version simplifiée)</h1>
        <a href="evaluations.php" class="btn btn-outline-primary">Version complète</a>
    </div>
    
    <?php if (isset($error)): ?>
    <div class="alert alert-danger">
        <?php echo h($error); ?>
    </div>
    <?php endif; ?>
    
    <!-- Version simplifiée des statistiques -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h3><?php echo h($stats['average']); ?></h3>
                    <p class="mb-0">Moyenne générale</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h3><?php echo h($stats['completed']); ?></h3>
                    <p class="mb-0">Évaluations complétées</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h3><?php echo h($stats['technical']); ?></h3>
                    <p class="mb-0">Compétences techniques</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h3><?php echo h($stats['professional']); ?></h3>
                    <p class="mb-0">Compétences professionnelles</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Liste simplifiée des évaluations -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Mes évaluations</h5>
            <?php if (isset($assignment) && !empty($assignment)): ?>
            <span class="badge bg-primary">Stage: <?php echo h($assignment['internship_title'] ?? 'Non assigné'); ?></span>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php if (empty($evaluations)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-clipboard-x display-1 text-muted mb-3"></i>
                    <h4>Aucune évaluation disponible</h4>
                    <p class="text-muted">Vous n'avez pas encore d'évaluations.</p>
                </div>
            <?php else: ?>
                <div class="list-group">
                    <?php foreach ($evaluations as $eval): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="mb-0">
                                    <?php 
                                        $typeLabel = '';
                                        if ($eval['type'] === 'self') {
                                            $typeLabel = 'Auto-évaluation';
                                        } elseif ($eval['type'] === 'mid_term') {
                                            $typeLabel = 'Évaluation mi-parcours';
                                        } elseif ($eval['type'] === 'final') {
                                            $typeLabel = 'Évaluation finale';
                                        } else {
                                            $typeLabel = 'Évaluation';
                                        }
                                        echo h($typeLabel);
                                    ?>
                                </h5>
                                <span class="badge bg-primary"><?php echo date('d/m/Y', strtotime($eval['date'])); ?></span>
                            </div>
                            
                            <div class="mb-2">
                                <strong>Score:</strong> <?php echo h($eval['score']); ?>/5
                                <span class="ms-2">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="bi <?php echo ($i <= $eval['score']) ? 'bi-star-fill text-warning' : 'bi-star text-muted'; ?>"></i>
                                    <?php endfor; ?>
                                </span>
                            </div>
                            
                            <?php if (!empty($eval['comments'])): ?>
                                <div class="mb-2">
                                    <strong>Commentaires:</strong>
                                    <p class="mb-0"><?php echo nl2br(h($eval['comments'])); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($eval['areas_for_improvement'])): ?>
                                <div class="mb-2">
                                    <strong>Points à améliorer:</strong>
                                    <ul class="mb-0">
                                        <?php foreach ((array)$eval['areas_for_improvement'] as $area): ?>
                                            <?php if (trim($area)): ?>
                                                <li><?php echo h($area); ?></li>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Bouton d'auto-évaluation -->
    <div class="text-center mt-4">
        <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#selfEvaluationModal">
            <i class="bi bi-pencil me-2"></i>Faire mon auto-évaluation
        </a>
    </div>
    
    <!-- Modal d'auto-évaluation (version simplifiée) -->
    <div class="modal fade" id="selfEvaluationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Auto-évaluation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="/tutoring/api/evaluations/submit-self-evaluation.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="student_id" value="<?php echo h($student_id); ?>">
                        
                        <!-- Compétences techniques -->
                        <div class="mb-3">
                            <label class="form-label">Compétences techniques</label>
                            <select class="form-select" name="criteria[technical_skills]" required>
                                <option value="" disabled selected>Sélectionnez une note</option>
                                <option value="1">1 - Insuffisant</option>
                                <option value="2">2 - Passable</option>
                                <option value="3">3 - Satisfaisant</option>
                                <option value="4">4 - Très bien</option>
                                <option value="5">5 - Excellent</option>
                            </select>
                        </div>
                        
                        <!-- Compétences professionnelles -->
                        <div class="mb-3">
                            <label class="form-label">Compétences professionnelles</label>
                            <select class="form-select" name="criteria[professional_skills]" required>
                                <option value="" disabled selected>Sélectionnez une note</option>
                                <option value="1">1 - Insuffisant</option>
                                <option value="2">2 - Passable</option>
                                <option value="3">3 - Satisfaisant</option>
                                <option value="4">4 - Très bien</option>
                                <option value="5">5 - Excellent</option>
                            </select>
                        </div>
                        
                        <!-- Commentaires -->
                        <div class="mb-3">
                            <label class="form-label">Commentaires</label>
                            <textarea class="form-control" name="comments" rows="4" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Soumettre</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<?php
// Inclure le pied de page
include_once __DIR__ . '/../common/footer.php';
?>