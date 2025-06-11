<?php
/**
 * Modèle pour la gestion des notifications
 */
class Notification {
    private $db;
    
    /**
     * Constructeur
     * @param PDO $db Instance de connexion à la base de données
     */
    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Récupère une notification par son ID
     * @param int $id ID de la notification
     * @return array|false Données de la notification si trouvée, sinon false
     */
    public function getById($id) {
        $query = "SELECT * FROM notifications WHERE id = :id LIMIT 1";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère toutes les notifications pour un utilisateur
     * @param array $options Options de filtre
     * @return array Liste des notifications
     */
    public function getAll($options = []) {
        $query = "SELECT * FROM notifications WHERE 1=1";
        
        $params = [];
        
        // Filtrer par utilisateur
        if (isset($options['user_id'])) {
            $query .= " AND user_id = :user_id";
            $params[':user_id'] = $options['user_id'];
        }
        
        // Filtrer par type
        if (isset($options['type'])) {
            $query .= " AND type = :type";
            $params[':type'] = $options['type'];
        }
        
        // Filtrer par état de lecture
        if (isset($options['read'])) {
            if ($options['read']) {
                $query .= " AND read_at IS NOT NULL";
            } else {
                $query .= " AND read_at IS NULL";
            }
        }
        
        // Trier par date de création (plus récentes d'abord)
        $query .= " ORDER BY created_at DESC";
        
        // Pagination
        if (isset($options['page']) && isset($options['limit'])) {
            $offset = ($options['page'] - 1) * $options['limit'];
            $query .= " LIMIT :offset, :limit";
            $params[':offset'] = $offset;
            $params[':limit'] = $options['limit'];
        }
        
        $stmt = $this->db->prepare($query);
        
        // Bind des paramètres
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
     * Compte le nombre total de notifications
     * @param array $options Options de filtre
     * @return int Nombre total de notifications
     */
    public function countAll($options = []) {
        $query = "SELECT COUNT(*) FROM notifications WHERE 1=1";
        
        $params = [];
        
        // Filtrer par utilisateur
        if (isset($options['user_id'])) {
            $query .= " AND user_id = :user_id";
            $params[':user_id'] = $options['user_id'];
        }
        
        // Filtrer par type
        if (isset($options['type'])) {
            $query .= " AND type = :type";
            $params[':type'] = $options['type'];
        }
        
        // Filtrer par état de lecture
        if (isset($options['read'])) {
            if ($options['read']) {
                $query .= " AND read_at IS NOT NULL";
            } else {
                $query .= " AND read_at IS NULL";
            }
        }
        
        $stmt = $this->db->prepare($query);
        
        // Bind des paramètres
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    /**
     * Compte le nombre de notifications non lues
     * @param int $userId ID de l'utilisateur
     * @return int Nombre de notifications non lues
     */
    public function countUnread($userId) {
        $query = "SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND read_at IS NULL";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        return (int)$stmt->fetchColumn();
    }

    /**
     * Crée une nouvelle notification
     * @param array $data Données de la notification
     * @return int|false ID de la notification créée, sinon false
     */
    public function create($data) {
        $query = "INSERT INTO notifications (user_id, title, message, type, related_type, related_id, link, created_at) 
                  VALUES (:user_id, :title, :message, :type, :related_type, :related_id, :link, :created_at)";
        
        $stmt = $this->db->prepare($query);
        
        $createdAt = isset($data['created_at']) ? $data['created_at'] : date('Y-m-d H:i:s');
        $relatedType = isset($data['related_type']) ? $data['related_type'] : null;
        $relatedId = isset($data['related_id']) ? $data['related_id'] : null;
        $link = isset($data['link']) ? $data['link'] : null;
        
        $stmt->bindParam(':user_id', $data['user_id']);
        $stmt->bindParam(':title', $data['title']);
        $stmt->bindParam(':message', $data['message']);
        $stmt->bindParam(':type', $data['type']);
        $stmt->bindParam(':related_type', $relatedType);
        $stmt->bindParam(':related_id', $relatedId);
        $stmt->bindParam(':link', $link);
        $stmt->bindParam(':created_at', $createdAt);
        
        if ($stmt->execute()) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }

    /**
     * Marque une notification comme lue
     * @param int $id ID de la notification
     * @return bool Succès de l'opération
     */
    public function markAsRead($id) {
        $query = "UPDATE notifications SET read_at = :read_at WHERE id = :id";
        
        $stmt = $this->db->prepare($query);
        
        $readAt = date('Y-m-d H:i:s');
        
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':read_at', $readAt);
        
        return $stmt->execute();
    }

    /**
     * Marque toutes les notifications d'un utilisateur comme lues
     * @param int $userId ID de l'utilisateur
     * @return bool Succès de l'opération
     */
    public function markAllAsRead($userId) {
        $query = "UPDATE notifications SET read_at = :read_at WHERE user_id = :user_id AND read_at IS NULL";
        
        $stmt = $this->db->prepare($query);
        
        $readAt = date('Y-m-d H:i:s');
        
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':read_at', $readAt);
        
        return $stmt->execute();
    }

    /**
     * Supprime une notification
     * @param int $id ID de la notification
     * @return bool Succès de l'opération
     */
    public function delete($id) {
        $query = "DELETE FROM notifications WHERE id = :id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }
}