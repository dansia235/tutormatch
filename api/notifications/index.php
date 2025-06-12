<?php
/**
 * Liste des notifications
 * GET /api/notifications
 */

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Méthode non autorisée', 405);
}

// Récupérer les paramètres de requête
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$type = isset($_GET['type']) ? $_GET['type'] : null;

// Valider les paramètres
if ($page < 1) $page = 1;
if ($limit < 1 || $limit > 100) $limit = 20;

// Initialiser le modèle de notifications
$notificationModel = new Notification($db);

// Récupérer l'utilisateur actuel
$currentUserId = $_SESSION['user_id'];

// Construire les options de requête
$options = [
    'page' => $page,
    'limit' => $limit,
    'user_id' => $currentUserId
];

// Filtrer par type si spécifié
if ($type) {
    $options['type'] = $type;
}

// Récupérer les notifications de l'utilisateur
$notifications = $notificationModel->getAll($options);
$total = $notificationModel->countAll($options);

// Calculer la pagination
$totalPages = ceil($total / $limit);

// Enrichir les données avec des informations additionnelles
$enrichedNotifications = [];
foreach ($notifications as $notification) {
    $enrichedNotification = $notification;
    
    // Déterminer si la notification est relative à un élément cliquable
    if ($notification['related_type'] && $notification['related_id']) {
        $relatedType = $notification['related_type'];
        $relatedId = $notification['related_id'];
        
        // Construire l'URL de l'élément lié
        $url = null;
        switch ($relatedType) {
            case 'assignment':
                $url = "/tutoring/views/admin/assignments/show.php?id=$relatedId";
                break;
            case 'internship':
                $url = "/tutoring/views/admin/internships/show.php?id=$relatedId";
                break;
            case 'document':
                $url = "/tutoring/views/admin/documents/show.php?id=$relatedId";
                break;
            case 'meeting':
                $url = "/tutoring/views/admin/meetings/show.php?id=$relatedId";
                break;
            case 'message':
                $url = "/tutoring/views/admin/messages.php?conversation=$relatedId";
                break;
            case 'evaluation':
                $url = "/tutoring/views/admin/evaluations/show.php?id=$relatedId";
                break;
            case 'company':
                $url = "/tutoring/views/admin/companies/show.php?id=$relatedId";
                break;
            case 'student':
                $url = "/tutoring/views/admin/students/show.php?id=$relatedId";
                break;
            case 'teacher':
                $url = "/tutoring/views/admin/teachers/show.php?id=$relatedId";
                break;
        }
        
        if ($url) {
            $enrichedNotification['action_url'] = $url;
        }
    }
    
    $enrichedNotifications[] = $enrichedNotification;
}

// Envoyer la réponse
sendJsonResponse([
    'data' => $enrichedNotifications,
    'meta' => [
        'current_page' => $page,
        'total_pages' => $totalPages,
        'total_records' => $total,
        'per_page' => $limit,
        'unread_count' => $notificationModel->countUnread($currentUserId)
    ]
]);