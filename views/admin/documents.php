<?php
/**
 * Vue pour la gestion des documents - Page principale
 */

// Initialiser les variables
$pageTitle = 'Gestion des documents';
$currentPage = 'documents';
$extraStyles = '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../includes/init.php';

// Vérifier les permissions
requireRole(['admin', 'coordinator']);

// Afficher tous les documents ou filtrer par catégorie
$category = isset($_GET['category']) ? $_GET['category'] : null;

// Create Document model directly
$documentModel = new Document($db);

// Récupérer les documents
$documents = $documentModel->getAll($category);

// Récupérer les statistiques
$stats = $documentModel->countByCategory();

// Inclure l'en-tête
include_once __DIR__ . '/../common/header.php';
?>

<div class="container-fluid">
    <!-- En-tête de page -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2 mb-0"><i class="bi bi-file-earmark-text me-2"></i>Gestion des documents</h1>
        
        <div class="btn-group">
            <a href="/tutoring/views/admin/documents/create.php" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i>Ajouter un document
            </a>
            <a href="/tutoring/views/admin/documents/my-documents.php" class="btn btn-outline-primary">
                <i class="bi bi-person me-2"></i>Mes documents
            </a>
        </div>
    </div>
    
    <!-- Filtres et recherche -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <form action="" method="GET" class="d-flex">
                        <input type="text" name="term" class="form-control me-2" placeholder="Rechercher..." value="<?php echo isset($_GET['term']) ? h($_GET['term']) : ''; ?>">
                        <button type="submit" name="search" class="btn btn-outline-primary">
                            <i class="bi bi-search"></i>
                        </button>
                    </form>
                </div>
                <div class="col-md-6">
                    <div class="d-flex justify-content-md-end">
                        <div class="btn-group" role="group">
                            <a href="?category=" class="btn btn-outline-secondary <?php echo !isset($_GET['category']) ? 'active' : ''; ?>">Tous</a>
                            <a href="?category=cv" class="btn btn-outline-secondary <?php echo isset($_GET['category']) && $_GET['category'] === 'cv' ? 'active' : ''; ?>">CV</a>
                            <a href="?category=report" class="btn btn-outline-secondary <?php echo isset($_GET['category']) && $_GET['category'] === 'report' ? 'active' : ''; ?>">Rapports</a>
                            <a href="?category=agreement" class="btn btn-outline-secondary <?php echo isset($_GET['category']) && $_GET['category'] === 'agreement' ? 'active' : ''; ?>">Conventions</a>
                            <a href="?category=evaluation" class="btn btn-outline-secondary <?php echo isset($_GET['category']) && $_GET['category'] === 'evaluation' ? 'active' : ''; ?>">Évaluations</a>
                            <a href="?category=other" class="btn btn-outline-secondary <?php echo isset($_GET['category']) && $_GET['category'] === 'other' ? 'active' : ''; ?>">Autres</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Statistiques des documents -->
    <div class="row mb-4">
        <?php 
        $categories = [
            'cv' => ['name' => 'CV', 'icon' => 'bi-file-person', 'color' => 'primary'],
            'report' => ['name' => 'Rapports', 'icon' => 'bi-file-text', 'color' => 'success'],
            'agreement' => ['name' => 'Conventions', 'icon' => 'bi-file-earmark-text', 'color' => 'info'],
            'evaluation' => ['name' => 'Évaluations', 'icon' => 'bi-file-check', 'color' => 'warning'],
            'image' => ['name' => 'Images', 'icon' => 'bi-file-image', 'color' => 'danger'],
            'presentation' => ['name' => 'Présentations', 'icon' => 'bi-file-slides', 'color' => 'secondary'],
            'other' => ['name' => 'Autres', 'icon' => 'bi-file', 'color' => 'dark']
        ];
        
        foreach ($categories as $key => $category):
            $count = $stats[$key] ?? 0;
        ?>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-<?php echo $category['color']; ?> h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-light p-3 me-3">
                            <i class="bi <?php echo $category['icon']; ?> text-<?php echo $category['color']; ?> fs-4"></i>
                        </div>
                        <div>
                            <h5 class="card-title mb-0"><?php echo $category['name']; ?></h5>
                            <p class="text-muted mb-0"><?php echo $count; ?> document<?php echo $count > 1 ? 's' : ''; ?></p>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-top-0">
                    <a href="?category=<?php echo $key; ?>" class="btn btn-sm btn-outline-<?php echo $category['color']; ?> w-100">
                        <i class="bi bi-eye me-2"></i>Voir
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Liste des documents -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Liste des documents</h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($documents)): ?>
            <div class="alert alert-info m-3">
                <i class="bi bi-info-circle me-2"></i>Aucun document trouvé.
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Titre</th>
                            <th scope="col">Catégorie</th>
                            <th scope="col">Utilisateur</th>
                            <th scope="col">Date</th>
                            <th scope="col">Taille</th>
                            <th scope="col">Visibilité</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($documents as $document): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php 
                                    $iconClass = 'bi-file';
                                    
                                    if (isset($document['file_type'])) {
                                        if (strpos($document['file_type'], 'pdf') !== false) {
                                            $iconClass = 'bi-file-pdf';
                                        } elseif (strpos($document['file_type'], 'word') !== false) {
                                            $iconClass = 'bi-file-word';
                                        } elseif (strpos($document['file_type'], 'excel') !== false || strpos($document['file_type'], 'sheet') !== false) {
                                            $iconClass = 'bi-file-excel';
                                        } elseif (strpos($document['file_type'], 'powerpoint') !== false || strpos($document['file_type'], 'presentation') !== false) {
                                            $iconClass = 'bi-file-slides';
                                        } elseif (strpos($document['file_type'], 'image') !== false) {
                                            $iconClass = 'bi-file-image';
                                        } elseif (strpos($document['file_type'], 'zip') !== false || strpos($document['file_type'], 'rar') !== false) {
                                            $iconClass = 'bi-file-zip';
                                        } elseif (strpos($document['file_type'], 'text') !== false) {
                                            $iconClass = 'bi-file-text';
                                        }
                                    }
                                    ?>
                                    <i class="bi <?php echo $iconClass; ?> me-2 fs-4"></i>
                                    <div>
                                        <div class="fw-bold"><?php echo h($document['title'] ?? ''); ?></div>
                                        <?php if (isset($document['description']) && !empty($document['description'])): ?>
                                        <div class="text-muted small"><?php echo h(substr($document['description'], 0, 50) . (strlen($document['description']) > 50 ? '...' : '')); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
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
                                $docCategory = $document['category'] ?? '';
                                echo $categoryLabels[$docCategory] ?? '<span class="badge bg-secondary">Inconnu</span>';
                                ?>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm me-2">
                                        <?php 
                                        $firstName = $document['first_name'] ?? '';
                                        $lastName = $document['last_name'] ?? '';
                                        echo strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1)); 
                                        ?>
                                    </div>
                                    <div>
                                        <?php echo h($firstName . ' ' . $lastName); ?>
                                        <div class="text-muted small"><?php echo h($document['role'] ?? ''); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo isset($document['upload_date']) ? date('d/m/Y H:i', strtotime($document['upload_date'])) : ''; ?></td>
                            <td>
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
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td>
                                <?php 
                                $visibilityLabels = [
                                    'private' => '<span class="badge bg-danger">Privé</span>',
                                    'public' => '<span class="badge bg-success">Public</span>',
                                    'restricted' => '<span class="badge bg-warning">Restreint</span>'
                                ];
                                $visibility = $document['visibility'] ?? '';
                                echo $visibilityLabels[$visibility] ?? '<span class="badge bg-secondary">Inconnu</span>';
                                ?>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="/tutoring/views/admin/documents/show.php?id=<?php echo $document['id']; ?>" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Voir les détails">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="/tutoring/views/admin/documents/edit.php?id=<?php echo $document['id']; ?>" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Modifier">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="/tutoring/views/admin/documents/download.php?id=<?php echo $document['id']; ?>" class="btn btn-sm btn-outline-info" data-bs-toggle="tooltip" title="Télécharger">
                                        <i class="bi bi-download"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $document['id']; ?>" title="Supprimer">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                                
                                <!-- Modal de confirmation de suppression -->
                                <div class="modal fade" id="deleteModal<?php echo $document['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $document['id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="deleteModalLabel<?php echo $document['id']; ?>">Confirmer la suppression</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Êtes-vous sûr de vouloir supprimer le document <strong><?php echo h($document['title'] ?? ''); ?></strong> ?</p>
                                                <p class="text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Cette action est irréversible et supprimera définitivement le fichier.</p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                <form action="/tutoring/views/admin/documents/delete.php" method="POST">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                                    <input type="hidden" name="id" value="<?php echo $document['id']; ?>">
                                                    <button type="submit" class="btn btn-danger">Supprimer</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
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

<?php
// Inclure le pied de page
include_once __DIR__ . '/../common/footer.php';
?>