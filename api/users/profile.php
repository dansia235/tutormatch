<?php
/**
 * Récupérer le profil de l'utilisateur connecté
 * GET /api/users/profile
 */

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Méthode non autorisée', 405);
}

// Récupérer l'ID de l'utilisateur à partir du token JWT
// Dans un système réel, cette information serait extraite du token
$userId = $_SESSION['user_id'];

// Initialiser le modèle utilisateur
$userModel = new User($db);

// Récupérer les données de l'utilisateur
$user = $userModel->getById($userId);

if (!$user) {
    sendError('Utilisateur non trouvé', 404);
}

// Masquer le mot de passe
unset($user['password']);

// Récupérer les informations spécifiques au rôle
$roleSpecificInfo = [];

if ($user['role'] === 'student') {
    $studentModel = new Student($db);
    $studentInfo = $studentModel->getByUserId($userId);
    
    if ($studentInfo) {
        $roleSpecificInfo = [
            'student_number' => $studentInfo['student_number'],
            'program' => $studentInfo['program'],
            'level' => $studentInfo['level'],
            'average_grade' => $studentInfo['average_grade'],
            'graduation_year' => $studentInfo['graduation_year'],
            'skills' => $studentInfo['skills']
        ];
        
        // Récupérer le stage actif si disponible
        $assignmentModel = new Assignment($db);
        $activeAssignment = $assignmentModel->getActiveByStudentId($studentInfo['id']);
        
        if ($activeAssignment) {
            $roleSpecificInfo['active_assignment'] = [
                'id' => $activeAssignment['id'],
                'internship_id' => $activeAssignment['internship_id'],
                'teacher_id' => $activeAssignment['teacher_id'],
                'status' => $activeAssignment['status'],
                'assignment_date' => $activeAssignment['assignment_date']
            ];
        }
    }
} elseif ($user['role'] === 'teacher') {
    $teacherModel = new Teacher($db);
    $teacherInfo = $teacherModel->getByUserId($userId);
    
    if ($teacherInfo) {
        $roleSpecificInfo = [
            'title' => $teacherInfo['title'],
            'specialty' => $teacherInfo['specialty'],
            'office_location' => $teacherInfo['office_location'],
            'max_students' => $teacherInfo['max_students'],
            'available' => $teacherInfo['available'],
            'expertise' => $teacherInfo['expertise']
        ];
        
        // Compter le nombre d'étudiants assignés
        $assignmentModel = new Assignment($db);
        $assignedStudentsCount = $assignmentModel->countByTeacherId($teacherInfo['id']);
        
        $roleSpecificInfo['assigned_students_count'] = $assignedStudentsCount;
    }
}

// Fusionner les informations générales et spécifiques au rôle
$profile = array_merge($user, ['role_info' => $roleSpecificInfo]);

// Envoyer la réponse
sendJsonResponse([
    'data' => $profile
]);