<?php
/**
 * Implémentation de l'algorithme génétique pour l'affectation étudiant-enseignant
 * Approche évolutive particulièrement adaptée aux grandes instances avec contraintes complexes
 */
namespace App\Algorithm;

use App\DTO\AssignmentParameters;
use App\DTO\AssignmentResult;
use App\Utils\Logger;
use App\Utils\Monitor;

class GeneticAlgorithm implements AssignmentAlgorithmInterface
{
    /**
     * Configuration de l'algorithme
     * @var array
     */
    private array $config;
    
    /**
     * Taille de la population
     * @var int
     */
    private int $populationSize = 100;
    
    /**
     * Nombre de générations
     * @var int
     */
    private int $generations = 50;
    
    /**
     * Taux de mutation
     * @var float
     */
    private float $mutationRate = 0.1;
    
    /**
     * Taux de croisement
     * @var float
     */
    private float $crossoverRate = 0.8;
    
    /**
     * Pourcentage d'élite à conserver
     * @var float
     */
    private float $eliteRate = 0.1;
    
    /**
     * Critère de convergence (générations sans amélioration)
     * @var int
     */
    private int $convergenceThreshold = 10;
    
    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->loadConfiguration();
    }
    
    /**
     * Charge la configuration depuis le fichier
     */
    private function loadConfiguration(): void
    {
        $configPath = __DIR__ . '/../../config/genetic_algorithm.php';
        if (file_exists($configPath)) {
            $this->config = require $configPath;
            
            // Appliquer la configuration par défaut
            $default = $this->config['default'] ?? [];
            $this->populationSize = $default['population_size'] ?? $this->populationSize;
            $this->generations = $default['generations'] ?? $this->generations;
            $this->mutationRate = $default['mutation_rate'] ?? $this->mutationRate;
            $this->crossoverRate = $default['crossover_rate'] ?? $this->crossoverRate;
            $this->eliteRate = $default['elite_rate'] ?? $this->eliteRate;
            $this->convergenceThreshold = $default['convergence_threshold'] ?? $this->convergenceThreshold;
        }
    }
    
    /**
     * Affecte les étudiants aux enseignants en utilisant l'algorithme génétique
     * Alias pour la méthode execute pour compatibilité avec la validation
     * 
     * @param array $students Liste des étudiants
     * @param array $teachers Liste des enseignants
     * @param array $internships Liste des stages
     * @param AssignmentParameters $parameters Paramètres de l'algorithme
     * @return AssignmentResult Résultat contenant les affectations générées
     */
    public function assignStudents(
        array $students, 
        array $teachers,
        array $internships,
        AssignmentParameters $parameters
    ): AssignmentResult {
        return $this->execute($students, $teachers, $internships, $parameters);
    }
    
    /**
     * Exécute l'algorithme génétique d'affectation
     * 
     * @param array $students Liste des étudiants
     * @param array $teachers Liste des enseignants
     * @param array $internships Liste des stages
     * @param AssignmentParameters $parameters Paramètres de l'algorithme
     * @return AssignmentResult Résultat contenant les affectations générées
     */
    public function execute(
        array $students, 
        array $teachers,
        array $internships,
        AssignmentParameters $parameters
    ): AssignmentResult {
        $result = new AssignmentResult();
        $startTime = microtime(true);
        
        // Logger le début de l'exécution
        $this->log('info', 'Démarrage de l\'algorithme génétique', [
            'students_count' => count($students),
            'teachers_count' => count($teachers),
            'population_size' => $this->populationSize,
            'generations' => $this->generations
        ]);
        
        // Enregistrer les métriques
        $this->recordMetric('executions', 1);
        $this->recordMetric('students_count', count($students), 'gauge');
        $this->recordMetric('teachers_count', count($teachers), 'gauge');
        
        try {
            // Validation des entrées
            if (empty($students)) {
                throw new \Exception("Aucun étudiant disponible pour l'affectation");
            }
            
            if (empty($teachers)) {
                throw new \Exception("Aucun enseignant disponible pour l'affectation");
            }
            
            // Ajuster les paramètres selon la taille du problème
            $this->adjustParameters(count($students), count($teachers));
            
            $this->log('debug', 'Paramètres ajustés', [
                'population_size' => $this->populationSize,
                'generations' => $this->generations,
                'mutation_rate' => $this->mutationRate,
                'crossover_rate' => $this->crossoverRate
            ]);
            
            // Initialiser la population
            $population = $this->initializePopulation($students, $teachers, $parameters);
            
            $bestFitness = -1;
            $generationsWithoutImprovement = 0;
            $bestSolution = null;
            
            // Évolution sur plusieurs générations
            for ($generation = 0; $generation < $this->generations; $generation++) {
                // Évaluer la fitness de chaque individu
                $fitnessScores = $this->evaluatePopulation($population, $students, $teachers, $parameters);
                
                // Trouver le meilleur individu de cette génération
                $currentBest = max($fitnessScores);
                $bestIndex = array_search($currentBest, $fitnessScores);
                
                // Vérifier l'amélioration
                if ($currentBest > $bestFitness) {
                    $bestFitness = $currentBest;
                    $bestSolution = $population[$bestIndex];
                    $generationsWithoutImprovement = 0;
                    
                    $this->log('debug', 'Nouvelle meilleure solution trouvée', [
                        'generation' => $generation,
                        'fitness' => $bestFitness
                    ]);
                } else {
                    $generationsWithoutImprovement++;
                }
                
                // Logger l'évolution toutes les 10 générations
                if ($generation % 10 === 0) {
                    $this->log('debug', 'Évolution en cours', [
                        'generation' => $generation,
                        'best_fitness' => $bestFitness,
                        'current_fitness' => $currentBest,
                        'stagnation' => $generationsWithoutImprovement
                    ]);
                    
                    $this->recordMetric('generation_fitness', $currentBest, 'gauge');
                }
                
                // Critère de convergence
                if ($generationsWithoutImprovement >= $this->convergenceThreshold) {
                    $this->log('info', 'Convergence atteinte', [
                        'generation' => $generation,
                        'fitness' => $bestFitness
                    ]);
                    break;
                }
                
                // Sélection et reproduction
                $newPopulation = $this->evolvePopulation($population, $fitnessScores, count($teachers));
                $population = $newPopulation;
            }
            
            // Traiter le meilleur résultat
            $assignments = $this->convertSolutionToAssignments($bestSolution, $students, $teachers);
            $this->populateResult($result, $assignments, $students, $teachers, $bestFitness);
            
            $result->setSuccessful(true);
            
            // Logger le succès
            $this->log('info', 'Algorithme génétique terminé avec succès', [
                'assignments_count' => count($assignments),
                'unassigned_students' => count($result->getUnassignedStudents()),
                'average_score' => $result->getAverageScore(),
                'execution_time' => microtime(true) - $startTime
            ]);
            
            // Enregistrer les métriques finales
            $this->recordMetric('success_rate', 1, 'gauge');
            $this->recordMetric('assignments_created', count($assignments));
            $this->recordMetric('execution_time', microtime(true) - $startTime, 'histogram');
            
        } catch (\Exception $e) {
            $result->setSuccessful(false);
            $result->setErrorMessage($e->getMessage());
            
            // Logger l'erreur
            $this->log('error', 'Erreur dans l\'algorithme génétique', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->recordMetric('errors', 1);
        }
        
        $endTime = microtime(true);
        $result->setExecutionTime($endTime - $startTime);
        
        return $result;
    }
    
    /**
     * Ajuste les paramètres de l'algorithme selon la taille du problème
     * 
     * @param int $studentCount Nombre d'étudiants
     * @param int $teacherCount Nombre d'enseignants
     */
    private function adjustParameters(int $studentCount, int $teacherCount): void
    {
        // Déterminer la taille du problème
        $configKey = 'default';
        
        if (isset($this->config)) {
            if ($studentCount < 50) {
                $configKey = 'small';
            } elseif ($studentCount >= 50 && $studentCount < 200) {
                $configKey = 'medium';
            } elseif ($studentCount >= 200 && $studentCount < 500) {
                $configKey = 'large';
            } else {
                $configKey = 'extra_large';
            }
            
            // Appliquer la configuration correspondante
            $params = $this->config[$configKey] ?? $this->config['default'];
            $this->populationSize = $params['population_size'] ?? $this->populationSize;
            $this->generations = $params['generations'] ?? $this->generations;
            $this->mutationRate = $params['mutation_rate'] ?? $this->mutationRate;
            $this->crossoverRate = $params['crossover_rate'] ?? $this->crossoverRate;
            $this->eliteRate = $params['elite_rate'] ?? $this->eliteRate;
            $this->convergenceThreshold = $params['convergence_threshold'] ?? $this->convergenceThreshold;
        } else {
            // Comportement par défaut si pas de config
            $problemSize = $studentCount + $teacherCount;
            
            // Adapter la taille de population
            if ($problemSize > 200) {
                $this->populationSize = 200;
                $this->generations = 100;
            } elseif ($problemSize > 100) {
                $this->populationSize = 150;
                $this->generations = 75;
            }
            
            // Adapter les taux selon la complexité
            if ($problemSize < 50) {
                $this->mutationRate = 0.15;
                $this->crossoverRate = 0.9;
            }
        }
    }
    
    /**
     * Initialise la population initiale
     * 
     * @param array $students Liste des étudiants
     * @param array $teachers Liste des enseignants
     * @param AssignmentParameters $parameters Paramètres
     * @return array Population initiale
     */
    private function initializePopulation(array $students, array $teachers, AssignmentParameters $parameters): array
    {
        $population = [];
        $studentCount = count($students);
        $teacherCount = count($teachers);
        
        // Récupérer les stratégies depuis la configuration
        $strategies = $this->config['initialization_strategies'] ?? [
            'random' => 0.3,
            'department' => 0.3,
            'greedy' => 0.4
        ];
        
        $randomThreshold = $strategies['random'];
        $departmentThreshold = $randomThreshold + $strategies['department'];
        
        for ($i = 0; $i < $this->populationSize; $i++) {
            $individual = [];
            $ratio = $i / $this->populationSize;
            
            // Stratégies diversifiées d'initialisation
            if ($ratio < $randomThreshold) {
                // Affectation aléatoire pure
                $individual = $this->createRandomSolution($studentCount, $teacherCount);
            } elseif ($ratio < $departmentThreshold) {
                // Affectation basée sur les départements
                $individual = $this->createDepartmentBasedSolution($students, $teachers, $parameters);
            } else {
                // Affectation glouton modifiée
                $individual = $this->createGreedyBasedSolution($students, $teachers, $parameters);
            }
            
            $population[] = $individual;
        }
        
        return $population;
    }
    
    /**
     * Crée une solution aléatoire
     * 
     * @param int $studentCount Nombre d'étudiants
     * @param int $teacherCount Nombre d'enseignants
     * @return array Solution (affectations)
     */
    private function createRandomSolution(int $studentCount, int $teacherCount): array
    {
        $solution = [];
        for ($i = 0; $i < $studentCount; $i++) {
            $solution[$i] = rand(0, $teacherCount - 1);
        }
        return $solution;
    }
    
    /**
     * Crée une solution basée sur les départements
     * 
     * @param array $students Étudiants
     * @param array $teachers Enseignants
     * @param AssignmentParameters $parameters Paramètres
     * @return array Solution
     */
    private function createDepartmentBasedSolution(array $students, array $teachers, AssignmentParameters $parameters): array
    {
        $solution = [];
        
        foreach ($students as $studentIndex => $student) {
            $sameDepTeachers = [];
            
            // Trouver les enseignants du même département
            foreach ($teachers as $teacherIndex => $teacher) {
                if ($student->getDepartment() === $teacher->getDepartment()) {
                    $sameDepTeachers[] = $teacherIndex;
                }
            }
            
            // Choisir aléatoirement parmi les enseignants du même département
            if (!empty($sameDepTeachers)) {
                $solution[$studentIndex] = $sameDepTeachers[array_rand($sameDepTeachers)];
            } else {
                $solution[$studentIndex] = rand(0, count($teachers) - 1);
            }
        }
        
        return $solution;
    }
    
    /**
     * Crée une solution basée sur l'approche glouton avec randomisation
     * 
     * @param array $students Étudiants
     * @param array $teachers Enseignants
     * @param AssignmentParameters $parameters Paramètres
     * @return array Solution
     */
    private function createGreedyBasedSolution(array $students, array $teachers, AssignmentParameters $parameters): array
    {
        $solution = [];
        $teacherLoads = array_fill(0, count($teachers), 0);
        
        foreach ($students as $studentIndex => $student) {
            $bestTeacher = 0;
            $bestScore = -1;
            
            // Évaluer chaque enseignant avec un facteur de randomisation
            foreach ($teachers as $teacherIndex => $teacher) {
                $score = $this->calculateCompatibilityScore($student, $teacher, $parameters);
                
                // Pénaliser la surcharge
                $score -= $teacherLoads[$teacherIndex] * 5;
                
                // Ajouter un facteur aléatoire (30% de variation)
                $randomFactor = (rand(-30, 30) / 100) * $score;
                $score += $randomFactor;
                
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestTeacher = $teacherIndex;
                }
            }
            
            $solution[$studentIndex] = $bestTeacher;
            $teacherLoads[$bestTeacher]++;
        }
        
        return $solution;
    }
    
    /**
     * Évalue la fitness de toute la population
     * 
     * @param array $population Population à évaluer
     * @param array $students Étudiants
     * @param array $teachers Enseignants
     * @param AssignmentParameters $parameters Paramètres
     * @return array Scores de fitness
     */
    private function evaluatePopulation(array $population, array $students, array $teachers, AssignmentParameters $parameters): array
    {
        $fitnessScores = [];
        
        foreach ($population as $individual) {
            $fitnessScores[] = $this->calculateFitness($individual, $students, $teachers, $parameters);
        }
        
        return $fitnessScores;
    }
    
    /**
     * Calcule la fitness d'un individu (solution)
     * 
     * @param array $individual Solution à évaluer
     * @param array $students Étudiants
     * @param array $teachers Enseignants
     * @param AssignmentParameters $parameters Paramètres
     * @return float Score de fitness
     */
    private function calculateFitness(array $individual, array $students, array $teachers, AssignmentParameters $parameters): float
    {
        $totalScore = 0;
        $teacherLoads = array_fill(0, count($teachers), 0);
        $validAssignments = 0;
        
        // 1. Score de compatibilité total
        foreach ($individual as $studentIndex => $teacherIndex) {
            if (isset($students[$studentIndex]) && isset($teachers[$teacherIndex])) {
                $student = $students[$studentIndex];
                $teacher = $teachers[$teacherIndex];
                
                $compatibilityScore = $this->calculateCompatibilityScore($student, $teacher, $parameters);
                $totalScore += $compatibilityScore;
                $teacherLoads[$teacherIndex]++;
                $validAssignments++;
            }
        }
        
        // 2. Pénalité pour déséquilibre de charge
        $avgLoad = array_sum($teacherLoads) / count($teacherLoads);
        $loadVariance = 0;
        foreach ($teacherLoads as $load) {
            $loadVariance += pow($load - $avgLoad, 2);
        }
        $loadVariance /= count($teacherLoads);
        
        // Pénaliser le déséquilibre (plus la variance est élevée, plus la pénalité est forte)
        $balancePenalty = sqrt($loadVariance) * 10;
        
        // 3. Pénalité pour affectations invalides
        $invalidAssignments = count($individual) - $validAssignments;
        $invalidPenalty = $invalidAssignments * 50;
        
        // 4. Bonus pour respect des contraintes de département
        $departmentBonus = 0;
        if (!$parameters->isAllowCrossDepartment()) {
            foreach ($individual as $studentIndex => $teacherIndex) {
                if (isset($students[$studentIndex]) && isset($teachers[$teacherIndex])) {
                    $student = $students[$studentIndex];
                    $teacher = $teachers[$teacherIndex];
                    
                    if ($student->getDepartment() === $teacher->getDepartment()) {
                        $departmentBonus += 20;
                    }
                }
            }
        }
        
        // Score final
        $fitness = $totalScore + $departmentBonus - $balancePenalty - $invalidPenalty;
        
        return max(0, $fitness); // Assurer un score positif
    }
    
    /**
     * Fait évoluer la population (sélection, croisement, mutation)
     * 
     * @param array $population Population actuelle
     * @param array $fitnessScores Scores de fitness
     * @return array Nouvelle population
     */
    private function evolvePopulation(array $population, array $fitnessScores, int $teacherCount): array
    {
        $newPopulation = [];
        
        // 1. Élitisme : conserver les meilleurs individus
        $eliteCount = (int)($this->populationSize * $this->eliteRate);
        $eliteIndices = $this->selectElite($fitnessScores, $eliteCount);
        
        foreach ($eliteIndices as $index) {
            $newPopulation[] = $population[$index];
        }
        
        // 2. Reproduction pour compléter la population
        while (count($newPopulation) < $this->populationSize) {
            // Sélection des parents par tournoi
            $parent1 = $this->tournamentSelection($population, $fitnessScores);
            $parent2 = $this->tournamentSelection($population, $fitnessScores);
            
            // Croisement
            if (rand() / getrandmax() < $this->crossoverRate) {
                [$child1, $child2] = $this->crossover($parent1, $parent2);
            } else {
                $child1 = $parent1;
                $child2 = $parent2;
            }
            
            // Mutation
            $child1 = $this->mutate($child1, $teacherCount);
            $child2 = $this->mutate($child2, $teacherCount);
            
            $newPopulation[] = $child1;
            if (count($newPopulation) < $this->populationSize) {
                $newPopulation[] = $child2;
            }
        }
        
        return array_slice($newPopulation, 0, $this->populationSize);
    }
    
    /**
     * Sélectionne l'élite de la population
     * 
     * @param array $fitnessScores Scores de fitness
     * @param int $eliteCount Nombre d'individus d'élite
     * @return array Indices des individus d'élite
     */
    private function selectElite(array $fitnessScores, int $eliteCount): array
    {
        arsort($fitnessScores);
        return array_slice(array_keys($fitnessScores), 0, $eliteCount);
    }
    
    /**
     * Sélection par tournoi
     * 
     * @param array $population Population
     * @param array $fitnessScores Scores de fitness
     * @param int $tournamentSize Taille du tournoi
     * @return array Individu sélectionné
     */
    private function tournamentSelection(array $population, array $fitnessScores, int $tournamentSize = 3): array
    {
        $tournamentIndices = array_rand($fitnessScores, min($tournamentSize, count($fitnessScores)));
        if (!is_array($tournamentIndices)) {
            $tournamentIndices = [$tournamentIndices];
        }
        
        $bestIndex = $tournamentIndices[0];
        $bestFitness = $fitnessScores[$bestIndex];
        
        foreach ($tournamentIndices as $index) {
            if ($fitnessScores[$index] > $bestFitness) {
                $bestFitness = $fitnessScores[$index];
                $bestIndex = $index;
            }
        }
        
        return $population[$bestIndex];
    }
    
    /**
     * Opérateur de croisement (Order Crossover adapté)
     * 
     * @param array $parent1 Premier parent
     * @param array $parent2 Deuxième parent
     * @return array Deux enfants
     */
    private function crossover(array $parent1, array $parent2): array
    {
        $length = count($parent1);
        
        // Croisement uniforme avec probabilité 50%
        $child1 = [];
        $child2 = [];
        
        for ($i = 0; $i < $length; $i++) {
            if (rand() / getrandmax() < 0.5) {
                $child1[$i] = $parent1[$i];
                $child2[$i] = $parent2[$i];
            } else {
                $child1[$i] = $parent2[$i];
                $child2[$i] = $parent1[$i];
            }
        }
        
        return [$child1, $child2];
    }
    
    /**
     * Opérateur de mutation
     * 
     * @param array $individual Individu à muter
     * @param int $teacherCount Nombre d'enseignants
     * @return array Individu muté
     */
    private function mutate(array $individual, int $teacherCount): array
    {
        $mutated = $individual;
        
        foreach ($mutated as $i => &$gene) {
            if (rand() / getrandmax() < $this->mutationRate) {
                $gene = rand(0, $teacherCount - 1);
            }
        }
        
        return $mutated;
    }
    
    /**
     * Convertit une solution en affectations
     * 
     * @param array $solution Solution à convertir
     * @param array $students Étudiants
     * @param array $teachers Enseignants
     * @return array Affectations
     */
    private function convertSolutionToAssignments(array $solution, array $students, array $teachers): array
    {
        $assignments = [];
        
        foreach ($solution as $studentIndex => $teacherIndex) {
            if (isset($students[$studentIndex]) && isset($teachers[$teacherIndex])) {
                $assignments[] = [
                    'student_id' => $students[$studentIndex]->getId(),
                    'teacher_id' => $teachers[$teacherIndex]->getId(),
                    'compatibility_score' => $this->calculateCompatibilityScore(
                        $students[$studentIndex], 
                        $teachers[$teacherIndex], 
                        new AssignmentParameters()
                    )
                ];
            }
        }
        
        return $assignments;
    }
    
    /**
     * Remplit le résultat avec les affectations
     * 
     * @param AssignmentResult $result Résultat à remplir
     * @param array $assignments Affectations
     * @param array $students Étudiants
     * @param array $teachers Enseignants
     * @param float $bestFitness Meilleur score de fitness
     */
    private function populateResult(AssignmentResult $result, array $assignments, array $students, array $teachers, float $bestFitness): void
    {
        $assignedStudentIds = [];
        $totalScore = 0;
        
        foreach ($assignments as $assignment) {
            $result->addAssignment($assignment);
            $assignedStudentIds[] = $assignment['student_id'];
            $totalScore += $assignment['compatibility_score'];
        }
        
        // Ajouter les étudiants non affectés
        foreach ($students as $student) {
            if (!in_array($student->getId(), $assignedStudentIds)) {
                $result->addUnassignedStudent($student);
            }
        }
        
        // Calculer le score moyen
        if (count($assignments) > 0) {
            $result->setAverageScore($totalScore / count($assignments));
        }
    }
    
    /**
     * Calcule le score de compatibilité entre un étudiant et un enseignant
     * 
     * @param object $student Étudiant
     * @param object $teacher Enseignant
     * @param AssignmentParameters $parameters Paramètres
     * @return float Score de compatibilité
     */
    private function calculateCompatibilityScore(object $student, object $teacher, AssignmentParameters $parameters): float
    {
        $score = 0;
        
        // Score basé sur le département
        if ($student->getDepartment() === $teacher->getDepartment()) {
            $score += $parameters->getDepartmentWeight();
        }
        
        // Score basé sur les préférences (simplifié)
        if ($parameters->isPrioritizePreferences()) {
            $score += $parameters->getPreferenceWeight() * 0.5; // Score par défaut
        }
        
        // Score basé sur l'équilibrage de charge
        if ($parameters->isBalanceWorkload()) {
            $score += $parameters->getCapacityWeight() * 0.5; // Score par défaut
        }
        
        return $score;
    }
    
    /**
     * Setters pour les paramètres de l'algorithme génétique
     */
    public function setPopulationSize(int $size): self
    {
        $this->populationSize = $size;
        return $this;
    }
    
    public function setGenerations(int $generations): self
    {
        $this->generations = $generations;
        return $this;
    }
    
    public function setMutationRate(float $rate): self
    {
        $this->mutationRate = $rate;
        return $this;
    }
    
    public function setCrossoverRate(float $rate): self
    {
        $this->crossoverRate = $rate;
        return $this;
    }
    
    public function setEliteRate(float $rate): self
    {
        $this->eliteRate = $rate;
        return $this;
    }
    
    /**
     * Méthode utilitaire pour logger
     * 
     * @param string $level Niveau de log (debug, info, warning, error)
     * @param string $message Message à logger
     * @param array $context Contexte additionnel
     */
    private function log(string $level, string $message, array $context = []): void
    {
        try {
            if (class_exists('\App\Utils\Logger')) {
                $logger = \App\Utils\Logger::getInstance();
                $context['algorithm'] = 'genetic';
                $logger->$level($message, $context);
            } elseif (file_exists(__DIR__ . '/../../includes/Logger.php')) {
                require_once __DIR__ . '/../../includes/Logger.php';
                $logger = \Logger::getInstance();
                $context['algorithm'] = 'genetic';
                $logger->$level($message, $context);
            } else {
                // Fallback sur error_log si logger non disponible
                error_log("[GeneticAlgorithm][$level] $message " . json_encode($context));
            }
        } catch (\Exception $e) {
            error_log("Erreur de logging: " . $e->getMessage());
        }
    }
    
    /**
     * Méthode utilitaire pour enregistrer des métriques
     * 
     * @param string $metric Nom de la métrique
     * @param float $value Valeur
     * @param string $type Type de métrique (counter, gauge, histogram)
     */
    private function recordMetric(string $metric, float $value, string $type = 'counter'): void
    {
        try {
            if (class_exists('\App\Utils\Monitor')) {
                $monitor = \App\Utils\Monitor::getInstance();
                $fullMetric = 'genetic_algorithm.' . $metric;
                
                switch ($type) {
                    case 'gauge':
                        $monitor->gauge($fullMetric, $value);
                        break;
                    case 'histogram':
                        $monitor->histogram($fullMetric, $value);
                        break;
                    case 'counter':
                    default:
                        $monitor->increment($fullMetric, $value);
                        break;
                }
            } elseif (file_exists(__DIR__ . '/../../includes/Monitor.php')) {
                require_once __DIR__ . '/../../includes/Monitor.php';
                $monitor = \Monitor::getInstance();
                $fullMetric = 'genetic_algorithm.' . $metric;
                
                switch ($type) {
                    case 'gauge':
                        $monitor->gauge($fullMetric, $value);
                        break;
                    case 'histogram':
                        $monitor->histogram($fullMetric, $value);
                        break;
                    case 'counter':
                    default:
                        $monitor->increment($fullMetric, $value);
                        break;
                }
            }
        } catch (\Exception $e) {
            // Ignorer silencieusement les erreurs de métriques
        }
    }
}