<?php
/**
 * Vue pour modifier une affectation
 */

// Initialiser les variables
$pageTitle = 'Modifier une affectation';
$currentPage = 'assignments';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../../includes/init.php';

// Vérifier les permissions
requireRole(['admin', 'coordinator']);

// Vérifier l'ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage('error', 'ID d\'affectation invalide');
    redirect('/tutoring/views/admin/assignments/index.php');
}

// Instancier le contrôleur
$assignmentController = new AssignmentController($db);

// Afficher le formulaire de modification
$assignmentController->edit($_GET['id']);

// Récupérer les anciennes données du formulaire en cas d'erreur
$formData = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']);

// Récupérer les erreurs du formulaire
$formErrors = $_SESSION['form_errors'] ?? [];
unset($_SESSION['form_errors']);

// Préparer les valeurs par défaut
if (empty($formData)) {
    $formData = [
        'student_id' => $assignment['student_id'],
        'teacher_id' => $assignment['teacher_id'],
        'internship_id' => $assignment['internship_id'],
        'status' => $assignment['status'],
        'notes' => $assignment['notes']
    ];
}
?>

<?php require_once __DIR__ . '/../../common/header.php'; ?>

<div class="container-fluid">
    <!-- En-tête de page avec actions -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-0">
                <i class="bi bi-diagram-3 me-2"></i>Modifier une affectation
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/tutoring/views/admin/dashboard.php">Tableau de bord</a></li>
                    <li class="breadcrumb-item"><a href="/tutoring/views/admin/assignments/index.php">Affectations</a></li>
                    <li class="breadcrumb-item"><a href="/tutoring/views/admin/assignments/show.php?id=<?php echo $assignment['id']; ?>">Affectation #<?php echo $assignment['id']; ?></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Modifier</li>
                </ol>
            </nav>
        </div>
        
        <div class="btn-group">
            <a href="/tutoring/views/admin/assignments/show.php?id=<?php echo $assignment['id']; ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Retour aux détails
            </a>
            <a href="/tutoring/views/admin/assignments/index.php" class="btn btn-outline-secondary">
                <i class="bi bi-list me-2"></i>Liste des affectations
            </a>
        </div>
    </div>
    
    <!-- Affichage des erreurs du formulaire -->
    <?php if (!empty($formErrors)): ?>
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <strong>Erreurs dans le formulaire :</strong>
        <ul class="mb-0 mt-2">
            <?php foreach ($formErrors as $error): ?>
            <li><?php echo h($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <!-- Formulaire de modification d'affectation -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Modifier l'affectation #<?php echo $assignment['id']; ?></h5>
            <?php
            $statusBadge = [
                'pending' => '<span class="badge bg-warning">En attente</span>',
                'confirmed' => '<span class="badge bg-success">Confirmée</span>',
                'rejected' => '<span class="badge bg-danger">Rejetée</span>',
                'completed' => '<span class="badge bg-info">Terminée</span>'
            ];
            echo $statusBadge[$assignment['status']] ?? '<span class="badge bg-secondary">Inconnue</span>';
            ?>
        </div>
        <div class="card-body">
            <form action="/tutoring/views/admin/assignments/update.php" method="POST" class="needs-validation" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="id" value="<?php echo $assignment['id']; ?>">
                
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <label for="student_id" class="form-label">Étudiant <span class="text-danger">*</span></label>
                        <select class="form-select" id="student_id" name="student_id" required>
                            <option value="">-- Sélectionner un étudiant --</option>
                            <?php foreach ($students as $student): ?>
                            <option value="<?php echo $student['id']; ?>" 
                                <?php echo ($formData['student_id'] == $student['id']) ? 'selected' : ''; ?>
                                data-department="<?php echo h($student['department']); ?>"
                                data-level="<?php echo h($student['level']); ?>"
                                data-program="<?php echo h($student['program']); ?>">
                                <?php echo h($student['first_name'] . ' ' . $student['last_name']); ?> 
                                (<?php echo h($student['program']); ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="teacher_id" class="form-label">Tuteur <span class="text-danger">*</span></label>
                        <select class="form-select" id="teacher_id" name="teacher_id" required>
                            <option value="">-- Sélectionner un tuteur --</option>
                            <?php foreach ($teachers as $teacher): ?>
                            <option value="<?php echo $teacher['id']; ?>" 
                                <?php echo ($formData['teacher_id'] == $teacher['id']) ? 'selected' : ''; ?>
                                data-department="<?php echo h($teacher['department']); ?>"
                                data-remaining="<?php echo h($teacher['remaining_capacity']); ?>"
                                data-specialty="<?php echo h($teacher['specialty']); ?>">
                                <?php echo h($teacher['first_name'] . ' ' . $teacher['last_name']); ?> 
                                (<?php echo h($teacher['department']); ?>, <?php echo h($teacher['remaining_capacity']); ?> places)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="internship_id" class="form-label">Stage <span class="text-danger">*</span></label>
                        <select class="form-select" id="internship_id" name="internship_id" required>
                            <option value="">-- Sélectionner un stage --</option>
                            <?php foreach ($internships as $internship): ?>
                            <option value="<?php echo $internship['id']; ?>" 
                                <?php echo ($formData['internship_id'] == $internship['id']) ? 'selected' : ''; ?>
                                data-domain="<?php echo h($internship['domain']); ?>"
                                data-company="<?php echo h($internship['company_id']); ?>"
                                data-title="<?php echo h($internship['title']); ?>">
                                <?php echo h($internship['title']); ?> 
                                (<?php echo h($internship['company_name']); ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <label for="status" class="form-label">Statut</label>
                        <select class="form-select" id="status" name="status">
                            <option value="pending" <?php echo ($formData['status'] === 'pending') ? 'selected' : ''; ?>>En attente</option>
                            <option value="confirmed" <?php echo ($formData['status'] === 'confirmed') ? 'selected' : ''; ?>>Confirmée</option>
                            <option value="rejected" <?php echo ($formData['status'] === 'rejected') ? 'selected' : ''; ?>>Rejetée</option>
                            <option value="completed" <?php echo ($formData['status'] === 'completed') ? 'selected' : ''; ?>>Terminée</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="compatibility_score" class="form-label">Score de compatibilité</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="compatibility_score" name="compatibility_score" 
                                value="<?php echo h($assignment['compatibility_score'] ?? 0); ?>" 
                                min="0" max="10" step="0.1">
                            <span class="input-group-text">/10</span>
                        </div>
                        <div class="form-text">Ce score est normalement calculé automatiquement, mais peut être ajusté manuellement.</div>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="notes" class="form-label">Notes</label>
                    <textarea class="form-control" id="notes" name="notes" rows="4"><?php echo h($formData['notes'] ?? ''); ?></textarea>
                    <div class="form-text">Notes sur l'affectation (visible par l'administrateur, le tuteur et l'étudiant).</div>
                </div>
                
                <!-- Informations additionnelles -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="card-subtitle mb-0">Informations additionnelles</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <h6>Détails de l'affectation</h6>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>Date d'affectation:</span>
                                        <span class="badge bg-secondary"><?php echo formatDate($assignment['assignment_date']); ?></span>
                                    </li>
                                    <?php if ($assignment['confirmation_date']): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>Date de confirmation:</span>
                                        <span class="badge bg-success"><?php echo formatDate($assignment['confirmation_date']); ?></span>
                                    </li>
                                    <?php endif; ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>Dernière modification:</span>
                                        <span class="badge bg-info"><?php echo formatDate($assignment['updated_at']); ?></span>
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    <strong>Attention:</strong> La modification d'une affectation peut avoir des conséquences:
                                    <ul class="mb-0 mt-2">
                                        <li>Changer de stage libérera l'ancien stage</li>
                                        <li>Changer le statut enverra des notifications</li>
                                        <li>Certaines restrictions s'appliquent pour éviter les doublons</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-end mt-4">
                    <a href="/tutoring/views/admin/assignments/show.php?id=<?php echo $assignment['id']; ?>" class="btn btn-secondary me-2">
                        Annuler
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Enregistrer les modifications
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Scripts spécifiques -->
<script>
    // Validation du formulaire
    (function() {
        'use strict';
        
        // Fetch all forms we want to apply custom validation styles to
        const forms = document.querySelectorAll('.needs-validation');
        
        // Loop over them and prevent submission
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                
                form.classList.add('was-validated');
            }, false);
        });
    })();
</script>

<?php require_once __DIR__ . '/../../common/footer.php'; ?>