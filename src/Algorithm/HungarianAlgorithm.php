<?php
/**
 * Implémentation de l'algorithme hongrois pour l'affectation optimale étudiant-enseignant
 * L'algorithme hongrois (ou méthode Kuhn-Munkres) résout le problème d'affectation en temps O(n³)
 */
namespace App\Algorithm;

use App\DTO\AssignmentParameters;
use App\DTO\AssignmentResult;

class HungarianAlgorithm implements AssignmentAlgorithmInterface
{
    /**
     * Exécute l'algorithme d'affectation hongrois (méthode Kuhn-Munkres)
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
        // Initialisation du résultat
        $result = new AssignmentResult();
        
        // Temps de début d'exécution
        $startTime = microtime(true);
        
        try {
            // Vérifier si nous avons des étudiants et des enseignants
            if (empty($students)) {
                throw new \Exception("Aucun étudiant disponible pour l'affectation");
            }
            
            if (empty($teachers)) {
                throw new \Exception("Aucun enseignant disponible pour l'affectation");
            }
            
            // Construire la matrice de coût (inversé de la compatibilité)
            $costMatrix = $this->buildCostMatrix($students, $teachers, $parameters);
            
            // Exécuter l'algorithme hongrois
            $assignments = $this->hungarianAlgorithm($costMatrix);
            
            // Traiter les résultats
            $assignedStudents = [];
            $totalScore = 0;
            $assignmentCount = 0;
            
            foreach ($assignments as $studentIndex => $teacherIndex) {
                // Vérifier si une affectation valide a été trouvée
                if ($teacherIndex !== null && isset($students[$studentIndex]) && isset($teachers[$teacherIndex])) {
                    $student = $students[$studentIndex];
                    $teacher = $teachers[$teacherIndex];
                    
                    // Vérifier la contrainte de département si activée
                    if (!$parameters->isAllowCrossDepartment() && 
                        $student->getDepartment() !== $teacher->getDepartment()) {
                        $result->addUnassignedStudent($student);
                        continue;
                    }
                    
                    // Vérifier la capacité de l'enseignant
                    if (!isset($teacher->getRemainingCapacity()) || $teacher->getRemainingCapacity() <= 0) {
                        $result->addUnassignedStudent($student);
                        continue;
                    }
                    
                    // Calculer le score de compatibilité
                    $compatibilityScore = 100 - $costMatrix[$studentIndex][$teacherIndex];
                    
                    // Créer l'affectation
                    $result->addAssignment([
                        'student_id' => $student->getId(),
                        'teacher_id' => $teacher->getId(),
                        'compatibility_score' => $compatibilityScore
                    ]);
                    
                    // Mettre à jour les statistiques
                    $assignedStudents[$student->getId()] = true;
                    $totalScore += $compatibilityScore;
                    $assignmentCount++;
                    
                    // Diminuer la capacité de l'enseignant
                    $teacher->setRemainingCapacity($teacher->getRemainingCapacity() - 1);
                }
            }
            
            // Ajouter les étudiants non affectés au résultat
            foreach ($students as $student) {
                if (!isset($assignedStudents[$student->getId()])) {
                    $result->addUnassignedStudent($student);
                }
            }
            
            // Calculer le score moyen des affectations
            if ($assignmentCount > 0) {
                $result->setAverageScore($totalScore / $assignmentCount);
            }
            
            // Marquer l'exécution comme réussie
            $result->setSuccessful(true);
        } catch (\Exception $e) {
            // En cas d'erreur, marquer l'exécution comme échouée
            $result->setSuccessful(false);
            $result->setErrorMessage($e->getMessage());
        }
        
        // Calculer le temps d'exécution
        $endTime = microtime(true);
        $result->setExecutionTime($endTime - $startTime);
        
        return $result;
    }
    
    /**
     * Construit la matrice de coût entre étudiants et enseignants
     * 
     * @param array $students Liste des étudiants
     * @param array $teachers Liste des enseignants
     * @param AssignmentParameters $parameters Paramètres de l'algorithme
     * @return array Matrice de coût
     */
    private function buildCostMatrix(array $students, array $teachers, AssignmentParameters $parameters): array
    {
        $costMatrix = [];
        
        foreach ($students as $studentIndex => $student) {
            $costMatrix[$studentIndex] = [];
            
            foreach ($teachers as $teacherIndex => $teacher) {
                // Calculer le score de compatibilité (0-100)
                $compatibilityScore = $this->calculateCompatibilityScore($student, $teacher, $parameters);
                
                // Convertir en coût (100 - score) pour l'algorithme hongrois
                // Plus le score est élevé, plus le coût est faible
                $costMatrix[$studentIndex][$teacherIndex] = 100 - $compatibilityScore;
                
                // Si affectation cross-département non autorisée, mettre un coût très élevé
                if (!$parameters->isAllowCrossDepartment() && 
                    $student->getDepartment() !== $teacher->getDepartment()) {
                    $costMatrix[$studentIndex][$teacherIndex] = 1000; // Coût prohibitif
                }
                
                // Si enseignant sans capacité, mettre un coût très élevé
                if (!isset($teacher->getRemainingCapacity()) || $teacher->getRemainingCapacity() <= 0) {
                    $costMatrix[$studentIndex][$teacherIndex] = 1000; // Coût prohibitif
                }
            }
        }
        
        return $costMatrix;
    }
    
