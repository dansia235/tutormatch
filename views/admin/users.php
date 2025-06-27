<?php
/**
 * Vue pour la gestion des utilisateurs
 */

// Initialiser les variables
$pageTitle = 'Gestion des utilisateurs';
$currentPage = 'users';

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
                    <h2><i class="bi bi-people me-2"></i>Gestion des Utilisateurs</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/tutoring/views/admin/dashboard.php">Tableau de bord</a></li>
                            <li class="breadcrumb-item active">Utilisateurs</li>
                        </ol>
                    </nav>
                </div>
                <?php if (hasRole(['admin'])): ?>
                <a href="/tutoring/views/admin/user/create.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>Ajouter un utilisateur
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Statistics cards -->
    <div class="row mb-4" id="statisticsCards">
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="value" id="totalUsers">-</div>
                <div class="label">Total Utilisateurs</div>
                <div class="progress mt-2">
                    <div class="progress-bar bg-primary" role="progressbar" style="width: 100%;"></div>
                </div>
                <small class="text-muted">Tous les rôles</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="value" id="totalStudents">-</div>
                <div class="label">Étudiants</div>
                <div class="progress mt-2">
                    <div class="progress-bar bg-success" role="progressbar" style="width: 0%;" id="studentsProgress"></div>
                </div>
                <small class="text-muted" id="studentsPercent">-% des utilisateurs</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="value" id="totalTeachers">-</div>
                <div class="label">Tuteurs</div>
                <div class="progress mt-2">
                    <div class="progress-bar bg-info" role="progressbar" style="width: 0%;" id="teachersProgress"></div>
                </div>
                <small class="text-muted" id="teachersPercent">-% des utilisateurs</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card">
                <div class="value" id="totalAdmins">-</div>
                <div class="label">Administrateurs</div>
                <div class="progress mt-2">
                    <div class="progress-bar bg-warning" role="progressbar" style="width: 0%;" id="adminsProgress"></div>
                </div>
                <small class="text-muted" id="adminsPercent">-% des utilisateurs</small>
            </div>
        </div>
    </div>
    
    <!-- Filtres et recherche -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <form id="searchForm" class="d-flex">
                        <input type="text" name="term" class="form-control me-2" placeholder="Rechercher un utilisateur...">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="bi bi-search"></i>
                        </button>
                    </form>
                </div>
                <div class="col-md-8 text-md-end mt-3 mt-md-0">
                    <div class="d-flex align-items-center justify-content-md-end gap-3 flex-wrap">
                        <!-- Filtres par rôle -->
                        <div class="btn-group" role="group" aria-label="Filtres par rôle">
                            <input type="radio" class="btn-check" name="roleFilter" id="role-all" value="" checked>
                            <label class="btn btn-outline-primary" for="role-all">Tous</label>
                            
                            <input type="radio" class="btn-check" name="roleFilter" id="role-admin" value="admin">
                            <label class="btn btn-outline-primary" for="role-admin">Admins</label>
                            
                            <input type="radio" class="btn-check" name="roleFilter" id="role-coordinator" value="coordinator">
                            <label class="btn btn-outline-primary" for="role-coordinator">Coordinateurs</label>
                            
                            <input type="radio" class="btn-check" name="roleFilter" id="role-teacher" value="teacher">
                            <label class="btn btn-outline-primary" for="role-teacher">Tuteurs</label>
                            
                            <input type="radio" class="btn-check" name="roleFilter" id="role-student" value="student">
                            <label class="btn btn-outline-primary" for="role-student">Étudiants</label>
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
    
    <!-- Liste des utilisateurs -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="bi bi-list me-2"></i>
                Liste des Utilisateurs
            </h5>
            <span class="badge bg-primary" id="userCount">
                Chargement...
            </span>
        </div>
        <div class="card-body p-0" id="usersTableContainer">
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
// Configuration de la table des utilisateurs
const userTableConfig = {
    apiEndpoint: '/tutoring/api/users/admin-list.php',
    tableContainer: '#usersTableContainer',
    searchForm: '#searchForm',
    defaultSort: 'name',
    columns: [
        { key: 'name', label: 'Utilisateur', sortable: true },
        { key: 'email', label: 'Email', sortable: true },
        { key: 'role', label: 'Rôle', sortable: true },
        { key: 'department', label: 'Département', sortable: true },
        { key: 'created_at', label: 'Date de création', sortable: true },
        { key: 'actions', label: 'Actions', sortable: false }
    ],
    renderRow: function(user) {
        const roles = {
            'admin': { label: 'Administrateur', class: 'bg-danger' },
            'coordinator': { label: 'Coordinateur', class: 'bg-warning' },
            'teacher': { label: 'Tuteur', class: 'bg-info' },
            'student': { label: 'Étudiant', class: 'bg-success' }
        };
        
        const roleInfo = roles[user.role] || { label: user.role, class: 'bg-secondary' };
        const initials = (user.first_name?.charAt(0) || '') + (user.last_name?.charAt(0) || '');
        
        return `
            <tr>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="avatar-circle me-3">
                            ${initials}
                        </div>
                        <div>
                            <div class="fw-bold">${user.full_name || ''}</div>
                            <div class="text-muted small">ID: ${user.id}</div>
                        </div>
                    </div>
                </td>
                <td>
                    <div>
                        <div>${user.email}</div>
                        ${user.phone ? `<div class="text-muted small"><i class="bi bi-telephone me-1"></i>${user.phone}</div>` : ''}
                    </div>
                </td>
                <td>
                    <span class="badge ${roleInfo.class}">${roleInfo.label}</span>
                </td>
                <td>${user.department || '<span class="text-muted">Non spécifié</span>'}</td>
                <td>
                    ${AdminTable.formatDate(user.created_at)}
                </td>
                <td>
                    <div class="btn-group" role="group">
                        <button class="btn btn-sm btn-outline-primary" onclick="viewUser(${user.id})" 
                                data-bs-toggle="tooltip" title="Voir les détails">
                            <i class="bi bi-eye"></i>
                        </button>
                        ${user.role !== 'admin' || user.id != currentUserId ? `
                        <button class="btn btn-sm btn-outline-secondary" onclick="editUser(${user.id})" 
                                data-bs-toggle="tooltip" title="Modifier">
                            <i class="bi bi-pencil"></i>
                        </button>
                        ` : ''}
                        ${user.id != currentUserId ? `
                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                onclick="confirmDeleteUser(${user.id}, '${user.full_name || (user.first_name + ' ' + user.last_name)}')" title="Supprimer">
                            <i class="bi bi-trash"></i>
                        </button>
                        ` : ''}
                    </div>
                </td>
            </tr>
        `;
    },
    onDataLoaded: function(data) {
        updateUserStats(data);
        updateUserCount(data.pagination);
    }
};

