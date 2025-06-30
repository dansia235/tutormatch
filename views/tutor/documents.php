<?php
/**
 * Vue pour la gestion des documents par le tuteur
 */

// Titre de la page
$pageTitle = 'Documents';
$currentPage = 'documents';
$extraStyles = '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">';
$extraScripts = '<script src="/tutoring/assets/js/admin-table.js"></script>';

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
                    <!-- En-tête avec compteur et sélecteur -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex align-items-center">
                            <h5 class="card-title mb-0 me-2">
                                <i class="bi bi-file-earmark-text me-2"></i>
                                Documents
                            </h5>
                            <span class="badge bg-primary" id="documentCount">
                                Chargement...
                            </span>
                        </div>
                        
                        <!-- Sélecteur du nombre d'éléments par page -->
                        <div class="d-flex align-items-center">
                            <label for="itemsPerPage" class="form-label me-2 mb-0 text-muted small">Afficher:</label>
                            <select id="itemsPerPage" class="form-select form-select-sm" style="width: auto;">
                                <option value="10">10</option>
                                <option value="20">20</option>
                                <option value="50">50</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Container pour la table -->
                    <div id="documentsTableContainer">
                        <!-- Le contenu sera chargé dynamiquement -->
                        <div class="text-center p-4">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Chargement...</span>
                            </div>
                        </div>
                    </div>
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

<style>
/* Styles pour les colonnes triables */
.sortable {
    cursor: pointer;
    user-select: none;
    transition: background-color 0.2s ease;
    position: relative;
}

.sortable:hover {
    background-color: #e9ecef !important;
}

.sort-icon {
    font-size: 0.8rem;
    opacity: 0.6;
    transition: all 0.2s ease;
}

.sortable:hover .sort-icon {
    opacity: 1;
}

.sort-icon.text-primary {
    opacity: 1;
    font-weight: bold;
}

/* Animation pour le tri */
@keyframes sortHighlight {
    0% { background-color: #e3f2fd; }
    100% { background-color: transparent; }
}

.sortable.sorting {
    animation: sortHighlight 0.3s ease;
}

/* Pagination améliorée */
.pagination .page-item.active .page-link {
    background-color: #0d6efd;
    border-color: #0d6efd;
    color: white !important;
    font-weight: 500;
    box-shadow: 0 2px 5px rgba(13, 110, 253, 0.3);
}

.pagination .page-link {
    color: #495057;
    background-color: #fff;
    border: 1px solid #dee2e6;
    transition: all 0.2s ease-in-out;
}

.pagination .page-link:hover {
    background-color: #e9ecef;
    border-color: #dee2e6;
    color: #0d6efd;
}
</style>

<script>
// Configuration de la table des documents avec AdminTable
const documentTableConfig = {
    apiEndpoint: '/tutoring/api/documents/tutor-list.php',
    tableContainer: '#documentsTableContainer',
    searchForm: '#searchForm',
    defaultSort: 'upload_date',
    columns: [
        { key: 'title', label: 'Document', sortable: true },
        { key: 'student_name', label: 'Étudiant', sortable: true },
        { key: 'type', label: 'Type', sortable: true },
        { key: 'upload_date', label: 'Date', sortable: true },
        { key: 'status', label: 'Statut', sortable: true },
        { key: 'file_size', label: 'Taille', sortable: true },
        { key: 'actions', label: 'Actions', sortable: false }
    ],
    renderRow: function(document) {
        // Icônes selon le type de fichier
        let iconClass = 'bi-file';
        if (document.file_type) {
            if (document.file_type.includes('pdf')) iconClass = 'bi-file-pdf text-danger';
            else if (document.file_type.includes('word')) iconClass = 'bi-file-word text-primary';
            else if (document.file_type.includes('excel') || document.file_type.includes('sheet')) iconClass = 'bi-file-excel text-success';
            else if (document.file_type.includes('powerpoint') || document.file_type.includes('presentation')) iconClass = 'bi-file-slides text-warning';
            else if (document.file_type.includes('image')) iconClass = 'bi-file-image text-info';
            else if (document.file_type.includes('zip') || document.file_type.includes('rar')) iconClass = 'bi-file-zip text-secondary';
            else if (document.file_type.includes('text')) iconClass = 'bi-file-text';
        }
        
        // Badges de type
        const typeLabels = {
            'contract': '<span class="badge bg-primary">Contrat</span>',
            'report': '<span class="badge bg-success">Rapport</span>',
            'evaluation': '<span class="badge bg-warning">Évaluation</span>',
            'certificate': '<span class="badge bg-info">Certificat</span>',
            'other': '<span class="badge bg-dark">Autre</span>'
        };
        const typeHTML = typeLabels[document.type] || '<span class="badge bg-secondary">Inconnu</span>';
        
        // Badges de statut
        const statusLabels = {
            'draft': '<span class="badge bg-secondary">Brouillon</span>',
            'submitted': '<span class="badge bg-info">Soumis</span>',
            'approved': '<span class="badge bg-success">Approuvé</span>',
            'rejected': '<span class="badge bg-danger">Rejeté</span>'
        };
        const statusHTML = statusLabels[document.status] || '<span class="badge bg-secondary">Inconnu</span>';
        
        // Initiales de l'étudiant
        const firstName = document.student_name ? document.student_name.split(' ')[0] : '';
        const lastName = document.student_name ? document.student_name.split(' ')[1] || '' : '';
        const initials = (firstName.charAt(0) + lastName.charAt(0)).toUpperCase();
        
        return `
            <tr>
                <td>
                    <div class="d-flex align-items-center">
                        <i class="bi ${iconClass} me-2 fs-4"></i>
                        <div>
                            <div class="fw-bold">${document.title || ''}</div>
                            ${document.description ? `<div class="text-muted small">${document.description.substring(0, 50)}${document.description.length > 50 ? '...' : ''}</div>` : ''}
                        </div>
                    </div>
                </td>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm me-2">${initials}</div>
                        <div>
                            <div>${document.student_name || 'Inconnu'}</div>
                            ${document.user_email ? `<div class="text-muted small">${document.user_email}</div>` : ''}
                        </div>
                    </div>
                </td>
                <td>${typeHTML}</td>
                <td>${document.upload_date_formatted}</td>
                <td>${statusHTML}</td>
                <td>${document.file_size_formatted}</td>
                <td>
                    <div class="btn-group" role="group">
                        <a href="/tutoring/views/tutor/documents/show.php?id=${document.id}" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Voir les détails">
                            <i class="bi bi-eye"></i>
                        </a>
                        <a href="/tutoring/api/documents/download.php?id=${document.id}" class="btn btn-sm btn-outline-info" data-bs-toggle="tooltip" title="Télécharger">
                            <i class="bi bi-download"></i>
                        </a>
                        ${document.status === 'submitted' ? 
                            `<button type="button" class="btn btn-sm btn-outline-success" onclick="approveDocument(${document.id}, '${document.title}')" title="Approuver">
                                <i class="bi bi-check"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="rejectDocument(${document.id}, '${document.title}')" title="Rejeter">
                                <i class="bi bi-x"></i>
                            </button>` : ''
                        }
                    </div>
                </td>
            </tr>
        `;
    },
    onDataLoaded: function(data) {
        // Mettre à jour le compteur
        const countBadge = document.getElementById('documentCount');
        if (data.pagination.total_items > 0) {
            countBadge.textContent = `${data.pagination.showing_from}-${data.pagination.showing_to} sur ${data.pagination.total_items} documents`;
        } else {
            countBadge.textContent = '0 documents';
        }
    }
};

let adminTable;

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser AdminTable
    adminTable = new AdminTable(documentTableConfig);
    
    // Gestion du sélecteur d'éléments par page
    document.getElementById('itemsPerPage').addEventListener('change', function() {
        adminTable.setItemsPerPage(this.value);
    });
});
</script>

