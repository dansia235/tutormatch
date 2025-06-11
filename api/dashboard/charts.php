<?php
/**
 * API pour les données des graphiques du tableau de bord
 * Endpoint: /api/dashboard/charts
 * Méthode: GET
 */

require_once __DIR__ . '/../utils.php';
require_once __DIR__ . '/../../controllers/StatisticsController.php';

// Vérifier que l'utilisateur est connecté et a les droits
requireApiAuth();
requireApiRole(['admin', 'coordinator']);

// Récupérer le type de graphique demandé (optionnel)
$chartType = isset($_GET['type']) ? $_GET['type'] : null;

// Récupérer la période (optionnelle)
$period = isset($_GET['period']) ? $_GET['period'] : 'month'; // Options: day, week, month, year

// Instancier le contrôleur de statistiques
$statsController = new StatisticsController($db);

// Récupérer les données pour les graphiques
$chartData = $statsController->generateChartData($period);

// Préparer la réponse en fonction du type de graphique demandé
$response = [];

if ($chartType) {
    // Retourner seulement les données pour un type de graphique spécifique
    switch ($chartType) {
        case 'assignment_status':
            $response = [
                'title' => 'Statut des affectations',
                'type' => 'pie',
                'labels' => ['Confirmées', 'En attente', 'Rejetées', 'Terminées'],
                'datasets' => [
                    [
                        'data' => [
                            $chartData['assignmentStatus']['confirmed'] ?? 0,
                            $chartData['assignmentStatus']['pending'] ?? 0,
                            $chartData['assignmentStatus']['rejected'] ?? 0,
                            $chartData['assignmentStatus']['completed'] ?? 0
                        ],
                        'backgroundColor' => [
                            'rgba(40, 167, 69, 0.7)',    // Vert (confirmées)
                            'rgba(255, 193, 7, 0.7)',    // Jaune (en attente)
                            'rgba(220, 53, 69, 0.7)',    // Rouge (rejetées)
                            'rgba(23, 162, 184, 0.7)'    // Bleu (terminées)
                        ],
                        'borderColor' => [
                            'rgba(40, 167, 69, 1)',
                            'rgba(255, 193, 7, 1)',
                            'rgba(220, 53, 69, 1)',
                            'rgba(23, 162, 184, 1)'
                        ],
                        'borderWidth' => 1
                    ]
                ]
            ];
            break;
            
        case 'tutor_workload':
            // Pour les tuteurs avec la plus grande charge de travail
            $labels = [];
            $data = [];
            $maxWorkload = [];
            
            if (isset($chartData['tutorWorkload']) && is_array($chartData['tutorWorkload'])) {
                // Prendre les 5 premiers tuteurs
                $topTutors = array_slice($chartData['tutorWorkload'], 0, 5);
                
                foreach ($topTutors as $tutor) {
                    $labels[] = $tutor['name'];
                    $data[] = $tutor['current_students'];
                    $maxWorkload[] = $tutor['max_students'];
                }
            }
            
            $response = [
                'title' => 'Charge de travail des tuteurs',
                'type' => 'bar',
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'Étudiants actuels',
                        'data' => $data,
                        'backgroundColor' => 'rgba(0, 123, 255, 0.7)',
                        'borderColor' => 'rgba(0, 123, 255, 1)',
                        'borderWidth' => 1
                    ],
                    [
                        'label' => 'Capacité maximale',
                        'data' => $maxWorkload,
                        'backgroundColor' => 'rgba(220, 53, 69, 0.5)',
                        'borderColor' => 'rgba(220, 53, 69, 1)',
                        'borderWidth' => 1,
                        'type' => 'line'
                    ]
                ]
            ];
            break;
            
        case 'internship_type':
            $response = [
                'title' => 'Types de stages',
                'type' => 'doughnut',
                'labels' => array_keys($chartData['internshipTypes'] ?? []),
                'datasets' => [
                    [
                        'data' => array_values($chartData['internshipTypes'] ?? []),
                        'backgroundColor' => [
                            'rgba(0, 123, 255, 0.7)',
                            'rgba(40, 167, 69, 0.7)',
                            'rgba(255, 193, 7, 0.7)',
                            'rgba(220, 53, 69, 0.7)',
                            'rgba(111, 66, 193, 0.7)',
                            'rgba(23, 162, 184, 0.7)'
                        ],
                        'borderColor' => [
                            'rgba(0, 123, 255, 1)',
                            'rgba(40, 167, 69, 1)',
                            'rgba(255, 193, 7, 1)',
                            'rgba(220, 53, 69, 1)',
                            'rgba(111, 66, 193, 1)',
                            'rgba(23, 162, 184, 1)'
                        ],
                        'borderWidth' => 1
                    ]
                ]
            ];
            break;
            
        case 'document_submission':
            // Données pour le graphique de soumission de documents au fil du temps
            $months = [];
            $counts = [];
            
            if (isset($chartData['documentSubmission']) && is_array($chartData['documentSubmission'])) {
                foreach ($chartData['documentSubmission'] as $month => $count) {
                    $months[] = $month;
                    $counts[] = $count;
                }
            }
            
            $response = [
                'title' => 'Soumission de documents',
                'type' => 'line',
                'labels' => $months,
                'datasets' => [
                    [
                        'label' => 'Documents soumis',
                        'data' => $counts,
                        'backgroundColor' => 'rgba(0, 123, 255, 0.2)',
                        'borderColor' => 'rgba(0, 123, 255, 1)',
                        'borderWidth' => 2,
                        'tension' => 0.4,
                        'fill' => true
                    ]
                ]
            ];
            break;
            
        case 'student_distribution':
            $response = [
                'title' => 'Répartition des étudiants par département',
                'type' => 'pie',
                'labels' => array_keys($chartData['studentDepartments'] ?? []),
                'datasets' => [
                    [
                        'data' => array_values($chartData['studentDepartments'] ?? []),
                        'backgroundColor' => [
                            'rgba(0, 123, 255, 0.7)',
                            'rgba(40, 167, 69, 0.7)',
                            'rgba(255, 193, 7, 0.7)',
                            'rgba(220, 53, 69, 0.7)',
                            'rgba(111, 66, 193, 0.7)',
                            'rgba(23, 162, 184, 0.7)'
                        ],
                        'borderColor' => [
                            'rgba(0, 123, 255, 1)',
                            'rgba(40, 167, 69, 1)',
                            'rgba(255, 193, 7, 1)',
                            'rgba(220, 53, 69, 1)',
                            'rgba(111, 66, 193, 1)',
                            'rgba(23, 162, 184, 1)'
                        ],
                        'borderWidth' => 1
                    ]
                ]
            ];
            break;
    }
} else {
    // Retourner toutes les données des graphiques
    $response = [
        'assignment_status' => [
            'title' => 'Statut des affectations',
            'type' => 'pie',
            'labels' => ['Confirmées', 'En attente', 'Rejetées', 'Terminées'],
            'datasets' => [
                [
                    'data' => [
                        $chartData['assignmentStatus']['confirmed'] ?? 0,
                        $chartData['assignmentStatus']['pending'] ?? 0,
                        $chartData['assignmentStatus']['rejected'] ?? 0,
                        $chartData['assignmentStatus']['completed'] ?? 0
                    ],
                    'backgroundColor' => [
                        'rgba(40, 167, 69, 0.7)',
                        'rgba(255, 193, 7, 0.7)',
                        'rgba(220, 53, 69, 0.7)',
                        'rgba(23, 162, 184, 0.7)'
                    ]
                ]
            ]
        ],
        'tutor_workload' => [
            'title' => 'Charge de travail des tuteurs',
            'type' => 'bar',
            'labels' => array_column($chartData['tutorWorkload'] ?? [], 'name'),
            'datasets' => [
                [
                    'label' => 'Étudiants actuels',
                    'data' => array_column($chartData['tutorWorkload'] ?? [], 'current_students'),
                    'backgroundColor' => 'rgba(0, 123, 255, 0.7)'
                ],
                [
                    'label' => 'Capacité maximale',
                    'data' => array_column($chartData['tutorWorkload'] ?? [], 'max_students'),
                    'backgroundColor' => 'rgba(220, 53, 69, 0.5)',
                    'type' => 'line'
                ]
            ]
        ],
        'internship_type' => [
            'title' => 'Types de stages',
            'type' => 'doughnut',
            'labels' => array_keys($chartData['internshipTypes'] ?? []),
            'datasets' => [
                [
                    'data' => array_values($chartData['internshipTypes'] ?? [])
                ]
            ]
        ],
        'document_submission' => [
            'title' => 'Soumission de documents',
            'type' => 'line',
            'labels' => array_keys($chartData['documentSubmission'] ?? []),
            'datasets' => [
                [
                    'label' => 'Documents soumis',
                    'data' => array_values($chartData['documentSubmission'] ?? [])
                ]
            ]
        ],
        'student_distribution' => [
            'title' => 'Répartition des étudiants par département',
            'type' => 'pie',
            'labels' => array_keys($chartData['studentDepartments'] ?? []),
            'datasets' => [
                [
                    'data' => array_values($chartData['studentDepartments'] ?? [])
                ]
            ]
        ]
    ];
}

// Ajouter des métadonnées
$response = array_merge([
    'period' => $period,
    'updated_at' => date('Y-m-d H:i:s')
], is_array($response) ? $response : ['data' => $response]);

// Envoyer la réponse
sendJsonResponse($response);
?>