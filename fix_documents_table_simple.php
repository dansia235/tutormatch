<?php
/**
 * Script simplifié pour corriger la structure de la table documents sans utiliser de transactions
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/includes/init.php';

// Afficher un message d'introduction
echo "<h1>Correction de la structure de la table documents (version simplifiée)</h1>";
echo "<p>Ce script va corriger la structure de la table documents pour permettre le téléversement de fichiers.</p>";

// Fonction pour exécuter une requête SQL avec gestion des erreurs
function executeQuery($db, $query, $description) {
    echo "<h3>$description</h3>";
    echo "<pre>$query</pre>";
    
    try {
        $result = $db->exec($query);
        echo "<p style='color: green;'>Requête exécutée avec succès.</p>";
        return true;
    } catch (PDOException $e) {
        echo "<p style='color: red;'>Erreur lors de l'exécution de la requête : " . $e->getMessage() . "</p>";
        return false;
    }
}

// Vérifier la structure actuelle de la table
echo "<h2>Structure actuelle de la table documents</h2>";
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

// CORRECTION 1: Ajout de PRIMARY KEY et AUTO_INCREMENT
echo "<h2>Étape 1: Ajout de PRIMARY KEY et AUTO_INCREMENT</h2>";
try {
    // Vérifier si la clé primaire existe
    $hasPrimaryKey = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'id' && $column['Key'] === 'PRI') {
            $hasPrimaryKey = true;
            break;
        }
    }
    
    // Modifier la colonne ID pour y ajouter AUTO_INCREMENT
    $query = "ALTER TABLE `documents` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT";
    if ($hasPrimaryKey) {
        $query .= ";";
    } else {
        $query .= ", ADD PRIMARY KEY (`id`);";
    }
    
    $result = $db->exec($query);
    echo "<p style='color: green;'>Modification de la colonne ID réussie.</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Erreur lors de la modification de la colonne ID: " . $e->getMessage() . "</p>";
}

// CORRECTION 2: Vérifier et ajouter les colonnes manquantes
echo "<h2>Étape 2: Ajout des colonnes manquantes</h2>";

// Vérifier si les colonnes file_type et file_size existent
$hasFileType = false;
$hasFileSize = false;
foreach ($columns as $column) {
    if ($column['Field'] === 'file_type') {
        $hasFileType = true;
    }
    if ($column['Field'] === 'file_size') {
        $hasFileSize = true;
    }
}

// Ajouter file_type si nécessaire
if (!$hasFileType) {
    try {
        $query = "ALTER TABLE `documents` ADD COLUMN `file_type` varchar(100) DEFAULT NULL AFTER `file_path`;";
        $result = $db->exec($query);
        echo "<p style='color: green;'>Colonne file_type ajoutée avec succès.</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>Erreur lors de l'ajout de la colonne file_type: " . $e->getMessage() . "</p>";
    }
}

// Ajouter file_size si nécessaire
if (!$hasFileSize) {
    try {
        $query = "ALTER TABLE `documents` ADD COLUMN `file_size` int(11) DEFAULT NULL AFTER `file_type`;";
        $result = $db->exec($query);
        echo "<p style='color: green;'>Colonne file_size ajoutée avec succès.</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>Erreur lors de l'ajout de la colonne file_size: " . $e->getMessage() . "</p>";
    }
}

// CORRECTION 3: Ajouter des index s'ils n'existent pas déjà
echo "<h2>Étape 3: Vérification et ajout des index</h2>";

// Vérifier les index existants
try {
    $query = "SHOW INDEX FROM documents";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Créer un tableau des noms d'index existants
    $existingIndexes = [];
    foreach ($indexes as $index) {
        $existingIndexes[] = $index['Key_name'];
    }
    
    echo "<p>Index existants : " . implode(", ", array_unique($existingIndexes)) . "</p>";
    
    // Ajouter index user_id si nécessaire
    if (!in_array('idx_user_id', $existingIndexes)) {
        try {
            $query = "ALTER TABLE `documents` ADD INDEX `idx_user_id` (`user_id`);";
            $result = $db->exec($query);
            echo "<p style='color: green;'>Index sur user_id ajouté avec succès.</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>Erreur lors de l'ajout de l'index sur user_id: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color: blue;'>L'index sur user_id existe déjà.</p>";
    }
    
    // Ajouter index assignment_id si nécessaire
    if (!in_array('idx_assignment_id', $existingIndexes)) {
        try {
            $query = "ALTER TABLE `documents` ADD INDEX `idx_assignment_id` (`assignment_id`);";
            $result = $db->exec($query);
            echo "<p style='color: green;'>Index sur assignment_id ajouté avec succès.</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>Erreur lors de l'ajout de l'index sur assignment_id: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color: blue;'>L'index sur assignment_id existe déjà.</p>";
    }
    
    // Ajouter index type si nécessaire
    if (!in_array('idx_type', $existingIndexes)) {
        try {
            $query = "ALTER TABLE `documents` ADD INDEX `idx_type` (`type`);";
            $result = $db->exec($query);
            echo "<p style='color: green;'>Index sur type ajouté avec succès.</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>Erreur lors de l'ajout de l'index sur type: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color: blue;'>L'index sur type existe déjà.</p>";
    }
    
    // Ajouter index status si nécessaire
    if (!in_array('idx_status', $existingIndexes)) {
        try {
            $query = "ALTER TABLE `documents` ADD INDEX `idx_status` (`status`);";
            $result = $db->exec($query);
            echo "<p style='color: green;'>Index sur status ajouté avec succès.</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>Erreur lors de l'ajout de l'index sur status: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color: blue;'>L'index sur status existe déjà.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Erreur lors de la vérification des index: " . $e->getMessage() . "</p>";
}

// CORRECTION 4: Mettre à jour AUTO_INCREMENT
echo "<h2>Étape 4: Mise à jour de la valeur AUTO_INCREMENT</h2>";
try {
    $query = "ALTER TABLE `documents` AUTO_INCREMENT = 1000;";
    $result = $db->exec($query);
    echo "<p style='color: green;'>Valeur AUTO_INCREMENT mise à jour avec succès.</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Erreur lors de la mise à jour de AUTO_INCREMENT: " . $e->getMessage() . "</p>";
}

// Vérifier la nouvelle structure de la table
echo "<h2>Nouvelle structure de la table documents</h2>";
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

// Vérifier si AUTO_INCREMENT est maintenant activé
echo "<h2>Vérification finale de AUTO_INCREMENT</h2>";
try {
    $query = "SHOW CREATE TABLE documents";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result && isset($result['Create Table'])) {
        echo "<pre>" . htmlspecialchars($result['Create Table']) . "</pre>";
        
        if (strpos($result['Create Table'], 'AUTO_INCREMENT') !== false) {
            echo "<p style='color: green;'>AUTO_INCREMENT est maintenant activé sur la table documents.</p>";
        } else {
            echo "<p style='color: red;'>AUTO_INCREMENT n'est toujours pas activé sur la table documents.</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Erreur lors de la vérification finale: " . $e->getMessage() . "</p>";
}

echo "<h2>Test de création d'un document</h2>";
try {
    $documentModel = new Document($db);
    
    // Données de test
    $testData = [
        'title' => 'Document de test après correction',
        'file_path' => 'uploads/documents/test_document_after_fix.pdf',
        'file_type' => 'application/pdf',
        'file_size' => 12345,
        'type' => 'report',
        'user_id' => $_SESSION['user_id'] ?? 1,
        'status' => 'submitted'
    ];
    
    echo "Tentative de création d'un document avec les données suivantes :<br>";
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
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Exception: " . $e->getMessage() . "</p>";
    echo "<pre>Trace: " . $e->getTraceAsString() . "</pre>";
}

echo "<p><a href='test_document_upload.php'>Retour au formulaire de test</a></p>";
?>