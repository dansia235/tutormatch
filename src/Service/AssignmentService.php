<?php
/**
 * Service pour la gestion des affectations
 */
namespace App\Service;

use App\Algorithm\AssignmentAlgorithmInterface;
use App\DTO\AssignmentParameters;
use App\DTO\AssignmentResult;
use App\Entity\Assignment;
use App\Entity\Internship;
use App\Entity\Student;
use App\Entity\Teacher;
use App\Repository\AssignmentRepository;
use App\Repository\InternshipRepository;
use App\Repository\StudentRepository;
use App\Repository\TeacherRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class AssignmentService
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    
    /**
     * @var AssignmentRepository
     */
    private $assignmentRepository;
    
    /**
     * @var StudentRepository
     */
    private $studentRepository;
    
    /**
     * @var TeacherRepository
     */
    private $teacherRepository;
    
    /**
     * @var InternshipRepository
     */
    private $internshipRepository;
    
    /**
     * Constructeur
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        AssignmentRepository $assignmentRepository,
        StudentRepository $studentRepository,
        TeacherRepository $teacherRepository,
        InternshipRepository $internshipRepository
    ) {
        $this->entityManager = $entityManager;
        $this->assignmentRepository = $assignmentRepository;
        $this->studentRepository = $studentRepository;
        $this->teacherRepository = $teacherRepository;
        $this->internshipRepository = $internshipRepository;
    }
    
    /**
     * Génère des affectations en utilisant l'algorithme spécifié
     * 
     * @param AssignmentAlgorithmInterface $algorithm L'algorithme à utiliser
     * @param array $parameters Paramètres d'exécution optionnels
     * @return array Résultat de la génération
     */
    public function generateAssignments(AssignmentAlgorithmInterface $algorithm, array $parameters = []): array
    {
        // Créer les paramètres d'algorithme
        $algorithmParameters = new AssignmentParameters();
        
        // Configurer les paramètres depuis le tableau
        if (isset($parameters['departmentWeight'])) {
            $algorithmParameters->setDepartmentWeight($parameters['departmentWeight']);
        }
        
        if (isset($parameters['preferenceWeight'])) {
            $algorithmParameters->setPreferenceWeight($parameters['preferenceWeight']);
        }
        
        if (isset($parameters['capacityWeight'])) {
            $algorithmParameters->setCapacityWeight($parameters['capacityWeight']);
        }
        
        if (isset($parameters['allowCrossDepartment'])) {
            $algorithmParameters->setAllowCrossDepartment($parameters['allowCrossDepartment']);
        }
        
        if (isset($parameters['prioritizePreferences'])) {
            $algorithmParameters->setPrioritizePreferences($parameters['prioritizePreferences']);
        }
        
        if (isset($parameters['balanceWorkload'])) {
            $algorithmParameters->setBalanceWorkload($parameters['balanceWorkload']);
        }
        
        if (isset($parameters['name'])) {
            $algorithmParameters->setName($parameters['name']);
        }
        
        if (isset($parameters['description'])) {
            $algorithmParameters->setDescription($parameters['description']);
        }
        
        // Récupérer les étudiants sans affectation
        $students = $this->getUnassignedStudents();
        
        if (empty($students)) {
            throw new Exception("Aucun étudiant sans affectation n'a été trouvé");
        }
        
        // Récupérer les enseignants disponibles
        $teachers = $this->getAvailableTeachers();
        
        if (empty($teachers)) {
            throw new Exception("Aucun enseignant disponible n'a été trouvé");
        }
        
        // Récupérer les stages disponibles
        $internships = $this->getAvailableInternships();
        
        if (empty($internships)) {
            throw new Exception("Aucun stage disponible n'a été trouvé");
        }
        
        // Exécuter l'algorithme
        $result = $algorithm->execute($students, $teachers, $internships, $algorithmParameters);
        
        if (!$result->isSuccessful()) {
            throw new Exception("Erreur lors de l'exécution de l'algorithme: " . $result->getErrorMessage());
        }
        
        // Enregistrer les affectations générées
        $savedAssignments = $this->saveAssignments($result);
        
        // Enregistrer l'exécution de l'algorithme
        $this->saveAlgorithmExecution($algorithm, $algorithmParameters, $result);
        
        return [
            'assignments' => $savedAssignments,
            'metrics' => [
                'totalStudents' => count($students),
                'totalAssigned' => count($result->getAssignments()),
                'totalUnassigned' => count($result->getUnassignedStudents()),
                'averageScore' => $result->getAverageScore(),
                'executionTime' => $result->getExecutionTime()
            ]
        ];
    }
    
    /**
     * Récupère les étudiants sans affectation
     * 
     * @return array Liste des étudiants sans affectation
     */
    private function getUnassignedStudents(): array
    {
        // Récupérer tous les étudiants actifs
        $allStudents = $this->studentRepository->findBy(['status' => 'active']);
        
        // Filtrer pour ne garder que ceux sans affectation
        $unassignedStudents = [];
        
        foreach ($allStudents as $student) {
            $existingAssignment = $this->assignmentRepository->findOneBy([
                'student' => $student->getId(),
                'status' => ['pending', 'confirmed', 'completed']
            ]);
            
            if (!$existingAssignment) {
                $unassignedStudents[] = $student;
            }
        }
        
        return $unassignedStudents;
    }
    
    /**
     * Récupère les enseignants avec de la capacité disponible
     * 
     * @return array Liste des enseignants disponibles
     */
    private function getAvailableTeachers(): array
    {
        // Récupérer tous les enseignants actifs
        $allTeachers = $this->teacherRepository->findBy(['isActive' => true]);
        
        // Filtrer pour ne garder que ceux avec de la capacité
        $availableTeachers = [];
        
        foreach ($allTeachers as $teacher) {
            $currentAssignments = $this->assignmentRepository->countByTeacher($teacher->getId());
            
            if ($currentAssignments < $teacher->getMaxStudents()) {
                $teacher->setRemainingCapacity($teacher->getMaxStudents() - $currentAssignments);
                $availableTeachers[] = $teacher;
            }
        }
        
        return $availableTeachers;
    }
    
    /**
     * Récupère les stages disponibles
     * 
     * @return array Liste des stages disponibles
     */
    private function getAvailableInternships(): array
    {
        return $this->internshipRepository->findBy(['status' => 'available']);
    }
    
    /**
     * Enregistre les affectations générées en base de données
     * 
     * @param AssignmentResult $result Résultat de l'algorithme
     * @return array Liste des affectations enregistrées
     */
    private function saveAssignments(AssignmentResult $result): array
    {
        $savedAssignments = [];
        
        // Commencer une transaction
        $this->entityManager->beginTransaction();
        
        try {
            $usedInternships = [];
            $availableInternships = $this->getAvailableInternships();
            
            foreach ($result->getAssignments() as $assignmentData) {
                $student = $this->studentRepository->find($assignmentData['student_id']);
                $teacher = $this->teacherRepository->find($assignmentData['teacher_id']);
                
                // Trouver un stage disponible qui n'a pas encore été utilisé
                $internship = null;
                
                foreach ($availableInternships as $availableInternship) {
                    if (!in_array($availableInternship->getId(), $usedInternships)) {
                        $internship = $availableInternship;
                        $usedInternships[] = $internship->getId();
                        break;
                    }
                }
                
                if (!$internship) {
                    // Plus de stages disponibles, on arrête
                    break;
                }
                
                // Créer une nouvelle affectation
                $assignment = new Assignment();
                $assignment->setStudent($student);
                $assignment->setTeacher($teacher);
                $assignment->setInternship($internship);
                $assignment->setStatus('pending');
                $assignment->setCompatibilityScore($assignmentData['compatibility_score']);
                $assignment->setNotes("Affectation générée automatiquement par algorithme");
                $assignment->setCreatedAt(new \DateTimeImmutable());
                
                // Persister l'affectation
                $this->entityManager->persist($assignment);
                
                // Mettre à jour le statut du stage
                $internship->setStatus('assigned');
                $this->entityManager->persist($internship);
                
                $savedAssignments[] = $assignment;
            }
            
            // Enregistrer les changements
            $this->entityManager->flush();
            
            // Valider la transaction
            $this->entityManager->commit();
            
            return $savedAssignments;
        } catch (Exception $e) {
            // Annuler la transaction en cas d'erreur
            $this->entityManager->rollback();
            
            throw $e;
        }
    }
    
    /**
     * Enregistre les informations d'exécution de l'algorithme
     * 
     * @param AssignmentAlgorithmInterface $algorithm Algorithme utilisé
     * @param AssignmentParameters $parameters Paramètres utilisés
     * @param AssignmentResult $result Résultat obtenu
     */
    private function saveAlgorithmExecution(
        AssignmentAlgorithmInterface $algorithm,
        AssignmentParameters $parameters,
        AssignmentResult $result
    ): void {
        // Cette méthode peut être implémentée pour sauvegarder l'historique des exécutions
        // Pour l'instant, on ne fait rien
    }
    
    /**
     * Récupère les affectations récentes
     * 
     * @param int $limit Nombre maximum d'affectations à récupérer
     * @return array Liste des affectations récentes
     */
    public function getRecentAssignments(int $limit = 10): array
    {
        return $this->assignmentRepository->findBy(
            [],
            ['createdAt' => 'DESC'],
            $limit
        );
    }
    
    /**
     * Récupère les affectations d'un enseignant
     * 
     * @param Teacher $teacher Enseignant
     * @return array Liste des affectations de l'enseignant
     */
    public function getAssignmentsByTeacher(Teacher $teacher): array
    {
        return $this->assignmentRepository->findBy([
            'teacher' => $teacher->getId()
        ]);
    }
    
    /**
     * Récupère l'affectation d'un étudiant
     * 
     * @param Student $student Étudiant
     * @return Assignment|null L'affectation de l'étudiant ou null
     */
    public function getAssignmentByStudent(Student $student): ?Assignment
    {
        return $this->assignmentRepository->findOneBy([
            'student' => $student->getId()
        ]);
    }
}