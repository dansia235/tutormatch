<?php
/**
 * Point d'entrée principal de l'API REST
 * Ce fichier gère les requêtes entrantes et les dirige vers les contrôleurs appropriés
 */

// Désactiver l'affichage des erreurs en production
ini_set('display_errors', 0);

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../includes/init.php';

// Définir les en-têtes pour l'API REST
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Gérer les requêtes OPTIONS (pre-flight CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Extraire le chemin de la requête
$requestPath = $_SERVER['REQUEST_URI'];
$basePath = '/tutoring/api/';

// Retirer le chemin de base pour isoler le point de terminaison
if (strpos($requestPath, $basePath) === 0) {
    $endpoint = substr($requestPath, strlen($basePath));
} else {
    $endpoint = $requestPath;
}

// Extraire les paramètres de l'URL
$endpoint = parse_url($endpoint, PHP_URL_PATH);
$endpoint = trim($endpoint, '/');
$urlParts = explode('/', $endpoint);

// Définir la ressource et l'action
$resource = !empty($urlParts[0]) ? $urlParts[0] : 'home';
$action = isset($urlParts[1]) ? $urlParts[1] : 'index';
$id = isset($urlParts[2]) ? $urlParts[2] : null;
$subResource = isset($urlParts[3]) ? $urlParts[3] : null;

// Décodage du corps de la requête pour les méthodes POST et PUT
$requestBody = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT') {
    $requestBody = json_decode(file_get_contents('php://input'), true);
}

// Tableau des ressources valides et leurs fichiers associés
$resourceMap = [
    'auth' => [
        'login' => __DIR__ . '/auth/login.php',
        'logout' => __DIR__ . '/auth/logout.php',
        'refresh' => __DIR__ . '/auth/refresh.php'
    ],
    'users' => [
        'index' => __DIR__ . '/users/index.php',
        'show' => __DIR__ . '/users/show.php',
        'create' => __DIR__ . '/users/create.php',
        'update' => __DIR__ . '/users/update.php',
        'delete' => __DIR__ . '/users/delete.php',
        'profile' => __DIR__ . '/users/profile.php'
    ],
    'students' => [
        'index' => __DIR__ . '/students/index.php',
        'show' => __DIR__ . '/students/show.php',
        'assignments' => __DIR__ . '/students/assignments.php',
        'preferences' => __DIR__ . '/students/preferences.php',
        'internships' => __DIR__ . '/students/internships.php'
    ],
    'teachers' => [
        'index' => __DIR__ . '/teachers/index.php',
        'show' => __DIR__ . '/teachers/show.php',
        'students' => __DIR__ . '/teachers/students.php',
        'availability' => __DIR__ . '/teachers/availability.php'
    ],
    'internships' => [
        'index' => __DIR__ . '/internships/index.php',
        'show' => __DIR__ . '/internships/show.php',
        'create' => __DIR__ . '/internships/create.php',
        'update' => __DIR__ . '/internships/update.php',
        'delete' => __DIR__ . '/internships/delete.php',
        'available' => __DIR__ . '/internships/available.php'
    ],
    'assignments' => [
        'index' => __DIR__ . '/assignments/index.php',
        'show' => __DIR__ . '/assignments/show.php',
        'create' => __DIR__ . '/assignments/create.php',
        'update' => __DIR__ . '/assignments/update.php',
        'status' => __DIR__ . '/assignments/status.php'
    ],
    'documents' => [
        'index' => __DIR__ . '/documents/index.php',
        'show' => __DIR__ . '/documents/show.php',
        'upload' => __DIR__ . '/documents/upload.php',
        'download' => __DIR__ . '/documents/download.php',
        'delete' => __DIR__ . '/documents/delete.php'
    ],
    'meetings' => [
        'index' => __DIR__ . '/meetings/index.php',
        'show' => __DIR__ . '/meetings/show.php',
        'create' => __DIR__ . '/meetings/create.php',
        'update' => __DIR__ . '/meetings/update.php',
        'delete' => __DIR__ . '/meetings/delete.php',
        'participants' => __DIR__ . '/meetings/participants.php'
    ],
    'messages' => [
        'index' => __DIR__ . '/messages/index.php',
        'conversations' => __DIR__ . '/messages/conversations.php',
        'send' => __DIR__ . '/messages/send.php',
        'show' => __DIR__ . '/messages/show.php',
        'mark-read' => __DIR__ . '/messages/mark-read.php'
    ],
    'evaluations' => [
        'index' => __DIR__ . '/evaluations/index.php',
        'show' => __DIR__ . '/evaluations/show.php',
        'create' => __DIR__ . '/evaluations/create.php',
        'update' => __DIR__ . '/evaluations/update.php',
        'reports' => __DIR__ . '/evaluations/reports.php'
    ],
    'companies' => [
        'index' => __DIR__ . '/companies/index.php',
        'show' => __DIR__ . '/companies/show.php',
        'create' => __DIR__ . '/companies/create.php',
        'update' => __DIR__ . '/companies/update.php'
    ],
    'notifications' => [
        'index' => __DIR__ . '/notifications/index.php',
        'unread' => __DIR__ . '/notifications/unread.php',
        'mark-read' => __DIR__ . '/notifications/mark-read.php'
    ]
];

