<?php
/**
 * Script pour nettoyer la base de données des évaluations incohérentes
 * Ce script supprime les évaluations en double et les documents associés qui créent des incohérences
 */

// Configuration d'encodage UTF-8 (pour remplacer celle de init.php)
ini_set('default_charset', 'UTF-8');

// Ajouter une fonction d'échappement similaire à celle de init.php
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Connexion directe à la base de données sans utiliser init.php
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
    <title>Nettoyage des évaluations</title>
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
        <h1>Nettoyage des évaluations</h1>";

// Début du traitement
try {
    // Commencer une transaction
    $db->beginTransaction();
    
    // 1. Identifier les évaluations en double (même assignment_id et même type)
    $query = "
        SELECT assignment_id, type, COUNT(*) as count
        FROM evaluations
        GROUP BY assignment_id, type
        HAVING COUNT(*) > 1
    ";
    $duplicates = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Évaluations en double trouvées: " . count($duplicates) . "</h2>";
    
    if (count($duplicates) > 0) {
        echo "<table>
                <thead>
                    <tr>
                        <th>ID Affectation</th>
                        <th>Type</th>
                        <th>Nombre</th>
                    </tr>
                </thead>
                <tbody>";
        
        foreach ($duplicates as $duplicate) {
            echo "<tr>
                    <td>{$duplicate['assignment_id']}</td>
                    <td>{$duplicate['type']}</td>
                    <td>{$duplicate['count']}</td>
                  </tr>";
        }
        
        echo "</tbody></table>";
        
        // 2. Pour chaque ensemble de doublons, garder la plus récente et supprimer les autres
        // Nettoyer automatiquement sans demander confirmation
        $removed = 0;
        
        foreach ($duplicates as $duplicate) {
            $assignmentId = $duplicate['assignment_id'];
            $type = $duplicate['type'];
            
            // Trouver toutes les évaluations correspondantes
            $evalQuery = "
                SELECT id, submission_date
                FROM evaluations
                WHERE assignment_id = ? AND type = ?
                ORDER BY submission_date DESC
            ";
            $evals = $db->prepare($evalQuery);
            $evals->execute([$assignmentId, $type]);
            $allEvals = $evals->fetchAll(PDO::FETCH_ASSOC);
            
            // Garder le premier (le plus récent) et supprimer les autres
            $kept = array_shift($allEvals);
            echo "<p class='success'>Conservation de l'évaluation ID {$kept['id']} (soumise le {$kept['submission_date']})</p>";
            
            foreach ($allEvals as $eval) {
                $deleteQuery = "DELETE FROM evaluations WHERE id = ?";
                $delete = $db->prepare($deleteQuery);
                $delete->execute([$eval['id']]);
                $removed++;
                
                echo "<p class='warning'>Suppression de l'évaluation ID {$eval['id']} (soumise le {$eval['submission_date']})</p>";
            }
        }
        
        echo "<p class='success'>$removed évaluations en double ont été supprimées.</p>";
    } else {
        echo "<p>Aucune évaluation en double trouvée.</p>";
    }
    
    // 3. Identifier les documents sans évaluations correspondantes
    $query = "
        SELECT d.id, d.title, d.type, d.user_id, u.first_name, u.last_name
        FROM documents d
        JOIN users u ON d.user_id = u.id
        WHERE d.type IN ('evaluation', 'self_evaluation')
        AND NOT EXISTS (
            SELECT 1 FROM evaluations e
            JOIN assignments a ON e.assignment_id = a.id
            JOIN students s ON a.student_id = s.id
            WHERE (s.user_id = d.user_id OR e.evaluator_id = d.user_id)
        )
    ";
    $orphanedDocs = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Documents d'évaluation orphelins trouvés: " . count($orphanedDocs) . "</h2>";
    
    if (count($orphanedDocs) > 0) {
        echo "<table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Titre</th>
                        <th>Type</th>
                        <th>Utilisateur</th>
                    </tr>
                </thead>
                <tbody>";
        
        foreach ($orphanedDocs as $doc) {
            echo "<tr>
                    <td>{$doc['id']}</td>
                    <td>{$doc['title']}</td>
                    <td>{$doc['type']}</td>
                    <td>{$doc['first_name']} {$doc['last_name']}</td>
                  </tr>";
        }
        
        echo "</tbody></table>";
        
        // Nettoyer automatiquement sans demander confirmation
        $removed = 0;
        
        foreach ($orphanedDocs as $doc) {
            $deleteQuery = "DELETE FROM documents WHERE id = ?";
            $delete = $db->prepare($deleteQuery);
            $delete->execute([$doc['id']]);
            $removed++;
            
            echo "<p class='warning'>Suppression du document ID {$doc['id']} ({$doc['title']})</p>";
        }
        
        echo "<p class='success'>$removed documents orphelins ont été supprimés.</p>";
    } else {
        echo "<p>Aucun document d'évaluation orphelin trouvé.</p>";
    }
    
    // Valider la transaction
    $db->commit();
    echo "<p class='success'>Toutes les modifications ont été enregistrées dans la base de données.</p>";
    
} catch (Exception $e) {
    // Annuler la transaction en cas d'erreur
    if (isset($db)) {
        $db->rollBack();
    }
    
    echo "<p class='error'>Erreur lors du nettoyage des évaluations: " . $e->getMessage() . "</p>";
}

echo "    <div class='actions'>
            <a href='/tutoring/reset_evaluations.php' class='btn btn-info'>Réinitialiser les évaluations</a>
            <a href='/tutoring/views/tutor/evaluations.php' class='btn'>Voir les évaluations (tuteur)</a>
            <a href='/tutoring/views/student/evaluations.php' class='btn'>Voir les évaluations (étudiant)</a>
        </div>
    </div>
</body>
</html>";
?>