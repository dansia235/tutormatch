<?php
// Test rapide de génération de quelques documents
echo "<!DOCTYPE html><html><head><title>Quick Document Test</title></head><body>";
echo "<h1>🚀 Test Rapide Documents</h1>";

function logProgress($message) {
    echo "<p>" . date('H:i:s') . " - " . $message . "</p>";
    flush();
    ob_flush();
}

try {
    $db = new PDO("mysql:host=localhost;dbname=tutoring_system;charset=utf8", 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    logProgress("✅ Connexion réussie");
    
    // Créer le dossier
    $docsDir = __DIR__ . '/uploads/documents/';
    if (!file_exists($docsDir)) {
        mkdir($docsDir, 0777, true);
        logProgress("✅ Dossier créé");
    }
    
    // Vérifier les affectations
    $stmt = $db->query("SELECT COUNT(*) as count FROM assignments");
    $assignmentCount = $stmt->fetch()['count'];
    logProgress("📋 Affectations disponibles: " . $assignmentCount);
    
    if ($assignmentCount == 0) {
        logProgress("❌ Aucune affectation - exécutez reset_full_system.php d'abord");
        exit;
    }
    
    // Récupérer une affectation pour test
    $stmt = $db->query("SELECT a.id as assignment_id, a.student_id, a.teacher_id FROM assignments a LIMIT 1");
    $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    logProgress("🎯 Test avec affectation " . $assignment['assignment_id']);
    
    // Créer 3 documents de test
    $testDocs = [
        ['Contrat de Stage Test', 'contrat_test', 'md'],
        ['Rapport Stage Test', 'rapport_test', 'md'],
        ['Évaluation Test', 'eval_test', 'md']
    ];
    
    $successCount = 0;
    
    foreach ($testDocs as $doc) {
        $fileName = strtolower(str_replace(' ', '_', $doc[0])) . '_' . time() . '_' . rand(100, 999) . '.' . $doc[2];
        $filePath = $docsDir . $fileName;
        
        $content = "# " . $doc[0] . "\n\n";
        $content .= "**Informations:**\n";
        $content .= "- Étudiant: " . $assignment['student_id'] . "\n";
        $content .= "- Tuteur: " . $assignment['teacher_id'] . "\n";
        $content .= "- Date: " . date('Y-m-d H:i:s') . "\n\n";
        $content .= "## Contenu\n\n";
        $content .= "Ceci est un document de test généré pour vérifier le système.\n\n";
        $content .= "### Détails\n\n";
        $content .= "- Type: " . $doc[1] . "\n";
        $content .= "- Statut: ✅ Validé\n";
        
        if (file_put_contents($filePath, $content)) {
            logProgress("✅ Fichier créé: " . $fileName);
            
            // Insérer en base
            try {
                // La table documents utilise user_id, pas student_id/teacher_id
                $stmt = $db->prepare("INSERT INTO documents (user_id, assignment_id, title, file_path, file_type, type, status, visibility) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $assignment['student_id'], // user_id sera l'étudiant
                    $assignment['assignment_id'],
                    $doc[0],
                    'uploads/documents/' . $fileName,
                    $doc[2], // file_type (md)
                    'other', // type enum
                    'approved', // status
                    'private' // visibility
                ]);
                logProgress("✅ Document inséré en base");
                $successCount++;
            } catch (Exception $e) {
                logProgress("❌ Erreur insertion: " . $e->getMessage());
            }
        } else {
            logProgress("❌ Impossible de créer le fichier");
        }
    }
    
    logProgress("🎉 Test terminé - $successCount/3 documents créés avec succès");
    
    // Vérification finale
    $stmt = $db->query("SELECT COUNT(*) as count FROM documents");
    $totalDocs = $stmt->fetch()['count'];
    logProgress("📊 Total documents en base: " . $totalDocs);
    
    $files = scandir($docsDir);
    $files = array_diff($files, ['.', '..', '.htaccess', 'index.php']);
    logProgress("📁 Fichiers physiques: " . count($files));
    
    if ($successCount > 0) {
        logProgress("✅ Génération de documents fonctionne !");
        logProgress("➡️ Vous pouvez maintenant exécuter reset_full_system.php");
    }
    
} catch (Exception $e) {
    logProgress("❌ Erreur: " . $e->getMessage());
}

echo "</body></html>";
?>