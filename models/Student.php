<?php
/**
 * Modèle pour la gestion des étudiants
 */
class Student {
    private $db;
    
    /**
     * Constructeur
     * @param PDO $db Instance de connexion à la base de données
     */
    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Récupère un étudiant par son ID
     * @param int $id ID de l'étudiant
     * @return array|false Données de l'étudiant si trouvé, sinon false
     */
    public function getById($id) {
        $query = "SELECT s.*, u.username, u.email, u.first_name, u.last_name, u.department, u.profile_image 
                  FROM students s
                  JOIN users u ON s.user_id = u.id
                  WHERE s.id = :id LIMIT 1";
                  
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère un étudiant par son ID utilisateur
     * @param int $userId ID de l'utilisateur
     * @return array|false Données de l'étudiant si trouvé, sinon false
     */
    public function getByUserId($userId) {
        $query = "SELECT s.*, u.username, u.email, u.first_name, u.last_name, u.department, u.profile_image 
                  FROM students s
                  JOIN users u ON s.user_id = u.id
                  WHERE s.user_id = :user_id LIMIT 1";
                  
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère tous les étudiants
     * @param string $status Statut pour filtrer (optionnel)
     * @return array Liste des étudiants
     */
    public function getAll($status = null) {
        $query = "SELECT s.*, u.username, u.email, u.first_name, u.last_name, u.department, u.profile_image 
                  FROM students s
                  JOIN users u ON s.user_id = u.id";
                  
        if ($status) {
            $query .= " WHERE s.status = :status";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':status', $status);
        } else {
            $stmt = $this->db->prepare($query);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crée un nouvel étudiant
     * @param array $data Données de l'étudiant
     * @return int|false ID de l'étudiant créé, sinon false
     */
    public function create($data) {
        $query = "INSERT INTO students (user_id, student_number, program, level, average_grade, graduation_year, skills, cv_path, status) 
                  VALUES (:user_id, :student_number, :program, :level, :average_grade, :graduation_year, :skills, :cv_path, :status)";
                  
        $stmt = $this->db->prepare($query);
        
        // Liaison des paramètres
        $stmt->bindParam(':user_id', $data['user_id']);
        $stmt->bindParam(':student_number', $data['student_number']);
        $stmt->bindParam(':program', $data['program']);
        $stmt->bindParam(':level', $data['level']);
        $stmt->bindParam(':average_grade', $data['average_grade']);
        $stmt->bindParam(':graduation_year', $data['graduation_year']);
        $stmt->bindParam(':skills', $data['skills']);
        $stmt->bindParam(':cv_path', $data['cv_path']);
        $stmt->bindParam(':status', $data['status']);
        
        if ($stmt->execute()) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }

    /**
     * Met à jour un étudiant existant
     * @param int $id ID de l'étudiant
     * @param array $data Données à mettre à jour
     * @return bool Succès de l'opération
     */
    public function update($id, $data) {
        $fields = [];
        $values = [':id' => $id];
        
        // Construire dynamiquement les champs à mettre à jour
        foreach ($data as $key => $value) {
            if ($key !== 'id' && $key !== 'user_id') {
                $fields[] = "$key = :$key";
                $values[":$key"] = $value;
            }
        }
        
        if (empty($fields)) {
            return false; // Rien à mettre à jour
        }
        
        $query = "UPDATE students SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($query);
        
        return $stmt->execute($values);
    }

    /**
     * Supprime un étudiant
     * @param int $id ID de l'étudiant
     * @return bool Succès de l'opération
     */
    public function delete($id) {
        $query = "DELETE FROM students WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }

    /**
     * Récupère les préférences de stage d'un étudiant
     * @param int $studentId ID de l'étudiant
     * @return array Liste des préférences
     */
    public function getPreferences($studentId) {
        $query = "SELECT sp.*, i.title, i.company_id, c.name as company_name 
                  FROM student_preferences sp
                  JOIN internships i ON sp.internship_id = i.id
                  JOIN companies c ON i.company_id = c.id
                  WHERE sp.student_id = :student_id
                  ORDER BY sp.preference_order ASC";
                  
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':student_id', $studentId);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ajoute une préférence de stage pour un étudiant
     * @param int $studentId ID de l'étudiant
     * @param int $internshipId ID du stage
     * @param int $preferenceOrder Ordre de préférence
     * @return bool Succès de l'opération
     */
    public function addPreference($studentId, $internshipId, $preferenceOrder) {
        // Vérifier si la préférence existe déjà
        $query = "SELECT id FROM student_preferences 
                  WHERE student_id = :student_id AND internship_id = :internship_id";
                  
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':student_id', $studentId);
        $stmt->bindParam(':internship_id', $internshipId);
        $stmt->execute();
        
        if ($stmt->fetch()) {
            // Mettre à jour l'ordre de préférence existant
            $query = "UPDATE student_preferences 
                      SET preference_order = :preference_order 
                      WHERE student_id = :student_id AND internship_id = :internship_id";
        } else {
            // Insérer une nouvelle préférence
            $query = "INSERT INTO student_preferences (student_id, internship_id, preference_order) 
                      VALUES (:student_id, :internship_id, :preference_order)";
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':student_id', $studentId);
        $stmt->bindParam(':internship_id', $internshipId);
        $stmt->bindParam(':preference_order', $preferenceOrder);
        
        return $stmt->execute();
    }

    /**
     * Supprime une préférence de stage pour un étudiant
     * @param int $studentId ID de l'étudiant
     * @param int $internshipId ID du stage
     * @return bool Succès de l'opération
     */
    public function removePreference($studentId, $internshipId) {
        $query = "DELETE FROM student_preferences 
                  WHERE student_id = :student_id AND internship_id = :internship_id";
                  
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':student_id', $studentId);
        $stmt->bindParam(':internship_id', $internshipId);
        
        return $stmt->execute();
    }

    /**
     * Récupère l'affectation d'un étudiant
     * @param int $studentId ID de l'étudiant
     * @return array|false Données de l'affectation si trouvée, sinon false
     */
    public function getAssignment($studentId) {
        $query = "SELECT a.*, 
                  t.id as teacher_id, u_t.first_name as teacher_first_name, u_t.last_name as teacher_last_name,
                  i.id as internship_id, i.title as internship_title, i.company_id,
                  c.name as company_name
                  FROM assignments a
                  JOIN teachers t ON a.teacher_id = t.id
                  JOIN users u_t ON t.user_id = u_t.id
                  JOIN internships i ON a.internship_id = i.id
                  JOIN companies c ON i.company_id = c.id
                  WHERE a.student_id = :student_id
                  ORDER BY a.assignment_date DESC
                  LIMIT 1";
                  
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':student_id', $studentId);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les documents d'un étudiant
     * @param int $studentId ID de l'étudiant
     * @return array Liste des documents
     */
    public function getDocuments($studentId) {
        // D'abord récupérer l'ID utilisateur de l'étudiant
        $query = "SELECT user_id FROM students WHERE id = :student_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':student_id', $studentId);
        $stmt->execute();
        $userId = $stmt->fetchColumn();
        
        if (!$userId) {
            return [];
        }
        
        // Récupérer les documents de l'utilisateur
        $query = "SELECT d.* FROM documents d
                  WHERE d.user_id = :user_id
                  ORDER BY d.upload_date DESC";
                  
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les réunions d'un étudiant
     * @param int $studentId ID de l'étudiant
     * @return array Liste des réunions
     */
    public function getMeetings($studentId) {
        // D'abord récupérer l'ID utilisateur de l'étudiant
        $query = "SELECT user_id FROM students WHERE id = :student_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':student_id', $studentId);
        $stmt->execute();
        $userId = $stmt->fetchColumn();
        
        if (!$userId) {
            return [];
        }
        
        // Récupérer les réunions où l'étudiant est participant
        $query = "SELECT m.*, mp.status as participant_status, 
                  u.first_name as organizer_first_name, u.last_name as organizer_last_name
                  FROM meetings m
                  JOIN meeting_participants mp ON m.id = mp.meeting_id
                  JOIN users u ON m.organizer_id = u.id
                  WHERE mp.user_id = :user_id
                  ORDER BY m.date_time ASC";
                  
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Recherche des étudiants
     * @param string $term Terme de recherche
     * @param string $status Statut pour filtrer (optionnel)
     * @return array Liste des étudiants correspondants
     */
    public function search($term, $status = null) {
        // Construire la requête de base
        $query = "SELECT s.*, u.username, u.email, u.first_name, u.last_name, u.department, u.profile_image 
                  FROM students s
                  JOIN users u ON s.user_id = u.id
                  WHERE (u.username LIKE :term1 
                  OR u.email LIKE :term2 
                  OR u.first_name LIKE :term3 
                  OR u.last_name LIKE :term4
                  OR s.student_number LIKE :term5
                  OR s.program LIKE :term6)";
                  
        $termValue = "%$term%";
        $params = [
            ':term1' => $termValue,
            ':term2' => $termValue,
            ':term3' => $termValue,
            ':term4' => $termValue,
            ':term5' => $termValue,
            ':term6' => $termValue
        ];
        
        // Ajouter la clause de statut si nécessaire
        if ($status) {
            $query .= " AND s.status = :status";
            $params[':status'] = $status;
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Vérifie si un numéro d'étudiant existe déjà
     * @param string $studentNumber Numéro d'étudiant
     * @param int $excludeId ID étudiant à exclure (optionnel, pour les mises à jour)
     * @return bool True si le numéro existe, sinon false
     */
    public function studentNumberExists($studentNumber, $excludeId = null) {
        $query = "SELECT COUNT(*) FROM students WHERE student_number = :student_number";
        
        if ($excludeId) {
            $query .= " AND id != :id";
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':student_number', $studentNumber);
        
        if ($excludeId) {
            $stmt->bindParam(':id', $excludeId);
        }
        
        $stmt->execute();
        
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Compte le nombre d'étudiants par statut
     * @return array Nombre d'étudiants par statut
     */
    public function countByStatus() {
        $query = "SELECT status, COUNT(*) as count FROM students GROUP BY status";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[$row['status']] = $row['count'];
        }
        
        return $result;
    }
    
    /**
     * Met à jour les préférences de stage d'un étudiant
     * @param int $studentId ID de l'étudiant
     * @param array $preferences Liste des préférences (internship_id, rank)
     * @return bool Succès de l'opération
     */
    public function updatePreferences($studentId, $preferences) {
        try {
            // Commencer une transaction
            $this->db->beginTransaction();
            
            // Supprimer toutes les préférences existantes
            $query = "DELETE FROM student_preferences WHERE student_id = :student_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':student_id', $studentId);
            $stmt->execute();
            
            // Insérer les nouvelles préférences
            if (!empty($preferences)) {
                $query = "INSERT INTO student_preferences (student_id, internship_id, preference_order, reason) 
                          VALUES (:student_id, :internship_id, :preference_order, :reason)";
                $stmt = $this->db->prepare($query);
                
                foreach ($preferences as $index => $preference) {
                    $internshipId = $preference['internship_id'];
                    $rank = $preference['rank'] ?? ($index + 1);
                    $reason = $preference['reason'] ?? null;
                    
                    $stmt->bindParam(':student_id', $studentId);
                    $stmt->bindParam(':internship_id', $internshipId);
                    $stmt->bindParam(':preference_order', $rank);
                    $stmt->bindParam(':reason', $reason);
                    $stmt->execute();
                }
            }
            
            // Valider la transaction
            $this->db->commit();
            return true;
            
        } catch (PDOException $e) {
            // Annuler la transaction en cas d'erreur
            $this->db->rollBack();
            error_log("Erreur lors de la mise à jour des préférences: " . $e->getMessage());
            return false;
        }
    }
}