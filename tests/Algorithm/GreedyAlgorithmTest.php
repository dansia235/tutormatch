<?php
namespace Tests\Algorithm;

use PHPUnit\Framework\TestCase;
use App\Algorithm\GreedyAlgorithm;
use App\DTO\AssignmentParameters;
use App\DTO\AssignmentResult;

class GreedyAlgorithmTest extends TestCase
{
    private GreedyAlgorithm $algorithm;
    private AssignmentParameters $parameters;

    protected function setUp(): void
    {
        $this->algorithm = new GreedyAlgorithm();
        $this->parameters = new AssignmentParameters();
    }

    public function testExecuteWithEmptyStudents()
    {
        $result = $this->algorithm->execute([], $this->createTeachers(2), [], $this->parameters);
        
        $this->assertFalse($result->isSuccessful());
        $this->assertEquals("Aucun étudiant disponible pour l'affectation", $result->getErrorMessage());
    }

    public function testExecuteWithEmptyTeachers()
    {
        $result = $this->algorithm->execute($this->createStudents(2), [], [], $this->parameters);
        
        $this->assertFalse($result->isSuccessful());
        $this->assertEquals("Aucun enseignant disponible pour l'affectation", $result->getErrorMessage());
    }

    public function testSuccessfulAssignment()
    {
        $students = $this->createStudents(3);
        $teachers = $this->createTeachers(2);
        
        $result = $this->algorithm->execute($students, $teachers, [], $this->parameters);
        
        $this->assertTrue($result->isSuccessful());
        $this->assertCount(3, $result->getAssignments());
        $this->assertCount(0, $result->getUnassignedStudents());
        $this->assertGreaterThan(0, $result->getAverageScore());
        $this->assertGreaterThan(0, $result->getExecutionTime());
    }

    public function testPartialAssignmentWithLimitedCapacity()
    {
        $students = $this->createStudents(5);
        $teachers = $this->createTeachers(1, 3); // 1 teacher with capacity of 3
        
        $result = $this->algorithm->execute($students, $teachers, [], $this->parameters);
        
        $this->assertTrue($result->isSuccessful());
        $this->assertCount(3, $result->getAssignments());
        $this->assertCount(2, $result->getUnassignedStudents());
    }

    public function testDepartmentConstraintEnforced()
    {
        $students = [
            $this->createStudent(1, 'Computer Science'),
            $this->createStudent(2, 'Mathematics')
        ];
        
        $teachers = [
            $this->createTeacher(1, 'Computer Science')
        ];
        
        // Disable cross-department assignments
        $this->parameters->setAllowCrossDepartment(false);
        
        $result = $this->algorithm->execute($students, $teachers, [], $this->parameters);
        
        $this->assertTrue($result->isSuccessful());
        $this->assertCount(1, $result->getAssignments());
        $this->assertCount(1, $result->getUnassignedStudents());
        
        // Get the assigned student ID
        $assignedStudentId = $result->getAssignments()[0]['student_id'];
        $this->assertEquals(1, $assignedStudentId);
    }

    public function testDepartmentConstraintDisabled()
    {
        $students = [
            $this->createStudent(1, 'Computer Science'),
            $this->createStudent(2, 'Mathematics')
        ];
        
        $teachers = [
            $this->createTeacher(1, 'Computer Science')
        ];
        
        // Enable cross-department assignments
        $this->parameters->setAllowCrossDepartment(true);
        
        $result = $this->algorithm->execute($students, $teachers, [], $this->parameters);
        
        $this->assertTrue($result->isSuccessful());
        $this->assertCount(2, $result->getAssignments());
        $this->assertCount(0, $result->getUnassignedStudents());
    }

    public function testAssignmentsOrderedByCompatibilityScore()
    {
        $students = $this->createStudents(3);
        $teachers = $this->createTeachers(2);
        
        $result = $this->algorithm->execute($students, $teachers, [], $this->parameters);
        
        $this->assertTrue($result->isSuccessful());
        $this->assertCount(3, $result->getAssignments());
        
        // Extract scores from assignments
        $scores = array_map(function($assignment) {
            return $assignment['compatibility_score'];
        }, $result->getAssignments());
        
        // Check that scores are in descending order
        $sortedScores = $scores;
        rsort($sortedScores);
        $this->assertEquals($sortedScores, $scores);
    }

    public function testPreferenceWeightImpactsAssignments()
    {
        $students = [
            $this->createStudent(1, 'Computer Science'),
            $this->createStudent(2, 'Computer Science')
        ];
        
        $teachers = [
            $this->createTeacher(1, 'Computer Science', 1) // Only has capacity for 1 student
        ];
        
        // First run with normal preference weight
        $this->parameters->setPreferenceWeight(30);
        $this->parameters->setPrioritizePreferences(true);
        $normalResult = $this->algorithm->execute($students, $teachers, [], $this->parameters);
        
        // Then run with increased preference weight
        $this->parameters = new AssignmentParameters();
        $this->parameters->setPreferenceWeight(80);
        $this->parameters->setPrioritizePreferences(true);
        $highPrefResult = $this->algorithm->execute($students, $teachers, [], $this->parameters);
        
        // Both should have 1 assignment and 1 unassigned student
        $this->assertCount(1, $normalResult->getAssignments());
        $this->assertCount(1, $normalResult->getUnassignedStudents());
        $this->assertCount(1, $highPrefResult->getAssignments());
        $this->assertCount(1, $highPrefResult->getUnassignedStudents());
        
        // The high preference weight should result in a higher average score
        $this->assertGreaterThan($normalResult->getAverageScore(), $highPrefResult->getAverageScore());
    }
    
