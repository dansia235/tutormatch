<?php
/**
 * Liste des utilisateurs
 * GET /api/users
 */

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Méthode non autorisée', 405);
}

// Récupérer les paramètres de requête
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$role = isset($_GET['role']) ? $_GET['role'] : null;
$search = isset($_GET['search']) ? $_GET['search'] : null;

// Valider les paramètres
if ($page < 1) $page = 1;
if ($limit < 1 || $limit > 50) $limit = 10;

// Initialiser le modèle utilisateur
$userModel = new User($db);

// Construire les options de requête
$options = [
    'page' => $page,
    'limit' => $limit
];

if ($role) {
    $validRoles = ['admin', 'coordinator', 'teacher', 'student'];
    if (in_array($role, $validRoles)) {
        $options['role'] = $role;
    }
}

if ($search) {
    $options['search'] = $search;
}

// Récupérer les utilisateurs selon les filtres
$users = $userModel->getAll($options);
$total = $userModel->countAll($options);

// Calculer la pagination
$totalPages = ceil($total / $limit);

// Transformer les données pour l'API
$formattedUsers = [];
foreach ($users as $user) {
    // Masquer le mot de passe et autres informations sensibles
    unset($user['password']);
    
    $formattedUsers[] = $user;
}

// Envoyer la réponse
sendJsonResponse([
    'data' => $formattedUsers,
    'meta' => [
        'current_page' => $page,
        'total_pages' => $totalPages,
        'total_records' => $total,
        'per_page' => $limit
    ]
]);