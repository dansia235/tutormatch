<?php
/**
 * API pour mettre à jour la raison d'une préférence de stage pour un étudiant
 * Endpoint: /api/students/update-preference-reason
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
    $internshipId = isset($_POST['internship_id']) ? (int)$_POST['internship_id'] : 0;
    $reason = $_POST['reason'] ?? '';
    
    // Vérifier les paramètres requis
    if (empty($internshipId)) {
        sendJsonError('ID de stage requis', 400);
    }
    
    // Récupérer l'étudiant connecté
    $studentModel = new Student($db);
    $student = $studentModel->getByUserId($_SESSION['user_id']);
    
    if (!$student) {
        sendJsonError('Profil étudiant non trouvé', 404);
    }
    
    // Vérifier que la préférence existe
    $preferences = $studentModel->getPreferences($student['id']);
    $preferenceExists = false;
    
    foreach ($preferences as $pref) {
        if ($pref['internship_id'] == $internshipId) {
            $preferenceExists = true;
            break;
        }
    }
    
    if (!$preferenceExists) {
        sendJsonError('Préférence non trouvée', 404);
    }
    
    // Mettre à jour la raison
    $success = false;
    
    try {
        $query = "UPDATE student_preferences SET reason = :reason 
                 WHERE student_id = :student_id AND internship_id = :internship_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':student_id', $student['id']);
        $stmt->bindParam(':internship_id', $internshipId);
        $stmt->bindParam(':reason', $reason);
        $success = $stmt->execute();
    } catch (Exception $e) {
        error_log("Erreur lors de la mise à jour de la raison: " . $e->getMessage());
        $success = false;
    }
    
    if ($success) {
        sendJsonResponse([
            'success' => true,
            'message' => 'Raison mise à jour avec succès'
        ]);
    } else {
        sendJsonError('Erreur lors de la mise à jour de la raison', 500);
    }
    
} catch (Exception $e) {
    error_log("Erreur lors de la mise à jour de la raison: " . $e->getMessage());
    sendJsonError('Erreur lors de la mise à jour de la raison: ' . $e->getMessage(), 500);
}
?>