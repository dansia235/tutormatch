<?php
/**
 * Authentification - Login
 * POST /api/auth/login
 */

// Inclure la classe JwtUtils
require_once __DIR__ . '/../../includes/JwtUtils.php';

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Méthode non autorisée', 405);
}

// Récupérer les données de la requête
$data = json_decode(file_get_contents('php://input'), true);

// Vérifier les données requises
if (!isset($data['username']) || !isset($data['password'])) {
    sendError('Nom d\'utilisateur et mot de passe requis', 400);
}

// Récupérer les identifiants
$username = $data['username'];
$password = $data['password'];

// Vérifier les identifiants
$userModel = new User($db);
$user = $userModel->authenticate($username, $password);

if (!$user) {
    sendError('Identifiants invalides', 401);
}

// Générer un token JWT d'accès
$accessToken = JwtUtils::generateToken($user);

// Générer un token JWT de rafraîchissement
$refreshToken = JwtUtils::generateToken($user, true);

// Mettre à jour la date de dernière connexion
$userModel->updateLastLogin($user['id']);

// Enregistrer la connexion dans l'historique
try {
    $curl = curl_init($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/tutoring/api/auth/record-login');
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, [
        'user_id' => $user['id'],
        'status' => 'success',
        'details' => 'Connexion via API'
    ]);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_exec($curl);
    curl_close($curl);
} catch (Exception $e) {
    // Ignorer les erreurs d'enregistrement pour ne pas bloquer la connexion
}

// Envoyer la réponse
sendJsonResponse([
    'success' => true,
    'message' => 'Authentification réussie',
    'user' => [
        'id' => $user['id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'role' => $user['role']
    ],
    'access_token' => $accessToken,
    'refresh_token' => $refreshToken,
    'token_type' => 'Bearer',
    'expires_in' => 3600 // 1 heure en secondes
]);