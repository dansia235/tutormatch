<?php
/**
 * Admin Dashboard Content Template - Version améliorée
 * Uses components to display dashboard statistics with Bootstrap
 * Utilise les API pour charger les données dynamiquement
 */
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2>Tableau de bord</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item active" aria-current="page">Tableau de bord</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="row mb-4">
        <?php foreach ($statCards as $index => $card): ?>
            <div class="col-md-3 fade-in delay-<?php echo $index + 1; ?>">
                <div class="card stat-card">
                    <div class="value"><?php echo $card['value']; ?></div>
                    <div class="label"><?php echo $card['title']; ?></div>
                    <div class="progress mt-2">
                        <?php 
                        $progressClass = 'progress-bar';
                        if (isset($card['changeType'])) {
                            switch ($card['changeType']) {
                                case 'positive':
                                    $progressClass .= ' bg-success';
                                    break;
                                case 'negative':
                                    $progressClass .= ' bg-danger';
                                    break;
                                case 'info':
                                    $progressClass .= ' bg-info';
                                    break;
                                case 'warning':
                                    $progressClass .= ' bg-warning';
                                    break;
                            }
                        }
                        
                        // Calculate progress width - for percentage values use the value itself
                        $progressWidth = 100;
                        if (strpos($card['value'], '%') !== false) {
                            $progressWidth = intval($card['value']);
                        } 
                        ?>
                        <div class="<?php echo $progressClass; ?>" role="progressbar" 
                             style="width: <?php echo $progressWidth; ?>%;" 
                             aria-valuenow="<?php echo $progressWidth; ?>" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                        </div>
                    </div>
                    <?php if (!empty($card['change'])): ?>
                        <small class="text-muted"><?php echo $card['change']; ?></small>
                    <?php else: ?>
                        <small class="text-muted"><?php echo $card['linkText']; ?></small>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Main Content Row -->
    <div class="row">
        <!-- Two Cards on the same line -->
        <div class="col-lg-8 mb-4">
            <!-- Recent Assignments -->
            <div class="card h-100 fade-in">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Affectations récentes</span>
                    <a href="/tutoring/views/admin/assignments.php" class="btn btn-sm btn-outline-primary">Voir tout</a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($assignmentsData)): ?>
                        <div class="alert alert-info m-3" role="alert">
                            <i class="bi bi-info-circle-fill me-2"></i>Aucune affectation récente.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <?php foreach ($assignmentHeaders as $header): ?>
                                            <th><?php echo $header; ?></th>
                                        <?php endforeach; ?>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($assignmentsData as $row): ?>
                                        <tr>
                                            <?php foreach ($assignmentHeaders as $key => $label): ?>
                                                <td><?php echo $row[$key]; ?></td>
                                            <?php endforeach; ?>
                                            <td>
                                                <a href="/tutoring/views/admin/assignments/show.php?id=<?php echo isset($row['id']) ? $row['id'] : rand(1, 100); ?>" class="btn btn-sm btn-outline-info">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="/tutoring/views/admin/assignments/edit.php?id=<?php echo isset($row['id']) ? $row['id'] : rand(1, 100); ?>" class="btn btn-sm btn-outline-warning">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
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
        
        <!-- Quick Actions on the same line -->
        <div class="col-lg-4 mb-4">
            <!-- Quick Actions with Form Interface -->
            <div class="card h-100 fade-in">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="m-0 font-weight-bold">Actions rapides</h5>
                    <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#quickActionsForm" aria-expanded="false" aria-controls="quickActionsForm">
                        <i class="bi bi-plus-circle"></i>
                    </button>
                </div>
                <div class="card-body">
                    <!-- Navigation Tabs -->
                    <ul class="nav nav-tabs nav-fill mb-3" id="quickActionsTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="actions-tab" data-bs-toggle="tab" data-bs-target="#actions-content" type="button" role="tab" aria-controls="actions-content" aria-selected="true">
                                <i class="bi bi-lightning-charge"></i>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="search-tab" data-bs-toggle="tab" data-bs-target="#search-content" type="button" role="tab" aria-controls="search-content" aria-selected="false">
                                <i class="bi bi-search"></i>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="add-tab" data-bs-toggle="tab" data-bs-target="#add-content" type="button" role="tab" aria-controls="add-content" aria-selected="false">
                                <i class="bi bi-plus-lg"></i>
                            </button>
                        </li>
                    </ul>
                    
                    <!-- Tab Content -->
                    <div class="tab-content" id="quickActionsTabContent">
                        <!-- Actions Tab -->
                        <div class="tab-pane fade show active" id="actions-content" role="tabpanel" aria-labelledby="actions-tab">
                            <a href="/tutoring/views/admin/students.php" class="btn btn-outline-primary w-100 mb-2">
                                <i class="bi bi-mortarboard me-2"></i>Gérer les étudiants
                            </a>
                            <a href="/tutoring/views/admin/tutors.php" class="btn btn-outline-primary w-100 mb-2">
                                <i class="bi bi-person-workspace me-2"></i>Gérer les tuteurs
                            </a>
                            <a href="/tutoring/views/admin/internships.php" class="btn btn-outline-primary w-100 mb-2">
                                <i class="bi bi-briefcase me-2"></i>Gérer les stages
                            </a>
                            <a href="/tutoring/views/admin/assignments.php" class="btn btn-outline-primary w-100 mb-2">
                                <i class="bi bi-arrow-left-right me-2"></i>Gérer les affectations
                            </a>
                            <a href="/tutoring/views/admin/companies.php" class="btn btn-outline-primary w-100 mb-2">
                                <i class="bi bi-building me-2"></i>Gérer les entreprises
                            </a>
                            <a href="/tutoring/views/admin/messages.php" class="btn btn-primary w-100 mb-2">
                                <i class="bi bi-chat-left-text me-2"></i>Messagerie
                            </a>
                            <a href="/tutoring/views/admin/users.php" class="btn btn-outline-secondary w-100 mb-2">
                                <i class="bi bi-people me-2"></i>Gérer les utilisateurs
                            </a>
                            <a href="/tutoring/views/admin/settings.php" class="btn btn-outline-secondary w-100">
                                <i class="bi bi-gear me-2"></i>Paramètres
                            </a>
                        </div>
                        
                        <!-- Search Tab -->
                        <div class="tab-pane fade" id="search-content" role="tabpanel" aria-labelledby="search-tab">
                            <form action="/tutoring/views/admin/search-results.php" method="GET" class="mb-0">
                                <div class="form-group mb-2">
                                    <select name="type" class="form-select mb-2" required>
                                        <option value="">Type de recherche</option>
                                        <option value="student">Étudiant</option>
                                        <option value="teacher">Tuteur</option>
                                        <option value="internship">Stage</option>
                                        <option value="company">Entreprise</option>
                                    </select>
                                </div>
                                <div class="input-group mb-2">
                                    <input type="text" name="query" class="form-control" placeholder="Rechercher..." required>
                                    <button class="btn btn-primary" type="submit">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </div>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="advancedSearch" name="advanced" value="1">
                                    <label class="form-check-label" for="advancedSearch">Recherche avancée</label>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Add Tab -->
                        <div class="tab-pane fade" id="add-content" role="tabpanel" aria-labelledby="add-tab">
                            <div class="btn-group-vertical w-100">
                                <a href="/tutoring/views/admin/students/create.php" class="btn btn-outline-success mb-2">
                                    <i class="bi bi-person-plus me-2"></i>Nouvel étudiant
                                </a>
                                <a href="/tutoring/views/admin/teachers/create.php" class="btn btn-outline-success mb-2">
                                    <i class="bi bi-person-plus-fill me-2"></i>Nouveau tuteur
                                </a>
                                <a href="/tutoring/views/admin/internships/create.php" class="btn btn-outline-success mb-2">
                                    <i class="bi bi-briefcase-fill me-2"></i>Nouveau stage
                                </a>
                                <a href="/tutoring/views/admin/companies/create.php" class="btn btn-outline-success mb-2">
                                    <i class="bi bi-building-add me-2"></i>Nouvelle entreprise
                                </a>
                                <a href="/tutoring/views/admin/assignments/create.php" class="btn btn-outline-success mb-2">
                                    <i class="bi bi-diagram-3-fill me-2"></i>Nouvelle affectation
                                </a>
                                <a href="/tutoring/views/admin/users/create.php" class="btn btn-outline-success">
                                    <i class="bi bi-person-add me-2"></i>Nouvel utilisateur
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Action Form (Collapsible) -->
                    <div class="collapse mt-3" id="quickActionsForm">
                        <div class="card card-body bg-light">
                            <h6 class="card-subtitle mb-2 text-muted">Actions avancées</h6>
                            <form id="quickActionForm">
                                <div class="mb-3">
                                    <label for="actionType" class="form-label">Type d'action</label>
                                    <select class="form-select" id="actionType" required>
                                        <option value="">Sélectionner une action</option>
                                        <option value="export">Exporter des données</option>
                                        <option value="import">Importer des données</option>
                                        <option value="report">Générer un rapport</option>
                                        <option value="email">Envoyer un email</option>
                                        <option value="assign">Affectation automatique</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="actionTarget" class="form-label">Cible</label>
                                    <select class="form-select" id="actionTarget" required>
                                        <option value="">Sélectionner une cible</option>
                                        <option value="students">Étudiants</option>
                                        <option value="teachers">Tuteurs</option>
                                        <option value="internships">Stages</option>
                                        <option value="companies">Entreprises</option>
                                        <option value="assignments">Affectations</option>
                                        <option value="all">Tous</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="actionFormat" class="form-label">Format</label>
                                    <select class="form-select" id="actionFormat">
                                        <option value="pdf">PDF</option>
                                        <option value="excel">Excel</option>
                                        <option value="csv">CSV</option>
                                        <option value="json">JSON</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-lightning-charge me-2"></i>Exécuter
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Left Column (8 cols) -->
        <div class="col-lg-8">
            <!-- Charts Row -->
            <div class="row">
                <!-- Assignments Status Chart -->
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100 chart-card" id="assignment-status-card">
                        <div class="card-header">
                            <h5 class="m-0 font-weight-bold">Statut des affectations</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container" style="position: relative; height: 240px;">
                                <canvas id="chart-assignment-status" 
                                    data-url="/tutoring/api/dashboard/assignment-status.php"
                                    class="dashboard-chart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Internships by Status Chart -->
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100 chart-card" id="internship-status-card">
                        <div class="card-header">
                            <h5 class="m-0 font-weight-bold">Stages par statut</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container" style="position: relative; height: 240px;">
                                <canvas id="chart-internship-status" 
                                    data-url="/tutoring/api/dashboard/internship-status.php"
                                    class="dashboard-chart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Assignments by Department Chart (Full width for left column) -->
            <div class="card mb-4 fade-in chart-card" id="assignments-by-dept-card">
                <div class="card-header">
                    <h5 class="m-0 font-weight-bold">Affectations par département</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="position: relative; height: 240px;">
                        <canvas id="chart-assignments-by-dept" 
                            data-url="/tutoring/api/dashboard/assignments-by-department.php"
                            class="dashboard-chart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Column (4 cols) -->
        <div class="col-lg-4">
            <!-- Tutor Workload Chart -->
            <div class="card mb-4 fade-in chart-card" id="tutor-workload-card">
                <div class="card-header">
                    <h5 class="m-0 font-weight-bold">Charge de travail des tuteurs</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="position: relative; height: 240px;">
                        <canvas id="chart-tutor-workload" 
                            data-url="/tutoring/api/dashboard/tutor-workload.php"
                            class="dashboard-chart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- System Stats Card -->
            <div class="card mb-4 fade-in">
                <div class="card-header">
                    <h5 class="m-0 font-weight-bold">État du système</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush" id="system-metrics-list">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div class="spinner-border spinner-border-sm text-primary" role="status">
                                <span class="visually-hidden">Chargement...</span>
                            </div>
                            Chargement des données...
                        </li>
                    </ul>
                </div>
                <div class="card-footer text-end">
                    <a href="/tutoring/views/admin/settings.php" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-gear me-1"></i>Paramètres du système
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Ajustement des stat cards */
.stat-card {
    padding: 20px;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}

.stat-card .value {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--primary-color);
}

.stat-card .label {
    color: #7f8c8d;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 1px;
}

/* Animation pour le fade-in */
.fade-in {
    opacity: 0;
    transform: translateY(20px);
    animation: fadeInUp 0.5s ease forwards;
}

@keyframes fadeInUp {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Délais pour l'animation */
.delay-1 { animation-delay: 0.1s; }
.delay-2 { animation-delay: 0.2s; }
.delay-3 { animation-delay: 0.3s; }
.delay-4 { animation-delay: 0.4s; }

/* Style pour les cartes de graphiques */
.chart-card {
    transition: all 0.3s ease;
}

.chart-card:hover {
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

/* Chargement des graphiques */
.chart-container {
    position: relative;
}

.chart-loading {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: rgba(255, 255, 255, 0.7);
}
</style>

<?php 
// Include the fixed chart script generator to avoid syntax issues
require_once __DIR__ . '/../../api/dashboard/dashboard_chart_fix.php';
echo generateDashboardChartScript();
?>