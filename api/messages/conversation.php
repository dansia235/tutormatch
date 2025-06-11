<?php
/**
 * API pour récupérer les messages d'une conversation
 * Endpoint: /api/messages/conversation
 * Méthode: GET
 * 
 * Paramètres:
 *  - contact_id: ID du contact
 *  - contact_type: Type du contact (student, teacher, coordinator)
 */

require_once __DIR__ . '/../utils.php';

// Vérifier que l'utilisateur est connecté
requireApiAuth();

try {
    // Récupérer les paramètres
    $contactId = isset($_GET['contact_id']) ? intval($_GET['contact_id']) : null;
    $contactType = isset($_GET['contact_type']) ? $_GET['contact_type'] : null;
    
    if (!$contactId || !$contactType) {
        sendJsonError('Paramètres manquants', 400);
    }
    
    // Récupérer le rôle de l'utilisateur
    $userRole = $_SESSION['user_role'];
    
    // Déterminer le type et l'ID de l'expéditeur
    $senderId = null;
    $senderType = $userRole;
    
    if ($userRole === 'teacher') {
        $teacherModel = new Teacher($db);
        $teacher = $teacherModel->getByUserId($_SESSION['user_id']);
        
        if (!$teacher) {
            sendJsonError('Profil tuteur non trouvé', 404);
        }
        
        $senderId = $teacher['id'];
        $senderType = 'teacher';
        
        // Vérifier que le contact est bien un étudiant assigné à ce tuteur
        if ($contactType === 'student') {
            $assignments = $teacherModel->getAssignments($teacher['id']);
            $isAssigned = false;
            
            foreach ($assignments as $assignment) {
                if ($assignment['student_id'] == $contactId) {
                    $isAssigned = true;
                    break;
                }
            }
            
            if (!$isAssigned) {
                sendJsonError('Vous n\'êtes pas autorisé à communiquer avec cet étudiant', 403);
            }
        }
    } elseif ($userRole === 'student') {
        $studentModel = new Student($db);
        $student = $studentModel->getByUserId($_SESSION['user_id']);
        
        if (!$student) {
            sendJsonError('Profil étudiant non trouvé', 404);
        }
        
        $senderId = $student['id'];
        $senderType = 'student';
        
        // Vérifier que le contact est bien le tuteur assigné à cet étudiant
        if ($contactType === 'teacher') {
            $assignmentModel = new Assignment($db);
            $assignment = $assignmentModel->getByStudentId($student['id']);
            
            if (!$assignment || $assignment['teacher_id'] != $contactId) {
                sendJsonError('Vous n\'êtes pas autorisé à communiquer avec ce tuteur', 403);
            }
        }
    } else {
        // Pour les autres rôles (admin, coordinateur)
        $senderId = $_SESSION['user_id'];
        $senderType = $userRole;
    }
    
    // Récupérer les messages de la conversation
    $messages = [];
    
    if (class_exists('Message')) {
        $messageModel = new Message($db);
        $messages = $messageModel->getConversation($senderId, $senderType, $contactId, $contactType);
        
        // Marquer les messages comme lus
        $messageModel->markAsRead($senderId, $senderType, $contactId, $contactType);
    } else {
        // Données fictives pour la démonstration
        $messages = generateDemoMessages($senderId, $contactId, $contactType);
    }
    
    // Formater les messages pour l'affichage
    foreach ($messages as &$message) {
        $message['is_outgoing'] = ($message['sender_type'] === $senderType && $message['sender_id'] == $senderId);
        $message['time'] = date('H:i', strtotime($message['sent_at']));
        
        // Format de la date pour les séparateurs
        $message['date'] = date('Y-m-d', strtotime($message['sent_at']));
        
        // Formatter la date en texte lisible
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        
        if ($message['date'] === $today) {
            $message['date_text'] = 'Aujourd\'hui';
        } elseif ($message['date'] === $yesterday) {
            $message['date_text'] = 'Hier';
        } else {
            $message['date_text'] = date('d/m/Y', strtotime($message['date']));
        }
    }
    
    // Récupérer les informations du contact
    $contact = null;
    
    if ($contactType === 'student') {
        $studentModel = new Student($db);
        $student = $studentModel->getById($contactId);
        
        if ($student) {
            $userModel = new User($db);
            $studentUser = $userModel->getById($student['user_id']);
            
            if ($studentUser) {
                $contact = [
                    'id' => $contactId,
                    'type' => $contactType,
                    'name' => $studentUser['first_name'] . ' ' . $studentUser['last_name'],
                    'role' => 'Étudiant',
                    'avatar' => "https://ui-avatars.com/api/?name=" . urlencode(mb_substr($studentUser['first_name'], 0, 1) . mb_substr($studentUser['last_name'], 0, 1)) . "&background=3498db&color=fff",
                    'status' => 'online' // Statut fictif ou à remplacer par un statut réel
                ];
            }
        }
    } elseif ($contactType === 'teacher') {
        $teacherModel = new Teacher($db);
        $teacher = $teacherModel->getById($contactId);
        
        if ($teacher) {
            $userModel = new User($db);
            $teacherUser = $userModel->getById($teacher['user_id']);
            
            if ($teacherUser) {
                $contact = [
                    'id' => $contactId,
                    'type' => $contactType,
                    'name' => $teacherUser['first_name'] . ' ' . $teacherUser['last_name'],
                    'role' => 'Tuteur',
                    'avatar' => "https://ui-avatars.com/api/?name=" . urlencode(mb_substr($teacherUser['first_name'], 0, 1) . mb_substr($teacherUser['last_name'], 0, 1)) . "&background=e74c3c&color=fff",
                    'status' => 'online' // Statut fictif ou à remplacer par un statut réel
                ];
            }
        }
    } elseif ($contactType === 'coordinator' || $contactType === 'admin') {
        $userModel = new User($db);
        $coordinator = $userModel->getById($contactId);
        
        if ($coordinator) {
            $contact = [
                'id' => $contactId,
                'type' => $contactType,
                'name' => $coordinator['first_name'] . ' ' . $coordinator['last_name'],
                'role' => $contactType === 'admin' ? 'Administrateur' : 'Coordinateur',
                'avatar' => "https://ui-avatars.com/api/?name=" . urlencode(mb_substr($coordinator['first_name'], 0, 1) . mb_substr($coordinator['last_name'], 0, 1)) . "&background=27ae60&color=fff",
                'status' => 'online' // Statut fictif ou à remplacer par un statut réel
            ];
        }
    }
    
    if (!$contact) {
        // Contact fictif si non trouvé
        $contact = [
            'id' => $contactId,
            'type' => $contactType,
            'name' => 'Contact #' . $contactId,
            'role' => ucfirst($contactType),
            'avatar' => "https://ui-avatars.com/api/?name=?&background=7f8c8d&color=fff",
            'status' => 'offline'
        ];
    }
    
    // Envoyer la réponse
    sendJsonResponse([
        'contact' => $contact,
        'messages' => $messages
    ]);
} catch (Exception $e) {
    sendJsonError('Erreur lors de la récupération des messages: ' . $e->getMessage(), 500);
}

