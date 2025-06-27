<?php
/**
 * Vue pour la liste des entreprises
 */

// Initialiser les variables
$pageTitle = 'Gestion des entreprises';
$currentPage = 'companies';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../includes/init.php';

// Vérifier les permissions
requireRole(['admin', 'coordinator']);

// Configuration de la pagination
$itemsPerPage = isset($_GET['per_page']) ? max(10, min(100, (int)$_GET['per_page'])) : 20; // 20 éléments par page par défaut
$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

// Traitement de la recherche
$search = isset($_GET['term']) ? trim($_GET['term']) : '';
$searchCondition = '';
$searchParams = [];

if (!empty($search)) {
    $searchCondition = "WHERE (c.name LIKE :search OR c.address LIKE :search OR c.city LIKE :search OR c.contact_name LIKE :search OR c.contact_email LIKE :search OR c.description LIKE :search)";
    $searchParams[':search'] = '%' . $search . '%';
}

// Compter le total d'entreprises (pour la pagination)
$countQuery = "SELECT COUNT(*) as total FROM companies c $searchCondition";
$countStmt = $db->prepare($countQuery);
foreach ($searchParams as $key => $value) {
    $countStmt->bindValue($key, $value);
}
$countStmt->execute();
$totalCompanies = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

// Calculer les informations de pagination
$totalPages = ceil($totalCompanies / $itemsPerPage);
$showingFrom = $totalCompanies > 0 ? $offset + 1 : 0;
$showingTo = min($offset + $itemsPerPage, $totalCompanies);

// Récupérer toutes les entreprises pour les statistiques
$allCompaniesQuery = "SELECT c.*, 
          (SELECT COUNT(*) FROM internships WHERE company_id = c.id) as internship_count 
          FROM companies c 
          $searchCondition
          ORDER BY c.name ASC";
$allCompaniesStmt = $db->prepare($allCompaniesQuery);
foreach ($searchParams as $key => $value) {
    $allCompaniesStmt->bindValue($key, $value);
}
$allCompaniesStmt->execute();
$allCompanies = $allCompaniesStmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les entreprises pour la page courante
$query = "SELECT c.*, 
          (SELECT COUNT(*) FROM internships WHERE company_id = c.id) as internship_count 
          FROM companies c 
          $searchCondition
          ORDER BY c.name ASC 
          LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($query);
