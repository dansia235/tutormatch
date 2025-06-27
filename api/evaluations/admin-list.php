<?php
/**
 * API pour la liste des évaluations (admin)
 * GET /api/evaluations/admin-list
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
    $typeFilter = isset($_GET['type']) ? $_GET['type'] : null;
    $searchTerm = isset($_GET['term']) ? trim($_GET['term']) : '';
    
    // Traitement du tri
    $sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'submission_date';
    $sortOrder = isset($_GET['order']) && strtolower($_GET['order']) === 'asc' ? 'ASC' : 'DESC';
    
    // Colonnes autorisées pour le tri
    $allowedSortColumns = [
        'evaluator_name' => 'CONCAT(evaluator.first_name, " ", evaluator.last_name)',
        'evaluatee_name' => 'CONCAT(evaluatee.first_name, " ", evaluatee.last_name)',
        'type' => 'e.type',
        'score' => 'e.score',
        'status' => 'e.status',
        'submission_date' => 'e.submission_date',
        'created_at' => 'e.created_at'
    ];
    
    // Valider la colonne de tri
    if (!array_key_exists($sortBy, $allowedSortColumns)) {
        $sortBy = 'submission_date';
    }
    
    $sortColumn = $allowedSortColumns[$sortBy];
    
    // Construction de la requête avec filtres
    $whereConditions = [];
    $params = [];
    
    if (!empty($searchTerm)) {
        $whereConditions[] = "(CONCAT(evaluator.first_name, ' ', evaluator.last_name) LIKE :search 
                              OR CONCAT(evaluatee.first_name, ' ', evaluatee.last_name) LIKE :search 
                              OR e.comments LIKE :search
                              OR e.type LIKE :search
                              OR e.status LIKE :search)";
        $params[':search'] = '%' . $searchTerm . '%';
    }
    
    if (!empty($statusFilter)) {
        $whereConditions[] = "e.status = :status";
        $params[':status'] = $statusFilter;
    }
    
    if (!empty($typeFilter)) {
        $whereConditions[] = "e.type = :type";
        $params[':type'] = $typeFilter;
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Compter le total d'évaluations
    $countQuery = "
        SELECT COUNT(*) as total 
        FROM evaluations e
        LEFT JOIN users evaluator ON e.evaluator_id = evaluator.id
        LEFT JOIN users evaluatee ON e.evaluatee_id = evaluatee.id
        $whereClause
    ";
    $countStmt = $db->prepare($countQuery);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $totalEvaluations = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Calculer les informations de pagination
    $totalPages = ceil($totalEvaluations / $itemsPerPage);
    $showingFrom = $totalEvaluations > 0 ? $offset + 1 : 0;
    $showingTo = min($offset + $itemsPerPage, $totalEvaluations);
    
    // Récupérer les évaluations avec pagination et normalisation des scores
    $query = "
        SELECT e.*, 
               CASE WHEN e.score > 5 THEN ROUND(e.score / 4, 1) ELSE e.score END as normalized_score,
               CONCAT(evaluator.first_name, ' ', evaluator.last_name) as evaluator_name,
               CONCAT(evaluatee.first_name, ' ', evaluatee.last_name) as evaluatee_name,
               evaluator.role as evaluator_role,
               evaluatee.role as evaluatee_role,
               evaluator.email as evaluator_email,
               evaluatee.email as evaluatee_email,
               a.status as assignment_status
        FROM evaluations e
        LEFT JOIN users evaluator ON e.evaluator_id = evaluator.id
        LEFT JOIN users evaluatee ON e.evaluatee_id = evaluatee.id
        LEFT JOIN assignments a ON e.assignment_id = a.id
        $whereClause
        ORDER BY $sortColumn $sortOrder, e.updated_at DESC
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $evaluations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Retourner les données
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data' => [
            'evaluations' => $evaluations,
            'pagination' => [
                'current_page' => $currentPage,
                'total_pages' => $totalPages,
                'total_items' => $totalEvaluations,
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
        'error' => 'Erreur lors de la récupération des évaluations: ' . $e->getMessage()
    ]);
}
?>