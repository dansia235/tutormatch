<?php
/**
 * Benchmark pour l'algorithme glouton d'affectation
 */
// Charger manuellement les classes nécessaires
require_once __DIR__ . '/../src/Algorithm/AssignmentAlgorithmInterface.php';
require_once __DIR__ . '/../src/Algorithm/GreedyAlgorithm.php';
require_once __DIR__ . '/../src/DTO/AssignmentParameters.php';
require_once __DIR__ . '/../src/DTO/AssignmentResult.php';

use App\Algorithm\GreedyAlgorithm;
use App\DTO\AssignmentParameters;

// Configuration des tests de benchmark
$testSizes = [
    'Petit' => ['students' => 10, 'teachers' => 3],
    'Moyen' => ['students' => 50, 'teachers' => 10],
    'Grand' => ['students' => 200, 'teachers' => 30],
    'Très grand' => ['students' => 500, 'teachers' => 50],
    'Extrême' => ['students' => 1000, 'teachers' => 100]
];

// Vérifier si une taille spécifique est demandée via la variable d'environnement
$requestedSize = getenv('BENCHMARK_SIZE');
if ($requestedSize && strtolower($requestedSize) !== 'all') {
    $requestedSize = ucfirst(strtolower($requestedSize));
    if (array_key_exists($requestedSize, $testSizes)) {
        // Garder uniquement la taille demandée
        $testSizes = [$requestedSize => $testSizes[$requestedSize]];
    } else {
        echo "AVERTISSEMENT: Taille de benchmark inconnue: $requestedSize. Utilisation de toutes les tailles.\n\n";
    }
}

// Configuration des variations de paramètres
$parameterVariations = [
    'Défaut' => function(AssignmentParameters $params) {
        return $params; // Paramètres par défaut
    },
    'Préférence élevée' => function(AssignmentParameters $params) {
        return $params->setPreferenceWeight(70)
                      ->setDepartmentWeight(20)
                      ->setCapacityWeight(10);
    },
    'Capacité élevée' => function(AssignmentParameters $params) {
        return $params->setCapacityWeight(70)
                      ->setDepartmentWeight(20)
                      ->setPreferenceWeight(10);
    },
    'Interdépartements' => function(AssignmentParameters $params) {
        return $params->setAllowCrossDepartment(true);
    }
];

$repetitions = 5; // Nombre de répétitions pour chaque test

// Création de l'algorithme
$algorithm = new GreedyAlgorithm();

// Mesurer l'utilisation de la mémoire
function getMemoryUsage() {
    return memory_get_usage(true);
}

// Fonction pour créer des étudiants de test
function createStudents(int $count): array
{
    $students = [];
    $departments = ['Informatique', 'Mathématiques', 'Physique', 'Biologie', 'Chimie'];
    
    for ($i = 1; $i <= $count; $i++) {
        $department = $departments[array_rand($departments)];
        $students[] = new class($i, $department) {
            private $id;
            private $department;
            
            public function __construct(int $id, string $department)
            {
                $this->id = $id;
                $this->department = $department;
            }
            
            public function getId(): int
            {
                return $this->id;
            }
            
            public function getDepartment(): string
            {
                return $this->department;
            }
        };
    }
    
    return $students;
}

// Fonction pour créer des enseignants de test
function createTeachers(int $count): array
{
    $teachers = [];
    $departments = ['Informatique', 'Mathématiques', 'Physique', 'Biologie', 'Chimie'];
    
    for ($i = 1; $i <= $count; $i++) {
        $department = $departments[array_rand($departments)];
        $maxStudents = mt_rand(3, 10);
        
        $teachers[] = new class($i, $department, $maxStudents) {
            private $id;
            private $department;
            private $maxStudents;
            private $remainingCapacity;
            
            public function __construct(int $id, string $department, int $maxStudents)
            {
                $this->id = $id;
                $this->department = $department;
                $this->maxStudents = $maxStudents;
                $this->remainingCapacity = $maxStudents;
            }
            
            public function getId(): int
            {
                return $this->id;
            }
            
            public function getDepartment(): string
            {
                return $this->department;
            }
            
            public function getMaxStudents(): int
            {
                return $this->maxStudents;
            }
            
            public function getRemainingCapacity(): int
            {
                return $this->remainingCapacity;
            }
            
            public function setRemainingCapacity(int $capacity): void
            {
                $this->remainingCapacity = $capacity;
            }
        };
    }
    
    return $teachers;
}

