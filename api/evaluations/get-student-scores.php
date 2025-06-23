<?php
/**
 * API pour récupérer les scores d'un étudiant
 * Endpoint: /api/evaluations/get-student-scores.php
 * Méthode: GET
 * 
 * Cette API récupère les scores de compétences stockés d'un étudiant
 * pour assurer la cohérence entre les vues étudiant et tuteur
 */

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../utils.php';

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    sendJsonResponse([
        'success' => false,
        'message' => 'Non autorisé - Utilisateur non connecté'
    ], 401);
    exit;
}

// Vérifier l'ID de l'étudiant
$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : null;

// Si l'utilisateur est un étudiant et qu'aucun ID n'est fourni, utiliser son propre ID
if (!$student_id && $_SESSION['user_role'] === 'student') {
    try {
        $studentModel = new Student($db);
        $student = $studentModel->getByUserId($_SESSION['user_id']);
        if ($student) {
            $student_id = $student['id'];
        }
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération de l'étudiant: " . $e->getMessage());
    }
}

if (!$student_id) {
    sendJsonResponse([
        'success' => false,
        'message' => 'ID étudiant manquant ou invalide'
    ], 400);
    exit;
}

try {
    // Récupérer les scores de l'étudiant
    $query = "SELECT * FROM student_scores WHERE student_id = :student_id ORDER BY last_updated DESC LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute(['student_id' => $student_id]);
    $scores = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Si aucun score n'est trouvé, initialiser les valeurs par défaut
    if (!$scores) {
        // Récupérer l'affectation
        $studentModel = new Student($db);
        $assignment = $studentModel->getAssignment($student_id);
        
        if ($assignment) {
            // Calculer les scores à la volée
            
            // Appeler l'API de mise à jour des scores
            $ch = curl_init();
            $payload = json_encode(['student_id' => $student_id]);
            
            curl_setopt($ch, CURLOPT_URL, 'http://' . $_SERVER['HTTP_HOST'] . '/tutoring/api/evaluations/update-student-scores.php');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
            
            $response = curl_exec($ch);
            curl_close($ch);
            
            // Récupérer les scores mis à jour
            $stmt = $db->prepare($query);
            $stmt->execute(['student_id' => $student_id]);
            $scores = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // Si toujours pas de scores, utiliser des valeurs par défaut
        if (!$scores) {
            $scores = [
                'student_id' => $student_id,
                'assignment_id' => $assignment['id'] ?? 0,
                'technical_score' => 0,
                'communication_score' => 0,
                'teamwork_score' => 0,
                'autonomy_score' => 0,
                'average_score' => 0,
                'completed_evaluations' => 0,
                'total_evaluations' => 5,
                'last_updated' => date('Y-m-d H:i:s')
            ];
        }
    }
    
    // Calculer la moyenne du score professionnel
    $professionalScore = 0;
    $professionalCount = 0;
    
    if ($scores['communication_score'] > 0) {
        $professionalScore += $scores['communication_score'];
        $professionalCount++;
    }
    
    if ($scores['teamwork_score'] > 0) {
        $professionalScore += $scores['teamwork_score'];
        $professionalCount++;
    }
    
    if ($scores['autonomy_score'] > 0) {
        $professionalScore += $scores['autonomy_score'];
        $professionalCount++;
    }
    
    $professionalAverage = $professionalCount > 0 ? 
        round($professionalScore / $professionalCount, 1) : 0;
    
    // Ajouter le score professionnel calculé
    $scores['professional_score'] = $professionalAverage;
    
    // Retourner les scores
    sendJsonResponse([
        'success' => true,
        'scores' => $scores
    ]);
    
} catch (Exception $e) {
    error_log("Erreur lors de la récupération des scores: " . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'message' => 'Erreur lors de la récupération des scores: ' . $e->getMessage()
    ], 500);
}
?>