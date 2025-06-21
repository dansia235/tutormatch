<?php
/**
 * Script de test pour vérifier l'état des sessions et l'identification de l'utilisateur
 */

// Démarrer la session
session_start();

// Définir les entêtes pour une sortie HTML
header('Content-Type: text/html; charset=utf-8');

echo '<!DOCTYPE html>
<html>
<head>
    <title>Test des sessions</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .info { color: blue; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Test des sessions et de l\'identification</h1>';

// Informations de connexion à la base de données
$host = 'localhost';
$dbname = 'tutoring_system';
$username = 'root';
$password = '';

try {
    // Connexion à la base de données
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Afficher l'état de la session
    echo '<h2>État de la session</h2>';
    
    if (empty($_SESSION)) {
        echo '<p class="warning">Aucune donnée de session trouvée. Vous n\'êtes probablement pas connecté.</p>';
    } else {
        echo '<p class="success">Session active trouvée.</p>';
        echo '<pre>';
        print_r($_SESSION);
        echo '</pre>';
    }
    
    // Vérifier si l'utilisateur est identifié
    $user_id = $_SESSION['user_id'] ?? null;
    echo '<h2>Identification de l\'utilisateur</h2>';
    
    if (!$user_id) {
        echo '<p class="warning">Aucun ID utilisateur trouvé dans la session.</p>';
        
        // Proposer de se connecter avec un compte de test
        echo '<h3>Connexion de test</h3>';
        echo '<p>Vous pouvez vous connecter avec un compte de test pour continuer les tests :</p>';
        echo '<form method="post" action="">
            <input type="hidden" name="action" value="login_test">
            <button type="submit" style="padding: 8px 16px; background: #4CAF50; color: white; border: none; cursor: pointer;">Se connecter avec un compte étudiant de test</button>
        </form>';
    } else {
        echo '<p class="success">Utilisateur identifié avec ID : ' . $user_id . '</p>';
        
        // Récupérer les informations de l'utilisateur
        $stmt = $db->prepare("SELECT * FROM users WHERE id = :user_id");
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo '<h3>Informations utilisateur</h3>';
            echo '<table>';
            echo '<tr><th>Champ</th><th>Valeur</th></tr>';
            foreach ($user as $key => $value) {
                if ($key !== 'password') { // Ne pas afficher le mot de passe
                    echo '<tr><td>' . $key . '</td><td>' . $value . '</td></tr>';
                }
            }
            echo '</table>';
            
            // Vérifier si l'utilisateur est un étudiant
            $stmt = $db->prepare("SELECT * FROM students WHERE user_id = :user_id");
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($student) {
                echo '<h3>Profil étudiant</h3>';
                echo '<p class="success">Profil étudiant trouvé avec ID : ' . $student['id'] . '</p>';
                echo '<table>';
                echo '<tr><th>Champ</th><th>Valeur</th></tr>';
                foreach ($student as $key => $value) {
                    echo '<tr><td>' . $key . '</td><td>' . $value . '</td></tr>';
                }
                echo '</table>';
                
                // Récupérer les préférences de l'étudiant
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
                
                echo '<h3>Préférences de stage</h3>';
                if (count($preferences) > 0) {
                    echo '<p class="success">L\'étudiant a ' . count($preferences) . ' préférences de stage.</p>';
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
                } else {
                    echo '<p class="info">L\'étudiant n\'a pas encore défini de préférences de stage.</p>';
                    
                    // Proposer d'ajouter une préférence de test
                    echo '<h4>Ajouter une préférence de test</h4>';
                    echo '<form method="post" action="">
                        <input type="hidden" name="action" value="add_preference">
                        <input type="hidden" name="student_id" value="' . $student['id'] . '">
                        <button type="submit" style="padding: 8px 16px; background: #4CAF50; color: white; border: none; cursor: pointer;">Ajouter une préférence de test</button>
                    </form>';
                }
            } else {
                echo '<p class="warning">Aucun profil étudiant trouvé pour cet utilisateur.</p>';
            }
        } else {
            echo '<p class="error">Utilisateur non trouvé dans la base de données.</p>';
        }
    }
    
    // Traitement des actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'login_test') {
            // Trouver un compte étudiant de test
            $stmt = $db->query("
                SELECT u.*, s.id as student_id 
                FROM users u 
                JOIN students s ON u.id = s.user_id 
                WHERE u.role = 'student' 
                LIMIT 1
            ");
            $testUser = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($testUser) {
                // Simuler une connexion
                $_SESSION['user_id'] = $testUser['id'];
                $_SESSION['user_role'] = 'student';
                $_SESSION['user'] = [
                    'id' => $testUser['student_id'],
                    'username' => $testUser['username'],
                    'email' => $testUser['email'],
                    'first_name' => $testUser['first_name'],
                    'last_name' => $testUser['last_name']
                ];
                
                echo '<p class="success">Connexion réussie avec le compte de test. ID utilisateur : ' . $testUser['id'] . ', ID étudiant : ' . $testUser['student_id'] . '</p>';
                echo '<p>La page va se recharger dans 2 secondes...</p>';
                echo '<script>setTimeout(function() { window.location.reload(); }, 2000);</script>';
            } else {
                echo '<p class="error">Aucun compte étudiant de test trouvé dans la base de données.</p>';
            }
        } elseif ($action === 'add_preference') {
            $student_id = $_POST['student_id'] ?? null;
            
            if ($student_id) {
                // Trouver un stage disponible
                $stmt = $db->query("
                    SELECT i.* FROM internships i 
                    WHERE i.status = 'available' 
                    AND i.id NOT IN (SELECT internship_id FROM student_preferences WHERE student_id = $student_id)
                    LIMIT 1
                ");
                $internship = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($internship) {
                    // Compter les préférences existantes pour déterminer l'ordre
                    $stmt = $db->prepare("SELECT COUNT(*) FROM student_preferences WHERE student_id = :student_id");
                    $stmt->bindValue(':student_id', $student_id, PDO::PARAM_INT);
                    $stmt->execute();
                    $preference_order = $stmt->fetchColumn() + 1;
                    
                    // Ajouter la préférence
                    $stmt = $db->prepare("
                        INSERT INTO student_preferences (student_id, internship_id, preference_order, reason) 
                        VALUES (:student_id, :internship_id, :preference_order, :reason)
                    ");
                    $stmt->bindValue(':student_id', $student_id, PDO::PARAM_INT);
                    $stmt->bindValue(':internship_id', $internship['id'], PDO::PARAM_INT);
                    $stmt->bindValue(':preference_order', $preference_order, PDO::PARAM_INT);
                    $stmt->bindValue(':reason', 'Préférence de test ajoutée automatiquement');
                    $stmt->execute();
                    
                    echo '<p class="success">Préférence de test ajoutée avec succès pour le stage "' . $internship['title'] . '".</p>';
                    echo '<p>La page va se recharger dans 2 secondes...</p>';
                    echo '<script>setTimeout(function() { window.location.reload(); }, 2000);</script>';
                } else {
                    echo '<p class="error">Aucun stage disponible trouvé pour ajouter une préférence.</p>';
                }
            } else {
                echo '<p class="error">ID étudiant manquant.</p>';
            }
        }
    }
    
    echo '<hr>';
    echo '<p>Vous pouvez maintenant <a href="/tutoring/views/student/preferences.php">retourner à la page des préférences</a>.</p>';
    
} catch (PDOException $e) {
    echo '<p class="error">Erreur de base de données : ' . $e->getMessage() . '</p>';
}

echo '</body></html>';