// Affichage de l'en-tête
echo "=======================================================\n";
echo "BENCHMARK DE L'ALGORITHME GLOUTON D'AFFECTATION\n";
echo "=======================================================\n\n";

// Exécution des benchmarks pour les tests de taille
$results = [];

foreach ($testSizes as $testName => $config) {
    echo "Test de taille: $testName ({$config['students']} étudiants, {$config['teachers']} enseignants)\n";
    echo "-------------------------------------------------------\n";
    
    $studentCount = $config['students'];
    $teacherCount = $config['teachers'];
    
    $times = [];
    $peakMemoryUsages = [];
    $assignments = [];
    $unassigned = [];
    $scores = [];
    
    for ($i = 1; $i <= $repetitions; $i++) {
        echo "  Exécution $i/$repetitions... ";
        
        // Création des données de test
        $students = createStudents($studentCount);
        $teachers = createTeachers($teacherCount);
        $internships = []; // Pas utilisé dans le benchmark
        $parameters = new AssignmentParameters();
        
        // Mesurer l'utilisation de la mémoire avant
        $memoryBefore = getMemoryUsage();
        
        // Exécution de l'algorithme et mesure du temps
        $startTime = microtime(true);
        $result = $algorithm->execute($students, $teachers, $internships, $parameters);
        $endTime = microtime(true);
        
        // Mesurer l'utilisation de la mémoire après
        $memoryAfter = getMemoryUsage();
        $memoryUsage = $memoryAfter - $memoryBefore;
        
        $executionTime = $endTime - $startTime;
        $times[] = $executionTime;
        $peakMemoryUsages[] = $memoryUsage;
        $assignments[] = count($result->getAssignments());
        $unassigned[] = count($result->getUnassignedStudents());
        $scores[] = $result->getAverageScore();
        
        echo "Terminé en " . number_format($executionTime, 4) . " secondes\n";
    }
    
    // Calculer les moyennes
    $avgTime = array_sum($times) / count($times);
    $avgMemory = array_sum($peakMemoryUsages) / count($peakMemoryUsages);
    $avgAssignments = array_sum($assignments) / count($assignments);
    $avgUnassigned = array_sum($unassigned) / count($unassigned);
    $avgScore = array_sum($scores) / count($scores);
    
    // Calculer min/max pour le temps
    $minTime = min($times);
    $maxTime = max($times);
    
    // Afficher les résultats
    echo "\n  Résultats moyens:\n";
    echo "  - Temps d'exécution: " . number_format($avgTime, 4) . " secondes (min: " . number_format($minTime, 4) . ", max: " . number_format($maxTime, 4) . ")\n";
    echo "  - Utilisation mémoire: " . number_format($avgMemory / 1024 / 1024, 2) . " MB\n";
    echo "  - Affectations réalisées: " . number_format($avgAssignments, 1) . " / $studentCount\n";
    echo "  - Étudiants non affectés: " . number_format($avgUnassigned, 1) . "\n";
    echo "  - Score moyen: " . number_format($avgScore, 2) . " / 100\n\n";
    
    // Stocker les résultats pour le récapitulatif
    $results[$testName] = [
        'students' => $studentCount,
        'teachers' => $teacherCount,
        'time' => $avgTime,
        'memory' => $avgMemory,
        'assignments' => $avgAssignments,
        'unassigned' => $avgUnassigned,
        'score' => $avgScore
    ];
}

// Tests avec différentes configurations de paramètres
echo "=======================================================\n";
echo "TESTS AVEC DIFFÉRENTES CONFIGURATIONS DE PARAMÈTRES\n";
echo "=======================================================\n\n";

