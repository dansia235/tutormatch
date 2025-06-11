<?php
/**
 * Créer un nouvel utilisateur
 * POST /api/users
 */

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Méthode non autorisée', 405);
}

// Vérifier les droits d'accès (seul l'administrateur peut créer des utilisateurs)
if (!hasRole('admin')) {
    sendError('Accès refusé', 403);
}

// Récupérer les données de la requête
$data = json_decode(file_get_contents('php://input'), true);

// Valider les données requises
$requiredFields = ['username', 'password', 'email', 'first_name', 'last_name', 'role'];
foreach ($requiredFields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        sendError("Le champ '$field' est requis", 400);
    }
}

// Valider le rôle
$validRoles = ['admin', 'coordinator', 'teacher', 'student'];
if (!in_array($data['role'], $validRoles)) {
    sendError('Rôle invalide', 400);
}

// Initialiser le modèle utilisateur
$userModel = new User($db);

// Vérifier si le nom d'utilisateur existe déjà
if ($userModel->usernameExists($data['username'])) {
    sendError('Ce nom d\'utilisateur existe déjà', 409);
}

// Vérifier si l'email existe déjà
if ($userModel->emailExists($data['email'])) {
    sendError('Cet email existe déjà', 409);
}

// Créer l'utilisateur
$userId = $userModel->create([
    'username' => $data['username'],
    'password' => password_hash($data['password'], PASSWORD_DEFAULT),
    'email' => $data['email'],
    'first_name' => $data['first_name'],
    'last_name' => $data['last_name'],
    'role' => $data['role'],
    'department' => $data['department'] ?? null,
    'profile_image' => $data['profile_image'] ?? null
]);

if (!$userId) {
    sendError('Erreur lors de la création de l\'utilisateur', 500);
}

// Si l'utilisateur est un étudiant, créer un profil étudiant
if ($data['role'] === 'student' && isset($data['student_info'])) {
    $studentModel = new Student($db);
    $studentModel->create([
        'user_id' => $userId,
        'student_number' => $data['student_info']['student_number'],
        'program' => $data['student_info']['program'],
        'level' => $data['student_info']['level'],
        'average_grade' => $data['student_info']['average_grade'] ?? null,
        'graduation_year' => $data['student_info']['graduation_year'] ?? null,
        'skills' => $data['student_info']['skills'] ?? null
    ]);
}

// Si l'utilisateur est un tuteur, créer un profil tuteur
if ($data['role'] === 'teacher' && isset($data['teacher_info'])) {
    $teacherModel = new Teacher($db);
    $teacherModel->create([
        'user_id' => $userId,
        'title' => $data['teacher_info']['title'] ?? null,
        'specialty' => $data['teacher_info']['specialty'] ?? null,
        'office_location' => $data['teacher_info']['office_location'] ?? null,
        'max_students' => $data['teacher_info']['max_students'] ?? 5,
        'expertise' => $data['teacher_info']['expertise'] ?? null
    ]);
}

// Récupérer l'utilisateur créé
$newUser = $userModel->getById($userId);
unset($newUser['password']);

// Envoyer la réponse
sendJsonResponse([
    'success' => true,
    'message' => 'Utilisateur créé avec succès',
    'data' => $newUser
], 201);