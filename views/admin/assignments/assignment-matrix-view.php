<?php
/**
 * Assignment Matrix View
 * This page displays the assignment matrix for visualizing and managing student-teacher assignments
 */

// Initialize variables
$pageTitle = 'Matrice d\'affectations';
$currentPage = 'assignments';

// Include initialization file
require_once __DIR__ . '/../../../includes/init.php';

// Check permissions
requireRole(['admin', 'coordinator']);

// Instantiate models
$studentModel = new Student($db);
$teacherModel = new Teacher($db);
$internshipModel = new Internship($db);
$assignmentModel = new Assignment($db);

// Get active students
$students = $studentModel->getAll('active');

// Get active teachers with capacity
$teachers = $teacherModel->getAll(true);

// Get existing assignments
$assignmentsData = $assignmentModel->getAll();
$assignments = [];
foreach ($assignmentsData as $assignment) {
    $assignments[$assignment['student_id']] = $assignment['teacher_id'];
}

// Calculate compatibility weights (simplified version)
// In a real app, this would be a more complex calculation
$weights = [];
foreach ($students as $student) {
    $weights[$student['id']] = [];
    
    foreach ($teachers as $teacher) {
        // Default weight factors
        $departmentWeight = 0.5; // 50% for same department
        $preferenceWeight = 0.3; // 30% for preferences
        $capacityWeight = 0.2;   // 20% for capacity balance
        
        // Calculate department compatibility (1 if same department, 0.3 otherwise)
        $departmentScore = (isset($student['department_id']) && isset($teacher['department_id']) && 
                           $student['department_id'] === $teacher['department_id']) ? 1 : 0.3;
        
        // Get teacher's current assignments
        $teacherAssignmentCount = $assignmentModel->countByTeacherId($teacher['id']);
        $maxStudents = $teacher['max_students'] ?? 5;
        
        // Calculate capacity score (1 if under capacity, decreases as approaches capacity)
        $capacityScore = max(0, 1 - ($teacherAssignmentCount / max(1, $maxStudents)));
        
        // Calculate preference score (simplified, would use actual preferences in real app)
        $preferenceScore = 0.5; // Neutral preference by default
        
        // Combine scores with weights
        $totalScore = ($departmentScore * $departmentWeight) + 
                      ($preferenceScore * $preferenceWeight) + 
                      ($capacityScore * $capacityWeight);
        
        $weights[$student['id']][$teacher['id']] = min(1, max(0, $totalScore));
    }
}

// Function helper for including files with variables
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

