<?php
/**
 * Vue pour la gestion des évaluations - Administration
 */

// Initialiser les variables
$pageTitle = 'Gestion des évaluations';
$currentPage = 'evaluations';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../includes/init.php';

// Vérifier les permissions (admin et coordinateur)
requireRole(['admin', 'coordinator']);

// Les données seront chargées via APIs JavaScript
$evaluations = [];
$statCards = [];
$totalEvaluations = 0;
$totalPages = 0;
$showingFrom = 0;
$showingTo = 0;
$itemsPerPage = isset($_GET['per_page']) ? max(10, min(100, (int)$_GET['per_page'])) : 10;
$currentPageNum = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$statusFilter = isset($_GET['status']) ? $_GET['status'] : null;
$typeFilter = isset($_GET['type']) ? $_GET['type'] : null;
$searchTerm = isset($_GET['term']) ? trim($_GET['term']) : '';

// Inclure l'en-tête
include_once __DIR__ . '/../common/header.php';
?>

<style>
/* Styles spécifiques pour la page des évaluations */
.evaluation-card {
    transition: all 0.3s ease;
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border-radius: 12px;
}

.evaluation-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.15);
}

.score-badge {
    font-size: 0.9rem;
    font-weight: 500;
    padding: 4px 8px;
    border-radius: 15px;
    min-width: 50px;
    text-align: center;
}

