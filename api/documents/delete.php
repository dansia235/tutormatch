<?php
/**
 * Supprimer un document
 * DELETE /api/documents/{id}
 */

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    sendError('Méthode non autorisée', 405);
}

// Vérifier que l'ID est présent
if (!isset($urlParts[2]) || !is_numeric($urlParts[2])) {
    sendError('ID de document invalide', 400);
}

$documentId = (int)$urlParts[2];

// Initialiser les modèles
$documentModel = new Document($db);
$userModel = new User($db);
$studentModel = new Student($db);
$teacherModel = new Teacher($db);
$assignmentModel = new Assignment($db);

// Récupérer le document
$document = $documentModel->getById($documentId);
if (!$document) {
    sendError('Document non trouvé', 404);
}

// Vérifier les permissions
$currentUserRole = $_SESSION['user_role'];
$currentUserId = $_SESSION['user_id'];

// Déterminer si l'utilisateur peut supprimer le document
$canDelete = false;

if ($currentUserRole === 'admin' || $currentUserRole === 'coordinator') {
    // Les administrateurs et coordinateurs peuvent supprimer tous les documents
    $canDelete = true;
} else {
    // Pour les étudiants et tuteurs, vérifier s'ils sont propriétaires du document
    if ($document['user_id'] == $currentUserId) {
        $canDelete = true;
    }
    
    // Pour les tuteurs, vérifier s'ils sont associés à l'affectation du document
    if ($currentUserRole === 'teacher' && $document['assignment_id']) {
        $assignment = $assignmentModel->getById($document['assignment_id']);
        if ($assignment) {
            $teacher = $teacherModel->getByUserId($currentUserId);
            if ($teacher && $teacher['id'] == $assignment['teacher_id']) {
                $canDelete = true;
            }
        }
    }
}

if (!$canDelete) {
    sendError('Vous n\'êtes pas autorisé à supprimer ce document', 403);
}

// Récupérer le chemin physique du fichier
$filePath = $_SERVER['DOCUMENT_ROOT'] . '/tutoring/uploads/' . $document['file_path'];

// Supprimer le document de la base de données
$success = $documentModel->delete($documentId);
if (!$success) {
    sendError('Échec de la suppression du document', 500);
}

// Supprimer le fichier physique
if (file_exists($filePath)) {
    unlink($filePath);
}

// Envoyer la réponse
sendJsonResponse([
    'message' => 'Document supprimé avec succès'
]);