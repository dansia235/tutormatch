<?php
// Script pour vérifier les erreurs PHP

// Inclure le fichier d'initialisation
require_once __DIR__ . '/includes/init.php';

// Afficher l'emplacement du fichier de log
echo "Fichier de log PHP: " . ini_get('error_log') . "<br>";

// Tester l'enregistrement d'erreurs
error_log("Test d'enregistrement d'erreur depuis check_errors.php");
echo "Un message de test a été enregistré dans le fichier de log.<br><br>";

// Vérifier si le fichier de log existe et est lisible
$error_log_file = ini_get('error_log');
if (file_exists($error_log_file) && is_readable($error_log_file)) {
    // Lire les 50 dernières lignes du fichier de log
    echo "<h2>Dernières entrées du fichier de log</h2>";
    echo "<pre>";
    $log_content = shell_exec("tail -n 50 " . escapeshellarg($error_log_file));
    echo htmlspecialchars($log_content);
    echo "</pre>";
} else {
    echo "Le fichier de log n'existe pas ou n'est pas lisible.<br>";
    
    // Essayer d'afficher les logs de XAMPP
    $xampp_log = "C:/xampp/apache/logs/error.log";
    if (file_exists($xampp_log) && is_readable($xampp_log)) {
        echo "<h2>Dernières entrées du fichier de log XAMPP</h2>";
        echo "<pre>";
        $log_content = shell_exec("tail -n 50 " . escapeshellarg($xampp_log));
        echo htmlspecialchars($log_content);
        echo "</pre>";
    } else {
        echo "Le fichier de log XAMPP n'existe pas ou n'est pas lisible.<br>";
    }
}

// Tester la création d'un document pour détecter les erreurs
echo "<h2>Test de création d'un document</h2>";

try {
    $documentModel = new Document($db);
    
    // Données de test
    $testData = [
        'title' => 'Document de test',
        'file_path' => 'uploads/documents/test_document.pdf',
        'file_type' => 'application/pdf',
        'file_size' => 12345,
        'type' => 'report', // Un type valide selon l'enum de la table
        'user_id' => $_SESSION['user_id'] ?? 1,
        'status' => 'submitted' // Un statut valide selon l'enum de la table
    ];
    
    echo "Tentative de création d'un document avec les données suivantes :<br>";
    echo "<pre>" . print_r($testData, true) . "</pre>";
    
    // Activer le mode d'erreur exception pour PDO
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Créer le document
    $documentId = $documentModel->create($testData);
    
    if ($documentId) {
        echo "<p style='color: green;'>Document créé avec succès, ID: " . $documentId . "</p>";
        
        // Récupérer le document créé
        $document = $documentModel->getById($documentId);
        echo "<pre>Document créé : " . print_r($document, true) . "</pre>";
        
        // Supprimer le document de test
        $documentModel->delete($documentId);
        echo "<p style='color: blue;'>Document de test supprimé</p>";
    } else {
        echo "<p style='color: red;'>Échec de la création du document</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Exception: " . $e->getMessage() . "</p>";
    echo "<pre>Trace: " . $e->getTraceAsString() . "</pre>";
}

// Vérifier la structure de la table documents
echo "<h2>Structure de la table documents</h2>";
try {
    $query = "DESCRIBE documents";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        foreach ($column as $key => $value) {
            echo "<td>" . htmlspecialchars($value ?? '') . "</td>";
        }
        echo "</tr>";
    }
    
    echo "</table>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Erreur lors de la récupération de la structure de la table: " . $e->getMessage() . "</p>";
}

// Vérifier si l'ID est auto-incrémenté
echo "<h2>Vérification de l'auto-incrémentation de l'ID</h2>";
try {
    $query = "SHOW CREATE TABLE documents";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result && isset($result['Create Table'])) {
        echo "<pre>" . htmlspecialchars($result['Create Table']) . "</pre>";
        
        // Vérifier si AUTO_INCREMENT est présent
        if (strpos($result['Create Table'], 'AUTO_INCREMENT') === false) {
            echo "<p style='color: red;'>L'ID n'est pas configuré en AUTO_INCREMENT !</p>";
        } else {
            echo "<p style='color: green;'>L'ID est configuré en AUTO_INCREMENT.</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Erreur lors de la vérification de l'auto-incrémentation: " . $e->getMessage() . "</p>";
}