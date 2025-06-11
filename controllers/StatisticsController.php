<?php
/**
 * Contrôleur pour la génération des statistiques
 */
class StatisticsController {
    private $db;
    private $userModel;
    private $studentModel;
    private $teacherModel;
    private $internshipModel;
    private $assignmentModel;
    private $documentModel;
    
    /**
     * Constructeur
     * @param PDO $db Instance de connexion à la base de données
     */
    public function __construct($db) {
        $this->db = $db;
        $this->userModel = new User($db);
        $this->studentModel = new Student($db);
        $this->teacherModel = new Teacher($db);
        $this->internshipModel = new Internship($db);
        $this->assignmentModel = new Assignment($db);
        $this->documentModel = new Document($db);
    }
    
    /**
     * Récupère toutes les statistiques du tableau de bord
     * @return array Statistiques
     */
    public function getDashboardStats() {
        $stats = [];
        
        // Statistiques des utilisateurs
        $stats['users'] = $this->userModel->countByRole();
        
        // Statistiques des étudiants
        $stats['students'] = $this->studentModel->countByStatus();
        
        // Statistiques des stages
        $stats['internships'] = $this->internshipModel->countByStatus();
        
        // Statistiques des affectations
        $stats['assignments'] = $this->assignmentModel->getStats();
        
        // Statistiques des documents
        $stats['documents'] = $this->documentModel->countByCategory();
        
        // Charge de travail des tuteurs
        $stats['workload'] = $this->teacherModel->getWorkloadStats();
        
        // Affectations récentes
        $stats['recentAssignments'] = $this->assignmentModel->getRecent(5);
        
        // Calculer d'autres statistiques dérivées
        $stats['totalStudents'] = array_sum($stats['students']);
        $stats['totalTeachers'] = isset($stats['users']['teacher']) ? $stats['users']['teacher'] : 0;
        $stats['totalInternships'] = array_sum($stats['internships']);
        
        // Calculer le taux d'affectation
        $totalPendingConfirmed = isset($stats['assignments']['byStatus']['pending']) ? $stats['assignments']['byStatus']['pending'] : 0;
        $totalPendingConfirmed += isset($stats['assignments']['byStatus']['confirmed']) ? $stats['assignments']['byStatus']['confirmed'] : 0;
        $stats['assignmentRate'] = $stats['totalStudents'] > 0 ? round(($totalPendingConfirmed / $stats['totalStudents']) * 100) : 0;
        
        return $stats;
    }
    
