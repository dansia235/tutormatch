<?php
/**
 * Vue principale pour la gestion des stages (admin)
 */

// Titre de la page
$pageTitle = 'Gestion des Stages';
$currentPage = 'internships';
$extraStyles = '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../includes/init.php';

// Vérifier que l'utilisateur est administrateur ou coordinateur
requireRole(['admin', 'coordinator']);

// Charger les contrôleurs
require_once __DIR__ . '/../../controllers/StatisticsController.php';
require_once __DIR__ . '/../../controllers/InternshipController.php';
$statsController = new StatisticsController($db);

// Obtenir les statistiques
$stats = $statsController->getDashboardStats();

// Instancier le contrôleur de stages
$internshipController = new InternshipController($db);

// Récupérer la liste des stages (avec filtres éventuels)
$status = isset($_GET['status']) ? $_GET['status'] : null;
$domain = isset($_GET['domain']) ? $_GET['domain'] : null;
$company = isset($_GET['company']) ? $_GET['company'] : null;
$searchTerm = isset($_GET['term']) ? $_GET['term'] : '';

if (!empty($searchTerm) || !empty($domain) || !empty($company)) {
    $internships = $internshipController->search($searchTerm, $status);
} else {
    // Récupérer les stages directement depuis le modèle
    $internships = $internshipController->getAll($status);
}

// Obtenir la liste des domaines pour le filtre
$domains = $internshipController->getDomains();

// Obtenir la liste des compétences pour le filtre
$skills = $internshipController->getAllSkills();

// Statistiques et données pour la page
$totalInternships = count($internships);

// Calculer les statistiques par statut
$statusStats = [];
$domainsStats = [];
$timelineStats = [];
$currentDate = date('Y-m-d');

foreach ($internships as $internship) {
    // Statistiques par statut
    $internshipStatus = $internship['status'] ?? 'unknown';
    if (!isset($statusStats[$internshipStatus])) {
        $statusStats[$internshipStatus] = 0;
    }
    $statusStats[$internshipStatus]++;
    
    // Statistiques par domaine
    $internshipDomain = $internship['domain'] ?? 'unknown';
    if (!isset($domainsStats[$internshipDomain])) {
        $domainsStats[$internshipDomain] = 0;
    }
    $domainsStats[$internshipDomain]++;
    
    // Statistiques par timeline
    if (isset($internship['start_date']) && isset($internship['end_date'])) {
        if ($internship['start_date'] > $currentDate) {
            if (!isset($timelineStats['upcoming'])) {
                $timelineStats['upcoming'] = 0;
            }
            $timelineStats['upcoming']++;
        } elseif ($internship['end_date'] < $currentDate) {
            if (!isset($timelineStats['past'])) {
                $timelineStats['past'] = 0;
            }
            $timelineStats['past']++;
        } else {
            if (!isset($timelineStats['current'])) {
                $timelineStats['current'] = 0;
            }
            $timelineStats['current']++;
        }
    }
}

// Statuts en français et leurs classes
$statusLabels = [
    'available' => 'Disponible',
    'assigned' => 'Affecté',
    'completed' => 'Terminé',
    'cancelled' => 'Annulé',
    'draft' => 'Brouillon',
    'pending' => 'En attente'
];

$statusClasses = [
    'available' => 'success',
    'assigned' => 'primary',
    'completed' => 'info',
    'cancelled' => 'danger',
    'draft' => 'secondary',
    'pending' => 'warning'
];

// Pagination
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$itemsPerPage = 10;
$totalPages = ceil($totalInternships / $itemsPerPage);
$offset = ($currentPage - 1) * $itemsPerPage;

// Limiter les stages pour la pagination
$paginatedInternships = array_slice($internships, $offset, $itemsPerPage);

// Inclure l'en-tête
include_once __DIR__ . '/../common/header.php';
?>

