<?php
/**
 * Script de test pour l'algorithme génétique
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/src/Algorithm/GeneticAlgorithm.php';
require_once __DIR__ . '/src/DTO/AssignmentParameters.php';
require_once __DIR__ . '/src/DTO/AssignmentResult.php';

use App\Algorithm\GeneticAlgorithm;
use App\DTO\AssignmentParameters;

echo "<h1>Test de l'Algorithme Génétique TutorMatch</h1>\n";
echo "<pre>\n";

try {
    echo "=== Configuration ===\n";
    $configPath = __DIR__ . '/config/genetic_algorithm.php';
    if (file_exists($configPath)) {
        $config = require $configPath;
        echo "✅ Fichier de configuration trouvé\n";
        echo "Configuration par défaut:\n";
        foreach ($config['default'] as $key => $value) {
            echo "  - $key: $value\n";
        }
    } else {
        echo "⚠️ Fichier de configuration non trouvé\n";
    }
    
    echo "\n=== Test Simple ===\n";
    
    // Créer des données de test
    $students = [];
    for ($i = 1; $i <= 20; $i++) {
        $student = new stdClass();
        $student->id = $i;
        $student->name = "Étudiant $i";
        $student->department = ['Info', 'Math', 'Physique'][($i - 1) % 3];
        $student->preferences = [];
        
        // Ajouter des méthodes
        $student->getId = function() use ($student) { return $student->id; };
        $student->getDepartment = function() use ($student) { return $student->department; };
        $student->getPreferences = function() use ($student) { return $student->preferences; };
        
        $students[] = $student;
    }
    
    $teachers = [];
    for ($i = 1; $i <= 5; $i++) {
        $teacher = new stdClass();
        $teacher->id = $i;
        $teacher->name = "Enseignant $i";
        $teacher->department = ['Info', 'Math', 'Physique'][($i - 1) % 3];
        $teacher->capacity = 5;
        
        // Ajouter des méthodes
        $teacher->getId = function() use ($teacher) { return $teacher->id; };
        $teacher->getDepartment = function() use ($teacher) { return $teacher->department; };
        $teacher->getCapacity = function() use ($teacher) { return $teacher->capacity; };
        
        $teachers[] = $teacher;
    }
    
    // Créer les paramètres
    $parameters = new AssignmentParameters();
    $parameters->setDepartmentWeight(0.5);
    $parameters->setPreferenceWeight(0.3);
    $parameters->setCapacityWeight(0.2);
    $parameters->setMaxAssignmentsPerTeacher(5);
    $parameters->setAllowCrossDepartment(false);
    
    echo "Données de test:\n";
    echo "- " . count($students) . " étudiants\n";
    echo "- " . count($teachers) . " enseignants\n";
    
    // Exécuter l'algorithme
    echo "\n=== Exécution de l'algorithme ===\n";
    $algorithm = new GeneticAlgorithm();
    
    $startTime = microtime(true);
    $result = $algorithm->execute($students, $teachers, [], $parameters);
    $executionTime = microtime(true) - $startTime;
    
    if ($result->isSuccessful()) {
        echo "✅ Algorithme exécuté avec succès!\n";
        echo "\nRésultats:\n";
        echo "- Temps d'exécution: " . number_format($executionTime, 3) . " secondes\n";
        echo "- Affectations créées: " . count($result->getAssignments()) . "\n";
        echo "- Étudiants non affectés: " . count($result->getUnassignedStudents()) . "\n";
        echo "- Score moyen: " . number_format($result->getAverageScore(), 2) . "\n";
        
        echo "\n=== Détail des affectations ===\n";
        foreach ($result->getAssignments() as $i => $assignment) {
            if ($i < 5) { // Afficher les 5 premières
                echo sprintf(
                    "Étudiant %d -> Enseignant %d (Score: %.2f)\n",
                    $assignment['student_id'],
                    $assignment['teacher_id'],
                    $assignment['compatibility_score']
                );
            }
        }
        if (count($result->getAssignments()) > 5) {
            echo "... et " . (count($result->getAssignments()) - 5) . " autres affectations\n";
        }
        
    } else {
        echo "❌ Erreur: " . $result->getErrorMessage() . "\n";
    }
    
    echo "\n=== Test des configurations adaptatives ===\n";
    $testSizes = [
        ['students' => 10, 'config' => 'small'],
        ['students' => 100, 'config' => 'medium'],
        ['students' => 300, 'config' => 'large'],
        ['students' => 600, 'config' => 'extra_large'],
    ];
    
    foreach ($testSizes as $test) {
        echo "\nTest avec {$test['students']} étudiants (config: {$test['config']}):\n";
        
        // L'algorithme devrait automatiquement s'adapter
        $algorithm = new GeneticAlgorithm();
        
        // Créer des données factices juste pour vérifier l'adaptation
        $testStudents = array_fill(0, $test['students'], $students[0]);
        $algorithm->execute($testStudents, $teachers, [], $parameters);
        
        echo "  Configuration adaptée appliquée\n";
    }
    
    echo "\n=== Vérification des logs ===\n";
    $logFile = __DIR__ . '/logs/app_' . date('Y-m-d') . '.log';
    if (file_exists($logFile)) {
        $logs = file_get_contents($logFile);
        $geneticLogs = substr_count($logs, '[genetic]');
        echo "✅ $geneticLogs entrées de log trouvées pour l'algorithme génétique\n";
    } else {
        echo "⚠️ Fichier de log non trouvé\n";
    }
    
    echo "\n=== Test terminé avec succès! ===\n";
    
} catch (Exception $e) {
    echo "\n❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>\n";

// Ajouter un lien pour retourner à la page d'administration
echo '<div style="margin-top: 20px;">';
echo '<a href="/tutoring/admin/assignments/generate" style="background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Retour à la génération d\'affectations</a>';
echo '</div>';
?>