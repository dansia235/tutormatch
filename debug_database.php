<?php
/**
 * Script de débogage pour examiner la base de données
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

echo "<h1>Débogage de la base de données</h1>";
echo "<p>Teacher ID: " . $teacher['id'] . "</p>";
echo "<p>Teacher Name: " . $teacher['first_name'] . " " . $teacher['last_name'] . "</p>";

// 1. Examiner la table assignments directement
echo "<h2>1. Contenu brut de la table assignments</h2>";
$stmt = $db->prepare("SELECT * FROM assignments ORDER BY id DESC");
$stmt->execute();
$allAssignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p>Total des affectations dans la table: <strong>" . count($allAssignments) . "</strong></p>";

// Afficher toutes les affectations
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>ID</th><th>Student ID</th><th>Teacher ID</th><th>Internship ID</th><th>Status</th><th>Assignment Date</th><th>Created At</th></tr>";
foreach ($allAssignments as $assignment) {
    $highlight = ($assignment['teacher_id'] == $teacher['id']) ? 'style="background-color: yellow;"' : '';
    echo "<tr $highlight>";
    echo "<td>" . $assignment['id'] . "</td>";
    echo "<td>" . $assignment['student_id'] . "</td>";
    echo "<td>" . $assignment['teacher_id'] . "</td>";
    echo "<td>" . $assignment['internship_id'] . "</td>";
    echo "<td>" . $assignment['status'] . "</td>";
    echo "<td>" . ($assignment['assignment_date'] ?? 'NULL') . "</td>";
    echo "<td>" . ($assignment['created_at'] ?? 'NULL') . "</td>";
    echo "</tr>";
}
echo "</table>";

// 2. Vérifier les affectations pour ce tuteur spécifiquement
echo "<h2>2. Affectations pour ce tuteur (Teacher ID: {$teacher['id']})</h2>";
$stmt = $db->prepare("SELECT * FROM assignments WHERE teacher_id = :teacher_id");
$stmt->bindParam(':teacher_id', $teacher['id']);
$stmt->execute();
$teacherAssignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p>Affectations pour ce tuteur: <strong>" . count($teacherAssignments) . "</strong></p>";

if (!empty($teacherAssignments)) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Student ID</th><th>Teacher ID</th><th>Internship ID</th><th>Status</th><th>Assignment Date</th></tr>";
    foreach ($teacherAssignments as $assignment) {
        echo "<tr>";
        echo "<td>" . $assignment['id'] . "</td>";
        echo "<td>" . $assignment['student_id'] . "</td>";
        echo "<td>" . $assignment['teacher_id'] . "</td>";
        echo "<td>" . $assignment['internship_id'] . "</td>";
        echo "<td>" . $assignment['status'] . "</td>";
        echo "<td>" . ($assignment['assignment_date'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 3. Vérifier les teachers
echo "<h2>3. Vérification de la table teachers</h2>";
$stmt = $db->prepare("SELECT t.*, u.first_name, u.last_name FROM teachers t JOIN users u ON t.user_id = u.id ORDER BY t.id");
$stmt->execute();
$allTeachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Teacher ID</th><th>User ID</th><th>Name</th><th>Active</th></tr>";
foreach ($allTeachers as $t) {
    $highlight = ($t['id'] == $teacher['id']) ? 'style="background-color: yellow;"' : '';
    echo "<tr $highlight>";
    echo "<td>" . $t['id'] . "</td>";
    echo "<td>" . $t['user_id'] . "</td>";
    echo "<td>" . $t['first_name'] . " " . $t['last_name'] . "</td>";
    echo "<td>" . ($t['is_active'] ?? 'NULL') . "</td>";
    echo "</tr>";
}
echo "</table>";

// 4. Vérifier si Thomas Robert a plusieurs entrées
echo "<h2>4. Recherche de 'Thomas Robert' dans la base</h2>";
$stmt = $db->prepare("SELECT t.*, u.first_name, u.last_name FROM teachers t JOIN users u ON t.user_id = u.id WHERE u.first_name LIKE '%Thomas%' OR u.last_name LIKE '%Robert%'");
$stmt->execute();
$thomasRoberts = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p>Entrées trouvées pour Thomas Robert: <strong>" . count($thomasRoberts) . "</strong></p>";

if (!empty($thomasRoberts)) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Teacher ID</th><th>User ID</th><th>Name</th><th>Email</th><th>Active</th></tr>";
    foreach ($thomasRoberts as $tr) {
        $highlight = ($tr['id'] == $teacher['id']) ? 'style="background-color: yellow;"' : '';
        echo "<tr $highlight>";
        echo "<td>" . $tr['id'] . "</td>";
        echo "<td>" . $tr['user_id'] . "</td>";
        echo "<td>" . $tr['first_name'] . " " . $tr['last_name'] . "</td>";
        echo "<td>" . ($tr['email'] ?? 'N/A') . "</td>";
        echo "<td>" . ($tr['is_active'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    // 5. Vérifier les affectations pour tous les Thomas Robert
    echo "<h2>5. Affectations pour tous les Thomas Robert</h2>";
    foreach ($thomasRoberts as $tr) {
        echo "<h3>Affectations pour Teacher ID: {$tr['id']} ({$tr['first_name']} {$tr['last_name']})</h3>";
        
        $stmt = $db->prepare("SELECT * FROM assignments WHERE teacher_id = :teacher_id");
        $stmt->bindParam(':teacher_id', $tr['id']);
        $stmt->execute();
        $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>Nombre d'affectations: <strong>" . count($assignments) . "</strong></p>";
        
        if (!empty($assignments)) {
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>Assignment ID</th><th>Student ID</th><th>Internship ID</th><th>Status</th><th>Date</th></tr>";
            foreach ($assignments as $assign) {
                echo "<tr>";
                echo "<td>" . $assign['id'] . "</td>";
                echo "<td>" . $assign['student_id'] . "</td>";
                echo "<td>" . $assign['internship_id'] . "</td>";
                echo "<td>" . $assign['status'] . "</td>";
                echo "<td>" . ($assign['assignment_date'] ?? 'NULL') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
}

// 6. Session debug
echo "<h2>6. Informations de session</h2>";
echo "<p>User ID de session: " . $_SESSION['user_id'] . "</p>";
echo "<p>Teacher trouvé - ID: " . $teacher['id'] . ", User ID: " . $teacher['user_id'] . "</p>";

echo "<h2>Conclusion</h2>";
echo "<p>Ce script aide à identifier s'il y a:</p>";
echo "<ul>";
echo "<li>Plusieurs comptes Thomas Robert</li>";
echo "<li>Un problème de correspondance teacher_id</li>";
echo "<li>Des affectations avec des teacher_id incorrects</li>";
echo "<li>Un problème de session ou d'authentification</li>";
echo "</ul>";
?>