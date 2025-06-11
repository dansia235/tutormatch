<?php
/**
 * Authentification - Logout
 * POST /api/auth/logout
 */

// Inclure la classe JwtUtils
require_once __DIR__ . '/../../includes/JwtUtils.php';

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Méthode non autorisée', 405);
}

// Récupérer le token depuis l'en-tête Authorization
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (!$authHeader) {
    sendError('Token manquant', 400);
}

// Extraire le token Bearer
$token = JwtUtils::extractBearerToken($authHeader);
if (!$token) {
    sendError('Format de token invalide', 400);
}

// Valider le token
$tokenData = JwtUtils::validateToken($token);
if (!$tokenData) {
    sendError('Token invalide ou expiré', 401);
}

// En production, vous devriez ajouter le token à une liste noire (blacklist)
// Cela nécessiterait une table dans la base de données pour stocker les tokens invalidés
// Par exemple:
// $tokenBlacklistModel->add($token, $tokenData['exp']);

// Pour cet exemple, nous allons simplement renvoyer une réponse de succès
sendJsonResponse([
    'success' => true,
    'message' => 'Déconnexion réussie'
]);