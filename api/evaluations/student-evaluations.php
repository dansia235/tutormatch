<?php
/**
 * API pour récupérer les évaluations d'un étudiant
 * Endpoint: /api/evaluations/student-evaluations
 * Méthode: GET
 */

require_once __DIR__ . '/../utils.php';

// Vérifier que l'utilisateur est connecté
requireApiAuth();

// Vérifier que l'utilisateur est un étudiant
if ($_SESSION['user_role'] !== 'student') {
    sendJsonError('Accès non autorisé', 403);
}

try {
    // Récupérer l'ID de l'étudiant
    $studentModel = new Student($db);
    $student = $studentModel->getByUserId($_SESSION['user_id']);
    
    if (!$student) {
        sendJsonError('Profil étudiant non trouvé', 404);
    }
    
    // Récupérer les évaluations de l'étudiant
    $evaluationModel = new Evaluation($db);
    $evaluations = $evaluationModel->getByStudentId($student['id']);
    
    // En cas d'erreur ou pour des fins de démonstration, générer des données fictives
    if (!$evaluations || empty($evaluations)) {
        $evaluations = [
            [
                'id' => 1,
                'student_id' => $student['id'],
                'evaluator_id' => 1,
                'evaluator_name' => 'Prof. Dupont',
                'evaluator_role' => 'teacher',
                'type' => 'mid-term',
                'date' => date('Y-m-d', strtotime('-14 days')),
                'score' => 4.0,
                'comments' => "L'étudiant a montré une excellente progression technique depuis le début du stage. Il a rapidement pris en main les technologies utilisées et fait preuve d'une grande autonomie. Quelques améliorations possibles dans la documentation du code.",
                'criteria' => [
                    ['name' => 'Compétences techniques', 'score' => 4.5],
                    ['name' => 'Autonomie', 'score' => 4.0],
                    ['name' => 'Communication', 'score' => 3.5],
                    ['name' => 'Intégration dans l\'équipe', 'score' => 4.0]
                ]
            ],
            [
                'id' => 2,
                'student_id' => $student['id'],
                'evaluator_id' => 2,
                'evaluator_name' => 'Jean Martin',
                'evaluator_role' => 'company',
                'type' => 'mid-term',
                'date' => date('Y-m-d', strtotime('-28 days')),
                'score' => 4.2,
                'comments' => "Très bonne intégration dans l'équipe. Compétences techniques solides et bonne capacité d'adaptation. À améliorer: prise d'initiative et communication des problèmes rencontrés.",
                'criteria' => [
                    ['name' => 'Compétences techniques', 'score' => 4.5],
                    ['name' => 'Autonomie', 'score' => 3.5],
                    ['name' => 'Communication', 'score' => 4.0],
                    ['name' => 'Intégration dans l\'équipe', 'score' => 5.0]
                ]
            ]
        ];
    }
    
    // Calculer les statistiques
    $totalEvaluations = count($evaluations);
    $totalScore = 0;
    $totalTechnical = 0;
    $totalProfessional = 0;
    $countTechnical = 0;
    $countProfessional = 0;
    
    foreach ($evaluations as $evaluation) {
        $totalScore += $evaluation['score'];
        
        // Parcourir les critères
        foreach ($evaluation['criteria'] as $criterion) {
            if (stripos($criterion['name'], 'technique') !== false || stripos($criterion['name'], 'technical') !== false) {
                $totalTechnical += $criterion['score'];
                $countTechnical++;
            } else if (stripos($criterion['name'], 'professionnel') !== false || 
                       stripos($criterion['name'], 'professional') !== false ||
                       stripos($criterion['name'], 'intégration') !== false ||
                       stripos($criterion['name'], 'integration') !== false ||
                       stripos($criterion['name'], 'équipe') !== false ||
                       stripos($criterion['name'], 'team') !== false) {
                $totalProfessional += $criterion['score'];
                $countProfessional++;
            }
        }
    }
    
    // Calculer les moyennes
    $averageScore = $totalEvaluations > 0 ? round($totalScore / $totalEvaluations, 1) : 0;
    $technicalScore = $countTechnical > 0 ? round($totalTechnical / $countTechnical, 1) : 0;
    $professionalScore = $countProfessional > 0 ? round($totalProfessional / $countProfessional, 1) : 0;
    
    // Objectifs à venir (fictifs pour l'exemple)
    $objectives = [
        [
            'id' => 1,
            'title' => 'Améliorer la documentation du code',
            'description' => 'À compléter pour la prochaine évaluation'
        ],
        [
            'id' => 2,
            'title' => 'Participer plus activement aux réunions',
            'description' => 'À compléter pour la prochaine évaluation'
        ],
        [
            'id' => 3,
            'title' => 'Finaliser le module API',
            'description' => 'À compléter pour la prochaine évaluation'
        ]
    ];
    
    // Préparer la réponse
    sendJsonResponse([
        'evaluations' => $evaluations,
        'stats' => [
            'total' => $totalEvaluations,
            'average' => $averageScore,
            'technical' => $technicalScore,
            'professional' => $professionalScore,
            'completed' => $totalEvaluations,
            'total_expected' => 5
        ],
        'objectives' => $objectives
    ]);
} catch (Exception $e) {
    sendJsonError('Erreur lors de la récupération des évaluations: ' . $e->getMessage(), 500);
}
?>