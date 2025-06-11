<?php
/**
 * Implémentation de l'algorithme glouton (greedy) pour l'affectation étudiant-enseignant
 */
namespace App\Algorithm;

use App\DTO\AssignmentParameters;
use App\DTO\AssignmentResult;

class GreedyAlgorithm implements AssignmentAlgorithmInterface
{
    /**
     * Exécute l'algorithme d'affectation glouton
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
            
            // Tableau pour stocker toutes les combinaisons possibles avec scores
            $allPossibleAssignments = [];
            
            // Calculer les scores de compatibilité pour toutes les combinaisons possibles
            foreach ($students as $student) {
                foreach ($teachers as $teacher) {
                    // Vérifier si l'enseignant a encore de la capacité
                    $remainingCapacity = $teacher->getRemainingCapacity();
                    if ($remainingCapacity === null || $remainingCapacity <= 0) {
                        continue;
                    }
                    
                    // Vérifier la contrainte de département si activée
                    if (!$parameters->isAllowCrossDepartment() && 
                        $student->getDepartment() !== $teacher->getDepartment()) {
                        continue;
                    }
                    
                    // Calculer le score de compatibilité
                    $score = $this->calculateCompatibilityScore($student, $teacher, $parameters);
                    
                    // Ajouter à la liste des affectations possibles
                    $allPossibleAssignments[] = [
                        'student_id' => $student->getId(),
                        'teacher_id' => $teacher->getId(),
                        'compatibility_score' => $score,
                        'student' => $student,
                        'teacher' => $teacher
                    ];
                }
            }
            
            // Trier les combinaisons par score de compatibilité (ordre décroissant)
            usort($allPossibleAssignments, function($a, $b) {
                return $b['compatibility_score'] <=> $a['compatibility_score'];
            });
            
            // Ensembles pour suivre les étudiants et enseignants déjà affectés
            $assignedStudents = [];
            $teacherCapacity = [];
            
            // Initialiser la capacité restante pour chaque enseignant
            foreach ($teachers as $teacher) {
                $teacherCapacity[$teacher->getId()] = $teacher->getRemainingCapacity();
            }
            
            // Effectuer les affectations selon l'algorithme glouton
            foreach ($allPossibleAssignments as $assignment) {
                $studentId = $assignment['student_id'];
                $teacherId = $assignment['teacher_id'];
                
                // Si l'étudiant est déjà affecté, passer au suivant
                if (isset($assignedStudents[$studentId])) {
                    continue;
                }
                
                // Si l'enseignant n'a plus de capacité, passer au suivant
                if ($teacherCapacity[$teacherId] <= 0) {
                    continue;
                }
                
                // Créer l'affectation
                $result->addAssignment([
                    'student_id' => $studentId,
                    'teacher_id' => $teacherId,
                    'compatibility_score' => $assignment['compatibility_score']
                ]);
                
                // Marquer l'étudiant comme affecté
                $assignedStudents[$studentId] = true;
                
                // Diminuer la capacité de l'enseignant
                $teacherCapacity[$teacherId]--;
            }
            
            // Ajouter les étudiants non affectés au résultat
            foreach ($students as $student) {
                if (!isset($assignedStudents[$student->getId()])) {
                    $result->addUnassignedStudent($student);
                }
            }
            
            // Calculer le score moyen des affectations
            $result->calculateAverageScore();
            
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
        // Implémentation par défaut - peut être améliorée avec les données réelles de préférence
        // Par exemple, si les étudiants peuvent classer leurs enseignants préférés
        
        // Pour l'instant, on retourne une valeur par défaut
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
        // Implémentation par défaut - peut être améliorée avec les données réelles de préférence
        // Par exemple, basée sur les domaines d'expertise de l'enseignant et les compétences de l'étudiant
        
        // Pour l'instant, on retourne une valeur par défaut
        return 50.0;
    }
}