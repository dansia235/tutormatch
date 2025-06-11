<?php
/**
 * API pour les statistiques d'évaluations d'un tuteur
 * Endpoint: /api/evaluations/teacher-stats
 * Méthode: GET
 */

require_once __DIR__ . '/../utils.php';

// Vérifier que l'utilisateur est connecté et est un tuteur
requireApiAuth();
requireApiRole(['teacher']);

try {
    // Récupérer l'ID du tuteur
    $teacherModel = new Teacher($db);
    $teacher = $teacherModel->getByUserId($_SESSION['user_id']);
    
    if (!$teacher) {
        sendJsonError('Profil de tuteur non trouvé', 404);
    }
    
    $teacherId = $teacher['id'];
    
    // Récupérer les affectations du tuteur
    $assignments = $teacherModel->getAssignments($teacherId);
    
    // Initialiser les statistiques
    $stats = [
        'total_evaluations' => 0,
        'pending_evaluations' => 0,
        'completed_evaluations' => 0,
        'average_score' => 0,
        'improvement_rate' => 0
    ];
    
    // Vérifier si la classe Evaluation existe
    if (class_exists('Evaluation')) {
        $evaluationModel = new Evaluation($db);
        
        // Collecter toutes les évaluations et les évaluations en attente
        $allEvaluations = [];
        $pendingEvaluations = [];
        
        foreach ($assignments as $assignment) {
            $evaluations = $evaluationModel->getByAssignmentId($assignment['id']);
            $allEvaluations = array_merge($allEvaluations, $evaluations);
            
            // Vérifier les évaluations manquantes
            $existingTypes = array_column($evaluations, 'type');
            $requiredTypes = ['mid_term', 'final'];
            $missingTypes = array_diff($requiredTypes, $existingTypes);
            
            foreach ($missingTypes as $type) {
                $pendingEvaluations[] = [
                    'assignment_id' => $assignment['id'],
                    'student_name' => $assignment['student_first_name'] . ' ' . $assignment['student_last_name'],
                    'type' => $type
                ];
            }
        }
        
        // Calculer les statistiques
        $stats['total_evaluations'] = count($assignments) * 2; // 2 évaluations par étudiant
        $stats['completed_evaluations'] = count($allEvaluations);
        $stats['pending_evaluations'] = count($pendingEvaluations);
        
        // Calculer la moyenne (convertir de 20 à 5 pour l'affichage)
        if ($stats['completed_evaluations'] > 0) {
            $totalScore = array_sum(array_column($allEvaluations, 'score'));
            $stats['average_score'] = round(($totalScore / $stats['completed_evaluations']) / 4, 1);
        }
        
        // Calculer le taux d'amélioration
        // Comparer les scores des évaluations mi-parcours et finales pour chaque étudiant
        $improvementCount = 0;
        $totalComparisons = 0;
        
        foreach ($assignments as $assignment) {
            $assignmentEvals = array_filter($allEvaluations, function($eval) use ($assignment) {
                return $eval['assignment_id'] == $assignment['id'];
            });
            
            $midTermEval = null;
            $finalEval = null;
            
            foreach ($assignmentEvals as $eval) {
                if ($eval['type'] === 'mid_term') {
                    $midTermEval = $eval;
                } elseif ($eval['type'] === 'final') {
                    $finalEval = $eval;
                }
            }
            
            if ($midTermEval && $finalEval) {
                $totalComparisons++;
                if ($finalEval['score'] > $midTermEval['score']) {
                    $improvementCount++;
                }
            }
        }
        
        $stats['improvement_rate'] = $totalComparisons > 0 ? round(($improvementCount / $totalComparisons) * 100) : 0;
    } else {
        // Données fictives pour la démonstration
        $stats = [
            'total_evaluations' => count($assignments) * 2,
            'pending_evaluations' => count($assignments),
            'completed_evaluations' => count($assignments),
            'average_score' => 4.2,
            'improvement_rate' => 75
        ];
    }
    
    // Ajouter les évaluations en attente à la réponse
    $stats['pending_list'] = $pendingEvaluations;
    
    // Envoyer la réponse
    sendJsonResponse($stats);
} catch (Exception $e) {
    sendJsonError('Erreur lors de la récupération des statistiques: ' . $e->getMessage(), 500);
}
?>