    public function testCapacityWeightImpactsAssignments()
    {
        $students = $this->createStudents(6);
        
        $teachers = [
            $this->createTeacher(1, 'Computer Science', 3), // Capacity of 3
            $this->createTeacher(2, 'Computer Science', 3)  // Capacity of 3
        ];
        
        // First run without workload balancing
        $this->parameters->setBalanceWorkload(false);
        $unbalancedResult = $this->algorithm->execute($students, $teachers, [], $this->parameters);
        
        // Then run with workload balancing
        $this->parameters = new AssignmentParameters();
        $this->parameters->setBalanceWorkload(true);
        $this->parameters->setCapacityWeight(70); // High weight for capacity balancing
        $balancedResult = $this->algorithm->execute($students, $teachers, [], $this->parameters);
        
        // Count assignments per teacher
        $unbalancedCounts = $this->countAssignmentsPerTeacher($unbalancedResult->getAssignments());
        $balancedCounts = $this->countAssignmentsPerTeacher($balancedResult->getAssignments());
        
        // With balancing, the distribution should be more even
        $this->assertLessThanOrEqual(
            max($unbalancedCounts) - min($unbalancedCounts),
            max($balancedCounts) - min($balancedCounts)
        );
    }
    
    public function testExecutionTimeTracking()
    {
        // Test with a small dataset
        $smallStudents = $this->createStudents(5);
        $smallTeachers = $this->createTeachers(2);
        
        $smallResult = $this->algorithm->execute($smallStudents, $smallTeachers, [], $this->parameters);
        $smallTime = $smallResult->getExecutionTime();
        
        // Test with a larger dataset
        $largeStudents = $this->createStudents(20);
        $largeTeachers = $this->createTeachers(5);
        
        $largeResult = $this->algorithm->execute($largeStudents, $largeTeachers, [], $this->parameters);
        $largeTime = $largeResult->getExecutionTime();
        
        // Larger dataset should take more time to process
        $this->assertGreaterThan($smallTime, $largeTime);
    }
    
    public function testAllPossibleCombinationsAreEvaluated()
    {
        $students = $this->createStudents(3);
        $teachers = $this->createTeachers(2, 10); // Each teacher can take 10 students
        
        $result = $this->algorithm->execute($students, $teachers, [], $this->parameters);
        
        // All students should be assigned
        $this->assertCount(3, $result->getAssignments());
        $this->assertCount(0, $result->getUnassignedStudents());
        
        // Each student has 2 possible teachers, so there should be 6 possible combinations
        // The algorithm should pick the 3 best combinations
        $studentIds = array_map(function($assignment) {
            return $assignment['student_id'];
        }, $result->getAssignments());
        
        // Each student should appear exactly once
        $this->assertEquals(count($studentIds), count(array_unique($studentIds)));
    }
    
    /**
     * Helper method to count how many students are assigned to each teacher
     */
    private function countAssignmentsPerTeacher(array $assignments): array
    {
        $counts = [];
        foreach ($assignments as $assignment) {
            $teacherId = $assignment['teacher_id'];
            if (!isset($counts[$teacherId])) {
                $counts[$teacherId] = 0;
            }
            $counts[$teacherId]++;
        }
        return $counts;
    }

    /**
     * Creates an array of student test objects
     */
    private function createStudents(int $count): array
    {
        $students = [];
        for ($i = 1; $i <= $count; $i++) {
            $students[] = $this->createStudent($i);
        }
        return $students;
    }
    
    /**
     * Creates a single student test object
     */
    private function createStudent(int $id, string $department = 'Computer Science'): object
    {
        return new class($id, $department) {
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
    
    /**
     * Creates an array of teacher test objects
     */
    private function createTeachers(int $count, int $capacity = 5): array
    {
        $teachers = [];
        for ($i = 1; $i <= $count; $i++) {
            $teachers[] = $this->createTeacher($i, 'Computer Science', $capacity);
        }
        return $teachers;
    }
    
    /**
     * Creates a single teacher test object
     */
    private function createTeacher(int $id, string $department = 'Computer Science', int $capacity = 5): object
    {
        return new class($id, $department, $capacity) {
            private $id;
            private $department;
            private $maxStudents;
            private $remainingCapacity;
            
            public function __construct(int $id, string $department, int $capacity)
            {
                $this->id = $id;
                $this->department = $department;
                $this->maxStudents = $capacity;
                $this->remainingCapacity = $capacity;
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
}