    /**
     * Implémentation de l'algorithme hongrois (Kuhn-Munkres)
     * 
     * @param array $costMatrix Matrice de coût
     * @return array Tableau d'affectations [student_index => teacher_index]
     */
    private function hungarianAlgorithm(array $costMatrix): array
    {
        $n = count($costMatrix); // Nombre d'étudiants
        $m = count($costMatrix[0]); // Nombre d'enseignants
        
        // Étape 1: Rendre la matrice carrée en ajoutant des lignes/colonnes fictives si nécessaire
        $squareMatrix = $this->makeSquareMatrix($costMatrix);
        $size = count($squareMatrix);
        
        // Étape 2: Réduire les lignes (soustraire le minimum de chaque ligne)
        $squareMatrix = $this->reduceRows($squareMatrix);
        
        // Étape 3: Réduire les colonnes (soustraire le minimum de chaque colonne)
        $squareMatrix = $this->reduceColumns($squareMatrix);
        
        // Nombre minimum de lignes pour couvrir tous les zéros
        $minLines = 0;
        $markedRows = [];
        $markedCols = [];
        
        // Répéter jusqu'à ce que le nombre minimum de lignes soit égal à la taille de la matrice
        while ($minLines < $size) {
            // Étape 4: Trouver le nombre minimum de lignes pour couvrir tous les zéros
            [$markedRows, $markedCols, $minLines] = $this->findMinimumCover($squareMatrix);
            
            // Si le nombre minimum de lignes est égal à la taille de la matrice, nous avons terminé
            if ($minLines >= $size) {
                break;
            }
            
            // Étape 5: Créer de nouveaux zéros
            $squareMatrix = $this->createNewZeros($squareMatrix, $markedRows, $markedCols);
        }
        
        // Étape 6: Trouver l'affectation optimale
        $assignments = $this->findOptimalAssignment($squareMatrix);
        
        // Étape 7: Convertir le résultat en fonction de la matrice originale
        $result = [];
        foreach ($assignments as $i => $j) {
            // Vérifier si l'affectation correspond à un étudiant et un enseignant réels
            if ($i < $n && $j < $m) {
                $result[$i] = $j;
            } else {
                $result[$i] = null; // Affectation à une entité fictive
            }
        }
        
        return $result;
    }
    
    /**
     * Convertit la matrice en matrice carrée en ajoutant des lignes/colonnes fictives si nécessaire
     * 
     * @param array $matrix Matrice originale
     * @return array Matrice carrée
     */
    private function makeSquareMatrix(array $matrix): array
    {
        $rows = count($matrix);
        $cols = count($matrix[0]);
        $size = max($rows, $cols);
        
        $squareMatrix = [];
        
        for ($i = 0; $i < $size; $i++) {
            $squareMatrix[$i] = [];
            for ($j = 0; $j < $size; $j++) {
                if ($i < $rows && $j < $cols) {
                    $squareMatrix[$i][$j] = $matrix[$i][$j];
                } else {
                    $squareMatrix[$i][$j] = 0; // Valeur par défaut pour les cellules fictives
                }
            }
        }
        
        return $squareMatrix;
    }
    
    /**
     * Soustrait le minimum de chaque ligne
     * 
     * @param array $matrix Matrice d'entrée
     * @return array Matrice avec lignes réduites
     */
    private function reduceRows(array $matrix): array
    {
        $size = count($matrix);
        
        for ($i = 0; $i < $size; $i++) {
            $minVal = min($matrix[$i]);
            for ($j = 0; $j < $size; $j++) {
                $matrix[$i][$j] -= $minVal;
            }
        }
        
        return $matrix;
    }
    
