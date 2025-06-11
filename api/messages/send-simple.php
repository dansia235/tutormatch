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