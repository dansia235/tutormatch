<?php
/**
 * API pour annuler une réunion
 * Endpoint: /api/meetings/cancel
 * Méthode: POST
 */

require_once __DIR__ . '/../utils.php';

// Vérifier que l'utilisateur est connecté
requireApiAuth();

// Vérifier que la méthode est POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonError('Méthode non autorisée', 405);
}

try {
    // Récupérer les données du corps de la requête
    $requestData = json_decode(file_get_contents('php://input'), true);
    
    if (!$requestData || !isset($requestData['meeting_id'])) {
        sendJsonError('ID de réunion requis', 400);
    }
    
    $meetingId = (int)$requestData['meeting_id'];
    
    // Récupérer l'ID de l'étudiant
    $studentModel = new Student($db);
    $student = $studentModel->getByUserId($_SESSION['user_id']);
    
    if (!$student) {
        sendJsonError('Profil étudiant non trouvé', 404);
    }
    
    // Récupérer la réunion
    $meetingModel = new Meeting($db);
    $meeting = $meetingModel->getById($meetingId);
    
    if (!$meeting) {
        sendJsonError('Réunion non trouvée', 404);
    }
    
    // Vérifier que la réunion appartient à l'étudiant
    if ($meeting['student_id'] != $student['id']) {
        sendJsonError('Vous n\'êtes pas autorisé à annuler cette réunion', 403);
    }
    
    // Vérifier que la réunion n'est pas déjà annulée ou terminée
    if ($meeting['status'] === 'cancelled' || $meeting['status'] === 'completed') {
        sendJsonError('Cette réunion ne peut pas être annulée', 400);
    }
    
    // Annuler la réunion
    if ($meetingModel->updateStatus($meetingId, 'cancelled')) {
        sendJsonResponse([
            'success' => true,
            'message' => 'Réunion annulée avec succès'
        ]);
    } else {
        sendJsonError('Erreur lors de l\'annulation de la réunion', 500);
    }
} catch (Exception $e) {
    sendJsonError('Erreur lors de l\'annulation de la réunion: ' . $e->getMessage(), 500);
}
?>