<?php
/**
 * Liste des affectations
 * GET /api/assignments
 */

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Méthode non autorisée', 405);
}

// Récupérer les paramètres de requête
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$studentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : null;
$teacherId = isset($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : null;
$internshipId = isset($_GET['internship_id']) ? (int)$_GET['internship_id'] : null;
$status = isset($_GET['status']) ? $_GET['status'] : null;

// Valider les paramètres
if ($page < 1) $page = 1;
if ($limit < 1 || $limit > 50) $limit = 10;

// Initialiser le modèle d'affectation
$assignmentModel = new Assignment($db);
$userModel = new User($db);

// Vérifier les permissions
$currentUserRole = $_SESSION['user_role'];
$currentUserId = $_SESSION['user_id'];

// Construire les options de requête
$options = [
    'page' => $page,
    'limit' => $limit
];

// Appliquer les filtres
if ($studentId) $options['student_id'] = $studentId;
if ($teacherId) $options['teacher_id'] = $teacherId;
if ($internshipId) $options['internship_id'] = $internshipId;
if ($status) $options['status'] = $status;

// Restreindre l'accès selon le rôle
if ($currentUserRole === 'student') {
    // Un étudiant ne peut voir que ses propres affectations
    $student = (new Student($db))->getByUserId($currentUserId);
    if (!$student) {
        sendError('Profil étudiant non trouvé', 404);
    }
    $options['student_id'] = $student['id'];
} elseif ($currentUserRole === 'teacher') {
    // Un tuteur ne peut voir que ses propres affectations
    $teacher = (new Teacher($db))->getByUserId($currentUserId);
    if (!$teacher) {
        sendError('Profil tuteur non trouvé', 404);
    }
    $options['teacher_id'] = $teacher['id'];
}

// Récupérer les affectations selon les filtres
$assignments = $assignmentModel->getAll($options);
$total = $assignmentModel->countAll($options);

// Calculer la pagination
$totalPages = ceil($total / $limit);

// Enrichir les données avec les informations associées
$enrichedAssignments = [];
foreach ($assignments as $assignment) {
    // Récupérer les détails de l'étudiant
    $student = (new Student($db))->getById($assignment['student_id']);
    $studentUser = $student ? $userModel->getById($student['user_id']) : null;
    
    // Récupérer les détails du tuteur
    $teacher = (new Teacher($db))->getById($assignment['teacher_id']);
    $teacherUser = $teacher ? $userModel->getById($teacher['user_id']) : null;
    
    // Récupérer les détails du stage
    $internship = null;
    if ($assignment['internship_id']) {
        $internship = (new Internship($db))->getById($assignment['internship_id']);
    }
    
    $enrichedAssignment = $assignment;
    $enrichedAssignment['student'] = $student ? [
        'id' => $student['id'],
        'name' => $studentUser ? $studentUser['first_name'] . ' ' . $studentUser['last_name'] : 'N/A',
        'email' => $studentUser ? $studentUser['email'] : 'N/A'
    ] : null;
    
    $enrichedAssignment['teacher'] = $teacher ? [
        'id' => $teacher['id'],
        'name' => $teacherUser ? $teacherUser['first_name'] . ' ' . $teacherUser['last_name'] : 'N/A',
        'email' => $teacherUser ? $teacherUser['email'] : 'N/A'
    ] : null;
    
    $enrichedAssignment['internship'] = $internship ? [
        'id' => $internship['id'],
        'title' => $internship['title'],
        'company' => $internship['company_name'],
        'start_date' => $internship['start_date'],
        'end_date' => $internship['end_date']
    ] : null;
    
    $enrichedAssignments[] = $enrichedAssignment;
}

// Envoyer la réponse
sendJsonResponse([
    'data' => $enrichedAssignments,
    'meta' => [
        'current_page' => $page,
        'total_pages' => $totalPages,
        'total_records' => $total,
        'per_page' => $limit
    ]
]);