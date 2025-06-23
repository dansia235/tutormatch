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
    
    <!-- Chargement -->
    <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Chargement...</span>
        </div>
        <p class="mt-3">Chargement des données...</p>
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
            <div class="col-md-3 fade-in delay-1 pe-3">
                <div class="card stat-card">
                    <div class="value">{{ stats.total_evaluations || 0 }}</div>
                    <div class="label">Total</div>
                    <div class="progress mt-2">
                        <div class="progress-bar" role="progressbar" style="width: 100%;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <small class="text-muted">Évaluations prévues</small>
                </div>
            </div>
            <div class="col-md-3 fade-in delay-2 pe-3">
                <div class="card stat-card">
                    <div class="value">{{ stats.completed_evaluations || 0 }}</div>
                    <div class="label">Complétées</div>
                    <div class="progress mt-2">
                        <div class="progress-bar bg-success" role="progressbar" 
                             :style="{ width: (stats.completed_percent || 0) + '%' }" 
                             :aria-valuenow="stats.completed_percent" 
                             aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <small class="text-muted">Évaluations terminées</small>
                </div>
            </div>
            <div class="col-md-3 fade-in delay-3 pe-3">
                <div class="card stat-card">
                    <div class="value">{{ stats.average_score ? stats.average_score.toFixed(1) : '-' }}</div>
                    <div class="label">Moyenne</div>
                    <div class="progress mt-2">
                        <div class="progress-bar bg-info" role="progressbar" 
                             :style="{ width: (stats.average_percent || 0) + '%' }" 
                             :aria-valuenow="stats.average_percent" 
                             aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <small class="text-muted">Note moyenne /5</small>
                </div>
            </div>
            <div class="col-md-3 fade-in delay-4">
                <div class="card stat-card">
                    <div class="value">{{ stats.improvement_rate || 0 }}%</div>
                    <div class="label">Progression</div>
                    <div class="progress mt-2">
                        <div class="progress-bar bg-warning" role="progressbar" 
                             :style="{ width: (stats.improvement_rate || 0) + '%' }" 
                             :aria-valuenow="stats.improvement_rate" 
                             aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <small class="text-muted">Taux d'amélioration</small>
                </div>
            </div>
        </div>
        
        <!-- Filtres et recherche -->
        <div class="row g-0 mx-0 px-4 mb-4">
            <div class="col-12">
                <div class="row g-0">
                    <div class="col-lg-8 pe-3">
                        <div class="card">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="student_id" class="form-label">Étudiant</label>
                                        <select class="form-select" id="student_id" v-model="selectedStudentId" @change="onStudentChange">
                                            <option value="" disabled selected>Choisir un étudiant...</option>
                                            <option v-for="student in students" :key="student.id" :value="student.id">
                                                {{ student.first_name + ' ' + student.last_name }}
                                            </option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6" v-if="selectedStudentId">
                                        <label for="type" class="form-label">Type d'évaluation</label>
                                        <select class="form-select" id="type" v-model="selectedType" @change="filterEvaluations">
                                            <option value="all">Toutes les évaluations</option>
                                            <option value="mid_term">Mi-parcours</option>
                                            <option value="final">Finale</option>
                                            <option value="student">Auto-évaluation</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card h-100">
                            <div class="card-header">
                                Actions rapides
                            </div>
                            <div class="card-body">
                                <button class="btn btn-primary w-100 mb-2" @click="showNewEvaluationModal" :disabled="!selectedStudentId">
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
        
        <!-- Évaluations en attente -->
        <div class="row g-0 mx-0 px-4 mb-4" v-if="pendingEvaluations.length > 0">
            <div class="col-12">
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <h5 class="alert-heading"><i class="bi bi-exclamation-triangle-fill me-2"></i>Évaluations en attente</h5>
                    <p>Vous avez {{ pendingEvaluations.length }} évaluation(s) à compléter :</p>
                    <ul class="mb-0">
                        <li v-for="pending in pendingEvaluations" :key="pending.id">
                            {{ pending.student_name }} - {{ getEvaluationTypeName(pending.type) }}
                            <button class="btn btn-sm btn-outline-primary ms-2" @click="startEvaluation(pending)">
                                Évaluer
                            </button>
                        </li>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        </div>
        
        <!-- Informations de l'étudiant si sélectionné -->
        <div class="row g-0 mx-0 px-4 mb-4" v-if="selectedStudent">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                <div class="d-flex align-items-center">
                                    <img :src="getStudentAvatar(selectedStudent)" alt="Student" class="rounded-circle me-3" width="80" height="80">
                                    <div>
                                        <h4 class="mb-1">{{ selectedStudent.first_name }} {{ selectedStudent.last_name }}</h4>
                                        <p class="text-muted mb-0">{{ selectedStudent.program || 'N/A' }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <h6 class="text-muted mb-1">Stage</h6>
                                <p class="mb-1"><strong>{{ selectedStudent.internship ? selectedStudent.internship.title : 'N/A' }}</strong></p>
                                <p class="mb-0">{{ selectedStudent.company ? selectedStudent.company.name : 'N/A' }}</p>
                                <p class="small text-muted mb-0" v-if="selectedStudent.internship">
                                    {{ formatDateRange(selectedStudent.internship.start_date, selectedStudent.internship.end_date) }}
                                </p>
                            </div>
                            <div class="col-md-2">
                                <h6 class="text-muted mb-1">Progression</h6>
                                <div class="progress mb-2" style="height: 10px;">
                                    <div class="progress-bar" role="progressbar" 
                                         :style="{ width: internshipProgress + '%' }" 
                                         :aria-valuenow="internshipProgress" 
                                         aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <p class="small text-muted mb-0">{{ internshipProgress }}% complété</p>
                            </div>
                            <div class="col-md-2 text-end">
                                <a :href="'/tutoring/views/tutor/documents.php?student_id=' + selectedStudentId" class="btn btn-outline-primary btn-sm mb-1 d-block">
                                    <i class="bi bi-folder me-1"></i>Documents
                                </a>
                                <a :href="'/tutoring/views/tutor/meetings.php?student_id=' + selectedStudentId" class="btn btn-outline-primary btn-sm d-block">
                                    <i class="bi bi-calendar-event me-1"></i>Réunions
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Statistiques de l'étudiant -->
        <div class="row g-0 mx-0 px-4 mb-4" v-if="selectedStudent && studentEvaluations.length > 0">
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
                                                <span>{{ studentStats.technical_avg ? studentStats.technical_avg.toFixed(1) : '-' }}/5</span>
                                            </div>
                                            <div class="progress" style="height: 8px;">
                                                <div class="progress-bar bg-primary" role="progressbar" 
                                                     :style="{ width: (studentStats.technical_avg / 5 * 100) + '%' }" 
                                                     :aria-valuenow="studentStats.technical_avg" 
                                                     aria-valuemin="0" aria-valuemax="5"></div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between">
                                                <span>Communication</span>
                                                <span>{{ studentStats.communication_score ? studentStats.communication_score.toFixed(1) : '-' }}/5</span>
                                            </div>
                                            <div class="progress" style="height: 8px;">
                                                <div class="progress-bar bg-success" role="progressbar" 
                                                     :style="{ width: (studentStats.communication_score / 5 * 100) + '%' }" 
                                                     :aria-valuenow="studentStats.communication_score" 
                                                     aria-valuemin="0" aria-valuemax="5"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between">
                                                <span>Travail d'équipe</span>
                                                <span>{{ studentStats.teamwork_score ? studentStats.teamwork_score.toFixed(1) : '-' }}/5</span>
                                            </div>
                                            <div class="progress" style="height: 8px;">
                                                <div class="progress-bar bg-info" role="progressbar" 
                                                     :style="{ width: (studentStats.teamwork_score / 5 * 100) + '%' }" 
                                                     :aria-valuenow="studentStats.teamwork_score" 
                                                     aria-valuemin="0" aria-valuemax="5"></div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between">
                                                <span>Autonomie</span>
                                                <span>{{ studentStats.autonomy_score ? studentStats.autonomy_score.toFixed(1) : '-' }}/5</span>
                                            </div>
                                            <div class="progress" style="height: 8px;">
                                                <div class="progress-bar bg-warning" role="progressbar" 
                                                     :style="{ width: (studentStats.autonomy_score / 5 * 100) + '%' }" 
                                                     :aria-valuenow="studentStats.autonomy_score" 
                                                     aria-valuemin="0" aria-valuemax="5"></div>
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
        
        <!-- Contenu principal -->
        <div class="row g-0 mx-0">
            <div class="col-12 px-4">
                <!-- Message lorsqu'aucun étudiant n'est sélectionné -->
                <div v-if="!selectedStudentId" class="card mb-4">
                    <div class="card-body">
                        <div class="text-center py-5">
                            <i class="bi bi-clipboard-check display-1 text-muted mb-3"></i>
                            <h4>Sélectionnez un étudiant pour commencer</h4>
                            <p class="text-muted">Choisissez un étudiant dans la liste pour consulter ou créer des évaluations.</p>
                        </div>
                    </div>
                </div>
                
                <!-- Vue d'ensemble de tous les étudiants -->
                <div v-if="!selectedStudentId" class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Vue d'ensemble des évaluations</span>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-download"></i> Exporter
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                                <li><a class="dropdown-item" href="#" @click.prevent="exportData('csv')">CSV</a></li>
                                <li><a class="dropdown-item" href="#" @click.prevent="exportData('pdf')">PDF</a></li>
                                <li><a class="dropdown-item" href="#" @click.prevent="exportData('excel')">Excel</a></li>
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
                                <tbody>
                                    <tr v-if="students.length === 0">
                                        <td colspan="7" class="text-center">Aucun étudiant assigné</td>
                                    </tr>
                                    <tr v-for="student in students" :key="student.id">
                                        <td>
                                            <a :href="'/tutoring/views/tutor/evaluations-api.php?student_id=' + student.id">
                                                {{ student.first_name }} {{ student.last_name }}
                                            </a>
                                        </td>
                                        <td>{{ student.program || 'N/A' }}</td>
                                        <td>{{ student.company_name || 'N/A' }}</td>
                                        <td>
                                            <span v-if="student.midterm_score" class="badge bg-success">{{ student.midterm_score }}/5</span>
                                            <span v-else class="badge bg-warning">En attente</span>
                                        </td>
                                        <td>
                                            <span v-if="student.final_score" class="badge bg-success">{{ student.final_score }}/5</span>
                                            <span v-else class="badge bg-warning">En attente</span>
                                        </td>
                                        <td>
                                            <strong v-if="student.average_score">{{ student.average_score }}/5</strong>
                                            <span v-else class="text-muted">-</span>
                                        </td>
                                        <td>
                                            <a :href="'/tutoring/views/tutor/evaluations-api.php?student_id=' + student.id" class="btn btn-sm btn-outline-primary" title="Voir le détail">
                                                <i class="bi bi-eye"></i> Détails
                                            </a>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- En-tête des évaluations d'un étudiant -->
                <div v-if="selectedStudentId" class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Évaluations de {{ selectedStudent ? selectedStudent.first_name + ' ' + selectedStudent.last_name : '' }}</h5>
                            <button class="btn btn-primary" @click="showNewEvaluationModal">
                                <i class="bi bi-plus-lg me-2"></i>Nouvelle évaluation
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Message si aucune évaluation -->
                <div v-if="selectedStudentId && filteredEvaluations.length === 0" class="card mb-4">
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
                
                <!-- Liste des évaluations -->
                <div v-if="selectedStudentId && filteredEvaluations.length > 0">
                    <div v-for="evaluation in filteredEvaluations" :key="evaluation.id" class="card mb-4 fade-in">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span>{{ getEvaluationTypeName(evaluation.type) }}</span>
                            <span class="badge bg-primary">{{ formatDate(evaluation.submission_date) }}</span>
                        </div>
                        <div class="card-body">
                            <!-- Note globale -->
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
                                        <small class="text-muted">Évaluateur: {{ evaluation.evaluator_name || 'N/A' }}</small>
                                    </div>
                                </div>
                                
                                <h6>Commentaires</h6>
                                <p v-html="formatText(evaluation.comments)"></p>
                            </div>
                            
                            <!-- Critères et recommandations -->
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
                                                     :style="{ width: (criterion.score / 5 * 100) + '%' }" 
                                                     :aria-valuenow="criterion.score" 
                                                     aria-valuemin="0" aria-valuemax="5"></div>
                                            </div>
                                        </div>
                                    </div>
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
                            
                            <!-- Actions -->
                            <div class="d-flex gap-2">
                                <button class="btn btn-outline-primary" @click="printEvaluation(evaluation)">
                                    <i class="bi bi-printer me-1"></i>Imprimer
                                </button>
                                <button class="btn btn-outline-secondary" @click="exportPDF(evaluation)">
                                    <i class="bi bi-file-earmark-pdf me-1"></i>Exporter PDF
                                </button>
                                <button v-if="canEditEvaluation(evaluation)" class="btn btn-outline-warning" @click="editEvaluation(evaluation)">
                                    <i class="bi bi-pencil me-1"></i>Modifier
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal pour créer/modifier une évaluation -->
    <div class="modal fade" id="evaluationModal" tabindex="-1" aria-labelledby="evaluationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="evaluationModalLabel">{{ isEditMode ? 'Modifier l\'évaluation' : 'Nouvelle évaluation' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form @submit.prevent="submitEvaluation">
                    <div class="modal-body">
                        <!-- Chargement des critères -->
                        <div v-if="criteriaLoading" class="text-center py-3">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Chargement...</span>
                            </div>
                            <p>Chargement des critères d'évaluation...</p>
                        </div>
                        
                        <!-- Erreur de chargement des critères -->
                        <div v-if="criteriaError" class="alert alert-danger" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            {{ criteriaErrorMessage }}
                        </div>
                        
                        <!-- Formulaire d'évaluation -->
                        <div v-if="!criteriaLoading && !criteriaError">
                            <div class="mb-4">
                                <h5>Informations générales</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="evaluation_type" class="form-label">Type d'évaluation</label>
                                            <select class="form-select" id="evaluation_type" v-model="evaluationForm.type" required>
                                                <option value="mid_term">Mi-parcours</option>
                                                <option value="final">Finale</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="evaluation_date" class="form-label">Date d'évaluation</label>
                                            <input type="date" class="form-control" id="evaluation_date" v-model="evaluationForm.date" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Critères techniques -->
                            <div class="mb-4" v-if="formStructure.technical">
                                <h5>Compétences techniques</h5>
                                <div class="row">
                                    <div v-for="(criterion, criterionKey) in formStructure.technical.criteria" 
                                         :key="criterionKey"
                                         class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">{{ criterion.name }}</label>
                                            <select class="form-select" v-model="evaluationForm.criteria[criterionKey]" required>
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
                            
                            <!-- Critères professionnels -->
                            <div class="mb-4" v-if="formStructure.professional">
                                <h5>Compétences professionnelles</h5>
                                <div class="row">
                                    <div v-for="(criterion, criterionKey) in formStructure.professional.criteria" 
                                         :key="criterionKey"
                                         class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">{{ criterion.name }}</label>
                                            <select class="form-select" v-model="evaluationForm.criteria[criterionKey]" required>
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
                            
                            <!-- Commentaires et recommandations -->
                            <div class="mb-4">
                                <h5>Commentaires et recommandations</h5>
                                <div class="mb-3">
                                    <label for="comments" class="form-label">Commentaires généraux</label>
                                    <textarea class="form-control" id="comments" v-model="evaluationForm.comments" rows="4" placeholder="Points forts, progression, observations générales..." required></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Points à améliorer</label>
                                    <div>
                                        <div v-for="(area, index) in evaluationForm.areas" :key="index" class="input-group mb-2">
                                            <input type="text" class="form-control" v-model="evaluationForm.areas[index]" placeholder="Point à améliorer...">
                                            <button class="btn btn-outline-secondary" type="button" v-if="index === evaluationForm.areas.length - 1" @click="addImprovementArea">
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
                                    <div>
                                        <div v-for="(step, index) in evaluationForm.steps" :key="index" class="input-group mb-2">
                                            <input type="text" class="form-control" v-model="evaluationForm.steps[index]" placeholder="Prochaine étape...">
                                            <button class="btn btn-outline-secondary" type="button" v-if="index === evaluationForm.steps.length - 1" @click="addNextStep">
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
                            {{ submitting ? 'Envoi en cours...' : (isEditMode ? 'Enregistrer les modifications' : 'Enregistrer l\'évaluation') }}
                        </button>
                    </div>
                </form>
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

/* Correction pour les éléments flex */
.d-flex {
    flex-wrap: nowrap;
}

/* Correction pour les éléments en ligne */
.inline-items {
    white-space: nowrap;
}

/* Animations de chargement */
.fade-in {
    animation: fadeIn 0.5s;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.delay-1 { animation-delay: 0.1s; }
.delay-2 { animation-delay: 0.2s; }
.delay-3 { animation-delay: 0.3s; }
.delay-4 { animation-delay: 0.4s; }

/* Étoiles d'évaluation */
.rating-stars .bi-star-fill {
    color: #ffc107;
}

/* Conteneur de graphique */
.chart-container {
    height: 200px;
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

<script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const app = new Vue({
            el: '#evaluations-app',
            data: {
                // ID du tuteur
                teacherId: document.getElementById('evaluations-app').dataset.teacherId,
                
                // Données des étudiants
                students: [],
                selectedStudentId: '',
                selectedStudent: null,
                
                // Données des évaluations
                studentEvaluations: [],
                filteredEvaluations: [],
                selectedType: 'all',
                pendingEvaluations: [],
                
                // Statistiques
                stats: {
                    total_evaluations: 0,
                    completed_evaluations: 0,
                    completed_percent: 0,
                    average_score: 0,
                    average_percent: 0,
                    improvement_rate: 0
                },
                studentStats: {
                    average_score: 0,
                    technical_avg: 0,
                    professional_avg: 0,
                    autonomy_score: 0,
                    communication_score: 0,
                    teamwork_score: 0
                },
                internshipProgress: 0,
                
                // État de l'interface
                loading: true,
                error: false,
                errorMessage: '',
                
                // Données pour le formulaire d'évaluation
                isEditMode: false,
                editingEvaluationId: null,
                evaluationForm: this.initEvaluationForm(),
                criteriaLoading: false,
                criteriaError: false,
                criteriaErrorMessage: '',
                criteriaStructure: {},
                formStructure: {},
                submitting: false,
                
                // Référence au graphique
                chart: null,
                
                // Types d'évaluation
                evaluationTypes: {
                    'mid_term': 'Mi-parcours',
                    'final': 'Finale',
                    'student': 'Auto-évaluation',
                    'company': 'Entreprise'
                }
            },
            mounted() {
                // Récupérer l'étudiant sélectionné dans l'URL
                const urlParams = new URLSearchParams(window.location.search);
                const studentId = urlParams.get('student_id');
                const type = urlParams.get('type') || 'all';
                
                this.selectedType = type;
                
                // Charger les données
                this.loadTeacherStats();
                this.loadStudents().then(() => {
                    if (studentId) {
                        this.selectedStudentId = studentId;
                        this.onStudentChange();
                    } else {
                        this.loading = false;
                    }
                });
            },
            methods: {
                // Initialiser le formulaire d'évaluation
                initEvaluationForm() {
                    return {
                        id: null,
                        type: 'mid_term',
                        date: new Date().toISOString().split('T')[0],
                        criteria: {},
                        comments: '',
                        areas: [''],
                        steps: ['']
                    };
                },
                
                // Charger les statistiques du tuteur
                loadTeacherStats() {
                    axios.get('/tutoring/api/evaluations/teacher-stats.php')
                        .then(response => {
                            if (response.data.success) {
                                this.stats = response.data;
                                
                                // Calculer les pourcentages
                                if (this.stats.total_evaluations > 0) {
                                    this.stats.completed_percent = (this.stats.completed_evaluations / this.stats.total_evaluations) * 100;
                                }
                                
                                if (this.stats.average_score) {
                                    this.stats.average_percent = (this.stats.average_score / 5) * 100;
                                }
                                
                                // Récupérer les évaluations en attente
                                if (response.data.pending_list && response.data.pending_list.length > 0) {
                                    this.pendingEvaluations = response.data.pending_list;
                                }
                            }
                        })
                        .catch(error => {
                            console.error('Erreur lors du chargement des statistiques:', error);
                        });
                },
                
                // Charger la liste des étudiants assignés
                loadStudents() {
                    return axios.get(`/tutoring/api/teachers/${this.teacherId}/students.php`)
                        .then(response => {
                            if (response.data.success && response.data.students) {
                                this.students = response.data.students;
                                
                                // Charger les données d'évaluation pour chaque étudiant
                                const promises = this.students.map(student => {
                                    return axios.get(`/tutoring/api/evaluations/list.php?student_id=${student.id}`)
                                        .then(evalResponse => {
                                            if (evalResponse.data.success && evalResponse.data.evaluations) {
                                                const evaluations = evalResponse.data.evaluations;
                                                let midterm = evaluations.find(e => e.type === 'mid_term');
                                                let final = evaluations.find(e => e.type === 'final');
                                                
                                                // Calculer la moyenne
                                                let total = 0;
                                                evaluations.forEach(e => total += parseFloat(e.score));
                                                let average = evaluations.length > 0 ? (total / evaluations.length).toFixed(1) : null;
                                                
                                                // Ajouter les scores à l'étudiant
                                                student.midterm_score = midterm ? midterm.score : null;
                                                student.final_score = final ? final.score : null;
                                                student.average_score = average;
                                            }
                                            return student;
                                        })
                                        .catch(error => {
                                            console.error(`Erreur lors du chargement des évaluations pour l'étudiant ${student.id}:`, error);
                                            return student;
                                        });
                                });
                                
                                return Promise.all(promises).then(updatedStudents => {
                                    this.students = updatedStudents;
                                    return this.students;
                                });
                            }
                            return [];
                        })
                        .catch(error => {
                            this.error = true;
                            this.errorMessage = 'Erreur lors du chargement des étudiants';
                            console.error('Erreur lors du chargement des étudiants:', error);
                            return [];
                        });
                },
                
                // Charger les détails d'un étudiant
                loadStudentDetails(studentId) {
                    return axios.get(`/tutoring/api/students/${studentId}.php`)
                        .then(response => {
                            if (response.data.success && response.data.student) {
                                this.selectedStudent = response.data.student;
                                
                                // Calculer la progression du stage
                                if (this.selectedStudent.internship) {
                                    const startDate = new Date(this.selectedStudent.internship.start_date);
                                    const endDate = new Date(this.selectedStudent.internship.end_date);
                                    const today = new Date();
                                    
                                    if (today >= startDate && today <= endDate) {
                                        const totalDays = Math.round((endDate - startDate) / (1000 * 60 * 60 * 24));
                                        const daysElapsed = Math.round((today - startDate) / (1000 * 60 * 60 * 24));
                                        this.internshipProgress = Math.min(100, Math.round((daysElapsed / totalDays) * 100));
                                    } else if (today > endDate) {
                                        this.internshipProgress = 100;
                                    } else {
                                        this.internshipProgress = 0;
                                    }
                                }
                                
                                return this.selectedStudent;
                            }
                            return null;
                        })
                        .catch(error => {
                            console.error('Erreur lors du chargement des détails de l\'étudiant:', error);
                            return null;
                        });
                },
                
                // Charger les évaluations d'un étudiant
                loadStudentEvaluations(studentId) {
                    return axios.get(`/tutoring/api/evaluations/list.php?student_id=${studentId}`)
                        .then(response => {
                            if (response.data.success && response.data.evaluations) {
                                this.studentEvaluations = response.data.evaluations;
                                this.filterEvaluations();
                                
                                // Calculer les statistiques de l'étudiant
                                this.calculateStudentStats();
                                
                                // Initialiser le graphique de progression
                                this.$nextTick(() => {
                                    this.initProgressChart();
                                });
                                
                                return this.studentEvaluations;
                            }
                            return [];
                        })
                        .catch(error => {
                            console.error('Erreur lors du chargement des évaluations:', error);
                            return [];
                        });
                },
                
                // Filtrer les évaluations par type
                filterEvaluations() {
                    if (this.selectedType === 'all') {
                        this.filteredEvaluations = [...this.studentEvaluations];
                    } else {
                        this.filteredEvaluations = this.studentEvaluations.filter(e => e.type === this.selectedType);
                    }
                    
                    // Trier par date
                    this.filteredEvaluations.sort((a, b) => {
                        return new Date(b.submission_date) - new Date(a.submission_date);
                    });
                },
                
                // Calculer les statistiques de l'étudiant
                calculateStudentStats() {
                    // Réinitialiser les statistiques
                    this.studentStats = {
                        average_score: 0,
                        technical_avg: 0,
                        professional_avg: 0,
                        autonomy_score: 0,
                        communication_score: 0,
                        teamwork_score: 0
                    };
                    
                    if (this.studentEvaluations.length === 0) {
                        return;
                    }
                    
                    // Calculer les moyennes
                    let totalScore = 0;
                    let technicalTotal = 0;
                    let professionalTotal = 0;
                    let autonomyTotal = 0;
                    let communicationTotal = 0;
                    let teamworkTotal = 0;
                    let autonomyCount = 0;
                    let communicationCount = 0;
                    let teamworkCount = 0;
                    
                    this.studentEvaluations.forEach(evaluation => {
                        totalScore += parseFloat(evaluation.score);
                        technicalTotal += parseFloat(evaluation.technical_avg);
                        professionalTotal += parseFloat(evaluation.professional_avg);
                        
                        // Compter les critères spécifiques
                        if (evaluation.criteria_scores) {
                            if (evaluation.criteria_scores.autonomy) {
                                autonomyTotal += parseFloat(evaluation.criteria_scores.autonomy.score);
                                autonomyCount++;
                            }
                            if (evaluation.criteria_scores.communication) {
                                communicationTotal += parseFloat(evaluation.criteria_scores.communication.score);
                                communicationCount++;
                            }
                            if (evaluation.criteria_scores.team_integration) {
                                teamworkTotal += parseFloat(evaluation.criteria_scores.team_integration.score);
                                teamworkCount++;
                            }
                        }
                    });
                    
                    // Calculer les moyennes
                    this.studentStats.average_score = totalScore / this.studentEvaluations.length;
                    this.studentStats.technical_avg = technicalTotal / this.studentEvaluations.length;
                    this.studentStats.professional_avg = professionalTotal / this.studentEvaluations.length;
                    this.studentStats.autonomy_score = autonomyCount > 0 ? autonomyTotal / autonomyCount : 0;
                    this.studentStats.communication_score = communicationCount > 0 ? communicationTotal / communicationCount : 0;
                    this.studentStats.teamwork_score = teamworkCount > 0 ? teamworkTotal / teamworkCount : 0;
                },
                
                // Initialiser le graphique de progression
                initProgressChart() {
                    const progressChartElement = document.getElementById('studentProgressChart');
                    if (!progressChartElement) return;
                    
                    // Détruire le graphique existant s'il y en a un
                    if (this.chart) {
                        this.chart.destroy();
                    }
                    
                    // Trier les évaluations par date
                    const sortedEvals = [...this.studentEvaluations].sort((a, b) => {
                        return new Date(a.submission_date) - new Date(b.submission_date);
                    });
                    
                    if (sortedEvals.length === 0) {
                        return;
                    }
                    
                    // Préparer les données du graphique
                    const labels = sortedEvals.map(eval => this.formatDate(eval.submission_date));
                    const technicalData = sortedEvals.map(eval => eval.technical_avg);
                    const professionalData = sortedEvals.map(eval => eval.professional_avg);
                    
                    // Créer le graphique
                    this.chart = new Chart(progressChartElement, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [
                                {
                                    label: 'Technique',
                                    data: technicalData,
                                    borderColor: '#3498db',
                                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                                    tension: 0.3,
                                    fill: true
                                },
                                {
                                    label: 'Professionnel',
                                    data: professionalData,
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
                },
                
                // Gérer le changement d'étudiant
                onStudentChange() {
                    if (!this.selectedStudentId) {
                        return;
                    }
                    
                    // Afficher l'URL dans la barre d'adresse
                    const url = new URL(window.location.href);
                    url.searchParams.set('student_id', this.selectedStudentId);
                    url.searchParams.set('type', this.selectedType);
                    window.history.pushState({}, '', url);
                    
                    this.loading = true;
                    
                    // Charger les détails de l'étudiant et ses évaluations
                    Promise.all([
                        this.loadStudentDetails(this.selectedStudentId),
                        this.loadStudentEvaluations(this.selectedStudentId)
                    ])
                    .finally(() => {
                        this.loading = false;
                    });
                },
                
                // Afficher le modal pour créer une nouvelle évaluation
                showNewEvaluationModal() {
                    this.isEditMode = false;
                    this.editingEvaluationId = null;
                    this.evaluationForm = this.initEvaluationForm();
                    this.loadCriteriaStructure().then(() => {
                        const modal = new bootstrap.Modal(document.getElementById('evaluationModal'));
                        modal.show();
                    });
                },
                
                // Afficher le modal pour modifier une évaluation
                editEvaluation(evaluation) {
                    this.isEditMode = true;
                    this.editingEvaluationId = evaluation.id;
                    
                    // Charger les critères
                    this.loadCriteriaStructure().then(() => {
                        // Remplir le formulaire avec les données de l'évaluation
                        this.evaluationForm = {
                            id: evaluation.id,
                            type: evaluation.type,
                            date: evaluation.submission_date ? new Date(evaluation.submission_date).toISOString().split('T')[0] : new Date().toISOString().split('T')[0],
                            criteria: {},
                            comments: evaluation.comments || '',
                            areas: evaluation.areas_for_improvement ? this.formatList(evaluation.areas_for_improvement) : [''],
                            steps: evaluation.next_steps ? this.formatList(evaluation.next_steps) : ['']
                        };
                        
                        // Transformer les critères pour le formulaire
                        if (evaluation.criteria_scores) {
                            for (const key in evaluation.criteria_scores) {
                                this.evaluationForm.criteria[key] = evaluation.criteria_scores[key].score;
                            }
                        }
                        
                        // Afficher le modal
                        const modal = new bootstrap.Modal(document.getElementById('evaluationModal'));
                        modal.show();
                    });
                },
                
                // Vérifier si une évaluation peut être modifiée
                canEditEvaluation(evaluation) {
                    // Seules les évaluations créées par le tuteur peuvent être modifiées
                    // Les auto-évaluations des étudiants ne peuvent pas être modifiées
                    return evaluation.type !== 'student';
                },
                
                // Charger la structure des critères
                loadCriteriaStructure() {
                    this.criteriaLoading = true;
                    this.criteriaError = false;
                    
                    return axios.get('/tutoring/api/evaluations/get-criteria-structure.php')
                        .then(response => {
                            if (response.data.success) {
                                this.criteriaStructure = response.data.criteria_structure;
                                this.formStructure = response.data.form_structure;
                                
                                // Initialiser les critères vides si on est en mode création
                                if (!this.isEditMode) {
                                    const emptyCriteria = {};
                                    for (const category in this.formStructure) {
                                        for (const key in this.formStructure[category].criteria) {
                                            emptyCriteria[key] = '';
                                        }
                                    }
                                    this.evaluationForm.criteria = emptyCriteria;
                                }
                                
                                return this.criteriaStructure;
                            } else {
                                this.criteriaError = true;
                                this.criteriaErrorMessage = response.data.message || 'Erreur lors du chargement des critères';
                                throw new Error(this.criteriaErrorMessage);
                            }
                        })
                        .catch(error => {
                            this.criteriaError = true;
                            this.criteriaErrorMessage = error.response?.data?.message || 'Erreur de connexion au serveur';
                            console.error('Erreur lors du chargement des critères:', error);
                            throw error;
                        })
                        .finally(() => {
                            this.criteriaLoading = false;
                        });
                },
                
                // Soumettre le formulaire d'évaluation
                submitEvaluation() {
                    this.submitting = true;
                    
                    // Préparer les données
                    const data = {
                        type: this.evaluationForm.type,
                        comments: this.evaluationForm.comments,
                        criteria: this.evaluationForm.criteria,
                        areas_for_improvement: this.evaluationForm.areas.filter(a => a.trim()).join('\n'),
                        next_steps: this.evaluationForm.steps.filter(s => s.trim()).join('\n'),
                        submission_date: this.evaluationForm.date
                    };
                    
                    // Ajouter l'ID de l'affectation ou de l'évaluation
                    if (this.isEditMode) {
                        data.id = this.editingEvaluationId;
                    } else {
                        // Trouver l'ID de l'affectation pour cet étudiant
                        const student = this.students.find(s => s.id === this.selectedStudentId);
                        data.assignment_id = student ? student.assignment_id : null;
                        
                        if (!data.assignment_id) {
                            alert('Erreur: Impossible de trouver l\'affectation pour cet étudiant.');
                            this.submitting = false;
                            return;
                        }
                    }
                    
                    // Envoyer les données
                    axios.post('/tutoring/api/evaluations/save-evaluation.php', data)
                        .then(response => {
                            if (response.data.success) {
                                // Fermer le modal
                                const modal = bootstrap.Modal.getInstance(document.getElementById('evaluationModal'));
                                modal.hide();
                                
                                // Afficher un message de succès
                                alert(this.isEditMode ? 'Évaluation mise à jour avec succès' : 'Évaluation créée avec succès');
                                
                                // Recharger les évaluations
                                this.loadStudentEvaluations(this.selectedStudentId);
                                
                                // Recharger les statistiques
                                this.loadTeacherStats();
                            } else {
                                alert('Erreur: ' + (response.data.message || 'Une erreur est survenue'));
                            }
                        })
                        .catch(error => {
                            console.error('Erreur lors de la soumission de l\'évaluation:', error);
                            alert('Erreur: ' + (error.response?.data?.message || 'Une erreur est survenue'));
                        })
                        .finally(() => {
                            this.submitting = false;
                        });
                },
                
                // Démarrer une évaluation à partir de la liste des évaluations en attente
                startEvaluation(pending) {
                    // Récupérer l'ID de l'étudiant
                    this.selectedStudentId = pending.student_id;
                    
                    // Charger les détails de l'étudiant
                    this.loadStudentDetails(this.selectedStudentId).then(() => {
                        this.loadStudentEvaluations(this.selectedStudentId).then(() => {
                            // Initialiser le formulaire
                            this.isEditMode = false;
                            this.editingEvaluationId = null;
                            this.evaluationForm = this.initEvaluationForm();
                            this.evaluationForm.type = pending.type;
                            
                            // Afficher le modal
                            this.loadCriteriaStructure().then(() => {
                                const modal = new bootstrap.Modal(document.getElementById('evaluationModal'));
                                modal.show();
                            });
                        });
                    });
                },
                
                // Ajouter un point à améliorer
                addImprovementArea() {
                    this.evaluationForm.areas.push('');
                },
                
                // Supprimer un point à améliorer
                removeImprovementArea(index) {
                    this.evaluationForm.areas.splice(index, 1);
                },
                
                // Ajouter une étape
                addNextStep() {
                    this.evaluationForm.steps.push('');
                },
                
                // Supprimer une étape
                removeNextStep(index) {
                    this.evaluationForm.steps.splice(index, 1);
                },
                
                // Exporter les données
                exportData(format) {
                    window.location.href = `/tutoring/views/tutor/export_evaluations.php?format=${format}`;
                },
                
                // Imprimer une évaluation
                printEvaluation(evaluation) {
                    // Créer une fenêtre d'impression
                    const printWindow = window.open('', '_blank');
                    if (!printWindow) {
                        alert('Veuillez autoriser les fenêtres popup pour imprimer l\'évaluation.');
                        return;
                    }
                    
                    // Déterminer le type d'évaluation
                    const evaluationType = this.getEvaluationTypeName(evaluation.type);
                    
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
                                <p><strong>Étudiant:</strong> ${this.selectedStudent ? this.selectedStudent.first_name + ' ' + this.selectedStudent.last_name : 'N/A'}</p>
                                <p><strong>Date:</strong> ${this.formatDate(evaluation.submission_date)}</p>
                                <p><strong>Évaluateur:</strong> ${evaluation.evaluator_name || 'Non spécifié'}</p>
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
                    
                    // Imprimer après chargement
                    setTimeout(() => {
                        printWindow.focus();
                        printWindow.print();
                    }, 1000);
                },
                
                // Exporter une évaluation au format PDF
                exportPDF(evaluation) {
                    window.location.href = `/tutoring/views/tutor/export_evaluation.php?id=${evaluation.id}&format=pdf`;
                },
                
                // Obtenir l'avatar d'un étudiant
                getStudentAvatar(student) {
                    if (!student) return '';
                    const initials = student.first_name.charAt(0) + student.last_name.charAt(0);
                    return `https://ui-avatars.com/api/?name=${encodeURIComponent(initials)}&background=3498db&color=fff&size=80`;
                },
                
                // Obtenir le nom d'un type d'évaluation
                getEvaluationTypeName(type) {
                    return this.evaluationTypes[type] || type;
                },
                
                // Obtenir le nom d'un critère
                getCriterionName(key) {
                    // Chercher dans la structure des critères
                    for (const category in this.criteriaStructure) {
                        if (this.criteriaStructure[category][key]) {
                            return this.criteriaStructure[category][key].name;
                        }
                    }
                    
                    // Si non trouvé, formater le nom
                    return key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                },
                
                // Formater une date
                formatDate(dateString) {
                    if (!dateString) return '';
                    const date = new Date(dateString);
                    return date.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' });
                },
                
                // Formater une plage de dates
                formatDateRange(startDate, endDate) {
                    if (!startDate || !endDate) return '';
                    return `${this.formatDate(startDate)} - ${this.formatDate(endDate)}`;
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
                }
            }
        });
    });
</script>

<?php
// Inclure le pied de page
include_once __DIR__ . '/../common/footer.php';
?>