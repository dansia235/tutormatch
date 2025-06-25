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
     * Récupère les documents avec pagination
     * @param int $page Numéro de page (commence à 1)
     * @param int $perPage Nombre d'éléments par page
     * @param string $category Catégorie pour filtrer (optionnel)
     * @param string $search Terme de recherche (optionnel)
     * @return array ['documents' => array, 'total' => int, 'totalPages' => int]
     */
    public function getAllPaginated($page = 1, $perPage = 10, $category = null, $search = null) {
        try {
            // Calculer l'offset
            $offset = ($page - 1) * $perPage;
            
            // Construction de la requête de base
            $baseQuery = "FROM documents d JOIN users u ON d.user_id = u.id";
            $whereConditions = [];
            $params = [];
            
            if ($category) {
                // Si category est un array (erreur de paramètre), extraire la clé appropriée
                if (is_array($category)) {
                    // Ignorer ce paramètre malformé
                    $category = null;
                } else {
                    $whereConditions[] = "d.type = :category";
                    $params[':category'] = $category;
                }
            }
            
            if ($search) {
                $whereConditions[] = "(d.title LIKE :search OR d.description LIKE :search)";
                $params[':search'] = '%' . $search . '%';
            }
            
            if (!empty($whereConditions)) {
                $baseQuery .= " WHERE " . implode(" AND ", $whereConditions);
            }
            
            // Compter le total
            $countQuery = "SELECT COUNT(*) as total " . $baseQuery;
            $countStmt = $this->db->prepare($countQuery);
            foreach ($params as $key => $value) {
                if (is_array($value)) {
                    $countStmt->bindValue($key, implode(',', $value));
                } else {
                    $countStmt->bindValue($key, $value);
                }
            }
            $countStmt->execute();
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Récupérer les documents
            $query = "SELECT d.*, u.first_name, u.last_name, u.role " . $baseQuery;
            $query .= " ORDER BY d.upload_date DESC LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($query);
            foreach ($params as $key => $value) {
                if (is_array($value)) {
                    $stmt->bindValue($key, implode(',', $value));
                } else {
                    $stmt->bindValue($key, $value);
                }
            }
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'documents' => $documents,
                'total' => $total,
                'totalPages' => ceil($total / $perPage),
                'currentPage' => $page,
                'perPage' => $perPage
            ];
            
        } catch (PDOException $e) {
            error_log("Erreur dans Document::getAllPaginated: " . $e->getMessage());
            return [
                'documents' => [],
                'total' => 0,
                'totalPages' => 0,
                'currentPage' => 1,
                'perPage' => $perPage
            ];
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
                if (!isset($data[$field]) || (is_string($data[$field]) && empty(trim($data[$field])))) {
                    error_log("Erreur dans Document::create: Champ requis manquant: $field");
                    return false;
                }
            }
            
            // Normaliser les champs optionnels
            $description = isset($data['description']) ? $data['description'] : null;
            $type = isset($data['type']) ? $data['type'] : (isset($data['category']) ? $data['category'] : 'other');
            $assignmentId = isset($data['assignment_id']) ? $data['assignment_id'] : null;
            $version = isset($data['version']) ? $data['version'] : null;
            
            // Vérifier si le type est valide
            $validTypes = ['contract', 'report', 'evaluation', 'certificate', 'other'];
            if (!in_array($type, $validTypes)) {
                error_log("Erreur dans Document::create: Type invalide: $type");
                $type = 'other'; // Fallback to 'other' if not valid
            }
            
            // Normaliser le statut
            $validStatus = ['draft', 'submitted', 'approved', 'rejected'];
            $status = isset($data['status']) ? $data['status'] : 'draft';
            if (!in_array($status, $validStatus)) {
                error_log("Erreur dans Document::create: Statut invalide: $status");
                $status = 'draft'; // Fallback to 'draft' if not valid
            }
            
            error_log("Document::create - Données d'insertion: " . json_encode([
                'title' => $data['title'],
                'type' => $type,
                'status' => $status,
                'user_id' => $data['user_id'],
                'assignment_id' => $assignmentId,
                'version' => $version
            ]));
            
            // Essayer une insertion très simplifiée avec les champs minimaux requis
            try {
                // Activer le mode exception pour PDO
                $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Déterminer les colonnes disponibles pour éviter les erreurs "column not found"
                $query = "SHOW COLUMNS FROM documents";
                $columnsStmt = $this->db->prepare($query);
                $columnsStmt->execute();
                $availableColumns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN, 0);
                
                error_log("Colonnes disponibles dans la table documents: " . implode(", ", $availableColumns));
                
                // Construire dynamiquement la requête en fonction des colonnes disponibles
                $columns = [];
                $placeholders = [];
                $params = [];
                
                // Ajouter les champs obligatoires
                $columns[] = 'title';
                $placeholders[] = ':title';
                $params[':title'] = $data['title'];
                
                $columns[] = 'file_path';
                $placeholders[] = ':file_path';
                $params[':file_path'] = $data['file_path'];
                
                $columns[] = 'type';
                $placeholders[] = ':type';
                $params[':type'] = $type;
                
                $columns[] = 'user_id';
                $placeholders[] = ':user_id';
                $params[':user_id'] = $data['user_id'];
                
                $columns[] = 'status';
                $placeholders[] = ':status';
                $params[':status'] = $status;
                
                // Ajouter les champs optionnels s'ils existent dans la table
                if (in_array('description', $availableColumns) && $description !== null) {
                    $columns[] = 'description';
                    $placeholders[] = ':description';
                    $params[':description'] = $description;
                }
                
                if (in_array('file_type', $availableColumns) && isset($data['file_type'])) {
                    $columns[] = 'file_type';
                    $placeholders[] = ':file_type';
                    $params[':file_type'] = $data['file_type'];
                }
                
                if (in_array('file_size', $availableColumns) && isset($data['file_size'])) {
                    $columns[] = 'file_size';
                    $placeholders[] = ':file_size';
                    $params[':file_size'] = $data['file_size'];
                }
                
                if (in_array('assignment_id', $availableColumns) && $assignmentId !== null) {
                    $columns[] = 'assignment_id';
                    $placeholders[] = ':assignment_id';
                    $params[':assignment_id'] = $assignmentId;
                }
                
                if (in_array('version', $availableColumns) && $version !== null) {
                    $columns[] = 'version';
                    $placeholders[] = ':version';
                    $params[':version'] = $version;
                }
                
                // Ajouter le champ upload_date si nécessaire
                if (in_array('upload_date', $availableColumns)) {
                    $columns[] = 'upload_date';
                    $placeholders[] = 'NOW()';
                }
                
                // Construire la requête SQL
                $query = "INSERT INTO documents (" . implode(", ", $columns) . ") 
                          VALUES (" . implode(", ", $placeholders) . ")";
                
                error_log("Requête SQL générée: " . $query);
                error_log("Paramètres: " . json_encode($params));
                
                $stmt = $this->db->prepare($query);
                
                // Exécuter la requête avec les paramètres
                if ($stmt->execute($params)) {
                    $id = $this->db->lastInsertId();
                    error_log("Document créé avec succès, ID: $id");
                    return $id;
                }
                
                error_log("Erreur lors de l'exécution de la requête: " . json_encode($stmt->errorInfo()));
                return false;
                
            } catch (PDOException $e) {
                error_log("Erreur PDO lors de l'exécution de la requête dynamique: " . $e->getMessage());
                
                // Tenter une insertion sans aucun champ optionnel
                try {
                    $query = "INSERT INTO documents (title, file_path, type, user_id, status, upload_date) 
                              VALUES (:title, :file_path, :type, :user_id, :status, NOW())";
                    
                    error_log("Tentative avec requête minimale: " . $query);
                    
                    $stmt = $this->db->prepare($query);
                    $stmt->bindParam(':title', $data['title']);
                    $stmt->bindParam(':file_path', $data['file_path']);
                    $stmt->bindParam(':type', $type);
                    $stmt->bindParam(':user_id', $data['user_id']);
                    $stmt->bindParam(':status', $status);
                    
                    if ($stmt->execute()) {
                        $id = $this->db->lastInsertId();
                        error_log("Document créé avec succès (requête minimale), ID: $id");
                        return $id;
                    }
                    
                    error_log("Échec de la requête minimale: " . json_encode($stmt->errorInfo()));
                    return false;
                    
                } catch (PDOException $e2) {
                    error_log("Erreur PDO avec la requête minimale: " . $e2->getMessage());
                    return false;
                }
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Erreur PDO dans Document::create: " . $e->getMessage());
            // Afficher plus de détails sur l'erreur
            error_log("Code d'erreur PDO: " . $e->getCode());
            error_log("Trace PDO: " . $e->getTraceAsString());
            return false;
        } catch (Exception $e) {
            error_log("Erreur dans Document::create: " . $e->getMessage());
            error_log("Trace: " . $e->getTraceAsString());
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
                  WHERE (d.title LIKE :term1 
                  OR u.first_name LIKE :term2 
                  OR u.last_name LIKE :term3)";
        
        if ($type) {
            $query .= " AND d.type = :type";
        }
        
        $query .= " ORDER BY d.upload_date DESC";
        
        $stmt = $this->db->prepare($query);
        $params = [
            ':term1' => $term,
            ':term2' => $term,
            ':term3' => $term
        ];
        
        if ($type) {
            $params[':type'] = $type;
        }
        
        $stmt->execute($params);
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