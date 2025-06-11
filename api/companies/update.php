<?php
/**
 * Mettre à jour une entreprise
 * PUT /api/companies/{id}
 */

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    sendError('Méthode non autorisée', 405);
}

// Vérifier que l'ID est présent
if (!isset($urlParts[2]) || !is_numeric($urlParts[2])) {
    sendError('ID d\'entreprise invalide', 400);
}

$companyId = (int)$urlParts[2];

// Vérifier les permissions (seuls les administrateurs et coordinateurs peuvent modifier des entreprises)
$currentUserRole = $_SESSION['user_role'];
if ($currentUserRole !== 'admin' && $currentUserRole !== 'coordinator') {
    sendError('Accès non autorisé', 403);
}

// Récupérer les données de la requête
$requestBody = json_decode(file_get_contents('php://input'), true);
if (!$requestBody) {
    sendError('Données de mise à jour manquantes', 400);
}

// Initialiser le modèle d'entreprises
$companyModel = new Company($db);

// Vérifier que l'entreprise existe
$company = $companyModel->getById($companyId);
if (!$company) {
    sendError('Entreprise non trouvée', 404);
}

// Préparer les données à mettre à jour
$updateData = [];

// Champs pouvant être mis à jour
$updatableFields = [
    'name', 'description', 'address', 'city', 'state', 'postal_code', 
    'country', 'phone', 'email', 'website', 'sector', 'size'
];

foreach ($updatableFields as $field) {
    if (isset($requestBody[$field])) {
        $updateData[$field] = $requestBody[$field];
    }
}

// Vérifier si le nom est modifié et qu'il n'existe pas déjà
if (isset($updateData['name']) && $updateData['name'] !== $company['name']) {
    $existingCompany = $companyModel->getByName($updateData['name']);
    if ($existingCompany && $existingCompany['id'] != $companyId) {
        sendError('Une entreprise avec ce nom existe déjà', 400);
    }
}

// Ajouter la date de mise à jour
$updateData['updated_at'] = date('Y-m-d H:i:s');

// Mettre à jour l'entreprise
$success = $companyModel->update($companyId, $updateData);
if (!$success) {
    sendError('Échec de la mise à jour de l\'entreprise', 500);
}

// Gérer les contacts si fournis
if (isset($requestBody['contacts']) && is_array($requestBody['contacts'])) {
    // Supprimer tous les contacts existants si on remplace la liste complète
    if (isset($requestBody['replace_contacts']) && $requestBody['replace_contacts'] === true) {
        $companyModel->deleteAllContacts($companyId);
    }
    
    foreach ($requestBody['contacts'] as $contact) {
        if (isset($contact['id']) && is_numeric($contact['id'])) {
            // Mettre à jour un contact existant
            $contactId = (int)$contact['id'];
            $contactData = [];
            
            foreach (['name', 'title', 'department', 'email', 'phone', 'is_primary'] as $field) {
                if (isset($contact[$field])) {
                    $contactData[$field] = $contact[$field];
                }
            }
            
            if (!empty($contactData)) {
                $companyModel->updateContact($contactId, $contactData);
            }
        } elseif (isset($contact['name']) && isset($contact['email'])) {
            // Ajouter un nouveau contact
            $contactData = [
                'company_id' => $companyId,
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

// Récupérer l'entreprise mise à jour
$updatedCompany = $companyModel->getById($companyId);
$contacts = $companyModel->getContacts($companyId);

// Enrichir les données de l'entreprise
$enrichedCompany = $updatedCompany;
$enrichedCompany['contacts'] = $contacts;

// Envoyer la réponse
sendJsonResponse([
    'message' => 'Entreprise mise à jour avec succès',
    'data' => $enrichedCompany
]);