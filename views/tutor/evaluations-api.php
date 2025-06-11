<?php
/**
 * Page de gestion des évaluations par le tuteur (version API)
 */

// Titre de la page
$pageTitle = 'Évaluations des étudiants';
$currentPage = 'evaluations';

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

// Filtre par étudiant
$studentFilter = $_GET['student_id'] ?? null;
$typeFilter = $_GET['type'] ?? 'all';

// Inclure l'en-tête
include_once __DIR__ . '/../common/header.php';
?>

<div class="container-fluid px-0" id="evaluations-app" data-teacher-id="<?php echo h($teacher['id']); ?>">
    <div class="row g-0 mx-0">
        <div class="col-12 px-4 py-3">
            <h2><i class="bi bi-clipboard-check me-2"></i>Évaluations des étudiants</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/tutoring/views/tutor/dashboard.php">Tableau de bord</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Évaluations</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="row g-0 mx-0 px-4 mb-4">
        <div class="col-md-3 fade-in delay-1 pe-3">
            <div class="card stat-card">
                <div class="value" id="total-evaluations">-</div>
                <div class="label">Total</div>
                <div class="progress mt-2">
                    <div class="progress-bar" role="progressbar" style="width: 100%;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <small class="text-muted">Évaluations prévues</small>
            </div>
        </div>
        <div class="col-md-3 fade-in delay-2 pe-3">
            <div class="card stat-card">
                <div class="value" id="completed-evaluations">-</div>
                <div class="label">Complétées</div>
                <div class="progress mt-2">
                    <div class="progress-bar bg-success" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" id="completed-progress"></div>
                </div>
                <small class="text-muted">Évaluations terminées</small>
            </div>
        </div>
        <div class="col-md-3 fade-in delay-3 pe-3">
            <div class="card stat-card">
                <div class="value" id="average-score">-</div>
                <div class="label">Moyenne</div>
                <div class="progress mt-2">
                    <div class="progress-bar bg-info" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" id="average-progress"></div>
                </div>
                <small class="text-muted">Note moyenne /5</small>
            </div>
        </div>
        <div class="col-md-3 fade-in delay-4">
            <div class="card stat-card">
                <div class="value" id="improvement-rate">-%</div>
                <div class="label">Progression</div>
                <div class="progress mt-2">
                    <div class="progress-bar bg-warning" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" id="improvement-progress"></div>
                </div>
                <small class="text-muted">Taux d'amélioration</small>
            </div>
        </div>
    </div>
    
    <!-- Filters and Search -->
    <div class="row g-0 mx-0 px-4 mb-4">
        <div class="col-12">
            <div class="row g-0">
                <div class="col-lg-8 pe-3">
                    <div class="card">
                        <div class="card-body">
                            <form method="get" class="row g-3">
                                <div class="col-md-6">
                                    <label for="student_id" class="form-label">Étudiant</label>
                                    <select class="form-select" id="student_id" name="student_id" onchange="this.form.submit()">
                                        <option value="" disabled selected>Choisir un étudiant...</option>
                                        <!-- Liste des étudiants à charger via API -->
                                    </select>
                                </div>
                                
                                <div class="col-md-6" id="type-filter-container" style="display: none;">
                                    <label for="type" class="form-label">Type d'évaluation</label>
                                    <select class="form-select" id="type" name="type" onchange="this.form.submit()">
                                        <option value="all">Toutes les évaluations</option>
                                        <option value="mid_term">Mi-parcours</option>
                                        <option value="final">Finale</option>
                                        <option value="company">Entreprise</option>
                                    </select>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card h-100">
                        <div class="card-header">
                            Actions rapides
                        </div>
                        <div class="card-body">
                            <button class="btn btn-primary w-100 mb-2" id="new-evaluation-btn" disabled>
                                <i class="bi bi-plus-lg me-2"></i>Nouvelle évaluation
                            </button>
                            <a href="/tutoring/views/tutor/students.php" class="btn btn-outline-primary w-100 mb-2">
                                <i class="bi bi-mortarboard me-2"></i>Mes étudiants
                            </a>
                            <a href="/tutoring/views/tutor/meetings.php" class="btn btn-outline-primary w-100 mb-2">
                                <i class="bi bi-calendar-event me-2"></i>Réunions
                            </a>
                            <a href="/tutoring/views/tutor/documents.php" class="btn btn-outline-primary w-100">
                                <i class="bi bi-folder me-2"></i>Documents
                            </a>
                            <hr>
                            <h6 class="mb-2">Guide d'évaluation</h6>
                            <p class="small mb-2"><strong>Mi-parcours:</strong> Évaluation de la progression et identification des axes d'amélioration.</p>
                            <p class="small mb-0"><strong>Finale:</strong> Bilan global des compétences acquises et recommandations futures.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Pending Evaluations Alert -->
    <div class="row g-0 mx-0 px-4 mb-4" id="pending-evaluations-alert" style="display: none;">
        <div class="col-12">
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <h5 class="alert-heading"><i class="bi bi-exclamation-triangle-fill me-2"></i>Évaluations en attente</h5>
                <p>Vous avez <span id="pending-count">0</span> évaluation(s) à compléter :</p>
                <ul class="mb-0" id="pending-list">
                    <!-- Liste des évaluations en attente à charger via API -->
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    </div>
    
    <!-- Student Information Card (if selected) -->
    <div class="row g-0 mx-0 px-4 mb-4" id="student-info-card" style="display: none;">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <div class="d-flex align-items-center">
                                <img id="student-avatar" src="" alt="Student" class="rounded-circle me-3" width="80" height="80">
                                <div>
                                    <h4 class="mb-1" id="student-name"></h4>
                                    <p class="text-muted mb-0" id="student-program"></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <h6 class="text-muted mb-1">Stage</h6>
                            <p class="mb-1"><strong id="internship-title"></strong></p>
                            <p class="mb-0" id="company-name"></p>
                            <p class="small text-muted mb-0" id="internship-dates"></p>
                        </div>
                        <div class="col-md-2">
                            <h6 class="text-muted mb-1">Progression</h6>
                            <div class="progress mb-2" style="height: 10px;">
                                <div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" id="internship-progress"></div>
                            </div>
                            <p class="small text-muted mb-0"><span id="progress-percent">0</span>% complété</p>
                        </div>
                        <div class="col-md-2 text-end">
                            <a href="#" class="btn btn-outline-primary btn-sm mb-1 d-block" id="student-documents-link">
                                <i class="bi bi-folder me-1"></i>Documents
                            </a>
                            <a href="#" class="btn btn-outline-primary btn-sm d-block" id="student-meetings-link">
                                <i class="bi bi-calendar-event me-1"></i>Réunions
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Student Statistics -->
    <div class="row g-0 mx-0 px-4 mb-4" id="student-stats-card" style="display: none;">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <span>Statistiques de l'étudiant</span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="chart-container">
                                <canvas id="studentProgressChart"></canvas>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <h5 class="mb-3">Compétences évaluées</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between">
                                            <span>Technique</span>
                                            <span id="technical-score">-/5</span>
                                        </div>
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar bg-primary" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="5" id="technical-progress"></div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between">
                                            <span>Communication</span>
                                            <span id="communication-score">-/5</span>
                                        </div>
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="5" id="communication-progress"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between">
                                            <span>Travail d'équipe</span>
                                            <span id="teamwork-score">-/5</span>
                                        </div>
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar bg-info" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="5" id="teamwork-progress"></div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between">
                                            <span>Autonomie</span>
                                            <span id="autonomy-score">-/5</span>
                                        </div>
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar bg-warning" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="5" id="autonomy-progress"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="row g-0 mx-0">
        <div class="col-12 px-4">
            <div id="no-student-selected" class="card mb-4">
                <div class="card-body">
                    <div class="text-center py-5">
                        <i class="bi bi-clipboard-check display-1 text-muted mb-3"></i>
                        <h4>Sélectionnez un étudiant pour commencer</h4>
                        <p class="text-muted">Choisissez un étudiant dans la liste pour consulter ou créer des évaluations.</p>
                    </div>
                </div>
            </div>
            
            <!-- Vue d'ensemble de tous les étudiants -->
            <div id="evaluations-overview" class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Vue d'ensemble des évaluations</span>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-download"></i> Exporter
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                            <li><a class="dropdown-item" href="#" id="export-csv">CSV</a></li>
                            <li><a class="dropdown-item" href="#" id="export-pdf">PDF</a></li>
                            <li><a class="dropdown-item" href="#" id="export-excel">Excel</a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Étudiant</th>
                                    <th>Formation</th>
                                    <th>Entreprise</th>
                                    <th>Mi-parcours</th>
                                    <th>Finale</th>
                                    <th>Moyenne</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="evaluations-table-body">
                                <!-- Liste des évaluations à charger via API -->
                                <tr>
                                    <td colspan="7" class="text-center">
                                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                                            <span class="visually-hidden">Chargement...</span>
                                        </div>
                                        <span class="ms-2">Chargement des données...</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Create Evaluation Button -->
            <div id="student-evaluations-header" class="card mb-4" style="display: none;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Évaluations de <span id="student-name-header"></span></h5>
                        <button class="btn btn-primary" id="create-evaluation-btn">
                            <i class="bi bi-plus-lg me-2"></i>Nouvelle évaluation
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- List of Evaluations -->
            <div id="no-evaluations-message" class="card mb-4" style="display: none;">
                <div class="card-body">
                    <div class="alert alert-info" role="alert">
                        <div class="d-flex">
                            <div class="me-3">
                                <i class="bi bi-info-circle-fill fs-4"></i>
                            </div>
                            <div>
                                <h5 class="alert-heading">Aucune évaluation disponible</h5>
                                <p class="mb-0">Vous n'avez pas encore créé d'évaluation pour cet étudiant. Utilisez le bouton "Nouvelle évaluation" pour commencer.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div id="evaluations-list">
                <!-- Les évaluations seront chargées ici via JavaScript -->
            </div>
        </div>
    </div>
