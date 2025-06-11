<?php
/**
 * Example of assignment matrix usage
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
    ['id' => 8, 'name' => 'Hugo Roux']
];

// Sample data for teachers
$teachers = [
    ['id' => 1, 'name' => 'Prof. Garcia', 'capacity' => 3],
    ['id' => 2, 'name' => 'Prof. Lambert', 'capacity' => 2],
    ['id' => 3, 'name' => 'Prof. Martin', 'capacity' => 2],
    ['id' => 4, 'name' => 'Prof. Durand', 'capacity' => 3]
];

// Sample data for current assignments
$assignments = [
    1 => 1, // Student 1 assigned to Teacher 1
    2 => 1, // Student 2 assigned to Teacher 1
    3 => 2, // Student 3 assigned to Teacher 2
    5 => 3, // Student 5 assigned to Teacher 3
    6 => 4, // Student 6 assigned to Teacher 4
    7 => 4  // Student 7 assigned to Teacher 4
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
    8 => [1 => 0.5, 2 => 0.7, 3 => 0.4, 4 => 0.3]
];

// Action buttons for the matrix controls
$actionButtons = '
<div class="flex space-x-4 mb-4">
    <button type="button" class="btn btn-primary" id="run-algorithm">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd" />
        </svg>
        Exécuter l\'algorithme
    </button>
    
    <button type="button" class="btn btn-success" id="save-assignments">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
        </svg>
        Enregistrer les affectations
    </button>
    
    <button type="button" class="btn btn-danger" id="reset-assignments">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
        </svg>
        Réinitialiser
    </button>
</div>
';

// Algorithm settings
$algorithmSettings = '
<div class="mb-6">
    <h3 class="text-lg font-semibold mb-3">Paramètres de l\'algorithme</h3>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label for="weight-preference" class="form-label">Poids des préférences</label>
            <input type="range" id="weight-preference" name="weight_preference" min="0" max="1" step="0.1" value="0.7" class="form-control">
            <div class="text-center mt-1"><span id="weight-preference-value">0.7</span></div>
        </div>
        
        <div>
            <label for="weight-capacity" class="form-label">Poids des capacités</label>
            <input type="range" id="weight-capacity" name="weight_capacity" min="0" max="1" step="0.1" value="0.3" class="form-control">
            <div class="text-center mt-1"><span id="weight-capacity-value">0.3</span></div>
        </div>
        
        <div>
            <label for="algorithm-type" class="form-label">Type d\'algorithme</label>
            <select id="algorithm-type" name="algorithm_type" class="form-control">
                <option value="greedy" selected>Glouton</option>
                <option value="hungarian">Hongrois</option>
            </select>
        </div>
    </div>
</div>
';

// Results summary
$resultsSummary = '
<div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="card">
        <div class="card-header">
            <h3 class="text-lg font-medium">Statistiques d\'affectation</h3>
        </div>
        <div class="card-body">
            <div class="grid grid-cols-2 gap-4">
                <div class="text-center">
                    <div class="text-3xl font-bold text-primary-600">6</div>
                    <div class="text-sm text-gray-500">Étudiants affectés</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-primary-600">2</div>
                    <div class="text-sm text-gray-500">Étudiants en attente</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-primary-600">75%</div>
                    <div class="text-sm text-gray-500">Taux d\'affectation</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-primary-600">0.76</div>
                    <div class="text-sm text-gray-500">Score moyen de compatibilité</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3 class="text-lg font-medium">Distribution des charges</h3>
        </div>
        <div class="card-body">
            <div class="chart-container" style="height: 200px;">
                <canvas id="capacity-chart"></canvas>
            </div>
        </div>
    </div>
</div>
';

// Create chart data for capacity distribution
$capacityChartData = [
    'labels' => array_map(function($teacher) { return $teacher['name']; }, $teachers),
    'datasets' => [
        [
            'label' => 'Étudiants affectés',
            'data' => [2, 1, 1, 2], // Hardcoded for this example
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

// Script for capacity chart
$capacityChartScript = "
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctxCapacity = document.getElementById('capacity-chart').getContext('2d');
    new Chart(ctxCapacity, {
        type: 'bar',
        data: " . json_encode($capacityChartData) . ",
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    stacked: false
                },
                y: {
                    beginAtZero: true,
                    max: 5,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
});
</script>
";
?>

<!-- Assignment Matrix Example -->
<div class="assignment-management">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-secondary-800 mb-2">Matrice d'affectation Étudiant-Tuteur</h2>
        <p class="text-gray-600">Utilisez cette matrice pour visualiser et gérer les affectations des étudiants aux tuteurs.</p>
    </div>
    
    <?php echo $actionButtons; ?>
    
    <?php echo $algorithmSettings; ?>
    
    <!-- Assignment Matrix -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
        <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
            <h3 class="text-lg font-semibold">Matrice d'affectation</h3>
        </div>
        
        <div class="p-4 overflow-x-auto">
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
    
    <?php echo $resultsSummary; ?>
    
    <?php echo $capacityChartScript; ?>
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