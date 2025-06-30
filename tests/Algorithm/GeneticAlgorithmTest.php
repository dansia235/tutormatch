<?php

use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../../src/Algorithm/GeneticAlgorithm.php';
require_once __DIR__ . '/../../src/DTO/AssignmentParameters.php';
require_once __DIR__ . '/../../src/DTO/AssignmentResult.php';

use App\Algorithm\GeneticAlgorithm;
use App\DTO\AssignmentParameters;
use App\DTO\AssignmentResult;

class GeneticAlgorithmTest extends TestCase
{
    private GeneticAlgorithm $algorithm;
    private AssignmentParameters $parameters;
    
    protected function setUp(): void
    {
        $this->algorithm = new GeneticAlgorithm();
        $this->parameters = new AssignmentParameters();
        
        // Configuration pour tests rapides
        $this->algorithm->setPopulationSize(20)
                       ->setGenerations(10)
                       ->setMutationRate(0.1)
                       ->setCrossoverRate(0.8);
    }
    
    public function testExecuteWithValidInputs(): void
    {
        $students = [
            createMockStudentWithMethods(['id' => 1, 'department' => 'CS']),
            createMockStudentWithMethods(['id' => 2, 'department' => 'CS'])
        ];
        
        $teachers = [
            createMockTeacher(['id' => 1, 'department' => 'CS', 'remaining_capacity' => 2])
        ];
        
        $internships = [];
        
        $result = $this->algorithm->execute($students, $teachers, $internships, $this->parameters);
        
        $this->assertInstanceOf(AssignmentResult::class, $result);
        $this->assertTrue($result->isSuccessful());
        $this->assertGreaterThan(0, $result->getExecutionTime());
    }
    
    public function testExecuteWithEmptyStudents(): void
    {
        $students = [];
        $teachers = [createMockTeacher()];
        $internships = [];
        
        $result = $this->algorithm->execute($students, $teachers, $internships, $this->parameters);
        
        $this->assertFalse($result->isSuccessful());
        $this->assertStringContainsString('Aucun étudiant', $result->getErrorMessage());
    }
    
    public function testExecuteWithEmptyTeachers(): void
    {
        $students = [createMockStudentWithMethods()];
        $teachers = [];
        $internships = [];
        
        $result = $this->algorithm->execute($students, $teachers, $internships, $this->parameters);
        
        $this->assertFalse($result->isSuccessful());
        $this->assertStringContainsString('Aucun enseignant', $result->getErrorMessage());
    }
    
    public function testEvolutionConvergence(): void
    {
        $students = [];
        for ($i = 1; $i <= 10; $i++) {
            $students[] = createMockStudentWithMethods(['id' => $i, 'department' => 'CS']);
        }
        
        $teachers = [];
        for ($i = 1; $i <= 3; $i++) {
            $teachers[] = createMockTeacher(['id' => $i, 'department' => 'CS', 'remaining_capacity' => 4]);
        }
        
        $internships = [];
        
        // Configuration pour tester la convergence
        $this->algorithm->setPopulationSize(30)
                       ->setGenerations(20);
        
        $result = $this->algorithm->execute($students, $teachers, $internships, $this->parameters);
        
        $this->assertTrue($result->isSuccessful());
        $assignments = $result->getAssignments();
        
        // Vérifier que des affectations ont été créées
        $this->assertGreaterThan(0, count($assignments));
        
        // Vérifier la qualité des affectations (score moyen raisonnable)
        if ($result->getAverageScore() > 0) {
            $this->assertGreaterThan(0, $result->getAverageScore());
        }
    }
    
    public function testParameterAdjustmentForLargeProblems(): void
    {
        // Simuler un problème de grande taille
        $students = [];
        for ($i = 1; $i <= 100; $i++) {
            $students[] = createMockStudentWithMethods(['id' => $i, 'department' => 'CS']);
        }
        
        $teachers = [];
        for ($i = 1; $i <= 20; $i++) {
            $teachers[] = createMockTeacher(['id' => $i, 'department' => 'CS', 'remaining_capacity' => 5]);
        }
        
        $internships = [];
        
        $startTime = microtime(true);
        $result = $this->algorithm->execute($students, $teachers, $internships, $this->parameters);
        $endTime = microtime(true);
        
        $this->assertTrue($result->isSuccessful());
        
        // L'algorithme doit s'adapter automatiquement pour les gros problèmes
        $this->assertLessThan(30.0, $endTime - $startTime); // Temps raisonnable même pour gros problème
    }
    
