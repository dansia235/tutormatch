<?php
/**
 * Modèle pour les évaluations
 * 
 * Gère les opérations CRUD pour les évaluations liées aux affectations
 */
class Evaluation {
    private $db;
    
    /**
     * Constructeur
     * 
     * @param PDO $db Connexion à la base de données
     */
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Récupère une évaluation par son ID
     * 
     * @param int $id ID de l'évaluation
     * @return array|bool Les données de l'évaluation ou false si non trouvée
     */
    public function getById($id) {
        $query = "SELECT * FROM evaluations WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupère toutes les évaluations d'une affectation
     * 
     * @param int $assignmentId ID de l'affectation
     * @return array Les évaluations de l'affectation
     */
    public function getByAssignmentId($assignmentId) {
        $query = "SELECT * FROM evaluations WHERE assignment_id = :assignment_id ORDER BY submission_date DESC";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':assignment_id', $assignmentId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupère toutes les évaluations d'un étudiant
     * 
     * @param int $studentId ID de l'étudiant
     * @return array Les évaluations de l'étudiant
     */
    public function getByStudentId($studentId) {
        $query = "SELECT e.*, a.student_id, a.teacher_id, a.internship_id 
                 FROM evaluations e 
                 JOIN assignments a ON e.assignment_id = a.id 
                 WHERE a.student_id = :student_id 
                 ORDER BY e.submission_date DESC";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':student_id', $studentId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupère toutes les évaluations faites par un enseignant
     * 
     * @param int $teacherId ID de l'enseignant
     * @return array Les évaluations faites par l'enseignant
     */
    public function getByTeacherId($teacherId) {
        $query = "SELECT e.*, a.student_id, a.teacher_id, a.internship_id 
                 FROM evaluations e 
                 JOIN assignments a ON e.assignment_id = a.id 
                 WHERE a.teacher_id = :teacher_id 
                 ORDER BY e.submission_date DESC";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':teacher_id', $teacherId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Crée une nouvelle évaluation
     * 
     * @param array $data Les données de l'évaluation
     * @return int|bool L'ID de la nouvelle évaluation ou false en cas d'échec
     */
    public function create($data) {
        $query = "INSERT INTO evaluations (
                    assignment_id, 
                    evaluator_id, 
                    type, 
                    score, 
                    comments, 
                    strengths, 
                    areas_for_improvement, 
                    next_steps,
                    status,
                    submission_date
                ) VALUES (
                    :assignment_id, 
                    :evaluator_id, 
                    :type, 
                    :score, 
                    :comments, 
                    :strengths, 
                    :areas_for_improvement, 
                    :next_steps,
                    :status,
                    :submission_date
                )";
                
        $stmt = $this->db->prepare($query);
        
        $stmt->bindParam(':assignment_id', $data['assignment_id'], PDO::PARAM_INT);
        $stmt->bindParam(':evaluator_id', $data['evaluator_id'], PDO::PARAM_INT);
        $stmt->bindParam(':type', $data['type'], PDO::PARAM_STR);
        $stmt->bindParam(':score', $data['score'], PDO::PARAM_INT);
        $stmt->bindParam(':comments', $data['comments'], PDO::PARAM_STR);
        $stmt->bindParam(':strengths', $data['strengths'], PDO::PARAM_STR);
        $stmt->bindParam(':areas_for_improvement', $data['areas_for_improvement'], PDO::PARAM_STR);
        $stmt->bindParam(':next_steps', $data['next_steps'], PDO::PARAM_STR);
        $stmt->bindParam(':status', $data['status'], PDO::PARAM_STR);
        $stmt->bindParam(':submission_date', $data['submission_date'], PDO::PARAM_STR);
        
        if ($stmt->execute()) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Met à jour une évaluation existante
     * 
     * @param int $id ID de l'évaluation
     * @param array $data Les nouvelles données
     * @return bool Succès ou échec de la mise à jour
     */
    public function update($id, $data) {
        $query = "UPDATE evaluations SET 
                    type = :type,
                    score = :score,
                    comments = :comments,
                    strengths = :strengths,
                    areas_for_improvement = :areas_for_improvement,
                    next_steps = :next_steps,
                    status = :status";
                    
        // Ajouter la date de soumission si elle est fournie
        if (isset($data['submission_date'])) {
            $query .= ", submission_date = :submission_date";
        }
        
        // Ajouter la date de mise à jour
        if (isset($data['updated_at'])) {
            $query .= ", updated_at = :updated_at";
        } else {
            $query .= ", updated_at = NOW()";
        }
        
        $query .= " WHERE id = :id";
                  
        $stmt = $this->db->prepare($query);
        
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':type', $data['type'], PDO::PARAM_STR);
        $stmt->bindParam(':score', $data['score'], PDO::PARAM_INT);
        $stmt->bindParam(':comments', $data['comments'], PDO::PARAM_STR);
        $stmt->bindParam(':strengths', $data['strengths'], PDO::PARAM_STR);
        $stmt->bindParam(':areas_for_improvement', $data['areas_for_improvement'], PDO::PARAM_STR);
        $stmt->bindParam(':next_steps', $data['next_steps'], PDO::PARAM_STR);
        $stmt->bindParam(':status', $data['status'], PDO::PARAM_STR);
        
        if (isset($data['submission_date'])) {
            $stmt->bindParam(':submission_date', $data['submission_date'], PDO::PARAM_STR);
        }
        
        if (isset($data['updated_at'])) {
            $stmt->bindParam(':updated_at', $data['updated_at'], PDO::PARAM_STR);
        }
        
        return $stmt->execute();
    }
    
    /**
     * Supprime une évaluation
     * 
     * @param int $id ID de l'évaluation à supprimer
     * @return bool Succès ou échec de la suppression
     */
    public function delete($id) {
        $query = "DELETE FROM evaluations WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
    
    /**
     * Calcule la moyenne des scores d'évaluation pour une affectation
     * 
     * @param int $assignmentId ID de l'affectation
     * @return float|null La moyenne des scores ou null si aucune évaluation
     */
    public function getAverageScoreForAssignment($assignmentId) {
        $query = "SELECT AVG(score) as average_score FROM evaluations 
                  WHERE assignment_id = :assignment_id AND score IS NOT NULL";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':assignment_id', $assignmentId, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['average_score'] ? (float)$result['average_score'] : null;
    }
    
    /**
     * Récupère les statistiques d'évaluation pour un enseignant
     * 
     * @param int $teacherId ID de l'enseignant
     * @return array Statistiques d'évaluation
     */
    public function getTeacherEvaluationStats($teacherId) {
        $query = "SELECT 
                    COUNT(*) as total_evaluations,
                    AVG(e.score) as average_score,
                    MIN(e.score) as min_score,
                    MAX(e.score) as max_score
                  FROM evaluations e
                  JOIN assignments a ON e.assignment_id = a.id
                  WHERE a.teacher_id = :teacher_id
                  AND e.score IS NOT NULL";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':teacher_id', $teacherId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Vérifie si une évaluation existe déjà pour une affectation et un type donnés
     * 
     * @param int $assignmentId ID de l'affectation
     * @param string $type Type d'évaluation
     * @return bool True si l'évaluation existe, false sinon
     */
    public function exists($assignmentId, $type) {
        $query = "SELECT COUNT(*) as count FROM evaluations 
                  WHERE assignment_id = :assignment_id AND type = :type";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':assignment_id', $assignmentId, PDO::PARAM_INT);
        $stmt->bindParam(':type', $type, PDO::PARAM_STR);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }
}