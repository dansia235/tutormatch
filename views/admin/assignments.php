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

// Traiter la recherche ou afficher toutes les affectations
if (isset($_GET['search'])) {
    $term = isset($_GET['term']) ? $_GET['term'] : '';
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    
    // Utiliser la méthode search du modèle
    $assignments = $assignmentModel->search($term, $status);
} else {
    // Afficher toutes les affectations ou filtrer par statut
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    
    // Utiliser la méthode getAll du modèle
    $assignments = $assignmentModel->getAll($status);
}

// Récupérer les statistiques
$totalAssignments = count($assignments);

// Calculer les statistiques par statut
$pendingCount = 0;
$confirmedCount = 0;
$rejectedCount = 0;
$completedCount = 0;

foreach ($assignments as $assignment) {
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
                        <form action="" method="GET">
                            <i class="bi bi-search search-icon"></i>
                            <input type="text" class="form-control" name="term" placeholder="Rechercher une affectation..." value="<?php echo isset($_GET['term']) ? h($_GET['term']) : ''; ?>">
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
                        <a href="?<?php echo isset($_GET['term']) ? 'term='.h($_GET['term']).'&search=1' : ''; ?>" class="filter-tab <?php echo $activeFilter === '' ? 'active' : ''; ?>">Toutes</a>
                        <a href="?status=pending<?php echo isset($_GET['term']) ? '&term='.h($_GET['term']).'&search=1' : ''; ?>" class="filter-tab <?php echo $activeFilter === 'pending' ? 'active' : ''; ?>">En attente</a>
                        <a href="?status=confirmed<?php echo isset($_GET['term']) ? '&term='.h($_GET['term']).'&search=1' : ''; ?>" class="filter-tab <?php echo $activeFilter === 'confirmed' ? 'active' : ''; ?>">Confirmées</a>
                        <a href="?status=rejected<?php echo isset($_GET['term']) ? '&term='.h($_GET['term']).'&search=1' : ''; ?>" class="filter-tab <?php echo $activeFilter === 'rejected' ? 'active' : ''; ?>">Rejetées</a>
                        <a href="?status=completed<?php echo isset($_GET['term']) ? '&term='.h($_GET['term']).'&search=1' : ''; ?>" class="filter-tab <?php echo $activeFilter === 'completed' ? 'active' : ''; ?>">Terminées</a>
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
                <span class="count-badge"><?php echo $totalAssignments; ?> affectations</span>
            </div>
            
            <?php if (empty($assignments)): ?>
            <div class="info-message">
                <i class="bi bi-info-circle"></i>
                <span>Aucune affectation trouvée.</span>
            </div>
            <?php else: ?>
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
                        <?php foreach ($assignments as $assignment): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php if (isset($assignment['student_profile_image']) && $assignment['student_profile_image']): ?>
                                    <img src="<?php echo h($assignment['student_profile_image']); ?>" alt="Student" class="rounded-circle me-2" width="32" height="32">
                                    <?php else: ?>
                                    <div class="avatar-sm me-2">
                                        <?php echo strtoupper(substr($assignment['student_first_name'] ?? '', 0, 1) . substr($assignment['student_last_name'] ?? '', 0, 1)); ?>
                                    </div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="fw-bold"><?php echo h($assignment['student_first_name'] ?? '') . ' ' . h($assignment['student_last_name'] ?? ''); ?></div>
                                        <div class="text-muted small"><?php echo h($assignment['student_program'] ?? ''); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php if (isset($assignment['teacher_profile_image']) && $assignment['teacher_profile_image']): ?>
                                    <img src="<?php echo h($assignment['teacher_profile_image']); ?>" alt="Teacher" class="rounded-circle me-2" width="32" height="32">
                                    <?php else: ?>
                                    <div class="avatar-sm me-2">
                                        <?php echo strtoupper(substr($assignment['teacher_first_name'] ?? '', 0, 1) . substr($assignment['teacher_last_name'] ?? '', 0, 1)); ?>
                                    </div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="fw-bold"><?php echo h($assignment['teacher_first_name'] ?? '') . ' ' . h($assignment['teacher_last_name'] ?? ''); ?></div>
                                        <div class="text-muted small"><?php echo h($assignment['teacher_specialty'] ?? ''); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold"><?php echo h($assignment['internship_title'] ?? ''); ?></div>
                                <div class="text-muted small"><?php echo h($assignment['company_name'] ?? ''); ?></div>
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span><i class="bi bi-calendar-event me-1"></i> <?php echo isset($assignment['assignment_date']) ? date('d/m/Y', strtotime($assignment['assignment_date'])) : ''; ?></span>
                                    <?php if (isset($assignment['confirmation_date']) && $assignment['confirmation_date']): ?>
                                    <span><i class="bi bi-calendar-check me-1"></i> <?php echo date('d/m/Y', strtotime($assignment['confirmation_date'])); ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?php
                                $statusBadges = [
                                    'pending' => '<span class="badge bg-warning">En attente</span>',
                                    'confirmed' => '<span class="badge bg-success">Confirmée</span>',
                                    'rejected' => '<span class="badge bg-danger">Rejetée</span>',
                                    'completed' => '<span class="badge bg-info">Terminée</span>'
                                ];
                                echo $statusBadges[$assignment['status']] ?? '<span class="badge bg-secondary">' . h($assignment['status']) . '</span>';
                                ?>
                            </td>
                            <td>
                                <div class="score-container mb-2" title="Compatibilité">
                                    <?php
                                    // Afficher le score de compatibilité
                                    $compatibilityScore = $assignment['compatibility_score'] ?? 0;
                                    $compatibilityClass = 'bg-danger';
                                    
                                    if ($compatibilityScore >= 7) {
                                        $compatibilityClass = 'bg-success';
                                    } elseif ($compatibilityScore >= 4) {
                                        $compatibilityClass = 'bg-warning';
                                    }
                                    ?>
                                    <small class="text-muted me-2">C:</small>
                                    <div class="progress score-progress">
                                        <div class="progress-bar <?php echo $compatibilityClass; ?>" role="progressbar" style="width: <?php echo ($compatibilityScore * 10); ?>%" aria-valuenow="<?php echo $compatibilityScore; ?>" aria-valuemin="0" aria-valuemax="10"></div>
                                    </div>
                                    <span class="score-value"><?php echo number_format($compatibilityScore, 1); ?></span>
                                </div>
                                
                                <?php if (isset($assignment['satisfaction_score']) && $assignment['satisfaction_score']): ?>
                                <div class="score-container" title="Satisfaction">
                                    <?php
                                    // Afficher le score de satisfaction
                                    $satisfactionScore = $assignment['satisfaction_score'];
                                    $satisfactionClass = 'bg-danger';
                                    
                                    if ($satisfactionScore >= 7) {
                                        $satisfactionClass = 'bg-success';
                                    } elseif ($satisfactionScore >= 4) {
                                        $satisfactionClass = 'bg-warning';
                                    }
                                    ?>
                                    <small class="text-muted me-2">S:</small>
                                    <div class="progress score-progress">
                                        <div class="progress-bar <?php echo $satisfactionClass; ?>" role="progressbar" style="width: <?php echo ($satisfactionScore * 10); ?>%" aria-valuenow="<?php echo $satisfactionScore; ?>" aria-valuemin="0" aria-valuemax="10"></div>
                                    </div>
                                    <span class="score-value"><?php echo number_format($satisfactionScore, 1); ?></span>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="/tutoring/views/admin/assignments/show.php?id=<?php echo $assignment['id']; ?>" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Voir les détails">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="/tutoring/views/admin/assignments/edit.php?id=<?php echo $assignment['id']; ?>" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Modifier">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $assignment['id']; ?>" title="Supprimer">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                                
                                <!-- Modal de confirmation de suppression -->
                                <div class="modal fade" id="deleteModal<?php echo $assignment['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $assignment['id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="deleteModalLabel<?php echo $assignment['id']; ?>">Confirmer la suppression</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Êtes-vous sûr de vouloir supprimer l'affectation de <strong><?php echo h($assignment['student_first_name'] ?? '') . ' ' . h($assignment['student_last_name'] ?? ''); ?></strong> au stage <strong><?php echo h($assignment['internship_title'] ?? ''); ?></strong> ?</p>
                                                <p class="text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Cette action est irréversible et libérera le stage pour d'autres affectations.</p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                <form action="/tutoring/views/admin/assignments/delete.php" method="POST">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                                    <input type="hidden" name="id" value="<?php echo $assignment['id']; ?>">
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

<?php
// Inclure le pied de page
include_once __DIR__ . '/../common/footer.php';
?>