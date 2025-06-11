<?php
/**
 * Script pour exécuter les benchmarks des algorithmes d'affectation
 */

// Vérifier les arguments
$algorithm = $argv[1] ?? null;
$size = $argv[2] ?? 'all'; // Taille du test (petit, moyen, grand, all)

if (!$algorithm) {
    echo "Usage: php benchmark_runner.php [algorithm] [size]\n";
    echo "Algorithmes disponibles: greedy, hungarian\n";
    echo "Tailles disponibles: petit, moyen, grand, extreme, all (défaut)\n";
    exit(1);
}

// Définir la variable d'environnement pour contrôler la taille du test
if ($size !== 'all') {
    putenv("BENCHMARK_SIZE=$size");
}

// Exécuter le benchmark approprié
switch (strtolower($algorithm)) {
    case 'greedy':
        echo "Exécution du benchmark pour l'algorithme Glouton...\n\n";
        require_once __DIR__ . '/GreedyAlgorithmBenchmark.php';
        break;
    
    case 'hungarian':
        echo "Benchmark pour l'algorithme Hongrois pas encore implémenté.\n";
        break;
    
    case 'all':
        echo "Exécution de tous les benchmarks disponibles...\n\n";
        
        echo "\n--- ALGORITHME GLOUTON ---\n\n";
        require_once __DIR__ . '/GreedyAlgorithmBenchmark.php';
        
        echo "\n--- ALGORITHME HONGROIS ---\n\n";
        echo "Pas encore implémenté.\n";
        break;
    
    default:
        echo "Algorithme inconnu: $algorithm\n";
        echo "Algorithmes disponibles: greedy, hungarian, all\n";
        exit(1);
}

// Fin du script
echo "\nBenchmark terminé.\n";