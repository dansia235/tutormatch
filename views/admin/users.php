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

// Instancier le contrôleur
$userController = new UserController($db);

// Créer une instance du modèle User
$userModel = new User($db);

// Traiter la recherche ou afficher tous les utilisateurs
if (isset($_GET['search'])) {
    $term = isset($_GET['term']) ? $_GET['term'] : '';
    $role = isset($_GET['role']) ? $_GET['role'] : null;
    
    // Utiliser directement le modèle User pour la recherche
    $users = $userModel->search($term, $role);
} else {
    // Afficher tous les utilisateurs ou filtrer par rôle
    $role = isset($_GET['role']) ? $_GET['role'] : null;
    
    // Utiliser directement le modèle User pour récupérer tous les utilisateurs
    $users = $userModel->getAll($role);
}

// Récupérer les statistiques
$totalUsers = count($users);

// Calculer les statistiques par rôle
$adminCount = 0;
$coordinatorCount = 0;
$teacherCount = 0;
$studentCount = 0;

foreach ($users as $user) {
    if ($user['role'] === 'admin') {
        $adminCount++;
    } elseif ($user['role'] === 'coordinator') {
        $coordinatorCount++;
    } elseif ($user['role'] === 'teacher') {
        $teacherCount++;
    } elseif ($user['role'] === 'student') {
        $studentCount++;
    }
}

// Inclure l'en-tête
include_once __DIR__ . '/../common/header.php';

// Définir le filtre actif
$activeFilter = isset($_GET['role']) ? $_GET['role'] : '';
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
    
    /* User avatar */
    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: #3498db;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 1rem;
    }
    
    .user-avatar img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
    }
    
    /* Role badges */
    .badge-admin {
        background-color: #e74c3c;
    }
    
    .badge-coordinator {
        background-color: #9b59b6;
    }
    
    .badge-teacher {
        background-color: #3498db;
    }
    
    .badge-student {
        background-color: #2ecc71;
    }
    
    /* Last login indicator */
    .last-login {
        display: flex;
        align-items: center;
        font-size: 0.8rem;
        color: #6c757d;
    }
    
    .last-login i {
        margin-right: 6px;
        font-size: 0.9rem;
    }
    
    .last-login.recent {
        color: #2ecc71;
    }
    
    .last-login.old {
        color: #e74c3c;
    }
</style>

