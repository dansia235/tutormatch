<?php
/**
 * Créer une nouvelle entreprise
 * POST /api/companies
 */

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Méthode non autorisée', 405);
}

// Vérifier les permissions (seuls les administrateurs et coordinateurs peuvent créer des entreprises)
$currentUserRole = $_SESSION['user_role'];
if ($currentUserRole !== 'admin' && $currentUserRole !== 'coordinator') {
    sendError('Accès non autorisé', 403);
}

// Récupérer les données de la requête
$requestBody = json_decode(file_get_contents('php://input'), true);
if (!$requestBody) {
    sendError('Données d\'entreprise manquantes', 400);
}

// Valider les données requises
$requiredFields = ['name', 'address', 'city', 'country'];
foreach ($requiredFields as $field) {
    if (!isset($requestBody[$field]) || empty($requestBody[$field])) {
        sendError("Le champ '$field' est requis", 400);
    }
}

// Initialiser le modèle d'entreprises
$companyModel = new Company($db);

// Vérifier si l'entreprise existe déjà
$existingCompany = $companyModel->getByName($requestBody['name']);
if ($existingCompany) {
    sendError('Une entreprise avec ce nom existe déjà', 400);
}

// Préparer les données de l'entreprise
$companyData = [
    'name' => $requestBody['name'],
    'description' => isset($requestBody['description']) ? $requestBody['description'] : '',
    'address' => $requestBody['address'],
    'city' => $requestBody['city'],
    'state' => isset($requestBody['state']) ? $requestBody['state'] : '',
    'postal_code' => isset($requestBody['postal_code']) ? $requestBody['postal_code'] : '',
    'country' => $requestBody['country'],
    'phone' => isset($requestBody['phone']) ? $requestBody['phone'] : '',
    'email' => isset($requestBody['email']) ? $requestBody['email'] : '',
    'website' => isset($requestBody['website']) ? $requestBody['website'] : '',
    'sector' => isset($requestBody['sector']) ? $requestBody['sector'] : '',
    'size' => isset($requestBody['size']) ? $requestBody['size'] : '',
    'created_by' => $_SESSION['user_id'],
    'created_at' => date('Y-m-d H:i:s')
];

// Créer l'entreprise
$newCompanyId = $companyModel->create($companyData);
if (!$newCompanyId) {
    sendError('Échec de la création de l\'entreprise', 500);
}

// Ajouter les contacts si fournis
if (isset($requestBody['contacts']) && is_array($requestBody['contacts'])) {
    foreach ($requestBody['contacts'] as $contact) {
        if (isset($contact['name']) && isset($contact['email'])) {
            $contactData = [
                'company_id' => $newCompanyId,
                'name' => $contact['name'],
                'title' => isset($contact['title']) ? $contact['title'] : '',
                'department' => isset($contact['department']) ? $contact['department'] : '',
                'email' => $contact['email'],
                'phone' => isset($contact['phone']) ? $contact['phone'] : '',
                'is_primary' => isset($contact['is_primary']) ? (bool)$contact['is_primary'] : false
            ];
            
            $companyModel->addContact($contactData);
        }
    }
}

// Récupérer l'entreprise créée
$newCompany = $companyModel->getById($newCompanyId);
$contacts = $companyModel->getContacts($newCompanyId);

// Enrichir les données de l'entreprise
$enrichedCompany = $newCompany;
$enrichedCompany['contacts'] = $contacts;

// Envoyer la réponse
sendJsonResponse([
    'message' => 'Entreprise créée avec succès',
    'data' => $enrichedCompany
], 201);