<?php
/**
 * Script pour corriger l'affichage des préférences étudiantes
 * Ce script est conçu pour être appelé directement dans le navigateur
 */

// Démarrer la session
session_start();

// Définir les entêtes pour une sortie HTML
header('Content-Type: text/html; charset=utf-8');

echo '<!DOCTYPE html>
<html>
<head>
    <title>Correction des préférences étudiantes</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .info { color: blue; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
        .action-button { padding: 10px 15px; background: #4CAF50; color: white; border: none; cursor: pointer; margin: 5px; }
        .secondary-button { padding: 10px 15px; background: #2196F3; color: white; border: none; cursor: pointer; margin: 5px; }
    </style>
</head>
<body>
    <h1>Correction des préférences étudiantes</h1>';

// Informations de connexion à la base de données
$host = 'localhost';
$dbname = 'tutoring_system';
$username = 'root';
$password = '';

try {
    // Connexion à la base de données
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 1. Vérifier et ajouter la colonne "reason" si nécessaire
    $checkQuery = "SHOW COLUMNS FROM `student_preferences` LIKE 'reason'";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute();
    
    $reasonColumnExists = $checkStmt->rowCount() > 0;
    
    echo '<h2>1. Vérification de la structure de la table</h2>';
    
    if ($reasonColumnExists) {
        echo '<p class="success">La colonne "reason" existe dans la table student_preferences.</p>';
    } else {
        echo '<p class="warning">La colonne "reason" n\'existe pas dans la table student_preferences. Tentative d\'ajout...</p>';
        
        try {
            $alterQuery = "ALTER TABLE `student_preferences` ADD COLUMN `reason` TEXT NULL AFTER `preference_order`";
            $db->exec($alterQuery);
            echo '<p class="success">La colonne "reason" a été ajoutée avec succès.</p>';
            $reasonColumnExists = true;
        } catch (Exception $e) {
            echo '<p class="error">Erreur lors de l\'ajout de la colonne : ' . $e->getMessage() . '</p>';
        }
    }
    
    // 2. Vérifier l'utilisateur actuel
    echo '<h2>2. Vérification de l\'utilisateur</h2>';
    
    $user_id = $_SESSION['user_id'] ?? null;
    
    if (!$user_id) {
        echo '<p class="warning">Aucun utilisateur connecté trouvé dans la session.</p>';
        
        // Trouver un étudiant pour les tests
        $stmt = $db->query("SELECT u.*, s.id as student_id FROM users u JOIN students s ON u.id = s.user_id LIMIT 1");
        $testUser = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($testUser) {
            echo '<p class="info">Un étudiant de test est disponible (ID: ' . $testUser['id'] . ').</p>';
            echo '<form method="post">
                <input type="hidden" name="action" value="login_test">
                <input type="hidden" name="user_id" value="' . $testUser['id'] . '">
                <input type="hidden" name="student_id" value="' . $testUser['student_id'] . '">
                <button type="submit" class="action-button">Se connecter avec cet étudiant</button>
            </form>';
        } else {
            echo '<p class="error">Aucun étudiant trouvé dans la base de données.</p>';
        }
    } else {
        echo '<p class="success">Utilisateur connecté trouvé avec ID: ' . $user_id . '</p>';
        
        // Récupérer les informations de l'étudiant
        $stmt = $db->prepare("SELECT * FROM students WHERE user_id = :user_id");
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($student) {
            echo '<p class="success">Profil étudiant trouvé avec ID: ' . $student['id'] . '</p>';
            
            // 3. Vérifier les préférences existantes
            echo '<h2>3. Vérification des préférences</h2>';
            
            $stmt = $db->prepare("
                SELECT sp.*, i.title, i.company_id, c.name as company_name 
                FROM student_preferences sp
                JOIN internships i ON sp.internship_id = i.id
                JOIN companies c ON i.company_id = c.id
                WHERE sp.student_id = :student_id
                ORDER BY sp.preference_order ASC
            ");
            $stmt->bindValue(':student_id', $student['id'], PDO::PARAM_INT);
            $stmt->execute();
            $preferences = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($preferences) > 0) {
                echo '<p class="success">L\'étudiant a ' . count($preferences) . ' préférences de stage :</p>';
                echo '<table>';
                echo '<tr><th>ID</th><th>Stage</th><th>Entreprise</th><th>Ordre</th><th>Raison</th></tr>';
                foreach ($preferences as $pref) {
                    echo '<tr>';
                    echo '<td>' . $pref['id'] . '</td>';
                    echo '<td>' . $pref['title'] . '</td>';
                    echo '<td>' . $pref['company_name'] . '</td>';
                    echo '<td>' . $pref['preference_order'] . '</td>';
                    echo '<td>' . ($pref['reason'] ?? '-') . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
                
                echo '<form method="post">
                    <input type="hidden" name="action" value="test_preferences">
                    <input type="hidden" name="student_id" value="' . $student['id'] . '">
                    <button type="submit" class="secondary-button">Tester l\'affichage des préférences</button>
                </form>';
            } else {
                echo '<p class="warning">Aucune préférence de stage trouvée pour cet étudiant.</p>';
                
                // Proposer d'ajouter des préférences de test
                echo '<form method="post">
                    <input type="hidden" name="action" value="add_test_preferences">
                    <input type="hidden" name="student_id" value="' . $student['id'] . '">
                    <button type="submit" class="action-button">Ajouter des préférences de test</button>
                </form>';
            }
        } else {
            echo '<p class="error">Aucun profil étudiant trouvé pour cet utilisateur.</p>';
        }
    }
    
    // Traitement des actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'login_test') {
            $test_user_id = $_POST['user_id'] ?? null;
            $test_student_id = $_POST['student_id'] ?? null;
            
            if ($test_user_id && $test_student_id) {
                // Définir les variables de session
                $_SESSION['user_id'] = $test_user_id;
                $_SESSION['user_role'] = 'student';
                $_SESSION['user'] = [
                    'id' => $test_student_id
                ];
                
                echo '<p class="success">Connexion réussie avec l\'étudiant de test.</p>';
                echo '<script>window.location.reload();</script>';
            }
        } elseif ($action === 'add_test_preferences') {
            $student_id = $_POST['student_id'] ?? null;
            
            if ($student_id) {
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
                
                if (count($internships) > 0) {
                    // Supprimer les préférences existantes
                    $db->exec("DELETE FROM student_preferences WHERE student_id = $student_id");
                    
                    // Ajouter les nouvelles préférences
                    $order = 1;
                    foreach ($internships as $internship) {
                        $stmt = $db->prepare("
                            INSERT INTO student_preferences (student_id, internship_id, preference_order, reason) 
                            VALUES (:student_id, :internship_id, :preference_order, :reason)
                        ");
                        $stmt->bindValue(':student_id', $student_id, PDO::PARAM_INT);
                        $stmt->bindValue(':internship_id', $internship['id'], PDO::PARAM_INT);
                        $stmt->bindValue(':preference_order', $order, PDO::PARAM_INT);
                        $stmt->bindValue(':reason', 'Préférence de test ' . $order);
                        $stmt->execute();
                        
                        echo '<p class="success">Préférence ajoutée pour le stage "' . $internship['title'] . '" (ID: ' . $internship['id'] . ') avec ordre ' . $order . '</p>';
                        
                        $order++;
                    }
                    
                    echo '<script>window.location.reload();</script>';
                } else {
                    echo '<p class="error">Aucun stage disponible trouvé.</p>';
                }
            }
        } elseif ($action === 'test_preferences') {
            $student_id = $_POST['student_id'] ?? null;
            
            if ($student_id) {
                echo '<h2>Test d\'affichage des préférences</h2>';
                
                // Récupérer les préférences formatées comme dans l'API
                $stmt = $db->prepare("
                    SELECT sp.*, i.title, i.company_id, c.name as company_name 
                    FROM student_preferences sp
                    JOIN internships i ON sp.internship_id = i.id
                    JOIN companies c ON i.company_id = c.id
                    WHERE sp.student_id = :student_id
                    ORDER BY sp.preference_order ASC
                ");
                $stmt->bindValue(':student_id', $student_id, PDO::PARAM_INT);
                $stmt->execute();
                $preferences = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $formattedPreferences = [];
                foreach ($preferences as $pref) {
                    $formattedPreferences[] = [
                        'internship_id' => $pref['internship_id'],
                        'title' => $pref['title'] ?? 'Stage sans titre',
                        'company_name' => $pref['company_name'] ?? 'Entreprise non spécifiée',
                        'preference_order' => $pref['preference_order'] ?? 1,
                        'rank' => $pref['preference_order'] ?? 1,
                        'reason' => $pref['reason'] ?? null
                    ];
                }
                
                echo '<p class="info">Données formatées comme attendues par le contrôleur JavaScript :</p>';
                echo '<pre>' . json_encode($formattedPreferences, JSON_PRETTY_PRINT) . '</pre>';
                
                echo '<p class="info">Ces données seront disponibles en tant que variable JavaScript pour la page des préférences.</p>';
                
                // Créer le script JavaScript
                $jsData = 'const testPreferences = ' . json_encode($formattedPreferences) . ';';
                $jsFile = '/mnt/c/xampp/htdocs/tutoring/assets/js/debug-student-preferences.js';
                
                if (file_put_contents($jsFile, $jsData)) {
                    echo '<p class="success">Données écrites dans ' . $jsFile . '</p>';
                    echo '<p>Vous pouvez maintenant <a href="/tutoring/views/student/preferences.php">accéder à la page des préférences</a> pour tester l\'affichage.</p>';
                } else {
                    echo '<p class="error">Impossible d\'écrire dans le fichier ' . $jsFile . '</p>';
                }
            }
        }
    }
    
    echo '<hr>';
    echo '<h2>Actions disponibles</h2>';
    echo '<a href="/tutoring/views/student/preferences.php" class="secondary-button">Aller à la page des préférences</a>';
    echo '<a href="/tutoring/views/student/internship.php" class="secondary-button">Aller à la page des stages</a>';
    
} catch (PDOException $e) {
    echo '<p class="error">Erreur de base de données : ' . $e->getMessage() . '</p>';
}

echo '</body></html>';