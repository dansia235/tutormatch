<?php
/**
 * Afficher un étudiant
 * GET /api/students/{id}
 */

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Méthode non autorisée', 405);
}

// Récupérer l'ID de l'étudiant depuis l'URL
$studentId = isset($urlParts[2]) ? (int)$urlParts[2] : 0;

if ($studentId <= 0) {
    sendError('ID étudiant invalide', 400);
}

// Initialiser le modèle étudiant
$studentModel = new Student($db);

// Récupérer l'étudiant
$student = $studentModel->getById($studentId);

if (!$student) {
    sendError('Étudiant non trouvé', 404);
}

// Vérifier les droits d'accès
// Les administrateurs et coordinateurs peuvent voir tous les étudiants
// Les tuteurs peuvent voir uniquement leurs étudiants assignés
// Les étudiants peuvent voir uniquement leur propre profil
if (!hasRole(['admin', 'coordinator'])) {
    $userModel = new User($db);
    $user = $userModel->getById($student['user_id']);
    
    if (hasRole('teacher')) {
        $teacherModel = new Teacher($db);
        $teacher = $teacherModel->getByUserId($_SESSION['user_id']);
        
        if (!$teacher) {
            sendError('Profil tuteur non trouvé', 404);
        }
        
        $assignmentModel = new Assignment($db);
        $assignment = $assignmentModel->getByStudentAndTeacherId($studentId, $teacher['id']);
        
        if (!$assignment) {
            sendError('Accès refusé: cet étudiant n\'est pas assigné à votre supervision', 403);
        }
    } elseif (hasRole('student')) {
        // Un étudiant ne peut voir que son propre profil
        if ($user['id'] !== $_SESSION['user_id']) {
            sendError('Accès refusé', 403);
        }
    } else {
        sendError('Accès refusé', 403);
    }
}

// Récupérer les données utilisateur associées
$userModel = new User($db);
$user = $userModel->getById($student['user_id']);

if (!$user) {
    sendError('Utilisateur associé non trouvé', 404);
}

// Masquer le mot de passe
unset($user['password']);

// Récupérer le stage actif si disponible
$assignmentModel = new Assignment($db);
$activeAssignment = $assignmentModel->getActiveByStudentId($studentId);

// Récupérer les documents soumis
$documentModel = new Document($db);
$documents = $documentModel->getByStudentId($studentId);

// Récupérer les préférences de stage
$preferences = [];
if (hasRole(['admin', 'coordinator']) || (hasRole('student') && $user['id'] === $_SESSION['user_id'])) {
    $preferenceModel = new StudentPreference($db);
    $preferences = $preferenceModel->getByStudentId($studentId);
}

// Fusionner les données
$studentData = [
    'id' => $student['id'],
    'user' => $user,
    'student_number' => $student['student_number'],
    'program' => $student['program'],
    'level' => $student['level'],
    'average_grade' => $student['average_grade'],
    'graduation_year' => $student['graduation_year'],
    'skills' => $student['skills'],
    'cv_path' => $student['cv_path'],
    'status' => $student['status']
];

// Ajouter le stage actif s'il existe
if ($activeAssignment) {
    $internshipModel = new Internship($db);
    $internship = $internshipModel->getById($activeAssignment['internship_id']);
    
    $teacherModel = new Teacher($db);
    $teacher = $teacherModel->getById($activeAssignment['teacher_id']);
    $teacherUser = $userModel->getById($teacher['user_id']);
    
    // Masquer le mot de passe du tuteur
    unset($teacherUser['password']);
    
    $studentData['active_assignment'] = [
        'id' => $activeAssignment['id'],
        'status' => $activeAssignment['status'],
        'assignment_date' => $activeAssignment['assignment_date'],
        'internship' => $internship,
        'teacher' => array_merge($teacher, ['user' => $teacherUser])
    ];
}

// Ajouter les documents si disponibles
if (!empty($documents)) {
    $studentData['documents'] = $documents;
}

// Ajouter les préférences si disponibles
if (!empty($preferences)) {
    $studentData['preferences'] = $preferences;
}

// Envoyer la réponse
sendJsonResponse([
    'data' => $studentData
]);