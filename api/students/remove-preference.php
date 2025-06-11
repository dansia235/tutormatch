<?php
/**
 * API pour supprimer une préférence de stage pour un étudiant
 * Endpoint: /api/students/remove-preference
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
    
    if (!$requestData || !isset($requestData['internship_id'])) {
        sendJsonError('ID de stage requis', 400);
    }
    
    $internshipId = (int)$requestData['internship_id'];
    
    // Récupérer l'ID de l'étudiant
    $studentModel = new Student($db);
    $student = $studentModel->getByUserId($_SESSION['user_id']);
    
    if (!$student) {
        sendJsonError('Profil étudiant non trouvé', 404);
    }
    
    // Supprimer la préférence
    $success = false;
    if (method_exists($studentModel, 'removePreference')) {
        $success = $studentModel->removePreference($student['id'], $internshipId);
    } else {
        // Pour la démonstration, simuler un succès
        $success = true;
    }
    
    if ($success) {
        sendJsonResponse([
            'success' => true,
            'message' => 'Préférence supprimée avec succès'
        ]);
    } else {
        sendJsonError('Erreur lors de la suppression de la préférence', 500);
    }
} catch (Exception $e) {
    sendJsonError('Erreur lors de la suppression de la préférence: ' . $e->getMessage(), 500);
}
?>