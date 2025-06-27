<?php
require_once 'includes/init.php';

echo "<h2>Test des scores d'évaluations</h2>";

try {
    // Test de base - existe-t-il des évaluations ?
    $stmt = $db->query("SELECT COUNT(*) as count FROM evaluations");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p><strong>Nombre total d'évaluations:</strong> " . $result['count'] . "</p>";
    
    if ($result['count'] > 0) {
        // Afficher quelques exemples avec scores
        $stmt = $db->query("
            SELECT id, score, status, type, submission_date,
                   CASE WHEN score > 5 THEN ROUND(score / 4, 1) ELSE score END as normalized_score
            FROM evaluations 
            WHERE score IS NOT NULL 
            ORDER BY id ASC 
            LIMIT 10
        ");
        
        echo "<h3>Exemples d'évaluations avec scores:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Score Original</th><th>Score Normalisé</th><th>Status</th><th>Type</th><th>Date</th></tr>";
        
        while ($eval = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $eval['id'] . "</td>";
            echo "<td>" . $eval['score'] . "</td>";
            echo "<td>" . $eval['normalized_score'] . "/5</td>";
            echo "<td>" . $eval['status'] . "</td>";
            echo "<td>" . $eval['type'] . "</td>";
            echo "<td>" . $eval['submission_date'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Test de l'API stats
        echo "<h3>Test API Stats:</h3>";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://localhost/tutoring/api/evaluations/stats.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
        $response = curl_exec($ch);
        curl_close($ch);
        
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
    } else {
        echo "<p style='color: red;'>Aucune évaluation trouvée dans la base de données!</p>";
        
        // Créer quelques évaluations de test
        echo "<h3>Création d'évaluations de test...</h3>";
        
        $testEvaluations = [
            [1, 2, 1, 'mid_term', 16.5, 'completed'], // 16.5/20 = 4.1/5
            [2, 3, 2, 'final', 14.0, 'completed'],    // 14/20 = 3.5/5
            [3, 4, 3, 'teacher', 10.5, 'completed'],  // 10.5/20 = 2.6/5
            [1, 5, 4, 'student', 4.2, 'completed'],   // Déjà sur 5
            [2, 6, 5, 'supervisor', 3.8, 'completed'] // Déjà sur 5
        ];
        
        $insertStmt = $db->prepare("
            INSERT INTO evaluations (evaluator_id, evaluatee_id, assignment_id, type, score, status, submission_date, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW(), NOW())
        ");
        
        foreach ($testEvaluations as $eval) {
            $insertStmt->execute($eval);
        }
        
        echo "<p style='color: green;'>5 évaluations de test créées!</p>";
        echo "<p><a href='test_evaluations_scores.php'>Actualiser pour voir les résultats</a></p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Erreur: " . $e->getMessage() . "</p>";
}
?>