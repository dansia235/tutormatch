<?php
/**
 * Internships Content Template
 * Uses components to display internships management interface
 */
?>

<div class="container mx-auto">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Gestion des stages</h1>
            <nav class="flex mt-1" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="/tutoring/views/admin/dashboard.php" class="text-sm text-gray-500 hover:text-gray-700">
                            Tableau de bord
                        </a>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-1 text-sm font-medium text-primary-600">Stages</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>
        
        <?php if (hasRole(['admin', 'coordinator'])): ?>
        <div class="mt-4 md:mt-0">
            <a href="/tutoring/views/admin/internships/create.php" class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="-ml-1 mr-2 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                </svg>
                Ajouter un stage
            </a>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Search and Filters -->
    <div class="card mb-6">
        <div class="card-body p-4">
            <div class="flex flex-col md:flex-row md:justify-between md:items-center space-y-4 md:space-y-0">
                <!-- Search Box -->
                <div class="md:w-1/2">
                    <?php
                    // Include search box component with variables
                    include_with_vars(__DIR__ . '/../../../components/filters/search-box.php', [
                        'action' => '',
                        'method' => 'GET',
                        'name' => 'term',
                        'value' => isset($_GET['term']) ? $_GET['term'] : '',
                        'placeholder' => 'Rechercher un stage...',
                        'class' => 'max-w-md'
                    ]);
                    ?>
                    <input type="hidden" name="search" value="1">
                    <?php if (!empty($activeFilter)): ?>
                    <input type="hidden" name="status" value="<?php echo h($activeFilter); ?>">
                    <?php endif; ?>
                </div>
                
                <!-- Filter Tabs -->
                <div class="inline-flex items-center rounded-md shadow-sm bg-gray-50">
                    <a href="?<?php echo isset($_GET['term']) ? 'term='.h($_GET['term']).'&search=1' : ''; ?>" 
                       class="px-4 py-2 text-sm font-medium rounded-l-md <?php echo $activeFilter === '' ? 'bg-primary-600 text-white' : 'bg-gray-50 text-gray-700 hover:bg-gray-100'; ?> transition-colors duration-200">
                       Tous
                    </a>
                    <a href="?status=available<?php echo isset($_GET['term']) ? '&term='.h($_GET['term']).'&search=1' : ''; ?>" 
                       class="px-4 py-2 text-sm font-medium <?php echo $activeFilter === 'available' ? 'bg-primary-600 text-white' : 'bg-gray-50 text-gray-700 hover:bg-gray-100'; ?> transition-colors duration-200">
                       Disponibles
                    </a>
                    <a href="?status=assigned<?php echo isset($_GET['term']) ? '&term='.h($_GET['term']).'&search=1' : ''; ?>" 
                       class="px-4 py-2 text-sm font-medium <?php echo $activeFilter === 'assigned' ? 'bg-primary-600 text-white' : 'bg-gray-50 text-gray-700 hover:bg-gray-100'; ?> transition-colors duration-200">
                       Assignés
                    </a>
                    <a href="?status=completed<?php echo isset($_GET['term']) ? '&term='.h($_GET['term']).'&search=1' : ''; ?>" 
                       class="px-4 py-2 text-sm font-medium <?php echo $activeFilter === 'completed' ? 'bg-primary-600 text-white' : 'bg-gray-50 text-gray-700 hover:bg-gray-100'; ?> transition-colors duration-200">
                       Complétés
                    </a>
                    <a href="?status=cancelled<?php echo isset($_GET['term']) ? '&term='.h($_GET['term']).'&search=1' : ''; ?>" 
                       class="px-4 py-2 text-sm font-medium rounded-r-md <?php echo $activeFilter === 'cancelled' ? 'bg-primary-600 text-white' : 'bg-gray-50 text-gray-700 hover:bg-gray-100'; ?> transition-colors duration-200">
                       Annulés
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <?php
        // Total Internships Stat Card
        include_with_vars(__DIR__ . '/../../../components/cards/stat-card.php', [
            'title' => 'Stages totaux',
            'value' => $totalInternships,
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                      </svg>'
        ]);
        
        // Available Internships Stat Card
        include_with_vars(__DIR__ . '/../../../components/cards/stat-card.php', [
            'title' => 'Stages disponibles',
            'value' => $availableCount,
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                      </svg>',
            'change' => $totalInternships > 0 ? number_format(($availableCount / $totalInternships) * 100, 0) . '% des stages' : '0% des stages',
            'changeType' => 'positive'
        ]);
        
        // Assigned Internships Stat Card
        include_with_vars(__DIR__ . '/../../../components/cards/stat-card.php', [
            'title' => 'Stages assignés',
            'value' => $assignedCount,
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                      </svg>',
            'change' => $totalInternships > 0 ? number_format(($assignedCount / $totalInternships) * 100, 0) . '% des stages' : '0% des stages',
            'changeType' => 'neutral'
        ]);
        
        // Completed Internships Stat Card
        include_with_vars(__DIR__ . '/../../../components/cards/stat-card.php', [
            'title' => 'Stages complétés',
            'value' => $completedCount,
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2M9 12l2 2 4-4" />
                      </svg>',
            'change' => $totalInternships > 0 ? number_format(($completedCount / $totalInternships) * 100, 0) . '% des stages' : '0% des stages',
            'changeType' => 'info'
        ]);
        ?>
    </div>
    
    <!-- Internships Table -->
    <div class="card shadow-sm">
        <div class="card-header border-b border-gray-200 px-5 py-4">
            <div class="flex items-center">
                <h2 class="font-semibold text-lg text-gray-800">Liste des stages</h2>
                <span class="ml-2 px-2.5 py-0.5 text-xs font-medium rounded-full bg-primary-100 text-primary-800">
                    <?php echo $totalInternships; ?> stages
                </span>
            </div>
        </div>
        <div class="card-body p-0">
            <?php
            // Prepare data for table component
            $headers = [
                'internship' => 'Stage',
                'company' => 'Entreprise',
                'domain' => 'Domaine',
                'period' => 'Période',
                'location' => 'Lieu',
                'status' => 'Statut'
            ];
            
            $tableData = [];
            $statusMap = [
                'available' => '<span class="px-2 py-1 text-xs font-medium rounded-full bg-success-100 text-success-800">Disponible</span>',
                'assigned' => '<span class="px-2 py-1 text-xs font-medium rounded-full bg-warning-100 text-warning-800">Assigné</span>',
                'completed' => '<span class="px-2 py-1 text-xs font-medium rounded-full bg-info-100 text-info-800">Complété</span>',
                'cancelled' => '<span class="px-2 py-1 text-xs font-medium rounded-full bg-danger-100 text-danger-800">Annulé</span>'
            ];
            
            $workModeMap = [
                'on_site' => 'Sur site',
                'remote' => 'Télétravail',
                'hybrid' => 'Hybride'
            ];
            
            foreach ($internships as $internship) {
                $tableData[] = [
                    'internship' => '<div class="font-medium text-gray-900">' . h($internship['title']) . '</div>' .
                                    '<div class="text-sm text-gray-500 truncate max-w-xs">' . substr(h($internship['description']), 0, 60) . (strlen($internship['description']) > 60 ? '...' : '') . '</div>',
                    'company' => h($internship['company_name']),
                    'domain' => h($internship['domain']),
                    'period' => '<div class="flex flex-col space-y-1">' .
                                '<div class="flex items-center text-sm">' .
                                '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">' .
                                '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />' .
                                '</svg>' .
                                'Début: ' . formatDate($internship['start_date']) .
                                '</div>' .
                                '<div class="flex items-center text-sm">' .
                                '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">' .
                                '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />' .
                                '</svg>' .
                                'Fin: ' . formatDate($internship['end_date']) .
                                '</div>' .
                                '</div>',
                    'location' => '<div class="flex items-center text-sm">' .
                                '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">' .
                                '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />' .
                                '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />' .
                                '</svg>' .
                                h($internship['location'] ?: 'Non spécifié') .
                                '</div>' .
                                '<div class="text-xs text-gray-500 mt-1">' .
                                ($workModeMap[$internship['work_mode']] ?? $internship['work_mode']) .
                                '</div>',
                    'status' => $statusMap[$internship['status']] ?? '<span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800">' . h($internship['status']) . '</span>'
                ];
            }
            
            // Define table actions
            $actions = [
                [
                    'label' => 'Voir',
                    'url' => function($row) use ($internships, $tableData) {
                        $index = array_search($row, $tableData);
                        if ($index !== false && isset($internships[$index])) {
                            return '/tutoring/views/admin/internships/show.php?id=' . $internships[$index]['id'];
                        }
                        return '#';
                    },
                    'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                        <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                    </svg>',
                    'class' => 'text-info-600 hover:text-info-800'
                ],
                [
                    'label' => 'Modifier',
                    'url' => function($row) use ($internships, $tableData) {
                        $index = array_search($row, $tableData);
                        if ($index !== false && isset($internships[$index])) {
                            return '/tutoring/views/admin/internships/edit.php?id=' . $internships[$index]['id'];
                        }
                        return '#';
                    },
                    'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                    </svg>',
                    'class' => 'text-warning-600 hover:text-warning-800'
                ],
                [
                    'label' => 'Supprimer',
                    'url' => '#',
                    'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>',
                    'class' => 'text-danger-600 hover:text-danger-800',
                    'attributes' => [
                        'data-action' => 'modal#open',
                        'data-modal-id' => function($row) use ($internships, $tableData) {
                            $index = array_search($row, $tableData);
                            if ($index !== false && isset($internships[$index])) {
                                return 'delete-internship-modal-' . $internships[$index]['id'];
                            }
                            return 'delete-internship-modal-0';
                        }
                    ]
                ]
            ];
            
            // Include table component
            include_with_vars(__DIR__ . '/../../../components/tables/table.php', [
                'id' => 'internships-table',
                'headers' => $headers,
                'data' => $tableData,
                'actions' => $actions,
                'emptyText' => 'Aucun stage trouvé.',
                'striped' => true,
                'hover' => true
            ]);
            ?>
        </div>
    </div>
    
    <?php if (!empty($internships)): ?>
    <!-- Delete Confirmation Modals -->
    <?php foreach ($internships as $internship): ?>
    <div id="delete-internship-modal-<?php echo $internship['id']; ?>" class="hidden" data-controller="modal">
        <?php
        // Include modal component for confirmation
        include_with_vars(__DIR__ . '/../../../components/modals/confirm-modal.php', [
            'id' => 'delete-internship-modal-content-' . $internship['id'],
            'title' => 'Confirmer la suppression',
            'message' => '<p>Êtes-vous sûr de vouloir supprimer le stage <strong>' . h($internship['title']) . '</strong> ?</p>
                          <p class="text-danger-500 mt-2 flex items-center">
                              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                  <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                              </svg>
                              Cette action est irréversible' . ($internship['status'] === 'assigned' ? ' et annulera toutes les affectations liées à ce stage' : '') . '.
                          </p>',
            'confirmUrl' => '/tutoring/views/admin/internships/delete.php',
            'confirmMethod' => 'POST',
            'confirmParams' => [
                'id' => $internship['id'],
                'csrf_token' => generateCsrfToken()
            ],
            'confirmText' => 'Supprimer',
            'confirmClass' => 'bg-danger-600 hover:bg-danger-700 focus:ring-danger-500',
            'cancelText' => 'Annuler'
        ]);
        ?>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize any JavaScript functionality here if needed
});
</script>