    /**
     * Génère les données pour les graphiques
     * @return array Données des graphiques
     */
    public function generateChartData() {
        $charts = [];
        
        // Récupérer les statistiques
        $stats = $this->getDashboardStats();
        
        // 1. Graphique des affectations par statut (doughnut)
        $assignmentStatus = isset($stats['assignments']['byStatus']) ? $stats['assignments']['byStatus'] : [];
        $statusLabels = [
            'pending' => 'En attente',
            'confirmed' => 'Confirmé',
            'rejected' => 'Rejeté',
            'completed' => 'Terminé'
        ];
        
        $charts['assignmentStatus'] = [
            'type' => 'doughnut',
            'data' => [
                'labels' => array_map(function($key) use ($statusLabels) {
                    return $statusLabels[$key] ?? $key;
                }, array_keys($assignmentStatus)),
                'datasets' => [
                    [
                        'data' => array_values($assignmentStatus),
                        'backgroundColor' => [
                            'rgba(255, 193, 7, 0.8)',  // Jaune pour en attente
                            'rgba(40, 167, 69, 0.8)',  // Vert pour confirmé
                            'rgba(220, 53, 69, 0.8)',  // Rouge pour rejeté
                            'rgba(23, 162, 184, 0.8)'  // Bleu pour terminé
                        ],
                        'borderColor' => [
                            'rgba(255, 193, 7, 1)',
                            'rgba(40, 167, 69, 1)',
                            'rgba(220, 53, 69, 1)',
                            'rgba(23, 162, 184, 1)'
                        ],
                        'borderWidth' => 1
                    ]
                ]
            ],
            'options' => [
                'responsive' => true,
                'plugins' => [
                    'legend' => [
                        'position' => 'bottom'
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Affectations par statut'
                    ]
                ]
            ]
        ];
        
        // 2. Graphique des stages par statut (bar)
        $internshipStatus = $stats['internships'] ?? [];
        $internshipLabels = [
            'available' => 'Disponible',
            'assigned' => 'Affecté',
            'completed' => 'Terminé',
            'cancelled' => 'Annulé'
        ];
        
        $charts['internshipStatus'] = [
            'type' => 'bar',
            'data' => [
                'labels' => array_map(function($key) use ($internshipLabels) {
                    return $internshipLabels[$key] ?? $key;
                }, array_keys($internshipStatus)),
                'datasets' => [
                    [
                        'label' => 'Nombre de stages',
                        'data' => array_values($internshipStatus),
                        'backgroundColor' => 'rgba(54, 162, 235, 0.8)',
                        'borderColor' => 'rgba(54, 162, 235, 1)',
                        'borderWidth' => 1
                    ]
                ]
            ],
            'options' => [
                'responsive' => true,
                'plugins' => [
                    'legend' => [
                        'display' => false
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Stages par statut'
                    ]
                ]
            ]
        ];
        
        // 3. Graphique de charge de travail des tuteurs (horizontal bar)
        $workload = $stats['workload'] ?? [];
        $tutorLabels = [];
        $workloadData = [];
        $maxStudents = [];
        
        foreach ($workload as $tutor) {
            $tutorLabels[] = $tutor['first_name'] . ' ' . $tutor['last_name'];
            $workloadData[] = $tutor['current_students'];
            $maxStudents[] = $tutor['max_students'];
        }
        
        $charts['tutorWorkload'] = [
            'type' => 'bar',
            'data' => [
                'labels' => $tutorLabels,
                'datasets' => [
                    [
                        'label' => 'Étudiants assignés',
                        'data' => $workloadData,
                        'backgroundColor' => 'rgba(40, 167, 69, 0.8)',
                        'borderColor' => 'rgba(40, 167, 69, 1)',
                        'borderWidth' => 1
                    ],
                    [
                        'label' => 'Capacité maximale',
                        'data' => $maxStudents,
                        'backgroundColor' => 'rgba(220, 53, 69, 0.3)',
                        'borderColor' => 'rgba(220, 53, 69, 1)',
                        'borderWidth' => 1,
                        'type' => 'line'
                    ]
                ]
            ],
            'options' => [
                'indexAxis' => 'y',
                'responsive' => true,
                'plugins' => [
                    'legend' => [
                        'position' => 'bottom'
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Charge de travail des tuteurs'
                    ]
                ]
            ]
        ];
        
        // 4. Graphique des documents par catégorie (pie)
        $docCategories = $stats['documents'] ?? [];
        $categoryLabels = [
            'rapport' => 'Rapports',
            'evaluation' => 'Évaluations',
            'convention' => 'Conventions',
            'cv' => 'CV',
            'autre' => 'Autres'
        ];
        
        $charts['documentCategories'] = [
            'type' => 'pie',
            'data' => [
                'labels' => array_map(function($key) use ($categoryLabels) {
                    return $categoryLabels[$key] ?? $key;
                }, array_keys($docCategories)),
                'datasets' => [
                    [
                        'data' => array_values($docCategories),
                        'backgroundColor' => [
                            'rgba(255, 99, 132, 0.8)',
                            'rgba(54, 162, 235, 0.8)',
                            'rgba(255, 206, 86, 0.8)',
                            'rgba(75, 192, 192, 0.8)',
                            'rgba(153, 102, 255, 0.8)'
                        ],
                        'borderColor' => [
                            'rgba(255, 99, 132, 1)',
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 206, 86, 1)',
                            'rgba(75, 192, 192, 1)',
                            'rgba(153, 102, 255, 1)'
                        ],
                        'borderWidth' => 1
                    ]
                ]
            ],
            'options' => [
                'responsive' => true,
                'plugins' => [
                    'legend' => [
                        'position' => 'bottom'
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Documents par catégorie'
                    ]
                ]
            ]
        ];
        
        // 5. Graphique d'affectations par département (bar)
        $deptAssignments = isset($stats['assignments']['byDepartment']) ? $stats['assignments']['byDepartment'] : [];
        
        $charts['assignmentsByDepartment'] = [
            'type' => 'bar',
            'data' => [
                'labels' => array_keys($deptAssignments),
                'datasets' => [
                    [
                        'label' => 'Affectations',
                        'data' => array_values($deptAssignments),
                        'backgroundColor' => 'rgba(123, 104, 238, 0.8)',
                        'borderColor' => 'rgba(123, 104, 238, 1)',
                        'borderWidth' => 1
                    ]
                ]
            ],
            'options' => [
                'responsive' => true,
                'plugins' => [
                    'legend' => [
                        'display' => false
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Affectations par département'
                    ]
                ]
            ]
        ];
        
        return $charts;
    }
}