/**
 * Fonction pour générer des messages de démonstration
 */
function generateDemoMessages($teacherId, $contactId, $contactType) {
    $demoMessages = [];
    
    // Messages fictifs pour la démonstration
    $messageTemplates = [
        [
            'sender_type' => $contactType,
            'sender_id' => $contactId,
            'content' => 'Bonjour, j\'aurais besoin de votre aide concernant mon projet de stage.',
            'sent_at' => date('Y-m-d H:i:s', strtotime('-3 days'))
        ],
        [
            'sender_type' => 'teacher',
            'sender_id' => $teacherId,
            'content' => 'Bonjour, bien sûr. Sur quel aspect avez-vous besoin d\'aide ?',
            'sent_at' => date('Y-m-d H:i:s', strtotime('-3 days +2 hours'))
        ],
        [
            'sender_type' => $contactType,
            'sender_id' => $contactId,
            'content' => 'Je dois finaliser mon rapport et j\'ai quelques questions sur la structure attendue.',
            'sent_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
        ],
        [
            'sender_type' => 'teacher',
            'sender_id' => $teacherId,
            'content' => 'Nous pouvons organiser une réunion pour en discuter. Quelles sont vos disponibilités cette semaine ?',
            'sent_at' => date('Y-m-d H:i:s', strtotime('-2 days +1 hour'))
        ],
        [
            'sender_type' => $contactType,
            'sender_id' => $contactId,
            'content' => 'Je suis disponible jeudi après-midi ou vendredi matin.',
            'sent_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
        ],
        [
            'sender_type' => 'teacher',
            'sender_id' => $teacherId,
            'content' => 'Parfait, je vous propose jeudi à 14h. Je vous enverrai un lien pour la visioconférence.',
            'sent_at' => date('Y-m-d H:i:s', strtotime('-1 day +3 hours'))
        ],
        [
            'sender_type' => $contactType,
            'sender_id' => $contactId,
            'content' => 'C\'est noté, merci beaucoup !',
            'sent_at' => date('Y-m-d H:i:s', strtotime('-1 day +4 hours'))
        ]
    ];
    
    // Ajouter des identifiants uniques aux messages
    foreach ($messageTemplates as $index => $message) {
        $message['id'] = $index + 1;
        $message['recipient_id'] = ($message['sender_type'] === 'teacher') ? $contactId : $teacherId;
        $message['recipient_type'] = ($message['sender_type'] === 'teacher') ? $contactType : 'teacher';
        $message['read'] = 1;
        $demoMessages[] = $message;
    }
    
    return $demoMessages;
}
?>
EOF < /dev/null
