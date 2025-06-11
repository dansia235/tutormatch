<?php
/**
 * Example of search and filter components usage
 */

// Sample filter data
$filters = [
    [
        'type' => 'select',
        'name' => 'program',
        'label' => 'Programme',
        'options' => [
            'info' => 'Informatique',
            'reseau' => 'Réseaux',
            'cyber' => 'Cybersécurité',
            'data' => 'Data Science',
            'ia' => 'Intelligence Artificielle'
        ],
        'placeholder' => 'Tous les programmes'
    ],
    [
        'type' => 'select',
        'name' => 'status',
        'label' => 'Statut',
        'options' => [
            'active' => 'Actif',
            'inactive' => 'Inactif',
            'pending' => 'En attente'
        ],
        'placeholder' => 'Tous les statuts'
    ],
    [
        'type' => 'daterange',
        'name' => 'registration_date',
        'label' => 'Date d\'inscription'
    ],
    [
        'type' => 'checkbox',
        'name' => 'has_internship',
        'label' => 'Avec stage'
    ]
];

// Current filter values (would come from GET/POST params in a real app)
$currentFilters = [
    'program' => 'info',
    'status' => '',
    'registration_date_start' => '',
    'registration_date_end' => '',
    'has_internship' => true
];

// Sample search results
$searchResults = [
    [
        'id' => 1,
        'title' => 'Marie Dupont',
        'subtitle' => 'Informatique • 3ème année',
        'image' => 'https://randomuser.me/api/portraits/women/1.jpg',
        'url' => '/tutoring/views/admin/students/show.php?id=1'
    ],
    [
        'id' => 2,
        'title' => 'Pierre Martin',
        'subtitle' => 'Informatique • 3ème année',
        'image' => 'https://randomuser.me/api/portraits/men/2.jpg',
        'url' => '/tutoring/views/admin/students/show.php?id=2'
    ],
    [
        'id' => 3,
        'title' => 'Julie Bernard',
        'subtitle' => 'Informatique • 3ème année',
        'image' => 'https://randomuser.me/api/portraits/women/3.jpg',
        'url' => '/tutoring/views/admin/students/show.php?id=3'
    ]
];

// Sample student data
$students = [
    [
        'id' => 1,
        'name' => 'Marie Dupont',
        'email' => 'marie.dupont@example.com',
        'program' => 'Informatique',
        'year' => '3ème année',
        'has_internship' => true,
        'status' => 'active',
        'registered_at' => '2023-09-01'
    ],
    [
        'id' => 2,
        'name' => 'Pierre Martin',
        'email' => 'pierre.martin@example.com',
        'program' => 'Informatique',
        'year' => '3ème année',
        'has_internship' => true,
        'status' => 'active',
        'registered_at' => '2023-09-02'
    ],
    [
        'id' => 3,
        'name' => 'Julie Bernard',
        'email' => 'julie.bernard@example.com',
        'program' => 'Informatique',
        'year' => '3ème année',
        'has_internship' => false,
        'status' => 'pending',
        'registered_at' => '2023-09-03'
    ],
    [
        'id' => 4,
        'name' => 'Thomas Petit',
        'email' => 'thomas.petit@example.com',
        'program' => 'Réseaux',
        'year' => '2ème année',
        'has_internship' => true,
        'status' => 'active',
        'registered_at' => '2023-09-04'
    ],
    [
        'id' => 5,
        'name' => 'Sophie Moreau',
        'email' => 'sophie.moreau@example.com',
        'program' => 'Cybersécurité',
        'year' => '3ème année',
        'has_internship' => false,
        'status' => 'inactive',
        'registered_at' => '2023-09-05'
    ]
];

// Table headers for student table
$studentHeaders = [
    'name' => 'Nom',
    'email' => 'Email',
    'program' => 'Programme',
    'year' => 'Année',
    'status' => 'Statut',
    'registered_at' => 'Date d\'inscription'
];

// Actions for student table
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
            'data-modal-trigger' => 'delete-student-modal'
        ]
    ]
];

// Helper function to render status badges
function getStatusBadge($status) {
    switch ($status) {
        case 'active':
            return '<span class="badge badge-success">Actif</span>';
        case 'inactive':
            return '<span class="badge badge-danger">Inactif</span>';
        case 'pending':
            return '<span class="badge badge-warning">En attente</span>';
        default:
            return '<span class="badge badge-secondary">' . ucfirst($status) . '</span>';
    }
}

// Process student data for display
foreach ($students as &$student) {
    // Format dates
    $student['registered_at'] = date('d/m/Y', strtotime($student['registered_at']));
    
    // Format status as badge
    $student['status'] = getStatusBadge($student['status']);
}
?>

<div class="search-filter-example">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-secondary-800">Gestion des Étudiants</h2>
            <p class="text-gray-600">Recherchez et filtrez la liste des étudiants</p>
        </div>
        
        <div class="flex space-x-2">
            <a href="#" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                </svg>
                Nouvel étudiant
            </a>
            
            <a href="#" class="btn btn-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
                Exporter
            </a>
        </div>
    </div>
    
    <!-- Search and filter section -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="md:col-span-1">
            <?php 
            include_with_vars(__DIR__ . '/../filters/search-box.php', [
                'id' => 'student-search',
                'action' => '/tutoring/views/admin/students.php',
                'placeholder' => 'Rechercher un étudiant...',
                'liveSearch' => true,
                'liveSearchUrl' => '/tutoring/api/students/search.php'
            ]);
            ?>
        </div>
        
        <div class="md:col-span-3">
            <?php 
            include_with_vars(__DIR__ . '/../filters/filter-bar.php', [
                'id' => 'student-filters',
                'action' => '/tutoring/views/admin/students.php',
                'filters' => $filters,
                'currentFilters' => $currentFilters,
                'inline' => true,
                'collapsible' => true
            ]);
            ?>
        </div>
    </div>
    
    <!-- Live search results example -->
    <div class="mb-6">
        <h3 class="text-lg font-semibold mb-3">Exemple de résultats de recherche</h3>
        
        <div class="card">
            <div class="card-header">
                <h3 class="text-base font-medium">Résultats de recherche pour "mar"</h3>
            </div>
            
            <div class="card-body p-0">
                <div class="search-results divide-y divide-gray-200">
                    <?php foreach ($searchResults as $result): ?>
                        <?php 
                        include_with_vars(__DIR__ . '/../filters/search-result-item.php', [
                            'result' => $result
                        ]);
                        ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filtered results example -->
    <div class="mb-6">
        <h3 class="text-lg font-semibold mb-3">Exemple de résultats filtrés</h3>
        
        <?php 
        $tableContent = include_with_vars(__DIR__ . '/../tables/table.php', [
            'headers' => $studentHeaders,
            'data' => $students,
            'actions' => $studentActions,
            'striped' => true,
            'hover' => true
        ]);
        
        $tableFooter = include_with_vars(__DIR__ . '/../tables/pagination.php', [
            'currentPage' => 1,
            'totalPages' => 5,
            'urlPattern' => '?page={page}&program=' . $currentFilters['program']
        ]);
        
        include_with_vars(__DIR__ . '/../cards/card.php', [
            'title' => 'Étudiants',
            'content' => $tableContent,
            'footer' => $tableFooter
        ]);
        ?>
    </div>
</div>

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