// Fonction pour envoyer une réponse JSON
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

// Fonction pour envoyer une erreur
function sendError($message, $statusCode = 400) {
    sendJsonResponse([
        'error' => true,
        'message' => $message
    ], $statusCode);
}

// Vérifier si la ressource existe
if (!isset($resourceMap[$resource])) {
    sendError('Ressource non trouvée', 404);
}

// Déterminer le fichier à inclure en fonction de la méthode HTTP et de la ressource
$file = null;

// Gestion des méthodes HTTP
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if ($id) {
            if ($subResource && isset($resourceMap[$resource][$subResource])) {
                $file = $resourceMap[$resource][$subResource];
            } else {
                $file = $resourceMap[$resource]['show'];
            }
        } else if ($action && $action !== 'index' && isset($resourceMap[$resource][$action])) {
            $file = $resourceMap[$resource][$action];
        } else {
            $file = $resourceMap[$resource]['index'];
        }
        break;
        
    case 'POST':
        if ($id && $subResource && isset($resourceMap[$resource][$subResource])) {
            $file = $resourceMap[$resource][$subResource];
        } else if ($resource === 'auth' && isset($resourceMap[$resource][$action])) {
            $file = $resourceMap[$resource][$action];
        } else if ($resource === 'messages' && $action === 'send') {
            $file = $resourceMap[$resource]['send'];
        } else {
            $file = $resourceMap[$resource]['create'];
        }
        break;
        
    case 'PUT':
        if ($id && $subResource && isset($resourceMap[$resource][$subResource])) {
            $file = $resourceMap[$resource][$subResource];
        } else if ($id) {
            $file = $resourceMap[$resource]['update'];
        } else {
            sendError('Méthode non autorisée', 405);
        }
        break;
        
    case 'DELETE':
        if ($id) {
            $file = $resourceMap[$resource]['delete'];
        } else {
            sendError('Méthode non autorisée', 405);
        }
        break;
        
    default:
        sendError('Méthode non supportée', 405);
}

// Vérifier si le fichier existe
if (!$file || !file_exists($file)) {
    sendError('Point de terminaison non trouvé', 404);
}

// Inclure la classe JwtUtils
require_once __DIR__ . '/../includes/JwtUtils.php';

// Vérifier l'authentification pour les routes protégées
// On exclut les routes d'authentification et certaines routes publiques
$publicRoutes = [
    'auth/login',
    'auth/refresh'
];

$currentRoute = $resource . '/' . $action;
if (!in_array($currentRoute, $publicRoutes)) {
    // Vérifier si le token JWT est présent et valide
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!$authHeader) {
        sendError('Non autorisé - Token manquant', 401);
    }
    
    // Extraire le token Bearer
    $token = JwtUtils::extractBearerToken($authHeader);
    if (!$token) {
        sendError('Non autorisé - Format de token invalide', 401);
    }
    
    // Valider le token
    $tokenData = JwtUtils::validateToken($token);
    if (!$tokenData) {
        sendError('Non autorisé - Token invalide ou expiré', 401);
    }
    
    // Vérifier que c'est un token d'accès
    if (isset($tokenData['type']) && $tokenData['type'] !== 'access') {
        sendError('Non autorisé - Type de token invalide', 401);
    }
    
    // Mettre les données utilisateur dans la requête pour un accès facile
    $_SESSION['user_id'] = $tokenData['sub'];
    $_SESSION['user_role'] = $tokenData['role'];
}

// Inclure le fichier du contrôleur et exécuter l'action
include $file;