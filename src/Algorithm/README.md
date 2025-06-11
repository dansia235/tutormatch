# Algorithmes d'affectation

Ce répertoire contient les implémentations des algorithmes d'optimisation pour l'affectation des étudiants aux enseignants.

## Structure

- `AssignmentAlgorithmInterface.php` - Interface commune pour tous les algorithmes d'affectation
- `GreedyAlgorithm.php` - Implémentation de l'algorithme glouton
- (À venir) `HungarianAlgorithm.php` - Implémentation de l'algorithme hongrois 
- (À venir) `GeneticAlgorithm.php` - Implémentation de l'algorithme génétique

## Algorithme Glouton (Greedy)

L'algorithme glouton implémente une approche simple mais efficace pour l'affectation:

1. Calcule un score de compatibilité pour chaque paire étudiant-enseignant
2. Trie les paires par score de compatibilité (ordre décroissant)
3. Assigne chaque étudiant au meilleur enseignant disponible

Avantages:
- Performance: O(n² log n) où n est le nombre d'étudiants
- Simplicité d'implémentation et de compréhension
- Adaptation facile à différents critères d'affectation

Limites:
- Ne garantit pas la solution optimale globale
- Peut être sous-optimal pour certaines contraintes complexes

## Tests unitaires

Des tests unitaires sont disponibles dans le répertoire `/tests/Algorithm/` pour valider le comportement des algorithmes:

```bash
vendor/bin/phpunit tests/Algorithm/GreedyAlgorithmTest.php
```

## Benchmarks

Des scripts de benchmark sont disponibles dans le répertoire `/benchmarks/` pour évaluer les performances:

```bash
php benchmarks/benchmark_runner.php greedy
```

## Comparaison des algorithmes

| Algorithme | Complexité | Optimalité | Cas d'usage idéal |
|------------|------------|------------|-------------------|
| Glouton    | O(n² log n)| Non garantie | Ensembles de données moyens, contraintes simples |
| Hongrois   | O(n³)      | Optimale    | Petits ensembles, besoin d'optimalité garantie |
| Génétique  | Variable   | Proche optimale | Grands ensembles, contraintes complexes |

## Utilisation via le service

Les algorithmes sont généralement utilisés via le `AssignmentService`:

```php
$result = $assignmentService->generateAssignments(new GreedyAlgorithm(), [
    'departmentWeight' => 60,
    'allowCrossDepartment' => false
]);
```