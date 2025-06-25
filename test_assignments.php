<?php
// Test script to compare Assignment::getByTeacherId() and Teacher::getAssignments()

require_once __DIR__ . '/includes/init.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die("User not logged in");
}

// Get teacher
$teacherModel = new Teacher($db);
$teacher = $teacherModel->getByUserId($_SESSION['user_id']);

if (!$teacher) {
    die("Teacher not found");
}

echo "<h1>Testing Assignment Methods for Teacher ID: " . $teacher['id'] . "</h1>";

// Test Teacher::getAssignments()
echo "<h2>1. Teacher::getAssignments()</h2>";
$assignments1 = $teacherModel->getAssignments($teacher['id']);
echo "<p>Count: " . count($assignments1) . "</p>";
echo "<pre>";
foreach ($assignments1 as $i => $assignment) {
    echo "Assignment #" . ($i + 1) . ":\n";
    echo "  - ID: " . $assignment['id'] . "\n";
    echo "  - Student: " . $assignment['student_first_name'] . " " . $assignment['student_last_name'] . " (ID: " . $assignment['student_id'] . ")\n";
    echo "  - Assignment Date: " . ($assignment['assignment_date'] ?? 'NULL') . "\n";
    echo "  - Status: " . ($assignment['status'] ?? 'NULL') . "\n";
    echo "\n";
}
echo "</pre>";

// Test Assignment::getByTeacherId()
echo "<h2>2. Assignment::getByTeacherId()</h2>";
$assignmentModel = new Assignment($db);
$assignments2 = $assignmentModel->getByTeacherId($teacher['id']);
echo "<p>Count: " . count($assignments2) . "</p>";
echo "<pre>";
foreach ($assignments2 as $i => $assignment) {
    echo "Assignment #" . ($i + 1) . ":\n";
    echo "  - ID: " . $assignment['id'] . "\n";
    echo "  - Student: " . $assignment['student_first_name'] . " " . $assignment['student_last_name'] . " (ID: " . $assignment['student_id'] . ")\n";
    echo "  - Assignment Date: " . ($assignment['assignment_date'] ?? 'NULL') . "\n";
    echo "  - Status: " . ($assignment['status'] ?? 'NULL') . "\n";
    echo "\n";
}
echo "</pre>";

// Compare the results
echo "<h2>3. Comparison</h2>";
if (count($assignments1) === count($assignments2)) {
    echo "<p style='color: green;'>✓ Both methods return the same number of assignments.</p>";
} else {
    echo "<p style='color: red;'>✗ Different number of assignments returned!</p>";
    echo "<p>Teacher::getAssignments(): " . count($assignments1) . " assignments</p>";
    echo "<p>Assignment::getByTeacherId(): " . count($assignments2) . " assignments</p>";
}

// Check for differences in assignment IDs
$ids1 = array_column($assignments1, 'id');
$ids2 = array_column($assignments2, 'id');
sort($ids1);
sort($ids2);

if ($ids1 === $ids2) {
    echo "<p style='color: green;'>✓ Both methods return the same assignment IDs.</p>";
} else {
    echo "<p style='color: red;'>✗ Different assignment IDs returned!</p>";
    $missing_in_2 = array_diff($ids1, $ids2);
    $missing_in_1 = array_diff($ids2, $ids1);
    
    if (!empty($missing_in_2)) {
        echo "<p>IDs in Teacher::getAssignments() but not in Assignment::getByTeacherId(): " . implode(', ', $missing_in_2) . "</p>";
    }
    if (!empty($missing_in_1)) {
        echo "<p>IDs in Assignment::getByTeacherId() but not in Teacher::getAssignments(): " . implode(', ', $missing_in_1) . "</p>";
    }
}

// Direct SQL query test
echo "<h2>4. Direct SQL Query Test</h2>";
$query = "SELECT a.*, 
          s.student_number, u_s.first_name as student_first_name, u_s.last_name as student_last_name
          FROM assignments a
          JOIN students s ON a.student_id = s.id
          JOIN users u_s ON s.user_id = u_s.id
          WHERE a.teacher_id = :teacher_id
          ORDER BY a.assignment_date DESC";
          
$stmt = $db->prepare($query);
$stmt->bindParam(':teacher_id', $teacher['id']);
$stmt->execute();
$direct_results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p>Direct query count: " . count($direct_results) . "</p>";
echo "<pre>";
foreach ($direct_results as $i => $assignment) {
    echo "Assignment #" . ($i + 1) . ":\n";
    echo "  - ID: " . $assignment['id'] . "\n";
    echo "  - Student: " . $assignment['student_first_name'] . " " . $assignment['student_last_name'] . " (ID: " . $assignment['student_id'] . ")\n";
    echo "  - Assignment Date: " . ($assignment['assignment_date'] ?? 'NULL') . "\n";
    echo "  - Status: " . ($assignment['status'] ?? 'NULL') . "\n";
    echo "\n";
}
echo "</pre>";

// Check assignment_date values
echo "<h2>5. Assignment Date Analysis</h2>";
$dates = array_column($direct_results, 'assignment_date');
$unique_dates = array_unique($dates);
$null_dates = array_filter($dates, function($date) { return $date === null; });

echo "<p>Total assignments: " . count($dates) . "</p>";
echo "<p>Unique dates: " . count($unique_dates) . "</p>";
echo "<p>NULL dates: " . count($null_dates) . "</p>";

if (count($null_dates) > 0) {
    echo "<p style='color: orange;'>⚠ Some assignments have NULL assignment_date values, which might affect ordering.</p>";
}

// Check for evaluations
echo "<h2>6. Evaluations Check</h2>";
if (class_exists('Evaluation')) {
    $evaluationModel = new Evaluation($db);
    $evaluations = $evaluationModel->getByTeacherId($teacher['id']);
    echo "<p>Total evaluations for teacher: " . count($evaluations) . "</p>";
    
    // Group evaluations by assignment
    $evalsByAssignment = [];
    foreach ($evaluations as $eval) {
        $assignmentId = $eval['assignment_id'];
        if (!isset($evalsByAssignment[$assignmentId])) {
            $evalsByAssignment[$assignmentId] = [];
        }
        $evalsByAssignment[$assignmentId][] = $eval;
    }
    
    echo "<p>Assignments with evaluations: " . count($evalsByAssignment) . "</p>";
    
    foreach ($evalsByAssignment as $assignmentId => $evals) {
        echo "<p>Assignment #$assignmentId has " . count($evals) . " evaluation(s)</p>";
    }
} else {
    echo "<p>Evaluation class not found</p>";
}