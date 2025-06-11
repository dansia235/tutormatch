<?php
/**
 * Initialisation pour les API de tableau de bord
 * Ce fichier contient les fonctions communes et l'initialisation nécessaire pour les API
 */

// Inclure le fichier d'initialisation principal
require_once __DIR__ . '/../../includes/init.php';

// S'assurer que l'utilisateur est connecté
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Authentification requise'
    ]);
    exit;
}

// Vérifier si l'utilisateur a les droits d'accès
function requireApiAuth() {
    if (!isLoggedIn()) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Authentification requise'
        ]);
        exit;
    }
    return true;
}

// Vérifier si l'utilisateur a un rôle spécifique
function requireApiRole($allowedRoles) {
    if (!isLoggedIn() || !hasRole($allowedRoles)) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Accès non autorisé'
        ]);
        exit;
    }
    return true;
}

// Fonction pour envoyer une réponse JSON
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}
?>