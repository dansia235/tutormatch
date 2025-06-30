<?php
/**
 * API pour la liste des affectations (admin)
 * GET /api/assignments/admin-list
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
    $departmentFilter = isset($_GET['department']) ? $_GET['department'] : null;
    $searchTerm = isset($_GET['term']) ? trim($_GET['term']) : '';
    
    // Traitement du tri
    $sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'assignment_date';
    $sortOrder = isset($_GET['order']) && strtolower($_GET['order']) === 'asc' ? 'ASC' : 'DESC';
    
    // Colonnes autorisées pour le tri
    $allowedSortColumns = [
        'student_name' => 'CONCAT(su.first_name, " ", su.last_name)',
        'teacher_name' => 'CONCAT(tu.first_name, " ", tu.last_name)',
        'internship_title' => 'i.title',
        'status' => 'a.status',
        'assignment_date' => 'a.assignment_date',
        'start_date' => 'a.start_date',
        'end_date' => 'a.end_date'
    ];
    
    // Valider la colonne de tri
    if (!array_key_exists($sortBy, $allowedSortColumns)) {
        $sortBy = 'assignment_date';
    }
    
    $sortColumn = $allowedSortColumns[$sortBy];
    
    // Construction de la requête avec filtres
    $whereConditions = [];
    $params = [];
    
    if (!empty($searchTerm)) {
        $whereConditions[] = "(CONCAT(su.first_name, ' ', su.last_name) LIKE ? 
                              OR CONCAT(tu.first_name, ' ', tu.last_name) LIKE ? 
                              OR i.title LIKE ?
                              OR c.name LIKE ?
                              OR a.status LIKE ?)";
        $searchParam = '%' . $searchTerm . '%';
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    if (!empty($statusFilter)) {
        $whereConditions[] = "a.status = ?";
        $params[] = $statusFilter;
    }
    
    if (!empty($departmentFilter)) {
        $whereConditions[] = "su.department = ?";
        $params[] = $departmentFilter;
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Compter le total d'affectations
    $countQuery = "
        SELECT COUNT(*) as total 
        FROM assignments a
        LEFT JOIN students s ON a.student_id = s.id
        LEFT JOIN users su ON s.user_id = su.id
        LEFT JOIN teachers t ON a.teacher_id = t.id
        LEFT JOIN users tu ON t.user_id = tu.id
        LEFT JOIN internships i ON a.internship_id = i.id
        LEFT JOIN companies c ON i.company_id = c.id
        $whereClause
    ";
    $countStmt = $db->prepare($countQuery);
    foreach ($params as $index => $value) {
        $countStmt->bindValue($index + 1, $value);
    }
    $countStmt->execute();
    $totalAssignments = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Calculer les informations de pagination
    $totalPages = ceil($totalAssignments / $itemsPerPage);
    $showingFrom = $totalAssignments > 0 ? $offset + 1 : 0;
    $showingTo = min($offset + $itemsPerPage, $totalAssignments);
    
    // Récupérer les affectations avec pagination
    $query = "
        SELECT a.*, 
               CONCAT(su.first_name, ' ', su.last_name) as student_name,
               CONCAT(tu.first_name, ' ', tu.last_name) as teacher_name,
               su.email as student_email,
               tu.email as teacher_email,
               su.department as student_department,
               s.program as student_program,
               s.level as student_level,
               i.title as internship_title,
               c.name as company_name,
               i.location as internship_location
        FROM assignments a
        LEFT JOIN students s ON a.student_id = s.id
        LEFT JOIN users su ON s.user_id = su.id
        LEFT JOIN teachers t ON a.teacher_id = t.id
        LEFT JOIN users tu ON t.user_id = tu.id
        LEFT JOIN internships i ON a.internship_id = i.id
        LEFT JOIN companies c ON i.company_id = c.id
        $whereClause
        ORDER BY $sortColumn $sortOrder, a.id DESC
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
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Retourner les données
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data' => [
            'assignments' => $assignments,
            'pagination' => [
                'current_page' => $currentPage,
                'total_pages' => $totalPages,
                'total_items' => $totalAssignments,
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
        'error' => 'Erreur lors de la récupération des affectations: ' . $e->getMessage()
    ]);
}
?>