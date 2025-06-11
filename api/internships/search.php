<?php
/**
 * Recherche de stages par terme
 * GET /api/internships/search.php?term=xxx
 */

// Inclure les fichiers nécessaires
require_once __DIR__ . '/../../includes/init.php';

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Récupérer les paramètres de requête
$term = isset($_GET['term']) ? trim($_GET['term']) : '';
$status = isset($_GET['status']) ? $_GET['status'] : 'available';

// Note: Nous permettons maintenant les recherches avec un terme vide, qui retourneront un tableau vide
// Log pour le débogage
error_log("Search API called with term: '$term', status: '$status'");

try {
    // Initialiser le modèle stage
    $internshipModel = new Internship($db);
    
    // Rechercher les stages
    $internships = $internshipModel->search($term, $status);
    
    // Transformer les données pour l'API
    $formattedInternships = [];
    foreach ($internships as $internship) {
        // Formater les dates
        $internship['start_date'] = date('Y-m-d', strtotime($internship['start_date']));
        $internship['end_date'] = date('Y-m-d', strtotime($internship['end_date']));
        
        // Formater le stage avec plus d'informations
        $formattedInternship = [
            'id' => $internship['id'],
            'title' => $internship['title'],
            'company' => [
                'id' => $internship['company_id'],
                'name' => $internship['company_name'],
                'logo_path' => $internship['company_logo'] ?? ''
            ],
            'description' => $internship['description'],
            'requirements' => $internship['requirements'],
            'domain' => $internship['domain'],
            'location' => $internship['location'],
            'work_mode' => $internship['work_mode'],
            'start_date' => $internship['start_date'],
            'end_date' => $internship['end_date'],
            'compensation' => $internship['compensation'] ?? '',
            'status' => $internship['status'],
            'skills' => $internship['skills'] ?? []
        ];
        
        $formattedInternships[] = $formattedInternship;
    }
    
    // Envoyer la réponse
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data' => $formattedInternships,
        'count' => count($formattedInternships),
        'term' => $term
    ]);
    exit;
} catch (Exception $e) {
    // Loggez l'erreur dans un environnement de production
    error_log("Error in search.php: " . $e->getMessage());
    
    // Réponse d'erreur
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur lors de la recherche: ' . $e->getMessage()
    ]);
    exit;
}