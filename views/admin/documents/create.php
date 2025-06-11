<?php
/**
 * Vue pour créer un nouveau document
 */

// Initialiser les variables
$pageTitle = 'Ajouter un document';
$currentPage = 'documents';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../../includes/init.php';

// Vérifier les permissions
requireRole(['admin', 'coordinator']);

// Instancier le contrôleur
$documentController = new DocumentController($db);

// Récupérer les anciennes données du formulaire en cas d'erreur
$formData = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']);

// Récupérer les erreurs du formulaire
$formErrors = $_SESSION['form_errors'] ?? [];
unset($_SESSION['form_errors']);

// Récupérer les informations de relation si présentes
$relatedId = isset($_GET['related_id']) ? intval($_GET['related_id']) : null;
$relatedType = isset($_GET['related_type']) ? $_GET['related_type'] : null;

// Si des relations sont spécifiées, récupérer les informations associées
$relatedInfo = null;
if ($relatedId && $relatedType) {
    switch ($relatedType) {
        case 'assignment':
            $assignmentModel = new Assignment($db);
            $relatedInfo = $assignmentModel->getById($relatedId);
            break;
        case 'internship':
            $internshipModel = new Internship($db);
            $relatedInfo = $internshipModel->getById($relatedId);
            break;
        case 'student':
            $studentModel = new Student($db);
            $relatedInfo = $studentModel->getById($relatedId);
            break;
        case 'teacher':
            $teacherModel = new Teacher($db);
            $relatedInfo = $teacherModel->getById($relatedId);
            break;
    }
}

// Définir les catégories disponibles
$categories = [
    'cv' => 'CV / Curriculum Vitae',
    'report' => 'Rapport de stage',
    'agreement' => 'Convention de stage',
    'evaluation' => 'Évaluation',
    'image' => 'Image',
    'presentation' => 'Présentation',
    'other' => 'Autre document'
];

// Définir le titre de la page en fonction du contexte
if ($relatedInfo) {
    switch ($relatedType) {
        case 'assignment':
            $contextTitle = "Ajouter un document pour l'affectation #" . $relatedId;
            break;
        case 'internship':
            $contextTitle = "Ajouter un document pour le stage: " . h($relatedInfo['title']);
            break;
        case 'student':
            $contextTitle = "Ajouter un document pour l'étudiant: " . h($relatedInfo['first_name'] . ' ' . $relatedInfo['last_name']);
            break;
        case 'teacher':
            $contextTitle = "Ajouter un document pour le tuteur: " . h($relatedInfo['first_name'] . ' ' . $relatedInfo['last_name']);
            break;
    }
}
?>

<?php require_once __DIR__ . '/../../common/header.php'; ?>

