<?php
echo "<!DOCTYPE html><html><head><title>Test Document Generation</title></head><body>";
echo "<h1>🧪 Test Génération Documents</h1>";

function logProgress($message) {
    echo "<p>" . date('H:i:s') . " - " . $message . "</p>";
    flush();
    ob_flush();
}

try {
    $db = new PDO("mysql:host=localhost;dbname=tutoring_system;charset=utf8", 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    logProgress("✅ Connexion à la base de données réussie");
    
    // Vérifier le dossier documents
    $docsDir = __DIR__ . '/uploads/documents/';
    logProgress("📁 Vérification dossier: " . $docsDir);
    
    if (!file_exists($docsDir)) {
        if (mkdir($docsDir, 0777, true)) {
            logProgress("✅ Dossier créé avec succès");
        } else {
            logProgress("❌ Impossible de créer le dossier");
            exit;
        }
    } else {
        logProgress("✅ Dossier existe déjà");
    }
    
    // Vérifier les permissions
    if (is_writable($docsDir)) {
        logProgress("✅ Dossier accessible en écriture");
    } else {
        logProgress("❌ Dossier non accessible en écriture");
        exit;
    }
    
    // Vérifier la structure de la table documents
    $stmt = $db->query("DESCRIBE documents");
    $docColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    logProgress("📋 Colonnes table documents: " . implode(', ', $docColumns));
    
    // Vérifier s'il y a des affectations
    $stmt = $db->query("SELECT COUNT(*) as count FROM assignments");
    $assignmentCount = $stmt->fetch()['count'];
    logProgress("📋 Nombre d'affectations: " . $assignmentCount);
    
    if ($assignmentCount == 0) {
        logProgress("❌ Aucune affectation trouvée. Exécutez d'abord reset_full_system.php");
        exit;
    }
    
    // Récupérer quelques affectations pour test
    $stmt = $db->query("SELECT a.id as assignment_id, a.student_id, a.teacher_id, a.status FROM assignments a LIMIT 3");
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    logProgress("🎯 Test avec " . count($assignments) . " affectations");
    
    $documentCount = 0;
    
    foreach ($assignments as $assignment) {
        logProgress("📄 Test document pour affectation " . $assignment['assignment_id']);
        
        // Créer un document test simple
        $docTitle = "Test Document Stage";
        $docType = "test_document";
        $fileName = "test_" . $assignment['student_id'] . "_" . time() . "_" . rand(100, 999) . ".md";
        $filePath = $docsDir . $fileName;
        
        // Contenu simple
        $content = "# " . $docTitle . "\n\n";
        $content .= "**Informations:**\n";
        $content .= "- Étudiant ID: " . $assignment['student_id'] . "\n";
        $content .= "- Tuteur ID: " . $assignment['teacher_id'] . "\n";
        $content .= "- Date: " . date('Y-m-d H:i:s') . "\n\n";
        $content .= "## Contenu\n\n";
        $content .= "Ceci est un document de test généré automatiquement pour vérifier le système de génération de documents.\n\n";
        $content .= "### Détails du stage\n\n";
        $content .= "- Statut: " . $assignment['status'] . "\n";
        $content .= "- Référence: " . $assignment['assignment_id'] . "\n";
        
        // Écrire le fichier
        if (file_put_contents($filePath, $content)) {
            logProgress("✅ Fichier créé: " . $fileName);
            
            // Insérer en base
            try {
                if (in_array('assignment_id', $docColumns)) {
                    $stmt = $db->prepare("INSERT INTO documents (assignment_id, student_id, teacher_id, title, file_path, file_type) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$assignment['assignment_id'], $assignment['student_id'], $assignment['teacher_id'], $docTitle, 'uploads/documents/' . $fileName, $docType]);
                } else {
                    $stmt = $db->prepare("INSERT INTO documents (student_id, teacher_id, title, file_path, file_type) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$assignment['student_id'], $assignment['teacher_id'], $docTitle, 'uploads/documents/' . $fileName, $docType]);
                }
                
                logProgress("✅ Document inséré en base");
                $documentCount++;
                
            } catch (Exception $e) {
                logProgress("❌ Erreur insertion base: " . $e->getMessage());
            }
            
        } else {
            logProgress("❌ Impossible de créer le fichier: " . $fileName);
        }
        
        // Arrêter après le premier pour test
        break;
    }
    
    logProgress("🎉 Test terminé - $documentCount documents créés");
    
    // Vérifier le résultat
    $stmt = $db->query("SELECT COUNT(*) as count FROM documents");
    $totalDocs = $stmt->fetch()['count'];
    logProgress("📊 Total documents en base: " . $totalDocs);
    
    // Lister les fichiers créés
    $files = scandir($docsDir);
    $files = array_diff($files, ['.', '..', '.htaccess', 'index.php']);
    logProgress("📁 Fichiers dans uploads/documents: " . count($files));
    
    if (count($files) > 0) {
        logProgress("📄 Derniers fichiers créés:");
        foreach (array_slice($files, -3) as $file) {
            logProgress("  - " . $file);
        }
    }
    
} catch (Exception $e) {
    logProgress("❌ Erreur: " . $e->getMessage());
    logProgress("📍 Ligne: " . $e->getLine());
    logProgress("📄 Fichier: " . $e->getFile());
}

echo "</body></html>";
?>