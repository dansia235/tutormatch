<?php
/**
 * Assignment algorithm example
 * This example demonstrates the use of client-side assignment algorithms
 */

// Sample data for students
$students = [
    ['id' => 1, 'name' => 'Marie Dupont'],
    ['id' => 2, 'name' => 'Pierre Martin'],
    ['id' => 3, 'name' => 'Julie Bernard'],
    ['id' => 4, 'name' => 'Thomas Petit'],
    ['id' => 5, 'name' => 'Sophie Moreau'],
    ['id' => 6, 'name' => 'Lucas Dubois'],
    ['id' => 7, 'name' => 'Emma Leroy'],
    ['id' => 8, 'name' => 'Hugo Roux'],
    ['id' => 9, 'name' => 'Léa Richard'],
    ['id' => 10, 'name' => 'Nathan Simon']
];

// Sample data for teachers
$teachers = [
    ['id' => 1, 'name' => 'Prof. Garcia', 'capacity' => 3],
    ['id' => 2, 'name' => 'Prof. Lambert', 'capacity' => 2],
    ['id' => 3, 'name' => 'Prof. Martin', 'capacity' => 2],
    ['id' => 4, 'name' => 'Prof. Durand', 'capacity' => 3]
];

// Sample data for compatibility weights
$weights = [
    1 => [1 => 0.9, 2 => 0.5, 3 => 0.3, 4 => 0.2],
    2 => [1 => 0.8, 2 => 0.4, 3 => 0.6, 4 => 0.3],
    3 => [1 => 0.4, 2 => 0.9, 3 => 0.5, 4 => 0.2],
    4 => [1 => 0.3, 2 => 0.6, 3 => 0.8, 4 => 0.4],
    5 => [1 => 0.2, 2 => 0.5, 3 => 0.9, 4 => 0.6],
    6 => [1 => 0.4, 2 => 0.3, 3 => 0.5, 4 => 0.9],
    7 => [1 => 0.3, 2 => 0.2, 3 => 0.6, 4 => 0.8],
    8 => [1 => 0.5, 2 => 0.7, 3 => 0.4, 4 => 0.3],
    9 => [1 => 0.7, 2 => 0.3, 3 => 0.2, 4 => 0.5],
    10 => [1 => 0.2, 2 => 0.8, 3 => 0.4, 4 => 0.3]
];

// Initial assignments (can be empty)
$assignments = [];

// Convert data to JSON for JavaScript
$studentsJson = json_encode($students);
$teachersJson = json_encode($teachers);
$weightsJson = json_encode($weights);
$assignmentsJson = json_encode($assignments);

// Create chart data for capacity distribution
$capacityChartData = [
    'labels' => array_map(function($teacher) { return $teacher['name']; }, $teachers),
    'datasets' => [
        [
            'label' => 'Étudiants affectés',
            'data' => array_fill(0, count($teachers), 0), // Start with zeros
            'backgroundColor' => 'rgba(59, 130, 246, 0.7)',
            'borderColor' => 'rgba(59, 130, 246, 1)',
            'borderWidth' => 1
        ],
        [
            'label' => 'Capacité maximale',
            'data' => array_map(function($teacher) { return $teacher['capacity']; }, $teachers),
            'backgroundColor' => 'rgba(209, 213, 219, 0.5)',
            'borderColor' => 'rgba(209, 213, 219, 1)',
            'borderWidth' => 1
        ]
    ]
];
?>

<div 
    class="assignment-algorithm-demo"
    data-controller="assignment-algorithm"
    data-assignment-algorithm-students-value='<?php echo $studentsJson; ?>'
    data-assignment-algorithm-teachers-value='<?php echo $teachersJson; ?>'
    data-assignment-algorithm-weights-value='<?php echo $weightsJson; ?>'
    data-assignment-algorithm-algorithm-value="greedy"
    data-assignment-algorithm-preference-weight-value="0.7"
    data-assignment-algorithm-capacity-weight-value="0.3"
