<?php
/**
 * API pour la liste des tuteurs (admin)
 * GET /api/teachers/admin-list
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
    $departmentFilter = isset($_GET['department']) ? $_GET['department'] : null;
    $activeFilter = isset($_GET['active']) ? $_GET['active'] : null;
    $searchTerm = isset($_GET['term']) ? trim($_GET['term']) : '';
    
    // Traitement du tri
    $sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'name';
    $sortOrder = isset($_GET['order']) && strtolower($_GET['order']) === 'asc' ? 'ASC' : 'DESC';
    
    // Colonnes autorisées pour le tri
    $allowedSortColumns = [
        'name' => 'CONCAT(u.first_name, " ", u.last_name)',
        'email' => 'u.email',
        'department' => 'u.department',
        'specialization' => 't.specialization',
        'experience_years' => 't.experience_years',
        'max_students' => 't.max_students',
        'current_students' => 'current_students_count',
        'is_active' => 'u.is_active',
        'created_at' => 't.created_at'
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
                              OR t.specialization LIKE :search)";
        $params[':search'] = '%' . $searchTerm . '%';
    }
    
    if (!empty($departmentFilter)) {
        $whereConditions[] = "u.department = :department";
        $params[':department'] = $departmentFilter;
    }
    
    if ($activeFilter !== null) {
        $whereConditions[] = "u.is_active = :active";
        $params[':active'] = $activeFilter === '1' ? 1 : 0;
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Compter le total de tuteurs
    $countQuery = "
        SELECT COUNT(*) as total 
        FROM teachers t
        LEFT JOIN users u ON t.user_id = u.id
        $whereClause
    ";
    $countStmt = $db->prepare($countQuery);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $totalTeachers = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Calculer les informations de pagination
    $totalPages = ceil($totalTeachers / $itemsPerPage);
    $showingFrom = $totalTeachers > 0 ? $offset + 1 : 0;
    $showingTo = min($offset + $itemsPerPage, $totalTeachers);
    
    // Récupérer les tuteurs avec pagination
    $query = "
        SELECT t.*, u.first_name, u.last_name, u.email, u.department, u.phone, u.is_active, u.created_at as user_created_at,
               CONCAT(u.first_name, ' ', u.last_name) as full_name,
               (SELECT COUNT(*) FROM assignments a WHERE a.teacher_id = t.id AND a.status IN ('confirmed', 'active')) as current_students_count,
               (SELECT COUNT(*) FROM evaluations e WHERE e.evaluator_id = t.id) as evaluations_count
        FROM teachers t
        LEFT JOIN users u ON t.user_id = u.id
        $whereClause
        ORDER BY $sortColumn $sortOrder, t.id DESC
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Retourner les données
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data' => [
            'teachers' => $teachers,
            'pagination' => [
                'current_page' => $currentPage,
                'total_pages' => $totalPages,
                'total_items' => $totalTeachers,
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
        'error' => 'Erreur lors de la récupération des tuteurs: ' . $e->getMessage()
    ]);
}
?>