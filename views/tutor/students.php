<?php
/**
 * Page de gestion des étudiants pour le tuteur
 */

// Titre de la page
$pageTitle = 'Mes Étudiants';
$currentPage = 'students';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../includes/init.php';

// Vérifier que l'utilisateur est tuteur
requireRole('teacher');

// Récupérer le tuteur de la session
$userModel = new User($db);
$user = $userModel->getById($_SESSION['user_id']);

// Récupérer le modèle du tuteur
$teacherModel = new Teacher($db);
$teacher = $teacherModel->getByUserId($_SESSION['user_id']);

if (!$teacher) {
    setFlashMessage('error', 'Profil de tuteur non trouvé.');
    redirect('/tutoring/index.php');
}

// Récupérer les affectations d'étudiants pour ce tuteur
$assignments = $teacherModel->getAssignments($teacher['id']);

// Modèle d'internship
$internshipModel = new Internship($db);
$studentModel = new Student($db);

// Filtre par statut
$statusFilter = $_GET['status'] ?? 'all';
$filteredAssignments = [];

if ($statusFilter !== 'all') {
    foreach ($assignments as $assignment) {
        if ($assignment['status'] === $statusFilter) {
            $filteredAssignments[] = $assignment;
        }
    }
} else {
    $filteredAssignments = $assignments;
}

// Statistiques
$stats = [
    'total' => count($assignments),
    'pending' => 0,
    'confirmed' => 0,
    'completed' => 0,
    'rejected' => 0
];

foreach ($assignments as $assignment) {
    if (isset($stats[$assignment['status']])) {
        $stats[$assignment['status']]++;
    }
}

