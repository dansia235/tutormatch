<?php
/**
 * Point d'entrée pour la mise à jour d'un utilisateur
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../includes/init.php';

// Inclure l'intercepteur de redirection
require_once __DIR__ . '/../../includes/RedirectInterceptor.php';

// Enregistrer l'URL de redirection par défaut en cas de succès
$_SESSION['success_redirect'] = '/tutoring/views/admin/users.php';

// Enregistrer les mappings de redirection pour ce contrôleur
$_SESSION['redirect_mappings'] = [
    '/tutoring/admin/users/edit.php' => '/tutoring/views/admin/user/edit.php',
    '/tutoring/admin/users/index.php' => '/tutoring/views/admin/users.php'
];

// Instancier le contrôleur
$userController = new UserController($db);

// Traiter la mise à jour de l'utilisateur
// Get the user ID from POST data
$userId = isset($_POST['id']) ? intval($_POST['id']) : 0;

if ($userId <= 0) {
    setFlashMessage('error', 'ID d\'utilisateur invalide.');
    header("Location: /tutoring/views/admin/users.php");
    exit;
}

$userController->update($userId);

// Si nous arrivons ici, le contrôleur n'a pas redirigé, alors rediriger vers la page de liste
header("Location: /tutoring/views/admin/users.php");
exit;