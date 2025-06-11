<?php
/**
 * Liste des tuteurs
 * GET /api/teachers
 */

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Méthode non autorisée', 405);
}

// Vérifier les droits d'accès (admin, coordinateur ou étudiant)
if (!hasRole(['admin', 'coordinator', 'student'])) {
    sendError('Accès refusé', 403);
}

// Récupérer les paramètres de requête
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$search = isset($_GET['search']) ? $_GET['search'] : null;
$department = isset($_GET['department']) ? $_GET['department'] : null;
$specialty = isset($_GET['specialty']) ? $_GET['specialty'] : null;
$available = isset($_GET['available']) ? filter_var($_GET['available'], FILTER_VALIDATE_BOOLEAN) : null;

// Valider les paramètres
if ($page < 1) $page = 1;
if ($limit < 1 || $limit > 50) $limit = 10;

// Initialiser le modèle tuteur
$teacherModel = new Teacher($db);

// Construire les options de requête
$options = [
    'page' => $page,
    'limit' => $limit
];

if ($search) {
    $options['search'] = $search;
}

if ($department) {
    $options['department'] = $department;
}

if ($specialty) {
    $options['specialty'] = $specialty;
}

if ($available !== null) {
    $options['available'] = $available;
}

// Récupérer les tuteurs selon les filtres
$teachers = $teacherModel->getAll($options);
$total = $teacherModel->countAll($options);

// Calculer la pagination
$totalPages = ceil($total / $limit);

// Transformer les données pour l'API
$formattedTeachers = [];
foreach ($teachers as $teacher) {
    // Récupérer les données utilisateur associées
    $userModel = new User($db);
    $user = $userModel->getById($teacher['user_id']);
    
    if ($user) {
        // Masquer le mot de passe
        unset($user['password']);
        
        // Fusionner les données tuteur et utilisateur
        $formattedTeacher = array_merge($teacher, [
            'user' => $user
        ]);
        
        // Compter le nombre d'étudiants assignés
        $assignmentModel = new Assignment($db);
        $assignedStudentsCount = $assignmentModel->countByTeacherId($teacher['id']);
        
        $formattedTeacher['assigned_students_count'] = $assignedStudentsCount;
        $formattedTeacher['available_slots'] = max(0, $teacher['max_students'] - $assignedStudentsCount);
        
        $formattedTeachers[] = $formattedTeacher;
    }
}

// Envoyer la réponse
sendJsonResponse([
    'data' => $formattedTeachers,
    'meta' => [
        'current_page' => $page,
        'total_pages' => $totalPages,
        'total_records' => $total,
        'per_page' => $limit
    ]
]);