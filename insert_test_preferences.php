<?php
/**
 * Script pour insérer des préférences de test pour un étudiant
 */

// Définir les entêtes pour une sortie HTML
header('Content-Type: text/html; charset=utf-8');

echo '<!DOCTYPE html>
<html>
<head>
    <title>Insertion de préférences de test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; }
        .action-button { padding: 10px 15px; background: #4CAF50; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Insertion de préférences de test</h1>';

// Informations de connexion à la base de données
$host = 'localhost';
$dbname = 'tutoring_system';
$username = 'root';
$password = '';

try {
    // Connexion à la base de données
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 1. Vérifier si la colonne "reason" existe
    $checkQuery = "SHOW COLUMNS FROM `student_preferences` LIKE 'reason'";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() === 0) {
        echo '<p class="info">La colonne "reason" n\'existe pas. Tentative d\'ajout...</p>';
        
        try {
            $alterQuery = "ALTER TABLE `student_preferences` ADD COLUMN `reason` TEXT NULL AFTER `preference_order`";
            $db->exec($alterQuery);
            echo '<p class="success">La colonne "reason" a été ajoutée avec succès.</p>';
        } catch (Exception $e) {
            echo '<p class="error">Erreur lors de l\'ajout de la colonne : ' . $e->getMessage() . '</p>';
        }
    } else {
        echo '<p class="success">La colonne "reason" existe déjà.</p>';
    }
    
    // 2. Récupérer les étudiants
    $stmt = $db->query("SELECT s.id, u.first_name, u.last_name FROM students s JOIN users u ON s.user_id = u.id LIMIT 10");
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($students)) {
        echo '<p class="error">Aucun étudiant trouvé dans la base de données.</p>';
        exit;
    }
    
    echo '<h2>Étudiants disponibles</h2>';
    echo '<form method="post">';
    echo '<select name="student_id">';
    foreach ($students as $student) {
        echo '<option value="' . $student['id'] . '">' . $student['first_name'] . ' ' . $student['last_name'] . ' (ID: ' . $student['id'] . ')</option>';
    }
    echo '</select>';
    echo '<button type="submit" name="action" value="insert" class="action-button" style="margin-left: 10px;">Insérer des préférences de test</button>';
    echo '</form>';
    
    // 3. Traiter la soumission du formulaire
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'insert') {
        $student_id = $_POST['student_id'] ?? null;
        
        if (!$student_id) {
            echo '<p class="error">Aucun étudiant sélectionné.</p>';
            exit;
        }
        
        // Récupérer les stages disponibles
        $stmt = $db->query("
            SELECT i.*, c.name as company_name 
            FROM internships i 
            JOIN companies c ON i.company_id = c.id
            WHERE i.status = 'available' 
            ORDER BY i.id ASC 
            LIMIT 3
        ");
        $internships = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($internships)) {
            echo '<p class="error">Aucun stage disponible trouvé.</p>';
            exit;
        }
        
        // Supprimer les préférences existantes pour cet étudiant
        $stmt = $db->prepare("DELETE FROM student_preferences WHERE student_id = :student_id");
        $stmt->bindValue(':student_id', $student_id, PDO::PARAM_INT);
        $stmt->execute();
        echo '<p class="info">Anciennes préférences supprimées pour l\'étudiant ID ' . $student_id . '.</p>';
        
        // Insérer les nouvelles préférences
        $inserted = 0;
        foreach ($internships as $index => $internship) {
            try {
                $order = $index + 1;
                $reason = 'Préférence de test ' . $order . ' - ' . date('Y-m-d H:i:s');
                
                $stmt = $db->prepare("
                    INSERT INTO student_preferences (student_id, internship_id, preference_order, reason) 
                    VALUES (:student_id, :internship_id, :preference_order, :reason)
                ");
                $stmt->bindValue(':student_id', $student_id, PDO::PARAM_INT);
                $stmt->bindValue(':internship_id', $internship['id'], PDO::PARAM_INT);
                $stmt->bindValue(':preference_order', $order, PDO::PARAM_INT);
                $stmt->bindValue(':reason', $reason);
                $stmt->execute();
                
                echo '<p class="success">Préférence ajoutée : ' . $internship['title'] . ' (ID: ' . $internship['id'] . ') avec ordre ' . $order . '</p>';
                $inserted++;
            } catch (Exception $e) {
                echo '<p class="error">Erreur lors de l\'insertion : ' . $e->getMessage() . '</p>';
            }
        }
        
        if ($inserted > 0) {
            echo '<h2>Préférences ajoutées avec succès</h2>';
            echo '<p>Vous pouvez maintenant <a href="/tutoring/views/student/preferences.php">accéder à la page des préférences</a> pour vérifier que le problème est résolu.</p>';
            
            // Créer une simulation de connexion pour cet étudiant
            echo '<h3>Simuler une connexion avec cet étudiant</h3>';
            echo '<form method="post" action="/tutoring/test_session.php">';
            echo '<input type="hidden" name="action" value="add_preference">';
            echo '<input type="hidden" name="student_id" value="' . $student_id . '">';
            echo '<button type="submit" class="action-button">Simuler une connexion et tester les préférences</button>';
            echo '</form>';
        }
    }
    
} catch (PDOException $e) {
    echo '<p class="error">Erreur de base de données : ' . $e->getMessage() . '</p>';
}

echo '</body></html>';