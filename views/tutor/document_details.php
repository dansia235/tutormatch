<?php
/**
 * Vue pour afficher les détails d'un document (Tuteur)
 */

// Initialiser les variables
$pageTitle = 'Détails du document';
$currentPage = 'documents';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../includes/init.php';

// Vérifier les permissions
requireRole('teacher');

// Vérifier l'ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage('error', 'ID de document invalide');
    redirect('/tutoring/views/tutor/documents.php');
}

// S'assurer que la connexion à la base de données est disponible
if (!isset($db) || $db === null) {
    $db = getDBConnection();
}

// Récupérer les modèles nécessaires
$documentModel = new Document($db);
$userModel = new User($db);
$teacherModel = new Teacher($db);
$studentModel = new Student($db);
$assignmentModel = new Assignment($db);

// Récupérer le document
$documentId = $_GET['id'];
$document = $documentModel->getById($documentId);

if (!$document) {
    setFlashMessage('error', 'Document non trouvé');
    redirect('/tutoring/views/tutor/documents.php');
}

// Récupérer le tuteur connecté
$currentUserId = $_SESSION['user_id'];
$teacher = $teacherModel->getByUserId($currentUserId);

if (!$teacher) {
    setFlashMessage('error', 'Profil tuteur non trouvé');
    redirect('/tutoring/index.php');
}

// Vérifier les autorisations d'accès
$canAccess = false;

// Si l'utilisateur est le propriétaire du document
if ($document['user_id'] == $currentUserId) {
    $canAccess = true;
} else {
    // Récupérer les affectations de ce tuteur
    $assignments = $teacherModel->getAssignments($teacher['id']);
    
    // Vérifier si le document appartient à un étudiant sous sa tutelle
    $documentOwner = $userModel->getById($document['user_id']);
    
    if ($documentOwner) {
        $student = $studentModel->getByUserId($documentOwner['id']);
        
        if ($student) {
            foreach ($assignments as $assignment) {
                if ($assignment['student_id'] == $student['id']) {
                    $canAccess = true;
                    break;
                }
            }
        }
    }
    
    // Vérifier également si le document est lié à une affectation de ce tuteur
    if (!$canAccess && !empty($document['assignment_id'])) {
        $assignment = $assignmentModel->getById($document['assignment_id']);
        
        if ($assignment && $assignment['teacher_id'] == $teacher['id']) {
            $canAccess = true;
        }
    }
}

// Refuser l'accès si l'utilisateur n'est pas autorisé
if (!$canAccess) {
    include_once __DIR__ . '/../../access-denied.php';
    exit;
}

// Récupérer les informations sur l'utilisateur qui a téléversé le document
$owner = $userModel->getById($document['user_id']);

// Récupérer les informations sur l'affectation si disponible
$assignmentInfo = null;
if (!empty($document['assignment_id'])) {
    $assignment = $assignmentModel->getById($document['assignment_id']);
    
    if ($assignment) {
        $student = $studentModel->getById($assignment['student_id']);
        $assignmentInfo = [
            'id' => $assignment['id'],
            'student_name' => $student ? $student['first_name'] . ' ' . $student['last_name'] : 'Inconnu',
            'student_id' => $student ? $student['id'] : null
        ];
    }
}

// Inclure l'en-tête
include_once __DIR__ . '/../common/header.php';
?>

