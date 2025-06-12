<?php
/**
 * Vue pour la gestion des documents par l'étudiant
 */

// Initialiser les variables
$pageTitle = 'Mes documents';
$currentPage = 'documents';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../includes/init.php';

// Vérifier que l'utilisateur est connecté et a le rôle étudiant
requireRole('student');

// Récupérer l'ID de l'étudiant
$studentModel = new Student($db);
$student = $studentModel->getByUserId($_SESSION['user_id']);

if (!$student) {
    setFlashMessage('error', 'Profil étudiant non trouvé');
    redirect('/tutoring/views/student/dashboard.php');
}

// Récupérer l'affectation active de l'étudiant (s'il en a une)
$assignmentModel = new Assignment($db);
$activeAssignment = $assignmentModel->getActiveByStudentId($student['id']);

// Récupérer les documents de l'étudiant
$documentModel = new Document($db);
$allDocuments = $documentModel->getByUserId($_SESSION['user_id']);

// Catégoriser les documents
$documents = [
    'report' => [],
    'contract' => [],
    'evaluation' => [],
    'certificate' => [],
    'other' => []
];

// Si l'étudiant a une affectation active, récupérer également les documents liés à cette affectation
$sharedDocuments = [];
if ($activeAssignment) {
    // Récupérer les documents de l'affectation
    $assignmentDocuments = $documentModel->getByAssignmentId($activeAssignment['id']);
    
    // Filtrer pour ne garder que les documents qui ne sont pas de l'étudiant (partagés)
    foreach ($assignmentDocuments as $doc) {
        if ($doc['user_id'] != $_SESSION['user_id']) {
            $sharedDocuments[] = $doc;
        }
    }
}

// Organiser les documents par catégorie
foreach ($allDocuments as $doc) {
    switch ($doc['type']) {
        case 'report':
            $documents['report'][] = $doc;
            break;
        case 'contract':
            $documents['contract'][] = $doc;
            break;
        case 'evaluation':
            $documents['evaluation'][] = $doc;
            break;
        case 'certificate':
            $documents['certificate'][] = $doc;
            break;
        default:
            $documents['other'][] = $doc;
            break;
    }
}

// Traitement de la suppression de document
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_document']) && isset($_POST['document_id'])) {
    $documentId = (int)$_POST['document_id'];
    
    // Vérifier que le document existe et appartient à l'utilisateur
    $document = $documentModel->getById($documentId);
    
    if (!$document) {
        setFlashMessage('error', 'Document non trouvé');
        redirect('/tutoring/views/student/documents.php');
        exit;
    }
    
    // Vérifier que l'utilisateur est bien le propriétaire du document
    if ($document['user_id'] != $_SESSION['user_id']) {
        setFlashMessage('error', 'Vous n\'êtes pas autorisé à supprimer ce document');
        redirect('/tutoring/views/student/documents.php');
        exit;
    }
    
    // Supprimer le fichier physique
    $filePath = ROOT_PATH . '/' . $document['file_path'];
    if (file_exists($filePath)) {
        unlink($filePath);
        error_log("Fichier supprimé: " . $filePath);
    } else {
        error_log("Fichier introuvable: " . $filePath);
    }
    
    // Supprimer l'enregistrement dans la base de données
    if ($documentModel->delete($documentId)) {
        setFlashMessage('success', 'Document supprimé avec succès');
    } else {
        setFlashMessage('error', 'Erreur lors de la suppression du document');
    }
    
    redirect('/tutoring/views/student/documents.php');
    exit;
}