<style>
    /* Styles spécifiques pour la page stages */
    .internship-card {
        transition: all 0.3s ease;
        border-radius: 10px;
        overflow: hidden;
    }
    
    .internship-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }
    
    .company-logo {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        background-color: #f8f9fa;
        border: 1px solid #e9ecef;
    }
    
    .company-logo img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }
    
    .company-logo-placeholder {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        background-color: #3498db;
    }
    
    .stats-card {
        border-radius: 10px;
        transition: all 0.3s ease;
        height: 100%;
    }
    
    .stats-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }
    
    .stats-card .card-body {
        padding: 1.5rem;
    }
    
    .stats-icon {
        width: 60px;
        height: 60px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-bottom: 15px;
    }
    
    .bg-green-light {
        background-color: rgba(46, 204, 113, 0.1);
        color: #2ecc71;
    }
    
    .bg-blue-light {
        background-color: rgba(52, 152, 219, 0.1);
        color: #3498db;
    }
    
    .bg-orange-light {
        background-color: rgba(243, 156, 18, 0.1);
        color: #f39c12;
    }
    
    .pagination-container {
        display: flex;
        justify-content: center;
        margin-top: 2rem;
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
    
    .search-box {
        position: relative;
    }
    
    .search-box .icon {
        position: absolute;
        left: 15px;
        top: 13px;
        color: #6c757d;
    }
    
    .search-box input {
        padding-left: 40px;
        border-radius: 50px;
    }
    
    .skill-badge {
        font-weight: 400;
        font-size: 0.7rem;
        padding: 4px 8px;
        border-radius: 20px;
        margin-right: 3px;
        margin-bottom: 3px;
        display: inline-block;
        background-color: rgba(52, 152, 219, 0.1);
        color: #3498db;
        border: 1px solid rgba(52, 152, 219, 0.3);
    }
    
    .status-badge {
        font-weight: 600;
        font-size: 0.75rem;
        padding: 5px 10px;
        border-radius: 20px;
        text-transform: uppercase;
    }
    
    .date-badge {
        font-size: 0.7rem;
        padding: 4px 8px;
        border-radius: 4px;
        background-color: rgba(0,0,0,0.05);
    }
    
    /* Fade-in animation */
    .fade-in {
        animation: fadeIn 0.5s ease-in-out;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .delay-1 { animation-delay: 0.1s; }
    .delay-2 { animation-delay: 0.2s; }
    .delay-3 { animation-delay: 0.3s; }
    
    .progress {
        height: 10px;
        border-radius: 5px;
    }
    
    .internship-title {
        font-weight: 600;
        color: #2c3e50;
    }
    
    .company-name {
        color: #7f8c8d;
        font-size: 0.85rem;
    }
    
    .internship-dates {
        font-size: 0.8rem;
        color: #7f8c8d;
    }
    
    .filter-chip {
        display: inline-block;
        padding: 5px 10px;
        margin-right: 5px;
        margin-bottom: 5px;
        border-radius: 20px;
        background-color: #f8f9fa;
        border: 1px solid #e9ecef;
        font-size: 0.8rem;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .filter-chip:hover {
        background-color: #e9ecef;
    }
    
    .filter-chip.active {
        background-color: #3498db;
        border-color: #3498db;
        color: white;
    }
    
    .timeline-indicator {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 5px;
    }
</style>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-briefcase me-2"></i>Gestion des Stages</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/tutoring/views/admin/dashboard.php">Tableau de bord</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Stages</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="row">
        <!-- Main Content Area (8 columns) -->
        <div class="col-lg-8">
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-4 fade-in delay-1">
                    <div class="card stats-card">
                        <div class="card-body">
                            <div class="stats-icon bg-green-light">
                                <i class="bi bi-briefcase"></i>
                            </div>
                            <h3 class="mb-1"><?php echo $totalInternships; ?></h3>
                            <h6 class="text-muted">Stages au total</h6>
                            <div class="progress mt-2">
                                <div class="progress-bar bg-success" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 fade-in delay-2">
                    <div class="card stats-card">
                        <div class="card-body">
                            <div class="stats-icon bg-blue-light">
                                <i class="bi bi-calendar-check"></i>
                            </div>
                            <h3 class="mb-1"><?php echo $statusStats['available'] ?? 0; ?></h3>
                            <h6 class="text-muted">Stages disponibles</h6>
                            <?php 
                            $availablePercentage = $totalInternships > 0 ? round((($statusStats['available'] ?? 0) / $totalInternships) * 100) : 0;
                            ?>
                            <div class="progress mt-2">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $availablePercentage; ?>%" aria-valuenow="<?php echo $availablePercentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 fade-in delay-3">
                    <div class="card stats-card">
                        <div class="card-body">
                            <div class="stats-icon bg-orange-light">
                                <i class="bi bi-diagram-3"></i>
                            </div>
                            <h3 class="mb-1"><?php echo $statusStats['assigned'] ?? 0; ?></h3>
                            <h6 class="text-muted">Stages affectés</h6>
                            <?php 
                            $assignedPercentage = $totalInternships > 0 ? round((($statusStats['assigned'] ?? 0) / $totalInternships) * 100) : 0;
                            ?>
                            <div class="progress mt-2">
                                <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $assignedPercentage; ?>%" aria-valuenow="<?php echo $assignedPercentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Internships List -->
            <div class="card mb-4 fade-in">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="m-0 font-weight-bold">Liste des Stages</h5>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#importModal">
                            <i class="bi bi-upload me-1"></i>Importer
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#exportModal">
                            <i class="bi bi-download me-1"></i>Exporter
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Search and Filter -->
                    <div class="row mb-3">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <form action="" method="GET" class="search-box">
                                <i class="bi bi-search icon"></i>
                                <input type="text" class="form-control" name="term" placeholder="Rechercher un stage..." value="<?php echo h($searchTerm); ?>">
                                <?php if (!empty($status)): ?>
                                <input type="hidden" name="status" value="<?php echo h($status); ?>">
                                <?php endif; ?>
                                <?php if (!empty($domain)): ?>
                                <input type="hidden" name="domain" value="<?php echo h($domain); ?>">
                                <?php endif; ?>
                                <?php if (!empty($company)): ?>
                                <input type="hidden" name="company" value="<?php echo h($company); ?>">
                                <?php endif; ?>
                                <button type="submit" class="d-none">Rechercher</button>
                            </form>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-md-end flex-wrap gap-2">
                                <div class="dropdown me-2">
                                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="domainFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-filter me-1"></i>Domaine
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="domainFilterDropdown">
                                        <li><a class="dropdown-item <?php echo empty($domain) ? 'active' : ''; ?>" href="?<?php echo http_build_query(array_merge($_GET, ['domain' => null])); ?>">Tous les domaines</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <?php foreach ($domains as $dom): ?>
                                        <li><a class="dropdown-item <?php echo $domain === $dom ? 'active' : ''; ?>" href="?<?php echo http_build_query(array_merge($_GET, ['domain' => $dom])); ?>"><?php echo h($dom); ?></a></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="statusFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-funnel me-1"></i>Statut
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="statusFilterDropdown">
                                        <li><a class="dropdown-item <?php echo empty($status) ? 'active' : ''; ?>" href="?<?php echo http_build_query(array_merge($_GET, ['status' => null])); ?>">Tous les statuts</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <?php foreach ($statusLabels as $key => $label): ?>
                                        <li><a class="dropdown-item <?php echo $status === $key ? 'active' : ''; ?>" href="?<?php echo http_build_query(array_merge($_GET, ['status' => $key])); ?>"><?php echo h($label); ?></a></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Filter Chips -->
                    <div class="mb-3">
                        <span class="filter-chip <?php echo empty($status) ? 'active' : ''; ?>" onclick="window.location.href='?<?php echo http_build_query(array_merge($_GET, ['status' => null])); ?>'">
                            Tous
                        </span>
                        <span class="filter-chip <?php echo $status === 'available' ? 'active' : ''; ?>" onclick="window.location.href='?<?php echo http_build_query(array_merge($_GET, ['status' => 'available'])); ?>'">
                            <i class="bi bi-check-circle-fill me-1 small"></i>Disponibles
                        </span>
                        <span class="filter-chip <?php echo $status === 'assigned' ? 'active' : ''; ?>" onclick="window.location.href='?<?php echo http_build_query(array_merge($_GET, ['status' => 'assigned'])); ?>'">
                            <i class="bi bi-person-check-fill me-1 small"></i>Affectés
                        </span>
                        <span class="filter-chip <?php echo $status === 'completed' ? 'active' : ''; ?>" onclick="window.location.href='?<?php echo http_build_query(array_merge($_GET, ['status' => 'completed'])); ?>'">
                            <i class="bi bi-check-all me-1 small"></i>Terminés
                        </span>
                    </div>
                    
                    <!-- Results Info -->
                    <?php if (!empty($searchTerm) || !empty($status) || !empty($domain) || !empty($company)): ?>
                    <div class="alert alert-info mb-4">
                        <i class="bi bi-info-circle me-2"></i>
                        <span>
                            <?php
                            $filterInfo = [];
                            if (!empty($searchTerm)) $filterInfo[] = "recherche: <strong>\"" . h($searchTerm) . "\"</strong>";
                            if (!empty($status)) $filterInfo[] = "statut: <strong>" . ($statusLabels[$status] ?? $status) . "</strong>";
                            if (!empty($domain)) $filterInfo[] = "domaine: <strong>" . h($domain) . "</strong>";
                            if (!empty($company)) $filterInfo[] = "entreprise: <strong>" . h($company) . "</strong>";
                            
                            echo "Affichage des résultats pour " . implode(', ', $filterInfo) . " (" . count($internships) . " stages trouvés)";
                            ?>
                        </span>
                        <a href="?" class="ms-2 text-decoration-none">Réinitialiser les filtres</a>
                    </div>
                    <?php endif; ?>

                    <!-- Internships Table -->
                    <?php if (empty($paginatedInternships)): ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>Aucun stage trouvé avec les critères de recherche spécifiés.
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Stage</th>
                                    <th scope="col">Dates</th>
                                    <th scope="col">Domaine</th>
                                    <th scope="col">Compétences</th>
                                    <th scope="col">Statut</th>
                                    <th scope="col" class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($paginatedInternships as $internship): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if (!empty($internship['company_logo'])): ?>
                                            <div class="company-logo me-3">
                                                <img src="<?php echo h($internship['company_logo']); ?>" alt="<?php echo h($internship['company_name'] ?? 'Company'); ?>">
                                            </div>
                                            <?php else: ?>
                                            <div class="company-logo-placeholder me-3">
                                                <?php echo strtoupper(substr($internship['company_name'] ?? 'C', 0, 1)); ?>
                                            </div>
                                            <?php endif; ?>
                                            <div>
                                                <div class="internship-title"><?php echo h($internship['title'] ?? ''); ?></div>
                                                <div class="company-name"><?php echo h($internship['company_name'] ?? ''); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (isset($internship['start_date']) && isset($internship['end_date'])): ?>
                                        <div class="internship-dates">
                                            <div class="mb-1">
                                                <i class="bi bi-calendar-event me-1 small"></i>
                                                <?php echo date('d/m/Y', strtotime($internship['start_date'])); ?>
                                            </div>
                                            <div>
                                                <i class="bi bi-calendar-check me-1 small"></i>
                                                <?php echo date('d/m/Y', strtotime($internship['end_date'])); ?>
                                            </div>
                                        </div>
                                        <?php else: ?>
                                        <span class="text-muted small">Non spécifié</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo h($internship['domain'] ?? 'Non spécifié'); ?>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap">
                                            <?php
                                            $internshipSkills = $internship['skills'] ?? [];
                                            foreach (array_slice($internshipSkills, 0, 3) as $skill):
                                            ?>
                                            <span class="skill-badge"><?php echo h($skill); ?></span>
                                            <?php endforeach; ?>
                                            <?php if (count($internshipSkills) > 3): ?>
                                            <span class="skill-badge bg-light text-dark">+<?php echo count($internshipSkills) - 3; ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        $internshipStatus = $internship['status'] ?? 'unknown';
                                        $statusClass = 'bg-' . ($statusClasses[$internshipStatus] ?? 'secondary');
                                        $statusLabel = $statusLabels[$internshipStatus] ?? 'Inconnu';
                                        ?>
                                        <span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusLabel; ?></span>
                                        
                                        <?php
                                        // Afficher l'indicateur de timeline
                                        if (isset($internship['start_date']) && isset($internship['end_date'])):
                                            $timelineIndicatorClass = '';
                                            $timelineLabel = '';
                                            
                                            if ($internship['start_date'] > $currentDate) {
                                                $timelineIndicatorClass = 'bg-info';
                                                $timelineLabel = 'À venir';
                                            } elseif ($internship['end_date'] < $currentDate) {
                                                $timelineIndicatorClass = 'bg-secondary';
                                                $timelineLabel = 'Terminé';
                                            } else {
                                                $timelineIndicatorClass = 'bg-success';
                                                $timelineLabel = 'En cours';
                                            }
                                        ?>
                                        <div class="mt-1 d-flex align-items-center">
                                            <span class="timeline-indicator <?php echo $timelineIndicatorClass; ?>"></span>
                                            <small><?php echo $timelineLabel; ?></small>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <a href="/tutoring/views/admin/internships/show.php?id=<?php echo $internship['id']; ?>" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Voir les détails">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="/tutoring/views/admin/internships/edit.php?id=<?php echo $internship['id']; ?>" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Modifier">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <?php if (hasRole('admin')): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $internship['id']; ?>" title="Supprimer">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                            
                                            <!-- Modal de confirmation de suppression -->
                                            <div class="modal fade" id="deleteModal<?php echo $internship['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $internship['id']; ?>" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="deleteModalLabel<?php echo $internship['id']; ?>">Confirmer la suppression</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="text-center mb-3">
                                                                <i class="bi bi-exclamation-triangle-fill text-warning" style="font-size: 3rem;"></i>
                                                            </div>
                                                            <p class="text-center">Êtes-vous sûr de vouloir supprimer le stage <strong><?php echo h($internship['title'] ?? ''); ?></strong> ?</p>
                                                            <p class="text-danger text-center">
                                                                <small>Cette action est irréversible et supprimera également toutes les données associées à ce stage.</small>
                                                            </p>
                                                            
                                                            <?php
                                                            // Vérifier si le stage est affecté
                                                            $isAssigned = isset($internship['status']) && $internship['status'] === 'assigned';
                                                            if ($isAssigned):
                                                            ?>
                                                            <div class="alert alert-warning text-center">
                                                                <i class="bi bi-exclamation-circle me-2"></i>
                                                                Ce stage est actuellement affecté à un étudiant. La suppression annulera cette affectation.
                                                            </div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="modal-footer justify-content-center">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                            <form action="/tutoring/views/admin/internships/delete.php" method="POST">
                                                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                                                <input type="hidden" name="id" value="<?php echo $internship['id']; ?>">
                                                                <button type="submit" class="btn btn-danger">
                                                                    <i class="bi bi-trash me-1"></i>Supprimer
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <div class="pagination-container">
                        <nav>
                            <ul class="pagination">
                                <li class="page-item <?php echo $currentPage == 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $currentPage - 1])); ?>">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                                
                                <?php
                                // Afficher 5 liens de page maximum
                                $startPage = max(1, $currentPage - 2);
                                $endPage = min($totalPages, $startPage + 4);
                                
                                // Ajuster le début si on est proche de la fin
                                if ($endPage - $startPage < 4) {
                                    $startPage = max(1, $endPage - 4);
                                }
                                
                                for ($i = $startPage; $i <= $endPage; $i++):
                                ?>
                                <li class="page-item <?php echo $i == $currentPage ? 'active' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                                </li>
                                <?php endfor; ?>
                                
                                <li class="page-item <?php echo $currentPage == $totalPages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $currentPage + 1])); ?>">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar (4 columns) -->
        <div class="col-lg-4">
            <!-- Quick Actions Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="m-0 font-weight-bold">Actions rapides</h5>
                </div>
                <div class="card-body">
                    <a href="/tutoring/views/admin/internships/create.php" class="btn btn-primary w-100 mb-2">
                        <i class="bi bi-plus-circle me-2"></i>Ajouter un stage
                    </a>
                    <a href="/tutoring/views/admin/assignments.php" class="btn btn-outline-primary w-100 mb-2">
                        <i class="bi bi-diagram-3 me-2"></i>Gérer les affectations
                    </a>
                    <a href="/tutoring/views/admin/companies.php" class="btn btn-outline-primary w-100 mb-2">
                        <i class="bi bi-building me-2"></i>Gérer les entreprises
                    </a>
                    <a href="/tutoring/views/admin/reports.php?type=internships" class="btn btn-outline-info w-100 mb-2">
                        <i class="bi bi-graph-up me-2"></i>Rapports sur les stages
                    </a>
                    <button class="btn btn-outline-primary w-100 mb-2" data-bs-toggle="modal" data-bs-target="#importModal">
                        <i class="bi bi-file-earmark-arrow-up me-2"></i>Importer des stages
                    </button>
                    <button class="btn btn-outline-primary w-100">
                        <i class="bi bi-file-earmark-arrow-down me-2"></i>Exporter des stages
                    </button>
                </div>
            </div>
            
            <!-- Status Distribution Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="m-0 font-weight-bold">Répartition par statut</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($statusStats)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>Aucune donnée disponible.
                    </div>
                    <?php else: ?>
                    <div class="chart-container mb-4">
                        <canvas id="statusChart" width="100%" height="200"></canvas>
                    </div>
                    <div class="mt-3">
                        <?php foreach ($statusStats as $stat => $count): ?>
                        <div class="mb-2">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small d-flex align-items-center">
                                    <span class="badge bg-<?php echo $statusClasses[$stat] ?? 'secondary'; ?> me-2" style="width:10px; height:10px; border-radius:50%; padding:0;"></span>
                                    <?php echo $statusLabels[$stat] ?? ucfirst($stat); ?>
                                </span>
                                <span class="small fw-bold"><?php echo $count; ?> (<?php echo round(($count / $totalInternships) * 100); ?>%)</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-<?php echo $statusClasses[$stat] ?? 'secondary'; ?>" role="progressbar" style="width: <?php echo ($count / $totalInternships) * 100; ?>%" aria-valuenow="<?php echo ($count / $totalInternships) * 100; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Timeline Distribution Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="m-0 font-weight-bold">Répartition temporelle</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($timelineStats)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>Aucune donnée disponible.
                    </div>
                    <?php else: ?>
                    <div class="chart-container mb-4">
                        <canvas id="timelineChart" width="100%" height="200"></canvas>
                    </div>
                    <div class="list-group list-group-flush">
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <span class="timeline-indicator bg-info me-2"></span> À venir
                            </div>
                            <span class="badge bg-info rounded-pill"><?php echo $timelineStats['upcoming'] ?? 0; ?> stages</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <span class="timeline-indicator bg-success me-2"></span> En cours
                            </div>
                            <span class="badge bg-success rounded-pill"><?php echo $timelineStats['current'] ?? 0; ?> stages</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <span class="timeline-indicator bg-secondary me-2"></span> Terminés
                            </div>
                            <span class="badge bg-secondary rounded-pill"><?php echo $timelineStats['past'] ?? 0; ?> stages</span>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Domains Distribution Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="m-0 font-weight-bold">Répartition par domaine</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($domainsStats)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>Aucune donnée disponible.
                    </div>
                    <?php else: ?>
                    <div class="chart-container mb-4">
                        <canvas id="domainChart" width="100%" height="200"></canvas>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importModalLabel">Importer des stages</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="importForm" action="/tutoring/api/internships/import.php" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="importFile" class="form-label">Fichier CSV</label>
                        <input class="form-control" type="file" id="importFile" name="import_file" accept=".csv" required>
                        <div class="form-text">Le fichier doit être au format CSV avec les en-têtes suivants : titre, entreprise, description, date_début, date_fin, domaine, statut.</div>
                    </div>
                    <div class="mb-3 form-check">
                        <input class="form-check-input" type="checkbox" id="skipHeader" name="skip_header" checked>
                        <label class="form-check-label" for="skipHeader">Ignorer la première ligne (en-têtes)</label>
                    </div>
                    <div class="mb-3">
                        <label for="importAction" class="form-label">Action en cas de doublon</label>
                        <select class="form-select" id="importAction" name="duplicate_action">
                            <option value="skip">Ignorer</option>
                            <option value="update">Mettre à jour</option>
                            <option value="error">Afficher une erreur</option>
                        </select>
                    </div>
                </form>
                <div class="alert alert-info mt-3">
                    <i class="bi bi-info-circle me-2"></i>
                    <span>Vous pouvez télécharger un <a href="/tutoring/templates/import_internships_template.csv">modèle de fichier CSV</a> pour l'importation.</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" form="importForm" class="btn btn-primary">
                    <i class="bi bi-upload me-1"></i>Importer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Export Modal -->
<div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exportModalLabel">Exporter des stages</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="exportForm" action="/tutoring/api/internships/export.php" method="get">
                    <div class="mb-3">
                        <label class="form-label">Format d'exportation</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="format" id="formatCsv" value="csv" checked>
                            <label class="btn btn-outline-primary" for="formatCsv">CSV</label>
                            
                            <input type="radio" class="btn-check" name="format" id="formatXls" value="xlsx">
                            <label class="btn btn-outline-primary" for="formatXls">Excel</label>
                            
                            <input type="radio" class="btn-check" name="format" id="formatPdf" value="pdf">
                            <label class="btn btn-outline-primary" for="formatPdf">PDF</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Filtres d'exportation</label>
                        <div class="mb-2">
                            <select class="form-select" name="export_status">
                                <option value="">Tous les statuts</option>
                                <?php foreach ($statusLabels as $key => $label): ?>
                                <option value="<?php echo h($key); ?>"><?php echo h($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <select class="form-select" name="export_domain">
                                <option value="">Tous les domaines</option>
                                <?php foreach ($domains as $dom): ?>
                                <option value="<?php echo h($dom); ?>"><?php echo h($dom); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mt-2">
                            <select class="form-select" name="export_timeline">
                                <option value="">Toutes les périodes</option>
                                <option value="upcoming">À venir</option>
                                <option value="current">En cours</option>
                                <option value="past">Terminés</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Champs à exporter</label>
                        <div class="row">
                            <div class="col-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="fields[]" value="title" id="fieldTitle" checked>
                                    <label class="form-check-label" for="fieldTitle">Titre</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="fields[]" value="company_name" id="fieldCompany" checked>
                                    <label class="form-check-label" for="fieldCompany">Entreprise</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="fields[]" value="description" id="fieldDescription" checked>
                                    <label class="form-check-label" for="fieldDescription">Description</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="fields[]" value="start_date" id="fieldStartDate" checked>
                                    <label class="form-check-label" for="fieldStartDate">Date de début</label>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="fields[]" value="end_date" id="fieldEndDate" checked>
                                    <label class="form-check-label" for="fieldEndDate">Date de fin</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="fields[]" value="domain" id="fieldDomain" checked>
                                    <label class="form-check-label" for="fieldDomain">Domaine</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="fields[]" value="location" id="fieldLocation" checked>
                                    <label class="form-check-label" for="fieldLocation">Lieu</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="fields[]" value="status" id="fieldStatus" checked>
                                    <label class="form-check-label" for="fieldStatus">Statut</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" form="exportForm" class="btn btn-primary">
                    <i class="bi bi-download me-1"></i>Exporter
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialisation des tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Générer des couleurs
        const generateColors = (count) => {
            const baseColors = [
                'rgba(46, 204, 113, 0.7)',
                'rgba(52, 152, 219, 0.7)',
                'rgba(155, 89, 182, 0.7)',
                'rgba(52, 73, 94, 0.7)',
                'rgba(243, 156, 18, 0.7)',
                'rgba(231, 76, 60, 0.7)',
                'rgba(26, 188, 156, 0.7)'
            ];
            
            const borderColors = [
                'rgba(46, 204, 113, 1)',
                'rgba(52, 152, 219, 1)',
                'rgba(155, 89, 182, 1)',
                'rgba(52, 73, 94, 1)',
                'rgba(243, 156, 18, 1)',
                'rgba(231, 76, 60, 1)',
                'rgba(26, 188, 156, 1)'
            ];
            
            if (count <= baseColors.length) {
                return {
                    bg: baseColors.slice(0, count),
                    border: borderColors.slice(0, count)
                };
            }
            
            const bgColors = [...baseColors];
            const borderColors = [...borderColors];
            
            for (let i = baseColors.length; i < count; i++) {
                const r = Math.floor(Math.random() * 200) + 55;
                const g = Math.floor(Math.random() * 200) + 55;
                const b = Math.floor(Math.random() * 200) + 55;
                bgColors.push(`rgba(${r}, ${g}, ${b}, 0.7)`);
                borderColors.push(`rgba(${r}, ${g}, ${b}, 1)`);
            }
            
            return {
                bg: bgColors,
                border: borderColors
            };
        };
        
        // Chart.js pour le graphique de répartition par statut
        if (document.getElementById('statusChart')) {
            const ctxStatus = document.getElementById('statusChart').getContext('2d');
            
            // Données pour le graphique
            const statusLabels = <?php 
                $labels = [];
                foreach ($statusStats as $status => $count) {
                    $labels[] = $statusLabels[$status] ?? ucfirst($status);
                }
                echo json_encode($labels); 
            ?>;
            
            const statusData = <?php echo json_encode(array_values($statusStats)); ?>;
            const statusColors = [
                'rgba(46, 204, 113, 0.7)',
                'rgba(52, 152, 219, 0.7)',
                'rgba(243, 156, 18, 0.7)',
                'rgba(231, 76, 60, 0.7)',
                'rgba(155, 89, 182, 0.7)',
                'rgba(52, 73, 94, 0.7)'
            ];
            
            new Chart(ctxStatus, {
                type: 'doughnut',
                data: {
                    labels: statusLabels,
                    datasets: [{
                        data: statusData,
                        backgroundColor: statusColors.slice(0, statusData.length),
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((acc, val) => acc + val, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: ${value} stages (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Chart.js pour le graphique de répartition temporelle
        if (document.getElementById('timelineChart')) {
            const ctxTimeline = document.getElementById('timelineChart').getContext('2d');
            
            // Données pour le graphique
            const timelineData = [
                <?php echo $timelineStats['upcoming'] ?? 0; ?>,
                <?php echo $timelineStats['current'] ?? 0; ?>,
                <?php echo $timelineStats['past'] ?? 0; ?>
            ];
            
            new Chart(ctxTimeline, {
                type: 'pie',
                data: {
                    labels: ['À venir', 'En cours', 'Terminés'],
                    datasets: [{
                        data: timelineData,
                        backgroundColor: [
                            'rgba(52, 152, 219, 0.7)',
                            'rgba(46, 204, 113, 0.7)',
                            'rgba(149, 165, 166, 0.7)'
                        ],
                        borderColor: [
                            'rgba(52, 152, 219, 1)',
                            'rgba(46, 204, 113, 1)',
                            'rgba(149, 165, 166, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((acc, val) => acc + val, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: ${value} stages (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Chart.js pour le graphique de répartition par domaine
        if (document.getElementById('domainChart')) {
            const ctxDomain = document.getElementById('domainChart').getContext('2d');
            
            // Données pour le graphique
            const domainLabels = <?php echo json_encode(array_keys($domainsStats)); ?>;
            const domainData = <?php echo json_encode(array_values($domainsStats)); ?>;
            const colors = generateColors(domainLabels.length);
            
            new Chart(ctxDomain, {
                type: 'bar',
                data: {
                    labels: domainLabels,
                    datasets: [{
                        label: 'Stages par domaine',
                        data: domainData,
                        backgroundColor: colors.bg,
                        borderColor: colors.border,
                        borderWidth: 1
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const value = context.raw || 0;
                                    const total = domainData.reduce((acc, val) => acc + val, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${value} stages (${percentage}%)`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        }
    });
</script>

<?php
// Inclure le pied de page
include_once __DIR__ . '/../common/footer.php';
?>