<?php
try {
    $db = new PDO("mysql:host=localhost;dbname=tutoring_system;charset=utf8", 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>Structure table documents</h1>";
    
    $stmt = $db->query("DESCRIBE documents");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td><strong>" . $column['Field'] . "</strong></td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . ($column['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>Colonnes disponibles:</h2>";
    $columnNames = array_column($columns, 'Field');
    echo "<ul>";
    foreach ($columnNames as $col) {
        echo "<li>$col</li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage();
}
?>