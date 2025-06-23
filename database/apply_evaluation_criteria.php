<?php
/**
 * Script pour appliquer les modifications de la structure de base de données
 * Crée les tables et colonnes nécessaires pour les critères d'évaluation
 */

require_once __DIR__ . '/../includes/init.php';

// Fonction pour exécuter une requête SQL avec gestion des erreurs
function executeSql($db, $sql, $description) {
    try {
        $result = $db->exec($sql);
        echo "✅ $description: OK\n";
        return true;
    } catch (PDOException $e) {
        echo "❌ $description: ERREUR\n";
        echo "   " . $e->getMessage() . "\n";
        return false;
    }
}

// Vérifier la connexion à la base de données
if (!isset($db) || !($db instanceof PDO)) {
    die("Erreur: Connexion à la base de données non disponible\n");
}

echo "🔄 Début de la mise à jour de la structure de base de données\n";

// 1. Créer la table evaluation_criteria
$createEvaluationCriteriaTable = "
CREATE TABLE IF NOT EXISTS evaluation_criteria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evaluation_id INT NOT NULL,
    category ENUM('technical', 'professional') NOT NULL,
    name VARCHAR(100) NOT NULL,
    score DECIMAL(3,1) DEFAULT 0,
    comments TEXT DEFAULT NULL,
    FOREIGN KEY (evaluation_id) REFERENCES evaluations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";
executeSql($db, $createEvaluationCriteriaTable, "Création de la table evaluation_criteria");

// 2. Créer les index
$createIndex1 = "CREATE INDEX IF NOT EXISTS idx_evaluation_criteria_evaluation ON evaluation_criteria(evaluation_id);";
executeSql($db, $createIndex1, "Création de l'index sur evaluation_id");

$createIndex2 = "CREATE INDEX IF NOT EXISTS idx_evaluation_criteria_category ON evaluation_criteria(category);";
executeSql($db, $createIndex2, "Création de l'index sur category");

// 3. Ajouter la colonne criteria_scores à la table evaluations
// Vérifier d'abord si la colonne existe
$checkCriteriaColumn = "SHOW COLUMNS FROM evaluations LIKE 'criteria_scores'";
$stmt = $db->prepare($checkCriteriaColumn);
$stmt->execute();
$columnExists = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$columnExists) {
    $addCriteriaColumn = "ALTER TABLE evaluations ADD COLUMN criteria_scores JSON DEFAULT NULL AFTER score;";
    executeSql($db, $addCriteriaColumn, "Ajout de la colonne criteria_scores à la table evaluations");
} else {
    echo "ℹ️ La colonne criteria_scores existe déjà dans la table evaluations\n";
}

// 4. Mettre à jour la table student_scores
// Vérifier si la table existe
$checkStudentScoresTable = "SHOW TABLES LIKE 'student_scores'";
$stmt = $db->prepare($checkStudentScoresTable);
$stmt->execute();
$tableExists = $stmt->fetch(PDO::FETCH_ASSOC);

