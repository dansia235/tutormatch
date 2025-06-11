<?php
/**
 * Bootstrap version of internships content for backward compatibility
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
    .badge-available {
        background-color: #2ecc71;
    }
    
    .badge-assigned {
        background-color: #f1c40f;
    }
    
    .badge-completed {
        background-color: #3498db;
    }
    
    .badge-cancelled {
        background-color: #e74c3c;
    }
</style>

<div class="container-fluid mt-4">
    <!-- Titre de la page et bouton d'ajout -->
    <div class="page-header">
        <div>
            <h2><i class="bi bi-briefcase me-2"></i>Gestion des stages</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/tutoring/views/admin/dashboard.php">Tableau de bord</a></li>
                    <li class="breadcrumb-item active">Stages</li>
                </ol>
            </nav>
        </div>
        
        <?php if (hasRole(['admin', 'coordinator'])): ?>
        <div class="btn-group">
            <a href="/tutoring/views/admin/internships/create.php" class="btn btn-primary add-button">
                <i class="bi bi-plus-circle"></i>Ajouter un stage
            </a>
            <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#exportModal">
                <i class="bi bi-download me-2"></i>Exporter
            </button>
        </div>
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
                            <input type="text" class="form-control" name="term" placeholder="Rechercher un stage..." value="<?php echo isset($_GET['term']) ? h($_GET['term']) : ''; ?>">
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
                        <a href="?status=available<?php echo isset($_GET['term']) ? '&term='.h($_GET['term']).'&search=1' : ''; ?>" class="filter-tab <?php echo $activeFilter === 'available' ? 'active' : ''; ?>">Disponibles</a>
                        <a href="?status=assigned<?php echo isset($_GET['term']) ? '&term='.h($_GET['term']).'&search=1' : ''; ?>" class="filter-tab <?php echo $activeFilter === 'assigned' ? 'active' : ''; ?>">Assignés</a>
                        <a href="?status=completed<?php echo isset($_GET['term']) ? '&term='.h($_GET['term']).'&search=1' : ''; ?>" class="filter-tab <?php echo $activeFilter === 'completed' ? 'active' : ''; ?>">Complétés</a>
                        <a href="?status=cancelled<?php echo isset($_GET['term']) ? '&term='.h($_GET['term']).'&search=1' : ''; ?>" class="filter-tab <?php echo $activeFilter === 'cancelled' ? 'active' : ''; ?>">Annulés</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Cartes statistiques -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-value"><?php echo $totalInternships; ?></div>
                <div class="stat-label">Stages totaux</div>
                <div class="progress">
                    <div class="progress-bar" role="progressbar" style="width: 100%;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-value"><?php echo $availableCount; ?></div>
                <div class="stat-label">Stages disponibles</div>
                <div class="progress">
                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $totalInternships > 0 ? ($availableCount / $totalInternships) * 100 : 0; ?>%;" aria-valuenow="<?php echo $availableCount; ?>" aria-valuemin="0" aria-valuemax="<?php echo $totalInternships; ?>"></div>
                </div>
                <div class="small text-muted mt-2"><?php echo $totalInternships > 0 ? number_format(($availableCount / $totalInternships) * 100, 0) : 0; ?>% des stages</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-value"><?php echo $assignedCount; ?></div>
                <div class="stat-label">Stages assignés</div>
                <div class="progress">
                    <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $totalInternships > 0 ? ($assignedCount / $totalInternships) * 100 : 0; ?>%;" aria-valuenow="<?php echo $assignedCount; ?>" aria-valuemin="0" aria-valuemax="<?php echo $totalInternships; ?>"></div>
                </div>
                <div class="small text-muted mt-2"><?php echo $totalInternships > 0 ? number_format(($assignedCount / $totalInternships) * 100, 0) : 0; ?>% des stages</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-value"><?php echo $completedCount; ?></div>
                <div class="stat-label">Stages complétés</div>
                <div class="progress">
                    <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $totalInternships > 0 ? ($completedCount / $totalInternships) * 100 : 0; ?>%;" aria-valuenow="<?php echo $completedCount; ?>" aria-valuemin="0" aria-valuemax="<?php echo $totalInternships; ?>"></div>
                </div>
                <div class="small text-muted mt-2"><?php echo $totalInternships > 0 ? number_format(($completedCount / $totalInternships) * 100, 0) : 0; ?>% des stages</div>
            </div>
        </div>
    </div>
    
    <!-- Liste des stages -->
    <div class="card">
        <div class="card-body p-4">
            <div class="list-header">
                <h4><i class="bi bi-list me-2"></i>Liste des stages</h4>
                <span class="count-badge"><?php echo $totalInternships; ?> stages</span>
            </div>
            
            <?php if (empty($internships)): ?>
            <div class="info-message">
                <i class="bi bi-info-circle"></i>
                <span>Aucun stage trouvé.</span>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>Entreprise</th>
                            <th>Domaine</th>
                            <th>Période</th>
                            <th>Lieu</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($internships as $internship): ?>
                        <tr>
                            <td>
                                <div class="fw-bold"><?php echo h($internship['title']); ?></div>
                                <div class="text-muted small"><?php echo substr(h($internship['description']), 0, 50) . (strlen($internship['description']) > 50 ? '...' : ''); ?></div>
                            </td>
                            <td><?php echo h($internship['company_name']); ?></td>
                            <td><?php echo h($internship['domain']); ?></td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span><i class="bi bi-calendar-event me-1"></i> <?php echo date('d/m/Y', strtotime($internship['start_date'])); ?></span>
                                    <span><i class="bi bi-calendar-check me-1"></i> <?php echo date('d/m/Y', strtotime($internship['end_date'])); ?></span>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <i class="bi bi-geo-alt me-1"></i> 
                                    <?php echo h($internship['location'] ?: 'Non spécifié'); ?>
                                </div>
                                <div class="small text-muted">
                                    <?php 
                                    $workModes = [
                                        'on_site' => 'Sur site',
                                        'remote' => 'Télétravail',
                                        'hybrid' => 'Hybride'
                                    ];
                                    echo $workModes[$internship['work_mode']] ?? $internship['work_mode']; 
                                    ?>
                                </div>
                            </td>
                            <td>
                                <?php
                                $statusBadges = [
                                    'available' => '<span class="badge bg-success">Disponible</span>',
                                    'assigned' => '<span class="badge bg-warning">Assigné</span>',
                                    'completed' => '<span class="badge bg-info">Complété</span>',
                                    'cancelled' => '<span class="badge bg-danger">Annulé</span>'
                                ];
                                echo $statusBadges[$internship['status']] ?? '<span class="badge bg-secondary">' . h($internship['status']) . '</span>';
                                ?>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="/tutoring/views/admin/internships/show.php?id=<?php echo $internship['id']; ?>" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Voir les détails">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="/tutoring/views/admin/internships/edit.php?id=<?php echo $internship['id']; ?>" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Modifier">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $internship['id']; ?>" title="Supprimer">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                                
                                <!-- Modal de confirmation de suppression -->
                                <div class="modal fade" id="deleteModal<?php echo $internship['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $internship['id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="deleteModalLabel<?php echo $internship['id']; ?>">Confirmer la suppression</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Êtes-vous sûr de vouloir supprimer le stage <strong><?php echo h($internship['title']); ?></strong> ?</p>
                                                <p class="text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Cette action est irréversible<?php if ($internship['status'] === 'assigned'): ?> et annulera toutes les affectations liées à ce stage<?php endif; ?>.</p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                <form action="/tutoring/views/admin/internships/delete.php" method="POST">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                                    <input type="hidden" name="id" value="<?php echo $internship['id']; ?>">
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

<!-- Export Modal -->
<div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exportModalLabel">Exporter les stages</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="exportForm" action="/tutoring/api/export/internships.php" method="GET" target="_blank">
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
                                Exporter tous les stages
                            </label>
                        </div>
                        
                        <div class="form-check mb-2">
                            <input class="form-check-input export-filter" type="radio" name="exportFilter" id="exportFiltered" value="filtered">
                            <label class="form-check-label" for="exportFiltered">
                                Exporter uniquement les stages filtrés
                            </label>
                        </div>
                        
                        <!-- Champs cachés pour les filtres actuels -->
                        <?php if (isset($_GET['term'])): ?>
                            <input type="hidden" name="term" id="exportTerm" value="<?php echo h($_GET['term']); ?>">
                        <?php endif; ?>
                        
                        <?php if (isset($_GET['status'])): ?>
                            <input type="hidden" name="status" id="exportStatus" value="<?php echo h($_GET['status']); ?>">
                        <?php endif; ?>
                        
                        <?php if (isset($_GET['domain'])): ?>
                            <input type="hidden" name="domain" id="exportDomain" value="<?php echo h($_GET['domain']); ?>">
                        <?php endif; ?>
                        
                        <?php if (isset($_GET['company_id'])): ?>
                            <input type="hidden" name="company_id" id="exportCompanyId" value="<?php echo h($_GET['company_id']); ?>">
                        <?php endif; ?>
                        
                        <input type="hidden" name="exportAll" id="exportAllInput" value="true">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Colonnes à exporter</label>
                        <div class="row">
                            <div class="col-6">
                                <div class="form-check mb-2">
                                    <input class="form-check-input export-column" type="checkbox" name="columns[]" id="colTitle" value="title" checked>
                                    <label class="form-check-label" for="colTitle">Titre</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input export-column" type="checkbox" name="columns[]" id="colCompany" value="company_name" checked>
                                    <label class="form-check-label" for="colCompany">Entreprise</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input export-column" type="checkbox" name="columns[]" id="colDomain" value="domain" checked>
                                    <label class="form-check-label" for="colDomain">Domaine</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input export-column" type="checkbox" name="columns[]" id="colStartDate" value="start_date" checked>
                                    <label class="form-check-label" for="colStartDate">Date de début</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input export-column" type="checkbox" name="columns[]" id="colEndDate" value="end_date" checked>
                                    <label class="form-check-label" for="colEndDate">Date de fin</label>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-check mb-2">
                                    <input class="form-check-input export-column" type="checkbox" name="columns[]" id="colLocation" value="location" checked>
                                    <label class="form-check-label" for="colLocation">Lieu</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input export-column" type="checkbox" name="columns[]" id="colWorkMode" value="work_mode" checked>
                                    <label class="form-check-label" for="colWorkMode">Mode de travail</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input export-column" type="checkbox" name="columns[]" id="colCompensation" value="compensation">
                                    <label class="form-check-label" for="colCompensation">Rémunération</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input export-column" type="checkbox" name="columns[]" id="colStatus" value="status" checked>
                                    <label class="form-check-label" for="colStatus">Statut</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input export-column" type="checkbox" name="columns[]" id="colDescription" value="description">
                                    <label class="form-check-label" for="colDescription">Description</label>
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