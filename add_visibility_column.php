<?php
/**
 * Script pour ajouter la colonne visibility à la table documents
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/includes/init.php';

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Ajout de la colonne 'visibility' à la table documents</h1>";

// Vérifier si la colonne visibility existe déjà
try {
    $query = "DESCRIBE documents";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasVisibilityColumn = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'visibility') {
            $hasVisibilityColumn = true;
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
    
    if ($hasVisibilityColumn) {
        echo "<p style='color: green;'>La colonne 'visibility' existe déjà dans la table documents.</p>";
    } else {
        echo "<p style='color: red;'>La colonne 'visibility' n'existe pas dans la table documents.</p>";
        
        // Ajouter la colonne visibility
        try {
            $query = "ALTER TABLE `documents` ADD COLUMN `visibility` ENUM('private', 'restricted', 'public') NOT NULL DEFAULT 'private' AFTER `status`";
            $result = $db->exec($query);
            echo "<p style='color: green;'>La colonne 'visibility' a été ajoutée avec succès.</p>";
            
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
            echo "<p style='color: red;'>Erreur lors de l'ajout de la colonne 'visibility': " . $e->getMessage() . "</p>";
        }
    }
    
    // Tester l'édition d'un document
    echo "<h2>Test de mise à jour d'un document</h2>";
    
    try {
        $documentModel = new Document($db);
        
        // Récupérer un document existant pour le test
        $query = "SELECT id FROM documents LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $testDocId = $stmt->fetchColumn();
        
        if ($testDocId) {
            // Données de test pour la mise à jour
            $testData = [
                'title' => 'Document test mis à jour',
                'description' => 'Description mise à jour',
                'visibility' => 'public'  // Tester la nouvelle colonne
            ];
            
            echo "<p>Tentative de mise à jour du document ID $testDocId avec les données suivantes :</p>";
            echo "<pre>" . print_r($testData, true) . "</pre>";
            
            // Mettre à jour le document
            $success = $documentModel->update($testDocId, $testData);
            
            if ($success) {
                echo "<p style='color: green;'>Document mis à jour avec succès, ID: " . $testDocId . "</p>";
                
                // Récupérer le document mis à jour
                $document = $documentModel->getById($testDocId);
                echo "<pre>Document mis à jour : " . print_r($document, true) . "</pre>";
            } else {
                echo "<p style='color: red;'>Échec de la mise à jour du document</p>";
                echo "<p>Vérifiez les logs d'erreur PHP pour plus de détails.</p>";
            }
        } else {
            echo "<p style='color: orange;'>Aucun document trouvé pour le test de mise à jour.</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Exception: " . $e->getMessage() . "</p>";
        echo "<pre>Trace: " . $e->getTraceAsString() . "</pre>";
    }
    
    echo "<p><a href='views/admin/documents/index.php'>Retour à la liste des documents</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Erreur lors de la vérification de la structure de la table: " . $e->getMessage() . "</p>";
}
?>