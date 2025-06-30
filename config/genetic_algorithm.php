<?php
/**
 * Configuration de l'algorithme génétique pour TutorMatch
 * 
 * Ce fichier définit les paramètres par défaut et les configurations
 * optimisées pour différentes tailles de problèmes.
 */

return [
    // Paramètres par défaut
    'default' => [
        'population_size' => 100,
        'generations' => 50,
        'mutation_rate' => 0.1,
        'crossover_rate' => 0.8,
        'elite_rate' => 0.1,
        'tournament_size' => 5,
        'convergence_threshold' => 10, // Nombre de générations sans amélioration
    ],
    
    // Configuration pour petits problèmes (< 50 étudiants)
    'small' => [
        'population_size' => 50,
        'generations' => 30,
        'mutation_rate' => 0.15,
        'crossover_rate' => 0.75,
        'elite_rate' => 0.15,
        'tournament_size' => 3,
        'convergence_threshold' => 8,
    ],
    
    // Configuration pour problèmes moyens (50-200 étudiants)
    'medium' => [
        'population_size' => 100,
        'generations' => 50,
        'mutation_rate' => 0.1,
        'crossover_rate' => 0.8,
        'elite_rate' => 0.1,
        'tournament_size' => 5,
        'convergence_threshold' => 10,
    ],
    
    // Configuration pour grands problèmes (200-500 étudiants)
    'large' => [
        'population_size' => 150,
        'generations' => 75,
        'mutation_rate' => 0.08,
        'crossover_rate' => 0.85,
        'elite_rate' => 0.08,
        'tournament_size' => 7,
        'convergence_threshold' => 12,
    ],
    
    // Configuration pour très grands problèmes (> 500 étudiants)
    'extra_large' => [
        'population_size' => 200,
        'generations' => 100,
        'mutation_rate' => 0.05,
        'crossover_rate' => 0.9,
        'elite_rate' => 0.05,
        'tournament_size' => 10,
        'convergence_threshold' => 15,
    ],
    
    // Stratégies d'initialisation
    'initialization_strategies' => [
        'random' => 0.3,        // 30% aléatoire pur
        'greedy' => 0.4,        // 40% basé sur greedy avec randomisation
        'department' => 0.3,    // 30% basé sur les départements
    ],
    
    // Poids pour la fonction de fitness
    'fitness_weights' => [
        'compatibility' => 1.0,      // Poids pour la compatibilité
        'load_balance' => 0.5,       // Poids pour l'équilibrage de charge
        'invalid_penalty' => 10.0,   // Pénalité pour affectations invalides
        'department_bonus' => 0.3,   // Bonus pour respect des départements
        'preference_weight' => 0.8,  // Poids pour les préférences
    ],
    
    // Paramètres adaptatifs
    'adaptive' => [
        'enable_adaptation' => true,
        'min_mutation_rate' => 0.01,
        'max_mutation_rate' => 0.3,
        'adaptation_speed' => 0.05,  // Vitesse d'adaptation du taux de mutation
    ],
    
    // Limites de performance
    'performance' => [
        'max_execution_time' => 300,     // 5 minutes max
        'max_memory_usage' => '256M',    // Limite mémoire
        'parallel_evaluation' => false,   // Pour future implémentation
        'cache_fitness' => true,         // Cache les calculs de fitness
    ],
    
    // Configuration pour les tests et benchmarks
    'testing' => [
        'seed' => 42,                   // Graine pour reproductibilité
        'verbose' => true,              // Logs détaillés
        'collect_statistics' => true,   // Collecter statistiques d'évolution
        'save_best_solutions' => true,  // Sauvegarder meilleures solutions
    ],
];