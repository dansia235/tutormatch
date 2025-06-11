<?php
/**
 * Modèle pour la gestion des enseignants (tuteurs)
 */
class Teacher {
    private $db;
    
    /**
     * Constructeur
     * @param PDO $db Instance de connexion à la base de données
     */
    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Récupère un tuteur par son ID
     * @param int $id ID du tuteur
     * @return array|false Données du tuteur si trouvé, sinon false
     */
    public function getById($id) {
        $query = "SELECT t.*, u.username, u.email, u.first_name, u.last_name, u.department, u.profile_image 
                  FROM teachers t
                  JOIN users u ON t.user_id = u.id
                  WHERE t.id = :id LIMIT 1";
                  
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère un tuteur par son ID utilisateur
     * @param int $userId ID de l'utilisateur
     * @return array|false Données du tuteur si trouvé, sinon false
     */
    public function getByUserId($userId) {
        $query = "SELECT t.*, u.username, u.email, u.first_name, u.last_name, u.department, u.profile_image 
                  FROM teachers t
                  JOIN users u ON t.user_id = u.id
                  WHERE t.user_id = :user_id LIMIT 1";
                  
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère tous les tuteurs
     * @param bool $availableOnly Si true, ne récupère que les tuteurs disponibles
     * @return array Liste des tuteurs
     */
    public function getAll($availableOnly = false) {
        $query = "SELECT t.*, u.username, u.email, u.first_name, u.last_name, u.department, u.profile_image 
                  FROM teachers t
                  JOIN users u ON t.user_id = u.id";
                  
        if ($availableOnly) {
            $query .= " WHERE t.available = 1";
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crée un nouveau tuteur
     * @param array $data Données du tuteur
     * @return int|false ID du tuteur créé, sinon false
     */
    public function create($data) {
        $query = "INSERT INTO teachers (user_id, title, specialty, office_location, max_students, available, expertise) 
                  VALUES (:user_id, :title, :specialty, :office_location, :max_students, :available, :expertise)";
                  
        $stmt = $this->db->prepare($query);
        
        // Liaison des paramètres
        $stmt->bindParam(':user_id', $data['user_id']);
        $stmt->bindParam(':title', $data['title']);
        $stmt->bindParam(':specialty', $data['specialty']);
        $stmt->bindParam(':office_location', $data['office_location']);
        $stmt->bindParam(':max_students', $data['max_students']);
        $stmt->bindParam(':available', $data['available']);
        $stmt->bindParam(':expertise', $data['expertise']);
        
        if ($stmt->execute()) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }

    /**
     * Met à jour un tuteur existant
     * @param int $id ID du tuteur
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
        
        $query = "UPDATE teachers SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($query);
        
        return $stmt->execute($values);
    }

    /**
     * Supprime un tuteur
     * @param int $id ID du tuteur
     * @return bool Succès de l'opération
     */
    public function delete($id) {
        $query = "DELETE FROM teachers WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }

    /**
     * Récupère les préférences d'un tuteur
     * @param int $teacherId ID du tuteur
     * @return array Liste des préférences
     */
    public function getPreferences($teacherId) {
        $query = "SELECT * FROM teacher_preferences 
                  WHERE teacher_id = :teacher_id 
                  ORDER BY priority_value DESC";
                  
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':teacher_id', $teacherId);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ajoute une préférence pour un tuteur
     * @param int $teacherId ID du tuteur
     * @param string $preferenceType Type de préférence
     * @param string $preferenceValue Valeur de la préférence
     * @param int $priorityValue Valeur de priorité
     * @return bool Succès de l'opération
     */
    public function addPreference($teacherId, $preferenceType, $preferenceValue, $priorityValue) {
        // Vérifier si la préférence existe déjà
        $query = "SELECT id FROM teacher_preferences 
                  WHERE teacher_id = :teacher_id 
                  AND preference_type = :preference_type 
                  AND preference_value = :preference_value";
                  
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':teacher_id', $teacherId);
        $stmt->bindParam(':preference_type', $preferenceType);
        $stmt->bindParam(':preference_value', $preferenceValue);
        $stmt->execute();
        
        if ($stmt->fetch()) {
            // Mettre à jour la priorité existante
            $query = "UPDATE teacher_preferences 
                      SET priority_value = :priority_value 
                      WHERE teacher_id = :teacher_id 
                      AND preference_type = :preference_type 
                      AND preference_value = :preference_value";
        } else {
            // Insérer une nouvelle préférence
            $query = "INSERT INTO teacher_preferences (teacher_id, preference_type, preference_value, priority_value) 
                      VALUES (:teacher_id, :preference_type, :preference_value, :priority_value)";
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':teacher_id', $teacherId);
        $stmt->bindParam(':preference_type', $preferenceType);
        $stmt->bindParam(':preference_value', $preferenceValue);
        $stmt->bindParam(':priority_value', $priorityValue);
        
        return $stmt->execute();
    }

    /**
     * Supprime une préférence d'un tuteur
     * @param int $preferenceId ID de la préférence
     * @return bool Succès de l'opération
     */
    public function removePreference($preferenceId) {
        $query = "DELETE FROM teacher_preferences WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $preferenceId);
        
        return $stmt->execute();
    }

    /**
     * Récupère les affectations d'un tuteur
     * @param int $teacherId ID du tuteur
     * @param string $status Statut pour filtrer (optionnel)
     * @return array Liste des affectations
     */
    public function getAssignments($teacherId, $status = null) {
        $query = "SELECT a.*, 
                  s.id as student_id, 
                  s.student_number,
                  u_s.id as student_user_id,
                  u_s.first_name as student_first_name, 
                  u_s.last_name as student_last_name,
                  u_s.email as student_email,
                  i.id as internship_id, 
                  i.title as internship_title, 
                  i.company_id,
                  c.name as company_name
                  FROM assignments a
                  JOIN students s ON a.student_id = s.id
                  JOIN users u_s ON s.user_id = u_s.id
                  JOIN internships i ON a.internship_id = i.id
                  JOIN companies c ON i.company_id = c.id
                  WHERE a.teacher_id = :teacher_id";
                  
        if ($status) {
            $query .= " AND a.status = :status";
        }
        
        $query .= " ORDER BY a.assignment_date DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':teacher_id', $teacherId);
        
        if ($status) {
            $stmt->bindParam(':status', $status);
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Compte le nombre d'étudiants actuellement affectés à un tuteur
     * @param int $teacherId ID du tuteur
     * @return int Nombre d'étudiants
     */
    public function countCurrentStudents($teacherId) {
        $query = "SELECT COUNT(*) FROM assignments 
                  WHERE teacher_id = :teacher_id 
                  AND status IN ('pending', 'confirmed')";
                  
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':teacher_id', $teacherId);
        $stmt->execute();
        
        return (int)$stmt->fetchColumn();
    }

    /**
     * Récupère les réunions d'un tuteur
     * @param int $teacherId ID du tuteur
     * @return array Liste des réunions
     */
    public function getMeetings($teacherId) {
        // D'abord récupérer l'ID utilisateur du tuteur
        $query = "SELECT user_id FROM teachers WHERE id = :teacher_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':teacher_id', $teacherId);
        $stmt->execute();
        $userId = $stmt->fetchColumn();
        
        if (!$userId) {
            return [];
        }
        
        // Récupérer les réunions où le tuteur est organisateur ou participant
        $query = "SELECT m.*, 
                  CASE WHEN m.organizer_id = :user_id THEN 'organizer' ELSE 'participant' END as role,
                  COALESCE(mp.status, 'organizer') as participant_status
                  FROM meetings m
                  LEFT JOIN meeting_participants mp ON m.id = mp.meeting_id AND mp.user_id = :user_id
                  WHERE m.organizer_id = :user_id OR mp.user_id = :user_id
                  ORDER BY m.date_time ASC";
                  
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Recherche des tuteurs
     * @param string $term Terme de recherche
     * @param bool $availableOnly Si true, ne recherche que les tuteurs disponibles
     * @return array Liste des tuteurs correspondants
     */
    public function search($term, $availableOnly = false) {
        $term = "%$term%";
        
        $query = "SELECT t.*, u.username, u.email, u.first_name, u.last_name, u.department, u.profile_image 
                  FROM teachers t
                  JOIN users u ON t.user_id = u.id
                  WHERE (u.username LIKE :term 
                  OR u.email LIKE :term 
                  OR u.first_name LIKE :term 
                  OR u.last_name LIKE :term
                  OR t.title LIKE :term
                  OR t.specialty LIKE :term
                  OR t.expertise LIKE :term)";
        
        if ($availableOnly) {
            $query .= " AND t.available = 1";
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':term', $term);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les tuteurs par département
     * @param string $department Département
     * @param bool $availableOnly Si true, ne récupère que les tuteurs disponibles
     * @return array Liste des tuteurs
     */
    public function getByDepartment($department, $availableOnly = false) {
        $query = "SELECT t.*, u.username, u.email, u.first_name, u.last_name, u.department, u.profile_image 
                  FROM teachers t
                  JOIN users u ON t.user_id = u.id
                  WHERE u.department = :department";
                  
        if ($availableOnly) {
            $query .= " AND t.available = 1";
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':department', $department);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les statistiques de charge de travail des tuteurs
     * @return array Statistiques
     */
    public function getWorkloadStats() {
        $query = "SELECT t.id, u.first_name, u.last_name, u.department, 
                  t.max_students, COUNT(a.id) as current_students,
                  (COUNT(a.id) / t.max_students * 100) as workload_percentage
                  FROM teachers t
                  JOIN users u ON t.user_id = u.id
                  LEFT JOIN assignments a ON t.id = a.teacher_id AND a.status IN ('pending', 'confirmed')
                  GROUP BY t.id
                  ORDER BY workload_percentage DESC";
                  
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}