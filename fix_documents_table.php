<?php
/**
 * Script pour corriger la structure de la table documents
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/includes/init.php';

// Vérifier si l'utilisateur est administrateur
if (!isLoggedIn() || !hasRole('admin')) {
    die("Accès refusé. Vous devez être connecté en tant qu'administrateur pour exécuter ce script.");
}

// Afficher un message d'introduction
echo "<h1>Correction de la structure de la table documents</h1>";
echo "<p>Ce script va corriger la structure de la table documents pour permettre le téléversement de fichiers.</p>";

// Fonction pour exécuter une requête SQL avec gestion des erreurs
function executeQuery($db, $query, $description) {
    echo "<h3>$description</h3>";
    echo "<pre>$query</pre>";
    
    try {
        $result = $db->exec($query);
        echo "<p style='color: green;'>Requête exécutée avec succès. Lignes affectées : $result</p>";
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

// Exécuter les corrections
$success = true;

// Commencer une transaction si possible
try {
    $db->beginTransaction();
    $transactionStarted = true;
} catch (PDOException $e) {
    echo "<p style='color: orange;'>Avertissement: Impossible de démarrer une transaction. Les modifications seront appliquées directement. Erreur: " . $e->getMessage() . "</p>";
    $transactionStarted = false;
}

// 1. Vérifier si la colonne id est déjà AUTO_INCREMENT
$isAutoIncrement = false;
try {
    $query = "SHOW CREATE TABLE documents";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result && isset($result['Create Table'])) {
        $isAutoIncrement = strpos($result['Create Table'], 'AUTO_INCREMENT') !== false;
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Erreur lors de la vérification de l'auto-incrémentation: " . $e->getMessage() . "</p>";
}

// 2. Ajouter AUTO_INCREMENT si nécessaire
if (!$isAutoIncrement) {
    $success = $success && executeQuery($db, 
        "ALTER TABLE `documents` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;",
        "Ajout de AUTO_INCREMENT à la colonne id"
    );
}

// 3. Vérifier si la clé primaire existe
$hasPrimaryKey = false;
foreach ($columns as $column) {
    if ($column['Field'] === 'id' && $column['Key'] === 'PRI') {
        $hasPrimaryKey = true;
        break;
    }
}

// 4. Ajouter la clé primaire si nécessaire
if (!$hasPrimaryKey) {
    $success = $success && executeQuery($db, 
        "ALTER TABLE `documents` ADD PRIMARY KEY (`id`);",
        "Ajout de la clé primaire sur la colonne id"
    );
}

// 5. Vérifier si les colonnes file_type et file_size existent
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

// 6. Ajouter les colonnes manquantes
if (!$hasFileType) {
    $success = $success && executeQuery($db, 
        "ALTER TABLE `documents` ADD COLUMN `file_type` varchar(100) DEFAULT NULL AFTER `file_path`;",
        "Ajout de la colonne file_type"
    );
}

if (!$hasFileSize) {
    $success = $success && executeQuery($db, 
        "ALTER TABLE `documents` ADD COLUMN `file_size` int(11) DEFAULT NULL AFTER `file_type`;",
        "Ajout de la colonne file_size"
    );
}

// 7. Vérifier et ajouter des index pour améliorer les performances
echo "<h3>Vérification et ajout des index</h3>";
try {
    // Récupérer les index existants
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
        executeQuery($db, "ALTER TABLE `documents` ADD INDEX `idx_user_id` (`user_id`);", "Ajout de l'index sur user_id");
    } else {
        echo "<p style='color: blue;'>L'index sur user_id existe déjà.</p>";
    }
    
    // Ajouter index assignment_id si nécessaire
    if (!in_array('idx_assignment_id', $existingIndexes)) {
        executeQuery($db, "ALTER TABLE `documents` ADD INDEX `idx_assignment_id` (`assignment_id`);", "Ajout de l'index sur assignment_id");
    } else {
        echo "<p style='color: blue;'>L'index sur assignment_id existe déjà.</p>";
    }
    
    // Ajouter index type si nécessaire
    if (!in_array('idx_type', $existingIndexes)) {
        executeQuery($db, "ALTER TABLE `documents` ADD INDEX `idx_type` (`type`);", "Ajout de l'index sur type");
    } else {
        echo "<p style='color: blue;'>L'index sur type existe déjà.</p>";
    }
    
    // Ajouter index status si nécessaire
    if (!in_array('idx_status', $existingIndexes)) {
        executeQuery($db, "ALTER TABLE `documents` ADD INDEX `idx_status` (`status`);", "Ajout de l'index sur status");
    } else {
        echo "<p style='color: blue;'>L'index sur status existe déjà.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Erreur lors de la vérification des index: " . $e->getMessage() . "</p>";
    $success = false;
}

// 8. Mettre à jour AUTO_INCREMENT à une valeur supérieure aux ID existants
$success = $success && executeQuery($db, 
    "ALTER TABLE `documents` AUTO_INCREMENT = 1000;",
    "Mise à jour de la valeur AUTO_INCREMENT"
);

// Valider ou annuler la transaction selon le résultat (si une transaction a été démarrée)
if ($transactionStarted) {
    if ($success) {
        $db->commit();
        echo "<h2 style='color: green;'>Toutes les corrections ont été appliquées avec succès !</h2>";
    } else {
        $db->rollBack();
        echo "<h2 style='color: red;'>Des erreurs se sont produites. Aucune modification n'a été appliquée.</h2>";
    }
} else {
    if ($success) {
        echo "<h2 style='color: green;'>Toutes les corrections ont été appliquées avec succès (sans transaction) !</h2>";
    } else {
        echo "<h2 style='color: orange;'>Des erreurs se sont produites, mais certaines modifications ont pu être appliquées (sans transaction).</h2>";
    }
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

// Test de création d'un document
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