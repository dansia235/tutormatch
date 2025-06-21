<?php
/**
 * API pour soumettre une auto-évaluation par un étudiant
 * Endpoint: /api/evaluations/submit-self-evaluation
 * Méthode: POST
 */

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../utils.php';
require_once __DIR__ . '/document-adapter.php';

// Vérifier que l'utilisateur est connecté et est un étudiant
if (!isset($_SESSION['user_id'])) {
    sendJsonResponse([
        'error' => true,
        'message' => 'Non autorisé - Utilisateur non connecté'
    ], 401);
    exit;
}

// Vérifier que l'utilisateur est un étudiant
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'student') {
    sendJsonResponse([
        'error' => true,
        'message' => 'Accès non autorisé - Rôle étudiant requis'
    ], 403);
    exit;
}

// Vérifier que la requête est une méthode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse([
        'error' => true,
        'message' => 'Méthode non autorisée - Requête POST requise'
    ], 405);
    exit;
}

try {
    // Récupérer les données du formulaire
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        // Essayer de récupérer les données depuis $_POST
        $data = $_POST;
    }
    
    // Valider les données d'entrée
    $validation = validateApiInput($data, [
        'comments' => 'required|max:2000',
        'criteria' => 'required'
    ]);
    
    if ($validation !== true) {
        sendJsonResponse([
            'error' => true,
            'message' => 'Données invalides',
            'validation_errors' => $validation
        ], 400);
        exit;
    }
    
    // Récupérer l'ID de l'étudiant
    $studentModel = new Student($db);
    $student = $studentModel->getByUserId($_SESSION['user_id']);
    
    if (!$student) {
        sendJsonResponse([
            'error' => true,
            'message' => 'Profil étudiant non trouvé'
        ], 404);
        exit;
    }
    
    // Calculer le score moyen à partir des critères
    $totalScore = 0;
    $criteriaCount = 0;
    
    if (isset($data['criteria']) && is_array($data['criteria'])) {
        foreach ($data['criteria'] as $criterion) {
            if (isset($criterion['score']) && is_numeric($criterion['score'])) {
                $totalScore += floatval($criterion['score']);
                $criteriaCount++;
            }
        }
    }
    
    $averageScore = $criteriaCount > 0 ? round($totalScore / $criteriaCount, 1) : 0;
    
    // Préparer les données pour la création du document
    $timestamp = time();
    $documentData = [
        'title' => 'Auto-évaluation - ' . date('Y-m-d'),
        'description' => $data['comments'] ?? '',
        'type' => 'self_evaluation',
        'file_path' => 'evaluations/self_' . $student['id'] . '_' . $timestamp . '.json',
        'file_type' => 'application/json',
        'user_id' => $_SESSION['user_id'],
        'status' => 'submitted'
    ];
    
    // Générer les métadonnées d'évaluation
    $evaluationData = [
        'score' => $averageScore,
        'comments' => $data['comments'] ?? '',
        'evaluator_id' => $_SESSION['user_id'],
        'evaluator_name' => $_SESSION['user_first_name'] . ' ' . $_SESSION['user_last_name'],
        'criteria' => $data['criteria'] ?? []
    ];
    
    // Générer les métadonnées
    $metadata = generateEvaluationMetadata($evaluationData);
    
    // Calculer la taille du fichier (métadonnées JSON)
    $metadataJson = json_encode($metadata);
    $documentData['file_size'] = strlen($metadataJson);
    $documentData['metadata'] = $metadata;
    
    // Créer le document
    $documentModel = new Document($db);
    $documentId = $documentModel->create($documentData);
    
    if (!$documentId) {
        sendJsonResponse([
            'error' => true,
            'message' => 'Erreur lors de la création du document d\'auto-évaluation'
        ], 500);
        exit;
    }
    
    // Enregistrer la réussite
    sendJsonResponse([
        'success' => true,
        'message' => 'Auto-évaluation soumise avec succès',
        'evaluation_id' => $documentId,
        'average_score' => $averageScore
    ]);
    
} catch (Exception $e) {
    error_log("Erreur API auto-évaluation: " . $e->getMessage());
    sendJsonResponse([
        'error' => true,
        'message' => 'Erreur lors de la soumission de l\'auto-évaluation: ' . $e->getMessage()
    ], 500);
}
?>