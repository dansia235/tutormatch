<?php
/**
 * Script de test pour l'API de préférences étudiant
 */

// Définir les entêtes pour une sortie JSON
header('Content-Type: application/json');

// Informations de connexion à la base de données
$host = 'localhost';
$dbname = 'tutoring_system';
$username = 'root';
$password = '';

try {
    // Connexion à la base de données
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Vérifier la structure de la table student_preferences
    $tableStructure = [];
    $stmt = $db->query("DESCRIBE student_preferences");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $column) {
        $tableStructure[$column['Field']] = $column;
    }
    
    // Vérifier si la colonne "reason" existe
    $reasonColumnExists = isset($tableStructure['reason']);
    
    // Si la colonne reason n'existe pas, l'ajouter
    if (!$reasonColumnExists) {
        $db->exec("ALTER TABLE `student_preferences` ADD COLUMN `reason` TEXT NULL AFTER `preference_order`");
        $reasonColumnExists = true;
    }
    
    // Récupérer les données de préférence pour l'étudiant ID 1 (pour test)
    $studentPreferences = [];
    $stmt = $db->prepare("
        SELECT sp.*, i.title, i.company_id, c.name as company_name 
        FROM student_preferences sp
        JOIN internships i ON sp.internship_id = i.id
        JOIN companies c ON i.company_id = c.id
        WHERE sp.student_id = :student_id
        ORDER BY sp.preference_order ASC
    ");
    $stmt->bindValue(':student_id', 1, PDO::PARAM_INT);
    $stmt->execute();
    $preferences = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($preferences as $pref) {
        $studentPreferences[] = [
            'internship_id' => $pref['internship_id'],
            'title' => $pref['title'] ?? 'Stage sans titre',
            'company_name' => $pref['company_name'] ?? 'Entreprise non spécifiée',
            'preference_order' => $pref['preference_order'] ?? 1,
            'rank' => $pref['preference_order'] ?? 1,
            'reason' => $pref['reason'] ?? null
        ];
    }
    
    // Compter le nombre total d'étudiants
    $stmt = $db->query("SELECT COUNT(*) FROM students");
    $studentCount = $stmt->fetchColumn();
    
    // Compter le nombre total de préférences
    $stmt = $db->query("SELECT COUNT(*) FROM student_preferences");
    $preferencesCount = $stmt->fetchColumn();
    
    // Récupérer tous les étudiants avec leurs préférences
    $stmt = $db->query("
        SELECT s.id, u.first_name, u.last_name, 
               COUNT(sp.id) as preference_count 
        FROM students s
        JOIN users u ON s.user_id = u.id
        LEFT JOIN student_preferences sp ON s.id = sp.student_id
        GROUP BY s.id
        ORDER BY preference_count DESC
    ");
    $studentsWithPreferences = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Retourner les résultats
    echo json_encode([
        'success' => true,
        'data' => [
            'table_structure' => $tableStructure,
            'reason_column_exists' => $reasonColumnExists,
            'student_preferences' => $studentPreferences,
            'student_count' => $studentCount,
            'preferences_count' => $preferencesCount,
            'students_with_preferences' => $studentsWithPreferences
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    // Afficher l'erreur
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}