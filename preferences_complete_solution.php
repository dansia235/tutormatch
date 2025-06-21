<?php
/**
 * Solution complète pour corriger les problèmes d'affichage des préférences étudiantes
 * Ce script corrige à la fois la structure de la base de données et l'interface utilisateur
 */

// Démarrer la session
session_start();

// Définir les entêtes pour une sortie HTML
header('Content-Type: text/html; charset=utf-8');

echo '<!DOCTYPE html>
<html>
<head>
    <title>Solution complète pour les préférences</title>
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
        .action-button { padding: 10px 15px; background: #4CAF50; color: white; border: none; cursor: pointer; margin-right: 10px; }
        .code-fix { background: #f8f8f8; padding: 15px; border-left: 4px solid #4CAF50; font-family: monospace; white-space: pre-wrap; margin: 10px 0; }
        .step { background: #f0f8ff; padding: 15px; margin: 20px 0; border-radius: 5px; }
        .step-title { font-weight: bold; margin-bottom: 10px; }
    </style>
</head>
<body>
    <h1>Solution complète pour les préférences étudiantes</h1>
    <p>Ce script va résoudre les problèmes d\'affichage des préférences étudiantes en appliquant plusieurs corrections.</p>';

// Informations de connexion à la base de données
$host = 'localhost';
$dbname = 'tutoring_system';
$username = 'root';
$password = '';

try {
    // Connexion à la base de données
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // ÉTAPE 1 : Vérifier et corriger la structure de la base de données
    echo '<div class="step">';
    echo '<div class="step-title">ÉTAPE 1 : Vérification et correction de la structure de la base de données</div>';
    
    // Vérifier si la colonne "reason" existe
    $checkQuery = "SHOW COLUMNS FROM `student_preferences` LIKE 'reason'";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute();
    
    $reasonColumnExists = $checkStmt->rowCount() > 0;
    
    if ($reasonColumnExists) {
        echo '<p class="success">La colonne "reason" existe déjà dans la table student_preferences.</p>';
    } else {
        echo '<p class="warning">La colonne "reason" n\'existe pas dans la table student_preferences. Ajout en cours...</p>';
        
        try {
            $alterQuery = "ALTER TABLE `student_preferences` ADD COLUMN `reason` TEXT NULL AFTER `preference_order`";
            $db->exec($alterQuery);
            echo '<p class="success">La colonne "reason" a été ajoutée avec succès à la table student_preferences.</p>';
            $reasonColumnExists = true;
        } catch (Exception $e) {
            echo '<p class="error">Erreur lors de l\'ajout de la colonne : ' . $e->getMessage() . '</p>';
        }
    }
    
    // Afficher la structure actuelle de la table
    $describeQuery = "DESCRIBE `student_preferences`";
    $describeStmt = $db->prepare($describeQuery);
    $describeStmt->execute();
    $columns = $describeStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo '<p class="info">Structure actuelle de la table student_preferences :</p>';
    echo '<table>';
    echo '<tr><th>Champ</th><th>Type</th><th>Null</th><th>Clé</th><th>Défaut</th><th>Extra</th></tr>';
    foreach ($columns as $column) {
        echo '<tr>';
        foreach ($column as $key => $value) {
            echo '<td>' . ($value ?? 'NULL') . '</td>';
        }
        echo '</tr>';
    }
    echo '</table>';
    echo '</div>';
    
    // ÉTAPE 2 : Vérifier les données existantes
    echo '<div class="step">';
    echo '<div class="step-title">ÉTAPE 2 : Vérification des données existantes</div>';
    
    // Compter le nombre total de préférences
    $stmt = $db->query("SELECT COUNT(*) FROM student_preferences");
    $totalPreferences = $stmt->fetchColumn();
    
    echo '<p>Nombre total de préférences dans la base de données : <strong>' . $totalPreferences . '</strong></p>';
    
    // Récupérer les étudiants avec leurs préférences
    $stmt = $db->query("
        SELECT s.id, u.username, u.first_name, u.last_name, COUNT(sp.id) as preference_count 
        FROM students s
        JOIN users u ON s.user_id = u.id
        LEFT JOIN student_preferences sp ON s.id = sp.student_id
        GROUP BY s.id
        ORDER BY preference_count DESC
        LIMIT 10
    ");
    $studentsWithPreferences = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($studentsWithPreferences) > 0) {
        echo '<p class="info">Top 10 des étudiants avec leurs préférences :</p>';
        echo '<table>';
        echo '<tr><th>ID</th><th>Nom</th><th>Nombre de préférences</th><th>Actions</th></tr>';
        foreach ($studentsWithPreferences as $student) {
            echo '<tr>';
            echo '<td>' . $student['id'] . '</td>';
            echo '<td>' . $student['first_name'] . ' ' . $student['last_name'] . ' (' . $student['username'] . ')</td>';
            echo '<td>' . $student['preference_count'] . '</td>';
            echo '<td>';
            if ($student['preference_count'] > 0) {
                echo '<form method="post" style="display:inline;">';
                echo '<input type="hidden" name="action" value="view_preferences">';
                echo '<input type="hidden" name="student_id" value="' . $student['id'] . '">';
                echo '<button type="submit" class="action-button" style="padding:5px 10px;font-size:12px;">Voir</button>';
                echo '</form>';
            }
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';
    } else {
        echo '<p class="warning">Aucun étudiant trouvé avec des préférences.</p>';
    }
    echo '</div>';
    
    // ÉTAPE 3 : Correction du code dans la page des préférences
    echo '<div class="step">';
    echo '<div class="step-title">ÉTAPE 3 : Correction du code dans la page des préférences</div>';
    
    $preferences_file = '/mnt/c/xampp/htdocs/tutoring/views/student/preferences.php';
    
    if (file_exists($preferences_file)) {
        echo '<p class="info">Fichier cible trouvé : ' . $preferences_file . '</p>';
        
        // Créer une sauvegarde
        $backup_file = $preferences_file . '.backup.' . date('Ymd_His');
        if (copy($preferences_file, $backup_file)) {
            echo '<p class="success">Sauvegarde créée : ' . $backup_file . '</p>';
            
            // Lire le contenu du fichier
            $content = file_get_contents($preferences_file);
            
            // Corrections à appliquer
            $fixes = [];
            
            // 1. Correction de la récupération de l'ID étudiant
            $old_student_id = '$student_id = $_SESSION[\'user\'][\'id\'] ?? null;';
            $new_student_id = '// Récupérer l\'ID de l\'étudiant de la session
$student_id = null;
if (isset($_SESSION[\'user\'][\'id\'])) {
    $student_id = $_SESSION[\'user\'][\'id\'];
} elseif (isset($_SESSION[\'user_id\'])) {
    // Récupérer l\'ID étudiant à partir de l\'ID utilisateur
    $studentModel = new Student($db);
    $student = $studentModel->getByUserId($_SESSION[\'user_id\']);
    if ($student) {
        $student_id = $student[\'id\'];
    }
}';
            
            if (strpos($content, $old_student_id) !== false) {
                $content = str_replace($old_student_id, $new_student_id, $content);
                $fixes[] = 'Correction de la récupération de l\'ID étudiant';
            }
            
            // 2. Ajouter un script de correction directe
            $correction_script = '
<script>
// Script de correction pour l\'affichage des préférences
document.addEventListener("DOMContentLoaded", function() {
    console.log("Script de correction des préférences chargé");
    
    // Fonction pour appliquer les données de secours au contrôleur
    const applyFallbackPreferences = () => {
        console.log("Tentative d\'application des données de secours");
        
        // Vérifier si nous avons des données de secours
        if (window.fallbackPreferencesData && window.fallbackPreferencesData.length > 0) {
            console.log("Données de secours disponibles:", window.fallbackPreferencesData);
            
            // Trouver l\'élément du contrôleur
            const preferencesElement = document.querySelector(\'[data-controller="student-preferences"]\');
            if (preferencesElement) {
                // Tenter de récupérer le contrôleur
                setTimeout(() => {
                    try {
                        // Récupérer le contrôleur via l\'API Stimulus
                        if (window.Stimulus && typeof window.Stimulus.getControllerForElementAndIdentifier === "function") {
                            const controller = window.Stimulus.getControllerForElementAndIdentifier(
                                preferencesElement, 
                                "student-preferences"
                            );
                            
                            if (controller) {
                                console.log("Contrôleur trouvé, application des préférences");
                                
                                // Appliquer directement les préférences
                                controller.preferences = window.fallbackPreferencesData.map(pref => ({
                                    internship_id: pref.internship_id,
                                    title: pref.title || "Stage sans titre",
                                    company: pref.company_name || "Entreprise non spécifiée",
                                    rank: pref.preference_order || 1,
                                    reason: pref.reason || null
                                }));
                                
                                // Mettre à jour l\'interface
                                controller.updatePreferencesList();
                                
                                // Masquer l\'indicateur de chargement
                                const loadingIndicator = document.querySelector(\'[data-student-preferences-target="loadingIndicator"]\');
                                if (loadingIndicator) {
                                    loadingIndicator.classList.add("hidden");
                                }
                                
                                // Mettre à jour le compteur de préférences
                                document.getElementById("preferences-count").textContent = 
                                    controller.preferences.length + "/5";
                                document.getElementById("preferences-progress").style.width = 
                                    (controller.preferences.length / 5 * 100) + "%";
                                
                                return true;
                            } else {
                                console.log("Contrôleur non trouvé dans l\'API Stimulus");
                            }
                        } else {
                            console.log("API Stimulus non disponible");
                        }
                    } catch (e) {
                        console.error("Erreur lors de l\'application des préférences:", e);
                    }
                }, 500);
            } else {
                console.log("Élément du contrôleur non trouvé");
            }
        } else {
            console.log("Aucune donnée de secours disponible");
        }
        
        return false;
    };
    
    // Première tentative immédiate
    applyFallbackPreferences();
    
    // Deuxième tentative après un délai
    setTimeout(applyFallbackPreferences, 1000);
    
    // Troisième tentative avec un délai plus long
    setTimeout(applyFallbackPreferences, 3000);
});
</script>';
            
            // Ajouter le script avant la fermeture body
            $content = str_replace('</body>', $correction_script . "\n</body>", $content);
            $fixes[] = 'Ajout d\'un script de correction pour l\'affichage des préférences';
            
            // Enregistrer les modifications
            if (file_put_contents($preferences_file, $content) !== false) {
                echo '<p class="success">Modifications appliquées avec succès au fichier :</p>';
                echo '<ul>';
                foreach ($fixes as $fix) {
                    echo '<li>' . $fix . '</li>';
                }
                echo '</ul>';
            } else {
                echo '<p class="error">Impossible d\'écrire les modifications dans le fichier.</p>';
            }
        } else {
            echo '<p class="error">Impossible de créer une sauvegarde du fichier.</p>';
        }
    } else {
        echo '<p class="error">Fichier cible non trouvé : ' . $preferences_file . '</p>';
    }
    echo '</div>';
    
    // ÉTAPE 4 : Création des préférences de test (si nécessaire)
    echo '<div class="step">';
    echo '<div class="step-title">ÉTAPE 4 : Création des préférences de test</div>';
    
    // Vérifier si une action est demandée
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'view_preferences') {
            $student_id = $_POST['student_id'] ?? null;
            
            if ($student_id) {
                // Récupérer les informations de l'étudiant
                $stmt = $db->prepare("
                    SELECT s.*, u.username, u.first_name, u.last_name 
                    FROM students s 
                    JOIN users u ON s.user_id = u.id 
                    WHERE s.id = :student_id
                ");
                $stmt->bindValue(':student_id', $student_id, PDO::PARAM_INT);
                $stmt->execute();
                $student = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($student) {
                    echo '<h3>Préférences de ' . $student['first_name'] . ' ' . $student['last_name'] . '</h3>';
                    
                    // Récupérer les préférences
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
                    
                    if (count($preferences) > 0) {
                        echo '<table>';
                        echo '<tr><th>Ordre</th><th>Stage</th><th>Entreprise</th><th>Raison</th></tr>';
                        foreach ($preferences as $pref) {
                            echo '<tr>';
                            echo '<td>' . $pref['preference_order'] . '</td>';
                            echo '<td>' . $pref['title'] . '</td>';
                            echo '<td>' . $pref['company_name'] . '</td>';
                            echo '<td>' . ($pref['reason'] ?? '-') . '</td>';
                            echo '</tr>';
                        }
                        echo '</table>';
                        
                        // Option pour tester avec cet étudiant
                        echo '<form method="post">';
                        echo '<input type="hidden" name="action" value="test_student">';
                        echo '<input type="hidden" name="student_id" value="' . $student_id . '">';
                        echo '<input type="hidden" name="user_id" value="' . $student['user_id'] . '">';
                        echo '<button type="submit" class="action-button">Tester la page avec cet étudiant</button>';
                        echo '</form>';
                    } else {
                        echo '<p class="warning">Aucune préférence trouvée pour cet étudiant.</p>';
                    }
                } else {
                    echo '<p class="error">Étudiant non trouvé.</p>';
                }
            }
        } elseif ($_POST['action'] === 'create_test_preferences') {
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
                    $stmt = $db->prepare("DELETE FROM student_preferences WHERE student_id = :student_id");
                    $stmt->bindValue(':student_id', $student_id, PDO::PARAM_INT);
                    $stmt->execute();
                    
                    // Ajouter les nouvelles préférences
                    $inserted = 0;
                    foreach ($internships as $index => $internship) {
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
                        
                        echo '<p class="success">Préférence ajoutée : ' . $internship['title'] . ' (ordre ' . $order . ')</p>';
                        $inserted++;
                    }
                    
                    if ($inserted > 0) {
                        echo '<p class="success">Les préférences de test ont été créées avec succès.</p>';
                        
                        // Récupérer l'utilisateur
                        $stmt = $db->prepare("SELECT user_id FROM students WHERE id = :student_id");
                        $stmt->bindValue(':student_id', $student_id, PDO::PARAM_INT);
                        $stmt->execute();
                        $user_id = $stmt->fetchColumn();
                        
                        if ($user_id) {
                            echo '<form method="post">';
                            echo '<input type="hidden" name="action" value="test_student">';
                            echo '<input type="hidden" name="student_id" value="' . $student_id . '">';
                            echo '<input type="hidden" name="user_id" value="' . $user_id . '">';
                            echo '<button type="submit" class="action-button">Tester la page avec cet étudiant</button>';
                            echo '</form>';
                        }
                    }
                } else {
                    echo '<p class="error">Aucun stage disponible trouvé.</p>';
                }
            }
        } elseif ($_POST['action'] === 'test_student') {
            $student_id = $_POST['student_id'] ?? null;
            $user_id = $_POST['user_id'] ?? null;
            
            if ($student_id && $user_id) {
                // Définir les variables de session
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_role'] = 'student';
                $_SESSION['user'] = [
                    'id' => $student_id
                ];
                
                echo '<p class="success">Session définie pour l\'étudiant ID ' . $student_id . ' (utilisateur ID ' . $user_id . ').</p>';
                echo '<p>Vous pouvez maintenant <a href="/tutoring/views/student/preferences.php" class="action-button">accéder à la page des préférences</a> pour tester.</p>';
            } else {
                echo '<p class="error">ID étudiant ou ID utilisateur manquant.</p>';
            }
        }
    }
    
    // Formulaire pour créer des préférences de test
    echo '<h3>Créer des préférences de test</h3>';
    echo '<p>Vous pouvez créer des préférences de test pour un étudiant sélectionné :</p>';
    
    $stmt = $db->query("
        SELECT s.id, u.username, u.first_name, u.last_name, 
               (SELECT COUNT(*) FROM student_preferences sp WHERE sp.student_id = s.id) as preference_count
        FROM students s
        JOIN users u ON s.user_id = u.id
        ORDER BY preference_count ASC, s.id ASC
        LIMIT 10
    ");
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($students) > 0) {
        echo '<form method="post">';
        echo '<select name="student_id">';
        foreach ($students as $student) {
            echo '<option value="' . $student['id'] . '">' . 
                 $student['first_name'] . ' ' . $student['last_name'] . 
                 ' (' . $student['username'] . ') - ' . 
                 $student['preference_count'] . ' préférences</option>';
        }
        echo '</select>';
        echo '<button type="submit" name="action" value="create_test_preferences" class="action-button" style="margin-left: 10px;">Créer des préférences de test</button>';
        echo '</form>';
    } else {
        echo '<p class="warning">Aucun étudiant disponible.</p>';
    }
    echo '</div>';
    
    // ÉTAPE 5 : Vérification finale et instructions
    echo '<div class="step">';
    echo '<div class="step-title">ÉTAPE 5 : Vérification finale et instructions</div>';
    
    echo '<p>Toutes les corrections ont été appliquées. Voici un résumé des actions effectuées :</p>';
    echo '<ol>';
    echo '<li>Vérification et correction de la structure de la base de données (colonne "reason")</li>';
    echo '<li>Vérification des données existantes dans la table des préférences</li>';
    echo '<li>Correction du code dans la page des préférences</li>';
    echo '<li>Création d\'outils pour tester les préférences</li>';
    echo '</ol>';
    
    echo '<p>Pour tester si les problèmes sont résolus :</p>';
    echo '<ol>';
    echo '<li>Créez des préférences de test pour un étudiant en utilisant le formulaire ci-dessus</li>';
    echo '<li>Utilisez l\'option "Tester la page avec cet étudiant" pour définir la session</li>';
    echo '<li>Accédez à la page des préférences pour vérifier que les préférences s\'affichent correctement</li>';
    echo '</ol>';
    
    echo '<p>Accès directs :</p>';
    echo '<a href="/tutoring/views/student/preferences.php" class="action-button">Page des préférences</a>';
    echo '<a href="/tutoring/views/student/internship.php" class="action-button">Page des stages</a>';
    echo '</div>';
    
} catch (PDOException $e) {
    echo '<p class="error">Erreur de base de données : ' . $e->getMessage() . '</p>';
}

echo '</body></html>';