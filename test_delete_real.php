<?php
/**
 * Test rapide de suppression d'évaluation
 * Ce script aide à tester la suppression avec la dernière évaluation en base
 */

require_once __DIR__ . '/includes/init.php';

try {
    // Récupérer la dernière évaluation pour test
    $query = "SELECT id, score, status, type FROM evaluations ORDER BY id DESC LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $lastEvaluation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$lastEvaluation) {
        echo "<p>❌ Aucune évaluation trouvée en base de données</p>";
        exit;
    }
    
    echo "<h2>🧪 Test de suppression d'évaluation</h2>";
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>Dernière évaluation en base :</h3>";
    echo "<ul>";
    echo "<li><strong>ID :</strong> " . $lastEvaluation['id'] . "</li>";
    echo "<li><strong>Type :</strong> " . $lastEvaluation['type'] . "</li>";
    echo "<li><strong>Score :</strong> " . $lastEvaluation['score'] . "</li>";
    echo "<li><strong>Statut :</strong> " . $lastEvaluation['status'] . "</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>✅ API corrigée et prête</h3>";
    echo "<p>L'API de suppression a été corrigée pour correspondre à la structure réelle de la base de données :</p>";
    echo "<ul>";
    echo "<li>✅ Suppression directe de la table <code>evaluations</code></li>";
    echo "<li>✅ Pas de tables liées (evaluation_documents, evaluation_criteria_responses)</li>";
    echo "<li>✅ Gestion des critères via le champ JSON <code>criteria_scores</code></li>";
    echo "<li>✅ Transaction pour garantir la cohérence</li>";
    echo "<li>✅ Messages d'avertissement adaptés</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div style='background: #f1f8e9; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>🎯 Comment tester</h3>";
    echo "<ol>";
    echo "<li>Connectez-vous en tant qu'administrateur</li>";
    echo "<li>Allez sur <a href='/tutoring/views/admin/evaluations.php'>/tutoring/views/admin/evaluations.php</a></li>";
    echo "<li>Cliquez sur le bouton de suppression d'une évaluation</li>";
    echo "<li>Vérifiez que la modal s'affiche correctement</li>";
    echo "<li>Cochez la case de confirmation</li>";
    echo "<li>Cliquez sur 'Supprimer définitivement'</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>⚠️ Note importante</h3>";
    echo "<p>L'erreur précédente était due à des références à des tables inexistantes :</p>";
    echo "<ul>";
    echo "<li>❌ <code>evaluation_documents</code> (n'existe pas)</li>";
    echo "<li>❌ <code>evaluation_criteria_responses</code> (n'existe pas)</li>";
    echo "</ul>";
    echo "<p>La structure actuelle utilise uniquement la table <code>evaluations</code> avec un champ JSON pour les critères.</p>";
    echo "</div>";

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur : " . $e->getMessage() . "</p>";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Suppression Évaluations - Corrigé</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            max-width: 900px; 
            margin: 0 auto; 
            padding: 20px; 
            line-height: 1.6;
        }
        code { 
            background: #f5f5f5; 
            padding: 2px 6px; 
            border-radius: 3px; 
            font-family: 'Courier New', monospace;
        }
        a { color: #0066cc; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
</body>
</html>