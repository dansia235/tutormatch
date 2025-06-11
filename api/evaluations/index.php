<?php
/**
 * Liste des évaluations
 * GET /api/evaluations
 */

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Méthode non autorisée', 405);
}

// Récupérer les paramètres de requête
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$assignmentId = isset($_GET['assignment_id']) ? (int)$_GET['assignment_id'] : null;
$studentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : null;
$teacherId = isset($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : null;
$type = isset($_GET['type']) ? $_GET['type'] : null;

// Valider les paramètres
if ($page < 1) $page = 1;
if ($limit < 1 || $limit > 50) $limit = 10;

// Initialiser les modèles
$evaluationModel = new Evaluation($db);
$userModel = new User($db);
$studentModel = new Student($db);
$teacherModel = new Teacher($db);
$assignmentModel = new Assignment($db);

// Vérifier les permissions
$currentUserRole = $_SESSION['user_role'];
$currentUserId = $_SESSION['user_id'];

// Construire les options de requête
$options = [
    'page' => $page,
    'limit' => $limit
];

// Appliquer les filtres
if ($assignmentId) $options['assignment_id'] = $assignmentId;
if ($type) $options['type'] = $type;

// Restreindre l'accès selon le rôle
if ($currentUserRole === 'student') {
    // Un étudiant ne peut voir que ses propres évaluations
    $student = $studentModel->getByUserId($currentUserId);
    if (!$student) {
        sendError('Profil étudiant non trouvé', 404);
    }
    
    // Si un ID d'étudiant est fourni, s'assurer qu'il correspond à l'étudiant actuel
    if ($studentId && $studentId != $student['id']) {
        sendError('Vous n\'êtes pas autorisé à voir les évaluations d\'autres étudiants', 403);
    }
    
    $options['student_id'] = $student['id'];
} elseif ($currentUserRole === 'teacher') {
    // Un tuteur ne peut voir que les évaluations des étudiants dont il est tuteur
    $teacher = $teacherModel->getByUserId($currentUserId);
    if (!$teacher) {
        sendError('Profil tuteur non trouvé', 404);
    }
    
    // Si un ID de tuteur est fourni, s'assurer qu'il correspond au tuteur actuel
    if ($teacherId && $teacherId != $teacher['id']) {
        sendError('Vous n\'êtes pas autorisé à voir les évaluations d\'autres tuteurs', 403);
    }
    
    $options['teacher_id'] = $teacher['id'];
} else {
    // Pour les administrateurs et coordinateurs, appliquer les filtres supplémentaires
    if ($studentId) $options['student_id'] = $studentId;
    if ($teacherId) $options['teacher_id'] = $teacherId;
}

// Récupérer les évaluations selon les filtres
$evaluations = $evaluationModel->getAll($options);
$total = $evaluationModel->countAll($options);

// Calculer la pagination
$totalPages = ceil($total / $limit);

// Enrichir les données avec les informations associées
$enrichedEvaluations = [];
foreach ($evaluations as $evaluation) {
    // Récupérer les détails de l'affectation
    $assignment = $assignmentModel->getById($evaluation['assignment_id']);
    
    $enrichedEvaluation = $evaluation;
    
    if ($assignment) {
        $student = $studentModel->getById($assignment['student_id']);
        $teacher = $teacherModel->getById($assignment['teacher_id']);
        
        $studentUser = $student ? $userModel->getById($student['user_id']) : null;
        $teacherUser = $teacher ? $userModel->getById($teacher['user_id']) : null;
        
        $enrichedEvaluation['assignment'] = [
            'id' => $assignment['id'],
            'student' => $studentUser ? [
                'id' => $student['id'],
                'name' => $studentUser['first_name'] . ' ' . $studentUser['last_name']
            ] : null,
            'teacher' => $teacherUser ? [
                'id' => $teacher['id'],
                'name' => $teacherUser['first_name'] . ' ' . $teacherUser['last_name']
            ] : null,
            'status' => $assignment['status']
        ];
    }
    
    // Masquer certaines informations sensibles selon le rôle
    if ($currentUserRole === 'student' && $evaluation['type'] === 'teacher') {
        // Les étudiants ne peuvent pas voir les commentaires des évaluations des tuteurs sur eux
        // avant la fin du stage
        if ($assignment && $assignment['status'] !== 'completed') {
            $enrichedEvaluation['comments'] = 'Cette évaluation sera visible à la fin du stage';
        }
    }
    
    $enrichedEvaluations[] = $enrichedEvaluation;
}

// Envoyer la réponse
sendJsonResponse([
    'data' => $enrichedEvaluations,
    'meta' => [
        'current_page' => $page,
        'total_pages' => $totalPages,
        'total_records' => $total,
        'per_page' => $limit
    ]
]);