<?php
/**
 * Vue pour la liste des affectations
 */

// Initialiser les variables
$pageTitle = 'Gestion des affectations';
$currentPage = 'assignments';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../includes/init.php';

// Vérifier les permissions
requireRole(['admin', 'coordinator']);

// Instancier le contrôleur
$assignmentController = new AssignmentController($db);

// Créer une instance du modèle Assignment
$assignmentModel = new Assignment($db);

// Configuration de la pagination
$itemsPerPage = isset($_GET['per_page']) ? max(10, min(100, (int)$_GET['per_page'])) : 10; // Nombre d'éléments par page
$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

// Traiter la recherche ou afficher toutes les affectations
if (isset($_GET['search'])) {
    $term = isset($_GET['term']) ? $_GET['term'] : '';
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    
    // Compter le total d'abord pour la pagination
    $allAssignments = $assignmentModel->search($term, $status);
    $totalAssignments = count($allAssignments);
    
    // Récupérer les affectations avec pagination
    $assignments = array_slice($allAssignments, $offset, $itemsPerPage);
} else {
    // Afficher toutes les affectations ou filtrer par statut
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    
    // Compter le total d'abord pour la pagination
    $allAssignments = $assignmentModel->getAll($status);
    $totalAssignments = count($allAssignments);
    
    // Récupérer les affectations avec pagination
    $assignments = array_slice($allAssignments, $offset, $itemsPerPage);
}

// Calculer les informations de pagination
$totalPages = ceil($totalAssignments / $itemsPerPage);
$showingFrom = $totalAssignments > 0 ? $offset + 1 : 0;
$showingTo = min($offset + $itemsPerPage, $totalAssignments);

// Calculer les statistiques par statut (sur toutes les affectations, pas seulement la page courante)
$pendingCount = 0;
$confirmedCount = 0;
$rejectedCount = 0;
$completedCount = 0;

foreach ($allAssignments as $assignment) {
    if ($assignment['status'] === 'pending') {
        $pendingCount++;
    } elseif ($assignment['status'] === 'confirmed') {
        $confirmedCount++;
    } elseif ($assignment['status'] === 'rejected') {
        $rejectedCount++;
    } elseif ($assignment['status'] === 'completed') {
        $completedCount++;
    }
}

// Inclure l'en-tête
include_once __DIR__ . '/../common/header.php';

