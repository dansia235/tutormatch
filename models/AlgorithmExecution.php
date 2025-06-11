<?php
/**
 * Modèle pour la gestion des exécutions d'algorithmes d'affectation
 */
class AlgorithmExecution {
    private $db;
    
    /**
     * Constructeur
     * @param PDO $db Instance de connexion à la base de données
     */
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Crée un nouvel enregistrement d'exécution d'algorithme
     * @param array $data Données de l'exécution
     * @return int|false ID de l'exécution créée, sinon false
     */
    public function create($data) {
        // S'assurer qu'il n'y a pas de transaction déjà active dans cette méthode
        $transactionStartedHere = false;
        
        try {
            // Vérifier si une transaction est déjà active
            if (!$this->db->inTransaction()) {
                $this->db->beginTransaction();
                $transactionStartedHere = true;
            }
            
            $query = "INSERT INTO algorithm_executions (
                parameters_id, 
                executed_by, 
                execution_time, 
                students_count, 
                teachers_count, 
                assignments_count, 
                unassigned_count, 
                average_satisfaction, 
                notes,
                executed_at
            ) VALUES (
                :parameters_id, 
                :executed_by, 
                :execution_time, 
                :students_count, 
                :teachers_count, 
                :assignments_count, 
                :unassigned_count, 
                :average_satisfaction, 
                :notes,
                NOW()
            )";
            
            $stmt = $this->db->prepare($query);
            
            // Liaison des paramètres
            $stmt->bindParam(':parameters_id', $data['parameters_id']);
            $stmt->bindParam(':executed_by', $data['executed_by']);
            $stmt->bindParam(':execution_time', $data['execution_time']);
            $stmt->bindParam(':students_count', $data['students_count']);
            $stmt->bindParam(':teachers_count', $data['teachers_count']);
            $stmt->bindParam(':assignments_count', $data['assignments_count']);
            $stmt->bindParam(':unassigned_count', $data['unassigned_count']);
            $stmt->bindParam(':average_satisfaction', $data['average_satisfaction']);
            $stmt->bindParam(':notes', $data['notes']);
            
            $stmt->execute();
            $lastId = $this->db->lastInsertId();
            
            // Si nous avons démarré la transaction ici, nous la commitons ici
            if ($transactionStartedHere) {
                $this->db->commit();
            }
            
            return $lastId;
        } catch (PDOException $e) {
            // Si nous avons démarré la transaction ici, nous l'annulons ici
            if ($transactionStartedHere && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            
            error_log("Erreur lors de la création de l'exécution: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupère une exécution par son ID
     * @param int $id ID de l'exécution
     * @return array|false Données de l'exécution
     */
    public function getById($id) {
        try {
            $query = "SELECT e.*, p.name as parameters_name, p.algorithm_type, u.full_name as executed_by_name
                      FROM algorithm_executions e
                      JOIN algorithm_parameters p ON e.parameters_id = p.id
                      JOIN users u ON e.executed_by = u.id
                      WHERE e.id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de l'exécution: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupère toutes les exécutions
     * @return array Liste des exécutions
     */
    public function getAll() {
        try {
            $query = "SELECT e.*, p.name as parameters_name, p.algorithm_type, u.full_name as executed_by_name
                      FROM algorithm_executions e
                      JOIN algorithm_parameters p ON e.parameters_id = p.id
                      JOIN users u ON e.executed_by = u.id
                      ORDER BY e.executed_at DESC";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des exécutions: " . $e->getMessage());
            return [];
        }
    }
}