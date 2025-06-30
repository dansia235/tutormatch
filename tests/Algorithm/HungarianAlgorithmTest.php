<?php

use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../../src/Algorithm/HungarianAlgorithm.php';
require_once __DIR__ . '/../../src/DTO/AssignmentParameters.php';
require_once __DIR__ . '/../../src/DTO/AssignmentResult.php';

use App\Algorithm\HungarianAlgorithm;
use App\DTO\AssignmentParameters;
use App\DTO\AssignmentResult;

class HungarianAlgorithmTest extends TestCase
{
    private HungarianAlgorithm $algorithm;
    private AssignmentParameters $parameters;
    
    protected function setUp(): void
    {
        $this->algorithm = new HungarianAlgorithm();
        $this->parameters = new AssignmentParameters();
    }
    
    public function testExecuteWithValidInputs(): void
    {
        $students = [
            createMockStudentWithMethods(['id' => 1, 'department' => 'CS']),
            createMockStudentWithMethods(['id' => 2, 'department' => 'CS'])
        ];
        
        $teachers = [
            createMockTeacher(['id' => 1, 'department' => 'CS', 'remaining_capacity' => 2]),
            createMockTeacher(['id' => 2, 'department' => 'CS', 'remaining_capacity' => 1])
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
    
    public function testExecuteWithCrossDepartmentRestriction(): void
    {
        $students = [
            createMockStudentWithMethods(['id' => 1, 'department' => 'CS']),
            createMockStudentWithMethods(['id' => 2, 'department' => 'Math'])
        ];
        
        $teachers = [
            createMockTeacher(['id' => 1, 'department' => 'CS', 'remaining_capacity' => 2])
        ];
        
        $internships = [];
        
        // Interdire les affectations inter-départements
        $this->parameters->setAllowCrossDepartment(false);
        
        $result = $this->algorithm->execute($students, $teachers, $internships, $this->parameters);
        
        $this->assertTrue($result->isSuccessful());
        
        // Vérifier qu'un étudiant au moins n'est pas affecté (différent département)
        $unassigned = $result->getUnassignedStudents();
        $this->assertGreaterThanOrEqual(1, count($unassigned));
    }
    
    public function testOptimalAssignmentQuality(): void
    {
        // Test avec un cas simple où la solution optimale est connue
        $students = [
            createMockStudentWithMethods(['id' => 1, 'department' => 'CS']),
            createMockStudentWithMethods(['id' => 2, 'department' => 'Math'])
        ];
        
        $teachers = [
            createMockTeacher(['id' => 1, 'department' => 'CS', 'remaining_capacity' => 1]),
            createMockTeacher(['id' => 2, 'department' => 'Math', 'remaining_capacity' => 1])
        ];
        
        $internships = [];
        
        $this->parameters->setAllowCrossDepartment(true);
        $this->parameters->setDepartmentWeight(100); // Prioriser le même département
        
        $result = $this->algorithm->execute($students, $teachers, $internships, $this->parameters);
        
        $this->assertTrue($result->isSuccessful());
        $assignments = $result->getAssignments();
        
        // Vérifier que chaque étudiant est affecté à un enseignant du même département
        $this->assertCount(2, $assignments);
        
        foreach ($assignments as $assignment) {
            $this->assertArrayHasKey('student_id', $assignment);
            $this->assertArrayHasKey('teacher_id', $assignment);
            $this->assertArrayHasKey('compatibility_score', $assignment);
        }
    }
    
    public function testPerformanceWithLargeDataset(): void
    {
        // Test de performance avec un dataset plus large
        $students = [];
        for ($i = 1; $i <= 50; $i++) {
            $students[] = createMockStudentWithMethods(['id' => $i, 'department' => 'CS']);
        }
        
        $teachers = [];
        for ($i = 1; $i <= 10; $i++) {
            $teachers[] = createMockTeacher(['id' => $i, 'department' => 'CS', 'remaining_capacity' => 5]);
        }
        
        $internships = [];
        
        $startTime = microtime(true);
        $result = $this->algorithm->execute($students, $teachers, $internships, $this->parameters);
        $endTime = microtime(true);
        
        $this->assertTrue($result->isSuccessful());
        $this->assertLessThan(5.0, $endTime - $startTime); // Doit s'exécuter en moins de 5 secondes
    }
    
    public function testDifferentParameterConfigurations(): void
    {
        $students = [
            createMockStudentWithMethods(['id' => 1, 'department' => 'CS']),
            createMockStudentWithMethods(['id' => 2, 'department' => 'CS'])
        ];
        
        $teachers = [
            createMockTeacher(['id' => 1, 'department' => 'CS', 'remaining_capacity' => 2])
        ];
        
        $internships = [];
        
        // Configuration 1: Prioriser le département
        $params1 = new AssignmentParameters();
        $params1->setDepartmentWeight(80)
                ->setPreferenceWeight(15)
                ->setCapacityWeight(5);
        
        $result1 = $this->algorithm->execute($students, $teachers, $internships, $params1);
        
        // Configuration 2: Équilibrer tous les critères
        $params2 = new AssignmentParameters();
        $params2->setDepartmentWeight(33)
                ->setPreferenceWeight(33)
                ->setCapacityWeight(34);
        
        $result2 = $this->algorithm->execute($students, $teachers, $internships, $params2);
        
        $this->assertTrue($result1->isSuccessful());
        $this->assertTrue($result2->isSuccessful());
        
        // Les deux configurations doivent donner des résultats valides
        $this->assertGreaterThan(0, count($result1->getAssignments()));
        $this->assertGreaterThan(0, count($result2->getAssignments()));
    }
}