    /**
     * Soustrait le minimum de chaque colonne
     * 
     * @param array $matrix Matrice d'entrée
     * @return array Matrice avec colonnes réduites
     */
    private function reduceColumns(array $matrix): array
    {
        $size = count($matrix);
        
        for ($j = 0; $j < $size; $j++) {
            $minVal = PHP_INT_MAX;
            for ($i = 0; $i < $size; $i++) {
                $minVal = min($minVal, $matrix[$i][$j]);
            }
            
            if ($minVal < PHP_INT_MAX) {
                for ($i = 0; $i < $size; $i++) {
                    $matrix[$i][$j] -= $minVal;
                }
            }
        }
        
        return $matrix;
    }
    
    /**
     * Trouve le nombre minimum de lignes pour couvrir tous les zéros
     * 
     * @param array $matrix Matrice d'entrée
     * @return array [markedRows, markedCols, minLinesCount]
     */
    private function findMinimumCover(array $matrix): array
    {
        $size = count($matrix);
        $markedRows = array_fill(0, $size, false);
        $markedCols = array_fill(0, $size, false);
        
        // Étape 1: Marquer les lignes sans zéros assignés
        $rowZeroCount = array_fill(0, $size, 0);
        $colZeroCount = array_fill(0, $size, 0);
        
        for ($i = 0; $i < $size; $i++) {
            for ($j = 0; $j < $size; $j++) {
                if ($matrix[$i][$j] == 0) {
                    $rowZeroCount[$i]++;
                    $colZeroCount[$j]++;
                }
            }
        }
        
        // Marquer les lignes sans zéros
        for ($i = 0; $i < $size; $i++) {
            if ($rowZeroCount[$i] == 0) {
                $markedRows[$i] = true;
            }
        }
        
        // Répéter jusqu'à ce qu'aucun nouveau marquage ne soit possible
        $changed = true;
        while ($changed) {
            $changed = false;
            
            // Marquer les colonnes ayant des zéros dans les lignes marquées
            for ($j = 0; $j < $size; $j++) {
                if ($markedCols[$j]) {
                    continue;
                }
                
                for ($i = 0; $i < $size; $i++) {
                    if ($markedRows[$i] && $matrix[$i][$j] == 0) {
                        $markedCols[$j] = true;
                        $changed = true;
                        break;
                    }
                }
            }
            
            // Marquer les lignes ayant des affectations dans les colonnes marquées
            for ($i = 0; $i < $size; $i++) {
                if ($markedRows[$i]) {
                    continue;
                }
                
                for ($j = 0; $j < $size; $j++) {
                    if ($markedCols[$j] && $matrix[$i][$j] == 0) {
                        $markedRows[$i] = true;
                        $changed = true;
                        break;
                    }
                }
            }
        }
        
        // Inverser les marquages de lignes pour couvrir les lignes non marquées
        for ($i = 0; $i < $size; $i++) {
            $markedRows[$i] = !$markedRows[$i];
        }
        
        // Compter le nombre minimum de lignes
        $minLines = array_sum($markedRows) + array_sum($markedCols);
        
        return [$markedRows, $markedCols, $minLines];
    }
    
    /**
     * Crée de nouveaux zéros en modifiant la matrice
     * 
     * @param array $matrix Matrice d'entrée
     * @param array $markedRows Lignes marquées
     * @param array $markedCols Colonnes marquées
     * @return array Matrice modifiée
     */
    private function createNewZeros(array $matrix, array $markedRows, array $markedCols): array
    {
        $size = count($matrix);
        
        // Trouver la valeur minimum parmi les éléments non couverts
        $minVal = PHP_INT_MAX;
        for ($i = 0; $i < $size; $i++) {
            for ($j = 0; $j < $size; $j++) {
                if (!$markedRows[$i] && !$markedCols[$j]) {
                    $minVal = min($minVal, $matrix[$i][$j]);
                }
            }
        }
        
        // Soustraire le minimum des éléments non couverts
        for ($i = 0; $i < $size; $i++) {
            for ($j = 0; $j < $size; $j++) {
                if (!$markedRows[$i] && !$markedCols[$j]) {
                    $matrix[$i][$j] -= $minVal;
                }
            }
        }
        
        // Ajouter le minimum aux éléments couverts deux fois
        for ($i = 0; $i < $size; $i++) {
            for ($j = 0; $j < $size; $j++) {
                if ($markedRows[$i] && $markedCols[$j]) {
                    $matrix[$i][$j] += $minVal;
                }
            }
        }
        
        return $matrix;
    }
    
