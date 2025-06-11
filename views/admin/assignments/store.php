<?php
/**
 * Traitement du formulaire de création d'affectation
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../../includes/init.php';

// Vérifier les permissions
requireRole(['admin', 'coordinator']);

// Instancier le contrôleur
$assignmentController = new AssignmentController($db);

// Traiter la création
$assignmentController->store();