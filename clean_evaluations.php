<?php
/**
 * Script pour vider complètement les tables d'évaluations
 * Ce script supprime toutes les données des tables liées aux évaluations
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
    if (file_exists(__DIR__ . '/config/database.php')) {
        $config = include __DIR__ . '/config/database.php';
    } else if (file_exists(__DIR__ . '/config/database.example.php')) {
        $config = include __DIR__ . '/config/database.example.php';
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
    <title>Nettoyage complet des tables d'évaluations</title>
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
    </style>
</head>
<body>
    <div class='container'>
        <h1>Nettoyage complet des tables d'évaluations</h1>";

// Confirmation par GET parameter
if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'yes') {
    echo "<div class='warning'>
            <h2>⚠️ Attention !</h2>
            <p>Ce script va supprimer <strong>TOUTES</strong> les données d'évaluations de la base de données.</p>
            <p>Cette action est irréversible. Assurez-vous d'avoir une sauvegarde si nécessaire.</p>
            <p><a href='?confirm=yes' class='btn btn-danger'>Confirmer et procéder au nettoyage</a></p>
            <p><a href='/tutoring/' class='btn'>Annuler et retourner à l'accueil</a></p>
          </div>";
    echo "</div></body></html>";
    exit;
}

// Début du traitement
try {
    // Démarrer une transaction
    $db->beginTransaction();
    
    // Désactiver temporairement les contraintes de clés étrangères
    $db->exec('SET FOREIGN_KEY_CHECKS = 0');
    
    // Tables à vider
    $tables = [
        'evaluation_scores',
        'evaluation_criteria',
        'evaluations'
    ];
    
    $totalRowsDeleted = 0;
    
    echo "<h2>Suppression des données</h2>";
    echo "<table>
            <thead>
                <tr>
                    <th>Table</th>
                    <th>Nombre de lignes</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>";
    
    foreach ($tables as $table) {
        // Vérifier si la table existe
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            // Compter les lignes avant suppression
            $countStmt = $db->query("SELECT COUNT(*) FROM $table");
            $rowCount = $countStmt->fetchColumn();
            
            // Supprimer les données
            $db->exec("TRUNCATE TABLE $table");
            
            echo "<tr>
                    <td>$table</td>
                    <td>$rowCount</td>
                    <td class='success'>Vidée avec succès</td>
                  </tr>";
            $totalRowsDeleted += $rowCount;
        } else {
            echo "<tr>
                    <td>$table</td>
                    <td>-</td>
                    <td class='warning'>Table non trouvée</td>
                  </tr>";
        }
    }
    
    echo "</tbody></table>";
    
    // Réactiver les contraintes de clés étrangères
    $db->exec('SET FOREIGN_KEY_CHECKS = 1');
    
    // Supprimer également les documents d'évaluation qui pourraient créer des conflits
    $countStmt = $db->query("SELECT COUNT(*) FROM documents WHERE type IN ('evaluation', 'self_evaluation', 'mid_term', 'final')");
    $docCount = $countStmt->fetchColumn();
    
    if ($docCount > 0) {
        $db->exec("DELETE FROM documents WHERE type IN ('evaluation', 'self_evaluation', 'mid_term', 'final')");
        echo "<p class='warning'>$docCount documents d'évaluation ont également été supprimés.</p>";
        $totalRowsDeleted += $docCount;
    } else {
        echo "<p>Aucun document d'évaluation à supprimer.</p>";
    }
    
    // Valider la transaction
    $db->commit();
    
    echo "<div class='success'>
            <h2>✅ Nettoyage terminé</h2>
            <p>Total: $totalRowsDeleted enregistrements supprimés de la base de données.</p>
          </div>";
    
} catch (Exception $e) {
    // Annuler la transaction en cas d'erreur
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    echo "<div class='error'>
            <h2>❌ Erreur</h2>
            <p>Une erreur est survenue lors du nettoyage des tables : " . h($e->getMessage()) . "</p>
          </div>";
}

echo "    <div class='actions'>
            <a href='/tutoring/reset_evaluations.php' class='btn btn-info'>Réinitialiser les évaluations</a>
            <a href='/tutoring/views/tutor/evaluations.php' class='btn'>Voir les évaluations (tuteur)</a>
            <a href='/tutoring/views/student/evaluations.php' class='btn'>Voir les évaluations (étudiant)</a>
            <a href='/tutoring/' class='btn'>Retour à l'accueil</a>
        </div>
    </div>
</body>
</html>";
?>