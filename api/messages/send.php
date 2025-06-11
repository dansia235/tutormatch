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