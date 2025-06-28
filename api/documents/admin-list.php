<?php
/**
 * API pour la liste des documents (admin) avec tri et pagination
 * GET /api/documents/admin-list.php
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
    $itemsPerPage = isset($_GET['per_page']) ? max(10, min(100, (int)$_GET['per_page'])) : 10;
    $currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $offset = ($currentPage - 1) * $itemsPerPage;
    
    // Traitement des filtres
    $categoryFilter = isset($_GET['category']) ? $_GET['category'] : null;
    $searchTerm = isset($_GET['term']) ? trim($_GET['term']) : '';
    
    // Traitement du tri
    $sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
    $sortOrder = isset($_GET['order']) && strtolower($_GET['order']) === 'asc' ? 'ASC' : 'DESC';
    
    // Colonnes autorisées pour le tri
    $allowedSortColumns = [
        'title' => 'd.title',
        'type' => 'd.type',
        'file_size' => 'd.file_size',
        'created_at' => 'd.upload_date',
        'updated_at' => 'd.upload_date',
        'user_name' => 'CONCAT(u.first_name, " ", u.last_name)',
        'visibility' => 'd.visibility'
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
        $whereConditions[] = "(d.title LIKE :search 
                              OR d.description LIKE :search 
                              OR d.file_path LIKE :search
                              OR CONCAT(u.first_name, ' ', u.last_name) LIKE :search)";
        $params[':search'] = '%' . $searchTerm . '%';
    }
    
    if (!empty($categoryFilter)) {
        $whereConditions[] = "d.type = :category";
        $params[':category'] = $categoryFilter;
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Compter le total de documents
    $countQuery = "
        SELECT COUNT(*) as total 
        FROM documents d
        LEFT JOIN users u ON d.user_id = u.id
        $whereClause
    ";
    $countStmt = $db->prepare($countQuery);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $totalDocuments = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Calculer les informations de pagination
    $totalPages = ceil($totalDocuments / $itemsPerPage);
    $showingFrom = $totalDocuments > 0 ? $offset + 1 : 0;
    $showingTo = min($offset + $itemsPerPage, $totalDocuments);
    
    // Récupérer les documents avec pagination
    $query = "
        SELECT d.*, 
               u.first_name,
               u.last_name,
               u.email,
               CONCAT(u.first_name, ' ', u.last_name) as user_full_name
        FROM documents d
        LEFT JOIN users u ON d.user_id = u.id
        $whereClause
        ORDER BY $sortColumn $sortOrder, d.id DESC
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatter les données pour l'affichage
    $formattedDocuments = [];
    foreach ($documents as $document) {
        $formattedDocuments[] = [
            'id' => $document['id'],
            'title' => $document['title'],
            'description' => $document['description'],
            'type' => $document['type'],
            'file_type' => $document['file_type'],
            'file_size' => $document['file_size'],
            'file_size_formatted' => formatFileSize($document['file_size']),
            'file_path' => $document['file_path'],
            'visibility' => $document['visibility'],
            'user_id' => $document['user_id'],
            'user_name' => $document['user_full_name'],
            'user_email' => $document['email'],
            'created_at' => $document['upload_date'],
            'updated_at' => $document['upload_date'],
            'created_at_formatted' => date('d/m/Y H:i', strtotime($document['upload_date'])),
            'updated_at_formatted' => date('d/m/Y H:i', strtotime($document['upload_date']))
        ];
    }
    
    // Retourner les données
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data' => [
            'documents' => $formattedDocuments,
            'pagination' => [
                'current_page' => $currentPage,
                'total_pages' => $totalPages,
                'total_items' => $totalDocuments,
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
        'error' => 'Erreur lors de la récupération des documents: ' . $e->getMessage()
    ]);
}

/**
 * Formater la taille de fichier
 */
function formatFileSize($bytes) {
    if ($bytes == 0) return '0 B';
    
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= (1 << (10 * $pow));
    
    return round($bytes, 1) . ' ' . $units[$pow];
}
?>