// Inclure l'en-tête
include_once __DIR__ . '/../common/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-people me-2"></i>Mes Étudiants</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/tutoring/views/tutor/dashboard.php">Tableau de bord</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Mes Étudiants</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3 fade-in delay-1">
            <div class="card stat-card">
                <div class="value"><?php echo h($stats['total']); ?></div>
                <div class="label">Total</div>
                <div class="progress mt-2">
                    <div class="progress-bar" role="progressbar" style="width: 100%;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <small class="text-muted">Étudiants affectés</small>
            </div>
        </div>
        <div class="col-md-3 fade-in delay-2">
            <div class="card stat-card">
                <div class="value"><?php echo h($stats['confirmed']); ?></div>
                <div class="label">Actifs</div>
                <div class="progress mt-2">
                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $stats['total'] > 0 ? h(($stats['confirmed'] / $stats['total']) * 100) : 0; ?>%;" aria-valuenow="<?php echo $stats['total'] > 0 ? h(($stats['confirmed'] / $stats['total']) * 100) : 0; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <small class="text-muted">Stages en cours</small>
            </div>
        </div>
        <div class="col-md-3 fade-in delay-3">
            <div class="card stat-card">
                <div class="value"><?php echo h($stats['pending']); ?></div>
                <div class="label">En attente</div>
                <div class="progress mt-2">
                    <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $stats['total'] > 0 ? h(($stats['pending'] / $stats['total']) * 100) : 0; ?>%;" aria-valuenow="<?php echo $stats['total'] > 0 ? h(($stats['pending'] / $stats['total']) * 100) : 0; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <small class="text-muted">Attente de confirmation</small>
            </div>
        </div>
        <div class="col-md-3 fade-in delay-4">
            <div class="card stat-card">
                <div class="value"><?php echo h($stats['completed']); ?></div>
                <div class="label">Terminés</div>
                <div class="progress mt-2">
                    <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $stats['total'] > 0 ? h(($stats['completed'] / $stats['total']) * 100) : 0; ?>%;" aria-valuenow="<?php echo $stats['total'] > 0 ? h(($stats['completed'] / $stats['total']) * 100) : 0; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <small class="text-muted">Stages terminés</small>
            </div>
        </div>
    </div>
    
    <!-- Filters and Search -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <form method="get" class="row g-3">
                        <div class="col-md-4">
                            <label for="status" class="form-label">Statut</label>
                            <select name="status" id="status" class="form-select">
                                <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>Tous les statuts</option>
                                <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>En attente</option>
                                <option value="confirmed" <?php echo $statusFilter === 'confirmed' ? 'selected' : ''; ?>>Confirmé</option>
                                <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Terminé</option>
                                <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejeté</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="search" class="form-label">Recherche</label>
                            <input type="text" class="form-control" id="search" name="search" placeholder="Nom, entreprise..." value="<?php echo h($_GET['search'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">Filtrer</button>
                            <a href="/tutoring/views/tutor/students.php" class="btn btn-outline-secondary">Réinitialiser</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body d-flex flex-column justify-content-center">
                    <div class="mb-2">
                        <span class="badge bg-success rounded-pill">■</span> Confirmé
                        <span class="badge bg-warning rounded-pill ms-2">■</span> En attente
                        <span class="badge bg-info rounded-pill ms-2">■</span> Terminé
                        <span class="badge bg-danger rounded-pill ms-2">■</span> Rejeté
                    </div>
                    <p class="mb-0 text-muted small">Cliquez sur un étudiant pour voir ses détails complets et gérer ses informations de stage.</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Students List -->
    <div class="row">
        <div class="col-12">
            <div class="card mb-4 fade-in">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Liste des étudiants</span>
                    <button class="btn btn-sm btn-outline-secondary" onclick="toggleView()">
                        <i class="bi bi-grid-3x3-gap" id="viewToggleIcon"></i>
                    </button>
                </div>
                <div class="card-body">
                    <?php if (empty($filteredAssignments)): ?>
                    <div class="alert alert-info" role="alert">
                        <i class="bi bi-info-circle-fill me-2"></i>Aucun étudiant ne correspond à vos critères de filtrage. Ajustez vos filtres ou contactez le coordinateur des stages si vous pensez qu'il s'agit d'une erreur.
                    </div>
                    <?php else: ?>
                    
                    <!-- Vue en cartes (par défaut) -->
                    <div id="cardView" class="row g-3">
                        <?php foreach ($filteredAssignments as $assignment): 
                            // Récupérer les informations supplémentaires de l'étudiant
                            $student = $studentModel->getById($assignment['student_id']);
                            
                            // Récupérer les détails du stage
                            $internship = $internshipModel->getById($assignment['internship_id']);
                            
                            // Déterminer la classe CSS pour le statut
                            $statusClass = [
                                'pending' => 'bg-warning',
                                'confirmed' => 'bg-success',
                                'completed' => 'bg-info',
                                'rejected' => 'bg-danger'
                            ][$assignment['status']] ?? 'bg-secondary';
                            
                            // Traduire le statut en français
                            $statusLabels = [
                                'pending' => 'En attente',
                                'confirmed' => 'Confirmé',
                                'completed' => 'Terminé',
                                'rejected' => 'Rejeté'
                            ];
                            
                            // Calculer la progression du stage
                            $progress = 0;
                            if ($assignment['status'] === 'confirmed') {
                                if (isset($internship['start_date']) && isset($internship['end_date'])) {
                                    $startDate = new DateTime($internship['start_date']);
                                    $endDate = new DateTime($internship['end_date']);
                                    $today = new DateTime();
                                    
                                    if ($today >= $startDate && $today <= $endDate) {
                                        $totalDays = $startDate->diff($endDate)->days;
                                        $daysElapsed = $startDate->diff($today)->days;
                                        $progress = min(100, round(($daysElapsed / $totalDays) * 100));
                                    } elseif ($today > $endDate) {
                                        $progress = 100;
                                    }
                                }
                            } elseif ($assignment['status'] === 'completed') {
                                $progress = 100;
                            }
                            
                            // Générer l'avatar
                            $initials = mb_substr($assignment['student_first_name'], 0, 1) . mb_substr($assignment['student_last_name'], 0, 1);
                            $avatarUrl = "https://ui-avatars.com/api/?name=" . urlencode($initials) . "&background=f39c12&color=fff";
                        ?>
                        <div class="col-md-6 col-lg-4 student-card" data-student-name="<?php echo h(strtolower($assignment['student_first_name'] . ' ' . $assignment['student_last_name'])); ?>" data-company="<?php echo h(strtolower($assignment['company_name'])); ?>">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex align-items-start mb-3">
                                        <img src="<?php echo h($avatarUrl); ?>" alt="Student" class="rounded-circle me-3" width="50" height="50">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1 fw-bold"><?php echo h($assignment['student_first_name'] . ' ' . $assignment['student_last_name']); ?></h6>
                                            <small class="text-muted"><?php echo h($student['student_number'] ?? 'N/A'); ?></small>
                                        </div>
                                        <span class="badge <?php echo $statusClass; ?> rounded-pill">
                                            <?php echo h($statusLabels[$assignment['status']] ?? $assignment['status']); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="fw-semibold text-truncate" title="<?php echo h($assignment['internship_title']); ?>">
                                            <?php echo h($assignment['internship_title']); ?>
                                        </div>
                                        <div class="text-muted small text-truncate" title="<?php echo h($assignment['company_name']); ?>">
                                            <?php echo h($assignment['company_name']); ?>
                                        </div>
                                        <?php if (isset($internship['start_date']) && isset($internship['end_date'])): ?>
                                        <div class="text-muted small">
                                            <?php echo h(date('d/m', strtotime($internship['start_date']))); ?> - 
                                            <?php echo h(date('d/m/Y', strtotime($internship['end_date']))); ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between mb-1">
                                            <small class="text-muted">Progression</small>
                                            <small class="text-muted"><?php echo h($progress); ?>%</small>
                                        </div>
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar" role="progressbar" style="width: <?php echo h($progress); ?>%;" aria-valuenow="<?php echo h($progress); ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex gap-1">
                                        <a href="/tutoring/views/tutor/student-details.php?id=<?php echo h($assignment['student_id']); ?>" class="btn btn-outline-primary btn-sm flex-fill">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="/tutoring/views/tutor/meetings.php?student_id=<?php echo h($assignment['student_id']); ?>" class="btn btn-outline-success btn-sm flex-fill">
                                            <i class="bi bi-calendar-plus"></i>
                                        </a>
                                        <a href="/tutoring/views/tutor/documents.php?student_id=<?php echo h($assignment['student_id']); ?>" class="btn btn-outline-info btn-sm flex-fill">
                                            <i class="bi bi-folder"></i>
                                        </a>
                                        <div class="dropdown flex-fill">
                                            <button class="btn btn-outline-secondary btn-sm w-100 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="bi bi-three-dots"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a class="dropdown-item" href="/tutoring/views/tutor/evaluations.php?student_id=<?php echo h($assignment['student_id']); ?>">
                                                    <i class="bi bi-clipboard-check me-2"></i>Évaluer
                                                </a></li>
                                                <li><a class="dropdown-item" href="/tutoring/views/tutor/messages.php?to=<?php echo h($student['user_id'] ?? ''); ?>">
                                                    <i class="bi bi-chat-left-text me-2"></i>Message
                                                </a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Vue en tableau (masquée par défaut) -->
                    <div id="tableView" style="display: none;">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th style="width: 25%;">Étudiant</th>
                                        <th style="width: 25%;">Stage</th>
                                        <th style="width: 20%;">Entreprise</th>
                                        <th style="width: 15%;">Statut</th>
                                        <th style="width: 15%;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($filteredAssignments as $assignment): 
                                        $student = $studentModel->getById($assignment['student_id']);
                                        $internship = $internshipModel->getById($assignment['internship_id']);
                                        
                                        $statusClass = [
                                            'pending' => 'bg-warning',
                                            'confirmed' => 'bg-success',
                                            'completed' => 'bg-info',
                                            'rejected' => 'bg-danger'
                                        ][$assignment['status']] ?? 'bg-secondary';
                                        
                                        $statusLabels = [
                                            'pending' => 'En attente',
                                            'confirmed' => 'Confirmé',
                                            'completed' => 'Terminé',
                                            'rejected' => 'Rejeté'
                                        ];
                                        
                                        $initials = mb_substr($assignment['student_first_name'], 0, 1) . mb_substr($assignment['student_last_name'], 0, 1);
                                        $avatarUrl = "https://ui-avatars.com/api/?name=" . urlencode($initials) . "&background=f39c12&color=fff";
                                    ?>
                                    <tr class="student-row" data-student-name="<?php echo h(strtolower($assignment['student_first_name'] . ' ' . $assignment['student_last_name'])); ?>" data-company="<?php echo h(strtolower($assignment['company_name'])); ?>">
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="<?php echo h($avatarUrl); ?>" alt="Student" class="rounded-circle me-2" width="32" height="32">
                                                <div>
                                                    <div class="fw-bold"><?php echo h($assignment['student_first_name'] . ' ' . $assignment['student_last_name']); ?></div>
                                                    <div class="small text-muted"><?php echo h($student['student_number'] ?? 'N/A'); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-bold"><?php echo h($assignment['internship_title']); ?></div>
                                            <?php if (isset($internship['start_date']) && isset($internship['end_date'])): ?>
                                            <div class="small text-muted">
                                                <?php echo h(date('d/m/Y', strtotime($internship['start_date']))); ?> - 
                                                <?php echo h(date('d/m/Y', strtotime($internship['end_date']))); ?>
                                            </div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo h($assignment['company_name']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $statusClass; ?> rounded-pill">
                                                <?php echo h($statusLabels[$assignment['status']] ?? $assignment['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="/tutoring/views/tutor/student-details.php?id=<?php echo h($assignment['student_id']); ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                        <i class="bi bi-three-dots"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li><a class="dropdown-item" href="/tutoring/views/tutor/meetings.php?student_id=<?php echo h($assignment['student_id']); ?>">Réunion</a></li>
                                                        <li><a class="dropdown-item" href="/tutoring/views/tutor/documents.php?student_id=<?php echo h($assignment['student_id']); ?>">Documents</a></li>
                                                        <li><a class="dropdown-item" href="/tutoring/views/tutor/evaluations.php?student_id=<?php echo h($assignment['student_id']); ?>">Évaluer</a></li>
                                                        <li><a class="dropdown-item" href="/tutoring/views/tutor/messages.php?to=<?php echo h($student['user_id'] ?? ''); ?>">Message</a></li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <?php if (count($filteredAssignments) > 10): ?>
                    <div class="d-flex justify-content-between mt-3">
                        <div>
                            <p class="small text-muted">Affichage de <?php echo count($filteredAssignments); ?> étudiant(s)</p>
                        </div>
                        <nav aria-label="Page navigation">
                            <ul class="pagination pagination-sm">
                                <li class="page-item disabled">
                                    <a class="page-link" href="#" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                <li class="page-item"><a class="page-link" href="#">2</a></li>
                                <li class="page-item">
                                    <a class="page-link" href="#" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Upcoming Deadlines and Recent Activities -->
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4 fade-in">
                <div class="card-header">
                    <span>Échéances à venir</span>
                </div>
                <div class="card-body">
                    <div class="alert alert-info" role="alert">
                        <i class="bi bi-info-circle-fill me-2"></i>Aucune échéance imminente.
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card mb-4 fade-in">
                <div class="card-header">
                    <span>Activités récentes</span>
                </div>
                <div class="card-body">
                    <div class="alert alert-info" role="alert">
                        <i class="bi bi-info-circle-fill me-2"></i>Aucune activité récente.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Fonction pour la recherche d'étudiants
    document.getElementById('search').addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();
        const currentView = document.getElementById('cardView').style.display !== 'none' ? 'card' : 'table';
        
        if (currentView === 'card') {
            const cards = document.querySelectorAll('.student-card');
            cards.forEach(card => {
                const studentName = card.getAttribute('data-student-name');
                const company = card.getAttribute('data-company');
                const isVisible = studentName.includes(searchTerm) || company.includes(searchTerm);
                card.style.display = isVisible ? '' : 'none';
            });
        } else {
            const rows = document.querySelectorAll('.student-row');
            rows.forEach(row => {
                const studentName = row.getAttribute('data-student-name');
                const company = row.getAttribute('data-company');
                const isVisible = studentName.includes(searchTerm) || company.includes(searchTerm);
                row.style.display = isVisible ? '' : 'none';
            });
        }
    });
    
    // Fonction pour basculer entre la vue cartes et tableau
    function toggleView() {
        const cardView = document.getElementById('cardView');
        const tableView = document.getElementById('tableView');
        const icon = document.getElementById('viewToggleIcon');
        
        if (cardView.style.display === 'none') {
            // Passer à la vue cartes
            cardView.style.display = '';
            tableView.style.display = 'none';
            icon.className = 'bi bi-grid-3x3-gap';
        } else {
            // Passer à la vue tableau
            cardView.style.display = 'none';
            tableView.style.display = '';
            icon.className = 'bi bi-table';
        }
    }
</script>

<style>
    /* Assurer que les cartes ont la même hauteur */
    .student-card .card {
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .student-card .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
    }
    
    /* Contrôler les débordements de texte */
    .text-truncate {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    /* Assurer que la table ne déborde pas */
    .table-responsive {
        overflow-x: auto;
    }
    
    /* Style pour les boutons d'action */
    .btn-group .btn {
        border-radius: 0.375rem;
    }
    
    .btn-group .btn:not(:last-child) {
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
    }
    
    .btn-group .btn:not(:first-child) {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
        margin-left: -1px;
    }
</style>

<?php
// Inclure le pied de page
include_once __DIR__ . '/../common/footer.php';
?>