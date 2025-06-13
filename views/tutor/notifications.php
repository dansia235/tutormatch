<?php
/**
 * Vue pour la liste des notifications du tuteur
 */

// Initialiser les variables
$pageTitle = 'Mes notifications';
$currentPage = 'notifications';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../includes/init.php';

// Vérifier les permissions
requireRole(['teacher']);

// Initialiser le modèle de notifications
$notificationModel = new Notification($db);

// Récupérer l'utilisateur actuel
$currentUserId = $_SESSION['user_id'];

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Récupérer les notifications
$options = [
    'user_id' => $currentUserId,
    'page' => $page,
    'limit' => $limit
];

// Filtrer par type si spécifié
if (isset($_GET['type']) && !empty($_GET['type'])) {
    $options['type'] = $_GET['type'];
}

// Filtrer par état de lecture si spécifié
if (isset($_GET['read'])) {
    $options['read'] = (bool)$_GET['read'];
}

// Récupérer les notifications
$notifications = $notificationModel->getAll($options);
$total = $notificationModel->countAll($options);

// Calculer la pagination
$totalPages = ceil($total / $limit);

// Inclure l'en-tête
include_once __DIR__ . '/../common/header.php';
?>

<div class="container-fluid mt-4">
    <!-- Titre de la page -->
    <div class="page-header d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="bi bi-bell me-2"></i>Mes notifications</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/tutoring/views/tutor/dashboard.php">Tableau de bord</a></li>
                    <li class="breadcrumb-item active">Notifications</li>
                </ol>
            </nav>
        </div>
        
        <div>
            <?php if ($notificationModel->countUnread($currentUserId) > 0): ?>
            <button id="mark-all-read-btn" class="btn btn-outline-primary">
                <i class="bi bi-check-all me-2"></i>Tout marquer comme lu
            </button>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Filtres -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="" method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="type" class="form-label">Type</label>
                    <select name="type" id="type" class="form-select">
                        <option value="">Tous les types</option>
                        <option value="info" <?php echo isset($_GET['type']) && $_GET['type'] === 'info' ? 'selected' : ''; ?>>Information</option>
                        <option value="success" <?php echo isset($_GET['type']) && $_GET['type'] === 'success' ? 'selected' : ''; ?>>Succès</option>
                        <option value="warning" <?php echo isset($_GET['type']) && $_GET['type'] === 'warning' ? 'selected' : ''; ?>>Avertissement</option>
                        <option value="error" <?php echo isset($_GET['type']) && $_GET['type'] === 'error' ? 'selected' : ''; ?>>Erreur</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="read" class="form-label">État</label>
                    <select name="read" id="read" class="form-select">
                        <option value="">Tous</option>
                        <option value="0" <?php echo isset($_GET['read']) && $_GET['read'] === '0' ? 'selected' : ''; ?>>Non lues</option>
                        <option value="1" <?php echo isset($_GET['read']) && $_GET['read'] === '1' ? 'selected' : ''; ?>>Lues</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Filtrer</button>
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-outline-secondary">Réinitialiser</a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Liste des notifications -->
    <div class="card">
        <div class="card-body p-0">
            <?php if (empty($notifications)): ?>
            <div class="p-4 text-center">
                <p class="text-muted">Aucune notification trouvée.</p>
            </div>
            <?php else: ?>
            <div class="list-group list-group-flush">
                <?php foreach ($notifications as $notification): ?>
                    <?php
                    // Déterminer les classes et icônes en fonction du type
                    $bgClass = '';
                    $textClass = '';
                    $icon = '';
                    
                    switch ($notification['type']) {
                        case 'success':
                            $bgClass = 'bg-success-100';
                            $textClass = 'text-success-800';
                            $icon = '<i class="bi bi-check-circle-fill text-success-500 fs-4"></i>';
                            break;
                        case 'error':
                            $bgClass = 'bg-danger-100';
                            $textClass = 'text-danger-800';
                            $icon = '<i class="bi bi-x-circle-fill text-danger-500 fs-4"></i>';
                            break;
                        case 'warning':
                            $bgClass = 'bg-warning-100';
                            $textClass = 'text-warning-800';
                            $icon = '<i class="bi bi-exclamation-triangle-fill text-warning-500 fs-4"></i>';
                            break;
                        case 'info':
                        default:
                            $bgClass = 'bg-info-100';
                            $textClass = 'text-info-800';
                            $icon = '<i class="bi bi-info-circle-fill text-info-500 fs-4"></i>';
                            break;
                    }
                    
                    // Déterminer l'URL de l'élément lié
                    $url = '#';
                    if ($notification['related_type'] && $notification['related_id']) {
                        $relatedType = $notification['related_type'];
                        $relatedId = $notification['related_id'];
                        
                        switch ($relatedType) {
                            case 'assignment':
                                $url = "/tutoring/views/tutor/assignments.php?id=$relatedId";
                                break;
                            case 'student':
                                $url = "/tutoring/views/tutor/students.php?id=$relatedId";
                                break;
                            case 'document':
                                $url = "/tutoring/views/tutor/documents.php?id=$relatedId";
                                break;
                            case 'meeting':
                                $url = "/tutoring/views/tutor/meetings.php?id=$relatedId";
                                break;
                            case 'message':
                                $url = "/tutoring/views/tutor/messages.php?conversation=$relatedId";
                                break;
                            case 'evaluation':
                                $url = "/tutoring/views/tutor/evaluations.php?id=$relatedId";
                                break;
                        }
                    }
                    
                    // Formater la date
                    $date = new DateTime($notification['created_at']);
                    $now = new DateTime();
                    $diff = $now->getTimestamp() - $date->getTimestamp();
                    
                    if ($diff < 60) {
                        $formattedDate = "À l'instant";
                    } elseif ($diff < 3600) {
                        $minutes = floor($diff / 60);
                        $formattedDate = "Il y a $minutes minute" . ($minutes > 1 ? 's' : '');
                    } elseif ($diff < 86400) {
                        $hours = floor($diff / 3600);
                        $formattedDate = "Il y a $hours heure" . ($hours > 1 ? 's' : '');
                    } elseif ($diff < 604800) {
                        $days = floor($diff / 86400);
                        $formattedDate = "Il y a $days jour" . ($days > 1 ? 's' : '');
                    } else {
                        $formattedDate = $date->format('d/m/Y à H:i');
                    }
                    ?>
                    <div class="list-group-item py-3 <?php echo $notification['read_at'] ? '' : $bgClass; ?> notification-item" data-id="<?php echo $notification['id']; ?>">
                        <div class="d-flex">
                            <div class="flex-shrink-0 me-3">
                                <?php echo $icon; ?>
                            </div>
                            <div class="flex-grow-1 ms-2">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <h5 class="mb-0 fw-bold <?php echo $notification['read_at'] ? 'text-muted' : $textClass; ?>">
                                        <?php echo h($notification['title']); ?>
                                    </h5>
                                    <small class="text-muted">
                                        <?php echo $formattedDate; ?>
                                    </small>
                                </div>
                                <p class="mb-2">
                                    <?php echo h($notification['message']); ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <div>
                                        <?php if ($url !== '#'): ?>
                                        <a href="<?php echo $url; ?>" class="btn btn-sm btn-outline-primary me-2">
                                            <i class="bi bi-eye me-1"></i> Voir détails
                                        </a>
                                        <?php endif; ?>
                                        
                                        <?php if (!$notification['read_at']): ?>
                                        <button type="button" class="btn btn-sm btn-outline-success mark-read-btn" data-id="<?php echo $notification['id']; ?>">
                                            <i class="bi bi-check-circle me-1"></i> Marquer comme lu
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <?php if ($notification['read_at']): ?>
                                        <span class="badge bg-secondary">Lu le <?php echo date('d/m/Y à H:i', strtotime($notification['read_at'])); ?></span>
                                        <?php else: ?>
                                        <span class="badge bg-primary">Non lu</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="p-3">
                <nav aria-label="Pagination des notifications">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo isset($_GET['type']) ? '&type=' . h($_GET['type']) : ''; ?><?php echo isset($_GET['read']) ? '&read=' . h($_GET['read']) : ''; ?>" aria-label="Précédent">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo isset($_GET['type']) ? '&type=' . h($_GET['type']) : ''; ?><?php echo isset($_GET['read']) ? '&read=' . h($_GET['read']) : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo isset($_GET['type']) ? '&type=' . h($_GET['type']) : ''; ?><?php echo isset($_GET['read']) ? '&read=' . h($_GET['read']) : ''; ?>" aria-label="Suivant">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Fonction pour marquer une notification comme lue