    /**
     * Trouve l'affectation optimale à partir de la matrice réduite
     * 
     * @param array $matrix Matrice réduite
     * @return array Affectations [ligne => colonne]
     */
    private function findOptimalAssignment(array $matrix): array
    {
        $size = count($matrix);
        $assignments = array_fill(0, $size, null);
        
        // Utiliser un algorithme d'augmentation de chemin pour trouver l'affectation maximale
        for ($i = 0; $i < $size; $i++) {
            $visited = array_fill(0, $size, false);
            $this->findAugmentingPath($matrix, $i, $visited, $assignments);
        }
        
        return $assignments;
    }
    
    /**
     * Recherche un chemin augmentant pour l'algorithme d'affectation
     * 
     * @param array $matrix Matrice de coût
     * @param int $i Indice de ligne courant
     * @param array $visited Colonnes visitées
     * @param array $assignments Affectations actuelles [ligne => colonne]
     * @return bool Succès de l'augmentation
     */
    private function findAugmentingPath(array $matrix, int $i, array &$visited, array &$assignments): bool
    {
        $size = count($matrix);
        
        for ($j = 0; $j < $size; $j++) {
            if ($matrix[$i][$j] == 0 && !$visited[$j]) {
                $visited[$j] = true;
                
                // Si la colonne n'est pas affectée ou si on peut réaffecter
                if ($assignments[$j] === null || $this->findAugmentingPath($matrix, $assignments[$j], $visited, $assignments)) {
                    $assignments[$j] = $i;
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Calcule le score de compatibilité entre un étudiant et un enseignant
     * 
     * @param object $student L'étudiant
     * @param object $teacher L'enseignant
     * @param AssignmentParameters $parameters Les paramètres de l'algorithme
     * @return float Score de compatibilité (0-100)
     */
    private function calculateCompatibilityScore(
        object $student, 
        object $teacher, 
        AssignmentParameters $parameters
    ): float {
        $score = 0;
        
        // 1. Score basé sur le département (même département = meilleur score)
        if ($student->getDepartment() === $teacher->getDepartment()) {
            $score += $parameters->getDepartmentWeight();
        }
        
        // 2. Score basé sur les préférences
        if ($parameters->isPrioritizePreferences()) {
            // Vérifier si l'étudiant a une préférence pour cet enseignant
            $studentPreferenceScore = $this->calculateStudentPreferenceScore($student, $teacher);
            
            // Vérifier si l'enseignant a une préférence pour cet étudiant
            $teacherPreferenceScore = $this->calculateTeacherPreferenceScore($teacher, $student);
            
            // Moyenne des deux scores de préférence
            $preferenceScore = ($studentPreferenceScore + $teacherPreferenceScore) / 2;
            $score += $preferenceScore * $parameters->getPreferenceWeight() / 100;
        }
        
        // 3. Score basé sur l'équilibrage de charge
        if ($parameters->isBalanceWorkload()) {
            // Plus la capacité restante est grande, plus le score est élevé
            $capacityScore = ($teacher->getRemainingCapacity() / $teacher->getMaxStudents()) * 100;
            $score += $capacityScore * $parameters->getCapacityWeight() / 100;
        }
        
        return $score;
    }
    
    /**
     * Calcule le score de préférence d'un étudiant pour un enseignant
     * 
     * @param object $student L'étudiant
     * @param object $teacher L'enseignant
     * @return float Score de préférence (0-100)
     */
    private function calculateStudentPreferenceScore(object $student, object $teacher): float
    {
        // Implémentation par défaut - à améliorer avec les données réelles
        return 50.0;
    }
    
    /**
     * Calcule le score de préférence d'un enseignant pour un étudiant
     * 
     * @param object $teacher L'enseignant
     * @param object $student L'étudiant
     * @return float Score de préférence (0-100)
     */
    private function calculateTeacherPreferenceScore(object $teacher, object $student): float
    {
        // Implémentation par défaut - à améliorer avec les données réelles
        return 50.0;
    }
}