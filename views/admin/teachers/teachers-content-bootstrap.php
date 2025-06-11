<?php
/**
 * Bootstrap version of teachers content for backward compatibility
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
</style>

<div class="container-fluid mt-4">
    <!-- Titre de la page et bouton d'ajout -->
    <div class="page-header">
        <div>
            <h2><i class="bi bi-person-badge me-2"></i>Gestion des tuteurs</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/tutoring/views/admin/dashboard.php">Tableau de bord</a></li>
                    <li class="breadcrumb-item active">Tuteurs</li>
                </ol>
            </nav>
        </div>
        
        <?php if (hasRole(['admin', 'coordinator'])): ?>
        <a href="/tutoring/views/admin/teachers/create.php" class="btn btn-primary add-button">
            <i class="bi bi-plus-circle"></i>Ajouter un tuteur
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
                            <input type="text" class="form-control" name="term" placeholder="Rechercher un tuteur..." value="<?php echo isset($_GET['term']) ? h($_GET['term']) : ''; ?>">
                            <?php if ($activeFilter !== ''): ?>
                            <input type="hidden" name="available" value="<?php echo h($activeFilter); ?>">
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
                        <a href="?available=1<?php echo isset($_GET['term']) ? '&term='.h($_GET['term']).'&search=1' : ''; ?>" class="filter-tab <?php echo $activeFilter === '1' ? 'active' : ''; ?>">Disponibles</a>
                        <a href="?available=0<?php echo isset($_GET['term']) ? '&term='.h($_GET['term']).'&search=1' : ''; ?>" class="filter-tab <?php echo $activeFilter === '0' ? 'active' : ''; ?>">Indisponibles</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Cartes statistiques -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-value"><?php echo $teacherCount; ?></div>
                <div class="stat-label">Tuteurs actifs</div>
                <div class="progress">
                    <div class="progress-bar" role="progressbar" style="width: 100%;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-value"><?php echo $availableCount; ?></div>
                <div class="stat-label">Tuteurs disponibles</div>
                <div class="progress">
                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $teacherCount > 0 ? ($availableCount / $teacherCount) * 100 : 0; ?>%;" aria-valuenow="<?php echo $availableCount; ?>" aria-valuemin="0" aria-valuemax="<?php echo $teacherCount; ?>"></div>
                </div>
                <div class="small text-muted mt-2"><?php echo $teacherCount > 0 ? number_format(($availableCount / $teacherCount) * 100, 0) : 0; ?>% des tuteurs</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-value"><?php echo $availableCapacity; ?></div>
                <div class="stat-label">Places disponibles</div>
                <div class="progress">
                    <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $totalMaxStudents > 0 ? ($availableCapacity / $totalMaxStudents) * 100 : 0; ?>%;" aria-valuenow="<?php echo $availableCapacity; ?>" aria-valuemin="0" aria-valuemax="<?php echo $totalMaxStudents; ?>"></div>
                </div>
                <div class="small text-muted mt-2"><?php echo $totalMaxStudents > 0 ? number_format(($availableCapacity / $totalMaxStudents) * 100, 0) : 0; ?>% de capacité libre</div>
            </div>
        </div>
    </div>
    
    <!-- Liste des tuteurs -->
    <div class="card">
        <div class="card-body p-4">
            <div class="list-header">
                <h4><i class="bi bi-list me-2"></i>Liste des tuteurs</h4>
                <span class="count-badge"><?php echo $teacherCount; ?> tuteurs</span>
            </div>
            
            <?php if (empty($teachers)): ?>
            <div class="info-message">
                <i class="bi bi-info-circle"></i>
                <span>Aucun tuteur trouvé.</span>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Tuteur</th>
                            <th>Département</th>
                            <th>Spécialité</th>
                            <th>Disponibilité</th>
                            <th>Capacité</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teachers as $teacher): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php if (!empty($teacher['profile_image'])): ?>
                                    <img src="<?php echo h($teacher['profile_image']); ?>" alt="Profile" class="rounded-circle me-3" width="40" height="40">
                                    <?php else: ?>
                                    <div class="avatar me-3" style="width: 40px; height: 40px; background-color: #3498db; color: white; display: flex; align-items: center; justify-content: center; border-radius: 50%;">
                                        <?php echo strtoupper(substr($teacher['first_name'], 0, 1) . substr($teacher['last_name'], 0, 1)); ?>
                                    </div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="fw-bold">
                                            <?php echo h(($teacher['title'] ? $teacher['title'] . ' ' : '') . $teacher['first_name'] . ' ' . $teacher['last_name']); ?>
                                        </div>
                                        <div class="text-muted small"><?php echo h($teacher['email']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo h($teacher['department']); ?></td>
                            <td><?php echo h($teacher['specialty']); ?></td>
                            <td>
                                <?php if ($teacher['available']): ?>
                                <span class="badge bg-success">Disponible</span>
                                <?php else: ?>
                                <span class="badge bg-warning">Indisponible</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                // Afficher le nombre d'étudiants actuels et le maximum
                                $currentCount = isset($teacher['students_count']) ? $teacher['students_count'] : 0;
                                $maxStudents = $teacher['max_students'];
                                $ratio = $maxStudents > 0 ? $currentCount / $maxStudents : 0;
                                
                                $badgeClass = 'bg-success';
                                if ($ratio >= 0.8) {
                                    $badgeClass = 'bg-danger';
                                } elseif ($ratio >= 0.5) {
                                    $badgeClass = 'bg-warning';
                                }
                                ?>
                                <div class="d-flex align-items-center">
                                    <div class="progress flex-grow-1 me-2" style="height: 8px;">
                                        <div class="progress-bar <?php echo $badgeClass; ?>" role="progressbar" style="width: <?php echo ($ratio * 100); ?>%" aria-valuenow="<?php echo $currentCount; ?>" aria-valuemin="0" aria-valuemax="<?php echo $maxStudents; ?>"></div>
                                    </div>
                                    <span class="badge <?php echo $badgeClass; ?>"><?php echo $currentCount; ?>/<?php echo $maxStudents; ?></span>
                                </div>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="/tutoring/views/admin/teachers/show.php?id=<?php echo $teacher['id']; ?>" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Voir les détails">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="/tutoring/views/admin/teachers/edit.php?id=<?php echo $teacher['id']; ?>" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Modifier">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php if (hasRole(['admin'])): ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $teacher['id']; ?>" title="Supprimer">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Modal de confirmation de suppression -->
                                <?php if (hasRole(['admin'])): ?>
                                <div class="modal fade" id="deleteModal<?php echo $teacher['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $teacher['id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="deleteModalLabel<?php echo $teacher['id']; ?>">Confirmer la suppression</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Êtes-vous sûr de vouloir supprimer le tuteur <strong><?php echo h($teacher['first_name'] . ' ' . $teacher['last_name']); ?></strong> ?</p>
                                                <p class="text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Cette action est irréversible et supprimera également toutes les données associées à ce tuteur.</p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                <form action="/tutoring/views/admin/teachers/delete.php" method="POST">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                                    <input type="hidden" name="id" value="<?php echo $teacher['id']; ?>">
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