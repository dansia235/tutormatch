<?php
/**
 * Vue pour la gestion des documents par le tuteur
 */

// Titre de la page
$pageTitle = 'Documents';
$currentPage = 'documents';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../includes/init.php';

// Vérifier que l'utilisateur est tuteur
requireRole('teacher');

// Récupérer le tuteur de la session
$userModel = new User($db);
$user = $userModel->getById($_SESSION['user_id']);

// Récupérer le modèle du tuteur
$teacherModel = new Teacher($db);
$teacher = $teacherModel->getByUserId($_SESSION['user_id']);

if (!$teacher) {
    setFlashMessage('error', 'Profil de tuteur non trouvé.');
    redirect('/tutoring/index.php');
}

// Récupérer les affectations d'étudiants pour ce tuteur
$assignments = $teacherModel->getAssignments($teacher['id']);

// Modèles nécessaires
$documentModel = new Document($db);
$studentModel = new Student($db);
$internshipModel = new Internship($db);

// Récupérer l'étudiant spécifique si un ID est fourni
$selectedStudent = null;
$studentDocuments = [];

if (isset($_GET['student_id']) && !empty($_GET['student_id'])) {
    $selectedStudent = $studentModel->getById($_GET['student_id']);
    
    if ($selectedStudent) {
        // Vérifier que l'étudiant est bien affecté à ce tuteur
        $isAssigned = false;
        foreach ($assignments as $assignment) {
            if ($assignment['student_id'] == $selectedStudent['id']) {
                $isAssigned = true;
                $currentAssignment = $assignment;
                break;
            }
        }
        
        if (!$isAssigned) {
            setFlashMessage('error', 'Cet étudiant n\'est pas sous votre tutorat.');
            redirect('/tutoring/views/tutor/documents.php');
        }
        
        // Récupérer les documents de l'étudiant
        $studentUser = $userModel->getById($selectedStudent['user_id']);
        if ($studentUser) {
            $studentDocuments = $documentModel->getByUserId($studentUser['id']);
        }
    }
}

// Filtres
$typeFilter = $_GET['type'] ?? 'all';
$statusFilter = $_GET['status'] ?? 'all';

// Récupérer tous les documents des étudiants assignés à ce tuteur
$allDocuments = [];
$documentsByStudent = [];

foreach ($assignments as $assignment) {
    $student = $studentModel->getById($assignment['student_id']);
    if (!$student) continue;
    
    $studentUser = $userModel->getById($student['user_id']);
    if (!$studentUser) continue;
    
    // Récupérer les documents de cet étudiant
    $docs = $documentModel->getByUserId($studentUser['id']);
    foreach ($docs as $doc) {
        $doc['student_name'] = $student['first_name'] . ' ' . $student['last_name'];
        $doc['student_id'] = $student['id'];
        $allDocuments[] = $doc;
        
        if (!isset($documentsByStudent[$student['id']])) {
            $documentsByStudent[$student['id']] = [];
        }
        $documentsByStudent[$student['id']][] = $doc;
    }
    
    // Récupérer également les documents liés à l'affectation
    $assignmentDocs = $documentModel->getByAssignmentId($assignment['id']);
    foreach ($assignmentDocs as $doc) {
        if ($doc['user_id'] == $_SESSION['user_id']) {
            $doc['student_name'] = $student['first_name'] . ' ' . $student['last_name'];
            $doc['student_id'] = $student['id'];
            $allDocuments[] = $doc;
            
            if (!isset($documentsByStudent[$student['id']])) {
                $documentsByStudent[$student['id']] = [];
            }
            $documentsByStudent[$student['id']][] = $doc;
        }
    }
}

// Appliquer les filtres
$filteredDocuments = [];
foreach ($allDocuments as $doc) {
    $typeMatch = $typeFilter == 'all' || $doc['type'] == $typeFilter;
    $statusMatch = $statusFilter == 'all' || $doc['status'] == $statusFilter;
    
    if ($typeMatch && $statusMatch) {
        if (!$selectedStudent || $doc['student_id'] == $selectedStudent['id']) {
            $filteredDocuments[] = $doc;
        }
    }
}

// Statistiques
$stats = [
    'total' => count($allDocuments),
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0,
    'report' => 0,
    'contract' => 0,
    'administrative' => 0,
    'other' => 0
];

