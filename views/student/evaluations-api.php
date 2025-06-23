<?php
/**
 * Vue pour la gestion des évaluations par l'étudiant - Version API
 * Cette version utilise les nouvelles API pour afficher les évaluations
 */

// Titre de la page
$pageTitle = 'Mes évaluations';
$currentPage = 'evaluations';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../includes/init.php';

// Vérifier que l'utilisateur est étudiant
requireRole('student');

// Récupérer l'ID de l'utilisateur
$user_id = $_SESSION['user_id'] ?? null;

// Initialiser les modèles nécessaires
try {
    // Utiliser la connexion à la base de données globale
    global $db;
    
    // S'assurer que la connexion existe
    if (!isset($db) || !($db instanceof PDO)) {
        error_log("Connexion à la base de données non disponible dans evaluations-api.php");
        throw new Exception("Connexion à la base de données non disponible");
    }
    
    // Initialiser le modèle étudiant
    $studentModel = new Student($db);
    $student = $studentModel->getByUserId($user_id);
    
    if (!$student) {
        throw new Exception("Profil étudiant non trouvé");
    }
    
    $student_id = $student['id'];
    
    // Récupérer l'affectation active
    $assignment = $studentModel->getAssignment($student_id);
    $assignment_id = $assignment['id'] ?? null;
    $internship_id = $assignment['internship_id'] ?? null;
    
} catch (Exception $e) {
    error_log("Erreur dans la page d'évaluations API: " . $e->getMessage());
    $student_id = null;
    $assignment_id = null;
    $internship_id = null;
}

// Inclure l'en-tête
include_once __DIR__ . '/../common/header.php';
?>

