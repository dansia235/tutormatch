<?php
/**
 * API pour mettre à jour l'ordre des préférences de stage pour un étudiant
 * Endpoint: /api/students/update-preference-order
 * Méthode: POST
 */

require_once __DIR__ . '/../utils.php';

// Vérifier que l'utilisateur est connecté
requireApiAuth();

// Vérifier que la méthode est POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonError('Méthode non autorisée', 405);
}

// Vérifier que l'utilisateur est un étudiant
if ($_SESSION['user_role'] !== 'student') {
    sendJsonError('Accès non autorisé', 403);
}

try {
    // Récupérer les données du corps de la requête
    $requestData = json_decode(file_get_contents('php://input'), true);
    
    if (!$requestData || !isset($requestData['preference_order']) || !is_array($requestData['preference_order'])) {
        sendJsonError('Données d\'ordre de préférence requises', 400);
    }
    
    $preferenceOrder = $requestData['preference_order'];
    
    // Récupérer l'ID de l'étudiant
    $studentModel = new Student($db);
    $student = $studentModel->getByUserId($_SESSION['user_id']);
    
    if (!$student) {
        sendJsonError('Profil étudiant non trouvé', 404);
    }
    
    // Début de la transaction
    $db->beginTransaction();
    $success = true;
    
    try {
        foreach ($preferenceOrder as $internshipId => $order) {
            $internshipId = (int)$internshipId;
            $order = (int)$order;
            
            if ($internshipId > 0 && $order > 0) {
                if (!method_exists($studentModel, 'updatePreferenceOrder') || 
                    !$studentModel->updatePreferenceOrder($student['id'], $internshipId, $order)) {
                    $success = false;
                    break;
                }
            }
        }
        
        if ($success) {
            $db->commit();
            sendJsonResponse([
                'success' => true,
                'message' => 'Préférences mises à jour avec succès'
            ]);
        } else {
            $db->rollBack();
            sendJsonError('Erreur lors de la mise à jour des préférences', 500);
        }
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
} catch (Exception $e) {
    sendJsonError('Erreur lors de la mise à jour des préférences: ' . $e->getMessage(), 500);
}
?>