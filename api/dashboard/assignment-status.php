<?php
/**
 * API pour les statuts d'affectation
 * Endpoint: /api/dashboard/assignment-status
 * Méthode: GET
 */

require_once __DIR__ . '/api-init.php';

// Vérifier que l'utilisateur est connecté et a les droits
requireApiAuth();
requireApiRole(['admin', 'coordinator']);

// Fonction pour récupérer des données de test
function getSimulatedAssignmentStatus() {
    return [
        'pending' => 12,
        'confirmed' => 25,
        'rejected' => 4,
        'completed' => 18
    ];
}

// Préparer les données pour le graphique
$assignmentStatus = getSimulatedAssignmentStatus();

// Préparer les étiquettes en français
$statusLabels = [
    'pending' => 'En attente',
    'confirmed' => 'Confirmées',
    'rejected' => 'Rejetées',
    'completed' => 'Terminées'
];

// Préparer les couleurs
$colors = [
    'pending' => 'rgba(255, 193, 7, 0.7)',  // Jaune
    'confirmed' => 'rgba(40, 167, 69, 0.7)', // Vert
    'rejected' => 'rgba(220, 53, 69, 0.7)',  // Rouge
    'completed' => 'rgba(23, 162, 184, 0.7)' // Bleu
];

$borderColors = [
    'pending' => 'rgba(255, 193, 7, 1)',
    'confirmed' => 'rgba(40, 167, 69, 1)',
    'rejected' => 'rgba(220, 53, 69, 1)',
    'completed' => 'rgba(23, 162, 184, 1)'
];

// Formater les données pour le graphique
$chartData = [
    'labels' => array_values(array_map(function($key) use ($statusLabels) {
        return $statusLabels[$key];
    }, array_keys($assignmentStatus))),
    'datasets' => [
        [
            'data' => array_values($assignmentStatus),
            'backgroundColor' => array_values(array_map(function($key) use ($colors) {
                return $colors[$key];
            }, array_keys($assignmentStatus))),
            'borderColor' => array_values(array_map(function($key) use ($borderColors) {
                return $borderColors[$key];
            }, array_keys($assignmentStatus))),
            'borderWidth' => 1
        ]
    ]
];

// Préparer les options du graphique
$chartOptions = [
    'responsive' => true,
    'maintainAspectRatio' => false,
    'plugins' => [
        'legend' => [
            'position' => 'bottom',
            'labels' => [
                'font' => [
                    'size' => 12
                ],
                'padding' => 20
            ]
        ],
        'tooltip' => [
            'displayColors' => true
        ]
    ]
];

// Préparer la réponse
$response = [
    'title' => 'Statut des affectations',
    'type' => 'pie',
    'data' => $chartData,
    'options' => $chartOptions,
    'summary' => [
        'total' => array_sum(array_values($assignmentStatus)),
        'pending' => $assignmentStatus['pending'],
        'confirmed' => $assignmentStatus['confirmed'],
        'rejected' => $assignmentStatus['rejected'],
        'completed' => $assignmentStatus['completed']
    ],
    'updated_at' => date('Y-m-d H:i:s')
];

// Envoyer la réponse
sendJsonResponse($response);
?>