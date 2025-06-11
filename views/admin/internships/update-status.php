<?php
/**
 * Traitement de la mise à jour du statut d'un stage
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../../includes/init.php';

// Vérifier les permissions
requireRole(['admin', 'coordinator']);

// Vérifier le jeton CSRF
if (!verifyCsrfToken($_POST['csrf_token'])) {
    setFlashMessage('error', 'Erreur de sécurité. Veuillez réessayer.');
    redirect('/tutoring/views/admin/internships.php');
    return;
}

// Vérifier les paramètres
if (!isset($_POST['id']) || !is_numeric($_POST['id']) || !isset($_POST['status'])) {
    setFlashMessage('error', 'Paramètres invalides');
    redirect('/tutoring/views/admin/internships.php');
    return;
}

// Instancier les modèles
$internshipModel = new Internship($db);
$assignmentModel = new Assignment($db);

// Récupérer le stage
$internship = $internshipModel->getById($_POST['id']);

if (!$internship) {
    setFlashMessage('error', 'Stage non trouvé');
    redirect('/tutoring/views/admin/internships.php');
    return;
}

// Valider le statut
$validStatuses = ['available', 'assigned', 'completed', 'cancelled'];
$newStatus = $_POST['status'];

if (!in_array($newStatus, $validStatuses)) {
    setFlashMessage('error', 'Statut invalide');
    redirect('/tutoring/views/admin/internships/show.php?id=' . $internship['id']);
    return;
}

// Gérer le cas où le stage est affecté et on veut le rendre disponible
if ($internship['status'] === 'assigned' && $newStatus === 'available') {
    // Vérifier s'il y a une affectation
    $assignment = $assignmentModel->getByInternshipId($internship['id']);
    
    if ($assignment) {
        // Annuler l'affectation
        $assignmentModel->update($assignment['id'], ['status' => 'rejected']);
    }
}

// Mettre à jour le statut
$success = $internshipModel->update($internship['id'], ['status' => $newStatus]);

if ($success) {
    setFlashMessage('success', 'Statut du stage mis à jour avec succès');
} else {
    setFlashMessage('error', 'Erreur lors de la mise à jour du statut');
}

redirect('/tutoring/views/admin/internships/show.php?id=' . $internship['id']);