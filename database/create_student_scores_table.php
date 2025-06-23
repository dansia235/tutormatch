<?php
/**
 * Script pour créer la table student_scores
 * Cette table stockera les scores calculés pour chaque étudiant afin d'assurer
 * une cohérence entre les différentes vues (étudiant et tuteur)
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../includes/init.php';

// Vérifier que l'utilisateur est administrateur
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    die("Accès non autorisé. Vous devez être administrateur pour exécuter ce script.");
}

try {
    // Lire le contenu du fichier SQL
    $sql = file_get_contents(__DIR__ . '/add_student_scores_table.sql');
    
    // Exécuter les requêtes SQL
    $db->exec($sql);
    
    echo "<p>Table student_scores créée avec succès !</p>";
} catch (PDOException $e) {
    echo "<p>Erreur lors de la création de la table : " . $e->getMessage() . "</p>";
}
?>