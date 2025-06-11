<?php
/**
 * Récupérer les affectations d'un étudiant
 * GET /api/students/{id}/assignments
 */

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Méthode non autorisée', 405);
}

// Récupérer l'ID de l'étudiant depuis l'URL
$studentId = isset($urlParts[2]) ? (int)$urlParts[2] : 0;

if ($studentId <= 0) {
    sendError('ID étudiant invalide', 400);
}

// Initialiser le modèle étudiant
$studentModel = new Student($db);

// Vérifier si l'étudiant existe
$student = $studentModel->getById($studentId);

if (!$student) {
    sendError('Étudiant non trouvé', 404);
}

// Vérifier les droits d'accès
$userModel = new User($db);
$user = $userModel->getById($student['user_id']);

if (!hasRole(['admin', 'coordinator'])) {
    if (hasRole('teacher')) {
        // Les tuteurs peuvent voir uniquement les affectations de leurs étudiants
        $teacherModel = new Teacher($db);
        $teacher = $teacherModel->getByUserId($_SESSION['user_id']);
        
        if (!$teacher) {
            sendError('Profil tuteur non trouvé', 404);
        }
        
        $assignmentModel = new Assignment($db);
        $assignment = $assignmentModel->getByStudentAndTeacherId($studentId, $teacher['id']);
        
        if (!$assignment) {
            sendError('Accès refusé: cet étudiant n\'est pas assigné à votre supervision', 403);
        }
    } elseif (hasRole('student')) {
        // Un étudiant ne peut voir que ses propres affectations
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
$assignments = $assignmentModel->getByStudentId($studentId, $options);

// Enrichir les données d'affectation
$formattedAssignments = [];
foreach ($assignments as $assignment) {
    // Récupérer les détails du stage
    $internshipModel = new Internship($db);
    $internship = $internshipModel->getById($assignment['internship_id']);
    
    // Récupérer les détails du tuteur
    $teacherModel = new Teacher($db);
    $teacher = $teacherModel->getById($assignment['teacher_id']);
    $teacherUser = $userModel->getById($teacher['user_id']);
    
    // Masquer le mot de passe du tuteur
    unset($teacherUser['password']);
    
    // Récupérer les évaluations liées à cette affectation
    $evaluationModel = new Evaluation($db);
    $evaluations = $evaluationModel->getByAssignmentId($assignment['id']);
    
    // Récupérer les documents liés à cette affectation
    $documentModel = new Document($db);
    $documents = $documentModel->getByAssignmentId($assignment['id']);
    
    // Formater l'affectation
    $formattedAssignment = [
        'id' => $assignment['id'],
        'status' => $assignment['status'],
        'assignment_date' => $assignment['assignment_date'],
        'confirmation_date' => $assignment['confirmation_date'],
        'satisfaction_score' => $assignment['satisfaction_score'],
        'compatibility_score' => $assignment['compatibility_score'],
        'notes' => $assignment['notes'],
        'internship' => $internship,
        'teacher' => array_merge($teacher, ['user' => $teacherUser])
    ];
    
    // Ajouter les évaluations si disponibles
    if (!empty($evaluations)) {
        $formattedAssignment['evaluations'] = $evaluations;
    }
    
    // Ajouter les documents si disponibles
    if (!empty($documents)) {
        $formattedAssignment['documents'] = $documents;
    }
    
    $formattedAssignments[] = $formattedAssignment;
}

// Envoyer la réponse
sendJsonResponse([
    'data' => $formattedAssignments
]);