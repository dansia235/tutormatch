<?php
/**
 * Script pour vérifier la structure de la table evaluations
 */

// Configuration d'encodage UTF-8
ini_set('default_charset', 'UTF-8');

// Inclure le fichier de configuration de la base de données
require_once __DIR__ . '/config/database.php';

try {
    // Établir la connexion à la base de données 
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8";
    $db = new PDO($dsn, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Requête pour obtenir la structure de la table
    $stmt = $db->query("DESCRIBE evaluations");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Structure de la table evaluations</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            table { border-collapse: collapse; width: 100%; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
        </style>
    </head>
    <body>
        <h1>Structure de la table evaluations</h1>
        <table>
            <thead>
                <tr>
                    <th>Field</th>
                    <th>Type</th>
                    <th>Null</th>
                    <th>Key</th>
                    <th>Default</th>
                    <th>Extra</th>
                </tr>
            </thead>
            <tbody>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        foreach ($column as $key => $value) {
            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    
    echo "</tbody>
        </table>
    </body>
    </html>";
    
} catch (Exception $e) {
    die("Erreur : " . $e->getMessage());
}
?>