>
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-secondary-800 mb-2">Algorithmes d'affectation étudiant-tuteur</h2>
        <p class="text-gray-600">Cette démo utilise des algorithmes JavaScript côté client pour générer des affectations optimales.</p>
    </div>
    
    <!-- Algorithm Controls -->
    <div class="card mb-6">
        <div class="card-header">
            <h3 class="text-lg font-medium">Paramètres de l'algorithme</h3>
        </div>
        <div class="card-body">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="algorithm-type" class="form-label">Type d'algorithme</label>
                    <select id="algorithm-type" name="algorithm_type" class="form-control">
                        <option value="greedy" selected>Glouton (priorité étudiants)</option>
                        <option value="hungarian">Hongrois (optimisation globale)</option>
                    </select>
                    <p class="text-sm text-gray-500 mt-1">
                        L'algorithme glouton est plus rapide, tandis que l'algorithme hongrois trouve la solution optimale.
                    </p>
                </div>
                
                <div>
                    <label for="weight-preference" class="form-label">Poids des préférences</label>
                    <input type="range" id="weight-preference" name="weight_preference" min="0" max="1" step="0.1" value="0.7" class="form-control">
                    <div class="flex justify-between text-sm text-gray-500 mt-1">
                        <span>0.0</span>
                        <span id="weight-preference-value">0.7</span>
                        <span>1.0</span>
                    </div>
                    <p class="text-sm text-gray-500 mt-1">
                        Influence des scores de compatibilité étudiant-tuteur.
                    </p>
                </div>
                
                <div>
                    <label for="weight-capacity" class="form-label">Poids des capacités</label>
                    <input type="range" id="weight-capacity" name="weight_capacity" min="0" max="1" step="0.1" value="0.3" class="form-control">
                    <div class="flex justify-between text-sm text-gray-500 mt-1">
                        <span>0.0</span>
                        <span id="weight-capacity-value">0.3</span>
                        <span>1.0</span>
                    </div>
                    <p class="text-sm text-gray-500 mt-1">
                        Influence de l'équilibre des charges entre les tuteurs.
                    </p>
                </div>
            </div>
            
            <div class="flex mt-6 justify-end space-x-3">
                <button type="button" id="reset-assignments" class="btn btn-danger">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
                    </svg>
                    Réinitialiser
                </button>
                
                <button type="button" id="run-algorithm" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd" />
                    </svg>
                    Exécuter l'algorithme
                </button>
                
                <button type="button" id="save-assignments" class="btn btn-success">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    Enregistrer les affectations
                </button>
            </div>
        </div>
    </div>
    
    <!-- Assignment Matrix -->
    <div class="card mb-6">
        <div class="card-header">
            <h3 class="text-lg font-medium">Matrice d'affectation</h3>
        </div>
        <div class="card-body overflow-x-auto p-0">
            <div class="p-4" data-assignment-algorithm-target="matrix">
                <?php 
                include_with_vars(__DIR__ . '/../charts/assignment-matrix.php', [
                    'students' => $students,
                    'teachers' => $teachers,
                    'assignments' => $assignments,
                    'weights' => $weights,
                    'editable' => true,
                    'updateUrl' => '/tutoring/api/assignments/update.php',
                    'id' => 'student-teacher-matrix'
                ]);
                ?>
            </div>
        </div>
    </div>
    
    <!-- Results Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6" data-assignment-algorithm-target="stats">
        <div class="card">
            <div class="card-header">
                <h3 class="text-lg font-medium">Statistiques d'affectation</h3>
            </div>
            <div class="card-body">
                <div class="grid grid-cols-2 gap-6">
                    <div class="text-center">
                        <div class="text-3xl font-bold text-primary-600 assigned-count">0</div>
                        <div class="text-sm text-gray-500">Étudiants affectés</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-danger-600 unassigned-count">10</div>
                        <div class="text-sm text-gray-500">Étudiants en attente</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-primary-600 assignment-rate">0%</div>
                        <div class="text-sm text-gray-500">Taux d'affectation</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-primary-600 average-score">0.00</div>
                        <div class="text-sm text-gray-500">Score moyen de compatibilité</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3 class="text-lg font-medium">Répartition des charges</h3>
            </div>
            <div class="card-body">
                <div data-controller="chart" data-chart-type-value="bar" data-chart-data-value='<?php echo json_encode($capacityChartData); ?>'>
                    <canvas data-chart-target="canvas" data-assignment-algorithm-target="capacityChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Notification container -->
    <?php 
    include_with_vars(__DIR__ . '/../notifications/notification-container.php', [
        'position' => 'top-right',
        'duration' => 5000,
        'maxCount' => 3
    ]);
    ?>
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