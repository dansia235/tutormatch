<?php
/**
 * Détails d'une évaluation
 * GET /api/evaluations/{id}
 */

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Méthode non autorisée', 405);
}

// Vérifier que l'ID est présent
if (!isset($urlParts[2]) || !is_numeric($urlParts[2])) {
    sendError('ID d\'évaluation invalide', 400);
}

$evaluationId = (int)$urlParts[2];

// Initialiser les modèles
$evaluationModel = new Evaluation($db);
$userModel = new User($db);
$studentModel = new Student($db);
$teacherModel = new Teacher($db);
$assignmentModel = new Assignment($db);

// Récupérer l'évaluation
$evaluation = $evaluationModel->getById($evaluationId);
if (!$evaluation) {
    sendError('Évaluation non trouvée', 404);
}

// Récupérer l'affectation associée
$assignment = $assignmentModel->getById($evaluation['assignment_id']);
if (!$assignment) {
    sendError('Affectation associée non trouvée', 404);
}

// Récupérer les informations sur l'étudiant et le tuteur
$student = $studentModel->getById($assignment['student_id']);
$teacher = $teacherModel->getById($assignment['teacher_id']);

$studentUser = $student ? $userModel->getById($student['user_id']) : null;
$teacherUser = $teacher ? $userModel->getById($teacher['user_id']) : null;

// Vérifier les permissions
$currentUserRole = $_SESSION['user_role'];
$currentUserId = $_SESSION['user_id'];

// Déterminer si l'utilisateur a accès à l'évaluation
$hasAccess = false;

if ($currentUserRole === 'admin' || $currentUserRole === 'coordinator') {
    // Les administrateurs et coordinateurs ont accès à toutes les évaluations
    $hasAccess = true;
} elseif ($currentUserRole === 'teacher') {
    // Un tuteur peut voir les évaluations des étudiants dont il est tuteur
    if ($teacherUser && $teacherUser['id'] == $currentUserId) {
        $hasAccess = true;
    }
} elseif ($currentUserRole === 'student') {
    // Un étudiant peut voir ses propres évaluations
    if ($studentUser && $studentUser['id'] == $currentUserId) {
        $hasAccess = true;
        
        // Les étudiants ne peuvent pas voir les commentaires des évaluations des tuteurs sur eux
        // avant la fin du stage
        if ($evaluation['type'] === 'teacher' && $assignment['status'] !== 'completed') {
            $evaluation['comments'] = 'Cette évaluation sera visible à la fin du stage';
        }
    }
}

if (!$hasAccess) {
    sendError('Vous n\'êtes pas autorisé à voir cette évaluation', 403);
}

// Enrichir les données de l'évaluation
$enrichedEvaluation = $evaluation;
$enrichedEvaluation['assignment'] = [
    'id' => $assignment['id'],
    'student' => $studentUser ? [
        'id' => $student['id'],
        'name' => $studentUser['first_name'] . ' ' . $studentUser['last_name'],
        'email' => $studentUser['email']
    ] : null,
    'teacher' => $teacherUser ? [
        'id' => $teacher['id'],
        'name' => $teacherUser['first_name'] . ' ' . $teacherUser['last_name'],
        'email' => $teacherUser['email']
    ] : null,
    'status' => $assignment['status']
];

// Récupérer les critères d'évaluation
$criteria = $evaluationModel->getEvaluationCriteria($evaluationId);
$enrichedEvaluation['criteria'] = $criteria;

// Envoyer la réponse
sendJsonResponse([
    'data' => $enrichedEvaluation
]);