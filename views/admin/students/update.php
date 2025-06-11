<?php
/**
 * Traitement du formulaire de modification d'un étudiant
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../../includes/init.php';

// Vérifier les permissions
requireRole(['admin', 'coordinator']);

// Vérifier l'ID
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    setFlashMessage('error', 'ID d\'étudiant invalide');
    redirect('/tutoring/views/admin/students.php');
}

// Instancier le contrôleur
$studentController = new StudentController($db);

// Traiter la modification d'étudiant
$studentController->update($_POST['id']);