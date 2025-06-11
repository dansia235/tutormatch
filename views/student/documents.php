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
    'administrative' => [],
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
        case 'administrative':
            $documents['administrative'][] = $doc;
            break;
        default:
            $documents['other'][] = $doc;
            break;
    }
}

// Traitement de l'upload de document si formulaire soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_document'])) {
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
                'assignment_id' => $activeAssignment ? $activeAssignment['id'] : null,
                'status' => 'pending'
            ];
            
            // Créer le document dans la base de données
            if ($documentModel->create($documentData)) {
                // Notifier le tuteur si demandé
                if (isset($_POST['notify_tutor']) && $activeAssignment) {
                    // Code pour envoyer une notification au tuteur
                    // À implémenter selon le système de notification existant
                }
                
                setFlashMessage('success', 'Document téléversé avec succès');
                redirect('/tutoring/views/student/documents.php');
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
                                        foreach ($documents['administrative'] as $doc) {
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
                            <option value="administrative">Document administratif</option>
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