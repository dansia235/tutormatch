<?php
/**
 * Vue pour la gestion des stages par l'étudiant
 */

// Initialiser les variables
$pageTitle = 'Mes stages';
$currentPage = 'internship';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../includes/init.php';

// Vérifier que l'utilisateur est connecté et a le rôle étudiant
requireRole('student');

// Récupérer l'ID de l'étudiant
$studentModel = new Student($db);
$student = $studentModel->getByUserId($_SESSION['user_id']);

if (!$student) {
    setFlashMessage('error', 'Profil étudiant non trouvé');
    redirect('/tutoring/views/student/dashboard.php');
}

// Récupérer les affectations de stage de l'étudiant
$assignmentModel = new Assignment($db);
$assignments = $assignmentModel->getByStudentId($student['id']);

// Récupérer les stages disponibles pour l'étudiant avec pagination
$internshipModel = new Internship($db);

// Paramètres de pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$itemsPerPage = 6; // 6 stages par page pour un affichage optimal en grille
$offset = ($page - 1) * $itemsPerPage;

// Essayer d'abord la méthode spécifique pour l'étudiant
$availableInternships = [];
$totalInternships = 0;

try {
    $availableInternships = $internshipModel->getAvailableForStudent($student['id']);
    
    // Si aucun stage disponible avec cette méthode, essayer une approche plus générale
    if (empty($availableInternships)) {
        // Récupérer tous les stages actifs ou disponibles
        $allInternships = $internshipModel->getAll();
        $availableInternships = array_filter($allInternships, function($internship) {
            return in_array($internship['status'], ['available', 'active']) && 
                   strtotime($internship['start_date']) > time() - (30 * 24 * 60 * 60); // Dans les 30 derniers jours ou futurs
        });
    }
    
    $totalInternships = count($availableInternships);
    
    // Appliquer la pagination
    $availableInternships = array_slice($availableInternships, $offset, $itemsPerPage);
    
} catch (Exception $e) {
    // En cas d'erreur, essayer de récupérer tous les stages
    error_log("Erreur lors de la récupération des stages: " . $e->getMessage());
    try {
        $allInternships = $internshipModel->getAll();
        $availableInternships = array_slice($allInternships, $offset, $itemsPerPage);
        $totalInternships = count($allInternships);
    } catch (Exception $e2) {
        $availableInternships = [];
        $totalInternships = 0;
    }
}

// Calculer le nombre de pages
$totalPages = ceil($totalInternships / $itemsPerPage);

// Récupérer les préférences de l'étudiant si elles existent
$preferences = $studentModel->getPreferences($student['id']);

