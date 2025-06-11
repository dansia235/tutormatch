<?php
/**
 * Liste des réunions
 * GET /api/meetings
 */

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Méthode non autorisée', 405);
}

// Récupérer les paramètres de requête
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$status = isset($_GET['status']) ? $_GET['status'] : null;
$assignmentId = isset($_GET['assignment_id']) ? (int)$_GET['assignment_id'] : null;
$from = isset($_GET['from_date']) ? $_GET['from_date'] : null;
$to = isset($_GET['to_date']) ? $_GET['to_date'] : null;

// Valider les paramètres
if ($page < 1) $page = 1;
if ($limit < 1 || $limit > 50) $limit = 10;

// Initialiser le modèle de réunions
$meetingModel = new Meeting($db);
$userModel = new User($db);
$studentModel = new Student($db);
$teacherModel = new Teacher($db);
$assignmentModel = new Assignment($db);

// Construire les options de requête
$options = [
    'page' => $page,
    'limit' => $limit
];

// Appliquer les filtres
if ($status) $options['status'] = $status;
if ($assignmentId) $options['assignment_id'] = $assignmentId;
if ($from) $options['from_date'] = $from;
if ($to) $options['to_date'] = $to;

// Restreindre l'accès selon le rôle
$currentUserRole = $_SESSION['user_role'];
$currentUserId = $_SESSION['user_id'];

if ($currentUserRole === 'student') {
    // Un étudiant ne peut voir que ses propres réunions
    $student = $studentModel->getByUserId($currentUserId);
    if (!$student) {
        sendError('Profil étudiant non trouvé', 404);
    }
    
    // Récupérer les affectations de l'étudiant
    $assignments = $assignmentModel->getByStudentId($student['id']);
    $assignmentIds = array_column($assignments, 'id');
    
    if (empty($assignmentIds)) {
        // Si l'étudiant n'a pas d'affectations, renvoyer une liste vide
        sendJsonResponse([
            'data' => [],
            'meta' => [
                'current_page' => $page,
                'total_pages' => 0,
                'total_records' => 0,
                'per_page' => $limit
            ]
        ]);
        exit;
    }
    
    $options['assignment_ids'] = $assignmentIds;
} elseif ($currentUserRole === 'teacher') {
    // Un tuteur ne peut voir que les réunions liées à ses affectations
    $teacher = $teacherModel->getByUserId($currentUserId);
    if (!$teacher) {
        sendError('Profil tuteur non trouvé', 404);
    }
    
    // Récupérer les affectations du tuteur
    $assignments = $assignmentModel->getByTeacherId($teacher['id']);
    $assignmentIds = array_column($assignments, 'id');
    
    if (empty($assignmentIds)) {
        // Si le tuteur n'a pas d'affectations, renvoyer une liste vide
        sendJsonResponse([
            'data' => [],
            'meta' => [
                'current_page' => $page,
                'total_pages' => 0,
                'total_records' => 0,
                'per_page' => $limit
            ]
        ]);
        exit;
    }
    
    $options['assignment_ids'] = $assignmentIds;
}

// Récupérer les réunions selon les filtres
$meetings = $meetingModel->getAll($options);
$total = $meetingModel->countAll($options);

// Calculer la pagination
$totalPages = ceil($total / $limit);

// Enrichir les données avec les informations associées
$enrichedMeetings = [];
foreach ($meetings as $meeting) {
    $assignmentId = $meeting['assignment_id'];
    $assignment = $assignmentId ? $assignmentModel->getById($assignmentId) : null;
    
    $participants = $meetingModel->getParticipants($meeting['id']);
    $participantDetails = [];
    
    foreach ($participants as $participant) {
        $user = $userModel->getById($participant['user_id']);
        if ($user) {
            $participantDetails[] = [
                'id' => $participant['id'],
                'user_id' => $user['id'],
                'name' => $user['first_name'] . ' ' . $user['last_name'],
                'email' => $user['email'],
                'role' => $user['role'],
                'status' => $participant['status']
            ];
        }
    }
    
    $enrichedMeeting = $meeting;
    $enrichedMeeting['participants'] = $participantDetails;
    
    if ($assignment) {
        $student = $studentModel->getById($assignment['student_id']);
        $teacher = $teacherModel->getById($assignment['teacher_id']);
        
        $studentUser = $student ? $userModel->getById($student['user_id']) : null;
        $teacherUser = $teacher ? $userModel->getById($teacher['user_id']) : null;
        
        $enrichedMeeting['assignment'] = [
            'id' => $assignment['id'],
            'student' => $studentUser ? [
                'id' => $student['id'],
                'name' => $studentUser['first_name'] . ' ' . $studentUser['last_name']
            ] : null,
            'teacher' => $teacherUser ? [
                'id' => $teacher['id'],
                'name' => $teacherUser['first_name'] . ' ' . $teacherUser['last_name']
            ] : null
        ];
    }
    
    $enrichedMeetings[] = $enrichedMeeting;
}

// Envoyer la réponse
sendJsonResponse([
    'data' => $enrichedMeetings,
    'meta' => [
        'current_page' => $page,
        'total_pages' => $totalPages,
        'total_records' => $total,
        'per_page' => $limit
    ]
]);