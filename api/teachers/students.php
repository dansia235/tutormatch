<?php
/**
 * Récupérer les étudiants assignés à un tuteur
 * GET /api/teachers/{id}/students
 */

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Méthode non autorisée', 405);
}

// Récupérer l'ID du tuteur depuis l'URL
$teacherId = isset($urlParts[2]) ? (int)$urlParts[2] : 0;

if ($teacherId <= 0) {
    sendError('ID tuteur invalide', 400);
}

// Initialiser le modèle tuteur
$teacherModel = new Teacher($db);

// Récupérer le tuteur
$teacher = $teacherModel->getById($teacherId);

if (!$teacher) {
    sendError('Tuteur non trouvé', 404);
}

// Vérifier les droits d'accès
$userModel = new User($db);
$user = $userModel->getById($teacher['user_id']);

if (!hasRole(['admin', 'coordinator'])) {
    if (hasRole('teacher')) {
        // Un tuteur ne peut voir que ses propres étudiants
        if ($user['id'] !== $_SESSION['user_id']) {
            sendError('Accès refusé', 403);
        }
    } else {
        sendError('Accès refusé', 403);
    }
}

// Récupérer les paramètres de requête
$status = isset($_GET['status']) ? $_GET['status'] : null;

// Initialiser le modèle d'affectation
$assignmentModel = new Assignment($db);

// Construire les options de requête
$options = [];

if ($status) {
    $validStatuses = ['pending', 'confirmed', 'rejected', 'completed'];
    if (in_array($status, $validStatuses)) {
        $options['status'] = $status;
    }
}

// Récupérer les affectations
$assignments = $assignmentModel->getByTeacherId($teacherId, $options);

// Transformer les données pour l'API
$assignedStudents = [];
foreach ($assignments as $assignment) {
    $studentModel = new Student($db);
    $student = $studentModel->getById($assignment['student_id']);
    
    if ($student) {
        $studentUser = $userModel->getById($student['user_id']);
        unset($studentUser['password']);
        
        // Récupérer les détails du stage
        $internshipModel = new Internship($db);
        $internship = $internshipModel->getById($assignment['internship_id']);
        
        // Récupérer les évaluations
        $evaluationModel = new Evaluation($db);
        $evaluations = $evaluationModel->getByAssignmentId($assignment['id']);
        
        $assignedStudent = [
            'assignment_id' => $assignment['id'],
            'assignment_status' => $assignment['status'],
            'assignment_date' => $assignment['assignment_date'],
            'confirmation_date' => $assignment['confirmation_date'],
            'student' => array_merge($student, ['user' => $studentUser]),
            'internship' => $internship
        ];
        
        // Ajouter les évaluations si disponibles
        if (!empty($evaluations)) {
            $assignedStudent['evaluations'] = $evaluations;
        }
        
        $assignedStudents[] = $assignedStudent;
    }
}

// Envoyer la réponse
sendJsonResponse([
    'data' => $assignedStudents
]);