<div class="container-fluid">
    <!-- En-tête de page avec actions -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-0">
                <i class="bi bi-file-earmark me-2"></i>Détails du document
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/tutoring/views/tutor/dashboard.php">Tableau de bord</a></li>
                    <li class="breadcrumb-item"><a href="/tutoring/views/tutor/documents.php">Documents</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?php echo h($document['title']); ?></li>
                </ol>
            </nav>
        </div>
        
        <div class="btn-group" role="group">
            <a href="/tutoring/views/tutor/documents.php<?php echo $assignmentInfo ? '?student_id=' . $assignmentInfo['student_id'] : ''; ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Retour
            </a>
            <a href="/tutoring/<?php echo h($document['file_path']); ?>" class="btn btn-outline-primary" download>
                <i class="bi bi-download me-2"></i>Télécharger
            </a>
        </div>
    </div>
    
    <div class="row">
        <!-- Informations sur le document -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Informations sur le document</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6 class="fw-bold"><?php echo h($document['title']); ?></h6>
                        <?php if (!empty($document['description'])): ?>
                        <p class="text-muted mb-0"><?php echo h($document['description']); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <p class="mb-1"><strong>Type :</strong> 
                            <?php 
                            $typeLabels = [
                                'report' => '<span class="badge bg-info">Rapport</span>',
                                'contract' => '<span class="badge bg-primary">Contrat</span>',
                                'administrative' => '<span class="badge bg-secondary">Administratif</span>',
                                'other' => '<span class="badge bg-light text-dark">Autre</span>'
                            ];
                            echo $typeLabels[$document['type']] ?? '<span class="badge bg-light text-dark">Autre</span>';
                            ?>
                        </p>
                        <p class="mb-1"><strong>Statut :</strong> 
                            <?php 
                            // Afficher le statut avec une étiquette colorée
                            $statusClass = match($document['status']) {
                                'pending' => 'bg-warning',
                                'approved' => 'bg-success',
                                'rejected' => 'bg-danger',
                                'active' => 'bg-success',
                                'draft' => 'bg-secondary',
                                default => 'bg-secondary'
                            };
                            
                            $statusLabel = match($document['status']) {
                                'pending' => 'En attente',
                                'approved' => 'Validé',
                                'rejected' => 'Rejeté',
                                'active' => 'Actif',
                                'draft' => 'Brouillon',
                                default => ucfirst($document['status'])
                            };
                            
                            echo '<span class="badge ' . $statusClass . '">' . $statusLabel . '</span>';
                            ?>
                        </p>
                        <p class="mb-1"><strong>Téléversé par :</strong> <?php echo $owner ? h($owner['username']) : 'Inconnu'; ?></p>
                        <p class="mb-1"><strong>Date de téléversement :</strong> <?php echo formatDate($document['upload_date']); ?></p>
                        <p class="mb-1"><strong>Type de fichier :</strong> <?php echo h($document['file_type'] ?? 'Non spécifié'); ?></p>
                        <p class="mb-1"><strong>Taille :</strong> <?php echo formatFileSize($document['file_size'] ?? 0); ?></p>
                    </div>
                    
                    <?php if (!empty($document['feedback'])): ?>
                    <div class="mb-3">
                        <h6 class="fw-bold">Commentaires</h6>
                        <p class="mb-0"><?php echo h($document['feedback']); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($assignmentInfo): ?>
                    <div class="mb-0">
                        <h6 class="fw-bold">Affectation associée</h6>
                        <p class="mb-0"><strong>Étudiant :</strong> <?php echo h($assignmentInfo['student_name']); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($document['user_id'] != $currentUserId && ($document['status'] === 'pending' || $document['status'] === 'submitted' || $document['status'] === 'draft')): ?>
            <!-- Actions sur le document -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Actions</h5>
                </div>
                <div class="card-body">
                    <form action="/tutoring/views/tutor/documents.php" method="POST">
                        <input type="hidden" name="document_id" value="<?php echo $document['id']; ?>">
                        
                        <div class="mb-3">
                            <label for="feedback" class="form-label">Commentaire</label>
                            <textarea class="form-control" id="feedback" name="feedback" rows="3" placeholder="Ajouter un commentaire..."><?php echo h($document['feedback'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" name="update_document_status" class="btn btn-success" onclick="document.querySelector('input[name=\'document_status\']').value='approved';">
                                <i class="bi bi-check-lg me-2"></i>Approuver
                            </button>
                            <button type="submit" name="update_document_status" class="btn btn-danger" onclick="document.querySelector('input[name=\'document_status\']').value='rejected';">
                                <i class="bi bi-x-lg me-2"></i>Rejeter
                            </button>
                        </div>
                        
                        <input type="hidden" name="document_status" value="approved">
                    </form>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($document['user_id'] == $currentUserId): ?>
            <!-- Actions pour les documents personnels -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteDocumentModal">
                            <i class="bi bi-trash me-2"></i>Supprimer
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Prévisualisation du document -->
        <div class="col-md-8 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Prévisualisation</h5>
                </div>
                <div class="card-body p-0">
                    <?php
                    $filePath = '/tutoring/' . $document['file_path'];
                    $fileType = $document['file_type'] ?? '';
                    $fileExtension = pathinfo($document['file_path'], PATHINFO_EXTENSION);
                    
                    // Prévisualisation adaptée au type de fichier
                    if (strpos($fileType, 'pdf') !== false || $fileExtension === 'pdf') {
                        echo '<embed src="' . $filePath . '" type="application/pdf" width="100%" height="600px">';
                    } 
                    else if (strpos($fileType, 'image') !== false || in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif'])) {
                        echo '<div class="text-center"><img src="' . $filePath . '" class="img-fluid" alt="' . h($document['title']) . '" style="max-height: 600px;"></div>';
                    }
                    else if (in_array($fileExtension, ['json', 'txt', 'md', 'csv'])) {
                        // Pour les fichiers texte, essayer d'afficher le contenu
                        $fullPath = $_SERVER['DOCUMENT_ROOT'] . $filePath;
                        if (file_exists($fullPath) && filesize($fullPath) < 1000000) { // Limiter à 1MB
                            $content = file_get_contents($fullPath);
                            echo '<pre class="m-3">' . h($content) . '</pre>';
                        } else {
                            echo '<div class="alert alert-info m-3">Le contenu de ce fichier ne peut pas être affiché directement.</div>';
                        }
                    }
                    else {
                        // Pour les autres types de fichiers (doc, xls, etc.)
                        echo '<div class="alert alert-info m-3">
                            <i class="bi bi-info-circle me-2"></i>
                            La prévisualisation n\'est pas disponible pour ce type de fichier. 
                            Veuillez télécharger le document pour le consulter.
                        </div>
                        <div class="text-center my-5">
                            <i class="bi bi-file-earmark" style="font-size: 5rem; color: #adb5bd;"></i>
                            <p class="mt-3">Type de fichier: ' . h($fileType) . '</p>
                            <a href="' . $filePath . '" class="btn btn-primary" download>
                                <i class="bi bi-download me-2"></i>Télécharger le fichier
                            </a>
                        </div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de suppression de document -->
<div class="modal fade" id="deleteDocumentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/tutoring/views/tutor/documents.php" method="POST">
                <div class="modal-body">
                    <p>Êtes-vous sûr de vouloir supprimer le document <strong><?php echo h($document['title']); ?></strong> ?</p>
                    <p class="text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Cette action est irréversible.</p>
                    <input type="hidden" name="document_id" value="<?php echo $document['id']; ?>">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" name="delete_document" class="btn btn-danger">Supprimer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
/**
 * Fonction pour formater la taille des fichiers
 * @param int|null $bytes Taille en octets
 * @return string Taille formatée
 */
function formatFileSize($bytes) {
    if ($bytes === null || $bytes === 0 || empty($bytes)) {
        return '0 Bytes';
    }
    
    $bytes = max(0, (float)$bytes);
    if ($bytes === 0) {
        return '0 Bytes';
    }
    
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    $i = floor(log($bytes) / log($k));
    
    $i = min(count($sizes) - 1, max(0, $i));
    
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}

// Inclure le pied de page
include_once __DIR__ . '/../common/footer.php';
?>