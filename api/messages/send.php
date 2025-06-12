<?php
/**
 * API pour envoyer un message
 * Endpoint: /api/messages/send
 * Méthode: POST
 */

require_once __DIR__ . '/../utils.php';

// Vérifier que l'utilisateur est connecté
requireApiAuth();

// Vérifier que la méthode est POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonError('Méthode non autorisée', 405);
}

try {
    // Récupérer les données du corps de la requête
    $requestData = json_decode(file_get_contents('php://input'), true);
    
    if (!$requestData) {
        sendJsonError('Données invalides', 400);
    }
    
    // Vérifier les champs requis
    $requiredFields = ['recipient_id', 'content'];
    foreach ($requiredFields as $field) {
        if (!isset($requestData[$field]) || empty(trim($requestData[$field]))) {
            sendJsonError("Le champ '$field' est requis", 400);
        }
    }
    
    // Préparer les données du message
    // Utiliser directement l'user_id de la session pour l'expéditeur
    $messageData = [
        'sender_id' => $_SESSION['user_id'],
        'receiver_id' => $requestData['recipient_id'],
        'subject' => $requestData['subject'] ?? 'Nouveau message',
        'content' => trim($requestData['content']),
        'sent_at' => date('Y-m-d H:i:s')
    ];
    
    // Initialiser le modèle de message
    $messageModel = new Message($db);
    
    // Envoyer le message
    $messageId = $messageModel->send($messageData);
    
    if ($messageId) {
        // Récupérer le message envoyé
        $message = $messageModel->getById($messageId);
        
        // Créer une notification pour le destinataire
        try {
            $notificationModel = new Notification($db);
            
            // Récupérer les informations de l'expéditeur pour le message de notification
            $userModel = new User($db);
            $sender = $userModel->getById($_SESSION['user_id']);
            $senderName = $sender ? $sender['first_name'] . ' ' . $sender['last_name'] : 'Un utilisateur';
            
            // Déterminer le lien approprié selon le rôle du destinataire
            $recipientLink = '/tutoring/views/student/messages.php';
            $recipient = $userModel->getById($requestData['recipient_id']);
            
            if ($recipient) {
                if ($recipient['role'] === 'admin' || $recipient['role'] === 'coordinator') {
                    $recipientLink = '/tutoring/views/admin/messages.php';
                } elseif ($recipient['role'] === 'teacher') {
                    $recipientLink = '/tutoring/views/tutor/messages.php';
                }
            }
            
            // Créer la notification
            $notificationData = [
                'user_id' => $requestData['recipient_id'],
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
        
        if ($message) {
            // Formater le message pour l'affichage
            $message['is_outgoing'] = true;
            $message['time'] = date('H:i', strtotime($message['sent_at']));
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
            
            sendJsonResponse([
                'success' => true,
                'message' => 'Message envoyé avec succès',
                'data' => $message
            ]);
        } else {
            sendJsonResponse([
                'success' => true,
                'message' => 'Message envoyé avec succès',
                'data' => ['id' => $messageId]
            ]);
        }
    } else {
        sendJsonError('Erreur lors de l\'envoi du message', 500);
    }
} catch (Exception $e) {
    sendJsonError('Erreur lors de l\'envoi du message: ' . $e->getMessage(), 500);
}
?>