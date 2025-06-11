<?php
/**
 * Traitement du formulaire de création d'un étudiant
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../../includes/init.php';

// Vérifier les permissions
requireRole(['admin', 'coordinator']);

// Instancier le contrôleur
$studentController = new StudentController($db);

// Traiter la création d'étudiant
$studentController->store();