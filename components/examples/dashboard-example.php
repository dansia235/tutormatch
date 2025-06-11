<?php
/**
 * Example of dashboard components usage
 */

// Sample data for stats
$stats = [
    [
        'title' => 'Étudiants',
        'value' => '124',
        'change' => '+12% depuis le mois dernier',
        'changeType' => 'positive',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>',
        'link' => '/tutoring/views/admin/students.php'
    ],
    [
        'title' => 'Tuteurs',
        'value' => '32',
        'change' => '+4% depuis le mois dernier',
        'changeType' => 'positive',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>',
        'link' => '/tutoring/views/admin/tutors.php'
    ],
    [
        'title' => 'Stages',
        'value' => '78',
        'change' => '-3% depuis le mois dernier',
        'changeType' => 'negative',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>',
        'link' => '/tutoring/views/admin/internships.php'
    ],
    [
        'title' => 'Taux d\'affectation',
        'value' => '89%',
        'change' => '+5% depuis le mois dernier',
        'changeType' => 'positive',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>',
        'link' => '/tutoring/views/admin/assignments.php'
    ]
];

// Sample data for charts
$chartData = [
    'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
    'datasets' => [
        [
            'label' => 'Étudiants',
            'data' => [65, 70, 80, 81, 90, 95, 100, 110, 115, 120, 122, 124],
            'backgroundColor' => 'rgba(59, 130, 246, 0.2)',
            'borderColor' => 'rgba(59, 130, 246, 1)',
            'borderWidth' => 2,
            'tension' => 0.4
        ],
        [
            'label' => 'Tuteurs',
            'data' => [25, 26, 26, 27, 28, 28, 29, 30, 30, 31, 32, 32],
            'backgroundColor' => 'rgba(16, 185, 129, 0.2)',
            'borderColor' => 'rgba(16, 185, 129, 1)',
            'borderWidth' => 2,
            'tension' => 0.4
        ],
        [
            'label' => 'Stages',
            'data' => [40, 45, 50, 55, 60, 65, 70, 75, 80, 82, 80, 78],
            'backgroundColor' => 'rgba(245, 158, 11, 0.2)',
            'borderColor' => 'rgba(245, 158, 11, 1)',
            'borderWidth' => 2,
            'tension' => 0.4
        ]
    ]
];

$pieChartData = [
    'labels' => ['Sur site', 'À distance', 'Hybride'],
    'datasets' => [
        [
            'label' => 'Types de stage',
            'data' => [35, 25, 40],
            'backgroundColor' => [
                'rgba(59, 130, 246, 0.7)',
                'rgba(16, 185, 129, 0.7)',
                'rgba(245, 158, 11, 0.7)'
            ],
            'borderColor' => [
                'rgba(59, 130, 246, 1)',
                'rgba(16, 185, 129, 1)',
                'rgba(245, 158, 11, 1)'
            ],
            'borderWidth' => 1
        ]
    ]
];

// Sample data for tables
$recentStudents = [
    [
        'id' => 1,
        'name' => 'Marie Dupont',
        'email' => 'marie.dupont@example.com',
        'program' => 'Informatique',
        'registered_at' => '2023-09-01'
    ],
    [
        'id' => 2,
        'name' => 'Pierre Martin',
        'email' => 'pierre.martin@example.com',
        'program' => 'Réseaux',
        'registered_at' => '2023-09-02'
    ],
    [
        'id' => 3,
        'name' => 'Julie Bernard',
        'email' => 'julie.bernard@example.com',
        'program' => 'Informatique',
        'registered_at' => '2023-09-03'
    ],
    [
        'id' => 4,
        'name' => 'Thomas Petit',
        'email' => 'thomas.petit@example.com',
        'program' => 'Informatique',
        'registered_at' => '2023-09-04'
    ],
    [
        'id' => 5,
        'name' => 'Sophie Moreau',
        'email' => 'sophie.moreau@example.com',
        'program' => 'Cybersécurité',
        'registered_at' => '2023-09-05'
    ]
];

// Table headers
$studentHeaders = [
    'name' => 'Nom',
    'email' => 'Email',
    'program' => 'Programme',
    'registered_at' => 'Date d\'inscription'
];

