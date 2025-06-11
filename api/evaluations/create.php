<?php
/**
 * Créer une nouvelle évaluation
 * POST /api/evaluations
 */

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Méthode non autorisée', 405);
}

// Récupérer les données de la requête
$requestBody = json_decode(file_get_contents('php://input'), true);
if (!$requestBody) {
    sendError('Données d\'évaluation manquantes', 400);
}

// Valider les données requises
$requiredFields = ['assignment_id', 'type', 'overall_rating'];
foreach ($requiredFields as $field) {
    if (!isset($requestBody[$field]) || (empty($requestBody[$field]) && $requestBody[$field] !== 0)) {
        sendError("Le champ '$field' est requis", 400);
    }
}

// Initialiser les modèles
$evaluationModel = new Evaluation($db);
$assignmentModel = new Assignment($db);
$studentModel = new Student($db);
$teacherModel = new Teacher($db);
$userModel = new User($db);

// Récupérer l'affectation
$assignmentId = (int)$requestBody['assignment_id'];
$assignment = $assignmentModel->getById($assignmentId);
if (!$assignment) {
    sendError('Affectation non trouvée', 404);
}

// Récupérer l'étudiant et le tuteur
$student = $studentModel->getById($assignment['student_id']);
$teacher = $teacherModel->getById($assignment['teacher_id']);

$studentUser = $student ? $userModel->getById($student['user_id']) : null;
$teacherUser = $teacher ? $userModel->getById($teacher['user_id']) : null;

// Vérifier les permissions
$currentUserRole = $_SESSION['user_role'];
$currentUserId = $_SESSION['user_id'];
$evaluationType = $requestBody['type'];

// Vérifier si l'utilisateur est autorisé à créer cette évaluation
$isAuthorized = false;

if ($currentUserRole === 'admin' || $currentUserRole === 'coordinator') {
    // Les administrateurs et coordinateurs peuvent créer tous types d'évaluations
    $isAuthorized = true;
} elseif ($currentUserRole === 'teacher' && $evaluationType === 'teacher') {
    // Un tuteur peut créer une évaluation de type "teacher" pour ses propres étudiants
    if ($teacherUser && $teacherUser['id'] == $currentUserId) {
        $isAuthorized = true;
    }
} elseif ($currentUserRole === 'student' && $evaluationType === 'student') {
    // Un étudiant peut créer une évaluation de type "student" pour son tuteur
    if ($studentUser && $studentUser['id'] == $currentUserId) {
        $isAuthorized = true;
    }
}

if (!$isAuthorized) {
    sendError('Vous n\'êtes pas autorisé à créer cette évaluation', 403);
}

// Vérifier s'il existe déjà une évaluation du même type pour cette affectation
$existingEvaluation = $evaluationModel->getByAssignmentAndType($assignmentId, $evaluationType);
if ($existingEvaluation) {
    sendError('Une évaluation de ce type existe déjà pour cette affectation', 400);
}

// Valider la note globale
$overallRating = (float)$requestBody['overall_rating'];
if ($overallRating < 0 || $overallRating > 5) {
    sendError('La note globale doit être comprise entre 0 et 5', 400);
}

// Préparer les données d'évaluation
$evaluationData = [
    'assignment_id' => $assignmentId,
    'type' => $evaluationType,
    'overall_rating' => $overallRating,
    'comments' => isset($requestBody['comments']) ? $requestBody['comments'] : '',
    'created_by' => $currentUserId,
    'created_at' => date('Y-m-d H:i:s')
];

// Créer l'évaluation
$newEvaluationId = $evaluationModel->create($evaluationData);
if (!$newEvaluationId) {
    sendError('Échec de la création de l\'évaluation', 500);
}

// Ajouter les critères d'évaluation si fournis
if (isset($requestBody['criteria']) && is_array($requestBody['criteria'])) {
    foreach ($requestBody['criteria'] as $criterion) {
        if (isset($criterion['name']) && isset($criterion['rating'])) {
            $criterionData = [
                'evaluation_id' => $newEvaluationId,
                'name' => $criterion['name'],
                'description' => isset($criterion['description']) ? $criterion['description'] : '',
                'rating' => (float)$criterion['rating'],
                'weight' => isset($criterion['weight']) ? (float)$criterion['weight'] : 1.0
            ];
            
            $evaluationModel->addCriterion($criterionData);
        }
    }
}

// Récupérer l'évaluation créée
$newEvaluation = $evaluationModel->getById($newEvaluationId);
$criteria = $evaluationModel->getEvaluationCriteria($newEvaluationId);

// Enrichir les données de l'évaluation
$enrichedEvaluation = $newEvaluation;
$enrichedEvaluation['criteria'] = $criteria;
$enrichedEvaluation['assignment'] = [
    'id' => $assignment['id'],
    'student' => $studentUser ? [
        'id' => $student['id'],
        'name' => $studentUser['first_name'] . ' ' . $studentUser['last_name']
    ] : null,
    'teacher' => $teacherUser ? [
        'id' => $teacher['id'],
        'name' => $teacherUser['first_name'] . ' ' . $teacherUser['last_name']
    ] : null
];

// Envoyer la réponse
sendJsonResponse([
    'message' => 'Évaluation créée avec succès',
    'data' => $enrichedEvaluation
], 201);