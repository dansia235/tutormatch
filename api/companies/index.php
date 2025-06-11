<?php
/**
 * Liste des entreprises
 * GET /api/companies
 */

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Méthode non autorisée', 405);
}

// Récupérer les paramètres de requête
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$search = isset($_GET['search']) ? $_GET['search'] : null;
$sector = isset($_GET['sector']) ? $_GET['sector'] : null;
$location = isset($_GET['location']) ? $_GET['location'] : null;

// Valider les paramètres
if ($page < 1) $page = 1;
if ($limit < 1 || $limit > 50) $limit = 10;

// Initialiser le modèle d'entreprises
$companyModel = new Company($db);

// Construire les options de requête
$options = [
    'page' => $page,
    'limit' => $limit
];

// Appliquer les filtres
if ($search) $options['search'] = $search;
if ($sector) $options['sector'] = $sector;
if ($location) $options['location'] = $location;

// Récupérer les entreprises selon les filtres
$companies = $companyModel->getAll($options);
$total = $companyModel->countAll($options);

// Calculer la pagination
$totalPages = ceil($total / $limit);

// Enrichir les données avec les informations additionnelles
$enrichedCompanies = [];
foreach ($companies as $company) {
    // Compter les stages actifs pour cette entreprise
    $activeInternships = $companyModel->countActiveInternships($company['id']);
    
    $enrichedCompany = $company;
    $enrichedCompany['active_internships_count'] = $activeInternships;
    
    $enrichedCompanies[] = $enrichedCompany;
}

// Envoyer la réponse
sendJsonResponse([
    'data' => $enrichedCompanies,
    'meta' => [
        'current_page' => $page,
        'total_pages' => $totalPages,
        'total_records' => $total,
        'per_page' => $limit
    ]
]);