</div>

<style>
/* Reset des marges et paddings pour utiliser toute la largeur */
.container-fluid {
    padding-left: 0;
    padding-right: 0;
    margin-left: 0;
    margin-right: 0;
    max-width: 100%;
}

/* Ajustement des cards et contenu */
.card {
    margin-bottom: 1.5rem;
    border-radius: 0.5rem;
}

/* Ajustement des colonnes */
[class*="col-"] {
    padding-left: 15px;
    padding-right: 15px;
}

/* Ajustement des lignes */
.row {
    margin-left: 0;
    margin-right: 0;
}

/* Correction pour le contenu principal */
.main-content {
    padding-left: 0;
    padding-right: 0;
}

/* Ajustement des marges internes */
.px-4 {
    padding-left: 1.5rem !important;
    padding-right: 1.5rem !important;
}

/* Ajustement pour les cartes de statistiques */
.stat-card {
    padding: 1.25rem;
    height: 100%;
}

/* Correction pour les éléments flex */
.d-flex {
    flex-wrap: nowrap;
}

/* Correction pour les éléments en ligne */
.inline-items {
    white-space: nowrap;
}

/* Ajustement pour les petits écrans */
@media (max-width: 768px) {
    .px-4 {
        padding-left: 1rem !important;
        padding-right: 1rem !important;
    }
    
    [class*="col-"] {
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }
}
</style>

