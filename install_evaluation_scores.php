<?php
/**
 * Script d'installation pour les fonctionnalités de scores d'évaluation cohérents
 * Ce script crée la table student_scores et calcule les scores initiaux pour tous les étudiants
 */

require_once __DIR__ . '/includes/init.php';

// Vérifier que l'utilisateur est administrateur
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    die("Accès non autorisé. Vous devez être administrateur pour exécuter ce script.");
}

echo '<!DOCTYPE html>
<html>
<head>
    <title>Installation des scores d\'évaluation</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        h1, h2 { color: #333; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .info { color: blue; }
        pre { background-color: #f5f5f5; padding: 10px; border-radius: 5px; overflow: auto; }
        .btn { display: inline-block; padding: 8px 16px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 4px; margin-top: 20px; }
    </style>
</head>
<body>
    <h1>Installation des scores d\'évaluation cohérents</h1>';

try {
    // 1. Créer la table student_scores
    echo '<h2>1. Création de la table student_scores</h2>';
    
    $db->beginTransaction();
    
    $createTableSQL = "
        CREATE TABLE IF NOT EXISTS student_scores (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            assignment_id INT NOT NULL,
            technical_score DECIMAL(3,1) DEFAULT 0,
            communication_score DECIMAL(3,1) DEFAULT 0,
            teamwork_score DECIMAL(3,1) DEFAULT 0,
            autonomy_score DECIMAL(3,1) DEFAULT 0,
            average_score DECIMAL(3,1) DEFAULT 0,
            completed_evaluations INT DEFAULT 0,
            total_evaluations INT DEFAULT 5,
            last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_student_assignment (student_id, assignment_id)
        )";
    
    $db->exec($createTableSQL);
    echo '<p class="success">Table student_scores créée avec succès.</p>';
    
    // 2. Créer les index pour améliorer les performances
    echo '<h2>2. Création des index</h2>';
    
    $createIndexSQL = "
        CREATE INDEX IF NOT EXISTS idx_student_scores_student ON student_scores(student_id);
        CREATE INDEX IF NOT EXISTS idx_student_scores_assignment ON student_scores(assignment_id);
    ";
    
    $db->exec($createIndexSQL);
    echo '<p class="success">Index créés avec succès.</p>';
    
    // 3. Lancer le calcul initial des scores
    echo '<h2>3. Calcul initial des scores</h2>';
    
    // Récupérer tous les étudiants ayant une affectation
    $query = "SELECT s.id as student_id, a.id as assignment_id 
              FROM students s 
              JOIN assignments a ON s.id = a.student_id
              WHERE a.status IN ('active', 'confirmed')";
    
    $stmt = $db->query($query);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo '<p>Nombre d\'étudiants trouvés: ' . count($students) . '</p>';
    
    if (count($students) > 0) {
        echo '<pre>';
        
        // Simuler l'exécution du script de calcul des scores
        ob_start();
        include __DIR__ . '/api/evaluations/calculate-student-scores.php';
        $output = ob_get_clean();
        
        echo htmlspecialchars($output);
        echo '</pre>';
    } else {
        echo '<p class="warning">Aucun étudiant avec affectation trouvé. Aucun score n\'a été calculé.</p>';
    }
    
    $db->commit();
    
    echo '<h2>Installation terminée</h2>';
    echo '<p class="success">L\'installation des scores d\'évaluation cohérents a été effectuée avec succès.</p>';
    
    echo '<div>
        <a href="/tutoring/views/student/evaluations.php" class="btn">Voir les évaluations (étudiant)</a>
        <a href="/tutoring/views/tutor/evaluations.php" class="btn">Voir les évaluations (tuteur)</a>
    </div>';
    
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    
    echo '<h2>Erreur lors de l\'installation</h2>';
    echo '<p class="error">Une erreur est survenue lors de l\'installation: ' . $e->getMessage() . '</p>';
    echo '<pre>' . $e->getTraceAsString() . '</pre>';
}

echo '</body>
</html>';
?>