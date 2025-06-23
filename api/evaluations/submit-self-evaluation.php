<?php
/**
 * API pour soumettre une auto-évaluation par un étudiant
 * Endpoint: /api/evaluations/submit-self-evaluation.php
 * Méthode: POST
 */

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../utils.php';

// Vérifier que l'utilisateur est connecté et est un étudiant
if (!isset($_SESSION['user_id'])) {
    sendJsonResponse([
        'error' => true,
        'message' => 'Non autorisé - Utilisateur non connecté'
    ], 401);
    exit;
}

// Vérifier que l'utilisateur est un étudiant
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'student') {
    sendJsonResponse([
        'error' => true,
        'message' => 'Accès non autorisé - Rôle étudiant requis'
    ], 403);
    exit;
}

// Vérifier que la requête est une méthode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse([
        'error' => true,
        'message' => 'Méthode non autorisée - Requête POST requise'
    ], 405);
    exit;
}

try {
    // Récupérer les données du formulaire
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        // Essayer de récupérer les données depuis $_POST
        $data = $_POST;
    }
    
    // Valider les données d'entrée
    $validation = validateApiInput($data, [
        'comments' => 'required|max:2000',
        'criteria' => 'required'
    ]);
    
    if ($validation !== true) {
        sendJsonResponse([
            'error' => true,
            'message' => 'Données invalides',
            'validation_errors' => $validation
        ], 400);
        exit;
    }
    
    // Récupérer l'ID de l'étudiant
    $studentModel = new Student($db);
    $student = $studentModel->getByUserId($_SESSION['user_id']);
    
    if (!$student) {
        sendJsonResponse([
            'error' => true,
            'message' => 'Profil étudiant non trouvé'
        ], 404);
        exit;
    }
    
    // Récupérer l'affectation active de l'étudiant
    $assignment = $studentModel->getAssignment($student['id']);
    
    if (!$assignment) {
        sendJsonResponse([
            'error' => true,
            'message' => 'Aucune affectation active trouvée pour cet étudiant'
        ], 404);
        exit;
    }
    
    // Initialiser le modèle d'évaluation
    $evaluationModel = new Evaluation($db);
    
    // Vérifier si une auto-évaluation existe déjà
    $existingEvaluation = $evaluationModel->exists($assignment['id'], 'student', $_SESSION['user_id']);
    
    if ($existingEvaluation) {
        sendJsonResponse([
            'error' => true,
            'message' => 'Une auto-évaluation a déjà été soumise. Veuillez contacter votre tuteur pour la modifier.'
        ], 409);
        exit;
    }
    
    // Formater les données de critères au format attendu
    $criteriaScores = [];
    
    if (isset($data['criteria']) && is_array($data['criteria'])) {
        // Traiter les critères tels qu'ils viennent du formulaire
        foreach ($data['criteria'] as $key => $value) {
            if (is_numeric($value)) {
                // Format simple key => score
                $criteriaScores[$key] = [
                    'score' => floatval($value),
                    'comment' => ''
                ];
            } else if (is_array($value) && isset($value['score'])) {
                // Format complet key => {score, comment}
                $criteriaScores[$key] = [
                    'score' => floatval($value['score']),
                    'comment' => isset($value['comment']) ? $value['comment'] : ''
                ];
            }
        }
    }
    
    // Si aucun critère n'a été fourni, initialiser avec la structure vide
    if (empty($criteriaScores)) {
        $criteriaScores = $evaluationModel->initEmptyCriteriaScores();
    }
    
    // Préparer les données pour la création de l'évaluation
    $evaluationData = [
        'assignment_id' => $assignment['id'],
        'evaluator_id' => $_SESSION['user_id'],
        'evaluatee_id' => $_SESSION['user_id'], // Auto-évaluation
        'type' => 'student',
        'status' => 'submitted',
        'comments' => $data['comments'] ?? '',
        'strengths' => $data['strengths'] ?? '',
        'areas_for_improvement' => $data['areas_for_improvement'] ?? '',
        'next_steps' => $data['next_steps'] ?? '',
        'criteria_scores' => $criteriaScores,
        'submission_date' => date('Y-m-d H:i:s')
    ];
    
    // Créer l'évaluation
    $evaluationId = $evaluationModel->create($evaluationData);
    
    if (!$evaluationId) {
        sendJsonResponse([
            'error' => true,
            'message' => 'Erreur lors de la création de l\'auto-évaluation'
        ], 500);
        exit;
    }
    
    // Notifier le tuteur
    if (class_exists('Notification')) {
        $notificationModel = new Notification($db);
        $teacherModel = new Teacher($db);
        $teacher = $teacherModel->getById($assignment['teacher_id']);
        
        if ($teacher && isset($teacher['user_id'])) {
            $notificationData = [
                'user_id' => $teacher['user_id'],
                'type' => 'evaluation',
                'content' => $_SESSION['user_first_name'] . ' ' . $_SESSION['user_last_name'] . ' a soumis une auto-évaluation.',
                'link' => '/tutoring/views/tutor/evaluations.php',
                'is_read' => 0
            ];
            
            $notificationModel->create($notificationData);
        }
    }
    
    // Enregistrer la réussite
    sendJsonResponse([
        'success' => true,
        'message' => 'Auto-évaluation soumise avec succès',
        'evaluation_id' => $evaluationId
    ]);
    
} catch (Exception $e) {
    error_log("Erreur API auto-évaluation: " . $e->getMessage());
    sendJsonResponse([
        'error' => true,
        'message' => 'Erreur lors de la soumission de l\'auto-évaluation: ' . $e->getMessage()
    ], 500);
}
?>