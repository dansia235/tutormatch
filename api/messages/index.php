<?php
/**
 * Liste des messages
 * GET /api/messages
 */

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Méthode non autorisée', 405);
}

// Récupérer les paramètres de requête
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$conversationId = isset($_GET['conversation_id']) ? (int)$_GET['conversation_id'] : null;
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
$unreadOnly = isset($_GET['unread']) && $_GET['unread'] === 'true';

// Valider les paramètres
if ($page < 1) $page = 1;
if ($limit < 1 || $limit > 100) $limit = 20;

// Initialiser le modèle de messages
$messageModel = new Message($db);
$userModel = new User($db);

// Vérifier les permissions
$currentUserRole = $_SESSION['user_role'];
$currentUserId = $_SESSION['user_id'];

// Construire les options de requête
$options = [
    'page' => $page,
    'limit' => $limit
];

// Par défaut, n'afficher que les messages de l'utilisateur connecté
if (!$userId) {
    $userId = $currentUserId;
}

// Si on demande les messages d'un autre utilisateur, vérifier les permissions
if ($userId != $currentUserId && $currentUserRole !== 'admin' && $currentUserRole !== 'coordinator') {
    sendError('Vous n\'êtes pas autorisé à voir les messages d\'autres utilisateurs', 403);
}

$options['user_id'] = $userId;

// Filtrer par conversation si spécifié
if ($conversationId) {
    // Vérifier que l'utilisateur fait partie de cette conversation
    $isInConversation = $messageModel->isUserInConversation($conversationId, $userId);
    if (!$isInConversation && $currentUserRole !== 'admin' && $currentUserRole !== 'coordinator') {
        sendError('Vous n\'êtes pas autorisé à voir cette conversation', 403);
    }
    
    $options['conversation_id'] = $conversationId;
}

// Filtrer les messages non lus si demandé
if ($unreadOnly) {
    $options['unread'] = true;
}

// Récupérer les messages selon les filtres
$messages = $messageModel->getAll($options);
$total = $messageModel->countAll($options);

// Calculer la pagination
$totalPages = ceil($total / $limit);

// Enrichir les données avec les informations associées
$enrichedMessages = [];
foreach ($messages as $message) {
    // Récupérer les détails de l'expéditeur
    $sender = $userModel->getById($message['sender_id']);
    
    $enrichedMessage = $message;
    $enrichedMessage['sender'] = $sender ? [
        'id' => $sender['id'],
        'name' => $sender['first_name'] . ' ' . $sender['last_name'],
        'email' => $sender['email'],
        'role' => $sender['role']
    ] : null;
    
    // Récupérer les destinataires si ce n'est pas une conversation individuelle
    if ($message['is_group']) {
        $recipients = $messageModel->getRecipients($message['id']);
        $recipientDetails = [];
        
        foreach ($recipients as $recipient) {
            $user = $userModel->getById($recipient['user_id']);
            if ($user) {
                $recipientDetails[] = [
                    'id' => $user['id'],
                    'name' => $user['first_name'] . ' ' . $user['last_name'],
                    'read_at' => $recipient['read_at']
                ];
            }
        }
        
        $enrichedMessage['recipients'] = $recipientDetails;
    } else {
        // Pour les conversations individuelles, ajouter le destinataire
        $recipient = $messageModel->getRecipient($message['id'], $userId == $message['sender_id'] ? $message['recipient_id'] : $userId);
        if ($recipient) {
            $user = $userModel->getById($recipient['user_id']);
            $enrichedMessage['recipient'] = $user ? [
                'id' => $user['id'],
                'name' => $user['first_name'] . ' ' . $user['last_name'],
                'read_at' => $recipient['read_at']
            ] : null;
        }
    }
    
    $enrichedMessages[] = $enrichedMessage;
}

// Envoyer la réponse
sendJsonResponse([
    'data' => $enrichedMessages,
    'meta' => [
        'current_page' => $page,
        'total_pages' => $totalPages,
        'total_records' => $total,
        'per_page' => $limit
    ]
]);