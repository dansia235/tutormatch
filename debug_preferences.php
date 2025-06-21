<?php
/**
 * Script de débogage des préférences de stage
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/includes/init.php';

// Vérifier que l'utilisateur est connecté
requireLogin();

// Récupérer les informations de session
echo "<h2>Session Information</h2>";
echo "<pre>";
echo "SESSION['user_id']: " . ($_SESSION['user_id'] ?? 'Not set') . "\n";
echo "SESSION['user']['id']: " . ($_SESSION['user']['id'] ?? 'Not set') . "\n";
echo "SESSION['user_role']: " . ($_SESSION['user_role'] ?? 'Not set') . "\n";
echo "</pre>";

// Récupérer l'ID de l'étudiant - vérifier les deux formats possibles
$user_id = $_SESSION['user_id'] ?? ($_SESSION['user']['id'] ?? null);
echo "<h2>User ID: " . ($user_id ?: 'null') . "</h2>";

// Récupérer l'étudiant
if ($user_id) {
    $studentModel = new Student($db);
    $student = $studentModel->getByUserId($user_id);
    
    echo "<h2>Student Information</h2>";
    if ($student) {
        echo "<pre>";
        print_r($student);
        echo "</pre>";
        
        // Récupérer les préférences
        $preferences = $studentModel->getPreferences($student['id']);
        
        echo "<h2>Student Preferences (" . count($preferences) . ")</h2>";
        if (!empty($preferences)) {
            echo "<pre>";
            print_r($preferences);
            echo "</pre>";
        } else {
            echo "<p>No preferences found for this student.</p>";
        }
        
        // Structure de la table student_preferences
        echo "<h2>student_preferences Table Structure</h2>";
        try {
            $stmt = $db->query("DESCRIBE student_preferences");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table border='1'>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
            
            foreach ($columns as $column) {
                echo "<tr>";
                foreach ($column as $key => $value) {
                    echo "<td>" . htmlspecialchars($value) . "</td>";
                }
                echo "</tr>";
            }
            
            echo "</table>";
        } catch (Exception $e) {
            echo "<p>Error fetching table structure: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p>Student not found for user ID: " . $user_id . "</p>";
    }
} else {
    echo "<p>No user ID available in session.</p>";
}
?>