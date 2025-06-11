<?php
/**
 * Gérer la disponibilité d'un tuteur
 * GET/PUT /api/teachers/{id}/availability
 */

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

// Vérifier les droits d'accès
$userModel = new User($db);
$user = $userModel->getById($teacher['user_id']);

if (!hasRole(['admin', 'coordinator'])) {
    if (hasRole('teacher')) {
        // Un tuteur ne peut gérer que sa propre disponibilité
        if ($user['id'] !== $_SESSION['user_id']) {
            sendError('Accès refusé', 403);
        }
    } else {
        sendError('Accès refusé', 403);
    }
}

// Traiter la requête selon la méthode HTTP
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Récupérer les informations de disponibilité du tuteur
        $availability = [
            'available' => (bool)$teacher['available'],
            'max_students' => (int)$teacher['max_students']
        ];
        
        // Récupérer le nombre d'étudiants assignés
        $assignmentModel = new Assignment($db);
        $assignedStudentsCount = $assignmentModel->countByTeacherId($teacherId);
        
        $availability['assigned_students_count'] = $assignedStudentsCount;
        $availability['available_slots'] = max(0, $teacher['max_students'] - $assignedStudentsCount);
        
        // Envoyer la réponse
        sendJsonResponse([
            'data' => $availability
        ]);
        break;
        
    case 'PUT':
        // Récupérer les données de la requête
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data)) {
            sendError('Aucune donnée fournie', 400);
        }
        
        // Préparer les données à mettre à jour
        $updateData = [];
        
        // Mettre à jour la disponibilité si fournie
        if (isset($data['available'])) {
            $updateData['available'] = (bool)$data['available'];
        }
        
        // Mettre à jour le nombre maximum d'étudiants si fourni
        if (isset($data['max_students'])) {
            $maxStudents = (int)$data['max_students'];
            
            if ($maxStudents < 1) {
                sendError('Le nombre maximum d\'étudiants doit être au moins 1', 400);
            }
            
            // Vérifier si le nombre maximum d'étudiants est inférieur au nombre d'étudiants déjà assignés
            $assignmentModel = new Assignment($db);
            $assignedStudentsCount = $assignmentModel->countByTeacherId($teacherId);
            
            if ($maxStudents < $assignedStudentsCount) {
                sendError('Le nombre maximum d\'étudiants ne peut pas être inférieur au nombre d\'étudiants déjà assignés (' . $assignedStudentsCount . ')', 400);
            }
            
            $updateData['max_students'] = $maxStudents;
        }
        
        // Si aucune donnée à mettre à jour
        if (empty($updateData)) {
            sendError('Aucune donnée valide à mettre à jour', 400);
        }
        
        // Mettre à jour le tuteur
        $success = $teacherModel->update($teacherId, $updateData);
        
        if (!$success) {
            sendError('Erreur lors de la mise à jour de la disponibilité', 500);
        }
        
        // Récupérer le tuteur mis à jour
        $updatedTeacher = $teacherModel->getById($teacherId);
        
        // Envoyer la réponse
        sendJsonResponse([
            'success' => true,
            'message' => 'Disponibilité mise à jour avec succès',
            'data' => [
                'available' => (bool)$updatedTeacher['available'],
                'max_students' => (int)$updatedTeacher['max_students']
            ]
        ]);
        break;
        
    default:
        sendError('Méthode non autorisée', 405);
}