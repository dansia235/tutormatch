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
        $whereConditions[] = "(CONCAT(u.first_name, ' ', u.last_name) LIKE :search 
                              OR u.email LIKE :search 
                              OR u.department LIKE :search
                              OR u.role LIKE :search)";
        $params[':search'] = '%' . $searchTerm . '%';
    }
    
    if (!empty($roleFilter)) {
        $whereConditions[] = "u.role = :role";
        $params[':role'] = $roleFilter;
    }
    
    if (!empty($departmentFilter)) {
        $whereConditions[] = "u.department = :department";
        $params[':department'] = $departmentFilter;
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Compter le total d'utilisateurs
    $countQuery = "
        SELECT COUNT(*) as total 
        FROM users u
        $whereClause
    ";
    $countStmt = $db->prepare($countQuery);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
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
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
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