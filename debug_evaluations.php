<?php
/**
 * Debug des évaluations - vérification des données
 */

// Configuration pour afficher les erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Démarrer la session pour les tests
session_start();

// Simuler un utilisateur étudiant pour les tests
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // ID d'un étudiant de test
    $_SESSION['role'] = 'student';
}

echo "<h1>Debug des évaluations</h1>";

try {
    $db = new PDO("mysql:host=localhost;dbname=tutoring_system;charset=utf8", 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>1. Connexion à la base de données</h2>";
    echo "<p style='color: green;'>✅ Connexion réussie</p>";
    
    echo "<h2>2. Vérification des tables</h2>";
    
    // Vérifier la table evaluations
    $stmt = $db->query("SHOW TABLES LIKE 'evaluations'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✅ Table evaluations existe</p>";
        
        // Structure de la table
        $stmt = $db->query("DESCRIBE evaluations");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "<p><strong>Colonnes disponibles:</strong> " . implode(", ", $columns) . "</p>";
    } else {
        echo "<p style='color: red;'>❌ Table evaluations n'existe pas</p>";
    }
    
    // Vérifier la table users
    $stmt = $db->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✅ Table users existe</p>";
    } else {
        echo "<p style='color: red;'>❌ Table users n'existe pas</p>";
    }
    
    echo "<h2>3. Vérification des données</h2>";
    
    // Compter les évaluations
    $stmt = $db->query("SELECT COUNT(*) as total FROM evaluations");
    $totalEvaluations = $stmt->fetchColumn();
    echo "<p><strong>Total évaluations:</strong> $totalEvaluations</p>";
    
    // Compter les utilisateurs étudiants
    $stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE role = 'student'");
    $totalStudents = $stmt->fetchColumn();
    echo "<p><strong>Total étudiants:</strong> $totalStudents</p>";
    
    // Vérifier s'il y a des évaluations pour l'utilisateur de test
    $userId = $_SESSION['user_id'];
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM evaluations WHERE evaluatee_id = ?");
    $stmt->execute([$userId]);
    $userEvaluations = $stmt->fetchColumn();
    echo "<p><strong>Évaluations pour l'utilisateur $userId:</strong> $userEvaluations</p>";
    
    if ($userEvaluations == 0) {
        echo "<p style='color: orange;'>⚠️ Aucune évaluation pour cet utilisateur</p>";
        
        // Essayer de trouver un utilisateur avec des évaluations
        $stmt = $db->query("SELECT DISTINCT evaluatee_id FROM evaluations LIMIT 5");
        $usersWithEvals = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($usersWithEvals)) {
            echo "<p><strong>Utilisateurs avec des évaluations:</strong> " . implode(", ", $usersWithEvals) . "</p>";
            echo "<p>Vous pouvez tester avec l'un de ces IDs</p>";
        }
    }
    
    echo "<h2>4. Test de requête complète</h2>";
    
    // Test de la requête principale (corriger le ORDER BY)
    $stmt = $db->prepare("
        SELECT 
            e.*,
            u_evaluator.first_name as evaluator_first_name,
            u_evaluator.last_name as evaluator_last_name,
            u_evaluator.role as evaluator_role
        FROM evaluations e
        LEFT JOIN users u_evaluator ON e.evaluator_id = u_evaluator.id
        WHERE e.evaluatee_id = ?
        ORDER BY e.submission_date DESC
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $evaluations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Résultats de la requête:</strong></p>";
    echo "<pre>";
    print_r($evaluations);
    echo "</pre>";
    
    if (empty($evaluations)) {
        echo "<p style='color: orange;'>⚠️ Aucun résultat avec la requête actuelle</p>";
        
        // Test avec un autre utilisateur
        if (!empty($usersWithEvals)) {
            $testUserId = $usersWithEvals[0];
            $stmt->execute([$testUserId]);
            $testEvaluations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h3>Test avec l'utilisateur $testUserId:</h3>";
            echo "<pre>";
            print_r($testEvaluations);
            echo "</pre>";
        }
    }
    
    echo "<h2>5. Test des critères JSON</h2>";
    
    // Vérifier s'il y a des criteria_scores JSON
    $stmt = $db->query("SELECT id, criteria_scores FROM evaluations WHERE criteria_scores IS NOT NULL AND criteria_scores != '' LIMIT 3");
    $criteriaExamples = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($criteriaExamples)) {
        echo "<p style='color: green;'>✅ Critères JSON trouvés</p>";
        foreach ($criteriaExamples as $example) {
            echo "<p><strong>Évaluation {$example['id']}:</strong></p>";
            echo "<pre>" . $example['criteria_scores'] . "</pre>";
            
            // Test de décodage JSON
            $decoded = json_decode($example['criteria_scores'], true);
            if ($decoded) {
                echo "<p>✅ JSON valide, contenu décodé:</p>";
                echo "<pre>";
                print_r($decoded);
                echo "</pre>";
            } else {
                echo "<p style='color: red;'>❌ JSON invalide</p>";
            }
        }
    } else {
        echo "<p style='color: orange;'>⚠️ Aucun critère JSON trouvé</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>6. Solutions suggérées</h2>";
echo "<ul>";
echo "<li>Si aucune évaluation n'existe, exécutez d'abord <code>reset_full_system.php</code></li>";
echo "<li>Vérifiez que l'utilisateur connecté a le bon ID</li>";
echo "<li>Vérifiez que les évaluations ont été générées pour cet utilisateur</li>";
echo "<li>Testez avec un des IDs d'utilisateur qui ont des évaluations</li>";
echo "</ul>";

echo "<p><a href='/tutoring/views/student/evaluations_simple.php'>← Retour aux évaluations</a></p>";
?>