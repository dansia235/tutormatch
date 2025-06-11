<?php
/**
 * Mettre à jour le statut d'une affectation
 * PUT /api/assignments/{id}/status
 */

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    sendError('Méthode non autorisée', 405);
}

// Vérifier que l'ID est présent
if (!isset($urlParts[2]) || !is_numeric($urlParts[2])) {
    sendError('ID d\'affectation invalide', 400);
}

$assignmentId = (int)$urlParts[2];

// Récupérer les données de la requête
$requestBody = json_decode(file_get_contents('php://input'), true);
if (!$requestBody || !isset($requestBody['status'])) {
    sendError('Statut manquant', 400);
}

$newStatus = $requestBody['status'];

// Valider le statut
$validStatuses = ['active', 'pending', 'completed', 'cancelled'];
if (!in_array($newStatus, $validStatuses)) {
    sendError('Statut invalide. Les valeurs acceptées sont: ' . implode(', ', $validStatuses), 400);
}

// Initialiser les modèles
$assignmentModel = new Assignment($db);
$studentModel = new Student($db);
$teacherModel = new Teacher($db);
$userModel = new User($db);

// Récupérer l'affectation
$assignment = $assignmentModel->getById($assignmentId);
if (!$assignment) {
    sendError('Affectation non trouvée', 404);
}

// Vérifier les permissions
$currentUserRole = $_SESSION['user_role'];
$currentUserId = $_SESSION['user_id'];

// Récupérer les détails de l'étudiant et du tuteur
$student = $studentModel->getById($assignment['student_id']);
$teacher = $teacherModel->getById($assignment['teacher_id']);

$studentUser = $student ? $userModel->getById($student['user_id']) : null;
$teacherUser = $teacher ? $userModel->getById($teacher['user_id']) : null;

// Définir qui peut changer quel statut
$isAllowed = false;

switch ($currentUserRole) {
    case 'admin':
    case 'coordinator':
        // Les administrateurs et coordinateurs peuvent changer tous les statuts
        $isAllowed = true;
        break;
        
    case 'teacher':
        // Les tuteurs peuvent marquer les affectations comme complétées/annulées pour leurs étudiants
        if ($teacherUser && $teacherUser['id'] == $currentUserId) {
            if (in_array($newStatus, ['completed', 'cancelled'])) {
                $isAllowed = true;
            }
        }
        break;
        
    case 'student':
        // Les étudiants ne peuvent pas changer le statut des affectations
        $isAllowed = false;
        break;
}

if (!$isAllowed) {
    sendError('Vous n\'êtes pas autorisé à modifier ce statut', 403);
}

// Mettre à jour le statut
$updateData = [
    'status' => $newStatus,
    'updated_at' => date('Y-m-d H:i:s')
];

// Ajouter des notes si fournies
if (isset($requestBody['notes'])) {
    $updateData['notes'] = $requestBody['notes'];
}

$success = $assignmentModel->update($assignmentId, $updateData);
if (!$success) {
    sendError('Échec de la mise à jour du statut', 500);
}

// Si l'affectation est annulée et qu'un stage est associé, rendre le stage disponible
if ($newStatus === 'cancelled' && $assignment['internship_id']) {
    $internshipModel = new Internship($db);
    $internshipModel->updateStatus($assignment['internship_id'], 'available');
}

// Récupérer l'affectation mise à jour
$updatedAssignment = $assignmentModel->getById($assignmentId);

// Envoyer la réponse
sendJsonResponse([
    'message' => 'Statut mis à jour avec succès',
    'data' => $updatedAssignment
]);