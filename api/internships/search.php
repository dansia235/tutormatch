<?php
/**
 * Recherche de stages par terme avec filtres avancés
 * GET /api/internships/search.php?term=xxx&status=available&domain=informatique&location=paris&work_mode=remote
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

// Paramètres de pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? min(50, max(1, intval($_GET['limit']))) : 10; // Par défaut 10, max 50
$offset = ($page - 1) * $limit;

// Récupérer les filtres avancés
$filters = [];

// Filtre par domaine
if (isset($_GET['domain']) && !empty($_GET['domain'])) {
    // Support pour domaines multiples (séparés par des virgules)
    if (strpos($_GET['domain'], ',') !== false) {
        $filters['domain'] = explode(',', $_GET['domain']);
    } else {
        $filters['domain'] = $_GET['domain'];
    }
}

// Filtre par localisation
if (isset($_GET['location']) && !empty($_GET['location'])) {
    $filters['location'] = $_GET['location'];
}

// Filtre par mode de travail
if (isset($_GET['work_mode']) && !empty($_GET['work_mode'])) {
    $filters['work_mode'] = $_GET['work_mode'];
}

// Filtre par compétences
if (isset($_GET['skills']) && !empty($_GET['skills'])) {
    $filters['skills'] = explode(',', $_GET['skills']);
}

// Filtre par entreprise
if (isset($_GET['company_id']) && !empty($_GET['company_id'])) {
    $filters['company_id'] = intval($_GET['company_id']);
}

// Filtre par dates
if ((isset($_GET['start_date_from']) && !empty($_GET['start_date_from'])) ||
    (isset($_GET['start_date_to']) && !empty($_GET['start_date_to']))) {
    $filters['start_date'] = [];
    
    if (isset($_GET['start_date_from']) && !empty($_GET['start_date_from'])) {
        $filters['start_date']['from'] = $_GET['start_date_from'];
    }
    
    if (isset($_GET['start_date_to']) && !empty($_GET['start_date_to'])) {
        $filters['start_date']['to'] = $_GET['start_date_to'];
    }
}

// Log pour le débogage
error_log("Search API called with term: '$term', status: '$status', page: $page, limit: $limit");
error_log("Filters: " . json_encode($filters));

try {
    // Initialiser le modèle stage
    $internshipModel = new Internship($db);
    
    // Rechercher les stages avec les filtres
    $internships = $internshipModel->search($term, $status, $filters, $limit, $offset);
    
    // Récupérer le compte total pour la pagination
    $totalCount = $internshipModel->countSearch($term, $status, $filters);
    $totalPages = ceil($totalCount / $limit);
    
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
        
        // Préparer l'URL pour le format de résultat de recherche
        $formattedInternship['url'] = "/tutoring/views/student/internship.php?id={$internship['id']}";
        
        // Ajouter un sous-titre pour l'affichage dans les résultats de recherche
        $formattedInternship['subtitle'] = "{$internship['domain']} • {$internship['location']} • {$internship['work_mode']}";
        
        // Ajouter une image si disponible
        if (!empty($internship['company_logo'])) {
            $formattedInternship['image'] = $internship['company_logo'];
        }
        
        $formattedInternships[] = $formattedInternship;
    }
    
    // Envoyer la réponse
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data' => $formattedInternships,
        'results' => $formattedInternships, // Pour compatibilité avec le contrôleur live-search
        'count' => count($formattedInternships),
        'total' => $totalCount,
        'page' => $page,
        'limit' => $limit,
        'total_pages' => $totalPages,
        'term' => $term,
        'filters' => $filters
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