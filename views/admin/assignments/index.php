<?php
/**
 * Vue pour la liste des affectations
 */

// Initialiser les variables
$pageTitle = 'Gestion des affectations';
$currentPage = 'assignments';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../../includes/init.php';

// Vérifier les permissions
requireRole(['admin', 'coordinator']);

// Instancier le contrôleur
$assignmentController = new AssignmentController($db);

// Traiter la recherche
if (isset($_GET['search'])) {
    $assignmentController->search();
} else {
    // Afficher toutes les affectations ou filtrer par statut
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    $assignmentController->index($status);
}
?>

<?php require_once __DIR__ . '/../../common/header.php'; ?>

<div class="container-fluid">
    <!-- En-tête de page -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2 mb-0"><i class="bi bi-diagram-3 me-2"></i>Gestion des affectations</h1>
        
        <div class="btn-group">
            <a href="/tutoring/views/admin/assignments/create.php" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i>Créer une affectation
            </a>
            <a href="/tutoring/views/admin/assignments/generate.php" class="btn btn-outline-primary">
                <i class="bi bi-magic me-2"></i>Générer automatiquement
            </a>
            <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#exportModal">
                <i class="bi bi-download me-2"></i>Exporter
            </button>
        </div>
    </div>
    
    <!-- Filtres et recherche -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <form action="" method="GET" class="d-flex">
                        <input type="text" name="term" class="form-control me-2" placeholder="Rechercher..." value="<?php echo isset($_GET['term']) ? h($_GET['term']) : ''; ?>">
                        <button type="submit" name="search" class="btn btn-outline-primary">
                            <i class="bi bi-search"></i>
                        </button>
                    </form>
                </div>
                <div class="col-md-6">
                    <div class="d-flex justify-content-md-end">
                        <div class="btn-group" role="group">
                            <a href="?status=" class="btn btn-outline-secondary <?php echo !isset($_GET['status']) ? 'active' : ''; ?>">Toutes</a>
                            <a href="?status=pending" class="btn btn-outline-warning <?php echo isset($_GET['status']) && $_GET['status'] === 'pending' ? 'active' : ''; ?>">En attente</a>
                            <a href="?status=confirmed" class="btn btn-outline-success <?php echo isset($_GET['status']) && $_GET['status'] === 'confirmed' ? 'active' : ''; ?>">Confirmées</a>
                            <a href="?status=rejected" class="btn btn-outline-danger <?php echo isset($_GET['status']) && $_GET['status'] === 'rejected' ? 'active' : ''; ?>">Rejetées</a>
                            <a href="?status=completed" class="btn btn-outline-info <?php echo isset($_GET['status']) && $_GET['status'] === 'completed' ? 'active' : ''; ?>">Terminées</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Liste des affectations -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Liste des affectations</h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($assignments)): ?>
            <div class="alert alert-info m-3">
                <i class="bi bi-info-circle me-2"></i>Aucune affectation trouvée.
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Étudiant</th>
                            <th scope="col">Tuteur</th>
                            <th scope="col">Stage</th>
                            <th scope="col">Date</th>
                            <th scope="col">Statut</th>
                            <th scope="col">Score</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assignments as $assignment): ?>
                        <tr>
                            <td><?php echo h($assignment['id']); ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php if (isset($assignment['student_profile_image']) && $assignment['student_profile_image']): ?>
                                    <img src="<?php echo h($assignment['student_profile_image']); ?>" alt="Student" class="rounded-circle me-2" width="32" height="32">
                                    <?php else: ?>
                                    <div class="avatar-sm me-2">
                                        <?php echo strtoupper(substr($assignment['student_first_name'], 0, 1) . substr($assignment['student_last_name'], 0, 1)); ?>
                                    </div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="fw-bold"><?php echo h($assignment['student_first_name'] . ' ' . $assignment['student_last_name']); ?></div>
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
                                        <?php echo strtoupper(substr($assignment['teacher_first_name'], 0, 1) . substr($assignment['teacher_last_name'], 0, 1)); ?>
                                    </div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="fw-bold"><?php echo h($assignment['teacher_first_name'] . ' ' . $assignment['teacher_last_name']); ?></div>
                                        <div class="text-muted small"><?php echo h($assignment['teacher_specialty'] ?? ''); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold"><?php echo h($assignment['internship_title']); ?></div>
                                <div class="text-muted small"><?php echo h($assignment['company_name']); ?></div>
                            </td>
                            <td>
                                <div><?php echo formatDate($assignment['assignment_date']); ?></div>
                                <?php if ($assignment['confirmation_date']): ?>
                                <div class="text-muted small">Confirmé: <?php echo formatDate($assignment['confirmation_date']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $statusBadge = [
                                    'pending' => '<span class="badge bg-warning">En attente</span>',
                                    'confirmed' => '<span class="badge bg-success">Confirmée</span>',
                                    'rejected' => '<span class="badge bg-danger">Rejetée</span>',
                                    'completed' => '<span class="badge bg-info">Terminée</span>'
                                ];
                                echo $statusBadge[$assignment['status']] ?? '<span class="badge bg-secondary">Inconnue</span>';
                                ?>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
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
                                    <div class="progress flex-grow-1 me-2" style="height: 6px;" title="Compatibilité: <?php echo number_format($compatibilityScore, 1); ?>/10">
                                        <div class="progress-bar <?php echo $compatibilityClass; ?>" role="progressbar" style="width: <?php echo ($compatibilityScore * 10); ?>%" aria-valuenow="<?php echo $compatibilityScore; ?>" aria-valuemin="0" aria-valuemax="10"></div>
                                    </div>
                                    <span class="small"><?php echo number_format($compatibilityScore, 1); ?></span>
                                </div>
                                
                                <?php if (isset($assignment['satisfaction_score']) && $assignment['satisfaction_score']): ?>
                                <div class="d-flex align-items-center mt-1">
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
                                    <div class="progress flex-grow-1 me-2" style="height: 6px;" title="Satisfaction: <?php echo number_format($satisfactionScore, 1); ?>/10">
                                        <div class="progress-bar <?php echo $satisfactionClass; ?>" role="progressbar" style="width: <?php echo ($satisfactionScore * 10); ?>%" aria-valuenow="<?php echo $satisfactionScore; ?>" aria-valuemin="0" aria-valuemax="10"></div>
                                    </div>
                                    <span class="small"><?php echo number_format($satisfactionScore, 1); ?></span>
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
                                                <p>Êtes-vous sûr de vouloir supprimer l'affectation de <strong><?php echo h($assignment['student_first_name'] . ' ' . $assignment['student_last_name']); ?></strong> au stage <strong><?php echo h($assignment['internship_title']); ?></strong> ?</p>
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

<!-- Export Modal -->
<div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exportModalLabel">Exporter les affectations</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="exportForm" action="/tutoring/api/export/assignments.php" method="GET" target="_blank">
                    <div class="mb-3">
                        <label class="form-label">Format d'exportation</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="format" id="formatCSV" value="csv" checked>
                            <label class="btn btn-outline-primary" for="formatCSV">CSV</label>
                            
                            <input type="radio" class="btn-check" name="format" id="formatExcel" value="excel">
                            <label class="btn btn-outline-primary" for="formatExcel">Excel</label>
                            
                            <input type="radio" class="btn-check" name="format" id="formatPDF" value="pdf">
                            <label class="btn btn-outline-primary" for="formatPDF">PDF</label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Options de filtrage</label>
                        <div class="form-check mb-2">
                            <input class="form-check-input export-filter" type="radio" name="exportFilter" id="exportAll" value="all" checked>
                            <label class="form-check-label" for="exportAll">
                                Exporter toutes les affectations
                            </label>
                        </div>
                        
                        <div class="form-check mb-2">
                            <input class="form-check-input export-filter" type="radio" name="exportFilter" id="exportFiltered" value="filtered">
                            <label class="form-check-label" for="exportFiltered">
                                Exporter uniquement les affectations filtrées
                            </label>
                        </div>
                        
                        <!-- Champs cachés pour les filtres actuels -->
                        <?php if (isset($_GET['term'])): ?>
                            <input type="hidden" name="term" id="exportTerm" value="<?php echo h($_GET['term']); ?>">
                        <?php endif; ?>
                        
                        <?php if (isset($_GET['status'])): ?>
                            <input type="hidden" name="status" id="exportStatus" value="<?php echo h($_GET['status']); ?>">
                        <?php endif; ?>
                        
                        <input type="hidden" name="exportAll" id="exportAllInput" value="true">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Colonnes à exporter</label>
                        <div class="row">
                            <div class="col-6">
                                <div class="form-check mb-2">
                                    <input class="form-check-input export-column" type="checkbox" name="columns[]" id="colStudentFirstName" value="student_first_name" checked>
                                    <label class="form-check-label" for="colStudentFirstName">Prénom étudiant</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input export-column" type="checkbox" name="columns[]" id="colStudentLastName" value="student_last_name" checked>
                                    <label class="form-check-label" for="colStudentLastName">Nom étudiant</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input export-column" type="checkbox" name="columns[]" id="colStudentNum" value="student_number" checked>
                                    <label class="form-check-label" for="colStudentNum">Numéro étudiant</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input export-column" type="checkbox" name="columns[]" id="colTeacherFirstName" value="teacher_first_name" checked>
                                    <label class="form-check-label" for="colTeacherFirstName">Prénom tuteur</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input export-column" type="checkbox" name="columns[]" id="colTeacherLastName" value="teacher_last_name" checked>
                                    <label class="form-check-label" for="colTeacherLastName">Nom tuteur</label>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-check mb-2">
                                    <input class="form-check-input export-column" type="checkbox" name="columns[]" id="colInternshipTitle" value="internship_title" checked>
                                    <label class="form-check-label" for="colInternshipTitle">Titre du stage</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input export-column" type="checkbox" name="columns[]" id="colCompanyName" value="company_name" checked>
                                    <label class="form-check-label" for="colCompanyName">Entreprise</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input export-column" type="checkbox" name="columns[]" id="colStatus" value="status" checked>
                                    <label class="form-check-label" for="colStatus">Statut</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input export-column" type="checkbox" name="columns[]" id="colCompat" value="compatibility_score" checked>
                                    <label class="form-check-label" for="colCompat">Compatibilité</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input export-column" type="checkbox" name="columns[]" id="colSatisf" value="satisfaction_score">
                                    <label class="form-check-label" for="colSatisf">Satisfaction</label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Message d'erreur pour les colonnes -->
                        <div id="columnsError" class="text-danger mt-2" style="display: none;">
                            Veuillez sélectionner au moins une colonne à exporter.
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="exportSubmitBtn">
                    <i class="bi bi-download me-1"></i>Exporter
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser les tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Gestion de l'exportation
    const exportForm = document.getElementById('exportForm');
    const exportSubmitBtn = document.getElementById('exportSubmitBtn');
    const exportFilterRadios = document.querySelectorAll('.export-filter');
    const exportAllInput = document.getElementById('exportAllInput');
    const exportColumns = document.querySelectorAll('.export-column');
    const columnsError = document.getElementById('columnsError');
    
    if (exportSubmitBtn && exportForm) {
        // Gestion de l'option de filtrage
        exportFilterRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'all') {
                    exportAllInput.value = 'true';
                } else {
                    exportAllInput.value = 'false';
                }
            });
        });
        
        // Soumission du formulaire d'exportation
        exportSubmitBtn.addEventListener('click', function() {
            // Vérifier qu'au moins une colonne est sélectionnée
            const selectedColumns = Array.from(exportColumns).filter(checkbox => checkbox.checked);
            
            if (selectedColumns.length === 0) {
                columnsError.style.display = 'block';
                return;
            } else {
                columnsError.style.display = 'none';
            }
            
            // Soumettre le formulaire
            exportForm.submit();
            
            // Fermer la modale
            const modal = bootstrap.Modal.getInstance(document.getElementById('exportModal'));
            modal.hide();
        });
        
        // Réinitialiser l'erreur des colonnes quand une est cochée
        exportColumns.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const anyChecked = Array.from(exportColumns).some(cb => cb.checked);
                if (anyChecked) {
                    columnsError.style.display = 'none';
                }
            });
        });
    }
});
</script>

<?php require_once __DIR__ . '/../../common/footer.php'; ?>