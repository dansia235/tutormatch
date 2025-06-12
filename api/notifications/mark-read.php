<?php
/**
 * Marquer une notification comme lue
 * POST /api/notifications/mark-read.php
 */

// Inclure les fichiers requis
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../utils.php';

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Méthode non autorisée', 405);
}

// Vérifier que l'ID est présent dans les données POST
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    sendError('ID de notification invalide', 400);
}

$notificationId = (int)$_POST['id'];

// Initialiser le modèle de notifications
$notificationModel = new Notification($db);

// Récupérer l'utilisateur actuel
$currentUserId = $_SESSION['user_id'];

// Récupérer la notification
$notification = $notificationModel->getById($notificationId);
if (!$notification) {
    sendError('Notification non trouvée', 404);
}

// Vérifier que la notification appartient à l'utilisateur
if ($notification['user_id'] != $currentUserId) {
    sendError('Vous n\'êtes pas autorisé à modifier cette notification', 403);
}

// Marquer la notification comme lue
$success = $notificationModel->markAsRead($notificationId);
if (!$success) {
    sendError('Échec de la mise à jour de la notification', 500);
}

// Récupérer le nombre de notifications non lues restantes
$unreadCount = $notificationModel->countUnread($currentUserId);

// Envoyer la réponse
sendJsonResponse([
    'message' => 'Notification marquée comme lue',
    'data' => [
        'id' => $notificationId,
        'read_at' => date('Y-m-d H:i:s')
    ],
    'meta' => [
        'unread_count' => $unreadCount
    ]
]);