<?php
/**
 * Mettre à jour une évaluation
 * POST /api/evaluations/update.php
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../includes/init.php';

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlashMessage('error', 'Méthode non autorisée');
    redirect('/tutoring/views/admin/assignments.php');
}

// Vérifier que l'utilisateur est connecté
requireLogin();

// Vérifier le jeton CSRF
if (!verifyCsrfToken($_POST['csrf_token'])) {
    setFlashMessage('error', 'Erreur de sécurité. Veuillez réessayer.');
    redirect('/tutoring/views/admin/assignments.php');
}

// Vérifier que l'ID est présent
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    setFlashMessage('error', 'ID d\'évaluation invalide');
    redirect('/tutoring/views/admin/assignments.php');
}

$evaluationId = (int)$_POST['id'];

// Valider les données requises
$requiredFields = ['assignment_id', 'type', 'score', 'comments'];
foreach ($requiredFields as $field) {
    if (!isset($_POST[$field]) || (empty($_POST[$field]) && $_POST[$field] !== '0')) {
        setFlashMessage('error', "Le champ '$field' est requis");
        $_SESSION['form_data'] = $_POST;
        
        if (isset($_POST['redirect_url'])) {
            redirect($_POST['redirect_url']);
        } else {
            redirect('/tutoring/views/admin/assignments/evaluation_form.php?id=' . $_POST['assignment_id'] . '&evaluation_id=' . $evaluationId);
        }
    }
}

// Initialiser les modèles
$evaluationModel = new Evaluation($db);
$assignmentModel = new Assignment($db);

// Récupérer l'évaluation
$evaluation = $evaluationModel->getById($evaluationId);
if (!$evaluation) {
    setFlashMessage('error', 'Évaluation non trouvée');
    redirect('/tutoring/views/admin/assignments.php');
}

// Récupérer l'affectation associée
$assignmentId = (int)$_POST['assignment_id'];
$assignment = $assignmentModel->getById($assignmentId);
if (!$assignment) {
    setFlashMessage('error', 'Affectation associée non trouvée');
    redirect('/tutoring/views/admin/assignments.php');
}

// Vérifier que l'évaluation appartient bien à cette affectation
if ($evaluation['assignment_id'] != $assignmentId) {
    setFlashMessage('error', 'Cette évaluation n\'appartient pas à l\'affectation spécifiée');
    redirect('/tutoring/views/admin/assignments.php');
}

// Vérifier les permissions
$currentUserRole = $_SESSION['user_role'];
$currentUserId = $_SESSION['user_id'];

// Déterminer si l'utilisateur est autorisé à modifier cette évaluation
$isAuthorized = false;

if ($currentUserRole === 'admin' || $currentUserRole === 'coordinator') {
    $isAuthorized = true;
} else if ($currentUserRole === 'teacher') {
    // Vérifier si l'utilisateur est le tuteur de cette affectation
    $teacherModel = new Teacher($db);
    $teacher = $teacherModel->getByUserId($currentUserId);
    if ($teacher && $teacher['id'] === $assignment['teacher_id']) {
        $isAuthorized = true;
    }
} else if ($currentUserRole === 'student' && $evaluation['status'] === 'draft') {
    // Les étudiants peuvent uniquement modifier leurs propres auto-évaluations à l'état de brouillon
    $studentModel = new Student($db);
    $student = $studentModel->getByUserId($currentUserId);
    if ($student && $student['id'] === $assignment['student_id'] && $evaluation['evaluator_id'] === $currentUserId) {
        $isAuthorized = true;
    }
}

if (!$isAuthorized) {
    setFlashMessage('error', "Vous n'êtes pas autorisé à modifier cette évaluation");
    redirect('/tutoring/views/admin/assignments.php');
}

// Valider le score
$score = (int)$_POST['score'];
if ($score < 0 || $score > 100) {
    setFlashMessage('error', "Le score doit être compris entre 0 et 100");
    $_SESSION['form_data'] = $_POST;
    redirect('/tutoring/views/admin/assignments/evaluation_form.php?id=' . $assignmentId . '&evaluation_id=' . $evaluationId);
}

// Préparer les données pour la mise à jour
$evaluationData = [
    'type' => $_POST['type'],
    'score' => $score,
    'comments' => $_POST['comments'],
    'strengths' => $_POST['strengths'] ?? '',
    'areas_for_improvement' => $_POST['areas_for_improvement'] ?? '',
    'next_steps' => $_POST['next_steps'] ?? '',
    'status' => $_POST['status'] ?? 'draft',
    'updated_at' => date('Y-m-d H:i:s')
];

// Si le statut passe de brouillon à soumis, mettre à jour la date de soumission
if ($evaluation['status'] === 'draft' && ($_POST['status'] === 'submitted' || $_POST['status'] === 'approved')) {
    $evaluationData['submission_date'] = date('Y-m-d H:i:s');
}

// Mettre à jour l'évaluation
$success = $evaluationModel->update($evaluationId, $evaluationData);

if (!$success) {
    setFlashMessage('error', "Erreur lors de la mise à jour de l'évaluation");
    $_SESSION['form_data'] = $_POST;
    redirect('/tutoring/views/admin/assignments/evaluation_form.php?id=' . $assignmentId . '&evaluation_id=' . $evaluationId);
}

// Notifier l'étudiant si l'évaluation est soumise
if (($evaluation['status'] === 'draft' || $evaluation['status'] !== $_POST['status']) 
    && ($_POST['status'] === 'submitted' || $_POST['status'] === 'approved')) {
    // Créer une notification
    if (class_exists('Notification')) {
        $notificationModel = new Notification($db);
        $studentModel = new Student($db);
        $student = $studentModel->getById($assignment['student_id']);
        
        if ($student) {
            $notificationData = [
                'user_id' => $student['user_id'],
                'type' => 'evaluation',
                'content' => 'Une évaluation a été mise à jour pour votre stage.',
                'link' => '/tutoring/views/student/evaluations.php',
                'is_read' => 0
            ];
            
            $notificationModel->create($notificationData);
        }
    }
}

// Redirection
setFlashMessage('success', "L'évaluation a été mise à jour avec succès");

if (isset($_POST['redirect_url'])) {
    redirect($_POST['redirect_url']);
} else {
    redirect('/tutoring/views/admin/assignments/show.php?id=' . $assignmentId);
}