<?php
/**
 * Page d'accueil du système
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/includes/init.php';

// Rediriger vers la page appropriée selon le rôle de l'utilisateur
if (isLoggedIn()) {
    switch ($_SESSION['user_role']) {
        case 'admin':
            redirect('/tutoring/views/admin/dashboard.php');
            break;
        case 'coordinator':
            redirect('/tutoring/views/admin/dashboard.php');
            break;
        case 'teacher':
            redirect('/tutoring/views/tutor/dashboard.php');
            break;
        case 'student':
            redirect('/tutoring/views/student/dashboard.php');
            break;
        default:
            // Si le rôle n'est pas reconnu, déconnecter l'utilisateur
            session_destroy();
            redirect('/tutoring/login.php');
    }
} else {
    // Si l'utilisateur n'est pas connecté, rediriger vers la page de connexion
    redirect('/tutoring/login.php');
}