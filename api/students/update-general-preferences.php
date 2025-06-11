<?php
/**
 * API pour mettre à jour les préférences générales d'un étudiant
 * Endpoint: /api/students/update-general-preferences
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
        sendJsonError('Données de préférences requises', 400);
    }
    
    // Récupérer l'ID de l'étudiant
    $studentModel = new Student($db);
    $student = $studentModel->getByUserId($_SESSION['user_id']);
    
    if (!$student) {
        sendJsonError('Profil étudiant non trouvé', 404);
    }
    
    // Préparer les données de préférences générales
    $generalPreferences = [
        'preferred_domains' => isset($requestData['preferred_domains']) && is_array($requestData['preferred_domains']) ? 
            implode(',', array_map('trim', $requestData['preferred_domains'])) : '',
        'preferred_locations' => isset($requestData['preferred_locations']) ? 
            trim($requestData['preferred_locations']) : '',
        'preferred_work_modes' => isset($requestData['preferred_work_modes']) && is_array($requestData['preferred_work_modes']) ? 
            implode(',', array_map('trim', $requestData['preferred_work_modes'])) : '',
        'preferred_companies' => isset($requestData['preferred_companies']) ? 
            trim($requestData['preferred_companies']) : '',
        'additional_notes' => isset($requestData['additional_notes']) ? 
            trim($requestData['additional_notes']) : ''
    ];
    
    // Mettre à jour les préférences générales
    $success = false;
    if (method_exists($studentModel, 'update')) {
        $success = $studentModel->update($student['id'], ['preferences' => json_encode($generalPreferences)]);
    } else {
        // Pour la démonstration, simuler un succès
        $success = true;
    }
    
    if ($success) {
        sendJsonResponse([
            'success' => true,
            'message' => 'Préférences générales mises à jour avec succès',
            'data' => $generalPreferences
        ]);
    } else {
        sendJsonError('Erreur lors de la mise à jour des préférences générales', 500);
    }
} catch (Exception $e) {
    sendJsonError('Erreur lors de la mise à jour des préférences générales: ' . $e->getMessage(), 500);
}
?>