<script>
// Configuration de la table des documents
const documentTableConfig = {
    apiEndpoint: '/tutoring/api/documents/tutor-list.php',
    tableContainer: '#documentsTableContainer',
    defaultSort: 'upload_date',
    defaultOrder: 'desc',
    columns: [
        { key: 'title', label: 'Titre', sortable: true },
        { key: 'student_name', label: 'Étudiant', sortable: true },
        { key: 'type', label: 'Type', sortable: true },
        { key: 'file_size', label: 'Taille', sortable: true },
        { key: 'upload_date', label: 'Date d\'ajout', sortable: true },
        { key: 'status', label: 'Statut', sortable: true },
        { key: 'actions', label: 'Actions', sortable: false }
    ],
    renderRow: function(document) {
        const statusBadge = {
            'pending': '<span class="badge bg-warning">En attente</span>',
            'approved': '<span class="badge bg-success">Approuvé</span>',
            'rejected': '<span class="badge bg-danger">Rejeté</span>'
        };

        const typeBadge = {
            'report': '<span class="badge bg-info">Rapport</span>',
            'contract': '<span class="badge bg-primary">Contrat</span>',
            'administrative': '<span class="badge bg-secondary">Administratif</span>',
            'other': '<span class="badge bg-dark">Autre</span>'
        };

        return `
            <tr>
                <td>
                    <div class="d-flex align-items-center">
                        <i class="bi bi-file-earmark-text me-2 text-primary"></i>
                        <div>
                            <div class="fw-medium">${document.title || 'Document'}</div>
                            ${document.description ? '<small class="text-muted">' + document.description + '</small>' : ''}
                        </div>
                    </div>
                </td>
                <td>
                    <div class="d-flex align-items-center">
                        <i class="bi bi-person-circle me-2 text-secondary"></i>
                        <div>
                            <div>${document.student_name || 'Non assigné'}</div>
                            ${document.student_number ? '<small class="text-muted">' + document.student_number + '</small>' : ''}
                        </div>
                    </div>
                </td>
                <td>${typeBadge[document.type] || '<span class="badge bg-secondary">Autre</span>'}</td>
                <td>
                    <small class="text-muted">${document.file_size_formatted || '0 B'}</small>
                </td>
                <td>
                    <small class="text-muted">${document.upload_date_formatted || ''}</small>
                </td>
                <td>${statusBadge[document.status] || '<span class="badge bg-secondary">Inconnu</span>'}</td>
                <td>
                    <div class="btn-group" role="group">
                        <a href="/tutoring/${document.file_path}" target="_blank" class="btn btn-sm btn-outline-primary" title="Télécharger">
                            <i class="bi bi-download"></i>
                        </a>
                        ${document.status === 'pending' ? `
                            <button class="btn btn-sm btn-outline-success" onclick="approveDocument(${document.id}, '${document.title}')" title="Approuver">
                                <i class="bi bi-check-lg"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="rejectDocument(${document.id}, '${document.title}')" title="Rejeter">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        ` : ''}
                        <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete(${document.id}, '${document.title}')" title="Supprimer">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }
};

// Initialiser AdminTable
document.addEventListener('DOMContentLoaded', function() {
    const adminTable = new AdminTable(documentTableConfig);
    
    // Gestionnaire pour le changement du nombre d'éléments par page
    document.getElementById('itemsPerPage').addEventListener('change', function() {
        adminTable.setItemsPerPage(this.value);
    });
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