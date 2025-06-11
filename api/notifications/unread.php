<?php
/**
 * Liste des notifications non lues
 * GET /api/notifications/unread
 */

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Méthode non autorisée', 405);
}

// Récupérer les paramètres de requête
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

// Valider les paramètres
if ($limit < 1 || $limit > 50) $limit = 10;

// Initialiser le modèle de notifications
$notificationModel = new Notification($db);

// Récupérer l'utilisateur actuel
$currentUserId = $_SESSION['user_id'];

// Construire les options de requête
$options = [
    'user_id' => $currentUserId,
    'unread' => true,
    'limit' => $limit
];

// Récupérer les notifications non lues de l'utilisateur
$notifications = $notificationModel->getAll($options);
$total = $notificationModel->countUnread($currentUserId);

// Enrichir les données avec des informations additionnelles
$enrichedNotifications = [];
foreach ($notifications as $notification) {
    $enrichedNotification = $notification;
    
    // Déterminer si la notification est relative à un élément cliquable
    if ($notification['related_type'] && $notification['related_id']) {
        $relatedType = $notification['related_type'];
        $relatedId = $notification['related_id'];
        
        // Construire l'URL de l'élément lié
        $url = null;
        switch ($relatedType) {
            case 'assignment':
                $url = "/api/assignments/$relatedId";
                break;
            case 'internship':
                $url = "/api/internships/$relatedId";
                break;
            case 'document':
                $url = "/api/documents/$relatedId";
                break;
            case 'meeting':
                $url = "/api/meetings/$relatedId";
                break;
            case 'message':
                $url = "/api/messages/$relatedId";
                break;
            case 'evaluation':
                $url = "/api/evaluations/$relatedId";
                break;
        }
        
        if ($url) {
            $enrichedNotification['action_url'] = $url;
        }
    }
    
    $enrichedNotifications[] = $enrichedNotification;
}

// Envoyer la réponse
sendJsonResponse([
    'data' => $enrichedNotifications,
    'meta' => [
        'total_unread' => $total
    ]
]);