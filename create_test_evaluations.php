<?php
require_once 'includes/init.php';

echo "<h2>Création d'évaluations de test</h2>";

try {
    // Supprimer les anciennes évaluations de test
    $db->exec("DELETE FROM evaluations");
    
    // Créer des évaluations avec des scores variés
    $testEvaluations = [
        // Scores sur 20 (ancienne échelle)
        [1, 2, 1, 'mid_term', 16.8, 'completed', 'Excellent travail technique', '2025-01-20 14:30:00'],
        [1, 3, 2, 'final', 14.4, 'completed', 'Bon travail, quelques améliorations possibles', '2025-01-21 09:15:00'],
        [2, 4, 3, 'teacher', 12.0, 'completed', 'Travail satisfaisant', '2025-01-22 11:45:00'],
        [2, 5, 4, 'student', 18.0, 'completed', 'Performance excellente', '2025-01-23 16:20:00'],
        [3, 6, 5, 'supervisor', 10.5, 'completed', 'Effort insuffisant', '2025-01-24 13:10:00'],
        
        // Scores déjà sur 5 (nouvelle échelle)
        [1, 7, 6, 'mid_term', 4.2, 'completed', 'Très bon travail', '2025-01-25 10:30:00'],
        [2, 8, 7, 'final', 3.7, 'completed', 'Travail correct', '2025-01-26 15:45:00'],
        [3, 9, 8, 'teacher', 2.8, 'completed', 'Besoin d\'amélioration', '2025-01-27 08:20:00'],
        [1, 10, 9, 'student', 4.9, 'completed', 'Exceptionnel!', '2025-01-28 12:00:00'],
        [2, 11, 10, 'supervisor', 1.8, 'completed', 'Très insuffisant', '2025-01-29 14:15:00'],
        
        // Quelques brouillons et en attente
        [3, 12, 11, 'draft', null, 'draft', 'Brouillon en cours', null],
        [1, 13, 12, 'pending', null, 'pending', 'En attente de soumission', null],
        [2, 14, 13, 'mid_term', 15.6, 'completed', 'Bon niveau technique', '2025-01-30 09:30:00']
    ];
    
    $insertStmt = $db->prepare("
        INSERT INTO evaluations (evaluator_id, evaluatee_id, assignment_id, type, score, status, comments, submission_date, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    
    foreach ($testEvaluations as $eval) {
        $insertStmt->execute($eval);
    }
    
    echo "<p style='color: green;'>" . count($testEvaluations) . " évaluations de test créées avec succès!</p>";
    
    // Afficher un résumé
    $stmt = $db->query("
        SELECT 
            COUNT(*) as total,
            COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
            COUNT(CASE WHEN score IS NOT NULL THEN 1 END) as with_scores,
            ROUND(AVG(CASE WHEN score IS NOT NULL THEN 
                CASE WHEN score > 5 THEN score / 4 ELSE score END 
            END), 1) as avg_score_normalized
        FROM evaluations
    ");
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h3>Résumé:</h3>";
    echo "<ul>";
    echo "<li><strong>Total:</strong> " . $summary['total'] . " évaluations</li>";
    echo "<li><strong>Complétées:</strong> " . $summary['completed'] . "</li>";
    echo "<li><strong>Avec scores:</strong> " . $summary['with_scores'] . "</li>";
    echo "<li><strong>Score moyen (normalisé):</strong> " . $summary['avg_score_normalized'] . "/5</li>";
    echo "</ul>";
    
    echo "<p><a href='/tutoring/views/admin/evaluations.php'>Voir la page d'évaluations</a></p>";
    echo "<p><a href='test_evaluations_scores.php'>Tester les APIs</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Erreur: " . $e->getMessage() . "</p>";
}
?>