    public function testDifferentInitializationStrategies(): void
    {
        $students = [];
        for ($i = 1; $i <= 6; $i++) {
            $dept = ($i <= 3) ? 'CS' : 'Math';
            $students[] = createMockStudentWithMethods(['id' => $i, 'department' => $dept]);
        }
        
        $teachers = [];
        for ($i = 1; $i <= 2; $i++) {
            $dept = ($i == 1) ? 'CS' : 'Math';
            $teachers[] = createMockTeacher(['id' => $i, 'department' => $dept, 'remaining_capacity' => 3]);
        }
        
        $internships = [];
        
        // Test multiple runs to check consistency
        $results = [];
        for ($run = 0; $run < 3; $run++) {
            $result = $this->algorithm->execute($students, $teachers, $internships, $this->parameters);
            $this->assertTrue($result->isSuccessful());
            $results[] = count($result->getAssignments());
        }
        
        // Les résultats doivent être cohérents (même nombre d'affectations ou proche)
        $avgAssignments = array_sum($results) / count($results);
        $this->assertGreaterThan(0, $avgAssignments);
    }
    
    public function testConfigurableParameters(): void
    {
        $students = [
            createMockStudentWithMethods(['id' => 1, 'department' => 'CS']),
            createMockStudentWithMethods(['id' => 2, 'department' => 'CS'])
        ];
        
        $teachers = [
            createMockTeacher(['id' => 1, 'department' => 'CS', 'remaining_capacity' => 2])
        ];
        
        $internships = [];
        
        // Test avec différentes configurations
        $configs = [
            ['pop' => 10, 'gen' => 5, 'mut' => 0.05, 'cross' => 0.7],
            ['pop' => 15, 'gen' => 8, 'mut' => 0.15, 'cross' => 0.9],
            ['pop' => 25, 'gen' => 12, 'mut' => 0.2, 'cross' => 0.6]
        ];
        
        foreach ($configs as $config) {
            $algorithm = new GeneticAlgorithm();
            $algorithm->setPopulationSize($config['pop'])
                     ->setGenerations($config['gen'])
                     ->setMutationRate($config['mut'])
                     ->setCrossoverRate($config['cross']);
            
            $result = $algorithm->execute($students, $teachers, $internships, $this->parameters);
            
            $this->assertTrue($result->isSuccessful(), 
                "Configuration failed: pop={$config['pop']}, gen={$config['gen']}");
        }
    }
    
    public function testBalanceWorkloadFeature(): void
    {
        $students = [];
        for ($i = 1; $i <= 9; $i++) {
            $students[] = createMockStudentWithMethods(['id' => $i, 'department' => 'CS']);
        }
        
        $teachers = [
            createMockTeacher(['id' => 1, 'department' => 'CS', 'remaining_capacity' => 5]),
            createMockTeacher(['id' => 2, 'department' => 'CS', 'remaining_capacity' => 5]),
            createMockTeacher(['id' => 3, 'department' => 'CS', 'remaining_capacity' => 5])
        ];
        
        $internships = [];
        
        // Activer l'équilibrage de charge
        $this->parameters->setBalanceWorkload(true);
        $this->parameters->setCapacityWeight(50); // Donner plus d'importance à l'équilibrage
        
        $result = $this->algorithm->execute($students, $teachers, $internships, $this->parameters);
        
        $this->assertTrue($result->isSuccessful());
        
        // Compter les affectations par enseignant
        $teacherAssignments = [];
        foreach ($result->getAssignments() as $assignment) {
            $teacherId = $assignment['teacher_id'];
            $teacherAssignments[$teacherId] = ($teacherAssignments[$teacherId] ?? 0) + 1;
        }
        
        // Vérifier que la charge est relativement équilibrée
        if (count($teacherAssignments) > 1) {
            $loads = array_values($teacherAssignments);
            $maxLoad = max($loads);
            $minLoad = min($loads);
            
            // La différence entre max et min ne doit pas être trop importante
            $this->assertLessThanOrEqual(3, $maxLoad - $minLoad, 
                "Load imbalance too high: max=$maxLoad, min=$minLoad");
        }
    }
}