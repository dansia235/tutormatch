<?php
/**
 * API pour les statistiques générales du tableau de bord
 * Endpoint: /api/dashboard/stats
 * Méthode: GET
 */

require_once __DIR__ . '/../utils.php';
require_once __DIR__ . '/../../controllers/StatisticsController.php';

// Vérifier que l'utilisateur est connecté et a les droits
requireApiAuth();
requireApiRole(['admin', 'coordinator']);

// Instancier le contrôleur de statistiques
$statsController = new StatisticsController($db);

// Récupérer les statistiques générales du tableau de bord
$stats = $statsController->getDashboardStats();

// Préparer les données des cartes statistiques avec format standardisé
$statCards = [
    [
        'title' => 'Étudiants',
        'value' => $stats['totalStudents'] ?? 0,
        'change' => isset($stats['students']['change']) ? $stats['students']['change'] : null,
        'changeType' => isset($stats['students']['changeType']) ? $stats['students']['changeType'] : 'neutral',
        'icon' => 'bi-people',
        'color' => 'primary',
        'link' => '/tutoring/views/admin/students.php',
        'linkText' => 'Gérer les étudiants'
    ],
    [
        'title' => 'Tuteurs',
        'value' => $stats['totalTeachers'] ?? 0,
        'change' => isset($stats['teachers']['change']) ? $stats['teachers']['change'] : null,
        'changeType' => isset($stats['teachers']['changeType']) ? $stats['teachers']['changeType'] : 'neutral',
        'icon' => 'bi-person-badge',
        'color' => 'info',
        'link' => '/tutoring/views/admin/tutors.php',
        'linkText' => 'Gérer les tuteurs'
    ],
    [
        'title' => 'Stages',
        'value' => $stats['totalInternships'] ?? 0,
        'change' => isset($stats['internships']['available']) ? $stats['internships']['available'] . ' disponibles' : '0 disponibles',
        'changeType' => 'info',
        'icon' => 'bi-briefcase',
        'color' => 'success',
        'link' => '/tutoring/views/admin/internships/index.php',
        'linkText' => 'Gérer les stages'
    ],
    [
        'title' => 'Taux d\'affectation',
        'value' => ($stats['assignmentRate'] ?? 0) . '%',
        'change' => isset($stats['assignments']['pending']) ? $stats['assignments']['pending'] . ' en attente' : null,
        'changeType' => 'warning',
        'icon' => 'bi-diagram-3',
        'color' => 'warning',
        'link' => '/tutoring/views/admin/assignments.php',
        'linkText' => 'Gérer les affectations'
    ]
];

// Préparer les données pour les affectations récentes
$recentAssignments = [];
$statusMap = [
    'pending' => 'En attente',
    'confirmed' => 'Confirmé',
    'rejected' => 'Rejeté',
    'completed' => 'Terminé'
];

$statusClass = [
    'pending' => 'warning',
    'confirmed' => 'success',
    'rejected' => 'danger',
    'completed' => 'info'
];

if (!empty($stats['recentAssignments'])) {
    foreach ($stats['recentAssignments'] as $assignment) {
        $statusLabel = $statusMap[$assignment['status']] ?? $assignment['status'];
        $badgeClass = $statusClass[$assignment['status']] ?? 'secondary';
        
        $recentAssignments[] = [
            'id' => $assignment['id'],
            'student' => [
                'id' => $assignment['student_id'],
                'name' => $assignment['student_first_name'] . ' ' . $assignment['student_last_name']
            ],
            'teacher' => [
                'id' => $assignment['teacher_id'],
                'name' => $assignment['teacher_first_name'] . ' ' . $assignment['teacher_last_name']
            ],
            'internship' => [
                'id' => $assignment['internship_id'],
                'title' => $assignment['internship_title'],
                'company' => $assignment['company_name']
            ],
            'status' => [
                'code' => $assignment['status'],
                'label' => $statusLabel,
                'class' => $badgeClass
            ],
            'date' => $assignment['assignment_date'],
            'formatted_date' => date('d/m/Y', strtotime($assignment['assignment_date']))
        ];
    }
}

// Préparer la réponse JSON
$response = [
    'overview' => [
        'total_students' => $stats['totalStudents'] ?? 0,
        'total_teachers' => $stats['totalTeachers'] ?? 0,
        'total_internships' => $stats['totalInternships'] ?? 0,
        'assignment_rate' => $stats['assignmentRate'] ?? 0,
        'active_assignments' => $stats['activeAssignments'] ?? 0,
        'pending_assignments' => $stats['pendingAssignments'] ?? 0
    ],
    'stat_cards' => $statCards,
    'recent_assignments' => $recentAssignments,
    'quick_stats' => [
        'new_students' => $stats['newStudents'] ?? 0,
        'new_internships' => $stats['newInternships'] ?? 0,
        'completed_assignments' => $stats['completedAssignments'] ?? 0,
        'upcoming_meetings' => $stats['upcomingMeetings'] ?? 0
    ]
];

// Envoyer la réponse
sendJsonResponse($response);
?>