<?php
/**
 * API pour les paramètres système
 * Endpoint: /api/settings/system
 * Méthodes: GET, PUT
 */

require_once __DIR__ . '/../utils.php';

// Vérifier que l'utilisateur est connecté et a les droits
requireApiAuth();
requireApiRole(['admin']);

// Table pour stocker les paramètres système
$settingsTable = 'system_settings';

// Vérifier si la table existe, sinon la créer
try {
    $checkTable = $db->query("SHOW TABLES LIKE '$settingsTable'");
    if ($checkTable->rowCount() === 0) {
        // Créer la table
        $db->exec("
            CREATE TABLE IF NOT EXISTS $settingsTable (
                id INT AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(100) NOT NULL UNIQUE,
                setting_value TEXT,
                setting_type VARCHAR(50) NOT NULL DEFAULT 'string',
                category VARCHAR(50) NOT NULL DEFAULT 'general',
                description VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        
        // Insérer les paramètres par défaut
        $defaultSettings = [
            // Paramètres généraux
            ['site_name', 'Système de gestion des stages', 'string', 'general', 'Nom du site'],
            ['contact_email', 'contact@example.com', 'string', 'general', 'Email de contact'],
            ['items_per_page', '15', 'integer', 'general', 'Éléments par page'],
            
            // Paramètres des stages
            ['max_internships_per_student', '5', 'integer', 'internships', 'Nombre maximum de préférences par étudiant'],
            ['max_students_per_teacher', '8', 'integer', 'internships', 'Nombre maximum d\'étudiants par tuteur'],
            ['allow_cross_department', '1', 'boolean', 'internships', 'Autoriser les affectations inter-départements'],
            
            // Paramètres de l'algorithme
            ['algorithm_type', 'greedy', 'string', 'algorithm', 'Type d\'algorithme d\'affectation'],
            ['preference_weight', '40', 'integer', 'algorithm', 'Poids des préférences'],
            ['department_weight', '30', 'integer', 'algorithm', 'Poids du département'],
            ['workload_weight', '30', 'integer', 'algorithm', 'Poids d\'équilibrage de charge'],
            
            // Paramètres de maintenance
            ['maintenance_mode', '0', 'boolean', 'maintenance', 'Activer le mode maintenance'],
            ['maintenance_message', 'Le site est actuellement en maintenance. Veuillez réessayer plus tard.', 'text', 'maintenance', 'Message de maintenance']
        ];
        
        $insertStmt = $db->prepare("
            INSERT INTO $settingsTable (setting_key, setting_value, setting_type, category, description)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        foreach ($defaultSettings as $setting) {
            $insertStmt->execute($setting);
        }
    }
} catch (PDOException $e) {
    sendJsonError('Erreur lors de la vérification ou création de la table: ' . $e->getMessage(), 500);
}

// Traiter la requête selon la méthode HTTP
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Récupérer les paramètres
        $category = isset($_GET['category']) ? $_GET['category'] : null;
        
        try {
            if ($category) {
                // Récupérer les paramètres d'une catégorie spécifique
                $stmt = $db->prepare("SELECT * FROM $settingsTable WHERE category = :category ORDER BY id");
                $stmt->bindParam(':category', $category, PDO::PARAM_STR);
            } else {
                // Récupérer tous les paramètres
                $stmt = $db->query("SELECT * FROM $settingsTable ORDER BY category, id");
            }
            
            $stmt->execute();
            $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Convertir les valeurs selon leur type
            foreach ($settings as &$setting) {
                switch ($setting['setting_type']) {
                    case 'integer':
                        $setting['setting_value'] = (int) $setting['setting_value'];
                        break;
                    case 'boolean':
                        $setting['setting_value'] = (bool) (int) $setting['setting_value'];
                        break;
                    case 'json':
                        $setting['setting_value'] = json_decode($setting['setting_value'], true);
                        break;
                }
            }
            
            // Organiser les paramètres par catégorie
            $organizedSettings = [];
            foreach ($settings as $setting) {
                $category = $setting['category'];
                
                if (!isset($organizedSettings[$category])) {
                    $organizedSettings[$category] = [];
                }
                
                $organizedSettings[$category][$setting['setting_key']] = $setting['setting_value'];
            }
            
            // Envoyer la réponse
            sendJsonResponse($organizedSettings);
        } catch (PDOException $e) {
            sendJsonError('Erreur lors de la récupération des paramètres: ' . $e->getMessage(), 500);
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
            
            // Mettre à jour les paramètres
            $updateStmt = $db->prepare("
                UPDATE $settingsTable 
                SET setting_value = :value 
                WHERE setting_key = :key
            ");
            
            foreach ($requestData as $key => $value) {
                // Convertir les booléens en entiers pour le stockage
                if (is_bool($value)) {
                    $value = $value ? '1' : '0';
                } elseif (is_array($value) || is_object($value)) {
                    // Convertir les tableaux/objets en JSON
                    $value = json_encode($value);
                }
                
                $updateStmt->bindParam(':key', $key, PDO::PARAM_STR);
                $updateStmt->bindParam(':value', $value, PDO::PARAM_STR);
                $updateStmt->execute();
                
                // Vérifier si le paramètre existe, sinon l'ajouter
                if ($updateStmt->rowCount() === 0) {
                    $insertStmt = $db->prepare("
                        INSERT INTO $settingsTable (setting_key, setting_value, setting_type, category)
                        VALUES (:key, :value, :type, :category)
                    ");
                    
                    // Déterminer le type
                    $type = 'string';
                    if (is_bool($value)) {
                        $type = 'boolean';
                    } elseif (is_numeric($value) && (int)$value == $value) {
                        $type = 'integer';
                    } elseif (is_array($value) || is_object($value)) {
                        $type = 'json';
                    }
                    
                    // Déterminer la catégorie à partir du préfixe de la clé
                    $category = 'general';
                    if (strpos($key, 'internship_') === 0) {
                        $category = 'internships';
                    } elseif (strpos($key, 'algorithm_') === 0) {
                        $category = 'algorithm';
                    } elseif (strpos($key, 'maintenance_') === 0) {
                        $category = 'maintenance';
                    }
                    
                    $insertStmt->bindParam(':key', $key, PDO::PARAM_STR);
                    $insertStmt->bindParam(':value', $value, PDO::PARAM_STR);
                    $insertStmt->bindParam(':type', $type, PDO::PARAM_STR);
                    $insertStmt->bindParam(':category', $category, PDO::PARAM_STR);
                    
                    $insertStmt->execute();
                }
            }
            
            // Valider la transaction
            $db->commit();
            
            // Envoyer la réponse
            sendJsonResponse([
                'success' => true,
                'message' => 'Paramètres système mis à jour avec succès'
            ]);
        } catch (PDOException $e) {
            // Annuler la transaction en cas d'erreur
            $db->rollBack();
            sendJsonError('Erreur lors de la mise à jour des paramètres: ' . $e->getMessage(), 500);
        }
        break;
        
    default:
        sendJsonError('Méthode HTTP non supportée', 405);
}
?>