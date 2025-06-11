<?php
/**
 * API pour enregistrer une connexion
 * Endpoint: /api/auth/record-login
 * Méthode: POST
 * 
 * Cette API est appelée automatiquement lors de la connexion d'un utilisateur
 * pour enregistrer l'événement dans l'historique.
 */

require_once __DIR__ . '/../utils.php';

// Cette API est appelée par le système d'authentification, donc pas besoin de vérifier l'authentification

// Récupérer les données POST
$userId = $_POST['user_id'] ?? null;
$status = $_POST['status'] ?? 'success';
$details = $_POST['details'] ?? null;

// Vérifier que l'ID utilisateur est fourni
if (!$userId) {
    sendJsonError('ID utilisateur manquant', 400);
}

// Table pour stocker l'historique de connexion
$historyTable = 'user_login_history';

// Récupérer les informations sur le client
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

// Détecter le type d'appareil
$device = 'Desktop';
if (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $userAgent)) {
    $device = 'Mobile';
} elseif (preg_match('/android|ipad|playbook|silk/i', $userAgent)) {
    $device = 'Tablet';
}

try {
    // Vérifier si la table existe, sinon la créer
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
    
    // Insérer l'entrée d'historique
    $stmt = $db->prepare("
        INSERT INTO $historyTable (user_id, ip_address, user_agent, device, status, details)
        VALUES (:user_id, :ip_address, :user_agent, :device, :status, :details)
    ");
    
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindParam(':ip_address', $ipAddress, PDO::PARAM_STR);
    $stmt->bindParam(':user_agent', $userAgent, PDO::PARAM_STR);
    $stmt->bindParam(':device', $device, PDO::PARAM_STR);
    $stmt->bindParam(':status', $status, PDO::PARAM_STR);
    $stmt->bindParam(':details', $details, PDO::PARAM_STR);
    
    $stmt->execute();
    
    // Envoyer la réponse
    sendJsonResponse([
        'success' => true,
        'message' => 'Connexion enregistrée avec succès'
    ]);
} catch (PDOException $e) {
    sendJsonError('Erreur lors de l\'enregistrement de la connexion: ' . $e->getMessage(), 500);
}
?>