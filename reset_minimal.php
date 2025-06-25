<?php
/**
 * Version minimale du reset - ne touche que les tables essentielles
 */

echo "<!DOCTYPE html><html><head><title>Reset Minimal</title></head><body>";
echo "<h1>Reset Minimal - Tables Essentielles</h1>";

try {
    $db = new PDO("mysql:host=localhost;dbname=tutoring_system;charset=utf8", 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✅ Connexion réussie</p>";
    
    // Désactiver contraintes
    $db->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    echo "<p>🗑️ Vidage des tables existantes...</p>";
    
    // Tables essentielles seulement
    $essentialTables = ['assignments', 'students', 'teachers', 'users', 'companies', 'internships'];
    
    foreach ($essentialTables as $table) {
        try {
            // Vérifier si la table existe
            $stmt = $db->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                $db->exec("DELETE FROM $table");
                echo "<p>✅ Table $table vidée</p>";
            } else {
                echo "<p style='color: orange;'>⚠️ Table $table n'existe pas</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color: orange;'>⚠️ $table: " . $e->getMessage() . "</p>";
        }
    }
    
    // Réactiver contraintes
    $db->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "<p>👥 Création des utilisateurs...</p>";
    
    // Commencer nouvelle transaction
    $db->beginTransaction();
    
    // Mot de passe pour tous
    $hashedPassword = password_hash('12345678', PASSWORD_DEFAULT);
    
    // Vérifier la structure de la table users
    $stmt = $db->query("DESCRIBE users");
    $userColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $hasUsername = in_array('username', $userColumns);
    
    echo "<p>Structure users détectée - Username: " . ($hasUsername ? "OUI" : "NON") . "</p>";
    
    // Utilisateurs de base
    $users = [
        ['Thomas', 'Robert', 'thomas.robert@universite.fr', 'thomas.robert', 'teacher', 'Informatique'],
        ['Claire', 'Bernard', 'claire.bernard@universite.fr', 'claire.bernard', 'teacher', 'Informatique'],
        ['Antoine', 'Petit', 'antoine.petit@universite.fr', 'antoine.petit', 'teacher', 'Génie Civil'],
        ['Alexandre', 'Dupont', 'alexandre.dupont@etudiant.fr', 'alexandre.dupont', 'student', 'Informatique'],
        ['Emma', 'Rousseau', 'emma.rousseau@etudiant.fr', 'emma.rousseau', 'student', 'Informatique'],
        ['Lucas', 'Morel', 'lucas.morel@etudiant.fr', 'lucas.morel', 'student', 'Génie Civil'],
        ['Chloé', 'Fournier', 'chloe.fournier@etudiant.fr', 'chloe.fournier', 'student', 'Électronique'],
        ['Hugo', 'Girard', 'hugo.girard@etudiant.fr', 'hugo.girard', 'student', 'Informatique'],
        ['Nathan', 'Muller', 'nathan.muller@etudiant.fr', 'nathan.muller', 'student', 'Informatique'],
    ];
    
    $userIds = [];
    foreach ($users as $index => $user) {
        if ($hasUsername) {
            $stmt = $db->prepare("INSERT INTO users (first_name, last_name, email, username, password, role, department) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user[0], $user[1], $user[2], $user[3], $hashedPassword, $user[4], $user[5]]);
        } else {
            $stmt = $db->prepare("INSERT INTO users (first_name, last_name, email, password, role, department) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user[0], $user[1], $user[2], $hashedPassword, $user[4], $user[5]]);
        }
        $userIds[$index] = $db->lastInsertId();
        echo "<p>✅ {$user[0]} {$user[1]} ({$user[4]})</p>";
    }
    
    // Créer les teachers
    echo "<p>🎓 Création des tuteurs...</p>";
    $teacherIds = [];
    for ($i = 0; $i < 3; $i++) { // Les 3 premiers sont teachers
        $stmt = $db->prepare("INSERT INTO teachers (user_id) VALUES (?)");
        $stmt->execute([$userIds[$i]]);
        $teacherIds[] = $db->lastInsertId();
    }
    
    // Créer les students
    echo "<p>🎒 Création des étudiants...</p>";
    $studentIds = [];
    for ($i = 3; $i < count($users); $i++) { // Le reste sont students
        $stmt = $db->prepare("INSERT INTO students (user_id, student_number, program, level) VALUES (?, ?, ?, ?)");
        $studentNumber = 'ET' . str_pad($i - 2, 4, '0', STR_PAD_LEFT);
        $stmt->execute([$userIds[$i], $studentNumber, $users[$i][5], 'L3']);
        $studentIds[] = $db->lastInsertId();
    }
    
    // Créer des entreprises
    echo "<p>🏢 Création des entreprises...</p>";
    $companies = [
        ['TechCorp', 'tech-corp.png'],
        ['InnovSoft', 'innov-soft.png'],
        ['DataSys', 'datasys.png'],
    ];
    
    $companyIds = [];
    foreach ($companies as $company) {
        $stmt = $db->prepare("INSERT INTO companies (name, logo_path) VALUES (?, ?)");
        $stmt->execute([$company[0], $company[1]]);
        $companyIds[] = $db->lastInsertId();
    }
    
    // Créer des stages
    echo "<p>💼 Création des stages...</p>";
    $internships = [
        ['Développeur Web', $companyIds[0], '2024-07-01', '2024-09-30'],
        ['Analyste Data', $companyIds[1], '2024-07-15', '2024-10-15'],
        ['Assistant Ingénieur', $companyIds[2], '2024-08-01', '2024-11-01'],
        ['Développeur Mobile', $companyIds[0], '2024-07-01', '2024-09-30'],
        ['Admin Systèmes', $companyIds[1], '2024-08-15', '2024-11-15'],
        ['Chef Projet Junior', $companyIds[2], '2024-07-01', '2024-09-30'],
    ];
    
    $internshipIds = [];
    foreach ($internships as $internship) {
        $stmt = $db->prepare("INSERT INTO internships (title, company_id, start_date, end_date, status) VALUES (?, ?, ?, ?, 'available')");
        $stmt->execute([$internship[0], $internship[1], $internship[2], $internship[3]]);
        $internshipIds[] = $db->lastInsertId();
    }
    
    // Créer les affectations - LE POINT CLÉ pour tester LEFT JOIN
    echo "<p>📋 Création des affectations...</p>";
    $assignments = [
        // Thomas Robert (teacherIds[0]) - 3 étudiants
        [$studentIds[0], $teacherIds[0], $internshipIds[0], 'confirmed'], // Alexandre
        [$studentIds[1], $teacherIds[0], $internshipIds[1], 'confirmed'], // Emma  
        [$studentIds[2], $teacherIds[0], $internshipIds[2], 'pending'],   // Lucas
        
        // Claire Bernard (teacherIds[1]) - 2 étudiants
        [$studentIds[3], $teacherIds[1], $internshipIds[3], 'confirmed'], // Chloé
        [$studentIds[4], $teacherIds[1], $internshipIds[4], 'pending'],   // Hugo
        
        // Antoine Petit (teacherIds[2]) - 1 étudiant
        [$studentIds[5], $teacherIds[2], $internshipIds[5], 'confirmed'], // Nathan
    ];
    
    foreach ($assignments as $assignment) {
        $stmt = $db->prepare("INSERT INTO assignments (student_id, teacher_id, internship_id, status, assignment_date) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute($assignment);
    }
    
    echo "<p>✅ " . count($assignments) . " affectations créées</p>";
    
    // Valider tout
    $db->commit();
    
    echo "<h2 style='color: green;'>✅ Reset minimal terminé!</h2>";
    
    echo "<p><strong>Données créées:</strong></p>";
    echo "<ul>";
    echo "<li>" . count($teacherIds) . " tuteurs</li>";
    echo "<li>" . count($studentIds) . " étudiants</li>";
    echo "<li>" . count($companyIds) . " entreprises</li>";
    echo "<li>" . count($internshipIds) . " stages</li>";
    echo "<li>" . count($assignments) . " affectations</li>";
    echo "</ul>";
    
    echo "<p><strong>🎯 Test LEFT JOIN - Affectations par tuteur:</strong></p>";
    echo "<ul>";
    echo "<li><strong>Thomas Robert:</strong> 3 étudiants</li>";
    echo "<li><strong>Claire Bernard:</strong> 2 étudiants</li>";
    echo "<li><strong>Antoine Petit:</strong> 1 étudiant</li>";
    echo "</ul>";
    
    echo "<div style='background-color: #e8f5e8; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>🚀 Prochaines étapes:</h3>";
    echo "<ol>";
    echo "<li>Se connecter: <code>thomas.robert@universite.fr</code> / <code>12345678</code></li>";
    echo "<li>Aller au dashboard tuteur</li>";
    echo "<li>Vérifier que les <strong>3 étudiants</strong> apparaissent (au lieu d'1 seul)</li>";
    echo "<li>Tester avec Claire: <code>claire.bernard@universite.fr</code> / <code>12345678</code></li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<p><a href='test_fixes.php' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔍 Voir les détails techniques</a></p>";

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo "<p style='color: red;'>❌ Erreur: " . $e->getMessage() . "</p>";
    echo "<p>Ligne: " . $e->getLine() . "</p>";
}

echo "</body></html>";
?>