<?php
/**
 * Récupérer les détails d'une réunion via l'API pour l'affichage dans un modal
 */

// Initialiser les variables
require_once __DIR__ . '/../../includes/init.php';

// Vérifier que l'utilisateur est connecté et a le rôle tuteur
requireRole('teacher');

// Vérifier que l'ID de la réunion est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de réunion invalide']);
    exit;
}

$meetingId = (int)$_GET['id'];

// Récupérer les détails de la réunion
$meetingModel = new Meeting($db);
$meeting = $meetingModel->getById($meetingId);

if (!$meeting) {
    http_response_code(404);
    echo json_encode(['error' => 'Réunion non trouvée']);
    exit;
}

// Récupérer l'ID du tuteur
$teacherModel = new Teacher($db);
$teacher = $teacherModel->getByUserId($_SESSION['user_id']);

// Vérifier que le tuteur a accès à cette réunion
$hasAccess = false;

// Vérifier si le tuteur est l'organisateur
if (isset($meeting['organizer_id']) && $meeting['organizer_id'] == $_SESSION['user_id']) {
    $hasAccess = true;
}

// Vérifier si le tuteur est associé via l'affectation
if (!$hasAccess && isset($meeting['assignment_id'])) {
    $assignmentModel = new Assignment($db);
    $assignment = $assignmentModel->getById($meeting['assignment_id']);
    
    if ($assignment && isset($assignment['teacher_id']) && $assignment['teacher_id'] == $teacher['id']) {
        $hasAccess = true;
    }
}

// Vérifier si le tuteur est explicitement lié à cette réunion
if (!$hasAccess && isset($meeting['teacher_id']) && $meeting['teacher_id'] == $teacher['id']) {
    $hasAccess = true;
}

if (!$hasAccess) {
    http_response_code(403);
    echo json_encode(['error' => 'Accès non autorisé']);
    exit;
}

// Enrichir les données de la réunion avec des informations supplémentaires
$studentName = "Étudiant inconnu";
$studentId = null;

// Déterminer l'étudiant associé à la réunion
if (isset($meeting['student_id']) && $meeting['student_id']) {
    $studentId = $meeting['student_id'];
    if (isset($meeting['student_first_name']) && isset($meeting['student_last_name'])) {
        $studentName = $meeting['student_first_name'] . ' ' . $meeting['student_last_name'];
    } else {
        // Chercher les informations de l'étudiant si elles ne sont pas dans la réunion
        $studentModel = new Student($db);
        $student = $studentModel->getById($meeting['student_id']);
        if ($student) {
            $userModel = new User($db);
            $studentUser = $userModel->getById($student['user_id']);
            if ($studentUser) {
                $studentName = $studentUser['first_name'] . ' ' . $studentUser['last_name'];
            }
        }
    }
} elseif (isset($meeting['assignment_id']) && $meeting['assignment_id']) {
    // Chercher l'étudiant via l'affectation
    $assignmentModel = new Assignment($db);
    $assignment = $assignmentModel->getById($meeting['assignment_id']);
    
    if ($assignment) {
        $studentId = $assignment['student_id'];
        $studentModel = new Student($db);
        $student = $studentModel->getById($studentId);
        
        if ($student) {
            $userModel = new User($db);
            $studentUser = $userModel->getById($student['user_id']);
            if ($studentUser) {
                $studentName = $studentUser['first_name'] . ' ' . $studentUser['last_name'];
            }
        }
    }
}

// Déterminer l'organisateur de la réunion
$organizerName = "Organisateur inconnu";
if (isset($meeting['organizer_id'])) {
    $userModel = new User($db);
    $organizer = $userModel->getById($meeting['organizer_id']);
    if ($organizer) {
        $organizerName = $organizer['first_name'] . ' ' . $organizer['last_name'];
    }
}

// Déterminer les dates et durée
$meetingDate = null;
$meetingTime = null;
$formattedDate = "Date non spécifiée";
$formattedTime = "Heure non spécifiée";

