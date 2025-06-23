<?php
/**
 * API pour créer ou mettre à jour une évaluation
 * Endpoint: /api/evaluations/save-evaluation.php
 * Méthode: POST
 */

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../utils.php';

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    sendJsonResponse([
        'error' => true,
        'message' => 'Non autorisé - Utilisateur non connecté'
    ], 401);
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
    
    // Vérifier le mode (création ou mise à jour)
    $isUpdate = isset($data['id']) && is_numeric($data['id']);
    
    // Valider les données d'entrée
    $validationRules = [
        'comments' => 'required|max:2000'
    ];
    
    // Pour une création, ces champs sont requis
    if (!$isUpdate) {
        $validationRules['assignment_id'] = 'required|numeric';
        $validationRules['type'] = 'required';
    }
    
    $validation = validateApiInput($data, $validationRules);
    
    if ($validation !== true) {
        sendJsonResponse([
            'error' => true,
            'message' => 'Données invalides',
            'validation_errors' => $validation
        ], 400);
        exit;
    }
    
    // Initialiser les modèles
    $evaluationModel = new Evaluation($db);
    $assignmentModel = new Assignment($db);
    
    // Vérifier les permissions
    if ($isUpdate) {
        // Récupérer l'évaluation existante
        $evaluation = $evaluationModel->getById($data['id']);
        
        if (!$evaluation) {
            sendJsonResponse([
                'error' => true,
                'message' => 'Évaluation non trouvée'
            ], 404);
            exit;
        }
        
        // Vérifier si l'utilisateur a le droit de modifier cette évaluation
        $canEdit = false;
        
        if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'coordinator') {
            $canEdit = true;
        } else if ($_SESSION['user_id'] === $evaluation['evaluator_id']) {
            // L'évaluateur peut modifier sa propre évaluation
            $canEdit = true;
        } else if ($_SESSION['user_role'] === 'teacher') {
            // Vérifier si l'utilisateur est le tuteur de cette affectation
            $teacherModel = new Teacher($db);
            $teacher = $teacherModel->getByUserId($_SESSION['user_id']);
            
            if ($teacher) {
                $assignment = $assignmentModel->getById($evaluation['assignment_id']);
                if ($assignment && $assignment['teacher_id'] === $teacher['id']) {
                    $canEdit = true;
                }
            }
        }
        
        if (!$canEdit) {
            sendJsonResponse([
                'error' => true,
                'message' => 'Vous n\'êtes pas autorisé à modifier cette évaluation'
            ], 403);
            exit;
        }
        
        $assignmentId = $evaluation['assignment_id'];
    } else {
        // Création d'une nouvelle évaluation
        $assignmentId = (int)$data['assignment_id'];
        
        // Récupérer l'affectation
        $assignment = $assignmentModel->getById($assignmentId);
        if (!$assignment) {
            sendJsonResponse([
                'error' => true,
                'message' => 'Affectation non trouvée'
            ], 404);
            exit;
        }
        
        // Vérifier si l'utilisateur a le droit de créer une évaluation pour cette affectation
        $canCreate = false;
        
        if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'coordinator') {
            $canCreate = true;
        } else if ($_SESSION['user_role'] === 'teacher') {
            // Vérifier si l'utilisateur est le tuteur de cette affectation
            $teacherModel = new Teacher($db);
            $teacher = $teacherModel->getByUserId($_SESSION['user_id']);
            
            if ($teacher && $assignment['teacher_id'] === $teacher['id']) {
                $canCreate = true;
            }
        } else if ($_SESSION['user_role'] === 'student') {
            // Vérifier si l'utilisateur est l'étudiant de cette affectation et que c'est une auto-évaluation
            $studentModel = new Student($db);
            $student = $studentModel->getByUserId($_SESSION['user_id']);
            
            if ($student && $assignment['student_id'] === $student['id'] && $data['type'] === 'student') {
                // Vérifier si une auto-évaluation existe déjà
                $existingEvaluation = $evaluationModel->exists($assignmentId, 'student', $_SESSION['user_id']);
                
                if ($existingEvaluation) {
                    sendJsonResponse([
                        'error' => true,
                        'message' => 'Une auto-évaluation a déjà été soumise'
                    ], 409);
                    exit;
                }
                
                $canCreate = true;
            }
        }
        
        if (!$canCreate) {
            sendJsonResponse([
                'error' => true,
                'message' => 'Vous n\'êtes pas autorisé à créer une évaluation pour cette affectation'
            ], 403);
            exit;
        }
    }
    
    // Formater les données de critères
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
    
    // Préparer les données pour l'évaluation
    $evaluationData = [
        'criteria_scores' => $criteriaScores,
        'comments' => $data['comments'],
        'strengths' => $data['strengths'] ?? '',
        'areas_for_improvement' => $data['areas_for_improvement'] ?? '',
        'next_steps' => $data['next_steps'] ?? '',
        'status' => $data['status'] ?? 'submitted'
    ];
    
    // Pour une création, ajouter les champs requis
    if (!$isUpdate) {
        $evaluationData['assignment_id'] = $assignmentId;
        $evaluationData['evaluator_id'] = $_SESSION['user_id'];
        $evaluationData['type'] = $data['type'];
        $evaluationData['submission_date'] = date('Y-m-d H:i:s');
        
        // Pour l'auto-évaluation, l'évaluateur est aussi l'évalué
        if ($data['type'] === 'student') {
            $evaluationData['evaluatee_id'] = $_SESSION['user_id'];
        } else {
            // Pour les autres types, l'évalué est l'étudiant de l'affectation
            $studentModel = new Student($db);
            $student = $studentModel->getById($assignment['student_id']);
            if ($student) {
                $evaluationData['evaluatee_id'] = $student['user_id'];
            }
        }
    }
    
    // Créer ou mettre à jour l'évaluation
    if ($isUpdate) {
        $success = $evaluationModel->update($data['id'], $evaluationData);
        $evaluationId = $data['id'];
    } else {
        $evaluationId = $evaluationModel->create($evaluationData);
        $success = $evaluationId !== false;
    }
    
    if (!$success) {
        sendJsonResponse([
            'error' => true,
            'message' => $isUpdate ? 'Erreur lors de la mise à jour de l\'évaluation' : 'Erreur lors de la création de l\'évaluation'
        ], 500);
        exit;
    }
    
    // Notifier l'étudiant pour les évaluations non auto-évaluées et soumises
    if (($evaluationData['status'] === 'submitted' || $evaluationData['status'] === 'approved') && 
        (!isset($data['type']) || $data['type'] !== 'student')) {
        
        // Créer une notification si la classe existe
        if (class_exists('Notification')) {
            $notificationModel = new Notification($db);
            
            // Récupérer l'étudiant de l'affectation
            $studentModel = new Student($db);
            $assignment = $assignmentModel->getById($assignmentId);
            $student = $assignment ? $studentModel->getById($assignment['student_id']) : null;
            
            if ($student) {
                $notificationData = [
                    'user_id' => $student['user_id'],
                    'type' => 'evaluation',
                    'content' => 'Une nouvelle évaluation a été soumise pour votre stage.',
                    'link' => '/tutoring/views/student/evaluations.php',
                    'is_read' => 0
                ];
                
                $notificationModel->create($notificationData);
            }
        }
    }
    
    // Envoyer la réponse
    sendJsonResponse([
        'success' => true,
        'message' => $isUpdate ? 'Évaluation mise à jour avec succès' : 'Évaluation créée avec succès',
        'evaluation_id' => $evaluationId
    ]);
    
} catch (Exception $e) {
    error_log("Erreur API sauvegarde évaluation: " . $e->getMessage());
    sendJsonResponse([
        'error' => true,
        'message' => 'Erreur lors de la sauvegarde de l\'évaluation: ' . $e->getMessage()
    ], 500);
}
?>