<?php
/**
 * Traitement du formulaire de modification d'affectation
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../../includes/init.php';

// Vérifier les permissions
requireRole(['admin', 'coordinator']);

// Vérifier l'ID
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    setFlashMessage('error', 'ID d\'affectation invalide');
    redirect('/tutoring/views/admin/assignments/index.php');
}

// Instancier le contrôleur
$assignmentController = new AssignmentController($db);

// Traiter la mise à jour
$assignmentController->update($_POST['id']);