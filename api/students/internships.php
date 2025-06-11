<?php
/**
 * Stages d'un étudiant
 * GET /api/students/{id}/internships - Récupérer les stages d'un étudiant
 */

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Méthode non autorisée', 405);
}

// Vérifier que l'ID de l'étudiant est présent
if (!isset($urlParts[2]) || !is_numeric($urlParts[2])) {
    sendError('ID d\'étudiant invalide', 400);
}

$studentId = (int)$urlParts[2];

// Initialiser les modèles
$studentModel = new Student($db);
$internshipModel = new Internship($db);
$assignmentModel = new Assignment($db);
$userModel = new User($db);

// Vérifier que l'étudiant existe
$student = $studentModel->getById($studentId);
if (!$student) {
    sendError('Étudiant non trouvé', 404);
}

// Vérifier les permissions: admin, coordinateur, tuteur de l'étudiant ou l'étudiant lui-même
$currentUserRole = $_SESSION['user_role'];
$currentUserId = $_SESSION['user_id'];

// Récupérer l'ID de l'utilisateur associé à l'étudiant
$studentUser = $userModel->getById($student['user_id']);

// Vérifier si l'utilisateur actuel est tuteur de cet étudiant
$isTeacherOfStudent = false;
if ($currentUserRole === 'teacher') {
    $teacherStudents = $assignmentModel->getByTeacherId($currentUserId);
    foreach ($teacherStudents as $ts) {
        if ($ts['student_id'] == $studentId) {
            $isTeacherOfStudent = true;
            break;
        }
    }
}

if ($currentUserRole !== 'admin' && 
    $currentUserRole !== 'coordinator' && 
    !$isTeacherOfStudent && 
    $currentUserId != $studentUser['id']) {
    sendError('Accès non autorisé', 403);
}

// Récupérer les stages actifs de l'étudiant
$assignments = $assignmentModel->getByStudentId($studentId);
$internships = [];

foreach ($assignments as $assignment) {
    if ($assignment['internship_id']) {
        $internship = $internshipModel->getById($assignment['internship_id']);
        if ($internship) {
            // Enrichir avec des données supplémentaires
            $internship['assignment_id'] = $assignment['id'];
            $internship['assignment_status'] = $assignment['status'];
            $internship['teacher_id'] = $assignment['teacher_id'];
            
            $internships[] = $internship;
        }
    }
}

// Récupérer également les stages disponibles pour l'étudiant si la requête vient de l'étudiant lui-même
$availableInternships = [];
if ($currentUserId == $studentUser['id']) {
    $availableInternships = $internshipModel->getAvailableForStudent($studentId);
}

// Envoyer la réponse
sendJsonResponse([
    'data' => [
        'current_internships' => $internships,
        'available_internships' => $availableInternships
    ]
]);