// Table actions
$studentActions = [
    [
        'label' => 'Voir',
        'url' => function($row) { return '/tutoring/views/admin/students/show.php?id=' . $row['id']; },
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z" /><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" /></svg>',
        'class' => 'text-primary-600 hover:text-primary-900'
    ],
    [
        'label' => 'Modifier',
        'url' => function($row) { return '/tutoring/views/admin/students/edit.php?id=' . $row['id']; },
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" /></svg>',
        'class' => 'text-secondary-600 hover:text-secondary-900'
    ],
    [
        'label' => 'Supprimer',
        'url' => '#',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>',
        'class' => 'text-danger-600 hover:text-danger-900',
        'attributes' => [
            'data-action' => 'click->modal#open',
            'data-modal-trigger' => 'delete-student-modal'
        ]
    ]
];
?>

<div class="dashboard">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <?php foreach ($stats as $stat): ?>
            <?php 
            include_with_vars(__DIR__ . '/../cards/stat-card.php', [
                'title' => $stat['title'],
                'value' => $stat['value'],
                'change' => $stat['change'],
                'changeType' => $stat['changeType'],
                'icon' => $stat['icon'],
                'link' => $stat['link'],
                'linkText' => 'Voir détails',
                'class' => ''
            ]);
            ?>
        <?php endforeach; ?>
    </div>
    
    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Main Chart -->
        <div class="lg:col-span-2">
            <?php 
            $chartContent = include_with_vars(__DIR__ . '/../charts/chart.php', [
                'id' => 'main-chart',
                'type' => 'line',
                'data' => $chartData,
                'options' => [
                    'responsive' => true,
                    'maintainAspectRatio' => false,
                    'plugins' => [
                        'legend' => ['position' => 'top'],
                        'tooltip' => ['mode' => 'index', 'intersect' => false]
                    ]
                ],
                'height' => '350px'
            ]);
            
            include_with_vars(__DIR__ . '/../cards/card.php', [
                'title' => 'Évolution annuelle',
                'content' => $chartContent,
                'class' => 'h-full'
            ]);
            ?>
        </div>
        
        <!-- Pie Chart -->
        <div class="lg:col-span-1">
            <?php 
            $pieChartContent = include_with_vars(__DIR__ . '/../charts/chart.php', [
                'id' => 'pie-chart',
                'type' => 'pie',
                'data' => $pieChartData,
                'options' => [
                    'responsive' => true,
                    'maintainAspectRatio' => false,
                    'plugins' => [
                        'legend' => ['position' => 'bottom']
                    ]
                ],
                'height' => '350px'
            ]);
            
            include_with_vars(__DIR__ . '/../cards/card.php', [
                'title' => 'Répartition des types de stage',
                'content' => $pieChartContent,
                'class' => 'h-full'
            ]);
            ?>
        </div>
    </div>
    
    <!-- Table Section -->
    <div class="mb-8">
        <?php 
        $tableContent = include_with_vars(__DIR__ . '/../tables/table.php', [
            'headers' => $studentHeaders,
            'data' => $recentStudents,
            'actions' => $studentActions,
            'striped' => true,
            'hover' => true
        ]);
        
        $tableFooter = include_with_vars(__DIR__ . '/../tables/pagination.php', [
            'currentPage' => 1,
            'totalPages' => 5,
            'urlPattern' => '?page={page}'
        ]);
        
        include_with_vars(__DIR__ . '/../cards/card.php', [
            'title' => 'Étudiants récemment inscrits',
            'content' => $tableContent,
            'footer' => $tableFooter
        ]);
        ?>
    </div>
</div>

<!-- Modal Example -->
<?php 
include_with_vars(__DIR__ . '/../modals/confirm-modal.php', [
    'id' => 'delete-student-modal',
    'title' => 'Confirmer la suppression',
    'message' => 'Êtes-vous sûr de vouloir supprimer cet étudiant ? Cette action est irréversible.',
    'confirmBtnText' => 'Supprimer',
    'cancelBtnText' => 'Annuler',
    'confirmBtnClass' => 'btn btn-danger',
    'cancelBtnClass' => 'btn btn-outline-secondary',
    'confirmAction' => 'click->student#delete'
]);
?>

<?php
/**
 * Helper function to include a file with variables
 * 
 * @param string $file File to include
 * @param array $vars Variables to extract
 * @return string Output of the included file
 */
function include_with_vars($file, array $vars = []) {
    if (file_exists($file)) {
        // Extract variables into the current scope
        extract($vars);
        
        // Start output buffering
        ob_start();
        
        // Include the file
        include $file;
        
        // Return the output
        return ob_get_clean();
    }
    
    return '';
}
?>