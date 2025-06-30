<?php
/**
 * API pour la liste des réunions (tuteur) avec tri et pagination
 * GET /api/meetings/tutor-list.php
 */

require_once __DIR__ . '/../../includes/init.php';

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

// Vérifier les permissions (tuteurs uniquement)
requireRole(['teacher']);

try {
    // Récupérer l'ID du tuteur
    $teacherModel = new Teacher($db);
    $teacher = $teacherModel->getByUserId($_SESSION['user_id']);
    
    if (!$teacher) {
        http_response_code(404);
        echo json_encode(['error' => 'Profil tuteur non trouvé']);
        exit;
    }
    
    // Configuration de la pagination
    $itemsPerPage = isset($_GET['per_page']) ? max(10, min(100, (int)$_GET['per_page'])) : 10;
    $currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $offset = ($currentPage - 1) * $itemsPerPage;
    
    // Traitement des filtres
    $statusFilter = isset($_GET['status']) ? $_GET['status'] : null;
    $searchTerm = isset($_GET['term']) ? trim($_GET['term']) : '';
    
    // Traitement du tri
    $sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'date_time';
    $sortOrder = isset($_GET['order']) && strtolower($_GET['order']) === 'asc' ? 'ASC' : 'DESC';
    
    // Colonnes autorisées pour le tri
    $allowedSortColumns = [
        'date_time' => 'm.date_time',
        'scheduled_date' => 'm.date_time', // Alias pour compatibilité
        'student_name' => 'CONCAT(us.first_name, " ", us.last_name)',
        'subject' => 'm.title', // title dans la DB
        'title' => 'm.title',
        'location' => 'm.location',
        'status' => 'm.status',
        'created_at' => 'm.created_at'
    ];
    
    // Valider la colonne de tri
    if (!array_key_exists($sortBy, $allowedSortColumns)) {
        $sortBy = 'date_time';
    }
    
    $sortColumn = $allowedSortColumns[$sortBy];
    
    // Construction de la requête avec filtres
    $whereConditions = ['(m.organizer_id = :teacher_user_id OR a.teacher_id = :teacher_id)'];
    $params = [
        ':teacher_user_id' => $_SESSION['user_id'],
        ':teacher_id' => $teacher['id']
    ];
    
    if (!empty($searchTerm)) {
        $whereConditions[] = "(m.title LIKE :search 
                              OR m.description LIKE :search
                              OR m.location LIKE :search 
                              OR CONCAT(us.first_name, ' ', us.last_name) LIKE :search)";
        $params[':search'] = '%' . $searchTerm . '%';
    }
    
    if (!empty($statusFilter)) {
        $whereConditions[] = "m.status = :status";
        $params[':status'] = $statusFilter;
    }
    
    $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
    
    // Compter le total de réunions
    $countQuery = "
        SELECT COUNT(DISTINCT m.id) as total 
        FROM meetings m
        LEFT JOIN assignments a ON m.assignment_id = a.id
        LEFT JOIN students s ON a.student_id = s.id
        LEFT JOIN users us ON s.user_id = us.id
        $whereClause
    ";
    $countStmt = $db->prepare($countQuery);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $totalMeetings = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Calculer les informations de pagination
    $totalPages = ceil($totalMeetings / $itemsPerPage);
    $showingFrom = $totalMeetings > 0 ? $offset + 1 : 0;
    $showingTo = min($offset + $itemsPerPage, $totalMeetings);
    
    // Récupérer les réunions avec pagination
    $query = "
        SELECT DISTINCT m.*, 
               CONCAT(us.first_name, ' ', us.last_name) as student_name,
               us.email as student_email,
               s.student_number,
               a.id as assignment_id
        FROM meetings m
        LEFT JOIN assignments a ON m.assignment_id = a.id
        LEFT JOIN students s ON a.student_id = s.id
        LEFT JOIN users us ON s.user_id = us.id
        $whereClause
        ORDER BY $sortColumn $sortOrder, m.id DESC
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $meetings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatter les données pour l'affichage
    $formattedMeetings = [];
    foreach ($meetings as $meeting) {
        $formattedMeetings[] = [
            'id' => $meeting['id'],
            'title' => $meeting['title'],
            'subject' => $meeting['title'], // Alias pour compatibilité
            'description' => $meeting['description'],
            'location' => $meeting['location'],
            'date_time' => $meeting['date_time'],
            'scheduled_date' => $meeting['date_time'], // Alias pour compatibilité
            'scheduled_date_formatted' => date('d/m/Y H:i', strtotime($meeting['date_time'])),
            'duration' => $meeting['duration'],
            'status' => $meeting['status'],
            'student_name' => $meeting['student_name'],
            'student_email' => $meeting['student_email'],
            'student_number' => $meeting['student_number'],
            'assignment_id' => $meeting['assignment_id'],
            'organizer_id' => $meeting['organizer_id'],
            'created_at' => $meeting['created_at'],
            'created_at_formatted' => date('d/m/Y H:i', strtotime($meeting['created_at']))
        ];
    }
    
    // Retourner les données
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data' => [
            'meetings' => $formattedMeetings,
            'pagination' => [
                'current_page' => $currentPage,
                'total_pages' => $totalPages,
                'total_items' => $totalMeetings,
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
        'error' => 'Erreur lors de la récupération des réunions: ' . $e->getMessage()
    ]);
}
?>