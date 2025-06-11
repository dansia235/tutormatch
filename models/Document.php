<?php
/**
 * Modèle pour la gestion des documents
 */
class Document {
    private $db;
    
    /**
     * Constructeur
     * @param PDO $db Instance de connexion à la base de données
     */
    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Récupère un document par son ID
     * @param int $id ID du document
     * @return array|false Données du document si trouvé, sinon false
     */
    public function getById($id) {
        $query = "SELECT d.*, u.first_name, u.last_name, u.role
                  FROM documents d
                  JOIN users u ON d.user_id = u.id
                  WHERE d.id = :id LIMIT 1";
                  
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère tous les documents
     * @param string $category Catégorie pour filtrer (optionnel)
     * @return array Liste des documents
     */
    public function getAll($category = null) {
        try {
            $query = "SELECT d.*, u.first_name, u.last_name, u.role
                      FROM documents d
                      JOIN users u ON d.user_id = u.id";
                      
            if ($category) {
                $query .= " WHERE d.type = :category";
            }
            
            $query .= " ORDER BY d.upload_date DESC";
            
            $stmt = $this->db->prepare($query);
            
            if ($category) {
                $stmt->bindParam(':category', $category);
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // En cas d'erreur, logger et retourner un tableau vide
            error_log("Erreur dans Document::getAll: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère tous les documents d'un utilisateur
     * @param int $userId ID de l'utilisateur
     * @param string $category Catégorie pour filtrer (optionnel)
     * @return array Liste des documents
     */
    public function getByUserId($userId, $category = null) {
        $query = "SELECT d.*, u.first_name, u.last_name, u.role
                  FROM documents d
                  JOIN users u ON d.user_id = u.id
                  WHERE d.user_id = :user_id";
                  
        if ($category) {
            $query .= " AND d.type = :category";
        }
        
        $query .= " ORDER BY d.upload_date DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        
        if ($category) {
            $stmt->bindParam(':category', $category);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère tous les documents liés à un stage
     * @param int $internshipId ID du stage
     * @return array Liste des documents
     */
    public function getByInternshipId($internshipId) {
        $query = "SELECT d.*, u.first_name, u.last_name, u.role
                  FROM documents d
                  JOIN users u ON d.user_id = u.id
                  JOIN assignments a ON d.assignment_id = a.id
                  WHERE a.internship_id = :internship_id
                  ORDER BY d.upload_date DESC";
                  
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':internship_id', $internshipId);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère tous les documents liés à une affectation
     * @param int $assignmentId ID de l'affectation
     * @return array Liste des documents
     */
    public function getByAssignmentId($assignmentId) {
        $query = "SELECT d.*, u.first_name, u.last_name, u.role
                  FROM documents d
                  JOIN users u ON d.user_id = u.id
                  WHERE d.assignment_id = :assignment_id
                  ORDER BY d.upload_date DESC";
                  
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':assignment_id', $assignmentId);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crée un nouveau document
     * @param array $data Données du document
     * @return int|false ID du document créé, sinon false
     */
    public function create($data) {
        try {
            // Vérifier les champs requis
            $requiredFields = ['title', 'file_path', 'file_type', 'file_size', 'user_id'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    error_log("Erreur dans Document::create: Champ requis manquant: $field");
                    return false;
                }
            }
            
            // Normaliser les champs optionnels
            $description = isset($data['description']) ? $data['description'] : null;
            $type = isset($data['type']) ? $data['type'] : (isset($data['category']) ? $data['category'] : 'other');
            $assignmentId = isset($data['assignment_id']) ? $data['assignment_id'] : null;
            $status = isset($data['status']) ? $data['status'] : 'active';
            
            $query = "INSERT INTO documents (
                        title, description, file_path, file_type, file_size, 
                        type, user_id, upload_date, assignment_id, status
                      ) VALUES (
                        :title, :description, :file_path, :file_type, :file_size,
                        :type, :user_id, NOW(), :assignment_id, :status
                      )";
                      
            $stmt = $this->db->prepare($query);
            
            // Liaison des paramètres
            $stmt->bindParam(':title', $data['title']);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':file_path', $data['file_path']);
            $stmt->bindParam(':file_type', $data['file_type']);
            $stmt->bindParam(':file_size', $data['file_size']);
            $stmt->bindParam(':type', $type);
            $stmt->bindParam(':user_id', $data['user_id']);
            $stmt->bindParam(':assignment_id', $assignmentId);
            $stmt->bindParam(':status', $status);
            
            if ($stmt->execute()) {
                return $this->db->lastInsertId();
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Erreur PDO dans Document::create: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Erreur dans Document::create: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Met à jour un document existant
     * @param int $id ID du document
     * @param array $data Données à mettre à jour
     * @return bool Succès de l'opération
     */
    public function update($id, $data) {
        $fields = [];
        $values = [':id' => $id];
        
        // Construire dynamiquement les champs à mettre à jour
        foreach ($data as $key => $value) {
            if ($key !== 'id' && $key !== 'upload_date') {
                $fields[] = "$key = :$key";
                $values[":$key"] = $value;
            }
        }
        
        if (empty($fields)) {
            return false; // Rien à mettre à jour
        }
        
        $query = "UPDATE documents SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($query);
        
        return $stmt->execute($values);
    }

    /**
     * Supprime un document
     * @param int $id ID du document
     * @return bool Succès de l'opération
     */
    public function delete($id) {
        $query = "DELETE FROM documents WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }
    
    /**
     * Recherche des documents
     * @param string $term Terme de recherche
     * @param string $type Type pour filtrer (optionnel)
     * @return array Liste des documents correspondants
     */
    public function search($term, $type = null) {
        $term = "%$term%";
        
        $query = "SELECT d.*, u.first_name, u.last_name, u.role
                  FROM documents d
                  JOIN users u ON d.user_id = u.id
                  WHERE (d.title LIKE :term 
                  OR u.first_name LIKE :term 
                  OR u.last_name LIKE :term)";
        
        if ($type) {
            $query .= " AND d.type = :type";
        }
        
        $query .= " ORDER BY d.upload_date DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':term', $term);
        
        if ($type) {
            $stmt->bindParam(':type', $type);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Compte le nombre de documents par type
     * @return array Nombre de documents par type
     */
    public function countByCategory() {
        try {
            $query = "SELECT type, COUNT(*) as count FROM documents GROUP BY type";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            $result = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $result[$row['type']] = $row['count'];
            }
            
            return $result;
        } catch (PDOException $e) {
            // En cas d'erreur, retourner un tableau vide
            return [
                'contract' => 0,
                'report' => 0,
                'evaluation' => 0,
                'certificate' => 0,
                'other' => 0
            ];
        }
    }
}