if ($tableExists) {
    // Liste des colonnes à ajouter
    $columns = [
        'technical_mastery' => 'DECIMAL(3,1) DEFAULT 0 AFTER technical_score',
        'work_quality' => 'DECIMAL(3,1) DEFAULT 0 AFTER technical_mastery',
        'problem_solving' => 'DECIMAL(3,1) DEFAULT 0 AFTER work_quality',
        'documentation' => 'DECIMAL(3,1) DEFAULT 0 AFTER problem_solving',
        'autonomy' => 'DECIMAL(3,1) DEFAULT 0 AFTER teamwork_score',
        'deadline_respect' => 'DECIMAL(3,1) DEFAULT 0 AFTER autonomy'
    ];
    
    // Vérifier et ajouter chaque colonne si nécessaire
    foreach ($columns as $column => $definition) {
        $checkColumnQuery = "SHOW COLUMNS FROM student_scores LIKE '$column'";
        $stmt = $db->prepare($checkColumnQuery);
        $stmt->execute();
        $columnExists = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$columnExists) {
            $addColumnQuery = "ALTER TABLE student_scores ADD COLUMN $column $definition;";
            executeSql($db, $addColumnQuery, "Ajout de la colonne $column à la table student_scores");
        } else {
            echo "ℹ️ La colonne $column existe déjà dans la table student_scores\n";
        }
    }
} else {
    // Créer la table student_scores si elle n'existe pas
    $createStudentScoresTable = "
    CREATE TABLE IF NOT EXISTS student_scores (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        assignment_id INT NOT NULL,
        technical_score DECIMAL(3,1) DEFAULT 0,
        technical_mastery DECIMAL(3,1) DEFAULT 0,
        work_quality DECIMAL(3,1) DEFAULT 0,
        problem_solving DECIMAL(3,1) DEFAULT 0,
        documentation DECIMAL(3,1) DEFAULT 0,
        communication_score DECIMAL(3,1) DEFAULT 0,
        teamwork_score DECIMAL(3,1) DEFAULT 0,
        autonomy DECIMAL(3,1) DEFAULT 0,
        deadline_respect DECIMAL(3,1) DEFAULT 0,
        average_score DECIMAL(3,1) DEFAULT 0,
        completed_evaluations INT DEFAULT 0,
        total_evaluations INT DEFAULT 5,
        last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_student_assignment (student_id, assignment_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    executeSql($db, $createStudentScoresTable, "Création de la table student_scores");
    
    // Créer les index pour la table student_scores
    $createStudentScoresIndex1 = "CREATE INDEX idx_student_scores_student ON student_scores(student_id);";
    executeSql($db, $createStudentScoresIndex1, "Création de l'index sur student_id");
    
    $createStudentScoresIndex2 = "CREATE INDEX idx_student_scores_assignment ON student_scores(assignment_id);";
    executeSql($db, $createStudentScoresIndex2, "Création de l'index sur assignment_id");
}

// 5. Créer la table des critères prédéfinis
$createPredefinedCriteriaTable = "
CREATE TABLE IF NOT EXISTS predefined_criteria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category ENUM('technical', 'professional') NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    UNIQUE KEY (category, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";
executeSql($db, $createPredefinedCriteriaTable, "Création de la table predefined_criteria");

// Vérifier si la table est vide
$checkPredefinedCriteriaCount = "SELECT COUNT(*) FROM predefined_criteria";
$stmt = $db->prepare($checkPredefinedCriteriaCount);
$stmt->execute();
$criteriaCount = $stmt->fetchColumn();

// Insérer les critères prédéfinis si la table est vide
if ($criteriaCount == 0) {
    $predefinedCriteria = [
        ['technical', 'Maîtrise des technologies', 'Capacité à utiliser efficacement les technologies et outils requis'],
        ['technical', 'Qualité du travail', 'Précision, rigueur et fiabilité des livrables'],
        ['technical', 'Résolution de problèmes', 'Capacité à identifier et résoudre les problèmes techniques'],
        ['technical', 'Documentation', 'Qualité et clarté de la documentation produite'],
        ['professional', 'Autonomie', 'Capacité à travailler de manière indépendante'],
        ['professional', 'Communication', 'Clarté et efficacité dans la communication orale et écrite'],
        ['professional', 'Intégration dans l\'équipe', 'Capacité à travailler en équipe et collaborer'],
        ['professional', 'Respect des délais', 'Ponctualité et respect des échéances fixées']
    ];
    
    $insertCriteriaQuery = "INSERT INTO predefined_criteria (category, name, description) VALUES (?, ?, ?)";
    $stmt = $db->prepare($insertCriteriaQuery);
    
    foreach ($predefinedCriteria as $criteria) {
        try {
            $stmt->execute($criteria);
            echo "✅ Insertion du critère '{$criteria[1]}': OK\n";
        } catch (PDOException $e) {
            echo "❌ Insertion du critère '{$criteria[1]}': ERREUR\n";
            echo "   " . $e->getMessage() . "\n";
        }
    }
} else {
    echo "ℹ️ La table predefined_criteria contient déjà des données\n";
}

echo "✅ Mise à jour de la structure de base de données terminée\n";
?>