<?php
/**
 * Script de téléchargement de document
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . "/../../../includes/init.php";

// Vérifier que l'utilisateur est connecté
requireLogin();

// Vérifier l'ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage('error', 'ID de document invalide');
    redirect("/tutoring/views/admin/documents.php");
    exit;
}

// Instancier le contrôleur
$documentController = new DocumentController($db);

// Traiter le téléchargement
$documentController->download($_GET['id']);
