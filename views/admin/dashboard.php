<?php
/**
 * Tableau de bord administrateur
 */

// Titre de la page
$pageTitle = 'Tableau de bord';
$currentPage = 'dashboard';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../includes/init.php';

// Vérifier que l'utilisateur est administrateur ou coordinateur
requireRole(['admin', 'coordinator']);

// Charger le contrôleur de statistiques
require_once __DIR__ . '/../../controllers/StatisticsController.php';
$statsController = new StatisticsController($db);

// Obtenir les statistiques
$stats = $statsController->getDashboardStats();
$chartData = $statsController->generateChartData();

// Fonction d'aide pour inclure un fichier avec des variables
function include_with_vars($file, array $vars = []) {
    if (file_exists($file)) {
        // Extraire les variables dans la portée actuelle
        extract($vars);
        
        // Démarrer la mise en mémoire tampon
        ob_start();
        
        // Inclure le fichier
        include $file;
        
        // Retourner la sortie
        return ob_get_clean();
    }
    
    return '';
}

// Préparer les données des cartes statistiques
$statCards = [
    [
        'title' => 'Étudiants',
        'value' => $stats['totalStudents'] ?? 0,
        'changeType' => 'neutral',
        'link' => '/tutoring/views/admin/students.php',
        'linkText' => 'Gérer les étudiants'
    ],
    [
        'title' => 'Tuteurs',
        'value' => $stats['totalTeachers'] ?? 0,
        'changeType' => 'neutral',
        'link' => '/tutoring/views/admin/tutors.php',
        'linkText' => 'Gérer les tuteurs'
    ],
    [
        'title' => 'Stages',
        'value' => $stats['totalInternships'] ?? 0,
        'change' => isset($stats['internships']['available']) ? $stats['internships']['available'] . ' disponibles' : '0 disponibles',
        'changeType' => 'info',
        'link' => '/tutoring/views/admin/internships/index.php',
        'linkText' => 'Gérer les stages'
    ],
    [
        'title' => 'Taux d\'affectation',
        'value' => ($stats['assignmentRate'] ?? 0) . '%',
        'changeType' => 'neutral',
        'link' => '/tutoring/views/admin/assignments.php',
        'linkText' => 'Gérer les affectations'
    ]
];

// Préparer le tableau des affectations récentes
$assignmentHeaders = [
    'student' => 'Étudiant',
    'teacher' => 'Tuteur',
    'internship' => 'Stage',
    'status' => 'Statut',
    'date' => 'Date'
];

$assignmentsData = [];
$statusMap = [
    'pending' => 'En attente',
    'confirmed' => 'Confirmé',
    'rejected' => 'Rejeté',
    'completed' => 'Terminé'
];

$statusClass = [
    'pending' => 'badge-warning',
    'confirmed' => 'badge-success',
    'rejected' => 'badge-danger',
    'completed' => 'badge-info'
];

if (!empty($stats['recentAssignments'])) {
    foreach ($stats['recentAssignments'] as $assignment) {
        $statusLabel = $statusMap[$assignment['status']] ?? $assignment['status'];
        $badgeClass = $statusClass[$assignment['status']] ?? 'badge-secondary';
        
        $assignmentsData[] = [
            'student' => $assignment['student_first_name'] . ' ' . $assignment['student_last_name'],
            'teacher' => $assignment['teacher_first_name'] . ' ' . $assignment['teacher_last_name'],
            'internship' => '<div>' . $assignment['internship_title'] . 
                            '<div class="small text-muted">' . $assignment['company_name'] . '</div></div>',
            'status' => '<span class="badge ' . $badgeClass . '">' . $statusLabel . '</span>',
            'date' => date('d/m/Y', strtotime($assignment['assignment_date']))
        ];
    }
}

// Inclure l'en-tête
include_once __DIR__ . '/../common/header.php';

// Inclure le contenu du dashboard
include __DIR__ . '/dashboard-content-bootstrap.php';

// Inclure le pied de page
include_once __DIR__ . '/../common/footer.php';
?>