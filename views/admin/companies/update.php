<?php
/**
 * Traitement du formulaire de modification d'entreprise
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

// Vérifier l'ID de l'entreprise
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    setFlashMessage('error', 'ID d\'entreprise invalide');
    redirect('/tutoring/views/admin/companies.php');
}

$companyId = (int)$_POST['id'];

// Récupérer les informations actuelles de l'entreprise
$query = "SELECT * FROM companies WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $companyId);
$stmt->execute();
$company = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$company) {
    setFlashMessage('error', 'Entreprise non trouvée');
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
    
    // Vérifier si le nom existe déjà (pour une autre entreprise)
    $checkQuery = "SELECT id FROM companies WHERE name = :name AND id != :id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':name', $formData['name']);
    $checkStmt->bindParam(':id', $companyId);
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
$formData['logo_path'] = $company['logo_path']; // Par défaut, conserver le logo existant

// Traitement de la suppression du logo
if (isset($_POST['remove_logo']) && $_POST['remove_logo'] && !empty($company['logo_path'])) {
    // Supprimer le fichier physique si c'est un fichier local
    if (file_exists($_SERVER['DOCUMENT_ROOT'] . $company['logo_path'])) {
        unlink($_SERVER['DOCUMENT_ROOT'] . $company['logo_path']);
    }
    $formData['logo_path'] = null;
}

// Traitement du téléchargement d'un nouveau logo
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
    
    // Si aucune erreur, traiter le téléchargement
    if (empty($errors)) {
        // Créer le dossier des logos s'il n'existe pas
        $uploadDir = '/tutoring/uploads/logos/';
        $physicalUploadDir = $_SERVER['DOCUMENT_ROOT'] . $uploadDir;
        
        if (!is_dir($physicalUploadDir)) {
            mkdir($physicalUploadDir, 0755, true);
        }
        
        // Générer un nom de fichier unique
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
        $newFileName = 'company_' . $companyId . '_' . uniqid() . '.' . $fileExtension;
        $filePath = $uploadDir . $newFileName;
        $physicalFilePath = $physicalUploadDir . $newFileName;
        
        // Supprimer l'ancien logo si existant
        if (!empty($company['logo_path']) && file_exists($_SERVER['DOCUMENT_ROOT'] . $company['logo_path'])) {
            unlink($_SERVER['DOCUMENT_ROOT'] . $company['logo_path']);
        }
        
        // Déplacer le fichier téléchargé
        if (move_uploaded_file($_FILES['logo_path']['tmp_name'], $physicalFilePath)) {
            $formData['logo_path'] = $filePath;
        } else {
            $errors[] = 'Erreur lors du téléchargement du logo';
        }
    }
}

// Si des erreurs sont présentes, rediriger vers le formulaire avec les erreurs
if (!empty($errors)) {
    $_SESSION['form_errors'] = $errors;
    $_SESSION['form_data'] = $_POST;
    redirect('/tutoring/views/admin/companies/edit.php?id=' . $companyId);
}

// Mettre à jour l'entreprise dans la base de données
try {
    $sql = "UPDATE companies SET 
            name = :name,
            description = :description,
            website = :website,
            address = :address,
            city = :city,
            postal_code = :postal_code,
            country = :country,
            contact_name = :contact_name,
            contact_title = :contact_title,
            contact_email = :contact_email,
            contact_phone = :contact_phone,
            logo_path = :logo_path,
            active = :active,
            updated_at = NOW()
            WHERE id = :id";
    
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
    $stmt->bindParam(':id', $companyId);
    
    $stmt->execute();
    
    // Rediriger vers la page de détails avec un message de succès
    setFlashMessage('success', 'L\'entreprise a été mise à jour avec succès');
    redirect('/tutoring/views/admin/companies/show.php?id=' . $companyId);
    
} catch (PDOException $e) {
    // Gérer les erreurs de base de données
    $_SESSION['form_errors'] = ['Erreur de base de données : ' . $e->getMessage()];
    $_SESSION['form_data'] = $_POST;
    redirect('/tutoring/views/admin/companies/edit.php?id=' . $companyId);
}