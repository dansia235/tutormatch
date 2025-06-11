<?php
/**
 * Traitement du formulaire d'ajout de document
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../../includes/init.php';

// Vérifier les permissions
requireRole(['admin', 'coordinator']);

// Instancier le contrôleur
$documentController = new DocumentController($db);

// Traiter l'ajout
$documentController->store();