<?php
/**
 * Vue pour la gestion des documents - Page principale
 */

// Initialiser les variables
$pageTitle = 'Gestion des documents';
$currentPage = 'documents';
$extraStyles = '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../includes/init.php';

// Vérifier les permissions
requireRole(['admin', 'coordinator']);

// Afficher tous les documents ou filtrer par catégorie
$category = isset($_GET['category']) ? $_GET['category'] : null;
// Si category est un array (erreur dans l'URL), prendre null
if (is_array($category)) {
    $category = null;
}
$search = isset($_GET['term']) ? $_GET['term'] : null;

// Configuration pour l'affichage initial (les données seront chargées via API)
$itemsPerPage = isset($_GET['per_page']) ? max(10, min(100, (int)$_GET['per_page'])) : 10;
$currentPageNum = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

// Variables pour les statistiques uniquement
$documentModel = new Document($db);

// Récupérer les statistiques
$stats = $documentModel->countByCategory();

// Inclure l'en-tête
include_once __DIR__ . '/../common/header.php';
?>

<div class="container-fluid">
    <!-- En-tête de page -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2 mb-0"><i class="bi bi-file-earmark-text me-2"></i>Gestion des documents</h1>
        
        <div class="btn-group">
            <a href="/tutoring/views/admin/documents/create.php" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i>Ajouter un document
            </a>
            <a href="/tutoring/views/admin/documents/my-documents.php" class="btn btn-outline-primary">
                <i class="bi bi-person me-2"></i>Mes documents
            </a>
        </div>
    </div>
    
    <!-- Filtres et recherche -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <form id="searchForm" class="d-flex">
                        <input type="text" name="term" class="form-control me-2" placeholder="Rechercher un document..." value="<?php echo isset($_GET['term']) ? h($_GET['term']) : ''; ?>">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="bi bi-search"></i>
                        </button>
                    </form>
                </div>
                <div class="col-md-8 text-md-end mt-3 mt-md-0">
                    <div class="d-flex align-items-center justify-content-md-end gap-3 flex-wrap">
                        <!-- Filtres par catégorie -->
                        <div class="btn-group" role="group" aria-label="Filtres par catégorie">
                            <input type="radio" class="btn-check" name="categoryFilter" id="category-all" value="" checked>
                            <label class="btn btn-outline-primary" for="category-all">Tous</label>
                            
                            <input type="radio" class="btn-check" name="categoryFilter" id="category-contract" value="contract">
                            <label class="btn btn-outline-primary" for="category-contract">Contrats</label>
                            
                            <input type="radio" class="btn-check" name="categoryFilter" id="category-report" value="report">
                            <label class="btn btn-outline-primary" for="category-report">Rapports</label>
                            
                            <input type="radio" class="btn-check" name="categoryFilter" id="category-evaluation" value="evaluation">
                            <label class="btn btn-outline-primary" for="category-evaluation">Évaluations</label>
                            
                            <input type="radio" class="btn-check" name="categoryFilter" id="category-certificate" value="certificate">
                            <label class="btn btn-outline-primary" for="category-certificate">Certificats</label>
                            
                            <input type="radio" class="btn-check" name="categoryFilter" id="category-other" value="other">
                            <label class="btn btn-outline-primary" for="category-other">Autres</label>
                        </div>
                        
                        <!-- Sélecteur du nombre d'éléments par page -->
                        <div class="d-flex align-items-center">
                            <label for="itemsPerPage" class="form-label me-2 mb-0 text-muted small">Afficher:</label>
                            <select id="itemsPerPage" class="form-select form-select-sm" style="width: auto;">
                                <option value="10">10</option>
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
    
    <!-- Statistiques des documents -->
    <div class="row mb-4">
        <?php 
        $categories = [
            'contract' => ['name' => 'Contrats', 'icon' => 'bi-file-earmark-text', 'color' => 'primary'],
            'report' => ['name' => 'Rapports', 'icon' => 'bi-file-text', 'color' => 'success'],
            'evaluation' => ['name' => 'Évaluations', 'icon' => 'bi-file-check', 'color' => 'warning'],
            'certificate' => ['name' => 'Certificats', 'icon' => 'bi-file-earmark-check', 'color' => 'info'],
            'other' => ['name' => 'Autres', 'icon' => 'bi-file', 'color' => 'dark']
        ];
        
        foreach ($categories as $key => $categoryInfo):
            $count = $stats[$key] ?? 0;
        ?>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-<?php echo $categoryInfo['color']; ?> h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-light p-3 me-3">
                            <i class="bi <?php echo $categoryInfo['icon']; ?> text-<?php echo $categoryInfo['color']; ?> fs-4"></i>
                        </div>
                        <div>
                            <h5 class="card-title mb-0"><?php echo $categoryInfo['name']; ?></h5>
                            <p class="text-muted mb-0"><?php echo $count; ?> document<?php echo $count > 1 ? 's' : ''; ?></p>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-top-0">
                    <a href="?category=<?php echo $key; ?>" class="btn btn-sm btn-outline-<?php echo $categoryInfo['color']; ?> w-100">
                        <i class="bi bi-eye me-2"></i>Voir
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Liste des documents -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <h5 class="card-title mb-0 me-2">
                    <i class="bi bi-list-check me-2"></i>
                    Liste des documents
                </h5>
                <span class="badge bg-primary" id="documentCount">
                    Chargement...
                </span>
            </div>
            
        </div>
        <div class="card-body p-0" id="documentsTableContainer">
            <!-- Le contenu sera chargé dynamiquement -->
            <div class="text-center p-4">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
            </div>
    </div>
