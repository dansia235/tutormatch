<?php
/**
 * Marquer toutes les notifications comme lues (version sans contrôle de méthode HTTP)
 */

// Inclure les fichiers requis
require_once __DIR__ . '/../../includes/init.php';

// Activer l'affichage des erreurs PHP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Définir l'en-tête de réponse JSON
header('Content-Type: application/json');

// Initialiser le modèle de notifications
$notificationModel = new Notification($db);

// Récupérer l'utilisateur actuel
$currentUserId = $_SESSION['user_id'];

// Marquer toutes les notifications comme lues
$success = $notificationModel->markAllAsRead($currentUserId);

// Retourner le résultat
echo json_encode([
    'success' => $success,
    'message' => $success ? 'Toutes les notifications ont été marquées comme lues' : 'Erreur lors du marquage des notifications'
]);