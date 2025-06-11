<?php
/**
 * API pour le flux d'activité
 * Endpoint: /api/dashboard/activity
 * Méthode: GET
 */

require_once __DIR__ . '/../utils.php';

// Vérifier que l'utilisateur est connecté et a les droits
requireApiAuth();
requireApiRole(['admin', 'coordinator']);

// Paramètres optionnels
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
$offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;
$type = isset($_GET['type']) ? $_GET['type'] : null;

// Requête de base pour les activités
$query = "
    (SELECT 
        'assignment' AS activity_type,
        a.id AS activity_id,
        a.created_at AS activity_date,
        CONCAT(s.first_name, ' ', s.last_name) AS primary_subject,
        CONCAT(t.first_name, ' ', t.last_name) AS secondary_subject,
        i.title AS tertiary_subject,
        CASE 
            WHEN a.status = 'pending' THEN 'Affectation en attente'
            WHEN a.status = 'confirmed' THEN 'Affectation confirmée'
            WHEN a.status = 'rejected' THEN 'Affectation rejetée'
            WHEN a.status = 'completed' THEN 'Affectation terminée'
            ELSE 'Affectation mise à jour'
        END AS activity_description,
        a.status AS activity_status,
        CONCAT('/tutoring/views/admin/assignments/show.php?id=', a.id) AS activity_link
    FROM assignments a
    JOIN students s ON a.student_id = s.id
    JOIN teachers t ON a.teacher_id = t.id
    JOIN internships i ON a.internship_id = i.id)
    
    UNION
    
    (SELECT
        'document' AS activity_type,
        d.id AS activity_id,
        d.upload_date AS activity_date,
        CONCAT(u.first_name, ' ', u.last_name) AS primary_subject,
        d.file_name AS secondary_subject,
        d.document_type AS tertiary_subject,
        'Document téléversé' AS activity_description,
        'uploaded' AS activity_status,
        CONCAT('/tutoring/views/admin/documents/show.php?id=', d.id) AS activity_link
    FROM documents d
    JOIN users u ON d.uploaded_by = u.id)
    
    UNION
    
    (SELECT
        'meeting' AS activity_type,
        m.id AS activity_id,
        m.created_at AS activity_date,
        CONCAT(u.first_name, ' ', u.last_name) AS primary_subject,
        m.title AS secondary_subject,
        DATE_FORMAT(m.meeting_date, '%d/%m/%Y %H:%i') AS tertiary_subject,
        'Réunion planifiée' AS activity_description,
        'scheduled' AS activity_status,
        CONCAT('/tutoring/views/admin/meetings/show.php?id=', m.id) AS activity_link
    FROM meetings m
    JOIN users u ON m.created_by = u.id)
    
    UNION
    
    (SELECT
        'user' AS activity_type,
        u.id AS activity_id,
        u.created_at AS activity_date,
        CONCAT(u.first_name, ' ', u.last_name) AS primary_subject,
        u.role AS secondary_subject,
        u.email AS tertiary_subject,
        'Utilisateur créé' AS activity_description,
        'created' AS activity_status,
        CONCAT('/tutoring/views/admin/users/show.php?id=', u.id) AS activity_link
    FROM users u
    WHERE u.created_at > DATE_SUB(NOW(), INTERVAL 30 DAY))
    
    UNION
    
    (SELECT
        'internship' AS activity_type,
        i.id AS activity_id,
        i.created_at AS activity_date,
        i.title AS primary_subject,
        c.name AS secondary_subject,
        i.location AS tertiary_subject,
        CASE
            WHEN i.status = 'available' THEN 'Stage disponible'
            WHEN i.status = 'assigned' THEN 'Stage assigné'
            WHEN i.status = 'completed' THEN 'Stage terminé'
            ELSE 'Stage mis à jour'
        END AS activity_description,
        i.status AS activity_status,
        CONCAT('/tutoring/views/admin/internships/show.php?id=', i.id) AS activity_link
    FROM internships i
    JOIN companies c ON i.company_id = c.id)
";

// Filtrer par type si spécifié
if ($type) {
    $query = "SELECT * FROM ($query) AS activities WHERE activity_type = :type ORDER BY activity_date DESC LIMIT :offset, :limit";
    $params = [
        ':type' => $type,
        ':limit' => $limit,
        ':offset' => $offset
    ];
} else {
    $query = "SELECT * FROM ($query) AS activities ORDER BY activity_date DESC LIMIT :offset, :limit";
    $params = [
        ':limit' => $limit,
        ':offset' => $offset
    ];
}

// Exécuter la requête
try {
    $stmt = $db->prepare($query);
    
    // Bind des paramètres
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    
    $stmt->execute();
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formater les dates
    foreach ($activities as &$activity) {
        $activity['formatted_date'] = date('d/m/Y H:i', strtotime($activity['activity_date']));
        $activity['relative_time'] = getRelativeTime($activity['activity_date']);
    }
    
    // Récupérer le nombre total d'activités
    $countQuery = "SELECT COUNT(*) AS total FROM ($query) AS activities";
    $countStmt = $db->prepare($countQuery);
    $countStmt->execute($params);
    $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Préparer la réponse
    $response = [
        'activities' => $activities,
        'pagination' => [
            'total' => (int) $totalCount,
            'limit' => $limit,
            'offset' => $offset,
            'has_more' => $offset + $limit < $totalCount
        ]
    ];
    
    // Envoyer la réponse
    sendJsonResponse($response);
} catch (PDOException $e) {
    sendJsonError('Erreur lors de la récupération des activités: ' . $e->getMessage(), 500);
}

/**
 * Fonction utilitaire pour formater le temps relatif
 */
function getRelativeTime($timestamp) {
    $time = strtotime($timestamp);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return "il y a quelques secondes";
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return "il y a " . $minutes . " minute" . ($minutes > 1 ? "s" : "");
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return "il y a " . $hours . " heure" . ($hours > 1 ? "s" : "");
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return "il y a " . $days . " jour" . ($days > 1 ? "s" : "");
    } elseif ($diff < 2592000) {
        $weeks = floor($diff / 604800);
        return "il y a " . $weeks . " semaine" . ($weeks > 1 ? "s" : "");
    } else {
        return date('d/m/Y', $time);
    }
}
?>