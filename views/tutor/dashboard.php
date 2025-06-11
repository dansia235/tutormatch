<?php
/**
 * Tableau de bord tuteur
 */

// Titre de la page
$pageTitle = 'Tableau de bord';
$currentPage = 'dashboard';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../includes/init.php';

// Vérifier que l'utilisateur est tuteur
requireRole('teacher');

// Inclure l'en-tête
include_once __DIR__ . '/../common/header.php';
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
    
    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3 fade-in delay-1">
            <div class="card stat-card">
                <div class="value">0</div>
                <div class="label">Étudiants</div>
                <div class="progress mt-2">
                    <div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <small class="text-muted">Étudiants affectés</small>
            </div>
        </div>
        <div class="col-md-3 fade-in delay-2">
            <div class="card stat-card">
                <div class="value">0</div>
                <div class="label">Réunions</div>
                <div class="progress mt-2">
                    <div class="progress-bar bg-success" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <small class="text-muted">Réunions à venir</small>
            </div>
        </div>
        <div class="col-md-3 fade-in delay-3">
            <div class="card stat-card">
                <div class="value">0</div>
                <div class="label">Évaluations</div>
                <div class="progress mt-2">
                    <div class="progress-bar bg-info" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <small class="text-muted">Évaluations en attente</small>
            </div>
        </div>
        <div class="col-md-3 fade-in delay-4">
            <div class="card stat-card">
                <div class="value">0</div>
                <div class="label">Messages</div>
                <div class="progress mt-2">
                    <div class="progress-bar bg-warning" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <small class="text-muted">Messages non lus</small>
            </div>
        </div>
    </div>
    
    <!-- Main Content Row -->
    <div class="row">
        <!-- Left Column -->
        <div class="col-lg-8">
            <!-- My Students -->
            <div class="card mb-4 fade-in">
                <div class="card-header">
                    <span>Mes étudiants</span>
                    <a href="/tutoring/views/tutor/students.php" class="btn btn-sm btn-outline-primary">Voir tout</a>
                </div>
                <div class="card-body">
                    <div class="alert alert-info" role="alert">
                        <i class="bi bi-info-circle-fill me-2"></i>Aucun étudiant affecté pour le moment.
                    </div>
                </div>
            </div>
            
            <!-- Upcoming Meetings -->
            <div class="card mb-4 fade-in">
                <div class="card-header">
                    <span>Réunions à venir</span>
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addMeetingModal">
                        <i class="bi bi-plus"></i>
                    </button>
                </div>
                <div class="card-body">
                    <div class="alert alert-info" role="alert">
                        <i class="bi bi-info-circle-fill me-2"></i>Aucune réunion planifiée. Ajoutez des réunions pour les voir apparaître ici.
                    </div>
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
                    <a href="/tutoring/views/tutor/students.php" class="btn btn-outline-primary w-100 mb-2">
                        <i class="bi bi-mortarboard me-2"></i>Voir mes étudiants
                    </a>
                    <a href="/tutoring/views/tutor/meetings.php" class="btn btn-outline-primary w-100 mb-2">
                        <i class="bi bi-calendar-event me-2"></i>Planifier une réunion
                    </a>
                    <a href="/tutoring/views/tutor/evaluations.php" class="btn btn-outline-primary w-100 mb-2">
                        <i class="bi bi-clipboard-check me-2"></i>Évaluer un étudiant
                    </a>
                    <a href="/tutoring/views/tutor/messages.php" class="btn btn-primary w-100">
                        <i class="bi bi-chat-left-text me-2"></i>Envoyer un message
                    </a>
                </div>
            </div>
            
            <!-- Recent Messages -->
            <div class="card mb-4 fade-in">
                <div class="card-header">
                    Messages récents
                </div>
                <div class="card-body">
                    <div class="alert alert-info" role="alert">
                        <i class="bi bi-info-circle-fill me-2"></i>Aucun message pour le moment.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Inclure le pied de page
include_once __DIR__ . '/../common/footer.php';
?>