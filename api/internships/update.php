<?php
/**
 * Mettre à jour un stage
 * PUT /api/internships/{id}
 */

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    sendError('Méthode non autorisée', 405);
}

// Vérifier les droits d'accès (admin ou coordinateur)
if (!hasRole(['admin', 'coordinator'])) {
    sendError('Accès refusé', 403);
}

// Récupérer l'ID du stage depuis l'URL
$internshipId = isset($urlParts[2]) ? (int)$urlParts[2] : 0;

if ($internshipId <= 0) {
    sendError('ID stage invalide', 400);
}

// Récupérer les données de la requête
$data = json_decode(file_get_contents('php://input'), true);

if (empty($data)) {
    sendError('Aucune donnée fournie', 400);
}

// Initialiser le modèle stage
$internshipModel = new Internship($db);

// Récupérer le stage existant
$existingInternship = $internshipModel->getById($internshipId);

if (!$existingInternship) {
    sendError('Stage non trouvé', 404);
}

// Vérifier si le stage est déjà assigné ou terminé
if ($existingInternship['status'] === 'assigned' || $existingInternship['status'] === 'completed') {
    // Limiter les champs qui peuvent être modifiés pour un stage assigné
    $allowedFields = ['title', 'description', 'requirements', 'status'];
    
    foreach ($data as $field => $value) {
        if (!in_array($field, $allowedFields)) {
            unset($data[$field]);
        }
    }
    
    if (empty($data)) {
        sendError('Aucune donnée valide à mettre à jour. Pour un stage assigné, seuls les champs suivants peuvent être modifiés: ' . implode(', ', $allowedFields), 400);
    }
}

// Préparer les données à mettre à jour
$updateData = [];

// Mettre à jour le titre si fourni
if (isset($data['title'])) {
    $updateData['title'] = $data['title'];
}

// Mettre à jour la description si fournie
if (isset($data['description'])) {
    $updateData['description'] = $data['description'];
}

// Mettre à jour les prérequis si fournis
if (isset($data['requirements'])) {
    $updateData['requirements'] = $data['requirements'];
}

// Mettre à jour les dates si fournies
if (isset($data['start_date']) && isset($data['end_date'])) {
    $startDate = strtotime($data['start_date']);
    $endDate = strtotime($data['end_date']);
    
    if (!$startDate || !$endDate) {
        sendError('Dates invalides', 400);
    }
    
    if ($startDate >= $endDate) {
        sendError('La date de début doit être antérieure à la date de fin', 400);
    }
    
    $updateData['start_date'] = date('Y-m-d', $startDate);
    $updateData['end_date'] = date('Y-m-d', $endDate);
} elseif (isset($data['start_date'])) {
    $startDate = strtotime($data['start_date']);
    $endDate = strtotime($existingInternship['end_date']);
    
    if (!$startDate) {
        sendError('Date de début invalide', 400);
    }
    
    if ($startDate >= $endDate) {
        sendError('La date de début doit être antérieure à la date de fin', 400);
    }
    
    $updateData['start_date'] = date('Y-m-d', $startDate);
} elseif (isset($data['end_date'])) {
    $startDate = strtotime($existingInternship['start_date']);
    $endDate = strtotime($data['end_date']);
    
    if (!$endDate) {
        sendError('Date de fin invalide', 400);
    }
    
    if ($startDate >= $endDate) {
        sendError('La date de début doit être antérieure à la date de fin', 400);
    }
    
    $updateData['end_date'] = date('Y-m-d', $endDate);
}

// Mettre à jour l'emplacement si fourni
if (isset($data['location'])) {
    $updateData['location'] = $data['location'];
}

// Mettre à jour le mode de travail si fourni
if (isset($data['work_mode'])) {
    $validWorkModes = ['on_site', 'remote', 'hybrid'];
    if (!in_array($data['work_mode'], $validWorkModes)) {
        sendError('Mode de travail invalide', 400);
    }
    
    $updateData['work_mode'] = $data['work_mode'];
}

// Mettre à jour la rémunération si fournie
if (isset($data['compensation'])) {
    $updateData['compensation'] = $data['compensation'];
}

// Mettre à jour le domaine si fourni
if (isset($data['domain'])) {
    $updateData['domain'] = $data['domain'];
}

// Mettre à jour le statut si fourni
if (isset($data['status'])) {
    $validStatuses = ['available', 'assigned', 'completed', 'cancelled'];
    if (!in_array($data['status'], $validStatuses)) {
        sendError('Statut invalide', 400);
    }
    
    $updateData['status'] = $data['status'];
}

// Mettre à jour l'entreprise si fournie
if (isset($data['company_id'])) {
    // Vérifier si l'entreprise existe
    $companyModel = new Company($db);
    $company = $companyModel->getById($data['company_id']);
    
    if (!$company) {
        sendError('Entreprise non trouvée', 404);
    }
    
    $updateData['company_id'] = $data['company_id'];
}

// Si aucune donnée à mettre à jour
if (empty($updateData)) {
    sendError('Aucune donnée valide à mettre à jour', 400);
}

// Mettre à jour le stage
$success = $internshipModel->update($internshipId, $updateData);

if (!$success) {
    sendError('Erreur lors de la mise à jour du stage', 500);
}

// Mettre à jour les compétences si fournies
if (isset($data['skills']) && is_array($data['skills'])) {
    $skillModel = new InternshipSkill($db);
    
    // Supprimer les compétences existantes
    $skillModel->deleteByInternshipId($internshipId);
    
    // Ajouter les nouvelles compétences
    foreach ($data['skills'] as $skill) {
        $skillModel->create([
            'internship_id' => $internshipId,
            'skill_name' => $skill
        ]);
    }
}

// Récupérer le stage mis à jour
$updatedInternship = $internshipModel->getById($internshipId);

// Récupérer les informations de l'entreprise
$companyModel = new Company($db);
$company = $companyModel->getById($updatedInternship['company_id']);

// Récupérer les compétences du stage
$skillModel = new InternshipSkill($db);
$skills = $skillModel->getByInternshipId($internshipId);

// Formater les dates
$updatedInternship['start_date'] = date('Y-m-d', strtotime($updatedInternship['start_date']));
$updatedInternship['end_date'] = date('Y-m-d', strtotime($updatedInternship['end_date']));
$updatedInternship['created_at'] = date('Y-m-d H:i:s', strtotime($updatedInternship['created_at']));
$updatedInternship['updated_at'] = date('Y-m-d H:i:s', strtotime($updatedInternship['updated_at']));

// Préparer la réponse
$responseData = array_merge($updatedInternship, [
    'company' => $company,
    'skills' => $skills
]);

// Envoyer la réponse
sendJsonResponse([
    'success' => true,
    'message' => 'Stage mis à jour avec succès',
    'data' => $responseData
]);