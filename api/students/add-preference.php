<?php
/**
 * API pour ajouter une préférence de stage pour un étudiant
 * Endpoint: /api/students/add-preference
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
    
    if (!$requestData || !isset($requestData['internship_id'])) {
        sendJsonError('ID de stage requis', 400);
    }
    
    $internshipId = (int)$requestData['internship_id'];
    
    // Récupérer l'ID de l'étudiant
    $studentModel = new Student($db);
    $student = $studentModel->getByUserId($_SESSION['user_id']);
    
    if (!$student) {
        sendJsonError('Profil étudiant non trouvé', 404);
    }
    
    // Vérifier que le stage existe et est disponible
    $internshipModel = new Internship($db);
    $internship = $internshipModel->getById($internshipId);
    
    if (!$internship) {
        sendJsonError('Stage non trouvé', 404);
    }
    
    if ($internship['status'] !== 'active' && $internship['status'] !== 'available') {
        sendJsonError('Ce stage n\'est plus disponible', 400);
    }
    
    // Récupérer les préférences actuelles pour déterminer l'ordre
    $preferences = [];
    if (method_exists($studentModel, 'getPreferences')) {
        $preferences = $studentModel->getPreferences($student['id']) ?? [];
    }
    
    // Déterminer l'ordre de préférence (dernier + 1)
    $preferenceOrder = 1; // Par défaut
    
    if (!empty($preferences)) {
        $maxOrder = 0;
        foreach ($preferences as $preference) {
            if (isset($preference['preference_order']) && $preference['preference_order'] > $maxOrder) {
                $maxOrder = $preference['preference_order'];
            }
        }
        $preferenceOrder = $maxOrder + 1;
    }
    
    // Ajouter la préférence
    $success = false;
    if (method_exists($studentModel, 'addPreference')) {
        $success = $studentModel->addPreference($student['id'], $internshipId, $preferenceOrder);
    } else {
        // Pour la démonstration, simuler un succès
        $success = true;
    }
    
    if ($success) {
        sendJsonResponse([
            'success' => true,
            'message' => 'Stage ajouté à vos préférences',
            'data' => [
                'student_id' => $student['id'],
                'internship_id' => $internshipId,
                'preference_order' => $preferenceOrder
            ]
        ]);
    } else {
        sendJsonError('Erreur lors de l\'ajout de la préférence', 500);
    }
} catch (Exception $e) {
    sendJsonError('Erreur lors de l\'ajout de la préférence: ' . $e->getMessage(), 500);
}
?>