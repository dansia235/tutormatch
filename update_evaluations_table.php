<?php
/**
 * Script de mise à jour de la table 'evaluations'
 * Ajoute de nouvelles colonnes pour compatibilité avec le nouveau formulaire d'évaluation
 */

// Inclure le fichier d'initialisation pour accéder à la base de données
require_once __DIR__ . '/includes/init.php';

// Vérifier les droits d'accès
requireRole(['admin']);

// Vérifier si la connexion à la base de données est disponible
if (!isset($db) || $db === null) {
    die("Erreur: Impossible de se connecter à la base de données.");
}

try {
    // Démarrer une transaction
    $db->beginTransaction();
    
    // 1. Vérifier si les colonnes existent déjà
    $columns = $db->query("SHOW COLUMNS FROM evaluations")->fetchAll(PDO::FETCH_COLUMN);
    
    // Ajouter la colonne 'comments' si elle n'existe pas
    if (!in_array('comments', $columns)) {
        $db->exec("ALTER TABLE evaluations ADD COLUMN comments TEXT DEFAULT NULL AFTER score");
        echo "Colonne 'comments' ajoutée.<br>";
        
        // Copier les données de 'feedback' vers 'comments'
        $db->exec("UPDATE evaluations SET comments = feedback WHERE comments IS NULL AND feedback IS NOT NULL");
        echo "Données copiées de 'feedback' vers 'comments'.<br>";
    } else {
        echo "La colonne 'comments' existe déjà.<br>";
    }
    
    // Ajouter la colonne 'areas_for_improvement' si elle n'existe pas
    if (!in_array('areas_for_improvement', $columns)) {
        $db->exec("ALTER TABLE evaluations ADD COLUMN areas_for_improvement TEXT DEFAULT NULL AFTER strengths");
        echo "Colonne 'areas_for_improvement' ajoutée.<br>";
        
        // Copier les données de 'areas_to_improve' vers 'areas_for_improvement'
        $db->exec("UPDATE evaluations SET areas_for_improvement = areas_to_improve WHERE areas_for_improvement IS NULL AND areas_to_improve IS NOT NULL");
        echo "Données copiées de 'areas_to_improve' vers 'areas_for_improvement'.<br>";
    } else {
        echo "La colonne 'areas_for_improvement' existe déjà.<br>";
    }
    
    // Ajouter la colonne 'next_steps' si elle n'existe pas
    if (!in_array('next_steps', $columns)) {
        $db->exec("ALTER TABLE evaluations ADD COLUMN next_steps TEXT DEFAULT NULL AFTER areas_for_improvement");
        echo "Colonne 'next_steps' ajoutée.<br>";
    } else {
        echo "La colonne 'next_steps' existe déjà.<br>";
    }
    
    // Ajouter la colonne 'status' si elle n'existe pas
    if (!in_array('status', $columns)) {
        $db->exec("ALTER TABLE evaluations ADD COLUMN status ENUM('draft', 'submitted', 'approved') NOT NULL DEFAULT 'draft' AFTER next_steps");
        echo "Colonne 'status' ajoutée.<br>";
        
        // Définir toutes les évaluations existantes comme 'submitted'
        $db->exec("UPDATE evaluations SET status = 'submitted' WHERE status = 'draft'");
        echo "Statut des évaluations existantes défini sur 'submitted'.<br>";
    } else {
        echo "La colonne 'status' existe déjà.<br>";
    }
    
    // Ajouter la colonne 'updated_at' si elle n'existe pas
    if (!in_array('updated_at', $columns)) {
        $db->exec("ALTER TABLE evaluations ADD COLUMN updated_at TIMESTAMP NULL DEFAULT NULL AFTER submission_date");
        echo "Colonne 'updated_at' ajoutée.<br>";
        
        // Initialiser updated_at avec la même valeur que submission_date
        $db->exec("UPDATE evaluations SET updated_at = submission_date WHERE updated_at IS NULL");
        echo "Dates de mise à jour initialisées.<br>";
    } else {
        echo "La colonne 'updated_at' existe déjà.<br>";
    }
    
    // Vérifier si la table a été mise à jour correctement
    $columns = $db->query("SHOW COLUMNS FROM evaluations")->fetchAll(PDO::FETCH_COLUMN);
    $requiredColumns = ['comments', 'areas_for_improvement', 'next_steps', 'status', 'updated_at'];
    $allColumnsExist = true;
    
    foreach ($requiredColumns as $column) {
        if (!in_array($column, $columns)) {
            $allColumnsExist = false;
            echo "ERREUR: La colonne '$column' n'a pas été créée correctement.<br>";
        }
    }
    
    if ($allColumnsExist) {
        echo "<br>Toutes les colonnes ont été ajoutées avec succès!<br>";
        $db->commit();
        echo "Les modifications ont été validées.<br>";
    } else {
        $db->rollBack();
        echo "ERREUR: Certaines colonnes n'ont pas été créées. Rollback effectué.<br>";
    }
    
} catch (PDOException $e) {
    // En cas d'erreur, annuler la transaction
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    die("Erreur de base de données: " . $e->getMessage());
}

// Afficher un lien pour retourner à la page principale
echo "<br><a href='/tutoring/views/admin/dashboard.php'>Retour au tableau de bord</a>";