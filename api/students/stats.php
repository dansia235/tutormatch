<?php
/**
 * Statistiques des préférences d'un étudiant
 * GET /api/students/stats.php - Récupérer les statistiques des préférences et stages
 */

require_once '../../includes/init.php';
require_once '../utils.php';

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Méthode non autorisée', 405);
}

// Récupérer l'ID de l'étudiant courant
$currentUserId = $_SESSION['user_id'] ?? null;

if (!$currentUserId) {
    sendError('Utilisateur non authentifié', 401);
}

// Initialiser les modèles
$userModel = new User($db);
$studentModel = new Student($db);
$internshipModel = new Internship($db);

// Récupérer l'étudiant à partir de l'ID utilisateur
$student = $studentModel->getByUserId($currentUserId);

if (!$student) {
    sendError('Profil étudiant non trouvé', 404);
}

$studentId = $student['id'];

// Récupérer les préférences actuelles de l'étudiant
$preferences = $studentModel->getPreferences($studentId);
$preferencesCount = count($preferences);

// Récupérer le nombre total de stages disponibles
$availableInternships = $internshipModel->getAvailableForStudent($studentId);
$internshipsCount = count($availableInternships);

// Calculer le taux de complétion du profil
$profileCompletion = 0;

// Vérifier les champs requis pour un profil complet
if (!empty($student['first_name']) && !empty($student['last_name'])) $profileCompletion += 20;
if (!empty($student['student_number'])) $profileCompletion += 20;
if (!empty($student['program'])) $profileCompletion += 20;
if (!empty($student['skills'])) $profileCompletion += 20;
if ($preferencesCount > 0) $profileCompletion += 20;

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