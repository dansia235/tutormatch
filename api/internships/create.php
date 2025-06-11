<?php
/**
 * Créer un nouveau stage
 * POST /api/internships
 */

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Méthode non autorisée', 405);
}

// Vérifier les droits d'accès (admin ou coordinateur)
if (!hasRole(['admin', 'coordinator'])) {
    sendError('Accès refusé', 403);
}

// Récupérer les données de la requête
$data = json_decode(file_get_contents('php://input'), true);

if (empty($data)) {
    sendError('Aucune donnée fournie', 400);
}

// Valider les données requises
$requiredFields = ['company_id', 'title', 'description', 'start_date', 'end_date', 'domain'];
foreach ($requiredFields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        sendError("Le champ '$field' est requis", 400);
    }
}

// Valider la période du stage
$startDate = strtotime($data['start_date']);
$endDate = strtotime($data['end_date']);

if (!$startDate || !$endDate) {
    sendError('Dates invalides', 400);
}

if ($startDate >= $endDate) {
    sendError('La date de début doit être antérieure à la date de fin', 400);
}

// Valider le mode de travail
if (isset($data['work_mode'])) {
    $validWorkModes = ['on_site', 'remote', 'hybrid'];
    if (!in_array($data['work_mode'], $validWorkModes)) {
        sendError('Mode de travail invalide', 400);
    }
}

// Vérifier si l'entreprise existe
$companyModel = new Company($db);
$company = $companyModel->getById($data['company_id']);

if (!$company) {
    sendError('Entreprise non trouvée', 404);
}

// Initialiser le modèle stage
$internshipModel = new Internship($db);

// Préparer les données du stage
$internshipData = [
    'company_id' => $data['company_id'],
    'title' => $data['title'],
    'description' => $data['description'],
    'requirements' => $data['requirements'] ?? null,
    'start_date' => date('Y-m-d', $startDate),
    'end_date' => date('Y-m-d', $endDate),
    'location' => $data['location'] ?? null,
    'work_mode' => $data['work_mode'] ?? 'on_site',
    'compensation' => $data['compensation'] ?? null,
    'domain' => $data['domain'],
    'status' => 'available'
];

// Créer le stage
$internshipId = $internshipModel->create($internshipData);

if (!$internshipId) {
    sendError('Erreur lors de la création du stage', 500);
}

// Ajouter les compétences si fournies
if (isset($data['skills']) && is_array($data['skills']) && !empty($data['skills'])) {
    $skillModel = new InternshipSkill($db);
    
    foreach ($data['skills'] as $skill) {
        $skillModel->create([
            'internship_id' => $internshipId,
            'skill_name' => $skill
        ]);
    }
}

// Récupérer le stage créé
$createdInternship = $internshipModel->getById($internshipId);

// Récupérer les compétences du stage
$skills = $skillModel->getByInternshipId($internshipId);

// Formater les dates
$createdInternship['start_date'] = date('Y-m-d', strtotime($createdInternship['start_date']));
$createdInternship['end_date'] = date('Y-m-d', strtotime($createdInternship['end_date']));
$createdInternship['created_at'] = date('Y-m-d H:i:s', strtotime($createdInternship['created_at']));
$createdInternship['updated_at'] = date('Y-m-d H:i:s', strtotime($createdInternship['updated_at']));

// Préparer la réponse
$responseData = array_merge($createdInternship, [
    'company' => $company,
    'skills' => $skills
]);

// Envoyer la réponse
sendJsonResponse([
    'success' => true,
    'message' => 'Stage créé avec succès',
    'data' => $responseData
], 201);