<?php
/**
 * Traitement du téléchargement de document
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../../includes/init.php';

// Vérifier les permissions
requireRole(['admin', 'coordinator']);

// Vérifier l'ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage('error', 'ID de document invalide');
    redirect('/tutoring/views/admin/documents/index.php');
}

// Vérifier que $db est défini
if (!isset($db) || !$db) {
    // Si $db n'est pas défini, on essaie de le récupérer à nouveau
    try {
        require_once __DIR__ . '/../../../config/database.php';
        $db = getDBConnection();
    } catch (Exception $e) {
        die("Erreur critique: Impossible de se connecter à la base de données.");
    }
}

// Instancier le modèle et récupérer directement le document
try {
    $documentModel = new Document($db);
    $document = $documentModel->getById($_GET['id']);
    
    // Vérifier si le document existe
    if (!$document) {
        setFlashMessage('error', 'Document non trouvé');
        redirect('/tutoring/views/admin/documents/index.php');
        exit;
    }
    
    // Vérifier les autorisations de visibilité
    $visibility = isset($document['visibility']) ? $document['visibility'] : 'private';
    $userId = isset($document['user_id']) ? $document['user_id'] : 0;
    
    if ($visibility === 'private' && $userId != $_SESSION['user_id'] && !hasRole(['admin', 'coordinator'])) {
        setFlashMessage('error', "Vous n'avez pas accès à ce document");
        redirect('/tutoring/dashboard.php');
        exit;
    }
    
    // Chemin complet du fichier
    $filePath = ROOT_PATH . (isset($document['file_path']) ? $document['file_path'] : '');
    
    // Vérifier si le fichier existe
    if (empty($filePath) || !file_exists($filePath)) {
        setFlashMessage('error', 'Fichier non trouvé');
        redirect('/tutoring/views/admin/documents/show.php?id=' . $_GET['id']);
        exit;
    }
    
    // Déterminer le type MIME
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $filePath);
    finfo_close($finfo);
    
    // Nom de fichier pour le téléchargement
    $filename = basename($filePath);
    
    // En-têtes HTTP pour le téléchargement
    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    
    // Lire et envoyer le fichier
    readfile($filePath);
    exit;
    
} catch (Exception $e) {
    setFlashMessage('error', "Erreur lors du téléchargement: " . $e->getMessage());
    redirect('/tutoring/views/admin/documents/index.php');
}