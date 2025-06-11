<?php
/**
 * Liste des conversations (système virtuel)
 * GET /api/messages/conversations
 */

require_once __DIR__ . '/../utils.php';

// Vérifier l'authentification
requireApiAuth();

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJsonError('Méthode non autorisée', 405);
}

try {
    // Récupérer les paramètres de requête
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $unreadOnly = isset($_GET['unread']) && $_GET['unread'] === 'true';
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';

    // Valider les paramètres
    if ($page < 1) $page = 1;
    if ($limit < 1 || $limit > 50) $limit = 10;

    // Récupérer l'utilisateur actuel
    $currentUserId = $_SESSION['user_id'];

    // Initialiser les modèles
    $messageModel = new Message($db);
    $userModel = new User($db);

    // Récupérer tous les messages de l'utilisateur pour créer des conversations virtuelles
    $query = "SELECT DISTINCT 
              CASE 
                WHEN m.sender_id = :user_id THEN m.receiver_id 
                ELSE m.sender_id 
              END as other_user_id,
              MAX(m.sent_at) as last_message_date
              FROM messages m
              WHERE (m.sender_id = :user_id2 OR m.receiver_id = :user_id3)
              AND m.status NOT IN ('sender_deleted', 'receiver_deleted')";

    // Ajouter la recherche si fournie
    if (!empty($search)) {
        $query .= " AND EXISTS (
            SELECT 1 FROM users u 
            WHERE u.id = CASE 
                WHEN m.sender_id = :user_id4 THEN m.receiver_id 
                ELSE m.sender_id 
            END
            AND (u.first_name LIKE :search OR u.last_name LIKE :search OR CONCAT(u.first_name, ' ', u.last_name) LIKE :search)
        )";
    }

    $query .= " GROUP BY other_user_id
                ORDER BY last_message_date DESC";

    // Ajouter la pagination
    $offset = ($page - 1) * $limit;
    $query .= " LIMIT :offset, :limit";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $currentUserId, PDO::PARAM_INT);
    $stmt->bindParam(':user_id2', $currentUserId, PDO::PARAM_INT);
    $stmt->bindParam(':user_id3', $currentUserId, PDO::PARAM_INT);
    
    if (!empty($search)) {
        $searchTerm = '%' . $search . '%';
        $stmt->bindParam(':user_id4', $currentUserId, PDO::PARAM_INT);
        $stmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
    }
    
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    $conversationUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Construire les conversations
    $conversations = [];
    
    foreach ($conversationUsers as $convUser) {
        $otherUserId = $convUser['other_user_id'];
        
        // Récupérer les informations de l'autre utilisateur
        $otherUser = $userModel->getById($otherUserId);
        if (!$otherUser) continue;
        
        // Créer l'ID de conversation virtuel
        $userIds = [$currentUserId, $otherUserId];
        sort($userIds);
        $conversationId = 'conv_' . implode('_', $userIds);
        
        // Récupérer le dernier message
        $lastMessageQuery = "SELECT m.*, 
                            s.first_name as sender_first_name, s.last_name as sender_last_name,
                            r.first_name as receiver_first_name, r.last_name as receiver_last_name
                            FROM messages m
                            JOIN users s ON m.sender_id = s.id
                            JOIN users r ON m.receiver_id = r.id
                            WHERE ((m.sender_id = :user_id AND m.receiver_id = :other_user_id) 
                               OR (m.sender_id = :other_user_id2 AND m.receiver_id = :user_id2))
                            AND m.status NOT IN ('sender_deleted', 'receiver_deleted')
                            ORDER BY m.sent_at DESC
                            LIMIT 1";
        
        $lastMsgStmt = $db->prepare($lastMessageQuery);
        $lastMsgStmt->bindParam(':user_id', $currentUserId, PDO::PARAM_INT);
        $lastMsgStmt->bindParam(':other_user_id', $otherUserId, PDO::PARAM_INT);
        $lastMsgStmt->bindParam(':other_user_id2', $otherUserId, PDO::PARAM_INT);
        $lastMsgStmt->bindParam(':user_id2', $currentUserId, PDO::PARAM_INT);
        $lastMsgStmt->execute();
        $lastMessage = $lastMsgStmt->fetch(PDO::FETCH_ASSOC);
        
        // Compter les messages non lus
        $unreadQuery = "SELECT COUNT(*) as unread_count
                       FROM messages
                       WHERE sender_id = :other_user_id 
                       AND receiver_id = :user_id
                       AND status = 'sent'";
        
        $unreadStmt = $db->prepare($unreadQuery);
        $unreadStmt->bindParam(':other_user_id', $otherUserId, PDO::PARAM_INT);
        $unreadStmt->bindParam(':user_id', $currentUserId, PDO::PARAM_INT);
        $unreadStmt->execute();
        $unreadResult = $unreadStmt->fetch(PDO::FETCH_ASSOC);
        $unreadCount = (int)$unreadResult['unread_count'];
        
        // Filtrer par messages non lus si demandé
        if ($unreadOnly && $unreadCount == 0) {
            continue;
        }
        
        // Construire l'objet conversation
        $conversation = [
            'id' => $conversationId,
            'title' => $otherUser['first_name'] . ' ' . $otherUser['last_name'],
            'is_group' => false,
            'participants' => [
                [
                    'id' => $otherUser['id'],
                    'name' => $otherUser['first_name'] . ' ' . $otherUser['last_name'],
                    'role' => $otherUser['role'],
                    'avatar' => !empty($otherUser['profile_image']) ? 
                        $otherUser['profile_image'] : 
                        "https://ui-avatars.com/api/?name=" . urlencode($otherUser['first_name'] . ' ' . $otherUser['last_name']) . "&background=4f46e5&color=fff",
                    'status' => 'online' // À implémenter avec un vrai système de statut
                ]
            ],
            'last_message' => null,
            'unread_count' => $unreadCount,
            'updated_at' => $convUser['last_message_date']
        ];
        
        // Ajouter le dernier message s'il existe
        if ($lastMessage) {
            $conversation['last_message'] = [
                'id' => $lastMessage['id'],
                'content' => $lastMessage['content'],
                'sent_at' => $lastMessage['sent_at'],
                'sender' => $lastMessage['sender_id'] == $currentUserId ? 'you' : $lastMessage['sender_first_name']
            ];
        }
        
        $conversations[] = $conversation;
    }

    // Compter le nombre total de conversations pour la pagination
    $countQuery = "SELECT COUNT(DISTINCT 
                   CASE 
                     WHEN m.sender_id = :user_id THEN m.receiver_id 
                     ELSE m.sender_id 
                   END) as total
                   FROM messages m
                   WHERE (m.sender_id = :user_id2 OR m.receiver_id = :user_id3)
                   AND m.status NOT IN ('sender_deleted', 'receiver_deleted')";

    if (!empty($search)) {
        $countQuery .= " AND EXISTS (
            SELECT 1 FROM users u 
            WHERE u.id = CASE 
                WHEN m.sender_id = :user_id4 THEN m.receiver_id 
                ELSE m.sender_id 
            END
            AND (u.first_name LIKE :search OR u.last_name LIKE :search OR CONCAT(u.first_name, ' ', u.last_name) LIKE :search)
        )";
    }

    $countStmt = $db->prepare($countQuery);
    $countStmt->bindParam(':user_id', $currentUserId, PDO::PARAM_INT);
    $countStmt->bindParam(':user_id2', $currentUserId, PDO::PARAM_INT);
    $countStmt->bindParam(':user_id3', $currentUserId, PDO::PARAM_INT);
    
    if (!empty($search)) {
        $countStmt->bindParam(':user_id4', $currentUserId, PDO::PARAM_INT);
        $countStmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
    }
    
    $countStmt->execute();
    $totalResult = $countStmt->fetch(PDO::FETCH_ASSOC);
    $total = (int)$totalResult['total'];

    // Calculer la pagination
    $totalPages = ceil($total / $limit);

    // Si on filtre par non lus, ajuster le total
    if ($unreadOnly) {
        $total = count($conversations);
        $totalPages = ceil($total / $limit);
    }

    // Envoyer la réponse
    sendJsonResponse([
        'data' => $conversations,
        'meta' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => $total,
            'per_page' => $limit
        ]
    ]);
    
} catch (Exception $e) {
    error_log('Error in conversations.php: ' . $e->getMessage());
    sendJsonError('Erreur lors de la récupération des conversations: ' . $e->getMessage(), 500);
}