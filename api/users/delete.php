<?php
/**
 * Supprimer un utilisateur
 * DELETE /api/users/{id}
 */

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    sendError('Méthode non autorisée', 405);
}

// Vérifier les droits d'accès (seul l'administrateur peut supprimer des utilisateurs)
if (!hasRole('admin')) {
    sendError('Accès refusé', 403);
}

// Récupérer l'ID de l'utilisateur depuis l'URL
$userId = isset($urlParts[2]) ? (int)$urlParts[2] : 0;

if ($userId <= 0) {
    sendError('ID utilisateur invalide', 400);
}

// Empêcher la suppression de son propre compte
if ($userId === (int)$_SESSION['user_id']) {
    sendError('Vous ne pouvez pas supprimer votre propre compte', 400);
}

// Initialiser le modèle utilisateur
$userModel = new User($db);

// Vérifier si l'utilisateur existe
$existingUser = $userModel->getById($userId);
if (!$existingUser) {
    sendError('Utilisateur non trouvé', 404);
}

// Vérifier si l'utilisateur est un administrateur
if ($existingUser['role'] === 'admin') {
    // Compter le nombre d'administrateurs
    $adminCount = $userModel->countAdmins();
    
    // Empêcher la suppression du dernier administrateur
    if ($adminCount <= 1) {
        sendError('Impossible de supprimer le dernier administrateur', 400);
    }
}

// Supprimer l'utilisateur
$success = $userModel->delete($userId);

if (!$success) {
    sendError('Erreur lors de la suppression de l\'utilisateur', 500);
}

// Envoyer la réponse
sendJsonResponse([
    'success' => true,
    'message' => 'Utilisateur supprimé avec succès'
]);