// Définir le filtre actif
$activeFilter = isset($_GET['status']) ? $_GET['status'] : '';
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
    
    /* Filtres */
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
    
    /* Status badges */
    .badge-pending {
        background-color: #f1c40f;
    }
    
    .badge-confirmed {
        background-color: #2ecc71;
    }
    
    .badge-rejected {
        background-color: #e74c3c;
    }
    
    .badge-completed {
        background-color: #3498db;
    }
    
    /* Avatar placeholder */
    .avatar-sm {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background-color: #3498db;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        font-weight: 600;
    }
    
    /* Score visualization */
    .score-container {
        display: flex;
        align-items: center;
    }
    
    .score-progress {
        flex-grow: 1;
        margin-right: 8px;
        height: 6px;
    }
    
    .score-value {
        font-size: 0.8rem;
        min-width: 30px;
        text-align: right;
    }
    
    /* Secondary button */
    .btn-outline-secondary-alt {
        border-color: #dee2e6;
        color: #6c757d;
    }
    
    .btn-outline-secondary-alt:hover {
        background-color: #f8f9fa;
        border-color: #dee2e6;
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

<div class="container-fluid mt-4">
    <!-- Titre de la page et bouton d'ajout -->
    <div class="page-header">
        <div>
            <h2><i class="bi bi-diagram-3 me-2"></i>Gestion des affectations</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/tutoring/views/admin/dashboard.php">Tableau de bord</a></li>
                    <li class="breadcrumb-item active">Affectations</li>
                </ol>
            </nav>
        </div>
        
        <div class="btn-group">
            <a href="/tutoring/views/admin/assignments/create.php" class="btn btn-primary add-button">
                <i class="bi bi-plus-circle"></i>Créer une affectation
            </a>
            <a href="/tutoring/views/admin/assignments/generate.php" class="btn btn-outline-primary">
                <i class="bi bi-magic"></i>Générer automatiquement
            </a>
        </div>
    </div>
    
    <!-- Barre de recherche et filtres -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="search-container">
                        <i class="bi bi-search search-icon"></i>
                        <input type="text" class="form-control" id="searchInput" placeholder="Rechercher une affectation...">
                    </div>
                </div>
                <div class="col-md-6 text-md-end mt-3 mt-md-0">
                    <div class="d-flex align-items-center justify-content-md-end gap-3">
                        <!-- Filtres par statut -->
                        <div class="btn-group" role="group" aria-label="Filtres par statut">
                            <input type="radio" class="btn-check" name="statusFilter" id="status-all" value="" checked>
                            <label class="btn btn-outline-primary" for="status-all">Toutes</label>
                            
                            <input type="radio" class="btn-check" name="statusFilter" id="status-pending" value="pending">
                            <label class="btn btn-outline-warning" for="status-pending">En attente</label>
                            
                            <input type="radio" class="btn-check" name="statusFilter" id="status-confirmed" value="confirmed">
                            <label class="btn btn-outline-success" for="status-confirmed">Confirmées</label>
                            
                            <input type="radio" class="btn-check" name="statusFilter" id="status-rejected" value="rejected">
                            <label class="btn btn-outline-danger" for="status-rejected">Rejetées</label>
                            
                            <input type="radio" class="btn-check" name="statusFilter" id="status-completed" value="completed">
                            <label class="btn btn-outline-info" for="status-completed">Terminées</label>
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
        </div>
    </div>
    
    <!-- Cartes statistiques -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-value"><?php echo $totalAssignments; ?></div>
                <div class="stat-label">Affectations totales</div>
                <div class="progress">
                    <div class="progress-bar" role="progressbar" style="width: 100%;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-value"><?php echo $pendingCount; ?></div>
                <div class="stat-label">En attente</div>
                <div class="progress">
                    <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $totalAssignments > 0 ? ($pendingCount / $totalAssignments) * 100 : 0; ?>%;" aria-valuenow="<?php echo $pendingCount; ?>" aria-valuemin="0" aria-valuemax="<?php echo $totalAssignments; ?>"></div>
                </div>
                <div class="small text-muted mt-2"><?php echo $totalAssignments > 0 ? number_format(($pendingCount / $totalAssignments) * 100, 0) : 0; ?>% des affectations</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-value"><?php echo $confirmedCount; ?></div>
                <div class="stat-label">Confirmées</div>
                <div class="progress">
                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $totalAssignments > 0 ? ($confirmedCount / $totalAssignments) * 100 : 0; ?>%;" aria-valuenow="<?php echo $confirmedCount; ?>" aria-valuemin="0" aria-valuemax="<?php echo $totalAssignments; ?>"></div>
                </div>
                <div class="small text-muted mt-2"><?php echo $totalAssignments > 0 ? number_format(($confirmedCount / $totalAssignments) * 100, 0) : 0; ?>% des affectations</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-value"><?php echo $completedCount; ?></div>
                <div class="stat-label">Terminées</div>
                <div class="progress">
                    <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $totalAssignments > 0 ? ($completedCount / $totalAssignments) * 100 : 0; ?>%;" aria-valuenow="<?php echo $completedCount; ?>" aria-valuemin="0" aria-valuemax="<?php echo $totalAssignments; ?>"></div>
                </div>
                <div class="small text-muted mt-2"><?php echo $totalAssignments > 0 ? number_format(($completedCount / $totalAssignments) * 100, 0) : 0; ?>% des affectations</div>
            </div>
        </div>
    </div>
    
    <!-- Liste des affectations -->
    <div class="card">
        <div class="card-body p-4">
            <div class="list-header">
                <h4><i class="bi bi-list me-2"></i>Liste des affectations</h4>
                <span class="count-badge" id="total-count">Chargement...</span>
            </div>
            
            <div id="assignments-container">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                    <p class="mt-2 text-muted">Chargement des affectations...</p>
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

<script>
    class AssignmentsTable {
        constructor() {
            this.apiUrl = '/tutoring/api/assignments/admin-list.php';
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
                    this.renderAssignments(result.data.assignments);
                    this.renderPagination(result.data.pagination);
                } else {
                    this.showError(result.error || 'Erreur inconnue');
                }
            } catch (error) {
                this.showError('Erreur lors du chargement des données: ' + error.message);
            }
        }
        
        renderAssignments(assignments) {
            const container = document.getElementById('assignments-container');
            const totalCount = document.getElementById('total-count');
            
            if (assignments.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-4">
                        <i class="bi bi-info-circle text-muted" style="font-size: 3rem;"></i>
                        <p class="mt-2 text-muted">Aucune affectation trouvée.</p>
                    </div>
                `;
                totalCount.textContent = '0 affectations';
                return;
            }
            
            const tableHtml = `
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Étudiant</th>
                                <th>Tuteur</th>
                                <th>Stage</th>
                                <th>Date</th>
                                <th>Statut</th>
                                <th>Scores</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${assignments.map(assignment => `
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm me-2">
                                                ${this.getInitials(assignment.student_name || '')}
                                            </div>
                                            <div>
                                                <div class="fw-bold">${this.escapeHtml(assignment.student_name || '')}</div>
                                                <div class="text-muted small">${this.escapeHtml(assignment.student_program || '')}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm me-2">
                                                ${this.getInitials(assignment.teacher_name || '')}
                                            </div>
                                            <div>
                                                <div class="fw-bold">${this.escapeHtml(assignment.teacher_name || '')}</div>
                                                <div class="text-muted small">${this.escapeHtml(assignment.student_department || '')}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-bold">${this.escapeHtml(assignment.internship_title || '')}</div>
                                        <div class="text-muted small">${this.escapeHtml(assignment.company_name || '')}</div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span><i class="bi bi-calendar-event me-1"></i> ${assignment.assignment_date ? new Date(assignment.assignment_date).toLocaleDateString('fr-FR') : ''}</span>
                                            ${assignment.start_date ? `<span><i class="bi bi-calendar-check me-1"></i> ${new Date(assignment.start_date).toLocaleDateString('fr-FR')}</span>` : ''}
                                        </div>
                                    </td>
                                    <td>
                                        ${this.getStatusBadge(assignment.status)}
                                    </td>
                                    <td>
                                        <div class="text-muted small">-</div>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="/tutoring/views/admin/assignments/show.php?id=${assignment.id}" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Voir les détails">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="/tutoring/views/admin/assignments/edit.php?id=${assignment.id}" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Modifier">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="openDeleteModal(${assignment.id}, '${assignment.student_name}', '${assignment.internship_title}')" title="Supprimer">
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
            const totalCount = document.getElementById('total-count');
            const paginationInfo = document.getElementById('pagination-info');
            const paginationControls = document.getElementById('pagination-controls');
            
            // Mettre à jour le compteur total
            if (pagination.total_items > 0) {
                totalCount.textContent = `${pagination.showing_from}-${pagination.showing_to} sur ${pagination.total_items} affectations`;
            } else {
                totalCount.textContent = '0 affectations';
            }
            
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
                    <a class="page-link" href="#" onclick="assignmentsTable.changePage(${pagination.current_page - 1}); return false;" aria-label="Précédent">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
            `;
            
            // Pages
            const startPage = Math.max(1, pagination.current_page - 2);
            const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);
            
            if (startPage > 1) {
                paginationHtml += `<li class="page-item"><a class="page-link" href="#" onclick="assignmentsTable.changePage(1); return false;">1</a></li>`;
                if (startPage > 2) {
                    paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                }
            }
            
            for (let i = startPage; i <= endPage; i++) {
                paginationHtml += `
                    <li class="page-item ${i === pagination.current_page ? 'active' : ''}">
                        <a class="page-link" href="#" onclick="assignmentsTable.changePage(${i}); return false;">${i}</a>
                    </li>
                `;
            }
            
            if (endPage < pagination.total_pages) {
                if (endPage < pagination.total_pages - 1) {
                    paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                }
                paginationHtml += `<li class="page-item"><a class="page-link" href="#" onclick="assignmentsTable.changePage(${pagination.total_pages}); return false;">${pagination.total_pages}</a></li>`;
            }
            
            // Bouton Suivant
            paginationHtml += `
                <li class="page-item ${pagination.current_page >= pagination.total_pages ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="assignmentsTable.changePage(${pagination.current_page + 1}); return false;" aria-label="Suivant">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            `;
            
            paginationControls.innerHTML = paginationHtml;
        }
        
        changePage(page) {
            this.currentPage = page;
            this.loadData();
        }
        
        showError(message) {
            const container = document.getElementById('assignments-container');
            container.innerHTML = `
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    ${message}
                </div>
            `;
        }
        
        getStatusBadge(status) {
            const badges = {
                'pending': '<span class="badge bg-warning">En attente</span>',
                'confirmed': '<span class="badge bg-success">Confirmée</span>',
                'rejected': '<span class="badge bg-danger">Rejetée</span>',
                'completed': '<span class="badge bg-info">Terminée</span>'
            };
            return badges[status] || `<span class="badge bg-secondary">${this.escapeHtml(status)}</span>`;
        }
        
        getInitials(name) {
            const words = name.split(' ');
            const initials = words.map(word => word.charAt(0).toUpperCase()).slice(0, 2).join('');
            return initials || '??';
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
    let assignmentsTable;
    document.addEventListener('DOMContentLoaded', function() {
        assignmentsTable = new AssignmentsTable();
    });
    
    // Fonction pour ouvrir le modal de suppression
    function openDeleteModal(id, studentName, internshipTitle) {
        // Cette fonction devra être implémentée selon les besoins
        alert(`Suppression de l'affectation #${id} - Fonctionnalité à implémenter`);
    }
</script>

<?php
// Inclure le pied de page
include_once __DIR__ . '/../common/footer.php';
?>