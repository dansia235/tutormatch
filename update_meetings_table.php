<?php
/**
 * Script pour mettre à jour la table des réunions
 * Ajoute les colonnes student_attended, notes et completed_at
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/includes/init.php';

// Vérifier que l'utilisateur est connecté et a les droits d'administration
requireRole('admin');

try {
    // Vérifier si les colonnes existent déjà
    $columns = $db->query("SHOW COLUMNS FROM meetings")->fetchAll(PDO::FETCH_COLUMN);
    
    $columnsToAdd = [];
    
    if (!in_array('student_attended', $columns)) {
        $columnsToAdd[] = "ADD COLUMN `student_attended` TINYINT(1) DEFAULT 0 COMMENT 'Indique si l\'étudiant était présent'";
    }
    
    if (!in_array('notes', $columns)) {
        $columnsToAdd[] = "ADD COLUMN `notes` TEXT NULL COMMENT 'Notes de la réunion'";
    }
    
    if (!in_array('completed_at', $columns)) {
        $columnsToAdd[] = "ADD COLUMN `completed_at` DATETIME NULL COMMENT 'Date et heure de complétion'";
    }
    
    if (!in_array('updated_at', $columns)) {
        $columnsToAdd[] = "ADD COLUMN `updated_at` DATETIME NULL COMMENT 'Date et heure de dernière mise à jour'";
    }
    
    if (!empty($columnsToAdd)) {
        // Exécuter l'alter table avec toutes les colonnes à ajouter
        $sql = "ALTER TABLE meetings " . implode(", ", $columnsToAdd);
        $db->exec($sql);
        
        echo "<div style='background-color: #d4edda; color: #155724; padding: 15px; margin: 20px; border-radius: 5px;'>";
        echo "<h3>Mise à jour réussie</h3>";
        echo "<p>Les colonnes suivantes ont été ajoutées à la table meetings :</p>";
        echo "<ul>";
        foreach ($columnsToAdd as $columnDef) {
            preg_match("/ADD COLUMN `([^`]+)`/", $columnDef, $matches);
            if (isset($matches[1])) {
                echo "<li>" . htmlspecialchars($matches[1]) . "</li>";
            }
        }
        echo "</ul>";
        echo "<p><a href='/tutoring/views/tutor/meetings.php'>Retour à la gestion des réunions</a></p>";
        echo "</div>";
    } else {
        echo "<div style='background-color: #cce5ff; color: #004085; padding: 15px; margin: 20px; border-radius: 5px;'>";
        echo "<h3>Information</h3>";
        echo "<p>Toutes les colonnes nécessaires existent déjà dans la table meetings.</p>";
        echo "<p><a href='/tutoring/views/tutor/meetings.php'>Retour à la gestion des réunions</a></p>";
        echo "</div>";
    }
} catch (PDOException $e) {
    echo "<div style='background-color: #f8d7da; color: #721c24; padding: 15px; margin: 20px; border-radius: 5px;'>";
    echo "<h3>Erreur</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><a href='/tutoring/views/tutor/meetings.php'>Retour à la gestion des réunions</a></p>";
    echo "</div>";
}
?>