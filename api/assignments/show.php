<?php
/**
 * Détails d'une affectation
 * GET /api/assignments/{id}
 */

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Méthode non autorisée', 405);
}

// Vérifier que l'ID est présent
if (!isset($urlParts[2]) || !is_numeric($urlParts[2])) {
    sendError('ID d\'affectation invalide', 400);
}

$assignmentId = (int)$urlParts[2];

// Initialiser les modèles
$assignmentModel = new Assignment($db);
$studentModel = new Student($db);
$teacherModel = new Teacher($db);
$internshipModel = new Internship($db);
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

// Vérifier si l'utilisateur actuel est concerné par cette affectation
$isInvolved = false;
if ($currentUserRole === 'admin' || $currentUserRole === 'coordinator') {
    $isInvolved = true;
} elseif ($currentUserRole === 'teacher' && $teacherUser && $teacherUser['id'] == $currentUserId) {
    $isInvolved = true;
} elseif ($currentUserRole === 'student' && $studentUser && $studentUser['id'] == $currentUserId) {
    $isInvolved = true;
}

if (!$isInvolved) {
    sendError('Accès non autorisé', 403);
}

// Récupérer les détails du stage
$internship = null;
if ($assignment['internship_id']) {
    $internship = $internshipModel->getById($assignment['internship_id']);
}

// Enrichir l'affectation avec les détails associés
$enrichedAssignment = $assignment;
$enrichedAssignment['student'] = $student ? [
    'id' => $student['id'],
    'name' => $studentUser ? $studentUser['first_name'] . ' ' . $studentUser['last_name'] : 'N/A',
    'email' => $studentUser ? $studentUser['email'] : 'N/A',
    'phone' => $student['phone'],
    'address' => $student['address'],
    'program' => $student['program']
] : null;

$enrichedAssignment['teacher'] = $teacher ? [
    'id' => $teacher['id'],
    'name' => $teacherUser ? $teacherUser['first_name'] . ' ' . $teacherUser['last_name'] : 'N/A',
    'email' => $teacherUser ? $teacherUser['email'] : 'N/A',
    'phone' => $teacher['phone'],
    'department' => $teacher['department'],
    'expertise' => $teacher['expertise']
] : null;

$enrichedAssignment['internship'] = $internship ? [
    'id' => $internship['id'],
    'title' => $internship['title'],
    'description' => $internship['description'],
    'company_name' => $internship['company_name'],
    'location' => $internship['location'],
    'supervisor_name' => $internship['supervisor_name'],
    'supervisor_email' => $internship['supervisor_email'],
    'supervisor_phone' => $internship['supervisor_phone'],
    'start_date' => $internship['start_date'],
    'end_date' => $internship['end_date'],
    'requirements' => $internship['requirements'],
    'status' => $internship['status']
] : null;

// Récupérer les documents associés
$documents = (new Document($db))->getByAssignmentId($assignmentId);
$enrichedAssignment['documents'] = $documents;

// Envoyer la réponse
sendJsonResponse([
    'data' => $enrichedAssignment
]);