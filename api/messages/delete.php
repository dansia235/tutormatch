<?php
/**
 * API pour supprimer une conversation ou un message
 * Endpoint: /api/messages/delete.php
 * Méthode: POST
 * 
 * Paramètres:
 *  - conversation_id: ID de la conversation à supprimer
 *  - message_id: (optionnel) ID du message à supprimer
 */

error_log("API de suppression des messages démarrée");

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

$userId = $_SESSION['user_id'];

try {
    // Ajouter des logs de débogage
    error_log("API de suppression appelée avec les paramètres: " . json_encode($_POST));
    
    // Initialiser le modèle de message
    $messageModel = new Message($db);
    $success = false;
    $message = '';
    
    // Supprimer une conversation entière
    if (isset($_POST['conversation_id']) && !empty($_POST['conversation_id'])) {
        $conversationId = $_POST['conversation_id'];
        error_log("Suppression de la conversation ID: " . $conversationId);
        
        // Vérifier si l'utilisateur est un participant de la conversation
        $hasAccess = false;
        
        // Format pour conversations sans ID valide (utilise le format conv_X_Y)
        $parts = explode('_', $conversationId);
        if (count($parts) >= 3 && $parts[0] === 'conv') {
            // Extrait les IDs des utilisateurs
            $user1 = (int)$parts[1];
            $user2 = (int)$parts[2];
            
            // Vérifier que l'utilisateur actuel fait partie de cette conversation
            if ($user1 == $userId || $user2 == $userId) {
                $hasAccess = true;
            }
        } elseif (is_numeric($conversationId)) {
            // Pour les conversations avec ID numérique
            $stmt = $db->prepare("SELECT COUNT(*) FROM messages 
                                  WHERE conversation_id = :conversation_id 
                                  AND (sender_id = :user_id OR receiver_id = :user_id)");
            $stmt->bindParam(':conversation_id', $conversationId);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            if ($stmt->fetchColumn() > 0) {
                $hasAccess = true;
            }
        }
        
        if (!$hasAccess) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Vous n\'avez pas accès à cette conversation']);
            exit;
        }
        
        // Supprimer les messages
        if (count($parts) >= 3 && $parts[0] === 'conv') {
            // Pour les conversations au format conv_X_Y
            $user1 = (int)$parts[1];
            $user2 = (int)$parts[2];
            
            // Marquer les messages comme supprimés pour cet utilisateur
            $stmt = $db->prepare("UPDATE messages 
                                 SET status = 
                                    CASE 
                                        WHEN sender_id = :user_id THEN 'sender_deleted' 
                                        WHEN receiver_id = :user_id THEN 'receiver_deleted'
                                        ELSE status 
                                    END
                                 WHERE (
                                    (sender_id = :user1_id AND receiver_id = :user2_id) OR
                                    (sender_id = :user2_id AND receiver_id = :user1_id)
                                 ) AND (sender_id = :user_id OR receiver_id = :user_id)");
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':user1_id', $user1);
            $stmt->bindParam(':user2_id', $user2);
            $success = $stmt->execute();
        } else {
            // Pour les conversations avec ID numérique
            $stmt = $db->prepare("UPDATE messages 
                                 SET status = 
                                    CASE 
                                        WHEN sender_id = :user_id THEN 'sender_deleted' 
                                        WHEN receiver_id = :user_id THEN 'receiver_deleted'
                                        ELSE status 
                                    END
                                 WHERE conversation_id = :conversation_id
                                 AND (sender_id = :user_id OR receiver_id = :user_id)");
            $stmt->bindParam(':conversation_id', $conversationId);
            $stmt->bindParam(':user_id', $userId);
            $success = $stmt->execute();
        }
        
        if ($success) {
            $message = 'Conversation supprimée avec succès';
        } else {
            $message = 'Erreur lors de la suppression de la conversation';
        }
    }
    // Supprimer un message spécifique
    elseif (isset($_POST['message_id']) && !empty($_POST['message_id'])) {
        $messageId = $_POST['message_id'];
        
        // Vérifier que l'utilisateur est l'expéditeur ou le destinataire du message
        $stmt = $db->prepare("SELECT sender_id, receiver_id FROM messages WHERE id = :message_id");
        $stmt->bindParam(':message_id', $messageId);
        $stmt->execute();
        $message = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$message || ($message['sender_id'] != $userId && $message['receiver_id'] != $userId)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Vous n\'avez pas accès à ce message']);
            exit;
        }
        
        // Marquer le message comme supprimé pour cet utilisateur
        $updateStatus = $message['sender_id'] == $userId ? 'sender_deleted' : 'receiver_deleted';
        $stmt = $db->prepare("UPDATE messages SET status = :status WHERE id = :message_id");
        $stmt->bindParam(':status', $updateStatus);
        $stmt->bindParam(':message_id', $messageId);
        $success = $stmt->execute();
        
        if ($success) {
            $message = 'Message supprimé avec succès';
        } else {
            $message = 'Erreur lors de la suppression du message';
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
        exit;
    }
    
    // Toujours renvoyer une réponse JSON pour la cohérence
    error_log("Suppression terminée, résultat: " . ($success ? "succès" : "échec"));
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message
    ]);
    
} catch (Exception $e) {
    error_log('Error in delete.php: ' . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
}