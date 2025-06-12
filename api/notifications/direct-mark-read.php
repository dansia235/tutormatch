<?php
/**
 * Marquer une notification comme lue (version sans contrôle de méthode HTTP)
 */

// Inclure les fichiers requis
require_once __DIR__ . '/../../includes/init.php';

// Activer l'affichage des erreurs PHP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Définir l'en-tête de réponse JSON
header('Content-Type: application/json');

// Vérifier que l'ID est présent
if (!isset($_REQUEST['id']) || !is_numeric($_REQUEST['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID de notification invalide',
        'debug' => [
            'request_method' => $_SERVER['REQUEST_METHOD'],
            'request_params' => $_REQUEST,
            'get_params' => $_GET,
            'post_params' => $_POST
        ]
    ]);
    exit;
}

$notificationId = (int)$_REQUEST['id'];

// Initialiser le modèle de notifications
$notificationModel = new Notification($db);

// Récupérer l'utilisateur actuel
$currentUserId = $_SESSION['user_id'];

// Récupérer la notification
$notification = $notificationModel->getById($notificationId);
if (!$notification) {
    die("Notification non trouvée");
}

// Vérifier que la notification appartient à l'utilisateur
if ($notification['user_id'] != $currentUserId) {
    die("Vous n'êtes pas autorisé à modifier cette notification");
}

// Marquer la notification comme lue
$success = $notificationModel->markAsRead($notificationId);
if (!$success) {
    die("Échec de la mise à jour de la notification");
}

// Récupérer le nombre de notifications non lues restantes
$unreadCount = $notificationModel->countUnread($currentUserId);

// Retourner le résultat
echo json_encode([
    'success' => true,
    'message' => 'Notification marquée comme lue',
    'data' => [
        'id' => $notificationId,
        'read_at' => date('Y-m-d H:i:s')
    ],
    'meta' => [
        'unread_count' => $unreadCount
    ]
]);