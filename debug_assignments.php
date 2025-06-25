<?php
/**
 * Script de débogage pour analyser le problème d'affichage des affectations
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/includes/init.php';

// Vérifier que l'utilisateur est tuteur
requireRole('teacher');

// Récupérer le tuteur de la session
$teacherModel = new Teacher($db);
$teacher = $teacherModel->getByUserId($_SESSION['user_id']);

if (!$teacher) {
    die("Tuteur non trouvé");
}

echo "<h1>Débogage des affectations pour le tuteur: " . $teacher['first_name'] . " " . $teacher['last_name'] . "</h1>";
echo "<p>Teacher ID: " . $teacher['id'] . "</p>";

// Test 1: Méthode Assignment::getByTeacherId()
echo "<h2>Test 1: Assignment::getByTeacherId() (APRÈS CORRECTION LEFT JOIN)</h2>";
$assignmentModel = new Assignment($db);
$assignments1 = $assignmentModel->getByTeacherId($teacher['id']);
echo "<p>Nombre d'affectations trouvées: <strong>" . count($assignments1) . "</strong></p>";

if (!empty($assignments1)) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Student ID</th><th>Student Name</th><th>Status</th><th>Assignment Date</th><th>Internship Title</th></tr>";
    foreach ($assignments1 as $assignment) {
        echo "<tr>";
        echo "<td>" . ($assignment['id'] ?? 'NULL') . "</td>";
        echo "<td>" . ($assignment['student_id'] ?? 'NULL') . "</td>";
        echo "<td>" . ($assignment['student_first_name'] ?? '') . " " . ($assignment['student_last_name'] ?? '') . "</td>";
        echo "<td>" . ($assignment['status'] ?? 'NULL') . "</td>";
        echo "<td>" . ($assignment['assignment_date'] ?? 'NULL') . "</td>";
        echo "<td>" . ($assignment['internship_title'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Test 2: Méthode Teacher::getAssignments()
echo "<h2>Test 2: Teacher::getAssignments() (APRÈS CORRECTION LEFT JOIN)</h2>";
$assignments2 = $teacherModel->getAssignments($teacher['id']);
echo "<p>Nombre d'affectations trouvées: <strong>" . count($assignments2) . "</strong></p>";

if (!empty($assignments2)) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Student ID</th><th>Student Name</th><th>Status</th><th>Assignment Date</th><th>Internship Title</th></tr>";
    foreach ($assignments2 as $assignment) {
        echo "<tr>";
        echo "<td>" . ($assignment['id'] ?? 'NULL') . "</td>";
        echo "<td>" . ($assignment['student_id'] ?? 'NULL') . "</td>";
        echo "<td>" . ($assignment['student_first_name'] ?? '') . " " . ($assignment['student_last_name'] ?? '') . "</td>";
        echo "<td>" . ($assignment['status'] ?? 'NULL') . "</td>";
        echo "<td>" . ($assignment['assignment_date'] ?? 'NULL') . "</td>";
        echo "<td>" . ($assignment['internship_title'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Test 3: Requête SQL directe
echo "<h2>Test 3: Requête SQL directe (toutes les affectations)</h2>";
$query = "SELECT a.*, 
          s.id as student_id, s.student_number, u_s.first_name as student_first_name, u_s.last_name as student_last_name,
          i.id as internship_id, i.title as internship_title, 
          c.id as company_id, c.name as company_name
          FROM assignments a
          LEFT JOIN students s ON a.student_id = s.id
          LEFT JOIN users u_s ON s.user_id = u_s.id
          LEFT JOIN internships i ON a.internship_id = i.id
          LEFT JOIN companies c ON i.company_id = c.id
          WHERE a.teacher_id = :teacher_id
          ORDER BY a.assignment_date DESC";

$stmt = $db->prepare($query);
$stmt->bindParam(':teacher_id', $teacher['id']);
$stmt->execute();
$assignments3 = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p>Nombre d'affectations trouvées (avec LEFT JOIN): <strong>" . count($assignments3) . "</strong></p>";

if (!empty($assignments3)) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Student ID</th><th>Student Name</th><th>Status</th><th>Assignment Date</th><th>Internship Title</th><th>Company Name</th><th>Problèmes</th></tr>";
    foreach ($assignments3 as $assignment) {
        $problems = [];
        if (!$assignment['student_id']) $problems[] = "Student manquant";
        if (!$assignment['internship_id']) $problems[] = "Internship manquant";
        if (!$assignment['company_id']) $problems[] = "Company manquante";
        if (!$assignment['assignment_date']) $problems[] = "Date manquante";
        
        echo "<tr>";
        echo "<td>" . ($assignment['id'] ?? 'NULL') . "</td>";
        echo "<td>" . ($assignment['student_id'] ?? 'NULL') . "</td>";
        echo "<td>" . ($assignment['student_first_name'] ?? '') . " " . ($assignment['student_last_name'] ?? '') . "</td>";
        echo "<td>" . ($assignment['status'] ?? 'NULL') . "</td>";
        echo "<td>" . ($assignment['assignment_date'] ?? 'NULL') . "</td>";
        echo "<td>" . ($assignment['internship_title'] ?? 'NULL') . "</td>";
        echo "<td>" . ($assignment['company_name'] ?? 'NULL') . "</td>";
        echo "<td style='color: red;'>" . implode(", ", $problems) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Test 4: Vérifier les données manquantes
echo "<h2>Test 4: Vérification de l'intégrité des données</h2>";

// Compter les affectations pour ce tuteur
$stmt = $db->prepare("SELECT COUNT(*) FROM assignments WHERE teacher_id = :teacher_id");
$stmt->bindParam(':teacher_id', $teacher['id']);
$stmt->execute();
$totalAssignments = $stmt->fetchColumn();
echo "<p>Total des affectations dans la table assignments: <strong>$totalAssignments</strong></p>";

// Compter les étudiants référencés
$stmt = $db->prepare("SELECT COUNT(DISTINCT a.student_id) FROM assignments a WHERE a.teacher_id = :teacher_id AND a.student_id IS NOT NULL");
$stmt->bindParam(':teacher_id', $teacher['id']);
$stmt->execute();
$studentsCount = $stmt->fetchColumn();
echo "<p>Étudiants uniques référencés: <strong>$studentsCount</strong></p>";

// Vérifier les étudiants manquants
$stmt = $db->prepare("SELECT a.id, a.student_id FROM assignments a WHERE a.teacher_id = :teacher_id AND a.student_id NOT IN (SELECT id FROM students)");
$stmt->bindParam(':teacher_id', $teacher['id']);
$stmt->execute();
$missingStudents = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (!empty($missingStudents)) {
    echo "<p style='color: red;'>Affectations avec étudiants manquants:</p>";
    foreach ($missingStudents as $missing) {
        echo "<p>Assignment ID: " . $missing['id'] . ", Student ID: " . $missing['student_id'] . "</p>";
    }
}

// Vérifier les stages manquants
$stmt = $db->prepare("SELECT a.id, a.internship_id FROM assignments a WHERE a.teacher_id = :teacher_id AND a.internship_id NOT IN (SELECT id FROM internships)");
$stmt->bindParam(':teacher_id', $teacher['id']);
$stmt->execute();
$missingInternships = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (!empty($missingInternships)) {
    echo "<p style='color: red;'>Affectations avec stages manquants:</p>";
    foreach ($missingInternships as $missing) {
        echo "<p>Assignment ID: " . $missing['id'] . ", Internship ID: " . $missing['internship_id'] . "</p>";
    }
}

// Test 5: Méthode Assignment::getAll() (page admin)
echo "<h2>Test 5: Assignment::getAll() - Comme dans la page admin (APRÈS CORRECTION LEFT JOIN)</h2>";
$assignments5 = $assignmentModel->getAll();
echo "<p>Nombre total d'affectations trouvées: <strong>" . count($assignments5) . "</strong></p>";

// Compter les affectations pour ce tuteur dans getAll()
$thisTeacherAssignments = array_filter($assignments5, function($assignment) use ($teacher) {
    return $assignment['teacher_id'] == $teacher['id'];
});
echo "<p>Affectations pour ce tuteur dans getAll(): <strong>" . count($thisTeacherAssignments) . "</strong></p>";

// Afficher les détails des affectations trouvées pour ce tuteur
if (!empty($thisTeacherAssignments)) {
    echo "<h3>Détails des affectations trouvées pour ce tuteur:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Assignment ID</th><th>Student Name</th><th>Teacher ID</th><th>Teacher Name</th><th>Status</th></tr>";
    foreach ($thisTeacherAssignments as $assignment) {
        echo "<tr>";
        echo "<td>" . ($assignment['id'] ?? 'NULL') . "</td>";
        echo "<td>" . ($assignment['student_first_name'] ?? '') . " " . ($assignment['student_last_name'] ?? '') . "</td>";
        echo "<td>" . ($assignment['teacher_id'] ?? 'NULL') . "</td>";
        echo "<td>" . ($assignment['teacher_first_name'] ?? '') . " " . ($assignment['teacher_last_name'] ?? '') . "</td>";
        echo "<td>" . ($assignment['status'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Test 6: Comparaison avec requête SQL directe
echo "<h2>Test 6: Requête SQL directe pour vérifier les teacher_id</h2>";
$stmt = $db->prepare("SELECT a.id, a.teacher_id, a.student_id, u_s.first_name as student_first, u_s.last_name as student_last, u_t.first_name as teacher_first, u_t.last_name as teacher_last FROM assignments a LEFT JOIN students s ON a.student_id = s.id LEFT JOIN users u_s ON s.user_id = u_s.id LEFT JOIN teachers t ON a.teacher_id = t.id LEFT JOIN users u_t ON t.user_id = u_t.id ORDER BY a.id");
$stmt->execute();
$directQuery = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p>Toutes les affectations avec noms (requête directe):</p>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Assignment ID</th><th>Teacher ID</th><th>Teacher Name</th><th>Student Name</th></tr>";
foreach ($directQuery as $row) {
    $highlight = ($row['teacher_id'] == $teacher['id']) ? 'style="background-color: yellow;"' : '';
    echo "<tr $highlight>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . $row['teacher_id'] . "</td>";
    echo "<td>" . ($row['teacher_first'] ?? 'NULL') . " " . ($row['teacher_last'] ?? 'NULL') . "</td>";
    echo "<td>" . ($row['student_first'] ?? 'NULL') . " " . ($row['student_last'] ?? 'NULL') . "</td>";
    echo "</tr>";
}
echo "</table>";

// Compter Thomas Robert dans la requête directe
$thomasRobertCount = 0;
foreach ($directQuery as $row) {
    if (stripos($row['teacher_first'] . ' ' . $row['teacher_last'], 'Thomas Robert') !== false) {
        $thomasRobertCount++;
    }
}
echo "<p>Affectations pour 'Thomas Robert' (requête directe): <strong>$thomasRobertCount</strong></p>";

echo "<h2>Conclusion</h2>";
echo "<p><strong>CORRECTION APPLIQUÉE:</strong> Changement de INNER JOIN vers LEFT JOIN dans toutes les méthodes du modèle Assignment.</p>";
echo "<p>Cela devrait résoudre le problème de l'affichage d'un seul étudiant lorsque certaines références sont manquantes dans la base de données.</p>";
echo "<p>Si les nombres sont maintenant cohérents, le problème est résolu !</p>";
?>