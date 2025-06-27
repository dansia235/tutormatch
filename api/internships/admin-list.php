<?php
/**
 * API pour la liste des stages (admin)
 * GET /api/internships/admin-list
 */

require_once __DIR__ . '/../../includes/init.php';

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

// Vérifier les permissions
requireRole(['admin', 'coordinator']);

try {
    // Configuration de la pagination
    $itemsPerPage = isset($_GET['per_page']) ? max(10, min(100, (int)$_GET['per_page'])) : 10;
    $currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $offset = ($currentPage - 1) * $itemsPerPage;
    
    // Traitement des filtres
    $statusFilter = isset($_GET['status']) ? $_GET['status'] : null;
    $domainFilter = isset($_GET['domain']) ? $_GET['domain'] : null;
    $searchTerm = isset($_GET['term']) ? trim($_GET['term']) : '';
    
    // Traitement du tri
    $sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
    $sortOrder = isset($_GET['order']) && strtolower($_GET['order']) === 'asc' ? 'ASC' : 'DESC';
    
    // Colonnes autorisées pour le tri
    $allowedSortColumns = [
        'title' => 'i.title',
        'company_name' => 'c.name',
        'location' => 'i.location',
        'domain' => 'i.domain',
        'status' => 'i.status',
        'start_date' => 'i.start_date',
        'end_date' => 'i.end_date',
        'duration_weeks' => 'i.duration_weeks',
        'compensation' => 'i.compensation',
        'created_at' => 'i.created_at'
    ];
    
    // Valider la colonne de tri
    if (!array_key_exists($sortBy, $allowedSortColumns)) {
        $sortBy = 'created_at';
    }
    
    $sortColumn = $allowedSortColumns[$sortBy];
    
    // Construction de la requête avec filtres
    $whereConditions = [];
    $params = [];
    
    if (!empty($searchTerm)) {
        $whereConditions[] = "(i.title LIKE :search 
                              OR i.description LIKE :search 
                              OR i.location LIKE :search
                              OR i.domain LIKE :search
                              OR c.name LIKE :search)";
        $params[':search'] = '%' . $searchTerm . '%';
    }
    
    if (!empty($statusFilter)) {
        $whereConditions[] = "i.status = :status";
        $params[':status'] = $statusFilter;
    }
    
    if (!empty($domainFilter)) {
        $whereConditions[] = "i.domain = :domain";
        $params[':domain'] = $domainFilter;
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Compter le total de stages
    $countQuery = "
        SELECT COUNT(*) as total 
        FROM internships i
        LEFT JOIN companies c ON i.company_id = c.id
        $whereClause
    ";
    $countStmt = $db->prepare($countQuery);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $totalInternships = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Calculer les informations de pagination
    $totalPages = ceil($totalInternships / $itemsPerPage);
    $showingFrom = $totalInternships > 0 ? $offset + 1 : 0;
    $showingTo = min($offset + $itemsPerPage, $totalInternships);
    
    // Récupérer les stages avec pagination
    $query = "
        SELECT i.*, 
               c.name as company_name,
               c.sector as company_sector,
               c.size as company_size,
               (SELECT COUNT(*) FROM assignments a WHERE a.internship_id = i.id AND a.status != 'cancelled') as assignments_count
        FROM internships i
        LEFT JOIN companies c ON i.company_id = c.id
        $whereClause
        ORDER BY $sortColumn $sortOrder, i.id DESC
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $internships = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Retourner les données
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data' => [
            'internships' => $internships,
            'pagination' => [
                'current_page' => $currentPage,
                'total_pages' => $totalPages,
                'total_items' => $totalInternships,
                'items_per_page' => $itemsPerPage,
                'showing_from' => $showingFrom,
                'showing_to' => $showingTo
            ]
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de la récupération des stages: ' . $e->getMessage()
    ]);
}
?>