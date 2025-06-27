<?php
/**
 * API pour les statistiques des évaluations
 * GET /api/evaluations/stats
 */

require_once __DIR__ . '/../../includes/init.php';

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

// Vérifier les permissions
requireRole(['admin', 'coordinator']);

try {
    // Récupérer les statistiques globales détaillées avec normalisation des scores
    $statsQuery = "
        SELECT 
            COUNT(*) as total_evaluations,
            COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_evaluations,
            COUNT(CASE WHEN status = 'draft' THEN 1 END) as draft_evaluations,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_evaluations,
            ROUND(AVG(CASE WHEN score IS NOT NULL THEN 
                CASE WHEN score > 5 THEN score / 4 ELSE score END 
            END), 1) as average_score,
            COUNT(CASE WHEN type = 'mid_term' THEN 1 END) as mid_term_count,
            COUNT(CASE WHEN type = 'final' THEN 1 END) as final_count,
            COUNT(CASE WHEN type = 'student' THEN 1 END) as self_evaluations,
            COUNT(CASE WHEN type = 'supervisor' THEN 1 END) as supervisor_evaluations,
            COUNT(CASE WHEN type = 'teacher' THEN 1 END) as teacher_evaluations,
            COUNT(CASE WHEN submission_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as recent_count,
            COUNT(CASE WHEN submission_date >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as weekly_count,
            COUNT(CASE WHEN (CASE WHEN score > 5 THEN score / 4 ELSE score END) >= 4.0 THEN 1 END) as excellent_count,
            COUNT(CASE WHEN (CASE WHEN score > 5 THEN score / 4 ELSE score END) >= 3.0 AND (CASE WHEN score > 5 THEN score / 4 ELSE score END) < 4.0 THEN 1 END) as good_count,
            COUNT(CASE WHEN (CASE WHEN score > 5 THEN score / 4 ELSE score END) >= 2.0 AND (CASE WHEN score > 5 THEN score / 4 ELSE score END) < 3.0 THEN 1 END) as average_count,
            COUNT(CASE WHEN (CASE WHEN score > 5 THEN score / 4 ELSE score END) < 2.0 AND score IS NOT NULL THEN 1 END) as poor_count
        FROM evaluations
    ";
    
    $stmt = $db->prepare($statsQuery);
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Préparer les données des cartes statistiques
    $statCards = [
        [
            'title' => 'Évaluations Totales',
            'value' => number_format($stats['total_evaluations'] ?? 0),
            'change' => ($stats['weekly_count'] ?? 0) . ' cette semaine',
            'changeType' => 'info',
            'link' => '#',
            'linkText' => 'Toutes les évaluations'
        ],
        [
            'title' => 'Score Moyen',
            'value' => ($stats['average_score'] ?? 0) . '/5',
            'change' => 'Sur ' . ($stats['completed_evaluations'] ?? 0) . ' complétées',
            'changeType' => $stats['average_score'] >= 4.0 ? 'positive' : ($stats['average_score'] >= 3.0 ? 'warning' : 'negative'),
            'link' => '#',
            'linkText' => 'Évaluations notées'
        ],
        [
            'title' => 'Complétées',
            'value' => number_format($stats['completed_evaluations'] ?? 0),
            'change' => ($stats['draft_evaluations'] ?? 0) . ' brouillons',
            'changeType' => 'positive',
            'link' => '?status=completed',
            'linkText' => 'Voir les complétées'
        ],
        [
            'title' => 'Ce Mois-ci',
            'value' => number_format($stats['recent_count'] ?? 0),
            'change' => ($stats['weekly_count'] ?? 0) . ' cette semaine',
            'changeType' => 'info',
            'link' => '#',
            'linkText' => 'Évaluations récentes'
        ]
    ];
    
    // Retourner les données
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data' => [
            'stats' => $stats,
            'cards' => $statCards
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de la récupération des statistiques: ' . $e->getMessage()
    ]);
}
?>