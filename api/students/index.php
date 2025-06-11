<?php
/**
 * Liste des étudiants
 * GET /api/students
 */

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Méthode non autorisée', 405);
}

// Vérifier les droits d'accès (admin, coordinateur ou tuteur)
if (!hasRole(['admin', 'coordinator', 'teacher'])) {
    sendError('Accès refusé', 403);
}

// Récupérer les paramètres de requête
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$search = isset($_GET['search']) ? $_GET['search'] : null;
$program = isset($_GET['program']) ? $_GET['program'] : null;
$level = isset($_GET['level']) ? $_GET['level'] : null;
$status = isset($_GET['status']) ? $_GET['status'] : null;
$teacherId = isset($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : null;
$hasInternship = isset($_GET['has_internship']) ? filter_var($_GET['has_internship'], FILTER_VALIDATE_BOOLEAN) : null;

// Valider les paramètres
if ($page < 1) $page = 1;
if ($limit < 1 || $limit > 50) $limit = 10;

// Initialiser le modèle étudiant
$studentModel = new Student($db);

// Construire les options de requête
$options = [
    'page' => $page,
    'limit' => $limit
];

if ($search) {
    $options['search'] = $search;
}

if ($program) {
    $options['program'] = $program;
}

if ($level) {
    $options['level'] = $level;
}

if ($status) {
    $validStatuses = ['active', 'graduated', 'suspended'];
    if (in_array($status, $validStatuses)) {
        $options['status'] = $status;
    }
}

if ($teacherId) {
    $options['teacher_id'] = $teacherId;
}

if ($hasInternship !== null) {
    $options['has_internship'] = $hasInternship;
}

// Si l'utilisateur est un tuteur, limiter aux étudiants assignés à ce tuteur
if (hasRole('teacher') && !hasRole(['admin', 'coordinator'])) {
    $teacherModel = new Teacher($db);
    $teacher = $teacherModel->getByUserId($_SESSION['user_id']);
    
    if ($teacher) {
        $options['teacher_id'] = $teacher['id'];
    } else {
        // Le tuteur n'a pas de profil associé
        sendJsonResponse([
            'data' => [],
            'meta' => [
                'current_page' => $page,
                'total_pages' => 0,
                'total_records' => 0,
                'per_page' => $limit
            ]
        ]);
        exit;
    }
}

// Récupérer les étudiants selon les filtres
$students = $studentModel->getAll($options);
$total = $studentModel->countAll($options);

// Calculer la pagination
$totalPages = ceil($total / $limit);

// Transformer les données pour l'API
$formattedStudents = [];
foreach ($students as $student) {
    // Récupérer les données utilisateur associées
    $userModel = new User($db);
    $user = $userModel->getById($student['user_id']);
    
    if ($user) {
        // Masquer le mot de passe
        unset($user['password']);
        
        // Fusionner les données étudiant et utilisateur
        $formattedStudent = array_merge($student, [
            'user' => $user
        ]);
        
        // Ajouter le stage actif si disponible
        $assignmentModel = new Assignment($db);
        $activeAssignment = $assignmentModel->getActiveByStudentId($student['id']);
        
        if ($activeAssignment) {
            $formattedStudent['active_assignment'] = $activeAssignment;
        }
        
        $formattedStudents[] = $formattedStudent;
    }
}

// Envoyer la réponse
sendJsonResponse([
    'data' => $formattedStudents,
    'meta' => [
        'current_page' => $page,
        'total_pages' => $totalPages,
        'total_records' => $total,
        'per_page' => $limit
    ]
]);