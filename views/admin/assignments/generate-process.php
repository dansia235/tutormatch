<?php
/**
 * Traitement de la génération automatique d'affectations
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../../includes/init.php';

// Vérifier les permissions
requireRole(['admin', 'coordinator']);

// S'assurer que la connexion à la base de données est disponible
if (!isset($db) || $db === null) {
    $db = getDBConnection();
}

// Instancier le contrôleur
$assignmentController = new AssignmentController($db);

// Traiter la génération
$assignmentController->generateAssignments();