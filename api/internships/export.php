<?php
/**
 * API pour exporter les stages
 * Endpoint: /api/internships/export.php
 * Méthode: GET
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../includes/init.php';

// Vérifier que l'utilisateur est connecté et a les droits
if (!isLoggedIn() || !hasRole(['admin', 'coordinator', 'teacher'])) {
    header("HTTP/1.1 403 Forbidden");
    echo "Accès non autorisé";
    exit;
}

// Rediriger vers le véritable point d'API d'exportation
header('Location: /tutoring/api/export/internships.php?' . $_SERVER['QUERY_STRING']);
exit;