.score-excellent { background-color: #28a745; color: white; }
.score-good { background-color: #17a2b8; color: white; }
.score-average { background-color: #ffc107; color: black; }
.score-poor { background-color: #dc3545; color: white; }

.type-badge {
    font-size: 0.75rem;
    padding: 2px 6px;
    border-radius: 12px;
    text-transform: uppercase;
    font-weight: 500;
}

.status-completed { color: #28a745; }
.status-draft { color: #6c757d; }
.status-pending { color: #ffc107; }

.filter-tabs {
    border-radius: 8px;
    overflow: hidden;
    display: inline-flex;
    background-color: #f0f2f5;
}

.filter-tabs .filter-tab {
    padding: 10px 20px;
    border: none;
    background: none;
    transition: all 0.3s;
    font-weight: 500;
    cursor: pointer;
    color: #495057;
    text-decoration: none;
}

.filter-tabs .filter-tab.active {
    background-color: #3498db;
    color: white;
}

.filter-tabs .filter-tab:hover:not(.active) {
    background-color: #e9ecef;
}

/* Utilise les styles définis dans style.css - pas de redéfinition */

/* Supprimé - utilise les styles globaux */

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

<div class="container-fluid">
    <!-- Header section -->
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-clipboard-check me-2"></i>Gestion des Évaluations</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/tutoring/views/admin/dashboard.php">Tableau de bord</a></li>
                    <li class="breadcrumb-item active">Évaluations</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-12 text-end">
            <div class="btn-group">
                <a href="/tutoring/api/evaluations/export-report.php" class="btn btn-outline-primary">
                    <i class="bi bi-download me-2"></i>Exporter Rapport
                </a>
                <a href="/tutoring/views/admin/evaluations/create.php" class="btn btn-primary">
                    <i class="bi bi-plus me-2"></i>Nouvelle Évaluation
                </a>
            </div>
        </div>
    </div>
    
    <!-- Statistics cards -->
    <div class="row mb-4" id="statisticsCards">
        <!-- Les cartes seront chargées dynamiquement -->
        <div class="col-12 text-center">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Chargement...</span>
            </div>
        </div>
    </div>
    
    <!-- Filtres et recherche -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <form action="" method="GET" class="d-flex">
                        <input type="text" name="term" class="form-control me-2" placeholder="Rechercher..." value="<?php echo h($searchTerm); ?>">
                        <button type="submit" name="search" class="btn btn-outline-primary">
                            <i class="bi bi-search"></i>
                        </button>
                        <?php if (!empty($statusFilter)): ?>
                        <input type="hidden" name="status" value="<?php echo h($statusFilter); ?>">
                        <?php endif; ?>
                        <?php if (!empty($typeFilter)): ?>
                        <input type="hidden" name="type" value="<?php echo h($typeFilter); ?>">
                        <?php endif; ?>
                    </form>
                </div>
                <div class="col-md-8 text-md-end mt-3 mt-md-0">
                    <div class="d-flex align-items-center justify-content-md-end gap-3 flex-wrap">
                        <!-- Filtres par statut -->
                        <div class="filter-tabs">
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['status' => null])); ?>" 
                               class="filter-tab <?php echo empty($statusFilter) ? 'active' : ''; ?>">Tous</a>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['status' => 'completed'])); ?>" 
                               class="filter-tab <?php echo $statusFilter === 'completed' ? 'active' : ''; ?>">Complétées</a>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['status' => 'draft'])); ?>" 
                               class="filter-tab <?php echo $statusFilter === 'draft' ? 'active' : ''; ?>">Brouillons</a>
                        </div>
                        
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
    
    <!-- Liste des évaluations -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="bi bi-list-check me-2"></i>
                Liste des Évaluations
            </h5>
            <span class="badge bg-primary" id="evaluationCount">
                Chargement...
            </span>
        </div>
        <div class="card-body p-0" id="evaluationsTableContainer">
            <!-- Le contenu sera chargé dynamiquement -->
            <div class="text-center p-4">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Évaluateur</th>
                            <th>Évalué</th>
                            <th>Type</th>
                            <th>Score</th>
                            <th>Statut</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($evaluations as $evaluation): ?>
                        <tr>
                            <td>
                                <div>
                                    <div class="fw-bold"><?php echo h($evaluation['evaluator_name']); ?></div>
                                    <div class="text-muted small">
                                        <i class="bi bi-person-badge me-1"></i>
                                        <?php 
                                        $roles = ['admin' => 'Admin', 'coordinator' => 'Coordinateur', 'teacher' => 'Tuteur', 'student' => 'Étudiant'];
                                        echo $roles[$evaluation['evaluator_role']] ?? $evaluation['evaluator_role']; 
                                        ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <div class="fw-bold"><?php echo h($evaluation['evaluatee_name']); ?></div>
                                    <div class="text-muted small">
                                        <i class="bi bi-person me-1"></i>
                                        <?php echo $roles[$evaluation['evaluatee_role']] ?? $evaluation['evaluatee_role']; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php
                                $typeLabels = [
                                    'mid_term' => ['label' => 'Mi-parcours', 'class' => 'bg-info'],
                                    'final' => ['label' => 'Finale', 'class' => 'bg-success'],
                                    'student' => ['label' => 'Auto-éval.', 'class' => 'bg-warning'],
                                    'supervisor' => ['label' => 'Superviseur', 'class' => 'bg-purple'],
                                    'teacher' => ['label' => 'Enseignant', 'class' => 'bg-primary']
                                ];
                                $typeInfo = $typeLabels[$evaluation['type']] ?? ['label' => $evaluation['type'], 'class' => 'bg-secondary'];
                                ?>
                                <span class="badge <?php echo $typeInfo['class']; ?> type-badge">
                                    <?php echo $typeInfo['label']; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($evaluation['score'] !== null): ?>
                                    <?php
                                    $score = (float)$evaluation['score'];
                                    $scoreClass = '';
                                    if ($score >= 4.0) $scoreClass = 'score-excellent';
                                    elseif ($score >= 3.0) $scoreClass = 'score-good';
                                    elseif ($score >= 2.0) $scoreClass = 'score-average';
                                    else $scoreClass = 'score-poor';
                                    ?>
                                    <span class="score-badge <?php echo $scoreClass; ?>">
                                        <?php echo number_format($score, 1); ?>/5
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">Non noté</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <i class="bi bi-circle-fill status-<?php echo $evaluation['status']; ?> me-1"></i>
                                <?php
                                $statusLabels = [
                                    'completed' => 'Complétée',
                                    'draft' => 'Brouillon',
                                    'pending' => 'En attente'
                                ];
                                echo $statusLabels[$evaluation['status']] ?? $evaluation['status'];
                                ?>
                            </td>
                            <td>
                                <?php if ($evaluation['submission_date']): ?>
                                    <div><?php echo date('d/m/Y', strtotime($evaluation['submission_date'])); ?></div>
                                    <div class="text-muted small"><?php echo date('H:i', strtotime($evaluation['submission_date'])); ?></div>
                                <?php else: ?>
                                    <span class="text-muted">Non soumise</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="/tutoring/views/admin/evaluations/show.php?id=<?php echo $evaluation['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Voir les détails">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php if ($evaluation['status'] === 'draft'): ?>
                                    <a href="/tutoring/views/admin/evaluations/edit.php?id=<?php echo $evaluation['id']; ?>" 
                                       class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Modifier">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                            data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $evaluation['id']; ?>" 
                                            title="Supprimer">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                                
                                <!-- Modal de confirmation de suppression -->
                                <div class="modal fade" id="deleteModal<?php echo $evaluation['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Confirmer la suppression</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Êtes-vous sûr de vouloir supprimer cette évaluation ?</p>
                                                <p class="text-danger">
                                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                                    Cette action est irréversible.
                                                </p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                <form action="/tutoring/views/admin/evaluations/delete.php" method="POST" class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                                    <input type="hidden" name="id" value="<?php echo $evaluation['id']; ?>">
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
            <div class="card-footer">
                <nav aria-label="Navigation des pages d'évaluations">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            <?php if ($totalEvaluations > 0): ?>
                                Affichage de <?php echo $showingFrom; ?> à <?php echo $showingTo; ?> sur <?php echo $totalEvaluations; ?> résultats
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
            <?php endif; ?>
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
</script>

<?php
// Inclure le pied de page
include_once __DIR__ . '/../common/footer.php';
?>