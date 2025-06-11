<?php
/**
 * Traitement de la suppression d'affectation
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../../includes/init.php';

// Vérifier les permissions
requireRole(['admin', 'coordinator']);

// Vérifier l'ID
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    setFlashMessage('error', 'ID d\'affectation invalide');
    redirect('/tutoring/views/admin/assignments.php');
}

// S'assurer que la connexion à la base de données est disponible
if (!isset($db) || $db === null) {
    $db = getDBConnection();
}

// Instancier le contrôleur
$assignmentController = new AssignmentController($db);

// Traiter la suppression
$assignmentController->delete($_POST['id']);