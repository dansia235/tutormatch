<?php
/**
 * Modèle pour les évaluations
 * 
 * Gère les opérations CRUD pour les évaluations liées aux affectations
 */
class Evaluation {
    private $db;
    
    // Structure standard des critères d'évaluation
    private $criteriaStructure = [
        // Critères techniques
        'technical' => [
            'technical_mastery' => [
                'name' => 'Maîtrise des technologies',
                'category' => 'technical',
                'description' => 'Capacité à utiliser les technologies et outils liés au stage',
                'weight' => 1.0
            ],
            'work_quality' => [
                'name' => 'Qualité du travail',
                'category' => 'technical',
                'description' => 'Précision, clarté et fiabilité des livrables produits',
                'weight' => 1.0
            ],
            'problem_solving' => [
                'name' => 'Résolution de problèmes',
                'category' => 'technical',
                'description' => 'Capacité à analyser et résoudre des problèmes techniques',
                'weight' => 1.0
            ],
            'documentation' => [
                'name' => 'Documentation',
                'category' => 'technical',
                'description' => 'Qualité de la documentation produite et des commentaires',
                'weight' => 1.0
            ]
        ],
        
        // Critères professionnels
        'professional' => [
            'autonomy' => [
                'name' => 'Autonomie',
                'category' => 'professional',
                'description' => 'Capacité à travailler de manière indépendante',
                'weight' => 1.0
            ],
            'communication' => [
                'name' => 'Communication',
                'category' => 'professional',
                'description' => 'Clarté et efficacité de la communication écrite et orale',
                'weight' => 1.0
            ],
            'team_integration' => [
                'name' => 'Intégration dans l\'équipe',
                'category' => 'professional',
                'description' => 'Collaboration et interactions avec les membres de l\'équipe',
                'weight' => 1.0
            ],
            'deadline_respect' => [
                'name' => 'Respect des délais',
                'category' => 'professional',
                'description' => 'Ponctualité et respect des échéances fixées',
                'weight' => 1.0
            ]
        ]
    ];
    
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
        
        $evaluation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($evaluation) {
            // Décoder les critères JSON si présents
            if (isset($evaluation['criteria_scores']) && !empty($evaluation['criteria_scores'])) {
                $evaluation['criteria_scores'] = json_decode($evaluation['criteria_scores'], true);
            } else {
                $evaluation['criteria_scores'] = $this->initEmptyCriteriaScores();
            }
        }
        
        return $evaluation;
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
        
        $evaluations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Décoder les critères JSON pour chaque évaluation
        foreach ($evaluations as &$evaluation) {
            if (isset($evaluation['criteria_scores']) && !empty($evaluation['criteria_scores'])) {
                $evaluation['criteria_scores'] = json_decode($evaluation['criteria_scores'], true);
            } else {
                $evaluation['criteria_scores'] = $this->initEmptyCriteriaScores();
            }
        }
        
        return $evaluations;
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
        
        $evaluations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Décoder les critères JSON pour chaque évaluation
        foreach ($evaluations as &$evaluation) {
            if (isset($evaluation['criteria_scores']) && !empty($evaluation['criteria_scores'])) {
                $evaluation['criteria_scores'] = json_decode($evaluation['criteria_scores'], true);
            } else {
                $evaluation['criteria_scores'] = $this->initEmptyCriteriaScores();
            }
        }
        
        return $evaluations;
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
        
        $evaluations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Décoder les critères JSON pour chaque évaluation
        foreach ($evaluations as &$evaluation) {
            if (isset($evaluation['criteria_scores']) && !empty($evaluation['criteria_scores'])) {
                $evaluation['criteria_scores'] = json_decode($evaluation['criteria_scores'], true);
            } else {
                $evaluation['criteria_scores'] = $this->initEmptyCriteriaScores();
            }
        }
        
