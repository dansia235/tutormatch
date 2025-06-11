<?php
/**
 * Vue d'administration pour la gestion des étudiants
 */

// Titre de la page
$pageTitle = 'Gestion des étudiants';
$currentPage = 'students';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../includes/init.php';

// Vérifier les permissions
requireRole(['admin', 'coordinator']);

// Instancier le contrôleur
$studentController = new StudentController($db);

// Traiter la recherche ou afficher tous les étudiants
if (isset($_GET['search'])) {
    $term = isset($_GET['term']) ? $_GET['term'] : '';
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    $program = isset($_GET['program']) ? $_GET['program'] : null;
    $level = isset($_GET['level']) ? $_GET['level'] : null;
    
    $students = $studentController->search($term, $status);
    
    // Filtrage supplémentaire côté PHP si nécessaire
    if ($program || $level) {
        $students = array_filter($students, function($student) use ($program, $level) {
            $matchProgram = !$program || $student['program'] === $program;
            $matchLevel = !$level || $student['level'] === $level;
            return $matchProgram && $matchLevel;
        });
    }
} else {
    // Afficher tous les étudiants ou filtrer par statut
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    $students = $studentController->getStudents($status);
}

// Pagination des résultats
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10; // Nombre d'étudiants par page
$totalStudents = count($students);
$totalPages = ceil($totalStudents / $perPage);

// S'assurer que la page demandée est valide
if ($page < 1) $page = 1;
if ($page > $totalPages && $totalPages > 0) $page = $totalPages;

// Extraire les étudiants pour la page courante
$offset = ($page - 1) * $perPage;
$paginatedStudents = array_slice($students, $offset, $perPage);

// Préparer les données pour les statistiques
$activeStudents = count(array_filter($students, function($student) {
    return $student['status'] === 'active';
}));
$graduatedStudents = count(array_filter($students, function($student) {
    return $student['status'] === 'graduated';
}));
$suspendedStudents = count(array_filter($students, function($student) {
    return $student['status'] === 'suspended';
}));

// Extraire les programmes et niveaux uniques pour les filtres
$programs = array_unique(array_column($students, 'program'));
$levels = array_unique(array_column($students, 'level'));

// Définir le filtre actif
$activeFilter = isset($_GET['status']) ? $_GET['status'] : '';

// Inclure l'en-tête
include_once __DIR__ . '/../common/header.php';
?>

