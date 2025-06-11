<?php
/**
 * Traitement du formulaire d'ajout d'entreprise
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../../includes/init.php';

// Vérifier les permissions
requireRole(['admin', 'coordinator']);

// Vérifier la méthode de requête
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlashMessage('error', 'Méthode non autorisée');
    redirect('/tutoring/views/admin/companies.php');
}

// Vérifier le jeton CSRF
if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
    setFlashMessage('error', 'Token de sécurité invalide. Veuillez réessayer.');
    redirect('/tutoring/views/admin/companies.php');
}

// Validation des données
$errors = [];
$formData = [];

// Nom (champ obligatoire)
if (empty($_POST['name'])) {
    $errors[] = 'Le nom de l\'entreprise est obligatoire';
} else {
    $formData['name'] = trim($_POST['name']);
    
    // Vérifier si le nom existe déjà
    $checkQuery = "SELECT id FROM companies WHERE name = :name";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':name', $formData['name']);
    $checkStmt->execute();
    
    if ($checkStmt->fetch()) {
        $errors[] = 'Une entreprise avec ce nom existe déjà';
    }
}

// Champs optionnels
$optionalFields = [
    'description', 'website', 'address', 'city', 'postal_code', 
    'country', 'contact_name', 'contact_title', 'contact_email', 'contact_phone'
];

foreach ($optionalFields as $field) {
    $formData[$field] = isset($_POST[$field]) ? trim($_POST[$field]) : null;
}

// Validation de l'email du contact
if (!empty($formData['contact_email']) && !filter_var($formData['contact_email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'L\'adresse email du contact n\'est pas valide';
}

// Validation de l'URL du site web
if (!empty($formData['website']) && !filter_var($formData['website'], FILTER_VALIDATE_URL)) {
    $errors[] = 'L\'URL du site web n\'est pas valide';
}

// Gestion de l'état actif
$formData['active'] = isset($_POST['active']) ? 1 : 0;

// Gestion du logo
$formData['logo_path'] = null; // Par défaut, pas de logo

// Traitement du téléchargement d'un logo
if (isset($_FILES['logo_path']) && $_FILES['logo_path']['error'] === UPLOAD_ERR_OK) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxFileSize = 2 * 1024 * 1024; // 2 Mo
    
    $fileType = $_FILES['logo_path']['type'];
    $fileSize = $_FILES['logo_path']['size'];
    $fileName = $_FILES['logo_path']['name'];
    
    // Vérifier le type de fichier
    if (!in_array($fileType, $allowedTypes)) {
        $errors[] = 'Le type de fichier n\'est pas autorisé. Formats acceptés : JPG, PNG, GIF';
    }
    
    // Vérifier la taille du fichier
    if ($fileSize > $maxFileSize) {
        $errors[] = 'Le fichier est trop volumineux. Taille maximale : 2 Mo';
    }
}

// Si des erreurs sont présentes, rediriger vers le formulaire avec les erreurs
if (!empty($errors)) {
    $_SESSION['form_errors'] = $errors;
    $_SESSION['form_data'] = $_POST;
    redirect('/tutoring/views/admin/companies/create.php');
}

// Insérer l'entreprise dans la base de données
try {
    $sql = "INSERT INTO companies (
                name, description, website, address, city, postal_code, 
                country, contact_name, contact_title, contact_email, 
                contact_phone, logo_path, active, created_at, updated_at
            ) VALUES (
                :name, :description, :website, :address, :city, :postal_code, 
                :country, :contact_name, :contact_title, :contact_email, 
                :contact_phone, :logo_path, :active, NOW(), NOW()
            )";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':name', $formData['name']);
    $stmt->bindParam(':description', $formData['description']);
    $stmt->bindParam(':website', $formData['website']);
    $stmt->bindParam(':address', $formData['address']);
    $stmt->bindParam(':city', $formData['city']);
    $stmt->bindParam(':postal_code', $formData['postal_code']);
    $stmt->bindParam(':country', $formData['country']);
    $stmt->bindParam(':contact_name', $formData['contact_name']);
    $stmt->bindParam(':contact_title', $formData['contact_title']);
    $stmt->bindParam(':contact_email', $formData['contact_email']);
    $stmt->bindParam(':contact_phone', $formData['contact_phone']);
    $stmt->bindParam(':logo_path', $formData['logo_path']);
    $stmt->bindParam(':active', $formData['active']);
    
    $stmt->execute();
    
    $companyId = $db->lastInsertId();
    
    // Traiter le téléchargement du logo si un fichier a été soumis
    if (isset($_FILES['logo_path']) && $_FILES['logo_path']['error'] === UPLOAD_ERR_OK) {
        // Créer le dossier des logos s'il n'existe pas
        $uploadDir = '/tutoring/uploads/logos/';
        $physicalUploadDir = $_SERVER['DOCUMENT_ROOT'] . $uploadDir;
        
        if (!is_dir($physicalUploadDir)) {
            mkdir($physicalUploadDir, 0755, true);
        }
        
        // Générer un nom de fichier unique
        $fileExtension = pathinfo($_FILES['logo_path']['name'], PATHINFO_EXTENSION);
        $newFileName = 'company_' . $companyId . '_' . uniqid() . '.' . $fileExtension;
        $filePath = $uploadDir . $newFileName;
        $physicalFilePath = $physicalUploadDir . $newFileName;
        
        // Déplacer le fichier téléchargé
        if (move_uploaded_file($_FILES['logo_path']['tmp_name'], $physicalFilePath)) {
            // Mettre à jour le chemin du logo dans la base de données
            $updateSql = "UPDATE companies SET logo_path = :logo_path WHERE id = :id";
            $updateStmt = $db->prepare($updateSql);
            $updateStmt->bindParam(':logo_path', $filePath);
            $updateStmt->bindParam(':id', $companyId);
            $updateStmt->execute();
        } else {
            // Enregistrer une erreur mais ne pas bloquer la création de l'entreprise
            setFlashMessage('warning', 'L\'entreprise a été créée, mais il y a eu un problème lors du téléchargement du logo.');
            redirect('/tutoring/views/admin/companies/show.php?id=' . $companyId);
        }
    }
    
    // Rediriger vers la page de détails avec un message de succès
    setFlashMessage('success', 'L\'entreprise a été créée avec succès');
    redirect('/tutoring/views/admin/companies/show.php?id=' . $companyId);
    
} catch (PDOException $e) {
    // Gérer les erreurs de base de données
    $_SESSION['form_errors'] = ['Erreur de base de données : ' . $e->getMessage()];
    $_SESSION['form_data'] = $_POST;
    redirect('/tutoring/views/admin/companies/create.php');
}