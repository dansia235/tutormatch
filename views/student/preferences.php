<?php
/**
 * Vue pour la gestion des préférences de stage par l'étudiant
 */

// Initialiser les variables
$pageTitle = 'Mes préférences de stage';
$currentPage = 'preferences';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../includes/init.php';

// Vérifier que l'utilisateur est connecté et a le rôle étudiant
requireRole('student');

// Récupérer l'ID de l'étudiant
$student_id = $_SESSION['user']['id'] ?? null;

// Inclure l'en-tête
include_once __DIR__ . '/../common/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-sliders me-2"></i>Mes préférences de stage</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/tutoring/views/student/dashboard.php">Tableau de bord</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Préférences</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <!-- Main content -->
    <div class="row">
        <!-- Left Column with Preferences Interface -->
        <div class="col-lg-8">
            <!-- Preferences Interface avec Stimulus -->
            <div class="card border-0 shadow-sm mb-4 fade-in" 
                 data-controller="student-preferences" 
                 <?php if ($student_id): ?>data-student-preferences-student-id-value="<?= $student_id ?>"<?php endif; ?>
                 data-student-preferences-max-preferences-value="5"
                 data-student-preferences-api-url-value="/tutoring/api/students">
                
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Stages préférés</h5>
                    <span class="badge bg-light text-primary" data-student-preferences-target="saveButton">
                        <i class="bi bi-info-circle me-1"></i>Faites glisser pour réordonner
                    </span>
                </div>
                
                <div class="card-body">
                    <!-- Loading Indicator -->
                    <div data-student-preferences-target="loadingIndicator" class="text-center py-3">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                        <p class="mt-2 text-muted">Chargement de vos préférences...</p>
                    </div>
                    
                    <!-- Empty State -->
                    <div data-student-preferences-target="emptyState" class="hidden py-3 text-center">
                        <div class="p-4 bg-light rounded">
                            <i class="bi bi-list-stars fs-1 text-muted"></i>
                            <h5 class="mt-3">Aucune préférence de stage</h5>
                            <p class="text-muted">Vous n'avez pas encore sélectionné de stages préférés. Utilisez la recherche ci-dessous pour ajouter des stages à vos préférences.</p>
                        </div>
                    </div>
                    
                    <!-- Selected Preferences List -->
                    <div data-student-preferences-target="selectedPreferences" class="mb-4"></div>
                    
                    <!-- Maximum Preferences Alert -->
                    <div data-student-preferences-target="maxPreferencesAlert" class="alert alert-warning hidden mb-4">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Vous avez atteint le nombre maximum de préférences (5). Veuillez supprimer une préférence avant d'en ajouter une nouvelle.
                    </div>
                    
                    <!-- Internship Search -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Rechercher un stage</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="Rechercher par titre, entreprise ou domaine..." 
                                           data-student-preferences-target="internshipSearch" 
                                           data-action="input->student-preferences#search">
                                    <button class="btn btn-outline-secondary" type="button">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Search Results -->
                            <div data-student-preferences-target="searchResults" class="border rounded overflow-hidden"></div>
                        </div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="button" class="btn btn-primary" 
                                data-student-preferences-target="saveButton" 
                                data-action="student-preferences#savePreferences" 
                                disabled>
                            <i class="bi bi-save me-2"></i>Enregistrer mes préférences
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Column -->
        <div class="col-lg-4">
            <!-- Statistiques -->
            <div class="card mb-4 fade-in">
                <div class="card-header">
                    Statistiques
                </div>
                <div class="card-body" id="stats-container">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Préférences définies</span>
                            <strong id="preferences-count">0</strong>
                        </div>
                        <div class="progress mt-1">
                            <div class="progress-bar bg-success" role="progressbar" style="width: 0%;" id="preferences-progress"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Stages disponibles</span>
                            <strong id="internships-count">0</strong>
                        </div>
                        <div class="progress mt-1">
                            <div class="progress-bar bg-info" role="progressbar" style="width: 0%;" id="internships-progress"></div>
                        </div>
                    </div>
                    <div>
                        <div class="d-flex justify-content-between">
                            <span>Profil complété</span>
                            <strong id="profile-completion">0%</strong>
                        </div>
                        <div class="progress mt-1">
                            <div class="progress-bar bg-warning" role="progressbar" style="width: 0%;" id="profile-progress"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Actions rapides -->
            <div class="card mb-4 fade-in">
                <div class="card-header">
                    Actions rapides
                </div>
                <div class="card-body">
                    <a href="/tutoring/views/student/internship.php" class="btn btn-outline-primary w-100 mb-2">
                        <i class="bi bi-briefcase me-2"></i>Voir les stages
                    </a>
                    <a href="/tutoring/views/student/documents.php" class="btn btn-outline-primary w-100 mb-2">
                        <i class="bi bi-folder me-2"></i>Mes documents
                    </a>
                    <a href="/tutoring/views/student/meetings.php" class="btn btn-outline-primary w-100 mb-2">
                        <i class="bi bi-calendar-event me-2"></i>Planifier une réunion
                    </a>
                    <a href="/tutoring/views/student/evaluations.php" class="btn btn-outline-primary w-100">
                        <i class="bi bi-star me-2"></i>Mes évaluations
                    </a>
                </div>
            </div>
            
            <!-- Aide -->
            <div class="card fade-in">
                <div class="card-header">
                    <i class="bi bi-question-circle me-1"></i> Aide
                </div>
                <div class="card-body">
                    <h6>Comment définir mes préférences ?</h6>
                    <ul class="small">
                        <li>Recherchez des stages qui vous intéressent</li>
                        <li>Ajoutez-les à vos préférences</li>
                        <li>Réorganisez-les par ordre de priorité</li>
                        <li>Enregistrez vos préférences</li>
                    </ul>
                    <hr>
                    <h6>Besoin d'aide ?</h6>
                    <p class="small">Contactez votre coordinateur ou votre tuteur pour obtenir de l'aide.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Enable more detailed console logging
    console.log("Initializing student preferences page");
    
    // Fetch statistics 
    fetchPreferenceStats();
    
    // Function to fetch preference statistics
    function fetchPreferenceStats() {
        console.log("Fetching preference statistics");
        fetch('/tutoring/api/students/stats.php')
            .then(response => {
                console.log("Stats response status:", response.status);
                if (!response.ok) {
                    throw new Error('Erreur lors de la récupération des statistiques');
                }
                return response.json();
            })
            .then(data => {
                console.log("Stats data:", data);
                updateStats(data.stats || {});
            })
            .catch(error => {
                console.error('Erreur:', error);
            });
    }
    
    // Update statistics display
    function updateStats(stats) {
        // Update preference count
        const preferencesCount = stats.preferences_count || 0;
        document.getElementById('preferences-count').textContent = preferencesCount + '/5';
        document.getElementById('preferences-progress').style.width = (preferencesCount / 5 * 100) + '%';
        
        // Update internships count
        const internshipsCount = stats.available_internships || 0;
        document.getElementById('internships-count').textContent = internshipsCount;
        document.getElementById('internships-progress').style.width = internshipsCount > 0 ? '100%' : '0%';
        
        // Update profile completion
        const profileCompletion = stats.profile_completion || 0;
        document.getElementById('profile-completion').textContent = profileCompletion + '%';
        document.getElementById('profile-progress').style.width = profileCompletion + '%';
    }
    
    // Amélioration de l'interface de recherche
    const searchInput = document.querySelector('[data-student-preferences-target="internshipSearch"]');
    if (searchInput) {
        console.log("Setting up search input");
        searchInput.setAttribute('placeholder', 'Saisissez une lettre pour rechercher un stage...');
        
        // Log input events to debug search issues
        searchInput.addEventListener('input', function(e) {
            console.log("Search input value:", e.target.value);
        });
    }
});

// Load debug script for development
document.write('<script src="/tutoring/assets/js/debug-student-preferences.js"><\/script>');
</script>

<?php
// Inclure le pied de page
include_once __DIR__ . '/../common/footer.php';
?>