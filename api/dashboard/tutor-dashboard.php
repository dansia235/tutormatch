<?php
/**
 * API pour les statistiques du tableau de bord tuteur
 * Endpoint: /api/dashboard/tutor-dashboard
 * Méthode: GET
 */

require_once __DIR__ . '/../utils.php';
require_once __DIR__ . '/../../controllers/StatisticsController.php';

// Vérifier que l'utilisateur est connecté et a les droits
requireApiAuth();
requireApiRole(['teacher']);

// Récupérer l'ID du tuteur
$teacherModel = new Teacher($db);
$teacher = $teacherModel->getByUserId($_SESSION['user_id']);

if (!$teacher) {
    sendJsonError('Profil tuteur non trouvé', 404);
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
            'student' => array_merge($student, ['user' => $studentUser])
        ];
    }
}

// Récupérer les réunions à venir
$today = date('Y-m-d');
$upcomingMeetings = [];
$assignmentIds = array_column($assignments, 'id');

if (!empty($assignmentIds)) {
    $meetingOptions = [
        'assignment_ids' => $assignmentIds,
        'from_date' => $today,
        'status' => 'scheduled',
        'limit' => 5
    ];
    
    $meetings = $meetingModel->getAll($meetingOptions);
    
    foreach ($meetings as $meeting) {
        $assignment = $assignmentModel->getById($meeting['assignment_id']);
        if (!$assignment) continue;
        
        $student = $studentModel->getById($assignment['student_id']);
        $studentUser = $student ? $userModel->getById($student['user_id']) : null;
        
        $upcomingMeetings[] = [
            'id' => $meeting['id'],
            'title' => $meeting['title'],
            'description' => $meeting['description'],
            'meeting_date' => $meeting['meeting_date'],
            'status' => $meeting['status'],
            'student' => $studentUser ? [
                'id' => $student['id'],
                'name' => $studentUser['first_name'] . ' ' . $studentUser['last_name']
            ] : null
        ];
    }
}

// Récupérer les évaluations récentes
$recentEvaluations = [];

if (!empty($assignments)) {
    $evaluationOptions = [
        'teacher_id' => $teacher['id'],
        'limit' => 5
    ];
    
    $evaluations = $evaluationModel->getAll($evaluationOptions);
    
    foreach ($evaluations as $evaluation) {
        $assignment = $assignmentModel->getById($evaluation['assignment_id']);
        if (!$assignment) continue;
        
        $student = $studentModel->getById($assignment['student_id']);
        $studentUser = $student ? $userModel->getById($student['user_id']) : null;
        
        $recentEvaluations[] = [
            'id' => $evaluation['id'],
            'type' => $evaluation['type'],
            'score' => $evaluation['score'],
            'created_at' => $evaluation['created_at'],
            'student' => $studentUser ? [
                'id' => $student['id'],
                'name' => $studentUser['first_name'] . ' ' . $studentUser['last_name']
            ] : null
        ];
    }
}

// Récupérer les messages non lus
$unreadMessages = 0;
$currentUserId = $_SESSION['user_id'];

$query = "SELECT COUNT(*) as unread_count 
          FROM messages 
          WHERE receiver_id = :user_id 
          AND status = 'sent'";

$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $currentUserId, PDO::PARAM_INT);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$unreadMessages = (int)$result['unread_count'];

// Récupérer les conversations récentes
$conversationsQuery = "SELECT DISTINCT 
                     CASE 
                       WHEN m.sender_id = :user_id THEN m.receiver_id 
                       ELSE m.sender_id 
                     END as other_user_id,
                     MAX(m.sent_at) as last_message_date
                     FROM messages m
                     WHERE (m.sender_id = :user_id2 OR m.receiver_id = :user_id3)
                     AND m.status NOT IN ('sender_deleted', 'receiver_deleted')
                     GROUP BY other_user_id
                     ORDER BY last_message_date DESC
                     LIMIT 5";

