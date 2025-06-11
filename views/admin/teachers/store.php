<?php
/**
 * Traitement du formulaire de création d'un enseignant
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../../includes/init.php';

// Vérifier les permissions
requireRole(['admin', 'coordinator']);

// Instancier le contrôleur
$teacherController = new TeacherController($db);

// Traiter la création d'enseignant
$teacherController->store();