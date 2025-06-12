<?php
/**
 * Traitement de la suppression de document
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../../includes/init.php';

// Vérifier les permissions
requireRole(['admin', 'coordinator']);

// Vérifier que l'ID du document est fourni
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    setFlashMessage('error', 'ID de document invalide');
    redirect('/tutoring/views/admin/documents.php');
    exit;
}

$documentId = (int)$_POST['id'];

// Vérifier le jeton CSRF
if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    setFlashMessage('error', 'Erreur de sécurité. Veuillez réessayer.');
    redirect('/tutoring/views/admin/documents.php');
    exit;
}

// Récupérer les informations du document
$documentModel = new Document($db);
$document = $documentModel->getById($documentId);

if (!$document) {
    setFlashMessage('error', 'Document non trouvé');
    redirect('/tutoring/views/admin/documents.php');
    exit;
}

// Supprimer le fichier physique
$filePath = ROOT_PATH . '/' . $document['file_path'];
if (file_exists($filePath)) {
    unlink($filePath);
    error_log("Fichier supprimé: " . $filePath);
} else {
    error_log("Fichier introuvable: " . $filePath);
}

// Supprimer l'enregistrement dans la base de données
if ($documentModel->delete($documentId)) {
    setFlashMessage('success', 'Document supprimé avec succès');
} else {
    setFlashMessage('error', 'Erreur lors de la suppression du document');
}

// Rediriger vers la liste des documents
redirect('/tutoring/views/admin/documents.php');