foreach ($searchParams as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les statistiques (sur toutes les entreprises, pas seulement la page courante)
$totalCompanies = count($allCompanies);
$activeCompanies = array_reduce($allCompanies, function($count, $company) {
    return $count + ($company['active'] ? 1 : 0);
}, 0);
$inactiveCompanies = $totalCompanies - $activeCompanies;
$companiesWithInternships = array_reduce($allCompanies, function($count, $company) {
    return $count + ($company['internship_count'] > 0 ? 1 : 0);
}, 0);

// Inclure l'en-tête
include_once __DIR__ . '/../common/header.php';
?>

<!-- Style spécifique pour correspondre à la maquette -->
<style>
    /* Barre de recherche */
    .search-container {
        position: relative;
        max-width: 400px;
    }
    
    .search-container .form-control {
        padding-left: 40px;
        border-radius: 50px;
        border: 1px solid #dee2e6;
        height: 45px;
        width: 100%;
        box-shadow: none;
    }
    
    .search-container .search-icon {
        position: absolute;
        left: 15px;
        top: 12px;
        color: #6c757d;
    }
    
    .search-container .btn-search {
        position: absolute;
        right: 5px;
        top: 5px;
        border-radius: 50%;
        height: 35px;
        width: 35px;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    /* Statistiques */
    .stat-card {
        background-color: white;
        border-radius: 10px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        padding: 20px;
        text-align: center;
        height: 100%;
    }
    
    .stat-card .stat-value {
        font-size: 3rem;
        font-weight: 700;
        color: #2c3e50;
        line-height: 1;
        margin-bottom: 10px;
    }
    
    .stat-card .stat-label {
        color: #7f8c8d;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    
    .stat-card .progress {
        height: 6px;
        margin-top: 15px;
    }
    
    .add-button {
        background-color: #3498db;
        border-color: #3498db;
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 500;
    }
    
    .add-button i {
        margin-right: 8px;
    }
    
    /* Info message */
    .info-message {
        background-color: #d1ecf1;
        border-radius: 8px;
        padding: 15px;
        color: #0c5460;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
    }
    
    .info-message i {
        margin-right: 10px;
        font-size: 1.2rem;
    }
    
    /* Page header */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
    }
    
    .page-header h2 {
        margin-bottom: 0;
        font-weight: 600;
    }
    
    .page-header .breadcrumb {
        margin-bottom: 0;
        margin-top: 5px;
    }
    
    /* Liste avec compteur */
    .list-header {
        display: flex;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .list-header h4 {
        margin-bottom: 0;
        margin-right: 10px;
        font-weight: 600;
    }
    
    .list-header .count-badge {
        background-color: #3498db;
        color: white;
        padding: 3px 10px;
        border-radius: 15px;
        font-size: 0.8rem;
    }
    
    /* Company cards */
    .company-card {
        position: relative;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        height: 100%;
    }
    
    .company-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .company-logo {
        width: 80px;
        height: 80px;
        object-fit: contain;
        border-radius: 10px;
        background-color: #f8f9fa;
        padding: 10px;
    }
    
    .company-logo-placeholder {
        width: 80px;
        height: 80px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        background-color: #e9ecef;
        color: #6c757d;
        font-size: 1.5rem;
    }
    
    .company-actions {
        position: absolute;
        top: 10px;
        right: 10px;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .company-card:hover .company-actions {
        opacity: 1;
    }
    
    .company-detail {
        display: flex;
        align-items: center;
        margin-bottom: 6px;
        color: #6c757d;
    }
    
    .company-detail i {
        margin-right: 8px;
        width: 16px;
        text-align: center;
    }
    
    .status-indicator {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        margin-right: 8px;
    }
    
    .status-active {
        background-color: #2ecc71;
    }
    
    .status-inactive {
        background-color: #e74c3c;
    }
    
    .internship-badge {
        position: absolute;
        top: 10px;
        left: 10px;
        z-index: 10;
    }
    
    /* Styles améliorés pour la pagination */
    .pagination .page-item.active .page-link {
        background-color: #0d6efd;
        border-color: #0d6efd;
        color: white !important;
        font-weight: 500;
        box-shadow: 0 2px 5px rgba(13, 110, 253, 0.3);
    }
    
    .pagination .page-link {
        color: #495057;
        background-color: #fff;
        border: 1px solid #dee2e6;
        transition: all 0.2s ease-in-out;
    }
    
    .pagination .page-link:hover {
        background-color: #e9ecef;
        border-color: #dee2e6;
        color: #0d6efd;
    }
    
    .pagination .page-item.disabled .page-link {
        color: #6c757d;
        pointer-events: none;
        background-color: #fff;
        border-color: #dee2e6;
    }
</style>

<div class="container-fluid mt-4">
    <!-- Titre de la page et bouton d'ajout -->
    <div class="page-header">
        <div>
            <h2><i class="bi bi-building me-2"></i>Gestion des entreprises</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/tutoring/views/admin/dashboard.php">Tableau de bord</a></li>
                    <li class="breadcrumb-item active">Entreprises</li>
                </ol>
            </nav>
        </div>
        
        <?php if (hasRole(['admin', 'coordinator'])): ?>
        <a href="/tutoring/views/admin/companies/create.php" class="btn btn-primary add-button">
            <i class="bi bi-plus-circle"></i>Ajouter une entreprise
        </a>
        <?php endif; ?>
    </div>
    
    <!-- Barre de recherche et filtres -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="search-container">
                        <form action="" method="GET">
                            <i class="bi bi-search search-icon"></i>
                            <input type="text" class="form-control" name="term" placeholder="Rechercher une entreprise..." value="<?php echo isset($_GET['term']) ? h($_GET['term']) : ''; ?>">
                            <button type="submit" name="search" class="btn btn-primary btn-search d-none">
                                <i class="bi bi-search"></i>
                            </button>
                        </form>
                    </div>
                </div>
                <div class="col-md-6 text-md-end mt-3 mt-md-0">
                    <div class="d-flex align-items-center justify-content-md-end gap-3">
                        <!-- Sélecteur du nombre d'éléments par page -->
                        <div class="d-flex align-items-center">
                            <label for="itemsPerPage" class="form-label me-2 mb-0 text-muted small">Afficher:</label>
                            <select id="itemsPerPage" class="form-select form-select-sm" style="width: auto;" onchange="changeItemsPerPage(this.value)">
                                <option value="10" <?php echo $itemsPerPage == 10 ? 'selected' : ''; ?>>10</option>
                                <option value="20" <?php echo $itemsPerPage == 20 ? 'selected' : ''; ?>>20</option>
                                <option value="50" <?php echo $itemsPerPage == 50 ? 'selected' : ''; ?>>50</option>
                                <option value="100" <?php echo $itemsPerPage == 100 ? 'selected' : ''; ?>>100</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Cartes statistiques -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-value"><?php echo $totalCompanies; ?></div>
                <div class="stat-label">Entreprises totales</div>
                <div class="progress">
                    <div class="progress-bar" role="progressbar" style="width: 100%;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-value"><?php echo $activeCompanies; ?></div>
                <div class="stat-label">Entreprises actives</div>
                <div class="progress">
                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $totalCompanies > 0 ? ($activeCompanies / $totalCompanies) * 100 : 0; ?>%;" aria-valuenow="<?php echo $activeCompanies; ?>" aria-valuemin="0" aria-valuemax="<?php echo $totalCompanies; ?>"></div>
                </div>
                <div class="small text-muted mt-2"><?php echo $totalCompanies > 0 ? number_format(($activeCompanies / $totalCompanies) * 100, 0) : 0; ?>% des entreprises</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-value"><?php echo $inactiveCompanies; ?></div>
                <div class="stat-label">Entreprises inactives</div>
                <div class="progress">
                    <div class="progress-bar bg-danger" role="progressbar" style="width: <?php echo $totalCompanies > 0 ? ($inactiveCompanies / $totalCompanies) * 100 : 0; ?>%;" aria-valuenow="<?php echo $inactiveCompanies; ?>" aria-valuemin="0" aria-valuemax="<?php echo $totalCompanies; ?>"></div>
                </div>
                <div class="small text-muted mt-2"><?php echo $totalCompanies > 0 ? number_format(($inactiveCompanies / $totalCompanies) * 100, 0) : 0; ?>% des entreprises</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-value"><?php echo $companiesWithInternships; ?></div>
                <div class="stat-label">Avec stages</div>
                <div class="progress">
                    <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $totalCompanies > 0 ? ($companiesWithInternships / $totalCompanies) * 100 : 0; ?>%;" aria-valuenow="<?php echo $companiesWithInternships; ?>" aria-valuemin="0" aria-valuemax="<?php echo $totalCompanies; ?>"></div>
                </div>
                <div class="small text-muted mt-2"><?php echo $totalCompanies > 0 ? number_format(($companiesWithInternships / $totalCompanies) * 100, 0) : 0; ?>% des entreprises</div>
            </div>
        </div>
    </div>
    
    <!-- Liste des entreprises -->
    <div class="card">
        <div class="card-body p-4">
            <div class="list-header">
                <h4><i class="bi bi-grid-3x3-gap me-2"></i>Liste des entreprises</h4>
                <span class="count-badge">
                    <?php if ($totalCompanies > 0): ?>
                        <?php echo $showingFrom; ?>-<?php echo $showingTo; ?> sur <?php echo $totalCompanies; ?> entreprises
                    <?php else: ?>
                        0 entreprises
                    <?php endif; ?>
                </span>
            </div>
            
            <!-- Info sur les filtres actifs -->
            <?php if (!empty($search)): ?>
            <div class="alert alert-info mb-4">
                <i class="bi bi-info-circle me-2"></i>
                <span>
                    Affichage des résultats pour la recherche: <strong>"<?php echo h($search); ?>"</strong>
                    (<?php echo $totalCompanies; ?> entreprises trouvées)
                </span>
                <a href="?" class="ms-2 text-decoration-none">Réinitialiser la recherche</a>
            </div>
            <?php endif; ?>
            
            <?php if (empty($companies)): ?>
            <div class="info-message">
                <i class="bi bi-info-circle"></i>
                <span>Aucune entreprise trouvée.</span>
            </div>
            <?php else: ?>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 g-4" id="companies-container">
                <?php foreach ($companies as $company): ?>
                <div class="col company-item">
                    <div class="card company-card h-100">
                        <?php if ($company['internship_count'] > 0): ?>
                        <div class="internship-badge">
                            <span class="badge bg-info"><?php echo $company['internship_count']; ?> stage<?php echo $company['internship_count'] > 1 ? 's' : ''; ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="card-body">
                            <div class="company-actions">
                                <div class="btn-group">
                                    <a href="/tutoring/views/admin/companies/show.php?id=<?php echo $company['id']; ?>" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Voir les détails">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="/tutoring/views/admin/companies/edit.php?id=<?php echo $company['id']; ?>" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Modifier">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="tooltip" title="Supprimer" onclick="openDeleteModal(<?php echo $company['id']; ?>, '<?php echo h(addslashes($company['name'])); ?>');">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="d-flex mb-3">
                                <div class="company-logo-placeholder me-3" style="background-color: <?php echo generateAvatarColor($company['name']); ?>; color: white; display: flex; align-items: center; justify-content: center;">
                                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M19 4H5C3.89543 4 3 4.89543 3 6V18C3 19.1046 3.89543 20 5 20H19C20.1046 20 21 19.1046 21 18V6C21 4.89543 20.1046 4 19 4Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M3 8H21" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M9 20V8" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                
                                <div>
                                    <h5 class="card-title mb-1"><?php echo h($company['name']); ?></h5>
                                    <p class="card-text mb-2">
                                        <span class="status-indicator <?php echo $company['active'] ? 'status-active' : 'status-inactive'; ?>"></span>
                                        <?php echo $company['active'] ? 'Active' : 'Inactive'; ?>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="company-details">
                                <?php if (!empty($company['address'])): ?>
                                <div class="company-detail">
                                    <i class="bi bi-geo-alt"></i>
                                    <span><?php echo h($company['address'] . (isset($company['city']) ? ', ' . $company['city'] : '') . (isset($company['country']) ? ', ' . $company['country'] : '')); ?></span>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($company['website'])): ?>
                                <div class="company-detail">
                                    <i class="bi bi-globe"></i>
                                    <a href="<?php echo h($company['website']); ?>" target="_blank" class="text-decoration-none"><?php echo h(preg_replace('#^https?://#', '', $company['website'])); ?></a>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($company['contact_name'])): ?>
                                <div class="company-detail">
                                    <i class="bi bi-person"></i>
                                    <span><?php echo h($company['contact_name']); ?></span>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($company['contact_email'])): ?>
                                <div class="company-detail">
                                    <i class="bi bi-envelope"></i>
                                    <a href="mailto:<?php echo h($company['contact_email']); ?>" class="text-decoration-none"><?php echo h($company['contact_email']); ?></a>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($company['contact_phone'])): ?>
                                <div class="company-detail">
                                    <i class="bi bi-telephone"></i>
                                    <a href="tel:<?php echo h($company['contact_phone']); ?>" class="text-decoration-none"><?php echo h($company['contact_phone']); ?></a>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($company['description'])): ?>
                            <div class="mt-3">
                                <p class="card-text text-muted small">
                                    <?php echo substr(h($company['description']), 0, 100) . (strlen($company['description']) > 100 ? '...' : ''); ?>
                                </p>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="card-footer bg-transparent">
                            <a href="/tutoring/views/admin/companies/company_internships.php?id=<?php echo $company['id']; ?>" class="btn btn-sm btn-outline-primary w-100">
                                <i class="bi bi-briefcase me-1"></i> Voir les stages
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <!-- Pagination (seulement si plus de 20 entreprises au total) -->
            <?php if ($totalCompanies > 20 && $totalPages > 1): ?>
            <div class="mt-4">
                <nav aria-label="Navigation des pages d'entreprises">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            <?php if ($totalCompanies > 0): ?>
                                Affichage de <?php echo $showingFrom; ?> à <?php echo $showingTo; ?> sur <?php echo $totalCompanies; ?> résultats
                            <?php endif; ?>
                        </div>
                        
                        <ul class="pagination pagination-sm mb-0">
                            <!-- Bouton Précédent -->
                            <li class="page-item <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>">
                                <?php if ($currentPage > 1): ?>
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $currentPage - 1])); ?>" aria-label="Précédent">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                <?php else: ?>
                                    <span class="page-link" aria-label="Précédent">
                                        <span aria-hidden="true">&laquo;</span>
                                    </span>
                                <?php endif; ?>
                            </li>
                            
                            <?php
                            // Logique d'affichage des numéros de page
                            $startPage = max(1, $currentPage - 2);
                            $endPage = min($totalPages, $currentPage + 2);
                            
                            // Afficher la première page si elle n'est pas dans la plage
                            if ($startPage > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>">1</a>
                                </li>
                                <?php if ($startPage > 2): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <!-- Pages dans la plage -->
                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <li class="page-item <?php echo $i == $currentPage ? 'active' : ''; ?>">
                                    <?php if ($i == $currentPage): ?>
                                        <span class="page-link"><?php echo $i; ?></span>
                                    <?php else: ?>
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                                    <?php endif; ?>
                                </li>
                            <?php endfor; ?>
                            
                            <!-- Afficher la dernière page si elle n'est pas dans la plage -->
                            <?php if ($endPage < $totalPages): ?>
                                <?php if ($endPage < $totalPages - 1): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                <?php endif; ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $totalPages])); ?>"><?php echo $totalPages; ?></a>
                                </li>
                            <?php endif; ?>
                            
                            <!-- Bouton Suivant -->
                            <li class="page-item <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>">
                                <?php if ($currentPage < $totalPages): ?>
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $currentPage + 1])); ?>" aria-label="Suivant">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                <?php else: ?>
                                    <span class="page-link" aria-label="Suivant">
                                        <span aria-hidden="true">&raquo;</span>
                                    </span>
                                <?php endif; ?>
                            </li>
                        </ul>
                    </div>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal de confirmation de suppression -->
