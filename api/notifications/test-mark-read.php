<?php
/**
 * Script de test pour marquer une notification comme lue
 */

// Inclure les fichiers requis
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../utils.php';

// Vérifier que l'utilisateur est connecté
if (!isLoggedIn()) {
    die("Vous devez être connecté pour utiliser cette fonctionnalité.");
}

// Récupérer le modèle de notifications
$notificationModel = new Notification($db);

// Récupérer la première notification non lue de l'utilisateur
$options = [
    'user_id' => $_SESSION['user_id'],
    'read' => false,
    'limit' => 1,
    'page' => 1
];

$notifications = $notificationModel->getAll($options);

if (empty($notifications)) {
    die("Aucune notification non lue trouvée.");
}

$notification = $notifications[0];
$notificationId = $notification['id'];

// Marquer la notification comme lue
$success = $notificationModel->markAsRead($notificationId);

if ($success) {
    echo "Notification ID $notificationId marquée comme lue avec succès.";
} else {
    echo "Erreur lors du marquage de la notification ID $notificationId comme lue.";
}