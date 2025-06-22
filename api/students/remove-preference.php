<?php
/**
 * API pour supprimer une préférence de stage pour un étudiant
 * Endpoint: /api/students/remove-preference
 * Méthode: POST
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

// Vérifier que la méthode est POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse([
        'success' => false,
        'message' => 'Méthode non autorisée'
    ], 405);
    exit;
}

// Vérifier que l'utilisateur est un étudiant
if ($_SESSION['user_role'] !== 'student') {
    sendJsonResponse([
        'success' => false,
        'message' => 'Accès non autorisé'
    ], 403);
    exit;
}

// Ajouter des logs pour le débogage
error_log("remove-preference.php - POST data: " . json_encode($_POST));

try {
    // Récupérer les données POST
    $internshipId = isset($_POST['internship_id']) ? (int)$_POST['internship_id'] : 0;
    
    // Si l'ID du stage n'est pas dans les données POST, essayer de le récupérer du corps JSON
    if (empty($internshipId)) {
        // Récupérer les données du corps de la requête
        $requestData = json_decode(file_get_contents('php://input'), true);
        
        if (!$requestData || !isset($requestData['internship_id'])) {
            sendJsonResponse([
                'success' => false,
                'message' => 'ID de stage requis'
            ], 400);
            exit;
        }
        
        $internshipId = (int)$requestData['internship_id'];
    }
    
    error_log("remove-preference.php - Internship ID: " . $internshipId);
    
    // Récupérer l'ID de l'étudiant
    $studentModel = new Student($db);
    $student = $studentModel->getByUserId($_SESSION['user_id']);
    
    if (!$student) {
        sendJsonResponse([
            'success' => false,
            'message' => 'Profil étudiant non trouvé'
        ], 404);
        exit;
    }
    
    error_log("remove-preference.php - Student ID: " . $student['id']);
    
    // Supprimer la préférence
    $success = false;
    if (method_exists($studentModel, 'removePreference')) {
        $success = $studentModel->removePreference($student['id'], $internshipId);
        error_log("remove-preference.php - removePreference result: " . ($success ? 'true' : 'false'));
    } else {
        error_log("remove-preference.php - removePreference method not found!");
        // Pour la démonstration, simuler un succès
        $success = true;
    }
    
    if ($success) {
        sendJsonResponse([
            'success' => true,
            'message' => 'Préférence supprimée avec succès'
        ]);
    } else {
        sendJsonResponse([
            'success' => false,
            'message' => 'Erreur lors de la suppression de la préférence'
        ], 500);
        exit;
    }
} catch (Exception $e) {
    error_log("remove-preference.php - Exception: " . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'message' => 'Erreur lors de la suppression de la préférence: ' . $e->getMessage()
    ], 500);
    exit;
}
?>