<?php
/**
 * API pour la liste des étudiants (admin)
 * GET /api/students/admin-list
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
    $programFilter = isset($_GET['program']) ? $_GET['program'] : null;
    $levelFilter = isset($_GET['level']) ? $_GET['level'] : null;
    $activeFilter = isset($_GET['active']) ? $_GET['active'] : null;
    $searchTerm = isset($_GET['term']) ? trim($_GET['term']) : '';
    
    // Traitement du tri
    $sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'name';
    $sortOrder = isset($_GET['order']) && strtolower($_GET['order']) === 'asc' ? 'ASC' : 'DESC';
    
    // Colonnes autorisées pour le tri
    $allowedSortColumns = [
        'name' => 'CONCAT(u.first_name, " ", u.last_name)',
        'email' => 'u.email',
        'student_number' => 's.student_number',
        'program' => 's.program',
        'level' => 's.level',
        'enrollment_year' => 's.enrollment_year',
        'created_at' => 's.created_at'
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
                              OR s.student_number LIKE :search
                              OR s.program LIKE :search
                              OR s.level LIKE :search)";
        $params[':search'] = '%' . $searchTerm . '%';
    }
    
    if (!empty($programFilter)) {
        $whereConditions[] = "s.program = :program";
        $params[':program'] = $programFilter;
    }
    
    if (!empty($levelFilter)) {
        $whereConditions[] = "s.level = :level";
        $params[':level'] = $levelFilter;
    }
    
    if ($activeFilter !== null) {
        $whereConditions[] = "u.is_active = :active";
        $params[':active'] = $activeFilter === '1' ? 1 : 0;
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Compter le total d'étudiants
    $countQuery = "
        SELECT COUNT(*) as total 
        FROM students s
        LEFT JOIN users u ON s.user_id = u.id
        $whereClause
    ";
    $countStmt = $db->prepare($countQuery);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $totalStudents = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Calculer les informations de pagination
    $totalPages = ceil($totalStudents / $itemsPerPage);
    $showingFrom = $totalStudents > 0 ? $offset + 1 : 0;
    $showingTo = min($offset + $itemsPerPage, $totalStudents);
    
    // Récupérer les étudiants avec pagination
    $query = "
        SELECT s.*, u.first_name, u.last_name, u.email, u.department, u.created_at as user_created_at,
               CONCAT(u.first_name, ' ', u.last_name) as full_name,
               (SELECT COUNT(*) FROM assignments a WHERE a.student_id = s.id AND a.status IN ('confirmed', 'active')) as current_assignments_count,
               (SELECT COUNT(*) FROM evaluations e WHERE e.evaluatee_id = s.id) as evaluations_count,
               (SELECT i.title FROM assignments a 
                LEFT JOIN internships i ON a.internship_id = i.id 
                WHERE a.student_id = s.id AND a.status IN ('confirmed', 'active') 
                LIMIT 1) as current_internship_title
        FROM students s
        LEFT JOIN users u ON s.user_id = u.id
        $whereClause
        ORDER BY $sortColumn $sortOrder, s.id DESC
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Retourner les données
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data' => [
            'students' => $students,
            'pagination' => [
                'current_page' => $currentPage,
                'total_pages' => $totalPages,
                'total_items' => $totalStudents,
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
        'error' => 'Erreur lors de la récupération des étudiants: ' . $e->getMessage()
    ]);
}
?>