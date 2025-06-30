<?php
/**
 * API pour la liste des documents (étudiant) avec tri et pagination
 * GET /api/documents/student-list.php
 */

require_once __DIR__ . '/../../includes/init.php';

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

// Vérifier les permissions (étudiants uniquement)
requireRole(['student']);

try {
    // Récupérer l'ID de l'étudiant
    $studentModel = new Student($db);
    $student = $studentModel->getByUserId($_SESSION['user_id']);
    
    if (!$student) {
        http_response_code(404);
        echo json_encode(['error' => 'Profil étudiant non trouvé']);
        exit;
    }
    
    // Configuration de la pagination
    $itemsPerPage = isset($_GET['per_page']) ? max(10, min(100, (int)$_GET['per_page'])) : 10;
    $currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $offset = ($currentPage - 1) * $itemsPerPage;
    
    // Traitement des filtres
    $categoryFilter = isset($_GET['category']) ? $_GET['category'] : null;
    $statusFilter = isset($_GET['status']) ? $_GET['status'] : null;
    $searchTerm = isset($_GET['term']) ? trim($_GET['term']) : '';
    
    // Traitement du tri
    $sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'upload_date';
    $sortOrder = isset($_GET['order']) && strtolower($_GET['order']) === 'asc' ? 'ASC' : 'DESC';
    
    // Colonnes autorisées pour le tri
    $allowedSortColumns = [
        'title' => 'd.title',
        'type' => 'd.type',
        'file_size' => 'd.file_size',
        'upload_date' => 'd.upload_date',
        'created_at' => 'd.upload_date',
        'status' => 'd.status'
    ];
    
    // Valider la colonne de tri
    if (!array_key_exists($sortBy, $allowedSortColumns)) {
        $sortBy = 'upload_date';
    }
    
    $sortColumn = $allowedSortColumns[$sortBy];
    
    // Construction de la requête avec filtres
    $whereConditions = ['d.user_id = ?'];
    $params = [$_SESSION['user_id']];
    
    if (!empty($searchTerm)) {
        $whereConditions[] = "(d.title LIKE ? 
                              OR d.description LIKE ? 
                              OR d.file_path LIKE ?)";
        $searchParam = '%' . $searchTerm . '%';
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
    }
    
    if (!empty($categoryFilter)) {
        $whereConditions[] = "d.type = ?";
        $params[] = $categoryFilter;
    }
    
    if (!empty($statusFilter)) {
        $whereConditions[] = "d.status = ?";
        $params[] = $statusFilter;
    }
    
    $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
    
    // Compter le total de documents
    $countQuery = "
        SELECT COUNT(*) as total 
        FROM documents d
        $whereClause
    ";
    $countStmt = $db->prepare($countQuery);
    $countStmt->execute($params);
    $totalDocuments = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Calculer les informations de pagination
    $totalPages = ceil($totalDocuments / $itemsPerPage);
    $showingFrom = $totalDocuments > 0 ? $offset + 1 : 0;
    $showingTo = min($offset + $itemsPerPage, $totalDocuments);
    
    // Récupérer les documents avec pagination
    $query = "
        SELECT d.*
        FROM documents d
        $whereClause
        ORDER BY $sortColumn $sortOrder, d.id DESC
        LIMIT ? OFFSET ?
    ";
    
    $stmt = $db->prepare($query);
    $allParams = array_merge($params, [$itemsPerPage, $offset]);
    $stmt->execute($allParams);
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
            'status' => $document['status'],
            'visibility' => $document['visibility'],
            'user_id' => $document['user_id'],
            'upload_date' => $document['upload_date'],
            'created_at' => $document['upload_date'],
            'upload_date_formatted' => date('d/m/Y H:i', strtotime($document['upload_date'])),
            'created_at_formatted' => date('d/m/Y H:i', strtotime($document['upload_date']))
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