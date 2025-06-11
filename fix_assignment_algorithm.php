<?php
/**
 * Script pour corriger le problème de génération d'affectations
 * - Vérifie l'existence des tables algorithm_parameters et algorithm_executions
 * - Les crée si elles n'existent pas
 * - Corrige les problèmes de compatibilité entre le modèle de données et les algorithmes
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/includes/init.php';

// Vérifier les permissions
requireRole(['admin', 'coordinator']);

// Fonction pour vérifier si une table existe
function tableExists($db, $tableName) {
    try {
        $result = $db->query("SHOW TABLES LIKE '{$tableName}'");
        return $result->rowCount() > 0;
    } catch (PDOException $e) {
        echo "Erreur lors de la vérification de la table {$tableName}: " . $e->getMessage() . "<br>";
        return false;
    }
}

// Fonction pour exécuter un script SQL
function executeSqlScript($db, $scriptContent) {
    // Diviser le script en requêtes individuelles
    $queries = explode(';', $scriptContent);
    
    // Exécuter chaque requête
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            try {
                $db->exec($query);
                echo "Requête exécutée avec succès.<br>";
            } catch (PDOException $e) {
                echo "Erreur lors de l'exécution de la requête: " . $e->getMessage() . "<br>";
                echo "Requête: " . $query . "<br>";
            }
        }
    }
}

// Vérifier les tables
$checkAlgorithmParametersTable = tableExists($db, 'algorithm_parameters');
$checkAlgorithmExecutionsTable = tableExists($db, 'algorithm_executions');

echo "<h1>Vérification et correction des tables d'algorithmes d'affectation</h1>";

echo "<h2>État actuel des tables</h2>";
echo "Table algorithm_parameters: " . ($checkAlgorithmParametersTable ? "Existe" : "N'existe pas") . "<br>";
echo "Table algorithm_executions: " . ($checkAlgorithmExecutionsTable ? "Existe" : "N'existe pas") . "<br>";

// Si les tables n'existent pas, les créer
if (!$checkAlgorithmParametersTable || !$checkAlgorithmExecutionsTable) {
    echo "<h2>Création des tables manquantes</h2>";
    
    // Désactiver les contraintes de clé étrangère
    $db->exec('SET FOREIGN_KEY_CHECKS = 0');
    
    // Créer la table algorithm_parameters si elle n'existe pas
    if (!$checkAlgorithmParametersTable) {
        echo "Création de la table algorithm_parameters...<br>";
        $parametersScript = file_get_contents(__DIR__ . '/database/create_algorithm_parameters_table.sql');
        
        if (!$parametersScript) {
            echo "Erreur: Impossible de charger le script SQL pour algorithm_parameters.<br>";
        } else {
            executeSqlScript($db, $parametersScript);
        }
    }
    
    // Créer la table algorithm_executions si elle n'existe pas
    if (!$checkAlgorithmExecutionsTable) {
        echo "Création de la table algorithm_executions...<br>";
        $executionsScript = file_get_contents(__DIR__ . '/database/create_algorithm_executions_table.sql');
        
        if (!$executionsScript) {
            echo "Erreur: Impossible de charger le script SQL pour algorithm_executions.<br>";
        } else {
            executeSqlScript($db, $executionsScript);
        }
    }
    
    // Réactiver les contraintes de clé étrangère
    $db->exec('SET FOREIGN_KEY_CHECKS = 1');
}

// Vérifier si les modèles PHP sont correctement définis
echo "<h2>Vérification des modèles PHP</h2>";

$algorithmParametersModelExists = class_exists('AlgorithmParameters');
$algorithmExecutionModelExists = class_exists('AlgorithmExecution');

echo "Modèle AlgorithmParameters: " . ($algorithmParametersModelExists ? "Existe" : "N'existe pas") . "<br>";
echo "Modèle AlgorithmExecution: " . ($algorithmExecutionModelExists ? "Existe" : "N'existe pas") . "<br>";

// Vérifier si les namespaces sont correctement définis
echo "<h2>Vérification des algorithmes d'affectation</h2>";

$greedyAlgorithmExists = class_exists('App\Algorithm\GreedyAlgorithm');
$hungarianAlgorithmExists = class_exists('App\Algorithm\HungarianAlgorithm');
$assignmentParametersDtoExists = class_exists('App\DTO\AssignmentParameters');
$assignmentResultDtoExists = class_exists('App\DTO\AssignmentResult');

echo "GreedyAlgorithm: " . ($greedyAlgorithmExists ? "Existe" : "N'existe pas") . "<br>";
echo "HungarianAlgorithm: " . ($hungarianAlgorithmExists ? "Existe" : "N'existe pas") . "<br>";
echo "AssignmentParameters DTO: " . ($assignmentParametersDtoExists ? "Existe" : "N'existe pas") . "<br>";
echo "AssignmentResult DTO: " . ($assignmentResultDtoExists ? "Existe" : "N'existe pas") . "<br>";

// Faire un test simple de génération d'affectations
echo "<h2>Test de génération d'affectations</h2>";

try {
    // Vérifier l'existence des algorithmes dans le contrôleur
    $reflection = new ReflectionClass('AssignmentController');
    $greedyMethod = $reflection->getMethod('greedyAlgorithm');
    $hungarianMethod = $reflection->getMethod('hungarianAlgorithm');
    
    echo "Méthode greedyAlgorithm dans AssignmentController: Existe<br>";
    echo "Méthode hungarianAlgorithm dans AssignmentController: Existe<br>";
    
    // Vérifier la compatibilité entre le namespace App\Algorithm et le code du contrôleur
    echo "<p>Note: Le contrôleur n'utilise pas directement les classes dans le namespace App\Algorithm. "
        . "Il utilise ses propres implémentations des algorithmes d'affectation.</p>";
    
    // Tester une génération d'affectations avec les méthodes internes du contrôleur
    $assignmentController = new AssignmentController($db);
    
    // Récupérer quelques étudiants, enseignants et stages pour le test
    $studentModel = new Student($db);
    $teacherModel = new Teacher($db);
    $internshipModel = new Internship($db);
    
    $students = $studentModel->getAll('active');
    $students = array_slice($students, 0, 5); // Limiter à 5 étudiants pour le test
    
    $teachers = $teacherModel->getAll(true);
    $internships = $internshipModel->getAll('available');
    
    echo "Nombre d'étudiants pour le test: " . count($students) . "<br>";
    echo "Nombre d'enseignants pour le test: " . count($teachers) . "<br>";
    echo "Nombre de stages disponibles pour le test: " . count($internships) . "<br>";
    
    // Paramètres par défaut pour le test
    $params = [
        'department_weight' => 50,
        'preference_weight' => 30,
        'capacity_weight' => 20,
        'allow_cross_department' => 0,
        'prioritize_preferences' => 1,
        'balance_workload' => 1
    ];
    
    // Nous ne pouvons pas appeler directement la méthode privée greedyAlgorithm
    // Utilisons la méthode generateAssignments à la place, qui appelle greedyAlgorithm en interne
    try {
        // Préparer les données nécessaires pour la génération d'affectations
        // Sauvegarder les anciennes valeurs POST
        $oldPost = $_POST;
        
        // Simuler une requête POST pour la génération d'affectations
        $_POST = [
            'algorithm_type' => 'greedy',
            'department_weight' => $params['department_weight'],
            'preference_weight' => $params['preference_weight'],
            'capacity_weight' => $params['capacity_weight'],
            'allow_cross_department' => $params['allow_cross_department'],
            'prioritize_preferences' => $params['prioritize_preferences'],
            'balance_workload' => $params['balance_workload'],
            'csrf_token' => generateCsrfToken() // Générer un token CSRF
        ];
        
        // Générer un petit nombre d'affectations pour tester
        // Définir une classe de test qui étend AssignmentController pour accéder à la méthode privée
        class TestAssignmentController extends AssignmentController {
            public function testGreedyAlgorithm($students, $teachers, $internships, $params) {
                return $this->greedyAlgorithm($students, $teachers, $internships, $params);
            }
        }
        
        $testController = new TestAssignmentController($db);
        $results = $testController->testGreedyAlgorithm($students, $teachers, $internships, $params);
        
        // Restaurer les anciennes valeurs POST
        $_POST = $oldPost;
    
    echo "Résultats du test avec l'algorithme glouton: " . count($results) . " affectations générées<br>";
    
    foreach ($results as $index => $result) {
        echo "Affectation " . ($index + 1) . ": Étudiant #" . $result['student_id'] . " -> Enseignant #" . $result['teacher_id'] . " (Score: " . $result['compatibility_score'] . ")<br>";
    }
    
} catch (Exception $e) {
    echo "Erreur lors du test: " . $e->getMessage() . "<br>";
    echo "Trace: " . $e->getTraceAsString() . "<br>";
}

echo "<h2>Conclusion</h2>";

if (!$checkAlgorithmParametersTable || !$checkAlgorithmExecutionsTable) {
    echo "<p>Les tables ont été créées ou corrigées. Veuillez réessayer la génération d'affectations.</p>";
} else {
    echo "<p>Les tables existent déjà. Le problème pourrait être lié à la compatibilité entre les données et les algorithmes.</p>";
}

echo "<p><a href='/tutoring/views/admin/assignments/generate.php' class='btn btn-primary'>Retourner à la page de génération d'affectations</a></p>";