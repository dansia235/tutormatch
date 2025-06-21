<?php
/**
 * Statistiques des préférences d'un étudiant
 * GET /api/students/stats.php - Récupérer les statistiques des préférences et stages
 */

require_once '../../includes/init.php';
require_once '../utils.php';

// Activer la journalisation des erreurs pour débogage
error_log("API stats.php called with method: {$_SERVER['REQUEST_METHOD']}");

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    error_log("API stats.php: Method not allowed: {$_SERVER['REQUEST_METHOD']}");
    sendJsonResponse([
        'success' => false,
        'message' => 'Méthode non autorisée',
        'stats' => []
    ], 405);
    exit;
}

// Récupérer l'ID de l'étudiant courant
$currentUserId = $_SESSION['user_id'] ?? null;
error_log("API stats.php: Current user ID: " . ($currentUserId ?: 'null'));

if (!$currentUserId) {
    error_log("API stats.php: User not authenticated");
    sendJsonResponse([
        'success' => false,
        'message' => 'Utilisateur non authentifié',
        'stats' => [
            'preferences_count' => 0,
            'available_internships' => 0,
            'profile_completion' => 0
        ]
    ], 401);
    exit;
}

try {
    // Initialiser les modèles
    $userModel = new User($db);
    $studentModel = new Student($db);
    $internshipModel = new Internship($db);

    // Récupérer l'étudiant à partir de l'ID utilisateur
    $student = $studentModel->getByUserId($currentUserId);

    if (!$student) {
        error_log("API stats.php: Student profile not found for user ID: {$currentUserId}");
        sendJsonResponse([
            'success' => false,
            'message' => 'Profil étudiant non trouvé',
            'stats' => [
                'preferences_count' => 0,
                'available_internships' => 0,
                'profile_completion' => 0
            ]
        ], 404);
        exit;
    }

    $studentId = $student['id'];
    error_log("API stats.php: Found student ID: {$studentId}");

    // Récupérer les préférences actuelles de l'étudiant
    $preferences = [];
    try {
        $preferences = $studentModel->getPreferences($studentId);
        error_log("API stats.php: Retrieved " . count($preferences) . " preferences");
    } catch (Exception $e) {
        error_log("API stats.php: Error retrieving preferences: " . $e->getMessage());
    }
    $preferencesCount = count($preferences);

    // Récupérer le nombre total de stages disponibles
    $internshipsCount = 0;
    try {
        $availableInternships = $internshipModel->getAvailableForStudent($studentId);
        $internshipsCount = count($availableInternships);
        error_log("API stats.php: Found {$internshipsCount} available internships");
    } catch (Exception $e) {
        // Si la méthode spécifique échoue, essayer la méthode générale
        error_log("API stats.php: Error getting specific internships, trying generic method: " . $e->getMessage());
        try {
            $availableInternships = $internshipModel->getAvailable();
            $internshipsCount = count($availableInternships);
            error_log("API stats.php: Found {$internshipsCount} generally available internships");
        } catch (Exception $e2) {
            error_log("API stats.php: Error retrieving internships: " . $e2->getMessage());
        }
    }

    // Calculer le taux de complétion du profil
    $profileCompletion = 0;

    // Vérifier les champs requis pour un profil complet
    if (!empty($student['first_name']) && !empty($student['last_name'])) $profileCompletion += 20;
    if (!empty($student['student_number'])) $profileCompletion += 20;
    if (!empty($student['program'])) $profileCompletion += 20;
    if (!empty($student['skills'])) $profileCompletion += 20;
    if ($preferencesCount > 0) $profileCompletion += 20;

    error_log("API stats.php: Profile completion calculated as {$profileCompletion}%");

    // Compiler les statistiques
    $stats = [
        'preferences_count' => $preferencesCount,
        'available_internships' => $internshipsCount,
        'profile_completion' => $profileCompletion
    ];

    // Envoyer la réponse
    sendJsonResponse([
        'success' => true,
        'stats' => $stats
    ]);
} catch (Exception $e) {
    error_log("API stats.php: Unexpected error: " . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'message' => 'Erreur lors de la récupération des statistiques: ' . $e->getMessage(),
        'stats' => [
            'preferences_count' => 0,
            'available_internships' => 0,
            'profile_completion' => 0
        ]
    ], 500);
}