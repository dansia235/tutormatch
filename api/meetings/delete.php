<?php
/**
 * Supprimer une réunion
 * DELETE /api/meetings/{id}
 */

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    sendError('Méthode non autorisée', 405);
}

// Vérifier que l'ID est présent
if (!isset($urlParts[2]) || !is_numeric($urlParts[2])) {
    sendError('ID de réunion invalide', 400);
}

$meetingId = (int)$urlParts[2];

// Initialiser les modèles
$meetingModel = new Meeting($db);

// Récupérer la réunion
$meeting = $meetingModel->getById($meetingId);
if (!$meeting) {
    sendError('Réunion non trouvée', 404);
}

// Vérifier les permissions
$currentUserRole = $_SESSION['user_role'];
$currentUserId = $_SESSION['user_id'];

// Seuls l'organisateur, les administrateurs et les coordinateurs peuvent supprimer une réunion
$isOrganizer = false;
$participants = $meetingModel->getParticipants($meetingId);

foreach ($participants as $participant) {
    if ($participant['user_id'] == $currentUserId && $participant['is_organizer'] == 1) {
        $isOrganizer = true;
        break;
    }
}

if (!$isOrganizer && $currentUserRole !== 'admin' && $currentUserRole !== 'coordinator') {
    sendError('Vous n\'êtes pas autorisé à supprimer cette réunion', 403);
}

// Supprimer tous les participants de la réunion
$meetingModel->removeAllParticipants($meetingId);

// Supprimer la réunion
$success = $meetingModel->delete($meetingId);
if (!$success) {
    sendError('Échec de la suppression de la réunion', 500);
}

// Envoyer la réponse
sendJsonResponse([
    'message' => 'Réunion supprimée avec succès'
]);