        return $evaluations;
    }
    
    /**
     * Récupère toutes les évaluations où un utilisateur est évaluateur
     * 
     * @param int $userId ID de l'utilisateur évaluateur
     * @return array Les évaluations créées par cet utilisateur
     */
    public function getByEvaluatorId($userId) {
        $query = "SELECT e.*, a.student_id, a.teacher_id, a.internship_id 
                 FROM evaluations e 
                 JOIN assignments a ON e.assignment_id = a.id 
                 WHERE e.evaluator_id = :evaluator_id 
                 ORDER BY e.submission_date DESC";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':evaluator_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        $evaluations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Décoder les critères JSON pour chaque évaluation
        foreach ($evaluations as &$evaluation) {
            if (isset($evaluation['criteria_scores']) && !empty($evaluation['criteria_scores'])) {
                $evaluation['criteria_scores'] = json_decode($evaluation['criteria_scores'], true);
            } else {
                $evaluation['criteria_scores'] = $this->initEmptyCriteriaScores();
            }
        }
        
        return $evaluations;
    }
    
    /**
     * Crée une nouvelle évaluation
     * 
     * @param array $data Les données de l'évaluation
     * @return int|bool L'ID de la nouvelle évaluation ou false en cas d'échec
     */
    public function create($data) {
        // Vérifier l'unicité de l'évaluation avant la création
        if (!$this->canCreateEvaluation($data['assignment_id'], $data['type'], $data['evaluator_id'])) {
            throw new Exception("Une évaluation de ce type existe déjà pour cet étudiant et cet évaluateur.");
        }
        
        // Préparer les critères d'évaluation
        $criteriaScores = isset($data['criteria_scores']) ? $data['criteria_scores'] : $this->initEmptyCriteriaScores();
        
        // Calculer les moyennes techniques et professionnelles
        $averages = $this->calculateAverages($criteriaScores);
        
        $query = "INSERT INTO evaluations (
                    assignment_id, 
                    evaluator_id, 
                    evaluatee_id,
                    type, 
                    status,
                    score,
                    technical_avg,
                    professional_avg,
                    criteria_scores,
                    comments, 
                    strengths, 
                    areas_for_improvement, 
                    next_steps,
                    submission_date,
                    updated_at
                ) VALUES (
                    :assignment_id, 
                    :evaluator_id, 
                    :evaluatee_id,
                    :type, 
                    :status,
                    :score,
                    :technical_avg,
                    :professional_avg,
                    :criteria_scores,
                    :comments, 
                    :strengths, 
                    :areas_for_improvement, 
                    :next_steps,
                    :submission_date,
                    :updated_at
                )";
                
        $stmt = $this->db->prepare($query);
        
        // Valeur par défaut pour la date de soumission
        $submissionDate = isset($data['submission_date']) ? $data['submission_date'] : date('Y-m-d H:i:s');
        
        // Valeur par défaut pour le statut
        $status = isset($data['status']) ? $data['status'] : 'submitted';
        
        // Préparer les données JSON
        $criteriaScoresJson = json_encode($criteriaScores);
        
        $params = [
            'assignment_id' => $data['assignment_id'],
            'evaluator_id' => $data['evaluator_id'],
            'evaluatee_id' => $data['evaluatee_id'] ?? $data['evaluator_id'], // Par défaut, l'évaluateur est l'évalué (auto-évaluation)
            'type' => $data['type'],
            'status' => $status,
            'score' => $averages['overall_avg'],
            'technical_avg' => $averages['technical_avg'],
            'professional_avg' => $averages['professional_avg'],
            'criteria_scores' => $criteriaScoresJson,
            'comments' => $data['comments'] ?? '',
            'strengths' => $data['strengths'] ?? '',
            'areas_for_improvement' => $data['areas_for_improvement'] ?? '',
            'next_steps' => $data['next_steps'] ?? '',
            'submission_date' => $submissionDate,
            'updated_at' => $submissionDate
        ];
        
        // Binding des paramètres
        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        
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
        // Récupérer l'évaluation existante
        $existing = $this->getById($id);
        if (!$existing) {
            return false;
        }
        
        // Fusionner les critères existants avec les nouveaux
        $criteriaScores = isset($data['criteria_scores']) ? $data['criteria_scores'] : $existing['criteria_scores'];
        
        // Calculer les moyennes techniques et professionnelles
        $averages = $this->calculateAverages($criteriaScores);
        
        $query = "UPDATE evaluations SET 
                    type = :type,
                    status = :status,
                    score = :score,
                    technical_avg = :technical_avg,
                    professional_avg = :professional_avg,
                    criteria_scores = :criteria_scores,
                    comments = :comments,
                    strengths = :strengths,
                    areas_for_improvement = :areas_for_improvement,
                    next_steps = :next_steps";
                    
        // Ajouter la date de soumission si elle est fournie
        if (isset($data['submission_date'])) {
            $query .= ", submission_date = :submission_date";
        }
        
        // Ajouter la date de mise à jour
        $query .= ", updated_at = :updated_at";
        $query .= " WHERE id = :id";
                  
        $stmt = $this->db->prepare($query);
        
        // Préparer les données JSON
        $criteriaScoresJson = json_encode($criteriaScores);
        
        // Valeur par défaut pour le statut
        $status = isset($data['status']) ? $data['status'] : $existing['status'];
        
        $params = [
            'id' => $id,
            'type' => $data['type'] ?? $existing['type'],
            'status' => $status,
            'score' => $averages['overall_avg'],
            'technical_avg' => $averages['technical_avg'],
            'professional_avg' => $averages['professional_avg'],
            'criteria_scores' => $criteriaScoresJson,
            'comments' => $data['comments'] ?? $existing['comments'],
            'strengths' => $data['strengths'] ?? $existing['strengths'],
            'areas_for_improvement' => $data['areas_for_improvement'] ?? $existing['areas_for_improvement'],
            'next_steps' => $data['next_steps'] ?? $existing['next_steps'],
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if (isset($data['submission_date'])) {
            $params['submission_date'] = $data['submission_date'];
        }
        
        // Binding des paramètres
        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value);
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
                    MAX(e.score) as max_score,
                    AVG(e.technical_avg) as avg_technical,
                    AVG(e.professional_avg) as avg_professional
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
     * Récupère les statistiques d'évaluation pour un étudiant
     * 
     * @param int $studentId ID de l'étudiant
     * @return array Statistiques d'évaluation
     */
    public function getStudentEvaluationStats($studentId) {
        $query = "SELECT 
                    COUNT(*) as total_evaluations,
                    AVG(e.score) as average_score,
                    MIN(e.score) as min_score,
                    MAX(e.score) as max_score,
                    AVG(e.technical_avg) as avg_technical,
                    AVG(e.professional_avg) as avg_professional
                  FROM evaluations e
                  JOIN assignments a ON e.assignment_id = a.id
                  WHERE a.student_id = :student_id
                  AND e.score IS NOT NULL";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':student_id', $studentId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Vérifie si une évaluation existe déjà pour une affectation et un type donnés
     * 
     * @param int $assignmentId ID de l'affectation
     * @param string $type Type d'évaluation
     * @param int $evaluatorId ID de l'évaluateur (optionnel)
     * @return bool True si l'évaluation existe, false sinon
     */
    public function exists($assignmentId, $type, $evaluatorId = null) {
        $query = "SELECT COUNT(*) as count FROM evaluations 
                  WHERE assignment_id = :assignment_id AND type = :type";
        
        // Ajouter une condition sur l'évaluateur si fourni
        if ($evaluatorId !== null) {
            $query .= " AND evaluator_id = :evaluator_id";
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':assignment_id', $assignmentId, PDO::PARAM_INT);
        $stmt->bindParam(':type', $type, PDO::PARAM_STR);
        
        if ($evaluatorId !== null) {
            $stmt->bindParam(':evaluator_id', $evaluatorId, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }
    
    /**
     * Initialise un ensemble de critères d'évaluation vides
     * 
     * @return array Structure de critères avec scores à 0
     */
    public function initEmptyCriteriaScores() {
        $criteriaScores = [];
        
        foreach ($this->criteriaStructure as $category => $criteria) {
            foreach ($criteria as $key => $criterion) {
                $criteriaScores[$key] = [
                    'score' => 0,
                    'comment' => ''
                ];
            }
        }
        
        return $criteriaScores;
    }
    
    /**
     * Calcule les moyennes techniques et professionnelles basées sur les critères
     * 
     * @param array $criteriaScores Tableau des scores de critères
     * @return array Tableau contenant les moyennes techniques, professionnelles et globales
     */
    private function calculateAverages($criteriaScores) {
        $technicalSum = 0;
        $technicalCount = 0;
        $professionalSum = 0;
        $professionalCount = 0;
        
        foreach ($criteriaScores as $key => $criterion) {
            $score = isset($criterion['score']) ? floatval($criterion['score']) : 0;
            
            // Déterminer la catégorie du critère
            $category = null;
            foreach ($this->criteriaStructure as $cat => $criteria) {
                if (isset($criteria[$key])) {
                    $category = $cat;
                    break;
                }
            }
            
            if ($category === 'technical') {
                $technicalSum += $score;
                $technicalCount++;
            } else if ($category === 'professional') {
                $professionalSum += $score;
                $professionalCount++;
            }
        }
        
        $averages = [
            'technical_avg' => $technicalCount > 0 ? round($technicalSum / $technicalCount, 1) : 0,
            'professional_avg' => $professionalCount > 0 ? round($professionalSum / $professionalCount, 1) : 0
        ];
        
        $averages['overall_avg'] = round(($averages['technical_avg'] + $averages['professional_avg']) / 2, 1);
        
        return $averages;
    }
    
    /**
     * Récupère la structure des critères d'évaluation
     * 
     * @return array Structure complète des critères
     */
    public function getCriteriaStructure() {
        return $this->criteriaStructure;
    }
    
    /**
     * Vérifie si une évaluation peut être créée en respectant les règles d'unicité
     * Règles:
     * - 1 seule évaluation finale par tuteur/étudiant
     * - 1 seule évaluation mi-parcours par tuteur/étudiant  
     * - 1 seule auto-évaluation par étudiant
     * 
     * @param int $assignmentId ID de l'affectation
     * @param string $type Type d'évaluation (mid_term, final, student)
     * @param int $evaluatorId ID de l'évaluateur
     * @return bool True si l'évaluation peut être créée, false sinon
     */
    public function canCreateEvaluation($assignmentId, $type, $evaluatorId) {
        // Vérifier s'il existe déjà une évaluation de ce type pour cette affectation
        $query = "SELECT COUNT(*) FROM evaluations 
                  WHERE assignment_id = :assignment_id 
                  AND type = :type";
        
        $params = [
            ':assignment_id' => $assignmentId,
            ':type' => $type
        ];
        
        // Pour les évaluations de tuteur (mid_term, final), vérifier aussi l'évaluateur
        if (in_array($type, ['mid_term', 'final'])) {
            $query .= " AND evaluator_id = :evaluator_id";
            $params[':evaluator_id'] = $evaluatorId;
        }
        
        // Pour les auto-évaluations (student), vérifier que l'évaluateur est l'étudié
        if ($type === 'student') {
            $query .= " AND evaluator_id = evaluatee_id";
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        
        $count = $stmt->fetchColumn();
        
        // Retourner true si aucune évaluation de ce type n'existe déjà
        return $count == 0;
    }
    
    /**
     * Vérifie les évaluations existantes pour un assignment donné
     * 
     * @param int $assignmentId ID de l'affectation
     * @return array Informations sur les évaluations existantes
     */
    public function getEvaluationStatus($assignmentId) {
        $query = "SELECT type, evaluator_id, COUNT(*) as count 
                  FROM evaluations 
                  WHERE assignment_id = :assignment_id 
                  GROUP BY type, evaluator_id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':assignment_id', $assignmentId, PDO::PARAM_INT);
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $status = [
            'mid_term' => false,
            'final' => false,
            'student' => false,
            'can_create_mid_term' => true,
            'can_create_final' => true,
            'can_create_student' => true
        ];
        
        foreach ($results as $result) {
            $status[$result['type']] = true;
            $status['can_create_' . $result['type']] = false;
        }
        
        return $status;
    }
}