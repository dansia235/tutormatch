<?php
/**
 * Traitement du changement de statut d'une affectation
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../../includes/init.php';

// Vérifier les permissions
requireRole(['admin', 'coordinator']);

// Vérifier les données POST
if (!isset($_POST['id']) || !is_numeric($_POST['id']) || !isset($_POST['status'])) {
    setFlashMessage('error', 'Données invalides pour le changement de statut');
    redirect('/tutoring/views/admin/assignments/index.php');
}

// Vérifier le jeton CSRF
if (!verifyCsrfToken($_POST['csrf_token'])) {
    setFlashMessage('error', 'Erreur de sécurité. Veuillez réessayer.');
    redirect('/tutoring/views/admin/assignments/show.php?id=' . $_POST['id']);
    exit;
}

// Instancier le contrôleur
$assignmentController = new AssignmentController($db);

// Récupérer l'affectation
$assignment = new Assignment($db);
$currentAssignment = $assignment->getById($_POST['id']);

if (!$currentAssignment) {
    setFlashMessage('error', 'Affectation non trouvée');
    redirect('/tutoring/views/admin/assignments/index.php');
    exit;
}

// Préparer les données pour la mise à jour
$data = [
    'status' => $_POST['status'],
    'notes' => isset($_POST['notes']) ? $_POST['notes'] : $currentAssignment['notes']
];

// Si le statut est confirmé ou complété, ajouter la date de confirmation
if ($_POST['status'] === 'confirmed' || $_POST['status'] === 'completed') {
    $data['confirmation_date'] = date('Y-m-d H:i:s');
}

// Si le statut est rejeté, mettre à jour le statut du stage
if ($_POST['status'] === 'rejected') {
    // Commencer une transaction
    $db->beginTransaction();
    
    try {
        // Mettre à jour l'affectation
        $success = $assignment->update($_POST['id'], $data);
        
        if (!$success) {
            throw new Exception("Erreur lors de la mise à jour de l'affectation");
        }
        
        // Libérer le stage
        $internship = new Internship($db);
        $internship->update($currentAssignment['internship_id'], ['status' => 'available']);
        
        // Valider la transaction
        $db->commit();
        
        setFlashMessage('success', 'Statut de l\'affectation mis à jour avec succès');
    } catch (Exception $e) {
        // Annuler la transaction en cas d'erreur
        $db->rollBack();
        
        setFlashMessage('error', $e->getMessage());
    }
} else {
    // Mettre à jour simplement le statut
    $success = $assignment->update($_POST['id'], $data);
    
    if ($success) {
        setFlashMessage('success', 'Statut de l\'affectation mis à jour avec succès');
    } else {
        setFlashMessage('error', 'Erreur lors de la mise à jour du statut');
    }
}

// Rediriger vers la page de détails
redirect('/tutoring/views/admin/assignments/show.php?id=' . $_POST['id']);