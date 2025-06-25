<?php
try {
    $db = new PDO("mysql:host=localhost;dbname=tutoring_system;charset=utf8", 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>🔍 Debug Documents</h1>";
    
    // 1. Vérifier la structure de la table documents
    echo "<h2>📋 Structure table documents</h2>";
    try {
        $stmt = $db->query("DESCRIBE documents");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td><strong>" . $column['Field'] . "</strong></td>";
            echo "<td>" . $column['Type'] . "</td>";
            echo "<td>" . $column['Null'] . "</td>";
            echo "<td>" . $column['Key'] . "</td>";
            echo "<td>" . ($column['Default'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        $docColumns = array_column($columns, 'Field');
        echo "<p><strong>Colonnes détectées:</strong> " . implode(', ', $docColumns) . "</p>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Erreur table documents: " . $e->getMessage() . "</p>";
    }
    
    // 2. Vérifier le contenu actuel
    echo "<h2>📄 Contenu table documents</h2>";
    try {
        $stmt = $db->query("SELECT COUNT(*) as count FROM documents");
        $count = $stmt->fetch()['count'];
        echo "<p><strong>Nombre de documents:</strong> $count</p>";
        
        if ($count > 0) {
            $stmt = $db->query("SELECT * FROM documents LIMIT 5");
            $docs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table border='1'>";
            echo "<tr><th>ID</th><th>Title</th><th>File Path</th><th>Student ID</th><th>Teacher ID</th></tr>";
            foreach ($docs as $doc) {
                echo "<tr>";
                echo "<td>" . $doc['id'] . "</td>";
                echo "<td>" . $doc['title'] . "</td>";
                echo "<td>" . $doc['file_path'] . "</td>";
                echo "<td>" . ($doc['student_id'] ?? 'NULL') . "</td>";
                echo "<td>" . ($doc['teacher_id'] ?? 'NULL') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Erreur lecture documents: " . $e->getMessage() . "</p>";
    }
    
    // 3. Vérifier les affectations
    echo "<h2>📋 Affectations disponibles</h2>";
    try {
        $stmt = $db->query("SELECT COUNT(*) as count FROM assignments");
        $assignmentCount = $stmt->fetch()['count'];
        echo "<p><strong>Nombre d'affectations:</strong> $assignmentCount</p>";
        
        if ($assignmentCount > 0) {
            $stmt = $db->query("SELECT a.id, a.student_id, a.teacher_id, a.status FROM assignments a LIMIT 5");
            $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table border='1'>";
            echo "<tr><th>Assignment ID</th><th>Student ID</th><th>Teacher ID</th><th>Status</th></tr>";
            foreach ($assignments as $assignment) {
                echo "<tr>";
                echo "<td>" . $assignment['id'] . "</td>";
                echo "<td>" . $assignment['student_id'] . "</td>";
                echo "<td>" . $assignment['teacher_id'] . "</td>";
                echo "<td>" . $assignment['status'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Erreur lecture assignments: " . $e->getMessage() . "</p>";
    }
    
    // 4. Vérifier le dossier uploads/documents
    echo "<h2>📁 Contenu dossier uploads/documents</h2>";
    $uploadsDir = 'uploads/documents/';
    if (!is_dir($uploadsDir)) {
        echo "<p style='color: orange;'>⚠️ Dossier n'existe pas, création...</p>";
        if (!mkdir($uploadsDir, 0777, true)) {
            echo "<p style='color: red;'>❌ Impossible de créer le dossier</p>";
        } else {
            echo "<p style='color: green;'>✅ Dossier créé</p>";
        }
    } else {
        $files = scandir($uploadsDir);
        $files = array_diff($files, ['.', '..', '.htaccess', 'index.php']);
        echo "<p><strong>Fichiers dans uploads/documents:</strong> " . count($files) . "</p>";
        
        if (count($files) > 0) {
            echo "<ul>";
            foreach (array_slice($files, 0, 10) as $file) {
                echo "<li>$file</li>";
            }
            echo "</ul>";
        }
    }
    
    // 5. Test simple de création de document
    echo "<h2>🧪 Test création document</h2>";
    try {
        $testFile = $uploadsDir . 'test_document_' . time() . '.md';
        $testContent = "# Test Document\n\nCeci est un test de création de document.\n\nDate: " . date('Y-m-d H:i:s');
        
        if (file_put_contents($testFile, $testContent)) {
            echo "<p style='color: green;'>✅ Fichier test créé: " . basename($testFile) . "</p>";
            
            // Tenter d'insérer en base
            if ($assignmentCount > 0) {
                $stmt = $db->query("SELECT id, student_id, teacher_id FROM assignments LIMIT 1");
                $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $insertQuery = "INSERT INTO documents (student_id, teacher_id, title, file_path, file_type) VALUES (?, ?, ?, ?, ?)";
                $stmt = $db->prepare($insertQuery);
                
                if ($stmt->execute([$assignment['student_id'], $assignment['teacher_id'], 'Test Document', 'uploads/documents/' . basename($testFile), 'test'])) {
                    echo "<p style='color: green;'>✅ Document inséré en base avec succès</p>";
                } else {
                    echo "<p style='color: red;'>❌ Échec insertion en base</p>";
                }
            } else {
                echo "<p style='color: orange;'>⚠️ Pas d'affectations disponibles pour le test</p>";
            }
            
        } else {
            echo "<p style='color: red;'>❌ Échec création fichier test</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Erreur test: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur connexion: " . $e->getMessage() . "</p>";
}
?>