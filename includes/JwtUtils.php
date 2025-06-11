<?php
/**
 * Utilitaires pour la gestion des JWT (JSON Web Tokens)
 */
class JwtUtils {
    /**
     * Clé secrète pour signer les tokens
     * En production, utilisez une clé sécurisée et stockée dans une variable d'environnement
     */
    private static $secretKey = 'your_secret_key_here'; // À remplacer par une clé sécurisée en production
    
    /**
     * Durée de validité du token d'accès (1 heure par défaut)
     */
    private static $accessTokenExpiry = 3600;
    
    /**
     * Durée de validité du token de rafraîchissement (14 jours par défaut)
     */
    private static $refreshTokenExpiry = 1209600;
    
    /**
     * Génère un token JWT pour l'utilisateur
     * 
     * @param array $user Données de l'utilisateur
     * @param bool $isRefreshToken Indique s'il s'agit d'un token de rafraîchissement
     * @return string Token JWT
     */
    public static function generateToken($user, $isRefreshToken = false) {
        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256'
        ];
        
        $issuedAt = time();
        $expiry = $isRefreshToken ? $issuedAt + self::$refreshTokenExpiry : $issuedAt + self::$accessTokenExpiry;
        
        $payload = [
            'sub' => $user['id'],
            'name' => $user['first_name'] . ' ' . $user['last_name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'iat' => $issuedAt,
            'exp' => $expiry,
            'type' => $isRefreshToken ? 'refresh' : 'access'
        ];
        
        // Encodage Base64Url des parties du token
        $base64UrlHeader = self::base64UrlEncode(json_encode($header));
        $base64UrlPayload = self::base64UrlEncode(json_encode($payload));
        
        // Création de la signature
        $signature = hash_hmac('sha256', $base64UrlHeader . '.' . $base64UrlPayload, self::$secretKey, true);
        $base64UrlSignature = self::base64UrlEncode($signature);
        
        // Création du token JWT
        return $base64UrlHeader . '.' . $base64UrlPayload . '.' . $base64UrlSignature;
    }
    
    /**
     * Valide un token JWT et retourne les données décodées
     * 
     * @param string $token Token JWT à valider
     * @return array|false Données décodées du token ou false si le token est invalide
     */
    public static function validateToken($token) {
        // Séparer les parties du token
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return false;
        }
        
        list($base64UrlHeader, $base64UrlPayload, $base64UrlSignature) = $parts;
        
        // Vérifier la signature
        $signature = self::base64UrlDecode($base64UrlSignature);
        $expectedSignature = hash_hmac('sha256', $base64UrlHeader . '.' . $base64UrlPayload, self::$secretKey, true);
        
        if (!hash_equals($signature, $expectedSignature)) {
            return false;
        }
        
        // Décoder le payload
        $payload = json_decode(self::base64UrlDecode($base64UrlPayload), true);
        
        // Vérifier si le token a expiré
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return false;
        }
        
        return $payload;
    }
    
    /**
     * Extrait le token Bearer de l'en-tête Authorization
     * 
     * @param string $authHeader En-tête Authorization
     * @return string|false Token extrait ou false si le format est invalide
     */
    public static function extractBearerToken($authHeader) {
        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return $matches[1];
        }
        return false;
    }
    
    /**
     * Encode en Base64Url
     * 
     * @param string $data Données à encoder
     * @return string Données encodées
     */
    private static function base64UrlEncode($data) {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }
    
    /**
     * Décode du Base64Url
     * 
     * @param string $data Données à décoder
     * @return string Données décodées
     */
    private static function base64UrlDecode($data) {
        $base64 = str_replace(['-', '_'], ['+', '/'], $data);
        $padLength = 4 - strlen($base64) % 4;
        if ($padLength < 4) {
            $base64 .= str_repeat('=', $padLength);
        }
        return base64_decode($base64);
    }
}