<?php
/**
 * API pour récupérer la liste des contacts
 * Endpoint: /api/messages/contacts
 * Méthode: GET
 */

require_once __DIR__ . '/../utils.php';

// Vérifier que l'utilisateur est connecté
requireApiAuth();

try {
    // Récupérer le rôle de l'utilisateur
    $isTeacher = $_SESSION['user_role'] === 'teacher';
    $isStudent = $_SESSION['user_role'] === 'student';
    $isAdmin = $_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'coordinator';
    
    // Initialiser la liste des contacts
    $contacts = [];
    
    if ($isTeacher) {
        // Pour un tuteur, récupérer les étudiants assignés
        $teacherModel = new Teacher($db);
        $teacher = $teacherModel->getByUserId($_SESSION['user_id']);
        
        if (!$teacher) {
            sendJsonError('Profil tuteur non trouvé', 404);
        }
        
        // Récupérer les affectations d'étudiants pour ce tuteur
        $assignments = $teacherModel->getAssignments($teacher['id']);
        
        // Ajouter les étudiants assignés à la liste des contacts
        foreach ($assignments as $assignment) {
            $contacts[] = [
                'id' => $assignment['student_id'],
                'type' => 'student',
                'name' => $assignment['student_first_name'] . ' ' . $assignment['student_last_name'],
                'role' => 'Étudiant',
                'avatar' => "https://ui-avatars.com/api/?name=" . urlencode(mb_substr($assignment['student_first_name'], 0, 1) . mb_substr($assignment['student_last_name'], 0, 1)) . "&background=3498db&color=fff",
                'status' => 'online', // Statut fictif ou à remplacer par un statut réel
                'last_message' => '' // À remplir avec le dernier message s'il existe
            ];
        }
        
        // Ajouter les coordinateurs à la liste des contacts
        if (class_exists('User')) {
            $userModel = new User($db);
            $coordinators = $userModel->getByRole(['admin', 'coordinator']);
            
            foreach ($coordinators as $coordinator) {
                $contacts[] = [
                    'id' => $coordinator['id'],
                    'type' => 'coordinator',
                    'name' => $coordinator['first_name'] . ' ' . $coordinator['last_name'],
                    'role' => $coordinator['role'] === 'admin' ? 'Administrateur' : 'Coordinateur',
                    'avatar' => "https://ui-avatars.com/api/?name=" . urlencode(mb_substr($coordinator['first_name'], 0, 1) . mb_substr($coordinator['last_name'], 0, 1)) . "&background=27ae60&color=fff",
                    'status' => 'online', // Statut fictif ou à remplacer par un statut réel
                    'last_message' => '' // À remplir avec le dernier message s'il existe
                ];
            }
        } else {
            // Données fictives pour la démonstration
            $coordinators = [
                [
                    'id' => 1,
                    'type' => 'coordinator',
                    'name' => 'Sophie Martin',
                    'role' => 'Coordinateur',
                    'avatar' => "https://ui-avatars.com/api/?name=SM&background=27ae60&color=fff",
                    'status' => 'online',
                    'last_message' => ''
                ],
                [
                    'id' => 2,
                    'type' => 'coordinator',
                    'name' => 'Marc Dupont',
                    'role' => 'Coordinateur',
                    'avatar' => "https://ui-avatars.com/api/?name=MD&background=27ae60&color=fff",
                    'status' => 'offline',
                    'last_message' => ''
                ]
            ];
            
            $contacts = array_merge($contacts, $coordinators);
        }
    } elseif ($isStudent) {
        // Pour un étudiant, récupérer son tuteur et les coordinateurs
        $studentModel = new Student($db);
        $student = $studentModel->getByUserId($_SESSION['user_id']);
        
        if (!$student) {
            sendJsonError('Profil étudiant non trouvé', 404);
        }
        
        // Récupérer le tuteur assigné à cet étudiant
        $assignmentModel = new Assignment($db);
        $assignment = $assignmentModel->getByStudentId($student['id']);
        
        if ($assignment && $assignment['teacher_id']) {
            $teacherModel = new Teacher($db);
            $teacher = $teacherModel->getById($assignment['teacher_id']);
            
            if ($teacher) {
                $userModel = new User($db);
                $teacherUser = $userModel->getById($teacher['user_id']);
                
                if ($teacherUser) {
                    $contacts[] = [
                        'id' => $teacher['id'],
                        'type' => 'teacher',
                        'name' => $teacherUser['first_name'] . ' ' . $teacherUser['last_name'],
                        'role' => 'Tuteur',
                        'avatar' => "https://ui-avatars.com/api/?name=" . urlencode(mb_substr($teacherUser['first_name'], 0, 1) . mb_substr($teacherUser['last_name'], 0, 1)) . "&background=e74c3c&color=fff",
                        'status' => 'online', // Statut fictif ou à remplacer par un statut réel
                        'last_message' => '' // À remplir avec le dernier message s'il existe
                    ];
                }
            }
        }
        
        // Ajouter les coordinateurs à la liste des contacts
        if (class_exists('User')) {
            $userModel = new User($db);
            $coordinators = $userModel->getByRole(['admin', 'coordinator']);
            
            foreach ($coordinators as $coordinator) {
                $contacts[] = [
                    'id' => $coordinator['id'],
                    'type' => 'coordinator',
                    'name' => $coordinator['first_name'] . ' ' . $coordinator['last_name'],
                    'role' => $coordinator['role'] === 'admin' ? 'Administrateur' : 'Coordinateur',
                    'avatar' => "https://ui-avatars.com/api/?name=" . urlencode(mb_substr($coordinator['first_name'], 0, 1) . mb_substr($coordinator['last_name'], 0, 1)) . "&background=27ae60&color=fff",
                    'status' => 'online', // Statut fictif ou à remplacer par un statut réel
                    'last_message' => '' // À remplir avec le dernier message s'il existe
                ];
            }
        } else {
            // Données fictives pour la démonstration
            $coordinators = [
                [
                    'id' => 1,
                    'type' => 'coordinator',
                    'name' => 'Sophie Martin',
                    'role' => 'Coordinateur',
                    'avatar' => "https://ui-avatars.com/api/?name=SM&background=27ae60&color=fff",
                    'status' => 'online',
                    'last_message' => ''
                ],
                [
                    'id' => 2,
                    'type' => 'coordinator',
                    'name' => 'Marc Dupont',
                    'role' => 'Coordinateur',
                    'avatar' => "https://ui-avatars.com/api/?name=MD&background=27ae60&color=fff",
                    'status' => 'offline',
                    'last_message' => ''
                ]
            ];
            
            $contacts = array_merge($contacts, $coordinators);
        }
    } elseif ($isAdmin) {
        // Pour un admin ou coordinateur, récupérer tous les étudiants et tuteurs
        if (class_exists('User')) {
            $userModel = new User($db);
            
            // Récupérer tous les étudiants
            $students = $userModel->getByRole('student');
            foreach ($students as $student) {
                $contacts[] = [
                    'id' => $student['id'],
                    'type' => 'student',
                    'name' => $student['first_name'] . ' ' . $student['last_name'],
                    'role' => 'Étudiant',
                    'avatar' => "https://ui-avatars.com/api/?name=" . urlencode(mb_substr($student['first_name'], 0, 1) . mb_substr($student['last_name'], 0, 1)) . "&background=3498db&color=fff",
                    'status' => 'online', // Statut fictif ou à remplacer par un statut réel
                    'last_message' => '' // À remplir avec le dernier message s'il existe
                ];
            }
            
            // Récupérer tous les tuteurs
            $teachers = $userModel->getByRole('teacher');
            foreach ($teachers as $teacher) {
                $contacts[] = [
                    'id' => $teacher['id'],
                    'type' => 'teacher',
                    'name' => $teacher['first_name'] . ' ' . $teacher['last_name'],
                    'role' => 'Tuteur',
                    'avatar' => "https://ui-avatars.com/api/?name=" . urlencode(mb_substr($teacher['first_name'], 0, 1) . mb_substr($teacher['last_name'], 0, 1)) . "&background=e74c3c&color=fff",
                    'status' => 'online', // Statut fictif ou à remplacer par un statut réel
                    'last_message' => '' // À remplir avec le dernier message s'il existe
                ];
            }
            
            // Récupérer les autres coordinateurs et admins
            $coordinators = $userModel->getByRole(['admin', 'coordinator']);
            foreach ($coordinators as $coordinator) {
                if ($coordinator['id'] != $_SESSION['user_id']) {
                    $contacts[] = [
                        'id' => $coordinator['id'],
                        'type' => 'coordinator',
                        'name' => $coordinator['first_name'] . ' ' . $coordinator['last_name'],
                        'role' => $coordinator['role'] === 'admin' ? 'Administrateur' : 'Coordinateur',
                        'avatar' => "https://ui-avatars.com/api/?name=" . urlencode(mb_substr($coordinator['first_name'], 0, 1) . mb_substr($coordinator['last_name'], 0, 1)) . "&background=27ae60&color=fff",
                        'status' => 'online', // Statut fictif ou à remplacer par un statut réel
                        'last_message' => '' // À remplir avec le dernier message s'il existe
                    ];
                }
            }
        } else {
            // Données fictives pour la démonstration
            $students = [
                [
                    'id' => 1,
                    'type' => 'student',
                    'name' => 'Alice Dubois',
                    'role' => 'Étudiant',
                    'avatar' => "https://ui-avatars.com/api/?name=AD&background=3498db&color=fff",
                    'status' => 'online',
                    'last_message' => ''
                ],
                [
                    'id' => 2,
                    'type' => 'student',
                    'name' => 'Thomas Bernard',
                    'role' => 'Étudiant',
                    'avatar' => "https://ui-avatars.com/api/?name=TB&background=3498db&color=fff",
                    'status' => 'offline',
                    'last_message' => ''
                ]
            ];
            
            $teachers = [
                [
                    'id' => 1,
                    'type' => 'teacher',
                    'name' => 'Pierre Durand',
                    'role' => 'Tuteur',
                    'avatar' => "https://ui-avatars.com/api/?name=PD&background=e74c3c&color=fff",
                    'status' => 'online',
                    'last_message' => ''
                ],
                [
                    'id' => 2,
                    'type' => 'teacher',
                    'name' => 'Isabelle Martin',
                    'role' => 'Tuteur',
                    'avatar' => "https://ui-avatars.com/api/?name=IM&background=e74c3c&color=fff",
                    'status' => 'offline',
                    'last_message' => ''
                ]
            ];
            
            $contacts = array_merge($students, $teachers);
        }
    }
    
    // Récupérer les derniers messages pour chaque contact
    if (class_exists('Message')) {
        $messageModel = new Message($db);
        
        // Déterminer le type et l'ID de l'expéditeur
        $senderType = $_SESSION['user_role'];
        $senderId = null;
        
        if ($senderType === 'teacher') {
            $teacherModel = new Teacher($db);
            $teacher = $teacherModel->getByUserId($_SESSION['user_id']);
            $senderId = $teacher ? $teacher['id'] : $_SESSION['user_id'];
        } elseif ($senderType === 'student') {
            $studentModel = new Student($db);
            $student = $studentModel->getByUserId($_SESSION['user_id']);
            $senderId = $student ? $student['id'] : $_SESSION['user_id'];
        } else {
            $senderId = $_SESSION['user_id'];
        }
        
        // Mettre à jour chaque contact avec le dernier message
        foreach ($contacts as &$contact) {
            $lastMessage = $messageModel->getLastMessage($senderId, $senderType, $contact['id'], $contact['type']);
            
            if ($lastMessage) {
                $contact['last_message'] = substr($lastMessage['content'], 0, 50) . (strlen($lastMessage['content']) > 50 ? '...' : '');
                $contact['last_message_time'] = date('H:i', strtotime($lastMessage['sent_at']));
                $contact['unread_count'] = $messageModel->getUnreadCount($senderId, $senderType, $contact['id'], $contact['type']);
            } else {
                $contact['last_message'] = '';
                $contact['last_message_time'] = '';
                $contact['unread_count'] = 0;
            }
        }
    }
    
    // Envoyer la réponse
    sendJsonResponse([
        'contacts' => $contacts
    ]);
} catch (Exception $e) {
    sendJsonError('Erreur lors de la récupération des contacts: ' . $e->getMessage(), 500);
}
?>
EOL < /dev/null
