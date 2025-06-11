<?php
/**
 * Vue pour la gestion des évaluations par l'étudiant - Version API
 */

// Titre de la page
$pageTitle = 'Mes évaluations';
$currentPage = 'evaluations';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../includes/init.php';

// Vérifier que l'utilisateur est étudiant
requireRole('student');

// Récupérer l'ID de l'étudiant
$student_id = $_SESSION['user']['id'] ?? null;
$internship_id = $_SESSION['user']['internship_id'] ?? null;

// Inclure l'en-tête
include_once __DIR__ . '/../common/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2>Mes évaluations</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/tutoring/views/student/dashboard.php">Tableau de bord</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Évaluations</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="row mb-4" id="stats-container">
        <div class="col-md-3 fade-in delay-1">
            <div class="card stat-card">
                <div class="value" id="average-score">...</div>
                <div class="label">Moyenne générale</div>
                <div class="progress mt-2">
                    <div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" id="average-progress"></div>
                </div>
                <small class="text-muted">Sur 5.0</small>
            </div>
        </div>
        <div class="col-md-3 fade-in delay-2">
            <div class="card stat-card">
                <div class="value" id="evaluations-count">...</div>
                <div class="label">Évaluations</div>
                <div class="progress mt-2">
                    <div class="progress-bar bg-success" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" id="evaluations-progress"></div>
                </div>
                <small class="text-muted" id="evaluations-text">Chargement...</small>
            </div>
        </div>
        <div class="col-md-3 fade-in delay-3">
            <div class="card stat-card">
                <div class="value" id="technical-score">...</div>
                <div class="label">Technique</div>
                <div class="progress mt-2">
                    <div class="progress-bar bg-info" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" id="technical-progress"></div>
                </div>
                <small class="text-muted">Compétences</small>
            </div>
        </div>
        <div class="col-md-3 fade-in delay-4">
            <div class="card stat-card">
                <div class="value" id="professional-score">...</div>
                <div class="label">Professionnel</div>
                <div class="progress mt-2">
                    <div class="progress-bar bg-warning" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" id="professional-progress"></div>
                </div>
                <small class="text-muted">Comportement</small>
            </div>
        </div>
    </div>
    
    <!-- Main Content Row -->
    <div class="row">
        <!-- Left Column -->
        <div class="col-lg-8">
            <!-- Evaluations Interface avec Stimulus -->
            <div class="card mb-4 fade-in" 
                 data-controller="student-evaluations" 
                 <?php if ($internship_id): ?>data-student-evaluations-internship-id-value="<?= $internship_id ?>"<?php endif; ?>
                 data-student-evaluations-api-url-value="/tutoring/api/evaluations">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Mes évaluations</span>
                </div>
                <div class="card-body">
                    <!-- Loading Indicator -->
                    <div data-student-evaluations-target="loadingIndicator" class="text-center py-3">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                        <p class="mt-2 text-muted">Chargement des évaluations...</p>
                    </div>
                    
                    <!-- Empty State -->
                    <div data-student-evaluations-target="emptyState" class="hidden py-3 text-center">
                        <div class="p-4 bg-light rounded">
                            <i class="bi bi-journal-x fs-1 text-muted"></i>
                            <h5 class="mt-3">Aucune évaluation disponible</h5>
                            <p class="text-muted">Vous n'avez pas encore d'évaluations. Attendez que votre tuteur complète votre première évaluation.</p>
                        </div>
                    </div>
                    
                    <!-- Evaluations List -->
                    <div data-student-evaluations-target="evaluationsList"></div>
                    
                    <!-- Evaluation Detail Modal -->
                    <div data-student-evaluations-target="modal" class="modal fade" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" data-student-evaluations-target="modalTitle">Détails de l'évaluation</h5>
                                    <button type="button" class="btn-close" data-action="student-evaluations#closeModal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body" data-student-evaluations-target="modalContent">
                                    <!-- Evaluation details will be loaded dynamically -->
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Error message for form submission -->
                    <div data-student-evaluations-target="submitButton" class="text-danger mt-3 hidden"></div>
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
                    <button class="btn btn-primary w-100 mb-2" 
                            data-controller="student-evaluations" 
                            data-action="student-evaluations#showEvaluationForm"
                            data-evaluation-id="self">
                        <i class="bi bi-pencil me-2"></i>Faire mon auto-évaluation
                    </button>
                    <a href="/tutoring/views/student/documents.php" class="btn btn-outline-primary w-100 mb-2">
                        <i class="bi bi-folder me-2"></i>Mes documents
                    </a>
                    <a href="/tutoring/views/student/meetings.php" class="btn btn-outline-primary w-100 mb-2">
                        <i class="bi bi-calendar-event me-2"></i>Planifier une réunion
                    </a>
                    <a href="/tutoring/views/student/tutor.php" class="btn btn-outline-primary w-100">
                        <i class="bi bi-person-badge me-2"></i>Contacter mon tuteur
                    </a>
                </div>
            </div>
            
            <!-- Objectifs à venir -->
            <div class="card mb-4 fade-in">
                <div class="card-header">
                    Objectifs à venir
                </div>
                <div class="card-body p-0" id="objectives-container">
                    <div class="list-group list-group-flush" id="objectives-list">
                        <!-- Objectives will be loaded by JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialisation des statistiques et des objectifs
    fetchEvaluationStats();
    loadObjectives();
    
    // Fonction pour récupérer les statistiques d'évaluations
    function fetchEvaluationStats() {
        fetch('/tutoring/api/evaluations/student-evaluations.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur lors de la récupération des statistiques');
                }
                return response.json();
            })
            .then(data => {
                updateStatCards(data.stats || {});
            })
            .catch(error => {
                console.error('Erreur:', error);
            });
    }
    
    // Fonction pour charger les objectifs
    function loadObjectives() {
        fetch('/tutoring/api/evaluations/objectives.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur lors de la récupération des objectifs');
                }
                return response.json();
            })
            .then(data => {
                renderObjectives(data.objectives || []);
            })
            .catch(error => {
                console.error('Erreur:', error);
                document.getElementById('objectives-list').innerHTML = `
                    <div class="list-group-item p-3">
                        <p class="mb-0 text-danger">Erreur lors du chargement des objectifs</p>
                    </div>
                `;
            });
    }
    
    // Fonction pour afficher les objectifs
    function renderObjectives(objectives) {
        const container = document.getElementById('objectives-list');
        
        if (!objectives || objectives.length === 0) {
            container.innerHTML = `
                <div class="list-group-item p-3">
                    <p class="mb-0 text-muted">Aucun objectif défini</p>
                </div>
            `;
            return;
        }
        
        let html = '';
        objectives.forEach((objective, index) => {
            html += `
                <div class="list-group-item p-3">
                    <div class="d-flex">
                        <div class="flex-shrink-0 bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 28px; height: 28px;">
                            <small class="fw-bold">${index + 1}</small>
                        </div>
                        <div>
                            <h6 class="mb-1">${objective.title}</h6>
                            <p class="mb-0 small text-muted">${objective.description || ''}</p>
                        </div>
                    </div>
                </div>
            `;
        });
        
        container.innerHTML = html;
    }
    
    // Mise à jour des statistiques dans les cartes
    function updateStatCards(stats) {
        // Mise à jour des cartes de statistiques principales
        document.getElementById('average-score').textContent = stats.average || '0.0';
        const averageProgress = (stats.average / 5) * 100 || 0;
        document.getElementById('average-progress').style.width = `${averageProgress}%`;
        document.getElementById('average-progress').setAttribute('aria-valuenow', averageProgress);
        
        document.getElementById('evaluations-count').textContent = stats.completed || 0;
        document.getElementById('evaluations-text').textContent = `${stats.completed || 0}/${stats.total_expected || 0} complétées`;
        const evaluationsProgress = stats.total_expected > 0 ? (stats.completed / stats.total_expected) * 100 : 0;
        document.getElementById('evaluations-progress').style.width = `${evaluationsProgress}%`;
        document.getElementById('evaluations-progress').setAttribute('aria-valuenow', evaluationsProgress);
        
        document.getElementById('technical-score').textContent = stats.technical || '0.0';
        const technicalProgress = (stats.technical / 5) * 100 || 0;
        document.getElementById('technical-progress').style.width = `${technicalProgress}%`;
        document.getElementById('technical-progress').setAttribute('aria-valuenow', technicalProgress);
        
        document.getElementById('professional-score').textContent = stats.professional || '0.0';
        const professionalProgress = (stats.professional / 5) * 100 || 0;
        document.getElementById('professional-progress').style.width = `${professionalProgress}%`;
        document.getElementById('professional-progress').setAttribute('aria-valuenow', professionalProgress);
    }
});
</script>

<?php
// Inclure le pied de page
include_once __DIR__ . '/../common/footer.php';
?>