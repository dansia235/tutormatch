<?php
/**
 * API pour les métriques système du tableau de bord
 * Endpoint: /api/dashboard/system-metrics
 * Méthode: GET
 */

require_once __DIR__ . '/api-init.php';

// Vérifier que l'utilisateur est connecté et a les droits
requireApiAuth();
requireApiRole(['admin', 'coordinator']);

// Fonction pour récupérer des statistiques de test
function getSimulatedMetrics() {
    // Données simulées pour assurer un retour fonctionnel
    return [
        'students_without_tutor' => 8,
        'internships_available' => 12,
        'pending_documents' => 5,
        'pending_evaluations' => 7,
        'active_users_today' => 15,
        'upcoming_meetings' => 3
    ];
}

// Préparer la réponse
$response = [
    'status' => 'success',
    'data' => [
        'metrics' => getSimulatedMetrics(),
        'updated_at' => date('Y-m-d H:i:s')
    ]
];

// Envoyer la réponse
sendJsonResponse($response);
?>