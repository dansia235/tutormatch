<?php
/**
 * Script pour créer les tables d'algorithmes d'affectation
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/includes/init.php';

// Vérifier que l'utilisateur est connecté et admin
requireRole('admin');

// Charger les scripts SQL
$parametersScript = file_get_contents(__DIR__ . '/database/create_algorithm_parameters_table.sql');
$executionsScript = file_get_contents(__DIR__ . '/database/create_algorithm_executions_table.sql');

if (!$parametersScript || !$executionsScript) {
    die("Erreur: Impossible de charger les scripts SQL.");
}

// Diviser les scripts en instructions individuelles
$parametersQueries = explode(';', $parametersScript);
$executionsQueries = explode(';', $executionsScript);

// Créer les tables
try {
    // Désactiver les contraintes de clé étrangère
    $db->exec('SET FOREIGN_KEY_CHECKS = 0');
    
    // Exécuter les requêtes pour la table des paramètres
    foreach ($parametersQueries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            $db->exec($query);
        }
    }
    
    // Exécuter les requêtes pour la table des exécutions
    foreach ($executionsQueries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            $db->exec($query);
        }
    }
    
    // Réactiver les contraintes de clé étrangère
    $db->exec('SET FOREIGN_KEY_CHECKS = 1');
    
    echo "Les tables d'algorithmes ont été créées avec succès!";
    
} catch (PDOException $e) {
    die("Erreur lors de la création des tables: " . $e->getMessage());
}