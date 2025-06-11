<?php
/**
 * Interface pour tous les algorithmes d'affectation
 */
namespace App\Algorithm;

use App\DTO\AssignmentParameters;
use App\DTO\AssignmentResult;

interface AssignmentAlgorithmInterface
{
    /**
     * Exécute l'algorithme d'affectation
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
    ): AssignmentResult;
}