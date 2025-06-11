<?php
// Basic database connection test
$host = '127.0.0.1';
$port = '3306';
$dbname = 'tutoring_system';
$user = 'dansia';
$pass = 'dansia';

echo "Testing database connection...\n";
echo "Host: $host:$port\n";
echo "Database: $dbname\n";
echo "User: $user\n";

try {
    // Try direct connection without PDO first
    $conn = mysqli_connect($host, $user, $pass, $dbname, $port);
    
    if (!$conn) {
        echo "MySQL connection failed: " . mysqli_connect_error() . "\n";
    } else {
        echo "MySQL connection successful\n";
        
        // Check if tables exist
        $tables = ['algorithm_parameters', 'algorithm_executions'];
        
        foreach ($tables as $table) {
            $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
            $exists = mysqli_num_rows($result) > 0;
            
            echo "$table: " . ($exists ? "EXISTS" : "DOES NOT EXIST") . "\n";
            
            if ($exists) {
                // Count rows
                $result = mysqli_query($conn, "SELECT COUNT(*) FROM $table");
                $count = mysqli_fetch_row($result)[0];
                echo "  - Row count: $count\n";
            }
        }
        
        mysqli_close($conn);
    }
    
    // Also test PDO connection
    echo "\nTesting PDO connection...\n";
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "PDO connection successful\n";
    $pdo = null;
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}