// Variables globales
const currentUserId = <?php echo $_SESSION['user_id'] ?? 0; ?>;
let adminTable;

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    adminTable = new AdminTable(userTableConfig);
    
    // Gestion des filtres par rôle
    document.querySelectorAll('input[name="roleFilter"]').forEach(radio => {
        radio.addEventListener('change', function() {
            adminTable.setFilter('role', this.value);
        });
    });
});

// Fonctions utilitaires
function updateUserStats(data) {
    if (!data.users) return;
    
    const stats = {
        total: data.users.length,
        students: data.users.filter(u => u.role === 'student').length,
        teachers: data.users.filter(u => u.role === 'teacher').length,
        admins: data.users.filter(u => u.role === 'admin' || u.role === 'coordinator').length
    };
    
    // Mettre à jour les cartes
    document.getElementById('totalUsers').textContent = data.pagination.total_items;
    document.getElementById('totalStudents').textContent = stats.students;
    document.getElementById('totalTeachers').textContent = stats.teachers;
    document.getElementById('totalAdmins').textContent = stats.admins;
    
    const total = data.pagination.total_items;
    if (total > 0) {
        // Pourcentages
        const studentPercent = Math.round((stats.students / total) * 100);
        const teacherPercent = Math.round((stats.teachers / total) * 100);
        const adminPercent = Math.round((stats.admins / total) * 100);
        
        // Barres de progression
        document.getElementById('studentsProgress').style.width = studentPercent + '%';
        document.getElementById('teachersProgress').style.width = teacherPercent + '%';
        document.getElementById('adminsProgress').style.width = adminPercent + '%';
        
        // Textes de pourcentage
        document.getElementById('studentsPercent').textContent = studentPercent + '% des utilisateurs';
        document.getElementById('teachersPercent').textContent = teacherPercent + '% des utilisateurs';
        document.getElementById('adminsPercent').textContent = adminPercent + '% des utilisateurs';
    }
}

function updateUserCount(pagination) {
    const countBadge = document.getElementById('userCount');
    if (pagination.total_items > 0) {
        countBadge.textContent = `${pagination.showing_from}-${pagination.showing_to} sur ${pagination.total_items} utilisateurs`;
    } else {
        countBadge.textContent = '0 utilisateurs';
    }
}

function viewUser(id) {
    window.location.href = `/tutoring/views/admin/user/show.php?id=${id}`;
}

function editUser(id) {
    window.location.href = `/tutoring/views/admin/user/edit.php?id=${id}`;
}

function confirmDeleteUser(id, name) {
    if (confirm(`Êtes-vous sûr de vouloir supprimer l'utilisateur "${name}" ?\n\nCette action est irréversible et supprimera toutes les données associées.`)) {
        deleteUser(id);
    }
}

async function deleteUser(id) {
    try {
        const response = await fetch(`/tutoring/api/users/delete.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: id })
        });
        
        const result = await response.json();
        
        if (result.success) {
            adminTable.loadData(); // Recharger les données
            alert('Utilisateur supprimé avec succès');
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