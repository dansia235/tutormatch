<?php
/**
 * Bootstrap version of students content for backward compatibility
 */

// If this file is directly accessed, redirect to index
if (!defined('BASE_PATH')) {
    header('Location: index.php');
    exit;
}
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
    }
    
    .filter-tabs .filter-tab.active {
        background-color: #3498db;
        color: white;
    }
    
    .filter-tabs .filter-tab:hover:not(.active) {
        background-color: #e9ecef;
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
</style>

<div class="container-fluid mt-4">
    <!-- Titre de la page et bouton d'ajout -->
    <div class="page-header">
        <div>
            <h2><i class="bi bi-mortarboard me-2"></i>Gestion des étudiants</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/tutoring/views/admin/dashboard.php">Tableau de bord</a></li>
                    <li class="breadcrumb-item active">Étudiants</li>
                </ol>
            </nav>
        </div>
        
        <?php if (hasRole(['admin', 'coordinator'])): ?>
        <a href="/tutoring/views/admin/students/create.php" class="btn btn-primary add-button">
            <i class="bi bi-plus-circle"></i>Ajouter un étudiant
        </a>
        <?php endif; ?>
    </div>
    
    <!-- Barre de recherche et filtres -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="search-container">
                        <form action="" method="GET">
                            <i class="bi bi-search search-icon"></i>
                            <input type="text" class="form-control" name="term" placeholder="Rechercher un étudiant..." value="<?php echo isset($_GET['term']) ? h($_GET['term']) : ''; ?>">
                            <?php if (!empty($activeFilter)): ?>
                            <input type="hidden" name="status" value="<?php echo h($activeFilter); ?>">
                            <?php endif; ?>
                            <button type="submit" name="search" class="btn btn-primary btn-search d-none">
                                <i class="bi bi-search"></i>
                            </button>
                        </form>
                    </div>
                </div>
                <div class="col-md-6 text-md-end mt-3 mt-md-0">
                    <div class="filter-tabs">
                        <a href="?<?php echo isset($_GET['term']) ? 'term='.h($_GET['term']).'&search=1' : ''; ?>" class="filter-tab <?php echo $activeFilter === '' ? 'active' : ''; ?>">Tous</a>
                        <a href="?status=active<?php echo isset($_GET['term']) ? '&term='.h($_GET['term']).'&search=1' : ''; ?>" class="filter-tab <?php echo $activeFilter === 'active' ? 'active' : ''; ?>">Actifs</a>
                        <a href="?status=graduated<?php echo isset($_GET['term']) ? '&term='.h($_GET['term']).'&search=1' : ''; ?>" class="filter-tab <?php echo $activeFilter === 'graduated' ? 'active' : ''; ?>">Diplômés</a>
                        <a href="?status=suspended<?php echo isset($_GET['term']) ? '&term='.h($_GET['term']).'&search=1' : ''; ?>" class="filter-tab <?php echo $activeFilter === 'suspended' ? 'active' : ''; ?>">Suspendus</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Liste des étudiants -->
    <div class="card">
        <div class="card-body p-4">
            <div class="list-header">
                <h4><i class="bi bi-list me-2"></i>Liste des étudiants</h4>
                <span class="count-badge"><?php echo count($students); ?> étudiants</span>
            </div>
            
            <?php if (empty($students)): ?>
            <div class="info-message">
                <i class="bi bi-info-circle"></i>
                <span>Aucun étudiant trouvé.</span>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Étudiant</th>
                            <th>Numéro</th>
                            <th>Programme</th>
                            <th>Niveau</th>
                            <th>Département</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                        <tr>
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
                                    <?php if (hasRole(['admin', 'coordinator'])): ?>
                                    <a href="/tutoring/views/admin/students/edit.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Modifier">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php if (hasRole(['admin'])): ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $student['id']; ?>" title="Supprimer">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Modal de confirmation de suppression -->
                                <?php if (hasRole(['admin'])): ?>
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
                                                <form action="/tutoring/views/admin/students/delete.php" method="POST">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                                    <input type="hidden" name="id" value="<?php echo $student['id']; ?>">
                                                    <button type="submit" class="btn btn-danger">Supprimer</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
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
</script>