</div>

<style>
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

<script>
    // Variables globales pour les paramètres de page
    let currentPage = <?php echo $currentPageNum; ?>;
    let itemsPerPage = <?php echo $itemsPerPage; ?>;
    let categoryFilter = '<?php echo $category ?? ''; ?>';
    let searchTerm = '<?php echo $search ?? ''; ?>';
    let sortBy = 'created_at';
    let sortOrder = 'desc';

    // Fonction pour charger les documents
    async function loadDocuments() {
        try {
            const params = new URLSearchParams({
                page: currentPage,
                per_page: itemsPerPage,
                sort: sortBy,
                order: sortOrder
            });
            
            if (categoryFilter) params.append('category', categoryFilter);
            if (searchTerm) params.append('term', searchTerm);

            const response = await fetch(`/tutoring/api/documents/admin-list.php?${params}`);
            const data = await response.json();
            
            if (data.success) {
                renderDocuments(data.data.documents, data.data.pagination);
            } else {
                console.error('Erreur lors du chargement des documents:', data.error);
                showErrorDocuments('Erreur lors du chargement des documents.');
            }
        } catch (error) {
            console.error('Erreur:', error);
            showErrorDocuments('Erreur de connexion lors du chargement des documents.');
        }
    }

    // Fonction pour afficher les documents
    function renderDocuments(documents, pagination) {
        const container = document.getElementById('documentsTableContainer');
        const countBadge = document.getElementById('documentCount');
        
        // Mettre à jour le badge de comptage
        if (pagination.total_items > 0) {
            countBadge.textContent = `${pagination.showing_from}-${pagination.showing_to} sur ${pagination.total_items} documents`;
        } else {
            countBadge.textContent = '0 documents';
        }
        
        if (documents.length === 0) {
            container.innerHTML = `
                <div class="alert alert-info m-3">
                    <i class="bi bi-info-circle me-2"></i>Aucun document trouvé.
                </div>
            `;
            return;
        }

        // Construire le tableau
        let tableHTML = `
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead class="table-light">
                        <tr>
                            <th class="sortable" data-column="title">
                                Titre
                                <i class="bi bi-arrow-down-up ms-1 sort-icon"></i>
                            </th>
                            <th class="sortable" data-column="type">
                                Catégorie
                                <i class="bi bi-arrow-down-up ms-1 sort-icon"></i>
                            </th>
                            <th class="sortable" data-column="user_name">
                                Utilisateur
                                <i class="bi bi-arrow-down-up ms-1 sort-icon"></i>
                            </th>
                            <th class="sortable" data-column="created_at">
                                Date
                                <i class="bi bi-arrow-down-up ms-1 sort-icon"></i>
                            </th>
                            <th class="sortable" data-column="file_size">
                                Taille
                                <i class="bi bi-arrow-down-up ms-1 sort-icon"></i>
                            </th>
                            <th class="sortable" data-column="visibility">
                                Visibilité
                                <i class="bi bi-arrow-down-up ms-1 sort-icon"></i>
                            </th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        documents.forEach(document => {
            // Icônes selon le type de fichier
            let iconClass = 'bi-file';
            if (document.file_type) {
                if (document.file_type.includes('pdf')) iconClass = 'bi-file-pdf';
                else if (document.file_type.includes('word')) iconClass = 'bi-file-word';
                else if (document.file_type.includes('excel') || document.file_type.includes('sheet')) iconClass = 'bi-file-excel';
                else if (document.file_type.includes('powerpoint') || document.file_type.includes('presentation')) iconClass = 'bi-file-slides';
                else if (document.file_type.includes('image')) iconClass = 'bi-file-image';
                else if (document.file_type.includes('zip') || document.file_type.includes('rar')) iconClass = 'bi-file-zip';
                else if (document.file_type.includes('text')) iconClass = 'bi-file-text';
            }
            
            // Badges de catégorie
            const categoryLabels = {
                'contract': '<span class="badge bg-primary">Contrat</span>',
                'report': '<span class="badge bg-success">Rapport</span>',
                'evaluation': '<span class="badge bg-warning">Évaluation</span>',
                'certificate': '<span class="badge bg-info">Certificat</span>',
                'other': '<span class="badge bg-dark">Autre</span>'
            };
            const categoryHTML = categoryLabels[document.type] || '<span class="badge bg-secondary">Inconnu</span>';
            
            // Badges de visibilité
            const visibilityLabels = {
                'private': '<span class="badge bg-danger">Privé</span>',
                'public': '<span class="badge bg-success">Public</span>',
                'restricted': '<span class="badge bg-warning">Restreint</span>'
            };
            const visibilityHTML = visibilityLabels[document.visibility] || '<span class="badge bg-secondary">Inconnu</span>';
            
            // Initiales utilisateur
            const firstName = document.user_name ? document.user_name.split(' ')[0] : '';
            const lastName = document.user_name ? document.user_name.split(' ')[1] || '' : '';
            const initials = (firstName.charAt(0) + lastName.charAt(0)).toUpperCase();
            
            tableHTML += `
                <tr>
                    <td>
                        <div class="d-flex align-items-center">
                            <i class="bi ${iconClass} me-2 fs-4"></i>
                            <div>
                                <div class="fw-bold">${escapeHtmlDocuments(document.title || '')}</div>
                                ${document.description ? `<div class="text-muted small">${escapeHtmlDocuments(document.description.substring(0, 50))}${document.description.length > 50 ? '...' : ''}</div>` : ''}
                            </div>
                        </div>
                    </td>
                    <td>${categoryHTML}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="avatar-sm me-2">${initials}</div>
                            <div>
                                <div>${escapeHtmlDocuments(document.user_name || 'Inconnu')}</div>
                                <div class="text-muted small">${escapeHtmlDocuments(document.user_email || '')}</div>
                            </div>
                        </div>
                    </td>
                    <td>${document.created_at_formatted}</td>
                    <td>${document.file_size_formatted}</td>
                    <td>${visibilityHTML}</td>
                    <td>
                        <div class="btn-group" role="group">
                            <a href="/tutoring/views/admin/documents/show.php?id=${document.id}" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Voir les détails">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="/tutoring/views/admin/documents/edit.php?id=${document.id}" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Modifier">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="/tutoring/api/documents/download.php?id=${document.id}" class="btn btn-sm btn-outline-info" data-bs-toggle="tooltip" title="Télécharger">
                                <i class="bi bi-download"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDeleteDocument(${document.id}, '${escapeHtmlDocuments(document.title)}')" title="Supprimer">
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
            tableHTML += renderPaginationDocuments(pagination);
        }

        container.innerHTML = tableHTML;
        
        // Mettre à jour les icônes de tri
        updateSortIconsDocuments();
        
        // Ajouter les événements de tri
        document.querySelectorAll('.sortable').forEach(header => {
            header.addEventListener('click', function() {
                const column = this.dataset.column;
                handleSortDocuments(column);
            });
        });
        
        // Initialiser les tooltips
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(element => {
            new bootstrap.Tooltip(element);
        });
    }

    // Fonction pour afficher la pagination
    function renderPaginationDocuments(pagination) {
        let paginationHTML = `
            <div class="card-footer">
                <nav aria-label="Navigation des pages de documents">
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
                    `<a class="page-link" href="#" onclick="changePageDocuments(${pagination.current_page - 1})" aria-label="Précédent"><span aria-hidden="true">&laquo;</span></a>` :
                    `<span class="page-link" aria-label="Précédent"><span aria-hidden="true">&laquo;</span></span>`
                }
            </li>
        `;

        // Pages
        const startPage = Math.max(1, pagination.current_page - 2);
        const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);

        if (startPage > 1) {
            paginationHTML += `<li class="page-item"><a class="page-link" href="#" onclick="changePageDocuments(1)">1</a></li>`;
            if (startPage > 2) {
                paginationHTML += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            paginationHTML += `
                <li class="page-item ${i === pagination.current_page ? 'active' : ''}">
                    ${i === pagination.current_page ? 
                        `<span class="page-link">${i}</span>` :
                        `<a class="page-link" href="#" onclick="changePageDocuments(${i})">${i}</a>`
                    }
                </li>
            `;
        }

        if (endPage < pagination.total_pages) {
            if (endPage < pagination.total_pages - 1) {
                paginationHTML += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
            paginationHTML += `<li class="page-item"><a class="page-link" href="#" onclick="changePageDocuments(${pagination.total_pages})">${pagination.total_pages}</a></li>`;
        }

        // Bouton suivant
        paginationHTML += `
            <li class="page-item ${pagination.current_page >= pagination.total_pages ? 'disabled' : ''}">
                ${pagination.current_page < pagination.total_pages ? 
                    `<a class="page-link" href="#" onclick="changePageDocuments(${pagination.current_page + 1})" aria-label="Suivant"><span aria-hidden="true">&raquo;</span></a>` :
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

    // Fonction pour gérer le tri
    function handleSortDocuments(column) {
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
        loadDocuments();
    }
    
    // Fonction pour mettre à jour les icônes de tri
    function updateSortIconsDocuments() {
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

    // Fonctions utilitaires
    function changePageDocuments(page) {
        currentPage = page;
        loadDocuments();
    }

    function changeItemsPerPageDocuments(value) {
        itemsPerPage = parseInt(value);
        currentPage = 1;
        loadDocuments();
    }
    
    // Fonction pour effectuer une recherche
    function performSearchDocuments() {
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
        loadDocuments();
    }

    function showErrorDocuments(message) {
        const container = document.getElementById('documentsTableContainer');
        container.innerHTML = `
            <div class="alert alert-danger m-3">
                <i class="bi bi-exclamation-circle me-2"></i>${message}
            </div>
        `;
    }

    function escapeHtmlDocuments(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    function confirmDeleteDocument(id, title) {
        if (confirm(`Êtes-vous sûr de vouloir supprimer le document "${title}" ? Cette action est irréversible.`)) {
            // Redirection vers la page de suppression pour le moment
            window.location.href = `/tutoring/views/admin/documents/delete.php?id=${id}`;
        }
    }

    // Initialisation au chargement de la page
    document.addEventListener('DOMContentLoaded', function() {
        loadDocuments();
        
        // Gestion du formulaire de recherche
        document.getElementById('searchForm').addEventListener('submit', function(e) {
            e.preventDefault();
            performSearchDocuments();
        });
        
        // Recherche en temps réel (optionnel)
        const searchInput = document.querySelector('input[name="term"]');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    performSearchDocuments();
                }, 500); // Délai de 500ms
            });
        }
        
        // Gestion des filtres par catégorie
        document.querySelectorAll('input[name="categoryFilter"]').forEach(radio => {
            radio.addEventListener('change', function() {
                categoryFilter = this.value;
                currentPage = 1;
                loadDocuments();
            });
        });
        
        // Gestionnaire pour le changement du nombre d'éléments par page
        document.getElementById('itemsPerPage').addEventListener('change', function() {
            changeItemsPerPageDocuments(this.value);
        });
        
        // Initialiser les tooltips
        setTimeout(() => {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }, 1000);
    });
</script>

<?php
// Inclure le pied de page
include_once __DIR__ . '/../common/footer.php';
?>