foreach ($allDocuments as $doc) {
    if (isset($stats[$doc['status']])) {
        $stats[$doc['status']]++;
    }
    
    if (isset($stats[$doc['type']])) {
        $stats[$doc['type']]++;
    }
}

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Approbation ou rejet d'un document
    if (isset($_POST['update_document_status'])) {
        $documentId = $_POST['document_id'] ?? 0;
        $newStatus = $_POST['document_status'] ?? '';
        $feedback = $_POST['feedback'] ?? '';
        
        if ($documentId && in_array($newStatus, ['approved', 'rejected'])) {
            $updateData = [
                'status' => $newStatus,
                'feedback' => $feedback
            ];
            
            if ($documentModel->update($documentId, $updateData)) {
                setFlashMessage('success', 'Statut du document mis à jour avec succès.');
            } else {
                setFlashMessage('error', 'Erreur lors de la mise à jour du statut.');
            }
        }
        
        // Rediriger pour éviter les soumissions multiples
        $redirectUrl = '/tutoring/views/tutor/documents.php';
        if ($selectedStudent) {
            $redirectUrl .= '?student_id=' . $selectedStudent['id'];
        }
        redirect($redirectUrl);
    }
    
    // Téléversement d'un document
    if (isset($_POST['upload_document'])) {
        $targetStudentId = $_POST['target_student_id'] ?? 0;
        $assignmentId = $_POST['assignment_id'] ?? 0;
        
        // Vérifier que l'étudiant est bien sous le tutorat de ce tuteur
        $validTarget = false;
        foreach ($assignments as $assignment) {
            if ($assignment['student_id'] == $targetStudentId) {
                $validTarget = true;
                $assignmentId = $assignment['id'];
                break;
            }
        }
        
        if (!$validTarget) {
            setFlashMessage('error', 'Cible invalide pour le document.');
            redirect('/tutoring/views/tutor/documents.php');
        }
        
        // Vérifier si un fichier a été envoyé
        if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../uploads/documents/';
            
            // Créer le répertoire s'il n'existe pas
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Générer un nom de fichier unique
            $fileName = time() . '_' . basename($_FILES['document_file']['name']);
            $filePath = $uploadDir . $fileName;
            
            // Déplacer le fichier téléchargé
            if (move_uploaded_file($_FILES['document_file']['tmp_name'], $filePath)) {
                // Préparer les données pour l'enregistrement en BDD
                $documentData = [
                    'title' => $_POST['document_title'],
                    'description' => $_POST['document_description'] ?? null,
                    'file_path' => 'uploads/documents/' . $fileName,
                    'file_type' => $_FILES['document_file']['type'],
                    'file_size' => $_FILES['document_file']['size'],
                    'type' => $_POST['document_type'],
                    'user_id' => $_SESSION['user_id'],
                    'assignment_id' => $assignmentId,
                    'status' => 'approved' // Les documents des tuteurs sont automatiquement approuvés
                ];
                
                // Créer le document dans la base de données
                if ($documentModel->create($documentData)) {
                    // Notifier l'étudiant si demandé
                    if (isset($_POST['notify_student'])) {
                        // Code pour envoyer une notification à l'étudiant
                        // À implémenter selon le système de notification existant
                    }
                    
                    setFlashMessage('success', 'Document téléversé avec succès');
                    
                    $redirectUrl = '/tutoring/views/tutor/documents.php';
                    if ($selectedStudent) {
                        $redirectUrl .= '?student_id=' . $targetStudentId;
                    }
                    redirect($redirectUrl);
                } else {
                    setFlashMessage('error', 'Erreur lors de l\'enregistrement du document');
                }
            } else {
                setFlashMessage('error', 'Erreur lors du téléversement du fichier');
            }
        } else {
            setFlashMessage('error', 'Veuillez sélectionner un fichier valide');
        }
    }
    
    // Suppression d'un document
    if (isset($_POST['delete_document'])) {
        $documentId = $_POST['document_id'] ?? 0;
        
        if ($documentId) {
            // Vérifier que le document appartient au tuteur
            $document = $documentModel->getById($documentId);
            
            if ($document && $document['user_id'] == $_SESSION['user_id']) {
                // Supprimer le fichier physique
                $filePath = __DIR__ . '/../../' . $document['file_path'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                
                // Supprimer l'entrée en base de données
                if ($documentModel->delete($documentId)) {
                    setFlashMessage('success', 'Document supprimé avec succès');
                } else {
                    setFlashMessage('error', 'Erreur lors de la suppression du document');
                }
            } else {
                setFlashMessage('error', 'Vous n\'êtes pas autorisé à supprimer ce document');
            }
        }
        
        // Rediriger pour éviter les soumissions multiples
        $redirectUrl = '/tutoring/views/tutor/documents.php';
        if ($selectedStudent) {
            $redirectUrl .= '?student_id=' . $selectedStudent['id'];
        }
        redirect($redirectUrl);
    }
}