// Choisir une taille appropriée pour les tests de paramètres
// Si 'Moyen' n'est pas disponible, utiliser la première taille disponible
$testConfig = isset($testSizes['Moyen']) ? $testSizes['Moyen'] : reset($testSizes);
$testSizeName = isset($testSizes['Moyen']) ? 'Moyen' : key($testSizes);
$studentCount = $testConfig['students'];
$teacherCount = $testConfig['teachers'];

echo "Utilisation de la taille: $testSizeName ({$studentCount} étudiants, {$teacherCount} enseignants)\n\n";

$paramResults = [];

foreach ($parameterVariations as $configName => $configFunction) {
    echo "Configuration: $configName\n";
    echo "-------------------------------------------------------\n";
    
    $times = [];
    $assignments = [];
    $unassigned = [];
    $scores = [];
    
    for ($i = 1; $i <= $repetitions; $i++) {
        echo "  Exécution $i/$repetitions... ";
        
        // Création des données de test
        $students = createStudents($studentCount);
        $teachers = createTeachers($teacherCount);
        $internships = []; // Pas utilisé dans le benchmark
        $parameters = $configFunction(new AssignmentParameters());
        
        // Exécution de l'algorithme et mesure du temps
        $startTime = microtime(true);
        $result = $algorithm->execute($students, $teachers, $internships, $parameters);
        $endTime = microtime(true);
        
        $executionTime = $endTime - $startTime;
        $times[] = $executionTime;
        $assignments[] = count($result->getAssignments());
        $unassigned[] = count($result->getUnassignedStudents());
        $scores[] = $result->getAverageScore();
        
        echo "Terminé en " . number_format($executionTime, 4) . " secondes\n";
    }
    
    // Calculer les moyennes
    $avgTime = array_sum($times) / count($times);
    $avgAssignments = array_sum($assignments) / count($assignments);
    $avgUnassigned = array_sum($unassigned) / count($unassigned);
    $avgScore = array_sum($scores) / count($scores);
    
    // Afficher les résultats
    echo "\n  Résultats moyens:\n";
    echo "  - Temps d'exécution: " . number_format($avgTime, 4) . " secondes\n";
    echo "  - Affectations réalisées: " . number_format($avgAssignments, 1) . " / $studentCount\n";
    echo "  - Étudiants non affectés: " . number_format($avgUnassigned, 1) . "\n";
    echo "  - Score moyen: " . number_format($avgScore, 2) . " / 100\n\n";
    
    // Stocker les résultats pour le récapitulatif
    $paramResults[$configName] = [
        'time' => $avgTime,
        'assignments' => $avgAssignments,
        'unassigned' => $avgUnassigned,
        'score' => $avgScore
    ];
}

// Calcul de complexité temporelle
echo "=======================================================\n";
echo "ANALYSE DE COMPLEXITÉ TEMPORELLE\n";
echo "=======================================================\n\n";

// Préparer les données pour l'analyse de complexité
$sizes = [];
$timings = [];

foreach ($results as $testName => $data) {
    $n = $data['students'] * $data['teachers']; // Taille du problème: n*m
    $sizes[] = $n;
    $timings[] = $data['time'];
}

// Afficher les données brutes
echo "Données brutes:\n";
echo "| Taille (n*m) | Temps (s) |\n";
echo "|-------------|----------|\n";

for ($i = 0; $i < count($sizes); $i++) {
    printf("| %11d | %8.4f |\n", $sizes[$i], $timings[$i]);
}

// Calculer le facteur de croissance
echo "\nFacteurs de croissance:\n";
for ($i = 1; $i < count($sizes); $i++) {
    $sizeRatio = $sizes[$i] / $sizes[$i-1];
    $timeRatio = $timings[$i] / $timings[$i-1];
    printf("De %d à %d éléments: taille x%.2f, temps x%.2f\n", 
        $sizes[$i-1], $sizes[$i], $sizeRatio, $timeRatio);
}

