<?php
/**
 * Vue pour modifier un document
 */

// Initialiser les variables
$pageTitle = 'Modifier un document';
$currentPage = 'documents';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../../includes/init.php';

// Vérifier les permissions
requireRole(['admin', 'coordinator']);

// Vérifier l'ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage('error', 'ID de document invalide');
    redirect('/tutoring/views/admin/documents/index.php');
}

// On s'assure d'avoir accès à la base de données
if (!isset($db) || !$db) {
    // Si $db n'est pas défini, on essaie de le récupérer à nouveau
    try {
        $db = getDBConnection();
    } catch (Exception $e) {
        die("Erreur critique: Impossible de se connecter à la base de données.");
    }
}

// Récupérer directement le document sans passer par le contrôleur
$documentModel = new Document($db);
$document = $documentModel->getById($_GET['id']);

if (!$document) {
    setFlashMessage('error', 'Document non trouvé');
    redirect('/tutoring/views/admin/documents/index.php');
    exit;
}

// Récupérer les anciennes données du formulaire en cas d'erreur
$formData = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']);

// Récupérer les erreurs du formulaire
$formErrors = $_SESSION['form_errors'] ?? [];
unset($_SESSION['form_errors']);

// Préparer les valeurs par défaut
if (empty($formData)) {
    $formData = [
        'title' => $document['title'] ?? '',
        'description' => $document['description'] ?? '',
        'category' => $document['category'] ?? 'other',
        'visibility' => $document['visibility'] ?? 'private',
        'status' => $document['status'] ?? 'active'
    ];
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
?>

<?php require_once __DIR__ . '/../../common/header.php'; ?>

<div class="container-fluid">
    <!-- En-tête de page avec actions -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-0">
                <i class="bi bi-file-earmark-text me-2"></i>Modifier un document
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/tutoring/views/admin/dashboard.php">Tableau de bord</a></li>
                    <li class="breadcrumb-item"><a href="/tutoring/views/admin/documents/index.php">Documents</a></li>
                    <li class="breadcrumb-item"><a href="/tutoring/views/admin/documents/show.php?id=<?php echo isset($document['id']) ? $document['id'] : $_GET['id']; ?>"><?php echo isset($document['title']) ? h(truncate($document['title'], 30)) : 'Document'; ?></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Modifier</li>
                </ol>
            </nav>
        </div>
        
        <div class="btn-group">
            <a href="/tutoring/views/admin/documents/show.php?id=<?php echo isset($document['id']) ? $document['id'] : $_GET['id']; ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Retour aux détails
            </a>
            <a href="/tutoring/views/admin/documents/index.php" class="btn btn-outline-secondary">
                <i class="bi bi-list me-2"></i>Liste des documents
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
    
    <!-- Formulaire de modification de document -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Modifier le document</h5>
            <?php 
            $categoryLabels = [
                'cv' => '<span class="badge bg-primary">CV</span>',
                'report' => '<span class="badge bg-success">Rapport</span>',
                'agreement' => '<span class="badge bg-info">Convention</span>',
                'evaluation' => '<span class="badge bg-warning">Évaluation</span>',
                'image' => '<span class="badge bg-danger">Image</span>',
                'presentation' => '<span class="badge bg-secondary">Présentation</span>',
                'other' => '<span class="badge bg-dark">Autre</span>'
            ];
            $category = isset($document['category']) ? $document['category'] : 'other';
            echo $categoryLabels[$category] ?? '<span class="badge bg-secondary">Inconnu</span>';
            ?>
        </div>
        <div class="card-body">
            <form action="/tutoring/views/admin/documents/update.php" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="id" value="<?php echo isset($document['id']) ? $document['id'] : $_GET['id']; ?>">
                
                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                        <label for="title" class="form-label">Titre du document <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" value="<?php echo h($formData['title']); ?>" required>
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
                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo h($formData['description']); ?></textarea>
                    <div class="form-text">Une description détaillée du document (optionnel).</div>
                </div>
                
                <div class="mb-4">
                    <label for="document" class="form-label">Nouveau fichier (optionnel)</label>
                    <input type="file" class="form-control" id="document" name="document">
                    <div class="form-text">
                        Laissez ce champ vide pour conserver le fichier actuel.<br>
                        Formats acceptés: <span id="allowedFormats">PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, JPG, PNG, GIF, ZIP, RAR, TXT</span><br>
                        Taille maximale: 10 Mo
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                        <label for="visibility" class="form-label">Visibilité</label>
                        <select class="form-select" id="visibility" name="visibility">
                            <option value="private" <?php echo ($formData['visibility'] === 'private') ? 'selected' : ''; ?>>Privé (visible uniquement par vous et les administrateurs)</option>
                            <option value="restricted" <?php echo ($formData['visibility'] === 'restricted') ? 'selected' : ''; ?>>Restreint (visible par les personnes concernées)</option>
                            <option value="public" <?php echo ($formData['visibility'] === 'public') ? 'selected' : ''; ?>>Public (visible par tous)</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="status" class="form-label">Statut</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active" <?php echo ($formData['status'] === 'active') ? 'selected' : ''; ?>>Actif</option>
                            <option value="archived" <?php echo ($formData['status'] === 'archived') ? 'selected' : ''; ?>>Archivé</option>
                        </select>
                    </div>
                </div>
                
                <!-- Fichier actuel -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="card-subtitle mb-0">Fichier actuel</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <?php 
                            $iconClass = 'bi-file';
                            $fileType = isset($document['file_type']) ? $document['file_type'] : '';
                            
                            if ($fileType) {
                                if (strpos($fileType, 'pdf') !== false) {
                                    $iconClass = 'bi-file-pdf';
                                } elseif (strpos($fileType, 'word') !== false) {
                                    $iconClass = 'bi-file-word';
                                } elseif (strpos($fileType, 'excel') !== false || strpos($fileType, 'sheet') !== false) {
                                    $iconClass = 'bi-file-excel';
                                } elseif (strpos($fileType, 'powerpoint') !== false || strpos($fileType, 'presentation') !== false) {
                                    $iconClass = 'bi-file-slides';
                                } elseif (strpos($fileType, 'image') !== false) {
                                    $iconClass = 'bi-file-image';
                                } elseif (strpos($fileType, 'zip') !== false || strpos($fileType, 'rar') !== false) {
                                    $iconClass = 'bi-file-zip';
                                } elseif (strpos($fileType, 'text') !== false) {
                                    $iconClass = 'bi-file-text';
                                }
                            }
                            ?>
                            <i class="bi <?php echo $iconClass; ?> me-3 fs-1"></i>
                            <div>
                                <p class="mb-1"><strong>Nom du fichier:</strong> <?php echo isset($document['file_path']) ? h(basename($document['file_path'])) : 'Non disponible'; ?></p>
                                <p class="mb-1"><strong>Type:</strong> <?php echo isset($document['file_type']) ? h($document['file_type']) : 'Non disponible'; ?></p>
                                <p class="mb-1">
                                    <strong>Taille:</strong> 
                                    <?php 
                                    // Formater la taille du fichier
                                    if (isset($document['file_size'])) {
                                        $size = $document['file_size'];
                                        $units = ['B', 'KB', 'MB', 'GB'];
                                        $unitIndex = 0;
                                        
                                        while ($size >= 1024 && $unitIndex < count($units) - 1) {
                                            $size /= 1024;
                                            $unitIndex++;
                                        }
                                        
                                        echo round($size, 2) . ' ' . $units[$unitIndex];
                                    } else {
                                        echo 'Non disponible';
                                    }
                                    ?>
                                </p>
                                <p class="mb-1"><strong>Téléversé le:</strong> <?php echo isset($document['upload_date']) ? formatDate($document['upload_date'], 'd/m/Y H:i') : 'Non disponible'; ?></p>
                            </div>
                        </div>
                        
                        <?php if (isset($document['file_type']) && strpos($document['file_type'], 'image') !== false): ?>
                        <div class="mt-3">
                            <img src="<?php echo isset($document['file_path']) ? h($document['file_path']) : ''; ?>" 
                                 alt="<?php echo isset($document['title']) ? h($document['title']) : 'Image'; ?>" 
                                 class="img-thumbnail" style="max-height: 200px;">
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="d-flex justify-content-end mt-4">
                    <a href="/tutoring/views/admin/documents/show.php?id=<?php echo isset($document['id']) ? $document['id'] : $_GET['id']; ?>" class="btn btn-secondary me-2">
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
    
    // Déclencher l'événement au chargement pour initialiser les formats
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('category').dispatchEvent(new Event('change'));
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

<?php require_once __DIR__ . '/../../common/footer.php'; ?>