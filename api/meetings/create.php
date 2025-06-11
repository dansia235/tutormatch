<?php
/**
 * Créer une nouvelle réunion
 * POST /api/meetings
 */

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Méthode non autorisée', 405);
}

// Récupérer les données de la requête
$requestBody = json_decode(file_get_contents('php://input'), true);
if (!$requestBody) {
    sendError('Données de réunion manquantes', 400);
}

// Valider les données requises
$requiredFields = ['title', 'date', 'start_time', 'end_time'];
foreach ($requiredFields as $field) {
    if (!isset($requestBody[$field]) || empty($requestBody[$field])) {
        sendError("Le champ '$field' est requis", 400);
    }
}

// Initialiser les modèles
$meetingModel = new Meeting($db);
$userModel = new User($db);
$studentModel = new Student($db);
$teacherModel = new Teacher($db);
$assignmentModel = new Assignment($db);

// Vérifier les permissions
$currentUserRole = $_SESSION['user_role'];
$currentUserId = $_SESSION['user_id'];

// Validation de l'affectation si elle est spécifiée
$assignmentId = isset($requestBody['assignment_id']) ? (int)$requestBody['assignment_id'] : null;

if ($assignmentId) {
    $assignment = $assignmentModel->getById($assignmentId);
    if (!$assignment) {
        sendError('Affectation non trouvée', 404);
    }
    
    // Vérifier que l'utilisateur est associé à cette affectation
    $isAssociated = false;
    
    if ($currentUserRole === 'admin' || $currentUserRole === 'coordinator') {
        $isAssociated = true;
    } elseif ($currentUserRole === 'teacher') {
        $teacher = $teacherModel->getByUserId($currentUserId);
        if ($teacher && $teacher['id'] == $assignment['teacher_id']) {
            $isAssociated = true;
        }
    } elseif ($currentUserRole === 'student') {
        $student = $studentModel->getByUserId($currentUserId);
        if ($student && $student['id'] == $assignment['student_id']) {
            $isAssociated = true;
        }
    }
    
    if (!$isAssociated) {
        sendError('Vous n\'êtes pas autorisé à créer une réunion pour cette affectation', 403);
    }
}

// Valider le format de la date et des heures
$date = $requestBody['date'];
$startTime = $requestBody['start_time'];
$endTime = $requestBody['end_time'];

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    sendError('Format de date invalide. Utilisez YYYY-MM-DD', 400);
}

if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $startTime)) {
    sendError('Format d\'heure de début invalide. Utilisez HH:MM ou HH:MM:SS', 400);
}

if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $endTime)) {
    sendError('Format d\'heure de fin invalide. Utilisez HH:MM ou HH:MM:SS', 400);
}

// Vérifier que l'heure de fin est après l'heure de début
$startDateTime = new DateTime($date . ' ' . $startTime);
$endDateTime = new DateTime($date . ' ' . $endTime);

if ($endDateTime <= $startDateTime) {
    sendError('L\'heure de fin doit être postérieure à l\'heure de début', 400);
}

// Préparer les données de la réunion
$meetingData = [
    'title' => $requestBody['title'],
    'description' => isset($requestBody['description']) ? $requestBody['description'] : '',
    'date' => $date,
    'start_time' => $startTime,
    'end_time' => $endTime,
    'location' => isset($requestBody['location']) ? $requestBody['location'] : '',
    'type' => isset($requestBody['type']) ? $requestBody['type'] : 'in-person',
    'status' => isset($requestBody['status']) ? $requestBody['status'] : 'scheduled',
    'meeting_url' => isset($requestBody['meeting_url']) ? $requestBody['meeting_url'] : '',
    'assignment_id' => $assignmentId,
    'created_by' => $currentUserId,
    'created_at' => date('Y-m-d H:i:s')
];

// Créer la réunion
$newMeetingId = $meetingModel->create($meetingData);
if (!$newMeetingId) {
    sendError('Échec de la création de la réunion', 500);
}

// Ajouter le créateur comme participant et organisateur
$participantData = [
    'meeting_id' => $newMeetingId,
    'user_id' => $currentUserId,
    'status' => 'accepted',
    'is_organizer' => 1,
    'joined_at' => date('Y-m-d H:i:s')
];
$meetingModel->addParticipant($participantData);

// Ajouter d'autres participants si spécifiés
if (isset($requestBody['participants']) && is_array($requestBody['participants'])) {
    foreach ($requestBody['participants'] as $participantId) {
        // Vérifier que l'utilisateur existe
        $user = $userModel->getById($participantId);
        if ($user) {
            $participantData = [
                'meeting_id' => $newMeetingId,
                'user_id' => $participantId,
                'status' => 'pending',
                'is_organizer' => 0,
                'joined_at' => null
            ];
            $meetingModel->addParticipant($participantData);
        }
    }
}

// Si une affectation est spécifiée, ajouter automatiquement l'étudiant et le tuteur comme participants
if ($assignmentId) {
    $assignment = $assignmentModel->getById($assignmentId);
    if ($assignment) {
        $student = $studentModel->getById($assignment['student_id']);
        $teacher = $teacherModel->getById($assignment['teacher_id']);
        
        $studentUser = $student ? $userModel->getById($student['user_id']) : null;
        $teacherUser = $teacher ? $userModel->getById($teacher['user_id']) : null;
        
        // Ajouter l'étudiant s'il n'est pas déjà le créateur
        if ($studentUser && $studentUser['id'] != $currentUserId) {
            $participantData = [
                'meeting_id' => $newMeetingId,
                'user_id' => $studentUser['id'],
                'status' => 'pending',
                'is_organizer' => 0,
                'joined_at' => null
            ];
            $meetingModel->addParticipant($participantData);
        }
        
        // Ajouter le tuteur s'il n'est pas déjà le créateur
        if ($teacherUser && $teacherUser['id'] != $currentUserId) {
            $participantData = [
                'meeting_id' => $newMeetingId,
                'user_id' => $teacherUser['id'],
                'status' => 'pending',
                'is_organizer' => 0,
                'joined_at' => null
            ];
            $meetingModel->addParticipant($participantData);
        }
    }
}

// Récupérer la réunion créée avec ses participants
$newMeeting = $meetingModel->getById($newMeetingId);
$participants = $meetingModel->getParticipants($newMeetingId);

// Enrichir les données de la réunion
$enrichedMeeting = $newMeeting;
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
            'status' => $participant['status'],
            'is_organizer' => $participant['is_organizer']
        ];
    }
}

$enrichedMeeting['participants'] = $participantDetails;

// Envoyer la réponse
sendJsonResponse([
    'message' => 'Réunion créée avec succès',
    'data' => $enrichedMeeting
], 201);