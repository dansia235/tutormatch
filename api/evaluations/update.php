<?php
/**
 * Mettre à jour une évaluation
 * PUT /api/evaluations/{id}
 */

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    sendError('Méthode non autorisée', 405);
}

// Vérifier que l'ID est présent
if (!isset($urlParts[2]) || !is_numeric($urlParts[2])) {
    sendError('ID d\'évaluation invalide', 400);
}

$evaluationId = (int)$urlParts[2];

// Récupérer les données de la requête
$requestBody = json_decode(file_get_contents('php://input'), true);
if (!$requestBody) {
    sendError('Données de mise à jour manquantes', 400);
}

// Initialiser les modèles
$evaluationModel = new Evaluation($db);
$assignmentModel = new Assignment($db);
$studentModel = new Student($db);
$teacherModel = new Teacher($db);
$userModel = new User($db);

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

// Vérifier les permissions
$currentUserRole = $_SESSION['user_role'];
$currentUserId = $_SESSION['user_id'];

// Déterminer si l'utilisateur est autorisé à modifier cette évaluation
$isAuthorized = false;

if ($currentUserRole === 'admin' || $currentUserRole === 'coordinator') {
    // Les administrateurs et coordinateurs peuvent modifier toutes les évaluations
    $isAuthorized = true;
} elseif ($evaluation['created_by'] == $currentUserId) {
    // Le créateur de l'évaluation peut la modifier
    $isAuthorized = true;
} elseif ($currentUserRole === 'teacher' && $evaluation['type'] === 'teacher') {
    // Un tuteur peut modifier une évaluation de type "teacher" pour ses propres étudiants
    $teacher = $teacherModel->getByUserId($currentUserId);
    if ($teacher && $teacher['id'] == $assignment['teacher_id']) {
        $isAuthorized = true;
    }
} elseif ($currentUserRole === 'student' && $evaluation['type'] === 'student') {
    // Un étudiant peut modifier une évaluation de type "student" qu'il a créée
    $student = $studentModel->getByUserId($currentUserId);
    if ($student && $student['id'] == $assignment['student_id']) {
        $isAuthorized = true;
    }
}

if (!$isAuthorized) {
    sendError('Vous n\'êtes pas autorisé à modifier cette évaluation', 403);
}

// Préparer les données à mettre à jour
$updateData = [];

// Champs pouvant être mis à jour
if (isset($requestBody['overall_rating'])) {
    $overallRating = (float)$requestBody['overall_rating'];
    if ($overallRating < 0 || $overallRating > 5) {
        sendError('La note globale doit être comprise entre 0 et 5', 400);
    }
    $updateData['overall_rating'] = $overallRating;
}

if (isset($requestBody['comments'])) {
    $updateData['comments'] = $requestBody['comments'];
}

// Ajouter la date de mise à jour
$updateData['updated_at'] = date('Y-m-d H:i:s');

// Mettre à jour l'évaluation
$success = $evaluationModel->update($evaluationId, $updateData);
if (!$success) {
    sendError('Échec de la mise à jour de l\'évaluation', 500);
}

// Mettre à jour les critères d'évaluation si fournis
if (isset($requestBody['criteria']) && is_array($requestBody['criteria'])) {
    // Supprimer les critères existants si on fournit une nouvelle liste complète
    if (isset($requestBody['replace_criteria']) && $requestBody['replace_criteria'] === true) {
        $evaluationModel->deleteCriteria($evaluationId);
    }
    
    foreach ($requestBody['criteria'] as $criterion) {
        if (isset($criterion['id']) && is_numeric($criterion['id'])) {
            // Mettre à jour un critère existant
            $criterionId = (int)$criterion['id'];
            $criterionData = [];
            
            if (isset($criterion['name'])) $criterionData['name'] = $criterion['name'];
            if (isset($criterion['description'])) $criterionData['description'] = $criterion['description'];
            if (isset($criterion['rating'])) $criterionData['rating'] = (float)$criterion['rating'];
            if (isset($criterion['weight'])) $criterionData['weight'] = (float)$criterion['weight'];
            
            if (!empty($criterionData)) {
                $evaluationModel->updateCriterion($criterionId, $criterionData);
            }
        } elseif (isset($criterion['name']) && isset($criterion['rating'])) {
            // Ajouter un nouveau critère
            $criterionData = [
                'evaluation_id' => $evaluationId,
                'name' => $criterion['name'],
                'description' => isset($criterion['description']) ? $criterion['description'] : '',
                'rating' => (float)$criterion['rating'],
                'weight' => isset($criterion['weight']) ? (float)$criterion['weight'] : 1.0
            ];
            
            $evaluationModel->addCriterion($criterionData);
        }
    }
}

// Récupérer l'évaluation mise à jour
$updatedEvaluation = $evaluationModel->getById($evaluationId);
$criteria = $evaluationModel->getEvaluationCriteria($evaluationId);

// Enrichir les données de l'évaluation
$enrichedEvaluation = $updatedEvaluation;
$enrichedEvaluation['criteria'] = $criteria;

// Envoyer la réponse
sendJsonResponse([
    'message' => 'Évaluation mise à jour avec succès',
    'data' => $enrichedEvaluation
]);