<div class="container-fluid px-0">
    <div class="row g-0 mx-0">
        <div class="col-12 px-4 py-3">
            <h2><i class="bi bi-clipboard-check me-2"></i>Mes évaluations</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/tutoring/views/student/dashboard.php">Tableau de bord</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Évaluations</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <div id="evaluations-app">
        <!-- Chargement -->
        <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Chargement...</span>
            </div>
            <p class="mt-3">Chargement des évaluations...</p>
        </div>

        <!-- Erreur -->
        <div v-if="error" class="alert alert-danger mx-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            {{ errorMessage }}
        </div>

        <!-- Contenu principal -->
        <div v-if="!loading && !error">
            <!-- Stats Cards -->
            <div class="row g-0 mx-0 px-4 mb-4">
                <div class="col-md-3 pe-3">
                    <div class="card stat-card">
                        <div class="value">{{ stats.average_score }}</div>
                        <div class="label">Moyenne générale</div>
                        <div class="progress mt-2">
                            <div class="progress-bar" role="progressbar" 
                                 :style="{ width: (stats.average_score / 5) * 100 + '%' }" 
                                 :aria-valuenow="(stats.average_score / 5) * 100" 
                                 aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <small class="text-muted">Sur 5.0</small>
                    </div>
                </div>
                <div class="col-md-3 pe-3">
                    <div class="card stat-card">
                        <div class="value">{{ stats.total }}</div>
                        <div class="label">Évaluations</div>
                        <div class="progress mt-2">
                            <div class="progress-bar bg-success" role="progressbar" 
                                 :style="{ width: (stats.total / 5) * 100 + '%' }" 
                                 :aria-valuenow="(stats.total / 5) * 100" 
                                 aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <small class="text-muted">{{ stats.total }}/5 complétées</small>
                    </div>
                </div>
                <div class="col-md-3 pe-3">
                    <div class="card stat-card">
                        <div class="value">{{ stats.technical_avg }}</div>
                        <div class="label">Technique</div>
                        <div class="progress mt-2">
                            <div class="progress-bar bg-info" role="progressbar" 
                                 :style="{ width: (stats.technical_avg / 5) * 100 + '%' }" 
                                 :aria-valuenow="(stats.technical_avg / 5) * 100" 
                                 aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <small class="text-muted">Compétences</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="value">{{ stats.professional_avg }}</div>
                        <div class="label">Professionnel</div>
                        <div class="progress mt-2">
                            <div class="progress-bar bg-warning" role="progressbar" 
                                 :style="{ width: (stats.professional_avg / 5) * 100 + '%' }" 
                                 :aria-valuenow="(stats.professional_avg / 5) * 100" 
                                 aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <small class="text-muted">Comportement</small>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="row g-0 mx-0">
                <!-- Left Column -->
                <div class="col-lg-8 px-4">
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span>Mes évaluations</span>
                            <a href="evaluations.php" class="btn btn-sm btn-outline-secondary">Version classique</a>
                        </div>
                        <div class="card-body">
                            <!-- Aucune évaluation -->
                            <div v-if="evaluations.length === 0" class="text-center py-5">
                                <i class="bi bi-clipboard-x display-1 text-muted mb-3"></i>
                                <h4>Aucune évaluation disponible</h4>
                                <p class="text-muted">Vous n'avez pas encore d'évaluations. Attendez que votre tuteur complète votre première évaluation ou faites votre auto-évaluation.</p>
                            </div>
                            
                            <!-- Liste des évaluations -->
                            <div v-for="(evaluation, index) in evaluations" :key="evaluation.id" class="card mb-4">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <span>{{ evaluation.type_name }}</span>
                                    <span class="badge bg-primary">{{ formatDate(evaluation.submission_date) }}</span>
                                </div>
                                <div class="card-body">
                                    <div class="mb-4">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="me-3">
                                                <div class="rating-stars">
                                                    <i v-for="i in 5" :key="i" 
                                                       :class="i <= Math.round(evaluation.score) ? 'bi bi-star-fill' : 'bi bi-star'" 
                                                       class="text-warning"></i>
                                                </div>
                                            </div>
                                            <div>
                                                <h5 class="mb-0">Note globale: {{ evaluation.score }}/5</h5>
                                                <small class="text-muted">Évaluateur: {{ evaluation.evaluator ? evaluation.evaluator.name : 'Non spécifié' }}</small>
                                            </div>
                                        </div>
                                        
                                        <h6>Commentaires</h6>
                                        <p v-html="formatText(evaluation.comments)"></p>
                                    </div>
                                    
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <h6>Critères d'évaluation</h6>
                                            <div v-if="evaluation.criteria_scores && Object.keys(evaluation.criteria_scores).length > 0">
                                                <div v-for="(criterion, key) in evaluation.criteria_scores" :key="key" class="mb-2">
                                                    <div class="d-flex justify-content-between">
                                                        <span>{{ getCriterionName(key) }}</span>
                                                        <span>{{ criterion.score }}/5</span>
                                                    </div>
                                                    <div class="progress" style="height: 6px;">
                                                        <div class="progress-bar" role="progressbar" 
                                                             :style="{ width: (criterion.score/5)*100 + '%' }" 
                                                             :aria-valuenow="criterion.score" 
                                                             aria-valuemin="0" aria-valuemax="5"></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <p v-else class="text-muted">Aucun critère détaillé disponible</p>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div v-if="evaluation.areas_for_improvement">
                                                <h6>Points à améliorer</h6>
                                                <ul class="list-group list-group-flush mb-3">
                                                    <li v-for="(area, areaIndex) in formatList(evaluation.areas_for_improvement)" 
                                                        :key="'area-' + areaIndex"
                                                        class="list-group-item px-0">{{ area }}</li>
                                                </ul>
                                            </div>
                                            
                                            <div v-if="evaluation.next_steps">
                                                <h6>Prochaines étapes</h6>
                                                <ul class="list-group list-group-flush">
                                                    <li v-for="(step, stepIndex) in formatList(evaluation.next_steps)" 
                                                        :key="'step-' + stepIndex"
                                                        class="list-group-item px-0">{{ step }}</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-outline-primary" @click="printEvaluation(index)">
                                            <i class="bi bi-printer me-1"></i>Imprimer
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Right Column -->
                <div class="col-lg-4 px-4">
                    <!-- Quick Actions -->
                    <div class="card mb-4">
                        <div class="card-header">
                            Actions rapides
                        </div>
                        <div class="card-body">
                            <button class="btn btn-primary w-100 mb-2" @click="showSelfEvaluationModal" 
                                    :disabled="!canCreateSelfEvaluation">
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
                    <div class="card mb-4">
                        <div class="card-header">
                            Objectifs à venir
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                <div v-if="objectives.length === 0" class="list-group-item p-3">
                                    <p class="mb-0 text-muted">Aucun objectif défini</p>
                                </div>
                                <div v-else v-for="(objective, index) in objectives" :key="objective.id" class="list-group-item p-3">
                                    <div class="d-flex">
                                        <div class="flex-shrink-0 bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 28px; height: 28px;">
                                            <small class="fw-bold">{{ index + 1 }}</small>
                                        </div>
                                        <div>
                                            <h6 class="mb-1">{{ objective.title }}</h6>
                                            <p class="mb-0 small text-muted">{{ objective.description }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Progression du stage -->
                    <div v-if="internshipId" class="card mb-4">
                        <div class="card-header">
                            Progression du stage
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="studentProgressChart"></canvas>
                            </div>
                            <div class="mt-3">
                                <h6>Statistiques globales</h6>
                                <div class="progress mb-2" style="height: 10px;">
                                    <div class="progress-bar" role="progressbar" 
                                         :style="{ width: (stats.average_score / 5) * 100 + '%' }" 
                                         :aria-valuenow="stats.average_score" 
                                         aria-valuemin="0" aria-valuemax="5"></div>
                                </div>
                                <p class="small text-muted mb-0">Progression moyenne: {{ stats.average_score }}/5</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Modal d'auto-évaluation -->
        <div class="modal fade" id="selfEvaluationModal" tabindex="-1" aria-labelledby="selfEvaluationModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="selfEvaluationModalLabel">Auto-évaluation</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form @submit.prevent="submitSelfEvaluation">
                        <div class="modal-body">
                            <div v-if="criteriaLoading" class="text-center py-3">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Chargement...</span>
                                </div>
                                <p>Chargement des critères d'évaluation...</p>
                            </div>
                            
                            <div v-if="criteriaError" class="alert alert-danger" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                {{ criteriaErrorMessage }}
                            </div>
                            
                            <div v-if="!criteriaLoading && !criteriaError">
                                <div class="mb-4" v-for="(category, categoryKey) in formStructure" :key="categoryKey">
                                    <h5>{{ category.name }}</h5>
                                    <p class="text-muted">{{ category.description }}</p>
                                    <div class="row">
                                        <div v-for="(criterion, criterionKey) in category.criteria" :key="criterionKey" class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">{{ criterion.name }}</label>
                                                <select class="form-select" v-model="selfEvaluation.criteria[criterionKey]" required>
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
                                    <h5>Commentaires et réflexions</h5>
                                    <div class="mb-3">
                                        <label for="comments" class="form-label">Commentaires généraux</label>
                                        <textarea class="form-control" id="comments" v-model="selfEvaluation.comments" rows="4" placeholder="Points forts, difficultés rencontrées, observations personnelles..." required></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Points à améliorer</label>
                                        <div id="improvement-areas-container">
                                            <div v-for="(area, index) in selfEvaluation.areas" :key="index" class="input-group mb-2">
                                                <input type="text" class="form-control" v-model="selfEvaluation.areas[index]" placeholder="Point à améliorer...">
                                                <button class="btn btn-outline-secondary" type="button" v-if="index === selfEvaluation.areas.length - 1" @click="addImprovementArea">
                                                    <i class="bi bi-plus"></i>
                                                </button>
                                                <button class="btn btn-outline-danger" type="button" v-else @click="removeImprovementArea(index)">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Prochaines étapes</label>
                                        <div id="next-steps-container">
                                            <div v-for="(step, index) in selfEvaluation.steps" :key="index" class="input-group mb-2">
                                                <input type="text" class="form-control" v-model="selfEvaluation.steps[index]" placeholder="Prochaine étape...">
                                                <button class="btn btn-outline-secondary" type="button" v-if="index === selfEvaluation.steps.length - 1" @click="addNextStep">
                                                    <i class="bi bi-plus"></i>
                                                </button>
                                                <button class="btn btn-outline-danger" type="button" v-else @click="removeNextStep(index)">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary" :disabled="submitting">
                                <span v-if="submitting" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                {{ submitting ? 'Envoi en cours...' : 'Soumettre mon auto-évaluation' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Styles CSS existants */
.container-fluid {
    padding-left: 0;
    padding-right: 0;
    margin-left: 0;
    margin-right: 0;
    max-width: 100%;
}

.card {
    margin-bottom: 1.5rem;
    border-radius: 0.5rem;
}

[class*="col-"] {
    padding-left: 15px;
    padding-right: 15px;
}

.row {
    margin-left: 0;
    margin-right: 0;
}

.main-content {
    padding-left: 0;
    padding-right: 0;
}

.px-4 {
    padding-left: 1.5rem !important;
    padding-right: 1.5rem !important;
}

.stat-card {
    padding: 1.25rem;
    height: 100%;
}

.stat-card .value {
    font-size: 2rem;
    font-weight: bold;
    color: #2c3e50;
}

.stat-card .label {
    font-size: 1rem;
    margin-bottom: 0.5rem;
    color: #7f8c8d;
}

.rating-stars .bi-star-fill {
    color: #ffc107;
}

.chart-container {
    height: 200px;
}

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

<script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    const app = new Vue({
        el: '#evaluations-app',
        data: {
            // Données de l'étudiant
            studentId: <?php echo json_encode($student_id); ?>,
            assignmentId: <?php echo json_encode($assignment_id); ?>,
            internshipId: <?php echo json_encode($internship_id); ?>,
            
            // Données des évaluations
            evaluations: [],
            stats: {
                total: 0,
                average_score: 0,
                technical_avg: 0,
                professional_avg: 0,
                by_type: {}
            },
            objectives: [
                { id: 1, title: 'Améliorer la documentation du code', description: 'À compléter pour la prochaine évaluation' },
                { id: 2, title: 'Participer plus activement aux réunions', description: 'À compléter pour la prochaine évaluation' },
                { id: 3, title: 'Finaliser le module API', description: 'À compléter pour la prochaine évaluation' }
            ],
            
            // État de l'interface
            loading: true,
            error: false,
            errorMessage: '',
            
            // Auto-évaluation
            selfEvaluation: {
                criteria: {},
                comments: '',
                areas: [''],
                steps: ['']
            },
            criteriaLoading: false,
            criteriaError: false,
            criteriaErrorMessage: '',
            criteriaStructure: {},
            formStructure: {},
            submitting: false,
            
            // Référence au graphique
            chart: null
        },
        computed: {
            canCreateSelfEvaluation() {
                // Vérifier si l'étudiant a déjà une auto-évaluation
                return this.assignmentId && !this.evaluations.some(e => e.type === 'student');
            }
        },
        mounted() {
            this.loadEvaluations();
        },
        methods: {
            // Charger les évaluations via l'API
            loadEvaluations() {
                this.loading = true;
                this.error = false;
                
                axios.get('/tutoring/api/evaluations/get-student-evaluations.php')
                    .then(response => {
                        if (response.data.success) {
                            this.evaluations = response.data.evaluations;
                            this.stats = response.data.stats;
                            
                            // Trier les évaluations par date
                            this.evaluations.sort((a, b) => {
                                return new Date(b.submission_date) - new Date(a.submission_date);
                            });
                            
                            // Initialiser le graphique de progression
                            this.$nextTick(() => {
                                this.initProgressChart();
                            });
                        } else {
                            this.error = true;
                            this.errorMessage = response.data.message || 'Erreur lors du chargement des évaluations';
                        }
                    })
                    .catch(error => {
                        this.error = true;
                        this.errorMessage = error.response?.data?.message || 'Erreur de connexion au serveur';
                        console.error('Error loading evaluations:', error);
                    })
                    .finally(() => {
                        this.loading = false;
                    });
            },
            
            // Initialiser le graphique de progression
            initProgressChart() {
                const progressChartElement = document.getElementById('studentProgressChart');
                if (!progressChartElement) return;
                
                try {
                    // Préparer les données pour le graphique
                    const chartData = {
                        labels: [],
                        technical: [],
                        professional: []
                    };
                    
                    // Organiser les données chronologiquement
                    const sortedEvals = [...this.evaluations].sort((a, b) => {
                        return new Date(a.submission_date) - new Date(b.submission_date);
                    });
                    
                    sortedEvals.forEach(eval => {
                        chartData.labels.push(this.formatDate(eval.submission_date));
                        chartData.technical.push(eval.technical_avg);
                        chartData.professional.push(eval.professional_avg);
                    });
                    
                    // Créer le graphique
                    if (chartData.labels.length > 0) {
                        this.chart = new Chart(progressChartElement, {
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
                                        intersect: false,
                                        callbacks: {
                                            label: function(context) {
                                                const label = context.dataset.label || '';
                                                const value = context.raw !== undefined && !isNaN(context.raw) ? 
                                                    context.raw.toFixed(1) : 'N/A';
                                                return `${label}: ${value}/5`;
                                            }
                                        }
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
                } catch (error) {
                    console.error('Error initializing chart:', error);
                    if (progressChartElement) {
                        progressChartElement.parentNode.innerHTML = '<p class="text-muted text-center my-3">Impossible de charger le graphique</p>';
                    }
                }
            },
            
            // Afficher la modal d'auto-évaluation
            showSelfEvaluationModal() {
                this.criteriaLoading = true;
                this.criteriaError = false;
                
                // Réinitialiser le formulaire
                this.selfEvaluation = {
                    criteria: {},
                    comments: '',
                    areas: [''],
                    steps: ['']
                };
                
                // Charger la structure des critères
                axios.get('/tutoring/api/evaluations/get-criteria-structure.php')
                    .then(response => {
                        if (response.data.success) {
                            this.criteriaStructure = response.data.criteria_structure;
                            this.formStructure = response.data.form_structure;
                            
                            // Initialiser les critères vides
                            const emptyCriteria = {};
                            Object.keys(response.data.empty_scores).forEach(key => {
                                emptyCriteria[key] = '';
                            });
                            this.selfEvaluation.criteria = emptyCriteria;
                            
                            // Afficher la modal
                            const modal = new bootstrap.Modal(document.getElementById('selfEvaluationModal'));
                            modal.show();
                        } else {
                            this.criteriaError = true;
                            this.criteriaErrorMessage = response.data.message || 'Erreur lors du chargement des critères';
                        }
                    })
                    .catch(error => {
                        this.criteriaError = true;
                        this.criteriaErrorMessage = error.response?.data?.message || 'Erreur de connexion au serveur';
                        console.error('Error loading criteria:', error);
                    })
                    .finally(() => {
                        this.criteriaLoading = false;
                    });
            },
            
            // Soumettre l'auto-évaluation
            submitSelfEvaluation() {
                this.submitting = true;
                
                // Formatter les données
                const areasText = this.selfEvaluation.areas.filter(a => a.trim() !== '').join('\n');
                const stepsText = this.selfEvaluation.steps.filter(s => s.trim() !== '').join('\n');
                
                const data = {
                    criteria: this.selfEvaluation.criteria,
                    comments: this.selfEvaluation.comments,
                    areas_for_improvement: areasText,
                    next_steps: stepsText
                };
                
                // Envoyer les données
                axios.post('/tutoring/api/evaluations/submit-self-evaluation.php', data)
                    .then(response => {
                        if (response.data.success) {
                            // Fermer la modal
                            const modal = bootstrap.Modal.getInstance(document.getElementById('selfEvaluationModal'));
                            modal.hide();
                            
                            // Afficher un message de succès
                            alert('Auto-évaluation soumise avec succès !');
                            
                            // Recharger les évaluations
                            this.loadEvaluations();
                        } else {
                            alert(response.data.message || 'Erreur lors de la soumission');
                        }
                    })
                    .catch(error => {
                        alert(error.response?.data?.message || 'Erreur de connexion au serveur');
                        console.error('Error submitting self-evaluation:', error);
                    })
                    .finally(() => {
                        this.submitting = false;
                    });
            },
            
            // Ajouter un point à améliorer
            addImprovementArea() {
                this.selfEvaluation.areas.push('');
            },
            
            // Supprimer un point à améliorer
            removeImprovementArea(index) {
                this.selfEvaluation.areas.splice(index, 1);
            },
            
            // Ajouter une prochaine étape
            addNextStep() {
                this.selfEvaluation.steps.push('');
            },
            
            // Supprimer une prochaine étape
            removeNextStep(index) {
                this.selfEvaluation.steps.splice(index, 1);
            },
            
            // Formater une date
            formatDate(dateString) {
                try {
                    const date = new Date(dateString);
                    return date.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' });
                } catch (e) {
                    return dateString;
                }
            },
            
            // Formater un texte avec des sauts de ligne
            formatText(text) {
                if (!text) return '';
                return text.replace(/\n/g, '<br>');
            },
            
            // Formater une liste (texte avec sauts de ligne)
            formatList(text) {
                if (!text) return [];
                return text.split('\n').filter(item => item.trim() !== '');
            },
            
            // Obtenir le nom d'un critère
            getCriterionName(key) {
                let name = key;
                
                // Chercher dans la structure des critères
                for (const category in this.criteriaStructure) {
                    if (this.criteriaStructure[category][key]) {
                        name = this.criteriaStructure[category][key].name;
                        break;
                    }
                }
                
                if (!name || name === key) {
                    // Formater le nom si non trouvé
                    name = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                }
                
                return name;
            },
            
            // Imprimer une évaluation
            printEvaluation(index) {
                try {
                    const evaluation = this.evaluations[index];
                    if (!evaluation) return;
                    
                    // Créer une fenêtre d'impression
                    const printWindow = window.open('', '_blank');
                    if (!printWindow) {
                        alert('Veuillez autoriser les fenêtres popup pour imprimer l\'évaluation.');
                        return;
                    }
                    
                    // Déterminer le type d'évaluation
                    let evaluationType = evaluation.type_name || 'Évaluation';
                    
                    // Préparer les critères
                    let criteriaHtml = '';
                    if (evaluation.criteria_scores && Object.keys(evaluation.criteria_scores).length > 0) {
                        criteriaHtml = '<h4>Critères d\'évaluation</h4><table style="width:100%; border-collapse: collapse; margin-bottom: 20px;">';
                        criteriaHtml += '<tr><th style="text-align:left; padding: 8px; border-bottom: 1px solid #ddd;">Critère</th><th style="text-align:right; padding: 8px; border-bottom: 1px solid #ddd;">Score</th></tr>';
                        
                        for (const key in evaluation.criteria_scores) {
                            const criterion = evaluation.criteria_scores[key];
                            criteriaHtml += `<tr>
                                <td style="text-align:left; padding: 8px; border-bottom: 1px solid #eee;">${this.getCriterionName(key)}</td>
                                <td style="text-align:right; padding: 8px; border-bottom: 1px solid #eee;">${criterion.score}/5</td>
                            </tr>`;
                        }
                        
                        criteriaHtml += '</table>';
                    }
                    
                    // Préparer les points à améliorer
                    let improvementsHtml = '';
                    if (evaluation.areas_for_improvement) {
                        improvementsHtml = '<h4>Points à améliorer</h4><ul style="margin-bottom: 20px;">';
                        
                        this.formatList(evaluation.areas_for_improvement).forEach(area => {
                            improvementsHtml += `<li>${area}</li>`;
                        });
                        
                        improvementsHtml += '</ul>';
                    }
                    
                    // Préparer les prochaines étapes
                    let nextStepsHtml = '';
                    if (evaluation.next_steps) {
                        nextStepsHtml = '<h4>Prochaines étapes</h4><ul style="margin-bottom: 20px;">';
                        
                        this.formatList(evaluation.next_steps).forEach(step => {
                            nextStepsHtml += `<li>${step}</li>`;
                        });
                        
                        nextStepsHtml += '</ul>';
                    }
                    
                    // Créer le contenu HTML
                    printWindow.document.write(`
                        <!DOCTYPE html>
                        <html lang="fr">
                        <head>
                            <meta charset="UTF-8">
                            <meta name="viewport" content="width=device-width, initial-scale=1.0">
                            <title>${evaluationType} - ${this.formatDate(evaluation.submission_date)}</title>
                            <style>
                                body {
                                    font-family: Arial, sans-serif;
                                    line-height: 1.6;
                                    color: #333;
                                    padding: 20px;
                                    max-width: 800px;
                                    margin: 0 auto;
                                }
                                h1 {
                                    color: #2c3e50;
                                    margin-bottom: 20px;
                                    border-bottom: 2px solid #3498db;
                                    padding-bottom: 10px;
                                }
                                h2 {
                                    color: #2c3e50;
                                    margin-top: 30px;
                                    margin-bottom: 15px;
                                }
                                h4 {
                                    margin-top: 25px;
                                    margin-bottom: 10px;
                                    color: #2c3e50;
                                    border-bottom: 1px solid #eee;
                                    padding-bottom: 5px;
                                }
                                p {
                                    margin-bottom: 15px;
                                }
                                .meta-info {
                                    background-color: #f8f9fa;
                                    padding: 15px;
                                    border-radius: 5px;
                                    margin-bottom: 20px;
                                    border-left: 4px solid #3498db;
                                }
                                .score {
                                    font-size: 24px;
                                    font-weight: bold;
                                    color: #3498db;
                                    margin-bottom: 10px;
                                }
                                .comments {
                                    background-color: #f8f9fa;
                                    padding: 15px;
                                    border-radius: 5px;
                                    margin: 20px 0;
                                    white-space: pre-line;
                                }
                                .footer {
                                    margin-top: 40px;
                                    padding-top: 20px;
                                    border-top: 1px solid #eee;
                                    font-size: 0.8em;
                                    color: #7f8c8d;
                                    text-align: center;
                                }
                                @media print {
                                    body {
                                        padding: 0;
                                    }
                                    .no-print {
                                        display: none;
                                    }
                                }
                            </style>
                        </head>
                        <body>
                            <div class="no-print" style="text-align: right; margin-bottom: 20px;">
                                <button onclick="window.print()" style="padding: 8px 16px; background: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer;">
                                    Imprimer
                                </button>
                            </div>
                            
                            <h1>${evaluationType}</h1>
                            
                            <div class="meta-info">
                                <p><strong>Date:</strong> ${this.formatDate(evaluation.submission_date)}</p>
                                <p><strong>Évaluateur:</strong> ${evaluation.evaluator ? evaluation.evaluator.name : 'Non spécifié'}</p>
                                <div class="score">
                                    Note globale: ${evaluation.score}/5
                                    <span class="stars">
                                        ${'★'.repeat(Math.round(evaluation.score))}${'☆'.repeat(5 - Math.round(evaluation.score))}
                                    </span>
                                </div>
                            </div>
                            
                            <h2>Commentaires</h2>
                            <div class="comments">
                                ${evaluation.comments || 'Aucun commentaire fourni.'}
                            </div>
                            
                            ${criteriaHtml}
                            ${improvementsHtml}
                            ${nextStepsHtml}
                            
                            <div class="footer">
                                Document généré le ${new Date().toLocaleDateString('fr-FR', {day: 'numeric', month: 'long', year: 'numeric'})} à ${new Date().toLocaleTimeString('fr-FR')}
                            </div>
                        </body>
                        </html>
                    `);
                    
                    printWindow.document.close();
                    
                    // Attendre que le contenu soit chargé avant d'imprimer
                    setTimeout(() => {
                        printWindow.focus();
                        printWindow.print();
                    }, 1000);
                    
                } catch (error) {
                    console.error('Error during print:', error);
                    alert('Impossible de préparer l\'impression');
                }
            }
        }
    });
</script>

<?php
// Inclure le pied de page
include_once __DIR__ . '/../common/footer.php';
?>