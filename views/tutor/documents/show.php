<?php
/**
 * Vue pour afficher les détails d'un document (tuteur)
 */

// Initialiser les variables
$pageTitle = 'Détails du document';
$currentPage = 'documents';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../../includes/init.php';

// Vérifier les permissions
requireRole('teacher');

// Vérifier l'ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage('error', 'ID de document invalide');
    redirect('/tutoring/views/tutor/documents/index.php');
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

// Récupérer directement le document sans utiliser le contrôleur pour éviter la récursion
$documentModel = new Document($db);
$document = $documentModel->getById($_GET['id']);

if (!$document) {
    setFlashMessage('error', 'Document non trouvé');
    redirect('/tutoring/views/tutor/documents/index.php');
    exit;
}

// Vérifier les autorisations de visibilité
$visibility = isset($document['visibility']) ? $document['visibility'] : 'private';
$userId = isset($document['user_id']) ? $document['user_id'] : 0;

// Un tuteur ne peut voir que ses propres documents et les documents des étudiants qu'il supervise
if ($visibility === 'private' && $userId !== $_SESSION['user_id']) {
    // Vérifier si le document est lié à un étudiant supervisé par ce tuteur
    $teacherModel = new Teacher($db);
    $teacher = $teacherModel->getByUserId($_SESSION['user_id']);
    
    if (!$teacher) {
        setFlashMessage('error', "Vous n'avez pas accès à ce document");
        redirect('/tutoring/views/tutor/dashboard.php');
        exit;
    }
    
    // Vérifier si l'étudiant est supervisé par ce tuteur
    $isSupervising = false;
    
    // Si le document est lié à une affectation, vérifier si le tuteur est impliqué
    if (isset($document['assignment_id']) && !empty($document['assignment_id'])) {
        $assignmentModel = new Assignment($db);
        $assignment = $assignmentModel->getById($document['assignment_id']);
        
        if ($assignment && $assignment['teacher_id'] == $teacher['id']) {
            $isSupervising = true;
        }
    }
    
    if (!$isSupervising) {
        setFlashMessage('error', "Vous n'avez pas accès à ce document");
        redirect('/tutoring/views/tutor/dashboard.php');
        exit;
    }
}

// Définir la variable $isDirectAccess pour indiquer que ce fichier est accédé directement
$isDirectAccess = !defined('CONTROLLER_INCLUDED');

// Si c'est un accès direct (pas via le contrôleur), récupérer les données nécessaires
if ($isDirectAccess) {
    // Instancier le contrôleur
    $documentController = new DocumentController($db);
    
    // Nous utilisons une approche différente pour éviter les problèmes de récursion
    global $relatedInfo, $userInfo;
    
    // Obtenir les informations sur l'utilisateur qui a téléversé le document
    $userModel = new User($db);
    $userInfo = isset($document['user_id']) ? $userModel->getById($document['user_id']) : null;
    
    // Initialiser les informations associées
    $relatedInfo = null;
    
    // Traiter les informations associées
    if (isset($document['related_type']) && !empty($document['related_type']) && 
        isset($document['related_id']) && !empty($document['related_id'])) {
        
        switch ($document['related_type']) {
            case 'assignment':
                $assignmentModel = new Assignment($db);
                $relatedInfo = $assignmentModel->getById($document['related_id']);
                break;
            case 'internship':
                $internshipModel = new Internship($db);
                $relatedInfo = $internshipModel->getById($document['related_id']);
                break;
            case 'student':
                $studentModel = new Student($db);
                $relatedInfo = $studentModel->getById($document['related_id']);
                break;
        }
    }
}
?>

<?php require_once __DIR__ . '/../../common/header.php'; ?>

