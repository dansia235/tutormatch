<?php
/**
 * Traitement du formulaire de création d'un stage
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../../includes/init.php';

// Vérifier les permissions
requireRole(['admin', 'coordinator']);

// Instancier le contrôleur
$internshipController = new InternshipController($db);

// Traiter la création du stage
$internshipController->store();