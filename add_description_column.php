<?php
/**
 * Script pour ajouter la colonne description à la table documents
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/includes/init.php';

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Ajout de la colonne 'description' à la table documents</h1>";

// Vérifier si la colonne description existe déjà
try {
    $query = "DESCRIBE documents";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasDescriptionColumn = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'description') {
            $hasDescriptionColumn = true;
            break;
        }
    }
    
    echo "<h2>Structure actuelle de la table documents</h2>";
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
    
    if ($hasDescriptionColumn) {
        echo "<p style='color: green;'>La colonne 'description' existe déjà dans la table documents.</p>";
    } else {
        echo "<p style='color: red;'>La colonne 'description' n'existe pas dans la table documents.</p>";
        
        // Ajouter la colonne description
        try {
            $query = "ALTER TABLE `documents` ADD COLUMN `description` TEXT NULL AFTER `title`";
            $result = $db->exec($query);
            echo "<p style='color: green;'>La colonne 'description' a été ajoutée avec succès.</p>";
            
            // Vérifier que la colonne a bien été ajoutée
            $query = "DESCRIBE documents";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $columnsAfter = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h2>Nouvelle structure de la table documents</h2>";
            echo "<table border='1'>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
            
            foreach ($columnsAfter as $column) {
                echo "<tr>";
                foreach ($column as $key => $value) {
                    echo "<td>" . htmlspecialchars($value ?? '') . "</td>";
                }
                echo "</tr>";
            }
            
            echo "</table>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>Erreur lors de l'ajout de la colonne 'description': " . $e->getMessage() . "</p>";
        }
    }
    
    // Tester l'insertion d'un document
    echo "<h2>Test d'insertion d'un document</h2>";
    
    try {
        $documentModel = new Document($db);
        
        // Données de test
        $testData = [
            'title' => 'Document de test après ajout de la colonne description',
            'description' => 'Ceci est une description de test',
            'file_path' => 'uploads/documents/test_document_after_fix.pdf',
            'file_type' => 'application/pdf',
            'file_size' => 12345,
            'type' => 'report',
            'user_id' => 1,
            'status' => 'submitted'
        ];
        
        echo "<p>Tentative de création d'un document avec les données suivantes :</p>";
        echo "<pre>" . print_r($testData, true) . "</pre>";
        
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
            echo "<p>Vérifiez les logs d'erreur PHP pour plus de détails.</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Exception: " . $e->getMessage() . "</p>";
        echo "<pre>Trace: " . $e->getTraceAsString() . "</pre>";
    }
    
    echo "<p><a href='test_document_upload.php'>Tester l'upload de document</a></p>";
    echo "<p><a href='views/student/documents.php'>Retour à la page de documents étudiant</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Erreur lors de la vérification de la structure de la table: " . $e->getMessage() . "</p>";
}
?>