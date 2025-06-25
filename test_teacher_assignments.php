<?php
/**
 * Test script to debug why only one student shows in evaluations page
 */

require_once __DIR__ . '/includes/init.php';

// Check if user is logged in and is a teacher
requireRole('teacher');

// Get teacher
$teacherModel = new Teacher($db);
$teacher = $teacherModel->getByUserId($_SESSION['user_id']);

if (!$teacher) {
    die("Teacher profile not found");
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Teacher Assignments Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
        .section { margin: 30px 0; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Teacher Assignments Debug</h1>
    <p>Teacher: <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?> (ID: <?php echo $teacher['id']; ?>)</p>
    
    <div class="section">
        <h2>1. Testing Teacher::getAssignments()</h2>
        <?php
        $start_time = microtime(true);
        $assignmentsFromTeacher = $teacherModel->getAssignments($teacher['id']);
        $time1 = microtime(true) - $start_time;
        ?>
        <p>Execution time: <?php echo number_format($time1 * 1000, 2); ?> ms</p>
        <p>Number of assignments: <strong><?php echo count($assignmentsFromTeacher); ?></strong></p>
        
        <?php if (empty($assignmentsFromTeacher)): ?>
            <p class="warning">No assignments found using Teacher::getAssignments()</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Assignment ID</th>
                        <th>Student ID</th>
                        <th>Student Name</th>
                        <th>Internship ID</th>
                        <th>Status</th>
                        <th>Assignment Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assignmentsFromTeacher as $assignment): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($assignment['id']); ?></td>
                        <td><?php echo htmlspecialchars($assignment['student_id']); ?></td>
                        <td><?php echo htmlspecialchars($assignment['student_first_name'] . ' ' . $assignment['student_last_name']); ?></td>
                        <td><?php echo htmlspecialchars($assignment['internship_id'] ?? 'NULL'); ?></td>
                        <td><?php echo htmlspecialchars($assignment['status'] ?? 'NULL'); ?></td>
                        <td><?php echo htmlspecialchars($assignment['assignment_date'] ?? 'NULL'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
    <div class="section">
        <h2>2. Testing Assignment::getByTeacherId()</h2>
        <?php
        $assignmentModel = new Assignment($db);
        $start_time = microtime(true);
        $assignmentsFromAssignment = $assignmentModel->getByTeacherId($teacher['id']);
        $time2 = microtime(true) - $start_time;
        ?>
        <p>Execution time: <?php echo number_format($time2 * 1000, 2); ?> ms</p>
        <p>Number of assignments: <strong><?php echo count($assignmentsFromAssignment); ?></strong></p>
        
        <?php if (empty($assignmentsFromAssignment)): ?>
            <p class="warning">No assignments found using Assignment::getByTeacherId()</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Assignment ID</th>
                        <th>Student ID</th>
                        <th>Student Name</th>
                        <th>Internship ID</th>
                        <th>Status</th>
                        <th>Assignment Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assignmentsFromAssignment as $assignment): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($assignment['id']); ?></td>
                        <td><?php echo htmlspecialchars($assignment['student_id']); ?></td>
                        <td><?php echo htmlspecialchars($assignment['student_first_name'] . ' ' . $assignment['student_last_name']); ?></td>
                        <td><?php echo htmlspecialchars($assignment['internship_id'] ?? 'NULL'); ?></td>
                        <td><?php echo htmlspecialchars($assignment['status'] ?? 'NULL'); ?></td>
                        <td><?php echo htmlspecialchars($assignment['assignment_date'] ?? 'NULL'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
    <div class="section">
        <h2>3. Comparison Results</h2>
        <?php
        $count1 = count($assignmentsFromTeacher);
        $count2 = count($assignmentsFromAssignment);
        
        if ($count1 === $count2): ?>
            <p class="success">✓ Both methods return the same number of assignments: <?php echo $count1; ?></p>
        <?php else: ?>
            <p class="error">✗ Different results!</p>
            <p>Teacher::getAssignments(): <?php echo $count1; ?> assignments</p>
            <p>Assignment::getByTeacherId(): <?php echo $count2; ?> assignments</p>
        <?php endif; ?>
        
        <?php
        // Compare assignment IDs
        $ids1 = array_column($assignmentsFromTeacher, 'id');
        $ids2 = array_column($assignmentsFromAssignment, 'id');
        sort($ids1);
        sort($ids2);
        
        if ($ids1 === $ids2): ?>
            <p class="success">✓ Both methods return the same assignment IDs</p>
        <?php else: ?>
            <p class="error">✗ Different assignment IDs!</p>
            <?php
            $only_in_teacher = array_diff($ids1, $ids2);
            $only_in_assignment = array_diff($ids2, $ids1);
            
            if (!empty($only_in_teacher)): ?>
                <p>Only in Teacher::getAssignments(): <?php echo implode(', ', $only_in_teacher); ?></p>
            <?php endif; ?>
            
            <?php if (!empty($only_in_assignment)): ?>
                <p>Only in Assignment::getByTeacherId(): <?php echo implode(', ', $only_in_assignment); ?></p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <div class="section">
        <h2>4. Direct Database Query</h2>
        <?php
        // Run the exact query from Teacher::getAssignments()
        $query = "SELECT a.*, 
                  s.id as student_id, 
                  s.student_number,
                  u_s.id as student_user_id,
                  u_s.first_name as student_first_name, 
                  u_s.last_name as student_last_name,
                  u_s.email as student_email,
                  i.id as internship_id, 
                  i.title as internship_title, 
                  i.company_id,
                  c.name as company_name
                  FROM assignments a
                  JOIN students s ON a.student_id = s.id
                  JOIN users u_s ON s.user_id = u_s.id
                  JOIN internships i ON a.internship_id = i.id
                  JOIN companies c ON i.company_id = c.id
                  WHERE a.teacher_id = :teacher_id
                  ORDER BY a.assignment_date DESC";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':teacher_id', $teacher['id']);
        $stmt->execute();
        $directResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <p>Direct query results: <strong><?php echo count($directResults); ?></strong> assignments</p>
        
        <h3>SQL Query Used:</h3>
        <pre><?php echo htmlspecialchars($query); ?></pre>
        
        <?php if (count($directResults) !== $count1): ?>
            <p class="error">⚠ Direct query returns different results than Teacher::getAssignments()!</p>
        <?php endif; ?>
    </div>
    
    <div class="section">
        <h2>5. Database Integrity Check</h2>
        <?php
        // Check for assignments without required joins
        $checkQuery = "SELECT a.id, a.student_id, a.teacher_id, a.internship_id,
                       s.id as s_exists, i.id as i_exists, t.id as t_exists
                       FROM assignments a
                       LEFT JOIN students s ON a.student_id = s.id
                       LEFT JOIN internships i ON a.internship_id = i.id
                       LEFT JOIN teachers t ON a.teacher_id = t.id
                       WHERE a.teacher_id = :teacher_id
                       AND (s.id IS NULL OR i.id IS NULL OR t.id IS NULL)";
        
        $stmt = $db->prepare($checkQuery);
        $stmt->bindParam(':teacher_id', $teacher['id']);
        $stmt->execute();
        $orphaned = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        
        <?php if (empty($orphaned)): ?>
            <p class="success">✓ No orphaned assignments found</p>
        <?php else: ?>
            <p class="error">✗ Found <?php echo count($orphaned); ?> assignments with missing references:</p>
            <ul>
            <?php foreach ($orphaned as $orph): ?>
                <li>Assignment ID <?php echo $orph['id']; ?>: 
                    <?php if (!$orph['s_exists']): ?>Missing student <?php endif; ?>
                    <?php if (!$orph['i_exists']): ?>Missing internship <?php endif; ?>
                    <?php if (!$orph['t_exists']): ?>Missing teacher <?php endif; ?>
                </li>
            <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        
        <?php
        // Check assignment dates
        $dateQuery = "SELECT id, assignment_date FROM assignments WHERE teacher_id = :teacher_id";
        $stmt = $db->prepare($dateQuery);
        $stmt->bindParam(':teacher_id', $teacher['id']);
        $stmt->execute();
        $dates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $nullDates = array_filter($dates, function($d) { return $d['assignment_date'] === null; });
        ?>
        
        <p>Assignment dates: <?php echo count($dates); ?> total, <?php echo count($nullDates); ?> with NULL dates</p>
        <?php if (count($nullDates) > 0): ?>
            <p class="warning">⚠ Assignments with NULL dates may affect ordering</p>
        <?php endif; ?>
    </div>
    
    <div class="section">
        <h2>6. View-Specific Test</h2>
        <p>This simulates what happens in evaluations.php:</p>
        <?php
        // Exactly like in evaluations.php
        $assignments = $teacherModel->getAssignments($teacher['id']);
        ?>
        <p>$assignments = $teacherModel->getAssignments($teacher['id']);</p>
        <p>Result: <strong><?php echo count($assignments); ?></strong> assignments</p>
        
        <h3>Student dropdown would show:</h3>
        <select class="form-select" disabled>
            <option>Choisir un étudiant...</option>
            <?php foreach ($assignments as $assignment): ?>
            <option><?php echo htmlspecialchars($assignment['student_first_name'] . ' ' . $assignment['student_last_name']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</body>
</html>