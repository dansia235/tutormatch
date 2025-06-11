<?php
/**
 * Télécharger un document
 * GET /api/documents/{id}/download
 */

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Méthode non autorisée', 405);
}

// Récupérer l'ID du document depuis l'URL
$documentId = isset($urlParts[2]) ? (int)$urlParts[2] : 0;

if ($documentId <= 0) {
    sendError('ID document invalide', 400);
}

// Initialiser le modèle document
$documentModel = new Document($db);

// Récupérer le document
$document = $documentModel->getById($documentId);

if (!$document) {
    sendError('Document non trouvé', 404);
}

// Vérifier les droits d'accès
if (!hasRole(['admin', 'coordinator'])) {
    if (hasRole('teacher')) {
        // Vérifier si l'utilisateur est le tuteur assigné
        if ($document['assignment_id']) {
            $assignmentModel = new Assignment($db);
            $assignment = $assignmentModel->getById($document['assignment_id']);
            
            if ($assignment) {
                $teacherModel = new Teacher($db);
                $teacher = $teacherModel->getByUserId($_SESSION['user_id']);
                
                if (!$teacher || $assignment['teacher_id'] !== $teacher['id']) {
                    sendError('Accès refusé: vous n\'êtes pas le tuteur assigné à cette affectation', 403);
                }
            }
        } else {
            // Document sans affectation - vérifier si l'utilisateur est le propriétaire
            if ($document['user_id'] !== $_SESSION['user_id']) {
                sendError('Accès refusé', 403);
            }
        }
    } elseif (hasRole('student')) {
        // Vérifier si l'utilisateur est l'étudiant assigné ou le propriétaire
        if ($document['assignment_id']) {
            $assignmentModel = new Assignment($db);
            $assignment = $assignmentModel->getById($document['assignment_id']);
            
            if ($assignment) {
                $studentModel = new Student($db);
                $student = $studentModel->getByUserId($_SESSION['user_id']);
                
                if (!$student || $assignment['student_id'] !== $student['id']) {
                    // Vérifier si l'utilisateur est le propriétaire
                    if ($document['user_id'] !== $_SESSION['user_id']) {
                        sendError('Accès refusé', 403);
                    }
                }
            }
        } else {
            // Document sans affectation - vérifier si l'utilisateur est le propriétaire
            if ($document['user_id'] !== $_SESSION['user_id']) {
                sendError('Accès refusé', 403);
            }
        }
    } else {
        sendError('Accès refusé', 403);
    }
}

// Vérifier si le fichier existe
$filePath = ROOT_PATH . $document['file_path'];
if (!file_exists($filePath)) {
    sendError('Fichier introuvable', 404);
}

// Déterminer le type MIME
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $filePath);
finfo_close($finfo);

// Extraire le nom du fichier depuis le chemin
$fileName = basename($filePath);

// Désactiver la mise en cache
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Content-Length: ' . filesize($filePath));

// Vider le tampon de sortie
ob_clean();
flush();

// Lire le fichier
readfile($filePath);
exit;