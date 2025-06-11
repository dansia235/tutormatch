<?php
/**
 * Liste des documents
 * GET /api/documents
 */

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Méthode non autorisée', 405);
}

// Récupérer les paramètres de requête
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
$assignmentId = isset($_GET['assignment_id']) ? (int)$_GET['assignment_id'] : null;
$type = isset($_GET['type']) ? $_GET['type'] : null;
$status = isset($_GET['status']) ? $_GET['status'] : null;

// Valider les paramètres
if ($page < 1) $page = 1;
if ($limit < 1 || $limit > 50) $limit = 10;

// Initialiser le modèle document
$documentModel = new Document($db);

// Construire les options de requête
$options = [
    'page' => $page,
    'limit' => $limit
];

// Si l'utilisateur n'est pas admin ou coordinateur, limiter aux documents accessibles
if (!hasRole(['admin', 'coordinator'])) {
    if (hasRole('teacher')) {
        // Les tuteurs peuvent voir les documents de leurs étudiants
        $teacherModel = new Teacher($db);
        $teacher = $teacherModel->getByUserId($_SESSION['user_id']);
        
        if (!$teacher) {
            sendError('Profil tuteur non trouvé', 404);
        }
        
        $options['teacher_id'] = $teacher['id'];
    } else {
        // Les étudiants peuvent voir uniquement leurs propres documents
        $options['user_id'] = $_SESSION['user_id'];
    }
} else {
    // Pour les admins et coordinateurs, filtre optionnel par utilisateur
    if ($userId) {
        $options['user_id'] = $userId;
    }
}

// Filtres supplémentaires
if ($assignmentId) {
    $options['assignment_id'] = $assignmentId;
}

if ($type) {
    $validTypes = ['contract', 'report', 'evaluation', 'certificate', 'other'];
    if (in_array($type, $validTypes)) {
        $options['type'] = $type;
    }
}

if ($status) {
    $validStatuses = ['draft', 'submitted', 'approved', 'rejected'];
    if (in_array($status, $validStatuses)) {
        $options['status'] = $status;
    }
}

// Récupérer les documents selon les filtres
$documents = $documentModel->getAll($options);
$total = $documentModel->countAll($options);

// Calculer la pagination
$totalPages = ceil($total / $limit);

// Transformer les données pour l'API
$formattedDocuments = [];
foreach ($documents as $document) {
    // Récupérer les informations sur l'utilisateur propriétaire
    $userModel = new User($db);
    $user = $userModel->getById($document['user_id']);
    
    // Masquer les informations sensibles
    unset($user['password']);
    
    // Récupérer les informations sur l'affectation si applicable
    $assignmentInfo = null;
    if ($document['assignment_id']) {
        $assignmentModel = new Assignment($db);
        $assignment = $assignmentModel->getById($document['assignment_id']);
        
        if ($assignment) {
            $studentModel = new Student($db);
            $student = $studentModel->getById($assignment['student_id']);
            
            $teacherModel = new Teacher($db);
            $teacher = $teacherModel->getById($assignment['teacher_id']);
            
            $internshipModel = new Internship($db);
            $internship = $internshipModel->getById($assignment['internship_id']);
            
            $assignmentInfo = [
                'id' => $assignment['id'],
                'status' => $assignment['status'],
                'student_id' => $assignment['student_id'],
                'teacher_id' => $assignment['teacher_id'],
                'internship_id' => $assignment['internship_id'],
                'student_name' => $student ? $student['user_first_name'] . ' ' . $student['user_last_name'] : 'N/A',
                'teacher_name' => $teacher ? $teacher['user_first_name'] . ' ' . $teacher['user_last_name'] : 'N/A',
                'internship_title' => $internship ? $internship['title'] : 'N/A'
            ];
        }
    }
    
    // Formater le document
    $formattedDocument = [
        'id' => $document['id'],
        'title' => $document['title'],
        'type' => $document['type'],
        'file_path' => $document['file_path'],
        'upload_date' => date('Y-m-d H:i:s', strtotime($document['upload_date'])),
        'status' => $document['status'],
        'feedback' => $document['feedback'],
        'version' => $document['version'],
        'user' => [
            'id' => $user['id'],
            'name' => $user['first_name'] . ' ' . $user['last_name'],
            'role' => $user['role']
        ]
    ];
    
    // Ajouter les informations d'affectation si disponibles
    if ($assignmentInfo) {
        $formattedDocument['assignment'] = $assignmentInfo;
    }
    
    $formattedDocuments[] = $formattedDocument;
}

// Envoyer la réponse
sendJsonResponse([
    'data' => $formattedDocuments,
    'meta' => [
        'current_page' => $page,
        'total_pages' => $totalPages,
        'total_records' => $total,
        'per_page' => $limit
    ]
]);