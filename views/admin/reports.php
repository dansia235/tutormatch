<?php
/**
 * Vue pour les rapports administratifs
 */

// Titre de la page
$pageTitle = 'Rapports';
$currentPage = 'reports';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../includes/init.php';

// Vérifier les permissions
requireRole(['admin', 'coordinator']);

// Récupérer le type de rapport demandé
$reportType = isset($_GET['type']) ? $_GET['type'] : 'general';

// Récupérer les périodes de début et de fin si fournies
$periodStart = isset($_GET['period_start']) ? $_GET['period_start'] : date('Y-m-d', strtotime('-6 months'));
$periodEnd = isset($_GET['period_end']) ? $_GET['period_end'] : date('Y-m-d');

// Instancier les contrôleurs nécessaires
$studentController = new StudentController($db);
$teacherController = new TeacherController($db);
$internshipController = new InternshipController($db);
$assignmentController = new AssignmentController($db);

// Instancier les modèles directement si nécessaire
$teacherModel = new Teacher($db);

// Obtenir les données en fonction du type de rapport
switch ($reportType) {
    case 'students':
        // Récupérer les statistiques des étudiants
        $students = $studentController->getStudents();
        $activeStudents = count(array_filter($students, function($student) {
            return $student['status'] === 'active';
        }));
        $graduatedStudents = count(array_filter($students, function($student) {
            return $student['status'] === 'graduated';
        }));
        $suspendedStudents = count(array_filter($students, function($student) {
            return $student['status'] === 'suspended';
        }));
        $totalStudents = count($students);

        // Programmes d'études
        $programs = array_count_values(array_column($students, 'program'));
        arsort($programs); // Trier par nombre décroissant

        // Niveaux d'études
        $levels = array_count_values(array_column($students, 'level'));
        arsort($levels); // Trier par nombre décroissant

        // Départements
        $departments = array_count_values(array_column($students, 'department'));
        arsort($departments); // Trier par nombre décroissant

        // Créer une instance du modèle Assignment directement
        $assignmentModel = new Assignment($db);
        
        // Récupérer les affectations des étudiants
        $assignments = $assignmentModel->getAll();
        $assignedStudents = [];
        foreach ($assignments as $assignment) {
            $assignedStudents[$assignment['student_id']] = $assignment;
        }
        
        // Compter les étudiants avec et sans affectation
        $studentsWithAssignment = count($assignedStudents);
        $studentsWithoutAssignment = $activeStudents - $studentsWithAssignment;
        
        // Répartition par statut d'affectation
        $assignmentStatusCounts = [];
        foreach ($assignments as $assignment) {
            $status = $assignment['status'];
            if (!isset($assignmentStatusCounts[$status])) {
                $assignmentStatusCounts[$status] = 0;
            }
            $assignmentStatusCounts[$status]++;
        }

        $reportData = [
            'students' => $students,
            'totalStudents' => $totalStudents,
            'activeStudents' => $activeStudents,
            'graduatedStudents' => $graduatedStudents,
            'suspendedStudents' => $suspendedStudents,
            'programs' => $programs,
            'levels' => $levels,
            'departments' => $departments,
            'studentsWithAssignment' => $studentsWithAssignment,
            'studentsWithoutAssignment' => $studentsWithoutAssignment,
            'assignmentStatusCounts' => $assignmentStatusCounts
        ];
        break;
        
    case 'teachers':
        // Récupérer les statistiques des tuteurs
        $teachers = $teacherController->getTeachers();
        $availableTeachers = count(array_filter($teachers, function($teacher) {
            return $teacher['available'] == 1;
        }));
        $unavailableTeachers = count($teachers) - $availableTeachers;
        $totalTeachers = count($teachers);
        
        // Départements
        $departments = array_count_values(array_column($teachers, 'department'));
        arsort($departments); // Trier par nombre décroissant
        
        // Spécialités
        $specialties = array_count_values(array_column($teachers, 'specialty'));
        arsort($specialties); // Trier par nombre décroissant
        
        // Titres académiques
        $titles = array_count_values(array_map(function($teacher) {
            return !empty($teacher['title']) ? $teacher['title'] : 'Non spécifié';
        }, $teachers));
        arsort($titles); // Trier par nombre décroissant
        
        // Charge de travail
        $workloadStats = $teacherModel->getWorkloadStats();
        
        // Calcul des statistiques de charge
        $lowWorkload = 0;  // < 33%
        $mediumWorkload = 0; // 33-66%
        $highWorkload = 0;  // > 66%
        $fullWorkload = 0;  // 100%
        
        foreach ($workloadStats as $stat) {
            $percentage = $stat['workload_percentage'];
            if ($percentage >= 100) {
                $fullWorkload++;
            } elseif ($percentage > 66) {
                $highWorkload++;
            } elseif ($percentage >= 33) {
                $mediumWorkload++;
            } else {
                $lowWorkload++;
            }
        }
        
        // Préparation des données pour le rapport
        $reportData = [
            'teachers' => $teachers,
            'totalTeachers' => $totalTeachers,
            'availableTeachers' => $availableTeachers,
            'unavailableTeachers' => $unavailableTeachers,
            'departments' => $departments,
            'specialties' => $specialties,
            'titles' => $titles,
            'workloadStats' => $workloadStats,
            'lowWorkload' => $lowWorkload,
            'mediumWorkload' => $mediumWorkload,
            'highWorkload' => $highWorkload,
            'fullWorkload' => $fullWorkload
        ];
        break;
        
    case 'internships':
        // Récupérer les statistiques des stages
        $internships = $internshipController->getAll();
        $totalInternships = count($internships);
        
        // Calculer les statistiques par statut
        $statusStats = [];
        $domainsStats = [];
        $timelineStats = [];
        $companyStats = [];
        $currentDate = date('Y-m-d');
        
        foreach ($internships as $internship) {
            // Statistiques par statut
            $internshipStatus = $internship['status'] ?? 'unknown';
            if (!isset($statusStats[$internshipStatus])) {
                $statusStats[$internshipStatus] = 0;
            }
            $statusStats[$internshipStatus]++;
            
            // Statistiques par domaine
            $internshipDomain = $internship['domain'] ?? 'unknown';
            if (!isset($domainsStats[$internshipDomain])) {
                $domainsStats[$internshipDomain] = 0;
            }
            $domainsStats[$internshipDomain]++;
            
            // Statistiques par entreprise
            $companyName = $internship['company_name'] ?? 'unknown';
            if (!isset($companyStats[$companyName])) {
                $companyStats[$companyName] = 0;
            }
            $companyStats[$companyName]++;
            
            // Statistiques par timeline
            if (isset($internship['start_date']) && isset($internship['end_date'])) {
                if ($internship['start_date'] > $currentDate) {
                    if (!isset($timelineStats['upcoming'])) {
                        $timelineStats['upcoming'] = 0;
                    }
                    $timelineStats['upcoming']++;
                } elseif ($internship['end_date'] < $currentDate) {
                    if (!isset($timelineStats['past'])) {
                        $timelineStats['past'] = 0;
                    }
                    $timelineStats['past']++;
                } else {
                    if (!isset($timelineStats['current'])) {
                        $timelineStats['current'] = 0;
                    }
                    $timelineStats['current']++;
                }
            }
        }
        
        // Trier par nombre décroissant
        arsort($domainsStats);
        arsort($companyStats);
        
        // Récupérer tous les domaines et compétences
        $domains = $internshipController->getDomains();
        $skills = $internshipController->getAllSkills();
        
        // Calculer les statistiques de compétences
        $skillStats = [];
        foreach ($internships as $internship) {
            if (isset($internship['skills']) && is_array($internship['skills'])) {
                foreach ($internship['skills'] as $skill) {
                    if (!isset($skillStats[$skill])) {
                        $skillStats[$skill] = 0;
                    }
                    $skillStats[$skill]++;
                }
            }
        }
        
        // Trier les compétences par fréquence
        arsort($skillStats);
        
        // Statuts en français et leurs classes
        $statusLabels = [
            'available' => 'Disponible',
            'assigned' => 'Affecté',
            'completed' => 'Terminé',
            'cancelled' => 'Annulé',
            'draft' => 'Brouillon',
            'pending' => 'En attente'
        ];
        
        $statusClasses = [
            'available' => 'success',
            'assigned' => 'primary',
            'completed' => 'info',
            'cancelled' => 'danger',
            'draft' => 'secondary',
            'pending' => 'warning'
        ];
        
        // Préparation des données pour le rapport
        $reportData = [
            'internships' => $internships,
            'totalInternships' => $totalInternships,
            'statusStats' => $statusStats,
            'domainsStats' => $domainsStats,
            'timelineStats' => $timelineStats,
            'companyStats' => $companyStats,
            'skillStats' => $skillStats,
            'statusLabels' => $statusLabels,
            'statusClasses' => $statusClasses,
            'domains' => $domains,
            'skills' => $skills
        ];
        break;
        
    case 'assignments':
        // Rapports sur les affectations
        $assignments = $assignmentController->getAll();
        $totalAssignments = count($assignments);
        
        // Statistiques par statut
        $statusStats = [];
        $departmentStats = [];
        $satisfactionStats = [
            'excellent' => 0,  // 4.5-5.0
            'good' => 0,       // 4.0-4.4
            'average' => 0,    // 3.0-3.9
            'poor' => 0,       // 2.0-2.9
            'very_poor' => 0   // 0-1.9
        ];
        $timeStats = [
            'this_month' => 0,
            'last_month' => 0,
            'last_quarter' => 0,
            'older' => 0
        ];
        $teacherAssignmentCount = [];
        
        $currentDate = date('Y-m-d');
        $currentMonth = date('Y-m-01');
        $lastMonth = date('Y-m-01', strtotime('-1 month'));
        $lastQuarter = date('Y-m-01', strtotime('-3 months'));
        
        foreach ($assignments as $assignment) {
            // Statistiques par statut
            $status = $assignment['status'] ?? 'unknown';
            if (!isset($statusStats[$status])) {
                $statusStats[$status] = 0;
            }
            $statusStats[$status]++;
            
            // Statistiques par département (si disponible)
            if (isset($assignment['student_department'])) {
                $department = $assignment['student_department'];
                if (!isset($departmentStats[$department])) {
                    $departmentStats[$department] = 0;
                }
                $departmentStats[$department]++;
            }
            
            // Statistiques de satisfaction
            if (isset($assignment['satisfaction_score']) && $assignment['satisfaction_score'] > 0) {
                $score = floatval($assignment['satisfaction_score']);
                if ($score >= 4.5) {
                    $satisfactionStats['excellent']++;
                } elseif ($score >= 4.0) {
                    $satisfactionStats['good']++;
                } elseif ($score >= 3.0) {
                    $satisfactionStats['average']++;
                } elseif ($score >= 2.0) {
                    $satisfactionStats['poor']++;
                } else {
                    $satisfactionStats['very_poor']++;
                }
            }
            
            // Statistiques temporelles
            if (isset($assignment['assignment_date'])) {
                $assignmentDate = $assignment['assignment_date'];
                if ($assignmentDate >= $currentMonth) {
                    $timeStats['this_month']++;
                } elseif ($assignmentDate >= $lastMonth) {
                    $timeStats['last_month']++;
                } elseif ($assignmentDate >= $lastQuarter) {
                    $timeStats['last_quarter']++;
                } else {
                    $timeStats['older']++;
                }
            }
            
            // Comptage des affectations par tuteur
            if (isset($assignment['teacher_id'])) {
                $teacherId = $assignment['teacher_id'];
                $teacherName = ($assignment['teacher_first_name'] ?? '') . ' ' . ($assignment['teacher_last_name'] ?? '');
                
                if (!isset($teacherAssignmentCount[$teacherId])) {
                    $teacherAssignmentCount[$teacherId] = [
                        'name' => $teacherName,
                        'count' => 0
                    ];
                }
                $teacherAssignmentCount[$teacherId]['count']++;
            }
        }
        
        // Trier par nombre d'affectations décroissant
        arsort($departmentStats);
        uasort($teacherAssignmentCount, function($a, $b) {
            return $b['count'] - $a['count'];
        });
        
        // Statistiques de compatibilité
        $compatibilityStats = [
            'high' => 0,    // > 80%
            'medium' => 0,  // 50-80%
            'low' => 0      // < 50%
        ];
        
        foreach ($assignments as $assignment) {
            if (isset($assignment['compatibility_score'])) {
                $score = floatval($assignment['compatibility_score']);
                if ($score > 0.8) {
                    $compatibilityStats['high']++;
                } elseif ($score >= 0.5) {
                    $compatibilityStats['medium']++;
                } else {
                    $compatibilityStats['low']++;
                }
            }
        }
        
        // Traductions des statuts
        $statusLabels = [
            'pending' => 'En attente',
            'confirmed' => 'Confirmée',
            'rejected' => 'Rejetée',
            'completed' => 'Terminée',
            'cancelled' => 'Annulée'
        ];
        
        // Classes CSS pour les statuts
        $statusClasses = [
            'pending' => 'warning',
            'confirmed' => 'success',
            'rejected' => 'danger',
            'completed' => 'info',
            'cancelled' => 'secondary'
        ];
        
        // Préparation des données pour le rapport
        $reportData = [
            'assignments' => $assignments,
            'totalAssignments' => $totalAssignments,
            'statusStats' => $statusStats,
            'departmentStats' => $departmentStats,
            'satisfactionStats' => $satisfactionStats,
            'timeStats' => $timeStats,
            'compatibilityStats' => $compatibilityStats,
            'teacherAssignmentCount' => $teacherAssignmentCount,
            'statusLabels' => $statusLabels,
            'statusClasses' => $statusClasses
        ];
        break;
        
    case 'evaluations':
        // Rapports sur les évaluations
        // TODO: Implémenter les rapports sur les évaluations
        $reportData = [
            'type' => 'evaluations',
            'message' => 'Rapports sur les évaluations à implémenter'
        ];
        break;
        
    default:
        // Rapport général
        $reportData = [
            'type' => 'general',
            'message' => 'Rapport général du système'
        ];
        break;
}

