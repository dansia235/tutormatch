<?php
/**
 * Page de démonstration de recherche de stages améliorée
 */

// Inclure les fichiers nécessaires
require_once __DIR__ . '/includes/init.php';

// Récupérer les paramètres de recherche
$term = isset($_GET['term']) ? trim($_GET['term']) : '';
$status = isset($_GET['status']) ? $_GET['status'] : 'available';

// Récupérer les paramètres de filtrage
$filters = [];
if (isset($_GET['domain']) && !empty($_GET['domain'])) {
    $filters['domain'] = $_GET['domain'];
}
if (isset($_GET['location']) && !empty($_GET['location'])) {
    $filters['location'] = $_GET['location'];
}
if (isset($_GET['work_mode']) && !empty($_GET['work_mode'])) {
    $filters['work_mode'] = $_GET['work_mode'];
}
if (isset($_GET['skills']) && !empty($_GET['skills'])) {
    $filters['skills'] = is_array($_GET['skills']) ? $_GET['skills'] : [$_GET['skills']];
}
if (isset($_GET['start_date_from']) || isset($_GET['start_date_to'])) {
    $filters['start_date'] = [];
    if (isset($_GET['start_date_from']) && !empty($_GET['start_date_from'])) {
        $filters['start_date']['from'] = $_GET['start_date_from'];
    }
    if (isset($_GET['start_date_to']) && !empty($_GET['start_date_to'])) {
        $filters['start_date']['to'] = $_GET['start_date_to'];
    }
}

// Paramètres de pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Initialiser les modèles
$internshipModel = new Internship($db);
$searchResults = [];
$totalResults = 0;
$totalPages = 1;

// Récupérer les données pour les filtres
$domains = $internshipModel->getDomains();
$skills = $internshipModel->getAllSkills();

// Effectuer la recherche si un terme est fourni ou si des filtres sont appliqués
if (!empty($term) || !empty($filters)) {
    // Ajouter le terme dans les filtres pour le formulaire
    $filters['term'] = $term;
    
    // Rechercher les stages
    $searchResults = $internshipModel->search($term, $status, $filters, $limit, $offset);
    $totalResults = $internshipModel->countSearch($term, $status, $filters);
    $totalPages = ceil($totalResults / $limit);
}

// Titre de la page
$pageTitle = 'Recherche de stages';

