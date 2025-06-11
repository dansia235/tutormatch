<?php
/**
 * Tableau de bord étudiant
 */

// Titre de la page
$pageTitle = 'Tableau de bord';
$currentPage = 'dashboard';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../includes/init.php';

// Vérifier que l'utilisateur est étudiant
requireRole('student');

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
                <div class="value">0%</div>
                <div class="label">Progression</div>
                <div class="progress mt-2">
                    <div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <small class="text-muted">Stage non commencé</small>
            </div>
        </div>
        <div class="col-md-3 fade-in delay-2">
            <div class="card stat-card">
                <div class="value">0</div>
                <div class="label">Évaluation</div>
                <div class="progress mt-2">
                    <div class="progress-bar bg-success" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <small class="text-muted">Pas d'évaluation</small>
            </div>
        </div>
        <div class="col-md-3 fade-in delay-3">
            <div class="card stat-card">
                <div class="value">0</div>
                <div class="label">Réunions</div>
                <div class="progress mt-2">
                    <div class="progress-bar bg-info" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <small class="text-muted">Réunions à venir</small>
            </div>
        </div>
        <div class="col-md-3 fade-in delay-4">
            <div class="card stat-card">
                <div class="value">0</div>
                <div class="label">Documents</div>
                <div class="progress mt-2">
                    <div class="progress-bar bg-warning" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <small class="text-muted">Documents soumis</small>
            </div>
        </div>
    </div>
    
    <!-- Main Content Row -->
    <div class="row">
        <!-- Left Column -->
        <div class="col-lg-8">
            <!-- Stage Details -->
            <div class="card mb-4 fade-in">
                <div class="card-header">
                    <span>Détails du stage</span>
                    <a href="/tutoring/views/student/internship.php" class="btn btn-sm btn-outline-primary">Voir les détails</a>
                </div>
                <div class="card-body">
                    <div class="alert alert-info" role="alert">
                        <i class="bi bi-info-circle-fill me-2"></i>Aucun stage affecté pour le moment. Contactez votre coordinateur pour plus d'informations.
                    </div>
                </div>
            </div>
            
            <!-- Tutor Details -->
            <div class="card mb-4 fade-in">
                <div class="card-header">
                    <span>Mon tuteur académique</span>
                    <a href="/tutoring/views/student/tutor.php" class="btn btn-sm btn-outline-primary">Voir le profil</a>
                </div>
                <div class="card-body">
                    <div class="alert alert-info" role="alert">
                        <i class="bi bi-info-circle-fill me-2"></i>Aucun tuteur affecté pour le moment. Contactez votre coordinateur pour plus d'informations.
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
                    <a href="/tutoring/views/student/documents.php" class="btn btn-outline-primary w-100 mb-2">
                        <i class="bi bi-folder me-2"></i>Soumettre un document
                    </a>
                    <a href="/tutoring/views/student/meetings.php" class="btn btn-outline-primary w-100 mb-2">
                        <i class="bi bi-calendar-event me-2"></i>Demander une réunion
                    </a>
                    <a href="/tutoring/views/student/preferences.php" class="btn btn-outline-primary w-100 mb-2">
                        <i class="bi bi-sliders me-2"></i>Définir mes préférences
                    </a>
                    <a href="/tutoring/views/student/messages.php" class="btn btn-primary w-100">
                        <i class="bi bi-chat-left-text me-2"></i>Contacter mon tuteur
                    </a>
                </div>
            </div>
            
            <!-- Upcoming Events -->
            <div class="card mb-4 fade-in">
                <div class="card-header">
                    Événements à venir
                </div>
                <div class="card-body p-0">
                    <div class="alert alert-info m-3" role="alert">
                        <i class="bi bi-info-circle-fill me-2"></i>Aucun événement planifié pour le moment.
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