<?php
/**
 * Traitement de la génération automatique d'affectations
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../../includes/init.php';

// Vérifier les permissions
requireRole(['admin', 'coordinator']);

// Instancier le contrôleur
$assignmentController = new AssignmentController($db);

// Traiter la génération
$assignmentController->generateAssignments();