// Include the appropriate layout
if (file_exists(__DIR__ . '/../../../templates/layouts/admin.php')) {
    // New layout with Tailwind
    $content = __DIR__ . '/assignment-matrix-content.php';
    include_once __DIR__ . '/../../../templates/layouts/admin.php';
} else {
    // Old layout with Bootstrap
    include_once __DIR__ . '/../../common/header.php';
?>

<div class="container-fluid">
    <!-- Page header with actions -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-0">
                <i class="bi bi-grid-3x3 me-2"></i>Matrice d'affectations
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/tutoring/views/admin/dashboard.php">Tableau de bord</a></li>
                    <li class="breadcrumb-item"><a href="/tutoring/views/admin/assignments.php">Affectations</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Matrice d'affectations</li>
                </ol>
            </nav>
        </div>
        
        <div>
            <a href="/tutoring/views/admin/assignments/generate.php" class="btn btn-outline-primary me-2">
                <i class="bi bi-magic me-2"></i>Génération automatique
            </a>
            <a href="/tutoring/views/admin/assignments.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Retour
            </a>
        </div>
    </div>
    
    <!-- Explanation card -->
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">
                <i class="bi bi-info-circle me-2"></i>À propos de la matrice d'affectations
            </h5>
            <p class="card-text">
                Cette matrice vous permet de visualiser et gérer les affectations étudiants-tuteurs. 
                Les couleurs indiquent le niveau de compatibilité calculé entre un étudiant et un tuteur.
            </p>
            <ul>
                <li><span class="badge bg-success">Vert foncé</span> - Excellente compatibilité (80-100%)</li>
                <li><span class="badge bg-success-light">Vert clair</span> - Bonne compatibilité (60-79%)</li>
                <li><span class="badge bg-warning">Jaune</span> - Compatibilité moyenne (40-59%)</li>
                <li><span class="badge bg-danger-light">Orange</span> - Compatibilité faible (20-39%)</li>
                <li><span class="badge bg-danger">Rouge</span> - Compatibilité très faible (0-19%)</li>
            </ul>
            <p class="text-muted small">
                Cliquez sur une cellule pour affecter un étudiant à un tuteur. Vous pouvez également 
                utiliser la génération automatique pour créer des affectations optimales.
            </p>
        </div>
    </div>
    
    <!-- Statistics and Controls -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Statistiques d'affectation</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Étudiants affectés:</span>
                            <span class="fw-bold assigned-count"><?php echo count($assignments); ?></span>
                        </div>
                        <div class="progress mt-1" style="height: 8px;">
                            <div class="progress-bar bg-success" 
                                 style="width: <?php echo count($students) > 0 ? (count($assignments) / count($students) * 100) : 0; ?>%" 
                                 aria-valuenow="<?php echo count($assignments); ?>" 
                                 aria-valuemin="0" 
                                 aria-valuemax="<?php echo count($students); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Étudiants sans affectation:</span>
                            <span class="fw-bold unassigned-count"><?php echo count($students) - count($assignments); ?></span>
                        </div>
                        <div class="progress mt-1" style="height: 8px;">
                            <div class="progress-bar bg-warning" 
                                 style="width: <?php echo count($students) > 0 ? ((count($students) - count($assignments)) / count($students) * 100) : 0; ?>%" 
                                 aria-valuenow="<?php echo count($students) - count($assignments); ?>" 
                                 aria-valuemin="0" 
                                 aria-valuemax="<?php echo count($students); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Taux d'affectation:</span>
                            <span class="fw-bold assignment-rate">
                                <?php echo count($students) > 0 ? round(count($assignments) / count($students) * 100) : 0; ?>%
                            </span>
                        </div>
                    </div>
                    
                    <div>
                        <div class="d-flex justify-content-between">
                            <span>Score moyen de compatibilité:</span>
                            <span class="fw-bold average-score">
                                <?php 
                                $totalScore = 0;
                                $count = 0;
                                foreach ($assignments as $studentId => $teacherId) {
                                    if (isset($weights[$studentId][$teacherId])) {
                                        $totalScore += $weights[$studentId][$teacherId];
                                        $count++;
                                    }
                                }
                                echo $count > 0 ? number_format($totalScore / $count, 2) : 'N/A';
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Répartition des charges</h5>
                </div>
                <div class="card-body">
                    <canvas id="teacherCapacityChart" width="400" height="250"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button id="run-algorithm" type="button" class="btn btn-primary">
                            <i class="bi bi-magic me-2"></i>Exécuter l'algorithme
                        </button>
                        
                        <div class="form-group mb-3 mt-3">
                            <label for="algorithm-type" class="form-label">Algorithme d'affectation</label>
                            <select class="form-select" id="algorithm-type">
                                <option value="greedy" selected>Algorithme glouton</option>
                                <option value="hungarian">Algorithme hongrois</option>
                                <option value="genetic">Algorithme génétique</option>
                                <option value="hybrid">Algorithme hybride</option>
                            </select>
                        </div>
                        
                        <button id="save-assignments" type="button" class="btn btn-success">
                            <i class="bi bi-save me-2"></i>Enregistrer les affectations
                        </button>
                        
                        <button id="reset-assignments" type="button" class="btn btn-outline-danger">
                            <i class="bi bi-trash me-2"></i>Réinitialiser
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Assignment Matrix -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Matrice d'affectations</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <?php
                // Render the assignment matrix
                echo '<div data-controller="assignment-algorithm" ';
                echo 'data-assignment-algorithm-students-value=\''.json_encode($students).'\' ';
                echo 'data-assignment-algorithm-teachers-value=\''.json_encode($teachers).'\' ';
                echo 'data-assignment-algorithm-weights-value=\''.json_encode($weights).'\' ';
                echo 'data-assignment-algorithm-matrix-target="matrix" ';
                echo 'data-assignment-algorithm-stats-target=".statistics-container" ';
                echo 'data-assignment-algorithm-capacity-chart-target="#teacherCapacityChart">';
                
                // Include the assignment matrix component
                include_once(__DIR__ . '/../../../components/charts/assignment-matrix.php');
                
                echo '</div>';
                ?>
            </div>
        </div>
    </div>
</div>

<!-- Load Charts.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>

<!-- Initialize charts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Teacher capacity chart
    const teacherCapacityCtx = document.getElementById('teacherCapacityChart');
    if (teacherCapacityCtx) {
        new Chart(teacherCapacityCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_map(function($teacher) { 
                    return $teacher['name'] ?? $teacher['id']; 
                }, $teachers)); ?>,
                datasets: [{
                    label: 'Étudiants affectés',
                    data: <?php 
                        $assignmentCounts = [];
                        foreach ($teachers as $teacher) {
                            $assignmentCounts[] = array_count_values($assignments)[$teacher['id']] ?? 0;
                        }
                        echo json_encode($assignmentCounts);
                    ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }, {
                    label: 'Capacité maximale',
                    data: <?php 
                        $capacities = [];
                        foreach ($teachers as $teacher) {
                            $capacities[] = $teacher['max_students'] ?? 0;
                        }
                        echo json_encode($capacities);
                    ?>,
                    type: 'line',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 2,
                    fill: false,
                    pointStyle: 'rectRot',
                    pointRadius: 5,
                    pointBorderColor: 'rgba(255, 99, 132, 1)'
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Nombre d\'étudiants'
                        },
                        ticks: {
                            stepSize: 1
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Tuteurs'
                        }
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Charge des tuteurs',
                        font: {
                            size: 14
                        }
                    }
                }
            }
        });
    }
});
</script>

<?php
    include_once __DIR__ . '/../../common/footer.php';
}
?>