<div class="container-fluid">
    <!-- Header section -->
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-mortarboard me-2"></i>Gestion des étudiants</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/tutoring/views/admin/dashboard.php">Tableau de bord</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Étudiants</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Content section -->
    <div class="row">
        <!-- Main content - Student list -->
        <div class="col-lg-9">
            <!-- Statistics cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="value"><?php echo $totalStudents; ?></div>
                        <div class="label">Étudiants total</div>
                        <div class="progress mt-2">
                            <div class="progress-bar" role="progressbar" style="width: 100%;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="value"><?php echo $activeStudents; ?></div>
                        <div class="label">Étudiants actifs</div>
                        <div class="progress mt-2">
                            <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo ($totalStudents > 0) ? ($activeStudents / $totalStudents * 100) : 0; ?>%;" aria-valuenow="<?php echo ($totalStudents > 0) ? ($activeStudents / $totalStudents * 100) : 0; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="value"><?php echo $graduatedStudents; ?></div>
                        <div class="label">Diplômés</div>
                        <div class="progress mt-2">
                            <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo ($totalStudents > 0) ? ($graduatedStudents / $totalStudents * 100) : 0; ?>%;" aria-valuenow="<?php echo ($totalStudents > 0) ? ($graduatedStudents / $totalStudents * 100) : 0; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="value"><?php echo $suspendedStudents; ?></div>
                        <div class="label">Suspendus</div>
                        <div class="progress mt-2">
                            <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo ($totalStudents > 0) ? ($suspendedStudents / $totalStudents * 100) : 0; ?>%;" aria-valuenow="<?php echo ($totalStudents > 0) ? ($suspendedStudents / $totalStudents * 100) : 0; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search and filter section -->
            <div class="card mb-4">
                <div class="card-body">
                    <form action="" method="GET" class="row g-3">
                        <div class="col-lg-4 col-md-6">
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" class="form-control" name="term" placeholder="Rechercher..." value="<?php echo isset($_GET['term']) ? h($_GET['term']) : ''; ?>">
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-6">
                            <select class="form-select" name="program">
                                <option value="">Tous les programmes</option>
                                <?php foreach($programs as $program): ?>
                                <option value="<?php echo h($program); ?>" <?php echo (isset($_GET['program']) && $_GET['program'] === $program) ? 'selected' : ''; ?>>
                                    <?php echo h($program); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-6">
                            <select class="form-select" name="level">
                                <option value="">Tous les niveaux</option>
                                <?php foreach($levels as $level): ?>
                                <option value="<?php echo h($level); ?>" <?php echo (isset($_GET['level']) && $_GET['level'] === $level) ? 'selected' : ''; ?>>
                                    <?php echo h($level); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-6">
                            <select class="form-select" name="status">
                                <option value="">Tous les statuts</option>
                                <option value="active" <?php echo $activeFilter === 'active' ? 'selected' : ''; ?>>Actifs</option>
                                <option value="graduated" <?php echo $activeFilter === 'graduated' ? 'selected' : ''; ?>>Diplômés</option>
                                <option value="suspended" <?php echo $activeFilter === 'suspended' ? 'selected' : ''; ?>>Suspendus</option>
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-6">
                            <button type="submit" name="search" value="1" class="btn btn-primary w-100">Filtrer</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Students list -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="m-0"><i class="bi bi-list me-2"></i>Liste des étudiants</h5>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#importModal">
                            <i class="bi bi-upload me-1"></i>Importer
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#exportModal">
                            <i class="bi bi-download me-1"></i>Exporter
                        </button>
                        <div class="btn-group" role="group">
                            <button id="btnGroupViews" type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-grid me-1"></i>Vue
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="btnGroupViews">
                                <li><a class="dropdown-item active" href="#" data-view="table"><i class="bi bi-table me-2"></i>Tableau</a></li>
                                <li><a class="dropdown-item" href="#" data-view="cards"><i class="bi bi-grid-3x3-gap me-2"></i>Cartes</a></li>
                                <li><a class="dropdown-item" href="#" data-view="list"><i class="bi bi-list-ul me-2"></i>Liste</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($students)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>Aucun étudiant trouvé.
                    </div>
                    <?php else: ?>
                    <!-- Table View (Default) -->
                    <div id="tableView" class="view-content">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">Étudiant</th>
                                        <th scope="col">Numéro</th>
                                        <th scope="col">Programme</th>
                                        <th scope="col">Niveau</th>
                                        <th scope="col">Département</th>
                                        <th scope="col">Statut</th>
                                        <th scope="col">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($paginatedStudents as $index => $student): ?>
                                    <tr>
                                        <td><?php echo $offset + $index + 1; ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if (!empty($student['profile_image'])): ?>
                                                <img src="<?php echo h($student['profile_image']); ?>" alt="Profile" class="rounded-circle me-3" width="40" height="40">
                                                <?php else: ?>
                                                <div class="avatar me-3" style="width: 40px; height: 40px; background-color: #6c757d; color: white; display: flex; align-items: center; justify-content: center; border-radius: 50%;">
                                                    <?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?>
                                                </div>
                                                <?php endif; ?>
                                                <div>
                                                    <div class="fw-bold"><?php echo h($student['first_name'] . ' ' . $student['last_name']); ?></div>
                                                    <div class="text-muted small"><?php echo h($student['email']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo h($student['student_number']); ?></td>
                                        <td><?php echo h($student['program']); ?></td>
                                        <td><?php echo h($student['level']); ?></td>
                                        <td><?php echo h($student['department']); ?></td>
                                        <td>
                                            <?php
                                            $statusBadge = [
                                                'active' => '<span class="badge bg-success">Actif</span>',
                                                'graduated' => '<span class="badge bg-info">Diplômé</span>',
                                                'suspended' => '<span class="badge bg-warning">Suspendu</span>'
                                            ];
                                            echo $statusBadge[$student['status']] ?? '<span class="badge bg-secondary">Inconnu</span>';
                                            ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="/tutoring/views/admin/students/show.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Voir les détails">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="/tutoring/views/admin/students/edit.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Modifier">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <?php if (hasRole(['admin'])): ?>
                                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $student['id']; ?>" title="Supprimer">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <!-- Modal de confirmation de suppression -->
                                            <div class="modal fade" id="deleteModal<?php echo $student['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $student['id']; ?>" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="deleteModalLabel<?php echo $student['id']; ?>">Confirmer la suppression</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>Êtes-vous sûr de vouloir supprimer l'étudiant <strong><?php echo h($student['first_name'] . ' ' . $student['last_name']); ?></strong> ?</p>
                                                            <p class="text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Cette action est irréversible et supprimera également toutes les données associées à cet étudiant.</p>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                            <form action="/tutoring/admin/students/delete.php" method="POST">
                                                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                                                <input type="hidden" name="id" value="<?php echo $student['id']; ?>">
                                                                <button type="submit" class="btn btn-danger">Supprimer</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                        <nav aria-label="Page navigation" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php
                                // Construire la query string pour les liens de pagination
                                $queryParams = $_GET;
                                
                                // Bouton précédent
                                echo '<li class="page-item' . ($page <= 1 ? ' disabled' : '') . '">';
                                if ($page > 1) {
                                    $queryParams['page'] = $page - 1;
                                    echo '<a class="page-link" href="?' . http_build_query($queryParams) . '">Précédent</a>';
                                } else {
                                    echo '<a class="page-link" href="#" tabindex="-1" aria-disabled="true">Précédent</a>';
                                }
                                echo '</li>';
                                
                                // Pages individuelles
                                $startPage = max(1, $page - 2);
                                $endPage = min($totalPages, $page + 2);
                                
                                // Toujours afficher la première page
                                if ($startPage > 1) {
                                    $queryParams['page'] = 1;
                                    echo '<li class="page-item"><a class="page-link" href="?' . http_build_query($queryParams) . '">1</a></li>';
                                    if ($startPage > 2) {
                                        echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                                    }
                                }
                                
                                // Pages centrales
                                for ($i = $startPage; $i <= $endPage; $i++) {
                                    $queryParams['page'] = $i;
                                    echo '<li class="page-item' . ($i == $page ? ' active' : '') . '">';
                                    echo '<a class="page-link' . ($i == $page ? ' text-white' : '') . '" href="?' . http_build_query($queryParams) . '">' . $i . '</a>';
                                    echo '</li>';
                                }
                                
                                // Toujours afficher la dernière page
                                if ($endPage < $totalPages) {
                                    if ($endPage < $totalPages - 1) {
                                        echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                                    }
                                    $queryParams['page'] = $totalPages;
                                    echo '<li class="page-item"><a class="page-link" href="?' . http_build_query($queryParams) . '">' . $totalPages . '</a></li>';
                                }
                                
                                // Bouton suivant
                                echo '<li class="page-item' . ($page >= $totalPages ? ' disabled' : '') . '">';
                                if ($page < $totalPages) {
                                    $queryParams['page'] = $page + 1;
                                    echo '<a class="page-link" href="?' . http_build_query($queryParams) . '">Suivant</a>';
                                } else {
                                    echo '<a class="page-link" href="#" tabindex="-1" aria-disabled="true">Suivant</a>';
                                }
                                echo '</li>';
                                ?>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Card View (Hidden by default) -->
                    <div id="cardsView" class="view-content" style="display: none;">
                        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                            <?php foreach($paginatedStudents as $student): ?>
                            <div class="col">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <div class="text-center mb-3">
                                            <?php if (!empty($student['profile_image'])): ?>
                                            <img src="<?php echo h($student['profile_image']); ?>" alt="Profile" class="rounded-circle" width="80" height="80">
                                            <?php else: ?>
                                            <div class="avatar mx-auto" style="width: 80px; height: 80px; background-color: #6c757d; color: white; display: flex; align-items: center; justify-content: center; border-radius: 50%;">
                                                <?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?>
                                            </div>
                                            <?php endif; ?>
                                            <h5 class="card-title mt-3"><?php echo h($student['first_name'] . ' ' . $student['last_name']); ?></h5>
                                            <p class="text-muted"><?php echo h($student['email']); ?></p>
                                            <?php
                                            $statusBadge = [
                                                'active' => '<span class="badge bg-success">Actif</span>',
                                                'graduated' => '<span class="badge bg-info">Diplômé</span>',
                                                'suspended' => '<span class="badge bg-warning">Suspendu</span>'
                                            ];
                                            echo $statusBadge[$student['status']] ?? '<span class="badge bg-secondary">Inconnu</span>';
                                            ?>
                                        </div>
                                        <ul class="list-group list-group-flush">
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <span>Numéro d'étudiant:</span>
                                                <span class="text-muted"><?php echo h($student['student_number']); ?></span>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <span>Programme:</span>
                                                <span class="text-muted"><?php echo h($student['program']); ?></span>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <span>Niveau:</span>
                                                <span class="text-muted"><?php echo h($student['level']); ?></span>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <span>Département:</span>
                                                <span class="text-muted"><?php echo h($student['department']); ?></span>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="card-footer bg-transparent border-top-0">
                                        <div class="d-flex justify-content-center">
                                            <a href="/tutoring/views/admin/students/show.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-outline-primary me-2">
                                                <i class="bi bi-eye me-1"></i>Détails
                                            </a>
                                            <a href="/tutoring/views/admin/students/edit.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-outline-secondary me-2">
                                                <i class="bi bi-pencil me-1"></i>Modifier
                                            </a>
                                            <?php if (hasRole(['admin'])): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $student['id']; ?>">
                                                <i class="bi bi-trash me-1"></i>Supprimer
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Pagination pour la vue carte -->
                        <?php if ($totalPages > 1): ?>
                        <nav aria-label="Page navigation" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php
                                // Construire la query string pour les liens de pagination
                                $queryParams = $_GET;
                                
                                // Bouton précédent
                                echo '<li class="page-item' . ($page <= 1 ? ' disabled' : '') . '">';
                                if ($page > 1) {
                                    $queryParams['page'] = $page - 1;
                                    echo '<a class="page-link" href="?' . http_build_query($queryParams) . '">Précédent</a>';
                                } else {
                                    echo '<a class="page-link" href="#" tabindex="-1" aria-disabled="true">Précédent</a>';
                                }
                                echo '</li>';
                                
                                // Pages individuelles
                                $startPage = max(1, $page - 2);
                                $endPage = min($totalPages, $page + 2);
                                
                                // Toujours afficher la première page
                                if ($startPage > 1) {
                                    $queryParams['page'] = 1;
                                    echo '<li class="page-item"><a class="page-link" href="?' . http_build_query($queryParams) . '">1</a></li>';
                                    if ($startPage > 2) {
                                        echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                                    }
                                }
                                
                                // Pages centrales
                                for ($i = $startPage; $i <= $endPage; $i++) {
                                    $queryParams['page'] = $i;
                                    echo '<li class="page-item' . ($i == $page ? ' active' : '') . '">';
                                    echo '<a class="page-link' . ($i == $page ? ' text-white' : '') . '" href="?' . http_build_query($queryParams) . '">' . $i . '</a>';
                                    echo '</li>';
                                }
                                
                                // Toujours afficher la dernière page
                                if ($endPage < $totalPages) {
                                    if ($endPage < $totalPages - 1) {
                                        echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                                    }
                                    $queryParams['page'] = $totalPages;
                                    echo '<li class="page-item"><a class="page-link" href="?' . http_build_query($queryParams) . '">' . $totalPages . '</a></li>';
                                }
                                
                                // Bouton suivant
                                echo '<li class="page-item' . ($page >= $totalPages ? ' disabled' : '') . '">';
                                if ($page < $totalPages) {
                                    $queryParams['page'] = $page + 1;
                                    echo '<a class="page-link" href="?' . http_build_query($queryParams) . '">Suivant</a>';
                                } else {
                                    echo '<a class="page-link" href="#" tabindex="-1" aria-disabled="true">Suivant</a>';
                                }
                                echo '</li>';
                                ?>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                    
                    <!-- List View (Hidden by default) -->
                    <div id="listView" class="view-content" style="display: none;">
                        <ul class="list-group">
                            <?php foreach($paginatedStudents as $student): ?>
                            <li class="list-group-item">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <div class="d-flex align-items-center">
                                            <?php if (!empty($student['profile_image'])): ?>
                                            <img src="<?php echo h($student['profile_image']); ?>" alt="Profile" class="rounded-circle me-3" width="50" height="50">
                                            <?php else: ?>
                                            <div class="avatar me-3" style="width: 50px; height: 50px; background-color: #6c757d; color: white; display: flex; align-items: center; justify-content: center; border-radius: 50%;">
                                                <?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?>
                                            </div>
                                            <?php endif; ?>
                                            <div>
                                                <h6 class="mb-0"><?php echo h($student['first_name'] . ' ' . $student['last_name']); ?></h6>
                                                <div class="text-muted small"><?php echo h($student['email']); ?></div>
                                                <div class="text-muted small">
                                                    Programme: <?php echo h($student['program']); ?> | 
                                                    Niveau: <?php echo h($student['level']); ?> |
                                                    <?php
                                                    $statusBadge = [
                                                        'active' => '<span class="badge bg-success">Actif</span>',
                                                        'graduated' => '<span class="badge bg-info">Diplômé</span>',
                                                        'suspended' => '<span class="badge bg-warning">Suspendu</span>'
                                                    ];
                                                    echo $statusBadge[$student['status']] ?? '<span class="badge bg-secondary">Inconnu</span>';
                                                    ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                        <a href="/tutoring/views/admin/students/show.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-outline-primary me-1">
                                            <i class="bi bi-eye me-1"></i>Détails
                                        </a>
                                        <a href="/tutoring/views/admin/students/edit.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-outline-secondary me-1">
                                            <i class="bi bi-pencil me-1"></i>Modifier
                                        </a>
                                        <?php if (hasRole(['admin'])): ?>
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $student['id']; ?>">
                                            <i class="bi bi-trash me-1"></i>Supprimer
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <!-- Pagination pour la vue liste -->
                        <?php if ($totalPages > 1): ?>
                        <nav aria-label="Page navigation" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php
                                // Construire la query string pour les liens de pagination
                                $queryParams = $_GET;
                                
                                // Bouton précédent
                                echo '<li class="page-item' . ($page <= 1 ? ' disabled' : '') . '">';
                                if ($page > 1) {
                                    $queryParams['page'] = $page - 1;
                                    echo '<a class="page-link" href="?' . http_build_query($queryParams) . '">Précédent</a>';
                                } else {
                                    echo '<a class="page-link" href="#" tabindex="-1" aria-disabled="true">Précédent</a>';
                                }
                                echo '</li>';
                                
                                // Pages individuelles
                                $startPage = max(1, $page - 2);
                                $endPage = min($totalPages, $page + 2);
                                
                                // Toujours afficher la première page
                                if ($startPage > 1) {
                                    $queryParams['page'] = 1;
                                    echo '<li class="page-item"><a class="page-link" href="?' . http_build_query($queryParams) . '">1</a></li>';
                                    if ($startPage > 2) {
                                        echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                                    }
                                }
                                
                                // Pages centrales
                                for ($i = $startPage; $i <= $endPage; $i++) {
                                    $queryParams['page'] = $i;
                                    echo '<li class="page-item' . ($i == $page ? ' active' : '') . '">';
                                    echo '<a class="page-link' . ($i == $page ? ' text-white' : '') . '" href="?' . http_build_query($queryParams) . '">' . $i . '</a>';
                                    echo '</li>';
                                }
                                
                                // Toujours afficher la dernière page
                                if ($endPage < $totalPages) {
                                    if ($endPage < $totalPages - 1) {
                                        echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                                    }
                                    $queryParams['page'] = $totalPages;
                                    echo '<li class="page-item"><a class="page-link" href="?' . http_build_query($queryParams) . '">' . $totalPages . '</a></li>';
                                }
                                
                                // Bouton suivant
                                echo '<li class="page-item' . ($page >= $totalPages ? ' disabled' : '') . '">';
                                if ($page < $totalPages) {
                                    $queryParams['page'] = $page + 1;
                                    echo '<a class="page-link" href="?' . http_build_query($queryParams) . '">Suivant</a>';
                                } else {
                                    echo '<a class="page-link" href="#" tabindex="-1" aria-disabled="true">Suivant</a>';
                                }
                                echo '</li>';
                                ?>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar - Quick actions and statistics -->
        <div class="col-lg-3">
            <!-- Quick Actions Card -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="m-0 font-weight-bold">Actions rapides</h5>
                    <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#quickActionsCollapse" aria-expanded="true" aria-controls="quickActionsCollapse">
                        <i class="bi bi-chevron-down"></i>
                    </button>
                </div>
                <div class="collapse show" id="quickActionsCollapse">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="/tutoring/views/admin/students/create.php" class="btn btn-primary">
                                <i class="bi bi-person-plus me-2"></i>Ajouter un étudiant
                            </a>
                            <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#importModal">
                                <i class="bi bi-upload me-2"></i>Importer des étudiants
                            </button>
                            <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#exportModal">
                                <i class="bi bi-download me-2"></i>Exporter les données
                            </button>
                            <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#filtersModal">
                                <i class="bi bi-funnel me-2"></i>Filtres avancés
                            </button>
                            <a href="/tutoring/views/admin/reports.php?type=students" class="btn btn-outline-info">
                                <i class="bi bi-graph-up me-2"></i>Rapports sur les étudiants
                            </a>
                        </div>
                        
                        <hr>
                        
                        <h6 class="mb-3">Statistiques</h6>
                        <div class="mb-3">
                            <label class="form-label d-flex justify-content-between">
                                <span>Étudiants actifs</span>
                                <span><?php echo $activeStudents; ?>/<?php echo $totalStudents; ?></span>
                            </label>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo ($totalStudents > 0) ? ($activeStudents / $totalStudents * 100) : 0; ?>%" aria-valuenow="<?php echo $activeStudents; ?>" aria-valuemin="0" aria-valuemax="<?php echo $totalStudents; ?>"></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label d-flex justify-content-between">
                                <span>Étudiants diplômés</span>
                                <span><?php echo $graduatedStudents; ?>/<?php echo $totalStudents; ?></span>
                            </label>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo ($totalStudents > 0) ? ($graduatedStudents / $totalStudents * 100) : 0; ?>%" aria-valuenow="<?php echo $graduatedStudents; ?>" aria-valuemin="0" aria-valuemax="<?php echo $totalStudents; ?>"></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label d-flex justify-content-between">
                                <span>Étudiants suspendus</span>
                                <span><?php echo $suspendedStudents; ?>/<?php echo $totalStudents; ?></span>
                            </label>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo ($totalStudents > 0) ? ($suspendedStudents / $totalStudents * 100) : 0; ?>%" aria-valuenow="<?php echo $suspendedStudents; ?>" aria-valuemin="0" aria-valuemax="<?php echo $totalStudents; ?>"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Latest Updates Card -->
            <div class="card">
                <div class="card-header">
                    <h5 class="m-0">Dernières mises à jour</h5>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">Nouvel étudiant inscrit</h6>
                                <small class="text-muted">Aujourd'hui</small>
                            </div>
                            <p class="mb-1">Julie Martin s'est inscrite au programme d'informatique.</p>
                        </li>
                        <li class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">Affectation complétée</h6>
                                <small class="text-muted">Hier</small>
                            </div>
                            <p class="mb-1">15 étudiants ont été affectés à leurs tuteurs.</p>
                        </li>
                        <li class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">Mise à jour des programmes</h6>
                                <small class="text-muted">3 jours</small>
                            </div>
                            <p class="mb-1">Les programmes d'études ont été mis à jour pour la session d'automne.</p>
                        </li>
                    </ul>
                </div>
                <div class="card-footer text-end">
                    <a href="#" class="btn btn-sm btn-outline-secondary">Voir tous les événements</a>
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
                <h5 class="modal-title" id="importModalLabel">Importer des étudiants</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Importez une liste d'étudiants à partir d'un fichier CSV. Le fichier doit contenir les colonnes suivantes:</p>
                <ul class="mb-4">
                    <li>Prénom</li>
                    <li>Nom</li>
                    <li>Email</li>
                    <li>Numéro d'étudiant</li>
                    <li>Programme</li>
                    <li>Niveau</li>
                    <li>Département (optionnel)</li>
                    <li>Statut (optionnel, par défaut "active")</li>
                </ul>
                <form id="importForm">
                    <div class="mb-3">
                        <label for="importFile" class="form-label">Fichier CSV</label>
                        <input class="form-control" type="file" id="importFile" accept=".csv">
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="headerRow" checked>
                            <label class="form-check-label" for="headerRow">
                                Le fichier contient une ligne d'en-tête
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="delimiter" class="form-label">Délimiteur</label>
                        <select class="form-select" id="delimiter">
                            <option value="," selected>Virgule (,)</option>
                            <option value=";">Point-virgule (;)</option>
                            <option value="\t">Tabulation</option>
                        </select>
                    </div>
                </form>
                
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    <span>Téléchargez un <a href="#" class="alert-link">modèle de fichier CSV</a> pour vous assurer que votre format est correct.</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary">Importer</button>
            </div>
        </div>
    </div>
</div>

<!-- Export Modal -->
<div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exportModalLabel">Exporter les données</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="exportForm" action="/tutoring/api/export/students.php" method="GET" target="_blank">
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
                                Exporter tous les étudiants
                            </label>
                        </div>
                        
                        <div class="form-check mb-2">
                            <input class="form-check-input export-filter" type="radio" name="exportFilter" id="exportFiltered" value="filtered">
                            <label class="form-check-label" for="exportFiltered">
                                Exporter uniquement les étudiants filtrés
                            </label>
                        </div>
                        
                        <!-- Champs cachés pour les filtres actuels -->
                        <?php if (isset($_GET['term'])): ?>
                            <input type="hidden" name="term" id="exportTerm" value="<?php echo h($_GET['term']); ?>">
                        <?php endif; ?>
                        
                        <?php if (isset($_GET['program'])): ?>
                            <input type="hidden" name="program" id="exportProgram" value="<?php echo h($_GET['program']); ?>">
                        <?php endif; ?>
                        
                        <?php if (isset($_GET['level'])): ?>
                            <input type="hidden" name="level" id="exportLevel" value="<?php echo h($_GET['level']); ?>">
                        <?php endif; ?>
                        
                        <?php if (isset($_GET['status'])): ?>
                            <input type="hidden" name="status" id="exportStatus" value="<?php echo h($_GET['status']); ?>">
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
                                <div class="form-check">
                                    <input class="form-check-input export-column" type="checkbox" name="columns[]" id="colStudentNum" value="student_number" checked>
                                    <label class="form-check-label" for="colStudentNum">Numéro d'étudiant</label>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-check mb-2">
                                    <input class="form-check-input export-column" type="checkbox" name="columns[]" id="colProgram" value="program" checked>
                                    <label class="form-check-label" for="colProgram">Programme</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input export-column" type="checkbox" name="columns[]" id="colLevel" value="level" checked>
                                    <label class="form-check-label" for="colLevel">Niveau</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input export-column" type="checkbox" name="columns[]" id="colDept" value="department" checked>
                                    <label class="form-check-label" for="colDept">Département</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input export-column" type="checkbox" name="columns[]" id="colStatus" value="status" checked>
                                    <label class="form-check-label" for="colStatus">Statut</label>
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
                <button type="button" class="btn btn-primary" id="exportSubmitBtn">Exporter</button>
            </div>
        </div>
    </div>
</div>

<!-- Advanced Filters Modal -->
<div class="modal fade" id="filtersModal" tabindex="-1" aria-labelledby="filtersModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="filtersModalLabel">Filtres avancés</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="advancedFiltersForm" action="" method="GET">
                    <div class="mb-3">
                        <label for="afProgram" class="form-label">Programme</label>
                        <select class="form-select" id="afProgram" name="program">
                            <option value="">Tous les programmes</option>
                            <?php foreach($programs as $program): ?>
                            <option value="<?php echo h($program); ?>"><?php echo h($program); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="afLevel" class="form-label">Niveau d'études</label>
                        <select class="form-select" id="afLevel" name="level">
                            <option value="">Tous les niveaux</option>
                            <?php foreach($levels as $level): ?>
                            <option value="<?php echo h($level); ?>"><?php echo h($level); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="afStatus" class="form-label">Statut</label>
                        <select class="form-select" id="afStatus" name="status">
                            <option value="">Tous les statuts</option>
                            <option value="active">Actif</option>
                            <option value="graduated">Diplômé</option>
                            <option value="suspended">Suspendu</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="afDepartment" class="form-label">Département</label>
                        <input type="text" class="form-control" id="afDepartment" name="department">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Affectation</label>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="assignment" id="afAssignmentAll" value="" checked>
                            <label class="form-check-label" for="afAssignmentAll">
                                Tous les étudiants
                            </label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="assignment" id="afAssignmentAssigned" value="assigned">
                            <label class="form-check-label" for="afAssignmentAssigned">
                                Étudiants avec tuteur
                            </label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="assignment" id="afAssignmentUnassigned" value="unassigned">
                            <label class="form-check-label" for="afAssignmentUnassigned">
                                Étudiants sans tuteur
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Date d'inscription</label>
                        <div class="input-group mb-3">
                            <span class="input-group-text">De</span>
                            <input type="date" class="form-control" name="date_from">
                        </div>
                        <div class="input-group">
                            <span class="input-group-text">À</span>
                            <input type="date" class="form-control" name="date_to">
                        </div>
                    </div>
                    
                    <input type="hidden" name="search" value="1">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-outline-secondary" id="resetFilters">Réinitialiser</button>
                <button type="button" class="btn btn-primary" id="applyFilters">Appliquer</button>
            </div>
        </div>
    </div>
</div>

<style>
/* Animation pour le fade-in */
.fade-in {
    opacity: 0;
    transform: translateY(20px);
    animation: fadeInUp 0.5s ease forwards;
}

@keyframes fadeInUp {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Délais pour l'animation */
.delay-1 { animation-delay: 0.1s; }
.delay-2 { animation-delay: 0.2s; }
.delay-3 { animation-delay: 0.3s; }
.delay-4 { animation-delay: 0.4s; }

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

/* Style pour les cartes de statistiques */
.stat-card {
    padding: 20px;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}

.stat-card .value {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--primary-color);
}

.stat-card .label {
    color: #7f8c8d;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 1px;
}

/* Style pour les avatar */
.avatar {
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser les tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Gestion des vues (tableau, cartes, liste)
    const viewButtons = document.querySelectorAll('[data-view]');
    const viewContents = document.querySelectorAll('.view-content');
    
    viewButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Retirer la classe active de tous les boutons
            viewButtons.forEach(btn => btn.classList.remove('active'));
            
            // Ajouter la classe active au bouton cliqué
            this.classList.add('active');
            
            // Masquer toutes les vues
            viewContents.forEach(content => {
                content.style.display = 'none';
            });
            
            // Afficher la vue sélectionnée
            const viewToShow = this.getAttribute('data-view');
            document.getElementById(viewToShow + 'View').style.display = 'block';
        });
    });
    
    // Gestion des filtres avancés
    const applyFiltersBtn = document.getElementById('applyFilters');
    const resetFiltersBtn = document.getElementById('resetFilters');
    const advancedFiltersForm = document.getElementById('advancedFiltersForm');
    
    if (applyFiltersBtn) {
        applyFiltersBtn.addEventListener('click', function() {
            advancedFiltersForm.submit();
        });
    }
    
    if (resetFiltersBtn) {
        resetFiltersBtn.addEventListener('click', function() {
            const inputs = advancedFiltersForm.querySelectorAll('input:not([type="hidden"]), select');
            inputs.forEach(input => {
                if (input.type === 'radio') {
                    if (input.value === '') input.checked = true;
                    else input.checked = false;
                } else if (input.type === 'checkbox') {
                    input.checked = false;
                } else {
                    input.value = '';
                }
            });
        });
    }
    
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
    
    // Animation pour les cartes statistiques
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach((card, index) => {
        card.classList.add('fade-in', `delay-${index + 1}`);
    });
});
</script>

<?php
// Inclure le pied de page
include_once __DIR__ . '/../common/footer.php';
?>