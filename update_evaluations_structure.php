<?php
/**
 * Script pour mettre à jour la structure des tables d'évaluation
 * Ce script nettoie la structure de la base de données en supprimant les tables redondantes
 * et en standardisant la structure de la table evaluations.
 */

// Configuration d'encodage UTF-8
ini_set('default_charset', 'UTF-8');

// Ajouter une fonction d'échappement
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Connexion à la base de données
try {
    // Inclure le fichier de configuration de la base de données
    require_once __DIR__ . '/config/database.php';
    
    // Établir la connexion à la base de données (avec utf8 au lieu de utf8mb4)
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8";
    $db = new PDO($dsn, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Créer une session si elle n'existe pas déjà
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
} catch (Exception $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

// Simuler les droits d'administration pour permettre l'exécution sans session
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'admin';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Mise à jour de la structure des tables d'évaluation</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 900px; margin: 0 auto; }
        h1, h2 { color: #333; }
        p { line-height: 1.5; }
        .success { color: green; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .actions { margin-top: 20px; }
        .btn { display: inline-block; padding: 8px 16px; margin-right: 10px; background-color: #4CAF50; color: white; 
               text-decoration: none; border-radius: 4px; border: none; cursor: pointer; }
        .btn-danger { background-color: #f44336; }
        .btn-info { background-color: #2196F3; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow: auto; font-family: monospace; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Mise à jour de la structure des tables d'évaluation</h1>";

// Confirmation par GET parameter
if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'yes') {
    echo "<div class='warning'>
            <h2>⚠️ Attention !</h2>
            <p>Ce script va mettre à jour la structure des tables d'évaluation en supprimant les tables redondantes.</p>
            <p>Les tables <strong>evaluation_criteria</strong> et <strong>evaluation_scores</strong> seront supprimées.</p>
            <p>Toutes les données sont désormais stockées dans le champ JSON <strong>criteria_scores</strong> de la table <strong>evaluations</strong>.</p>
            <p>Cette action est irréversible. Assurez-vous d'avoir une sauvegarde si nécessaire.</p>
            <p><a href='?confirm=yes' class='btn btn-danger'>Confirmer et procéder à la mise à jour</a></p>
            <p><a href='/tutoring/' class='btn'>Annuler et retourner à l'accueil</a></p>
          </div>";
    echo "</div></body></html>";
    exit;
}

// Fonction pour vérifier si une table existe
function tableExists($db, $table) {
    $stmt = $db->query("SHOW TABLES LIKE '$table'");
    return $stmt->rowCount() > 0;
}

// Fonction pour vérifier si une colonne existe dans une table
function columnExists($db, $table, $column) {
    $stmt = $db->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $stmt->rowCount() > 0;
}

// Début du traitement
try {
    echo "<h2>Analyse de la structure actuelle</h2>";
    
    // 1. Vérifier les tables existantes
    $evaluationTables = [];
    $tables = ['evaluations', 'evaluation_criteria', 'evaluation_scores', 'predefined_criteria'];
    
    echo "<table>
            <thead>
                <tr>
                    <th>Table</th>
                    <th>Existe</th>
                    <th>Nombre de lignes</th>
                </tr>
            </thead>
            <tbody>";
    
    foreach ($tables as $table) {
        $exists = tableExists($db, $table);
        $rowCount = 0;
        
        if ($exists) {
            $evaluationTables[] = $table;
            $stmt = $db->query("SELECT COUNT(*) FROM `$table`");
            $rowCount = $stmt->fetchColumn();
        }
        
        echo "<tr>
                <td>$table</td>
                <td>" . ($exists ? "Oui" : "Non") . "</td>
                <td>" . ($exists ? $rowCount : "-") . "</td>
              </tr>";
    }
    
    echo "</tbody></table>";
    
    // 2. Vérifier la structure de la table evaluations
    if (tableExists($db, 'evaluations')) {
        $columns = $db->query("DESCRIBE `evaluations`")->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Structure actuelle de la table 'evaluations'</h3>";
        echo "<pre>";
        foreach ($columns as $column) {
            echo "{$column['Field']} ({$column['Type']})" . ($column['Key'] == 'PRI' ? " (Primary Key)" : "") . "\n";
        }
        echo "</pre>";
    }
    
    echo "<h2>Mise à jour de la structure</h2>";
    
    // 3. Ajouter les colonnes manquantes à la table evaluations
    $columnsToAdd = [
        'technical_avg' => "ADD COLUMN `technical_avg` DECIMAL(3,1) DEFAULT 0.0 AFTER `criteria_scores`",
        'professional_avg' => "ADD COLUMN `professional_avg` DECIMAL(3,1) DEFAULT 0.0 AFTER `technical_avg`",
        'score' => "ADD COLUMN `score` DECIMAL(3,1) DEFAULT 0.0 AFTER `type`",
        'comments' => "ADD COLUMN `comments` TEXT DEFAULT NULL AFTER `professional_avg`",
        'areas_for_improvement' => "ADD COLUMN `areas_for_improvement` TEXT DEFAULT NULL AFTER `strengths`",
        'next_steps' => "ADD COLUMN `next_steps` TEXT DEFAULT NULL AFTER `areas_for_improvement`",
        'status' => "ADD COLUMN `status` ENUM('draft', 'submitted', 'approved') NOT NULL DEFAULT 'submitted' AFTER `next_steps`",
        'updated_at' => "ADD COLUMN `updated_at` TIMESTAMP NULL DEFAULT NULL AFTER `submission_date`"
    ];
    
    foreach ($columnsToAdd as $column => $sql) {
        if (!columnExists($db, 'evaluations', $column)) {
            $db->exec("ALTER TABLE `evaluations` $sql");
            echo "<p>Colonne <strong>$column</strong> ajoutée à la table evaluations.</p>";
        } else {
            echo "<p>Colonne <strong>$column</strong> existe déjà dans la table evaluations.</p>";
        }
    }
    
    // 4. Migrer les données des anciennes colonnes vers les nouvelles
    $dataMigrations = [
        ['from' => 'feedback', 'to' => 'comments'],
        ['from' => 'areas_to_improve', 'to' => 'areas_for_improvement']
    ];
    
    foreach ($dataMigrations as $migration) {
        $from = $migration['from'];
        $to = $migration['to'];
        
        if (columnExists($db, 'evaluations', $from) && columnExists($db, 'evaluations', $to)) {
            $db->exec("UPDATE `evaluations` SET `$to` = `$from` WHERE `$to` IS NULL AND `$from` IS NOT NULL");
            echo "<p>Données migrées de <strong>$from</strong> vers <strong>$to</strong>.</p>";
        }
    }
    
    // 5. Supprimer les tables redondantes
    $db->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // Démarrer une transaction pour les suppressions
    $db->beginTransaction();
    
    try {
        $tablesToDrop = ['evaluation_criteria', 'evaluation_scores', 'predefined_criteria'];
        foreach ($tablesToDrop as $table) {
            if (tableExists($db, $table)) {
                $db->exec("DROP TABLE IF EXISTS `$table`");
                echo "<p class='warning'>Table <strong>$table</strong> supprimée.</p>";
            } else {
                echo "<p>Table <strong>$table</strong> n'existe pas, aucune action nécessaire.</p>";
            }
        }
        
        // 6. Supprimer les colonnes redondantes
        $columnsToRemove = ['feedback', 'areas_to_improve'];
        
        foreach ($columnsToRemove as $column) {
            if (columnExists($db, 'evaluations', $column)) {
                $db->exec("ALTER TABLE `evaluations` DROP COLUMN `$column`");
                echo "<p>Colonne redondante <strong>$column</strong> supprimée de la table evaluations.</p>";
            }
        }
        
        // Valider la transaction
        $db->commit();
    } catch (Exception $e) {
        // En cas d'erreur, annuler la transaction
        $db->rollBack();
        throw $e;
    }
    
    $db->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    // 7. Vérifier la structure finale
    if (tableExists($db, 'evaluations')) {
        $columns = $db->query("DESCRIBE `evaluations`")->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Structure finale de la table 'evaluations'</h3>";
        echo "<pre>";
        foreach ($columns as $column) {
            echo "{$column['Field']} ({$column['Type']})" . ($column['Key'] == 'PRI' ? " (Primary Key)" : "") . "\n";
        }
        echo "</pre>";
    }
    
    echo "<div class='success'>
            <h2>✅ Structure des tables mise à jour avec succès</h2>
            <p>La structure des tables d'évaluation a été simplifiée. Toutes les données sont maintenant stockées dans la table <strong>evaluations</strong> avec un format JSON pour les critères d'évaluation.</p>
          </div>";
    
} catch (Exception $e) {
    echo "<div class='error'>
            <h2>❌ Erreur</h2>
            <p>Une erreur est survenue lors de la mise à jour de la structure : " . h($e->getMessage()) . "</p>
          </div>";
}

echo "    <div class='actions'>
            <a href='/tutoring/reset_evaluations_new.php' class='btn btn-info'>Réinitialiser les évaluations</a>
            <a href='/tutoring/views/tutor/evaluations.php' class='btn'>Voir les évaluations (tuteur)</a>
            <a href='/tutoring/views/student/evaluations.php' class='btn'>Voir les évaluations (étudiant)</a>
            <a href='/tutoring/' class='btn'>Retour à l'accueil</a>
        </div>
    </div>
</body>
</html>";
?>