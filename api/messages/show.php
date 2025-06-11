<?php
/**
 * Détails d'un message
 * GET /api/messages/{id}
 */

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Méthode non autorisée', 405);
}

// Vérifier que l'ID est présent
if (!isset($urlParts[2]) || !is_numeric($urlParts[2])) {
    sendError('ID de message invalide', 400);
}

$messageId = (int)$urlParts[2];

// Initialiser les modèles
$messageModel = new Message($db);
$userModel = new User($db);

// Récupérer le message
$message = $messageModel->getById($messageId);
if (!$message) {
    sendError('Message non trouvé', 404);
}

// Vérifier les permissions
$currentUserRole = $_SESSION['user_role'];
$currentUserId = $_SESSION['user_id'];

// Vérifier si l'utilisateur est autorisé à voir ce message
$isAuthorized = false;

// Administrateurs et coordinateurs peuvent voir tous les messages
if ($currentUserRole === 'admin' || $currentUserRole === 'coordinator') {
    $isAuthorized = true;
} 
// Vérifier si l'utilisateur est l'expéditeur
elseif ($message['sender_id'] == $currentUserId) {
    $isAuthorized = true;
} 
// Vérifier si l'utilisateur est un destinataire
else {
    $recipients = $messageModel->getRecipients($messageId);
    foreach ($recipients as $recipient) {
        if ($recipient['user_id'] == $currentUserId) {
            $isAuthorized = true;
            
            // Marquer le message comme lu s'il ne l'est pas déjà
            if (!$recipient['read_at']) {
                $messageModel->markAsRead($messageId, $currentUserId);
            }
            
            break;
        }
    }
}

if (!$isAuthorized) {
    sendError('Vous n\'êtes pas autorisé à voir ce message', 403);
}

// Enrichir les données du message
$enrichedMessage = $message;

// Informations sur l'expéditeur
$sender = $userModel->getById($message['sender_id']);
$enrichedMessage['sender'] = $sender ? [
    'id' => $sender['id'],
    'name' => $sender['first_name'] . ' ' . $sender['last_name'],
    'email' => $sender['email'],
    'role' => $sender['role']
] : null;

// Récupérer les détails des destinataires
$recipients = $messageModel->getRecipients($messageId);
$recipientDetails = [];

foreach ($recipients as $recipient) {
    $user = $userModel->getById($recipient['user_id']);
    if ($user) {
        $recipientDetails[] = [
            'id' => $user['id'],
            'name' => $user['first_name'] . ' ' . $user['last_name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'read_at' => $recipient['read_at']
        ];
    }
}

$enrichedMessage['recipients'] = $recipientDetails;

// Informations sur la conversation
$conversation = $messageModel->getConversationById($message['conversation_id']);
if ($conversation) {
    $enrichedMessage['conversation'] = [
        'id' => $conversation['id'],
        'title' => $conversation['title'],
        'is_group' => $conversation['is_group']
    ];
}

// Envoyer la réponse
sendJsonResponse([
    'data' => $enrichedMessage
]);