<div class="modal fade" id="deleteCompanyModal" tabindex="-1" aria-labelledby="deleteCompanyModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteCompanyModalLabel">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer l'entreprise <span id="companyNameToDelete" class="fw-bold"></span> ?</p>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Cette action est irréversible. Tous les stages associés à cette entreprise seront également supprimés.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form id="deleteCompanyForm" action="/tutoring/views/admin/companies/delete.php" method="POST">
                    <input type="hidden" name="id" id="companyIdToDelete">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <button type="submit" class="btn btn-danger">Supprimer</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialiser les tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    });
    
    // Fonction pour changer le nombre d'éléments par page
    function changeItemsPerPage(value) {
        const url = new URL(window.location);
        url.searchParams.set('per_page', value);
        url.searchParams.set('page', '1'); // Retourner à la première page
        window.location.href = url.toString();
    }
    
    // Fonction pour ouvrir le modal de suppression
    function openDeleteModal(id, name) {
        document.getElementById('companyIdToDelete').value = id;
        document.getElementById('companyNameToDelete').textContent = name;
        
        // Ouvrir le modal
        var deleteModal = new bootstrap.Modal(document.getElementById('deleteCompanyModal'));
        deleteModal.show();
    }
</script>

<?php
// Fonction pour générer une couleur d'avatar à partir du nom
function generateAvatarColor($name) {
    $hash = md5($name);
    $h = hexdec(substr($hash, 0, 2)) % 360;
    $s = 75; // Saturation à 75%
    $l = 45; // Luminosité à 45%
    
    return "hsl($h, $s%, $l%)";
}

// Fonction pour obtenir les initiales
function getInitials($name) {
    $words = preg_split('/\s+/', $name);
    $initials = '';
    
    foreach ($words as $word) {
        if (!empty($word)) {
            $initials .= mb_substr($word, 0, 1, 'UTF-8');
            if (strlen($initials) >= 2) break;
        }
    }
    
    return strtoupper($initials);
}

// Inclure le pied de page
include_once __DIR__ . '/../common/footer.php';
?>