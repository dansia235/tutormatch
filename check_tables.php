<?php
// Include database configuration
require_once 'config/database.php';

// Connect to database
$pdo = getDBConnection();

// Tables to check
$tables = [
    'algorithm_parameters',
    'algorithm_executions'
];

// Check each table
echo "Checking database tables...\n";
foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->rowCount() > 0;
        
        echo "$table: " . ($exists ? "EXISTS" : "DOES NOT EXIST") . "\n";
        
        if ($exists) {
            // Check row count
            $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            echo "  - Row count: $count\n";
            
            // Show table structure
            echo "  - Table structure:\n";
            $columns = $pdo->query("DESCRIBE $table")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($columns as $column) {
                echo "      {$column['Field']} ({$column['Type']})\n";
            }
        }
    } catch (PDOException $e) {
        echo "$table: ERROR checking table - " . $e->getMessage() . "\n";
    }
}

// Check for associated model files
echo "\nChecking model files...\n";
$modelFiles = [
    '/mnt/c/xampp/htdocs/tutoring/models/AlgorithmParameters.php',
    '/mnt/c/xampp/htdocs/tutoring/models/AlgorithmExecution.php'
];

foreach ($modelFiles as $file) {
    echo basename($file) . ": " . (file_exists($file) ? "EXISTS" : "DOES NOT EXIST") . "\n";
    if (file_exists($file)) {
        echo "  - File size: " . filesize($file) . " bytes\n";
    }
}

// Check assignment generation files
echo "\nChecking assignment algorithm files...\n";
$algoFiles = [
    '/mnt/c/xampp/htdocs/tutoring/src/Algorithm/AssignmentAlgorithmInterface.php',
    '/mnt/c/xampp/htdocs/tutoring/src/Algorithm/GreedyAlgorithm.php',
    '/mnt/c/xampp/htdocs/tutoring/src/Service/AssignmentService.php'
];

foreach ($algoFiles as $file) {
    echo basename($file) . ": " . (file_exists($file) ? "EXISTS" : "DOES NOT EXIST") . "\n";
}