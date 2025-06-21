<?php
/**
 * Script pour ajouter la colonne "reason" à la table student_preferences
 * Cette colonne est référencée dans le code mais n'existe pas dans la base de données
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/includes/init.php';

// Afficher un en-tête HTML simple
echo '<!DOCTYPE html>
<html>
<head>
    <title>Mise à jour de la base de données</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Mise à jour de la structure de la base de données</h1>';

try {
    // Vérifier si la colonne existe déjà
    $checkQuery = "SHOW COLUMNS FROM `student_preferences` LIKE 'reason'";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() > 0) {
        echo '<p class="info">La colonne "reason" existe déjà dans la table student_preferences. Aucune action nécessaire.</p>';
    } else {
        // Ajouter la colonne
        $alterQuery = "ALTER TABLE `student_preferences` ADD COLUMN `reason` TEXT NULL AFTER `preference_order`";
        $alterStmt = $db->prepare($alterQuery);
        $alterStmt->execute();
        
        echo '<p class="success">La colonne "reason" a été ajoutée avec succès à la table student_preferences.</p>';
        
        // Vérifier que la colonne a bien été ajoutée
        $verifyStmt = $db->prepare($checkQuery);
        $verifyStmt->execute();
        
        if ($verifyStmt->rowCount() > 0) {
            echo '<p class="success">Vérification réussie : la colonne "reason" existe maintenant dans la table.</p>';
        } else {
            echo '<p class="error">Erreur : La colonne n\'a pas été ajoutée correctement.</p>';
        }
    }
    
    // Afficher la structure actuelle de la table
    $describeQuery = "DESCRIBE `student_preferences`";
    $describeStmt = $db->prepare($describeQuery);
    $describeStmt->execute();
    $columns = $describeStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo '<h2>Structure actuelle de la table student_preferences :</h2>';
    echo '<pre>';
    print_r($columns);
    echo '</pre>';
    
    echo '<p>Vous pouvez maintenant <a href="/tutoring/views/student/preferences.php">retourner à la page des préférences</a>.</p>';
    
} catch (PDOException $e) {
    echo '<p class="error">Erreur lors de la mise à jour de la base de données : ' . $e->getMessage() . '</p>';
}

echo '</body></html>';