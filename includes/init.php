<?php
/**
 * Fichier d'initialisation du système
 * Ce fichier est inclus dans toutes les pages du système
 */

// Démarrer la session
session_start();

// Définir le fuseau horaire
date_default_timezone_set('Europe/Paris');

// Définir l'encodage
mb_internal_encoding('UTF-8');

// Afficher toutes les erreurs en développement
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Chemin racine du système
define('ROOT_PATH', dirname(__DIR__));

// Inclure les fichiers de configuration
require_once ROOT_PATH . '/config/database.php';

// Fonction d'autoloading des classes
spl_autoload_register(function ($class_name) {
    $model_file = ROOT_PATH . '/models/' . $class_name . '.php';
    $controller_file = ROOT_PATH . '/controllers/' . $class_name . '.php';
    
    if (file_exists($model_file)) {
        require_once $model_file;
    } elseif (file_exists($controller_file)) {
        require_once $controller_file;
    }
});

// Connexion à la base de données avec gestion des erreurs de connexion
try {
    $db = getDBConnection();
    
    // Fermeture automatique de la connexion à la fin du script
    register_shutdown_function(function() {
        global $db;
        if ($db) {
            $db = null; // Libérer la connexion
        }
    });
    
} catch (Exception $e) {
    error_log("Erreur lors de la connexion à la base de données: " . $e->getMessage());
    
    // En mode production, afficher un message plus convivial
    if (strpos($e->getMessage(), 'Too many connections') !== false) {
        die('Le système est actuellement surchargé. Veuillez réessayer dans quelques instants.');
    }
    
    // En mode développement, l'erreur complète sera affichée par getDBConnection()
}

// Fonctions utilitaires

/**
 * Redirige vers une URL
 * @param string $url URL de redirection
 */
function redirect($url) {
    // Vérifier s'il y a un hook de redirection
    if (isset($_SESSION['redirect_hook']) && is_callable($_SESSION['redirect_hook'])) {
        $hook = $_SESSION['redirect_hook'];
        $hook($url);
        // Le hook doit gérer la sortie, mais au cas où
        exit;
    }
    
    // Vérifier s'il y a des mappings de redirection en session
    if (isset($_SESSION['redirect_mappings']) && is_array($_SESSION['redirect_mappings'])) {
        foreach ($_SESSION['redirect_mappings'] as $from => $to) {
            if (strpos($url, $from) !== false) {
                $url = str_replace($from, $to, $url);
                break;
            }
        }
    }
    
    // Redirection standard
    header("Location: $url");
    exit;
}

/**
 * Vérifie si l'utilisateur est connecté
 * @return bool True si l'utilisateur est connecté, sinon false
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Vérifie si l'utilisateur a un rôle spécifique
 * @param string|array $roles Rôle(s) à vérifier
 * @return bool True si l'utilisateur a le rôle, sinon false
 */
function hasRole($roles) {
    if (!isLoggedIn()) {
        return false;
    }
    
    if (is_string($roles)) {
        $roles = [$roles];
    }
    
    return in_array($_SESSION['user_role'], $roles);
}

/**
 * Requiert que l'utilisateur soit connecté, sinon redirige vers la page de connexion
 */
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        redirect('/tutoring/login.php');
    }
}

/**
 * Requiert que l'utilisateur ait un rôle spécifique, sinon redirige vers la page d'accès refusé
 * @param string|array $roles Rôle(s) requis
 */
function requireRole($roles) {
    requireLogin();
    
    if (!hasRole($roles)) {
        redirect('/tutoring/access-denied.php');
    }
}

/**
 * Échappe les caractères spéciaux dans une chaîne pour affichage HTML
 * @param string|null $str Chaîne à échapper
 * @return string Chaîne échappée
 */
