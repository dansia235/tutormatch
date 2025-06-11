<?php
/**
 * Vue pour la liste des enseignants (tuteurs)
 */

// Initialiser les variables
$pageTitle = 'Gestion des tuteurs';
$currentPage = 'tutors';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../../includes/init.php';

// Vérifier les permissions
requireRole(['admin', 'coordinator']);

// Vérifier si cette page est incluse par le contrôleur ou appelée directement
$isIncludedByController = isset($teachers);

// Si la page n'est pas incluse par le contrôleur, initialiser le contrôleur et charger les données
if (!$isIncludedByController) {
    // Instancier le contrôleur
    $teacherController = new TeacherController($db);
    
    // Traiter la recherche ou afficher tous les enseignants
    if (isset($_GET['search'])) {
        $term = isset($_GET['term']) ? $_GET['term'] : '';
        $availableOnly = isset($_GET['available']) ? (bool)$_GET['available'] : false;
        $teachers = $teacherController->search($term, $availableOnly, true);
    } else {
        // Afficher tous les enseignants ou filtrer par disponibilité
        $availableOnly = isset($_GET['available']) ? (bool)$_GET['available'] : false;
        $teachers = $teacherController->getTeachers($availableOnly);
    }
    
    // Récupérer les statistiques
    $teacherCount = count($teachers);
    $availableTeachers = array_filter($teachers, function($teacher) {
        return $teacher['available'] == 1;
    });
    $availableCount = count($availableTeachers);
    
    // Calculer la capacité disponible
    $totalMaxStudents = array_sum(array_column($teachers, 'max_students'));
    $totalStudentsCount = 0;
    foreach ($teachers as $teacher) {
        $totalStudentsCount += isset($teacher['students_count']) ? $teacher['students_count'] : 0;
    }
    $availableCapacity = $totalMaxStudents - $totalStudentsCount;
}

// Définir le filtre actif
$activeFilter = isset($_GET['available']) ? $_GET['available'] : '';

// Fonction helper pour inclure des fichiers avec des variables
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
if (file_exists(__DIR__ . '/../../../templates/layouts/admin.php')) {
    // Nouveau layout avec Tailwind
    $content = __DIR__ . '/teachers-content.php';
    include_once __DIR__ . '/../../../templates/layouts/admin.php';
} else {
    // Ancien layout avec Bootstrap
    include_once __DIR__ . '/../../common/header.php';
    include_once __DIR__ . '/teachers-content-bootstrap.php';
    include_once __DIR__ . '/../../common/footer.php';
}