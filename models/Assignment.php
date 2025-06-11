<?php
/**
 * Modèle pour la gestion des affectations de stages
 */
class Assignment {
    private $db;
    
    /**
     * Constructeur
     * @param PDO $db Instance de connexion à la base de données
     */
    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Récupère une affectation par son ID
     * @param int $id ID de l'affectation
     * @return array|false Données de l'affectation si trouvée, sinon false
     */
    public function getById($id) {
        $query = "SELECT a.*, 
                  s.id as student_id, s.student_number, u_s.first_name as student_first_name, u_s.last_name as student_last_name,
                  t.id as teacher_id, u_t.first_name as teacher_first_name, u_t.last_name as teacher_last_name,
                  i.id as internship_id, i.title as internship_title, i.company_id,
                  c.name as company_name, c.logo_path as company_logo
                  FROM assignments a
                  JOIN students s ON a.student_id = s.id
                  JOIN users u_s ON s.user_id = u_s.id
                  JOIN teachers t ON a.teacher_id = t.id
                  JOIN users u_t ON t.user_id = u_t.id
                  JOIN internships i ON a.internship_id = i.id
                  JOIN companies c ON i.company_id = c.id
                  WHERE a.id = :id LIMIT 1";
                  
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère toutes les affectations
     * @param string $status Statut pour filtrer (optionnel)
     * @return array Liste des affectations
     */
    public function getAll($status = null) {
        $query = "SELECT a.*, 
                  s.id as student_id, s.student_number, u_s.first_name as student_first_name, u_s.last_name as student_last_name,
                  t.id as teacher_id, u_t.first_name as teacher_first_name, u_t.last_name as teacher_last_name,
                  i.id as internship_id, i.title as internship_title, i.company_id,
                  c.name as company_name
                  FROM assignments a
                  JOIN students s ON a.student_id = s.id
                  JOIN users u_s ON s.user_id = u_s.id
                  JOIN teachers t ON a.teacher_id = t.id
                  JOIN users u_t ON t.user_id = u_t.id
                  JOIN internships i ON a.internship_id = i.id
                  JOIN companies c ON i.company_id = c.id";
                  
        if ($status) {
            $query .= " WHERE a.status = :status";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':status', $status);
        } else {
            $stmt = $this->db->prepare($query);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crée une nouvelle affectation
     * @param array $data Données de l'affectation
     * @return int|false ID de l'affectation créée, sinon false
     */
    public function create($data) {
        $this->db->beginTransaction();
        
        try {
            $query = "INSERT INTO assignments (student_id, teacher_id, internship_id, status, satisfaction_score, compatibility_score, notes) 
                      VALUES (:student_id, :teacher_id, :internship_id, :status, :satisfaction_score, :compatibility_score, :notes)";
                      
            $stmt = $this->db->prepare($query);
            
            // Liaison des paramètres
            $stmt->bindParam(':student_id', $data['student_id']);
            $stmt->bindParam(':teacher_id', $data['teacher_id']);
            $stmt->bindParam(':internship_id', $data['internship_id']);
            $stmt->bindParam(':status', $data['status']);
            $stmt->bindParam(':satisfaction_score', $data['satisfaction_score']);
            $stmt->bindParam(':compatibility_score', $data['compatibility_score']);
            $stmt->bindParam(':notes', $data['notes']);
            
            $stmt->execute();
            $assignmentId = $this->db->lastInsertId();
            
            // Mettre à jour le statut du stage si nécessaire
            if ($data['status'] === 'confirmed') {
                $query = "UPDATE internships SET status = 'assigned' WHERE id = :internship_id";
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(':internship_id', $data['internship_id']);
                $stmt->execute();
            }
            
            $this->db->commit();
            return $assignmentId;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            // En production, loggez l'erreur au lieu de l'afficher
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * Met à jour une affectation existante
     * @param int $id ID de l'affectation
     * @param array $data Données à mettre à jour
     * @return bool Succès de l'opération
     */
    public function update($id, $data) {
        $this->db->beginTransaction();
        
        try {
            // Récupérer l'affectation actuelle
            $query = "SELECT * FROM assignments WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $currentAssignment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$currentAssignment) {
                throw new Exception("Affectation non trouvée");
            }
            
            $fields = [];
            $values = [':id' => $id];
            
            // Construire dynamiquement les champs à mettre à jour
            foreach ($data as $key => $value) {
                if ($key !== 'id') {
                    $fields[] = "$key = :$key";
                    $values[":$key"] = $value;
                }
            }
            
            if (empty($fields)) {
                return false; // Rien à mettre à jour
            }
            
            $query = "UPDATE assignments SET " . implode(', ', $fields) . " WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->execute($values);
            
            // Mise à jour du statut de confirmation si nécessaire
            if (isset($data['status']) && $data['status'] === 'confirmed' && $currentAssignment['status'] !== 'confirmed') {
                $query = "UPDATE assignments SET confirmation_date = CURRENT_TIMESTAMP WHERE id = :id";
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(':id', $id);
                $stmt->execute();
                
                // Mettre à jour le statut du stage
                $query = "UPDATE internships SET status = 'assigned' WHERE id = :internship_id";
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(':internship_id', $currentAssignment['internship_id']);
                $stmt->execute();
            }
            
            // Si l'affectation est rejetée ou complétée, libérer le stage
            if (isset($data['status']) && ($data['status'] === 'rejected' || $data['status'] === 'completed') && 
                $currentAssignment['status'] === 'confirmed') {
                $query = "UPDATE internships SET status = :new_status WHERE id = :internship_id";
                $stmt = $this->db->prepare($query);
                $newStatus = $data['status'] === 'completed' ? 'completed' : 'available';
                $stmt->bindParam(':new_status', $newStatus);
                $stmt->bindParam(':internship_id', $currentAssignment['internship_id']);
                $stmt->execute();
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            // En production, loggez l'erreur au lieu de l'afficher
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * Supprime une affectation
     * @param int $id ID de l'affectation
     * @return bool Succès de l'opération
     */
    public function delete($id) {
        $this->db->beginTransaction();
        
        try {
            // Récupérer l'affectation actuelle pour mettre à jour le statut du stage
            $query = "SELECT * FROM assignments WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$assignment) {
                throw new Exception("Affectation non trouvée");
            }
            
            // Supprimer l'affectation
            $query = "DELETE FROM assignments WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            // Si l'affectation était confirmée, rendre le stage disponible à nouveau
            if ($assignment['status'] === 'confirmed') {
                $query = "UPDATE internships SET status = 'available' WHERE id = :internship_id";
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(':internship_id', $assignment['internship_id']);
                $stmt->execute();
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            // En production, loggez l'erreur au lieu de l'afficher
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * Recherche des affectations
     * @param string $term Terme de recherche
     * @param string $status Statut pour filtrer (optionnel)
     * @return array Liste des affectations correspondantes
     */
    public function search($term, $status = null) {
        // Si le terme est vide, récupérer toutes les affectations
        if (empty($term) && $status === null) {
            return $this->getAll();
        }
        
        $term = "%$term%";
        
        $query = "SELECT a.*, 
                  s.id as student_id, s.student_number, u_s.first_name as student_first_name, u_s.last_name as student_last_name,
                  t.id as teacher_id, u_t.first_name as teacher_first_name, u_t.last_name as teacher_last_name,
                  i.id as internship_id, i.title as internship_title, i.company_id,
                  c.name as company_name
                  FROM assignments a
                  JOIN students s ON a.student_id = s.id
                  JOIN users u_s ON s.user_id = u_s.id
                  JOIN teachers t ON a.teacher_id = t.id
                  JOIN users u_t ON t.user_id = u_t.id
                  JOIN internships i ON a.internship_id = i.id
                  JOIN companies c ON i.company_id = c.id
                  WHERE ";
                  
        $conditions = [];
        $params = [];
        
        // Si un terme de recherche est fourni
        if (!empty($term)) {
            $conditions[] = "(u_s.first_name LIKE :term 
                           OR u_s.last_name LIKE :term 
                           OR s.student_number LIKE :term
                           OR u_t.first_name LIKE :term 
                           OR u_t.last_name LIKE :term
                           OR i.title LIKE :term
                           OR c.name LIKE :term)";
            $params[':term'] = $term;
        } else {
            // Si aucun terme, ajouter une condition toujours vraie
            $conditions[] = "1=1";
        }
        
        // Si un statut est fourni
        if ($status !== null && $status !== '') {
            $conditions[] = "a.status = :status";
            $params[':status'] = $status;
        }
        
        // Combiner les conditions
        $query .= implode(" AND ", $conditions);
        
        // Ajouter un tri
        $query .= " ORDER BY a.assignment_date DESC";
        
        $stmt = $this->db->prepare($query);
        
        // Lier les paramètres
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère des statistiques sur les affectations
     * @return array Statistiques
     */
    public function getStats() {
        $stats = [];
        
        // Nombre d'affectations par statut
        $query = "SELECT status, COUNT(*) as count FROM assignments GROUP BY status";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        $stats['byStatus'] = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $stats['byStatus'][$row['status']] = $row['count'];
        }
        
        // Satisfaction moyenne
        $query = "SELECT AVG(satisfaction_score) as avg_satisfaction FROM assignments WHERE satisfaction_score IS NOT NULL";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $stats['avgSatisfaction'] = $stmt->fetchColumn();
        
        // Compatibilité moyenne
        $query = "SELECT AVG(compatibility_score) as avg_compatibility FROM assignments WHERE compatibility_score IS NOT NULL";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $stats['avgCompatibility'] = $stmt->fetchColumn();
        
        // Nombre d'affectations par département
        $query = "SELECT u.department, COUNT(*) as count 
                  FROM assignments a
                  JOIN students s ON a.student_id = s.id
                  JOIN users u ON s.user_id = u.id
                  WHERE u.department IS NOT NULL
                  GROUP BY u.department";
                  
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        $stats['byDepartment'] = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $stats['byDepartment'][$row['department']] = $row['count'];
        }
        
        return $stats;
    }

    /**
     * Récupère les dernières affectations
     * @param int $limit Nombre d'affectations à récupérer
     * @return array Liste des affectations
     */
    public function getRecent($limit = 10) {
        $query = "SELECT a.*, 
                  s.id as student_id, u_s.first_name as student_first_name, u_s.last_name as student_last_name,
                  t.id as teacher_id, u_t.first_name as teacher_first_name, u_t.last_name as teacher_last_name,
                  i.id as internship_id, i.title as internship_title,
                  c.name as company_name
                  FROM assignments a
                  JOIN students s ON a.student_id = s.id
                  JOIN users u_s ON s.user_id = u_s.id
                  JOIN teachers t ON a.teacher_id = t.id
                  JOIN users u_t ON t.user_id = u_t.id
                  JOIN internships i ON a.internship_id = i.id
                  JOIN companies c ON i.company_id = c.id
                  ORDER BY a.assignment_date DESC
                  LIMIT :limit";
                  
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Vérifie si un étudiant est déjà affecté
     * @param int $studentId ID de l'étudiant
     * @return bool True si l'étudiant est déjà affecté, sinon false
     */
    public function isStudentAssigned($studentId) {
        $query = "SELECT COUNT(*) FROM assignments 
                  WHERE student_id = :student_id 
                  AND status IN ('pending', 'confirmed')";
                  
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':student_id', $studentId);
        $stmt->execute();
        
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Vérifie si un stage est déjà attribué
     * @param int $internshipId ID du stage
     * @return bool True si le stage est déjà attribué, sinon false
     */
    public function isInternshipAssigned($internshipId) {
        $query = "SELECT COUNT(*) FROM assignments 
                  WHERE internship_id = :internship_id 
                  AND status IN ('pending', 'confirmed')";
                  
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':internship_id', $internshipId);
        $stmt->execute();
        
        return (bool)$stmt->fetchColumn();
    }
    
    /**
     * Récupère les affectations d'un étudiant
     * @param int $studentId ID de l'étudiant
     * @param array $options Options de filtre (ex: status)
     * @return array Liste des affectations
     */
    public function getByStudentId($studentId, $options = []) {
        $query = "SELECT a.*, 
                  i.id as internship_id, i.title as internship_title, i.description as internship_description,
                  i.start_date as internship_start_date, i.end_date as internship_end_date,
                  t.id as teacher_id, u_t.first_name as teacher_first_name, u_t.last_name as teacher_last_name,
                  c.id as company_id, c.name as company_name, c.logo_path as company_logo
                  FROM assignments a
                  JOIN internships i ON a.internship_id = i.id
                  JOIN teachers t ON a.teacher_id = t.id
                  JOIN users u_t ON t.user_id = u_t.id
                  JOIN companies c ON i.company_id = c.id
                  WHERE a.student_id = :student_id";
        
        // Ajouter des filtres si nécessaire
        if (isset($options['status'])) {
            $query .= " AND a.status = :status";
        }
        
        $query .= " ORDER BY a.assignment_date DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':student_id', $studentId);
        
        if (isset($options['status'])) {
            $stmt->bindParam(':status', $options['status']);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupère l'affectation active d'un étudiant
     * @param int $studentId ID de l'étudiant
     * @return array|false Données de l'affectation si trouvée, sinon false
     */
    public function getActiveByStudentId($studentId) {
        $query = "SELECT a.*, 
                  i.id as internship_id, i.title as internship_title, i.description as internship_description,
                  i.start_date as internship_start_date, i.end_date as internship_end_date,
                  t.id as teacher_id, u_t.first_name as teacher_first_name, u_t.last_name as teacher_last_name,
                  c.id as company_id, c.name as company_name, c.logo_path as company_logo
                  FROM assignments a
                  JOIN internships i ON a.internship_id = i.id
                  JOIN teachers t ON a.teacher_id = t.id
                  JOIN users u_t ON t.user_id = u_t.id
                  JOIN companies c ON i.company_id = c.id
                  WHERE a.student_id = :student_id
                  AND a.status IN ('confirmed', 'pending')
                  ORDER BY a.status = 'confirmed' DESC, a.assignment_date DESC
                  LIMIT 1";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':student_id', $studentId);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupère les affectations d'un tuteur
     * @param int $teacherId ID du tuteur
     * @param array $options Options de filtre (ex: status)
     * @return array Liste des affectations
     */
    public function getByTeacherId($teacherId, $options = []) {
        $query = "SELECT a.*, 
                  s.id as student_id, s.student_number, u_s.first_name as student_first_name, u_s.last_name as student_last_name,
                  i.id as internship_id, i.title as internship_title, i.description as internship_description,
                  i.start_date as internship_start_date, i.end_date as internship_end_date,
                  c.id as company_id, c.name as company_name
                  FROM assignments a
                  JOIN students s ON a.student_id = s.id
                  JOIN users u_s ON s.user_id = u_s.id
                  JOIN internships i ON a.internship_id = i.id
                  JOIN companies c ON i.company_id = c.id
                  WHERE a.teacher_id = :teacher_id";
        
        // Ajouter des filtres si nécessaire
        if (isset($options['status'])) {
            $query .= " AND a.status = :status";
        }
        
        $query .= " ORDER BY a.assignment_date DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':teacher_id', $teacherId);
        
        if (isset($options['status'])) {
            $stmt->bindParam(':status', $options['status']);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Compte le nombre d'étudiants assignés à un tuteur
     * @param int $teacherId ID du tuteur
     * @return int Nombre d'étudiants assignés
     */
    public function countByTeacherId($teacherId) {
        $query = "SELECT COUNT(*) FROM assignments 
                  WHERE teacher_id = :teacher_id 
                  AND status IN ('pending', 'confirmed')";
                  
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':teacher_id', $teacherId);
        $stmt->execute();
        
        return (int)$stmt->fetchColumn();
    }
    
    /**
     * Récupère une affectation par étudiant et tuteur
     * @param int $studentId ID de l'étudiant
     * @param int $teacherId ID du tuteur
     * @return array|false Données de l'affectation si trouvée, sinon false
     */
    public function getByStudentAndTeacherId($studentId, $teacherId) {
        $query = "SELECT * FROM assignments 
                  WHERE student_id = :student_id 
                  AND teacher_id = :teacher_id
                  AND status IN ('pending', 'confirmed')
                  LIMIT 1";
                  
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':student_id', $studentId);
        $stmt->bindParam(':teacher_id', $teacherId);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupère les affectations liées à un stage
     * @param int $internshipId ID du stage
     * @return array Liste des affectations
     */
    public function getByInternshipId($internshipId) {
        $query = "SELECT a.*, 
                  s.id as student_id, u_s.first_name as student_first_name, u_s.last_name as student_last_name,
                  t.id as teacher_id, u_t.first_name as teacher_first_name, u_t.last_name as teacher_last_name
                  FROM assignments a
                  JOIN students s ON a.student_id = s.id
                  JOIN users u_s ON s.user_id = u_s.id
                  JOIN teachers t ON a.teacher_id = t.id
                  JOIN users u_t ON t.user_id = u_t.id
                  WHERE a.internship_id = :internship_id
                  ORDER BY a.assignment_date DESC";
                  
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':internship_id', $internshipId);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}