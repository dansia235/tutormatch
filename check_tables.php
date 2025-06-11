<?php
/**
 * Script pour vérifier la structure des tables d'algorithmes d'affectation
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/includes/init.php';

// Vérifier que l'utilisateur est connecté et admin
requireRole('admin');

// Vérifier si la table algorithm_parameters existe
try {
    $query = "SHOW TABLES LIKE 'algorithm_parameters'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $tableExists = $stmt->rowCount() > 0;
    
    echo "<h2>Vérification de la table algorithm_parameters</h2>";
    if ($tableExists) {
        echo "La table algorithm_parameters existe.<br>";
        
        // Vérifier la structure de la table
        $query = "DESCRIBE algorithm_parameters";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Structure actuelle de la table:</h3>";
        echo "<pre>";
        print_r($columns);
        echo "</pre>";
    } else {
        echo "La table algorithm_parameters n'existe pas.<br>";
    }
    
    // Vérifier si la table algorithm_executions existe
    $query = "SHOW TABLES LIKE 'algorithm_executions'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $tableExists = $stmt->rowCount() > 0;
    
    echo "<h2>Vérification de la table algorithm_executions</h2>";
    if ($tableExists) {
        echo "La table algorithm_executions existe.<br>";
        
        // Vérifier la structure de la table
        $query = "DESCRIBE algorithm_executions";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Structure actuelle de la table:</h3>";
        echo "<pre>";
        print_r($columns);
        echo "</pre>";
    } else {
        echo "La table algorithm_executions n'existe pas.<br>";
    }
    
} catch (PDOException $e) {
    die("Erreur lors de la vérification des tables: " . $e->getMessage());
}
?>