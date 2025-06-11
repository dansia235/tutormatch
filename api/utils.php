<?php
/**
 * Utilitaires pour l'API
 */

/**
 * Vérifie si une requête est authentifiée via JWT
 * 
 * @param PDO $db Connexion à la base de données
 * @return array|false Données utilisateur ou false si non authentifié
 */
function authenticateRequest($db) {
    // Récupérer l'en-tête Authorization
    $authHeader = getAuthorizationHeader();
    
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
    
    // Vérifier que le token n'est pas un token de rafraîchissement
    if (isset($payload['type']) && $payload['type'] === 'refresh') {
        return false;
    }
    
    // Récupérer l'ID utilisateur
    $userId = $payload['sub'];
    
    // Charger l'utilisateur depuis la base de données
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
function getAuthorizationHeader() {
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
    
    // Pour les serveurs Apache avec mod_rewrite
    if (!$authHeader && function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : false;
    }
    
    return $authHeader;
}

/**
 * Vérifie si l'utilisateur a un rôle autorisé
 * 
 * @param array $user Données utilisateur
 * @param array $allowedRoles Rôles autorisés
 * @return boolean Vrai si l'utilisateur a un rôle autorisé
 */
function hasRole($user, $allowedRoles) {
    if (!$user || !isset($user['role'])) {
        return false;
    }
    
    return in_array($user['role'], $allowedRoles);
}

/**
 * Requiert un rôle spécifique pour accéder à l'API
 * 
 * @param PDO $db Connexion à la base de données
 * @param array $allowedRoles Rôles autorisés
 */
function requireApiRole($db, $allowedRoles) {
    $user = authenticateRequest($db);
    
    if (!$user || !hasRole($user, $allowedRoles)) {
        sendError('Accès non autorisé', 403);
    }
    
    return $user;
}

/**
 * Envoie une réponse JSON
 * 
 * @param mixed $data Données à envoyer
 * @param int $statusCode Code HTTP de statut
 */
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

/**
 * Envoie une erreur en JSON
 * 
 * @param string $message Message d'erreur
 * @param int $statusCode Code HTTP d'erreur
 */
function sendError($message, $statusCode = 400) {
    sendJsonResponse([
        'success' => false,
        'message' => $message
    ], $statusCode);
}

/**
 * Valide les données d'entrée pour une API
 * 
 * @param array $data Données à valider
 * @param array $rules Règles de validation (champ => règle)
 * @return array|true Erreurs de validation ou true si valide
 */
function validateApiInput($data, $rules) {
    $errors = [];
    
    foreach ($rules as $field => $rule) {
        // Règle requise
        if (strpos($rule, 'required') !== false && (!isset($data[$field]) || trim($data[$field]) === '')) {
            $errors[$field] = "Le champ $field est requis";
            continue;
        }
        
        // Si le champ n'est pas présent et n'est pas requis, passer à la suite
        if (!isset($data[$field]) || $data[$field] === '') {
            continue;
        }
        
        // Valider le type
        if (strpos($rule, 'integer') !== false && !filter_var($data[$field], FILTER_VALIDATE_INT)) {
            $errors[$field] = "Le champ $field doit être un nombre entier";
        }
        
        if (strpos($rule, 'numeric') !== false && !is_numeric($data[$field])) {
            $errors[$field] = "Le champ $field doit être un nombre";
        }
        
        if (strpos($rule, 'email') !== false && !filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
            $errors[$field] = "Le champ $field doit être une adresse email valide";
        }
        
        // Valider la longueur
        if (preg_match('/min:(\d+)/', $rule, $matches) && strlen($data[$field]) < $matches[1]) {
            $errors[$field] = "Le champ $field doit contenir au moins {$matches[1]} caractères";
        }
        
        if (preg_match('/max:(\d+)/', $rule, $matches) && strlen($data[$field]) > $matches[1]) {
            $errors[$field] = "Le champ $field ne doit pas dépasser {$matches[1]} caractères";
        }
        
        // Valider le format de date
        if (strpos($rule, 'date') !== false && !strtotime($data[$field])) {
            $errors[$field] = "Le champ $field doit être une date valide";
        }
    }
    
    return empty($errors) ? true : $errors;
}

/**
 * Vérifie si une requête est faite en AJAX
 * 
 * @return boolean Vrai si la requête est AJAX
 */
function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}