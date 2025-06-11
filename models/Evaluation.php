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
                    evaluatee_id, 
                    type, 
                    score, 
                    feedback, 
                    strengths, 
                    areas_to_improve, 
                    submission_date
                ) VALUES (
                    :assignment_id, 
                    :evaluator_id, 
                    :evaluatee_id, 
                    :type, 
                    :score, 
                    :feedback, 
                    :strengths, 
                    :areas_to_improve, 
                    NOW()
                )";
                
        $stmt = $this->db->prepare($query);
        
        $stmt->bindParam(':assignment_id', $data['assignment_id'], PDO::PARAM_INT);
        $stmt->bindParam(':evaluator_id', $data['evaluator_id'], PDO::PARAM_INT);
        $stmt->bindParam(':evaluatee_id', $data['evaluatee_id'], PDO::PARAM_INT);
        $stmt->bindParam(':type', $data['type'], PDO::PARAM_STR);
        $stmt->bindParam(':score', $data['score'], PDO::PARAM_STR);
        $stmt->bindParam(':feedback', $data['feedback'], PDO::PARAM_STR);
        $stmt->bindParam(':strengths', $data['strengths'], PDO::PARAM_STR);
        $stmt->bindParam(':areas_to_improve', $data['areas_to_improve'], PDO::PARAM_STR);
        
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
                    score = :score,
                    feedback = :feedback,
                    strengths = :strengths,
                    areas_to_improve = :areas_to_improve,
                    submission_date = NOW()
                  WHERE id = :id";
                  
        $stmt = $this->db->prepare($query);
        
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':score', $data['score'], PDO::PARAM_STR);
        $stmt->bindParam(':feedback', $data['feedback'], PDO::PARAM_STR);
        $stmt->bindParam(':strengths', $data['strengths'], PDO::PARAM_STR);
        $stmt->bindParam(':areas_to_improve', $data['areas_to_improve'], PDO::PARAM_STR);
        
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
}