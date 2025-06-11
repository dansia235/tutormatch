<?php
/**
 * Vue pour la liste des étudiants
 */

// Initialiser les variables
$pageTitle = 'Gestion des étudiants';
$currentPage = 'students';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../../includes/init.php';

// Vérifier les permissions
requireRole(['admin', 'coordinator', 'teacher']);

// Vérifier si cette page est incluse par le contrôleur ou appelée directement
$isIncludedByController = isset($students);

// Si la page n'est pas incluse par le contrôleur, initialiser le contrôleur et charger les données
if (!$isIncludedByController) {
    // Instancier le contrôleur
    $studentController = new StudentController($db);
    
    // Traiter la recherche ou afficher tous les étudiants
    if (isset($_GET['search'])) {
        $term = isset($_GET['term']) ? $_GET['term'] : '';
        $status = isset($_GET['status']) ? $_GET['status'] : null;
        $students = $studentController->search($term, $status, true);
    } else {
        // Afficher tous les étudiants ou filtrer par statut
        $status = isset($_GET['status']) ? $_GET['status'] : null;
        $students = $studentController->getStudents($status);
    }
}

// Définir le filtre actif
$activeFilter = isset($_GET['status']) ? $_GET['status'] : '';

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
    $content = __DIR__ . '/students-content.php';
    include_once __DIR__ . '/../../../templates/layouts/admin.php';
} else {
    // Ancien layout avec Bootstrap
    include_once __DIR__ . '/../../common/header.php';
    include_once __DIR__ . '/students-content-bootstrap.php';
    include_once __DIR__ . '/../../common/footer.php';
}