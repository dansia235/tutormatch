<?php
/**
 * API pour la matrice d'affectation
 * Endpoint: /api/assignments/matrix
 * Méthode: GET
 * 
 * Récupère les données nécessaires pour afficher et manipuler
 * la matrice d'affectation étudiants/tuteurs.
 */

require_once __DIR__ . '/../utils.php';
require_once __DIR__ . '/../../src/Algorithm/GreedyAlgorithm.php';
require_once __DIR__ . '/../../controllers/StatisticsController.php';

// Vérifier que l'utilisateur est connecté et a les droits
requireApiAuth();
requireApiRole(['admin', 'coordinator']);

// Paramètres optionnels
$departmentFilter = isset($_GET['department']) ? $_GET['department'] : null;
$excludeAssigned = isset($_GET['exclude_assigned']) && $_GET['exclude_assigned'] === '1';

try {
    // Instancier les contrôleurs
    $teacherController = new TeacherController($db);
    $studentController = new StudentController($db);
    $internshipController = new InternshipController($db);
    $assignmentController = new AssignmentController($db);
    $statsController = new StatisticsController($db);
    
    // Récupérer les tuteurs
    $teachersParams = ['available' => true];
    if ($departmentFilter) {
        $teachersParams['department'] = $departmentFilter;
    }
    $teachers = $teacherController->getTeachers($teachersParams);
    
    // Récupérer les étudiants
    $studentsParams = [];
    if ($departmentFilter) {
        $studentsParams['department'] = $departmentFilter;
    }
    if ($excludeAssigned) {
        $studentsParams['assigned'] = false;
    }
    $students = $studentController->getAll($studentsParams);
    
    // Récupérer les stages
    $internships = $internshipController->getAll();
    
    // Récupérer les affectations existantes
    $assignments = $assignmentController->getAll();
    
    // Récupérer les préférences des étudiants
    $studentPreferences = [];
    foreach ($students as $student) {
        $preferences = $studentController->getInternshipPreferences($student['id']);
        $studentPreferences[$student['id']] = $preferences;
    }
    
    // Récupérer les préférences de département
    $crossDepartmentMatrix = $statsController->generateCrossDepartmentMatrix();
    
    // Préparer les données pour la matrice d'affectation
    $matrixData = [
        'students' => array_map(function($student) {
            return [
                'id' => $student['id'],
                'name' => $student['first_name'] . ' ' . $student['last_name'],
                'department' => $student['department'] ?? '',
                'email' => $student['email'] ?? '',
                'assigned' => isset($student['assignment_id']) && $student['assignment_id'] > 0
            ];
        }, $students),
        
        'teachers' => array_map(function($teacher) {
            return [
                'id' => $teacher['id'],
                'name' => $teacher['first_name'] . ' ' . $teacher['last_name'],
                'department' => $teacher['department'] ?? '',
                'email' => $teacher['email'] ?? '',
                'specialty' => $teacher['specialty'] ?? '',
                'max_students' => (int) ($teacher['max_students'] ?? 5),
                'current_students' => (int) ($teacher['current_students'] ?? 0),
                'workload_percentage' => $teacher['max_students'] > 0 
                    ? round(($teacher['current_students'] / $teacher['max_students']) * 100) 
                    : 0
            ];
        }, $teachers),
        
        'internships' => array_map(function($internship) {
            return [
                'id' => $internship['id'],
                'title' => $internship['title'],
                'company' => $internship['company_name'] ?? '',
                'domain' => $internship['domain'] ?? '',
                'skills' => $internship['skills'] ?? [],
                'status' => $internship['status']
            ];
        }, $internships),
        
        'assignments' => array_map(function($assignment) {
            return [
                'id' => $assignment['id'],
                'student_id' => $assignment['student_id'],
                'teacher_id' => $assignment['teacher_id'],
                'internship_id' => $assignment['internship_id'],
                'status' => $assignment['status'],
                'created_at' => $assignment['created_at'],
                'preference_rank' => (int) ($assignment['preference_rank'] ?? 0)
            ];
        }, $assignments),
        
        'preferences' => $studentPreferences,
        
        'department_matrix' => $crossDepartmentMatrix
    ];
    
    // Récupérer les paramètres de l'algorithme depuis les paramètres système
    $systemSettingsTable = 'system_settings';
    $algorithmSettings = [
        'algorithm_type' => 'greedy',
        'preference_weight' => 40,
        'department_weight' => 30,
        'workload_weight' => 30,
        'allow_cross_department' => true
    ];
    
    // Vérifier si la table de paramètres système existe
    $checkTable = $db->query("SHOW TABLES LIKE '$systemSettingsTable'");
    if ($checkTable->rowCount() > 0) {
        // Récupérer les paramètres
        $stmt = $db->query("
            SELECT setting_key, setting_value FROM $systemSettingsTable 
            WHERE setting_key IN ('algorithm_type', 'preference_weight', 'department_weight', 'workload_weight', 'allow_cross_department')
        ");
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $key = $row['setting_key'];
            $value = $row['setting_value'];
            
            // Convertir les valeurs si nécessaire
            if (in_array($key, ['preference_weight', 'department_weight', 'workload_weight'])) {
                $value = (int) $value;
            } elseif ($key === 'allow_cross_department') {
                $value = (bool) (int) $value;
            }
            
            $algorithmSettings[$key] = $value;
        }
    }
    
    // Ajouter les paramètres d'algorithme aux données
    $matrixData['algorithm_settings'] = $algorithmSettings;
    
    // Envoyer la réponse
    sendJsonResponse($matrixData);
} catch (Exception $e) {
    sendJsonError('Erreur lors de la récupération des données de la matrice: ' . $e->getMessage(), 500);
}
?>