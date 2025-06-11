<?php
/**
 * API pour récupérer les messages d'une conversation par son ID
 * Endpoint: /api/messages/conversation-by-id.php
 * Méthode: GET
 * 
 * Paramètres:
 *  - id: ID de la conversation
 */

require_once __DIR__ . '/../../includes/init.php';

// Vérifier que l'utilisateur est connecté
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Vérifier la méthode de requête
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Vérifier les paramètres
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID de conversation manquant']);
    exit;
}

$conversationId = $_GET['id'];
$userId = $_SESSION['user_id'];

try {
    // Initialiser les modèles
    $messageModel = new Message($db);
    
    // Vérifier que la conversation existe
    $conversation = null;
    $hasAccess = false;
    
    try {
        $conversation = $messageModel->getConversationById($conversationId);
        if ($conversation) {
            // Vérifier que l'utilisateur a accès à cette conversation
            $participants = $messageModel->getConversationParticipants($conversationId);
            foreach ($participants as $participant) {
                if ($participant['user_id'] == $userId) {
                    $hasAccess = true;
                    break;
                }
            }
        }
    } catch (Exception $e) {
        error_log("Error checking conversation: " . $e->getMessage());
        $hasAccess = true; // Assume access for direct messages
    }
    
    // Pour les conversations virtuelles (conv_X_Y), vérifier l'accès
    if (strpos($conversationId, 'conv_') === 0) {
        $parts = explode('_', $conversationId);
        if (count($parts) >= 3) {
            $user1 = (int)$parts[1];
            $user2 = (int)$parts[2];
            $hasAccess = ($user1 == $userId || $user2 == $userId);
        }
    }
    
    if (!$hasAccess && $conversation) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Accès non autorisé à cette conversation']);
        exit;
    }
    
    // Récupérer les messages de la conversation
    $query = "";
    $params = [];
    
    // Vérifie si nous devons utiliser conversation_id ou chercher par expéditeur/destinataire
    if (is_numeric($conversationId) && $conversationId > 0) {
        // Essayons d'abord avec l'ID de conversation
        $query = "SELECT m.*, 
                  s.id as sender_id, s.first_name as sender_first_name, s.last_name as sender_last_name, s.role as sender_role,
                  r.id as receiver_id, r.first_name as receiver_first_name, r.last_name as receiver_last_name, r.role as receiver_role
                  FROM messages m
                  JOIN users s ON m.sender_id = s.id
                  JOIN users r ON m.receiver_id = r.id
                  WHERE m.conversation_id = :conversation_id
                  ORDER BY m.sent_at ASC";
        $params[':conversation_id'] = $conversationId;
    } else {
        // Format pour conversations sans ID valide (utilise le format conv_X_Y)
        $parts = explode('_', $conversationId);
        if (count($parts) >= 3 && $parts[0] === 'conv') {
            // Extrait les IDs des utilisateurs
            $user1 = (int)$parts[1];
            $user2 = (int)$parts[2];
            
            // Requête pour trouver les messages entre ces deux utilisateurs
            $query = "SELECT m.*, 
                      s.id as sender_id, s.first_name as sender_first_name, s.last_name as sender_last_name, s.role as sender_role,
                      r.id as receiver_id, r.first_name as receiver_first_name, r.last_name as receiver_last_name, r.role as receiver_role
                      FROM messages m
                      JOIN users s ON m.sender_id = s.id
                      JOIN users r ON m.receiver_id = r.id
                      WHERE (
                        (m.sender_id = :user1_id AND m.receiver_id = :user2_id) OR
                        (m.sender_id = :user2_id2 AND m.receiver_id = :user1_id2)
                      )
                      ORDER BY m.sent_at ASC";
            
            $params[':user1_id'] = $user1;
            $params[':user1_id2'] = $user1;
            $params[':user2_id'] = $user2;
            $params[':user2_id2'] = $user2;
        } else {
            // Fallback pour les cas non gérés
            $query = "SELECT m.*, 
                      s.id as sender_id, s.first_name as sender_first_name, s.last_name as sender_last_name, s.role as sender_role,
                      r.id as receiver_id, r.first_name as receiver_first_name, r.last_name as receiver_last_name, r.role as receiver_role
                      FROM messages m
                      JOIN users s ON m.sender_id = s.id
                      JOIN users r ON m.receiver_id = r.id
                      WHERE m.id IN (
                          SELECT MAX(id) FROM messages 
                          WHERE sender_id = :user_id OR receiver_id = :user_id2
                          GROUP BY LEAST(sender_id, receiver_id), GREATEST(sender_id, receiver_id)
                          ORDER BY MAX(sent_at) DESC
                          LIMIT 20
                      )
                      ORDER BY m.sent_at ASC";
            
            $params[':user_id'] = $userId;
            $params[':user_id2'] = $userId;
        }
    }
    
    // Exécuter la requête
    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formater les messages pour l'affichage
    $formattedMessages = [];
    foreach ($messages as $message) {
        $isCurrentUser = $message['sender_id'] == $userId;
        
        // Si l'utilisateur est le destinataire et que le message n'est pas lu, le marquer comme lu
        if (!$isCurrentUser && $message['status'] !== 'read' && $message['receiver_id'] == $userId) {
            $messageModel->markAsRead($message['id'], $userId);
        }
        
        $senderName = $message['sender_first_name'] . ' ' . $message['sender_last_name'];
        $receiverName = $message['receiver_first_name'] . ' ' . $message['receiver_last_name'];
        
        $formattedMessages[] = [
            'id' => $message['id'],
            'sender_id' => $message['sender_id'],
            'sender_first_name' => $message['sender_first_name'],
            'sender_last_name' => $message['sender_last_name'],
            'sender_name' => $senderName,
            'receiver_id' => $message['receiver_id'],
            'receiver_first_name' => $message['receiver_first_name'],
            'receiver_last_name' => $message['receiver_last_name'],
            'receiver_name' => $receiverName,
            'content' => $message['content'],
            'sent_at' => $message['sent_at'],
            'created_at' => $message['sent_at'], // Ajouter created_at pour compatibilité
            'status' => $message['status'],
            'is_read' => $message['status'] === 'read' || $isCurrentUser,
            'avatar' => "https://ui-avatars.com/api/?name=" . urlencode(mb_substr($message['sender_first_name'], 0, 1) . mb_substr($message['sender_last_name'], 0, 1)) . "&background=" . ($isCurrentUser ? '2ecc71' : '3498db') . "&color=fff"
        ];
    }
    
    // Log pour débogage
    error_log("Getting conversation with ID: " . $conversationId);
    error_log("Conversation found: " . ($conversation ? "Yes" : "No"));
    
    // Construire la réponse
    $response = [
        'success' => true,
        'messages' => $formattedMessages,
        'debug' => [
            'conversation_id' => $conversationId,
            'query_type' => is_numeric($conversationId) && $conversationId > 0 ? 'by_conversation_id' : 
                           (isset($parts) && count($parts) >= 3 && $parts[0] === 'conv' ? 'by_user_pair' : 'fallback'),
            'message_count' => count($messages),
            'formatted_count' => count($formattedMessages),
            'user_id' => $userId
        ]
    ];
    
    // Ajouter les détails de la conversation si elle existe
    if ($conversation) {
        $response['conversation'] = [
            'id' => $conversation['id'],
            'title' => $conversation['title'],
            'is_group' => $conversation['is_group'],
            'created_at' => $conversation['created_at'],
            'updated_at' => $conversation['updated_at']
        ];
    } else {
        // Pour les messages directs sans conversation enregistrée
        $title = 'Conversation';
        
        // Si nous avons des messages, utiliser le nom du contact
        if (!empty($formattedMessages)) {
            $firstMessage = $formattedMessages[0];
            $contactName = $firstMessage['sender_id'] == $userId ? 
                $firstMessage['receiver_name'] : $firstMessage['sender_name'];
            $title = 'Conversation avec ' . $contactName;
        }
        
        $response['conversation'] = [
            'id' => $conversationId,
            'title' => $title,
            'is_group' => false,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
    }
    
    // Envoyer la réponse
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log('Error in conversation-by-id.php: ' . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération des messages: ' . $e->getMessage()]);
}