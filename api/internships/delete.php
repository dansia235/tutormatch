<?php
/**
 * Supprimer un stage
 * DELETE /api/internships/{id}
 */

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    sendError('Méthode non autorisée', 405);
}

// Vérifier les droits d'accès (admin ou coordinateur)
if (!hasRole(['admin', 'coordinator'])) {
    sendError('Accès refusé', 403);
}

// Récupérer l'ID du stage depuis l'URL
$internshipId = isset($urlParts[2]) ? (int)$urlParts[2] : 0;

if ($internshipId <= 0) {
    sendError('ID stage invalide', 400);
}

// Initialiser le modèle stage
$internshipModel = new Internship($db);

// Récupérer le stage existant
$existingInternship = $internshipModel->getById($internshipId);

if (!$existingInternship) {
    sendError('Stage non trouvé', 404);
}

// Vérifier si le stage est déjà assigné
if ($existingInternship['status'] === 'assigned') {
    // Vérifier s'il y a une affectation active
    $assignmentModel = new Assignment($db);
    $assignment = $assignmentModel->getByInternshipId($internshipId);
    
    if ($assignment && $assignment['status'] !== 'rejected') {
        sendError('Impossible de supprimer un stage qui a une affectation active', 400);
    }
}

// Supprimer d'abord les compétences associées
$skillModel = new InternshipSkill($db);
$skillModel->deleteByInternshipId($internshipId);

// Supprimer les préférences d'étudiants associées
$preferenceModel = new StudentPreference($db);
$preferenceModel->deleteByInternshipId($internshipId);

// Supprimer les affectations associées (s'il y en a)
$assignmentModel = new Assignment($db);
$assignments = $assignmentModel->getByInternshipId($internshipId);

foreach ($assignments as $assignment) {
    // Supprimer les documents associés à l'affectation
    $documentModel = new Document($db);
    $documentModel->deleteByAssignmentId($assignment['id']);
    
    // Supprimer les évaluations associées à l'affectation
    $evaluationModel = new Evaluation($db);
    $evaluationModel->deleteByAssignmentId($assignment['id']);
    
    // Supprimer l'affectation
    $assignmentModel->delete($assignment['id']);
}

// Supprimer le stage
$success = $internshipModel->delete($internshipId);

if (!$success) {
    sendError('Erreur lors de la suppression du stage', 500);
}

// Envoyer la réponse
sendJsonResponse([
    'success' => true,
    'message' => 'Stage supprimé avec succès'
]);