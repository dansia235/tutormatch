<?php
/**
 * Formulaire d'évaluation pour les affectations
 */

// Initialiser les variables
$pageTitle = 'Formulaire d\'évaluation';
$currentPage = 'assignments';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../../includes/init.php';

// Vérifier les permissions
requireRole(['admin', 'coordinator', 'teacher']);

// Vérifier les paramètres
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage('error', 'ID d\'affectation invalide');
    redirect('/tutoring/views/admin/assignments.php');
}

$assignmentId = intval($_GET['id']);
$evaluationId = isset($_GET['evaluation_id']) ? intval($_GET['evaluation_id']) : null;
$evaluationType = isset($_GET['type']) ? $_GET['type'] : 'mid_term';

// Récupérer l'affectation
$assignmentModel = new Assignment($db);
$assignment = $assignmentModel->getById($assignmentId);

if (!$assignment) {
    setFlashMessage('error', 'Affectation non trouvée');
    redirect('/tutoring/views/admin/assignments.php');
}

// Récupérer les informations associées
$studentModel = new Student($db);
$student = $studentModel->getById($assignment['student_id']);

$teacherModel = new Teacher($db);
$teacher = $teacherModel->getById($assignment['teacher_id']);

$internshipModel = new Internship($db);
$internship = $internshipModel->getById($assignment['internship_id']);

// Récupérer l'évaluation si on est en mode édition
$evaluation = null;
$formData = [
    'type' => $evaluationType,
    'score' => '',
    'comments' => '',
    'strengths' => '',
    'areas_for_improvement' => '',
    'next_steps' => '',
    'status' => 'draft'
];

if ($evaluationId) {
    $evaluationModel = new Evaluation($db);
    $evaluation = $evaluationModel->getById($evaluationId);
    
    if ($evaluation && $evaluation['assignment_id'] == $assignmentId) {
        $formData = [
            'type' => $evaluation['type'],
            'score' => $evaluation['score'],
            'comments' => $evaluation['comments'] ?? $evaluation['feedback'] ?? '',
            'strengths' => $evaluation['strengths'] ?? '',
            'areas_for_improvement' => $evaluation['areas_for_improvement'] ?? $evaluation['areas_to_improve'] ?? '',
            'next_steps' => $evaluation['next_steps'] ?? '',
            'status' => $evaluation['status'] ?? 'draft'
        ];
    } else {
        setFlashMessage('error', 'Évaluation non trouvée ou non associée à cette affectation');
        redirect('/tutoring/views/admin/assignments/show.php?id=' . $assignmentId);
    }
}

// Définir le titre de la page en fonction du type d'évaluation
$evaluationTypeTitle = ($evaluationType == 'mid_term') ? 'mi-parcours' : 'finale';
$pageTitle = ($evaluationId) ? 'Modifier l\'évaluation ' . $evaluationTypeTitle : 'Nouvelle évaluation ' . $evaluationTypeTitle;

// Récupérer les erreurs du formulaire si elles existent
$formErrors = $_SESSION['form_errors'] ?? [];
unset($_SESSION['form_errors']);

// Récupérer les anciennes données du formulaire en cas d'erreur
if (isset($_SESSION['form_data'])) {
    $formData = $_SESSION['form_data'];
    unset($_SESSION['form_data']);
}
?>

<?php require_once __DIR__ . '/../../common/header.php'; ?>