$stmt = $db->prepare($conversationsQuery);
$stmt->bindParam(':user_id', $currentUserId, PDO::PARAM_INT);
$stmt->bindParam(':user_id2', $currentUserId, PDO::PARAM_INT);
$stmt->bindParam(':user_id3', $currentUserId, PDO::PARAM_INT);
$stmt->execute();
$conversationUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$recentConversations = [];
foreach ($conversationUsers as $convUser) {
    $otherUserId = $convUser['other_user_id'];
    
    // Récupérer les informations de l'autre utilisateur
    $otherUser = $userModel->getById($otherUserId);
    if (!$otherUser) continue;
    
    // Créer l'ID de conversation virtuel
    $userIds = [$currentUserId, $otherUserId];
    sort($userIds);
    $conversationId = 'conv_' . implode('_', $userIds);
    
    // Récupérer le dernier message
    $lastMessageQuery = "SELECT m.* FROM messages m
                        WHERE ((m.sender_id = :user_id AND m.receiver_id = :other_user_id) 
                           OR (m.sender_id = :other_user_id2 AND m.receiver_id = :user_id2))
                        AND m.status NOT IN ('sender_deleted', 'receiver_deleted')
                        ORDER BY m.sent_at DESC
                        LIMIT 1";
    
    $lastMsgStmt = $db->prepare($lastMessageQuery);
    $lastMsgStmt->bindParam(':user_id', $currentUserId, PDO::PARAM_INT);
    $lastMsgStmt->bindParam(':other_user_id', $otherUserId, PDO::PARAM_INT);
    $lastMsgStmt->bindParam(':other_user_id2', $otherUserId, PDO::PARAM_INT);
    $lastMsgStmt->bindParam(':user_id2', $currentUserId, PDO::PARAM_INT);
    $lastMsgStmt->execute();
    $lastMessage = $lastMsgStmt->fetch(PDO::FETCH_ASSOC);
    
    // Compter les messages non lus
    $unreadQuery = "SELECT COUNT(*) as unread_count
                   FROM messages
                   WHERE sender_id = :other_user_id 
                   AND receiver_id = :user_id
                   AND status = 'sent'";
    
    $unreadStmt = $db->prepare($unreadQuery);
    $unreadStmt->bindParam(':other_user_id', $otherUserId, PDO::PARAM_INT);
    $unreadStmt->bindParam(':user_id', $currentUserId, PDO::PARAM_INT);
    $unreadStmt->execute();
    $unreadResult = $unreadStmt->fetch(PDO::FETCH_ASSOC);
    $unreadCount = (int)$unreadResult['unread_count'];
    
    $conversation = [
        'id' => $conversationId,
        'title' => $otherUser['first_name'] . ' ' . $otherUser['last_name'],
        'participant' => [
            'id' => $otherUser['id'],
            'name' => $otherUser['first_name'] . ' ' . $otherUser['last_name'],
            'role' => $otherUser['role']
        ],
        'unread_count' => $unreadCount,
        'updated_at' => $convUser['last_message_date']
    ];
    
    if ($lastMessage) {
        $conversation['last_message'] = [
            'id' => $lastMessage['id'],
            'content' => $lastMessage['content'],
            'sent_at' => $lastMessage['sent_at'],
            'is_sent_by_me' => $lastMessage['sender_id'] == $currentUserId
        ];
    }
    
    $recentConversations[] = $conversation;
}

// Récupérer les notifications non lues
$notificationOptions = [
    'user_id' => $currentUserId,
    'unread' => true,
    'limit' => 5
];

$recentNotifications = $notificationModel->getAll($notificationOptions);
$totalUnreadNotifications = $notificationModel->countUnread($currentUserId);

// Compiler toutes les statistiques
$stats = [
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
    ],
    'messages' => [
        'unread_count' => $unreadMessages,
        'conversations' => $recentConversations
    ],
    'notifications' => [
        'unread_count' => $totalUnreadNotifications,
        'list' => $recentNotifications
    ]
];

// Envoyer la réponse
sendJsonResponse([
    'success' => true,
    'data' => $stats
]);