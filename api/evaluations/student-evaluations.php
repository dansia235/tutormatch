<?php
/**
 * API pour récupérer les évaluations d'un étudiant
 * Endpoint: /api/evaluations/student-evaluations
 * Méthode: GET
 */

require_once __DIR__ . '/../utils.php';
require_once __DIR__ . '/document-adapter.php';

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    sendJsonResponse([
        'error' => true,
        'message' => 'Non autorisé - Utilisateur non connecté'
    ], 401);
    exit;
}

// Vérifier que l'utilisateur est un étudiant
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'student') {
    sendJsonResponse([
        'error' => true,
        'message' => 'Accès non autorisé - Rôle étudiant requis'
    ], 403);
    exit;
}

try {
    // Récupérer l'ID de l'étudiant
    $studentModel = new Student($db);
    $student = $studentModel->getByUserId($_SESSION['user_id']);
    
    if (!$student) {
        sendJsonResponse([
            'error' => true,
            'message' => 'Profil étudiant non trouvé'
        ], 404);
        exit;
    }
    
    // Récupérer les documents d'évaluation de l'étudiant
    $documents = $studentModel->getDocuments($student['id']);
    
    // Journaliser le nombre de documents trouvés
    error_log("API évaluations: " . count($documents) . " documents trouvés pour l'étudiant ID: " . $student['id']);
    
    // Filtrer pour ne garder que les documents de type évaluation
    $evaluationDocuments = [];
    foreach ($documents as $doc) {
        // Journaliser les types de documents
        error_log("Document ID: " . $doc['id'] . ", Type: " . ($doc['type'] ?? 'non défini'));
        
        if (isset($doc['type']) && (
            $doc['type'] === 'evaluation' || 
            $doc['type'] === 'self_evaluation' || 
            $doc['type'] === 'mid_term' || 
            $doc['type'] === 'final')
        ) {
            $evaluationDocuments[] = $doc;
            error_log("Document d'évaluation ajouté: " . $doc['id']);
        }
    }
    
    // Convertir les documents en format d'évaluation
    $evaluations = [];
    foreach ($evaluationDocuments as $doc) {
        $evaluations[] = convertDocumentToEvaluation($doc);
    }
    
    // Si aucune évaluation, générer des exemples fictifs pour le développement
    if (empty($evaluations) && defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE) {
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
        if (isset($evaluation['criteria']) && is_array($evaluation['criteria'])) {
            foreach ($evaluation['criteria'] as $criterion) {
                if (!isset($criterion['name']) || !isset($criterion['score'])) {
                    continue;
                }
                
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
    error_log("Erreur API évaluations: " . $e->getMessage());
    sendJsonResponse([
        'error' => true,
        'message' => 'Erreur lors de la récupération des évaluations: ' . $e->getMessage()
    ], 500);
}
?>