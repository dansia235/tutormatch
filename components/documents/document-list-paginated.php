<?php
/**
 * Composant réutilisable pour afficher une liste de documents avec pagination
 * 
 * @param array $documents Liste des documents à afficher
 * @param array $paginationInfo Informations de pagination (currentPage, totalPages, total, perPage)
 * @param array $queryParams Paramètres GET additionnels
 * @param bool $showUser Afficher la colonne utilisateur
 * @param bool $showActions Afficher les actions
 */

function renderDocumentList($documents, $paginationInfo = null, $queryParams = [], $showUser = true, $showActions = true) {
    if (empty($documents)) {
        ?>
        <div class="alert alert-info m-3">
            <i class="bi bi-info-circle me-2"></i>Aucun document trouvé.
        </div>
        <?php
        return;
    }
    ?>
    
    <div class="table-responsive">
        <table class="table table-hover table-striped mb-0">
            <thead class="table-light">
                <tr>
                    <th scope="col">Titre</th>
                    <th scope="col">Type</th>
                    <?php if ($showUser): ?>
                    <th scope="col">Utilisateur</th>
                    <?php endif; ?>
                    <th scope="col">Date</th>
                    <th scope="col">Taille</th>
                    <th scope="col">Statut</th>
                    <?php if ($showActions): ?>
                    <th scope="col">Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($documents as $document): ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center">
                            <?php 
                            $iconClass = getDocumentIcon($document['file_type'] ?? '');
                            ?>
                            <i class="bi <?php echo $iconClass; ?> me-2 fs-4"></i>
                            <div>
                                <div class="fw-bold"><?php echo h($document['title'] ?? ''); ?></div>
                                <?php if (!empty($document['description'])): ?>
                                <div class="text-muted small"><?php echo h(substr($document['description'], 0, 50) . (strlen($document['description']) > 50 ? '...' : '')); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td>
                        <?php echo getDocumentTypeBadge($document['type'] ?? ''); ?>
                    </td>
                    <?php if ($showUser): ?>
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
                    <?php endif; ?>
                    <td><?php echo isset($document['upload_date']) ? date('d/m/Y H:i', strtotime($document['upload_date'])) : ''; ?></td>
                    <td><?php echo formatFileSize($document['file_size'] ?? 0); ?></td>
                    <td><?php echo getDocumentStatusBadge($document['status'] ?? ''); ?></td>
                    <?php if ($showActions): ?>
                    <td>
                        <div class="btn-group" role="group">
                            <?php echo renderDocumentActions($document); ?>
                        </div>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <?php
    // Afficher la pagination si fournie
    if ($paginationInfo && $paginationInfo['totalPages'] > 1) {
        ?>
        <div class="card-footer">
            <?php 
            require_once __DIR__ . '/../common/pagination.php';
            renderPagination($paginationInfo['currentPage'], $paginationInfo['totalPages'], $queryParams);
            renderPaginationInfo($paginationInfo['currentPage'], $paginationInfo['perPage'], $paginationInfo['total']);
            ?>
        </div>
        <?php
    }
}

/**
 * Retourne l'icône Bootstrap Icons appropriée pour un type de fichier
 */
function getDocumentIcon($fileType) {
    if (strpos($fileType, 'pdf') !== false) {
        return 'bi-file-pdf';
    } elseif (strpos($fileType, 'word') !== false || strpos($fileType, 'doc') !== false) {
        return 'bi-file-word';
    } elseif (strpos($fileType, 'excel') !== false || strpos($fileType, 'sheet') !== false) {
        return 'bi-file-excel';
    } elseif (strpos($fileType, 'powerpoint') !== false || strpos($fileType, 'presentation') !== false) {
        return 'bi-file-slides';
    } elseif (strpos($fileType, 'image') !== false) {
        return 'bi-file-image';
    } elseif (strpos($fileType, 'zip') !== false || strpos($fileType, 'rar') !== false) {
        return 'bi-file-zip';
    } elseif (strpos($fileType, 'text') !== false || $fileType == 'txt') {
        return 'bi-file-text';
    } elseif ($fileType == 'md' || strpos($fileType, 'markdown') !== false) {
        return 'bi-file-code';
    }
    return 'bi-file';
}

