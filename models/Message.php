<?php
/**
 * Modèle pour la gestion des messages
 */
class Message {
    private $db;
    
    /**
     * Constructeur
     * @param PDO $db Instance de connexion à la base de données
     */
    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Récupère un message par son ID
     * @param int $id ID du message
     * @return array|false Données du message si trouvé, sinon false
     */
    public function getById($id) {
        $query = "SELECT m.*, 
                  s.first_name as sender_first_name, s.last_name as sender_last_name, s.role as sender_role,
                  r.first_name as receiver_first_name, r.last_name as receiver_last_name, r.role as receiver_role
                  FROM messages m
                  JOIN users s ON m.sender_id = s.id
                  JOIN users r ON m.receiver_id = r.id
                  WHERE m.id = :id 
                  LIMIT 1";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère tous les messages selon les filtres
     * @param array $options Options de filtre
     * @return array Liste des messages
     */
    public function getAll($options = []) {
        $query = "SELECT m.*, 
                  s.first_name as sender_first_name, s.last_name as sender_last_name, s.role as sender_role,
                  r.first_name as receiver_first_name, r.last_name as receiver_last_name, r.role as receiver_role
                  FROM messages m
                  JOIN users s ON m.sender_id = s.id
                  JOIN users r ON m.receiver_id = r.id
                  WHERE 1=1";
        
        $params = [];
        
        // Filtre par expéditeur
        if (isset($options['sender_id'])) {
            $query .= " AND m.sender_id = :sender_id";
            $params[':sender_id'] = $options['sender_id'];
        }
        
        // Filtre par destinataire
        if (isset($options['receiver_id'])) {
            $query .= " AND m.receiver_id = :receiver_id";
            $params[':receiver_id'] = $options['receiver_id'];
        }
        
        // Filtre par utilisateur (expéditeur ou destinataire)
        if (isset($options['user_id'])) {
            $query .= " AND (m.sender_id = :user_id OR m.receiver_id = :user_id)";
            $params[':user_id'] = $options['user_id'];
        }
        
        // Filtre par statut
        if (isset($options['status'])) {
            $query .= " AND m.status = :status";
            $params[':status'] = $options['status'];
        }
        
        // Exclure les messages supprimés
        $query .= " AND m.status NOT IN ('sender_deleted', 'receiver_deleted')";
        
        // Tri par date de création (plus récent en premier)
        $query .= " ORDER BY m.sent_at DESC";
        
        // Pagination
        if (isset($options['page']) && isset($options['limit'])) {
            $offset = ($options['page'] - 1) * $options['limit'];
            $query .= " LIMIT :offset, :limit";
            $params[':offset'] = $offset;
            $params[':limit'] = $options['limit'];
        } elseif (isset($options['limit'])) {
            $query .= " LIMIT :limit";
            $params[':limit'] = $options['limit'];
        }
        
        $stmt = $this->db->prepare($query);
        
        foreach ($params as $key => $value) {
            if (strpos($key, 'limit') !== false || strpos($key, 'offset') !== false) {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value);
            }
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crée un nouveau message
     * @param array $data Données du message
     * @return int|false ID du message créé, sinon false
     */
    public function create($data) {
        try {
            $query = "INSERT INTO messages (sender_id, receiver_id, subject, content, sent_at, status) 
                      VALUES (:sender_id, :receiver_id, :subject, :content, :sent_at, :status)";
            
            $stmt = $this->db->prepare($query);
            
            $subject = isset($data['subject']) ? $data['subject'] : 'Nouveau message';
            $status = isset($data['status']) ? $data['status'] : 'sent';
            $sent_at = isset($data['sent_at']) ? $data['sent_at'] : date('Y-m-d H:i:s');
            
            $stmt->bindParam(':sender_id', $data['sender_id']);
            $stmt->bindParam(':receiver_id', $data['receiver_id']);
            $stmt->bindParam(':subject', $subject);
            $stmt->bindParam(':content', $data['content']);
            $stmt->bindParam(':sent_at', $sent_at);
            $stmt->bindParam(':status', $status);
            
            if ($stmt->execute()) {
                return $this->db->lastInsertId();
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("PDO Exception in Message::create: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Envoie un message
     * @param array $data Données du message
     * @return int|false ID du message créé, sinon false
     */
    public function send($data) {
        // S'assurer que tous les champs requis sont présents
        if (!isset($data['sender_id']) || !isset($data['receiver_id']) || !isset($data['content'])) {
            error_log("Message::send - Missing required fields");
            return false;
        }
        
        return $this->create($data);
    }

    /**
     * Marque un message comme lu
     * @param int $messageId ID du message
     * @param int $userId ID de l'utilisateur (optionnel)
     * @return bool Succès de l'opération
     */
    public function markAsRead($messageId, $userId = null) {
        try {
            $query = "UPDATE messages 
                      SET status = 'read', read_at = NOW() 
                      WHERE id = :message_id";
            
            if ($userId !== null) {
                $query .= " AND receiver_id = :receiver_id";
            }
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':message_id', $messageId);
            
            if ($userId !== null) {
                $stmt->bindParam(':receiver_id', $userId);
            }
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error in Message::markAsRead: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère les messages d'une conversation entre deux utilisateurs
     * @param int $userId ID de l'utilisateur actuel
     * @param string $userRole Rôle de l'utilisateur (ignoré)
     * @param int $contactId ID du contact
     * @param string $contactType Type du contact (ignoré)
     * @return array Liste des messages
     */
    public function getConversation($userId, $userRole, $contactId, $contactType) {
        try {
            // Utiliser directement les IDs fournis (qui doivent être des user_id)
            $query = "SELECT m.*, 
                      s.id as sender_id, s.first_name as sender_first_name, s.last_name as sender_last_name, s.role as sender_role,
                      r.id as receiver_id, r.first_name as receiver_first_name, r.last_name as receiver_last_name, r.role as receiver_role
                      FROM messages m
                      JOIN users s ON m.sender_id = s.id
                      JOIN users r ON m.receiver_id = r.id
                      WHERE (
                        (m.sender_id = :user_id AND m.receiver_id = :contact_id) OR
                        (m.sender_id = :contact_id2 AND m.receiver_id = :user_id2)
                      )
                      AND m.status NOT IN ('sender_deleted', 'receiver_deleted')
                      ORDER BY m.sent_at ASC";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':contact_id', $contactId);
            $stmt->bindParam(':contact_id2', $contactId);
            $stmt->bindParam(':user_id2', $userId);
            $stmt->execute();
            
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Marquer les messages reçus comme lus
            foreach ($messages as $message) {
                if ($message['receiver_id'] == $userId && $message['status'] !== 'read') {
                    $this->markAsRead($message['id'], $userId);
                }
            }
            
            return $messages;
            
        } catch (Exception $e) {
            error_log("Error in getConversation: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère le dernier message entre deux utilisateurs
     * @param int $userId ID de l'utilisateur
     * @param string $userType Type de l'utilisateur (ignoré)
     * @param int $contactId ID du contact
     * @param string $contactType Type du contact (ignoré)
     * @return array|false Données du message si trouvé, sinon false
     */
    public function getLastMessage($userId, $userType, $contactId, $contactType) {
        try {
            $query = "SELECT m.*, 
                      s.first_name as sender_first_name, s.last_name as sender_last_name,
                      r.first_name as receiver_first_name, r.last_name as receiver_last_name
                      FROM messages m
                      JOIN users s ON m.sender_id = s.id
                      JOIN users r ON m.receiver_id = r.id
                      WHERE (
                        (m.sender_id = :user_id AND m.receiver_id = :contact_id) OR
                        (m.sender_id = :contact_id2 AND m.receiver_id = :user_id2)
                      )
                      AND m.status NOT IN ('sender_deleted', 'receiver_deleted')
                      ORDER BY m.sent_at DESC
                      LIMIT 1";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':contact_id', $contactId);
            $stmt->bindParam(':contact_id2', $contactId);
            $stmt->bindParam(':user_id2', $userId);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error in getLastMessage: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Compte le nombre de messages non lus entre deux utilisateurs
     * @param int $userId ID de l'utilisateur
     * @param string $userType Type de l'utilisateur (ignoré)
     * @param int $contactId ID du contact
     * @param string $contactType Type du contact (ignoré)
     * @return int Nombre de messages non lus
     */
    public function getUnreadCount($userId, $userType, $contactId, $contactType) {
        try {
            $query = "SELECT COUNT(*) as count
                      FROM messages
                      WHERE sender_id = :contact_id 
                      AND receiver_id = :user_id
                      AND status = 'sent'";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':contact_id', $contactId);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? (int)$result['count'] : 0;
        } catch (Exception $e) {
            error_log("Error in getUnreadCount: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Récupère les conversations d'un utilisateur (format virtuel)
     * @param int $userId ID de l'utilisateur
     * @param array $options Options de filtre
     * @return array Liste des conversations
     */
    public function getConversationsByUserId($userId, $options = []) {
        try {
            // Récupérer tous les messages de l'utilisateur
            $query = "SELECT m.*, 
                      s.first_name as sender_first_name, s.last_name as sender_last_name, s.role as sender_role,
                      r.first_name as receiver_first_name, r.last_name as receiver_last_name, r.role as receiver_role,
                      m.id as message_id
                      FROM messages m
                      JOIN users s ON m.sender_id = s.id
                      JOIN users r ON m.receiver_id = r.id
                      WHERE (m.sender_id = :user_id OR m.receiver_id = :user_id2)
                      AND m.status NOT IN ('sender_deleted', 'receiver_deleted')
                      ORDER BY m.sent_at DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':user_id2', $userId);
            $stmt->execute();
            $allMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Grouper les messages par conversation
            $conversations = [];
            $processedConversations = [];
            
            foreach ($allMessages as $message) {
                // Créer un ID de conversation unique
                $participantIds = [$message['sender_id'], $message['receiver_id']];
                sort($participantIds);
                $conversationId = 'conv_' . implode('_', $participantIds);
                
                // Si on a déjà traité cette conversation, continuer
                if (isset($processedConversations[$conversationId])) {
                    continue;
                }
                
                // Déterminer l'autre participant
                $otherParticipantId = ($message['sender_id'] == $userId) ? $message['receiver_id'] : $message['sender_id'];
                $otherParticipantFirstName = ($message['sender_id'] == $userId) ? $message['receiver_first_name'] : $message['sender_first_name'];
                $otherParticipantLastName = ($message['sender_id'] == $userId) ? $message['receiver_last_name'] : $message['sender_last_name'];
                $otherParticipantRole = ($message['sender_id'] == $userId) ? $message['receiver_role'] : $message['sender_role'];
                
                // Créer l'entrée de conversation
                $conversation = [
                    'conversation_id' => $conversationId,
                    'conversation_title' => $message['subject'],
                    'is_group' => 0,
                    'created_at' => $message['sent_at'],
                    'message_id' => $message['message_id'],
                    'content' => $message['content'],
                    'sender_id' => $message['sender_id'],
                    'sent_at' => $message['sent_at'],
                    'sender_first_name' => $message['sender_first_name'],
                    'sender_last_name' => $message['sender_last_name'],
                    'sender_role' => $message['sender_role'],
                    'receiver_id' => $message['receiver_id'],
                    'receiver_first_name' => $message['receiver_first_name'],
                    'receiver_last_name' => $message['receiver_last_name'],
                    'receiver_role' => $message['receiver_role'],
                    'read_at' => $message['read_at'],
                    'is_read' => ($message['receiver_id'] == $userId && $message['status'] !== 'read') ? 0 : 1
                ];
                
                // Filtrer par messages non lus si demandé
                if (isset($options['unread']) && $options['unread'] === true && $conversation['is_read'] === 1) {
                    continue;
                }
                
                $conversations[] = $conversation;
                $processedConversations[$conversationId] = true;
            }
            
            return $conversations;
            
        } catch (Exception $e) {
            error_log('Error in getConversationsByUserId: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Supprime un message
     * @param int $id ID du message
     * @return bool Succès de l'opération
     */
    public function delete($id) {
        try {
            // Au lieu de supprimer, on marque comme supprimé
            $query = "UPDATE messages 
                      SET status = CASE 
                        WHEN sender_id = :user_id THEN 'sender_deleted'
                        WHEN receiver_id = :user_id THEN 'receiver_deleted'
                        ELSE status
                      END
                      WHERE id = :id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':user_id', $_SESSION['user_id']);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error in Message::delete: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère une conversation par son ID
     * @param string $conversationId ID de la conversation
     * @return array|false Données de la conversation
     */
    public function getConversationById($conversationId) {
        // Pour les conversations virtuelles, retourner null
        if (strpos($conversationId, 'conv_') === 0) {
            return null;
        }
        
        // Si c'est un ID numérique, chercher dans la table conversations
        $query = "SELECT * FROM conversations WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $conversationId);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les participants d'une conversation
     * @param string $conversationId ID de la conversation
     * @return array Liste des participants
     */
    public function getConversationParticipants($conversationId) {
        // Pour les conversations virtuelles, extraire les participants de l'ID
        if (strpos($conversationId, 'conv_') === 0) {
            $parts = explode('_', $conversationId);
            if (count($parts) >= 3) {
                return [
                    ['user_id' => $parts[1]],
                    ['user_id' => $parts[2]]
                ];
            }
        }
        
        // Si c'est un ID numérique, chercher dans la table conversation_participants
        $query = "SELECT * FROM conversation_participants WHERE conversation_id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $conversationId);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupère les messages échangés entre deux utilisateurs
     * @param int $user1Id ID du premier utilisateur
     * @param int $user2Id ID du deuxième utilisateur
     * @return array Liste des messages échangés
     */
    public function getConversationBetweenUsers($user1Id, $user2Id) {
        try {
            $query = "SELECT m.*, 
                      s.first_name as sender_first_name, s.last_name as sender_last_name, s.role as sender_role,
                      r.first_name as receiver_first_name, r.last_name as receiver_last_name, r.role as receiver_role,
                      m.id as message_id
                      FROM messages m
                      JOIN users s ON m.sender_id = s.id
                      JOIN users r ON m.receiver_id = r.id
                      WHERE ((m.sender_id = :user1_id AND m.receiver_id = :user2_id) OR
                            (m.sender_id = :user2_id2 AND m.receiver_id = :user1_id2))
                      AND m.status NOT IN ('sender_deleted', 'receiver_deleted')
                      ORDER BY m.sent_at ASC";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user1_id', $user1Id);
            $stmt->bindParam(':user2_id', $user2Id);
            $stmt->bindParam(':user2_id2', $user2Id);
            $stmt->bindParam(':user1_id2', $user1Id);
            $stmt->execute();
            
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Ajouter le champ is_read pour chaque message
            foreach ($messages as &$message) {
                // Message considéré comme non lu si l'utilisateur est le destinataire et le statut n'est pas 'read'
                $message['is_read'] = ($message['receiver_id'] == $user1Id && $message['status'] !== 'read') ? 0 : 1;
            }
            
            return $messages;
        } catch (Exception $e) {
            error_log("Error in getConversationBetweenUsers: " . $e->getMessage());
            return [];
        }
    }
}