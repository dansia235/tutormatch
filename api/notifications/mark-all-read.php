<?php
/**
 * Marquer toutes les notifications comme lues
 * POST /api/notifications/mark-all-read
 */

// Inclure les fichiers requis
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../utils.php';

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Méthode non autorisée', 405);
}

// Initialiser le modèle de notifications
$notificationModel = new Notification($db);

// Récupérer l'utilisateur actuel
$currentUserId = $_SESSION['user_id'];

// Marquer toutes les notifications comme lues
$success = $notificationModel->markAllAsRead($currentUserId);

if ($success) {
    // Envoyer une réponse de succès
    sendJsonResponse([
        'success' => true,
        'message' => 'Toutes les notifications ont été marquées comme lues',
    ]);
} else {
    // Envoyer une réponse d'erreur
    sendError('Impossible de marquer les notifications comme lues', 500);
}