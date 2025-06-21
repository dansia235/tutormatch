<?php
/**
 * API pour mettre à jour l'ordre des préférences de stage pour un étudiant
 * Endpoint: /api/students/update-preference-order
 * Méthode: POST
 */

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../utils.php';

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    sendJsonResponse([
        'success' => false,
        'message' => 'Non autorisé - Utilisateur non connecté'
    ], 401);
    exit;
}

// Vérifier que la méthode est POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse([
        'success' => false,
        'message' => 'Méthode non autorisée'
    ], 405);
    exit;
}

// Vérifier que l'utilisateur est un étudiant
if ($_SESSION['user_role'] !== 'student') {
    sendJsonResponse([
        'success' => false,
        'message' => 'Accès non autorisé'
    ], 403);
    exit;
}

try {
    // Journaliser les données reçues
    error_log("update-preference-order.php - REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
    error_log("update-preference-order.php - POST data: " . json_encode($_POST));
    
    // Récupérer les données de la requête
    $action = $_POST['action'] ?? '';
    $internshipId = isset($_POST['internship_id']) ? (int)$_POST['internship_id'] : 0;
    $currentOrder = isset($_POST['current_order']) ? (int)$_POST['current_order'] : 0;
    $fromOrder = isset($_POST['from_order']) ? (int)$_POST['from_order'] : 0;
    $toOrder = isset($_POST['to_order']) ? (int)$_POST['to_order'] : 0;
    
    error_log("update-preference-order.php - Parsed values: action=$action, internshipId=$internshipId, currentOrder=$currentOrder, fromOrder=$fromOrder, toOrder=$toOrder");
    
    // Support pour le drag and drop qui utilise fromOrder et toOrder
    if (!empty($fromOrder) && !empty($toOrder) && !empty($internshipId)) {
        // Mode drag and drop
        $action = 'drag_drop';
    }
    // Si nous utilisons l'ancienne méthode (corps JSON) et qu'on n'a pas de mode drag and drop
    else if ((empty($action) || empty($internshipId)) && empty($fromOrder) && empty($toOrder)) {
        // Récupérer les données du corps de la requête
        $requestData = json_decode(file_get_contents('php://input'), true);
        
        if (!$requestData || !isset($requestData['preference_order']) || !is_array($requestData['preference_order'])) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Données d\'ordre de préférence requises'
            ], 400);
            exit;
        }
        
        $preferenceOrder = $requestData['preference_order'];
        
        // Récupérer l'ID de l'étudiant
        $studentModel = new Student($db);
        $student = $studentModel->getByUserId($_SESSION['user_id']);
        
        if (!$student) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Profil étudiant non trouvé'
            ], 404);
            exit;
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
                sendJsonResponse([
                    'success' => false,
                    'message' => 'Erreur lors de la mise à jour des préférences'
                ], 500);
                exit;
            }
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
        return;
    }
    
    // Vérifier les paramètres requis pour les modes 'move_up' et 'move_down'
    if (($action === 'move_up' || $action === 'move_down') && (empty($internshipId) || empty($currentOrder))) {
        sendJsonResponse([
            'success' => false,
            'message' => 'Paramètres manquants pour déplacer la préférence'
        ], 400);
        exit;
    }
    
    // Récupérer l'étudiant connecté
    $studentModel = new Student($db);
    $student = $studentModel->getByUserId($_SESSION['user_id']);
    
    if (!$student) {
        sendJsonResponse([
            'success' => false,
            'message' => 'Profil étudiant non trouvé'
        ], 404);
        exit;
    }
    
    // Récupérer les préférences actuelles
    $preferences = $studentModel->getPreferences($student['id']);
    
    // Pour les modes move_up et move_down, vérifier que la préférence existe avec l'ordre actuel
    if ($action === 'move_up' || $action === 'move_down') {
        $preferenceIndex = -1;
        foreach ($preferences as $index => $pref) {
            if ($pref['internship_id'] == $internshipId && $pref['preference_order'] == $currentOrder) {
                $preferenceIndex = $index;
                break;
            }
        }
        
        if ($preferenceIndex === -1) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Préférence non trouvée pour le déplacement'
            ], 404);
            exit;
        }
    }
    
    // Déterminer le nouvel ordre
    $newOrder = $currentOrder;
    $success = false;
    
    if ($action === 'drag_drop') {
        error_log("Drag and drop mode: internship_id=$internshipId, from=$fromOrder, to=$toOrder");

        // Vérifier que l'ordre de départ et d'arrivée sont valides
        if ($fromOrder <= 0 || $toOrder <= 0) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Ordres de préférence invalides'
            ], 400);
            exit;
        }

        // Vérifier que la préférence à déplacer existe (on vérifie juste l'ID, pas l'ordre)
        $preferenceToMove = null;
        $preferenceToDropOn = null;
        
        // Journaliser toutes les préférences pour le débogage
        error_log("update-preference-order.php - All preferences: " . json_encode($preferences));
        
        foreach ($preferences as $pref) {
            // Trouver la préférence par ID
            if ($pref['internship_id'] == $internshipId) {
                $preferenceToMove = $pref;
                error_log("update-preference-order.php - Found preference to move: " . json_encode($pref));
            }
            
            // Trouver la préférence sur laquelle on dépose
            if ($pref['preference_order'] == $toOrder) {
                $preferenceToDropOn = $pref;
                error_log("update-preference-order.php - Found preference to drop on: " . json_encode($pref));
            }
        }
        
        // Vérifier qu'on a trouvé la préférence à déplacer
        if (!$preferenceToMove) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Préférence à déplacer non trouvée (ID: ' . $internshipId . ')'
            ], 404);
            exit;
        }
        
        // Remplacer l'ordre de départ par l'ordre réel de la préférence
        $fromOrder = $preferenceToMove['preference_order'];
        error_log("update-preference-order.php - Using actual fromOrder: $fromOrder");
        
        // Vérifier qu'on a trouvé une position de destination valide
        if (!$preferenceToDropOn && $toOrder > 0 && $toOrder <= count($preferences)) {
            error_log("update-preference-order.php - No preference found with order $toOrder, but order is valid");
        } else if (!$preferenceToDropOn) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Position de destination invalide (Ordre: ' . $toOrder . ')'
            ], 400);
            exit;
        }

        try {
            $db->beginTransaction();
            
            if ($fromOrder < $toOrder) {
                // Déplacer vers le bas
                // Décaler les préférences entre fromOrder+1 et toOrder
                $stmt = $db->prepare("UPDATE student_preferences 
                                     SET preference_order = preference_order - 1 
                                     WHERE student_id = :student_id 
                                     AND preference_order > :from_order 
                                     AND preference_order <= :to_order");
                $stmt->bindParam(':student_id', $student['id']);
                $stmt->bindParam(':from_order', $fromOrder);
                $stmt->bindParam(':to_order', $toOrder);
                $stmt->execute();
            } else {
                // Déplacer vers le haut
                // Décaler les préférences entre toOrder et fromOrder-1
                $stmt = $db->prepare("UPDATE student_preferences 
                                     SET preference_order = preference_order + 1 
                                     WHERE student_id = :student_id 
                                     AND preference_order >= :to_order 
                                     AND preference_order < :from_order");
                $stmt->bindParam(':student_id', $student['id']);
                $stmt->bindParam(':to_order', $toOrder);
                $stmt->bindParam(':from_order', $fromOrder);
                $stmt->execute();
            }
            
            // Mettre à jour l'ordre de la préférence déplacée
            $stmt = $db->prepare("UPDATE student_preferences 
                                 SET preference_order = :new_order 
                                 WHERE student_id = :student_id 
                                 AND internship_id = :internship_id");
            $stmt->bindParam(':student_id', $student['id']);
            $stmt->bindParam(':internship_id', $internshipId);
            $stmt->bindParam(':new_order', $toOrder);
            $stmt->execute();
            
            $db->commit();
            $success = true;
            
            error_log("Drag and drop operation successful");
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Error during drag and drop: " . $e->getMessage());
            throw $e;
        }
    }
    elseif ($action === 'move_up' && $currentOrder > 1) {
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
        sendJsonResponse([
            'success' => false,
            'message' => 'Impossible de mettre à jour l\'ordre de préférence'
        ], 400);
        exit;
    }
    
} catch (Exception $e) {
    if ($db && $db->inTransaction()) {
        $db->rollBack();
    }
    
    error_log("Erreur lors de la mise à jour de l'ordre de préférence: " . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'message' => 'Erreur lors de la mise à jour des préférences: ' . $e->getMessage()
    ], 500);
}
?>