<?php
/**
 * Script pour charger les données du tableau de bord tuteur
 * Ce fichier est utilisé par le dashboard pour récupérer les données via AJAX
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../includes/init.php';

// Définir l'en-tête pour indiquer que le contenu est du JSON
header('Content-Type: application/json');

// Gérer les erreurs de façon élégante
try {
    // Vérifier que l'utilisateur est tuteur
    requireRole('teacher');
    
    // Vérifier quel type de données est demandé
    $type = isset($_GET['type']) ? $_GET['type'] : '';
    
    // Récupérer l'ID du tuteur
    $teacherModel = new Teacher($db);
    $teacher = $teacherModel->getByUserId($_SESSION['user_id']);
    
    if (!$teacher) {
        http_response_code(404);
        echo json_encode(['error' => 'Profil tuteur non trouvé']);
        exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur interne du serveur', 'message' => $e->getMessage()]);
    exit;
}

// Traiter selon le type de données demandé
switch ($type) {
    case 'meetings':
        try {
            // Récupérer les réunions
            $meetingModel = new Meeting($db);
            $userModel = new User($db);
            $studentModel = new Student($db);
            $userId = $_SESSION['user_id'];
            $teacherId = $teacher['id'];
            
            // Récupérer les affectations pour obtenir les IDs des étudiants
            $assignmentModel = new Assignment($db);
            $assignments = $assignmentModel->getByTeacherId($teacherId);
            $studentIds = [];
            
            foreach ($assignments as $assignment) {
                $studentIds[] = $assignment['student_id'];
            }
            
            // Récupérer toutes les réunions du tuteur (tous types confondus)
            $allMeetings = [];
            
            // 1. Réunions où le tuteur est l'organisateur
            $query = "SELECT m.*, 
                      s.id as student_id, u_s.first_name as student_first_name, u_s.last_name as student_last_name
                      FROM meetings m
                      LEFT JOIN assignments a ON m.assignment_id = a.id
                      LEFT JOIN students s ON a.student_id = s.id
                      LEFT JOIN users u_s ON s.user_id = u_s.id
                      WHERE m.organizer_id = :user_id 
                      AND m.status NOT IN ('cancelled', 'completed')
                      AND (m.date_time > NOW() OR DATE(m.date_time) = CURDATE())";
                      
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            $organizerMeetings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $allMeetings = array_merge($allMeetings, $organizerMeetings);
            
            // 2. Réunions associées aux affectations du tuteur
            if (!empty($assignments)) {
                $placeholders = implode(',', array_fill(0, count($assignments), '?'));
                $assignmentIds = array_column($assignments, 'id');
                
                $query = "SELECT m.*, 
                          s.id as student_id, u_s.first_name as student_first_name, u_s.last_name as student_last_name
                          FROM meetings m
                          LEFT JOIN assignments a ON m.assignment_id = a.id
                          LEFT JOIN students s ON a.student_id = s.id
                          LEFT JOIN users u_s ON s.user_id = u_s.id
                          WHERE m.assignment_id IN ($placeholders)
                          AND m.status NOT IN ('cancelled', 'completed')
                          AND (m.date_time > NOW() OR DATE(m.date_time) = CURDATE())";
                          
                $stmt = $db->prepare($query);
                foreach ($assignmentIds as $index => $id) {
                    $stmt->bindValue($index + 1, $id);
                }
                $stmt->execute();
                $assignmentMeetings = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $allMeetings = array_merge($allMeetings, $assignmentMeetings);
            }
            
            // Supprimer les doublons
            $uniqueMeetings = [];
            foreach ($allMeetings as $meeting) {
                if (!isset($uniqueMeetings[$meeting['id']])) {
                    $uniqueMeetings[$meeting['id']] = $meeting;
                }
            }
            
            $enrichedMeetings = [];
            foreach ($uniqueMeetings as $meeting) {
                // Formater les dates
                $meetingDate = $meeting['date_time'] ?? $meeting['date'] . ' ' . ($meeting['start_time'] ?? '00:00:00');
                
                // Récupérer les informations de l'étudiant
                $studentName = "Étudiant non spécifié";
                $studentId = null;
                
                if (isset($meeting['student_id']) && $meeting['student_id']) {
                    $studentId = $meeting['student_id'];
                    if (isset($meeting['student_first_name']) && isset($meeting['student_last_name'])) {
                        $studentName = $meeting['student_first_name'] . ' ' . $meeting['student_last_name'];
                    }
                } elseif (isset($meeting['assignment_id']) && $meeting['assignment_id']) {
                    $assignment = $assignmentModel->getById($meeting['assignment_id']);
                    if ($assignment) {
                        $studentId = $assignment['student_id'];
                        $student = $studentModel->getById($studentId);
                        if ($student) {
                            $studentUser = $userModel->getById($student['user_id']);
                            if ($studentUser) {
                                $studentName = $studentUser['first_name'] . ' ' . $studentUser['last_name'];
                            }
                        }
                    }
                }
                
                // Enrichir avec les détails manquants
                $enrichedMeeting = [
                    'id' => $meeting['id'],
                    'title' => $meeting['title'] ?? 'Réunion',
                    'description' => $meeting['description'] ?? '',
                    'meeting_date' => $meetingDate,
                    'status' => $meeting['status'],
                    'location' => $meeting['location'] ?? '',
                    'assignment' => [
                        'id' => $meeting['assignment_id'] ?? null,
                        'student' => [
                            'id' => $studentId,
                            'name' => $studentName
                        ]
                    ]
                ];
                
                $enrichedMeetings[] = $enrichedMeeting;
            }
            
            // Trier par date (les plus proches d'abord)
            usort($enrichedMeetings, function($a, $b) {
                $dateA = strtotime($a['meeting_date']);
                $dateB = strtotime($b['meeting_date']);
                return $dateA - $dateB;
            });
            
            // Limiter à 5 réunions
            $enrichedMeetings = array_slice($enrichedMeetings, 0, 5);
            
            // Réponse JSON
            echo json_encode([
                'data' => $enrichedMeetings,
                'meta' => [
                    'current_page' => 1,
                    'total_pages' => 1,
                    'total_records' => count($enrichedMeetings),
                    'per_page' => 5
                ]
            ]);
        } catch (Exception $e) {
            // Log détaillé de l'erreur
            error_log("Erreur lors du chargement des réunions: " . $e->getMessage());
            error_log("Trace: " . $e->getTraceAsString());
            
            // Créer une réponse minimaliste qui fonctionnera quoi qu'il arrive
            echo json_encode([
                'data' => [],
                'meta' => [
                    'current_page' => 1,
                    'total_pages' => 0,
                    'total_records' => 0,
                    'per_page' => 5
                ]
            ]);
        }
        break;
        
    case 'messages':
        try {
            // Récupérer les messages non lus
            $messageModel = new Message($db);
            $userModel = new User($db);
            $currentUserId = $_SESSION['user_id'];
            
            // Compter les messages non lus
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
            
            // Préparer les conversations
            $conversations = [];
            foreach ($conversationUsers as $convUser) {
                $otherUserId = $convUser['other_user_id'];
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
                    'participants' => [
                        [
                            'id' => $otherUser['id'],
                            'name' => $otherUser['first_name'] . ' ' . $otherUser['last_name'],
                            'role' => $otherUser['role']
                        ]
                    ],
                    'unread_count' => $unreadCount,
                    'updated_at' => $convUser['last_message_date']
                ];
                
                if ($lastMessage) {
                    $conversation['last_message'] = [
                        'id' => $lastMessage['id'],
                        'content' => $lastMessage['content'],
                        'sent_at' => $lastMessage['sent_at'],
                        'sender' => $lastMessage['sender_id'] == $currentUserId ? 'you' : 'other'
                    ];
                }
                
                $conversations[] = $conversation;
            }
            
            // Renvoyer les données
            echo json_encode([
                'data' => $conversations,
                'meta' => [
                    'total_unread' => $unreadMessages
                ]
            ]);
        } catch (Exception $e) {
            error_log("Erreur lors du chargement des messages: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'error' => 'Erreur lors de la récupération des messages',
                'message' => $e->getMessage()
            ]);
        }
        break;
        
    case 'notifications':
        try {
            // Récupérer les notifications
            $notificationModel = new Notification($db);
            $options = [
                'user_id' => $_SESSION['user_id'],
                'unread' => true,
                'limit' => 5
            ];
            
            $notifications = $notificationModel->getAll($options);
            $totalUnread = $notificationModel->countUnread($_SESSION['user_id']);
            
            echo json_encode([
                'data' => $notifications,
                'meta' => [
                    'total_unread' => $totalUnread
                ]
            ]);
        } catch (Exception $e) {
            error_log("Erreur lors du chargement des notifications: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'error' => 'Erreur lors de la récupération des notifications',
                'message' => $e->getMessage()
            ]);
        }
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Type de données non spécifié']);
        break;
}