<?php
/**
 * Authentification - Refresh Token
 * POST /api/auth/refresh
 */

// Inclure la classe JwtUtils
require_once __DIR__ . '/../../includes/JwtUtils.php';

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Méthode non autorisée', 405);
}

// Récupérer les données de la requête
$data = json_decode(file_get_contents('php://input'), true);

// Vérifier si le token de rafraîchissement est présent
if (!isset($data['refresh_token'])) {
    sendError('Token de rafraîchissement manquant', 400);
}

$refreshToken = $data['refresh_token'];

// Valider le token de rafraîchissement
$tokenData = JwtUtils::validateToken($refreshToken);

if (!$tokenData) {
    sendError('Token de rafraîchissement invalide ou expiré', 401);
}

// Vérifier que c'est bien un token de rafraîchissement
if (!isset($tokenData['type']) || $tokenData['type'] !== 'refresh') {
    sendError('Type de token invalide', 401);
}

// Récupérer l'ID utilisateur à partir du token
$userId = $tokenData['sub'];

// Récupérer les données utilisateur
$userModel = new User($db);
$user = $userModel->getById($userId);

if (!$user) {
    sendError('Utilisateur non trouvé', 404);
}

// Générer un nouveau token d'accès
$newAccessToken = JwtUtils::generateToken($user);

// Générer un nouveau token de rafraîchissement (rotation des tokens)
$newRefreshToken = JwtUtils::generateToken($user, true);

// Envoyer la réponse
sendJsonResponse([
    'success' => true,
    'message' => 'Token rafraîchi avec succès',
    'access_token' => $newAccessToken,
    'refresh_token' => $newRefreshToken,
    'token_type' => 'Bearer',
    'expires_in' => 3600 // 1 heure en secondes
]);