<div class="container-fluid">
    <!-- En-tête de page avec actions -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-0">
                <i class="bi bi-clipboard-check me-2"></i><?php echo $pageTitle; ?>
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/tutoring/views/admin/dashboard.php">Tableau de bord</a></li>
                    <li class="breadcrumb-item"><a href="/tutoring/views/admin/assignments.php">Affectations</a></li>
                    <li class="breadcrumb-item"><a href="/tutoring/views/admin/assignments/show.php?id=<?php echo $assignmentId; ?>">Affectation #<?php echo $assignmentId; ?></a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?php echo $pageTitle; ?></li>
                </ol>
            </nav>
        </div>
        
        <div class="btn-group" role="group">
            <a href="/tutoring/views/admin/assignments/show.php?id=<?php echo $assignmentId; ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Retour à l'affectation
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
    
    <!-- Informations sur l'affectation -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="card-title mb-0">Informations sur l'affectation</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <p class="mb-1"><strong>Étudiant :</strong></p>
                    <p><?php echo h($student['first_name'] . ' ' . $student['last_name']); ?></p>
                </div>
                <div class="col-md-4">
                    <p class="mb-1"><strong>Tuteur :</strong></p>
                    <p><?php echo h($teacher['first_name'] . ' ' . $teacher['last_name']); ?></p>
                </div>
                <div class="col-md-4">
                    <p class="mb-1"><strong>Stage :</strong></p>
                    <p><?php echo h($internship['title']); ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Formulaire d'évaluation -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Formulaire d'évaluation <?php echo $evaluationTypeTitle; ?></h5>
        </div>
        <div class="card-body">
            <form action="/tutoring/api/evaluations/<?php echo $evaluationId ? 'update.php' : 'create.php'; ?>" method="POST" class="needs-validation" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="assignment_id" value="<?php echo $assignmentId; ?>">
                <?php if ($evaluationId): ?>
                <input type="hidden" name="id" value="<?php echo $evaluationId; ?>">
                <?php endif; ?>
                <input type="hidden" name="type" value="<?php echo h($formData['type']); ?>">
                <input type="hidden" name="redirect_url" value="/tutoring/views/admin/assignments/show.php?id=<?php echo $assignmentId; ?>">
                
                <div class="mb-4">
                    <label for="score" class="form-label">Score global (0-100)</label>
                    <div class="input-group">
                        <input type="number" class="form-control" id="score" name="score" min="0" max="100" value="<?php echo h($formData['score']); ?>" required>
                        <span class="input-group-text">/100</span>
                    </div>
                    <div class="form-text">Attribuez un score global à l'étudiant pour cette évaluation.</div>
                </div>
                
                <div class="mb-4">
                    <label for="comments" class="form-label">Commentaires généraux</label>
                    <textarea class="form-control" id="comments" name="comments" rows="4" required><?php echo h($formData['comments']); ?></textarea>
                    <div class="form-text">Fournissez une évaluation générale du travail de l'étudiant.</div>
                </div>
                
                <div class="mb-4">
                    <label for="strengths" class="form-label">Points forts</label>
                    <textarea class="form-control" id="strengths" name="strengths" rows="3"><?php echo h($formData['strengths']); ?></textarea>
                    <div class="form-text">Identifiez les principales forces démontrées par l'étudiant.</div>
                </div>
                
                <div class="mb-4">
                    <label for="areas_for_improvement" class="form-label">Axes d'amélioration</label>
                    <textarea class="form-control" id="areas_for_improvement" name="areas_for_improvement" rows="3"><?php echo h($formData['areas_for_improvement']); ?></textarea>
                    <div class="form-text">Identifiez les domaines dans lesquels l'étudiant pourrait s'améliorer.</div>
                </div>
                
                <div class="mb-4">
                    <label for="next_steps" class="form-label">Prochaines étapes</label>
                    <textarea class="form-control" id="next_steps" name="next_steps" rows="3"><?php echo h($formData['next_steps']); ?></textarea>
                    <div class="form-text">Définissez les prochaines étapes ou objectifs pour l'étudiant.</div>
                </div>
                
                <div class="mb-4">
                    <label for="status" class="form-label">Statut</label>
                    <select class="form-select" id="status" name="status" required>
                        <option value="draft" <?php echo ($formData['status'] === 'draft') ? 'selected' : ''; ?>>Brouillon</option>
                        <option value="submitted" <?php echo ($formData['status'] === 'submitted') ? 'selected' : ''; ?>>Soumis</option>
                        <option value="approved" <?php echo ($formData['status'] === 'approved') ? 'selected' : ''; ?>>Approuvé</option>
                    </select>
                    <div class="form-text">Définissez le statut de l'évaluation. Les évaluations soumises seront visibles par l'étudiant.</div>
                </div>
                
                <div class="d-flex justify-content-end mt-4">
                    <a href="/tutoring/views/admin/assignments/show.php?id=<?php echo $assignmentId; ?>" class="btn btn-secondary me-2">
                        Annuler
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i><?php echo $evaluationId ? 'Mettre à jour l\'évaluation' : 'Enregistrer l\'évaluation'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../common/footer.php'; ?>