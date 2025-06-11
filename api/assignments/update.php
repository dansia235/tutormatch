<?php
/**
 * Mettre à jour une affectation
 * PUT /api/assignments/{id}
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

// Vérifier les permissions (seuls les administrateurs et les coordinateurs peuvent modifier des affectations)
$currentUserRole = $_SESSION['user_role'];
if ($currentUserRole !== 'admin' && $currentUserRole !== 'coordinator') {
    sendError('Accès non autorisé', 403);
}

// Récupérer les données de la requête
$requestBody = json_decode(file_get_contents('php://input'), true);
if (!$requestBody) {
    sendError('Données de mise à jour manquantes', 400);
}

// Initialiser les modèles
$assignmentModel = new Assignment($db);
$studentModel = new Student($db);
$teacherModel = new Teacher($db);
$internshipModel = new Internship($db);

// Vérifier que l'affectation existe
$assignment = $assignmentModel->getById($assignmentId);
if (!$assignment) {
    sendError('Affectation non trouvée', 404);
}

// Préparer les données à mettre à jour
$updateData = [];

// Mettre à jour l'étudiant si fourni
if (isset($requestBody['student_id'])) {
    $studentId = (int)$requestBody['student_id'];
    $student = $studentModel->getById($studentId);
    if (!$student) {
        sendError('Étudiant non trouvé', 404);
    }
    $updateData['student_id'] = $studentId;
}

// Mettre à jour le tuteur si fourni
if (isset($requestBody['teacher_id'])) {
    $teacherId = (int)$requestBody['teacher_id'];
    $teacher = $teacherModel->getById($teacherId);
    if (!$teacher) {
        sendError('Tuteur non trouvé', 404);
    }
    $updateData['teacher_id'] = $teacherId;
}

// Mettre à jour le stage si fourni
$oldInternshipId = $assignment['internship_id'];
if (isset($requestBody['internship_id'])) {
    $internshipId = (int)$requestBody['internship_id'];
    
    if ($internshipId !== 0) {  // 0 signifie supprimer l'affectation de stage
        $internship = $internshipModel->getById($internshipId);
        if (!$internship) {
            sendError('Stage non trouvé', 404);
        }
        
        // Vérifier que le stage est disponible si différent de l'actuel
        if ($internshipId !== $oldInternshipId && $internship['status'] !== 'available') {
            sendError('Ce stage n\'est pas disponible', 400);
        }
    }
    
    $updateData['internship_id'] = $internshipId === 0 ? null : $internshipId;
}

// Mettre à jour le statut et les notes
if (isset($requestBody['status'])) {
    $updateData['status'] = $requestBody['status'];
}

if (isset($requestBody['notes'])) {
    $updateData['notes'] = $requestBody['notes'];
}

// Ajouter la date de mise à jour
$updateData['updated_at'] = date('Y-m-d H:i:s');

// Mettre à jour l'affectation
$success = $assignmentModel->update($assignmentId, $updateData);
if (!$success) {
    sendError('Échec de la mise à jour de l\'affectation', 500);
}

// Gérer les changements de stage
$newInternshipId = isset($updateData['internship_id']) ? $updateData['internship_id'] : $oldInternshipId;

// Si l'ancien stage n'est plus affecté, le rendre disponible
if ($oldInternshipId && $oldInternshipId !== $newInternshipId) {
    $internshipModel->updateStatus($oldInternshipId, 'available');
}

// Si un nouveau stage est affecté, le marquer comme assigné
if ($newInternshipId && $newInternshipId !== $oldInternshipId) {
    $internshipModel->updateStatus($newInternshipId, 'assigned');
}

// Récupérer l'affectation mise à jour
$updatedAssignment = $assignmentModel->getById($assignmentId);

// Envoyer la réponse
sendJsonResponse([
    'message' => 'Affectation mise à jour avec succès',
    'data' => $updatedAssignment
]);