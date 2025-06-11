<?php
/**
 * Afficher un tuteur
 * GET /api/teachers/{id}
 */

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Méthode non autorisée', 405);
}

// Récupérer l'ID du tuteur depuis l'URL
$teacherId = isset($urlParts[2]) ? (int)$urlParts[2] : 0;

if ($teacherId <= 0) {
    sendError('ID tuteur invalide', 400);
}

// Initialiser le modèle tuteur
$teacherModel = new Teacher($db);

// Récupérer le tuteur
$teacher = $teacherModel->getById($teacherId);

if (!$teacher) {
    sendError('Tuteur non trouvé', 404);
}

// Récupérer les données utilisateur associées
$userModel = new User($db);
$user = $userModel->getById($teacher['user_id']);

if (!$user) {
    sendError('Utilisateur associé non trouvé', 404);
}

// Masquer le mot de passe
unset($user['password']);

// Récupérer le nombre d'étudiants assignés
$assignmentModel = new Assignment($db);
$assignedStudentsCount = $assignmentModel->countByTeacherId($teacherId);

// Récupérer les préférences du tuteur (accessible uniquement par admin, coordinateur ou le tuteur lui-même)
$preferences = [];
if (hasRole(['admin', 'coordinator']) || (hasRole('teacher') && $user['id'] === $_SESSION['user_id'])) {
    $preferenceModel = new TeacherPreference($db);
    $preferences = $preferenceModel->getByTeacherId($teacherId);
}

// Fusionner les données
$teacherData = [
    'id' => $teacher['id'],
    'user' => $user,
    'title' => $teacher['title'],
    'specialty' => $teacher['specialty'],
    'office_location' => $teacher['office_location'],
    'max_students' => $teacher['max_students'],
    'available' => $teacher['available'],
    'expertise' => $teacher['expertise'],
    'assigned_students_count' => $assignedStudentsCount,
    'available_slots' => max(0, $teacher['max_students'] - $assignedStudentsCount)
];

// Ajouter les préférences si disponibles
if (!empty($preferences)) {
    $teacherData['preferences'] = $preferences;
}

// Ajouter la liste des étudiants assignés (uniquement pour admin, coordinateur ou le tuteur lui-même)
if (hasRole(['admin', 'coordinator']) || (hasRole('teacher') && $user['id'] === $_SESSION['user_id'])) {
    $assignments = $assignmentModel->getByTeacherId($teacherId);
    
    $assignedStudents = [];
    foreach ($assignments as $assignment) {
        $studentModel = new Student($db);
        $student = $studentModel->getById($assignment['student_id']);
        
        if ($student) {
            $studentUser = $userModel->getById($student['user_id']);
            unset($studentUser['password']);
            
            $assignedStudents[] = [
                'assignment_id' => $assignment['id'],
                'assignment_status' => $assignment['status'],
                'assignment_date' => $assignment['assignment_date'],
                'student' => array_merge($student, ['user' => $studentUser])
            ];
        }
    }
    
    $teacherData['assigned_students'] = $assignedStudents;
}

// Envoyer la réponse
sendJsonResponse([
    'data' => $teacherData
]);