<?php
/**
 * Script amélioré pour la recherche de stages
 * Ce script permet de rechercher des stages et de les ajouter aux préférences
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../includes/init.php';

// Vérifier que l'utilisateur est connecté et a le rôle étudiant
requireRole('student');

// Récupérer l'ID de l'étudiant
$user_id = $_SESSION['user_id'] ?? ($_SESSION['user']['id'] ?? null);
$student_id = null;

if ($user_id) {
    $studentModel = new Student($db);
    $student = $studentModel->getByUserId($user_id);
    
    if ($student) {
        $student_id = $student['id'];
    }
}

// Traitement de la recherche
$searchTerm = isset($_GET['term']) ? trim($_GET['term']) : '';
$searchResults = [];
$totalResults = 0;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$resultsPerPage = 12; // Nombre de résultats par page
$offset = ($currentPage - 1) * $resultsPerPage;

// Paramètres de filtrage
$domain = isset($_GET['domain']) ? trim($_GET['domain']) : '';
$location = isset($_GET['location']) ? trim($_GET['location']) : '';
$workMode = isset($_GET['work_mode']) ? trim($_GET['work_mode']) : '';
$hidePreferred = isset($_GET['hide_preferred']) && $_GET['hide_preferred'] == '1';

// Construire l'array de filtres
$filters = ['status' => 'available'];
if (!empty($domain)) $filters['domain'] = $domain;
if (!empty($location)) $filters['location'] = $location;
if (!empty($workMode)) $filters['work_mode'] = $workMode;

if (!empty($searchTerm) || isset($_GET['show_all']) || !empty($domain) || !empty($location) || !empty($workMode)) {
    $internshipModel = new Internship($db);
    
    try {
        if (isset($_GET['show_all'])) {
            // Récupérer le nombre total de résultats pour la pagination
            $totalResults = $internshipModel->countAll(['status' => 'available']);
            
            // Récupérer les stages pour la page actuelle avec pagination
            $searchResults = $internshipModel->getAll('available');
            // Appliquer la pagination manuellement si nécessaire
            if (count($searchResults) > $resultsPerPage) {
                $searchResults = array_slice($searchResults, $offset, $resultsPerPage);
            }
            
            // Si la méthode getAll ne fonctionne pas correctement
            if (empty($searchResults)) {
                // Essayer avec getAvailable
                $searchResults = $internshipModel->getAvailable();
                $totalResults = count($searchResults);
                
                // Appliquer la pagination manuellement
                if (count($searchResults) > $resultsPerPage) {
                    $searchResults = array_slice($searchResults, $offset, $resultsPerPage);
                }
                
                // Dernière tentative avec getAvailableForStudent si disponible
                if (empty($searchResults) && $student_id) {
                    $searchResults = $internshipModel->getAvailableForStudent($student_id);
                    $totalResults = count($searchResults);
                    
                    // Appliquer la pagination manuellement
                    if (count($searchResults) > $resultsPerPage) {
                        $searchResults = array_slice($searchResults, $offset, $resultsPerPage);
                    }
                }
            }
        } else {
            // Si le terme de recherche est court (moins de 3 caractères), 
            // utiliser une recherche plus restrictive pour éviter trop de résultats non pertinents
            if (!empty($searchTerm) && strlen($searchTerm) < 3 && !isset($_GET['domain']) && !isset($_GET['location']) && !isset($_GET['work_mode'])) {
                // Pour les recherches courtes, n'effectuer la recherche que sur les titres et les domaines
                $specialFilters = $filters;
                $specialFilters['title_prefix'] = $searchTerm; // Recherche par préfixe dans le titre uniquement
                
                // Compter le total de résultats
                $totalResults = $internshipModel->countSearch($searchTerm, 'available', $specialFilters);
                
                // Récupérer les résultats avec pagination
                $searchResults = $internshipModel->search($searchTerm, 'available', $specialFilters, $resultsPerPage, $offset);
                
                // Si aucun résultat, tenter avec getAvailable et filtrer manuellement (fallback)
                if (empty($searchResults)) {
                    $allInternships = $internshipModel->getAvailable();
                    $searchResults = array_filter($allInternships, function($internship) use ($searchTerm) {
                        return stripos($internship['title'], $searchTerm) !== false || 
                               stripos($internship['domain'], $searchTerm) !== false ||
                               stripos($internship['company_name'], $searchTerm) !== false;
                    });
                    $totalResults = count($searchResults);
                    
                    // Appliquer la pagination manuellement
                    if (count($searchResults) > $resultsPerPage) {
                        $searchResults = array_slice($searchResults, $offset, $resultsPerPage);
                    }
                }
            } else {
                // Recherche standard avec terme de recherche
                // Compter le total de résultats
                $totalResults = $internshipModel->countSearch($searchTerm, 'available', $filters);
                
                // Récupérer les résultats avec pagination
                $searchResults = $internshipModel->search($searchTerm, 'available', $filters, $resultsPerPage, $offset);
            }
        }
        
        error_log("Search for '$searchTerm' found $totalResults total results, showing " . count($searchResults) . " results on page $currentPage");
        
        // Récupérer les préférences actuelles de l'étudiant pour le filtrage
        if ($hidePreferred && $student_id) {
            $studentModel = new Student($db);
            $currentPreferencesForFilter = $studentModel->getPreferences($student_id);
            $preferredIds = array_column($currentPreferencesForFilter, 'internship_id');
            
            // Filtrer les résultats pour ne pas inclure les stages déjà dans les préférences
            if (!empty($preferredIds)) {
                $searchResults = array_filter($searchResults, function($internship) use ($preferredIds) {
                    return !in_array($internship['id'], $preferredIds);
                });
                
                // Mettre à jour le total après filtrage
                $totalResults = count($searchResults);
                error_log("After filtering out preferred internships, showing " . count($searchResults) . " results");
            }
        }
    } catch (Exception $e) {
        error_log("Search error: " . $e->getMessage());
        $searchResults = [];
        $totalResults = 0;
        setFlashMessage('error', 'Une erreur est survenue lors de la recherche de stages.');
    }
}

// Récupérer les préférences actuelles de l'étudiant
$currentPreferences = [];
if ($student_id) {
    $studentModel = new Student($db);
    $currentPreferences = $studentModel->getPreferences($student_id);
}

// Fonction pour vérifier si un stage est déjà dans les préférences
function isInPreferences($internshipId, $preferences) {
    foreach ($preferences as $preference) {
        if ($preference['internship_id'] == $internshipId) {
            return true;
        }
    }
    return false;
}

// Traitement de l'ajout aux préférences
if (isset($_GET['add']) && !empty($_GET['add']) && $student_id) {
    $internshipId = (int)$_GET['add'];
    
    // Vérifier que le stage existe
    $internshipModel = new Internship($db);
    $internship = $internshipModel->getById($internshipId);
    
    // Vérifier si l'étudiant a déjà atteint le maximum de 5 préférences
    if (count($currentPreferences) >= 5) {
        setFlashMessage('warning', 'Vous avez atteint le nombre maximum de préférences (5). Veuillez supprimer une préférence avant d\'en ajouter une nouvelle.');
    }
    else if ($internship && !isInPreferences($internshipId, $currentPreferences)) {
        // Déterminer l'ordre de préférence (dernier + 1)
        $preferenceOrder = 1;
        
        if (!empty($currentPreferences)) {
            $maxOrder = 0;
            foreach ($currentPreferences as $pref) {
                if (isset($pref['preference_order']) && $pref['preference_order'] > $maxOrder) {
                    $maxOrder = $pref['preference_order'];
                }
            }
            $preferenceOrder = $maxOrder + 1;
        }
        
        // Ajouter la préférence
        $success = $studentModel->addPreference($student_id, $internshipId, $preferenceOrder);
        
        if ($success) {
            setFlashMessage('success', 'Stage ajouté à vos préférences avec succès.');
        } else {
            setFlashMessage('error', 'Erreur lors de l\'ajout du stage à vos préférences.');
        }
    } else if (isInPreferences($internshipId, $currentPreferences)) {
        setFlashMessage('info', 'Ce stage est déjà dans vos préférences.');
    } else {
        setFlashMessage('error', 'Stage non trouvé ou non disponible.');
    }
    
    // Recharger les préférences
    $currentPreferences = $studentModel->getPreferences($student_id);
}

// Titre de la page
$pageTitle = 'Recherche de stages';
$currentPage = 'preferences';

// CSS personnalisé pour les améliorations d'interface
$customCSS = "
<style>
    /* Hover effect for internship cards */
    .hover-shadow {
        transition: all 0.3s ease;
    }
    
    .hover-shadow:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
    }
    
    /* Better badge styling */
    .badge {
        padding: 0.5em 0.75em;
        font-weight: 500;
    }
    
    /* Highlight matched search terms */
    .border-primary {
        border-width: 2px !important;
    }
    
    /* Quick filter badges */
    .badge.bg-light {
        border: 1px solid #dee2e6;
        transition: all 0.2s ease;
    }
    
    .badge.bg-light:hover {
        background-color: #e9ecef !important;
        border-color: #adb5bd;
    }
    
    /* Improve mobile spacing */
    @media (max-width: 767.98px) {
        .card-header, .card-body, .card-footer {
            padding: 0.75rem;
        }
    }
    
    /* Animations */
    .fade-in {
        animation: fadeIn 0.5s ease-in-out;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>
";

// Inclure l'en-tête
include_once __DIR__ . '/../common/header.php';

// Ajouter le CSS personnalisé
echo $customCSS;
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-search me-2"></i>Recherche de stages</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/tutoring/views/student/dashboard.php">Tableau de bord</a></li>
                    <li class="breadcrumb-item"><a href="/tutoring/views/student/preferences.php">Préférences</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Recherche</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <!-- Main content -->
    <div class="row">
        <!-- Left Column - Search and Results -->
        <div class="col-lg-8">
            <!-- Search Box -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Rechercher un stage</h5>
                </div>
                <div class="card-body">
                    <form action="" method="GET" class="mb-3">
                        <div class="input-group">
                            <input type="text" class="form-control" name="term" value="<?= htmlspecialchars($searchTerm) ?>" placeholder="Titre, domaine, entreprise..." aria-label="Terme de recherche" autocomplete="off" autofocus>
                            <button class="btn btn-primary" type="submit">
                                <i class="bi bi-search me-1"></i> Rechercher
                            </button>
                        </div>
                        <div class="form-text small text-muted">
                            <i class="bi bi-info-circle me-1"></i>Vous pouvez rechercher par titre de stage, domaine, compétence ou entreprise. Minimum 3 caractères pour une recherche précise.
                        </div>
                        
                        <!-- Quick filters -->
                        <div class="mt-3">
                            <div class="d-flex flex-wrap gap-2">
                                <?php 
                                // Liste de domaines courants pour les filtres rapides
                                $quickDomains = ['Informatique', 'Marketing', 'Finance', 'Communication', 'Ressources Humaines'];
                                foreach($quickDomains as $domain): 
                                ?>
                                <a href="?term=<?= urlencode($domain) ?>" class="badge bg-light text-dark text-decoration-none p-2">
                                    <?= htmlspecialchars($domain) ?>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </form>
                    
                    <!-- Filtres avancés -->
                    <div class="mb-3 border rounded p-3 bg-light">
                        <h6 class="mb-3"><i class="bi bi-funnel me-1"></i>Filtres avancés</h6>
                        <form action="" method="GET" class="row g-2">
                            <?php if (!empty($searchTerm)): ?>
                            <input type="hidden" name="term" value="<?= htmlspecialchars($searchTerm) ?>">
                            <?php endif; ?>
                            
                            <?php if (isset($_GET['show_all'])): ?>
                            <input type="hidden" name="show_all" value="1">
                            <?php endif; ?>
                            
                            <!-- Domaine -->
                            <div class="col-md-4">
                                <label for="domain" class="form-label small">Domaine</label>
                                <select name="domain" id="domain" class="form-select form-select-sm">
                                    <option value="">Tous les domaines</option>
                                    <?php 
                                    // Récupérer les domaines uniques
                                    $internshipModel = new Internship($db);
                                    $domains = $internshipModel->getDomains();
                                    foreach ($domains as $d): 
                                    ?>
                                    <option value="<?= htmlspecialchars($d) ?>" <?= $domain === $d ? 'selected' : '' ?>><?= htmlspecialchars($d) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Localisation -->
                            <div class="col-md-4">
                                <label for="location" class="form-label small">Localisation</label>
                                <input type="text" class="form-control form-control-sm" id="location" name="location" value="<?= htmlspecialchars($location) ?>" placeholder="Ville, pays...">
                            </div>
                            
                            <!-- Mode de travail -->
                            <div class="col-md-4">
                                <label for="work_mode" class="form-label small">Mode de travail</label>
                                <select name="work_mode" id="work_mode" class="form-select form-select-sm">
                                    <option value="">Tous les modes</option>
                                    <option value="Présentiel" <?= $workMode === 'Présentiel' ? 'selected' : '' ?>>Présentiel</option>
                                    <option value="Télétravail" <?= $workMode === 'Télétravail' ? 'selected' : '' ?>>Télétravail</option>
                                    <option value="Hybride" <?= $workMode === 'Hybride' ? 'selected' : '' ?>>Hybride</option>
                                </select>
                            </div>
                            
                            <!-- Option pour masquer les stages déjà dans les préférences -->
                            <div class="col-12 mt-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="hide_preferred" name="hide_preferred" value="1" <?= $hidePreferred ? 'checked' : '' ?>>
                                    <label class="form-check-label small" for="hide_preferred">
                                        Masquer les stages déjà dans mes préférences
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Boutons d'action -->
                            <div class="col-12 mt-3">
                                <div class="d-flex justify-content-between">
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <i class="bi bi-filter me-1"></i>Appliquer les filtres
                                    </button>
                                    <?php if (!empty($domain) || !empty($location) || !empty($workMode) || $hidePreferred): ?>
                                    <a href="<?= !empty($searchTerm) ? '?term=' . urlencode($searchTerm) : (isset($_GET['show_all']) ? '?show_all=1' : '') ?>" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-x-circle me-1"></i>Réinitialiser
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <a href="?show_all=1" class="btn btn-light btn-sm">
                            <i class="bi bi-grid-3x3-gap-fill me-1"></i>Tous les stages
                        </a>
                        <a href="/tutoring/views/student/preferences.php" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-arrow-left me-1"></i>Retour aux préférences
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Search Results -->
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Résultats de recherche</h5>
                    <span class="badge bg-primary"><?= count($searchResults) ?> stages trouvés</span>
                </div>
                <div class="card-body">
                    <?php if (empty($searchResults)): ?>
                        <?php if (!empty($searchTerm) || isset($_GET['show_all'])): ?>
                            <div class="alert alert-info">
                                <div class="d-flex">
                                    <div class="me-3">
                                        <i class="bi bi-search fs-3"></i>
                                    </div>
                                    <div>
                                        <h5 class="alert-heading">Aucun stage trouvé</h5>
                                        <p>Aucun stage ne correspond à votre recherche<?= !empty($searchTerm) ? ' "<strong>' . htmlspecialchars($searchTerm) . '</strong>"' : '' ?>.</p>
                                        <hr>
                                        <p class="mb-0">Suggestions :</p>
                                        <ul class="mb-0">
                                            <li>Vérifiez l'orthographe des termes de recherche</li>
                                            <li>Essayez des mots-clés plus généraux</li>
                                            <li>Utilisez un des filtres rapides ci-dessus</li>
                                            <li><a href="?show_all=1" class="alert-link">Affichez tous les stages disponibles</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <div class="d-flex">
                                    <div class="me-3">
                                        <i class="bi bi-lightbulb fs-3"></i>
                                    </div>
                                    <div>
                                        <h5 class="alert-heading">Commencez votre recherche</h5>
                                        <p>Utilisez le champ de recherche ci-dessus pour trouver des stages qui correspondent à vos intérêts et compétences.</p>
                                        <p class="mb-0">Vous pouvez aussi <a href="?show_all=1" class="alert-link">afficher tous les stages disponibles</a>.</p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="row g-3">
                            <?php foreach ($searchResults as $internship): ?>
                                <?php 
                                    // Vérifier si le stage est déjà dans les préférences
                                    $isPreferred = isInPreferences($internship['id'], $currentPreferences);
                                    
                                    // Définir une classe pour la mise en évidence des stages correspondant à la recherche
                                    $highlightClass = '';
                                    if (!empty($searchTerm) && stripos($internship['title'], $searchTerm) !== false) {
                                        $highlightClass = 'border-primary';
                                    }
                                ?>
                                <div class="col-md-6 col-lg-6 col-xl-4 mb-4">
                                    <div class="card h-100 shadow-sm <?= $highlightClass ?> hover-shadow">
                                        <div class="card-header bg-light py-2 px-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="badge bg-primary"><?= htmlspecialchars($internship['domain']) ?></span>
                                                <small class="text-muted"><?= htmlspecialchars($internship['work_mode']) ?></small>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <h5 class="card-title"><?= htmlspecialchars($internship['title']) ?></h5>
                                            <h6 class="card-subtitle mb-3 d-flex align-items-center">
                                                <?php if (!empty($internship['company_logo'])): ?>
                                                <img src="<?= htmlspecialchars($internship['company_logo']) ?>" alt="Logo" class="me-2" style="height: 20px; width: auto;">
                                                <?php else: ?>
                                                <i class="bi bi-building me-2"></i>
                                                <?php endif; ?>
                                                <?= htmlspecialchars($internship['company_name']) ?>
                                            </h6>
                                            
                                            <div class="mb-3">
                                                <span class="badge bg-light text-dark border me-1"><?= htmlspecialchars($internship['location']) ?></span>
                                                <?php 
                                                // Afficher les compétences si disponibles
                                                if (!empty($internship['skills']) && is_array($internship['skills'])):
                                                    $skills = array_slice($internship['skills'], 0, 3); // Limiter à 3 compétences
                                                    foreach ($skills as $skill):
                                                ?>
                                                <span class="badge bg-light text-dark border me-1"><?= htmlspecialchars($skill) ?></span>
                                                <?php 
                                                    endforeach;
                                                    // Indiquer s'il y a d'autres compétences
                                                    if (count($internship['skills']) > 3):
                                                ?>
                                                <span class="badge bg-light text-dark border">+<?= count($internship['skills']) - 3 ?></span>
                                                <?php 
                                                    endif;
                                                endif;
                                                ?>
                                            </div>
                                            
                                            <p class="card-text small mb-3">
                                                <?= substr(htmlspecialchars($internship['description']), 0, 120) . (strlen($internship['description']) > 120 ? '...' : '') ?>
                                            </p>
                                            
                                            <div class="d-flex align-items-center mb-3 small">
                                                <i class="bi bi-calendar-event me-1 text-primary"></i>
                                                <span>Du <?= date('d/m/Y', strtotime($internship['start_date'])) ?> au <?= date('d/m/Y', strtotime($internship['end_date'])) ?></span>
                                            </div>
                                            
                                            <div class="d-grid">
                                                <?php if ($isPreferred): ?>
                                                    <button class="btn btn-success btn-sm" disabled>
                                                        <i class="bi bi-check-circle me-1"></i>Dans vos préférences
                                                    </button>
                                                <?php elseif (count($currentPreferences) >= 5): ?>
                                                    <button class="btn btn-secondary btn-sm" disabled>
                                                        <i class="bi bi-exclamation-triangle me-1"></i>Maximum atteint (5)
                                                    </button>
                                                <?php else: ?>
                                                    <a href="?term=<?= urlencode($searchTerm) ?>&add=<?= $internship['id'] ?><?= isset($_GET['show_all']) ? '&show_all=1' : '' ?>" class="btn btn-outline-primary btn-sm">
                                                        <i class="bi bi-plus-circle me-1"></i>Ajouter aux préférences
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="card-footer bg-white text-end py-2 small">
                                            <span class="text-muted">Compensation: <?= htmlspecialchars($internship['compensation'] ?: 'Non spécifiée') ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Pagination -->
                    <?php if ($totalResults > $resultsPerPage): ?>
                    <div class="mt-4">
                        <nav aria-label="Navigation des résultats">
                            <ul class="pagination justify-content-center">
                                <?php
                                $totalPages = ceil($totalResults / $resultsPerPage);
                                $paginationRange = 2; // Nombre de pages à afficher avant et après la page courante
                                
                                // Construire les paramètres de l'URL pour la pagination
                                $queryParams = [];
                                if (!empty($searchTerm)) $queryParams['term'] = $searchTerm;
                                if (isset($_GET['show_all'])) $queryParams['show_all'] = '1';
                                if (!empty($domain)) $queryParams['domain'] = $domain;
                                if (!empty($location)) $queryParams['location'] = $location;
                                if (!empty($workMode)) $queryParams['work_mode'] = $workMode;
                                
                                // Fonction pour générer l'URL avec les paramètres
                                $buildUrl = function($page) use ($queryParams) {
                                    $params = $queryParams;
                                    $params['page'] = $page;
                                    return '?' . http_build_query($params);
                                };
                                
                                // Bouton précédent
                                if ($currentPage > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?= $buildUrl($currentPage - 1) ?>" aria-label="Précédent">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                                <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link">&laquo;</span>
                                </li>
                                <?php endif; ?>
                                
                                <!-- Première page -->
                                <?php if ($currentPage > $paginationRange + 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?= $buildUrl(1) ?>">1</a>
                                </li>
                                <?php if ($currentPage > $paginationRange + 2): ?>
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                                <?php endif; ?>
                                <?php endif; ?>
                                
                                <!-- Pages autour de la page courante -->
                                <?php 
                                $startPage = max(1, $currentPage - $paginationRange);
                                $endPage = min($totalPages, $currentPage + $paginationRange);
                                
                                for ($i = $startPage; $i <= $endPage; $i++): 
                                ?>
                                <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
                                    <a class="page-link" href="<?= $buildUrl($i) ?>"><?= $i ?></a>
                                </li>
                                <?php endfor; ?>
                                
                                <!-- Dernière page -->
                                <?php if ($currentPage < $totalPages - $paginationRange): ?>
                                <?php if ($currentPage < $totalPages - $paginationRange - 1): ?>
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                                <?php endif; ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?= $buildUrl($totalPages) ?>"><?= $totalPages ?></a>
                                </li>
                                <?php endif; ?>
                                
                                <!-- Bouton suivant -->
                                <?php if ($currentPage < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?= $buildUrl($currentPage + 1) ?>" aria-label="Suivant">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                                <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link">&raquo;</span>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                        <div class="text-center small text-muted mt-2">
                            Affichage des résultats <?= $offset + 1 ?>-<?= min($offset + $resultsPerPage, $totalResults) ?> sur <?= $totalResults ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Right Column - Current Preferences and Help -->
        <div class="col-lg-4">
            <!-- Current Preferences Summary -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Vos préférences actuelles</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($currentPreferences)): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>Vous n'avez pas encore défini de préférences.
                        </div>
                    <?php else: ?>
                        <p class="mb-3">Vous avez défini <strong><?= count($currentPreferences) ?></strong> préférence(s) sur un maximum de 5.</p>
                        <div class="list-group">
                            <?php foreach ($currentPreferences as $index => $preference): ?>
                                <div class="list-group-item list-group-item-action d-flex align-items-center">
                                    <span class="badge bg-primary rounded-circle me-3"><?= $preference['preference_order'] ?></span>
                                    <div>
                                        <h6 class="mb-0"><?= htmlspecialchars($preference['title']) ?></h6>
                                        <small class="text-muted"><?= htmlspecialchars($preference['company_name']) ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="d-grid mt-3">
                            <a href="/tutoring/views/student/preferences.php" class="btn btn-primary">
                                <i class="bi bi-gear me-1"></i>Gérer mes préférences
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Help Section -->
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-question-circle me-1"></i>Aide</h5>
                </div>
                <div class="card-body">
                    <h6>Comment choisir mes stages préférés ?</h6>
                    <ol class="small">
                        <li>Recherchez des stages qui correspondent à vos intérêts</li>
                        <li>Cliquez sur "Ajouter aux préférences" pour les ajouter à votre liste</li>
                        <li>Retournez à la page "Préférences" pour les ordonner selon votre priorité</li>
                        <li>Vous pouvez sélectionner jusqu'à 5 stages préférés</li>
                    </ol>
                    <h6>Conseils</h6>
                    <ul class="small">
                        <li>Essayez différents termes de recherche pour trouver plus de stages</li>
                        <li>Consultez la description complète avant de faire votre choix</li>
                        <li>Vérifiez les dates pour vous assurer de votre disponibilité</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Inclure le pied de page
include_once __DIR__ . '/../common/footer.php';
?>