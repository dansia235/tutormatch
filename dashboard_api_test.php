<?php
/**
 * Script de test pour l'API du tableau de bord tuteur
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/includes/init.php';

// Vérifier que l'utilisateur est tuteur
requireRole('teacher');

// Récupérer l'ID du tuteur
$teacherModel = new Teacher($db);
$teacher = $teacherModel->getByUserId($_SESSION['user_id']);

if (!$teacher) {
    die("Profil tuteur non trouvé");
}

// Initialiser les modèles nécessaires
$studentModel = new Student($db);
$assignmentModel = new Assignment($db);
$meetingModel = new Meeting($db);
$evaluationModel = new Evaluation($db);
$messageModel = new Message($db);
$notificationModel = new Notification($db);
$userModel = new User($db);

// Récupérer les affectations du tuteur
$assignments = $assignmentModel->getByTeacherId($teacher['id']);

// Récupérer les étudiants assignés à ce tuteur
$assignedStudents = [];
foreach ($assignments as $assignment) {
    $student = $studentModel->getById($assignment['student_id']);
    
    if ($student) {
        $studentUser = $userModel->getById($student['user_id']);
        unset($studentUser['password']);
        
        $assignedStudents[] = [
            'assignment_id' => $assignment['id'],
            'assignment_status' => $assignment['status'],
            'assignment_date' => $assignment['assignment_date'],
            'student' => [
                'id' => $student['id'],
                'first_name' => $student['first_name'],
                'last_name' => $student['last_name'],
                'program' => $student['program'] ?? 'Non spécifié'
            ]
        ];
    }
}

// Récupérer les réunions à venir
$today = date('Y-m-d');
$upcomingMeetings = [];

if (!empty($assignments)) {
    $meetingOptions = [
        'assignment_ids' => array_column($assignments, 'id'),
        'from_date' => $today,
        'limit' => 5
    ];
    
    $meetings = $meetingModel->getAll($meetingOptions);
    
    foreach ($meetings as $meeting) {
        $upcomingMeetings[] = [
            'id' => $meeting['id'],
            'title' => $meeting['title'],
            'meeting_date' => $meeting['meeting_date']
        ];
    }
}

// Récupérer les évaluations récentes
$recentEvaluations = [];

$evaluationOptions = [
    'teacher_id' => $teacher['id'],
    'limit' => 5
];

$evaluations = $evaluationModel->getAll($evaluationOptions);

foreach ($evaluations as $evaluation) {
    $recentEvaluations[] = [
        'id' => $evaluation['id'],
        'type' => $evaluation['type'],
        'score' => $evaluation['score'],
        'created_at' => $evaluation['created_at']
    ];
}

// Afficher les résultats
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'data' => [
        'teacher' => [
            'id' => $teacher['id'],
            'user_id' => $teacher['user_id'],
            'first_name' => $teacher['first_name'],
            'last_name' => $teacher['last_name']
        ],
        'students' => [
            'count' => count($assignedStudents),
            'list' => $assignedStudents
        ],
        'meetings' => [
            'count' => count($upcomingMeetings),
            'list' => $upcomingMeetings
        ],
        'evaluations' => [
            'count' => count($recentEvaluations),
            'list' => $recentEvaluations
        ]
    ]
]);