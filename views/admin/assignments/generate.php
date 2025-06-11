<?php
/**
 * Vue pour la génération automatique d'affectations
 */

// Initialiser les variables
$pageTitle = 'Génération automatique d\'affectations';
$currentPage = 'assignments';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../../includes/init.php';

// Vérifier les permissions
requireRole(['admin', 'coordinator']);

// Récupérer les anciennes données du formulaire en cas d'erreur
$formData = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']);

// Récupérer les erreurs du formulaire
$formErrors = $_SESSION['form_errors'] ?? [];
unset($_SESSION['form_errors']);

// Instancier les modèles nécessaires
$studentModel = new Student($db);
$teacherModel = new Teacher($db);
$internshipModel = new Internship($db);
$assignmentModel = new Assignment($db);

// Récupérer les statistiques pour l'affichage
$totalStudents = count($studentModel->getAll('active'));
$totalTeachers = count($teacherModel->getAll(true));
$totalInternships = count($internshipModel->getAll('available'));
$totalAssignments = count($assignmentModel->getAll());

// Récupérer les étudiants sans affectation
$students = $studentModel->getAll('active');
$unassignedStudents = [];

foreach ($students as $student) {
    $existingAssignment = $assignmentModel->getByStudentId($student['id']);
    if (!$existingAssignment) {
        $unassignedStudents[] = $student;
    }
}

// Récupérer les enseignants disponibles
$teachers = $teacherModel->getAll(true);
$availableTeachers = [];
$teacherCapacity = 0;

foreach ($teachers as $teacher) {
    $assignmentCount = $assignmentModel->countByTeacherId($teacher['id']);
    if ($assignmentCount < $teacher['max_students']) {
        $remaining = $teacher['max_students'] - $assignmentCount;
        $teacher['remaining_capacity'] = $remaining;
        $availableTeachers[] = $teacher;
        $teacherCapacity += $remaining;
    }
}

// Récupérer les stages disponibles
$availableInternships = $internshipModel->getAll('available');

// Function helper for including files with variables
function include_with_vars($file, array $vars = []) {
    if (file_exists($file)) {
        // Extract variables into the current scope
        extract($vars);
        
        // Start output buffering
        ob_start();
        
        // Include the file
        include $file;
        
        // Return the output
        return ob_get_clean();
    }
    
    return '';
}

// Inclure le contenu ou l'en-tête selon le type de layout
if (file_exists(__DIR__ . '/../../../templates/layouts/admin.php') && false) {
    // Nouveau layout avec Tailwind - désactivé temporairement
    $content = __DIR__ . '/generate-content.php';
    include_once __DIR__ . '/../../../templates/layouts/admin.php';
} else {
    // Layout avec Bootstrap
    include_once __DIR__ . '/../../common/header.php';
    
    // Inclure le contenu Bootstrap
    include_once __DIR__ . '/generate-content-bootstrap.php';
    
    // Inclure le pied de page
    include_once __DIR__ . '/../../common/footer.php';
}
?>