// Traitement de l'upload de document si formulaire soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_document'])) {
    // Vérifier si un fichier a été envoyé
    if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] === UPLOAD_ERR_OK) {
        // Définir les messages d'erreur pour les codes d'erreur d'upload
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'Le fichier dépasse la taille maximale définie dans php.ini',
            UPLOAD_ERR_FORM_SIZE => 'Le fichier dépasse la taille maximale définie dans le formulaire',
            UPLOAD_ERR_PARTIAL => 'Le fichier n\'a été que partiellement téléchargé',
            UPLOAD_ERR_NO_FILE => 'Aucun fichier n\'a été téléchargé',
            UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant',
            UPLOAD_ERR_CANT_WRITE => 'Échec de l\'écriture du fichier sur le disque',
            UPLOAD_ERR_EXTENSION => 'Une extension PHP a arrêté le téléchargement du fichier'
        ];
        
        // Vérifier si le fichier a été correctement téléchargé
        if ($_FILES['document_file']['error'] !== UPLOAD_ERR_OK) {
            $errorMessage = $errorMessages[$_FILES['document_file']['error']] ?? 'Erreur inconnue lors du téléchargement';
            setFlashMessage('error', $errorMessage);
            redirect('/tutoring/views/student/documents.php');
            exit;
        }
        
        // Utiliser le chemin absolu pour le dossier d'upload
        $uploadDir = ROOT_PATH . '/uploads/documents/';
        
        // Debug - Afficher des informations sur le dossier de destination
        error_log("Tentative de création du dossier : " . $uploadDir);
        error_log("Le dossier existe : " . (is_dir($uploadDir) ? 'Oui' : 'Non'));
        error_log("Permissions du dossier parent : " . substr(sprintf('%o', fileperms(dirname($uploadDir))), -4));
        
        // Créer le répertoire s'il n'existe pas - avec permissions plus permissives
        if (!is_dir($uploadDir)) {
            $mkdirResult = mkdir($uploadDir, 0777, true);
            error_log("Résultat de mkdir : " . ($mkdirResult ? 'Succès' : 'Échec'));
            if (!$mkdirResult) {
                error_log("Erreur lors de la création du dossier : " . $uploadDir);
                error_log("Message d'erreur : " . error_get_last()['message']);
                setFlashMessage('error', 'Erreur lors de la création du dossier de destination');
                redirect('/tutoring/views/student/documents.php');
                exit;
            }
            
            // Vérifier si le dossier a été créé
            if (!is_dir($uploadDir)) {
                error_log("Le dossier n'a pas été créé malgré le succès de mkdir");
                setFlashMessage('error', 'Le dossier de destination n\'a pas pu être créé');
                redirect('/tutoring/views/student/documents.php');
                exit;
            }
            
            // Essayer de changer les permissions explicitement
            chmod($uploadDir, 0777);
        }
        
        // Générer un nom de fichier unique
        $fileName = time() . '_' . basename($_FILES['document_file']['name']);
        $filePath = $uploadDir . $fileName;
        
        error_log("Tentative de déplacement du fichier vers : " . $filePath);
        error_log("Fichier temporaire existe : " . (file_exists($_FILES['document_file']['tmp_name']) ? 'Oui' : 'Non'));
        
        // Déplacer le fichier téléchargé
        if (move_uploaded_file($_FILES['document_file']['tmp_name'], $filePath)) {
            error_log("Fichier déplacé avec succès");
            
            // Vérifier que le fichier a bien été déplacé
            if (!file_exists($filePath)) {
                error_log("Le fichier n'existe pas après déplacement : " . $filePath);
                setFlashMessage('error', 'Le fichier a été déplacé mais n\'est pas accessible');
                redirect('/tutoring/views/student/documents.php');
                exit;
            }
            
            // Préparer les données pour l'enregistrement en BDD
            $documentData = [
                'title' => $_POST['document_title'],
                'description' => $_POST['document_description'] ?? null,
                'file_path' => 'uploads/documents/' . $fileName,
                'file_type' => $_FILES['document_file']['type'],
                'file_size' => $_FILES['document_file']['size'],
                'type' => $_POST['document_type'],
                'user_id' => $_SESSION['user_id'],
                'assignment_id' => $activeAssignment ? $activeAssignment['id'] : null,
                'status' => 'submitted', // 'submitted' est un statut valide dans l'enum: 'draft','submitted','approved','rejected'
                'version' => '1.0' // Ajouter la version par défaut
            ];
            
            // Ajouter un log détaillé pour le débogage
            error_log("Document::upload - Données complètes: " . json_encode($documentData));
            
            error_log("Tentative d'enregistrement en BDD : " . json_encode($documentData));
            
            // Créer le document dans la base de données
            $documentId = $documentModel->create($documentData);
            if ($documentId) {
                error_log("Document créé avec succès, ID : " . $documentId);
                
                // Notifier le tuteur si demandé
                if (isset($_POST['notify_tutor']) && $activeAssignment) {
                    try {
                        // Récupérer les informations du tuteur
                        $teacherId = $activeAssignment['teacher_id'];
                        $teacherModel = new Teacher($db);
                        $teacher = $teacherModel->getById($teacherId);
                        
                        if ($teacher && isset($teacher['user_id'])) {
                            $tutorUserId = $teacher['user_id'];
                            
                            // Créer une notification pour le tuteur
                            $notificationModel = new Notification($db);
                            $notificationData = [
                                'user_id' => $tutorUserId,
                                'title' => 'Nouveau document',
                                'message' => 'L\'étudiant ' . $_SESSION['user_name'] . ' a téléversé un nouveau document: ' . $_POST['document_title'],
                                'type' => 'info',
                                'related_type' => 'document',
                                'related_id' => $documentId,
                                'link' => '/tutoring/views/tutor/documents.php'
                            ];
                            
                            $notificationId = $notificationModel->create($notificationData);
                            error_log("Notification envoyée au tuteur, ID : " . $notificationId);
                        }
                    } catch (Exception $e) {
                        error_log("Erreur lors de l'envoi de la notification : " . $e->getMessage());
                        // Ne pas bloquer le processus si la notification échoue
                    }
                }
                
                setFlashMessage('success', 'Document téléversé avec succès');
                redirect('/tutoring/views/student/documents.php');
            } else {
                error_log("Échec de la création du document en BDD");
                setFlashMessage('error', 'Erreur lors de l\'enregistrement du document dans la base de données');
                
                // Supprimer le fichier si l'enregistrement en BDD a échoué
                if (file_exists($filePath)) {
                    unlink($filePath);
                    error_log("Fichier supprimé après échec BDD : " . $filePath);
                }
            }
        } else {
            error_log("Échec du déplacement du fichier");
            error_log("Message d'erreur : " . error_get_last()['message']);
            setFlashMessage('error', 'Erreur lors du déplacement du fichier téléchargé');
        }
    } else {
        // Détail de l'erreur d'upload
        $errorCode = isset($_FILES['document_file']) ? $_FILES['document_file']['error'] : 'No file';
        error_log("Erreur d'upload de fichier, code : " . $errorCode);
        
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'Le fichier dépasse la taille maximale définie dans php.ini',
            UPLOAD_ERR_FORM_SIZE => 'Le fichier dépasse la taille maximale définie dans le formulaire',
            UPLOAD_ERR_PARTIAL => 'Le fichier n\'a été que partiellement téléchargé',
            UPLOAD_ERR_NO_FILE => 'Aucun fichier n\'a été téléchargé',
            UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant',
            UPLOAD_ERR_CANT_WRITE => 'Échec de l\'écriture du fichier sur le disque',
            UPLOAD_ERR_EXTENSION => 'Une extension PHP a arrêté le téléchargement du fichier'
        ];
        
        $errorMessage = isset($_FILES['document_file']) ? 
            ($errorMessages[$_FILES['document_file']['error']] ?? 'Erreur inconnue lors du téléchargement') : 
            'Aucun fichier n\'a été envoyé';
            
        setFlashMessage('error', $errorMessage);
    }
}

