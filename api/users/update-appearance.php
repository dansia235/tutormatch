<?php
/**
 * API pour mettre à jour les préférences d'apparence de l'utilisateur
 * Endpoint: /api/users/update-appearance.php
 * Méthodes: POST
 */

require_once __DIR__ . '/../utils.php';

// Vérifier que l'utilisateur est connecté
requireLogin();

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlashMessage('error', 'Méthode non autorisée');
    redirect('/tutoring/views/common/settings.php');
}

// Vérifier le token CSRF
validateCsrfToken();

// Récupérer les données du formulaire
$theme = isset($_POST['theme']) ? $_POST['theme'] : 'light';
$primaryColor = isset($_POST['primary_color']) ? $_POST['primary_color'] : 'blue';
$fontSize = isset($_POST['font_size']) ? intval($_POST['font_size']) : 100;
$animationsEnabled = isset($_POST['animations_enabled']) ? true : false;

// Valider les données
$validThemes = ['light', 'dark', 'system'];
if (!in_array($theme, $validThemes)) {
    $theme = 'light';
}

$validColors = ['blue', 'green', 'purple', 'orange', 'red', 'teal'];
if (!in_array($primaryColor, $validColors)) {
    $primaryColor = 'blue';
}

if ($fontSize < 80 || $fontSize > 120) {
    $fontSize = 100;
}

try {
    // Préparer les préférences à enregistrer
    $preferences = [
        'theme' => $theme,
        'primary_color' => $primaryColor,
        'font_size' => $fontSize,
        'animations_enabled' => $animationsEnabled
    ];
    
    // Enregistrer les préférences dans la base de données
    $result = updateUserPreferences($_SESSION['user_id'], $preferences);
    
    if ($result) {
        setFlashMessage('success', 'Préférences d\'apparence mises à jour avec succès');
    } else {
        setFlashMessage('error', 'Erreur lors de la mise à jour des préférences d\'apparence');
    }
} catch (Exception $e) {
    setFlashMessage('error', 'Erreur: ' . $e->getMessage());
}

// Rediriger vers la page des paramètres
redirect('/tutoring/views/common/settings.php');

/**
 * Met à jour les préférences d'un utilisateur
 * @param int $userId ID de l'utilisateur
 * @param array $preferences Tableau de préférences à mettre à jour
 * @return bool Succès de l'opération
 */
function updateUserPreferences($userId, $preferences) {
    global $db;
    
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
        error_log('Erreur lors de la vérification ou création de la table: ' . $e->getMessage());
        return false;
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
        
        foreach ($preferences as $key => $value) {
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
        
        // Mettre à jour la session si nécessaire
        $_SESSION['user_preferences'] = $preferences;
        
        return true;
    } catch (PDOException $e) {
        // Annuler la transaction en cas d'erreur
        $db->rollBack();
        error_log('Erreur lors de la mise à jour des préférences: ' . $e->getMessage());
        return false;
    }
}
?>