function markNotificationAsRead(id) {
    // Envoyer la requête
    fetch('/tutoring/api/notifications/direct-mark-read.php?id=' + id, {
        method: 'GET'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Recharger la page pour actualiser la liste
            window.location.reload();
        } else {
            console.error('Erreur:', data.message);
            alert('Une erreur est survenue lors du marquage de la notification comme lue.');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Une erreur est survenue lors de la communication avec le serveur.');
    });
}

// Fonction pour marquer toutes les notifications comme lues
function markAllNotificationsAsRead() {
    // Envoyer la requête
    fetch('/tutoring/api/notifications/direct-mark-all-read.php', {
        method: 'GET'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Recharger la page pour actualiser la liste
            window.location.reload();
        } else {
            console.error('Erreur:', data.message);
            alert('Une erreur est survenue lors du marquage de toutes les notifications comme lues.');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Une erreur est survenue lors de la communication avec le serveur.');
    });
}

// Ajouter les gestionnaires d'événements
document.addEventListener('DOMContentLoaded', function() {
    // Gestionnaire pour le bouton "Tout marquer comme lu"
    const markAllReadBtn = document.getElementById('mark-all-read-btn');
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', function() {
            markAllNotificationsAsRead();
        });
    }
    
    // Gestionnaires pour les boutons "Marquer comme lu"
    const markReadBtns = document.querySelectorAll('.mark-read-btn');
    markReadBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            markNotificationAsRead(id);
        });
    });
});
</script>

<?php
// Inclure le pied de page
include_once __DIR__ . '/../common/footer.php';
?>