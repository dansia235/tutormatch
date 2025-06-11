<?php
/**
 * Liste des stages disponibles
 * GET /api/internships/available
 */

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Méthode non autorisée', 405);
}

// Récupérer les paramètres de requête
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$domain = isset($_GET['domain']) ? $_GET['domain'] : null;
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
    'limit' => $limit,
    'status' => 'available' // Uniquement les stages disponibles
];

if ($domain) {
    $options['domain'] = $domain;
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

// Récupérer les stages disponibles selon les filtres
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
    
    // Calculer la durée en semaines
    $startDate = new DateTime($internship['start_date']);
    $endDate = new DateTime($internship['end_date']);
    $interval = $startDate->diff($endDate);
    $durationWeeks = ceil($interval->days / 7);
    
    // Formater le stage
    $formattedInternship = [
        'id' => $internship['id'],
        'title' => $internship['title'],
        'description' => $internship['description'],
        'requirements' => $internship['requirements'],
        'start_date' => $internship['start_date'],
        'end_date' => $internship['end_date'],
        'duration_weeks' => $durationWeeks,
        'location' => $internship['location'],
        'work_mode' => $internship['work_mode'],
        'compensation' => $internship['compensation'],
        'domain' => $internship['domain'],
        'company' => [
            'id' => $company['id'],
            'name' => $company['name'],
            'city' => $company['city'],
            'country' => $company['country'],
            'logo_path' => $company['logo_path']
        ],
        'skills' => $skills
    ];
    
    // Ajouter des informations sur les préférences de l'étudiant connecté (si applicable)
    if (hasRole('student')) {
        $studentModel = new Student($db);
        $student = $studentModel->getByUserId($_SESSION['user_id']);
        
        if ($student) {
            $preferenceModel = new StudentPreference($db);
            $preference = $preferenceModel->getByStudentAndInternshipId($student['id'], $internship['id']);
            
            if ($preference) {
                $formattedInternship['preference'] = [
                    'id' => $preference['id'],
                    'preference_order' => $preference['preference_order']
                ];
            }
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