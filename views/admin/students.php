<?php
/**
 * Vue pour la gestion des étudiants
 */

// Initialiser les variables
$pageTitle = 'Gestion des étudiants';
$currentPage = 'students';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../includes/init.php';

// Vérifier les permissions
requireRole(['admin', 'coordinator']);

// Inclure l'en-tête
include_once __DIR__ . '/../common/header.php';
?>

<div class="container-fluid">
    <!-- Header section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="bi bi-mortarboard me-2"></i>Gestion des Étudiants</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/tutoring/views/admin/dashboard.php">Tableau de bord</a></li>
                            <li class="breadcrumb-item active">Étudiants</li>
                        </ol>
                    </nav>
                </div>
                <?php if (hasRole(['admin'])): ?>
                <a href="/tutoring/views/admin/student/create.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>Ajouter un étudiant
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Statistics cards -->
    <div class="row mb-4" id="statisticsCards">
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="value" id="totalStudents">-</div>
                <div class="label">Total Étudiants</div>
                <div class="progress mt-2">
                    <div class="progress-bar bg-primary" role="progressbar" style="width: 100%;"></div>
                </div>
                <small class="text-muted">Tous statuts</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="value" id="activeStudents">-</div>
                <div class="label">Étudiants Actifs</div>
                <div class="progress mt-2">
                    <div class="progress-bar bg-success" role="progressbar" style="width: 0%;" id="activeProgress"></div>
                </div>
                <small class="text-muted" id="activePercent">-% des étudiants</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="value" id="assignedStudents">-</div>
                <div class="label">Avec Affectations</div>
                <div class="progress mt-2">
                    <div class="progress-bar bg-info" role="progressbar" style="width: 0%;" id="assignedProgress"></div>
                </div>
                <small class="text-muted" id="assignedPercent">-% des étudiants</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="value" id="graduatedStudents">-</div>
                <div class="label">Diplômés</div>
                <div class="progress mt-2">
                    <div class="progress-bar bg-warning" role="progressbar" style="width: 0%;" id="graduatedProgress"></div>
                </div>
                <small class="text-muted" id="graduatedPercent">-% des étudiants</small>
            </div>
        </div>
    </div>
    
    <!-- Filtres et recherche -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <form id="searchForm" class="d-flex">
                        <input type="text" name="term" class="form-control me-2" placeholder="Rechercher un étudiant...">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="bi bi-search"></i>
                        </button>
                    </form>
                </div>
                <div class="col-md-8 text-md-end mt-3 mt-md-0">
                    <div class="d-flex align-items-center justify-content-md-end gap-3 flex-wrap">
                        <!-- Filtres par programme -->
                        <div class="btn-group" role="group" aria-label="Filtres par programme">
                            <input type="radio" class="btn-check" name="programFilter" id="program-all" value="" checked>
                            <label class="btn btn-outline-primary" for="program-all">Tous</label>
                            
                            <input type="radio" class="btn-check" name="programFilter" id="program-info" value="Informatique">
                            <label class="btn btn-outline-primary" for="program-info">Informatique</label>
                            
                            <input type="radio" class="btn-check" name="programFilter" id="program-genie" value="Génie Logiciel">
                            <label class="btn btn-outline-primary" for="program-genie">Génie</label>
                            
                            <input type="radio" class="btn-check" name="programFilter" id="program-reseaux" value="Réseaux et Télécommunications">
                            <label class="btn btn-outline-primary" for="program-reseaux">Réseaux</label>
                        </div>
                        
                        <!-- Filtre par niveau -->
                        <select id="levelFilter" class="form-select" style="width: auto;">
                            <option value="">Tous les niveaux</option>
                            <option value="L1">L1</option>
                            <option value="L2">L2</option>
                            <option value="L3">L3</option>
                            <option value="M1">M1</option>
                            <option value="M2">M2</option>
                        </select>
                        
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
    
    <!-- Liste des étudiants -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="bi bi-list me-2"></i>
                Liste des Étudiants
            </h5>
            <span class="badge bg-primary" id="studentCount">
                Chargement...
            </span>
        </div>
        <div class="card-body p-0" id="studentsTableContainer">
            <!-- Le contenu sera chargé dynamiquement -->
            <div class="text-center p-4">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="/tutoring/assets/js/admin-table.js"></script>
