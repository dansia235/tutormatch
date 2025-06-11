<?php
/**
 * API pour l'historique de connexion
 * Endpoint: /api/users/login-history
 * Méthode: GET
 * 
 * Paramètres:
 *  - user_id: ID de l'utilisateur (optionnel, admin seulement)
 *  - limit: Nombre maximum d'entrées (défaut: 10)
 *  - offset: Décalage pour la pagination (défaut: 0)
 */

require_once __DIR__ . '/../utils.php';

// Vérifier que l'utilisateur est connecté
requireApiAuth();

// Table pour stocker l'historique de connexion
$historyTable = 'user_login_history';

// Vérifier si la table existe, sinon la créer
try {
    $checkTable = $db->query("SHOW TABLES LIKE '$historyTable'");
    if ($checkTable->rowCount() === 0) {
        // Créer la table
        $db->exec("
            CREATE TABLE IF NOT EXISTS $historyTable (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                login_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                ip_address VARCHAR(45),
                user_agent TEXT,
                device VARCHAR(255),
                status ENUM('success', 'failed') NOT NULL DEFAULT 'success',
                details TEXT,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }
} catch (PDOException $e) {
    sendJsonError('Erreur lors de la vérification ou création de la table: ' . $e->getMessage(), 500);
}

// Récupérer les paramètres
$requestedUserId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : null;
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
$offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;

// Déterminer l'utilisateur cible
$currentUserId = $_SESSION['user_id'];
$isAdmin = $_SESSION['user_role'] === 'admin';

// Si un ID d'utilisateur est demandé et que l'utilisateur n'est pas admin, vérifier que c'est son propre ID
if ($requestedUserId && !$isAdmin && $requestedUserId !== $currentUserId) {
    sendJsonError('Accès non autorisé', 403);
}

// Déterminer l'ID de l'utilisateur pour la requête
$targetUserId = $requestedUserId ?: $currentUserId;

try {
    // Construire la requête
    $query = "
        SELECT id, login_date, ip_address, user_agent, device, status, details
        FROM $historyTable
        WHERE user_id = :user_id
        ORDER BY login_date DESC
        LIMIT :offset, :limit
    ";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $targetUserId, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formater les données
    foreach ($history as &$entry) {
        // Formater la date
        $entry['formatted_date'] = date('d/m/Y H:i', strtotime($entry['login_date']));
        
        // Extraire les informations du user agent
        $userAgent = $entry['user_agent'];
        
        // Détecter le navigateur
        $browser = 'Inconnu';
        if (preg_match('/Chrome/i', $userAgent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/Firefox/i', $userAgent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/Safari/i', $userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/Edge/i', $userAgent)) {
            $browser = 'Edge';
        } elseif (preg_match('/MSIE|Trident/i', $userAgent)) {
            $browser = 'Internet Explorer';
        }
        
        // Détecter le système d'exploitation
        $os = 'Inconnu';
        if (preg_match('/Windows/i', $userAgent)) {
            $os = 'Windows';
        } elseif (preg_match('/Mac OS X/i', $userAgent)) {
            $os = 'macOS';
        } elseif (preg_match('/Linux/i', $userAgent)) {
            $os = 'Linux';
        } elseif (preg_match('/Android/i', $userAgent)) {
            $os = 'Android';
        } elseif (preg_match('/iPhone|iPad|iPod/i', $userAgent)) {
            $os = 'iOS';
        }
        
        // Mettre à jour l'entrée avec les informations formatées
        $entry['browser'] = $browser;
        $entry['os'] = $os;
        $entry['device_info'] = "$browser / $os";
    }
    
    // Récupérer le nombre total d'entrées
    $countQuery = "SELECT COUNT(*) AS total FROM $historyTable WHERE user_id = :user_id";
    $countStmt = $db->prepare($countQuery);
    $countStmt->bindParam(':user_id', $targetUserId, PDO::PARAM_INT);
    $countStmt->execute();
    $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Préparer la réponse
    $response = [
        'history' => $history,
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
    sendJsonError('Erreur lors de la récupération de l\'historique de connexion: ' . $e->getMessage(), 500);
}
?>