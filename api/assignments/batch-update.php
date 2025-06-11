<?php
/**
 * API: Mise à jour par lot des affectations
 * POST /api/assignments/batch-update
 * 
 * Permet de mettre à jour plusieurs affectations en une seule requête
 */

// Headers requis
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Inclure les fichiers nécessaires
require_once __DIR__ . '/../../includes/init.php';

// Vérifier que l'utilisateur est connecté et a les permissions nécessaires
requireRole(['admin', 'coordinator']);

// Vérifier que la requête est en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Méthode non autorisée', 405);
}

// Récupérer les données JSON de la requête
$requestData = json_decode(file_get_contents('php://input'), true);

// Vérifier que les données sont valides
if (!isset($requestData['assignments']) || !is_array($requestData['assignments'])) {
    sendError('Format de données invalide', 400);
}

// Initialiser les modèles nécessaires
$assignmentModel = new Assignment($db);
$studentModel = new Student($db);
$teacherModel = new Teacher($db);
$internshipModel = new Internship($db);

// Préparer le suivi des résultats
$results = [
    'success' => true,
    'processed' => 0,
    'errors' => [],
    'created' => [],
    'updated' => [],
    'unchanged' => []
];

// Traiter chaque affectation
foreach ($requestData['assignments'] as $studentId => $teacherId) {
    // Valider les données
    $studentId = (int)$studentId;
    $teacherId = (int)$teacherId;
    
    // Vérifier que l'étudiant existe
    $student = $studentModel->getById($studentId);
    if (!$student) {
        $results['errors'][] = [
            'studentId' => $studentId, 
            'message' => 'Étudiant non trouvé'
        ];
        $results['success'] = false;
        continue;
    }
    
    // Vérifier que l'enseignant existe
    $teacher = $teacherModel->getById($teacherId);
    if (!$teacher) {
        $results['errors'][] = [
            'studentId' => $studentId, 
            'teacherId' => $teacherId,
            'message' => 'Tuteur non trouvé'
        ];
        $results['success'] = false;
        continue;
    }
    
    // Vérifier si l'affectation existe déjà
    $existingAssignment = $assignmentModel->getByStudentId($studentId);
    
    if ($existingAssignment) {
        // Si l'affectation est identique, ne rien faire
        if ($existingAssignment['teacher_id'] == $teacherId) {
            $results['unchanged'][] = $existingAssignment['id'];
            $results['processed']++;
            continue;
        }
        
        // Sinon, mettre à jour l'affectation
        $updateData = [
            'teacher_id' => $teacherId,
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => $_SESSION['user_id']
        ];
        
        if ($assignmentModel->update($existingAssignment['id'], $updateData)) {
            $results['updated'][] = $existingAssignment['id'];
        } else {
            $results['errors'][] = [
                'studentId' => $studentId,
                'message' => 'Échec de mise à jour de l\'affectation'
            ];
            $results['success'] = false;
        }
    } else {
        // Créer une nouvelle affectation
        $newAssignment = [
            'student_id' => $studentId,
            'teacher_id' => $teacherId,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $_SESSION['user_id']
        ];
        
        $assignmentId = $assignmentModel->create($newAssignment);
        if ($assignmentId) {
            $results['created'][] = $assignmentId;
        } else {
            $results['errors'][] = [
                'studentId' => $studentId,
                'message' => 'Échec de création de l\'affectation'
            ];
            $results['success'] = false;
        }
    }
    
    $results['processed']++;
}

// Vérifier si le tuteur a dépassé sa capacité
$teachers = $teacherModel->getAll();
$teacherCapacityWarnings = [];

foreach ($teachers as $teacher) {
    $assignmentCount = $assignmentModel->countByTeacherId($teacher['id']);
    if ($assignmentCount > $teacher['max_students']) {
        $teacherCapacityWarnings[] = [
            'teacherId' => $teacher['id'],
            'teacherName' => $teacher['name'],
            'capacity' => $teacher['max_students'],
            'assigned' => $assignmentCount
        ];
    }
}

// Ajouter les avertissements aux résultats
$results['warnings'] = [
    'teacherCapacity' => $teacherCapacityWarnings
];

// Renvoyer les résultats
sendJsonResponse($results);

/**
 * Envoie une réponse JSON
 * 
 * @param mixed $data Les données à envoyer
 * @param int $statusCode Code HTTP de statut
 */
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit();
}

/**
 * Envoie une erreur en JSON
 * 
 * @param string $message Message d'erreur
 * @param int $statusCode Code HTTP d'erreur
 */
function sendError($message, $statusCode = 400) {
    http_response_code($statusCode);
    echo json_encode([
        'success' => false,
        'message' => $message
    ]);
    exit();
}
?>