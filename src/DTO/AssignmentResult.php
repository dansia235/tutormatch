<?php
/**
 * Résultat des algorithmes d'affectation
 */
namespace App\DTO;

class AssignmentResult
{
    /**
     * Liste des affectations générées
     * @var array
     */
    private array $assignments = [];
    
    /**
     * Liste des étudiants non affectés
     * @var array
     */
    private array $unassignedStudents = [];
    
    /**
     * Score moyen des affectations
     * @var float
     */
    private float $averageScore = 0.0;
    
    /**
     * Temps d'exécution de l'algorithme (en secondes)
     * @var float
     */
    private float $executionTime = 0.0;
    
    /**
     * Indique si l'exécution de l'algorithme a réussi
     * @var bool
     */
    private bool $successful = false;
    
    /**
     * Message d'erreur en cas d'échec
     * @var string|null
     */
    private ?string $errorMessage = null;
    
    /**
     * Ajoute une affectation au résultat
     * 
     * @param array $assignment Données de l'affectation
     * @return self
     */
    public function addAssignment(array $assignment): self
    {
        $this->assignments[] = $assignment;
        return $this;
    }
    
    /**
     * Ajoute un étudiant non affecté
     * 
     * @param object $student L'étudiant non affecté
     * @return self
     */
    public function addUnassignedStudent(object $student): self
    {
        $this->unassignedStudents[] = $student;
        return $this;
    }
    
    /**
     * Calcule le score moyen des affectations
     * 
     * @return float Le score moyen
     */
    public function calculateAverageScore(): float
    {
        if (empty($this->assignments)) {
            $this->averageScore = 0.0;
            return $this->averageScore;
        }
        
        $totalScore = 0.0;
        foreach ($this->assignments as $assignment) {
            if (isset($assignment['compatibility_score'])) {
                $totalScore += $assignment['compatibility_score'];
            }
        }
        
        $this->averageScore = $totalScore / count($this->assignments);
        return $this->averageScore;
    }
    
    /**
     * @return array
     */
    public function getAssignments(): array
    {
        return $this->assignments;
    }
    
    /**
     * @return array
     */
    public function getUnassignedStudents(): array
    {
        return $this->unassignedStudents;
    }
    
    /**
     * @return float
     */
    public function getAverageScore(): float
    {
        return $this->averageScore;
    }
    
    /**
     * @param float $averageScore
     * @return self
     */
    public function setAverageScore(float $averageScore): self
    {
        $this->averageScore = $averageScore;
        return $this;
    }
    
    /**
     * @return float
     */
    public function getExecutionTime(): float
    {
        return $this->executionTime;
    }
    
    /**
     * @param float $executionTime
     * @return self
     */
    public function setExecutionTime(float $executionTime): self
    {
        $this->executionTime = $executionTime;
        return $this;
    }
    
    /**
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return $this->successful;
    }
    
    /**
     * @param bool $successful
     * @return self
     */
    public function setSuccessful(bool $successful): self
    {
        $this->successful = $successful;
        return $this;
    }
    
    /**
     * @return string|null
     */
    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }
    
    /**
     * @param string|null $errorMessage
     * @return self
     */
    public function setErrorMessage(?string $errorMessage): self
    {
        $this->errorMessage = $errorMessage;
        return $this;
    }
}