<?php
/**
 * Classe de base pour les modèles avec une meilleure gestion des connexions
 */
class BaseModel {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $transactionStartedHere = false;
    
    /**
     * Constructeur
     * @param PDO $db Instance de connexion à la base de données
     */
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Débute une transaction de manière sécurisée
     * @return bool True si une nouvelle transaction a été démarrée
     */
    protected function beginTransactionSafe() {
        try {
            // Vérifier si une transaction est déjà active
            if (!$this->db->inTransaction()) {
                $this->db->beginTransaction();
                $this->transactionStartedHere = true;
                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Erreur lors du démarrage de la transaction: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Valide une transaction si elle a été démarrée ici
     * @return bool True si la transaction a été validée
     */
    protected function commitSafe() {
        try {
            if ($this->transactionStartedHere && $this->db->inTransaction()) {
                $this->db->commit();
                $this->transactionStartedHere = false;
                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Erreur lors de la validation de la transaction: " . $e->getMessage());
            $this->rollBackSafe();
            return false;
        }
    }
    
    /**
     * Annule une transaction si elle a été démarrée ici
     * @return bool True si la transaction a été annulée
     */
    protected function rollBackSafe() {
        try {
            if ($this->transactionStartedHere && $this->db->inTransaction()) {
                $this->db->rollBack();
                $this->transactionStartedHere = false;
                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Erreur lors de l'annulation de la transaction: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Exécute une requête préparée avec gestion des erreurs
     * @param string $sql Requête SQL avec placeholders
     * @param array $params Paramètres pour la requête préparée
     * @return PDOStatement|false Résultat de la requête ou false en cas d'erreur
     */
    protected function executeQuery($sql, $params = []) {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Erreur d'exécution de requête: " . $e->getMessage() . " - SQL: $sql");
            
            if (strpos($e->getMessage(), 'Too many connections') !== false) {
                // Tenter de se reconnecter une fois
                try {
                    $this->db = getDBConnection();
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute($params);
                    return $stmt;
                } catch (PDOException $e2) {
                    error_log("Échec de la tentative de reconnexion: " . $e2->getMessage());
                    return false;
                }
            }
            
            return false;
        }
    }
    
    /**
     * Récupère tous les enregistrements
     * @param string|array $conditions Conditions supplémentaires (WHERE)
     * @param array $params Paramètres pour les conditions
     * @return array Tableau d'enregistrements
     */
    public function getAll($conditions = null, $params = []) {
        $sql = "SELECT * FROM {$this->table}";
        
        if ($conditions) {
            if (is_string($conditions)) {
                $sql .= " WHERE $conditions";
            } elseif (is_array($conditions)) {
                $whereClause = [];
                foreach ($conditions as $key => $value) {
                    $whereClause[] = "$key = :$key";
                    $params[":$key"] = $value;
                }
                $sql .= " WHERE " . implode(" AND ", $whereClause);
            }
        }
        
        $stmt = $this->executeQuery($sql, $params);
        return $stmt ? $stmt->fetchAll() : [];
    }
    
    /**
     * Récupère un enregistrement par son ID
     * @param int $id ID de l'enregistrement
     * @return array|null Enregistrement ou null si non trouvé
     */
    public function getById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $stmt = $this->executeQuery($sql, [':id' => $id]);
        return $stmt ? $stmt->fetch() : null;
    }
    
    /**
     * Crée un nouvel enregistrement
     * @param array $data Données à insérer
     * @return int|bool ID de l'enregistrement créé ou false en cas d'erreur
     */
    public function create($data) {
        $columns = implode(", ", array_keys($data));
        $placeholders = ":" . implode(", :", array_keys($data));
        
        $sql = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)";
        
        $transactionStartedHere = $this->beginTransactionSafe();
        
        try {
            $stmt = $this->executeQuery($sql, $data);
            
            if ($stmt) {
                $id = $this->db->lastInsertId();
                
                if ($transactionStartedHere) {
                    $this->commitSafe();
                }
                
                return $id;
            }
            
            throw new Exception("Échec de l'insertion");
        } catch (Exception $e) {
            if ($transactionStartedHere) {
                $this->rollBackSafe();
            }
            error_log("Erreur lors de la création d'un enregistrement: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Met à jour un enregistrement existant
     * @param int $id ID de l'enregistrement à mettre à jour
     * @param array $data Données à mettre à jour
     * @return bool Succès de la mise à jour
     */
    public function update($id, $data) {
        $setParts = [];
        $params = [':id' => $id];
        
        foreach ($data as $key => $value) {
            $setParts[] = "$key = :$key";
            $params[":$key"] = $value;
        }
        
        $setClause = implode(", ", $setParts);
        
        $sql = "UPDATE {$this->table} SET $setClause WHERE {$this->primaryKey} = :id";
        
        $transactionStartedHere = $this->beginTransactionSafe();
        
        try {
            $stmt = $this->executeQuery($sql, $params);
            
            if ($stmt) {
                if ($transactionStartedHere) {
                    $this->commitSafe();
                }
                
                return true;
            }
            
            throw new Exception("Échec de la mise à jour");
        } catch (Exception $e) {
            if ($transactionStartedHere) {
                $this->rollBackSafe();
            }
            error_log("Erreur lors de la mise à jour d'un enregistrement: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Supprime un enregistrement
     * @param int $id ID de l'enregistrement à supprimer
     * @return bool Succès de la suppression
     */
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
        
        $transactionStartedHere = $this->beginTransactionSafe();
        
        try {
            $stmt = $this->executeQuery($sql, [':id' => $id]);
            
            if ($stmt) {
                if ($transactionStartedHere) {
                    $this->commitSafe();
                }
                
                return true;
            }
            
            throw new Exception("Échec de la suppression");
        } catch (Exception $e) {
            if ($transactionStartedHere) {
                $this->rollBackSafe();
            }
            error_log("Erreur lors de la suppression d'un enregistrement: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Compte le nombre d'enregistrements
     * @param string|array $conditions Conditions supplémentaires (WHERE)
     * @param array $params Paramètres pour les conditions
     * @return int Nombre d'enregistrements
     */
    public function count($conditions = null, $params = []) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        
        if ($conditions) {
            if (is_string($conditions)) {
                $sql .= " WHERE $conditions";
            } elseif (is_array($conditions)) {
                $whereClause = [];
                foreach ($conditions as $key => $value) {
                    $whereClause[] = "$key = :$key";
                    $params[":$key"] = $value;
                }
                $sql .= " WHERE " . implode(" AND ", $whereClause);
            }
        }
        
        $stmt = $this->executeQuery($sql, $params);
        $result = $stmt ? $stmt->fetch() : null;
        
        return $result ? $result['count'] : 0;
    }
    
    /**
     * Vérifie si un enregistrement existe
     * @param string|array $conditions Conditions (WHERE)
     * @param array $params Paramètres pour les conditions
     * @return bool True si l'enregistrement existe
     */
    public function exists($conditions, $params = []) {
        return $this->count($conditions, $params) > 0;
    }
}