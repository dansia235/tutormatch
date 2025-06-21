<?php
/**
 * API pour récupérer et mettre à jour les préférences d'un étudiant
 * GET /api/students/preferences.php?student_id=X - Récupérer les préférences
 * POST /api/students/preferences.php?student_id=X - Mettre à jour les préférences
 */

require_once '../../includes/init.php';
require_once '../utils.php';

// Activer la journalisation des erreurs pour débogage
error_log("API preferences.php called with method: {$_SERVER['REQUEST_METHOD']}");

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    error_log("API preferences.php: User not logged in");
    sendJsonResponse([
        'success' => false,
        'message' => 'Non autorisé - Utilisateur non connecté',
        'data' => []
    ], 401);
    exit;
}

// Récupérer l'ID de l'étudiant depuis la requête
$student_id = $_GET['student_id'] ?? null;
error_log("API preferences.php: Initial student_id: " . ($student_id ?: 'null'));

// Si student_id n'est pas fourni, utiliser l'utilisateur courant
if (!$student_id || $student_id === 'current') {
    try {
        $userModel = new User($db);
        $user = $userModel->getById($_SESSION['user_id']);
        
        if (!$user) {
            error_log("API preferences.php: User not found for ID: {$_SESSION['user_id']}");
            sendJsonResponse([
                'success' => false,
                'message' => 'Utilisateur non trouvé',
                'data' => []
            ], 404);
            exit;
        }
        
        // Récupérer l'ID de l'étudiant à partir de l'ID utilisateur
        $studentModel = new Student($db);
        $student = $studentModel->getByUserId($_SESSION['user_id']);
        
        if (!$student) {
            error_log("API preferences.php: Student profile not found for user ID: {$_SESSION['user_id']}");
            sendJsonResponse([
                'success' => false,
                'message' => 'Profil étudiant non trouvé',
                'data' => []
            ], 404);
            exit;
        }
        
        $student_id = $student['id'];
        error_log("API preferences.php: Resolved student_id to: {$student_id}");
    } catch (Exception $e) {
        error_log("API preferences.php: Exception getting student ID: " . $e->getMessage());
        sendJsonResponse([
            'success' => false,
            'message' => 'Erreur lors de la récupération des informations étudiant: ' . $e->getMessage(),
            'data' => []
        ], 500);
        exit;
    }
}

// Vérifier les permissions (admin, coordinateur ou l'étudiant lui-même)
$currentUserRole = $_SESSION['user_role'] ?? 'unknown';
$currentUserId = $_SESSION['user_id'];
error_log("API preferences.php: Current user role: {$currentUserRole}, User ID: {$currentUserId}");

// Si ce n'est pas un admin ou coordinateur, vérifier si c'est l'étudiant lui-même
if ($currentUserRole !== 'admin' && $currentUserRole !== 'coordinator') {
    try {
        $studentModel = new Student($db);
        $student = $studentModel->getById($student_id);
        
        if (!$student || $student['user_id'] != $currentUserId) {
            error_log("API preferences.php: Access denied - current user ID {$currentUserId} does not match student's user ID");
            sendJsonResponse([
                'success' => false,
                'message' => 'Accès non autorisé',
                'data' => []
            ], 403);
            exit;
        }
        error_log("API preferences.php: Access permitted for student ID {$student_id}");
    } catch (Exception $e) {
        error_log("API preferences.php: Exception checking permissions: " . $e->getMessage());
        sendJsonResponse([
            'success' => false,
            'message' => 'Erreur lors de la vérification des permissions: ' . $e->getMessage(),
            'data' => []
        ], 500);
        exit;
    }
}

