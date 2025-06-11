<?php
/**
 * Détails d'un document
 * GET /api/documents/{id}
 */

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Méthode non autorisée', 405);
}

// Vérifier que l'ID est présent
if (!isset($urlParts[2]) || !is_numeric($urlParts[2])) {
    sendError('ID de document invalide', 400);
}

$documentId = (int)$urlParts[2];

// Initialiser les modèles
$documentModel = new Document($db);
$userModel = new User($db);
$studentModel = new Student($db);
$teacherModel = new Teacher($db);
$assignmentModel = new Assignment($db);

// Récupérer le document
$document = $documentModel->getById($documentId);
if (!$document) {
    sendError('Document non trouvé', 404);
}

// Vérifier les permissions
$currentUserRole = $_SESSION['user_role'];
$currentUserId = $_SESSION['user_id'];

// Déterminer si l'utilisateur a accès au document
$hasAccess = false;

if ($currentUserRole === 'admin' || $currentUserRole === 'coordinator') {
    // Les administrateurs et coordinateurs ont accès à tous les documents
    $hasAccess = true;
} else {
    // Pour les étudiants et tuteurs, vérifier si le document est lié à leur affectation
    $assignmentId = $document['assignment_id'];
    
    if ($assignmentId) {
        $assignment = $assignmentModel->getById($assignmentId);
        
        if ($assignment) {
            if ($currentUserRole === 'student') {
                // Vérifier si l'étudiant est concerné par cette affectation
                $student = $studentModel->getByUserId($currentUserId);
                if ($student && $student['id'] == $assignment['student_id']) {
                    $hasAccess = true;
                }
            } elseif ($currentUserRole === 'teacher') {
                // Vérifier si le tuteur est concerné par cette affectation
                $teacher = $teacherModel->getByUserId($currentUserId);
                if ($teacher && $teacher['id'] == $assignment['teacher_id']) {
                    $hasAccess = true;
                }
            }
        }
    } else {
        // Si le document n'est pas lié à une affectation, vérifier s'il appartient à l'utilisateur
        if ($document['user_id'] == $currentUserId) {
            $hasAccess = true;
        }
    }
}

if (!$hasAccess) {
    sendError('Accès non autorisé', 403);
}

// Enrichir le document avec des informations additionnelles
$enrichedDocument = $document;

// Informations sur l'utilisateur qui a téléversé le document
if ($document['user_id']) {
    $user = $userModel->getById($document['user_id']);
    if ($user) {
        $enrichedDocument['uploaded_by'] = [
            'id' => $user['id'],
            'name' => $user['first_name'] . ' ' . $user['last_name'],
            'email' => $user['email'],
            'role' => $user['role']
        ];
    }
}

// Informations sur l'affectation
if ($document['assignment_id']) {
    $assignment = $assignmentModel->getById($document['assignment_id']);
    if ($assignment) {
        $student = $studentModel->getById($assignment['student_id']);
        $teacher = $teacherModel->getById($assignment['teacher_id']);
        
        $studentUser = $student ? $userModel->getById($student['user_id']) : null;
        $teacherUser = $teacher ? $userModel->getById($teacher['user_id']) : null;
        
        $enrichedDocument['assignment'] = [
            'id' => $assignment['id'],
            'student' => $studentUser ? [
                'id' => $student['id'],
                'name' => $studentUser['first_name'] . ' ' . $studentUser['last_name']
            ] : null,
            'teacher' => $teacherUser ? [
                'id' => $teacher['id'],
                'name' => $teacherUser['first_name'] . ' ' . $teacherUser['last_name']
            ] : null,
            'status' => $assignment['status']
        ];
    }
}

// Créer l'URL de téléchargement
$enrichedDocument['download_url'] = '/tutoring/api/documents/' . $documentId . '/download';

// Envoyer la réponse
sendJsonResponse([
    'data' => $enrichedDocument
]);