<?php
/**
 * API pour récupérer la liste des évaluations
 * Endpoint: /api/evaluations/list
 * Méthode: GET
 * 
 * Paramètres:
 *  - student_id: (optionnel) ID de l'étudiant pour filtrer les évaluations
 *  - type: (optionnel) Type d'évaluation ('mid_term', 'final', 'company')
 */

require_once __DIR__ . '/../utils.php';

// Vérifier que l'utilisateur est connecté
requireApiAuth();

try {
    // Récupérer les paramètres
    $studentId = isset($_GET['student_id']) ? intval($_GET['student_id']) : null;
    $type = isset($_GET['type']) ? $_GET['type'] : 'all';
    
    // Récupérer le rôle de l'utilisateur
    $isTeacher = $_SESSION['user_role'] === 'teacher';
    $isStudent = $_SESSION['user_role'] === 'student';
    $isAdmin = $_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'coordinator';
    
    // Si l'utilisateur est un étudiant, il ne peut voir que ses propres évaluations
    if ($isStudent) {
        // Récupérer l'ID de l'étudiant associé à l'utilisateur
        $studentModel = new Student($db);
        $student = $studentModel->getByUserId($_SESSION['user_id']);
        
        if (!$student) {
            sendJsonError('Profil étudiant non trouvé', 404);
        }
        
        // Forcer le studentId à celui de l'étudiant connecté
        $studentId = $student['id'];
    }
    
    // Si l'utilisateur est un tuteur, vérifier qu'il est bien assigné à l'étudiant
    if ($isTeacher && $studentId) {
        $teacherModel = new Teacher($db);
        $teacher = $teacherModel->getByUserId($_SESSION['user_id']);
        
        if (!$teacher) {
            sendJsonError('Profil tuteur non trouvé', 404);
        }
        
        // Vérifier que l'étudiant est assigné à ce tuteur
        $isAssigned = false;
        $assignments = $teacherModel->getAssignments($teacher['id']);
        
        foreach ($assignments as $assignment) {
            if ($assignment['student_id'] == $studentId) {
                $isAssigned = true;
                break;
            }
        }
        
        if (!$isAssigned) {
            sendJsonError('Vous n\'êtes pas autorisé à accéder aux évaluations de cet étudiant', 403);
        }
    }
    
    // Initialiser le modèle d'évaluation
    $evaluations = [];
    
    if (class_exists('Evaluation')) {
        $evaluationModel = new Evaluation($db);
        
        // Construire la requête en fonction des filtres
        if ($studentId) {
            // Récupérer l'assignment de l'étudiant
            $assignmentModel = new Assignment($db);
            $assignment = $assignmentModel->getByStudentId($studentId);
            
            if ($assignment) {
                // Récupérer les évaluations pour cet assignment
                $evaluations = $evaluationModel->getByAssignmentId($assignment['id']);
                
                // Filtrer par type si nécessaire
                if ($type !== 'all') {
                    $evaluations = array_filter($evaluations, function($eval) use ($type) {
                        return $eval['type'] === $type;
                    });
                }
                
                // Récupérer les informations supplémentaires
                $studentModel = new Student($db);
                $internshipModel = new Internship($db);
                $companyModel = new Company($db);
                
                $studentDetails = $studentModel->getById($studentId);
                $internship = null;
                $company = null;
                
                if ($assignment['internship_id']) {
                    $internship = $internshipModel->getById($assignment['internship_id']);
                    if ($internship && $internship['company_id']) {
                        $company = $companyModel->getById($internship['company_id']);
                    }
                }
                
                // Formatter les évaluations pour l'affichage
                foreach ($evaluations as &$eval) {
                    // Convertir le score de 20 à 5
                    if (isset($eval['score']) && $eval['score'] > 5) {
                        $eval['score'] = round($eval['score'] / 4, 1);
                    }
                    
                    // Décoder les critères si stockés en JSON
                    if (isset($eval['criteria_scores']) && is_string($eval['criteria_scores'])) {
                        $criteriaScores = json_decode($eval['criteria_scores'], true);
                        $eval['criteria'] = [];
                        
                        $criteriaLabels = [
                            'technical_skills' => 'Compétences techniques',
                            'professional_behavior' => 'Comportement professionnel',
                            'communication' => 'Communication',
                            'initiative' => 'Initiative et autonomie',
                            'teamwork' => 'Travail en équipe',
                            'punctuality' => 'Ponctualité et assiduité'
                        ];
                        
                        foreach ($criteriaScores as $key => $score) {
                            $eval['criteria'][] = [
                                'name' => $criteriaLabels[$key] ?? ucfirst(str_replace('_', ' ', $key)),
                                'score' => round($score / 4, 1) // Convertir de 20 à 5
                            ];
                        }
                    }
                    
                    // Ajouter les autres champs si manquants
                    if (!isset($eval['evaluator_name'])) {
                        $eval['evaluator_name'] = 'Évaluateur';
                        
                        // Récupérer le nom de l'évaluateur si possible
                        if ($eval['evaluator_type'] === 'teacher') {
                            $teacherModel = new Teacher($db);
                            $evaluator = $teacherModel->getById($eval['evaluator_id']);
                            if ($evaluator) {
                                $userModel = new User($db);
                                $evaluatorUser = $userModel->getById($evaluator['user_id']);
                                if ($evaluatorUser) {
                                    $eval['evaluator_name'] = $evaluatorUser['first_name'] . ' ' . $evaluatorUser['last_name'];
                                }
                            }
                        }
                    }
                    
                    $eval['date'] = $eval['created_at'] ?? date('Y-m-d');
                    
                    // Parser les champs texte si nécessaire
                    if (!empty($eval['improvements']) && !is_array($eval['improvements'])) {
                        $eval['areas_for_improvement'] = explode("\n", $eval['improvements']);
                    }
                    if (!empty($eval['recommendations']) && !is_array($eval['recommendations'])) {
                        $eval['recommendations'] = explode("\n", $eval['recommendations']);
                    }
                    
                    // Ajouter les informations de l'étudiant et du stage
                    $eval['student'] = $studentDetails;
                    $eval['internship'] = $internship;
                    $eval['company'] = $company;
                }
            }
        } else {
            // Si aucun étudiant n'est spécifié, renvoyer toutes les évaluations visibles par l'utilisateur
            if ($isTeacher) {
                // Pour un tuteur, récupérer les évaluations de tous ses étudiants
                $teacherModel = new Teacher($db);
                $teacher = $teacherModel->getByUserId($_SESSION['user_id']);
                
                if ($teacher) {
                    $assignments = $teacherModel->getAssignments($teacher['id']);
                    
                    foreach ($assignments as $assignment) {
                        $assignmentEvals = $evaluationModel->getByAssignmentId($assignment['id']);
                        
                        // Filtrer par type si nécessaire
                        if ($type !== 'all') {
                            $assignmentEvals = array_filter($assignmentEvals, function($eval) use ($type) {
                                return $eval['type'] === $type;
                            });
                        }
                        
                        $evaluations = array_merge($evaluations, $assignmentEvals);
                    }
                }
            } elseif ($isAdmin) {
                // Pour un admin, récupérer toutes les évaluations
                $evaluations = $evaluationModel->getAll();
                
                // Filtrer par type si nécessaire
                if ($type !== 'all') {
                    $evaluations = array_filter($evaluations, function($eval) use ($type) {
                        return $eval['type'] === $type;
                    });
                }
            }
        }
    } else {
        // Données fictives pour la démonstration
        if ($studentId && ($type === 'all' || $type === 'mid_term')) {
            $evaluations[] = [
                'id' => 1,
                'assignment_id' => 1,
                'date' => date('Y-m-d', strtotime('-30 days')),
                'type' => 'mid_term',
                'evaluator_id' => 1,
                'evaluator_type' => 'teacher',
                'evaluator_name' => 'Professeur Exemple',
                'score' => 4.2,
                'comments' => "L'étudiant montre une bonne progression technique. Il a su s'adapter rapidement aux outils de développement et méthodologies de l'entreprise. Points à améliorer: documentation du code et communication proactive des difficultés rencontrées.",
                'criteria' => [
                    ['name' => 'Compétences techniques', 'score' => 4.5],
                    ['name' => 'Autonomie', 'score' => 4.0],
                    ['name' => 'Communication', 'score' => 3.5],
                    ['name' => 'Intégration dans l\'équipe', 'score' => 4.5],
                    ['name' => 'Qualité du travail', 'score' => 4.0],
                    ['name' => 'Respect des délais', 'score' => 4.5]
                ],
                'areas_for_improvement' => [
                    'Documentation du code',
                    'Communication proactive des problèmes',
                    'Participation aux réunions'
                ],
                'recommendations' => [
                    'Prévoir des points réguliers sur l\'avancement',
                    'Mettre en place un système de documentation',
                    'Participer plus activement aux stand-up meetings'
                ]
            ];
        }
    }
    
    // Envoyer la réponse
    sendJsonResponse([
        'evaluations' => array_values($evaluations)
    ]);
} catch (Exception $e) {
    sendJsonError('Erreur lors de la récupération des évaluations: ' . $e->getMessage(), 500);
}
?>