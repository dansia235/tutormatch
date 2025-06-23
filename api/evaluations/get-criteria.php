<?php
/**
 * API pour récupérer les critères d'évaluation d'un étudiant
 * Endpoint: /api/evaluations/get-criteria.php
 * Méthode: GET
 * 
 * Paramètres:
 * - student_id: ID de l'étudiant (obligatoire)
 * - evaluation_id: ID de l'évaluation (optionnel)
 * - category: Catégorie de critères (technical, professional) (optionnel)
 */

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../utils.php';

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJsonResponse([
        'success' => false,
        'message' => 'Méthode non autorisée - Requête GET requise'
    ], 405);
    exit;
}

// Récupérer et valider les paramètres
$studentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : null;
$evaluationId = isset($_GET['evaluation_id']) ? (int)$_GET['evaluation_id'] : null;
$category = isset($_GET['category']) ? $_GET['category'] : null;

// Valider le format de catégorie
if ($category && !in_array($category, ['technical', 'professional'])) {
    sendJsonResponse([
        'success' => false,
        'message' => 'Catégorie invalide - Doit être "technical" ou "professional"'
    ], 400);
    exit;
}

// Au moins un paramètre d'identification est requis
if (!$studentId && !$evaluationId) {
    sendJsonResponse([
        'success' => false,
        'message' => 'Paramètre manquant - student_id ou evaluation_id requis'
    ], 400);
    exit;
}

try {
    if ($evaluationId) {
        // Récupérer les critères d'une évaluation spécifique
        $evaluationModel = new Evaluation($db);
        $criteria = $evaluationModel->getCriteria($evaluationId);
        
        // Filtrer par catégorie si nécessaire
        if ($category) {
            $criteria = array_filter($criteria, function($item) use ($category) {
                return $item['category'] === $category;
            });
        }
        
        sendJsonResponse([
            'success' => true,
            'evaluation_id' => $evaluationId,
            'criteria' => array_values($criteria)
        ]);
    } else {
        // Récupérer l'affectation de l'étudiant
        $studentModel = new Student($db);
        $assignment = $studentModel->getAssignment($studentId);
        
        if (!$assignment) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Aucune affectation trouvée pour cet étudiant'
            ], 404);
            exit;
        }
        
        // Récupérer les scores de l'étudiant
        $query = "SELECT * FROM student_scores WHERE student_id = :student_id AND assignment_id = :assignment_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':student_id', $studentId, PDO::PARAM_INT);
        $stmt->bindParam(':assignment_id', $assignment['id'], PDO::PARAM_INT);
        $stmt->execute();
        $scores = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Si aucun score n'est trouvé, lancer le calcul via l'API
        if (!$scores) {
            // Appeler l'API pour calculer les scores
            $updateScoresUrl = '/tutoring/api/evaluations/update-student-scores.php';
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $updateScoresUrl);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['student_id' => $studentId]));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            $result = curl_exec($ch);
            curl_close($ch);
            
            // Récupérer les scores mis à jour
            $stmt->execute();
            $scores = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // Préparer les résultats
        $result = [
            'success' => true,
            'student_id' => $studentId,
            'assignment_id' => $assignment['id']
        ];
        
        // Organiser les scores par catégorie
        if ($scores) {
            $technicalScores = [
                ['name' => 'Maîtrise des technologies', 'score' => floatval($scores['technical_mastery']), 'category' => 'technical'],
                ['name' => 'Qualité du travail', 'score' => floatval($scores['work_quality']), 'category' => 'technical'],
                ['name' => 'Résolution de problèmes', 'score' => floatval($scores['problem_solving']), 'category' => 'technical'],
                ['name' => 'Documentation', 'score' => floatval($scores['documentation']), 'category' => 'technical']
            ];
            
            $professionalScores = [
                ['name' => 'Communication', 'score' => floatval($scores['communication_score']), 'category' => 'professional'],
                ['name' => 'Intégration dans l\'équipe', 'score' => floatval($scores['teamwork_score']), 'category' => 'professional'],
                ['name' => 'Autonomie', 'score' => floatval($scores['autonomy']), 'category' => 'professional'],
                ['name' => 'Respect des délais', 'score' => floatval($scores['deadline_respect']), 'category' => 'professional']
            ];
            
            // Ajouter les statistiques globales
            $result['average_score'] = floatval($scores['average_score']);
            $result['technical_score'] = floatval($scores['technical_score']);
            $result['professional_score'] = isset($scores['teamwork_score']) ? 
                (floatval($scores['communication_score']) + floatval($scores['teamwork_score']) + floatval($scores['autonomy']) + floatval($scores['deadline_respect'])) / 4 :
                0;
            
            // Ajouter les critères selon le filtre
            if (!$category || $category === 'technical') {
                $result['technical_criteria'] = $technicalScores;
            }
            
            if (!$category || $category === 'professional') {
                $result['professional_criteria'] = $professionalScores;
            }
            
            // Ajouter les statistiques d'évaluation
            $result['completed_evaluations'] = (int)$scores['completed_evaluations'];
            $result['total_evaluations'] = (int)$scores['total_evaluations'];
            $result['progress'] = $scores['total_evaluations'] > 0 ? 
                round(($scores['completed_evaluations'] / $scores['total_evaluations']) * 100) : 0;
        } else {
            // Aucun score trouvé
            $result['average_score'] = 0;
            $result['technical_score'] = 0;
            $result['professional_score'] = 0;
            $result['technical_criteria'] = [];
            $result['professional_criteria'] = [];
            $result['completed_evaluations'] = 0;
            $result['total_evaluations'] = 5;
            $result['progress'] = 0;
        }
        
        sendJsonResponse($result);
    }
} catch (Exception $e) {
    error_log("Erreur lors de la récupération des critères: " . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'message' => 'Erreur lors de la récupération des critères: ' . $e->getMessage()
    ], 500);
}
?>