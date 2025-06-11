<?php
/**
 * Teachers/Tutors Content Template
 * Uses components to display tutor management interface
 */
?>

<div class="container mx-auto">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Gestion des tuteurs</h1>
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
                            <span class="ml-1 text-sm font-medium text-primary-600">Tuteurs</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>
        
        <?php if (hasRole(['admin', 'coordinator'])): ?>
        <div class="mt-4 md:mt-0">
            <a href="/tutoring/views/admin/teachers/create.php" class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="-ml-1 mr-2 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                </svg>
                Ajouter un tuteur
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
                        'placeholder' => 'Rechercher un tuteur...',
                        'class' => 'max-w-md'
                    ]);
                    ?>
                    <input type="hidden" name="search" value="1">
                    <?php if ($activeFilter !== ''): ?>
                    <input type="hidden" name="available" value="<?php echo h($activeFilter); ?>">
                    <?php endif; ?>
                </div>
                
                <!-- Filter Tabs -->
                <div class="inline-flex items-center rounded-md shadow-sm bg-gray-50">
                    <a href="?<?php echo isset($_GET['term']) ? 'term='.h($_GET['term']).'&search=1' : ''; ?>" 
                       class="px-4 py-2 text-sm font-medium rounded-l-md <?php echo $activeFilter === '' ? 'bg-primary-600 text-white' : 'bg-gray-50 text-gray-700 hover:bg-gray-100'; ?> transition-colors duration-200">
                       Tous
                    </a>
                    <a href="?available=1<?php echo isset($_GET['term']) ? '&term='.h($_GET['term']).'&search=1' : ''; ?>" 
                       class="px-4 py-2 text-sm font-medium <?php echo $activeFilter === '1' ? 'bg-primary-600 text-white' : 'bg-gray-50 text-gray-700 hover:bg-gray-100'; ?> transition-colors duration-200">
                       Disponibles
                    </a>
                    <a href="?available=0<?php echo isset($_GET['term']) ? '&term='.h($_GET['term']).'&search=1' : ''; ?>" 
                       class="px-4 py-2 text-sm font-medium rounded-r-md <?php echo $activeFilter === '0' ? 'bg-primary-600 text-white' : 'bg-gray-50 text-gray-700 hover:bg-gray-100'; ?> transition-colors duration-200">
                       Indisponibles
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <?php
        // Tutors Count Stat Card
        include_with_vars(__DIR__ . '/../../../components/cards/stat-card.php', [
            'title' => 'Tuteurs actifs',
            'value' => $teacherCount,
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                      </svg>',
            'change' => '',
            'changeType' => 'neutral'
        ]);
        
        // Available Tutors Stat Card
        include_with_vars(__DIR__ . '/../../../components/cards/stat-card.php', [
            'title' => 'Tuteurs disponibles',
            'value' => $availableCount,
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                      </svg>',
            'change' => $teacherCount > 0 ? number_format(($availableCount / $teacherCount) * 100, 0) . '% des tuteurs' : '0% des tuteurs',
            'changeType' => $availableCount > 0 ? 'positive' : 'neutral'
        ]);
        
        // Available Capacity Stat Card
        include_with_vars(__DIR__ . '/../../../components/cards/stat-card.php', [
            'title' => 'Places disponibles',
            'value' => $availableCapacity,
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                      </svg>',
            'change' => $totalMaxStudents > 0 ? number_format(($availableCapacity / $totalMaxStudents) * 100, 0) . '% de capacité libre' : '0% de capacité libre',
            'changeType' => $availableCapacity > 0 ? 'positive' : ($availableCapacity == 0 ? 'neutral' : 'negative')
        ]);
        ?>
    </div>
    
    <!-- Tutors Table -->
    <div class="card shadow-sm">
        <div class="card-header border-b border-gray-200 px-5 py-4">
            <div class="flex items-center">
                <h2 class="font-semibold text-lg text-gray-800">Liste des tuteurs</h2>
                <span class="ml-2 px-2.5 py-0.5 text-xs font-medium rounded-full bg-primary-100 text-primary-800">
                    <?php echo $teacherCount; ?> tuteurs
                </span>
            </div>
        </div>
        <div class="card-body p-0">
            <?php
            // Prepare data for table component
            $headers = [
                'teacher' => 'Tuteur',
                'department' => 'Département',
                'specialty' => 'Spécialité',
                'availability' => 'Disponibilité',
                'capacity' => 'Capacité'
            ];
            
            $tableData = [];
            
            foreach ($teachers as $teacher) {
                // Create avatar for teacher
                $avatar = '';
                if (!empty($teacher['profile_image'])) {
                    $avatar = '<img src="' . h($teacher['profile_image']) . '" alt="Profile" class="h-10 w-10 rounded-full mr-3">';
                } else {
                    $initials = strtoupper(substr($teacher['first_name'], 0, 1) . substr($teacher['last_name'], 0, 1));
                    $avatar = '<div class="flex items-center justify-center h-10 w-10 rounded-full bg-primary-500 text-white mr-3">' . $initials . '</div>';
                }
                
                // Calculate capacity percentages
                $currentCount = isset($teacher['students_count']) ? $teacher['students_count'] : 0;
                $maxStudents = $teacher['max_students'];
                $ratio = $maxStudents > 0 ? ($currentCount / $maxStudents) : 0;
                $percentFilled = number_format($ratio * 100, 0);
                
                // Determine color based on capacity
                $barColorClass = 'bg-success-500';
                if ($ratio >= 0.8) {
                    $barColorClass = 'bg-danger-500';
                } elseif ($ratio >= 0.5) {
                    $barColorClass = 'bg-warning-500';
                }
                
                // Create capacity bar
                $capacityBar = '
                <div class="flex items-center">
                    <div class="w-32 bg-gray-200 rounded-full h-2.5 mr-2">
                        <div class="' . $barColorClass . ' h-2.5 rounded-full" style="width: ' . $percentFilled . '%"></div>
                    </div>
                    <span class="text-sm font-medium">' . $currentCount . '/' . $maxStudents . '</span>
                </div>';
                
                $tableData[] = [
                    'teacher' => '<div class="flex items-center">' . 
                                 $avatar . 
                                 '<div>' .
                                 '<div class="font-medium text-gray-900">' . h(($teacher['title'] ? $teacher['title'] . ' ' : '') . $teacher['first_name'] . ' ' . $teacher['last_name']) . '</div>' .
                                 '<div class="text-sm text-gray-500">' . h($teacher['email']) . '</div>' .
                                 '</div></div>',
                    'department' => h($teacher['department']),
                    'specialty' => h($teacher['specialty']),
                    'availability' => $teacher['available'] ? 
                                      '<span class="px-2 py-1 text-xs font-medium rounded-full bg-success-100 text-success-800">Disponible</span>' :
                                      '<span class="px-2 py-1 text-xs font-medium rounded-full bg-warning-100 text-warning-800">Indisponible</span>',
                    'capacity' => $capacityBar
                ];
            }
            
            // Define table actions
            $actions = [
                [
                    'label' => 'Voir',
                    'url' => function($row) use ($teachers, $tableData) {
                        // Find the corresponding teacher from the original array
                        $index = array_search($row, $tableData);
                        if ($index !== false && isset($teachers[$index])) {
                            return '/tutoring/views/admin/teachers/show.php?id=' . $teachers[$index]['id'];
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
                    'url' => function($row) use ($teachers, $tableData) {
                        // Find the corresponding teacher from the original array
                        $index = array_search($row, $tableData);
                        if ($index !== false && isset($teachers[$index])) {
                            return '/tutoring/views/admin/teachers/edit.php?id=' . $teachers[$index]['id'];
                        }
                        return '#';
                    },
                    'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                    </svg>',
                    'class' => 'text-warning-600 hover:text-warning-800'
                ]
            ];
            
            // Add delete action for admin role
            if (hasRole(['admin'])) {
                $actions[] = [
                    'label' => 'Supprimer',
                    'url' => '#',
                    'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>',
                    'class' => 'text-danger-600 hover:text-danger-800',
                    'attributes' => [
                        'data-action' => 'modal#open',
                        'data-modal-id' => function($row) use ($teachers, $tableData) {
                            $index = array_search($row, $tableData);
                            if ($index !== false && isset($teachers[$index])) {
                                return 'delete-teacher-modal-' . $teachers[$index]['id'];
                            }
                            return 'delete-teacher-modal-0';
                        }
                    ]
                ];
            }
            
            // Include table component
            include_with_vars(__DIR__ . '/../../../components/tables/table.php', [
                'id' => 'teachers-table',
                'headers' => $headers,
                'data' => $tableData,
                'actions' => $actions,
                'emptyText' => 'Aucun tuteur trouvé.',
                'striped' => true,
                'hover' => true
            ]);
            ?>
        </div>
    </div>
    
    <?php if (hasRole(['admin']) && !empty($teachers)): ?>
    <!-- Delete Confirmation Modals -->
    <?php foreach ($teachers as $teacher): ?>
    <div id="delete-teacher-modal-<?php echo $teacher['id']; ?>" class="hidden" data-controller="modal">
        <?php
        // Include modal component for confirmation
        include_with_vars(__DIR__ . '/../../../components/modals/confirm-modal.php', [
            'id' => 'delete-teacher-modal-content-' . $teacher['id'],
            'title' => 'Confirmer la suppression',
            'message' => '<p>Êtes-vous sûr de vouloir supprimer le tuteur <strong>' . h(($teacher['title'] ? $teacher['title'] . ' ' : '') . $teacher['first_name'] . ' ' . $teacher['last_name']) . '</strong> ?</p>
                          <p class="text-danger-500 mt-2 flex items-center">
                              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                  <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                              </svg>
                              Cette action est irréversible et supprimera également toutes les données associées à ce tuteur.
                          </p>',
            'confirmUrl' => '/tutoring/views/admin/teachers/delete.php',
            'confirmMethod' => 'POST',
            'confirmParams' => [
                'id' => $teacher['id'],
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