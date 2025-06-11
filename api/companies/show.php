<?php
/**
 * Détails d'une entreprise
 * GET /api/companies/{id}
 */

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Méthode non autorisée', 405);
}

// Vérifier que l'ID est présent
if (!isset($urlParts[2]) || !is_numeric($urlParts[2])) {
    sendError('ID d\'entreprise invalide', 400);
}

$companyId = (int)$urlParts[2];

// Initialiser les modèles
$companyModel = new Company($db);
$internshipModel = new Internship($db);

// Récupérer l'entreprise
$company = $companyModel->getById($companyId);
if (!$company) {
    sendError('Entreprise non trouvée', 404);
}

// Enrichir les données de l'entreprise
$enrichedCompany = $company;

// Récupérer les stages actifs de l'entreprise
$activeInternships = $internshipModel->getByCompany($companyId, ['status' => 'available']);
$enrichedCompany['active_internships'] = $activeInternships;

// Récupérer les statistiques de l'entreprise
$statistics = $companyModel->getStatistics($companyId);
$enrichedCompany['statistics'] = $statistics;

// Récupérer les contacts de l'entreprise
$contacts = $companyModel->getContacts($companyId);
$enrichedCompany['contacts'] = $contacts;

// Envoyer la réponse
sendJsonResponse([
    'data' => $enrichedCompany
]);