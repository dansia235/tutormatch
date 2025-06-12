<?php
/**
 * Script de vérification de la structure de la table documents
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/includes/init.php';

// Vérifier la connexion à la base de données
if (!$db) {
    die("Erreur de connexion à la base de données");
}

// Récupérer la structure de la table documents
$query = "DESCRIBE documents";
$stmt = $db->prepare($query);
$stmt->execute();
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h1>Structure de la table 'documents'</h1>";
echo "<table border='1'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

foreach ($columns as $column) {
    echo "<tr>";
    echo "<td>" . $column['Field'] . "</td>";
    echo "<td>" . $column['Type'] . "</td>";
    echo "<td>" . $column['Null'] . "</td>";
    echo "<td>" . $column['Key'] . "</td>";
    echo "<td>" . $column['Default'] . "</td>";
    echo "<td>" . ($column['Extra'] ?? '') . "</td>";
    echo "</tr>";
}

echo "</table>";

// Tester la création d'un document factice
try {
    $documentModel = new Document($db);
    
    // Données de test
    $testData = [
        'title' => 'Document de test',
        'description' => 'Ceci est un document de test pour vérifier la structure de la table',
        'file_path' => '/uploads/documents/test.pdf',
        'file_type' => 'application/pdf',
        'file_size' => 1024,
        'type' => 'report', // Type valide selon l'enum: 'contract','report','evaluation','certificate','other'
        'user_id' => 1, // ID utilisateur existant
        'status' => 'submitted' // Statut valide selon l'enum: 'draft','submitted','approved','rejected'
    ];
    
    echo "<h2>Test de création d'un document</h2>";
    echo "<pre>Données : " . print_r($testData, true) . "</pre>";
    
    // Activer l'affichage des erreurs PDO
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Tester la création
    $documentId = $documentModel->create($testData);
    
    if ($documentId) {
        echo "<div style='color: green;'>Document créé avec succès, ID: " . $documentId . "</div>";
        
        // Récupérer et afficher le document créé
        $document = $documentModel->getById($documentId);
        echo "<pre>Document créé : " . print_r($document, true) . "</pre>";
        
        // Supprimer le document de test
        $documentModel->delete($documentId);
        echo "<div style='color: blue;'>Document de test supprimé</div>";
    } else {
        echo "<div style='color: red;'>Échec de la création du document</div>";
        echo "<div>Erreurs de journalisation :</div>";
        // Afficher les erreurs de journalisation
        $logFile = '/var/log/php/error.log'; // Ajustez selon votre configuration
        if (file_exists($logFile)) {
            $logContent = file_get_contents($logFile);
            echo "<pre>" . htmlspecialchars(substr($logContent, -2000)) . "</pre>";
        } else {
            echo "<div>Fichier de log non accessible</div>";
        }
    }
} catch (Exception $e) {
    echo "<div style='color: red;'>Exception: " . $e->getMessage() . "</div>";
    echo "<pre>Trace: " . $e->getTraceAsString() . "</pre>";
}