<div class="container-fluid">
    <!-- En-tête de page avec actions -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-0">
                <i class="bi bi-file-earmark-text me-2"></i>Détails du document
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/tutoring/views/tutor/dashboard.php">Tableau de bord</a></li>
                    <li class="breadcrumb-item"><a href="/tutoring/views/tutor/documents/index.php">Documents</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?php echo h(truncate($document['title'], 30)); ?></li>
                </ol>
            </nav>
        </div>
        
        <div class="btn-group" role="group">
            <?php if ($document['user_id'] == $_SESSION['user_id']): ?>
            <a href="/tutoring/views/tutor/documents/edit.php?id=<?php echo $document['id']; ?>" class="btn btn-outline-primary">
                <i class="bi bi-pencil me-2"></i>Modifier
            </a>
            <?php endif; ?>
            <a href="/tutoring/views/tutor/documents/download.php?id=<?php echo $document['id']; ?>" class="btn btn-outline-info">
                <i class="bi bi-download me-2"></i>Télécharger
            </a>
            <a href="/tutoring/views/tutor/documents/index.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Retour
            </a>
        </div>
    </div>
    
    <div class="row">
        <!-- Informations sur le document -->
        <div class="col-md-8 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Informations sur le document</h5>
                    <?php 
                    // Adapter pour utiliser type au lieu de category
                    $typeLabels = [
                        'contract' => '<span class="badge bg-primary">Contrat</span>',
                        'report' => '<span class="badge bg-success">Rapport</span>',
                        'evaluation' => '<span class="badge bg-warning">Évaluation</span>',
                        'certificate' => '<span class="badge bg-info">Certificat</span>',
                        'other' => '<span class="badge bg-dark">Autre</span>'
                    ];
                    $type = $document['type'] ?? 'other';
                    echo $typeLabels[$type] ?? '<span class="badge bg-secondary">Inconnu</span>';
                    ?>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <div class="d-flex align-items-center mb-3">
                            <?php 
                            $iconClass = 'bi-file-text';
                            $bgColor = 'bg-secondary';
                            
                            switch ($type) {
                                case 'contract':
                                    $iconClass = 'bi-file-earmark-text';
                                    $bgColor = 'bg-primary';
                                    break;
                                case 'report':
                                    $iconClass = 'bi-file-text';
                                    $bgColor = 'bg-success';
                                    break;
                                case 'evaluation':
                                    $iconClass = 'bi-file-check';
                                    $bgColor = 'bg-warning';
                                    break;
                                case 'certificate':
                                    $iconClass = 'bi-file-earmark-check';
                                    $bgColor = 'bg-info';
                                    break;
                            }
                            ?>
                            <div class="rounded-circle <?php echo $bgColor; ?> p-3 me-3 text-white">
                                <i class="bi <?php echo $iconClass; ?> fs-4"></i>
                            </div>
                            <div>
                                <h4><?php echo h($document['title']); ?></h4>
                                <p class="text-muted mb-0">
                                    Téléversé le <?php echo formatDate($document['upload_date'], 'd/m/Y à H:i'); ?> 
                                    par <?php echo h($document['first_name'] . ' ' . $document['last_name']); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (isset($document['description']) && $document['description']): ?>
                    <div class="mb-4">
                        <h6 class="fw-bold">Description</h6>
                        <div class="card-text">
                            <?php echo nl2br(h($document['description'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <h6 class="fw-bold">Détails du fichier</h6>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>Type de fichier</span>
                                    <span class="badge bg-secondary"><?php echo h($document['file_type']); ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>Taille</span>
                                    <span class="badge bg-secondary">
                                        <?php 
                                        // Formater la taille du fichier
                                        $size = $document['file_size'];
                                        $units = ['B', 'KB', 'MB', 'GB'];
                                        $unitIndex = 0;
                                        
                                        while ($size >= 1024 && $unitIndex < count($units) - 1) {
                                            $size /= 1024;
                                            $unitIndex++;
                                        }
                                        
                                        echo round($size, 2) . ' ' . $units[$unitIndex];
                                        ?>
                                    </span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>Type</span>
                                    <?php echo $typeLabels[$type] ?? '<span class="badge bg-secondary">Inconnu</span>'; ?>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>Visibilité</span>
                                    <?php 
                                    $visibilityLabels = [
                                        'private' => '<span class="badge bg-danger">Privé</span>',
                                        'public' => '<span class="badge bg-success">Public</span>',
                                        'restricted' => '<span class="badge bg-warning">Restreint</span>'
                                    ];
                                    $visibility = $document['visibility'] ?? 'private';
                                    echo $visibilityLabels[$visibility] ?? '<span class="badge bg-secondary">Inconnu</span>';
                                    ?>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>Statut</span>
                                    <?php 
                                    $statusLabels = [
                                        'draft' => '<span class="badge bg-secondary">Brouillon</span>',
                                        'submitted' => '<span class="badge bg-primary">Soumis</span>',
                                        'approved' => '<span class="badge bg-success">Approuvé</span>',
                                        'rejected' => '<span class="badge bg-danger">Rejeté</span>'
                                    ];
                                    $status = $document['status'] ?? 'draft';
                                    echo $statusLabels[$status] ?? '<span class="badge bg-secondary">Inconnu</span>';
                                    ?>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <?php if (isset($document['assignment_id']) && $document['assignment_id']): ?>
                            <h6 class="fw-bold">Affectation associée</h6>
                            <div class="card">
                                <div class="card-body">
                                    <?php 
                                    $assignmentModel = new Assignment($db);
                                    $assignment = $assignmentModel->getById($document['assignment_id']);
                                    
                                    if ($assignment):
                                        $studentModel = new Student($db);
                                        $student = $studentModel->getById($assignment['student_id']);
                                        
                                        $internshipModel = new Internship($db);
                                        $internship = $internshipModel->getById($assignment['internship_id']);
                                    ?>
                                    <h6 class="card-subtitle mb-2 text-muted">Affectation #<?php echo $assignment['id']; ?></h6>
                                    <p class="mb-1">
                                        <strong>Étudiant:</strong> <?php echo h($student['first_name'] . ' ' . $student['last_name']); ?>
                                    </p>
                                    <p class="mb-1">
                                        <strong>Stage:</strong> <?php echo h($internship['title']); ?>
                                    </p>
                                    <a href="/tutoring/views/tutor/assignments/show.php?id=<?php echo $assignment['id']; ?>" class="btn btn-sm btn-outline-primary mt-2">
                                        <i class="bi bi-arrow-right me-1"></i>Voir l'affectation
                                    </a>
                                    <?php else: ?>
                                    <p class="text-muted mb-0">Information d'affectation non disponible.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Aperçu du document si c'est une image -->
                            <?php if (isset($document['file_type']) && strpos($document['file_type'], 'image') !== false): ?>
                            <h6 class="fw-bold mt-3">Aperçu</h6>
                            <div class="card">
                                <div class="card-body text-center">
                                    <img src="<?php echo $document['file_path']; ?>" alt="<?php echo h($document['title']); ?>" class="img-fluid" style="max-height: 300px;">
                                </div>
                            </div>
                            <?php elseif (isset($document['file_type']) && strpos($document['file_type'], 'pdf') !== false): ?>
                            <h6 class="fw-bold mt-3">Aperçu</h6>
                            <div class="card">
                                <div class="card-body text-center">
                                    <p class="text-muted mb-2">Aperçu PDF non disponible.</p>
                                    <a href="/tutoring/views/tutor/documents/download.php?id=<?php echo $document['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-download me-1"></i>Télécharger pour voir
                                    </a>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="mb-3 text-muted">
                        <small>
                            <i class="bi bi-clock me-1"></i>Créé le <?php echo formatDate($document['upload_date'], 'd/m/Y H:i'); ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Informations sur l'utilisateur et actions -->
        <div class="col-md-4 mb-4">
            <!-- Carte de l'utilisateur -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Utilisateur</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar-lg me-3">
                            <?php echo strtoupper(substr($document['first_name'], 0, 1) . substr($document['last_name'], 0, 1)); ?>
                        </div>
                        <div>
                            <h5 class="mb-0"><?php echo h($document['first_name'] . ' ' . $document['last_name']); ?></h5>
                            <p class="text-muted mb-0"><?php echo h(ucfirst($document['role'])); ?></p>
                        </div>
                    </div>
                    
                    <p class="text-muted small">
                        Cet utilisateur a téléversé ce document le <?php echo formatDate($document['upload_date'], 'd/m/Y à H:i'); ?>.
                    </p>
                </div>
            </div>
            
            <!-- Carte des actions -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="/tutoring/views/tutor/documents/download.php?id=<?php echo $document['id']; ?>" class="btn btn-primary">
                            <i class="bi bi-download me-2"></i>Télécharger le document
                        </a>
                        
                        <?php if ($document['user_id'] == $_SESSION['user_id']): ?>
                        <a href="/tutoring/views/tutor/documents/edit.php?id=<?php echo $document['id']; ?>" class="btn btn-outline-primary">
                            <i class="bi bi-pencil me-2"></i>Modifier le document
                        </a>
                        
                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                            <i class="bi bi-trash me-2"></i>Supprimer le document
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmation de suppression -->
<?php if ($document['user_id'] == $_SESSION['user_id']): ?>
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer le document <strong><?php echo h($document['title']); ?></strong> ?</p>
                <p class="text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Cette action est irréversible et supprimera définitivement le fichier.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form action="/tutoring/views/tutor/documents/delete.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="id" value="<?php echo $document['id']; ?>">
                    <button type="submit" class="btn btn-danger">Supprimer</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../common/footer.php'; ?>