<?php
/**
 * Traitement de la suppression d'un enseignant
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../../includes/init.php';

// Vérifier les permissions
requireRole(['admin']);

// Vérifier l'ID
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    setFlashMessage('error', 'ID d\'enseignant invalide');
    redirect('/tutoring/views/admin/tutors.php');
}

// Instancier le contrôleur
$teacherController = new TeacherController($db);

// Traiter la suppression d'enseignant
$teacherController->delete($_POST['id']);