// Inclure l'en-tête
include_once __DIR__ . '/../common/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2>Mes documents</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/tutoring/views/student/dashboard.php">Tableau de bord</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Documents</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <!-- Main Content Row -->
    <div class="row">
        <!-- Left Column -->
        <div class="col-lg-8">
            <!-- Filtres et Actions -->
            <div class="card mb-4 fade-in">
                <div class="card-header">
                    <span>Filtres et recherche</span>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#uploadDocumentModal">
                        <i class="bi bi-upload me-1"></i>Téléverser
                    </button>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="search-box">
                                <i class="bi bi-search"></i>
                                <input type="text" class="form-control" id="documentSearch" placeholder="Rechercher un document...">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-outline-secondary dropdown-toggle w-100" type="button" id="documentsFilter" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-funnel me-1"></i>Filtrer
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="documentsFilter">
                                <li><a class="dropdown-item active" href="#" data-filter="all">Tous les documents</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#" data-filter="report">Rapports</a></li>
                                <li><a class="dropdown-item" href="#" data-filter="contract">Contrats</a></li>
                                <li><a class="dropdown-item" href="#" data-filter="administrative">Documents administratifs</a></li>
                                <li><a class="dropdown-item" href="#" data-filter="other">Autres</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Liste des Documents -->
            <div class="card mb-4 fade-in">
                <div class="card-header">
                    <span>Mes Documents</span>
                    <div class="dropdown d-inline-block">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="documentsSort" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-sort-down"></i> Trier par
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="documentsSort">
                            <li><a class="dropdown-item active" href="#" data-sort="date-desc">Date d'ajout (récent)</a></li>
                            <li><a class="dropdown-item" href="#" data-sort="date-asc">Date d'ajout (ancien)</a></li>
                            <li><a class="dropdown-item" href="#" data-sort="type">Type</a></li>
                            <li><a class="dropdown-item" href="#" data-sort="status">Statut</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" data-sort="name">Nom</a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($allDocuments)): ?>
                    <div class="alert alert-info" role="alert">
                        <i class="bi bi-info-circle-fill me-2"></i>Vous n'avez pas encore téléversé de documents. Utilisez le bouton "Téléverser" pour ajouter votre premier document.
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover" id="documentsTable">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Type</th>
                                    <th>Date</th>
                                    <th>Statut</th>
                                    <th>Taille</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allDocuments as $doc): ?>
                                <tr class="document-card" data-document-type="<?php echo h($doc['type']); ?>">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php
                                            // Déterminer l'icône en fonction du type de fichier
                                            $iconClass = 'bi-file-earmark text-secondary';
                                            $fileType = $doc['file_type'] ?? '';
                                            if (strpos($fileType, 'pdf') !== false) {
                                                $iconClass = 'bi-file-earmark-pdf text-danger';
                                            } elseif (strpos($fileType, 'word') !== false || strpos($fileType, 'document') !== false) {
                                                $iconClass = 'bi-file-earmark-word text-primary';
                                            } elseif (strpos($fileType, 'excel') !== false || strpos($fileType, 'sheet') !== false) {
                                                $iconClass = 'bi-file-earmark-excel text-success';
                                            } elseif (strpos($fileType, 'image') !== false) {
                                                $iconClass = 'bi-file-earmark-image text-info';
                                            } elseif (strpos($fileType, 'presentation') !== false || strpos($fileType, 'powerpoint') !== false) {
                                                $iconClass = 'bi-file-earmark-slides text-warning';
                                            }
                                            ?>
                                            <i class="bi <?php echo $iconClass; ?> me-2"></i>
                                            <div>
                                                <strong><?php echo h($doc['title']); ?></strong>
                                                <?php if (!empty($doc['description'])): ?>
                                                <br><small class="text-muted"><?php echo h(substr($doc['description'], 0, 50)) . (strlen($doc['description']) > 50 ? '...' : ''); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?php 
                                            echo match($doc['type']) {
                                                'report' => 'Rapport',
                                                'contract' => 'Contrat',
                                                'administrative' => 'Administratif',
                                                default => 'Autre'
                                            };
                                            ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($doc['upload_date'])); ?></td>
                                    <td>
                                        <span class="badge <?php 
                                            echo match($doc['status']) {
                                                'pending' => 'bg-warning',
                                                'approved' => 'bg-success',
                                                'rejected' => 'bg-danger',
                                                default => 'bg-secondary'
                                            };
                                        ?>">
                                            <?php 
                                            echo match($doc['status']) {
                                                'pending' => 'En attente',
                                                'approved' => 'Validé',
                                                'rejected' => 'Rejeté',
                                                default => ucfirst($doc['status'])
                                            };
                                            ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatFileSize($doc['file_size'] ?? 0); ?></td>
                                    <td>
                                        <a href="/tutoring/<?php echo h($doc['file_path']); ?>" class="btn btn-sm btn-outline-primary me-1" download>
                                            <i class="bi bi-download"></i>
                                        </a>
                                        <a href="/tutoring/<?php echo h($doc['file_path']); ?>" class="btn btn-sm btn-outline-secondary me-1" target="_blank">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?php echo $doc['id']; ?>)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        <p class="small text-muted">Affichage de <?php echo count($allDocuments); ?> document(s)</p>
                    </div>
                    <?php endif; ?>
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
                    <button class="btn btn-primary w-100 mb-2" data-bs-toggle="modal" data-bs-target="#uploadDocumentModal">
                        <i class="bi bi-upload me-2"></i>Téléverser un document
                    </button>
                    <a href="/tutoring/views/student/internship.php" class="btn btn-outline-primary w-100 mb-2">
                        <i class="bi bi-briefcase me-2"></i>Voir mon stage
                    </a>
                    <a href="/tutoring/views/student/meetings.php" class="btn btn-outline-primary w-100 mb-2">
                        <i class="bi bi-calendar-event me-2"></i>Planifier une réunion
                    </a>
                    <a href="/tutoring/views/student/tutor.php" class="btn btn-outline-primary w-100">
                        <i class="bi bi-person-badge me-2"></i>Contacter mon tuteur
                    </a>
                </div>
            </div>
            
            <!-- Documents Obligatoires -->
            <div class="card mb-4 fade-in">
                <div class="card-header">
                    Documents Obligatoires
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <!-- Document obligatoire: Convention de stage -->
                        <div class="list-group-item">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-file-earmark-text text-primary me-3"></i>
                                <div class="flex-grow-1">
                                    <strong>Convention de stage</strong>
                                    <p class="mb-0 small text-muted">
                                        <?php
                                        $conventionFound = false;
                                        foreach ($documents['contract'] as $doc) {
                                            if (stripos($doc['title'], 'convention') !== false) {
                                                echo 'Téléversé le ' . date('d/m/Y', strtotime($doc['upload_date']));
                                                $conventionFound = true;
                                                break;
                                            }
                                        }
                                        if (!$conventionFound) {
                                            echo 'Document requis - Non téléversé';
                                        }
                                        ?>
                                    </p>
                                </div>
                                <span class="badge <?php echo $conventionFound ? 'bg-success' : 'bg-warning'; ?>">
                                    <?php echo $conventionFound ? 'Complet' : 'À fournir'; ?>
                                </span>
                            </div>
                        </div>
                        
                        <!-- Document obligatoire: Rapport final -->
                        <div class="list-group-item">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-file-earmark-richtext text-danger me-3"></i>
                                <div class="flex-grow-1">
                                    <strong>Rapport final</strong>
                                    <p class="mb-0 small text-muted">
                                        <?php
                                        $finalReportFound = false;
                                        foreach ($documents['report'] as $doc) {
                                            if (stripos($doc['title'], 'final') !== false || stripos($doc['title'], 'rapport final') !== false) {
                                                echo 'Téléversé le ' . date('d/m/Y', strtotime($doc['upload_date']));
                                                $finalReportFound = true;
                                                break;
                                            }
                                        }
                                        if (!$finalReportFound) {
                                            echo 'À remettre à la fin du stage';
                                        }
                                        ?>
                                    </p>
                                </div>
                                <span class="badge <?php echo $finalReportFound ? 'bg-success' : 'bg-secondary'; ?>">
                                    <?php echo $finalReportFound ? 'Complet' : 'À venir'; ?>
                                </span>
                            </div>
                        </div>
                        
                        <!-- Document obligatoire: Évaluation entreprise -->
                        <div class="list-group-item">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-file-earmark-check text-success me-3"></i>
                                <div class="flex-grow-1">
                                    <strong>Évaluation entreprise</strong>
                                    <p class="mb-0 small text-muted">
                                        <?php
                                        $evalFound = false;
                                        foreach ($documents['evaluation'] as $doc) {
                                            if (stripos($doc['title'], 'évaluation') !== false || stripos($doc['title'], 'evaluation') !== false) {
                                                echo 'Téléversé le ' . date('d/m/Y', strtotime($doc['upload_date']));
                                                $evalFound = true;
                                                break;
                                            }
                                        }
                                        if (!$evalFound) {
                                            echo 'À faire remplir par l\'entreprise';
                                        }
                                        ?>
                                    </p>
                                </div>
                                <span class="badge <?php echo $evalFound ? 'bg-success' : 'bg-secondary'; ?>">
                                    <?php echo $evalFound ? 'Complet' : 'À venir'; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Documents Partagés -->
            <div class="card fade-in">
                <div class="card-header">
                    Documents Partagés
                </div>
                <div class="card-body p-0">
                    <?php if (empty($sharedDocuments)): ?>
                    <div class="alert alert-info m-3" role="alert">
                        <i class="bi bi-info-circle-fill me-2"></i>Aucun document partagé pour le moment.
                    </div>
                    <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($sharedDocuments as $doc): ?>
                        <a href="/tutoring/<?php echo h($doc['file_path']); ?>" class="list-group-item list-group-item-action" target="_blank">
                            <div class="d-flex align-items-center">
                                <?php
                                // Déterminer l'icône en fonction du type de fichier
                                $iconClass = 'bi-file-earmark text-secondary';
                                $fileType = $doc['file_type'] ?? '';
                                if (strpos($fileType, 'pdf') !== false) {
                                    $iconClass = 'bi-file-earmark-pdf text-danger';
                                } elseif (strpos($fileType, 'word') !== false || strpos($fileType, 'document') !== false) {
                                    $iconClass = 'bi-file-earmark-word text-primary';
                                } elseif (strpos($fileType, 'excel') !== false || strpos($fileType, 'sheet') !== false) {
                                    $iconClass = 'bi-file-earmark-excel text-success';
                                }
                                ?>
                                <i class="bi <?php echo $iconClass; ?> me-3"></i>
                                <div class="flex-grow-1">
                                    <strong><?php echo h($doc['title']); ?></strong>
                                    <p class="mb-0 small text-muted">
                                        Partagé par <?php echo h($doc['first_name'] . ' ' . $doc['last_name']); ?> 
                                        le <?php echo date('d/m/Y', strtotime($doc['upload_date'])); ?>
                                    </p>
                                </div>
                                <span class="badge bg-primary">Télécharger</span>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
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
                            <option value="evaluation">Évaluation</option>
                            <option value="certificate">Certificat</option>
                            <option value="other">Autre</option>
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
                    <?php if ($activeAssignment): ?>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="notify_tutor" name="notify_tutor" value="1">
                        <label class="form-check-label" for="notify_tutor">Notifier mon tuteur</label>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" name="upload_document" class="btn btn-primary">Téléverser</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteDocumentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer ce document ? Cette action est irréversible.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form action="" method="POST" id="deleteDocumentForm">
                    <input type="hidden" name="document_id" id="document_id_to_delete">
                    <button type="submit" name="delete_document" class="btn btn-danger">Supprimer</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Recherche de documents
    document.getElementById('documentSearch').addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();
        const table = document.getElementById('documentsTable');
        if (!table) return;
        
        const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
        
        for (let i = 0; i < rows.length; i++) {
            const rowText = rows[i].textContent.toLowerCase();
            rows[i].style.display = rowText.includes(searchTerm) ? '' : 'none';
        }
    });
    
    // Filtre par type de document
    document.querySelectorAll('[data-filter]').forEach(filter => {
        filter.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Mettre à jour l'élément actif
            document.querySelectorAll('[data-filter]').forEach(f => f.classList.remove('active'));
            this.classList.add('active');
            
            const filterType = this.getAttribute('data-filter');
            const table = document.getElementById('documentsTable');
            if (!table) return;
            
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            
            for (let i = 0; i < rows.length; i++) {
                if (filterType === 'all') {
                    rows[i].style.display = '';
                } else {
                    const rowType = rows[i].getAttribute('data-document-type');
                    rows[i].style.display = rowType === filterType ? '' : 'none';
                }
            }
        });
    });
    
    // Confirmation de suppression
    function confirmDelete(documentId) {
        document.getElementById('document_id_to_delete').value = documentId;
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
    if ($bytes === null || $bytes === 0) return '0 Bytes';
    
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    $i = floor(log(max(1, $bytes)) / log($k));
    
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}

// Inclure le pied de page
include_once __DIR__ . '/../common/footer.php';
?>