// Traitement selon la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Récupérer les préférences
        $studentModel = new Student($db);
        $preferences = $studentModel->getPreferences($student_id);
        error_log("API preferences.php: Retrieved " . count($preferences) . " preferences for student ID {$student_id}");
        
        // Afficher les informations détaillées pour le débogage
        error_log("API preferences.php: Raw preferences data: " . json_encode($preferences));
        
        // Adapter le format des données pour le client JavaScript si nécessaire
        $formattedPreferences = [];
        
        if (is_array($preferences)) {
            foreach ($preferences as $pref) {
                // Vérifier que toutes les clés nécessaires existent
                $internship_id = $pref['internship_id'] ?? null;
                $title = $pref['title'] ?? 'Stage sans titre';
                $company_name = $pref['company_name'] ?? 'Entreprise non spécifiée';
                $preference_order = $pref['preference_order'] ?? 1;
                
                if ($internship_id) {
                    $reason = $pref['reason'] ?? null;
                    
                    $formattedPreferences[] = [
                        'internship_id' => $internship_id,
                        'title' => $title,
                        'company_name' => $company_name,
                        'preference_order' => $preference_order,
                        // Ajouter ces propriétés pour compatibilité avec le contrôleur
                        'rank' => $preference_order,
                        'reason' => $reason
                    ];
                }
            }
            
            // Log des préférences formatées
            error_log("API preferences.php: Formatted preferences: " . json_encode($formattedPreferences));
        }
        
        // Renvoyer les données
        sendJsonResponse([
            'success' => true,
            'data' => $formattedPreferences,
            'count' => count($formattedPreferences)
        ]);
    } catch (Exception $e) {
        error_log("API preferences.php: Exception getting preferences: " . $e->getMessage());
        sendJsonResponse([
            'success' => false,
            'message' => 'Erreur lors de la récupération des préférences: ' . $e->getMessage(),
            'data' => []
        ], 500);
    }
} 
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Récupérer les données JSON
        $requestBody = json_decode(file_get_contents('php://input'), true);
        error_log("API preferences.php: Received POST data: " . json_encode($requestBody));
        
        if (!$requestBody || !isset($requestBody['preferences'])) {
            error_log("API preferences.php: Missing preferences data in POST request");
            sendJsonResponse([
                'success' => false,
                'message' => 'Données de préférences manquantes',
                'data' => []
            ], 400);
            exit;
        }
        
        $preferences = $requestBody['preferences'];
        
        // Valider les préférences
        if (!is_array($preferences)) {
            error_log("API preferences.php: Invalid preferences format - not an array");
            sendJsonResponse([
                'success' => false,
                'message' => 'Format de préférences invalide',
                'data' => []
            ], 400);
            exit;
        }
        
        // Vérifier que chaque préférence a les champs requis
        foreach ($preferences as $index => $preference) {
            if (!isset($preference['internship_id']) || !isset($preference['rank'])) {
                error_log("API preferences.php: Missing required fields in preference at index {$index}");
                sendJsonResponse([
                    'success' => false,
                    'message' => 'Champs requis manquants dans la préférence ' . ($index + 1),
                    'data' => []
                ], 400);
                exit;
            }
        }
        
        // Mettre à jour les préférences
        $studentModel = new Student($db);
        $success = $studentModel->updatePreferences($student_id, $preferences);
        
        if (!$success) {
            error_log("API preferences.php: Failed to update preferences for student ID {$student_id}");
            sendJsonResponse([
                'success' => false,
                'message' => 'Échec de la mise à jour des préférences',
                'data' => []
            ], 500);
            exit;
        }
        
        // Récupérer les préférences mises à jour
        $updatedPreferences = $studentModel->getPreferences($student_id);
        error_log("API preferences.php: Successfully updated preferences, now has " . count($updatedPreferences) . " preferences");
        
        // Formater les préférences pour la réponse
        $formattedPreferences = [];
        foreach ($updatedPreferences as $pref) {
            $formattedPreferences[] = [
                'internship_id' => $pref['internship_id'],
                'title' => $pref['title'] ?? 'Stage sans titre',
                'company_name' => $pref['company_name'] ?? 'Entreprise non spécifiée',
                'preference_order' => $pref['preference_order'],
                'rank' => $pref['preference_order'],
                'reason' => $pref['reason'] ?? null
            ];
        }
        
        sendJsonResponse([
            'success' => true,
            'message' => 'Préférences mises à jour avec succès',
            'data' => $formattedPreferences,
            'count' => count($formattedPreferences)
        ]);
    } catch (Exception $e) {
        error_log("API preferences.php: Exception during POST processing: " . $e->getMessage());
        sendJsonResponse([
            'success' => false,
            'message' => 'Erreur lors de la mise à jour des préférences: ' . $e->getMessage(),
            'data' => []
        ], 500);
    }
} 
else {
    error_log("API preferences.php: Method not allowed: {$_SERVER['REQUEST_METHOD']}");
    sendJsonResponse([
        'success' => false,
        'message' => 'Méthode non autorisée',
        'data' => []
    ], 405);
}