<div class="container-fluid mt-4">
    <!-- Titre de la page et bouton d'ajout -->
    <div class="page-header">
        <div>
            <h2><i class="bi bi-people me-2"></i>Gestion des utilisateurs</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/tutoring/views/admin/dashboard.php">Tableau de bord</a></li>
                    <li class="breadcrumb-item active">Utilisateurs</li>
                </ol>
            </nav>
        </div>
        
        <?php if (hasRole(['admin'])): ?>
        <a href="/tutoring/views/admin/user/create.php" class="btn btn-primary add-button">
            <i class="bi bi-plus-circle"></i>Ajouter un utilisateur
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
                            <input type="text" class="form-control" name="term" placeholder="Rechercher un utilisateur..." value="<?php echo isset($_GET['term']) ? h($_GET['term']) : ''; ?>">
                            <?php if (!empty($activeFilter)): ?>
                            <input type="hidden" name="role" value="<?php echo h($activeFilter); ?>">
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
                        <a href="?role=admin<?php echo isset($_GET['term']) ? '&term='.h($_GET['term']).'&search=1' : ''; ?>" class="filter-tab <?php echo $activeFilter === 'admin' ? 'active' : ''; ?>">Administrateurs</a>
                        <a href="?role=coordinator<?php echo isset($_GET['term']) ? '&term='.h($_GET['term']).'&search=1' : ''; ?>" class="filter-tab <?php echo $activeFilter === 'coordinator' ? 'active' : ''; ?>">Coordinateurs</a>
                        <a href="?role=teacher<?php echo isset($_GET['term']) ? '&term='.h($_GET['term']).'&search=1' : ''; ?>" class="filter-tab <?php echo $activeFilter === 'teacher' ? 'active' : ''; ?>">Tuteurs</a>
                        <a href="?role=student<?php echo isset($_GET['term']) ? '&term='.h($_GET['term']).'&search=1' : ''; ?>" class="filter-tab <?php echo $activeFilter === 'student' ? 'active' : ''; ?>">Étudiants</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Cartes statistiques -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-value"><?php echo $totalUsers; ?></div>
                <div class="stat-label">Utilisateurs totaux</div>
                <div class="progress">
                    <div class="progress-bar" role="progressbar" style="width: 100%;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-value"><?php echo $studentCount; ?></div>
                <div class="stat-label">Étudiants</div>
                <div class="progress">
                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $totalUsers > 0 ? ($studentCount / $totalUsers) * 100 : 0; ?>%;" aria-valuenow="<?php echo $studentCount; ?>" aria-valuemin="0" aria-valuemax="<?php echo $totalUsers; ?>"></div>
                </div>
                <div class="small text-muted mt-2"><?php echo $totalUsers > 0 ? number_format(($studentCount / $totalUsers) * 100, 0) : 0; ?>% des utilisateurs</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-value"><?php echo $teacherCount; ?></div>
                <div class="stat-label">Tuteurs</div>
                <div class="progress">
                    <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $totalUsers > 0 ? ($teacherCount / $totalUsers) * 100 : 0; ?>%;" aria-valuenow="<?php echo $teacherCount; ?>" aria-valuemin="0" aria-valuemax="<?php echo $totalUsers; ?>"></div>
                </div>
                <div class="small text-muted mt-2"><?php echo $totalUsers > 0 ? number_format(($teacherCount / $totalUsers) * 100, 0) : 0; ?>% des utilisateurs</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-value"><?php echo $adminCount + $coordinatorCount; ?></div>
                <div class="stat-label">Administrateurs</div>
                <div class="progress">
                    <div class="progress-bar bg-danger" role="progressbar" style="width: <?php echo $totalUsers > 0 ? (($adminCount + $coordinatorCount) / $totalUsers) * 100 : 0; ?>%;" aria-valuenow="<?php echo $adminCount + $coordinatorCount; ?>" aria-valuemin="0" aria-valuemax="<?php echo $totalUsers; ?>"></div>
                </div>
                <div class="small text-muted mt-2"><?php echo $totalUsers > 0 ? number_format((($adminCount + $coordinatorCount) / $totalUsers) * 100, 0) : 0; ?>% des utilisateurs</div>
            </div>
        </div>
    </div>
    
    <!-- Liste des utilisateurs -->
    <div class="card">
        <div class="card-body p-4">
            <div class="list-header">
                <h4><i class="bi bi-list me-2"></i>Liste des utilisateurs</h4>
                <span class="count-badge"><?php echo $totalUsers; ?> utilisateurs</span>
            </div>
            
            <?php if (empty($users)): ?>
            <div class="info-message">
                <i class="bi bi-info-circle"></i>
                <span>Aucun utilisateur trouvé.</span>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Utilisateur</th>
                            <th>Email</th>
                            <th>Rôle</th>
                            <th>Département</th>
                            <th>Date de création</th>
                            <th>Dernière connexion</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="user-avatar me-3">
                                        <?php if (!empty($user['profile_image'])): ?>
                                        <img src="<?php echo h($user['profile_image']); ?>" alt="<?php echo h($user['first_name'] . ' ' . $user['last_name']); ?>">
                                        <?php else: ?>
                                        <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold"><?php echo h($user['first_name'] . ' ' . $user['last_name']); ?></div>
                                        <div class="text-muted small"><?php echo h($user['username']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo h($user['email']); ?></td>
                            <td>
                                <?php
                                $roleBadges = [
                                    'admin' => '<span class="badge bg-danger">Administrateur</span>',
                                    'coordinator' => '<span class="badge bg-warning">Coordinateur</span>',
                                    'teacher' => '<span class="badge bg-info">Tuteur</span>',
                                    'student' => '<span class="badge bg-success">Étudiant</span>'
                                ];
                                echo $roleBadges[$user['role']] ?? '<span class="badge bg-secondary">' . h($user['role']) . '</span>';
                                ?>
                            </td>
                            <td><?php echo h($user['department'] ?? 'Non spécifié'); ?></td>
                            <td>
                                <?php if (isset($user['created_at'])): ?>
                                <div>
                                    <i class="bi bi-calendar-event me-1"></i>
                                    <?php echo date('d/m/Y', strtotime($user['created_at'])); ?>
                                </div>
                                <?php else: ?>
                                <span class="text-muted">Non disponible</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                // Afficher la dernière connexion avec un indicateur visuel
                                if (isset($user['last_login']) && $user['last_login']) {
                                    $lastLogin = strtotime($user['last_login']);
                                    $now = time();
                                    $daysAgo = floor(($now - $lastLogin) / (60 * 60 * 24));
                                    
                                    $loginClass = 'normal';
                                    if ($daysAgo < 3) {
                                        $loginClass = 'recent';
                                    } elseif ($daysAgo > 30) {
                                        $loginClass = 'old';
                                    }
                                    
                                    echo '<div class="last-login ' . $loginClass . '">';
                                    echo '<i class="bi bi-clock"></i>';
                                    
                                    if ($daysAgo == 0) {
                                        // Aujourd'hui
                                        $hoursAgo = floor(($now - $lastLogin) / (60 * 60));
                                        if ($hoursAgo == 0) {
                                            $minutesAgo = floor(($now - $lastLogin) / 60);
                                            echo 'Il y a ' . $minutesAgo . ' minute' . ($minutesAgo > 1 ? 's' : '');
                                        } else {
                                            echo 'Il y a ' . $hoursAgo . ' heure' . ($hoursAgo > 1 ? 's' : '');
                                        }
                                    } elseif ($daysAgo == 1) {
                                        echo 'Hier';
                                    } elseif ($daysAgo < 7) {
                                        echo 'Il y a ' . $daysAgo . ' jours';
                                    } else {
                                        echo date('d/m/Y', $lastLogin);
                                    }
                                    
                                    echo '</div>';
                                } else {
                                    echo '<span class="text-muted">Jamais connecté</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="/tutoring/views/admin/user/show.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Voir le profil">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    
                                    <?php if (hasRole(['admin']) || (hasRole(['coordinator']) && $user['role'] !== 'admin')): ?>
                                    <a href="/tutoring/views/admin/user/edit.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Modifier">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php endif; ?>
                                    
                                    <?php 
                                    // Récupérer l'ID de l'utilisateur courant
                                    $currentUserId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
                                    
                                    if (hasRole(['admin']) && $user['id'] != $currentUserId): ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $user['id']; ?>" title="Supprimer">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if (hasRole(['admin']) && $user['id'] != $currentUserId): ?>
                                <!-- Modal de confirmation de suppression -->
                                <div class="modal fade" id="deleteModal<?php echo $user['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $user['id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="deleteModalLabel<?php echo $user['id']; ?>">Confirmer la suppression</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Êtes-vous sûr de vouloir supprimer l'utilisateur <strong><?php echo h($user['first_name'] . ' ' . $user['last_name']); ?></strong> ?</p>
                                                <p class="text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Cette action est irréversible et supprimera toutes les données associées à cet utilisateur.</p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                <form action="/tutoring/views/admin/user/delete.php" method="POST">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                                    <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
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

<?php
// Inclure le pied de page
include_once __DIR__ . '/../common/footer.php';
?>