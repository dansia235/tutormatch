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

// Configuration de la pagination
$itemsPerPage = isset($_GET['per_page']) ? max(10, min(100, (int)$_GET['per_page'])) : 10; // Nombre d'éléments par page
$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

// Configuration pour l'affichage initial (les données seront chargées via API)
$status = isset($_GET['status']) ? $_GET['status'] : null;
$domain = isset($_GET['domain']) ? $_GET['domain'] : null;
$company = isset($_GET['company']) ? $_GET['company'] : null;
$searchTerm = isset($_GET['term']) ? $_GET['term'] : '';

// Variables pour les statistiques uniquement
$allInternships = $internshipController->getAll();
$totalInternships = count($allInternships);

// Les informations de pagination seront gérées côté client via l'API

// Obtenir la liste des domaines pour le filtre
$domains = $internshipController->getDomains();

// Obtenir la liste des compétences pour le filtre
$skills = $internshipController->getAllSkills();

// Calculer les statistiques par statut (sur tous les stages, pas seulement la page courante)
$statusStats = [];
$domainsStats = [];
$timelineStats = [];
$currentDate = date('Y-m-d');

foreach ($allInternships as $internship) {
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

// Configuration pour la pagination (utilisée par JavaScript)
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$itemsPerPage = isset($_GET['per_page']) ? max(10, min(100, (int)$_GET['per_page'])) : 10;

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
    
    /* Styles pour le tri des colonnes */
    .table th.sortable {
        cursor: pointer;
        user-select: none;
        position: relative;
        white-space: nowrap;
        transition: background-color 0.2s ease;
    }
    
    .table th.sortable:hover {
        background-color: rgba(0, 0, 0, 0.05);
    }
    
    .table th .d-flex {
        align-items: center;
        justify-content: space-between;
        min-width: 120px;
    }
    
    .sort-icon {
        font-size: 0.8rem;
        transition: all 0.2s ease;
    }
    
    .sort-icon:hover {
        transform: scale(1.1);
    }
    
    /* Animation pour les lignes triées */
    tbody tr {
        transition: all 0.3s ease;
    }
    
    /* Responsive pour le tri */
    @media (max-width: 768px) {
        .table th .d-flex {
            flex-direction: column;
            align-items: flex-start;
            gap: 5px;
        }
        
        .sort-icon {
            align-self: flex-end;
        }
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
                            <div class="search-box">
                                <i class="bi bi-search icon"></i>
                                <input type="text" class="form-control" id="searchInput" placeholder="Rechercher un stage...">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-md-end flex-wrap gap-2 align-items-center">
                                <!-- Filtres par statut -->
                                <div class="btn-group me-3" role="group" aria-label="Filtres par statut">
                                    <input type="radio" class="btn-check" name="statusFilter" id="status-all" value="" checked>
                                    <label class="btn btn-outline-primary" for="status-all">Tous</label>
                                    
                                    <input type="radio" class="btn-check" name="statusFilter" id="status-available" value="available">
                                    <label class="btn btn-outline-success" for="status-available">Disponibles</label>
                                    
                                    <input type="radio" class="btn-check" name="statusFilter" id="status-assigned" value="assigned">
                                    <label class="btn btn-outline-warning" for="status-assigned">Affectés</label>
                                    
                                    <input type="radio" class="btn-check" name="statusFilter" id="status-completed" value="completed">
                                    <label class="btn btn-outline-info" for="status-completed">Terminés</label>
                                </div>
                                
                                <!-- Sélecteur du nombre d'éléments par page -->
                                <div class="d-flex align-items-center">
                                    <label for="itemsPerPage" class="form-label me-2 mb-0 text-muted small">Afficher:</label>
                                    <select id="itemsPerPage" class="form-select form-select-sm" style="width: auto;">
                                        <option value="10" selected>10</option>
                                        <option value="20">20</option>
                                        <option value="50">50</option>
                                        <option value="100">100</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    
                    <!-- Results Info -->
                    <div class="alert alert-light border mb-4" id="results-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <span id="results-text">Chargement des stages...</span>
                    </div>

                    <!-- Internships Table -->
                    <div id="internships-container">
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Chargement...</span>
                            </div>
                            <p class="mt-2 text-muted">Chargement des stages...</p>
                        </div>
                    </div>
                </div>
                
                <div class="card-footer">
                    <nav aria-label="Navigation des pages">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-muted" id="pagination-info">
                                <!-- Sera rempli par JavaScript -->
                            </div>
                            <ul class="pagination pagination-sm mb-0" id="pagination-controls">
                                <!-- Sera rempli par JavaScript -->
                            </ul>
                        </div>
                    </nav>
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
                    <div class="chart-container mb-4" style="position: relative; height: 200px;">
                        <canvas id="statusChart"></canvas>
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
                    <div class="chart-container mb-4" style="position: relative; height: 200px;">
                        <canvas id="domainChart"></canvas>
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
                    <div class="chart-container mb-4" style="position: relative; height: 200px;">
                        <canvas id="timelineChart"></canvas>
                    </div>
                    <div class="mt-3">
                        <?php foreach ($timelineStats as $stat => $count): ?>
                        <div class="mb-2">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small d-flex align-items-center">
                                    <span class="badge bg-<?php echo ($stat === 'upcoming') ? 'primary' : (($stat === 'current') ? 'success' : 'secondary'); ?> me-2" style="width:10px; height:10px; border-radius:50%; padding:0;"></span>
                                    <?php echo ($stat === 'upcoming') ? 'À venir' : (($stat === 'current') ? 'En cours' : 'Terminés'); ?>
                                </span>
                                <span class="small fw-bold"><?php echo $count; ?> (<?php echo $totalInternships > 0 ? round(($count / $totalInternships) * 100) : 0; ?>%)</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-<?php echo ($stat === 'upcoming') ? 'primary' : (($stat === 'current') ? 'success' : 'secondary'); ?>" role="progressbar" style="width: <?php echo $totalInternships > 0 ? ($count / $totalInternships) * 100 : 0; ?>%" aria-valuenow="<?php echo $totalInternships > 0 ? ($count / $totalInternships) * 100 : 0; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
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
// Attendre que Chart.js soit chargé
window.addEventListener('load', function() {
    console.log('Window fully loaded');
    console.log('Chart.js available?', typeof Chart !== 'undefined');
    
    if (typeof Chart === 'undefined') {
        console.error('Chart.js failed to load!');
        return;
    }
    
    // Créer le graphique de statut
    const statusCanvas = document.getElementById('statusChart');
    if (statusCanvas) {
        console.log('Creating status chart with data:', {
            available: <?php echo $statusStats['available'] ?? 0; ?>,
            assigned: <?php echo $statusStats['assigned'] ?? 0; ?>,
            completed: <?php echo $statusStats['completed'] ?? 0; ?>,
            cancelled: <?php echo $statusStats['cancelled'] ?? 0; ?>
        });
        
        try {
            const statusChart = new Chart(statusCanvas.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['Disponible', 'Affecté', 'Terminé', 'Annulé'],
                    datasets: [{
                        data: [
                            <?php echo $statusStats['available'] ?? 0; ?>,
                            <?php echo $statusStats['assigned'] ?? 0; ?>,
                            <?php echo $statusStats['completed'] ?? 0; ?>,
                            <?php echo $statusStats['cancelled'] ?? 0; ?>
                        ],
                        backgroundColor: [
                            'rgba(46, 204, 113, 0.8)',
                            'rgba(52, 152, 219, 0.8)',
                            'rgba(23, 162, 184, 0.8)',
                            'rgba(220, 53, 69, 0.8)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
            console.log('Status chart created!');
        } catch (e) {
            console.error('Error creating status chart:', e);
        }
    }
    
    // Créer le graphique de répartition par domaine
    const domainCanvas = document.getElementById('domainChart');
    if (domainCanvas) {
        console.log('Creating domain chart with data:', <?php echo json_encode($domainsStats); ?>);
        
        try {
            const domainChart = new Chart(domainCanvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode(array_keys($domainsStats)); ?>,
                    datasets: [{
                        label: 'Stages par domaine',
                        data: <?php echo json_encode(array_values($domainsStats)); ?>,
                        backgroundColor: [
                            'rgba(46, 204, 113, 0.8)',
                            'rgba(52, 152, 219, 0.8)',
                            'rgba(155, 89, 182, 0.8)',
                            'rgba(52, 73, 94, 0.8)',
                            'rgba(243, 156, 18, 0.8)',
                            'rgba(231, 76, 60, 0.8)',
                            'rgba(26, 188, 156, 0.8)'
                        ],
                        borderColor: [
                            'rgba(46, 204, 113, 1)',
                            'rgba(52, 152, 219, 1)',
                            'rgba(155, 89, 182, 1)',
                            'rgba(52, 73, 94, 1)',
                            'rgba(243, 156, 18, 1)',
                            'rgba(231, 76, 60, 1)',
                            'rgba(26, 188, 156, 1)'
                        ],
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
            console.log('Domain chart created!');
        } catch (e) {
            console.error('Error creating domain chart:', e);
        }
    }
});
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM loaded, checking for chart elements...');
        console.log('statusChart exists:', document.getElementById('statusChart') !== null);
        console.log('timelineChart exists:', document.getElementById('timelineChart') !== null);
        console.log('Chart.js loaded:', typeof Chart !== 'undefined');
        
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
        
        // Chart.js pour le graphique de répartition par statut - Version simplifiée
        setTimeout(function() {
            console.log('Attempting to create charts...');
            console.log('Chart.js available?', typeof Chart !== 'undefined');
            
            if (typeof Chart === 'undefined') {
                console.error('Chart.js is not loaded!');
                return;
            }
            
            const statusCanvas = document.getElementById('statusChart');
            console.log('Status canvas element:', statusCanvas);
            
            if (statusCanvas) {
                console.log('Creating status chart...');
                
                try {
                    // Données simples et directes
                    const statusChart = new Chart(statusCanvas, {
                    type: 'doughnut',
                    data: {
                        labels: [
                            'Disponible',
                            'Affecté',
                            'Terminé',
                            'Annulé'
                        ],
                        datasets: [{
                            data: [
                                <?php echo $statusStats['available'] ?? 0; ?>,
                                <?php echo $statusStats['assigned'] ?? 0; ?>,
                                <?php echo $statusStats['completed'] ?? 0; ?>,
                                <?php echo $statusStats['cancelled'] ?? 0; ?>
                            ],
                            backgroundColor: [
                                'rgba(46, 204, 113, 0.7)',
                                'rgba(52, 152, 219, 0.7)',
                                'rgba(23, 162, 184, 0.7)',
                                'rgba(220, 53, 69, 0.7)'
                            ],
                            borderColor: [
                                'rgba(46, 204, 113, 1)',
                                'rgba(52, 152, 219, 1)',
                                'rgba(23, 162, 184, 1)',
                                'rgba(220, 53, 69, 1)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }
        
            // Chart.js pour le graphique de répartition temporelle
            const timelineCanvas = document.getElementById('timelineChart');
            if (timelineCanvas) {
                console.log('Creating timeline chart...');
                
                const timelineChart = new Chart(timelineCanvas, {
                    type: 'pie',
                    data: {
                        labels: ['À venir', 'En cours', 'Terminés'],
                        datasets: [{
                            data: [
                                <?php echo $timelineStats['upcoming'] ?? 0; ?>,
                                <?php echo $timelineStats['current'] ?? 0; ?>,
                                <?php echo $timelineStats['past'] ?? 0; ?>
                            ],
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
                                position: 'bottom'
                            }
                        }
                    }
                });
            }
        
        
        }, 100); // Délai de 100ms
        
        // Les stages seront chargés par le script séparé
    });
    
    // Fonction pour changer le nombre d'éléments par page
    function changeItemsPerPage(value) {
        const url = new URL(window.location);
        url.searchParams.set('per_page', value);
        url.searchParams.set('page', '1'); // Retourner à la première page
        window.location.href = url.toString();
    }
    
    }, 1000);

</script>

<script>
    class InternshipsTable {
        constructor() {
            this.apiUrl = '/tutoring/api/internships/admin-list.php';
            this.currentPage = 1;
            this.itemsPerPage = 10;
            this.searchTerm = '';
            this.statusFilter = '';
            this.searchTimeout = null;
            
            this.init();
        }
        
        init() {
            this.setupEventListeners();
            this.loadData();
        }
        
        setupEventListeners() {
            // Recherche en temps réel
            document.getElementById('searchInput').addEventListener('input', (e) => {
                clearTimeout(this.searchTimeout);
                this.searchTimeout = setTimeout(() => {
                    this.searchTerm = e.target.value;
                    this.currentPage = 1;
                    this.loadData();
                }, 500);
            });
            
            // Filtres par statut
            document.querySelectorAll('input[name="statusFilter"]').forEach(radio => {
                radio.addEventListener('change', (e) => {
                    this.statusFilter = e.target.value;
                    this.currentPage = 1;
                    this.loadData();
                });
            });
            
            // Changement du nombre d'éléments par page
            document.getElementById('itemsPerPage').addEventListener('change', (e) => {
                this.itemsPerPage = parseInt(e.target.value);
                this.currentPage = 1;
                this.loadData();
            });
        }
        
        async loadData() {
            try {
                const params = new URLSearchParams({
                    page: this.currentPage,
                    per_page: this.itemsPerPage,
                    term: this.searchTerm,
                    status: this.statusFilter
                });
                
                const response = await fetch(`${this.apiUrl}?${params}`);
                const result = await response.json();
                
                if (result.success) {
                    this.renderInternships(result.data.internships);
                    this.renderPagination(result.data.pagination);
                    this.updateResultsInfo(result.data.pagination);
                } else {
                    this.showError(result.error || 'Erreur inconnue');
                }
            } catch (error) {
                this.showError('Erreur lors du chargement des données: ' + error.message);
            }
        }
        
        renderInternships(internships) {
            const container = document.getElementById('internships-container');
            
            if (internships.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-4">
                        <i class="bi bi-info-circle text-muted" style="font-size: 3rem;"></i>
                        <p class="mt-2 text-muted">Aucun stage trouvé.</p>
                    </div>
                `;
                return;
            }
            
            const tableHtml = `
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Titre</th>
                                <th>Entreprise</th>
                                <th>Lieu</th>
                                <th>Domaine</th>
                                <th>Statut</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${internships.map(internship => `
                                <tr>
                                    <td>
                                        <div class="fw-bold">${this.escapeHtml(internship.title || '')}</div>
                                        <div class="text-muted small">${this.escapeHtml(internship.requirements ? internship.requirements.substring(0, 50) + '...' : '')}</div>
                                    </td>
                                    <td>
                                        <div class="fw-bold">${this.escapeHtml(internship.company_name || '')}</div>
                                        <div class="text-muted small">${this.escapeHtml(internship.company_city || '')}</div>
                                    </td>
                                    <td>${this.escapeHtml(internship.location || '')}</td>
                                    <td><span class="skill-badge">${this.escapeHtml(internship.domain || '')}</span></td>
                                    <td>${this.getStatusBadge(internship.status)}</td>
                                    <td>
                                        <div class="date-badge">${internship.start_date_formatted || ''}</div>
                                        <div class="date-badge">${internship.end_date_formatted || ''}</div>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="/tutoring/views/admin/internships/show.php?id=${internship.id}" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Voir les détails">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="/tutoring/views/admin/internships/edit.php?id=${internship.id}" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Modifier">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteInternship(${internship.id}, '${internship.title}')" title="Supprimer">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
            
            container.innerHTML = tableHtml;
            
            // Initialiser les tooltips
            this.initTooltips();
        }
        
        renderPagination(pagination) {
            const paginationInfo = document.getElementById('pagination-info');
            const paginationControls = document.getElementById('pagination-controls');
            
            // Mettre à jour les informations de pagination
            if (pagination.total_items > 0) {
                paginationInfo.textContent = `Affichage de ${pagination.showing_from} à ${pagination.showing_to} sur ${pagination.total_items} résultats`;
            } else {
                paginationInfo.textContent = '';
            }
            
            // Générer les contrôles de pagination
            if (pagination.total_pages <= 1) {
                paginationControls.innerHTML = '';
                return;
            }
            
            let paginationHtml = '';
            
            // Bouton Précédent
            paginationHtml += `
                <li class="page-item ${pagination.current_page <= 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="internshipsTable.changePage(${pagination.current_page - 1}); return false;" aria-label="Précédent">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
            `;
            
            // Pages
            const startPage = Math.max(1, pagination.current_page - 2);
            const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);
            
            if (startPage > 1) {
                paginationHtml += `<li class="page-item"><a class="page-link" href="#" onclick="internshipsTable.changePage(1); return false;">1</a></li>`;
                if (startPage > 2) {
                    paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                }
            }
            
            for (let i = startPage; i <= endPage; i++) {
                paginationHtml += `
                    <li class="page-item ${i === pagination.current_page ? 'active' : ''}">
                        <a class="page-link" href="#" onclick="internshipsTable.changePage(${i}); return false;">${i}</a>
                    </li>
                `;
            }
            
            if (endPage < pagination.total_pages) {
                if (endPage < pagination.total_pages - 1) {
                    paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                }
                paginationHtml += `<li class="page-item"><a class="page-link" href="#" onclick="internshipsTable.changePage(${pagination.total_pages}); return false;">${pagination.total_pages}</a></li>`;
            }
            
            // Bouton Suivant
            paginationHtml += `
                <li class="page-item ${pagination.current_page >= pagination.total_pages ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="internshipsTable.changePage(${pagination.current_page + 1}); return false;" aria-label="Suivant">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            `;
            
            paginationControls.innerHTML = paginationHtml;
        }
        
        updateResultsInfo(pagination) {
            const resultsText = document.getElementById('results-text');
            if (pagination.total_items > 0) {
                resultsText.textContent = `Affichage de ${pagination.showing_from}-${pagination.showing_to} sur ${pagination.total_items} stages`;
            } else {
                resultsText.textContent = 'Aucun stage trouvé';
            }
        }
        
        changePage(page) {
            this.currentPage = page;
            this.loadData();
        }
        
        showError(message) {
            const container = document.getElementById('internships-container');
            container.innerHTML = `
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    ${message}
                </div>
            `;
        }
        
        getStatusBadge(status) {
            const badges = {
                'available': '<span class="status-badge bg-success">Disponible</span>',
                'assigned': '<span class="status-badge bg-warning">Affecté</span>',
                'completed': '<span class="status-badge bg-info">Terminé</span>',
                'cancelled': '<span class="status-badge bg-danger">Annulé</span>'
            };
            return badges[status] || `<span class="status-badge bg-secondary">${this.escapeHtml(status)}</span>`;
        }
        
        initTooltips() {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }
        
        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    }
    
    // Initialiser la table
    let internshipsTable;
    document.addEventListener('DOMContentLoaded', function() {
        // Attendre que les autres scripts soient chargés
        setTimeout(() => {
            internshipsTable = new InternshipsTable();
        }, 100);
    });
    
    // Fonction pour supprimer un stage
    function deleteInternship(id, title) {
        alert(`Suppression du stage #${id} - ${title} - Fonctionnalité à implémenter`);
    }

// Variables globales pour les stages (pour compatibilité)
let currentPageInternships = 1;
let itemsPerPageInternships = 10;
let statusFilterInternships = '';
let domainFilterInternships = '';
let searchTermInternships = '';

// Fonction pour charger les stages (pour compatibilité)
async function loadInternships() {
    if (internshipsTable) {
        internshipsTable.loadData();
    }
}
</script>


<?php
// Inclure le pied de page
include_once __DIR__ . '/../common/footer.php';
?>
