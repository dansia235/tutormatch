<?php
/**
 * Script simple pour ajouter la colonne "reason" à la table student_preferences
 * Cette colonne est nécessaire pour le bon fonctionnement des préférences étudiantes
 */

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Informations de connexion à la base de données
$host = 'localhost';
$dbname = 'tutoring_system';
$username = 'root';
$password = '';

echo '<html>
<head>
    <title>Ajout de la colonne "reason"</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .info { color: blue; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Ajout de la colonne "reason" à la table student_preferences</h1>';

try {
    // Connexion à la base de données
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo '<p class="success">Connexion à la base de données réussie.</p>';
    
    // Vérifier si la colonne existe déjà
    $checkQuery = "SHOW COLUMNS FROM `student_preferences` LIKE 'reason'";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() > 0) {
        echo '<p class="info">La colonne "reason" existe déjà dans la table student_preferences. Aucune action nécessaire.</p>';
    } else {
        echo '<p class="warning">La colonne "reason" n\'existe pas encore. Tentative d\'ajout...</p>';
        
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
    foreach ($columns as $column) {
        echo $column['Field'] . ' - ' . $column['Type'] . ' - ' . ($column['Null'] == 'YES' ? 'NULL' : 'NOT NULL') . ' - ' . ($column['Default'] ? 'DEFAULT ' . $column['Default'] : '') . "\n";
    }
    echo '</pre>';
    
    // Compter les préférences existantes
    $countQuery = "SELECT COUNT(*) FROM student_preferences";
    $countStmt = $db->prepare($countQuery);
    $countStmt->execute();
    $preferencesCount = $countStmt->fetchColumn();
    
    echo '<p>Nombre total de préférences dans la base de données : <strong>' . $preferencesCount . '</strong></p>';
    
    if ($preferencesCount > 0) {
        // Afficher quelques exemples de préférences
        $exampleQuery = "SELECT sp.*, s.id as student_id, i.title, c.name as company_name 
                         FROM student_preferences sp
                         JOIN students s ON sp.student_id = s.id
                         JOIN internships i ON sp.internship_id = i.id
                         JOIN companies c ON i.company_id = c.id
                         LIMIT 5";
        $exampleStmt = $db->prepare($exampleQuery);
        $exampleStmt->execute();
        $examples = $exampleStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo '<h2>Exemples de préférences :</h2>';
        echo '<pre>';
        foreach ($examples as $example) {
            echo "ID: {$example['id']} - Étudiant: {$example['student_id']} - Stage: {$example['title']} - Entreprise: {$example['company_name']} - Ordre: {$example['preference_order']} - Raison: " . ($example['reason'] ?? 'NULL') . "\n";
        }
        echo '</pre>';
    }
    
    echo '<h2>Prochaines étapes</h2>';
    echo '<p>La colonne "reason" a été ajoutée à la table student_preferences. Voici les prochaines étapes :</p>';
    echo '<ol>';
    echo '<li>Vérifiez que la <a href="/tutoring/views/student/preferences.php">page des préférences</a> fonctionne correctement.</li>';
    echo '<li>Si l\'affichage ne fonctionne toujours pas, utilisez le <a href="/tutoring/fix_student_preferences_simple.php">script de correction d\'affichage</a>.</li>';
    echo '<li>Pour ajouter de nouvelles préférences, accédez à la <a href="/tutoring/views/student/internship.php">page des stages</a>.</li>';
    echo '</ol>';
    
} catch (PDOException $e) {
    echo '<p class="error">Erreur de base de données : ' . $e->getMessage() . '</p>';
    
    echo '<h2>Suggestions de correction :</h2>';
    echo '<ul>';
    echo '<li>Vérifiez que les informations de connexion à la base de données sont correctes.</li>';
    echo '<li>Vérifiez que la base de données "tutoring_system" existe.</li>';
    echo '<li>Vérifiez que la table "student_preferences" existe.</li>';
    echo '<li>Si la table n\'existe pas, vous devrez d\'abord créer la table avant d\'ajouter la colonne.</li>';
    echo '</ul>';
}

echo '</body>
</html>';
?>