<div class="container-fluid">
    <!-- En-tête de page avec actions -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-0">
                <i class="bi bi-file-earmark-plus me-2"></i><?php echo isset($contextTitle) ? $contextTitle : 'Ajouter un document'; ?>
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/tutoring/views/admin/dashboard.php">Tableau de bord</a></li>
                    <li class="breadcrumb-item"><a href="/tutoring/views/admin/documents.php">Documents</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Ajouter</li>
                </ol>
            </nav>
        </div>
        
        <a href="<?php echo $relatedId && $relatedType ? getBackUrl($relatedType, $relatedId) : '/tutoring/views/admin/documents.php'; ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Retour
        </a>
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
    
    <!-- Formulaire d'ajout de document -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Informations du document</h5>
        </div>
        <div class="card-body">
            <form action="/tutoring/views/admin/documents/store.php" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                
                <!-- Champs cachés pour les relations -->
                <?php if ($relatedId && $relatedType): ?>
                <input type="hidden" name="related_id" value="<?php echo $relatedId; ?>">
                <input type="hidden" name="related_type" value="<?php echo $relatedType; ?>">
                <?php endif; ?>
                
                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                        <label for="title" class="form-label">Titre du document <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" value="<?php echo h($formData['title'] ?? ''); ?>" required>
                        <div class="form-text">Un titre descriptif pour le document.</div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="category" class="form-label">Catégorie <span class="text-danger">*</span></label>
                        <select class="form-select" id="category" name="category" required>
                            <option value="">-- Sélectionner une catégorie --</option>
                            <?php foreach ($categories as $key => $value): ?>
                            <option value="<?php echo $key; ?>" <?php echo (isset($formData['category']) && $formData['category'] === $key) ? 'selected' : ''; ?>>
                                <?php echo $value; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo h($formData['description'] ?? ''); ?></textarea>
                    <div class="form-text">Une description détaillée du document (optionnel).</div>
                </div>
                
                <div class="mb-4">
                    <label for="document" class="form-label">Fichier <span class="text-danger">*</span></label>
                    <input type="file" class="form-control" id="document" name="document" required>
                    <div class="form-text">
                        Formats acceptés: <span id="allowedFormats">PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, JPG, PNG, GIF, ZIP, RAR, TXT</span><br>
                        Taille maximale: 10 Mo
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                        <label for="visibility" class="form-label">Visibilité</label>
                        <select class="form-select" id="visibility" name="visibility">
                            <option value="private" <?php echo (!isset($formData['visibility']) || $formData['visibility'] === 'private') ? 'selected' : ''; ?>>Privé (visible uniquement par vous et les administrateurs)</option>
                            <option value="restricted" <?php echo (isset($formData['visibility']) && $formData['visibility'] === 'restricted') ? 'selected' : ''; ?>>Restreint (visible par les personnes concernées)</option>
                            <option value="public" <?php echo (isset($formData['visibility']) && $formData['visibility'] === 'public') ? 'selected' : ''; ?>>Public (visible par tous)</option>
                        </select>
                    </div>
                </div>
                
                <?php if ($relatedInfo): ?>
                <!-- Informations contextuelles -->
                <div class="alert alert-info mb-4">
                    <h6 class="alert-heading"><i class="bi bi-info-circle me-2"></i>Information</h6>
                    <p class="mb-0">
                        <?php if ($relatedType === 'assignment'): ?>
                        Ce document sera associé à l'affectation #<?php echo $relatedId; ?> 
                        (Étudiant: <?php echo h($relatedInfo['student_first_name'] . ' ' . $relatedInfo['student_last_name']); ?>, 
                        Tuteur: <?php echo h($relatedInfo['teacher_first_name'] . ' ' . $relatedInfo['teacher_last_name']); ?>).
                        <?php elseif ($relatedType === 'internship'): ?>
                        Ce document sera associé au stage "<?php echo h($relatedInfo['title']); ?>" 
                        (Entreprise: <?php echo h($relatedInfo['company_name']); ?>).
                        <?php elseif ($relatedType === 'student'): ?>
                        Ce document sera associé à l'étudiant <?php echo h($relatedInfo['first_name'] . ' ' . $relatedInfo['last_name']); ?>
                        (<?php echo h($relatedInfo['program']); ?>).
                        <?php elseif ($relatedType === 'teacher'): ?>
                        Ce document sera associé au tuteur <?php echo h($relatedInfo['first_name'] . ' ' . $relatedInfo['last_name']); ?>
                        (<?php echo h($relatedInfo['specialty']); ?>).
                        <?php endif; ?>
                    </p>
                </div>
                <?php endif; ?>
                
                <div class="d-flex justify-content-end mt-4">
                    <button type="reset" class="btn btn-secondary me-2">Réinitialiser</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Scripts spécifiques -->
<script>
    // Mise à jour des formats acceptés selon la catégorie
    document.getElementById('category').addEventListener('change', function() {
        const category = this.value;
        const allowedFormats = document.getElementById('allowedFormats');
        
        switch (category) {
            case 'cv':
            case 'report':
            case 'agreement':
            case 'evaluation':
                allowedFormats.textContent = 'PDF, DOC, DOCX';
                break;
                
            case 'image':
                allowedFormats.textContent = 'JPG, PNG, GIF';
                break;
                
            case 'presentation':
                allowedFormats.textContent = 'PDF, PPT, PPTX';
                break;
                
            case 'other':
            default:
                allowedFormats.textContent = 'PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, JPG, PNG, GIF, ZIP, RAR, TXT';
                break;
        }
    });
    
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

<?php
// Fonction pour déterminer l'URL de retour selon le contexte
function getBackUrl($type, $id) {
    switch ($type) {
        case 'assignment':
            return '/tutoring/views/admin/assignments/show.php?id=' . $id;
        case 'internship':
            return '/tutoring/views/admin/internships/show.php?id=' . $id;
        case 'student':
            return '/tutoring/views/admin/students/show.php?id=' . $id;
        case 'teacher':
            return '/tutoring/views/admin/teachers/show.php?id=' . $id;
        default:
            return '/tutoring/views/admin/documents.php';
    }
}
?>

<?php require_once __DIR__ . '/../../common/footer.php'; ?>