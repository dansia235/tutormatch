<?php
/**
 * API pour récupérer les évaluations d'un étudiant
 * Endpoint: /api/evaluations/get-student-evaluations.php
 * Méthode: GET
 * Paramètres:
 * - student_id: ID de l'étudiant (optionnel, utilise l'étudiant connecté par défaut)
 */

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../utils.php';

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    sendJsonResponse([
        'error' => true,
        'message' => 'Non autorisé - Utilisateur non connecté'
    ], 401);
    exit;
}

// Vérifier que la requête est une méthode GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJsonResponse([
        'error' => true,
        'message' => 'Méthode non autorisée - Requête GET requise'
    ], 405);
    exit;
}

try {
    // Initialiser les modèles
    $evaluationModel = new Evaluation($db);
    $studentModel = new Student($db);
    $teacherModel = new Teacher($db);
    $userModel = new User($db);
    
    // Déterminer l'ID de l'étudiant à utiliser
    $studentId = null;
    
    // Si un ID d'étudiant est fourni dans la requête
    if (isset($_GET['student_id']) && is_numeric($_GET['student_id'])) {
        $requestedStudentId = (int)$_GET['student_id'];
        
        // Vérifier les permissions pour accéder aux données d'un autre étudiant
        if ($_SESSION['user_role'] === 'student') {
            // Un étudiant ne peut voir que ses propres évaluations
            $student = $studentModel->getByUserId($_SESSION['user_id']);
            if (!$student || $student['id'] !== $requestedStudentId) {
                sendJsonResponse([
                    'error' => true,
                    'message' => 'Non autorisé à accéder aux évaluations d\'un autre étudiant'
                ], 403);
                exit;
            }
            $studentId = $student['id'];
        } else if ($_SESSION['user_role'] === 'teacher') {
            // Un tuteur ne peut voir que les évaluations des étudiants dont il est tuteur
            $teacher = $teacherModel->getByUserId($_SESSION['user_id']);
            if (!$teacher) {
                sendJsonResponse([
                    'error' => true,
                    'message' => 'Profil tuteur non trouvé'
                ], 404);
                exit;
            }
            
            // Vérifier si l'étudiant est assigné à ce tuteur
            $isAssigned = $teacherModel->hasStudentAssigned($teacher['id'], $requestedStudentId);
            if (!$isAssigned) {
                sendJsonResponse([
                    'error' => true,
                    'message' => 'Non autorisé à accéder aux évaluations de cet étudiant'
                ], 403);
                exit;
            }
            $studentId = $requestedStudentId;
        } else {
            // Administrateurs et coordinateurs peuvent voir toutes les évaluations
            $studentId = $requestedStudentId;
        }
    } else if ($_SESSION['user_role'] === 'student') {
        // Si aucun ID n'est fourni et l'utilisateur est un étudiant, utiliser son propre ID
        $student = $studentModel->getByUserId($_SESSION['user_id']);
        if (!$student) {
            sendJsonResponse([
                'error' => true,
                'message' => 'Profil étudiant non trouvé'
            ], 404);
            exit;
        }
        $studentId = $student['id'];
    } else {
        // Pour les autres rôles, un ID d'étudiant est requis
        sendJsonResponse([
            'error' => true,
            'message' => 'ID d\'étudiant requis'
        ], 400);
        exit;
    }
    
    // Récupérer les évaluations de l'étudiant
    $evaluations = $evaluationModel->getByStudentId($studentId);
    
    // Enrichir les données avec des informations supplémentaires
    $enrichedEvaluations = [];
    
    foreach ($evaluations as $evaluation) {
        // Récupérer les informations sur l'évaluateur
        $evaluator = $userModel->getById($evaluation['evaluator_id']);
        
        // Type d'évaluation formaté
        $evaluationType = '';
        switch($evaluation['type']) {
            case 'mid_term': $evaluationType = 'Mi-parcours'; break;
            case 'final': $evaluationType = 'Finale'; break;
            case 'student': $evaluationType = 'Auto-évaluation'; break;
            case 'supervisor': $evaluationType = 'Superviseur'; break;
            case 'teacher': $evaluationType = 'Tuteur'; break;
            default: $evaluationType = ucfirst($evaluation['type']); break;
        }
        
        // Créer une entrée enrichie
        $enrichedEvaluation = [
            'id' => $evaluation['id'],
            'assignment_id' => $evaluation['assignment_id'],
            'type' => $evaluation['type'],
            'type_name' => $evaluationType,
            'status' => $evaluation['status'],
            'score' => floatval($evaluation['score']),
            'technical_avg' => floatval($evaluation['technical_avg']),
            'professional_avg' => floatval($evaluation['professional_avg']),
            'criteria_scores' => $evaluation['criteria_scores'],
            'comments' => $evaluation['comments'],
            'strengths' => $evaluation['strengths'],
            'areas_for_improvement' => $evaluation['areas_for_improvement'],
            'next_steps' => $evaluation['next_steps'],
            'submission_date' => $evaluation['submission_date'],
            'evaluator' => $evaluator ? [
                'id' => $evaluator['id'],
                'name' => $evaluator['first_name'] . ' ' . $evaluator['last_name'],
                'role' => $evaluator['role']
            ] : null
        ];
        
        $enrichedEvaluations[] = $enrichedEvaluation;
    }
    
    // Calculer les statistiques globales
    $stats = [
        'total' => count($enrichedEvaluations),
        'average_score' => 0,
        'technical_avg' => 0,
        'professional_avg' => 0,
        'by_type' => [
            'mid_term' => 0,
            'final' => 0,
            'student' => 0,
            'supervisor' => 0,
            'teacher' => 0
        ]
    ];
    
    // Calculer les moyennes
    $totalScore = 0;
    $totalTechnical = 0;
    $totalProfessional = 0;
    $countWithScore = 0;
    
    foreach ($enrichedEvaluations as $evaluation) {
        // Compter par type
        if (isset($stats['by_type'][$evaluation['type']])) {
            $stats['by_type'][$evaluation['type']]++;
        }
        
        // Calculer les moyennes
        if ($evaluation['score'] > 0) {
            $totalScore += $evaluation['score'];
            $totalTechnical += $evaluation['technical_avg'];
            $totalProfessional += $evaluation['professional_avg'];
            $countWithScore++;
        }
    }
    
    if ($countWithScore > 0) {
        $stats['average_score'] = round($totalScore / $countWithScore, 1);
        $stats['technical_avg'] = round($totalTechnical / $countWithScore, 1);
        $stats['professional_avg'] = round($totalProfessional / $countWithScore, 1);
    }
    
    // Récupérer les informations de l'étudiant
    $student = $studentModel->getById($studentId);
    $studentUser = $student ? $userModel->getById($student['user_id']) : null;
    
    $studentInfo = $studentUser ? [
        'id' => $student['id'],
        'user_id' => $student['user_id'],
        'name' => $studentUser['first_name'] . ' ' . $studentUser['last_name'],
        'email' => $studentUser['email'],
        'department' => $student['department']
    ] : null;
    
    // Envoyer la réponse
    sendJsonResponse([
        'success' => true,
        'student' => $studentInfo,
        'evaluations' => $enrichedEvaluations,
        'stats' => $stats
    ]);
    
} catch (Exception $e) {
    error_log("Erreur API évaluations étudiant: " . $e->getMessage());
    sendJsonResponse([
        'error' => true,
        'message' => 'Erreur lors de la récupération des évaluations: ' . $e->getMessage()
    ], 500);
}
?>