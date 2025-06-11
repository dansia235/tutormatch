<?php
/**
 * Rapports d'évaluations
 * GET /api/evaluations/reports
 */

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Méthode non autorisée', 405);
}

// Vérifier les permissions (seuls les administrateurs et coordinateurs peuvent accéder aux rapports)
$currentUserRole = $_SESSION['user_role'];
if ($currentUserRole !== 'admin' && $currentUserRole !== 'coordinator') {
    sendError('Accès non autorisé', 403);
}

// Récupérer les paramètres de requête
$type = isset($_GET['type']) ? $_GET['type'] : 'all';
$periodStart = isset($_GET['period_start']) ? $_GET['period_start'] : null;
$periodEnd = isset($_GET['period_end']) ? $_GET['period_end'] : null;
$teacherId = isset($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : null;
$programId = isset($_GET['program_id']) ? (int)$_GET['program_id'] : null;

// Initialiser les modèles
$evaluationModel = new Evaluation($db);
$assignmentModel = new Assignment($db);
$studentModel = new Student($db);
$teacherModel = new Teacher($db);

// Construire les options de requête pour les rapports
$options = [];

// Filtrer par type d'évaluation
if ($type !== 'all') {
    $options['type'] = $type;
}

// Filtrer par période
if ($periodStart) {
    $options['period_start'] = $periodStart;
}
if ($periodEnd) {
    $options['period_end'] = $periodEnd;
}

// Filtrer par tuteur
if ($teacherId) {
    $options['teacher_id'] = $teacherId;
}

// Filtrer par programme d'études
if ($programId) {
    $options['program_id'] = $programId;
}

// Récupérer les statistiques d'évaluation
$statistics = $evaluationModel->getStatistics($options);

// Préparer les données de rapport
$report = [
    'summary' => [
        'total_evaluations' => $statistics['total_evaluations'],
        'average_rating' => $statistics['average_rating'],
        'teacher_evaluations' => $statistics['teacher_evaluations'],
        'student_evaluations' => $statistics['student_evaluations']
    ],
    'ratings_distribution' => $statistics['ratings_distribution'],
    'by_criteria' => $statistics['by_criteria']
];

// Ajouter des données par programme si disponibles
if (isset($statistics['by_program'])) {
    $report['by_program'] = $statistics['by_program'];
}

// Ajouter des données par tuteur si disponibles
if (isset($statistics['by_teacher'])) {
    $report['by_teacher'] = $statistics['by_teacher'];
}

// Ajouter des données par période si disponibles
if (isset($statistics['by_period'])) {
    $report['by_period'] = $statistics['by_period'];
}

// Ajouter des recommandations basées sur les données
$recommendations = [];

if ($statistics['total_evaluations'] > 0) {
    // Identifier les critères à améliorer (note moyenne < 3.5)
    $lowRatedCriteria = [];
    foreach ($statistics['by_criteria'] as $criterion) {
        if ($criterion['average_rating'] < 3.5) {
            $lowRatedCriteria[] = $criterion['name'];
        }
    }
    
    if (!empty($lowRatedCriteria)) {
        $recommendations[] = [
            'type' => 'improvement_areas',
            'description' => 'Domaines nécessitant une amélioration',
            'items' => $lowRatedCriteria
        ];
    }
    
    // Identifier les tuteurs ayant besoin de soutien
    if (isset($statistics['by_teacher'])) {
        $lowRatedTeachers = [];
        foreach ($statistics['by_teacher'] as $teacher) {
            if ($teacher['average_rating'] < 3.0) {
                $lowRatedTeachers[] = [
                    'id' => $teacher['id'],
                    'name' => $teacher['name'],
                    'average_rating' => $teacher['average_rating']
                ];
            }
        }
        
        if (!empty($lowRatedTeachers)) {
            $recommendations[] = [
                'type' => 'teacher_support',
                'description' => 'Tuteurs nécessitant un soutien supplémentaire',
                'items' => $lowRatedTeachers
            ];
        }
    }
}

$report['recommendations'] = $recommendations;

// Envoyer la réponse
sendJsonResponse([
    'data' => $report
]);