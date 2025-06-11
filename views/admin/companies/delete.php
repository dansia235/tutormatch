<?php
/**
 * Traitement de la suppression d'une entreprise
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../../includes/init.php';

// Vérifier les permissions
requireRole(['admin', 'coordinator']);

// Vérifier l'ID de l'entreprise
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    setFlashMessage('error', 'ID d\'entreprise invalide');
    redirect('/tutoring/views/admin/companies.php');
    exit;
}

$companyId = (int)$_POST['id'];

// Vérifier le jeton CSRF
if (!verifyCsrfToken($_POST['csrf_token'])) {
    setFlashMessage('error', 'Erreur de sécurité. Veuillez réessayer.');
    redirect('/tutoring/views/admin/companies.php');
    exit;
}

// Récupérer les informations sur l'entreprise
$query = "SELECT * FROM companies WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $companyId);
$stmt->execute();
$company = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$company) {
    setFlashMessage('error', 'Entreprise non trouvée');
    redirect('/tutoring/views/admin/companies.php');
    exit;
}

// Vérifier si l'entreprise a des stages associés
$queryInternships = "SELECT COUNT(*) as count FROM internships WHERE company_id = :company_id";
$stmtInternships = $db->prepare($queryInternships);
$stmtInternships->bindParam(':company_id', $companyId);
$stmtInternships->execute();
$internshipCount = $stmtInternships->fetch(PDO::FETCH_ASSOC)['count'];

// Commencer une transaction pour assurer l'intégrité des données
$db->beginTransaction();

try {
    // Si l'entreprise a des stages associés, les supprimer d'abord
    if ($internshipCount > 0) {
        // Supprimer les préférences d'étudiants liées aux stages de cette entreprise
        $queryDeletePreferences = "DELETE FROM student_preferences WHERE internship_id IN 
                                 (SELECT id FROM internships WHERE company_id = :company_id)";
        $stmtDeletePreferences = $db->prepare($queryDeletePreferences);
        $stmtDeletePreferences->bindParam(':company_id', $companyId);
        $stmtDeletePreferences->execute();
        
        // Supprimer les compétences associées aux stages
        $queryDeleteSkills = "DELETE FROM internship_skills WHERE internship_id IN 
                            (SELECT id FROM internships WHERE company_id = :company_id)";
        $stmtDeleteSkills = $db->prepare($queryDeleteSkills);
        $stmtDeleteSkills->bindParam(':company_id', $companyId);
        $stmtDeleteSkills->execute();
        
        // Supprimer les stages
        $queryDeleteInternships = "DELETE FROM internships WHERE company_id = :company_id";
        $stmtDeleteInternships = $db->prepare($queryDeleteInternships);
        $stmtDeleteInternships->bindParam(':company_id', $companyId);
        $stmtDeleteInternships->execute();
    }
    
    // Supprimer l'entreprise
    $queryDeleteCompany = "DELETE FROM companies WHERE id = :id";
    $stmtDeleteCompany = $db->prepare($queryDeleteCompany);
    $stmtDeleteCompany->bindParam(':id', $companyId);
    $result = $stmtDeleteCompany->execute();
    
    // Vérifier si la suppression a réussi
    if (!$result) {
        throw new Exception("Erreur lors de la suppression de l'entreprise");
    }
    
    // Supprimer le logo si existant
    if (!empty($company['logo_path']) && file_exists($_SERVER['DOCUMENT_ROOT'] . $company['logo_path'])) {
        unlink($_SERVER['DOCUMENT_ROOT'] . $company['logo_path']);
    }
    
    // Valider la transaction
    $db->commit();
    
    setFlashMessage('success', 'Entreprise supprimée avec succès');
    redirect('/tutoring/views/admin/companies.php');
} catch (Exception $e) {
    // Annuler la transaction en cas d'erreur
    $db->rollBack();
    
    setFlashMessage('error', $e->getMessage());
    redirect('/tutoring/views/admin/companies/show.php?id=' . $companyId);
}