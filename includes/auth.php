<?php
/**
 * Fonctions d'authentification et d'autorisation
 */

/**
 * Vérifie si un utilisateur est connecté
 * 
 * @return boolean Vrai si l'utilisateur est connecté
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Vérifie si l'utilisateur connecté a un rôle spécifique
 * 
 * @param string|array $roles Rôle(s) à vérifier
 * @return boolean Vrai si l'utilisateur a le rôle
 */
function hasRole($roles) {
    if (!isLoggedIn()) {
        return false;
    }

    if (!is_array($roles)) {
        $roles = [$roles];
    }

    return in_array($_SESSION['user_role'], $roles);
}

/**
 * Requiert que l'utilisateur soit connecté, sinon redirige
 * 
 * @param string $redirect URL de redirection si l'utilisateur n'est pas connecté
 * @return void
 */
function requireLogin($redirect = '/tutoring/login.php') {
    if (!isLoggedIn()) {
        // Sauvegarder l'URL actuelle pour revenir après la connexion
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        redirect($redirect);
    }
}

/**
 * Requiert que l'utilisateur ait un rôle spécifique, sinon redirige
 * 
 * @param string|array $roles Rôle(s) requis
 * @param string $redirect URL de redirection si l'utilisateur n'a pas le rôle
 * @return void
 */
function requireRole($roles, $redirect = '/tutoring/access-denied.php') {
    requireLogin();
    
    if (!hasRole($roles)) {
        redirect($redirect);
    }
}

/**
 * Vérifie si l'utilisateur connecté est le propriétaire d'une ressource
 * 
 * @param int $resourceUserId ID de l'utilisateur propriétaire de la ressource
 * @return boolean Vrai si l'utilisateur est le propriétaire
 */
function isOwner($resourceUserId) {
    if (!isLoggedIn()) {
        return false;
    }

    return $_SESSION['user_id'] == $resourceUserId;
}

/**
 * Requiert que l'utilisateur soit le propriétaire d'une ressource ou ait un rôle administrateur
 * 
 * @param int $resourceUserId ID de l'utilisateur propriétaire de la ressource
 * @param string $redirect URL de redirection si l'utilisateur n'est pas autorisé
 * @return void
 */
function requireOwnerOrAdmin($resourceUserId, $redirect = '/tutoring/access-denied.php') {
    requireLogin();
    
    if (!isOwner($resourceUserId) && !hasRole(['admin', 'coordinator'])) {
        redirect($redirect);
    }
}

/**
 * Génère un jeton CSRF (Cross-Site Request Forgery)
 * 
 * @return string Jeton CSRF
 */
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie un jeton CSRF
 * 
 * @param string $token Jeton à vérifier
 * @return boolean Vrai si le jeton est valide
 */
function verifyCsrfToken($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Vérifie si une requête API est authentifiée via Bearer token
 * 
 * @return array|false Données de l'utilisateur ou false si non authentifié
 */
function authenticateApiRequest() {
    // Récupérer l'en-tête Authorization
    $authHeader = getAuthHeader();
    
    // Vérifier que l'en-tête est présent
    if (!$authHeader) {
        return false;
    }
    
    // Extraire le token Bearer
    $token = JwtUtils::extractBearerToken($authHeader);
    if (!$token) {
        return false;
    }
    
    // Valider le token
    $payload = JwtUtils::validateToken($token);
    if (!$payload) {
        return false;
    }
    
    // Vérifier que c'est un token d'accès et pas de rafraîchissement
    if (isset($payload['type']) && $payload['type'] === 'refresh') {
        return false;
    }
    
    // Récupérer l'ID utilisateur
    $userId = $payload['sub'];
    
    // Charger l'utilisateur depuis la base de données
    global $db;
    $userModel = new User($db);
    $user = $userModel->getById($userId);
    
    // Vérifier que l'utilisateur existe et est actif
    if (!$user || $user['status'] !== 'active') {
        return false;
    }
    
    return $user;
}

/**
 * Récupère l'en-tête Authorization
 * 
 * @return string|false En-tête Authorization ou false s'il n'existe pas
 */
function getAuthHeader() {
    // Essayer de récupérer l'en-tête via getallheaders() si disponible
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        foreach ($headers as $name => $value) {
            if (strtolower($name) === 'authorization') {
                return $value;
            }
        }
    }
    
    // Sinon, essayer avec les variables de serveur
    $authHeader = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : false;
    
    // Pour les serveurs Apache qui n'exposent pas HTTP_AUTHORIZATION
    if (!$authHeader && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    }
    
    return $authHeader;
}

/**
 * Requiert que la requête API ait un rôle spécifique
 * 
 * @param string|array $roles Rôle(s) requis
 * @return array Données de l'utilisateur authentifié
 */
function requireApiRole($roles) {
    $user = authenticateApiRequest();
    
    if (!$user) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Non authentifié'
        ]);
        exit;
    }
    
    if (!is_array($roles)) {
        $roles = [$roles];
    }
    
    if (!in_array($user['role'], $roles)) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Accès non autorisé'
        ]);
        exit;
    }
    
    return $user;
}

/**
 * Récupère l'utilisateur courant (depuis la session ou l'API)
 * 
 * @return array|null Données de l'utilisateur ou null si non connecté
 */
function getCurrentUser() {
    // Vérifier d'abord la session
    if (isLoggedIn()) {
        global $db;
        $userModel = new User($db);
        return $userModel->getById($_SESSION['user_id']);
    }
    
    // Sinon, essayer l'authentification API
    return authenticateApiRequest();
}