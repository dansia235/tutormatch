<?php
/**
 * API pour la charge de travail des tuteurs
 * Endpoint: /api/dashboard/tutor-workload
 * Méthode: GET
 */

require_once __DIR__ . '/api-init.php';

// Vérifier que l'utilisateur est connecté et a les droits
requireApiAuth();
requireApiRole(['admin', 'coordinator']);

// Fonction pour récupérer des données simulées
function getSimulatedTutorWorkload() {
    return [
        [
            'id' => 1,
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'department' => 'Informatique',
            'current_students' => 5,
            'max_students' => 6,
            'workload_percentage' => 83
        ],
        [
            'id' => 2,
            'first_name' => 'Marie',
            'last_name' => 'Martin',
            'department' => 'Électronique',
            'current_students' => 4,
            'max_students' => 5,
            'workload_percentage' => 80
        ],
        [
            'id' => 3,
            'first_name' => 'Pierre',
            'last_name' => 'Laurent',
            'department' => 'Mécanique',
            'current_students' => 3,
            'max_students' => 4,
            'workload_percentage' => 75
        ],
        [
            'id' => 4,
            'first_name' => 'Sophie',
            'last_name' => 'Bernard',
            'department' => 'Génie civil',
            'current_students' => 3,
            'max_students' => 6,
            'workload_percentage' => 50
        ],
        [
            'id' => 5,
            'first_name' => 'Thomas',
            'last_name' => 'Petit',
            'department' => 'Chimie',
            'current_students' => 2,
            'max_students' => 4,
            'workload_percentage' => 50
        ]
    ];
}

// Récupérer les statistiques de charge de travail
$workloadStats = getSimulatedTutorWorkload();

// Limiter aux 5 premiers tuteurs pour la lisibilité
$topTutors = array_slice($workloadStats, 0, 5);

// Préparer les données pour le graphique
$labels = [];
$currentStudents = [];
$maxStudents = [];
$workloadPercentage = [];

foreach ($topTutors as $tutor) {
    $labels[] = $tutor['first_name'] . ' ' . $tutor['last_name'];
    $currentStudents[] = $tutor['current_students'];
    $maxStudents[] = $tutor['max_students'];
    $workloadPercentage[] = $tutor['workload_percentage'];
}

// Formater les données pour le graphique
$chartData = [
    'labels' => $labels,
    'datasets' => [
        [
            'label' => 'Étudiants assignés',
            'data' => $currentStudents,
            'backgroundColor' => 'rgba(40, 167, 69, 0.7)',
            'borderColor' => 'rgba(40, 167, 69, 1)',
            'borderWidth' => 1
        ],
        [
            'label' => 'Capacité maximale',
            'data' => $maxStudents,
            'backgroundColor' => 'rgba(220, 53, 69, 0.2)',
            'borderColor' => 'rgba(220, 53, 69, 1)',
            'borderWidth' => 1,
            'type' => 'line'
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
                'padding' => 10
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
    'title' => 'Charge de travail des tuteurs',
    'type' => 'bar',
    'data' => $chartData,
    'options' => $chartOptions,
    'summary' => [
        'tutors' => array_map(function($tutor) {
            return [
                'name' => $tutor['first_name'] . ' ' . $tutor['last_name'],
                'department' => $tutor['department'],
                'current_students' => $tutor['current_students'],
                'max_students' => $tutor['max_students'],
                'workload_percentage' => $tutor['workload_percentage']
            ];
        }, $workloadStats)
    ],
    'updated_at' => date('Y-m-d H:i:s')
];

// Envoyer la réponse
sendJsonResponse($response);
?>