// Inclure l'en-tête
include_once __DIR__ . '/../common/header.php';

// Inclure Chart.js pour les graphiques
echo '<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>';
?>

<div class="container-fluid">
    <!-- Header section -->
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-graph-up me-2"></i>Rapports</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/tutoring/views/admin/dashboard.php">Tableau de bord</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Rapports</li>
                    <li class="breadcrumb-item active" aria-current="page">
                        <?php
                        $reportTitles = [
                            'general' => 'Général',
                            'students' => 'Étudiants',
                            'teachers' => 'Tuteurs',
                            'internships' => 'Stages',
                            'assignments' => 'Affectations',
                            'evaluations' => 'Évaluations'
                        ];
                        echo $reportTitles[$reportType] ?? 'Rapports';
                        ?>
                    </li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Content section -->
    <div class="row">
        <!-- Main content -->
        <div class="col-lg-9">
            <!-- Report type selector -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="btn-group w-100" role="group" aria-label="Report types">
                        <a href="?type=general" class="btn <?php echo $reportType === 'general' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            <i class="bi bi-house me-1"></i>Général
                        </a>
                        <a href="?type=students" class="btn <?php echo $reportType === 'students' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            <i class="bi bi-mortarboard me-1"></i>Étudiants
                        </a>
                        <a href="?type=teachers" class="btn <?php echo $reportType === 'teachers' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            <i class="bi bi-person-badge me-1"></i>Tuteurs
                        </a>
                        <a href="?type=internships" class="btn <?php echo $reportType === 'internships' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            <i class="bi bi-briefcase me-1"></i>Stages
                        </a>
                        <a href="?type=assignments" class="btn <?php echo $reportType === 'assignments' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            <i class="bi bi-diagram-3 me-1"></i>Affectations
                        </a>
                        <a href="?type=evaluations" class="btn <?php echo $reportType === 'evaluations' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            <i class="bi bi-star me-1"></i>Évaluations
                        </a>
                    </div>
                </div>
            </div>

            <?php if ($reportType === 'students'): ?>
                <!-- Student Reports Section -->
                <div class="report-section">
                    <h3 class="mb-4">Rapport sur les étudiants</h3>
                    
                    <!-- Overview Stats -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card stat-card">
                                <div class="value"><?php echo $reportData['totalStudents']; ?></div>
                                <div class="label">Étudiants total</div>
                                <div class="progress mt-2">
                                    <div class="progress-bar" role="progressbar" style="width: 100%;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stat-card">
                                <div class="value"><?php echo $reportData['activeStudents']; ?></div>
                                <div class="label">Étudiants actifs</div>
                                <div class="progress mt-2">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo ($reportData['totalStudents'] > 0) ? ($reportData['activeStudents'] / $reportData['totalStudents'] * 100) : 0; ?>%;" aria-valuenow="<?php echo ($reportData['totalStudents'] > 0) ? ($reportData['activeStudents'] / $reportData['totalStudents'] * 100) : 0; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stat-card">
                                <div class="value"><?php echo $reportData['graduatedStudents']; ?></div>
                                <div class="label">Diplômés</div>
                                <div class="progress mt-2">
                                    <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo ($reportData['totalStudents'] > 0) ? ($reportData['graduatedStudents'] / $reportData['totalStudents'] * 100) : 0; ?>%;" aria-valuenow="<?php echo ($reportData['totalStudents'] > 0) ? ($reportData['graduatedStudents'] / $reportData['totalStudents'] * 100) : 0; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stat-card">
                                <div class="value"><?php echo $reportData['suspendedStudents']; ?></div>
                                <div class="label">Suspendus</div>
                                <div class="progress mt-2">
                                    <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo ($reportData['totalStudents'] > 0) ? ($reportData['suspendedStudents'] / $reportData['totalStudents'] * 100) : 0; ?>%;" aria-valuenow="<?php echo ($reportData['totalStudents'] > 0) ? ($reportData['suspendedStudents'] / $reportData['totalStudents'] * 100) : 0; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Student Distribution Charts -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Répartition par statut</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="statusChart"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Répartition par affectation</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="assignmentChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Programs and Levels -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Répartition par programme</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="programChart"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Répartition par niveau</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="levelChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Detailed Statistics -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title">Statistiques détaillées</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Catégorie</th>
                                            <th>Nombre</th>
                                            <th>Pourcentage</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Étudiants actifs</td>
                                            <td><?php echo $reportData['activeStudents']; ?></td>
                                            <td><?php echo $reportData['totalStudents'] > 0 ? number_format(($reportData['activeStudents'] / $reportData['totalStudents']) * 100, 1) : 0; ?>%</td>
                                        </tr>
                                        <tr>
                                            <td>Étudiants diplômés</td>
                                            <td><?php echo $reportData['graduatedStudents']; ?></td>
                                            <td><?php echo $reportData['totalStudents'] > 0 ? number_format(($reportData['graduatedStudents'] / $reportData['totalStudents']) * 100, 1) : 0; ?>%</td>
                                        </tr>
                                        <tr>
                                            <td>Étudiants suspendus</td>
                                            <td><?php echo $reportData['suspendedStudents']; ?></td>
                                            <td><?php echo $reportData['totalStudents'] > 0 ? number_format(($reportData['suspendedStudents'] / $reportData['totalStudents']) * 100, 1) : 0; ?>%</td>
                                        </tr>
                                        <tr>
                                            <td>Étudiants avec tuteur</td>
                                            <td><?php echo $reportData['studentsWithAssignment']; ?></td>
                                            <td><?php echo $reportData['activeStudents'] > 0 ? number_format(($reportData['studentsWithAssignment'] / $reportData['activeStudents']) * 100, 1) : 0; ?>%</td>
                                        </tr>
                                        <tr>
                                            <td>Étudiants sans tuteur</td>
                                            <td><?php echo $reportData['studentsWithoutAssignment']; ?></td>
                                            <td><?php echo $reportData['activeStudents'] > 0 ? number_format(($reportData['studentsWithoutAssignment'] / $reportData['activeStudents']) * 100, 1) : 0; ?>%</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Programs Table -->
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Programmes d'études</h5>
                            <button class="btn btn-sm btn-outline-primary" id="togglePrograms">
                                <i class="bi bi-arrows-expand"></i> Afficher tout
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Programme</th>
                                            <th>Nombre d'étudiants</th>
                                            <th>Pourcentage</th>
                                        </tr>
                                    </thead>
                                    <tbody id="programsTable">
                                        <?php 
                                        $counter = 0;
                                        foreach ($reportData['programs'] as $program => $count): 
                                            $isHidden = $counter >= 5;
                                        ?>
                                        <tr class="<?php echo $isHidden ? 'program-row-hidden' : ''; ?>" <?php echo $isHidden ? 'style="display: none;"' : ''; ?>>
                                            <td><?php echo h($program); ?></td>
                                            <td><?php echo $count; ?></td>
                                            <td><?php echo $reportData['totalStudents'] > 0 ? number_format(($count / $reportData['totalStudents']) * 100, 1) : 0; ?>%</td>
                                        </tr>
                                        <?php 
                                        $counter++;
                                        endforeach; 
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Export Options -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title">Exporter les rapports</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <i class="bi bi-file-earmark-text fs-1 mb-3 text-primary"></i>
                                            <h5 class="card-title">Rapport complet</h5>
                                            <p class="card-text">Exporter toutes les statistiques et graphiques sur les étudiants</p>
                                            <a href="/tutoring/api/export/students.php?format=pdf&report=full" target="_blank" class="btn btn-primary">
                                                <i class="bi bi-download me-1"></i>Télécharger PDF
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <i class="bi bi-bar-chart fs-1 mb-3 text-success"></i>
                                            <h5 class="card-title">Statistiques seulement</h5>
                                            <p class="card-text">Exporter uniquement les données statistiques</p>
                                            <a href="/tutoring/api/export/students.php?format=excel&report=stats" target="_blank" class="btn btn-success">
                                                <i class="bi bi-download me-1"></i>Télécharger Excel
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <i class="bi bi-images fs-1 mb-3 text-info"></i>
                                            <h5 class="card-title">Graphiques seulement</h5>
                                            <p class="card-text">Exporter uniquement les graphiques et visualisations</p>
                                            <a href="/tutoring/api/export/students.php?format=pdf&report=charts" target="_blank" class="btn btn-info">
                                                <i class="bi bi-download me-1"></i>Télécharger PDF
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php elseif ($reportType === 'teachers'): ?>
                <!-- Teacher Reports Section -->
                <div class="report-section">
                    <h3 class="mb-4">Rapport sur les tuteurs</h3>
                    
                    <!-- Overview Stats -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card stat-card">
                                <div class="value"><?php echo $reportData['totalTeachers']; ?></div>
                                <div class="label">Tuteurs total</div>
                                <div class="progress mt-2">
                                    <div class="progress-bar" role="progressbar" style="width: 100%;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stat-card">
                                <div class="value"><?php echo $reportData['availableTeachers']; ?></div>
                                <div class="label">Tuteurs disponibles</div>
                                <div class="progress mt-2">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo ($reportData['totalTeachers'] > 0) ? ($reportData['availableTeachers'] / $reportData['totalTeachers'] * 100) : 0; ?>%;" aria-valuenow="<?php echo ($reportData['totalTeachers'] > 0) ? ($reportData['availableTeachers'] / $reportData['totalTeachers'] * 100) : 0; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stat-card">
                                <div class="value"><?php echo $reportData['unavailableTeachers']; ?></div>
                                <div class="label">Tuteurs non disponibles</div>
                                <div class="progress mt-2">
                                    <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo ($reportData['totalTeachers'] > 0) ? ($reportData['unavailableTeachers'] / $reportData['totalTeachers'] * 100) : 0; ?>%;" aria-valuenow="<?php echo ($reportData['totalTeachers'] > 0) ? ($reportData['unavailableTeachers'] / $reportData['totalTeachers'] * 100) : 0; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stat-card">
                                <div class="value"><?php echo array_sum(array_column($reportData['workloadStats'], 'current_students')); ?></div>
                                <div class="label">Étudiants encadrés</div>
                                <div class="progress mt-2">
                                    <div class="progress-bar bg-info" role="progressbar" style="width: 100%;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Teacher Distribution Charts -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Répartition par disponibilité</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="availabilityChart"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Répartition par charge de travail</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="workloadChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Departments and Specialties -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Répartition par département</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="departmentChart"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Répartition par spécialité</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="specialtyChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Detailed Statistics -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title">Statistiques détaillées</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Catégorie</th>
                                            <th>Nombre</th>
                                            <th>Pourcentage</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Tuteurs disponibles</td>
                                            <td><?php echo $reportData['availableTeachers']; ?></td>
                                            <td><?php echo $reportData['totalTeachers'] > 0 ? number_format(($reportData['availableTeachers'] / $reportData['totalTeachers']) * 100, 1) : 0; ?>%</td>
                                        </tr>
                                        <tr>
                                            <td>Tuteurs non disponibles</td>
                                            <td><?php echo $reportData['unavailableTeachers']; ?></td>
                                            <td><?php echo $reportData['totalTeachers'] > 0 ? number_format(($reportData['unavailableTeachers'] / $reportData['totalTeachers']) * 100, 1) : 0; ?>%</td>
                                        </tr>
                                        <tr>
                                            <td>Tuteurs à charge faible (&lt; 33%)</td>
                                            <td><?php echo $reportData['lowWorkload']; ?></td>
                                            <td><?php echo $reportData['totalTeachers'] > 0 ? number_format(($reportData['lowWorkload'] / $reportData['totalTeachers']) * 100, 1) : 0; ?>%</td>
                                        </tr>
                                        <tr>
                                            <td>Tuteurs à charge moyenne (33% - 66%)</td>
                                            <td><?php echo $reportData['mediumWorkload']; ?></td>
                                            <td><?php echo $reportData['totalTeachers'] > 0 ? number_format(($reportData['mediumWorkload'] / $reportData['totalTeachers']) * 100, 1) : 0; ?>%</td>
                                        </tr>
                                        <tr>
                                            <td>Tuteurs à charge élevée (67% - 99%)</td>
                                            <td><?php echo $reportData['highWorkload']; ?></td>
                                            <td><?php echo $reportData['totalTeachers'] > 0 ? number_format(($reportData['highWorkload'] / $reportData['totalTeachers']) * 100, 1) : 0; ?>%</td>
                                        </tr>
                                        <tr>
                                            <td>Tuteurs à pleine capacité (100%)</td>
                                            <td><?php echo $reportData['fullWorkload']; ?></td>
                                            <td><?php echo $reportData['totalTeachers'] > 0 ? number_format(($reportData['fullWorkload'] / $reportData['totalTeachers']) * 100, 1) : 0; ?>%</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Workload Table -->
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Charge de travail des tuteurs</h5>
                            <button class="btn btn-sm btn-outline-primary" id="toggleWorkload">
                                <i class="bi bi-arrows-expand"></i> Afficher tout
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Tuteur</th>
                                            <th>Département</th>
                                            <th>Capacité max</th>
                                            <th>Étudiants actuels</th>
                                            <th>Charge (%)</th>
                                            <th>Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody id="workloadTable">
                                        <?php 
                                        $counter = 0;
                                        foreach ($reportData['workloadStats'] as $stat): 
                                            $isHidden = $counter >= 5;
                                            $percentage = $stat['workload_percentage'];
                                            $statusClass = '';
                                            $statusText = '';
                                            
                                            if ($percentage >= 100) {
                                                $statusClass = 'bg-danger';
                                                $statusText = 'Complet';
                                            } elseif ($percentage > 66) {
                                                $statusClass = 'bg-warning';
                                                $statusText = 'Charge élevée';
                                            } elseif ($percentage >= 33) {
                                                $statusClass = 'bg-info';
                                                $statusText = 'Charge moyenne';
                                            } else {
                                                $statusClass = 'bg-success';
                                                $statusText = 'Charge faible';
                                            }
                                        ?>
                                        <tr class="<?php echo $isHidden ? 'workload-row-hidden' : ''; ?>" <?php echo $isHidden ? 'style="display: none;"' : ''; ?>>
                                            <td><?php echo h($stat['first_name'] . ' ' . $stat['last_name']); ?></td>
                                            <td><?php echo h($stat['department']); ?></td>
                                            <td><?php echo $stat['max_students']; ?></td>
                                            <td><?php echo $stat['current_students']; ?></td>
                                            <td>
                                                <div class="progress">
                                                    <div class="progress-bar <?php echo $statusClass; ?>" role="progressbar" style="width: <?php echo min(100, $percentage); ?>%;" aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100"><?php echo number_format($percentage, 1); ?>%</div>
                                                </div>
                                            </td>
                                            <td><span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span></td>
                                        </tr>
                                        <?php 
                                        $counter++;
                                        endforeach; 
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Export Options -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title">Exporter les rapports</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <i class="bi bi-file-earmark-text fs-1 mb-3 text-primary"></i>
                                            <h5 class="card-title">Rapport complet</h5>
                                            <p class="card-text">Exporter toutes les statistiques et graphiques sur les tuteurs</p>
                                            <a href="/tutoring/api/export/teachers.php?format=pdf&report=full" target="_blank" class="btn btn-primary">
                                                <i class="bi bi-download me-1"></i>Télécharger PDF
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <i class="bi bi-bar-chart fs-1 mb-3 text-success"></i>
                                            <h5 class="card-title">Statistiques seulement</h5>
                                            <p class="card-text">Exporter uniquement les données statistiques</p>
                                            <a href="/tutoring/api/export/teachers.php?format=excel&report=stats" target="_blank" class="btn btn-success">
                                                <i class="bi bi-download me-1"></i>Télécharger Excel
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <i class="bi bi-images fs-1 mb-3 text-info"></i>
                                            <h5 class="card-title">Graphiques seulement</h5>
                                            <p class="card-text">Exporter uniquement les graphiques et visualisations</p>
                                            <a href="/tutoring/api/export/teachers.php?format=pdf&report=charts" target="_blank" class="btn btn-info">
                                                <i class="bi bi-download me-1"></i>Télécharger PDF
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php elseif ($reportType === 'internships'): ?>
                <!-- Internship Reports Section -->
                <div class="report-section">
                    <h3 class="mb-4">Rapport sur les stages</h3>
                    
                    <!-- Overview Stats -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card stat-card">
                                <div class="value"><?php echo $reportData['totalInternships']; ?></div>
                                <div class="label">Stages au total</div>
                                <div class="progress mt-2">
                                    <div class="progress-bar" role="progressbar" style="width: 100%;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stat-card">
                                <div class="value"><?php echo $reportData['statusStats']['available'] ?? 0; ?></div>
                                <div class="label">Stages disponibles</div>
                                <div class="progress mt-2">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo ($reportData['totalInternships'] > 0) ? (($reportData['statusStats']['available'] ?? 0) / $reportData['totalInternships'] * 100) : 0; ?>%;" aria-valuenow="<?php echo ($reportData['totalInternships'] > 0) ? (($reportData['statusStats']['available'] ?? 0) / $reportData['totalInternships'] * 100) : 0; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stat-card">
                                <div class="value"><?php echo $reportData['statusStats']['assigned'] ?? 0; ?></div>
                                <div class="label">Stages affectés</div>
                                <div class="progress mt-2">
                                    <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo ($reportData['totalInternships'] > 0) ? (($reportData['statusStats']['assigned'] ?? 0) / $reportData['totalInternships'] * 100) : 0; ?>%;" aria-valuenow="<?php echo ($reportData['totalInternships'] > 0) ? (($reportData['statusStats']['assigned'] ?? 0) / $reportData['totalInternships'] * 100) : 0; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stat-card">
                                <div class="value"><?php echo $reportData['statusStats']['completed'] ?? 0; ?></div>
                                <div class="label">Stages terminés</div>
                                <div class="progress mt-2">
                                    <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo ($reportData['totalInternships'] > 0) ? (($reportData['statusStats']['completed'] ?? 0) / $reportData['totalInternships'] * 100) : 0; ?>%;" aria-valuenow="<?php echo ($reportData['totalInternships'] > 0) ? (($reportData['statusStats']['completed'] ?? 0) / $reportData['totalInternships'] * 100) : 0; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pie Charts for Status and Timeline Distribution -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title">Répartition des stages</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th class="text-center">Par statut</th>
                                            <th class="text-center">Temporelle</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td width="50%">
                                                <div style="height: 200px; position: relative;">
                                                    <canvas id="internshipStatusChart"></canvas>
                                                </div>
                                            </td>
                                            <td width="50%">
                                                <div style="height: 200px; position: relative;">
                                                    <canvas id="internshipTimelineChart"></canvas>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Bar Charts for Domains and Companies -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Répartition par domaine</h5>
                                </div>
                                <div class="card-body">
                                    <div style="height: 350px; position: relative; margin-bottom: 20px;">
                                        <canvas id="internshipDomainChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Répartition par entreprise</h5>
                                </div>
                                <div class="card-body">
                                    <div style="height: 350px; position: relative; margin-bottom: 20px;">
                                        <canvas id="internshipCompanyChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Skills Chart -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title">Compétences les plus demandées</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="internshipSkillsChart" height="200"></canvas>
                        </div>
                    </div>

                    <!-- Detailed Statistics -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title">Statistiques détaillées</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Catégorie</th>
                                            <th>Nombre</th>
                                            <th>Pourcentage</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($reportData['statusStats'] as $status => $count): ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-<?php echo $reportData['statusClasses'][$status] ?? 'secondary'; ?>">
                                                    <?php echo $reportData['statusLabels'][$status] ?? ucfirst($status); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $count; ?></td>
                                            <td><?php echo $reportData['totalInternships'] > 0 ? number_format(($count / $reportData['totalInternships']) * 100, 1) : 0; ?>%</td>
                                        </tr>
                                        <?php endforeach; ?>
                                        
                                        <tr><td colspan="3" class="table-light"><strong>Répartition temporelle</strong></td></tr>
                                        
                                        <tr>
                                            <td>Stages à venir</td>
                                            <td><?php echo $reportData['timelineStats']['upcoming'] ?? 0; ?></td>
                                            <td><?php echo $reportData['totalInternships'] > 0 ? number_format((($reportData['timelineStats']['upcoming'] ?? 0) / $reportData['totalInternships']) * 100, 1) : 0; ?>%</td>
                                        </tr>
                                        <tr>
                                            <td>Stages en cours</td>
                                            <td><?php echo $reportData['timelineStats']['current'] ?? 0; ?></td>
                                            <td><?php echo $reportData['totalInternships'] > 0 ? number_format((($reportData['timelineStats']['current'] ?? 0) / $reportData['totalInternships']) * 100, 1) : 0; ?>%</td>
                                        </tr>
                                        <tr>
                                            <td>Stages terminés</td>
                                            <td><?php echo $reportData['timelineStats']['past'] ?? 0; ?></td>
                                            <td><?php echo $reportData['totalInternships'] > 0 ? number_format((($reportData['timelineStats']['past'] ?? 0) / $reportData['totalInternships']) * 100, 1) : 0; ?>%</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Top Domains and Companies -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">Top domaines</h5>
                                    <button class="btn btn-sm btn-outline-primary" id="toggleDomains">
                                        <i class="bi bi-arrows-expand"></i> Afficher tout
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Domaine</th>
                                                    <th>Nombre de stages</th>
                                                    <th>Pourcentage</th>
                                                </tr>
                                            </thead>
                                            <tbody id="domainsTable">
                                                <?php 
                                                $counter = 0;
                                                foreach ($reportData['domainsStats'] as $domain => $count): 
                                                    $isHidden = $counter >= 5;
                                                ?>
                                                <tr class="<?php echo $isHidden ? 'domain-row-hidden' : ''; ?>" <?php echo $isHidden ? 'style="display: none;"' : ''; ?>>
                                                    <td><?php echo h($domain); ?></td>
                                                    <td><?php echo $count; ?></td>
                                                    <td><?php echo $reportData['totalInternships'] > 0 ? number_format(($count / $reportData['totalInternships']) * 100, 1) : 0; ?>%</td>
                                                </tr>
                                                <?php 
                                                $counter++;
                                                endforeach; 
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">Top entreprises</h5>
                                    <button class="btn btn-sm btn-outline-primary" id="toggleCompanies">
                                        <i class="bi bi-arrows-expand"></i> Afficher tout
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Entreprise</th>
                                                    <th>Nombre de stages</th>
                                                    <th>Pourcentage</th>
                                                </tr>
                                            </thead>
                                            <tbody id="companiesTable">
                                                <?php 
                                                $counter = 0;
                                                foreach ($reportData['companyStats'] as $company => $count): 
                                                    $isHidden = $counter >= 5;
                                                ?>
                                                <tr class="<?php echo $isHidden ? 'company-row-hidden' : ''; ?>" <?php echo $isHidden ? 'style="display: none;"' : ''; ?>>
                                                    <td><?php echo h($company); ?></td>
                                                    <td><?php echo $count; ?></td>
                                                    <td><?php echo $reportData['totalInternships'] > 0 ? number_format(($count / $reportData['totalInternships']) * 100, 1) : 0; ?>%</td>
                                                </tr>
                                                <?php 
                                                $counter++;
                                                endforeach; 
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Export Options -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title">Exporter les rapports</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <i class="bi bi-file-earmark-text fs-1 mb-3 text-primary"></i>
                                            <h5 class="card-title">Rapport complet</h5>
                                            <p class="card-text">Exporter toutes les statistiques et graphiques sur les stages</p>
                                            <a href="/tutoring/api/export/internships.php?format=pdf&report=full" target="_blank" class="btn btn-primary">
                                                <i class="bi bi-download me-1"></i>Télécharger PDF
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <i class="bi bi-bar-chart fs-1 mb-3 text-success"></i>
                                            <h5 class="card-title">Statistiques seulement</h5>
                                            <p class="card-text">Exporter uniquement les données statistiques</p>
                                            <a href="/tutoring/api/export/internships.php?format=excel&report=stats" target="_blank" class="btn btn-success">
                                                <i class="bi bi-download me-1"></i>Télécharger Excel
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <i class="bi bi-images fs-1 mb-3 text-info"></i>
                                            <h5 class="card-title">Graphiques seulement</h5>
                                            <p class="card-text">Exporter uniquement les graphiques et visualisations</p>
                                            <a href="/tutoring/api/export/internships.php?format=pdf&report=charts" target="_blank" class="btn btn-info">
                                                <i class="bi bi-download me-1"></i>Télécharger PDF
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php elseif ($reportType === 'assignments'): ?>
                <!-- Assignment Reports Section -->
                <div class="report-section">
                    <h3 class="mb-4">Rapport sur les affectations</h3>
                    
                    <!-- Overview Stats -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card stat-card">
                                <div class="value"><?php echo $reportData['totalAssignments']; ?></div>
                                <div class="label">Affectations total</div>
                                <div class="progress mt-2">
                                    <div class="progress-bar" role="progressbar" style="width: 100%;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stat-card">
                                <div class="value"><?php echo $reportData['statusStats']['confirmed'] ?? 0; ?></div>
                                <div class="label">Affectations confirmées</div>
                                <div class="progress mt-2">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo ($reportData['totalAssignments'] > 0) ? (($reportData['statusStats']['confirmed'] ?? 0) / $reportData['totalAssignments'] * 100) : 0; ?>%;" aria-valuenow="<?php echo ($reportData['totalAssignments'] > 0) ? (($reportData['statusStats']['confirmed'] ?? 0) / $reportData['totalAssignments'] * 100) : 0; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stat-card">
                                <div class="value"><?php echo $reportData['statusStats']['pending'] ?? 0; ?></div>
                                <div class="label">Affectations en attente</div>
                                <div class="progress mt-2">
                                    <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo ($reportData['totalAssignments'] > 0) ? (($reportData['statusStats']['pending'] ?? 0) / $reportData['totalAssignments'] * 100) : 0; ?>%;" aria-valuenow="<?php echo ($reportData['totalAssignments'] > 0) ? (($reportData['statusStats']['pending'] ?? 0) / $reportData['totalAssignments'] * 100) : 0; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stat-card">
                                <div class="value"><?php echo $reportData['statusStats']['completed'] ?? 0; ?></div>
                                <div class="label">Affectations terminées</div>
                                <div class="progress mt-2">
                                    <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo ($reportData['totalAssignments'] > 0) ? (($reportData['statusStats']['completed'] ?? 0) / $reportData['totalAssignments'] * 100) : 0; ?>%;" aria-valuenow="<?php echo ($reportData['totalAssignments'] > 0) ? (($reportData['statusStats']['completed'] ?? 0) / $reportData['totalAssignments'] * 100) : 0; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pie Charts for Status and Satisfaction Distribution -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title">Répartition des affectations</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th class="text-center">Par statut</th>
                                            <th class="text-center">Par satisfaction</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td width="50%">
                                                <div style="height: 200px; position: relative;">
                                                    <canvas id="assignmentStatusChart"></canvas>
                                                </div>
                                            </td>
                                            <td width="50%">
                                                <div style="height: 200px; position: relative;">
                                                    <canvas id="assignmentSatisfactionChart"></canvas>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Bar Charts for Departments and Teachers -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Répartition par département</h5>
                                </div>
                                <div class="card-body">
                                    <div style="height: 350px; position: relative; margin-bottom: 20px;">
                                        <canvas id="assignmentDepartmentChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title">Top tuteurs par nombre d'affectations</h5>
                                </div>
                                <div class="card-body">
                                    <div style="height: 350px; position: relative; margin-bottom: 20px;">
                                        <canvas id="assignmentTeacherChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Timeline Chart -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title">Répartition temporelle des affectations</h5>
                        </div>
                        <div class="card-body">
                            <div style="height: 300px; position: relative;">
                                <canvas id="assignmentTimelineChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Detailed Statistics -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title">Statistiques détaillées</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Catégorie</th>
                                            <th>Nombre</th>
                                            <th>Pourcentage</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($reportData['statusStats'] as $status => $count): ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-<?php echo $reportData['statusClasses'][$status] ?? 'secondary'; ?>">
                                                    <?php echo $reportData['statusLabels'][$status] ?? ucfirst($status); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $count; ?></td>
                                            <td><?php echo $reportData['totalAssignments'] > 0 ? number_format(($count / $reportData['totalAssignments']) * 100, 1) : 0; ?>%</td>
                                        </tr>
                                        <?php endforeach; ?>
                                        
                                        <tr><td colspan="3" class="table-light"><strong>Répartition par compatibilité</strong></td></tr>
                                        
                                        <tr>
                                            <td>Compatibilité élevée (>80%)</td>
                                            <td><?php echo $reportData['compatibilityStats']['high'] ?? 0; ?></td>
                                            <td><?php echo $reportData['totalAssignments'] > 0 ? number_format((($reportData['compatibilityStats']['high'] ?? 0) / $reportData['totalAssignments']) * 100, 1) : 0; ?>%</td>
                                        </tr>
                                        <tr>
                                            <td>Compatibilité moyenne (50-80%)</td>
                                            <td><?php echo $reportData['compatibilityStats']['medium'] ?? 0; ?></td>
                                            <td><?php echo $reportData['totalAssignments'] > 0 ? number_format((($reportData['compatibilityStats']['medium'] ?? 0) / $reportData['totalAssignments']) * 100, 1) : 0; ?>%</td>
                                        </tr>
                                        <tr>
                                            <td>Compatibilité faible (<50%)</td>
                                            <td><?php echo $reportData['compatibilityStats']['low'] ?? 0; ?></td>
                                            <td><?php echo $reportData['totalAssignments'] > 0 ? number_format((($reportData['compatibilityStats']['low'] ?? 0) / $reportData['totalAssignments']) * 100, 1) : 0; ?>%</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Export Options -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title">Exporter les rapports</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <i class="bi bi-file-earmark-text fs-1 mb-3 text-primary"></i>
                                            <h5 class="card-title">Rapport complet</h5>
                                            <p class="card-text">Exporter toutes les statistiques et graphiques sur les affectations</p>
                                            <a href="/tutoring/api/export/assignments.php?format=pdf&report=full" target="_blank" class="btn btn-primary">
                                                <i class="bi bi-download me-1"></i>Télécharger PDF
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <i class="bi bi-bar-chart fs-1 mb-3 text-success"></i>
                                            <h5 class="card-title">Statistiques seulement</h5>
                                            <p class="card-text">Exporter uniquement les données statistiques</p>
                                            <a href="/tutoring/api/export/assignments.php?format=excel&report=stats" target="_blank" class="btn btn-success">
                                                <i class="bi bi-download me-1"></i>Télécharger Excel
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <i class="bi bi-images fs-1 mb-3 text-info"></i>
                                            <h5 class="card-title">Graphiques seulement</h5>
                                            <p class="card-text">Exporter uniquement les graphiques et visualisations</p>
                                            <a href="/tutoring/api/export/assignments.php?format=pdf&report=charts" target="_blank" class="btn btn-info">
                                                <i class="bi bi-download me-1"></i>Télécharger PDF
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php elseif ($reportType === 'evaluations'): ?>
                <!-- Evaluation Reports Section -->
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>Les rapports détaillés sur les évaluations seront disponibles prochainement.
                </div>
            <?php else: ?>
                <!-- General Report Section -->
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>Veuillez sélectionner un type de rapport spécifique pour voir les statistiques détaillées.
                </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-3">
            <!-- Date Range Selector -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title">Période de rapport</h5>
                </div>
                <div class="card-body">
                    <form id="periodForm" method="GET" action="">
                        <input type="hidden" name="type" value="<?php echo h($reportType); ?>">
                        
                        <div class="mb-3">
                            <label for="period_start" class="form-label">Date de début</label>
                            <input type="date" class="form-control" id="period_start" name="period_start" value="<?php echo h($periodStart); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="period_end" class="form-label">Date de fin</label>
                            <input type="date" class="form-control" id="period_end" name="period_end" value="<?php echo h($periodEnd); ?>">
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-filter me-2"></i>Appliquer
                            </button>
                        </div>
                        
                        <hr>
                        
                        <div class="btn-group w-100 mb-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-period="this-month">Mois courant</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-period="last-month">Mois précédent</button>
                        </div>
                        
                        <div class="btn-group w-100">
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-period="this-year">Année courante</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-period="last-year">Année précédente</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Report Options -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title">Options de rapport</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary" id="printReport">
                            <i class="bi bi-printer me-2"></i>Imprimer le rapport
                        </button>
                        <a href="?type=<?php echo h($reportType); ?>&format=pdf" class="btn btn-outline-primary" target="_blank">
                            <i class="bi bi-file-earmark-pdf me-2"></i>Exporter en PDF
                        </a>
                        <a href="?type=<?php echo h($reportType); ?>&format=excel" class="btn btn-outline-primary" target="_blank">
                            <i class="bi bi-file-earmark-excel me-2"></i>Exporter en Excel
                        </a>
                        <a href="#" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#emailReportModal">
                            <i class="bi bi-envelope me-2"></i>Envoyer par email
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Report Navigation -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Navigation des rapports</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <a href="?type=general" class="list-group-item list-group-item-action <?php echo $reportType === 'general' ? 'active' : ''; ?>">
                            <i class="bi bi-house me-2"></i>Général
                        </a>
                        <a href="?type=students" class="list-group-item list-group-item-action <?php echo $reportType === 'students' ? 'active' : ''; ?>">
                            <i class="bi bi-mortarboard me-2"></i>Étudiants
                        </a>
                        <a href="?type=teachers" class="list-group-item list-group-item-action <?php echo $reportType === 'teachers' ? 'active' : ''; ?>">
                            <i class="bi bi-person-badge me-2"></i>Tuteurs
                        </a>
                        <a href="?type=internships" class="list-group-item list-group-item-action <?php echo $reportType === 'internships' ? 'active' : ''; ?>">
                            <i class="bi bi-briefcase me-2"></i>Stages
                        </a>
                        <a href="?type=assignments" class="list-group-item list-group-item-action <?php echo $reportType === 'assignments' ? 'active' : ''; ?>">
                            <i class="bi bi-diagram-3 me-2"></i>Affectations
                        </a>
                        <a href="?type=evaluations" class="list-group-item list-group-item-action <?php echo $reportType === 'evaluations' ? 'active' : ''; ?>">
                            <i class="bi bi-star me-2"></i>Évaluations
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Email Report Modal -->
<div class="modal fade" id="emailReportModal" tabindex="-1" aria-labelledby="emailReportModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="emailReportModalLabel">Envoyer le rapport par email</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="emailReportForm">
                    <div class="mb-3">
                        <label for="emailTo" class="form-label">Destinataire</label>
                        <input type="email" class="form-control" id="emailTo" placeholder="nom@exemple.com">
                    </div>
                    <div class="mb-3">
                        <label for="emailSubject" class="form-label">Sujet</label>
                        <input type="text" class="form-control" id="emailSubject" value="Rapport sur les étudiants - <?php echo date('d/m/Y'); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="emailMessage" class="form-label">Message</label>
                        <textarea class="form-control" id="emailMessage" rows="4">Veuillez trouver ci-joint le rapport sur les étudiants généré le <?php echo date('d/m/Y à H:i'); ?>.</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Format du rapport</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="reportFormat" id="reportFormatPdf" value="pdf" checked>
                            <label class="btn btn-outline-primary" for="reportFormatPdf">PDF</label>
                            
                            <input type="radio" class="btn-check" name="reportFormat" id="reportFormatExcel" value="excel">
                            <label class="btn btn-outline-primary" for="reportFormatExcel">Excel</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary">Envoyer</button>
            </div>
        </div>
    </div>
