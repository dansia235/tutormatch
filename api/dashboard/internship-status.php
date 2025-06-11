<?php
/**
 * API pour les statuts des stages
 * Endpoint: /api/dashboard/internship-status
 * Méthode: GET
 */

require_once __DIR__ . '/api-init.php';

// Vérifier que l'utilisateur est connecté et a les droits
requireApiAuth();
requireApiRole(['admin', 'coordinator']);

// Fonction pour récupérer des données de test
function getSimulatedInternshipStatus() {
    return [
        'available' => 15,
        'assigned' => 22,
        'completed' => 10,
        'cancelled' => 3
    ];
}

// Préparer les données pour le graphique
$internshipStatus = getSimulatedInternshipStatus();

// Préparer les étiquettes en français
$statusLabels = [
    'available' => 'Disponibles',
    'assigned' => 'Affectés',
    'completed' => 'Terminés',
    'cancelled' => 'Annulés'
];

// Préparer les couleurs
$colors = [
    'available' => 'rgba(40, 167, 69, 0.7)',  // Vert
    'assigned' => 'rgba(0, 123, 255, 0.7)',   // Bleu
    'completed' => 'rgba(23, 162, 184, 0.7)', // Cyan
    'cancelled' => 'rgba(220, 53, 69, 0.7)'   // Rouge
];

$borderColors = [
    'available' => 'rgba(40, 167, 69, 1)',
    'assigned' => 'rgba(0, 123, 255, 1)',
    'completed' => 'rgba(23, 162, 184, 1)',
    'cancelled' => 'rgba(220, 53, 69, 1)'
];

// Formater les données pour le graphique
$chartData = [
    'labels' => array_values(array_map(function($key) use ($statusLabels) {
        return $statusLabels[$key];
    }, array_keys($internshipStatus))),
    'datasets' => [
        [
            'label' => 'Nombre de stages',
            'data' => array_values($internshipStatus),
            'backgroundColor' => array_values(array_map(function($key) use ($colors) {
                return $colors[$key];
            }, array_keys($internshipStatus))),
            'borderColor' => array_values(array_map(function($key) use ($borderColors) {
                return $borderColors[$key];
            }, array_keys($internshipStatus))),
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
    ],
    'scales' => [
        'y' => [
            'beginAtZero' => true,
            'ticks' => [
                'precision' => 0
            ]
        ]
    ]
];

// Préparer la réponse
$response = [
    'title' => 'Stages par statut',
    'type' => 'bar',
    'data' => $chartData,
    'options' => $chartOptions,
    'summary' => [
        'total' => array_sum(array_values($internshipStatus)),
        'available' => $internshipStatus['available'],
        'assigned' => $internshipStatus['assigned'],
        'completed' => $internshipStatus['completed'],
        'cancelled' => $internshipStatus['cancelled']
    ],
    'updated_at' => date('Y-m-d H:i:s')
];

// Envoyer la réponse
sendJsonResponse($response);
?>