<?php
/**
 * Vue principale pour la gestion des tuteurs (admin)
 */

// Titre de la page
$pageTitle = 'Gestion des Tuteurs';
$currentPage = 'tutors';
$extraStyles = '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../includes/init.php';

// Vérifier que l'utilisateur est administrateur ou coordinateur
requireRole(['admin', 'coordinator']);

// Charger le contrôleur de statistiques
require_once __DIR__ . '/../../controllers/StatisticsController.php';
$statsController = new StatisticsController($db);

// Obtenir les statistiques
$stats = $statsController->getDashboardStats();

// Instancier le contrôleur des tuteurs
$teacherController = new TeacherController($db);

// Instancier le modèle des affectations pour calculer la charge de travail
$assignmentModel = new Assignment($db);

// Récupérer la liste des tuteurs (avec filtres éventuels)
$availableOnly = isset($_GET['available']) && $_GET['available'] === '1';
$department = isset($_GET['department']) ? $_GET['department'] : null;
$searchTerm = isset($_GET['term']) ? $_GET['term'] : '';
$specialty = isset($_GET['specialty']) ? $_GET['specialty'] : '';

if (!empty($searchTerm)) {
    $teachers = $teacherController->search($searchTerm, $availableOnly);
} else {
    $teachers = $teacherController->getTeachers($availableOnly);
}

// Récupérer le nombre réel d'étudiants pour chaque tuteur
foreach ($teachers as $key => $teacher) {
    $teachers[$key]['current_students'] = $assignmentModel->countByTeacherId($teacher['id']);
}

// Filtrer par département si nécessaire
if (!empty($department)) {
    $teachers = array_filter($teachers, function($teacher) use ($department) {
        return isset($teacher['department']) && $teacher['department'] === $department;
    });
}

// Filtrer par spécialité si nécessaire
if (!empty($specialty)) {
    $teachers = array_filter($teachers, function($teacher) use ($specialty) {
        return isset($teacher['specialty']) && stripos($teacher['specialty'], $specialty) !== false;
    });
}

// Statistiques et données pour la page
$totalTutors = count($teachers);

// Liste des départements uniques pour le filtre
$departments = [];
$specialties = [];
$workloadStats = [
    'under_capacity' => 0,
    'optimal' => 0,
    'over_capacity' => 0
];

// Calculer les statistiques
foreach ($teachers as $teacher) {
    // Collecter les départements uniques
    if (!empty($teacher['department']) && !in_array($teacher['department'], $departments)) {
        $departments[] = $teacher['department'];
    }
    
    // Collecter les spécialités uniques
    if (!empty($teacher['specialty'])) {
        $specialtyTags = explode(',', $teacher['specialty']);
        foreach ($specialtyTags as $tag) {
            $tag = trim($tag);
            if (!empty($tag) && !in_array($tag, $specialties)) {
                $specialties[] = $tag;
            }
        }
    }
    
    // Calculer la charge de travail
    $maxStudents = isset($teacher['max_students']) ? (int)$teacher['max_students'] : 0;
    $currentStudents = isset($teacher['current_students']) ? (int)$teacher['current_students'] : 0;
    
    if ($maxStudents > 0) {
        $workloadPercentage = ($currentStudents / $maxStudents) * 100;
        
        if ($workloadPercentage < 70) {
            $workloadStats['under_capacity']++;
        } elseif ($workloadPercentage <= 100) {
            $workloadStats['optimal']++;
        } else {
            $workloadStats['over_capacity']++;
        }
    }
}

// Trier les listes
sort($departments);
sort($specialties);

// Pagination
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$itemsPerPage = 10;
$totalPages = ceil($totalTutors / $itemsPerPage);
$offset = ($currentPage - 1) * $itemsPerPage;

// Limiter les tuteurs pour la pagination
$paginatedTeachers = array_slice($teachers, $offset, $itemsPerPage);

// Inclure l'en-tête
include_once __DIR__ . '/../common/header.php';
?>