/**
 * Retourne le badge HTML pour un type de document
 */
function getDocumentTypeBadge($type) {
    $badges = [
        'contract' => '<span class="badge bg-primary">Contrat</span>',
        'report' => '<span class="badge bg-success">Rapport</span>',
        'evaluation' => '<span class="badge bg-warning">Évaluation</span>',
        'certificate' => '<span class="badge bg-info">Certificat</span>',
        'other' => '<span class="badge bg-secondary">Autre</span>'
    ];
    return $badges[$type] ?? '<span class="badge bg-secondary">Inconnu</span>';
}

/**
 * Retourne le badge HTML pour un statut de document
 */
function getDocumentStatusBadge($status) {
    $badges = [
        'draft' => '<span class="badge bg-secondary">Brouillon</span>',
        'submitted' => '<span class="badge bg-primary">Soumis</span>',
        'approved' => '<span class="badge bg-success">Approuvé</span>',
        'rejected' => '<span class="badge bg-danger">Rejeté</span>'
    ];
    return $badges[$status] ?? '<span class="badge bg-secondary">-</span>';
}

/**
 * Formate la taille d'un fichier
 */
function formatFileSize($size) {
    if ($size == 0) return '-';
    
    $units = ['B', 'KB', 'MB', 'GB'];
    $unitIndex = 0;
    
    while ($size >= 1024 && $unitIndex < count($units) - 1) {
        $size /= 1024;
        $unitIndex++;
    }
    
    return round($size, 2) . ' ' . $units[$unitIndex];
}

/**
 * Génère les boutons d'action pour un document
 */
function renderDocumentActions($document) {
    $actions = '';
    
    // Voir
    $actions .= '<a href="/tutoring/views/admin/documents/show.php?id=' . $document['id'] . '" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Voir">
        <i class="bi bi-eye"></i>
    </a>';
    
    // Modifier (seulement si l'utilisateur est le propriétaire ou admin)
    if ($_SESSION['user_id'] == $document['user_id'] || $_SESSION['role'] == 'admin') {
        $actions .= '<a href="/tutoring/views/admin/documents/edit.php?id=' . $document['id'] . '" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Modifier">
            <i class="bi bi-pencil"></i>
        </a>';
    }
    
    // Télécharger
    $actions .= '<a href="/tutoring/views/admin/documents/download.php?id=' . $document['id'] . '" class="btn btn-sm btn-outline-info" data-bs-toggle="tooltip" title="Télécharger">
        <i class="bi bi-download"></i>
    </a>';
    
    // Supprimer (seulement si l'utilisateur est le propriétaire ou admin)
    if ($_SESSION['user_id'] == $document['user_id'] || $_SESSION['role'] == 'admin') {
        $actions .= '<button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal' . $document['id'] . '" title="Supprimer">
            <i class="bi bi-trash"></i>
        </button>';
        
        // Modal de suppression
        $actions .= renderDeleteModal($document);
    }
    
    return $actions;
}

/**
 * Génère le modal de suppression pour un document
 */
function renderDeleteModal($document) {
    ob_start();
    ?>
    <div class="modal fade" id="deleteModal<?php echo $document['id']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmer la suppression</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Êtes-vous sûr de vouloir supprimer le document <strong><?php echo h($document['title'] ?? ''); ?></strong> ?</p>
                    <p class="text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Cette action est irréversible.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <form action="/tutoring/views/admin/documents/delete.php" method="POST" style="display: inline;">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        <input type="hidden" name="id" value="<?php echo $document['id']; ?>">
                        <button type="submit" class="btn btn-danger">Supprimer</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
?>