// Inclure l'en-tête
include_once __DIR__ . '/../common/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2>Mes stages</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/tutoring/views/student/dashboard.php">Tableau de bord</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Mes stages</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <!-- Main Content Row -->
    <div class="row">
        <!-- Left Column -->
        <div class="col-lg-8">
            <!-- Stage Assignments -->
            <div class="card mb-4 fade-in">
                <div class="card-header">
                    <span>Mes affectations de stage</span>
                    <a href="/tutoring/views/student/preferences.php" class="btn btn-sm btn-outline-primary">Mes préférences</a>
                </div>
                <div class="card-body">
                    <?php if (empty($assignments)): ?>
                    <div class="alert alert-info" role="alert">
                        <i class="bi bi-info-circle-fill me-2"></i>Vous n'avez pas encore été affecté à un stage. Consultez la liste des stages disponibles ci-dessous ou contactez votre coordinateur pour plus d'informations.
                    </div>
                    <?php else: ?>
                        <?php foreach ($assignments as $assignment): ?>
                        <div class="card mb-3 border-0 shadow-sm">
                            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">Stage : <?php echo h($assignment['internship_title']); ?></h6>
                                <span class="badge <?php 
                                    echo match($assignment['status']) {
                                        'pending' => 'bg-warning',
                                        'confirmed' => 'bg-success',
                                        'rejected' => 'bg-danger',
                                        'completed' => 'bg-info',
                                        default => 'bg-secondary'
                                    };
                                ?>">
                                    <?php 
                                    echo match($assignment['status']) {
                                        'pending' => 'En attente',
                                        'confirmed' => 'Confirmé',
                                        'rejected' => 'Rejeté',
                                        'completed' => 'Terminé',
                                        default => ucfirst($assignment['status'])
                                    };
                                    ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <p><strong>Entreprise :</strong> <?php echo h($assignment['company_name']); ?></p>
                                        <p><strong>Période :</strong> 
                                            Du <?php echo date('d/m/Y', strtotime($assignment['internship_start_date'])); ?> 
                                            au <?php echo date('d/m/Y', strtotime($assignment['internship_end_date'])); ?>
                                        </p>
                                        <p><strong>Tuteur :</strong> 
                                            <?php echo h($assignment['teacher_first_name'] . ' ' . $assignment['teacher_last_name']); ?>
                                        </p>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="d-grid gap-2">
                                            <a href="/tutoring/views/student/documents.php" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-file-earmark-text me-1"></i> Documents
                                            </a>
                                            <a href="/tutoring/views/student/meetings.php" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-calendar-event me-1"></i> Réunions
                                            </a>
                                            <a href="/tutoring/views/student/tutor.php" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-person-badge me-1"></i> Tuteur
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Right Column -->
        <div class="col-lg-4">
            <!-- Quick Actions -->
            <div class="card mb-4 fade-in">
                <div class="card-header">
                    Actions rapides
                </div>
                <div class="card-body">
                    <a href="/tutoring/views/student/preferences.php" class="btn btn-outline-primary w-100 mb-2">
                        <i class="bi bi-sliders me-2"></i>Définir mes préférences
                    </a>
                    <a href="/tutoring/views/student/documents.php" class="btn btn-outline-primary w-100 mb-2">
                        <i class="bi bi-folder me-2"></i>Gérer mes documents
                    </a>
                    <a href="/tutoring/views/student/meetings.php" class="btn btn-outline-primary w-100 mb-2">
                        <i class="bi bi-calendar-event me-2"></i>Planifier une réunion
                    </a>
                    <a href="/tutoring/views/student/evaluations.php" class="btn btn-primary w-100">
                        <i class="bi bi-star me-2"></i>Voir mes évaluations
                    </a>
                </div>
            </div>
            
            <!-- Stage Stats -->
            <div class="card mb-4 fade-in">
                <div class="card-header">
                    Statistiques
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Stages disponibles</span>
                            <strong><?php echo $totalInternships; ?></strong>
                        </div>
                        <div class="progress mt-1">
                            <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $totalInternships > 0 ? 100 : 0; ?>%;"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Préférences définies</span>
                            <strong><?php echo count($preferences); ?></strong>
                        </div>
                        <div class="progress mt-1">
                            <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo count($preferences) > 0 ? 100 : 0; ?>%;"></div>
                        </div>
                    </div>
                    <div>
                        <div class="d-flex justify-content-between">
                            <span>Affectations</span>
                            <strong><?php echo count($assignments); ?></strong>
                        </div>
                        <div class="progress mt-1">
                            <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo count($assignments) > 0 ? 100 : 0; ?>%;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Available Internships Section -->
    <div class="row">
        <div class="col-12">
            <div class="card fade-in">
                <div class="card-header">
                    <span>Stages disponibles</span>
                    <a href="/tutoring/views/student/preferences.php" class="btn btn-sm btn-outline-primary">Gérer mes préférences</a>
                </div>
                <div class="card-body">
                    <?php if (empty($availableInternships)): ?>
                    <div class="alert alert-info" role="alert">
                        <i class="bi bi-info-circle-fill me-2"></i>Il n'y a actuellement aucun stage disponible correspondant à votre profil. Revenez régulièrement ou contactez votre coordinateur.
                    </div>
                    <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($availableInternships as $internship): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="card h-100 border-0 shadow-sm hover-shadow">
                                <div class="card-body">
                                    <h6 class="card-title"><?php echo h($internship['title']); ?></h6>
                                    <p class="card-subtitle mb-2 text-muted"><?php echo h($internship['company_name']); ?></p>
                                    
                                    <div class="mb-2">
                                        <span class="badge bg-secondary me-1"><?php echo h($internship['domain']); ?></span>
                                        <span class="badge bg-secondary me-1"><?php echo h($internship['location']); ?></span>
                                    </div>
                                    
                                    <p class="card-text small">
                                        <?php echo substr(h($internship['description']), 0, 100) . (strlen($internship['description']) > 100 ? '...' : ''); ?>
                                    </p>
                                    
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="bi bi-calendar-event me-1"></i>
                                        <small>Du <?php echo date('d/m', strtotime($internship['start_date'])); ?> au <?php echo date('d/m/Y', strtotime($internship['end_date'])); ?></small>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <?php
                                        // Vérifier si le stage est déjà dans les préférences
                                        $isPreferred = false;
                                        foreach ($preferences as $preference) {
                                            if ($preference['internship_id'] == $internship['id']) {
                                                $isPreferred = true;
                                                break;
                                            }
                                        }
                                        ?>
                                        
                                        <?php if ($isPreferred): ?>
                                        <button class="btn btn-success btn-sm" disabled>
                                            <i class="bi bi-check-circle me-1"></i>Dans mes préférences
                                        </button>
                                        <?php else: ?>
                                        <button class="btn btn-outline-primary btn-sm" onclick="addPreference(<?php echo $internship['id']; ?>)">
                                            <i class="bi bi-star me-1"></i>Ajouter aux préférences
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <div class="d-flex justify-content-center mt-4">
                        <nav aria-label="Navigation des stages">
                            <ul class="pagination">
                                <!-- Bouton Précédent -->
                                <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page - 1 ?>" aria-label="Précédent">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                                <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link" aria-label="Précédent">
                                        <span aria-hidden="true">&laquo;</span>
                                    </span>
                                </li>
                                <?php endif; ?>
                                
                                <!-- Numéros de page -->
                                <?php
                                $startPage = max(1, $page - 2);
                                $endPage = min($totalPages, $page + 2);
                                
                                if ($startPage > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=1">1</a>
                                </li>
                                <?php if ($startPage > 2): ?>
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                                <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                </li>
                                <?php endfor; ?>
                                
                                <?php if ($endPage < $totalPages): ?>
                                <?php if ($endPage < $totalPages - 1): ?>
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                                <?php endif; ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $totalPages ?>"><?= $totalPages ?></a>
                                </li>
                                <?php endif; ?>
                                
                                <!-- Bouton Suivant -->
                                <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page + 1 ?>" aria-label="Suivant">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                                <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link" aria-label="Suivant">
                                        <span aria-hidden="true">&raquo;</span>
                                    </span>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Information de pagination -->
                    <?php if ($totalInternships > 0): ?>
                    <div class="text-center text-muted mt-2">
                        <small>
                            Affichage de <?= min($offset + 1, $totalInternships) ?> à <?= min($offset + $itemsPerPage, $totalInternships) ?> 
                            sur <?= $totalInternships ?> stage<?= $totalInternships > 1 ? 's' : '' ?>
                        </small>
                    </div>
                    <?php endif; ?>
                    
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Styles pour la pagination */
.pagination .page-item.active .page-link {
    color: #fff !important;
    background-color: #0d6efd;
    border-color: #0d6efd;
}

.card.fade-in {
    animation: fadeIn 0.5s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.hover-shadow:hover {
    transform: translateY(-2px);
    transition: all 0.3s ease;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.badge {
    font-size: 0.75em;
}
</style>

<script>
    // Fonction pour ajouter un stage aux préférences de l'étudiant
    function addPreference(internshipId) {
        window.location.href = '/tutoring/views/student/preferences.php?add=' + internshipId;
    }
</script>

<?php
// Inclure le pied de page
include_once __DIR__ . '/../common/footer.php';
?>