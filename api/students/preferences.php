<?php
/**
 * Préférences d'un étudiant
 * GET /api/students/{id}/preferences - Récupérer les préférences d'un étudiant
 * POST /api/students/{id}/preferences - Mettre à jour les préférences d'un étudiant
 */

// Vérifier les méthodes HTTP autorisées
if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Méthode non autorisée', 405);
}

// Vérifier que l'ID de l'étudiant est présent
if (!isset($urlParts[2]) || !is_numeric($urlParts[2])) {
    sendError('ID d\'étudiant invalide', 400);
}

$studentId = (int)$urlParts[2];

// Initialiser les modèles
$studentModel = new Student($db);
$userModel = new User($db);

// Vérifier que l'étudiant existe
$student = $studentModel->getById($studentId);
if (!$student) {
    sendError('Étudiant non trouvé', 404);
}

// Vérifier les permissions: admin, coordinateur ou l'étudiant lui-même
$currentUserRole = $_SESSION['user_role'];
$currentUserId = $_SESSION['user_id'];

// Récupérer l'ID de l'utilisateur associé à l'étudiant
$studentUser = $userModel->getById($student['user_id']);

if ($currentUserRole !== 'admin' && $currentUserRole !== 'coordinator' && $currentUserId != $studentUser['id']) {
    sendError('Accès non autorisé', 403);
}

// Traitement en fonction de la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Récupérer les préférences de l'étudiant
    $preferences = $studentModel->getPreferences($studentId);
    
    sendJsonResponse([
        'data' => $preferences
    ]);
} else {
    // Méthode POST - Mettre à jour les préférences
    $requestBody = json_decode(file_get_contents('php://input'), true);
    
    if (!$requestBody || !isset($requestBody['preferences'])) {
        sendError('Données de préférences manquantes', 400);
    }
    
    $preferences = $requestBody['preferences'];
    
    // Valider les préférences
    if (!is_array($preferences)) {
        sendError('Format de préférences invalide', 400);
    }
    
    // Mettre à jour les préférences
    $success = $studentModel->updatePreferences($studentId, $preferences);
    
    if (!$success) {
        sendError('Échec de la mise à jour des préférences', 500);
    }
    
    sendJsonResponse([
        'message' => 'Préférences mises à jour avec succès',
        'data' => $preferences
    ]);
}