// Inclure l'en-tête
include_once __DIR__ . '/../common/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-folder2-open me-2"></i>Documents</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/tutoring/views/tutor/dashboard.php">Tableau de bord</a></li>
                    <?php if ($selectedStudent): ?>
                    <li class="breadcrumb-item"><a href="/tutoring/views/tutor/documents.php">Documents</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?php echo h($selectedStudent['first_name'] . ' ' . $selectedStudent['last_name']); ?></li>
                    <?php else: ?>
                    <li class="breadcrumb-item active" aria-current="page">Documents</li>
                    <?php endif; ?>
                </ol>
            </nav>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3 fade-in delay-1">
            <div class="card stat-card">
                <div class="value"><?php echo h($stats['total']); ?></div>
                <div class="label">Total</div>
                <div class="progress mt-2">
                    <div class="progress-bar" role="progressbar" style="width: 100%;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <small class="text-muted">Documents soumis</small>
            </div>
        </div>
        <div class="col-md-3 fade-in delay-2">
            <div class="card stat-card">
                <div class="value"><?php echo h($stats['pending']); ?></div>
                <div class="label">En attente</div>
                <div class="progress mt-2">
                    <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $stats['total'] > 0 ? h(($stats['pending'] / $stats['total']) * 100) : 0; ?>%;" aria-valuenow="<?php echo $stats['total'] > 0 ? h(($stats['pending'] / $stats['total']) * 100) : 0; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <small class="text-muted">Nécessitent votre validation</small>
            </div>
        </div>
        <div class="col-md-3 fade-in delay-3">
            <div class="card stat-card">
                <div class="value"><?php echo h($stats['approved']); ?></div>
                <div class="label">Validés</div>
                <div class="progress mt-2">
                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $stats['total'] > 0 ? h(($stats['approved'] / $stats['total']) * 100) : 0; ?>%;" aria-valuenow="<?php echo $stats['total'] > 0 ? h(($stats['approved'] / $stats['total']) * 100) : 0; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <small class="text-muted">Documents approuvés</small>
            </div>
        </div>
        <div class="col-md-3 fade-in delay-4">
            <div class="card stat-card">
                <div class="value"><?php echo h($stats['rejected']); ?></div>
                <div class="label">Rejetés</div>
                <div class="progress mt-2">
                    <div class="progress-bar bg-danger" role="progressbar" style="width: <?php echo $stats['total'] > 0 ? h(($stats['rejected'] / $stats['total']) * 100) : 0; ?>%;" aria-valuenow="<?php echo $stats['total'] > 0 ? h(($stats['rejected'] / $stats['total']) * 100) : 0; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <small class="text-muted">Documents non conformes</small>
            </div>
        </div>
    </div>
    
    <!-- Filters and Search -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <form method="get" class="row g-3">
                        <?php if ($selectedStudent): ?>
                        <input type="hidden" name="student_id" value="<?php echo h($selectedStudent['id']); ?>">
                        <?php else: ?>
                        <div class="col-md-4">
                            <label for="student_id" class="form-label">Étudiant</label>
                            <select name="student_id" id="student_id" class="form-select">
                                <option value="">Tous les étudiants</option>
                                <?php foreach ($assignments as $assignment): ?>
                                <option value="<?php echo h($assignment['student_id']); ?>" <?php echo isset($_GET['student_id']) && $_GET['student_id'] == $assignment['student_id'] ? 'selected' : ''; ?>>
                                    <?php echo h($assignment['student_first_name'] . ' ' . $assignment['student_last_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        <div class="col-md-4">
                            <label for="type" class="form-label">Type de document</label>
                            <select name="type" id="type" class="form-select">
                                <option value="all" <?php echo $typeFilter === 'all' ? 'selected' : ''; ?>>Tous les types</option>
                                <option value="report" <?php echo $typeFilter === 'report' ? 'selected' : ''; ?>>Rapports</option>
                                <option value="contract" <?php echo $typeFilter === 'contract' ? 'selected' : ''; ?>>Contrats</option>
                                <option value="administrative" <?php echo $typeFilter === 'administrative' ? 'selected' : ''; ?>>Documents administratifs</option>
                                <option value="other" <?php echo $typeFilter === 'other' ? 'selected' : ''; ?>>Autres</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="status" class="form-label">Statut</label>
                            <select name="status" id="status" class="form-select">
                                <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>Tous les statuts</option>
                                <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>En attente</option>
                                <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>Validés</option>
                                <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejetés</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="search" class="form-label">Recherche</label>
                            <input type="text" class="form-control" id="search" name="search" placeholder="Titre, nom..." value="<?php echo h($_GET['search'] ?? ''); ?>">
                        </div>
                        <div class="col-md-8 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">Filtrer</button>
                            <a href="<?php echo $selectedStudent ? '/tutoring/views/tutor/documents.php?student_id=' . $selectedStudent['id'] : '/tutoring/views/tutor/documents.php'; ?>" class="btn btn-outline-secondary me-2">Réinitialiser</a>
                            <button type="button" class="btn btn-success ms-auto" data-bs-toggle="modal" data-bs-target="#uploadDocumentModal">
                                <i class="bi bi-upload me-1"></i>Ajouter un document
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body d-flex flex-column justify-content-center">
                    <div class="mb-2">
                        <span class="badge bg-warning rounded-pill">■</span> En attente
                        <span class="badge bg-success rounded-pill ms-2">■</span> Validé
                        <span class="badge bg-danger rounded-pill ms-2">■</span> Rejeté
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Types:</small>
                        <span class="badge bg-info rounded-pill ms-2">Rapport</span>
                        <span class="badge bg-primary rounded-pill ms-2">Contrat</span>
                        <span class="badge bg-secondary rounded-pill ms-2">Administratif</span>
                    </div>
                    <p class="mb-0 text-muted small">Validez ou rejetez les documents soumis par vos étudiants, et partagez vos propres documents avec eux.</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Documents List -->
    <div class="row">
        <div class="col-12">
            <div class="card mb-4 fade-in">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><?php echo $selectedStudent ? 'Documents de ' . h($selectedStudent['first_name'] . ' ' . $selectedStudent['last_name']) : 'Tous les documents'; ?></span>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="documentsSort" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-sort-down"></i> Trier par
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="documentsSort">
                            <li><a class="dropdown-item active" href="#" data-sort="date-desc">Date (récent)</a></li>
                            <li><a class="dropdown-item" href="#" data-sort="date-asc">Date (ancien)</a></li>
                            <li><a class="dropdown-item" href="#" data-sort="name">Nom</a></li>
                            <li><a class="dropdown-item" href="#" data-sort="student">Étudiant</a></li>
                            <li><a class="dropdown-item" href="#" data-sort="type">Type</a></li>
                            <li><a class="dropdown-item" href="#" data-sort="status">Statut</a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($filteredDocuments)): ?>
                    <div class="alert alert-info" role="alert">
                        <i class="bi bi-info-circle-fill me-2"></i>Aucun document ne correspond à vos critères de recherche.
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover" id="documentsTable">
                            <thead>
                                <tr>
                                    <th>Document</th>
                                    <?php if (!$selectedStudent): ?>
                                    <th>Étudiant</th>
                                    <?php endif; ?>
                                    <th>Type</th>
                                    <th>Date</th>
                                    <th>Statut</th>
                                    <th>Taille</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($filteredDocuments as $doc): ?>
                                <tr class="document-row" data-document-type="<?php echo h($doc['type']); ?>" data-document-status="<?php echo h($doc['status']); ?>">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php
                                            // Déterminer l'icône en fonction du type de fichier
                                            $iconClass = 'bi-file-earmark text-secondary';
                                            $fileType = isset($doc['file_type']) ? (string)$doc['file_type'] : '';
                                            
                                            if ($fileType && strpos($fileType, 'pdf') !== false) {
                                                $iconClass = 'bi-file-earmark-pdf text-danger';
                                            } elseif ($fileType && (strpos($fileType, 'word') !== false || strpos($fileType, 'document') !== false)) {
                                                $iconClass = 'bi-file-earmark-word text-primary';
                                            } elseif ($fileType && (strpos($fileType, 'excel') !== false || strpos($fileType, 'sheet') !== false)) {
                                                $iconClass = 'bi-file-earmark-excel text-success';
                                            } elseif ($fileType && strpos($fileType, 'image') !== false) {
                                                $iconClass = 'bi-file-earmark-image text-info';
                                            } elseif ($fileType && (strpos($fileType, 'presentation') !== false || strpos($fileType, 'powerpoint') !== false)) {
                                                $iconClass = 'bi-file-earmark-slides text-warning';
                                            }
                                            ?>
                                            <i class="bi <?php echo $iconClass; ?> document-icon me-2"></i>
                                            <div>
                                                <strong><?php echo h($doc['title']); ?></strong>
                                                <?php if (!empty($doc['description'])): ?>
                                                <br><small class="text-muted"><?php echo h(substr($doc['description'], 0, 50)) . (strlen($doc['description']) > 50 ? '...' : ''); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <?php if (!$selectedStudent): ?>
                                    <td>
                                        <a href="/tutoring/views/tutor/documents.php?student_id=<?php echo h($doc['student_id']); ?>">
                                            <?php echo h($doc['student_name']); ?>
                                        </a>
                                    </td>
                                    <?php endif; ?>
                                    <td>
                                        <?php 
                                        $typeLabel = match($doc['type']) {
                                            'report' => '<span class="badge bg-info">Rapport</span>',
                                            'contract' => '<span class="badge bg-primary">Contrat</span>',
                                            'administrative' => '<span class="badge bg-secondary">Administratif</span>',
                                            default => '<span class="badge bg-light text-dark">Autre</span>'
                                        };
                                        echo $typeLabel;
                                        ?>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($doc['upload_date'])); ?></td>
                                    <td>
                                        <?php 
                                        $statusClass = match($doc['status']) {
                                            'pending' => 'bg-warning',
                                            'approved' => 'bg-success',
                                            'rejected' => 'bg-danger',
                                            default => 'bg-secondary'
                                        };
                                        
                                        $statusLabel = match($doc['status']) {
                                            'pending' => 'En attente',
                                            'approved' => 'Validé',
                                            'rejected' => 'Rejeté',
                                            default => ucfirst($doc['status'])
                                        };
                                        ?>
                                        <span class="badge <?php echo $statusClass; ?>"><?php echo $statusLabel; ?></span>
                                    </td>
                                    <td><?php echo formatFileSize($doc['file_size'] ?? null); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="/tutoring/<?php echo h($doc['file_path']); ?>" class="btn btn-sm btn-outline-primary" target="_blank" title="Visualiser">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="/tutoring/<?php echo h($doc['file_path']); ?>" class="btn btn-sm btn-outline-secondary" download title="Télécharger">
                                                <i class="bi bi-download"></i>
                                            </a>
                                            <?php if ($doc['status'] === 'pending' && $doc['user_id'] != $_SESSION['user_id']): ?>
                                            <button type="button" class="btn btn-sm btn-outline-success" 
                                                    onclick="approveDocument(<?php echo $doc['id']; ?>, '<?php echo h($doc['title']); ?>')" title="Approuver">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    onclick="rejectDocument(<?php echo $doc['id']; ?>, '<?php echo h($doc['title']); ?>')" title="Rejeter">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                            <?php endif; ?>
                                            <?php if ($doc['user_id'] == $_SESSION['user_id']): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    onclick="confirmDelete(<?php echo $doc['id']; ?>, '<?php echo h($doc['title']); ?>')" title="Supprimer">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
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
    </div>
    
    <?php if ($selectedStudent): ?>
    <!-- Document Categories for Selected Student -->
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4 fade-in">
                <div class="card-header">
                    <span>Documents requis</span>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <!-- Liste des documents obligatoires et leur statut -->
                        <?php
                        $requiredDocs = [
                            'convention' => [
                                'title' => 'Convention de stage',
                                'type' => 'contract',
                                'icon' => 'bi-file-earmark-text text-primary',
                                'found' => false
                            ],
                            'report_final' => [
                                'title' => 'Rapport final',
                                'type' => 'report',
                                'icon' => 'bi-file-earmark-richtext text-danger',
                                'found' => false
                            ],
                            'evaluation' => [
                                'title' => 'Évaluation entreprise',
                                'type' => 'administrative',
                                'icon' => 'bi-file-earmark-check text-success',
                                'found' => false
                            ]
                        ];
                        
                        // Vérifier quels documents sont présents
                        foreach ($studentDocuments as $doc) {
                            if ($doc['type'] === 'contract' && stripos($doc['title'], 'convention') !== false) {
                                $requiredDocs['convention']['found'] = true;
                                $requiredDocs['convention']['status'] = $doc['status'];
                                $requiredDocs['convention']['date'] = $doc['upload_date'];
                            } elseif ($doc['type'] === 'report' && (stripos($doc['title'], 'final') !== false || stripos($doc['title'], 'rapport final') !== false)) {
                                $requiredDocs['report_final']['found'] = true;
                                $requiredDocs['report_final']['status'] = $doc['status'];
                                $requiredDocs['report_final']['date'] = $doc['upload_date'];
                            } elseif ($doc['type'] === 'administrative' && (stripos($doc['title'], 'évaluation') !== false || stripos($doc['title'], 'evaluation') !== false)) {
                                $requiredDocs['evaluation']['found'] = true;
                                $requiredDocs['evaluation']['status'] = $doc['status'];
                                $requiredDocs['evaluation']['date'] = $doc['upload_date'];
                            }
                        }
                        
                        foreach ($requiredDocs as $key => $doc):
                        ?>
                        <div class="list-group-item list-group-item-action">
                            <div class="d-flex align-items-center">
                                <i class="bi <?php echo $doc['icon']; ?> me-3"></i>
                                <div>
                                    <strong><?php echo $doc['title']; ?></strong>
                                    <p class="mb-0 small text-muted">
                                        <?php
                                        if ($doc['found']) {
                                            echo 'Téléversé le ' . date('d/m/Y', strtotime($doc['date']));
                                            
                                            $statusBadge = match($doc['status']) {
                                                'pending' => '<span class="badge bg-warning ms-2">En attente</span>',
                                                'approved' => '<span class="badge bg-success ms-2">Validé</span>',
                                                'rejected' => '<span class="badge bg-danger ms-2">Rejeté</span>',
                                                default => ''
                                            };
                                            
                                            echo $statusBadge;
                                        } else {
                                            echo 'Non téléversé';
                                        }
                                        ?>
                                    </p>
                                </div>
                                <span class="ms-auto badge <?php echo $doc['found'] ? ($doc['status'] === 'approved' ? 'bg-success' : 'bg-warning') : 'bg-danger'; ?>">
                                    <?php echo $doc['found'] ? ($doc['status'] === 'approved' ? 'Complet' : 'À valider') : 'Manquant'; ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card mb-4 fade-in">
                <div class="card-header">
                    <span>Statistiques de documents</span>
                </div>
                <div class="card-body">
                    <?php if (empty($studentDocuments)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i> Cet étudiant n'a pas encore téléversé de documents.
                    </div>
                    <?php else: ?>
                    <?php
                    // Statistiques par type
                    $typeStats = [
                        'report' => 0,
                        'contract' => 0,
                        'administrative' => 0,
                        'other' => 0
                    ];
                    
                    // Statistiques par statut
                    $statusStats = [
                        'pending' => 0,
                        'approved' => 0,
                        'rejected' => 0
                    ];
                    
                    foreach ($studentDocuments as $doc) {
                        if (isset($typeStats[$doc['type']])) {
                            $typeStats[$doc['type']]++;
                        } else {
                            $typeStats['other']++;
                        }
                        
                        if (isset($statusStats[$doc['status']])) {
                            $statusStats[$doc['status']]++;
                        }
                    }
                    ?>
                    
                    <h5 class="mb-3">Documents par type</h5>
                    <div class="progress mb-3" style="height: 24px;">
                        <?php if ($typeStats['report'] > 0): ?>
                        <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo ($typeStats['report'] / count($studentDocuments)) * 100; ?>%;" 
                             aria-valuenow="<?php echo $typeStats['report']; ?>" aria-valuemin="0" aria-valuemax="<?php echo count($studentDocuments); ?>">
                            Rapports (<?php echo $typeStats['report']; ?>)
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($typeStats['contract'] > 0): ?>
                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo ($typeStats['contract'] / count($studentDocuments)) * 100; ?>%;" 
                             aria-valuenow="<?php echo $typeStats['contract']; ?>" aria-valuemin="0" aria-valuemax="<?php echo count($studentDocuments); ?>">
                            Contrats (<?php echo $typeStats['contract']; ?>)
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($typeStats['administrative'] > 0): ?>
                        <div class="progress-bar bg-secondary" role="progressbar" style="width: <?php echo ($typeStats['administrative'] / count($studentDocuments)) * 100; ?>%;" 
                             aria-valuenow="<?php echo $typeStats['administrative']; ?>" aria-valuemin="0" aria-valuemax="<?php echo count($studentDocuments); ?>">
                            Admin (<?php echo $typeStats['administrative']; ?>)
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($typeStats['other'] > 0): ?>
                        <div class="progress-bar bg-light text-dark" role="progressbar" style="width: <?php echo ($typeStats['other'] / count($studentDocuments)) * 100; ?>%;" 
                             aria-valuenow="<?php echo $typeStats['other']; ?>" aria-valuemin="0" aria-valuemax="<?php echo count($studentDocuments); ?>">
                            Autres (<?php echo $typeStats['other']; ?>)
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <h5 class="mt-4 mb-3">Statut des documents</h5>
                    <div class="progress mb-3" style="height: 24px;">
                        <?php if ($statusStats['approved'] > 0): ?>
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo ($statusStats['approved'] / count($studentDocuments)) * 100; ?>%;" 
                             aria-valuenow="<?php echo $statusStats['approved']; ?>" aria-valuemin="0" aria-valuemax="<?php echo count($studentDocuments); ?>">
                            Validés (<?php echo $statusStats['approved']; ?>)
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($statusStats['pending'] > 0): ?>
                        <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo ($statusStats['pending'] / count($studentDocuments)) * 100; ?>%;" 
                             aria-valuenow="<?php echo $statusStats['pending']; ?>" aria-valuemin="0" aria-valuemax="<?php echo count($studentDocuments); ?>">
                            En attente (<?php echo $statusStats['pending']; ?>)
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($statusStats['rejected'] > 0): ?>
                        <div class="progress-bar bg-danger" role="progressbar" style="width: <?php echo ($statusStats['rejected'] / count($studentDocuments)) * 100; ?>%;" 
                             aria-valuenow="<?php echo $statusStats['rejected']; ?>" aria-valuemin="0" aria-valuemax="<?php echo count($studentDocuments); ?>">
                            Rejetés (<?php echo $statusStats['rejected']; ?>)
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mt-4">
                        <p class="mb-1">Taux de validation: <strong><?php echo count($studentDocuments) > 0 ? round(($statusStats['approved'] / count($studentDocuments)) * 100) : 0; ?>%</strong></p>
                        <p class="mb-0">Documents en attente: <strong><?php echo $statusStats['pending']; ?></strong></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Upload Document Modal -->
<div class="modal fade" id="uploadDocumentModal" tabindex="-1" aria-labelledby="uploadDocumentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadDocumentModalLabel">Téléverser un document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="document_title" class="form-label">Titre du document</label>
                        <input type="text" class="form-control" id="document_title" name="document_title" required>
                    </div>
                    <div class="mb-3">
                        <label for="document_type" class="form-label">Type de document</label>
                        <select class="form-select" id="document_type" name="document_type" required>
                            <option value="report">Rapport</option>
                            <option value="contract">Contrat</option>
                            <option value="administrative">Document administratif</option>
                            <option value="other">Autre</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="target_student_id" class="form-label">Destinataire</label>
                        <select class="form-select" id="target_student_id" name="target_student_id" required>
                            <?php if ($selectedStudent): ?>
                            <option value="<?php echo h($selectedStudent['id']); ?>" selected>
                                <?php echo h($selectedStudent['first_name'] . ' ' . $selectedStudent['last_name']); ?>
                            </option>
                            <?php else: ?>
                            <?php foreach ($assignments as $assignment): ?>
                            <option value="<?php echo h($assignment['student_id']); ?>">
                                <?php echo h($assignment['student_first_name'] . ' ' . $assignment['student_last_name']); ?>
                            </option>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="document_file" class="form-label">Fichier</label>
                        <input type="file" class="form-control" id="document_file" name="document_file" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.jpg,.jpeg,.png" required>
                        <div class="form-text">Formats acceptés: PDF, Word, Excel, PowerPoint, Images</div>
                    </div>
                    <div class="mb-3">
                        <label for="document_description" class="form-label">Description (optionnelle)</label>
                        <textarea class="form-control" id="document_description" name="document_description" rows="3" placeholder="Ajoutez une description..."></textarea>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="notify_student" name="notify_student" value="1" checked>
                        <label class="form-check-label" for="notify_student">Notifier l'étudiant</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" name="upload_document" class="btn btn-primary">Téléverser</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Approve Document Modal -->
<div class="modal fade" id="approveDocumentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Approuver le document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body">
                    <p>Vous êtes sur le point d'approuver le document <strong id="approveDocumentTitle"></strong>.</p>
                    <div class="mb-3">
                        <label for="approve_feedback" class="form-label">Commentaire (optionnel)</label>
                        <textarea class="form-control" id="approve_feedback" name="feedback" rows="3" placeholder="Ajouter un commentaire..."></textarea>
                    </div>
                    <input type="hidden" name="document_id" id="approve_document_id">
                    <input type="hidden" name="document_status" value="approved">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" name="update_document_status" class="btn btn-success">Approuver</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Document Modal -->
<div class="modal fade" id="rejectDocumentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Rejeter le document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body">
                    <p>Vous êtes sur le point de rejeter le document <strong id="rejectDocumentTitle"></strong>.</p>
                    <div class="mb-3">
                        <label for="reject_feedback" class="form-label">Motif du rejet</label>
                        <textarea class="form-control" id="reject_feedback" name="feedback" rows="3" placeholder="Expliquez pourquoi ce document est rejeté..." required></textarea>
                    </div>
                    <input type="hidden" name="document_id" id="reject_document_id">
                    <input type="hidden" name="document_status" value="rejected">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" name="update_document_status" class="btn btn-danger">Rejeter</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Document Modal -->
<div class="modal fade" id="deleteDocumentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body">
                    <p>Êtes-vous sûr de vouloir supprimer le document <strong id="deleteDocumentTitle"></strong> ?</p>
                    <p class="text-danger">Cette action est irréversible.</p>
                    <input type="hidden" name="document_id" id="delete_document_id">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" name="delete_document" class="btn btn-danger">Supprimer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Fonction pour la recherche de documents
    document.getElementById('search').addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();
        const table = document.getElementById('documentsTable');
        if (!table) return;
        
        const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
        
        for (let i = 0; i < rows.length; i++) {
            const rowText = rows[i].textContent.toLowerCase();
            rows[i].style.display = rowText.includes(searchTerm) ? '' : 'none';
        }
    });
    
    // Fonction pour approuver un document
    function approveDocument(documentId, documentTitle) {
        document.getElementById('approve_document_id').value = documentId;
        document.getElementById('approveDocumentTitle').textContent = documentTitle;
        const modal = new bootstrap.Modal(document.getElementById('approveDocumentModal'));
        modal.show();
    }
    
    // Fonction pour rejeter un document
    function rejectDocument(documentId, documentTitle) {
        document.getElementById('reject_document_id').value = documentId;
        document.getElementById('rejectDocumentTitle').textContent = documentTitle;
        const modal = new bootstrap.Modal(document.getElementById('rejectDocumentModal'));
        modal.show();
    }
    
    // Fonction pour confirmer la suppression
    function confirmDelete(documentId, documentTitle) {
        document.getElementById('delete_document_id').value = documentId;
        document.getElementById('deleteDocumentTitle').textContent = documentTitle;
        const modal = new bootstrap.Modal(document.getElementById('deleteDocumentModal'));
        modal.show();
    }
    
    // Triage des documents
    document.querySelectorAll('[data-sort]').forEach(sortBtn => {
        sortBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Mettre à jour l'élément actif
            document.querySelectorAll('[data-sort]').forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            const sortType = this.getAttribute('data-sort');
            const table = document.getElementById('documentsTable');
            if (!table) return;
            
            const tbody = table.getElementsByTagName('tbody')[0];
            const rows = Array.from(tbody.getElementsByTagName('tr'));
            
            // Trier les lignes
            rows.sort((a, b) => {
                let aValue, bValue;
                
                switch (sortType) {
                    case 'date-desc':
                    case 'date-asc':
                        aValue = new Date(a.getElementsByTagName('td')[sortType === 'date-desc' ? 2 : 2].textContent);
                        bValue = new Date(b.getElementsByTagName('td')[sortType === 'date-desc' ? 2 : 2].textContent);
                        return sortType === 'date-desc' ? bValue - aValue : aValue - bValue;
                    
                    case 'name':
                        aValue = a.getElementsByTagName('td')[0].textContent.toLowerCase();
                        bValue = b.getElementsByTagName('td')[0].textContent.toLowerCase();
                        return aValue.localeCompare(bValue);
                    
                    case 'student':
                        aValue = a.getElementsByTagName('td')[1]?.textContent.toLowerCase() || '';
                        bValue = b.getElementsByTagName('td')[1]?.textContent.toLowerCase() || '';
                        return aValue.localeCompare(bValue);
                    
                    case 'type':
                        aValue = a.getAttribute('data-document-type');
                        bValue = b.getAttribute('data-document-type');
                        return aValue.localeCompare(bValue);
                    
                    case 'status':
                        aValue = a.getAttribute('data-document-status');
                        bValue = b.getAttribute('data-document-status');
                        return aValue.localeCompare(bValue);
                    
                    default:
                        return 0;
                }
            });
            
            // Réorganiser les lignes dans le tableau
            rows.forEach(row => tbody.appendChild(row));
        });
    });
</script>

<?php
/**
 * Fonction pour formater la taille des fichiers
 * @param int|null $bytes Taille en octets
 * @return string Taille formatée
 */
function formatFileSize($bytes) {
    // Gérer les cas où $bytes est null ou 0
    if ($bytes === null || $bytes === 0 || empty($bytes)) {
        return '0 Bytes';
    }
    
    // S'assurer que $bytes est un nombre positif
    $bytes = max(0, (float)$bytes);
    if ($bytes === 0) {
        return '0 Bytes';
    }
    
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    $i = floor(log($bytes) / log($k));
    
    // S'assurer que l'index est dans les limites du tableau
    $i = min(count($sizes) - 1, max(0, $i));
    
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}

// Inclure le pied de page
include_once __DIR__ . '/../common/footer.php';
?>