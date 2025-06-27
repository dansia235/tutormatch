/**
 * Composant JavaScript réutilisable pour les tables admin
 * Gestion de la recherche, tri et pagination
 */

class AdminTable {
    constructor(config) {
        this.apiEndpoint = config.apiEndpoint;
        this.tableContainer = config.tableContainer;
        this.searchForm = config.searchForm;
        this.columns = config.columns;
        this.filters = config.filters || {};
        this.renderRow = config.renderRow;
        this.onDataLoaded = config.onDataLoaded || (() => {});
        
        // État de la table
        this.currentPage = 1;
        this.itemsPerPage = 10;
        this.sortBy = config.defaultSort || 'created_at';
        this.sortOrder = 'desc';
        this.searchTerm = '';
        this.activeFilters = {};
        
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.loadData();
    }
    
    setupEventListeners() {
        // Recherche
        const searchFormElement = document.querySelector(this.searchForm);
        if (searchFormElement) {
            const searchInput = searchFormElement.querySelector('input[name="term"]');
            
            searchFormElement.addEventListener('submit', (e) => {
                e.preventDefault();
                this.performSearch();
            });
            
            if (searchInput) {
                let searchTimeout;
                searchInput.addEventListener('input', () => {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        this.performSearch();
                    }, 500);
                });
            }
        }
        
        // Changement du nombre d'éléments par page
        document.addEventListener('change', (e) => {
            if (e.target.id === 'itemsPerPage') {
                this.itemsPerPage = parseInt(e.target.value);
                this.currentPage = 1;
                this.loadData();
            }
        });
    }
    
    async loadData() {
        try {
            const params = new URLSearchParams({
                page: this.currentPage,
                per_page: this.itemsPerPage,
                sort: this.sortBy,
                order: this.sortOrder
            });
            
            // Ajouter les filtres
            Object.keys(this.activeFilters).forEach(key => {
                if (this.activeFilters[key]) {
                    params.append(key, this.activeFilters[key]);
                }
            });
            
            if (this.searchTerm) {
                params.append('term', this.searchTerm);
            }
            
            const response = await fetch(`${this.apiEndpoint}?${params}`);
            const data = await response.json();
            
            if (data.success) {
                this.renderTable(data.data);
                this.onDataLoaded(data.data);
            } else {
                console.error('Erreur API:', data.error);
                this.showError('Erreur lors du chargement des données: ' + (data.error || 'Erreur inconnue'));
            }
        } catch (error) {
            console.error('Erreur:', error);
            this.showError('Erreur de connexion');
        }
    }
    
    renderTable(data) {
        const container = document.querySelector(this.tableContainer);
        if (!container) return;
        
        if (!data.pagination || data.pagination.total_items === 0) {
            container.innerHTML = `
                <div class="alert alert-info m-3">
                    <i class="bi bi-info-circle me-2"></i>Aucun élément trouvé.
                </div>
            `;
            return;
        }
        
        let tableHTML = `
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
        `;
        
        // Générer les en-têtes avec tri
        this.columns.forEach(column => {
            if (column.sortable) {
                tableHTML += `
                    <th class="sortable" data-column="${column.key}">
                        ${column.label}
                        <i class="bi bi-arrow-down-up ms-1 sort-icon"></i>
                    </th>
                `;
            } else {
                tableHTML += `<th>${column.label}</th>`;
            }
        });
        
        tableHTML += `
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        // Générer les lignes
        // Déterminer la clé des données (users, students, teachers, etc.)
        const dataKeys = Object.keys(data).filter(key => key !== 'pagination');
        const items = dataKeys.length > 0 ? data[dataKeys[0]] : [];
        
        if (items && Array.isArray(items)) {
            items.forEach(item => {
                tableHTML += this.renderRow(item);
            });
        }
        
        tableHTML += `
                    </tbody>
                </table>
            </div>
        `;
        
        // Ajouter la pagination
        if (data.pagination.total_pages > 1) {
            tableHTML += this.renderPagination(data.pagination);
        }
        
        container.innerHTML = tableHTML;
        
        // Mettre à jour les icônes de tri et ajouter les événements
        this.updateSortIcons();
        this.setupTableEvents();
    }
    
    setupTableEvents() {
        // Événements de tri
        document.querySelectorAll('.sortable').forEach(header => {
            header.addEventListener('click', () => {
                const column = header.dataset.column;
                this.handleSort(column);
            });
        });
        
        // Initialiser les tooltips
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(element => {
            if (window.bootstrap) {
                new bootstrap.Tooltip(element);
            }
        });
    }
    
    handleSort(column) {
        // Animation visuelle
        const header = document.querySelector(`[data-column="${column}"]`);
        if (header) {
            header.classList.add('sorting');
            setTimeout(() => header.classList.remove('sorting'), 300);
        }
        
        if (this.sortBy === column) {
            this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc';
        } else {
            this.sortBy = column;
            this.sortOrder = 'desc';
        }
        
        this.currentPage = 1;
        this.loadData();
    }
    
    updateSortIcons() {
        // Réinitialiser toutes les icônes
        document.querySelectorAll('.sort-icon').forEach(icon => {
            icon.className = 'bi bi-arrow-down-up ms-1 sort-icon text-muted';
        });
        
        // Mettre en évidence la colonne active
        const activeHeader = document.querySelector(`[data-column="${this.sortBy}"]`);
        if (activeHeader) {
            const icon = activeHeader.querySelector('.sort-icon');
            if (icon) {
                icon.className = `bi bi-arrow-${this.sortOrder === 'asc' ? 'up' : 'down'} ms-1 sort-icon text-primary`;
            }
        }
    }
    
    performSearch() {
        const searchInput = document.querySelector('input[name="term"]');
        this.searchTerm = searchInput ? searchInput.value.trim() : '';
        
        // Indicateur visuel de recherche active
        if (searchInput) {
            if (this.searchTerm.length > 0) {
                searchInput.classList.add('search-active');
            } else {
                searchInput.classList.remove('search-active');
            }
        }
        
        this.currentPage = 1;
        this.loadData();
    }
    
    changePage(page) {
        this.currentPage = page;
        this.loadData();
    }
    
    setFilter(key, value) {
        this.activeFilters[key] = value;
        this.currentPage = 1;
        this.loadData();
    }
    
    renderPagination(pagination) {
        let paginationHTML = `
            <div class="card-footer">
                <nav aria-label="Navigation des pages">
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
                    `<a class="page-link" href="#" onclick="adminTable.changePage(${pagination.current_page - 1}); return false;" aria-label="Précédent"><span aria-hidden="true">&laquo;</span></a>` :
                    `<span class="page-link" aria-label="Précédent"><span aria-hidden="true">&laquo;</span></span>`
                }
            </li>
        `;
        
        // Pages
        const startPage = Math.max(1, pagination.current_page - 2);
        const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);
        
        if (startPage > 1) {
            paginationHTML += `<li class="page-item"><a class="page-link" href="#" onclick="adminTable.changePage(1); return false;">1</a></li>`;
            if (startPage > 2) {
                paginationHTML += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
        }
        
        for (let i = startPage; i <= endPage; i++) {
            paginationHTML += `
                <li class="page-item ${i === pagination.current_page ? 'active' : ''}">
                    ${i === pagination.current_page ? 
                        `<span class="page-link">${i}</span>` :
                        `<a class="page-link" href="#" onclick="adminTable.changePage(${i}); return false;">${i}</a>`
                    }
                </li>
            `;
        }
        
        if (endPage < pagination.total_pages) {
            if (endPage < pagination.total_pages - 1) {
                paginationHTML += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
            paginationHTML += `<li class="page-item"><a class="page-link" href="#" onclick="adminTable.changePage(${pagination.total_pages}); return false;">${pagination.total_pages}</a></li>`;
        }
        
        // Bouton suivant
        paginationHTML += `
            <li class="page-item ${pagination.current_page >= pagination.total_pages ? 'disabled' : ''}">
                ${pagination.current_page < pagination.total_pages ? 
                    `<a class="page-link" href="#" onclick="adminTable.changePage(${pagination.current_page + 1}); return false;" aria-label="Suivant"><span aria-hidden="true">&raquo;</span></a>` :
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
    
    showError(message) {
        const container = document.querySelector(this.tableContainer);
        if (container) {
            container.innerHTML = `
                <div class="alert alert-danger m-3">
                    <i class="bi bi-exclamation-triangle me-2"></i>${message}
                </div>
            `;
        }
    }
    
    // Méthodes utilitaires pour les badges et statuts
    static getStatusBadge(status, labels = {}) {
        const defaultLabels = {
            'active': { label: 'Actif', class: 'bg-success' },
            'inactive': { label: 'Inactif', class: 'bg-secondary' },
            'pending': { label: 'En attente', class: 'bg-warning' },
            'confirmed': { label: 'Confirmé', class: 'bg-success' },
            'cancelled': { label: 'Annulé', class: 'bg-danger' },
            'completed': { label: 'Terminé', class: 'bg-success' },
            'available': { label: 'Disponible', class: 'bg-info' },
            'assigned': { label: 'Assigné', class: 'bg-primary' }
        };
        
        const config = { ...defaultLabels, ...labels };
        const statusConfig = config[status] || { label: status, class: 'bg-secondary' };
        
        return `<span class="badge ${statusConfig.class}">${statusConfig.label}</span>`;
    }
    
    static formatDate(dateString) {
        if (!dateString) return '<span class="text-muted">Non définie</span>';
        
        const date = new Date(dateString);
        return `
            <div>${date.toLocaleDateString('fr-FR')}</div>
            <div class="text-muted small">${date.toLocaleTimeString('fr-FR', {hour: '2-digit', minute: '2-digit'})}</div>
        `;
    }
    
    static formatUserInfo(user, role = null) {
        return `
            <div>
                <div class="fw-bold">${user.first_name || ''} ${user.last_name || ''}</div>
                <div class="text-muted small">
                    <i class="bi bi-envelope me-1"></i>${user.email || ''}
                    ${role ? `<br><i class="bi bi-person-badge me-1"></i>${role}` : ''}
                </div>
            </div>
        `;
    }
}

// Styles CSS pour les tables admin
const adminTableStyles = `
<style>
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

/* Pagination améliorée */
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
</style>
`;

// Injecter les styles si ce n'est pas déjà fait
if (!document.querySelector('#admin-table-styles')) {
    const styleElement = document.createElement('div');
    styleElement.id = 'admin-table-styles';
    styleElement.innerHTML = adminTableStyles;
    document.head.appendChild(styleElement);
}