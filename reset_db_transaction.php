<?php
/**
 * Script pour réinitialiser les transactions dans la base de données
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/includes/init.php';

// Vérifier que l'utilisateur est connecté et admin
requireRole('admin');

try {
    // Affichage des informations sur les transactions
    echo "<h2>État des transactions</h2>";
    
    try {
        $inTransaction = $db->inTransaction();
        echo "Transaction active: " . ($inTransaction ? "OUI" : "NON") . "<br>";
    } catch (Exception $e) {
        echo "Erreur lors de la vérification des transactions: " . $e->getMessage() . "<br>";
    }
    
    // Forcer un rollback si une transaction est active
    try {
        if ($db->inTransaction()) {
            $db->rollBack();
            echo "<span style='color: green;'>Transaction annulée avec succès.</span><br>";
        } else {
            echo "<span style='color: blue;'>Aucune transaction active à annuler.</span><br>";
        }
    } catch (Exception $e) {
        echo "<span style='color: red;'>Erreur lors de l'annulation de la transaction: " . $e->getMessage() . "</span><br>";
        
        // Essayer une approche plus radicale
        try {
            $db->exec('ROLLBACK');
            echo "<span style='color: green;'>Transaction annulée manuellement.</span><br>";
        } catch (Exception $e2) {
            echo "<span style='color: red;'>Échec de l'annulation manuelle: " . $e2->getMessage() . "</span><br>";
        }
    }
    
    // Vérifier à nouveau l'état des transactions
    try {
        $inTransaction = $db->inTransaction();
        echo "Transaction active après réinitialisation: " . ($inTransaction ? "OUI" : "NON") . "<br>";
    } catch (Exception $e) {
        echo "Erreur lors de la re-vérification des transactions: " . $e->getMessage() . "<br>";
    }
    
    echo "<h3>Actions supplémentaires</h3>";
    
    // Lien pour retourner à la génération d'affectations
    echo "<a href='/tutoring/views/admin/assignments/generate.php' class='btn btn-primary'>Retourner à la page de génération</a><br><br>";
    
    // Vérifier si les tables d'algorithmes existent
    echo "<h3>Vérification des tables</h3>";
    
    $tables = ['algorithm_parameters', 'algorithm_executions'];
    
    foreach ($tables as $table) {
        $query = "SHOW TABLES LIKE '$table'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $exists = $stmt->rowCount() > 0;
        
        echo "Table '$table': " . ($exists ? "<span style='color: green;'>Existe</span>" : "<span style='color: red;'>N'existe pas</span>") . "<br>";
        
        if ($exists) {
            // Vérifier la structure
            $query = "DESCRIBE $table";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<details>";
            echo "<summary>Structure de la table '$table'</summary>";
            echo "<pre>";
            print_r($columns);
            echo "</pre>";
            echo "</details>";
        }
    }
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>Erreur: " . $e->getMessage() . "</h2>";
}
?>