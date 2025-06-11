<?php
/**
 * Script pour créer ou corriger les tables d'algorithmes d'affectation
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/includes/init.php';

// Vérifier que l'utilisateur est connecté et admin
requireRole('admin');

// Fonction pour créer la table algorithm_parameters
function createAlgorithmParametersTable($db) {
    try {
        // Vérifier si la table existe déjà
        $query = "SHOW TABLES LIKE 'algorithm_parameters'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $tableExists = $stmt->rowCount() > 0;
        
        if ($tableExists) {
            // Supprimer la table existante
            $db->exec('DROP TABLE IF EXISTS algorithm_parameters');
            echo "Table algorithm_parameters existante supprimée.<br>";
        }
        
        // Créer la table
        $query = "CREATE TABLE IF NOT EXISTS `algorithm_parameters` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL,
            `description` text DEFAULT NULL,
            `algorithm_type` varchar(50) NOT NULL DEFAULT 'greedy',
            `department_weight` int(11) NOT NULL DEFAULT 50,
            `preference_weight` int(11) NOT NULL DEFAULT 30,
            `capacity_weight` int(11) NOT NULL DEFAULT 20,
            `allow_cross_department` tinyint(1) NOT NULL DEFAULT 0,
            `prioritize_preferences` tinyint(1) NOT NULL DEFAULT 1,
            `balance_workload` tinyint(1) NOT NULL DEFAULT 1,
            `is_default` tinyint(1) NOT NULL DEFAULT 0,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $db->exec($query);
        
        // Insérer un jeu de paramètres par défaut
        $query = "INSERT INTO `algorithm_parameters` (
            `name`, 
            `description`, 
            `algorithm_type`, 
            `department_weight`, 
            `preference_weight`, 
            `capacity_weight`, 
            `allow_cross_department`, 
            `prioritize_preferences`, 
            `balance_workload`, 
            `is_default`
        ) VALUES (
            'Paramètres par défaut',
            'Paramètres générés automatiquement lors de l\'installation',
            'greedy',
            50,
            30,
            20,
            0,
            1,
            1,
            1
        )";
        
        $db->exec($query);
        
        echo "Table algorithm_parameters créée avec succès.<br>";
        return true;
    } catch (PDOException $e) {
        echo "Erreur lors de la création de la table algorithm_parameters: " . $e->getMessage() . "<br>";
        return false;
    }
}

// Fonction pour créer la table algorithm_executions
function createAlgorithmExecutionsTable($db) {
    try {
        // Vérifier si la table existe déjà
        $query = "SHOW TABLES LIKE 'algorithm_executions'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $tableExists = $stmt->rowCount() > 0;
        
        if ($tableExists) {
            // Supprimer la table existante
            $db->exec('DROP TABLE IF EXISTS algorithm_executions');
            echo "Table algorithm_executions existante supprimée.<br>";
        }
        
        // Créer la table
        $query = "CREATE TABLE IF NOT EXISTS `algorithm_executions` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `parameters_id` int(11) NOT NULL,
            `executed_by` int(11) NOT NULL,
            `execution_time` float NOT NULL,
            `students_count` int(11) NOT NULL,
            `teachers_count` int(11) NOT NULL,
            `assignments_count` int(11) NOT NULL,
            `unassigned_count` int(11) NOT NULL,
            `average_satisfaction` float NOT NULL,
            `notes` text DEFAULT NULL,
            `executed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `parameters_id` (`parameters_id`),
            KEY `executed_by` (`executed_by`),
            CONSTRAINT `algorithm_executions_ibfk_1` FOREIGN KEY (`parameters_id`) REFERENCES `algorithm_parameters` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT `algorithm_executions_ibfk_2` FOREIGN KEY (`executed_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $db->exec($query);
        
        echo "Table algorithm_executions créée avec succès.<br>";
        return true;
    } catch (PDOException $e) {
        echo "Erreur lors de la création de la table algorithm_executions: " . $e->getMessage() . "<br>";
        return false;
    }
}

// Exécuter les fonctions
try {
    // Désactiver les contraintes de clé étrangère
    $db->exec('SET FOREIGN_KEY_CHECKS = 0');
    
    // Créer les tables
    $paramsSuccess = createAlgorithmParametersTable($db);
    $execSuccess = createAlgorithmExecutionsTable($db);
    
    // Réactiver les contraintes de clé étrangère
    $db->exec('SET FOREIGN_KEY_CHECKS = 1');
    
    if ($paramsSuccess && $execSuccess) {
        echo "<h3>Toutes les tables ont été créées avec succès!</h3>";
        echo "Vous pouvez maintenant <a href='/tutoring/views/admin/assignments/generate.php'>générer des affectations</a>.";
    } else {
        echo "<h3>Des erreurs sont survenues lors de la création des tables.</h3>";
    }
    
} catch (PDOException $e) {
    die("Erreur générale: " . $e->getMessage());
}
?>