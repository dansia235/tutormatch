<?php
/**
 * Script de dépannage pour l'insertion de documents
 * Ce script tente d'insérer un document directement via SQL pour identifier les problèmes potentiels
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/includes/init.php';

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Activer le mode d'erreur exception pour PDO
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "<h1>Dépannage pour l'insertion de documents</h1>";

// Étape 1: Vérifier la structure de la table
echo "<h2>1. Structure de la table documents</h2>";
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

// Étape 2: Vérifier si l'ID est auto-incrémenté
echo "<h2>2. Vérification de l'auto-incrémentation de l'ID</h2>";
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
            
            // Tenter de corriger l'auto-incrémentation
            try {
                // Vérifier si la colonne id est déjà une clé primaire
                $hasPrimaryKey = false;
                foreach ($columns as $column) {
                    if ($column['Field'] === 'id' && $column['Key'] === 'PRI') {
                        $hasPrimaryKey = true;
                        break;
                    }
                }
                
                // Modifier la colonne ID pour y ajouter AUTO_INCREMENT
                if ($hasPrimaryKey) {
                    $query = "ALTER TABLE `documents` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;";
                } else {
                    $query = "ALTER TABLE `documents` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (`id`);";
                }
                
                echo "<p>Tentative de correction avec la requête: " . htmlspecialchars($query) . "</p>";
                $result = $db->exec($query);
                echo "<p style='color: green;'>Correction réussie. Lignes affectées: " . $result . "</p>";
            } catch (Exception $e) {
                echo "<p style='color: red;'>Erreur lors de la correction de l'auto-incrémentation: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p style='color: green;'>L'ID est configuré en AUTO_INCREMENT.</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Erreur lors de la vérification de l'auto-incrémentation: " . $e->getMessage() . "</p>";
}

// Étape 3: Tester une insertion directe via SQL
echo "<h2>3. Test d'insertion directe via SQL</h2>";
try {
    // Supprimer le document de test s'il existe déjà (pour éviter les doublons)
    $query = "DELETE FROM documents WHERE title = 'Document de test SQL'";
    $db->exec($query);
    
    // Créer un document de test avec insertion SQL directe
    $query = "INSERT INTO documents (title, file_path, file_type, file_size, type, user_id, status)
              VALUES ('Document de test SQL', 'uploads/documents/test_sql.pdf', 'application/pdf', 12345, 'report', 1, 'submitted')";
    
    echo "<p>Requête SQL d'insertion: " . htmlspecialchars($query) . "</p>";
    
    $result = $db->exec($query);
    echo "<p style='color: green;'>Insertion réussie. Lignes affectées: " . $result . "</p>";
    
    // Récupérer l'ID du document inséré
    $lastId = $db->lastInsertId();
    echo "<p style='color: green;'>ID du document inséré: " . $lastId . "</p>";
    
    // Vérifier que le document a bien été inséré
    $query = "SELECT * FROM documents WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $lastId);
    $stmt->execute();
    $document = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($document) {
        echo "<p style='color: green;'>Document récupéré avec succès:</p>";
        echo "<pre>" . print_r($document, true) . "</pre>";
    } else {
        echo "<p style='color: red;'>Impossible de récupérer le document inséré!</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Erreur lors de l'insertion directe: " . $e->getMessage() . "</p>";
}

// Étape 4: Tester l'insertion via le modèle Document
echo "<h2>4. Test d'insertion via le modèle Document</h2>";
try {
    $documentModel = new Document($db);
    
    // Données de test
    $testData = [
        'title' => 'Document de test via modèle',
        'file_path' => 'uploads/documents/test_model.pdf',
        'file_type' => 'application/pdf',
        'file_size' => 12345,
        'type' => 'report',
        'user_id' => 1,
        'status' => 'submitted'
    ];
    
    echo "<p>Données pour l'insertion via modèle:</p>";
    echo "<pre>" . print_r($testData, true) . "</pre>";
    
    // Ajouter une journalisation détaillée
    error_log("Tentative de création d'un document via le modèle: " . json_encode($testData));
    
    // Créer le document
    $documentId = $documentModel->create($testData);
    
    if ($documentId) {
        echo "<p style='color: green;'>Document créé avec succès via le modèle. ID: " . $documentId . "</p>";
        
        // Récupérer le document créé
        $document = $documentModel->getById($documentId);
        echo "<pre>Document récupéré: " . print_r($document, true) . "</pre>";
    } else {
        echo "<p style='color: red;'>Échec de la création du document via le modèle</p>";
        
        // Vérifier si des erreurs ont été enregistrées
        echo "<p>Vérifiez les logs d'erreur PHP pour plus de détails.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Exception lors de la création via modèle: " . $e->getMessage() . "</p>";
    echo "<pre>Trace: " . $e->getTraceAsString() . "</pre>";
}

// Étape 5: Vérifier les colonnes manquantes et ajouter celles qui sont nécessaires
echo "<h2>5. Vérification des colonnes nécessaires</h2>";

// Colonnes requises par le modèle Document
$requiredColumns = ['id', 'user_id', 'title', 'type', 'file_path', 'file_type', 'file_size', 'status'];

// Vérifier chaque colonne requise
$missingColumns = [];
$existingColumns = [];

foreach ($columns as $column) {
    $existingColumns[] = $column['Field'];
}

foreach ($requiredColumns as $requiredColumn) {
    if (!in_array($requiredColumn, $existingColumns)) {
        $missingColumns[] = $requiredColumn;
    }
}

if (empty($missingColumns)) {
    echo "<p style='color: green;'>Toutes les colonnes requises sont présentes dans la table.</p>";
} else {
    echo "<p style='color: red;'>Colonnes manquantes: " . implode(", ", $missingColumns) . "</p>";
    
    // Tenter d'ajouter les colonnes manquantes
    foreach ($missingColumns as $missingColumn) {
        try {
            $dataType = "";
            switch ($missingColumn) {
                case 'file_type':
                    $dataType = "VARCHAR(100) DEFAULT NULL";
                    break;
                case 'file_size':
                    $dataType = "INT(11) DEFAULT NULL";
                    break;
                case 'status':
                    $dataType = "ENUM('draft','submitted','approved','rejected') NOT NULL DEFAULT 'draft'";
                    break;
                default:
                    $dataType = "VARCHAR(255) DEFAULT NULL";
                    break;
            }
            
            $query = "ALTER TABLE `documents` ADD COLUMN `$missingColumn` $dataType";
            echo "<p>Tentative d'ajout de la colonne $missingColumn avec la requête: " . htmlspecialchars($query) . "</p>";
            
            $result = $db->exec($query);
            echo "<p style='color: green;'>Colonne $missingColumn ajoutée avec succès.</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>Erreur lors de l'ajout de la colonne $missingColumn: " . $e->getMessage() . "</p>";
        }
    }
}

echo "<p><a href='test_document_upload.php'>Retour au formulaire de test</a></p>";
?>