<?php
/**
 * Afficher un stage
 * GET /api/internships/{id}
 */

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Méthode non autorisée', 405);
}

// Récupérer l'ID du stage depuis l'URL
$internshipId = isset($urlParts[2]) ? (int)$urlParts[2] : 0;

if ($internshipId <= 0) {
    sendError('ID stage invalide', 400);
}

// Initialiser le modèle stage
$internshipModel = new Internship($db);

// Récupérer le stage
$internship = $internshipModel->getById($internshipId);

if (!$internship) {
    sendError('Stage non trouvé', 404);
}

// Récupérer les informations de l'entreprise
$companyModel = new Company($db);
$company = $companyModel->getById($internship['company_id']);

// Récupérer les compétences requises
$skillModel = new InternshipSkill($db);
$skills = $skillModel->getByInternshipId($internshipId);

// Formater les dates
$internship['start_date'] = date('Y-m-d', strtotime($internship['start_date']));
$internship['end_date'] = date('Y-m-d', strtotime($internship['end_date']));
$internship['created_at'] = date('Y-m-d H:i:s', strtotime($internship['created_at']));
$internship['updated_at'] = date('Y-m-d H:i:s', strtotime($internship['updated_at']));

// Préparer les données du stage
$internshipData = [
    'id' => $internship['id'],
    'title' => $internship['title'],
    'description' => $internship['description'],
    'requirements' => $internship['requirements'],
    'start_date' => $internship['start_date'],
    'end_date' => $internship['end_date'],
    'location' => $internship['location'],
    'work_mode' => $internship['work_mode'],
    'compensation' => $internship['compensation'],
    'domain' => $internship['domain'],
    'status' => $internship['status'],
    'created_at' => $internship['created_at'],
    'updated_at' => $internship['updated_at'],
    'company' => $company,
    'skills' => $skills
];

// Vérifier si le stage est assigné
if ($internship['status'] === 'assigned' || $internship['status'] === 'completed') {
    $assignmentModel = new Assignment($db);
    $assignment = $assignmentModel->getByInternshipId($internshipId);
    
    if ($assignment) {
        // Récupérer les détails de l'étudiant
        $studentModel = new Student($db);
        $student = $studentModel->getById($assignment['student_id']);
        
        // Récupérer les détails du tuteur
        $teacherModel = new Teacher($db);
        $teacher = $teacherModel->getById($assignment['teacher_id']);
        
        // Récupérer les utilisateurs associés
        $userModel = new User($db);
        $studentUser = $userModel->getById($student['user_id']);
        $teacherUser = $userModel->getById($teacher['user_id']);
        
        // Masquer les mots de passe
        unset($studentUser['password']);
        unset($teacherUser['password']);
        
        // Ajouter les informations d'affectation
        $internshipData['assignment'] = [
            'id' => $assignment['id'],
            'status' => $assignment['status'],
            'assignment_date' => $assignment['assignment_date'],
            'confirmation_date' => $assignment['confirmation_date'],
            'student' => array_merge($student, ['user' => $studentUser]),
            'teacher' => array_merge($teacher, ['user' => $teacherUser])
        ];
        
        // Récupérer les documents liés à l'affectation
        $documentModel = new Document($db);
        $documents = $documentModel->getByAssignmentId($assignment['id']);
        
        if (!empty($documents)) {
            $internshipData['assignment']['documents'] = $documents;
        }
        
        // Récupérer les évaluations liées à l'affectation
        $evaluationModel = new Evaluation($db);
        $evaluations = $evaluationModel->getByAssignmentId($assignment['id']);
        
        if (!empty($evaluations)) {
            $internshipData['assignment']['evaluations'] = $evaluations;
        }
    }
}

// Envoyer la réponse
sendJsonResponse([
    'data' => $internshipData
]);