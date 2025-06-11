<?php
/**
 * Liste des stages
 * GET /api/internships
 */

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Méthode non autorisée', 405);
}

// Récupérer les paramètres de requête
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$search = isset($_GET['search']) ? $_GET['search'] : null;
$company = isset($_GET['company_id']) ? (int)$_GET['company_id'] : null;
$domain = isset($_GET['domain']) ? $_GET['domain'] : null;
$status = isset($_GET['status']) ? $_GET['status'] : null;
$location = isset($_GET['location']) ? $_GET['location'] : null;
$workMode = isset($_GET['work_mode']) ? $_GET['work_mode'] : null;

// Valider les paramètres
if ($page < 1) $page = 1;
if ($limit < 1 || $limit > 50) $limit = 10;

// Initialiser le modèle stage
$internshipModel = new Internship($db);

// Construire les options de requête
$options = [
    'page' => $page,
    'limit' => $limit
];

if ($search) {
    $options['search'] = $search;
}

if ($company) {
    $options['company_id'] = $company;
}

if ($domain) {
    $options['domain'] = $domain;
}

if ($status) {
    $validStatuses = ['available', 'assigned', 'completed', 'cancelled'];
    if (in_array($status, $validStatuses)) {
        $options['status'] = $status;
    }
}

if ($location) {
    $options['location'] = $location;
}

if ($workMode) {
    $validWorkModes = ['on_site', 'remote', 'hybrid'];
    if (in_array($workMode, $validWorkModes)) {
        $options['work_mode'] = $workMode;
    }
}

// Récupérer les stages selon les filtres
$internships = $internshipModel->getAll($options);
$total = $internshipModel->countAll($options);

// Calculer la pagination
$totalPages = ceil($total / $limit);

// Transformer les données pour l'API
$formattedInternships = [];
foreach ($internships as $internship) {
    // Récupérer les informations de l'entreprise
    $companyModel = new Company($db);
    $company = $companyModel->getById($internship['company_id']);
    
    // Récupérer les compétences requises
    $skillModel = new InternshipSkill($db);
    $skills = $skillModel->getByInternshipId($internship['id']);
    
    // Formater les dates
    $internship['start_date'] = date('Y-m-d', strtotime($internship['start_date']));
    $internship['end_date'] = date('Y-m-d', strtotime($internship['end_date']));
    $internship['created_at'] = date('Y-m-d H:i:s', strtotime($internship['created_at']));
    $internship['updated_at'] = date('Y-m-d H:i:s', strtotime($internship['updated_at']));
    
    // Formater le stage
    $formattedInternship = [
        'id' => $internship['id'],
        'title' => $internship['title'],
        'description' => $internship['description'],
        'requirements' => $internship['requirements'],
        'start_date' => $internship['start_date'],
        'end_date' => $internship['end_date'],
        'location' => $internship['location'],
        'work_mode' => $internship['work_mode'],
        'compensation' => $internship['compensation'],
        'domain' => $internship['domain'],
        'status' => $internship['status'],
        'created_at' => $internship['created_at'],
        'updated_at' => $internship['updated_at'],
        'company' => $company,
        'skills' => $skills
    ];
    
    // Vérifier si le stage est assigné
    if ($internship['status'] === 'assigned' || $internship['status'] === 'completed') {
        $assignmentModel = new Assignment($db);
        $assignment = $assignmentModel->getByInternshipId($internship['id']);
        
        if ($assignment) {
            $formattedInternship['assignment'] = [
                'id' => $assignment['id'],
                'student_id' => $assignment['student_id'],
                'teacher_id' => $assignment['teacher_id'],
                'status' => $assignment['status'],
                'assignment_date' => $assignment['assignment_date']
            ];
        }
    }
    
    $formattedInternships[] = $formattedInternship;
}

// Envoyer la réponse
sendJsonResponse([
    'data' => $formattedInternships,
    'meta' => [
        'current_page' => $page,
        'total_pages' => $totalPages,
        'total_records' => $total,
        'per_page' => $limit
    ]
]);