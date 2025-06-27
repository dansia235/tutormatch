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

/* Styles pour les colonnes triables */
.sortable {
    cursor: pointer;
    user-select: none;
    transition: background-color 0.2s ease;
    position: relative;
}

.sortable:hover {
    background-color: #e9ecef !important;
}

.sort-icon {
    font-size: 0.8rem;
    opacity: 0.6;
    transition: all 0.2s ease;
}

.sortable:hover .sort-icon {
    opacity: 1;
}

.sort-icon.text-primary {
    opacity: 1;
    font-weight: bold;
}

/* Animation pour le tri */
@keyframes sortHighlight {
    0% { background-color: #e3f2fd; }
    100% { background-color: transparent; }
}

.sortable.sorting {
    animation: sortHighlight 0.3s ease;
}

/* Indicateur de recherche active */
.search-active {
    border-color: #0d6efd !important;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important;
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
                <button class="btn btn-outline-primary" onclick="alert('Fonctionnalité d\'export à implémenter')">
                    <i class="bi bi-download me-2"></i>Exporter Rapport
                </button>
                <button class="btn btn-primary" onclick="alert('Fonctionnalité à implémenter')">
                    <i class="bi bi-plus me-2"></i>Nouvelle Évaluation
                </button>
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
                    <form action="" method="GET" class="d-flex" id="searchForm">
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
        </div>
    </div>
</div>

<script>
    // Variables globales pour les paramètres de page
    let currentPage = <?php echo $currentPageNum; ?>;
    let itemsPerPage = <?php echo $itemsPerPage; ?>;
    let statusFilter = '<?php echo $statusFilter ?? ''; ?>';
    let typeFilter = '<?php echo $typeFilter ?? ''; ?>';
    let searchTerm = '<?php echo $searchTerm; ?>';
    let sortBy = 'submission_date';
    let sortOrder = 'desc';

    // Fonction pour charger les statistiques
    async function loadStatistics() {
        try {
            const response = await fetch('/tutoring/api/evaluations/stats.php');
            const data = await response.json();
            
            if (data.success) {
                renderStatistics(data.data.cards);
            } else {
                console.error('Erreur lors du chargement des statistiques:', data.error);
            }
        } catch (error) {
            console.error('Erreur:', error);
        }
    }

    // Fonction pour charger les évaluations
    async function loadEvaluations() {
        try {
            const params = new URLSearchParams({
                page: currentPage,
                per_page: itemsPerPage,
                sort: sortBy,
                order: sortOrder
            });
            
            if (statusFilter) params.append('status', statusFilter);
            if (typeFilter) params.append('type', typeFilter);
            if (searchTerm) params.append('term', searchTerm);

            const response = await fetch(`/tutoring/api/evaluations/admin-list.php?${params}`);
            const data = await response.json();
            
            if (data.success) {
                renderEvaluations(data.data.evaluations, data.data.pagination);
            } else {
                console.error('Erreur lors du chargement des évaluations:', data.error);
            }
        } catch (error) {
            console.error('Erreur:', error);
        }
    }

    // Fonction pour afficher les statistiques
    function renderStatistics(cards) {
        const container = document.getElementById('statisticsCards');
        container.innerHTML = '';
        
        cards.forEach((card, index) => {
            const progressClass = `progress-bar ${card.changeType === 'positive' ? 'bg-success' : 
                card.changeType === 'negative' ? 'bg-danger' : 
                card.changeType === 'warning' ? 'bg-warning' : 'bg-info'}`;
            
            let progressWidth = 100;
            if (card.value.includes('/5')) {
                const scoreValue = parseFloat(card.value);
                progressWidth = (scoreValue / 5) * 100;
            }
            
            container.innerHTML += `
                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="value">${card.value}</div>
                        <div class="label">${card.title}</div>
                        <div class="progress mt-2">
                            <div class="${progressClass}" role="progressbar" 
                                 style="width: ${progressWidth}%;" 
                                 aria-valuenow="${progressWidth}" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                            </div>
                        </div>
                        <small class="text-muted">${card.change || card.linkText}</small>
                    </div>
                </div>
            `;
        });
    }

    // Fonction pour afficher les évaluations
    function renderEvaluations(evaluations, pagination) {
        const container = document.getElementById('evaluationsTableContainer');
        const countBadge = document.getElementById('evaluationCount');
        
        // Mettre à jour le badge de comptage
        if (pagination.total_items > 0) {
            countBadge.textContent = `${pagination.showing_from}-${pagination.showing_to} sur ${pagination.total_items} évaluations`;
        } else {
            countBadge.textContent = '0 évaluations';
        }
        
        if (evaluations.length === 0) {
            container.innerHTML = `
                <div class="alert alert-info m-3">
                    <i class="bi bi-info-circle me-2"></i>Aucune évaluation trouvée.
                </div>
            `;
            return;
        }

        // Construire le tableau
        let tableHTML = `
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th class="sortable" data-column="evaluator_name">
                                Évaluateur 
                                <i class="bi bi-arrow-down-up ms-1 sort-icon"></i>
                            </th>
                            <th class="sortable" data-column="evaluatee_name">
                                Évalué 
                                <i class="bi bi-arrow-down-up ms-1 sort-icon"></i>
                            </th>
                            <th class="sortable" data-column="type">
                                Type 
                                <i class="bi bi-arrow-down-up ms-1 sort-icon"></i>
                            </th>
                            <th class="sortable" data-column="score">
                                Score 
                                <i class="bi bi-arrow-down-up ms-1 sort-icon"></i>
                            </th>
                            <th class="sortable" data-column="status">
                                Statut 
                                <i class="bi bi-arrow-down-up ms-1 sort-icon"></i>
                            </th>
                            <th class="sortable" data-column="submission_date">
                                Date 
                                <i class="bi bi-arrow-down-up ms-1 sort-icon"></i>
                            </th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        evaluations.forEach(evaluation => {
            const roles = {'admin': 'Admin', 'coordinator': 'Coordinateur', 'teacher': 'Tuteur', 'student': 'Étudiant'};
            const typeLabels = {
                'mid_term': {label: 'Mi-parcours', class: 'bg-info'},
                'final': {label: 'Finale', class: 'bg-success'},
                'student': {label: 'Auto-éval.', class: 'bg-warning'},
                'supervisor': {label: 'Superviseur', class: 'bg-purple'},
                'teacher': {label: 'Enseignant', class: 'bg-primary'}
            };
            const statusLabels = {
                'completed': 'Complétée',
                'draft': 'Brouillon',
                'pending': 'En attente'
            };

            const typeInfo = typeLabels[evaluation.type] || {label: evaluation.type, class: 'bg-secondary'};
            
            let scoreHTML = '<span class="text-muted">Non noté</span>';
            if (evaluation.normalized_score !== null && evaluation.normalized_score !== undefined) {
                const score = parseFloat(evaluation.normalized_score);
                let scoreClass = '';
                if (score >= 4.0) scoreClass = 'score-excellent';
                else if (score >= 3.0) scoreClass = 'score-good';
                else if (score >= 2.0) scoreClass = 'score-average';
                else scoreClass = 'score-poor';
                
                scoreHTML = `<span class="score-badge ${scoreClass}">${score.toFixed(1)}/5</span>`;
            } else if (evaluation.score !== null) {
                // Fallback si normalized_score n'est pas disponible
                let score = parseFloat(evaluation.score);
                if (score > 5) score = score / 4; // Normaliser côté client
                let scoreClass = '';
                if (score >= 4.0) scoreClass = 'score-excellent';
                else if (score >= 3.0) scoreClass = 'score-good';
                else if (score >= 2.0) scoreClass = 'score-average';
                else scoreClass = 'score-poor';
                
                scoreHTML = `<span class="score-badge ${scoreClass}">${score.toFixed(1)}/5</span>`;
            }

            let dateHTML = '<span class="text-muted">Non soumise</span>';
            if (evaluation.submission_date) {
                const date = new Date(evaluation.submission_date);
                dateHTML = `
                    <div>${date.toLocaleDateString('fr-FR')}</div>
                    <div class="text-muted small">${date.toLocaleTimeString('fr-FR', {hour: '2-digit', minute: '2-digit'})}</div>
                `;
            }

            tableHTML += `
                <tr>
                    <td>
                        <div>
                            <div class="fw-bold">${evaluation.evaluator_name || 'N/A'}</div>
                            <div class="text-muted small">
                                <i class="bi bi-person-badge me-1"></i>
                                ${roles[evaluation.evaluator_role] || evaluation.evaluator_role}
                            </div>
                        </div>
                    </td>
                    <td>
                        <div>
                            <div class="fw-bold">${evaluation.evaluatee_name || 'N/A'}</div>
                            <div class="text-muted small">
                                <i class="bi bi-person me-1"></i>
                                ${roles[evaluation.evaluatee_role] || evaluation.evaluatee_role}
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge ${typeInfo.class} type-badge">
                            ${typeInfo.label}
                        </span>
                    </td>
                    <td>${scoreHTML}</td>
                    <td>
                        <i class="bi bi-circle-fill status-${evaluation.status} me-1"></i>
                        ${statusLabels[evaluation.status] || evaluation.status}
                    </td>
                    <td>${dateHTML}</td>
                    <td>
                        <div class="btn-group" role="group">
                            <button class="btn btn-sm btn-outline-primary" onclick="viewEvaluation(${evaluation.id})" 
                                    data-bs-toggle="tooltip" title="Voir les détails">
                                <i class="bi bi-eye"></i>
                            </button>
                            ${evaluation.status === 'draft' ? `
                            <button class="btn btn-sm btn-outline-secondary" onclick="editEvaluation(${evaluation.id})" 
                                    data-bs-toggle="tooltip" title="Modifier">
                                <i class="bi bi-pencil"></i>
                            </button>
                            ` : ''}
                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                    onclick="confirmDelete(${evaluation.id})" title="Supprimer">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });

        tableHTML += `
                    </tbody>
                </table>
            </div>
        `;

        // Ajouter la pagination si nécessaire
        if (pagination.total_pages > 1) {
            tableHTML += renderPagination(pagination);
        }

        container.innerHTML = tableHTML;
        
        // Mettre à jour les icônes de tri
        updateSortIcons();
        
        // Ajouter les événements de tri
        document.querySelectorAll('.sortable').forEach(header => {
            header.addEventListener('click', function() {
                const column = this.dataset.column;
                handleSort(column);
            });
        });
        
        // Initialiser les tooltips
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(element => {
            new bootstrap.Tooltip(element);
        });
    }

    // Fonction pour afficher la pagination
    function renderPagination(pagination) {
        let paginationHTML = `
            <div class="card-footer">
                <nav aria-label="Navigation des pages d'évaluations">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            Affichage de ${pagination.showing_from} à ${pagination.showing_to} sur ${pagination.total_items} résultats
                        </div>
                        <ul class="pagination pagination-sm mb-0">
        `;

        // Bouton précédent
        paginationHTML += `
            <li class="page-item ${pagination.current_page <= 1 ? 'disabled' : ''}">
                ${pagination.current_page > 1 ? 
                    `<a class="page-link" href="#" onclick="changePage(${pagination.current_page - 1})" aria-label="Précédent"><span aria-hidden="true">&laquo;</span></a>` :
                    `<span class="page-link" aria-label="Précédent"><span aria-hidden="true">&laquo;</span></span>`
                }
            </li>
        `;

        // Pages
        const startPage = Math.max(1, pagination.current_page - 2);
        const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);

        if (startPage > 1) {
            paginationHTML += `<li class="page-item"><a class="page-link" href="#" onclick="changePage(1)">1</a></li>`;
            if (startPage > 2) {
                paginationHTML += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            paginationHTML += `
                <li class="page-item ${i === pagination.current_page ? 'active' : ''}">
                    ${i === pagination.current_page ? 
                        `<span class="page-link">${i}</span>` :
                        `<a class="page-link" href="#" onclick="changePage(${i})">${i}</a>`
                    }
                </li>
            `;
        }

        if (endPage < pagination.total_pages) {
            if (endPage < pagination.total_pages - 1) {
                paginationHTML += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
            paginationHTML += `<li class="page-item"><a class="page-link" href="#" onclick="changePage(${pagination.total_pages})">${pagination.total_pages}</a></li>`;
        }

        // Bouton suivant
        paginationHTML += `
            <li class="page-item ${pagination.current_page >= pagination.total_pages ? 'disabled' : ''}">
                ${pagination.current_page < pagination.total_pages ? 
                    `<a class="page-link" href="#" onclick="changePage(${pagination.current_page + 1})" aria-label="Suivant"><span aria-hidden="true">&raquo;</span></a>` :
                    `<span class="page-link" aria-label="Suivant"><span aria-hidden="true">&raquo;</span></span>`
                }
            </li>
        `;

        paginationHTML += `
                        </ul>
                    </div>
                </nav>
            </div>
        `;

        return paginationHTML;
    }

    // Fonctions utilitaires
    function changePage(page) {
        currentPage = page;
        loadEvaluations();
    }
    
    // Fonction pour gérer le tri
    function handleSort(column) {
        // Animation visuelle
        const header = document.querySelector(`[data-column="${column}"]`);
        if (header) {
            header.classList.add('sorting');
            setTimeout(() => header.classList.remove('sorting'), 300);
        }
        
        if (sortBy === column) {
            // Inverser l'ordre si on clique sur la même colonne
            sortOrder = sortOrder === 'asc' ? 'desc' : 'asc';
        } else {
            // Nouvelle colonne, commencer par desc
            sortBy = column;
            sortOrder = 'desc';
        }
        
        // Revenir à la première page lors du tri
        currentPage = 1;
        loadEvaluations();
    }
    
    // Fonction pour mettre à jour les icônes de tri
    function updateSortIcons() {
        // Réinitialiser toutes les icônes
        document.querySelectorAll('.sort-icon').forEach(icon => {
            icon.className = 'bi bi-arrow-down-up ms-1 sort-icon text-muted';
        });
        
        // Mettre en évidence la colonne active
        const activeHeader = document.querySelector(`[data-column="${sortBy}"]`);
        if (activeHeader) {
            const icon = activeHeader.querySelector('.sort-icon');
            if (icon) {
                icon.className = `bi bi-arrow-${sortOrder === 'asc' ? 'up' : 'down'} ms-1 sort-icon text-primary`;
            }
        }
    }
    
    // Fonction pour effectuer une recherche
    function performSearch() {
        const searchInput = document.querySelector('input[name="term"]');
        searchTerm = searchInput ? searchInput.value.trim() : '';
        
        // Indicateur visuel de recherche active
        if (searchInput) {
            if (searchTerm.length > 0) {
                searchInput.classList.add('search-active');
            } else {
                searchInput.classList.remove('search-active');
            }
        }
        
        currentPage = 1; // Revenir à la première page
        loadEvaluations();
    }

    function changeItemsPerPage(value) {
        const url = new URL(window.location);
        url.searchParams.set('per_page', value);
        url.searchParams.set('page', '1');
        window.location.href = url.toString();
    }

    function viewEvaluation(id) {
        // Rediriger vers la page de détails (à implémenter)
        alert('Voir l\'évaluation #' + id + ' - Fonctionnalité à implémenter');
    }

    function editEvaluation(id) {
        // Rediriger vers la page d'édition (à implémenter)
        alert('Modifier l\'évaluation #' + id + ' - Fonctionnalité à implémenter');
    }

    function confirmDelete(id) {
        if (confirm('Êtes-vous sûr de vouloir supprimer cette évaluation ? Cette action est irréversible.')) {
            // Ici vous pouvez implémenter la suppression via API
            alert('Suppression de l\'évaluation #' + id + ' - Fonctionnalité à implémenter');
        }
    }

    // Initialisation au chargement de la page
    document.addEventListener('DOMContentLoaded', function() {
        loadStatistics();
        loadEvaluations();
        
        // Gestion du formulaire de recherche
        document.getElementById('searchForm').addEventListener('submit', function(e) {
            e.preventDefault();
            performSearch();
        });
        
        // Recherche en temps réel (optionnel)
        const searchInput = document.querySelector('input[name="term"]');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    performSearch();
                }, 500); // Délai de 500ms
            });
        }
    });
</script>

<?php
// Inclure le pied de page
include_once __DIR__ . '/../common/footer.php';
?>