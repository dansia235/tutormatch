<?php
/**
 * Benchmarks pour l'algorithme génétique
 * Compare les performances avec les autres algorithmes
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/Algorithm/GeneticAlgorithm.php';
require_once __DIR__ . '/../../src/Algorithm/GreedyAlgorithm.php';
require_once __DIR__ . '/../../src/Algorithm/HungarianAlgorithm.php';
require_once __DIR__ . '/../../src/DTO/AssignmentParameters.php';
require_once __DIR__ . '/../../src/DTO/AssignmentResult.php';

use App\Algorithm\GeneticAlgorithm;
use App\Algorithm\GreedyAlgorithm;
use App\Algorithm\HungarianAlgorithm;
use App\DTO\AssignmentParameters;

class GeneticAlgorithmBenchmark
{
    private array $benchmarkResults = [];
    
    /**
     * Execute tous les benchmarks
     */
    public function run(): void
    {
        echo "=== Benchmarks de l'Algorithme Génétique ===\n\n";
        
        // Différentes tailles de problèmes
        $testCases = [
            ['students' => 10, 'teachers' => 3, 'label' => 'Petit (10 étudiants, 3 enseignants)'],
            ['students' => 50, 'teachers' => 10, 'label' => 'Moyen (50 étudiants, 10 enseignants)'],
            ['students' => 100, 'teachers' => 20, 'label' => 'Grand (100 étudiants, 20 enseignants)'],
            ['students' => 200, 'teachers' => 40, 'label' => 'Très grand (200 étudiants, 40 enseignants)'],
        ];
        
        foreach ($testCases as $case) {
            echo "\n--- Test: {$case['label']} ---\n";
            $this->benchmarkCase($case['students'], $case['teachers']);
        }
        
        // Afficher le résumé
        $this->displaySummary();
        
        // Sauvegarder les résultats
        $this->saveResults();
    }
    
    /**
     * Benchmark un cas spécifique
     */
    private function benchmarkCase(int $studentCount, int $teacherCount): void
    {
        // Générer les données de test
        list($students, $teachers, $parameters) = $this->generateTestData($studentCount, $teacherCount);
        
        // Algorithmes à tester
        $algorithms = [
            'genetic' => new GeneticAlgorithm(),
            'greedy' => new GreedyAlgorithm(),
            'hungarian' => new HungarianAlgorithm(),
        ];
        
        $caseResults = [
            'students' => $studentCount,
            'teachers' => $teacherCount,
            'algorithms' => []
        ];
        
        foreach ($algorithms as $name => $algorithm) {
            echo "\nTest de l'algorithme $name...\n";
            
            // Mesurer le temps et la mémoire
            $startTime = microtime(true);
            $startMemory = memory_get_usage(true);
            
            try {
                $result = $algorithm->execute($students, $teachers, [], $parameters);
                
                $endTime = microtime(true);
                $endMemory = memory_get_usage(true);
                
                $executionTime = $endTime - $startTime;
                $memoryUsed = ($endMemory - $startMemory) / 1024 / 1024; // En MB
                
                $algorithmResults = [
                    'success' => $result->isSuccessful(),
                    'execution_time' => $executionTime,
                    'memory_mb' => $memoryUsed,
                    'assignments' => count($result->getAssignments()),
                    'unassigned' => count($result->getUnassignedStudents()),
                    'average_score' => $result->getAverageScore(),
                ];
                
                // Afficher les résultats
                printf("  Temps: %.3f s\n", $executionTime);
                printf("  Mémoire: %.2f MB\n", $memoryUsed);
                printf("  Affectations: %d/%d\n", count($result->getAssignments()), $studentCount);
                printf("  Score moyen: %.2f\n", $result->getAverageScore());
                
                $caseResults['algorithms'][$name] = $algorithmResults;
                
            } catch (\Exception $e) {
                echo "  Erreur: " . $e->getMessage() . "\n";
                $caseResults['algorithms'][$name] = [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        $this->benchmarkResults[] = $caseResults;
    }
    
    /**
     * Génère des données de test
     */
    private function generateTestData(int $studentCount, int $teacherCount): array
    {
        $students = [];
        $teachers = [];
        
        $departments = ['Informatique', 'Mathématiques', 'Physique', 'Chimie', 'Biologie'];
        
        // Générer les étudiants
        for ($i = 0; $i < $studentCount; $i++) {
            $student = new \stdClass();
            $student->id = $i + 1;
            $student->name = "Étudiant " . ($i + 1);
            $student->department = $departments[array_rand($departments)];
            $student->preferences = $this->generatePreferences($teacherCount);
            
            // Méthodes requises
            $student->getId = function() use ($student) { return $student->id; };
            $student->getDepartment = function() use ($student) { return $student->department; };
            $student->getPreferences = function() use ($student) { return $student->preferences; };
            
            $students[] = $student;
        }
        
        // Générer les enseignants
        for ($i = 0; $i < $teacherCount; $i++) {
            $teacher = new \stdClass();
            $teacher->id = $i + 1;
            $teacher->name = "Enseignant " . ($i + 1);
            $teacher->department = $departments[array_rand($departments)];
            $teacher->capacity = rand(3, 8);
            
            // Méthodes requises
            $teacher->getId = function() use ($teacher) { return $teacher->id; };
            $teacher->getDepartment = function() use ($teacher) { return $teacher->department; };
            $teacher->getCapacity = function() use ($teacher) { return $teacher->capacity; };
            
            $teachers[] = $teacher;
        }
        
        // Paramètres
        $parameters = new AssignmentParameters();
        $parameters->setDepartmentWeight(0.5);
        $parameters->setPreferenceWeight(0.3);
        $parameters->setCapacityWeight(0.2);
        $parameters->setMaxAssignmentsPerTeacher(10);
        $parameters->setAllowCrossDepartment(false);
        $parameters->setPrioritizePreferences(true);
        $parameters->setBalanceWorkload(true);
        
        return [$students, $teachers, $parameters];
    }
    
    /**
     * Génère des préférences aléatoires
     */
    private function generatePreferences(int $teacherCount): array
    {
        $preferences = [];
        $numPreferences = min(3, $teacherCount);
        
        $teacherIds = range(1, $teacherCount);
        shuffle($teacherIds);
        
        for ($i = 0; $i < $numPreferences; $i++) {
            $preferences[] = [
                'teacher_id' => $teacherIds[$i],
                'rank' => $i + 1
            ];
        }
        
        return $preferences;
    }
    
    /**
     * Affiche le résumé des benchmarks
     */
    private function displaySummary(): void
    {
        echo "\n\n=== RÉSUMÉ DES PERFORMANCES ===\n\n";
        
        $summary = [];
        
        foreach ($this->benchmarkResults as $result) {
            $label = "{$result['students']} étudiants";
            
            foreach ($result['algorithms'] as $algo => $data) {
                if (!isset($summary[$algo])) {
                    $summary[$algo] = [];
                }
                
                if ($data['success']) {
                    $summary[$algo][$label] = [
                        'time' => $data['execution_time'],
                        'memory' => $data['memory_mb'],
                        'score' => $data['average_score']
                    ];
                }
            }
        }
        
        // Tableau comparatif
        echo "Temps d'exécution (secondes):\n";
        $this->printComparisonTable($summary, 'time', '%.3f');
        
        echo "\nMémoire utilisée (MB):\n";
        $this->printComparisonTable($summary, 'memory', '%.2f');
        
        echo "\nScore moyen:\n";
        $this->printComparisonTable($summary, 'score', '%.2f');
    }
    
    /**
     * Affiche un tableau comparatif
     */
    private function printComparisonTable(array $summary, string $metric, string $format): void
    {
        // En-tête
        printf("%-15s", "Taille");
        foreach (array_keys($summary) as $algo) {
            printf(" | %-12s", ucfirst($algo));
        }
        echo "\n" . str_repeat('-', 15 + count($summary) * 15) . "\n";
        
        // Données
        $sizes = array_keys(reset($summary));
        foreach ($sizes as $size) {
            printf("%-15s", $size);
            foreach ($summary as $algo => $data) {
                if (isset($data[$size][$metric])) {
                    printf(" | " . $format . "      ", $data[$size][$metric]);
                } else {
                    printf(" | %-12s", "N/A");
                }
            }
            echo "\n";
        }
    }
    
    /**
     * Sauvegarde les résultats dans un fichier
     */
    private function saveResults(): void
    {
        $timestamp = date('Y-m-d_H-i-s');
        $filename = __DIR__ . "/../../logs/benchmark_genetic_{$timestamp}.json";
        
        $data = [
            'timestamp' => $timestamp,
            'results' => $this->benchmarkResults,
            'summary' => $this->generateSummaryData()
        ];
        
        file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
        echo "\n\nRésultats sauvegardés dans: $filename\n";
    }
    
    /**
     * Génère les données de résumé
     */
    private function generateSummaryData(): array
    {
        $summary = [];
        
        foreach ($this->benchmarkResults as $result) {
            foreach ($result['algorithms'] as $algo => $data) {
                if (!$data['success']) continue;
                
                if (!isset($summary[$algo])) {
                    $summary[$algo] = [
                        'total_time' => 0,
                        'total_memory' => 0,
                        'total_score' => 0,
                        'count' => 0
                    ];
                }
                
                $summary[$algo]['total_time'] += $data['execution_time'];
                $summary[$algo]['total_memory'] += $data['memory_mb'];
                $summary[$algo]['total_score'] += $data['average_score'];
                $summary[$algo]['count']++;
            }
        }
        
        // Calculer les moyennes
        foreach ($summary as $algo => &$data) {
            if ($data['count'] > 0) {
                $data['avg_time'] = $data['total_time'] / $data['count'];
                $data['avg_memory'] = $data['total_memory'] / $data['count'];
                $data['avg_score'] = $data['total_score'] / $data['count'];
            }
        }
        
        return $summary;
    }
}

// Exécuter les benchmarks
if (php_sapi_name() === 'cli') {
    $benchmark = new GeneticAlgorithmBenchmark();
    $benchmark->run();
}