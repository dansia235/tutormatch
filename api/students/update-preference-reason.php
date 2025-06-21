<?php
/**
 * API pour mettre à jour la raison d'une préférence de stage
 * POST /api/students/update-preference-reason.php
 */

require_once '../../includes/init.php';
require_once '../utils.php';

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

// Récupérer les paramètres
$internshipId = isset($_POST['internship_id']) ? (int)$_POST['internship_id'] : 0;
$reason = $_POST['reason'] ?? '';

// Vérifier les paramètres requis
if (empty($internshipId)) {
    sendJsonResponse([
        'success' => false,
        'message' => 'Paramètres manquants'
    ], 400);
    exit;
}

try {
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

    // Vérifier si la colonne reason existe dans la table
    try {
        $stmt = $db->prepare("SHOW COLUMNS FROM student_preferences LIKE 'reason'");
        $stmt->execute();
        $reasonColumnExists = ($stmt->rowCount() > 0);
    } catch (Exception $e) {
        error_log("Erreur lors de la vérification de la colonne 'reason': " . $e->getMessage());
        $reasonColumnExists = false;
    }

    // Si la colonne reason n'existe pas, la créer
    if (!$reasonColumnExists) {
        try {
            $db->exec("ALTER TABLE student_preferences ADD COLUMN reason TEXT");
            error_log("Colonne 'reason' ajoutée à la table student_preferences");
        } catch (Exception $e) {
            error_log("Erreur lors de l'ajout de la colonne 'reason': " . $e->getMessage());
        }
    }
    
    // Mettre à jour la raison
    $query = "UPDATE student_preferences SET reason = :reason 
              WHERE student_id = :student_id AND internship_id = :internship_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':student_id', $student['id']);
    $stmt->bindParam(':internship_id', $internshipId);
    $stmt->bindParam(':reason', $reason);
    $success = $stmt->execute();
    
    if ($success) {
        sendJsonResponse([
            'success' => true,
            'message' => 'Raison mise à jour avec succès'
        ]);
    } else {
        sendJsonResponse([
            'success' => false,
            'message' => 'Erreur lors de la mise à jour de la raison'
        ], 500);
    }
    
} catch (Exception $e) {
    error_log("Erreur lors de la mise à jour de la raison: " . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'message' => 'Erreur lors de la mise à jour de la raison: ' . $e->getMessage()
    ], 500);
}