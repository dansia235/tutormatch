<?php
/**
 * Créer une nouvelle évaluation
 * POST /api/evaluations/create.php
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

// Valider les données requises
$requiredFields = ['assignment_id', 'type', 'score', 'comments'];
foreach ($requiredFields as $field) {
    if (!isset($_POST[$field]) || (empty($_POST[$field]) && $_POST[$field] !== '0')) {
        setFlashMessage('error', "Le champ '$field' est requis");
        $_SESSION['form_data'] = $_POST;
        
        if (isset($_POST['redirect_url'])) {
            redirect($_POST['redirect_url']);
        } else {
            redirect('/tutoring/views/admin/assignments/evaluation_form.php?id=' . $_POST['assignment_id'] . '&type=' . $_POST['type']);
        }
    }
}

// Initialiser les modèles
$evaluationModel = new Evaluation($db);
$assignmentModel = new Assignment($db);

// Récupérer l'affectation
$assignmentId = (int)$_POST['assignment_id'];
$assignment = $assignmentModel->getById($assignmentId);
if (!$assignment) {
    setFlashMessage('error', 'Affectation non trouvée');
    redirect('/tutoring/views/admin/assignments.php');
}

// Vérifier les permissions
$currentUserRole = $_SESSION['user_role'];
$currentUserId = $_SESSION['user_id'];

// Déterminer si l'utilisateur est autorisé à créer cette évaluation
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
}

if (!$isAuthorized) {
    setFlashMessage('error', "Vous n'êtes pas autorisé à créer cette évaluation");
    redirect('/tutoring/views/admin/assignments.php');
}

// Valider le score
$score = (int)$_POST['score'];
if ($score < 0 || $score > 100) {
    setFlashMessage('error', "Le score doit être compris entre 0 et 100");
    $_SESSION['form_data'] = $_POST;
    redirect('/tutoring/views/admin/assignments/evaluation_form.php?id=' . $assignmentId . '&type=' . $_POST['type']);
}

// Préparer les données pour la création
$evaluationData = [
    'assignment_id' => $assignmentId,
    'type' => $_POST['type'],
    'score' => $score,
    'comments' => $_POST['comments'],
    'strengths' => $_POST['strengths'] ?? '',
    'areas_for_improvement' => $_POST['areas_for_improvement'] ?? '',
    'next_steps' => $_POST['next_steps'] ?? '',
    'status' => $_POST['status'] ?? 'draft',
    'evaluator_id' => $currentUserId,
    'submission_date' => date('Y-m-d H:i:s')
];

// Créer l'évaluation
$evaluationId = $evaluationModel->create($evaluationData);

if (!$evaluationId) {
    setFlashMessage('error', "Erreur lors de la création de l'évaluation");
    $_SESSION['form_data'] = $_POST;
    redirect('/tutoring/views/admin/assignments/evaluation_form.php?id=' . $assignmentId . '&type=' . $_POST['type']);
}

// Notifier l'étudiant si l'évaluation est soumise
if ($_POST['status'] === 'submitted' || $_POST['status'] === 'approved') {
    // Créer une notification
    if (class_exists('Notification')) {
        $notificationModel = new Notification($db);
        $studentModel = new Student($db);
        $student = $studentModel->getById($assignment['student_id']);
        
        if ($student) {
            $notificationData = [
                'user_id' => $student['user_id'],
                'type' => 'evaluation',
                'content' => 'Une nouvelle évaluation a été soumise pour votre stage.',
                'link' => '/tutoring/views/student/evaluations.php',
                'is_read' => 0
            ];
            
            $notificationModel->create($notificationData);
        }
    }
}

// Redirection
setFlashMessage('success', "L'évaluation a été créée avec succès");

if (isset($_POST['redirect_url'])) {
    redirect($_POST['redirect_url']);
} else {
    redirect('/tutoring/views/admin/assignments/show.php?id=' . $assignmentId);
}