<?php
/**
 * Vue pour afficher les détails d'un stage
 */

// Initialiser les variables
$pageTitle = 'Détails du stage';
$currentPage = 'internships';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../../includes/init.php';

// Vérifier les permissions
requireRole(['admin', 'coordinator']);

// Vérifier l'ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage('error', 'ID de stage invalide');
    redirect('/tutoring/views/admin/internships.php');
}

// Instancier le contrôleur
$internshipController = new InternshipController($db);

// Afficher les détails du stage
$internshipController->show($_GET['id']);
?>

<?php require_once __DIR__ . '/../../common/header.php'; ?>

<div class="container-fluid">
    <!-- En-tête de page avec actions -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-0">
                <i class="bi bi-briefcase me-2"></i>Détails du stage
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/tutoring/views/admin/dashboard.php">Tableau de bord</a></li>
                    <li class="breadcrumb-item"><a href="/tutoring/views/admin/internships.php">Stages</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?php echo h($internship['title']); ?></li>
                </ol>
            </nav>
        </div>
        
        <div class="btn-group" role="group">
            <a href="/tutoring/views/admin/internships/edit.php?id=<?php echo $internship['id']; ?>" class="btn btn-outline-primary">
                <i class="bi bi-pencil me-2"></i>Modifier
            </a>
            <a href="/tutoring/views/admin/internships.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Retour
            </a>
        </div>
    </div>
    
    <div class="row">
        <!-- Informations sur le stage -->
        <div class="col-md-8 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Informations sur le stage</h5>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4><?php echo h($internship['title']); ?></h4>
                            <?php
                            $statusBadge = [
                                'available' => '<span class="badge bg-success">Disponible</span>',
                                'assigned' => '<span class="badge bg-primary">Affecté</span>',
                                'completed' => '<span class="badge bg-info">Terminé</span>',
                                'cancelled' => '<span class="badge bg-danger">Annulé</span>'
                            ];
                            echo $statusBadge[$internship['status']] ?? '<span class="badge bg-secondary">Inconnu</span>';
                            ?>
                        </div>
                        <div class="d-flex align-items-center mb-3">
                            <div class="me-3">
                                <?php if ($company['logo_path']): ?>
                                <img src="<?php echo h($company['logo_path']); ?>" alt="<?php echo h($company['name']); ?>" width="60" height="60" class="rounded">
                                <?php else: ?>
                                <div class="company-avatar">
                                    <?php echo strtoupper(substr($company['name'], 0, 2)); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <h5 class="mb-0"><?php echo h($company['name']); ?></h5>
                                <p class="text-muted mb-0">
                                    <?php echo h($company['city'] . ($company['country'] ? ', ' . $company['country'] : '')); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <h6 class="fw-bold">Période</h6>
                            <p class="mb-0">
                                <i class="bi bi-calendar3 me-2"></i>
                                Du <?php echo formatDate($internship['start_date']); ?> au <?php echo formatDate($internship['end_date']); ?>
                            </p>
                            <?php
                            // Calculer la durée en mois
                            $start = new DateTime($internship['start_date']);
                            $end = new DateTime($internship['end_date']);
                            $interval = $start->diff($end);
                            $months = $interval->m + ($interval->y * 12);
                            $days = $interval->d;
                            
                            $durationText = '';
                            if ($months > 0) {
                                $durationText = $months . ' mois';
                                if ($days > 0) {
                                    $durationText .= ' et ' . $days . ' jours';
                                }
                            } else {
                                $durationText = $interval->days . ' jours';
                            }
                            ?>
                            <p class="text-muted"><small>Durée : <?php echo $durationText; ?></small></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h6 class="fw-bold">Localisation</h6>
                            <p class="mb-0">
                                <i class="bi bi-geo-alt me-2"></i>
                                <?php echo h($internship['location'] ?: 'Non spécifié'); ?>
                            </p>
                            <p class="mb-0">
                                <i class="bi bi-building me-2"></i>
                                <?php 
                                $workModes = [
                                    'on_site' => 'Sur site',
                                    'remote' => 'À distance',
                                    'hybrid' => 'Hybride'
                                ];
                                echo $workModes[$internship['work_mode']] ?? 'Non spécifié';
                                ?>
                            </p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h6 class="fw-bold">Domaine</h6>
                            <p class="mb-0">
                                <i class="bi bi-tag me-2"></i>
                                <?php echo h($internship['domain']); ?>
                            </p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h6 class="fw-bold">Compensation</h6>
                            <p class="mb-0">
                                <i class="bi bi-currency-euro me-2"></i>
                                <?php echo $internship['compensation'] ? formatMoney($internship['compensation']) : 'Non spécifié'; ?>
                            </p>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h6 class="fw-bold">Description</h6>
                        <div class="card-text">
                            <?php echo nl2br(h($internship['description'])); ?>
                        </div>
                    </div>
                    
                    <?php if ($internship['requirements']): ?>
                    <div class="mb-4">
                        <h6 class="fw-bold">Prérequis</h6>
                        <div class="card-text">
                            <?php echo nl2br(h($internship['requirements'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($skills)): ?>
                    <div class="mb-4">
                        <h6 class="fw-bold">Compétences requises</h6>
                        <div>
                            <?php foreach ($skills as $skill): ?>
                            <span class="badge bg-secondary me-1 mb-1"><?php echo h($skill['skill_name']); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mb-4">
                        <h6 class="fw-bold">Informations de contact</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1">
                                    <i class="bi bi-person me-2"></i>
                                    <strong>Contact :</strong> 
                                    <?php echo h($company['contact_name'] ?: 'Non spécifié'); ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1">
                                    <i class="bi bi-envelope me-2"></i>
                                    <strong>Email :</strong> 
                                    <?php if ($company['contact_email']): ?>
                                    <a href="mailto:<?php echo h($company['contact_email']); ?>"><?php echo h($company['contact_email']); ?></a>
                                    <?php else: ?>
                                    Non spécifié
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1">
                                    <i class="bi bi-telephone me-2"></i>
                                    <strong>Téléphone :</strong> 
                                    <?php if ($company['contact_phone']): ?>
                                    <a href="tel:<?php echo h($company['contact_phone']); ?>"><?php echo h($company['contact_phone']); ?></a>
                                    <?php else: ?>
                                    Non spécifié
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1">
                                    <i class="bi bi-globe me-2"></i>
                                    <strong>Site web :</strong> 
                                    <?php if ($company['website']): ?>
                                    <a href="<?php echo h($company['website']); ?>" target="_blank"><?php echo h($company['website']); ?></a>
                                    <?php else: ?>
                                    Non spécifié
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3 text-muted">
                        <small>
                            <i class="bi bi-clock me-1"></i>Créé le <?php echo formatDate($internship['created_at'], 'd/m/Y H:i'); ?>
                            <?php if ($internship['updated_at'] && $internship['updated_at'] !== $internship['created_at']): ?>
                            <span class="mx-2">|</span>
                            <i class="bi bi-pencil me-1"></i>Mis à jour le <?php echo formatDate($internship['updated_at'], 'd/m/Y H:i'); ?>
                            <?php endif; ?>
                        </small>
                    </div>
                </div>
                <div class="card-footer">
                    <?php if ($internship['status'] === 'available'): ?>
                    <a href="/tutoring/views/admin/assignments/create.php?internship_id=<?php echo $internship['id']; ?>" class="btn btn-primary">
                        <i class="bi bi-diagram-3 me-2"></i>Affecter ce stage
                    </a>
                    <?php elseif ($internship['status'] === 'assigned'): ?>
                    <div class="alert alert-info mb-0">
                        <i class="bi bi-info-circle me-2"></i>Ce stage est déjà affecté.
                        <a href="/tutoring/views/admin/assignments/show.php?id=<?php echo $assignment['id']; ?>" class="alert-link ms-2">
                            Voir l'affectation
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Informations supplémentaires -->
        <div class="col-md-4 mb-4">
            <!-- Carte de l'entreprise -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">À propos de l'entreprise</h5>
                </div>
                <div class="card-body">
                    <h6><?php echo h($company['name']); ?></h6>
                    <?php if ($company['description']): ?>
                    <p class="mb-3"><?php echo nl2br(h(truncate($company['description'], 200))); ?></p>
                    <?php endif; ?>
                    
                    <?php if ($company['address'] || $company['city'] || $company['country']): ?>
                    <p class="mb-2">
                        <i class="bi bi-geo-alt me-2"></i>
                        <?php
                        $addressParts = [];
                        if ($company['address']) $addressParts[] = $company['address'];
                        if ($company['city']) $addressParts[] = $company['city'];
                        if ($company['country']) $addressParts[] = $company['country'];
                        echo h(implode(', ', $addressParts));
                        ?>
                    </p>
                    <?php endif; ?>
                    
                    <a href="/tutoring/views/admin/companies/show.php?id=<?php echo $company['id']; ?>" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-building me-2"></i>Voir le profil complet
                    </a>
                </div>
            </div>
            
            <!-- Carte d'affectation (si affecté) -->
            <?php if ($internship['status'] === 'assigned' && isset($assignment)): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Affectation</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6 class="fw-bold">Étudiant</h6>
                        <div class="d-flex align-items-center">
                            <?php if ($assignment['student_profile_image']): ?>
                            <img src="<?php echo h($assignment['student_profile_image']); ?>" alt="Student" class="rounded-circle me-2" width="40" height="40">
                            <?php else: ?>
                            <div class="avatar-sm me-2">
                                <?php echo strtoupper(substr($assignment['student_first_name'], 0, 1) . substr($assignment['student_last_name'], 0, 1)); ?>
                            </div>
                            <?php endif; ?>
                            <div>
                                <p class="mb-0 fw-bold"><?php echo h($assignment['student_first_name'] . ' ' . $assignment['student_last_name']); ?></p>
                                <p class="mb-0 text-muted small"><?php echo h($assignment['student_program']); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <h6 class="fw-bold">Tuteur</h6>
                        <div class="d-flex align-items-center">
                            <?php if ($assignment['teacher_profile_image']): ?>
                            <img src="<?php echo h($assignment['teacher_profile_image']); ?>" alt="Teacher" class="rounded-circle me-2" width="40" height="40">
                            <?php else: ?>
                            <div class="avatar-sm me-2">
                                <?php echo strtoupper(substr($assignment['teacher_first_name'], 0, 1) . substr($assignment['teacher_last_name'], 0, 1)); ?>
                            </div>
                            <?php endif; ?>
                            <div>
                                <p class="mb-0 fw-bold"><?php echo h($assignment['teacher_first_name'] . ' ' . $assignment['teacher_last_name']); ?></p>
                                <p class="mb-0 text-muted small"><?php echo h($assignment['teacher_specialty']); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <h6 class="fw-bold">Détails</h6>
                        <p class="mb-1">
                            <strong>Statut:</strong>
                            <?php
                            $assignmentStatusBadge = [
                                'pending' => '<span class="badge bg-warning">En attente</span>',
                                'confirmed' => '<span class="badge bg-success">Confirmé</span>',
                                'rejected' => '<span class="badge bg-danger">Rejeté</span>',
                                'completed' => '<span class="badge bg-info">Terminé</span>'
                            ];
                            echo $assignmentStatusBadge[$assignment['status']] ?? '<span class="badge bg-secondary">Inconnu</span>';
                            ?>
                        </p>
                        <p class="mb-1">
                            <strong>Date d'affectation:</strong> <?php echo formatDate($assignment['assignment_date'], 'd/m/Y'); ?>
                        </p>
                        <?php if ($assignment['confirmation_date']): ?>
                        <p class="mb-1">
                            <strong>Date de confirmation:</strong> <?php echo formatDate($assignment['confirmation_date'], 'd/m/Y'); ?>
                        </p>
                        <?php endif; ?>
                        
                        <div class="mt-3">
                            <a href="/tutoring/views/admin/assignments/show.php?id=<?php echo $assignment['id']; ?>" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-eye me-2"></i>Voir les détails
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Carte des étudiants intéressés (si disponible) -->
            <?php if ($internship['status'] === 'available' && !empty($interestedStudents)): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Étudiants intéressés</h5>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php foreach ($interestedStudents as $student): ?>
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <?php if ($student['profile_image']): ?>
                                    <img src="<?php echo h($student['profile_image']); ?>" alt="Student" class="rounded-circle me-2" width="40" height="40">
                                    <?php else: ?>
                                    <div class="avatar-sm me-2">
                                        <?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?>
                                    </div>
                                    <?php endif; ?>
                                    <div>
                                        <p class="mb-0 fw-bold"><?php echo h($student['first_name'] . ' ' . $student['last_name']); ?></p>
                                        <p class="mb-0 text-muted small"><?php echo h($student['program']); ?></p>
                                    </div>
                                </div>
                                <span class="badge bg-primary">Préférence: <?php echo h($student['preference_order']); ?></span>
                            </div>
                            <div class="mt-2">
                                <a href="/tutoring/views/admin/students/show.php?id=<?php echo $student['id']; ?>" class="btn btn-outline-info btn-sm">
                                    <i class="bi bi-person me-1"></i>Profil
                                </a>
                                <a href="/tutoring/views/admin/assignments/create.php?student_id=<?php echo $student['id']; ?>&internship_id=<?php echo $internship['id']; ?>" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-diagram-3 me-1"></i>Affecter
                                </a>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Actions -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="/tutoring/views/admin/internships/edit.php?id=<?php echo $internship['id']; ?>" class="btn btn-outline-primary">
                            <i class="bi bi-pencil me-2"></i>Modifier ce stage
                        </a>
                        
                        <?php if ($internship['status'] === 'available'): ?>
                        <a href="/tutoring/views/admin/assignments/create.php?internship_id=<?php echo $internship['id']; ?>" class="btn btn-primary">
                            <i class="bi bi-diagram-3 me-2"></i>Affecter ce stage
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($internship['status'] !== 'cancelled'): ?>
                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#statusModal">
                            <?php if ($internship['status'] === 'available'): ?>
                            <i class="bi bi-x-circle me-2"></i>Annuler ce stage
                            <?php else: ?>
                            <i class="bi bi-arrow-repeat me-2"></i>Changer le statut
                            <?php endif; ?>
                        </button>
                        <?php else: ?>
                        <form action="/tutoring/views/admin/internships/update-status.php" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            <input type="hidden" name="id" value="<?php echo $internship['id']; ?>">
                            <input type="hidden" name="status" value="available">
                            <button type="submit" class="btn btn-outline-success w-100">
                                <i class="bi bi-arrow-counterclockwise me-2"></i>Remettre en disponible
                            </button>
                        </form>
                        <?php endif; ?>
                        
                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                            <i class="bi bi-trash me-2"></i>Supprimer ce stage
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de changement de statut -->
<div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="statusModalLabel">Changer le statut du stage</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/tutoring/views/admin/internships/update-status.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="id" value="<?php echo $internship['id']; ?>">
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Nouveau statut</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="available" <?php echo $internship['status'] === 'available' ? 'selected' : ''; ?>>Disponible</option>
                            <option value="assigned" <?php echo $internship['status'] === 'assigned' ? 'selected' : ''; ?>>Affecté</option>
                            <option value="completed" <?php echo $internship['status'] === 'completed' ? 'selected' : ''; ?>>Terminé</option>
                            <option value="cancelled" <?php echo $internship['status'] === 'cancelled' ? 'selected' : ''; ?>>Annulé</option>
                        </select>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Attention :</strong> Changer le statut peut avoir un impact sur les affectations existantes.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de confirmation de suppression -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirmer la suppression</h5>
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

<?php require_once __DIR__ . '/../../common/footer.php'; ?>