<!-- Create Evaluation Modal -->
<div class="modal fade" id="createEvaluationModal" tabindex="-1" aria-labelledby="createEvaluationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createEvaluationModalLabel">Nouvelle évaluation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="evaluation-form">
                <div class="modal-body">
                    <input type="hidden" id="assignment_id" name="assignment_id" value="">
                    <input type="hidden" id="selected_student_id" name="student_id" value="">
                    
                    <div class="mb-4">
                        <h5>Informations générales</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="evaluation_type" class="form-label">Type d'évaluation</label>
                                    <select class="form-select" id="evaluation_type" name="evaluation_type" required>
                                        <option value="mid_term">Mi-parcours</option>
                                        <option value="final">Finale</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="evaluation_date" class="form-label">Date d'évaluation</label>
                                    <input type="date" class="form-control" id="evaluation_date" name="evaluation_date" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h5>Compétences techniques</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Maîtrise des technologies</label>
                                    <select class="form-select" name="criteria[technical_mastery]" required>
                                        <option value="" disabled selected>Sélectionnez une note</option>
                                        <option value="1">1 - Insuffisant</option>
                                        <option value="2">2 - Passable</option>
                                        <option value="3">3 - Satisfaisant</option>
                                        <option value="4">4 - Très bien</option>
                                        <option value="5">5 - Excellent</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Qualité du travail</label>
                                    <select class="form-select" name="criteria[work_quality]" required>
                                        <option value="" disabled selected>Sélectionnez une note</option>
                                        <option value="1">1 - Insuffisant</option>
                                        <option value="2">2 - Passable</option>
                                        <option value="3">3 - Satisfaisant</option>
                                        <option value="4">4 - Très bien</option>
                                        <option value="5">5 - Excellent</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Résolution de problèmes</label>
                                    <select class="form-select" name="criteria[problem_solving]" required>
                                        <option value="" disabled selected>Sélectionnez une note</option>
                                        <option value="1">1 - Insuffisant</option>
                                        <option value="2">2 - Passable</option>
                                        <option value="3">3 - Satisfaisant</option>
                                        <option value="4">4 - Très bien</option>
                                        <option value="5">5 - Excellent</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Documentation</label>
                                    <select class="form-select" name="criteria[documentation]" required>
                                        <option value="" disabled selected>Sélectionnez une note</option>
                                        <option value="1">1 - Insuffisant</option>
                                        <option value="2">2 - Passable</option>
                                        <option value="3">3 - Satisfaisant</option>
                                        <option value="4">4 - Très bien</option>
                                        <option value="5">5 - Excellent</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h5>Compétences professionnelles</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Autonomie</label>
                                    <select class="form-select" name="criteria[autonomy]" required>
                                        <option value="" disabled selected>Sélectionnez une note</option>
                                        <option value="1">1 - Insuffisant</option>
                                        <option value="2">2 - Passable</option>
                                        <option value="3">3 - Satisfaisant</option>
                                        <option value="4">4 - Très bien</option>
                                        <option value="5">5 - Excellent</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Communication</label>
                                    <select class="form-select" name="criteria[communication]" required>
                                        <option value="" disabled selected>Sélectionnez une note</option>
                                        <option value="1">1 - Insuffisant</option>
                                        <option value="2">2 - Passable</option>
                                        <option value="3">3 - Satisfaisant</option>
                                        <option value="4">4 - Très bien</option>
                                        <option value="5">5 - Excellent</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Intégration dans l'équipe</label>
                                    <select class="form-select" name="criteria[team_integration]" required>
                                        <option value="" disabled selected>Sélectionnez une note</option>
                                        <option value="1">1 - Insuffisant</option>
                                        <option value="2">2 - Passable</option>
                                        <option value="3">3 - Satisfaisant</option>
                                        <option value="4">4 - Très bien</option>
                                        <option value="5">5 - Excellent</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Respect des délais</label>
                                    <select class="form-select" name="criteria[deadline_respect]" required>
                                        <option value="" disabled selected>Sélectionnez une note</option>
                                        <option value="1">1 - Insuffisant</option>
                                        <option value="2">2 - Passable</option>
                                        <option value="3">3 - Satisfaisant</option>
                                        <option value="4">4 - Très bien</option>
                                        <option value="5">5 - Excellent</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h5>Commentaires et recommandations</h5>
                        <div class="mb-3">
                            <label for="comments" class="form-label">Commentaires généraux</label>
                            <textarea class="form-control" id="comments" name="comments" rows="4" placeholder="Points forts, progression, observations générales..." required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Points à améliorer</label>
                            <div id="improvement-areas-container">
                                <div class="input-group mb-2">
                                    <input type="text" class="form-control" name="areas_for_improvement[]" placeholder="Point à améliorer...">
                                    <button class="btn btn-outline-secondary" type="button" onclick="addImprovementArea()"><i class="bi bi-plus"></i></button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Recommandations</label>
                            <div id="recommendations-container">
                                <div class="input-group mb-2">
                                    <input type="text" class="form-control" name="recommendations[]" placeholder="Recommandation...">
                                    <button class="btn btn-outline-secondary" type="button" onclick="addRecommendation()"><i class="bi bi-plus"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer l'évaluation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Variables globales
    let selectedStudent = null;
    let selectedAssignment = null;
    let studentEvaluations = [];
    let studentChart = null;
    let assignments = [];
    const evaluationTypes = {
        'mid_term': 'Mi-parcours', 
        'final': 'Finale', 
        'company': 'Entreprise'
    };
    
    // Au chargement du document
    document.addEventListener('DOMContentLoaded', function() {
        // Récupérer les statistiques globales
        fetchTeacherStats();
        
        // Récupérer les affectations du tuteur
        fetchAssignments();
        
        // Gérer le changement d'étudiant
        document.getElementById('student_id').addEventListener('change', function() {
            const studentId = this.value;
            if (studentId) {
                window.location.href = `/tutoring/views/tutor/evaluations-api.php?student_id=${studentId}`;
            }
        });
        
        // Gérer le changement de type d'évaluation
        document.getElementById('type').addEventListener('change', function() {
            const studentId = document.getElementById('student_id').value;
            const type = this.value;
            if (studentId) {
                window.location.href = `/tutoring/views/tutor/evaluations-api.php?student_id=${studentId}&type=${type}`;
            }
        });
        
        // Gérer le bouton de nouvelle évaluation
        document.getElementById('new-evaluation-btn').addEventListener('click', function() {
            if (selectedStudent) {
                showEvaluationModal();
            }
        });
        
        document.getElementById('create-evaluation-btn').addEventListener('click', function() {
            showEvaluationModal();
        });
        
        // Gérer la soumission du formulaire d'évaluation
        document.getElementById('evaluation-form').addEventListener('submit', function(e) {
            e.preventDefault();
            submitEvaluation();
        });
        
        // Vérifier s'il y a un étudiant sélectionné dans l'URL
        const urlParams = new URLSearchParams(window.location.search);
        const studentId = urlParams.get('student_id');
        const type = urlParams.get('type') || 'all';
        
        if (studentId) {
            // Mettre à jour la valeur du select
            document.getElementById('student_id').value = studentId;
            document.getElementById('type').value = type;
            
            // Afficher le filtre de type
            document.getElementById('type-filter-container').style.display = 'block';
            
            // Charger les détails de l'étudiant et ses évaluations
            fetchStudentDetails(studentId);
            fetchStudentEvaluations(studentId, type);
        } else {
            // Afficher la vue d'ensemble
            fetchEvaluationsOverview();
        }
        
        // Gestionnaires d'événements pour l'export
        document.getElementById('export-csv').addEventListener('click', function() {
            exportData('csv');
        });
        
        document.getElementById('export-pdf').addEventListener('click', function() {
            exportData('pdf');
        });
        
        document.getElementById('export-excel').addEventListener('click', function() {
            exportData('excel');
        });
    });
    
    // Fonction pour récupérer les statistiques du tuteur
    function fetchTeacherStats() {
        fetch('/tutoring/api/evaluations/teacher-stats.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur lors de la récupération des statistiques');
                }
                return response.json();
            })
            .then(data => {
                // Mettre à jour les statistiques
                document.getElementById('total-evaluations').textContent = data.total_evaluations;
                document.getElementById('completed-evaluations').textContent = data.completed_evaluations;
                document.getElementById('average-score').textContent = data.average_score;
                document.getElementById('improvement-rate').textContent = data.improvement_rate + '%';
                
                // Mettre à jour les barres de progression
                const completedPercent = data.total_evaluations > 0 ? (data.completed_evaluations / data.total_evaluations) * 100 : 0;
                const averagePercent = (data.average_score / 5) * 100;
                
                document.getElementById('completed-progress').style.width = completedPercent + '%';
                document.getElementById('completed-progress').setAttribute('aria-valuenow', completedPercent);
                
                document.getElementById('average-progress').style.width = averagePercent + '%';
                document.getElementById('average-progress').setAttribute('aria-valuenow', averagePercent);
                
                document.getElementById('improvement-progress').style.width = data.improvement_rate + '%';
                document.getElementById('improvement-progress').setAttribute('aria-valuenow', data.improvement_rate);
                
                // Afficher les évaluations en attente s'il y en a
                if (data.pending_list && data.pending_list.length > 0 && data.pending_list.length <= 5) {
                    const pendingList = document.getElementById('pending-list');
                    pendingList.innerHTML = '';
                    
                    data.pending_list.forEach(pending => {
                        const li = document.createElement('li');
                        li.textContent = `${pending.student_name} - ${evaluationTypes[pending.type] || pending.type}`;
                        pendingList.appendChild(li);
                    });
                    
                    document.getElementById('pending-count').textContent = data.pending_list.length;
                    document.getElementById('pending-evaluations-alert').style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
            });
    }
    
    // Fonction pour récupérer les affectations du tuteur
    function fetchAssignments() {
        const teacherId = document.getElementById('evaluations-app').dataset.teacherId;
        
        fetch(`/tutoring/api/teachers/${teacherId}/assignments.php`)
            .then(response => {
                if (!response.ok) {
                    return []; // Retourner un tableau vide en cas d'erreur
                }
                return response.json();
            })
            .then(data => {
                assignments = data.assignments || [];
                
                // Remplir le select d'étudiants
                const studentSelect = document.getElementById('student_id');
                studentSelect.innerHTML = '<option value="" disabled selected>Choisir un étudiant...</option>';
                
                assignments.forEach(assignment => {
                    const option = document.createElement('option');
                    option.value = assignment.student_id;
                    option.textContent = `${assignment.student_first_name} ${assignment.student_last_name}`;
                    option.dataset.assignmentId = assignment.id;
                    studentSelect.appendChild(option);
                });
                
                // Si aucun étudiant, désactiver le bouton de nouvelle évaluation
                if (assignments.length === 0) {
                    document.getElementById('new-evaluation-btn').disabled = true;
                } else {
                    document.getElementById('new-evaluation-btn').disabled = false;
                }
                
                // Vérifier si un étudiant est déjà sélectionné
                const urlParams = new URLSearchParams(window.location.search);
                const studentId = urlParams.get('student_id');
                
                if (studentId) {
                    studentSelect.value = studentId;
                    
                    // Trouver l'assignment correspondant
                    for (const assignment of assignments) {
                        if (assignment.student_id == studentId) {
                            selectedAssignment = assignment;
                            break;
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
            });
    }
    
    // Fonction pour récupérer les détails d'un étudiant
    function fetchStudentDetails(studentId) {
        fetch(`/tutoring/api/students/${studentId}.php`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur lors de la récupération des détails de l\'étudiant');
                }
                return response.json();
            })
            .then(data => {
                selectedStudent = data.student;
                
                // Mettre à jour la carte d'information de l'étudiant
                document.getElementById('student-name').textContent = `${selectedStudent.first_name} ${selectedStudent.last_name}`;
                document.getElementById('student-name-header').textContent = `${selectedStudent.first_name} ${selectedStudent.last_name}`;
                document.getElementById('student-program').textContent = selectedStudent.program || 'N/A';
                
                // Générer l'avatar avec les initiales
                const initials = selectedStudent.first_name.charAt(0) + selectedStudent.last_name.charAt(0);
                const avatarUrl = `https://ui-avatars.com/api/?name=${encodeURIComponent(initials)}&background=3498db&color=fff`;
                document.getElementById('student-avatar').src = avatarUrl;
                
                // Mettre à jour les informations du stage
                if (selectedStudent.internship) {
                    document.getElementById('internship-title').textContent = selectedStudent.internship.title || 'N/A';
                    document.getElementById('company-name').textContent = selectedStudent.company ? selectedStudent.company.name : 'N/A';
                    
                    if (selectedStudent.internship.start_date && selectedStudent.internship.end_date) {
                        const startDate = new Date(selectedStudent.internship.start_date);
                        const endDate = new Date(selectedStudent.internship.end_date);
                        
                        document.getElementById('internship-dates').textContent = `${startDate.toLocaleDateString()} - ${endDate.toLocaleDateString()}`;
                        
                        // Calculer la progression
                        const today = new Date();
                        let progress = 0;
                        
                        if (today >= startDate && today <= endDate) {
                            const totalDays = Math.round((endDate - startDate) / (1000 * 60 * 60 * 24));
                            const daysElapsed = Math.round((today - startDate) / (1000 * 60 * 60 * 24));
                            progress = Math.min(100, Math.round((daysElapsed / totalDays) * 100));
                        } else if (today > endDate) {
                            progress = 100;
                        }
                        
                        document.getElementById('internship-progress').style.width = progress + '%';
                        document.getElementById('internship-progress').setAttribute('aria-valuenow', progress);
                        document.getElementById('progress-percent').textContent = progress;
                    }
                } else {
                    document.getElementById('internship-title').textContent = 'N/A';
                    document.getElementById('company-name').textContent = 'N/A';
                    document.getElementById('internship-dates').textContent = '';
                }
                
                // Mettre à jour les liens
                document.getElementById('student-documents-link').href = `/tutoring/views/tutor/documents.php?student_id=${studentId}`;
                document.getElementById('student-meetings-link').href = `/tutoring/views/tutor/meetings.php?student_id=${studentId}`;
                
                // Afficher la carte d'informations
                document.getElementById('student-info-card').style.display = 'block';
                document.getElementById('student-evaluations-header').style.display = 'block';
                document.getElementById('no-student-selected').style.display = 'none';
                document.getElementById('evaluations-overview').style.display = 'none';
            })
            .catch(error => {
                console.error('Erreur:', error);
            });
    }
    
    // Fonction pour récupérer les évaluations d'un étudiant
    function fetchStudentEvaluations(studentId, type = 'all') {
        fetch(`/tutoring/api/evaluations/list.php?student_id=${studentId}&type=${type}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur lors de la récupération des évaluations');
                }
                return response.json();
            })
            .then(data => {
                studentEvaluations = data.evaluations || [];
                
                // Afficher ou masquer le message si aucune évaluation
                if (studentEvaluations.length === 0) {
                    document.getElementById('no-evaluations-message').style.display = 'block';
                    document.getElementById('student-stats-card').style.display = 'none';
                } else {
                    document.getElementById('no-evaluations-message').style.display = 'none';
                    document.getElementById('student-stats-card').style.display = 'block';
                    
                    // Afficher les évaluations
                    displayStudentEvaluations();
                    
                    // Mettre à jour les statistiques de l'étudiant
                    updateStudentStats();
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
            });
    }
    
    // Fonction pour afficher les évaluations d'un étudiant
    function displayStudentEvaluations() {
        const evaluationsList = document.getElementById('evaluations-list');
        evaluationsList.innerHTML = '';
        
        studentEvaluations.forEach((evaluation, index) => {
            const card = document.createElement('div');
            card.className = 'card mb-4 fade-in';
            
            // En-tête de la carte
            const header = document.createElement('div');
            header.className = 'd-flex justify-content-between align-items-center card-header';
            header.innerHTML = `
                <span>Évaluation ${evaluationTypes[evaluation.type] || evaluation.type}</span>
                <span class="badge bg-primary">${new Date(evaluation.date).toLocaleDateString()}</span>
            `;
            
            // Corps de la carte
            const body = document.createElement('div');
            body.className = 'card-body';
            
            // Note globale
            let ratingStars = '';
            for (let i = 1; i <= 5; i++) {
                ratingStars += `<i class="bi ${i <= evaluation.score ? 'bi-star-fill' : 'bi-star'} text-warning"></i>`;
            }
            
            const scoreSection = document.createElement('div');
            scoreSection.className = 'mb-4';
            scoreSection.innerHTML = `
                <div class="d-flex align-items-center mb-3">
                    <div class="me-3">
                        <div class="rating-stars">${ratingStars}</div>
                    </div>
                    <div>
                        <h5 class="mb-0">Note globale: ${evaluation.score}/5</h5>
                    </div>
                </div>
                
                <h6>Commentaires</h6>
                <p>${evaluation.comments ? evaluation.comments.replace(/\n/g, '<br>') : ''}</p>
            `;
            
            // Critères d'évaluation et recommandations
            const detailsSection = document.createElement('div');
            detailsSection.className = 'row mb-4';
            
            // Critères
            let criteriaHtml = '<h6>Critères d\'évaluation</h6>';
            if (evaluation.criteria && evaluation.criteria.length > 0) {
                evaluation.criteria.forEach(criterion => {
                    const percent = (criterion.score / 5) * 100;
                    criteriaHtml += `
                        <div class="mb-2">
                            <div class="d-flex justify-content-between">
                                <span>${criterion.name}</span>
                                <span>${criterion.score}/5</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar" role="progressbar" style="width: ${percent}%;" aria-valuenow="${criterion.score}" aria-valuemin="0" aria-valuemax="5"></div>
                            </div>
                        </div>
                    `;
                });
            }
            
            // Points à améliorer et recommandations
            let improvementsHtml = '';
            if (evaluation.areas_for_improvement && evaluation.areas_for_improvement.length > 0) {
                improvementsHtml += '<h6>Points à améliorer</h6>';
                improvementsHtml += '<ul class="list-group list-group-flush mb-3">';
                evaluation.areas_for_improvement.forEach(area => {
                    improvementsHtml += `<li class="list-group-item px-0">${area}</li>`;
                });
                improvementsHtml += '</ul>';
            }
            
            let recommendationsHtml = '';
            if (evaluation.recommendations && evaluation.recommendations.length > 0) {
                recommendationsHtml += '<h6>Recommandations</h6>';
                recommendationsHtml += '<ul class="list-group list-group-flush">';
                evaluation.recommendations.forEach(recommendation => {
                    recommendationsHtml += `<li class="list-group-item px-0">${recommendation}</li>`;
                });
                recommendationsHtml += '</ul>';
            }
            
            detailsSection.innerHTML = `
                <div class="col-md-6">${criteriaHtml}</div>
                <div class="col-md-6">${improvementsHtml}${recommendationsHtml}</div>
            `;
            
            // Actions
            const actionsSection = document.createElement('div');
            actionsSection.className = 'd-flex gap-2';
            actionsSection.innerHTML = `
                <button class="btn btn-outline-primary" onclick="printEvaluation(${index})">
                    <i class="bi bi-printer me-1"></i>Imprimer
                </button>
                <button class="btn btn-outline-secondary" onclick="exportPDF(${index})">
                    <i class="bi bi-file-earmark-pdf me-1"></i>Exporter PDF
                </button>
                <button class="btn btn-outline-info" onclick="shareEvaluation(${index})">
                    <i class="bi bi-share me-1"></i>Partager
                </button>
            `;
            
            // Ajouter les sections au corps de la carte
            body.appendChild(scoreSection);
            body.appendChild(detailsSection);
            body.appendChild(actionsSection);
            
            // Ajouter l'en-tête et le corps à la carte
            card.appendChild(header);
            card.appendChild(body);
            
            // Ajouter la carte à la liste
            evaluationsList.appendChild(card);
        });
    }
    
    // Fonction pour mettre à jour les statistiques de l'étudiant
    function updateStudentStats() {
        // Calculer les statistiques
        const studentStats = {
            technical_score: 0,
            communication_score: 0,
            teamwork_score: 0,
            autonomy_score: 0,
            count: 0
        };
        
        // Données pour le graphique
        const chartData = {
            labels: [],
            technical: [],
            professional: []
        };
        
        // Trier les évaluations par date
        const sortedEvals = [...studentEvaluations].sort((a, b) => new Date(a.date) - new Date(b.date));
        
        sortedEvals.forEach(evaluation => {
            if (!evaluation.criteria || !Array.isArray(evaluation.criteria)) {
                return;
            }
            
            // Ajouter la date au format court pour le graphique
            chartData.labels.push(new Date(evaluation.date).toLocaleDateString('fr-FR', {day: '2-digit', month: '2-digit'}));
            
            // Variables pour les moyennes techniques et professionnelles
            let techScore = 0;
            let techCount = 0;
            let profScore = 0;
            let profCount = 0;
            
            // Parcourir les critères
            evaluation.criteria.forEach(criterion => {
                const name = criterion.name.toLowerCase();
                
                if (name.includes('technique') || 
                    name.includes('technical') ||
                    name.includes('maîtrise') ||
                    name.includes('qualité') ||
                    name.includes('problème') ||
                    name.includes('documentation')) {
                    studentStats.technical_score += criterion.score;
                    techScore += criterion.score;
                    techCount++;
                } else if (name.includes('communication')) {
                    studentStats.communication_score += criterion.score;
                    profScore += criterion.score;
                    profCount++;
                } else if (name.includes('équipe') || name.includes('team')) {
                    studentStats.teamwork_score += criterion.score;
                    profScore += criterion.score;
                    profCount++;
                } else if (name.includes('autonomie') || name.includes('autonomy')) {
                    studentStats.autonomy_score += criterion.score;
                    profScore += criterion.score;
                    profCount++;
                } else {
                    // Par défaut, considérer comme compétence professionnelle
                    profScore += criterion.score;
                    profCount++;
                }
            });
            
            // Ajouter les scores au graphique
            chartData.technical.push(techCount > 0 ? techScore / techCount : 0);
            chartData.professional.push(profCount > 0 ? profScore / profCount : 0);
            
            studentStats.count++;
        });
        
        // Calculer les moyennes
        if (studentStats.count > 0) {
            studentStats.technical_score = (studentStats.technical_score / studentStats.count).toFixed(1);
            studentStats.communication_score = (studentStats.communication_score / studentStats.count).toFixed(1);
            studentStats.teamwork_score = (studentStats.teamwork_score / studentStats.count).toFixed(1);
            studentStats.autonomy_score = (studentStats.autonomy_score / studentStats.count).toFixed(1);
        }
        
        // Mettre à jour l'affichage des statistiques
        document.getElementById('technical-score').textContent = studentStats.technical_score + '/5';
        document.getElementById('communication-score').textContent = studentStats.communication_score + '/5';
        document.getElementById('teamwork-score').textContent = studentStats.teamwork_score + '/5';
        document.getElementById('autonomy-score').textContent = studentStats.autonomy_score + '/5';
        
        // Mettre à jour les barres de progression
        document.getElementById('technical-progress').style.width = (studentStats.technical_score / 5) * 100 + '%';
        document.getElementById('communication-progress').style.width = (studentStats.communication_score / 5) * 100 + '%';
        document.getElementById('teamwork-progress').style.width = (studentStats.teamwork_score / 5) * 100 + '%';
        document.getElementById('autonomy-progress').style.width = (studentStats.autonomy_score / 5) * 100 + '%';
        
        // Créer le graphique si des données sont disponibles
        if (chartData.labels.length > 0) {
            const ctx = document.getElementById('studentProgressChart');
            if (ctx) {
                // Détruire le graphique précédent s'il existe
                if (studentChart) {
                    studentChart.destroy();
                }
                
                studentChart = new Chart(ctx.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: chartData.labels,
                        datasets: [
                            {
                                label: 'Technique',
                                data: chartData.technical,
                                borderColor: '#3498db',
                                backgroundColor: 'rgba(52, 152, 219, 0.1)',
                                tension: 0.3,
                                fill: true
                            },
                            {
                                label: 'Professionnel',
                                data: chartData.professional,
                                borderColor: '#2ecc71',
                                backgroundColor: 'rgba(46, 204, 113, 0.1)',
                                tension: 0.3,
                                fill: true
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'bottom'
                            },
                            tooltip: {
                                mode: 'index',
                                intersect: false
                            }
                        },
                        scales: {
                            y: {
                                min: 0,
                                max: 5,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                });
            }
        }
    }
    
    // Fonction pour récupérer la vue d'ensemble des évaluations
    function fetchEvaluationsOverview() {
        // Masquer les sections spécifiques à un étudiant
        document.getElementById('student-info-card').style.display = 'none';
        document.getElementById('student-stats-card').style.display = 'none';
        document.getElementById('student-evaluations-header').style.display = 'none';
        document.getElementById('no-evaluations-message').style.display = 'none';
        document.getElementById('evaluations-list').innerHTML = '';
        
        // Afficher les sections de vue d'ensemble
        document.getElementById('no-student-selected').style.display = 'block';
        document.getElementById('evaluations-overview').style.display = 'block';
        
        // Récupérer les données pour le tableau
        const tbody = document.getElementById('evaluations-table-body');
        tbody.innerHTML = '<tr><td colspan="7" class="text-center"><div class="spinner-border spinner-border-sm text-primary" role="status"></div><span class="ms-2">Chargement des données...</span></td></tr>';
        
        // Si les assignments sont déjà chargés, on peut construire le tableau
        if (assignments.length > 0) {
            buildOverviewTable();
        } else {
            // Sinon, on attend que les assignments soient chargés
            // La fonction fetchAssignments a déjà été appelée au chargement
        }
    }
    
    // Fonction pour construire le tableau de vue d'ensemble
    function buildOverviewTable() {
        const tbody = document.getElementById('evaluations-table-body');
        tbody.innerHTML = '';
        
        // Si aucune affectation, afficher un message
        if (assignments.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center">Aucun étudiant assigné</td></tr>';
            return;
        }
        
        // Pour chaque affectation, ajouter une ligne au tableau
        assignments.forEach(assignment => {
            const row = document.createElement('tr');
            
            // Récupérer les évaluations pour cet étudiant
            fetch(`/tutoring/api/evaluations/list.php?student_id=${assignment.student_id}`)
                .then(response => response.json())
                .then(data => {
                    const evaluations = data.evaluations || [];
                    
                    // Identifier les évaluations mi-parcours et finale
                    let midTermEval = null;
                    let finalEval = null;
                    let average = null;
                    
                    evaluations.forEach(eval => {
                        if (eval.type === 'mid_term') {
                            midTermEval = eval;
                        } else if (eval.type === 'final') {
                            finalEval = eval;
                        }
                    });
                    
                    // Calculer la moyenne s'il y a des évaluations
                    if (evaluations.length > 0) {
                        const sum = evaluations.reduce((acc, eval) => acc + eval.score, 0);
                        average = (sum / evaluations.length).toFixed(1);
                    }
                    
                    // Construire la ligne du tableau
                    row.innerHTML = `
                        <td>
                            <a href="/tutoring/views/tutor/evaluations-api.php?student_id=${assignment.student_id}">
                                ${assignment.student_first_name} ${assignment.student_last_name}
                            </a>
                        </td>
                        <td>${assignment.student_program || 'N/A'}</td>
                        <td>${assignment.company_name || '<span class="text-muted">N/A</span>'}</td>
                        <td>
                            ${midTermEval ? 
                                `<span class="badge bg-success">${midTermEval.score}/5</span>` : 
                                '<span class="badge bg-warning">En attente</span>'}
                        </td>
                        <td>
                            ${finalEval ? 
                                `<span class="badge bg-success">${finalEval.score}/5</span>` : 
                                '<span class="badge bg-warning">En attente</span>'}
                        </td>
                        <td>
                            ${average ? 
                                `<strong>${average}/5</strong>` : 
                                '<span class="text-muted">-</span>'}
                        </td>
                        <td>
                            <a href="/tutoring/views/tutor/evaluations-api.php?student_id=${assignment.student_id}" class="btn btn-sm btn-outline-primary" title="Voir le détail">
                                <i class="bi bi-eye"></i> Détails
                            </a>
                        </td>
                    `;
                    
                    tbody.appendChild(row);
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    row.innerHTML = `
                        <td>
                            <a href="/tutoring/views/tutor/evaluations-api.php?student_id=${assignment.student_id}">
                                ${assignment.student_first_name} ${assignment.student_last_name}
                            </a>
                        </td>
                        <td>${assignment.student_program || 'N/A'}</td>
                        <td>${assignment.company_name || '<span class="text-muted">N/A</span>'}</td>
                        <td colspan="4" class="text-center text-danger">Erreur lors du chargement des données</td>
                    `;
                    tbody.appendChild(row);
                });
        });
    }
    
    // Fonction pour afficher le modal d'évaluation
    function showEvaluationModal() {
        // Réinitialiser le formulaire
        document.getElementById('evaluation-form').reset();
        
        // Mettre à jour les champs cachés
        document.getElementById('assignment_id').value = selectedAssignment.id;
        document.getElementById('selected_student_id').value = selectedStudent.id;
        
        // Afficher le modal
        const modal = new bootstrap.Modal(document.getElementById('createEvaluationModal'));
        modal.show();
    }
    
    // Fonction pour soumettre une évaluation
    function submitEvaluation() {
        // Récupérer les données du formulaire
        const form = document.getElementById('evaluation-form');
        const formData = new FormData(form);
        
        // Convertir FormData en objet
        const data = {};
        data.assignment_id = formData.get('assignment_id');
        data.type = formData.get('evaluation_type');
        data.comments = formData.get('comments');
        
        // Récupérer les critères
        data.criteria = {};
        for (const [key, value] of formData.entries()) {
            if (key.startsWith('criteria[')) {
                const criterionKey = key.substring(9, key.length - 1);
                data.criteria[criterionKey] = value;
            }
        }
        
        // Récupérer les points à améliorer et les recommandations
        data.areas_for_improvement = [];
        data.recommendations = [];
        
        for (const [key, value] of formData.entries()) {
            if (key === 'areas_for_improvement[]' && value.trim()) {
                data.areas_for_improvement.push(value.trim());
            }
            if (key === 'recommendations[]' && value.trim()) {
                data.recommendations.push(value.trim());
            }
        }
        
        // Envoyer les données à l'API
        fetch('/tutoring/api/evaluations/create.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur lors de l\'envoi de l\'évaluation');
            }
            return response.json();
        })
        .then(responseData => {
            if (responseData.success) {
                // Fermer le modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('createEvaluationModal'));
                modal.hide();
                
                // Afficher un message de succès
                alert('Évaluation enregistrée avec succès');
                
                // Recharger les évaluations
                const studentId = document.getElementById('student_id').value;
                const type = document.getElementById('type').value;
                fetchStudentEvaluations(studentId, type);
                
                // Mettre à jour les statistiques
                fetchTeacherStats();
            } else {
                alert('Erreur: ' + (responseData.message || 'Une erreur est survenue'));
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors de l\'envoi de l\'évaluation');
        });
    }
    
    // Fonctions pour ajouter des champs dynamiques
    function addImprovementArea() {
        const container = document.getElementById('improvement-areas-container');
        const newField = document.createElement('div');
        newField.className = 'input-group mb-2';
        newField.innerHTML = `
            <input type="text" class="form-control" name="areas_for_improvement[]" placeholder="Point à améliorer...">
            <button class="btn btn-outline-danger" type="button" onclick="this.parentElement.remove()"><i class="bi bi-trash"></i></button>
        `;
        container.appendChild(newField);
    }
    
    function addRecommendation() {
        const container = document.getElementById('recommendations-container');
        const newField = document.createElement('div');
        newField.className = 'input-group mb-2';
        newField.innerHTML = `
            <input type="text" class="form-control" name="recommendations[]" placeholder="Recommandation...">
            <button class="btn btn-outline-danger" type="button" onclick="this.parentElement.remove()"><i class="bi bi-trash"></i></button>
        `;
        container.appendChild(newField);
    }
    
    // Fonctions pour les actions d'évaluation
    function printEvaluation(index) {
        // Implémenter la fonction d'impression
        window.print();
    }
    
    function exportPDF(index) {
        // Rediriger vers un script PHP qui génère le PDF
        const evaluation = studentEvaluations[index];
        if (evaluation && evaluation.id) {
            window.location.href = `/tutoring/views/tutor/export_evaluation.php?id=${evaluation.id}&format=pdf`;
        } else {
            alert('Fonctionnalité d\'export PDF à implémenter');
        }
    }
    
    function exportData(format) {
        // Rediriger vers un script PHP qui génère l'export
        window.location.href = `/tutoring/views/tutor/export_evaluations.php?format=${format}`;
    }
    
    function shareEvaluation(index) {
        // Implémenter la fonction de partage
        const evaluation = studentEvaluations[index];
        
        if (navigator.share) {
            navigator.share({
                title: 'Évaluation de stage',
                text: `Évaluation ${evaluation.type === 'mid_term' ? 'mi-parcours' : 'finale'} - Note: ${evaluation.score}/5`,
                url: window.location.href
            }).then(() => {
                console.log('Partage réussi');
            }).catch((error) => {
                console.error('Erreur lors du partage:', error);
            });
        } else {
            // Fallback pour les navigateurs qui ne supportent pas l'API de partage
            const shareUrl = window.location.href;
            if (navigator.clipboard) {
                navigator.clipboard.writeText(shareUrl).then(() => {
                    alert('Le lien a été copié dans le presse-papiers');
                });
            } else {
                alert('La fonction de partage n\'est pas supportée par votre navigateur');
            }
        }
    }
</script>

<?php
// Inclure le pied de page
include_once __DIR__ . '/../common/footer.php';
?>