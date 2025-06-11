<?php
/**
 * Traitement de la suppression de document
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../../includes/init.php';

// Vérifier les permissions
requireRole(['admin', 'coordinator']);

// Vérifier l'ID
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    setFlashMessage('error', 'ID de document invalide');
    redirect('/tutoring/views/admin/documents/index.php');
}

// Instancier le contrôleur
$documentController = new DocumentController($db);

// Traiter la suppression
$documentController->delete($_POST['id']);