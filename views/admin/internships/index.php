<?php
/**
 * Vue pour la liste des stages
 */

// Initialiser les variables
$pageTitle = 'Gestion des stages';
$currentPage = 'internships';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../../includes/init.php';

// Vérifier les permissions
requireRole(['admin', 'coordinator']);

// Instancier le contrôleur
$internshipController = new InternshipController($db);

// Traiter la recherche ou afficher tous les stages
if (isset($_GET['search'])) {
    $term = isset($_GET['term']) ? $_GET['term'] : '';
    $domain = isset($_GET['domain']) ? $_GET['domain'] : null;
    $company = isset($_GET['company']) ? $_GET['company'] : null;
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    
    // Utiliser la méthode search du contrôleur ou directement depuis l'instance du modèle créée ci-dessous
    $internshipModel = new Internship($db);
    $internships = $internshipModel->search($term, $domain, $company, $status);
} else {
    // Afficher tous les stages ou filtrer par statut
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    
    // Utiliser la méthode index du contrôleur ou directement depuis l'instance du modèle
    $internshipModel = new Internship($db);
    $internships = $internshipModel->getAll($status);
}

// Récupérer les statistiques
$totalInternships = count($internships);

// Calculer les statistiques par statut
$availableCount = 0;
$assignedCount = 0;
$completedCount = 0;

foreach ($internships as $internship) {
    if ($internship['status'] === 'available') {
        $availableCount++;
    } elseif ($internship['status'] === 'assigned') {
        $assignedCount++;
    } elseif ($internship['status'] === 'completed') {
        $completedCount++;
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
    $content = __DIR__ . '/internships-content.php';
    include_once __DIR__ . '/../../../templates/layouts/admin.php';
} else if (file_exists(__DIR__ . '/internships-content-tailwind.php')) {
    // Layout avec Tailwind sans admin.php
    include_once __DIR__ . '/../../common/header.php';
    include_once __DIR__ . '/internships-content-tailwind.php';
    include_once __DIR__ . '/../../common/footer.php';
} else {
    // Ancien layout avec Bootstrap
    include_once __DIR__ . '/../../common/header.php';
    include_once __DIR__ . '/internships-content-bootstrap.php';
    include_once __DIR__ . '/../../common/footer.php';
}