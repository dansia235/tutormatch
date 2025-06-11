<?php
/**
 * API pour marquer un message comme lu
 * Endpoint: /api/messages/mark-read.php
 * Méthode: POST
 * 
 * Paramètres:
 *  - message_id: ID du message (optionnel si conversation_id est fourni)
 *  - conversation_id: ID de la conversation (optionnel si message_id est fourni)
 *  - mark_all: true pour marquer tous les messages d'une conversation comme lus
 */

require_once __DIR__ . '/../../includes/init.php';

// Vérifier que l'utilisateur est connecté
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Vérifier la méthode de requête
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Récupérer les données JSON si envoyées en JSON
$jsonData = json_decode(file_get_contents('php://input'), true);
$data = $jsonData ?: $_POST;

$userId = $_SESSION['user_id'];
$success = false;
$messagesMarked = 0;

try {
    // Initialiser le modèle de message
    $messageModel = new Message($db);
    
    // Cas 1 : Marquer un message spécifique comme lu
    if (isset($data['message_id']) && !empty($data['message_id'])) {
        $messageId = $data['message_id'];
        
        // Vérifier que le message existe et que l'utilisateur en est le destinataire
        $query = "SELECT * FROM messages WHERE id = :id AND receiver_id = :receiver_id LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $messageId);
        $stmt->bindParam(':receiver_id', $userId);
        $stmt->execute();
        $message = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($message && $message['status'] !== 'read') {
            // Mettre à jour le statut du message
            $updateQuery = "UPDATE messages 
                           SET status = 'read', 
                               read_at = CASE 
                                   WHEN read_at IS NULL THEN NOW() 
                                   ELSE read_at 
                               END
                           WHERE id = :id AND receiver_id = :receiver_id";
            
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->bindParam(':id', $messageId);
            $updateStmt->bindParam(':receiver_id', $userId);
            $success = $updateStmt->execute();
            
            if ($success) {
                $messagesMarked = 1;
            }
        } else if ($message && $message['status'] === 'read') {
            // Le message est déjà lu
            $success = true;
            $messagesMarked = 0;
        }
    }
    // Cas 2 : Marquer tous les messages d'une conversation comme lus
    else if (isset($data['conversation_id']) && !empty($data['conversation_id'])) {
        $conversationId = $data['conversation_id'];
        
        // Extraire les IDs des participants de l'ID de conversation virtuel
        if (strpos($conversationId, 'conv_') === 0) {
            $parts = explode('_', $conversationId);
            if (count($parts) >= 3) {
                $user1 = (int)$parts[1];
                $user2 = (int)$parts[2];
                
                // Vérifier que l'utilisateur actuel fait partie de cette conversation
                if ($userId == $user1 || $userId == $user2) {
                    $otherUserId = ($userId == $user1) ? $user2 : $user1;
                    
                    // Marquer tous les messages reçus de l'autre utilisateur comme lus
                    $updateQuery = "UPDATE messages 
                                   SET status = 'read',
                                       read_at = CASE 
                                           WHEN read_at IS NULL THEN NOW() 
                                           ELSE read_at 
                                       END
                                   WHERE sender_id = :sender_id 
                                   AND receiver_id = :receiver_id 
                                   AND status = 'sent'";
                    
                    $updateStmt = $db->prepare($updateQuery);
                    $updateStmt->bindParam(':sender_id', $otherUserId);
                    $updateStmt->bindParam(':receiver_id', $userId);
                    $success = $updateStmt->execute();
                    
                    if ($success) {
                        $messagesMarked = $updateStmt->rowCount();
                    }
                } else {
                    throw new Exception("Accès non autorisé à cette conversation");
                }
            } else {
                throw new Exception("Format d'ID de conversation invalide");
            }
        } else {
            throw new Exception("Format d'ID de conversation non reconnu");
        }
    }
    // Cas 3 : Marquer tous les messages comme lus (si mark_all est true)
    else if (isset($data['mark_all']) && $data['mark_all'] === true) {
        $updateQuery = "UPDATE messages 
                       SET status = 'read',
                           read_at = CASE 
                               WHEN read_at IS NULL THEN NOW() 
                               ELSE read_at 
                           END
                       WHERE receiver_id = :receiver_id 
                       AND status = 'sent'";
        
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->bindParam(':receiver_id', $userId);
        $success = $updateStmt->execute();
        
        if ($success) {
            $messagesMarked = $updateStmt->rowCount();
        }
    } else {
        throw new Exception("Paramètres manquants : message_id ou conversation_id requis");
    }
    
    // Envoyer la réponse
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $success ? 
            ($messagesMarked > 0 ? "$messagesMarked message(s) marqué(s) comme lu(s)" : "Aucun nouveau message à marquer") : 
            'Erreur lors du marquage du message',
        'messages_marked' => $messagesMarked
    ]);
    
} catch (Exception $e) {
    error_log('Error in mark-read.php: ' . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur : ' . $e->getMessage(),
        'messages_marked' => 0
    ]);
}