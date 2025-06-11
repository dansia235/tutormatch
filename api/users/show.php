<?php
/**
 * Afficher un utilisateur
 * GET /api/users/{id}
 */

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Méthode non autorisée', 405);
}

// Récupérer l'ID de l'utilisateur depuis l'URL
$userId = isset($urlParts[2]) ? (int)$urlParts[2] : 0;

if ($userId <= 0) {
    sendError('ID utilisateur invalide', 400);
}

// Initialiser le modèle utilisateur
$userModel = new User($db);

// Récupérer l'utilisateur
$user = $userModel->getById($userId);

if (!$user) {
    sendError('Utilisateur non trouvé', 404);
}

// Masquer le mot de passe et autres informations sensibles
unset($user['password']);

// Envoyer la réponse
sendJsonResponse([
    'data' => $user
]);