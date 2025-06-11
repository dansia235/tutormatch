<?php
/**
 * Traitement du formulaire de modification d'un enseignant
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../../includes/init.php';

// Vérifier les permissions
requireRole(['admin', 'coordinator']);

// Vérifier l'ID
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    setFlashMessage('error', 'ID d\'enseignant invalide');
    redirect('/tutoring/views/admin/tutors.php');
}

// Instancier le contrôleur
$teacherController = new TeacherController($db);

// Traiter la modification d'enseignant
$teacherController->update($_POST['id']);