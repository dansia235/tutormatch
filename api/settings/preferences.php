<?php
/**
 * API pour les préférences utilisateur
 * Endpoint: /api/settings/preferences
 * Méthodes: GET, PUT
 */

require_once __DIR__ . '/../utils.php';

// Vérifier que l'utilisateur est connecté
requireApiAuth();

// Table pour stocker les préférences utilisateur
$preferencesTable = 'user_preferences';

// Vérifier si la table existe, sinon la créer
try {
    $checkTable = $db->query("SHOW TABLES LIKE '$preferencesTable'");
    if ($checkTable->rowCount() === 0) {
        // Créer la table
        $db->exec("
            CREATE TABLE IF NOT EXISTS $preferencesTable (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                preference_key VARCHAR(100) NOT NULL,
                preference_value TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY user_preference (user_id, preference_key),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }
} catch (PDOException $e) {
    sendJsonError('Erreur lors de la vérification ou création de la table: ' . $e->getMessage(), 500);
}

// Récupérer l'ID de l'utilisateur connecté
$userId = $_SESSION['user_id'];

// Traiter la requête selon la méthode HTTP
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Récupérer une préférence spécifique ou toutes les préférences
        $key = isset($_GET['key']) ? $_GET['key'] : null;
        
        try {
            if ($key) {
                // Récupérer une préférence spécifique
                $stmt = $db->prepare("
                    SELECT preference_key, preference_value 
                    FROM $preferencesTable 
                    WHERE user_id = :user_id AND preference_key = :key
                ");
                $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
                $stmt->bindParam(':key', $key, PDO::PARAM_STR);
                $stmt->execute();
                
                $preference = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($preference) {
                    // Tenter de décoder JSON
                    $value = $preference['preference_value'];
                    $decodedValue = json_decode($value, true);
                    
                    // Si la valeur est du JSON valide, retourner l'objet JSON, sinon la valeur brute
                    $response = [
                        $preference['preference_key'] => $decodedValue !== null && json_last_error() === JSON_ERROR_NONE ? $decodedValue : $value
                    ];
                } else {
                    // Préférence non trouvée, retourner les valeurs par défaut
                    $response = getDefaultPreferences($key);
                }
            } else {
                // Récupérer toutes les préférences
                $stmt = $db->prepare("
                    SELECT preference_key, preference_value 
                    FROM $preferencesTable 
                    WHERE user_id = :user_id
                ");
                $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
                $stmt->execute();
                
                $preferences = [];
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    // Tenter de décoder JSON
                    $value = $row['preference_value'];
                    $decodedValue = json_decode($value, true);
                    
                    // Si la valeur est du JSON valide, utiliser l'objet JSON, sinon la valeur brute
                    $preferences[$row['preference_key']] = $decodedValue !== null && json_last_error() === JSON_ERROR_NONE ? $decodedValue : $value;
                }
                
                // Fusionner avec les préférences par défaut
                $response = array_merge(getDefaultPreferences(), $preferences);
            }
            
            // Envoyer la réponse
            sendJsonResponse($response);
        } catch (PDOException $e) {
            sendJsonError('Erreur lors de la récupération des préférences: ' . $e->getMessage(), 500);
        }
        break;
        
    case 'PUT':
    case 'POST':
        // Récupérer les données du corps de la requête
        $requestData = json_decode(file_get_contents('php://input'), true);
        
        if (!$requestData) {
            sendJsonError('Données JSON invalides', 400);
        }
        
        try {
            // Début de la transaction
            $db->beginTransaction();
            
            // Mettre à jour ou insérer les préférences
            $stmt = $db->prepare("
                INSERT INTO $preferencesTable (user_id, preference_key, preference_value)
                VALUES (:user_id, :key, :value)
                ON DUPLICATE KEY UPDATE preference_value = :value
            ");
            
            foreach ($requestData as $key => $value) {
                // Si la valeur est un tableau ou un objet, la convertir en JSON
                if (is_array($value) || is_object($value)) {
                    $value = json_encode($value);
                }
                
                // Sinon, convertir en chaîne
                $value = (string) $value;
                
                $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
                $stmt->bindParam(':key', $key, PDO::PARAM_STR);
                $stmt->bindParam(':value', $value, PDO::PARAM_STR);
                $stmt->execute();
            }
            
            // Valider la transaction
            $db->commit();
            
            // Envoyer la réponse
            sendJsonResponse([
                'success' => true,
                'message' => 'Préférences mises à jour avec succès'
            ]);
        } catch (PDOException $e) {
            // Annuler la transaction en cas d'erreur
            $db->rollBack();
            sendJsonError('Erreur lors de la mise à jour des préférences: ' . $e->getMessage(), 500);
        }
        break;
        
    default:
        sendJsonError('Méthode HTTP non supportée', 405);
}

/**
 * Récupère les préférences par défaut
 * @param string|null $key Clé de préférence spécifique
 * @return array Préférences par défaut
 */
function getDefaultPreferences($key = null) {
    $defaults = [
        // Préférences de notification
        'email_notifications' => [
            'messages' => true,
            'assignments' => true,
            'meetings' => true,
            'documents' => true,
            'announcements' => true
        ],
        'system_notifications' => [
            'messages' => true,
            'assignments' => true,
            'meetings' => true,
            'documents' => true,
            'announcements' => true
        ],
        'notification_frequency' => 'realtime',
        
        // Préférences d'apparence
        'theme' => 'light',
        'primary_color' => 'blue',
        'font_size' => 100,
        'animations_enabled' => true,
        
        // Préférences de tableau de bord
        'dashboard_widgets' => [
            'recent_assignments' => true,
            'upcoming_meetings' => true,
            'recent_documents' => true,
            'unread_messages' => true
        ],
        
        // Préférences d'affichage
        'display_mode' => 'list',
        'sort_order' => 'newest',
        'default_page_size' => 15
    ];
    
    if ($key !== null) {
        return [$key => $defaults[$key] ?? null];
    }
    
    return $defaults;
}
?>