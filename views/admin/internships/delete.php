<?php
/**
 * Traitement de la suppression d'un stage
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../../includes/init.php';

// Vérifier les permissions
requireRole(['admin', 'coordinator']);

// Vérifier l'ID
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    setFlashMessage('error', 'ID de stage invalide');
    redirect('/tutoring/views/admin/internships.php');
}

// Instancier le contrôleur
$internshipController = new InternshipController($db);

// Traiter la suppression du stage
$internshipController->delete($_POST['id']);