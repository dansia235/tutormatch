<?php
/**
 * API simplifiée pour envoyer un nouveau message
 * Endpoint: /api/messages/send-simple.php
 * Méthode: POST
 */

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../includes/init.php';

// Vérifier que l'utilisateur est connecté
if (!isLoggedIn()) {
    // Rediriger vers la page de connexion
    header('Location: /tutoring/login.php');
    exit;
}

// Traiter la soumission du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $receiverId = $_POST['receiver_id'] ?? '';
    $receiverType = $_POST['receiver_type'] ?? '';
    $subject = $_POST['subject'] ?? 'Nouveau message';
    $content = $_POST['message_content'] ?? '';
    
    // Validation des données
    $errors = [];
    
    if (empty($receiverId)) {
        $errors[] = 'Le destinataire est requis';
    }
    
    if (empty($content)) {
        $errors[] = 'Le contenu du message est requis';
    }
    
    if (empty($errors)) {
        try {
            // Récupérer l'ID utilisateur du destinataire si nécessaire
            $receiverUserId = $receiverId;
            
            if ($receiverType === 'student') {
                $studentModel = new Student($db);
                $student = $studentModel->getById($receiverId);
                if ($student && isset($student['user_id'])) {
                    $receiverUserId = $student['user_id'];
                }
            }
            
            // Création du message
            $messageModel = new Message($db);
            $messageData = [
                'sender_id' => $_SESSION['user_id'],
                'receiver_id' => $receiverUserId,
                'subject' => $subject,
                'content' => $content,
                'sent_at' => date('Y-m-d H:i:s'),
                'status' => 'sent'
            ];
            
            $messageId = $messageModel->send($messageData);
            
            if ($messageId) {
                // Créer une notification pour le destinataire
                try {
                    $notificationModel = new Notification($db);
                    
                    // Récupérer les informations de l'expéditeur pour le message de notification
                    $userModel = new User($db);
                    $sender = $userModel->getById($_SESSION['user_id']);
                    $senderName = $sender ? $sender['first_name'] . ' ' . $sender['last_name'] : 'Un utilisateur';
                    
                    // Déterminer le lien approprié selon le rôle du destinataire
                    $recipientLink = '/tutoring/views/student/messages.php';
                    $recipient = $userModel->getById($receiverUserId);
                    
                    if ($recipient) {
                        if ($recipient['role'] === 'admin' || $recipient['role'] === 'coordinator') {
                            $recipientLink = '/tutoring/views/admin/messages.php';
                        } elseif ($recipient['role'] === 'teacher') {
                            $recipientLink = '/tutoring/views/tutor/messages.php';
                        }
                    }
                    
                    // Créer la notification
                    $notificationData = [
                        'user_id' => $receiverUserId,
                        'title' => 'Nouveau message',
                        'message' => "Vous avez reçu un nouveau message de $senderName",
                        'type' => 'info',
                        'related_type' => 'message',
                        'related_id' => $messageId,
                        'link' => $recipientLink
                    ];
                    
                    $notificationId = $notificationModel->create($notificationData);
                    
                    // Log de débogage
                    error_log("Notification créée pour le message $messageId: notification ID $notificationId");
                } catch (Exception $e) {
                    // Log l'erreur mais ne pas arrêter le flux
                    error_log("Erreur lors de la création de la notification: " . $e->getMessage());
                }
                
                // Message envoyé avec succès
                $_SESSION['flash_message'] = [
                    'type' => 'success',
                    'message' => 'Message envoyé avec succès'
                ];
                header('Location: /tutoring/views/tutor/messages-repair.php');
                exit;
            } else {
                // Erreur lors de l'envoi du message
                $_SESSION['flash_message'] = [
                    'type' => 'error',
                    'message' => 'Erreur lors de l\'envoi du message'
                ];
                header('Location: /tutoring/views/tutor/messages-repair.php');
                exit;
            }
        } catch (Exception $e) {
            // Erreur lors de l'envoi du message
            $_SESSION['flash_message'] = [
                'type' => 'error',
                'message' => 'Erreur lors de l\'envoi du message: ' . $e->getMessage()
            ];
            header('Location: /tutoring/views/tutor/messages-repair.php');
            exit;
        }
    } else {
        // Erreurs de validation
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'message' => implode('<br>', $errors)
        ];
        header('Location: /tutoring/views/tutor/messages-repair.php');
        exit;
    }
} else {
    // Méthode non autorisée
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'Méthode non autorisée'
    ];
    header('Location: /tutoring/views/tutor/messages-repair.php');
    exit;
}