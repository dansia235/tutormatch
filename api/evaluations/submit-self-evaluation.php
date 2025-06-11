<?php
/**
 * API pour soumettre une auto-évaluation
 * Endpoint: /api/evaluations/submit-self-evaluation
 * Méthode: POST
 */

require_once __DIR__ . '/../utils.php';

// Vérifier que l'utilisateur est connecté
requireApiAuth();

// Vérifier que la méthode est POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonError('Méthode non autorisée', 405);
}

// Vérifier que l'utilisateur est un étudiant
if ($_SESSION['user_role'] !== 'student') {
    sendJsonError('Accès non autorisé', 403);
}

try {
    // Récupérer les données du corps de la requête
    $requestData = json_decode(file_get_contents('php://input'), true);
    
    if (!$requestData) {
        sendJsonError('Données invalides', 400);
    }
    
    // Vérifier les champs requis
    if (!isset($requestData['criteria']) || !is_array($requestData['criteria'])) {
        sendJsonError('Les critères d\'évaluation sont requis', 400);
    }
    
    // Récupérer l'ID de l'étudiant
    $studentModel = new Student($db);
    $student = $studentModel->getByUserId($_SESSION['user_id']);
    
    if (!$student) {
        sendJsonError('Profil étudiant non trouvé', 404);
    }
    
    // Préparer les données de l'auto-évaluation
    $evaluationData = [
        'student_id' => $student['id'],
        'evaluator_id' => $_SESSION['user_id'],
        'evaluator_type' => 'student',
        'type' => 'self-evaluation',
        'date' => date('Y-m-d'),
        'criteria' => $requestData['criteria'],
        'comments' => $requestData['comments'] ?? '',
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    // Calculer le score moyen
    $totalScore = 0;
    $criteriaCount = 0;
    
    foreach ($evaluationData['criteria'] as $criterion) {
        if (isset($criterion['score']) && is_numeric($criterion['score'])) {
            $totalScore += (float)$criterion['score'];
            $criteriaCount++;
        }
    }
    
    $evaluationData['score'] = $criteriaCount > 0 ? round($totalScore / $criteriaCount, 1) : 0;
    
    // Enregistrer l'auto-évaluation
    // Note: Dans un environnement réel, cela serait enregistré dans la base de données
    // Pour cet exemple, nous simulons simplement une réponse de succès
    
    // Réponse simulée
    sendJsonResponse([
        'success' => true,
        'message' => 'Auto-évaluation soumise avec succès',
        'evaluation' => [
            'id' => time(), // ID fictif
            'date' => date('Y-m-d'),
            'type' => 'self-evaluation',
            'score' => $evaluationData['score'],
            'criteria' => $evaluationData['criteria'],
            'comments' => $evaluationData['comments']
        ]
    ]);
} catch (Exception $e) {
    sendJsonError('Erreur lors de la soumission de l\'auto-évaluation: ' . $e->getMessage(), 500);
}
?>