<style>
    /* Styles spécifiques pour la page tuteurs */
    .tutor-card {
        transition: all 0.3s ease;
        border-radius: 10px;
        overflow: hidden;
    }
    
    .tutor-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }
    
    .avatar-initials {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        background-color: #2ecc71;
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
    
    .bg-primary-light {
        background-color: rgba(46, 204, 113, 0.1);
        color: #2ecc71;
    }
    
    .bg-info-light {
        background-color: rgba(52, 152, 219, 0.1);
        color: #3498db;
    }
    
    .bg-warning-light {
        background-color: rgba(243, 156, 18, 0.1);
        color: #f39c12;
    }
    
    .bg-danger-light {
        background-color: rgba(231, 76, 60, 0.1);
        color: #e74c3c;
    }
    
    .pagination-container {
        display: flex;
        justify-content: center;
        margin-top: 2rem;
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
    
    .expertise-tags .badge {
        font-weight: 400;
        padding: 6px 10px;
        margin-right: 5px;
        margin-bottom: 5px;
        border-radius: 20px;
        font-size: 0.75rem;
        background-color: rgba(52, 152, 219, 0.1);
        color: #3498db;
        border: 1px solid rgba(52, 152, 219, 0.3);
    }
    
    .workload-badge {
        font-weight: 600;
        font-size: 0.8rem;
        padding: 5px 10px;
        border-radius: 20px;
    }
    
    .availability-indicator {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 5px;
    }
    
    .tutor-specialty {
        color: #6c757d;
        font-size: 0.85rem;
        display: block;
        margin-top: 2px;
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
        overflow: hidden;
    }
    
    .progress-sm {
        height: 5px;
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
    
    /* Styles pour l'autocomplétion */
    .search-suggestions {
        top: calc(100% + 5px);
        background-color: white;
        z-index: 1000;
        max-height: 300px;
        overflow-y: auto;
        border: 1px solid #dee2e6;
    }
    
    .suggestion-item {
        padding: 10px 15px;
        cursor: pointer;
        transition: background-color 0.2s;
        border-bottom: 1px solid #f5f5f5;
    }
    
    .suggestion-item:hover, .suggestion-item.active {
        background-color: #f8f9fa;
    }
    
    .suggestion-item .suggestion-name {
        font-weight: 500;
        margin-bottom: 3px;
    }
    
    .suggestion-item .suggestion-details {
        font-size: 0.8rem;
        color: #6c757d;
    }
    
    .suggestion-item .highlight {
        background-color: rgba(52, 152, 219, 0.2);
        font-weight: bold;
    }
    
    .suggestion-empty {
        padding: 15px;
        text-align: center;
        color: #6c757d;
    }
</style>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-person-badge me-2"></i>Gestion des Tuteurs</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/tutoring/views/admin/dashboard.php">Tableau de bord</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Tuteurs</li>
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
                            <div class="stats-icon bg-primary-light">
                                <i class="bi bi-people"></i>
                            </div>
                            <h3 class="mb-1"><?php echo $totalTutors; ?></h3>
                            <h6 class="text-muted">Tuteurs au total</h6>
                            <div class="progress mt-2">
                                <div class="progress-bar bg-success" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 fade-in delay-2">
                    <div class="card stats-card">
                        <div class="card-body">
                            <div class="stats-icon bg-info-light">
                                <i class="bi bi-check-circle"></i>
                            </div>
                            <h3 class="mb-1"><?php echo $workloadStats['optimal']; ?></h3>
                            <h6 class="text-muted">Charge optimale</h6>
                            <?php 
                            $optimalPercentage = $totalTutors > 0 ? round(($workloadStats['optimal'] / $totalTutors) * 100) : 0;
                            ?>
                            <div class="progress mt-2">
                                <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $optimalPercentage; ?>%" aria-valuenow="<?php echo $optimalPercentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 fade-in delay-3">
                    <div class="card stats-card">
                        <div class="card-body">
                            <div class="stats-icon bg-danger-light">
                                <i class="bi bi-exclamation-triangle"></i>
                            </div>
                            <h3 class="mb-1"><?php echo $workloadStats['over_capacity']; ?></h3>
                            <h6 class="text-muted">Surcharge de travail</h6>
                            <?php 
                            $overCapacityPercentage = $totalTutors > 0 ? round(($workloadStats['over_capacity'] / $totalTutors) * 100) : 0;
                            ?>
                            <div class="progress mt-2">
                                <div class="progress-bar bg-danger" role="progressbar" style="width: <?php echo $overCapacityPercentage; ?>%" aria-valuenow="<?php echo $overCapacityPercentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tutors List -->
            <div class="card mb-4 fade-in">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="m-0 font-weight-bold">Liste des Tuteurs</h5>
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
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <div class="search-box position-relative">
                                <i class="bi bi-search icon"></i>
                                <input type="text" class="form-control" id="tutorSearch" name="term" placeholder="Rechercher un tuteur..." value="<?php echo h($searchTerm); ?>" autocomplete="off">
                                <div id="searchSuggestions" class="search-suggestions shadow rounded position-absolute w-100 d-none"></div>
                                <!-- Formulaire invisible pour la soumission traditionnelle (fallback) -->
                                <form id="searchForm" action="" method="GET" class="d-none">
                                    <input type="hidden" id="searchTermHidden" name="term" value="<?php echo h($searchTerm); ?>">
                                    <?php if ($availableOnly): ?>
                                    <input type="hidden" name="available" value="1">
                                    <?php endif; ?>
                                    <?php if (!empty($department)): ?>
                                    <input type="hidden" name="department" value="<?php echo h($department); ?>">
                                    <?php endif; ?>
                                    <button type="submit">Rechercher</button>
                                </form>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-md-end flex-wrap gap-2">
                                <div class="form-check form-switch me-3">
                                    <input class="form-check-input" type="checkbox" id="availableOnly" <?php echo $availableOnly ? 'checked' : ''; ?> onchange="window.location.href='?<?php echo http_build_query(array_merge($_GET, ['available' => $availableOnly ? '0' : '1'])); ?>'">
                                    <label class="form-check-label" for="availableOnly">Tuteurs disponibles</label>
                                </div>
                                <div class="dropdown me-2">
                                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="departmentFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-filter me-1"></i>Département
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="departmentFilterDropdown">
                                        <li><a class="dropdown-item <?php echo empty($department) ? 'active' : ''; ?>" href="?<?php echo http_build_query(array_merge($_GET, ['department' => null])); ?>">Tous les départements</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <?php foreach ($departments as $dept): ?>
                                        <li><a class="dropdown-item <?php echo $department === $dept ? 'active' : ''; ?>" href="?<?php echo http_build_query(array_merge($_GET, ['department' => $dept])); ?>"><?php echo h($dept); ?></a></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="specialtyFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-funnel me-1"></i>Spécialité
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="specialtyFilterDropdown">
                                        <li><a class="dropdown-item <?php echo empty($specialty) ? 'active' : ''; ?>" href="?<?php echo http_build_query(array_merge($_GET, ['specialty' => null])); ?>">Toutes les spécialités</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <?php foreach ($specialties as $spec): ?>
                                        <li><a class="dropdown-item <?php echo $specialty === $spec ? 'active' : ''; ?>" href="?<?php echo http_build_query(array_merge($_GET, ['specialty' => $spec])); ?>"><?php echo h($spec); ?></a></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Results Info -->
                    <?php if (!empty($searchTerm) || !empty($department) || !empty($specialty) || $availableOnly): ?>
                    <div class="alert alert-info mb-4">
                        <i class="bi bi-info-circle me-2"></i>
                        <span>
                            <?php
                            $filterInfo = [];
                            if (!empty($searchTerm)) $filterInfo[] = "recherche: <strong>\"" . h($searchTerm) . "\"</strong>";
                            if (!empty($department)) $filterInfo[] = "département: <strong>" . h($department) . "</strong>";
                            if (!empty($specialty)) $filterInfo[] = "spécialité: <strong>" . h($specialty) . "</strong>";
                            if ($availableOnly) $filterInfo[] = "tuteurs <strong>disponibles</strong> uniquement";
                            
                            echo "Affichage des résultats pour " . implode(', ', $filterInfo) . " (" . count($teachers) . " tuteurs trouvés)";
                            ?>
                        </span>
                        <a href="?" class="ms-2 text-decoration-none">Réinitialiser les filtres</a>
                    </div>
                    <?php endif; ?>

                    <!-- Tutors Table -->
                    <?php if (empty($paginatedTeachers)): ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>Aucun tuteur trouvé avec les critères de recherche spécifiés.
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Tuteur</th>
                                    <th scope="col">Département</th>
                                    <th scope="col">Spécialité</th>
                                    <th scope="col">Charge de travail</th>
                                    <th scope="col">Disponibilité</th>
                                    <th scope="col" class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($paginatedTeachers as $teacher): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if (!empty($teacher['profile_image'])): ?>
                                            <img src="<?php echo h($teacher['profile_image']); ?>" alt="Profile" class="rounded-circle me-3" width="40" height="40">
                                            <?php else: ?>
                                            <div class="avatar-initials me-3">
                                                <?php echo strtoupper(substr($teacher['first_name'] ?? '', 0, 1) . substr($teacher['last_name'] ?? '', 0, 1)); ?>
                                            </div>
                                            <?php endif; ?>
                                            <div>
                                                <div class="fw-bold">
                                                    <?php if (!empty($teacher['title'])): ?>
                                                    <span class="text-muted"><?php echo h($teacher['title']); ?></span>
                                                    <?php endif; ?>
                                                    <?php echo h(($teacher['first_name'] ?? '') . ' ' . ($teacher['last_name'] ?? '')); ?>
                                                </div>
                                                <div class="text-muted small"><?php echo h($teacher['email'] ?? ''); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo h($teacher['department'] ?? ''); ?></td>
                                    <td>
                                        <span class="tutor-specialty"><?php echo h($teacher['specialty'] ?? ''); ?></span>
                                        <?php if (!empty($teacher['expertise'])): ?>
                                        <div class="expertise-tags mt-1">
                                            <?php
                                            $expertiseTags = explode(',', $teacher['expertise']);
                                            foreach (array_slice($expertiseTags, 0, 3) as $tag):
                                            ?>
                                            <span class="badge"><?php echo h(trim($tag)); ?></span>
                                            <?php endforeach; ?>
                                            <?php if (count($expertiseTags) > 3): ?>
                                            <span class="badge bg-light text-dark">+<?php echo count($expertiseTags) - 3; ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        // Calculer la charge de travail
                                        $maxStudents = isset($teacher['max_students']) ? (int)$teacher['max_students'] : 0;
                                        $currentStudents = isset($teacher['current_students']) ? (int)$teacher['current_students'] : 0;
                                        
                                        if ($maxStudents > 0) {
                                            $workloadPercentage = ($currentStudents / $maxStudents) * 100;
                                            
                                            if ($workloadPercentage < 70) {
                                                $workloadClass = "bg-success";
                                                $workloadText = "Basse";
                                            } elseif ($workloadPercentage <= 100) {
                                                $workloadClass = "bg-info";
                                                $workloadText = "Optimale";
                                            } else {
                                                $workloadClass = "bg-danger";
                                                $workloadText = "Surcharge";
                                            }
                                            
                                            echo '<div class="small mb-1">' . $currentStudents . '/' . $maxStudents . ' étudiants</div>';
                                            echo '<div class="progress progress-sm">';
                                            echo '<div class="progress-bar ' . $workloadClass . '" role="progressbar" style="width: ' . min(100, $workloadPercentage) . '%" aria-valuenow="' . $workloadPercentage . '" aria-valuemin="0" aria-valuemax="100"></div>';
                                            echo '</div>';
                                            echo '<div class="text-end mt-1"><span class="workload-badge ' . str_replace('bg-', 'bg-light text-', $workloadClass) . '">' . $workloadText . '</span></div>';
                                        } else {
                                            echo '<span class="text-muted">Non défini</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        $availableClass = isset($teacher['available']) && $teacher['available'] ? 'bg-success' : 'bg-secondary';
                                        $availableText = isset($teacher['available']) && $teacher['available'] ? 'Disponible' : 'Non disponible';
                                        ?>
                                        <div class="d-flex align-items-center">
                                            <span class="availability-indicator <?php echo $availableClass; ?>"></span>
                                            <span><?php echo $availableText; ?></span>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <a href="/tutoring/views/admin/teachers/show.php?id=<?php echo $teacher['id']; ?>" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Voir les détails">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="/tutoring/views/admin/teachers/edit.php?id=<?php echo $teacher['id']; ?>" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Modifier">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <?php if (hasRole('admin')): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $teacher['id']; ?>" title="Supprimer">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                            
                                            <!-- Modal de confirmation de suppression -->
                                            <div class="modal fade" id="deleteModal<?php echo $teacher['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $teacher['id']; ?>" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="deleteModalLabel<?php echo $teacher['id']; ?>">Confirmer la suppression</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="text-center mb-3">
                                                                <i class="bi bi-exclamation-triangle-fill text-warning" style="font-size: 3rem;"></i>
                                                            </div>
                                                            <p class="text-center">Êtes-vous sûr de vouloir supprimer le tuteur <strong><?php echo h(($teacher['first_name'] ?? '') . ' ' . ($teacher['last_name'] ?? '')); ?></strong> ?</p>
                                                            <p class="text-danger text-center">
                                                                <small>Cette action est irréversible et supprimera également toutes les données associées à ce tuteur.</small>
                                                            </p>
                                                            <?php
                                                            // Vérifier si le tuteur a des étudiants
                                                            $hasStudents = isset($teacher['current_students']) && $teacher['current_students'] > 0;
                                                            if ($hasStudents):
                                                            ?>
                                                            <div class="alert alert-warning text-center">
                                                                <i class="bi bi-exclamation-circle me-2"></i>
                                                                Ce tuteur a actuellement des étudiants affectés. La suppression modifiera leurs affectations.
                                                            </div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="modal-footer justify-content-center">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                            <form action="/tutoring/views/admin/teachers/delete.php" method="POST">
                                                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                                                <input type="hidden" name="id" value="<?php echo $teacher['id']; ?>">
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
                <div class="card-footer">
                    <div class="text-muted small text-center">
                        <i class="bi bi-info-circle me-1"></i> La charge de travail est calculée en fonction du nombre d'étudiants affectés par rapport au maximum défini.
                    </div>
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
                    <a href="/tutoring/views/admin/teachers/create.php" class="btn btn-primary w-100 mb-2">
                        <i class="bi bi-person-plus-fill me-2"></i>Ajouter un tuteur
                    </a>
                    <a href="/tutoring/views/admin/assignments.php" class="btn btn-outline-primary w-100 mb-2">
                        <i class="bi bi-diagram-3 me-2"></i>Gérer les affectations
                    </a>
                    <a href="/tutoring/views/admin/reports.php?type=teachers" class="btn btn-outline-info w-100 mb-2">
                        <i class="bi bi-graph-up me-2"></i>Rapports sur les tuteurs
                    </a>
                    <button class="btn btn-outline-primary w-100 mb-2" data-bs-toggle="modal" data-bs-target="#importModal">
                        <i class="bi bi-file-earmark-arrow-up me-2"></i>Importer des tuteurs
                    </button>
                    <button class="btn btn-outline-primary w-100 mb-2" data-bs-toggle="modal" data-bs-target="#exportModal">
                        <i class="bi bi-file-earmark-arrow-down me-2"></i>Exporter des tuteurs
                    </button>
                    <button class="btn btn-outline-secondary w-100">
                        <i class="bi bi-envelope me-2"></i>Envoyer un email groupé
                    </button>
                </div>
            </div>
            
            <!-- Tutors Workload Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="m-0 font-weight-bold">Charge de travail</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container mb-4">
                        <canvas id="workloadChart" width="100%" height="200"></canvas>
                    </div>
                    <div class="list-group list-group-flush">
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <i class="bi bi-check-circle text-success me-2"></i> Charge basse
                            </div>
                            <span class="badge bg-success rounded-pill"><?php echo $workloadStats['under_capacity']; ?> tuteurs</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <i class="bi bi-star text-info me-2"></i> Charge optimale
                            </div>
                            <span class="badge bg-info rounded-pill"><?php echo $workloadStats['optimal']; ?> tuteurs</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <i class="bi bi-exclamation-triangle text-danger me-2"></i> Surcharge
                            </div>
                            <span class="badge bg-danger rounded-pill"><?php echo $workloadStats['over_capacity']; ?> tuteurs</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Departments Distribution -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="m-0 font-weight-bold">Répartition par département</h5>
                </div>
                <div class="card-body">
                    <?php
                    // Calcul des statistiques par département
                    $departmentStats = [];
                    foreach ($teachers as $teacher) {
                        $dept = $teacher['department'] ?? 'Non spécifié';
                        if (!isset($departmentStats[$dept])) {
                            $departmentStats[$dept] = 0;
                        }
                        $departmentStats[$dept]++;
                    }
                    
                    // Trier par nombre de tuteurs
                    arsort($departmentStats);
                    ?>
                    
                    <?php if (empty($departmentStats)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>Aucune donnée disponible.
                    </div>
                    <?php else: ?>
                    <div class="chart-container">
                        <canvas id="departmentDistribution" width="100%" height="200"></canvas>
                    </div>
                    <div class="mt-3">
                        <?php foreach ($departmentStats as $dept => $count): ?>
                        <div class="mb-2">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small"><?php echo h($dept); ?></span>
                                <span class="small fw-bold"><?php echo $count; ?> (<?php echo round(($count / $totalTutors) * 100); ?>%)</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo ($count / $totalTutors) * 100; ?>%" aria-valuenow="<?php echo ($count / $totalTutors) * 100; ?>" aria-valuemin="0" aria-valuemax="100"></div>
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
                <h5 class="modal-title" id="importModalLabel">Importer des tuteurs</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="importForm" action="/tutoring/api/teachers/import.php" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="importFile" class="form-label">Fichier CSV</label>
                        <input class="form-control" type="file" id="importFile" name="import_file" accept=".csv" required>
                        <div class="form-text">Le fichier doit être au format CSV avec les en-têtes suivants : nom, prénom, email, département, spécialité, max_étudiants.</div>
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
                    <span>Vous pouvez télécharger un <a href="/tutoring/templates/import_teachers_template.csv">modèle de fichier CSV</a> pour l'importation.</span>
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
                <h5 class="modal-title" id="exportModalLabel">Exporter des tuteurs</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="exportForm" action="/tutoring/api/export/teachers.php" method="GET" target="_blank">
                    <div class="mb-3">
                        <label class="form-label">Format d'exportation</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="format" id="formatCSV" value="csv" checked>
                            <label class="btn btn-outline-primary" for="formatCSV">CSV</label>
                            
                            <input type="radio" class="btn-check" name="format" id="formatExcel" value="excel">
                            <label class="btn btn-outline-primary" for="formatExcel">Excel</label>
                            
                            <input type="radio" class="btn-check" name="format" id="formatPDF" value="pdf">
                            <label class="btn btn-outline-primary" for="formatPDF">PDF</label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Options de filtrage</label>
                        <div class="form-check mb-2">
                            <input class="form-check-input export-filter" type="radio" name="exportFilter" id="exportAll" value="all" checked>
                            <label class="form-check-label" for="exportAll">
                                Exporter tous les tuteurs
                            </label>
                        </div>
                        
                        <div class="form-check mb-2">
                            <input class="form-check-input export-filter" type="radio" name="exportFilter" id="exportFiltered" value="filtered">
                            <label class="form-check-label" for="exportFiltered">
                                Exporter uniquement les tuteurs filtrés
                            </label>
                        </div>
                        
                        <!-- Champs cachés pour les filtres actuels -->
                        <?php if (isset($_GET['term'])): ?>
                            <input type="hidden" name="term" id="exportTerm" value="<?php echo h($_GET['term']); ?>">
                        <?php endif; ?>
                        
                        <?php if (isset($_GET['department'])): ?>
                            <input type="hidden" name="department" id="exportDepartment" value="<?php echo h($_GET['department']); ?>">
                        <?php endif; ?>
                        
                        <?php if (isset($_GET['specialty'])): ?>
                            <input type="hidden" name="specialty" id="exportSpecialty" value="<?php echo h($_GET['specialty']); ?>">
                        <?php endif; ?>
                        
                        <?php if (isset($_GET['available'])): ?>
                            <input type="hidden" name="available" id="exportAvailable" value="<?php echo h($_GET['available']); ?>">
                        <?php endif; ?>
                        
                        <input type="hidden" name="exportAll" id="exportAllInput" value="true">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Colonnes à exporter</label>
                        <div class="row">
                            <div class="col-6">
                                <div class="form-check mb-2">
                                    <input class="form-check-input export-column" type="checkbox" name="columns[]" id="colFirstName" value="first_name" checked>
                                    <label class="form-check-label" for="colFirstName">Prénom</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input export-column" type="checkbox" name="columns[]" id="colLastName" value="last_name" checked>
                                    <label class="form-check-label" for="colLastName">Nom</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input export-column" type="checkbox" name="columns[]" id="colEmail" value="email" checked>
                                    <label class="form-check-label" for="colEmail">Email</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input export-column" type="checkbox" name="columns[]" id="colTitle" value="title" checked>
                                    <label class="form-check-label" for="colTitle">Titre</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input export-column" type="checkbox" name="columns[]" id="colDept" value="department" checked>
                                    <label class="form-check-label" for="colDept">Département</label>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-check mb-2">
                                    <input class="form-check-input export-column" type="checkbox" name="columns[]" id="colSpecialty" value="specialty" checked>
                                    <label class="form-check-label" for="colSpecialty">Spécialité</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input export-column" type="checkbox" name="columns[]" id="colOffice" value="office_location" checked>
                                    <label class="form-check-label" for="colOffice">Bureau</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input export-column" type="checkbox" name="columns[]" id="colMaxStudents" value="max_students" checked>
                                    <label class="form-check-label" for="colMaxStudents">Capacité max.</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input export-column" type="checkbox" name="columns[]" id="colAvailable" value="available" checked>
                                    <label class="form-check-label" for="colAvailable">Disponibilité</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input export-column" type="checkbox" name="columns[]" id="colExpertise" value="expertise" checked>
                                    <label class="form-check-label" for="colExpertise">Expertise</label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Message d'erreur pour les colonnes -->
                        <div id="columnsError" class="text-danger mt-2" style="display: none;">
                            Veuillez sélectionner au moins une colonne à exporter.
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="exportSubmitBtn">
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
        
        // Gestion de l'exportation
        const exportForm = document.getElementById('exportForm');
        const exportSubmitBtn = document.getElementById('exportSubmitBtn');
        const exportFilterRadios = document.querySelectorAll('.export-filter');
        const exportAllInput = document.getElementById('exportAllInput');
        const exportColumns = document.querySelectorAll('.export-column');
        const columnsError = document.getElementById('columnsError');
        
        if (exportSubmitBtn && exportForm) {
            // Gestion de l'option de filtrage
            exportFilterRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    if (this.value === 'all') {
                        exportAllInput.value = 'true';
                    } else {
                        exportAllInput.value = 'false';
                    }
                });
            });
            
            // Soumission du formulaire d'exportation
            exportSubmitBtn.addEventListener('click', function() {
                // Vérifier qu'au moins une colonne est sélectionnée
                const selectedColumns = Array.from(exportColumns).filter(checkbox => checkbox.checked);
                
                if (selectedColumns.length === 0) {
                    columnsError.style.display = 'block';
                    return;
                } else {
                    columnsError.style.display = 'none';
                }
                
                // Soumettre le formulaire
                exportForm.submit();
                
                // Fermer la modale
                const modal = bootstrap.Modal.getInstance(document.getElementById('exportModal'));
                modal.hide();
            });
            
            // Réinitialiser l'erreur des colonnes quand une est cochée
            exportColumns.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const anyChecked = Array.from(exportColumns).some(cb => cb.checked);
                    if (anyChecked) {
                        columnsError.style.display = 'none';
                    }
                });
            });
        }
        
        // Chart.js pour le graphique de répartition des charges de travail
        if (document.getElementById('workloadChart')) {
            const ctxWorkload = document.getElementById('workloadChart').getContext('2d');
            const workloadData = {
                labels: ['Charge basse', 'Charge optimale', 'Surcharge'],
                datasets: [{
                    data: [
                        <?php echo $workloadStats['under_capacity']; ?>,
                        <?php echo $workloadStats['optimal']; ?>,
                        <?php echo $workloadStats['over_capacity']; ?>
                    ],
                    backgroundColor: [
                        'rgba(46, 204, 113, 0.7)',
                        'rgba(52, 152, 219, 0.7)',
                        'rgba(231, 76, 60, 0.7)'
                    ],
                    borderColor: [
                        'rgba(46, 204, 113, 1)',
                        'rgba(52, 152, 219, 1)',
                        'rgba(231, 76, 60, 1)'
                    ],
                    borderWidth: 1
                }]
            };
            
            new Chart(ctxWorkload, {
                type: 'pie',
                data: workloadData,
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
                                    return `${label}: ${value} tuteurs (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Chart.js pour le graphique de répartition des départements
        if (document.getElementById('departmentDistribution')) {
            const ctxDept = document.getElementById('departmentDistribution').getContext('2d');
            
            // Données pour le graphique
            const deptLabels = <?php echo json_encode(array_keys($departmentStats)); ?>;
            const deptData = <?php echo json_encode(array_values($departmentStats)); ?>;
            
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
                
                if (count <= baseColors.length) {
                    return baseColors.slice(0, count);
                }
                
                const colors = [...baseColors];
                for (let i = baseColors.length; i < count; i++) {
                    const r = Math.floor(Math.random() * 200) + 55;
                    const g = Math.floor(Math.random() * 200) + 55;
                    const b = Math.floor(Math.random() * 200) + 55;
                    colors.push(`rgba(${r}, ${g}, ${b}, 0.7)`);
                }
                
                return colors;
            };
            
            new Chart(ctxDept, {
                type: 'doughnut',
                data: {
                    labels: deptLabels,
                    datasets: [{
                        data: deptData,
                        backgroundColor: generateColors(deptLabels.length),
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
                                boxWidth: 12,
                                font: {
                                    size: 11
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((acc, val) => acc + val, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: ${value} tuteurs (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Fonctionnalité d'autocomplétion pour la recherche de tuteurs
        const tutorSearch = document.getElementById('tutorSearch');
        const searchSuggestions = document.getElementById('searchSuggestions');
        const searchForm = document.getElementById('searchForm');
        const searchTermHidden = document.getElementById('searchTermHidden');
        
        if (tutorSearch && searchSuggestions) {
            let currentSelection = -1;
            let suggestions = [];
            let debounceTimer;
            
            // Fonction pour mettre en surbrillance le terme recherché
            function highlightText(text, term) {
                if (!term) return text;
                const regex = new RegExp(`(${term.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&')})`, 'gi');
                return text.replace(regex, '<span class="highlight">$1</span>');
            }
            
            // Fonction pour afficher les suggestions
            function displaySuggestions(results, term) {
                searchSuggestions.innerHTML = '';
                suggestions = results;
                
                if (results.length === 0) {
                    searchSuggestions.innerHTML = '<div class="suggestion-empty">Aucun tuteur trouvé</div>';
                    searchSuggestions.classList.remove('d-none');
                    return;
                }
                
                results.forEach((result, index) => {
                    const suggestionItem = document.createElement('div');
                    suggestionItem.className = 'suggestion-item';
                    suggestionItem.dataset.index = index;
                    
                    // Mettre en surbrillance les correspondances
                    const highlightedName = highlightText(result.label, term);
                    const highlightedSpecialty = result.specialty ? highlightText(result.specialty, term) : '';
                    
                    suggestionItem.innerHTML = `
                        <div class="suggestion-name">${highlightedName}</div>
                        <div class="suggestion-details">
                            <i class="bi bi-envelope-fill me-1"></i>${result.email}
                            ${result.specialty ? '<br><i class="bi bi-briefcase-fill me-1"></i>' + highlightedSpecialty : ''}
                        </div>
                    `;
                    
                    // Gérer le clic sur une suggestion
                    suggestionItem.addEventListener('click', () => {
                        window.location.href = result.url;
                    });
                    
                    searchSuggestions.appendChild(suggestionItem);
                });
                
                searchSuggestions.classList.remove('d-none');
            }
            
            // Gérer la saisie dans le champ de recherche
            tutorSearch.addEventListener('input', function() {
                const term = this.value.trim();
                searchTermHidden.value = term;
                
                // Réinitialiser la sélection
                currentSelection = -1;
                
                // Effacer le timer de debounce existant
                clearTimeout(debounceTimer);
                
                if (term.length < 1) {
                    searchSuggestions.classList.add('d-none');
                    return;
                }
                
                // Debounce pour éviter trop de requêtes
                debounceTimer = setTimeout(() => {
                    // Appel à l'API pour récupérer les suggestions
                    const params = new URLSearchParams({
                        term: term,
                        limit: 10
                    });
                    
                    <?php if ($availableOnly): ?>
                    params.append('available', '1');
                    <?php endif; ?>
                    
                    fetch(`/tutoring/api/teachers/search.php?${params.toString()}`)
                        .then(response => response.json())
                        .then(data => {
                            displaySuggestions(data, term);
                        })
                        .catch(error => {
                            console.error('Erreur lors de la recherche:', error);
                        });
                }, 300); // 300ms de délai
            });
            
            // Gérer les touches fléchées et Entrée
            tutorSearch.addEventListener('keydown', function(e) {
                const suggestionItems = searchSuggestions.querySelectorAll('.suggestion-item');
                
                // Si les suggestions ne sont pas affichées, retourner
                if (searchSuggestions.classList.contains('d-none')) {
                    return;
                }
                
                // Flèche Bas
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    currentSelection = Math.min(currentSelection + 1, suggestionItems.length - 1);
                    
                    suggestionItems.forEach((item, index) => {
                        item.classList.toggle('active', index === currentSelection);
                    });
                    
                    if (currentSelection >= 0) {
                        suggestionItems[currentSelection].scrollIntoView({ block: 'nearest' });
                    }
                }
                
                // Flèche Haut
                else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    currentSelection = Math.max(currentSelection - 1, -1);
                    
                    suggestionItems.forEach((item, index) => {
                        item.classList.toggle('active', index === currentSelection);
                    });
                    
                    if (currentSelection >= 0) {
                        suggestionItems[currentSelection].scrollIntoView({ block: 'nearest' });
                    }
                }
                
                // Touche Entrée
                else if (e.key === 'Enter') {
                    e.preventDefault();
                    
                    if (currentSelection >= 0 && currentSelection < suggestions.length) {
                        window.location.href = suggestions[currentSelection].url;
                    } else {
                        // Soumission traditionnelle si aucune suggestion n'est sélectionnée
                        searchForm.submit();
                    }
                }
                
                // Touche Échap
                else if (e.key === 'Escape') {
                    searchSuggestions.classList.add('d-none');
                    currentSelection = -1;
                }
            });
            
            // Fermer les suggestions en cliquant en dehors
            document.addEventListener('click', function(e) {
                if (!tutorSearch.contains(e.target) && !searchSuggestions.contains(e.target)) {
                    searchSuggestions.classList.add('d-none');
                }
            });
        }
    });
</script>

<?php
// Inclure le pied de page
include_once __DIR__ . '/../common/footer.php';
?>