function h($str) {
    // Ensure $str is a string to avoid deprecation warnings with null values
    if ($str === null) {
        return '';
    }
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

/**
 * Génère un jeton CSRF et le stocke en session
 * @return string Jeton CSRF
 */
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie si le jeton CSRF fourni est valide
 * @param string $token Jeton CSRF à vérifier
 * @return bool True si le jeton est valide, sinon false
 */
function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Affiche un message de notification (flash message)
 * @param string $type Type de message (success, error, warning, info)
 * @param string $message Contenu du message
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Récupère et supprime le message de notification
 * @return array|null Message de notification ou null s'il n'y en a pas
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

/**
 * Formate une date selon le format spécifié
 * @param string $date Date à formater
 * @param string $format Format de date (par défaut: 'd/m/Y')
 * @return string Date formatée
 */
function formatDate($date, $format = 'd/m/Y') {
    if (!$date) return '';
    
    $dateObj = new DateTime($date);
    return $dateObj->format($format);
}

/**
 * Tronque une chaîne à la longueur spécifiée
 * @param string $str Chaîne à tronquer
 * @param int $length Longueur maximale
 * @param string $append Texte à ajouter si la chaîne est tronquée
 * @return string Chaîne tronquée
 */
function truncate($str, $length = 100, $append = '...') {
    if (mb_strlen($str) <= $length) {
        return $str;
    }
    
    return mb_substr($str, 0, $length) . $append;
}

/**
 * Génère un slug à partir d'une chaîne
 * @param string $str Chaîne d'entrée
 * @return string Slug généré
 */
function generateSlug($str) {
    // Convertir en minuscules et supprimer les accents
    $str = mb_strtolower($str, 'UTF-8');
    $str = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
    
    // Remplacer tout ce qui n'est pas une lettre ou un chiffre par un tiret
    $str = preg_replace('/[^a-z0-9]/', '-', $str);
    
    // Remplacer les tirets multiples par un seul
    $str = preg_replace('/-+/', '-', $str);
    
    // Supprimer les tirets au début et à la fin
    return trim($str, '-');
}

/**
 * Vérifie si une chaîne est un email valide
 * @param string $email Email à vérifier
 * @return bool True si l'email est valide, sinon false
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Génère un mot de passe aléatoire
 * @param int $length Longueur du mot de passe
 * @return string Mot de passe généré
 */
function generateRandomPassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+;:,.?';
    $password = '';
    
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    
    return $password;
}

/**
 * Envoie un email
 * @param string $to Destinataire
 * @param string $subject Sujet
 * @param string $message Corps du message
 * @param array $headers En-têtes supplémentaires
 * @return bool Succès de l'envoi
 */
function sendEmail($to, $subject, $message, $headers = []) {
    // En-têtes par défaut
    $defaultHeaders = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: TutorMatch <noreply@tutormatch.com>'
    ];
    
    $allHeaders = array_merge($defaultHeaders, $headers);
    
    return mail($to, $subject, $message, implode("\r\n", $allHeaders));
}

/**
 * Télécharge un fichier vers le dossier d'uploads
 * @param array $file Tableau $_FILES
 * @param string $destination Sous-dossier de destination
 * @param array $allowedTypes Types MIME autorisés
 * @param int $maxSize Taille maximale en octets
 * @return string|false Chemin du fichier téléchargé, ou false en cas d'erreur
 */
function uploadFile($file, $destination = '', $allowedTypes = [], $maxSize = 5242880) {
    // Vérifier s'il y a une erreur
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    // Vérifier la taille
    if ($file['size'] > $maxSize) {
        return false;
    }
    
    // Vérifier le type MIME
    if (!empty($allowedTypes) && !in_array($file['type'], $allowedTypes)) {
        return false;
    }
    
    // Créer le dossier de destination s'il n'existe pas
    $uploadDir = ROOT_PATH . '/uploads/' . $destination;
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Générer un nom de fichier unique
    $fileInfo = pathinfo($file['name']);
    $newFilename = generateSlug($fileInfo['filename']) . '-' . uniqid() . '.' . $fileInfo['extension'];
    $targetPath = $uploadDir . '/' . $newFilename;
    
    // Déplacer le fichier
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return '/uploads/' . ($destination ? $destination . '/' : '') . $newFilename;
    }
    
    return false;
}

/**
 * Supprime un fichier d'uploads
 * @param string $filePath Chemin du fichier (relatif à ROOT_PATH)
 * @return bool Succès de la suppression
 */
function deleteFile($filePath) {
    $fullPath = ROOT_PATH . $filePath;
    
    if (file_exists($fullPath) && is_file($fullPath)) {
        return unlink($fullPath);
    }
    
    return false;
}

/**
 * Formate un montant avec le symbole de l'euro
 * @param float $amount Montant à formater
 * @return string Montant formaté
 */
function formatMoney($amount) {
    return number_format($amount, 2, ',', ' ') . ' €';
}

/**
 * Nettoie une valeur de spécialité (supprime les chemins de fichier)
 * @param string|null $specialty Valeur de spécialité à nettoyer
 * @return string Valeur nettoyée
 */
function cleanSpecialty($specialty) {
    if (empty($specialty)) {
        return '';
    }
    
    // Si la spécialité ressemble à un chemin de fichier, extraire juste le nom de base
    if (strpos($specialty, '/') !== false || strpos($specialty, '\\') !== false) {
        return basename($specialty);
    }
    
    // Si la spécialité ressemble à une URL, extraire le domaine ou le chemin
    if (filter_var($specialty, FILTER_VALIDATE_URL)) {
        $parts = parse_url($specialty);
        if (isset($parts['path']) && !empty($parts['path'])) {
            return basename($parts['path']);
        } elseif (isset($parts['host'])) {
            return $parts['host'];
        }
    }
    
    // Sinon, renvoyer la valeur d'origine
    return $specialty;
}