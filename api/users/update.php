<?php
/**
 * Mettre à jour un utilisateur
 * PUT /api/users/{id}
 */

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    sendError('Méthode non autorisée', 405);
}

// Récupérer l'ID de l'utilisateur depuis l'URL
$userId = isset($urlParts[2]) ? (int)$urlParts[2] : 0;

if ($userId <= 0) {
    sendError('ID utilisateur invalide', 400);
}

// Vérifier les droits d'accès (admin ou l'utilisateur lui-même)
if (!hasRole('admin') && $_SESSION['user_id'] != $userId) {
    sendError('Accès refusé', 403);
}

// Récupérer les données de la requête
$data = json_decode(file_get_contents('php://input'), true);

if (empty($data)) {
    sendError('Aucune donnée fournie', 400);
}

// Initialiser le modèle utilisateur
$userModel = new User($db);

// Récupérer l'utilisateur existant
$existingUser = $userModel->getById($userId);

if (!$existingUser) {
    sendError('Utilisateur non trouvé', 404);
}

// Préparer les données à mettre à jour
$updateData = [];

// Mettre à jour uniquement les champs fournis
if (isset($data['email'])) {
    // Vérifier si l'email existe déjà (sauf pour l'utilisateur actuel)
    if ($data['email'] !== $existingUser['email'] && $userModel->emailExists($data['email'])) {
        sendError('Cet email est déjà utilisé', 409);
    }
    $updateData['email'] = $data['email'];
}

if (isset($data['first_name'])) {
    $updateData['first_name'] = $data['first_name'];
}

if (isset($data['last_name'])) {
    $updateData['last_name'] = $data['last_name'];
}

if (isset($data['department'])) {
    $updateData['department'] = $data['department'];
}

if (isset($data['profile_image'])) {
    $updateData['profile_image'] = $data['profile_image'];
}

// Seul l'admin peut modifier le rôle
if (isset($data['role']) && hasRole('admin')) {
    $validRoles = ['admin', 'coordinator', 'teacher', 'student'];
    if (!in_array($data['role'], $validRoles)) {
        sendError('Rôle invalide', 400);
    }
    $updateData['role'] = $data['role'];
}

// Mot de passe (nécessite une confirmation)
if (isset($data['password']) && isset($data['password_confirm'])) {
    if ($data['password'] !== $data['password_confirm']) {
        sendError('Les mots de passe ne correspondent pas', 400);
    }
    $updateData['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
}

// Si aucune donnée à mettre à jour
if (empty($updateData)) {
    sendError('Aucune donnée valide à mettre à jour', 400);
}

// Mettre à jour l'utilisateur
$success = $userModel->update($userId, $updateData);

if (!$success) {
    sendError('Erreur lors de la mise à jour de l\'utilisateur', 500);
}

// Mettre à jour les informations spécifiques au rôle
if ($existingUser['role'] === 'student' && isset($data['student_info'])) {
    $studentModel = new Student($db);
    $student = $studentModel->getByUserId($userId);
    
    if ($student) {
        $studentModel->update($student['id'], [
            'program' => $data['student_info']['program'] ?? $student['program'],
            'level' => $data['student_info']['level'] ?? $student['level'],
            'average_grade' => $data['student_info']['average_grade'] ?? $student['average_grade'],
            'graduation_year' => $data['student_info']['graduation_year'] ?? $student['graduation_year'],
            'skills' => $data['student_info']['skills'] ?? $student['skills']
        ]);
    }
}

if ($existingUser['role'] === 'teacher' && isset($data['teacher_info'])) {
    $teacherModel = new Teacher($db);
    $teacher = $teacherModel->getByUserId($userId);
    
    if ($teacher) {
        $teacherModel->update($teacher['id'], [
            'title' => $data['teacher_info']['title'] ?? $teacher['title'],
            'specialty' => $data['teacher_info']['specialty'] ?? $teacher['specialty'],
            'office_location' => $data['teacher_info']['office_location'] ?? $teacher['office_location'],
            'max_students' => $data['teacher_info']['max_students'] ?? $teacher['max_students'],
            'expertise' => $data['teacher_info']['expertise'] ?? $teacher['expertise']
        ]);
    }
}

// Récupérer l'utilisateur mis à jour
$updatedUser = $userModel->getById($userId);
unset($updatedUser['password']);

// Envoyer la réponse
sendJsonResponse([
    'success' => true,
    'message' => 'Utilisateur mis à jour avec succès',
    'data' => $updatedUser
]);