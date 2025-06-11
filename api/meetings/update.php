<?php
/**
 * Mettre à jour une réunion
 * PUT /api/meetings/{id}
 */

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    sendError('Méthode non autorisée', 405);
}

// Vérifier que l'ID est présent
if (!isset($urlParts[2]) || !is_numeric($urlParts[2])) {
    sendError('ID de réunion invalide', 400);
}

$meetingId = (int)$urlParts[2];

// Récupérer les données de la requête
$requestBody = json_decode(file_get_contents('php://input'), true);
if (!$requestBody) {
    sendError('Données de mise à jour manquantes', 400);
}

// Initialiser les modèles
$meetingModel = new Meeting($db);
$userModel = new User($db);

// Récupérer la réunion
$meeting = $meetingModel->getById($meetingId);
if (!$meeting) {
    sendError('Réunion non trouvée', 404);
}

// Vérifier les permissions
$currentUserRole = $_SESSION['user_role'];
$currentUserId = $_SESSION['user_id'];

// Seuls l'organisateur, les administrateurs et les coordinateurs peuvent modifier une réunion
$isOrganizer = false;
$participants = $meetingModel->getParticipants($meetingId);

foreach ($participants as $participant) {
    if ($participant['user_id'] == $currentUserId && $participant['is_organizer'] == 1) {
        $isOrganizer = true;
        break;
    }
}

if (!$isOrganizer && $currentUserRole !== 'admin' && $currentUserRole !== 'coordinator') {
    sendError('Vous n\'êtes pas autorisé à modifier cette réunion', 403);
}

// Préparer les données à mettre à jour
$updateData = [];

// Champs pouvant être mis à jour
$updatableFields = [
    'title', 'description', 'date', 'start_time', 'end_time',
    'location', 'type', 'status', 'meeting_url', 'assignment_id'
];

foreach ($updatableFields as $field) {
    if (isset($requestBody[$field])) {
        $updateData[$field] = $requestBody[$field];
    }
}

// Valider le format de la date et des heures si fournis
if (isset($updateData['date']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $updateData['date'])) {
    sendError('Format de date invalide. Utilisez YYYY-MM-DD', 400);
}

if (isset($updateData['start_time']) && !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $updateData['start_time'])) {
    sendError('Format d\'heure de début invalide. Utilisez HH:MM ou HH:MM:SS', 400);
}

if (isset($updateData['end_time']) && !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $updateData['end_time'])) {
    sendError('Format d\'heure de fin invalide. Utilisez HH:MM ou HH:MM:SS', 400);
}

// Vérifier que l'heure de fin est après l'heure de début si les deux sont modifiées
if (isset($updateData['date']) && isset($updateData['start_time']) && isset($updateData['end_time'])) {
    $startDateTime = new DateTime($updateData['date'] . ' ' . $updateData['start_time']);
    $endDateTime = new DateTime($updateData['date'] . ' ' . $updateData['end_time']);
    
    if ($endDateTime <= $startDateTime) {
        sendError('L\'heure de fin doit être postérieure à l\'heure de début', 400);
    }
} elseif (isset($updateData['start_time']) && isset($updateData['end_time'])) {
    // Si seules les heures sont modifiées
    $startDateTime = new DateTime($meeting['date'] . ' ' . $updateData['start_time']);
    $endDateTime = new DateTime($meeting['date'] . ' ' . $updateData['end_time']);
    
    if ($endDateTime <= $startDateTime) {
        sendError('L\'heure de fin doit être postérieure à l\'heure de début', 400);
    }
} elseif (isset($updateData['date']) && isset($updateData['start_time'])) {
    // Si la date et l'heure de début sont modifiées
    $startDateTime = new DateTime($updateData['date'] . ' ' . $updateData['start_time']);
    $endDateTime = new DateTime($updateData['date'] . ' ' . $meeting['end_time']);
    
    if ($endDateTime <= $startDateTime) {
        sendError('L\'heure de fin doit être postérieure à l\'heure de début', 400);
    }
} elseif (isset($updateData['date']) && isset($updateData['end_time'])) {
    // Si la date et l'heure de fin sont modifiées
    $startDateTime = new DateTime($updateData['date'] . ' ' . $meeting['start_time']);
    $endDateTime = new DateTime($updateData['date'] . ' ' . $updateData['end_time']);
    
    if ($endDateTime <= $startDateTime) {
        sendError('L\'heure de fin doit être postérieure à l\'heure de début', 400);
    }
}

// Ajouter la date de mise à jour
$updateData['updated_at'] = date('Y-m-d H:i:s');

// Mettre à jour la réunion
$success = $meetingModel->update($meetingId, $updateData);
if (!$success) {
    sendError('Échec de la mise à jour de la réunion', 500);
}

// Récupérer la réunion mise à jour
$updatedMeeting = $meetingModel->getById($meetingId);
$participants = $meetingModel->getParticipants($meetingId);

// Enrichir les données de la réunion
$enrichedMeeting = $updatedMeeting;
$participantDetails = [];

foreach ($participants as $participant) {
    $user = $userModel->getById($participant['user_id']);
    if ($user) {
        $participantDetails[] = [
            'id' => $participant['id'],
            'user_id' => $user['id'],
            'name' => $user['first_name'] . ' ' . $user['last_name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'status' => $participant['status'],
            'is_organizer' => $participant['is_organizer']
        ];
    }
}

$enrichedMeeting['participants'] = $participantDetails;

// Envoyer la réponse
sendJsonResponse([
    'message' => 'Réunion mise à jour avec succès',
    'data' => $enrichedMeeting
]);