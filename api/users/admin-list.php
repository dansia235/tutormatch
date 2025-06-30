<?php
/**
 * API pour la liste des utilisateurs (admin)
 * GET /api/users/admin-list
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
    $roleFilter = isset($_GET['role']) ? $_GET['role'] : null;
    $departmentFilter = isset($_GET['department']) ? $_GET['department'] : null;
    $searchTerm = isset($_GET['term']) ? trim($_GET['term']) : '';
    
    // Traitement du tri
    $sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'name';
    $sortOrder = isset($_GET['order']) && strtolower($_GET['order']) === 'asc' ? 'ASC' : 'DESC';
    
    // Colonnes autorisées pour le tri
    $allowedSortColumns = [
        'name' => 'CONCAT(u.first_name, " ", u.last_name)',
        'email' => 'u.email',
        'role' => 'u.role',
        'department' => 'u.department',
        'created_at' => 'u.created_at'
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
        $whereConditions[] = "(CONCAT(u.first_name, ' ', u.last_name) LIKE ? 
                              OR u.email LIKE ? 
                              OR u.department LIKE ?
                              OR u.role LIKE ?)";
        $searchParam = '%' . $searchTerm . '%';
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    if (!empty($roleFilter)) {
        $whereConditions[] = "u.role = ?";
        $params[] = $roleFilter;
    }
    
    if (!empty($departmentFilter)) {
        $whereConditions[] = "u.department = ?";
        $params[] = $departmentFilter;
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Compter le total d'utilisateurs
    $countQuery = "
        SELECT COUNT(*) as total 
        FROM users u
        $whereClause
    ";
    $countStmt = $db->prepare($countQuery);
    foreach ($params as $index => $value) {
        $countStmt->bindValue($index + 1, $value);
    }
    $countStmt->execute();
    $totalUsers = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Calculer les informations de pagination
    $totalPages = ceil($totalUsers / $itemsPerPage);
    $showingFrom = $totalUsers > 0 ? $offset + 1 : 0;
    $showingTo = min($offset + $itemsPerPage, $totalUsers);
    
    // Récupérer les utilisateurs avec pagination
    $query = "
        SELECT u.*, 
               CONCAT(u.first_name, ' ', u.last_name) as full_name,
               (SELECT COUNT(*) FROM students s WHERE s.user_id = u.id) as is_student,
               (SELECT COUNT(*) FROM teachers t WHERE t.user_id = u.id) as is_teacher,
               1 as is_active
        FROM users u
        $whereClause
        ORDER BY $sortColumn $sortOrder, u.id DESC
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
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Retourner les données
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data' => [
            'users' => $users,
            'pagination' => [
                'current_page' => $currentPage,
                'total_pages' => $totalPages,
                'total_items' => $totalUsers,
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
        'error' => 'Erreur lors de la récupération des utilisateurs: ' . $e->getMessage()
    ]);
}
?>