if (isset($meeting['date_time']) && !empty($meeting['date_time'])) {
    $dateTime = new DateTime($meeting['date_time']);
    $meetingDate = $dateTime->format('Y-m-d');
    $meetingTime = $dateTime->format('H:i');
    $formattedDate = $dateTime->format('d/m/Y');
    $formattedTime = $dateTime->format('H:i');
} elseif (isset($meeting['date']) && !empty($meeting['date'])) {
    $meetingDate = $meeting['date'];
    $formattedDate = date('d/m/Y', strtotime($meeting['date']));
    
    if (isset($meeting['start_time']) && !empty($meeting['start_time'])) {
        $meetingTime = $meeting['start_time'];
        $formattedTime = date('H:i', strtotime($meeting['start_time']));
    }
}

// Calculer l'heure de fin
$duration = isset($meeting['duration']) ? (int)$meeting['duration'] : 60;
$endTime = "Non spécifiée";

if ($meetingTime) {
    $startDateTime = new DateTime($meetingDate . ' ' . $meetingTime);
    $endDateTime = clone $startDateTime;
    $endDateTime->add(new DateInterval('PT' . $duration . 'M'));
    $endTime = $endDateTime->format('H:i');
}

// Formater l'emplacement/lien
$location = isset($meeting['location']) ? $meeting['location'] : "Non spécifié";
$meetingLink = isset($meeting['meeting_link']) && !empty($meeting['meeting_link']) ? $meeting['meeting_link'] : null;

// Déterminer le type de la réunion
$meetingType = "Non spécifié";
if (isset($meeting['meeting_type']) && !empty($meeting['meeting_type'])) {
    $meetingType = $meeting['meeting_type'];
} else {
    // Essayer d'extraire le type de la description
    $description = $meeting['description'] ?? '';
    if (preg_match('/Type:\s*([^\n]+)/', $description, $matches)) {
        $meetingType = trim($matches[1]);
    }
}

// Nettoyer la description
$description = $meeting['description'] ?? "Aucune description";
$description = preg_replace('/Type:\s*[^\n]+\n+/', '', $description);
$description = trim($description);

// Déterminer les actions disponibles en fonction du statut
$status = $meeting['status'] ?? 'scheduled';
$statusLabel = match($status) {
    'pending' => 'En attente',
    'confirmed' => 'Confirmée',
    'completed' => 'Terminée',
    'cancelled' => 'Annulée',
    'scheduled' => 'Planifiée',
    default => ucfirst($status)
};

$statusClass = match($status) {
    'pending' => 'bg-warning',
    'confirmed' => 'bg-primary',
    'completed' => 'bg-success',
    'cancelled' => 'bg-danger',
    'scheduled' => 'bg-info',
    default => 'bg-secondary'
};

// Vérifier si la réunion est passée
$isPast = false;
if ($meetingDate) {
    $meetingDateTime = new DateTime($meetingDate . ' ' . ($meetingTime ?? '00:00:00'));
    $isPast = $meetingDateTime < new DateTime();
}

// Construire la réponse
$response = [
    'id' => $meeting['id'],
    'title' => $meeting['title'] ?? 'Réunion sans titre',
    'status' => [
        'value' => $status,
        'label' => $statusLabel,
        'class' => $statusClass
    ],
    'date' => $formattedDate,
    'time' => $formattedTime . ' - ' . $endTime,
    'duration' => $duration . ' minutes',
    'location' => $location,
    'meeting_link' => $meetingLink,
    'description' => $description,
    'student' => [
        'id' => $studentId,
        'name' => $studentName
    ],
    'organizer' => $organizerName,
    'type' => $meetingType,
    'past' => $isPast,
    'assignment_id' => $meeting['assignment_id'] ?? null,
    'created_at' => isset($meeting['created_at']) ? date('d/m/Y H:i', strtotime($meeting['created_at'])) : null,
    'student_attended' => isset($meeting['student_attended']) && $meeting['student_attended'] == 1,
    'notes' => $meeting['notes'] ?? '',
    'actions' => [
        'can_edit' => $status !== 'completed' && $status !== 'cancelled',
        'can_cancel' => $status !== 'completed' && $status !== 'cancelled',
        'can_complete' => $status !== 'completed' && $status !== 'cancelled' && $isPast
    ]
];

// Envoyer la réponse
header('Content-Type: application/json');
echo json_encode($response);
exit;