<?php
/**
 * API pour les affectations par département
 * Endpoint: /api/dashboard/assignments-by-department
 * Méthode: GET
 */

require_once __DIR__ . '/api-init.php';

// Vérifier que l'utilisateur est connecté et a les droits
requireApiAuth();
requireApiRole(['admin', 'coordinator']);

// Fonction pour récupérer des données simulées
function getSimulatedDepartmentAssignments() {
    return [
        'Informatique' => 18,
        'Électronique' => 12,
        'Mécanique' => 9,
        'Génie civil' => 7,
        'Chimie' => 5
    ];
}

// Préparer les données pour le graphique
$departmentAssignments = getSimulatedDepartmentAssignments();

// Trier par nombre d'affectations décroissant
arsort($departmentAssignments);

// Préparer les couleurs
$backgroundColors = [
    'rgba(54, 162, 235, 0.7)',
    'rgba(255, 99, 132, 0.7)',
    'rgba(255, 205, 86, 0.7)',
    'rgba(75, 192, 192, 0.7)',
    'rgba(153, 102, 255, 0.7)',
    'rgba(255, 159, 64, 0.7)',
    'rgba(201, 203, 207, 0.7)'
];

$borderColors = [
    'rgba(54, 162, 235, 1)',
    'rgba(255, 99, 132, 1)',
    'rgba(255, 205, 86, 1)',
    'rgba(75, 192, 192, 1)',
    'rgba(153, 102, 255, 1)',
    'rgba(255, 159, 64, 1)',
    'rgba(201, 203, 207, 1)'
];

// Assurer que nous avons assez de couleurs
while (count($backgroundColors) < count($departmentAssignments)) {
    $backgroundColors = array_merge($backgroundColors, $backgroundColors);
    $borderColors = array_merge($borderColors, $borderColors);
}

// Formater les données pour le graphique
$chartData = [
    'labels' => array_keys($departmentAssignments),
    'datasets' => [
        [
            'label' => 'Nombre d\'affectations',
            'data' => array_values($departmentAssignments),
            'backgroundColor' => array_slice($backgroundColors, 0, count($departmentAssignments)),
            'borderColor' => array_slice($borderColors, 0, count($departmentAssignments)),
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
            'display' => false
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
    'title' => 'Affectations par département',
    'type' => 'bar',
    'data' => $chartData,
    'options' => $chartOptions,
    'summary' => [
        'total' => array_sum(array_values($departmentAssignments)),
        'departments' => $departmentAssignments
    ],
    'updated_at' => date('Y-m-d H:i:s')
];

// Envoyer la réponse
sendJsonResponse($response);
?>