<script>
// Configuration de la table des étudiants
const studentTableConfig = {
    apiEndpoint: '/tutoring/api/students/admin-list.php',
    tableContainer: '#studentsTableContainer',
    searchForm: '#searchForm',
    defaultSort: 'name',
    columns: [
        { key: 'name', label: 'Étudiant', sortable: true },
        { key: 'student_number', label: 'Numéro', sortable: true },
        { key: 'email', label: 'Email', sortable: true },
        { key: 'program', label: 'Programme', sortable: true },
        { key: 'level', label: 'Niveau', sortable: true },
        { key: 'enrollment_year', label: 'Année', sortable: true },
        { key: 'actions', label: 'Actions', sortable: false }
    ],
    renderRow: function(student) {
        const initials = (student.first_name?.charAt(0) || '') + (student.last_name?.charAt(0) || '');
        
        return `
            <tr>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="avatar-circle me-3">
                            ${initials}
                        </div>
                        <div>
                            <div class="fw-bold">${student.full_name || ''}</div>
                            <div class="text-muted small">ID: ${student.id}</div>
                            ${student.current_internship_title ? `<div class="text-muted small"><i class="bi bi-briefcase me-1"></i>${student.current_internship_title}</div>` : ''}
                        </div>
                    </div>
                </td>
                <td>
                    <span class="badge bg-light text-dark">${student.student_number || 'Non défini'}</span>
                </td>
                <td>
                    <div>
                        <div>${student.email}</div>
                        ${student.phone ? `<div class="text-muted small"><i class="bi bi-telephone me-1"></i>${student.phone}</div>` : ''}
                    </div>
                </td>
                <td>
                    <span class="badge bg-info">${student.program || 'Non spécifié'}</span>
                </td>
                <td>
                    <span class="badge bg-secondary">${student.level || 'Non spécifié'}</span>
                </td>
                <td>
                    ${student.enrollment_year || '<span class="text-muted">Non défini</span>'}
                </td>
                <td>
                    <div class="btn-group" role="group">
                        <button class="btn btn-sm btn-outline-primary" onclick="viewStudent(${student.id})" 
                                data-bs-toggle="tooltip" title="Voir les détails">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="editStudent(${student.id})" 
                                data-bs-toggle="tooltip" title="Modifier">
                            <i class="bi bi-pencil"></i>
                        </button>
                        ${hasAdminRole ? `
                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                onclick="confirmDeleteStudent(${student.id}, '${student.full_name || (student.first_name + ' ' + student.last_name)}')" title="Supprimer">
                            <i class="bi bi-trash"></i>
                        </button>
                        ` : ''}
                    </div>
                </td>
            </tr>
        `;
    },
    onDataLoaded: function(data) {
        updateStudentStats(data);
        updateStudentCount(data.pagination);
    }
};

// Variables globales
const hasAdminRole = <?php echo hasRole(['admin']) ? 'true' : 'false'; ?>;
let adminTable;

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    adminTable = new AdminTable(studentTableConfig);
    
    // Gestion des filtres par programme
    document.querySelectorAll('input[name="programFilter"]').forEach(radio => {
        radio.addEventListener('change', function() {
            adminTable.setFilter('program', this.value);
        });
    });
    
    // Gestion du filtre par niveau
    document.getElementById('levelFilter').addEventListener('change', function() {
        adminTable.setFilter('level', this.value);
    });
});

// Fonctions utilitaires
function updateStudentStats(data) {
    if (!data.students) return;
    
    const stats = {
        total: data.pagination.total_items,
        active: 0,
        assigned: 0,
        graduated: 0
    };
    
    data.students.forEach(student => {
        if (student.current_assignments_count > 0) stats.assigned++;
        // Note: Ajouter logique pour actifs et diplômés selon vos critères
        stats.active = stats.total; // Supposer tous actifs pour l'instant
    });
    
    // Mettre à jour les cartes
    document.getElementById('totalStudents').textContent = stats.total;
    document.getElementById('activeStudents').textContent = stats.active;
    document.getElementById('assignedStudents').textContent = stats.assigned;
    document.getElementById('graduatedStudents').textContent = stats.graduated;
    
    if (stats.total > 0) {
        // Pourcentages
        const activePercent = Math.round((stats.active / stats.total) * 100);
        const assignedPercent = Math.round((stats.assigned / stats.total) * 100);
        const graduatedPercent = Math.round((stats.graduated / stats.total) * 100);
        
        // Barres de progression
        document.getElementById('activeProgress').style.width = activePercent + '%';
        document.getElementById('assignedProgress').style.width = assignedPercent + '%';
        document.getElementById('graduatedProgress').style.width = graduatedPercent + '%';
        
        // Textes de pourcentage
        document.getElementById('activePercent').textContent = activePercent + '% des étudiants';
        document.getElementById('assignedPercent').textContent = assignedPercent + '% des étudiants';
        document.getElementById('graduatedPercent').textContent = graduatedPercent + '% des étudiants';
    }
}

function updateStudentCount(pagination) {
    const countBadge = document.getElementById('studentCount');
    if (pagination.total_items > 0) {
        countBadge.textContent = `${pagination.showing_from}-${pagination.showing_to} sur ${pagination.total_items} étudiants`;
    } else {
        countBadge.textContent = '0 étudiants';
    }
}

function viewStudent(id) {
    window.location.href = `/tutoring/views/admin/student/show.php?id=${id}`;
}

function editStudent(id) {
    window.location.href = `/tutoring/views/admin/student/edit.php?id=${id}`;
}

function confirmDeleteStudent(id, name) {
    if (confirm(`Êtes-vous sûr de vouloir supprimer l'étudiant "${name}" ?\n\nCette action est irréversible et supprimera toutes les données associées.`)) {
        deleteStudent(id);
    }
}

async function deleteStudent(id) {
    try {
        const response = await fetch(`/tutoring/api/students/delete.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: id })
        });
        
        const result = await response.json();
        
        if (result.success) {
            adminTable.loadData(); // Recharger les données
            alert('Étudiant supprimé avec succès');
        } else {
            alert('Erreur lors de la suppression: ' + result.error);
        }
    } catch (error) {
        console.error('Erreur:', error);
        alert('Erreur lors de la suppression');
    }
}
</script>

<style>
.avatar-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(45deg, #3498db, #2980b9);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.9rem;
}

.stat-card {
    text-align: center;
    padding: 1.5rem;
    border-radius: 10px;
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    height: 100%;
}

.stat-card .value {
    font-size: 2.5rem;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 0.5rem;
}

.stat-card .label {
    color: #7f8c8d;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 1rem;
}
</style>

<?php
// Inclure le pied de page
include_once __DIR__ . '/../common/footer.php';
?>