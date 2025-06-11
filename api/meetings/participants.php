<?php
/**
 * Gestion des participants à une réunion
 * GET /api/meetings/{id}/participants - Récupérer les participants
 * POST /api/meetings/{id}/participants - Ajouter des participants
 */

// Vérifier les méthodes HTTP autorisées
if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Méthode non autorisée', 405);
}

// Vérifier que l'ID de la réunion est présent
if (!isset($urlParts[2]) || !is_numeric($urlParts[2])) {
    sendError('ID de réunion invalide', 400);
}

$meetingId = (int)$urlParts[2];

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

// Vérifier si l'utilisateur est participant ou organisateur
$isParticipant = false;
$isOrganizer = false;
$participants = $meetingModel->getParticipants($meetingId);

foreach ($participants as $participant) {
    if ($participant['user_id'] == $currentUserId) {
        $isParticipant = true;
        if ($participant['is_organizer'] == 1) {
            $isOrganizer = true;
        }
        break;
    }
}

// Pour GET, tout participant peut voir les autres participants
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Vérifier si l'utilisateur a accès à la réunion
    if (!$isParticipant && $currentUserRole !== 'admin' && $currentUserRole !== 'coordinator') {
        sendError('Accès non autorisé', 403);
    }
    
    // Récupérer tous les participants avec leurs détails
    $participants = $meetingModel->getParticipants($meetingId);
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
                'is_organizer' => $participant['is_organizer'],
                'joined_at' => $participant['joined_at']
            ];
        }
    }
    
    // Envoyer la réponse
    sendJsonResponse([
        'data' => $participantDetails
    ]);
} 
// Pour POST, seul l'organisateur, les admins et les coordinateurs peuvent ajouter des participants
else {
    // Vérifier si l'utilisateur est autorisé à ajouter des participants
    if (!$isOrganizer && $currentUserRole !== 'admin' && $currentUserRole !== 'coordinator') {
        sendError('Vous n\'êtes pas autorisé à ajouter des participants', 403);
    }
    
    // Récupérer les données de la requête
    $requestBody = json_decode(file_get_contents('php://input'), true);
    if (!$requestBody || !isset($requestBody['participants']) || !is_array($requestBody['participants'])) {
        sendError('Données de participants manquantes ou invalides', 400);
    }
    
    $newParticipants = $requestBody['participants'];
    
    // Récupérer les participants existants pour éviter les doublons
    $existingParticipants = $meetingModel->getParticipants($meetingId);
    $existingUserIds = array_column($existingParticipants, 'user_id');
    
    $addedParticipants = [];
    $skippedParticipants = [];
    
    foreach ($newParticipants as $participantData) {
        // Vérifier que l'ID utilisateur est fourni
        if (!isset($participantData['user_id']) || !is_numeric($participantData['user_id'])) {
            continue;
        }
        
        $userId = (int)$participantData['user_id'];
        
        // Vérifier que l'utilisateur existe
        $user = $userModel->getById($userId);
        if (!$user) {
            $skippedParticipants[] = [
                'user_id' => $userId,
                'reason' => 'Utilisateur non trouvé'
            ];
            continue;
        }
        
        // Vérifier si l'utilisateur est déjà participant
        if (in_array($userId, $existingUserIds)) {
            $skippedParticipants[] = [
                'user_id' => $userId,
                'name' => $user['first_name'] . ' ' . $user['last_name'],
                'reason' => 'Déjà participant'
            ];
            continue;
        }
        
        // Préparer les données du participant
        $newParticipant = [
            'meeting_id' => $meetingId,
            'user_id' => $userId,
            'status' => isset($participantData['status']) ? $participantData['status'] : 'pending',
            'is_organizer' => isset($participantData['is_organizer']) ? (int)$participantData['is_organizer'] : 0,
            'joined_at' => isset($participantData['joined_at']) ? $participantData['joined_at'] : null
        ];
        
        // Ajouter le participant
        $participantId = $meetingModel->addParticipant($newParticipant);
        
        if ($participantId) {
            $addedParticipants[] = [
                'id' => $participantId,
                'user_id' => $userId,
                'name' => $user['first_name'] . ' ' . $user['last_name'],
                'email' => $user['email'],
                'role' => $user['role'],
                'status' => $newParticipant['status'],
                'is_organizer' => $newParticipant['is_organizer']
            ];
        } else {
            $skippedParticipants[] = [
                'user_id' => $userId,
                'name' => $user['first_name'] . ' ' . $user['last_name'],
                'reason' => 'Erreur lors de l\'ajout'
            ];
        }
    }
    
    // Envoyer la réponse
    sendJsonResponse([
        'message' => 'Participants ajoutés avec succès',
        'data' => [
            'added' => $addedParticipants,
            'skipped' => $skippedParticipants
        ]
    ]);
}