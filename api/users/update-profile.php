<?php
/**
 * API pour mettre à jour le profil utilisateur
 * Endpoint: /api/users/update-profile.php
 * Méthodes: POST
 */

require_once __DIR__ . '/../../includes/init.php';

// Vérifier que l'utilisateur est connecté
requireLogin();

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (isAjaxRequest()) {
        sendJsonResponse(['error' => true, 'message' => 'Méthode non autorisée'], 405);
    } else {
        setFlashMessage('error', 'Méthode non autorisée');
        redirect('/tutoring/views/common/settings.php');
    }
}

// Vérifier le token CSRF
if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    if (isAjaxRequest()) {
        sendJsonResponse(['error' => true, 'message' => 'Token CSRF invalide ou manquant'], 403);
    } else {
        setFlashMessage('error', 'Token de sécurité invalide ou manquant. Veuillez réessayer.');
        redirect('/tutoring/views/common/settings.php');
    }
}

// Récupérer l'ID de l'utilisateur
$userId = $_SESSION['user_id'];

// Initialiser le modèle utilisateur
$userModel = new User($db);

// Récupérer les données actuelles de l'utilisateur
$user = $userModel->getById($userId);
if (!$user) {
    if (isAjaxRequest()) {
        sendJsonResponse(['error' => true, 'message' => 'Utilisateur non trouvé'], 404);
    } else {
        setFlashMessage('error', 'Utilisateur non trouvé');
        redirect('/tutoring/views/common/settings.php');
    }
}

// Récupérer les données du formulaire
$data = [
    'first_name' => $_POST['first_name'] ?? $user['first_name'],
    'last_name' => $_POST['last_name'] ?? $user['last_name'],
    'email' => $_POST['email'] ?? $user['email'],
    'department' => $_POST['department'] ?? $user['department']
];

// Validation des données
$errors = [];
if (empty($data['first_name'])) {
    $errors[] = 'Le prénom est requis';
}
if (empty($data['last_name'])) {
    $errors[] = 'Le nom est requis';
}
if (empty($data['email'])) {
    $errors[] = 'L\'email est requis';
} elseif (!isValidEmail($data['email'])) {
    $errors[] = 'L\'email n\'est pas valide';
}

// Si l'email est modifié, vérifier qu'il n'est pas déjà utilisé
if ($data['email'] !== $user['email']) {
    $existingUser = $userModel->getByEmail($data['email']);
    if ($existingUser && $existingUser['id'] != $userId) {
        $errors[] = 'Cet email est déjà utilisé';
    }
}

// Traiter l'upload de la photo de profil
if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize = 5 * 1024 * 1024; // 5 Mo
    
    $uploadResult = uploadFile($_FILES['profile_image'], 'profiles', $allowedTypes, $maxSize);
    
    if ($uploadResult === false) {
        $errors[] = 'Erreur lors de l\'upload de l\'image. Vérifiez que le fichier est une image (JPG, PNG, GIF) et ne dépasse pas 5 Mo.';
    } else {
        $data['profile_image'] = $uploadResult;
        
        // Supprimer l'ancienne image si elle existe
        if (!empty($user['profile_image']) && $user['profile_image'] !== $uploadResult) {
            deleteFile($user['profile_image']);
        }
    }
}

// Gestion des informations spécifiques au rôle
if ($user['role'] === 'student' && class_exists('Student')) {
    $studentModel = new Student($db);
    $student = $studentModel->getByUserId($userId);
    
    if ($student) {
        $studentData = [
            'program' => $_POST['program'] ?? $student['program'],
            'level' => $_POST['level'] ?? $student['level'],
            'skills' => $_POST['skills'] ?? $student['skills']
        ];
        
        // Mettre à jour les informations de l'étudiant
        try {
            $studentModel->update($student['id'], $studentData);
        } catch (Exception $e) {
            $errors[] = 'Erreur lors de la mise à jour des informations étudiant: ' . $e->getMessage();
        }
    }
} elseif ($user['role'] === 'teacher' && class_exists('Teacher')) {
    $teacherModel = new Teacher($db);
    $teacher = $teacherModel->getByUserId($userId);
    
    if ($teacher) {
        $teacherData = [
            'title' => $_POST['title'] ?? $teacher['title'],
            'specialty' => $_POST['specialty'] ?? $teacher['specialty'],
            'office_location' => $_POST['office_location'] ?? $teacher['office_location'],
            'max_students' => isset($_POST['max_students']) ? intval($_POST['max_students']) : $teacher['max_students'],
            'expertise' => $_POST['expertise'] ?? $teacher['expertise']
        ];
        
        // Validation des données du tuteur
        if ($teacherData['max_students'] < 1 || $teacherData['max_students'] > 20) {
            $errors[] = 'Le nombre maximum d\'étudiants doit être compris entre 1 et 20';
        }
        
        // Mettre à jour les informations du tuteur
        if (empty($errors)) {
            try {
                $teacherModel->update($teacher['id'], $teacherData);
            } catch (Exception $e) {
                $errors[] = 'Erreur lors de la mise à jour des informations tuteur: ' . $e->getMessage();
            }
        }
    }
}

// S'il y a des erreurs, rediriger avec les erreurs
if (!empty($errors)) {
    if (isAjaxRequest()) {
        sendJsonResponse(['error' => true, 'messages' => $errors], 400);
    } else {
        $_SESSION['form_errors'] = $errors;
        $_SESSION['form_data'] = $_POST;
        redirect('/tutoring/views/common/settings.php');
    }
}

// Mettre à jour le profil de l'utilisateur
try {
    $result = $userModel->update($userId, $data);
    
    // Mettre à jour la session
    $_SESSION['user_name'] = $data['first_name'] . ' ' . $data['last_name'];
    
    if ($result) {
        if (isAjaxRequest()) {
            sendJsonResponse(['success' => true, 'message' => 'Profil mis à jour avec succès']);
        } else {
            setFlashMessage('success', 'Profil mis à jour avec succès');
            redirect('/tutoring/views/common/settings.php');
        }
    } else {
        if (isAjaxRequest()) {
            sendJsonResponse(['error' => true, 'message' => 'Erreur lors de la mise à jour du profil'], 500);
        } else {
            setFlashMessage('error', 'Erreur lors de la mise à jour du profil');
            redirect('/tutoring/views/common/settings.php');
        }
    }
} catch (Exception $e) {
    if (isAjaxRequest()) {
        sendJsonResponse(['error' => true, 'message' => 'Erreur: ' . $e->getMessage()], 500);
    } else {
        setFlashMessage('error', 'Erreur: ' . $e->getMessage());
        redirect('/tutoring/views/common/settings.php');
    }
}

/**
 * Vérifie si la requête est AJAX
 * @return bool True si la requête est AJAX, sinon false
 */
function isAjaxRequest() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Envoie une réponse JSON
 * @param mixed $data Les données à envoyer
 * @param int $code Le code HTTP
 */
function sendJsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
?>