// Inclure l'en-tête
include_once __DIR__ . '/views/common/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-primary-800">Recherche de stages</h1>
            <p class="text-gray-600">Trouvez le stage idéal pour votre formation</p>
        </div>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <!-- Filtres et recherche -->
        <div class="md:col-span-1 space-y-6">
            <!-- Barre de recherche -->
            <?php 
            include_once __DIR__ . '/components/filters/search-box.php';
            ?>
            
            <!-- Filtres avancés -->
            <?php 
            include __DIR__ . '/components/filters/internship-filter.php';
            ?>
        </div>
        
        <!-- Résultats de recherche -->
        <div class="md:col-span-3">
            <div class="card">
                <div class="card-header">
                    <?php if (!empty($term) || !empty(array_filter($filters, function($key) { return $key !== 'term'; }, ARRAY_FILTER_USE_KEY))): ?>
                        <h2 class="text-lg font-semibold">
                            <?php echo $totalResults; ?> résultat<?php echo $totalResults !== 1 ? 's' : ''; ?> trouvé<?php echo $totalResults !== 1 ? 's' : ''; ?>
                            <?php if (!empty($term)): ?>
                                pour "<span class="text-primary-600"><?php echo htmlspecialchars($term); ?></span>"
                            <?php endif; ?>
                        </h2>
                    <?php else: ?>
                        <h2 class="text-lg font-semibold">Tous les stages disponibles</h2>
                    <?php endif; ?>
                </div>
                
                <div class="card-body p-0">
                    <?php if (empty($searchResults)): ?>
                        <div class="py-6 px-4 text-center text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="text-lg font-medium mb-1">Aucun stage trouvé</p>
                            <p>Essayez d'autres termes de recherche ou de modifier vos filtres.</p>
                        </div>
                    <?php else: ?>
                        <div class="divide-y divide-gray-200">
                            <?php foreach ($searchResults as $internship): ?>
                                <div class="p-4 hover:bg-gray-50 transition-colors">
                                    <div class="flex justify-between">
                                        <div>
                                            <h3 class="text-lg font-medium text-primary-800">
                                                <a href="/tutoring/views/student/internship.php?id=<?php echo $internship['id']; ?>" class="hover:underline">
                                                    <?php echo htmlspecialchars($internship['title']); ?>
                                                </a>
                                            </h3>
                                            <p class="text-gray-600">
                                                <?php echo htmlspecialchars($internship['company_name']); ?> • 
                                                <?php echo htmlspecialchars($internship['location']); ?> • 
                                                <?php echo htmlspecialchars($internship['work_mode']); ?>
                                            </p>
                                        </div>
                                        <?php if (!empty($internship['company_logo'])): ?>
                                            <div class="flex-shrink-0">
                                                <img src="<?php echo htmlspecialchars($internship['company_logo']); ?>" alt="<?php echo htmlspecialchars($internship['company_name']); ?>" class="h-12 w-12 object-contain">
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="mt-2">
                                        <p class="text-sm text-gray-500 line-clamp-2"><?php echo htmlspecialchars(substr($internship['description'], 0, 150)) . (strlen($internship['description']) > 150 ? '...' : ''); ?></p>
                                    </div>
                                    
                                    <?php if (!empty($internship['skills'])): ?>
                                        <div class="mt-3 flex flex-wrap gap-1">
                                            <?php foreach ($internship['skills'] as $skill): ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800">
                                                    <?php echo htmlspecialchars($skill); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="mt-3 flex justify-between items-center">
                                        <div class="text-sm text-gray-500">
                                            <span>Du <?php echo date('d/m/Y', strtotime($internship['start_date'])); ?> au <?php echo date('d/m/Y', strtotime($internship['end_date'])); ?></span>
                                        </div>
                                        
                                        <div>
                                            <a href="/tutoring/views/student/internship.php?id=<?php echo $internship['id']; ?>" class="btn btn-sm btn-primary">
                                                Voir le détail
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($totalPages > 1): ?>
                    <div class="card-footer bg-gray-50 px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Affichage de <span class="font-medium"><?php echo ($page - 1) * $limit + 1; ?></span> à 
                                    <span class="font-medium"><?php echo min($page * $limit, $totalResults); ?></span> sur 
                                    <span class="font-medium"><?php echo $totalResults; ?></span> résultats
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                    <?php if ($page > 1): ?>
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                            <span class="sr-only">Précédent</span>
                                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                            </svg>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php
                                    // Afficher un nombre limité de liens de page
                                    $startPage = max(1, $page - 2);
                                    $endPage = min($totalPages, $page + 2);
                                    
                                    // Ajouter la première page si nécessaire
                                    if ($startPage > 1) {
                                        echo '<a href="?' . http_build_query(array_merge($_GET, ['page' => 1])) . '" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">1</a>';
                                        if ($startPage > 2) {
                                            echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                                        }
                                    }
                                    
                                    // Afficher les pages
                                    for ($i = $startPage; $i <= $endPage; $i++) {
                                        $isCurrentPage = $i === $page;
                                        $classes = $isCurrentPage 
                                            ? 'z-10 bg-primary-50 border-primary-500 text-primary-600 relative inline-flex items-center px-4 py-2 border text-sm font-medium' 
                                            : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50 relative inline-flex items-center px-4 py-2 border text-sm font-medium';
                                        
                                        echo '<a href="?' . http_build_query(array_merge($_GET, ['page' => $i])) . '" class="' . $classes . '">' . $i . '</a>';
                                    }
                                    
                                    // Ajouter la dernière page si nécessaire
                                    if ($endPage < $totalPages) {
                                        if ($endPage < $totalPages - 1) {
                                            echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                                        }
                                        echo '<a href="?' . http_build_query(array_merge($_GET, ['page' => $totalPages])) . '" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">' . $totalPages . '</a>';
                                    }
                                    ?>
                                    
                                    <?php if ($page < $totalPages): ?>
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                            <span class="sr-only">Suivant</span>
                                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                            </svg>
                                        </a>
                                    <?php endif; ?>
                                </nav>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Inclure le pied de page
include_once __DIR__ . '/views/common/footer.php';
?>