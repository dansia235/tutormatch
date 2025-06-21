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
    // Récupérer les données de la requête
    $action = $_POST['action'] ?? '';
    $internshipId = isset($_POST['internship_id']) ? (int)$_POST['internship_id'] : 0;
    $currentOrder = isset($_POST['current_order']) ? (int)$_POST['current_order'] : 0;
    
    // Si nous utilisons l'ancienne méthode (corps JSON)
    if (empty($action) || empty($internshipId)) {
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
        return;
    }
    
    // Vérifier les paramètres requis pour la nouvelle méthode
    if (empty($action) || empty($internshipId) || empty($currentOrder)) {
        sendJsonError('Paramètres manquants', 400);
    }
    
    // Récupérer l'étudiant connecté
    $studentModel = new Student($db);
    $student = $studentModel->getByUserId($_SESSION['user_id']);
    
    if (!$student) {
        sendJsonError('Profil étudiant non trouvé', 404);
    }
    
    // Récupérer les préférences actuelles
    $preferences = $studentModel->getPreferences($student['id']);
    
    // Vérifier que la préférence existe
    $preferenceIndex = -1;
    foreach ($preferences as $index => $pref) {
        if ($pref['internship_id'] == $internshipId && $pref['preference_order'] == $currentOrder) {
            $preferenceIndex = $index;
            break;
        }
    }
    
    if ($preferenceIndex === -1) {
        sendJsonError('Préférence non trouvée', 404);
    }
    
    // Déterminer le nouvel ordre
    $newOrder = $currentOrder;
    $success = false;
    
    if ($action === 'move_up' && $currentOrder > 1) {
        // Trouver la préférence avec l'ordre précédent
        $previousPreference = null;
        foreach ($preferences as $pref) {
            if ($pref['preference_order'] == $currentOrder - 1) {
                $previousPreference = $pref;
                break;
            }
        }
        
        if ($previousPreference) {
            // Échanger les ordres
            $db->beginTransaction();
            
            // Déplacer temporairement la préférence précédente à un ordre spécial
            $stmt = $db->prepare("UPDATE student_preferences SET preference_order = -1 
                                 WHERE student_id = :student_id AND internship_id = :internship_id");
            $stmt->bindParam(':student_id', $student['id']);
            $stmt->bindParam(':internship_id', $previousPreference['internship_id']);
            $stmt->execute();
            
            // Déplacer la préférence actuelle vers l'ordre précédent
            $stmt = $db->prepare("UPDATE student_preferences SET preference_order = :new_order 
                                 WHERE student_id = :student_id AND internship_id = :internship_id");
            $stmt->bindParam(':student_id', $student['id']);
            $stmt->bindParam(':internship_id', $internshipId);
            $stmt->bindParam(':new_order', $previousPreference['preference_order']);
            $stmt->execute();
            
            // Déplacer la préférence précédente vers l'ordre actuel
            $stmt = $db->prepare("UPDATE student_preferences SET preference_order = :new_order 
                                 WHERE student_id = :student_id AND internship_id = :internship_id AND preference_order = -1");
            $stmt->bindParam(':student_id', $student['id']);
            $stmt->bindParam(':internship_id', $previousPreference['internship_id']);
            $stmt->bindParam(':new_order', $currentOrder);
            $stmt->execute();
            
            $db->commit();
            $success = true;
        }
    } 
    elseif ($action === 'move_down') {
        // Trouver la préférence avec l'ordre suivant
        $nextPreference = null;
        foreach ($preferences as $pref) {
            if ($pref['preference_order'] == $currentOrder + 1) {
                $nextPreference = $pref;
                break;
            }
        }
        
        if ($nextPreference) {
            // Échanger les ordres
            $db->beginTransaction();
            
            // Déplacer temporairement la préférence suivante à un ordre spécial
            $stmt = $db->prepare("UPDATE student_preferences SET preference_order = -1 
                                 WHERE student_id = :student_id AND internship_id = :internship_id");
            $stmt->bindParam(':student_id', $student['id']);
            $stmt->bindParam(':internship_id', $nextPreference['internship_id']);
            $stmt->execute();
            
            // Déplacer la préférence actuelle vers l'ordre suivant
            $stmt = $db->prepare("UPDATE student_preferences SET preference_order = :new_order 
                                 WHERE student_id = :student_id AND internship_id = :internship_id");
            $stmt->bindParam(':student_id', $student['id']);
            $stmt->bindParam(':internship_id', $internshipId);
            $stmt->bindParam(':new_order', $nextPreference['preference_order']);
            $stmt->execute();
            
            // Déplacer la préférence suivante vers l'ordre actuel
            $stmt = $db->prepare("UPDATE student_preferences SET preference_order = :new_order 
                                 WHERE student_id = :student_id AND internship_id = :internship_id AND preference_order = -1");
            $stmt->bindParam(':student_id', $student['id']);
            $stmt->bindParam(':internship_id', $nextPreference['internship_id']);
            $stmt->bindParam(':new_order', $currentOrder);
            $stmt->execute();
            
            $db->commit();
            $success = true;
        }
    }
    
    if ($success) {
        sendJsonResponse([
            'success' => true,
            'message' => 'Ordre de préférence mis à jour avec succès'
        ]);
    } else {
        sendJsonError('Impossible de mettre à jour l\'ordre de préférence', 400);
    }
    
} catch (Exception $e) {
    if ($db && $db->inTransaction()) {
        $db->rollBack();
    }
    
    error_log("Erreur lors de la mise à jour de l'ordre de préférence: " . $e->getMessage());
    sendJsonError('Erreur lors de la mise à jour des préférences: ' . $e->getMessage(), 500);
}
?>