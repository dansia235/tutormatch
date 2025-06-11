<?php
/**
 * Créer une nouvelle affectation
 * POST /api/assignments
 */

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Méthode non autorisée', 405);
}

// Vérifier les permissions (seuls les administrateurs et les coordinateurs peuvent créer des affectations)
$currentUserRole = $_SESSION['user_role'];
if ($currentUserRole !== 'admin' && $currentUserRole !== 'coordinator') {
    sendError('Accès non autorisé', 403);
}

// Récupérer les données de la requête
$requestBody = json_decode(file_get_contents('php://input'), true);
if (!$requestBody) {
    sendError('Données d\'affectation manquantes', 400);
}

// Valider les données requises
$requiredFields = ['student_id', 'teacher_id'];
foreach ($requiredFields as $field) {
    if (!isset($requestBody[$field]) || empty($requestBody[$field])) {
        sendError("Le champ '$field' est requis", 400);
    }
}

// Initialiser les modèles
$assignmentModel = new Assignment($db);
$studentModel = new Student($db);
$teacherModel = new Teacher($db);
$internshipModel = new Internship($db);

// Valider l'existence de l'étudiant
$studentId = (int)$requestBody['student_id'];
$student = $studentModel->getById($studentId);
if (!$student) {
    sendError('Étudiant non trouvé', 404);
}

// Valider l'existence du tuteur
$teacherId = (int)$requestBody['teacher_id'];
$teacher = $teacherModel->getById($teacherId);
if (!$teacher) {
    sendError('Tuteur non trouvé', 404);
}

// Valider le stage s'il est fourni
$internshipId = isset($requestBody['internship_id']) ? (int)$requestBody['internship_id'] : null;
if ($internshipId) {
    $internship = $internshipModel->getById($internshipId);
    if (!$internship) {
        sendError('Stage non trouvé', 404);
    }
    
    // Vérifier que le stage est disponible
    if ($internship['status'] !== 'available') {
        sendError('Ce stage n\'est pas disponible', 400);
    }
}

// Vérifier si l'étudiant a déjà une affectation active
$existingAssignments = $assignmentModel->getActiveByStudentId($studentId);
if (!empty($existingAssignments)) {
    sendError('Cet étudiant a déjà une affectation active', 400);
}

// Préparer les données d'affectation
$assignmentData = [
    'student_id' => $studentId,
    'teacher_id' => $teacherId,
    'internship_id' => $internshipId,
    'status' => isset($requestBody['status']) ? $requestBody['status'] : 'active',
    'notes' => isset($requestBody['notes']) ? $requestBody['notes'] : '',
    'created_at' => date('Y-m-d H:i:s')
];

// Créer l'affectation
$newAssignmentId = $assignmentModel->create($assignmentData);
if (!$newAssignmentId) {
    sendError('Échec de la création de l\'affectation', 500);
}

// Si l'affectation a un stage, mettre à jour le statut du stage
if ($internshipId) {
    $internshipModel->updateStatus($internshipId, 'assigned');
}

// Récupérer l'affectation créée
$newAssignment = $assignmentModel->getById($newAssignmentId);

// Envoyer la réponse
sendJsonResponse([
    'message' => 'Affectation créée avec succès',
    'data' => $newAssignment
], 201);