// Afficher le récapitulatif des résultats
echo "\n=======================================================\n";
echo "RÉCAPITULATIF DES PERFORMANCES\n";
echo "=======================================================\n\n";

echo "Tests de taille:\n";
echo "| Test       | Étudiants | Enseignants | Temps (s) | Mémoire (MB) | Affectations | Non affectés | Score |\n";
echo "|------------|-----------|-------------|-----------|--------------|--------------|--------------|-------|\n";

// Préparer les données pour la visualisation
$timeData = [];
$memoryData = [];
$scoreData = [];

foreach ($results as $testName => $data) {
    printf("| %-10s | %9d | %11d | %9.4f | %12.2f | %12.1f | %12.1f | %5.2f |\n",
        $testName,
        $data['students'],
        $data['teachers'],
        $data['time'],
        $data['memory'] / 1024 / 1024,
        $data['assignments'],
        $data['unassigned'],
        $data['score']
    );
    
    // Collecter les données pour la visualisation
    $timeData[$testName] = $data['time'];
    $memoryData[$testName] = $data['memory'] / 1024 / 1024; // Convertir en MB
    $scoreData[$testName] = $data['score'];
}

echo "\nTests de configuration (taille: {$testSizeName}, {$studentCount} étudiants, {$teacherCount} enseignants):\n";
echo "| Configuration    | Temps (s) | Affectations | Non affectés | Score |\n";
echo "|------------------|-----------|--------------|--------------|-------|\n";

// Préparer les données pour la visualisation des configurations
$configTimeData = [];
$configScoreData = [];

foreach ($paramResults as $configName => $data) {
    printf("| %-16s | %9.4f | %12.1f | %12.1f | %5.2f |\n",
        $configName,
        $data['time'],
        $data['assignments'],
        $data['unassigned'],
        $data['score']
    );
    
    // Collecter les données pour la visualisation
    $configTimeData[$configName] = $data['time'];
    $configScoreData[$configName] = $data['score'];
}

echo "\nAnalyse de complexité:\n";
echo "- Complexité temporelle: O(n² log n) où n est le nombre d'étudiants\n";
echo "- Complexité spatiale: O(n*m) où n est le nombre d'étudiants et m le nombre d'enseignants\n";

echo "\nRecommandations d'optimisation:\n";
echo "1. Implémentation de la mise en cache pour les calculs de compatibilité\n";
echo "2. Parallélisation des calculs pour les grands ensembles de données\n";
echo "3. Optimisation de la structure de données pour les grandes quantités de données\n";
echo "4. Utilisation d'une stratégie de filtrage pour réduire le nombre de combinaisons évaluées\n";
echo "5. Implémentation d'un algorithme de correspondance plus efficace pour les très grands ensembles\n";

// Inclure l'outil de visualisation
require_once __DIR__ . '/BenchmarkVisualizer.php';

// Afficher les graphiques ASCII
echo "\n=======================================================\n";
echo "VISUALISATION DES RÉSULTATS\n";
echo "=======================================================\n\n";

echo "Temps d'exécution par taille:\n";
generateAsciiGraph($timeData, "Temps d'exécution", "Taille du test", "Secondes");

echo "\nUtilisation mémoire par taille:\n";
generateAsciiGraph($memoryData, "Utilisation mémoire", "Taille du test", "MB");

echo "\nScores moyens par taille:\n";
generateAsciiGraph($scoreData, "Scores moyens", "Taille du test", "Score");

echo "\nTemps d'exécution par configuration:\n";
generateAsciiGraph($configTimeData, "Temps d'exécution", "Configuration", "Secondes");

echo "\nScores moyens par configuration:\n";
generateAsciiGraph($configScoreData, "Scores moyens", "Configuration", "Score");

// Générer un rapport HTML si un répertoire de sortie est spécifié
$outputDir = getenv('BENCHMARK_OUTPUT_DIR');
if ($outputDir) {
    $outputFile = $outputDir . '/greedy_benchmark_' . date('Y-m-d_H-i-s') . '.html';
    generateHtmlReport($timeData, $memoryData, $scoreData, $outputFile);
}