</div>

<script>
// Graphiques pour les rapports sur les étudiants
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($reportType === 'students'): ?>
    // Données pour les graphiques
    const statusData = {
        labels: ['Actifs', 'Diplômés', 'Suspendus'],
        datasets: [{
            label: 'Nombre d\'étudiants',
            data: [<?php echo $reportData['activeStudents']; ?>, <?php echo $reportData['graduatedStudents']; ?>, <?php echo $reportData['suspendedStudents']; ?>],
            backgroundColor: ['rgba(40, 167, 69, 0.7)', 'rgba(23, 162, 184, 0.7)', 'rgba(255, 193, 7, 0.7)'],
            borderColor: ['rgb(40, 167, 69)', 'rgb(23, 162, 184)', 'rgb(255, 193, 7)'],
            borderWidth: 1
        }]
    };

    const assignmentData = {
        labels: ['Avec tuteur', 'Sans tuteur'],
        datasets: [{
            label: 'Nombre d\'étudiants',
            data: [<?php echo $reportData['studentsWithAssignment']; ?>, <?php echo $reportData['studentsWithoutAssignment']; ?>],
            backgroundColor: ['rgba(0, 123, 255, 0.7)', 'rgba(108, 117, 125, 0.7)'],
            borderColor: ['rgb(0, 123, 255)', 'rgb(108, 117, 125)'],
            borderWidth: 1
        }]
    };

    // Préparer les données pour les programmes (limiter à 5 pour lisibilité)
    const programLabels = [];
    const programValues = [];
    let counter = 0;
    
    for (const [program, count] of Object.entries(<?php echo json_encode($reportData['programs']); ?>)) {
        if (counter < 5) {
            programLabels.push(program);
            programValues.push(count);
            counter++;
        }
    }
    
    const programData = {
        labels: programLabels,
        datasets: [{
            label: 'Nombre d\'étudiants',
            data: programValues,
            backgroundColor: 'rgba(54, 162, 235, 0.7)',
            borderColor: 'rgb(54, 162, 235)',
            borderWidth: 1
        }]
    };

    // Préparer les données pour les niveaux
    const levelLabels = [];
    const levelValues = [];
    
    for (const [level, count] of Object.entries(<?php echo json_encode($reportData['levels']); ?>)) {
        levelLabels.push(level);
        levelValues.push(count);
    }
    
    const levelData = {
        labels: levelLabels,
        datasets: [{
            label: 'Nombre d\'étudiants',
            data: levelValues,
            backgroundColor: 'rgba(153, 102, 255, 0.7)',
            borderColor: 'rgb(153, 102, 255)',
            borderWidth: 1
        }]
    };

    // Créer les graphiques
    const statusChart = new Chart(
        document.getElementById('statusChart'),
        {
            type: 'pie',
            data: statusData,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right',
                    },
                    title: {
                        display: true,
                        text: 'Répartition par statut'
                    }
                }
            }
        }
    );

    const assignmentChart = new Chart(
        document.getElementById('assignmentChart'),
        {
            type: 'pie',
            data: assignmentData,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right',
                    },
                    title: {
                        display: true,
                        text: 'Étudiants avec/sans tuteur'
                    }
                }
            }
        }
    );

    const programChart = new Chart(
        document.getElementById('programChart'),
        {
            type: 'bar',
            data: programData,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false,
                    },
                    title: {
                        display: true,
                        text: 'Top 5 des programmes d\'études'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        }
    );

    const levelChart = new Chart(
        document.getElementById('levelChart'),
        {
            type: 'bar',
            data: levelData,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false,
                    },
                    title: {
                        display: true,
                        text: 'Répartition par niveau d\'études'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        }
    );
    <?php endif; ?>
    
    <?php if ($reportType === 'internships'): ?>
    // Données pour les graphiques des stages
    const statusLabels = [];
    const statusData = [];
    const statusColors = [];
    const statusBorderColors = [];
    
    <?php 
    foreach ($reportData['statusStats'] as $status => $count):
        $label = $reportData['statusLabels'][$status] ?? ucfirst($status);
        $colorClass = $reportData['statusClasses'][$status] ?? 'secondary';
        $bgColor = '';
        $borderColor = '';
        
        switch ($colorClass) {
            case 'success':
                $bgColor = 'rgba(40, 167, 69, 0.7)';
                $borderColor = 'rgb(40, 167, 69)';
                break;
            case 'primary':
                $bgColor = 'rgba(0, 123, 255, 0.7)';
                $borderColor = 'rgb(0, 123, 255)';
                break;
            case 'info':
                $bgColor = 'rgba(23, 162, 184, 0.7)';
                $borderColor = 'rgb(23, 162, 184)';
                break;
            case 'warning':
                $bgColor = 'rgba(255, 193, 7, 0.7)';
                $borderColor = 'rgb(255, 193, 7)';
                break;
            case 'danger':
                $bgColor = 'rgba(220, 53, 69, 0.7)';
                $borderColor = 'rgb(220, 53, 69)';
                break;
            default:
                $bgColor = 'rgba(108, 117, 125, 0.7)';
                $borderColor = 'rgb(108, 117, 125)';
                break;
        }
    ?>
    statusLabels.push('<?php echo addslashes($label); ?>');
    statusData.push(<?php echo $count; ?>);
    statusColors.push('<?php echo $bgColor; ?>');
    statusBorderColors.push('<?php echo $borderColor; ?>');
    <?php endforeach; ?>
    
    const timelineLabels = ['À venir', 'En cours', 'Terminés'];
    const timelineData = [
        <?php echo $reportData['timelineStats']['upcoming'] ?? 0; ?>,
        <?php echo $reportData['timelineStats']['current'] ?? 0; ?>,
        <?php echo $reportData['timelineStats']['past'] ?? 0; ?>
    ];
    const timelineColors = [
        'rgba(52, 152, 219, 0.7)',
        'rgba(46, 204, 113, 0.7)',
        'rgba(149, 165, 166, 0.7)'
    ];
    const timelineBorderColors = [
        'rgb(52, 152, 219)',
        'rgb(46, 204, 113)',
        'rgb(149, 165, 166)'
    ];
    
    // Préparation des données pour les domaines (limiter à 10 pour lisibilité)
    const domainLabels = [];
    const domainData = [];
    let domainCounter = 0;
    
    <?php
    foreach (array_slice($reportData['domainsStats'], 0, 10) as $domain => $count):
    ?>
    domainLabels.push('<?php echo addslashes($domain); ?>');
    domainData.push(<?php echo $count; ?>);
    <?php endforeach; ?>
    
    // Préparation des données pour les entreprises (limiter à 10 pour lisibilité)
    const companyLabels = [];
    const companyData = [];
    
    <?php
    foreach (array_slice($reportData['companyStats'], 0, 10) as $company => $count):
    ?>
    companyLabels.push('<?php echo addslashes($company); ?>');
    companyData.push(<?php echo $count; ?>);
    <?php endforeach; ?>
    
    // Préparation des données pour les compétences (limiter à 15 pour lisibilité)
    const skillLabels = [];
    const skillData = [];
    
    <?php
    foreach (array_slice($reportData['skillStats'], 0, 15) as $skill => $count):
    ?>
    skillLabels.push('<?php echo addslashes($skill); ?>');
    skillData.push(<?php echo $count; ?>);
    <?php endforeach; ?>
    
    // Créer les graphiques
    const statusChart = new Chart(
        document.getElementById('internshipStatusChart'),
        {
            type: 'pie',
            data: {
                labels: statusLabels,
                datasets: [{
                    label: 'Nombre de stages',
                    data: statusData,
                    backgroundColor: statusColors,
                    borderColor: statusBorderColors,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 10,
                            font: {
                                size: 8
                            },
                            padding: 3
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((acc, val) => acc + val, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        }
    );
    
    const timelineChart = new Chart(
        document.getElementById('internshipTimelineChart'),
        {
            type: 'pie',
            data: {
                labels: timelineLabels,
                datasets: [{
                    label: 'Nombre de stages',
                    data: timelineData,
                    backgroundColor: timelineColors,
                    borderColor: timelineBorderColors,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 10,
                            font: {
                                size: 8
                            },
                            padding: 3
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((acc, val) => acc + val, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        }
    );
    
    const domainChart = new Chart(
        document.getElementById('internshipDomainChart'),
        {
            type: 'bar',
            data: {
                labels: domainLabels,
                datasets: [{
                    label: 'Nombre de stages',
                    data: domainData,
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgb(54, 162, 235)',
                    borderWidth: 1
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false,
                    },
                    title: {
                        display: true,
                        text: 'Top domaines de stages',
                        padding: {
                            top: 10,
                            bottom: 10
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    },
                    y: {
                        ticks: {
                            font: {
                                size: 10
                            }
                        }
                    }
                }
            }
        }
    );
    
    const companyChart = new Chart(
        document.getElementById('internshipCompanyChart'),
        {
            type: 'bar',
            data: {
                labels: companyLabels,
                datasets: [{
                    label: 'Nombre de stages',
                    data: companyData,
                    backgroundColor: 'rgba(153, 102, 255, 0.7)',
                    borderColor: 'rgb(153, 102, 255)',
                    borderWidth: 1
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false,
                    },
                    title: {
                        display: true,
                        text: 'Top entreprises',
                        padding: {
                            top: 10,
                            bottom: 10
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    },
                    y: {
                        ticks: {
                            font: {
                                size: 10
                            }
                        }
                    }
                }
            }
        }
    );
    
    const skillsChart = new Chart(
        document.getElementById('internshipSkillsChart'),
        {
            type: 'bar',
            data: {
                labels: skillLabels,
                datasets: [{
                    label: 'Fréquence',
                    data: skillData,
                    backgroundColor: 'rgba(255, 159, 64, 0.7)',
                    borderColor: 'rgb(255, 159, 64)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false,
                    },
                    title: {
                        display: true,
                        text: 'Compétences les plus demandées'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        }
    );
    
    // Gestion des tableaux expandables
    const toggleDomainsBtn = document.getElementById('toggleDomains');
    const domainRows = document.querySelectorAll('.domain-row-hidden');
    
    if (toggleDomainsBtn) {
        toggleDomainsBtn.addEventListener('click', function() {
            const isExpanded = this.getAttribute('data-expanded') === 'true';
            
            domainRows.forEach(row => {
                row.style.display = isExpanded ? 'none' : 'table-row';
            });
            
            this.innerHTML = isExpanded 
                ? '<i class="bi bi-arrows-expand"></i> Afficher tout'
                : '<i class="bi bi-arrows-collapse"></i> Masquer';
                
            this.setAttribute('data-expanded', isExpanded ? 'false' : 'true');
        });
    }
    
    const toggleCompaniesBtn = document.getElementById('toggleCompanies');
    const companyRows = document.querySelectorAll('.company-row-hidden');
    
    if (toggleCompaniesBtn) {
        toggleCompaniesBtn.addEventListener('click', function() {
            const isExpanded = this.getAttribute('data-expanded') === 'true';
            
            companyRows.forEach(row => {
                row.style.display = isExpanded ? 'none' : 'table-row';
            });
            
            this.innerHTML = isExpanded 
                ? '<i class="bi bi-arrows-expand"></i> Afficher tout'
                : '<i class="bi bi-arrows-collapse"></i> Masquer';
                
            this.setAttribute('data-expanded', isExpanded ? 'false' : 'true');
        });
    }
    <?php endif; ?>
    
    <?php if ($reportType === 'teachers'): ?>
    // Données pour les graphiques des tuteurs
    const availabilityData = {
        labels: ['Disponibles', 'Non disponibles'],
        datasets: [{
            label: 'Nombre de tuteurs',
            data: [<?php echo $reportData['availableTeachers']; ?>, <?php echo $reportData['unavailableTeachers']; ?>],
            backgroundColor: ['rgba(40, 167, 69, 0.7)', 'rgba(255, 193, 7, 0.7)'],
            borderColor: ['rgb(40, 167, 69)', 'rgb(255, 193, 7)'],
            borderWidth: 1
        }]
    };

    const workloadData = {
        labels: ['Faible (<33%)', 'Moyenne (33-66%)', 'Élevée (67-99%)', 'Complète (100%)'],
        datasets: [{
            label: 'Nombre de tuteurs',
            data: [
                <?php echo $reportData['lowWorkload']; ?>, 
                <?php echo $reportData['mediumWorkload']; ?>, 
                <?php echo $reportData['highWorkload']; ?>, 
                <?php echo $reportData['fullWorkload']; ?>
            ],
            backgroundColor: [
                'rgba(40, 167, 69, 0.7)', 
                'rgba(23, 162, 184, 0.7)', 
                'rgba(255, 193, 7, 0.7)', 
                'rgba(220, 53, 69, 0.7)'
            ],
            borderColor: [
                'rgb(40, 167, 69)', 
                'rgb(23, 162, 184)', 
                'rgb(255, 193, 7)', 
                'rgb(220, 53, 69)'
            ],
            borderWidth: 1
        }]
    };

    // Préparer les données pour les départements (limiter à 5 pour lisibilité)
    const departmentLabels = [];
    const departmentValues = [];
    let counter = 0;
    
    for (const [department, count] of Object.entries(<?php echo json_encode($reportData['departments']); ?>)) {
        if (counter < 5) {
            departmentLabels.push(department);
            departmentValues.push(count);
            counter++;
        }
    }
    
    const departmentData = {
        labels: departmentLabels,
        datasets: [{
            label: 'Nombre de tuteurs',
            data: departmentValues,
            backgroundColor: 'rgba(54, 162, 235, 0.7)',
            borderColor: 'rgb(54, 162, 235)',
            borderWidth: 1
        }]
    };

    // Préparer les données pour les spécialités (limiter à 5 pour lisibilité)
    const specialtyLabels = [];
    const specialtyValues = [];
    counter = 0;
    
    for (const [specialty, count] of Object.entries(<?php echo json_encode($reportData['specialties']); ?>)) {
        if (counter < 5) {
            specialtyLabels.push(specialty);
            specialtyValues.push(count);
            counter++;
        }
    }
    
    const specialtyData = {
        labels: specialtyLabels,
        datasets: [{
            label: 'Nombre de tuteurs',
            data: specialtyValues,
            backgroundColor: 'rgba(153, 102, 255, 0.7)',
            borderColor: 'rgb(153, 102, 255)',
            borderWidth: 1
        }]
    };

    // Créer les graphiques pour les tuteurs
    const availabilityChart = new Chart(
        document.getElementById('availabilityChart'),
        {
            type: 'pie',
            data: availabilityData,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right',
                    },
                    title: {
                        display: true,
                        text: 'Répartition par disponibilité'
                    }
                }
            }
        }
    );

    const workloadChart = new Chart(
        document.getElementById('workloadChart'),
        {
            type: 'pie',
            data: workloadData,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right',
                    },
                    title: {
                        display: true,
                        text: 'Répartition par charge de travail'
                    }
                }
            }
        }
    );

    const departmentChart = new Chart(
        document.getElementById('departmentChart'),
        {
            type: 'bar',
            data: departmentData,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false,
                    },
                    title: {
                        display: true,
                        text: 'Top 5 des départements'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        }
    );

    const specialtyChart = new Chart(
        document.getElementById('specialtyChart'),
        {
            type: 'bar',
            data: specialtyData,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false,
                    },
                    title: {
                        display: true,
                        text: 'Top 5 des spécialités'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        }
    );
    
    // Gestion du tableau de charge de travail
    const toggleWorkloadBtn = document.getElementById('toggleWorkload');
    const workloadRows = document.querySelectorAll('.workload-row-hidden');
    
    if (toggleWorkloadBtn) {
        toggleWorkloadBtn.addEventListener('click', function() {
            const isExpanded = this.getAttribute('data-expanded') === 'true';
            
            workloadRows.forEach(row => {
                row.style.display = isExpanded ? 'none' : 'table-row';
            });
            
            this.innerHTML = isExpanded 
                ? '<i class="bi bi-arrows-expand"></i> Afficher tout'
                : '<i class="bi bi-arrows-collapse"></i> Masquer';
                
            this.setAttribute('data-expanded', isExpanded ? 'false' : 'true');
        });
    }
    <?php endif; ?>

    // Gestion du bouton d'impression
    document.getElementById('printReport').addEventListener('click', function() {
        window.print();
    });

    // Gestion des boutons de période prédéfinie
    const periodButtons = document.querySelectorAll('[data-period]');
    const periodStartInput = document.getElementById('period_start');
    const periodEndInput = document.getElementById('period_end');
    
    periodButtons.forEach(button => {
        button.addEventListener('click', function() {
            const period = this.getAttribute('data-period');
            let startDate, endDate;
            const today = new Date();
            
            switch(period) {
                case 'this-month':
                    startDate = new Date(today.getFullYear(), today.getMonth(), 1);
                    endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                    break;
                case 'last-month':
                    startDate = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                    endDate = new Date(today.getFullYear(), today.getMonth(), 0);
                    break;
                case 'this-year':
                    startDate = new Date(today.getFullYear(), 0, 1);
                    endDate = new Date(today.getFullYear(), 11, 31);
                    break;
                case 'last-year':
                    startDate = new Date(today.getFullYear() - 1, 0, 1);
                    endDate = new Date(today.getFullYear() - 1, 11, 31);
                    break;
            }
            
            if (startDate && endDate) {
                periodStartInput.value = startDate.toISOString().split('T')[0];
                periodEndInput.value = endDate.toISOString().split('T')[0];
            }
        });
    });
    
    // Gestion du tableau des programmes
    const toggleProgramsBtn = document.getElementById('togglePrograms');
    const programRows = document.querySelectorAll('.program-row-hidden');
    
    if (toggleProgramsBtn) {
        toggleProgramsBtn.addEventListener('click', function() {
            const isExpanded = this.getAttribute('data-expanded') === 'true';
            
            programRows.forEach(row => {
                row.style.display = isExpanded ? 'none' : 'table-row';
            });
            
            this.innerHTML = isExpanded 
                ? '<i class="bi bi-arrows-expand"></i> Afficher tout'
                : '<i class="bi bi-arrows-collapse"></i> Masquer';
                
            this.setAttribute('data-expanded', isExpanded ? 'false' : 'true');
        });
    }
});
</script>

<style>
/* Style pour l'impression */
@media print {
    .col-lg-3, .breadcrumb, .card-header, .btn, .modal, .footer, nav, .sidebar {
        display: none !important;
    }
    
    .container-fluid, .row, .col-lg-9, .card {
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    
    h2, h3, h4, h5 {
        margin-top: 20px !important;
    }
    
    /* Assurer que les graphiques s'affichent correctement */
    canvas {
        max-width: 100% !important;
        height: auto !important;
    }
}

/* Styles pour les cartes de statistiques */
.stat-card {
    padding: 20px;
    border-radius: 8px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}

.stat-card .value {
    font-size: 2.5rem;
    font-weight: 700;
    color: #2c3e50;
}

.stat-card .label {
    color: #7f8c8d;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 1px;
}
</style>

<?php
// Inclure le pied de page
include_once __DIR__ . '/../common/footer.php';
?>