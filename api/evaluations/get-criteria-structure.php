<?php
/**
 * API pour récupérer la structure standardisée des critères d'évaluation
 * Endpoint: /api/evaluations/get-criteria-structure.php
 * Méthode: GET
 */

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../utils.php';

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    sendJsonResponse([
        'error' => true,
        'message' => 'Non autorisé - Utilisateur non connecté'
    ], 401);
    exit;
}

// Vérifier que la requête est une méthode GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJsonResponse([
        'error' => true,
        'message' => 'Méthode non autorisée - Requête GET requise'
    ], 405);
    exit;
}

try {
    // Initialiser le modèle d'évaluation
    $evaluationModel = new Evaluation($db);
    
    // Récupérer la structure des critères
    $criteriaStructure = $evaluationModel->getCriteriaStructure();
    
    // Regrouper les critères par catégorie pour faciliter l'affichage
    $categorizedCriteria = [];
    
    foreach ($criteriaStructure as $category => $criteria) {
        $categorizedCriteria[$category] = [
            'name' => $category === 'technical' ? 'Compétences techniques' : 'Compétences professionnelles',
            'criteria' => $criteria
        ];
    }
    
    // Créer la structure d'affichage pour le formulaire
    $formStructure = [
        'technical' => [
            'name' => 'Compétences techniques',
            'description' => 'Évaluation des compétences techniques acquises pendant le stage',
            'criteria' => []
        ],
        'professional' => [
            'name' => 'Compétences professionnelles',
            'description' => 'Évaluation des comportements et compétences professionnelles',
            'criteria' => []
        ]
    ];
    
    // Remplir la structure d'affichage
    foreach ($criteriaStructure as $category => $criteria) {
        foreach ($criteria as $key => $criterion) {
            $formStructure[$category]['criteria'][$key] = $criterion;
        }
    }
    
    // Envoyer la réponse
    sendJsonResponse([
        'success' => true,
        'criteria_structure' => $criteriaStructure,
        'categorized_criteria' => $categorizedCriteria,
        'form_structure' => $formStructure,
        'empty_scores' => $evaluationModel->initEmptyCriteriaScores()
    ]);
    
} catch (Exception $e) {
    error_log("Erreur API critères d'évaluation: " . $e->getMessage());
    sendJsonResponse([
        'error' => true,
        'message' => 'Erreur lors de la récupération des critères d\'évaluation: ' . $e->getMessage()
    ], 500);
}
?>