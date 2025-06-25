<?php
try {
    $db = new PDO("mysql:host=localhost;dbname=tutoring_system;charset=utf8", 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>Structures des Tables du Système</h1>";
    
    $tables = ['users', 'teachers', 'students', 'companies', 'internships', 'assignments', 'evaluations', 'documents', 'messages'];
    
    foreach ($tables as $table) {
        try {
            echo "<h2>Table: $table</h2>";
            $stmt = $db->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table border='1' style='border-collapse: collapse; margin-bottom: 20px;'>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
            foreach ($columns as $column) {
                echo "<tr>";
                echo "<td><strong>" . $column['Field'] . "</strong></td>";
                echo "<td>" . $column['Type'] . "</td>";
                echo "<td>" . $column['Null'] . "</td>";
                echo "<td>" . $column['Key'] . "</td>";
                echo "<td>" . ($column['Default'] ?? 'NULL') . "</td>";
                echo "<td>" . $column['Extra'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>Erreur pour $table: " . $e->getMessage() . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "Erreur de connexion: " . $e->getMessage();
}
?>