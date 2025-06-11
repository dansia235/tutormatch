<?php
/**
 * Modèle pour la gestion des paramètres d'algorithme d'affectation
 */
class AlgorithmParameters {
    private $db;
    
    /**
     * Constructeur
     * @param PDO $db Instance de connexion à la base de données
     */
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Crée un nouvel enregistrement de paramètres d'algorithme
     * @param array $data Données des paramètres
     * @return int|false ID du paramètre créé, sinon false
     */
    public function create($data) {
        try {
            // Simplifier la requête pour éviter les problèmes de colonnes
            $query = "INSERT INTO algorithm_parameters SET 
                name = :name, 
                description = :description, 
                algorithm_type = :algorithm_type, 
                department_weight = :department_weight, 
                preference_weight = :preference_weight, 
                capacity_weight = :capacity_weight, 
                allow_cross_department = :allow_cross_department, 
                prioritize_preferences = :prioritize_preferences, 
                balance_workload = :balance_workload, 
                is_default = :is_default,
                created_at = NOW()";
            
            $stmt = $this->db->prepare($query);
            
            // Conversion des valeurs booléennes en entiers et gestion des valeurs manquantes
            $name = isset($data['name']) ? $data['name'] : 'Exécution du ' . date('Y-m-d H:i:s');
            $description = isset($data['description']) ? $data['description'] : null;
            $algorithmType = isset($data['algorithm_type']) ? $data['algorithm_type'] : 'greedy';
            $departmentWeight = isset($data['department_weight']) ? (int)$data['department_weight'] : 50;
            $preferenceWeight = isset($data['preference_weight']) ? (int)$data['preference_weight'] : 30;
            $capacityWeight = isset($data['capacity_weight']) ? (int)$data['capacity_weight'] : 20;
            $allowCrossDept = isset($data['allow_cross_department']) ? (int)$data['allow_cross_department'] : 0;
            $prioritizePref = isset($data['prioritize_preferences']) ? (int)$data['prioritize_preferences'] : 1;
            $balanceWork = isset($data['balance_workload']) ? (int)$data['balance_workload'] : 1;
            $isDefault = isset($data['is_default']) ? (int)$data['is_default'] : 0;
            
            // Liaison des paramètres
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':algorithm_type', $algorithmType);
            $stmt->bindParam(':department_weight', $departmentWeight);
            $stmt->bindParam(':preference_weight', $preferenceWeight);
            $stmt->bindParam(':capacity_weight', $capacityWeight);
            $stmt->bindParam(':allow_cross_department', $allowCrossDept);
            $stmt->bindParam(':prioritize_preferences', $prioritizePref);
            $stmt->bindParam(':balance_workload', $balanceWork);
            $stmt->bindParam(':is_default', $isDefault);
            
            // Debuggage
            error_log("Executing SQL: " . print_r([
                'name' => $name,
                'description' => $description,
                'algorithm_type' => $algorithmType,
                'department_weight' => $departmentWeight,
                'preference_weight' => $preferenceWeight,
                'capacity_weight' => $capacityWeight,
                'allow_cross_department' => $allowCrossDept,
                'prioritize_preferences' => $prioritizePref,
                'balance_workload' => $balanceWork,
                'is_default' => $isDefault
            ], true));
            
            $stmt->execute();
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Erreur lors de la création des paramètres: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Réinitialise le flag par défaut pour tous les paramètres sauf celui spécifié
     * @param int $exceptId ID du paramètre à ne pas réinitialiser
     * @return bool Succès de l'opération
     */
    public function resetDefaultFlag($exceptId) {
        try {
            $query = "UPDATE algorithm_parameters SET is_default = 0 WHERE id != :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $exceptId);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erreur lors de la réinitialisation des flags par défaut: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupère les paramètres par défaut
     * @return array|false Paramètres par défaut
     */
    public function getDefault() {
        try {
            $query = "SELECT * FROM algorithm_parameters WHERE is_default = 1 ORDER BY created_at DESC LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$result) {
                // Retourne des valeurs par défaut si aucun paramètre trouvé
                return [
                    'name' => 'Paramètres par défaut',
                    'description' => 'Paramètres générés automatiquement',
                    'algorithm_type' => 'greedy',
                    'department_weight' => 50,
                    'preference_weight' => 30,
                    'capacity_weight' => 20,
                    'allow_cross_department' => 0,
                    'prioritize_preferences' => 1,
                    'balance_workload' => 1,
                    'is_default' => 1
                ];
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des paramètres par défaut: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupère les paramètres par ID
     * @param int $id ID des paramètres
     * @return array|false Paramètres
     */
    public function getById($id) {
        try {
            $query = "SELECT * FROM algorithm_parameters WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des paramètres: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupère tous les paramètres
     * @return array Liste des paramètres
     */
    public function getAll() {
        try {
            $query = "SELECT * FROM algorithm_parameters ORDER BY created_at DESC";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de tous les paramètres: " . $e->getMessage());
            return [];
        }
    }
}