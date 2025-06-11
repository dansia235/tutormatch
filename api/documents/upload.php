<?php
/**
 * Télécharger un document
 * POST /api/documents
 */

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Méthode non autorisée', 405);
}

// Vérifier si le fichier a été téléchargé
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $errorMessages = [
        UPLOAD_ERR_INI_SIZE => 'Le fichier dépasse la taille maximale définie dans php.ini',
        UPLOAD_ERR_FORM_SIZE => 'Le fichier dépasse la taille maximale définie dans le formulaire',
        UPLOAD_ERR_PARTIAL => 'Le fichier n\'a été que partiellement téléchargé',
        UPLOAD_ERR_NO_FILE => 'Aucun fichier n\'a été téléchargé',
        UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant',
        UPLOAD_ERR_CANT_WRITE => 'Échec de l\'écriture du fichier sur le disque',
        UPLOAD_ERR_EXTENSION => 'Une extension PHP a arrêté le téléchargement du fichier'
    ];
    
    $errorMessage = isset($_FILES['file']) ? 
        ($errorMessages[$_FILES['file']['error']] ?? 'Erreur inconnue lors du téléchargement') : 
        'Aucun fichier n\'a été envoyé';
    
    sendError($errorMessage, 400);
}

// Récupérer les données du formulaire
$title = $_POST['title'] ?? '';
$type = $_POST['type'] ?? '';
$assignmentId = isset($_POST['assignment_id']) ? (int)$_POST['assignment_id'] : null;
$version = $_POST['version'] ?? '1.0';

// Valider les données requises
if (empty($title)) {
    sendError('Le titre du document est requis', 400);
}

// Valider le type de document
$validTypes = ['contract', 'report', 'evaluation', 'certificate', 'other'];
if (empty($type) || !in_array($type, $validTypes)) {
    sendError('Type de document invalide', 400);
}

// Valider l'affectation si fournie
if ($assignmentId) {
    $assignmentModel = new Assignment($db);
    $assignment = $assignmentModel->getById($assignmentId);
    
    if (!$assignment) {
        sendError('Affectation non trouvée', 404);
    }
    
    // Vérifier les droits d'accès
    if (!hasRole(['admin', 'coordinator'])) {
        $userModel = new User($db);
        
        if (hasRole('teacher')) {
            // Vérifier si l'utilisateur est le tuteur assigné
            $teacherModel = new Teacher($db);
            $teacher = $teacherModel->getByUserId($_SESSION['user_id']);
            
            if (!$teacher || $assignment['teacher_id'] !== $teacher['id']) {
                sendError('Accès refusé: vous n\'êtes pas le tuteur assigné à cette affectation', 403);
            }
        } elseif (hasRole('student')) {
            // Vérifier si l'utilisateur est l'étudiant assigné
            $studentModel = new Student($db);
            $student = $studentModel->getByUserId($_SESSION['user_id']);
            
            if (!$student || $assignment['student_id'] !== $student['id']) {
                sendError('Accès refusé: vous n\'êtes pas l\'étudiant assigné à cette affectation', 403);
            }
        }
    }
}

// Vérifier le type de fichier
$allowedTypes = [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-powerpoint',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'image/jpeg',
    'image/png'
];

$fileType = $_FILES['file']['type'];
if (!in_array($fileType, $allowedTypes)) {
    sendError('Type de fichier non autorisé', 400);
}

// Vérifier la taille du fichier (max 10 Mo)
$maxSize = 10 * 1024 * 1024; // 10 Mo
if ($_FILES['file']['size'] > $maxSize) {
    sendError('Le fichier est trop volumineux (maximum 10 Mo)', 400);
}

// Déterminer le dossier de destination
$uploadDir = $type;
if ($assignmentId) {
    $uploadDir .= '/' . $assignmentId;
}

// Télécharger le fichier
$filePath = uploadFile($_FILES['file'], $uploadDir, $allowedTypes, $maxSize);

if (!$filePath) {
    sendError('Erreur lors du téléchargement du fichier', 500);
}

// Initialiser le modèle document
$documentModel = new Document($db);

// Créer le document
$documentData = [
    'user_id' => $_SESSION['user_id'],
    'assignment_id' => $assignmentId,
    'title' => $title,
    'type' => $type,
    'file_path' => $filePath,
    'status' => 'draft', // Par défaut, le document est en statut brouillon
    'version' => $version
];

$documentId = $documentModel->create($documentData);

if (!$documentId) {
    // Supprimer le fichier si l'enregistrement échoue
    deleteFile($filePath);
    sendError('Erreur lors de la création du document', 500);
}

// Récupérer le document créé
$createdDocument = $documentModel->getById($documentId);

// Récupérer les informations sur l'utilisateur propriétaire
$userModel = new User($db);
$user = $userModel->getById($createdDocument['user_id']);

// Récupérer les informations sur l'affectation si applicable
$assignmentInfo = null;
if ($createdDocument['assignment_id']) {
    $assignmentModel = new Assignment($db);
    $assignment = $assignmentModel->getById($createdDocument['assignment_id']);
    
    if ($assignment) {
        $studentModel = new Student($db);
        $student = $studentModel->getById($assignment['student_id']);
        
        $teacherModel = new Teacher($db);
        $teacher = $teacherModel->getById($assignment['teacher_id']);
        
        $internshipModel = new Internship($db);
        $internship = $internshipModel->getById($assignment['internship_id']);
        
        $assignmentInfo = [
            'id' => $assignment['id'],
            'status' => $assignment['status'],
            'student_id' => $assignment['student_id'],
            'teacher_id' => $assignment['teacher_id'],
            'internship_id' => $assignment['internship_id'],
            'student_name' => $student ? $student['user_first_name'] . ' ' . $student['user_last_name'] : 'N/A',
            'teacher_name' => $teacher ? $teacher['user_first_name'] . ' ' . $teacher['user_last_name'] : 'N/A',
            'internship_title' => $internship ? $internship['title'] : 'N/A'
        ];
    }
}

// Formater le document
$formattedDocument = [
    'id' => $createdDocument['id'],
    'title' => $createdDocument['title'],
    'type' => $createdDocument['type'],
    'file_path' => $createdDocument['file_path'],
    'upload_date' => date('Y-m-d H:i:s', strtotime($createdDocument['upload_date'])),
    'status' => $createdDocument['status'],
    'feedback' => $createdDocument['feedback'],
    'version' => $createdDocument['version'],
    'user' => [
        'id' => $user['id'],
        'name' => $user['first_name'] . ' ' . $user['last_name'],
        'role' => $user['role']
    ]
];

// Ajouter les informations d'affectation si disponibles
if ($assignmentInfo) {
    $formattedDocument['assignment'] = $assignmentInfo;
}

// Envoyer la réponse
sendJsonResponse([
    'success' => true,
    'message' => 'Document téléchargé avec succès',
    'data' => $formattedDocument
], 201);