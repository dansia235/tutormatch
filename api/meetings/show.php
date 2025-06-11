<?php
/**
 * Détails d'une réunion
 * GET /api/meetings/{id}
 */

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Méthode non autorisée', 405);
}

// Vérifier que l'ID est présent
if (!isset($urlParts[2]) || !is_numeric($urlParts[2])) {
    sendError('ID de réunion invalide', 400);
}

$meetingId = (int)$urlParts[2];

// Initialiser les modèles
$meetingModel = new Meeting($db);
$userModel = new User($db);
$studentModel = new Student($db);
$teacherModel = new Teacher($db);
$assignmentModel = new Assignment($db);

// Récupérer la réunion
$meeting = $meetingModel->getById($meetingId);
if (!$meeting) {
    sendError('Réunion non trouvée', 404);
}

// Vérifier les permissions
$currentUserRole = $_SESSION['user_role'];
$currentUserId = $_SESSION['user_id'];

// Déterminer si l'utilisateur a accès à la réunion
$hasAccess = false;

if ($currentUserRole === 'admin' || $currentUserRole === 'coordinator') {
    // Les administrateurs et coordinateurs ont accès à toutes les réunions
    $hasAccess = true;
} else {
    // Pour les étudiants et tuteurs, vérifier s'ils sont associés à la réunion
    
    // Vérifier s'ils sont participants
    $participants = $meetingModel->getParticipants($meetingId);
    foreach ($participants as $participant) {
        if ($participant['user_id'] == $currentUserId) {
            $hasAccess = true;
            break;
        }
    }
    
    // Vérifier s'ils sont associés à l'affectation liée
    if (!$hasAccess && $meeting['assignment_id']) {
        $assignment = $assignmentModel->getById($meeting['assignment_id']);
        
        if ($assignment) {
            if ($currentUserRole === 'student') {
                $student = $studentModel->getByUserId($currentUserId);
                if ($student && $student['id'] == $assignment['student_id']) {
                    $hasAccess = true;
                }
            } elseif ($currentUserRole === 'teacher') {
                $teacher = $teacherModel->getByUserId($currentUserId);
                if ($teacher && $teacher['id'] == $assignment['teacher_id']) {
                    $hasAccess = true;
                }
            }
        }
    }
}

if (!$hasAccess) {
    sendError('Accès non autorisé', 403);
}

// Enrichir la réunion avec des informations additionnelles
$enrichedMeeting = $meeting;

// Récupérer les détails des participants
$participants = $meetingModel->getParticipants($meetingId);
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
            'joined_at' => $participant['joined_at'],
            'is_organizer' => $participant['is_organizer']
        ];
    }
}

$enrichedMeeting['participants'] = $participantDetails;

// Récupérer les détails de l'affectation si elle existe
if ($meeting['assignment_id']) {
    $assignment = $assignmentModel->getById($meeting['assignment_id']);
    
    if ($assignment) {
        $student = $studentModel->getById($assignment['student_id']);
        $teacher = $teacherModel->getById($assignment['teacher_id']);
        
        $studentUser = $student ? $userModel->getById($student['user_id']) : null;
        $teacherUser = $teacher ? $userModel->getById($teacher['user_id']) : null;
        
        $enrichedMeeting['assignment'] = [
            'id' => $assignment['id'],
            'student' => $studentUser ? [
                'id' => $student['id'],
                'name' => $studentUser['first_name'] . ' ' . $studentUser['last_name'],
                'email' => $studentUser['email']
            ] : null,
            'teacher' => $teacherUser ? [
                'id' => $teacher['id'],
                'name' => $teacherUser['first_name'] . ' ' . $teacherUser['last_name'],
                'email' => $teacherUser['email']
            ] : null,
            'status' => $assignment['status']
        ];
    }
}

// Récupérer les documents liés à la réunion
$documents = (new Document($db))->getByMeetingId($meetingId);
$enrichedMeeting['documents'] = $documents;

// Envoyer la réponse
sendJsonResponse([
    'data' => $enrichedMeeting
]);