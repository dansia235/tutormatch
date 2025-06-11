<?php
/**
 * Paramètres pour les algorithmes d'affectation
 */
namespace App\DTO;

class AssignmentParameters
{
    /**
     * Poids pour la correspondance de département
     * @var int
     */
    private int $departmentWeight = 50;
    
    /**
     * Poids pour les préférences (étudiant et enseignant)
     * @var int
     */
    private int $preferenceWeight = 30;
    
    /**
     * Poids pour l'équilibrage de charge des enseignants
     * @var int
     */
    private int $capacityWeight = 20;
    
    /**
     * Autoriser les affectations entre départements différents
     * @var bool
     */
    private bool $allowCrossDepartment = false;
    
    /**
     * Prioriser les préférences des étudiants et enseignants
     * @var bool
     */
    private bool $prioritizePreferences = true;
    
    /**
     * Équilibrer la charge de travail entre enseignants
     * @var bool
     */
    private bool $balanceWorkload = true;
    
    /**
     * Nom de l'exécution de l'algorithme
     * @var string|null
     */
    private ?string $name = null;
    
    /**
     * Description de l'exécution
     * @var string|null
     */
    private ?string $description = null;
    
    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->name = 'Exécution du ' . (new \DateTime())->format('Y-m-d H:i:s');
    }
    
    /**
     * @return int
     */
    public function getDepartmentWeight(): int
    {
        return $this->departmentWeight;
    }
    
    /**
     * @param int $departmentWeight
     * @return self
     */
    public function setDepartmentWeight(int $departmentWeight): self
    {
        $this->departmentWeight = $departmentWeight;
        return $this;
    }
    
    /**
     * @return int
     */
    public function getPreferenceWeight(): int
    {
        return $this->preferenceWeight;
    }
    
    /**
     * @param int $preferenceWeight
     * @return self
     */
    public function setPreferenceWeight(int $preferenceWeight): self
    {
        $this->preferenceWeight = $preferenceWeight;
        return $this;
    }
    
    /**
     * @return int
     */
    public function getCapacityWeight(): int
    {
        return $this->capacityWeight;
    }
    
    /**
     * @param int $capacityWeight
     * @return self
     */
    public function setCapacityWeight(int $capacityWeight): self
    {
        $this->capacityWeight = $capacityWeight;
        return $this;
    }
    
    /**
     * @return bool
     */
    public function isAllowCrossDepartment(): bool
    {
        return $this->allowCrossDepartment;
    }
    
    /**
     * @param bool $allowCrossDepartment
     * @return self
     */
    public function setAllowCrossDepartment(bool $allowCrossDepartment): self
    {
        $this->allowCrossDepartment = $allowCrossDepartment;
        return $this;
    }
    
    /**
     * @return bool
     */
    public function isPrioritizePreferences(): bool
    {
        return $this->prioritizePreferences;
    }
    
    /**
     * @param bool $prioritizePreferences
     * @return self
     */
    public function setPrioritizePreferences(bool $prioritizePreferences): self
    {
        $this->prioritizePreferences = $prioritizePreferences;
        return $this;
    }
    
    /**
     * @return bool
     */
    public function isBalanceWorkload(): bool
    {
        return $this->balanceWorkload;
    }
    
    /**
     * @param bool $balanceWorkload
     * @return self
     */
    public function setBalanceWorkload(bool $balanceWorkload): self
    {
        $this->balanceWorkload = $balanceWorkload;
        return $this;
    }
    
    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }
    
    /**
     * @param string|null $name
     * @return self
     */
    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }
    
    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }
    
    /**
     * @param string|null $description
     * @return self
     */
    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }
}