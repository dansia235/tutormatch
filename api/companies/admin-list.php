<?php
/**
 * API pour la liste des entreprises (admin) avec tri et pagination
 * GET /api/companies/admin-list.php
 */

require_once __DIR__ . '/../../includes/init.php';

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

// Vérifier les permissions (admin et coordinateur uniquement)
requireRole(['admin', 'coordinator']);

try {
    // Configuration de la pagination
    $itemsPerPage = isset($_GET['per_page']) ? max(10, min(100, (int)$_GET['per_page'])) : 20;
    $currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $offset = ($currentPage - 1) * $itemsPerPage;
    
    // Traitement des filtres
    $statusFilter = isset($_GET['status']) ? $_GET['status'] : null;
    $searchTerm = isset($_GET['term']) ? trim($_GET['term']) : '';
    
    // Traitement du tri
    $sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'name';
    $sortOrder = isset($_GET['order']) && strtolower($_GET['order']) === 'asc' ? 'ASC' : 'DESC';
    
    // Colonnes autorisées pour le tri
    $allowedSortColumns = [
        'name' => 'c.name',
        'city' => 'c.city',
        'contact_name' => 'c.contact_name',
        'contact_email' => 'c.contact_email',
        'created_at' => 'c.created_at',
        'internship_count' => 'internship_count'
    ];
    
    // Valider la colonne de tri
    if (!array_key_exists($sortBy, $allowedSortColumns)) {
        $sortBy = 'name';
    }
    
    $sortColumn = $allowedSortColumns[$sortBy];
    
    // Construction de la requête avec filtres
    $whereConditions = [];
    $params = [];
    
    if (!empty($searchTerm)) {
        $whereConditions[] = "(c.name LIKE ? 
                              OR c.address LIKE ? 
                              OR c.city LIKE ?
                              OR c.contact_name LIKE ?
                              OR c.contact_email LIKE ?
                              OR c.description LIKE ?)";
        $searchParam = '%' . $searchTerm . '%';
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    if (!empty($statusFilter)) {
        if ($statusFilter === 'active') {
            $whereConditions[] = "c.active = ?";
            $params[] = 1;
        } elseif ($statusFilter === 'inactive') {
            $whereConditions[] = "c.active = ?";
            $params[] = 0;
        }
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Compter le total d'entreprises
    $countQuery = "
        SELECT COUNT(*) as total 
        FROM companies c
        $whereClause
    ";
    $countStmt = $db->prepare($countQuery);
    foreach ($params as $index => $value) {
        $countStmt->bindValue($index + 1, $value);
    }
    $countStmt->execute();
    $totalCompanies = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Calculer les informations de pagination
    $totalPages = ceil($totalCompanies / $itemsPerPage);
    $showingFrom = $totalCompanies > 0 ? $offset + 1 : 0;
    $showingTo = min($offset + $itemsPerPage, $totalCompanies);
    
    // Récupérer les entreprises avec pagination
    $query = "
        SELECT c.*, 
               (SELECT COUNT(*) FROM internships WHERE company_id = c.id) as internship_count
        FROM companies c
        $whereClause
        ORDER BY $sortColumn $sortOrder, c.id DESC
        LIMIT ? OFFSET ?
    ";
    
    $stmt = $db->prepare($query);
    
    // Bind les paramètres de recherche et filtres d'abord
    $paramIndex = 1;
    foreach ($params as $value) {
        $stmt->bindValue($paramIndex, $value);
        $paramIndex++;
    }
    
    // Puis bind les paramètres de pagination
    $stmt->bindValue($paramIndex, $itemsPerPage, PDO::PARAM_INT);
    $stmt->bindValue($paramIndex + 1, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatter les données pour l'affichage
    $formattedCompanies = [];
    foreach ($companies as $company) {
        $formattedCompanies[] = [
            'id' => $company['id'],
            'name' => $company['name'],
            'address' => $company['address'],
            'city' => $company['city'],
            'country' => $company['country'],
            'website' => $company['website'],
            'contact_name' => $company['contact_name'],
            'contact_email' => $company['contact_email'],
            'contact_phone' => $company['contact_phone'],
            'description' => $company['description'],
            'active' => (bool)$company['active'],
            'internship_count' => (int)$company['internship_count'],
            'created_at' => $company['created_at'],
            'created_at_formatted' => date('d/m/Y H:i', strtotime($company['created_at']))
        ];
    }
    
    // Retourner les données
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data' => [
            'companies' => $formattedCompanies,
            'pagination' => [
                'current_page' => $currentPage,
                'total_pages' => $totalPages,
                'total_items' => $totalCompanies,
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
        'error' => 'Erreur lors de la récupération des entreprises: ' . $e->getMessage()
    ]);
}
?>