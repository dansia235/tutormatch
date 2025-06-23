<?php
/**
 * Script pour standardiser les noms de champs dans la table evaluations
 * 
 * Ce script met à jour la structure de la table evaluations pour uniformiser
 * les noms de champs et ajouter les champs manquants.
 */

// Configuration d'encodage UTF-8
ini_set('default_charset', 'UTF-8');

// Ajouter une fonction d'échappement
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Connexion directe à la base de données
try {
    // Charger les informations de connexion à la base de données
    if (file_exists(__DIR__ . '/../config/database.php')) {
        $config = include __DIR__ . '/../config/database.php';
    } else if (file_exists(__DIR__ . '/../config/database.example.php')) {
        $config = include __DIR__ . '/../config/database.example.php';
    } else {
        throw new Exception("Fichier de configuration de la base de données introuvable");
    }
    
    // Établir la connexion à la base de données
    $db = new PDO("mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}", $config['username'], $config['password']);
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
    <title>Standardisation des champs d'évaluation</title>
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
        code { background: #f5f5f5; padding: 2px 5px; border-radius: 3px; font-family: monospace; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Standardisation des champs d'évaluation</h1>";

// Confirmation par GET parameter
if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'yes') {
    echo "<div class='warning'>
            <h2>⚠️ Attention !</h2>
            <p>Ce script va modifier la structure de la table <code>evaluations</code> pour standardiser les noms de champs.</p>
            <p>Il est recommandé de faire une sauvegarde de la base de données avant de continuer.</p>
            <p><a href='?confirm=yes' class='btn btn-danger'>Confirmer et procéder aux modifications</a></p>
            <p><a href='/tutoring/' class='btn'>Annuler et retourner à l'accueil</a></p>
          </div>";
    echo "</div></body></html>";
    exit;
}

// Début du traitement
try {
    // Vérifier d'abord si la table evaluations existe
    $stmt = $db->query("SHOW TABLES LIKE 'evaluations'");
    if ($stmt->rowCount() == 0) {
        throw new Exception("La table 'evaluations' n'existe pas dans la base de données.");
    }
    
    // Récupérer la structure actuelle de la table
    $columns = [];
    $stmt = $db->query("SHOW COLUMNS FROM evaluations");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $columns[$row['Field']] = $row;
    }
    
    // Définir les changements de noms de champs à effectuer
    $renameColumns = [
        'feedback' => 'comments',
        'areas_to_improve' => 'areas_for_improvement'
    ];
    
    // Définir les nouveaux champs à ajouter
    $addColumns = [
        'next_steps' => "TEXT DEFAULT NULL AFTER " . (isset($columns['areas_to_improve']) ? 'areas_to_improve' : 'areas_for_improvement'),
        'status' => "ENUM('draft','submitted','reviewed') NOT NULL DEFAULT 'submitted' AFTER type",
        'technical_avg' => "DECIMAL(3,1) DEFAULT NULL AFTER score",
        'professional_avg' => "DECIMAL(3,1) DEFAULT NULL AFTER technical_avg",
        'updated_at' => "TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER submission_date"
    ];
    
    // Commencer une transaction
    $db->beginTransaction();
    
    echo "<h2>Modifications de la structure</h2>";
    echo "<table>
            <thead>
                <tr>
                    <th>Opération</th>
                    <th>Détails</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>";
    
    // 1. Renommer les colonnes existantes pour standardiser les noms
    foreach ($renameColumns as $oldName => $newName) {
        if (isset($columns[$oldName]) && !isset($columns[$newName])) {
            $columnDef = $columns[$oldName]['Type'];
            if ($columns[$oldName]['Null'] === 'YES') {
                $columnDef .= " DEFAULT NULL";
            } else if ($columns[$oldName]['Default'] !== null) {
                $columnDef .= " DEFAULT '{$columns[$oldName]['Default']}'";
            }
            
            try {
                $sql = "ALTER TABLE evaluations CHANGE COLUMN `$oldName` `$newName` $columnDef";
                $db->exec($sql);
                
                echo "<tr>
                        <td>Renommer colonne</td>
                        <td><code>$oldName</code> → <code>$newName</code></td>
                        <td class='success'>Réussi</td>
                      </tr>";
            } catch (PDOException $e) {
                echo "<tr>
                        <td>Renommer colonne</td>
                        <td><code>$oldName</code> → <code>$newName</code></td>
                        <td class='error'>Échec: " . h($e->getMessage()) . "</td>
                      </tr>";
            }
        } else if (isset($columns[$newName])) {
            echo "<tr>
                    <td>Renommer colonne</td>
                    <td><code>$oldName</code> → <code>$newName</code></td>
                    <td class='warning'>Ignoré: la colonne <code>$newName</code> existe déjà</td>
                  </tr>";
        } else {
            echo "<tr>
                    <td>Renommer colonne</td>
                    <td><code>$oldName</code> → <code>$newName</code></td>
                    <td class='warning'>Ignoré: la colonne <code>$oldName</code> n'existe pas</td>
                  </tr>";
        }
    }
    
    // 2. Ajouter les colonnes manquantes
    foreach ($addColumns as $columnName => $columnDef) {
        if (!isset($columns[$columnName])) {
            try {
                $sql = "ALTER TABLE evaluations ADD COLUMN `$columnName` $columnDef";
                $db->exec($sql);
                
                echo "<tr>
                        <td>Ajouter colonne</td>
                        <td><code>$columnName</code> : <code>$columnDef</code></td>
                        <td class='success'>Réussi</td>
                      </tr>";
            } catch (PDOException $e) {
                echo "<tr>
                        <td>Ajouter colonne</td>
                        <td><code>$columnName</code> : <code>$columnDef</code></td>
                        <td class='error'>Échec: " . h($e->getMessage()) . "</td>
                      </tr>";
            }
        } else {
            echo "<tr>
                    <td>Ajouter colonne</td>
                    <td><code>$columnName</code></td>
                    <td class='warning'>Ignoré: la colonne existe déjà</td>
                  </tr>";
        }
    }
    
    // 3. Mettre à jour le champ criteria_scores pour s'assurer qu'il est au format JSON
    if (isset($columns['criteria_scores'])) {
        try {
            // Vérifier si le champ a déjà la contrainte JSON_VALID
            $hasJsonCheck = false;
            $stmt = $db->query("SHOW CREATE TABLE evaluations");
            $createTable = $stmt->fetch(PDO::FETCH_ASSOC);
            if (isset($createTable['Create Table']) && strpos($createTable['Create Table'], 'json_valid') !== false) {
                $hasJsonCheck = true;
            }
            
            if (!$hasJsonCheck) {
                $sql = "ALTER TABLE evaluations MODIFY COLUMN `criteria_scores` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`criteria_scores`))";
                $db->exec($sql);
                
                echo "<tr>
                        <td>Modifier colonne</td>
                        <td><code>criteria_scores</code> : ajouter vérification JSON</td>
                        <td class='success'>Réussi</td>
                      </tr>";
            } else {
                echo "<tr>
                        <td>Modifier colonne</td>
                        <td><code>criteria_scores</code> : ajouter vérification JSON</td>
                        <td class='warning'>Ignoré: la contrainte existe déjà</td>
                      </tr>";
            }
        } catch (PDOException $e) {
            echo "<tr>
                    <td>Modifier colonne</td>
                    <td><code>criteria_scores</code> : ajouter vérification JSON</td>
                    <td class='error'>Échec: " . h($e->getMessage()) . "</td>
                  </tr>";
        }
    } else {
        // Ajouter le champ criteria_scores s'il n'existe pas
        try {
            $sql = "ALTER TABLE evaluations ADD COLUMN `criteria_scores` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`criteria_scores`)) AFTER `professional_avg`";
            $db->exec($sql);
            
            echo "<tr>
                    <td>Ajouter colonne</td>
                    <td><code>criteria_scores</code> : champ JSON pour les critères</td>
                    <td class='success'>Réussi</td>
                  </tr>";
        } catch (PDOException $e) {
            echo "<tr>
                    <td>Ajouter colonne</td>
                    <td><code>criteria_scores</code> : champ JSON pour les critères</td>
                    <td class='error'>Échec: " . h($e->getMessage()) . "</td>
                  </tr>";
        }
    }
    
    echo "</tbody></table>";
    
    // Valider la transaction
    $db->commit();
    
    // Afficher la structure finale de la table
    $stmt = $db->query("DESCRIBE evaluations");
    $finalColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Structure finale de la table</h2>";
    echo "<table>
            <thead>
                <tr>
                    <th>Champ</th>
                    <th>Type</th>
                    <th>Null</th>
                    <th>Défaut</th>
                </tr>
            </thead>
            <tbody>";
    
    foreach ($finalColumns as $column) {
        echo "<tr>
                <td><code>{$column['Field']}</code></td>
                <td>{$column['Type']}</td>
                <td>{$column['Null']}</td>
                <td>{$column['Default']}</td>
              </tr>";
    }
    
    echo "</tbody></table>";
    
    echo "<div class='success'>
            <h2>✅ Standardisation terminée</h2>
            <p>La structure de la table <code>evaluations</code> a été mise à jour avec succès.</p>
          </div>";
    
} catch (Exception $e) {
    // Annuler la transaction en cas d'erreur
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    echo "<div class='error'>
            <h2>❌ Erreur</h2>
            <p>Une erreur est survenue lors de la standardisation des champs : " . h($e->getMessage()) . "</p>
          </div>";
}

echo "    <div class='actions'>
            <a href='/tutoring/clean_evaluations.php' class='btn btn-info'>Nettoyer les évaluations</a>
            <a href='/tutoring/reset_evaluations.php' class='btn btn-info'>Réinitialiser les évaluations</a>
            <a href='/tutoring/' class='btn'>Retour à l'accueil</a>
        </div>
    </div>
</body>
</html>";
?>