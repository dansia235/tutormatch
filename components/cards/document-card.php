<?php
/**
 * Document card component
 * 
 * @param array  $document    Document data
 * @param bool   $showActions Whether to show action buttons (default: true)
 * @param bool   $compact     Whether to use a compact layout (default: false)
 * @param string $class       Additional CSS classes
 */

// Extract document data
if (!isset($document) || empty($document)) {
    return;
}

// Set default values
$showActions = $showActions ?? true;
$compact = $compact ?? false;
$class = $class ?? '';

// Determine file type icon and color
$iconClass = 'fa-file';
$colorClass = 'text-gray-500';
$bgColorClass = 'bg-gray-100';

$fileExtension = pathinfo($document['file_path'], PATHINFO_EXTENSION);
$fileType = $document['file_type'] ?? '';

if (strpos($fileType, 'pdf') !== false || $fileExtension === 'pdf') {
    $iconClass = 'fa-file-pdf';
    $colorClass = 'text-red-600';
    $bgColorClass = 'bg-red-50';
} elseif (strpos($fileType, 'word') !== false || strpos($fileType, 'document') !== false || in_array($fileExtension, ['doc', 'docx'])) {
    $iconClass = 'fa-file-word';
    $colorClass = 'text-blue-600';
    $bgColorClass = 'bg-blue-50';
} elseif (strpos($fileType, 'excel') !== false || strpos($fileType, 'sheet') !== false || in_array($fileExtension, ['xls', 'xlsx', 'csv'])) {
    $iconClass = 'fa-file-excel';
    $colorClass = 'text-green-600';
    $bgColorClass = 'bg-green-50';
} elseif (strpos($fileType, 'image') !== false || in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])) {
    $iconClass = 'fa-file-image';
    $colorClass = 'text-blue-500';
    $bgColorClass = 'bg-blue-50';
} elseif (strpos($fileType, 'presentation') !== false || strpos($fileType, 'powerpoint') !== false || in_array($fileExtension, ['ppt', 'pptx'])) {
    $iconClass = 'fa-file-powerpoint';
    $colorClass = 'text-orange-600';
    $bgColorClass = 'bg-orange-50';
} elseif (strpos($fileType, 'zip') !== false || strpos($fileType, 'compressed') !== false || in_array($fileExtension, ['zip', 'rar', '7z', 'tar', 'gz'])) {
    $iconClass = 'fa-file-archive';
    $colorClass = 'text-yellow-600';
    $bgColorClass = 'bg-yellow-50';
} elseif (strpos($fileType, 'text') !== false || in_array($fileExtension, ['txt', 'md', 'rtf'])) {
    $iconClass = 'fa-file-alt';
    $colorClass = 'text-gray-600';
    $bgColorClass = 'bg-gray-50';
}

// Format upload date
$uploadDate = date('d/m/Y', strtotime($document['upload_date'] ?? 'now'));

// Format file size
$fileSize = '';
if (isset($document['file_size'])) {
    $bytes = $document['file_size'];
    if ($bytes < 1024) {
        $fileSize = $bytes . ' o';
    } elseif ($bytes < 1048576) {
        $fileSize = round($bytes / 1024, 2) . ' Ko';
    } elseif ($bytes < 1073741824) {
        $fileSize = round($bytes / 1048576, 2) . ' Mo';
    } else {
        $fileSize = round($bytes / 1073741824, 2) . ' Go';
    }
}

// Document type
$docTypes = [
    'cv' => 'CV',
    'report' => 'Rapport',
    'agreement' => 'Convention',
    'evaluation' => 'Évaluation',
    'contract' => 'Contrat',
    'administrative' => 'Administratif',
    'image' => 'Image',
    'presentation' => 'Présentation',
    'other' => 'Autre'
];

$docType = $docTypes[$document['type'] ?? 'other'] ?? 'Document';

// Document status
$statusClasses = [
    'pending' => 'bg-yellow-100 text-yellow-800',
    'approved' => 'bg-green-100 text-green-800',
    'rejected' => 'bg-red-100 text-red-800',
    'draft' => 'bg-gray-100 text-gray-800',
    'active' => 'bg-blue-100 text-blue-800',
    'archived' => 'bg-purple-100 text-purple-800'
];

$statusLabels = [
    'pending' => 'En attente',
    'approved' => 'Validé',
    'rejected' => 'Rejeté',
    'draft' => 'Brouillon',
    'active' => 'Actif',
    'archived' => 'Archivé'
];

$status = $document['status'] ?? 'draft';
$statusClass = $statusClasses[$status] ?? 'bg-gray-100 text-gray-800';
$statusLabel = $statusLabels[$status] ?? ucfirst($status);

// Document URL
$documentUrl = '/tutoring/' . $document['file_path'];

// Document owner
$ownerName = $document['first_name'] . ' ' . $document['last_name'] ?? 'Utilisateur inconnu';
$ownerRole = $document['role'] ?? '';
?>

<?php if ($compact): ?>
<!-- Compact Card Layout -->
<div class="bg-white rounded-lg shadow-sm border overflow-hidden hover:shadow-md transition-shadow duration-300 <?php echo h($class); ?>">
    <div class="flex items-center p-3">
        <div class="flex-shrink-0 rounded-lg <?php echo h($bgColorClass); ?> p-2 mr-3">
            <i class="fas <?php echo h($iconClass); ?> <?php echo h($colorClass); ?> text-xl"></i>
        </div>
        <div class="flex-grow min-w-0">
            <h5 class="text-sm font-medium text-gray-900 truncate" title="<?php echo h($document['title']); ?>"><?php echo h($document['title']); ?></h5>
            <p class="text-xs text-gray-500"><?php echo h($docType); ?> • <?php echo h($uploadDate); ?></p>
        </div>
        <?php if ($showActions): ?>
        <div class="flex-shrink-0 ml-2">
            <div class="dropdown">
                <button class="text-gray-400 hover:text-gray-500 focus:outline-none" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="<?php echo h($documentUrl); ?>" target="_blank"><i class="fas fa-eye mr-2"></i>Visualiser</a></li>
                    <li><a class="dropdown-item" href="<?php echo h($documentUrl); ?>" download><i class="fas fa-download mr-2"></i>Télécharger</a></li>
                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $document['user_id']): ?>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item text-red-600 hover:text-red-800" href="#" 
                           data-bs-toggle="modal" 
                           data-bs-target="#deleteDocumentModal" 
                           data-document-id="<?php echo $document['id']; ?>">
                            <i class="fas fa-trash mr-2"></i>Supprimer
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php else: ?>
<!-- Full Card Layout -->
<div class="bg-white rounded-lg shadow overflow-hidden hover:shadow-md transition-shadow duration-300 <?php echo h($class); ?>" data-document-id="<?php echo $document['id']; ?>">
    <div class="p-4">
        <div class="flex items-center mb-3">
            <div class="flex-shrink-0 rounded-lg <?php echo h($bgColorClass); ?> p-3 mr-4">
                <i class="fas <?php echo h($iconClass); ?> <?php echo h($colorClass); ?> text-2xl"></i>
            </div>
            <div class="flex-grow">
                <h4 class="text-lg font-semibold text-gray-900"><?php echo h($document['title']); ?></h4>
                <div class="flex items-center text-sm text-gray-500">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium <?php echo h($statusClass); ?> mr-2">
                        <?php echo h($statusLabel); ?>
                    </span>
                    <span class="mr-2"><?php echo h($docType); ?></span>
                    <span><?php echo h($uploadDate); ?></span>
                    <?php if ($fileSize): ?>
                    <span class="mx-1">•</span>
                    <span><?php echo h($fileSize); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php if (!empty($document['description'])): ?>
        <div class="mb-4 text-sm text-gray-600 border-l-4 border-gray-200 pl-3 py-1">
            <?php echo h($document['description']); ?>
        </div>
        <?php endif; ?>
        
        <div class="flex items-center justify-between mt-3">
            <div class="flex items-center text-sm text-gray-500">
                <i class="far fa-user mr-1"></i>
                <span><?php echo h($ownerName); ?></span>
                <?php if ($ownerRole): ?>
                <span class="ml-1 text-gray-400">(<?php echo h($ownerRole); ?>)</span>
                <?php endif; ?>
            </div>
            
            <?php if ($showActions): ?>
            <div class="flex space-x-2">
                <a href="<?php echo h($documentUrl); ?>" class="inline-flex items-center px-3 py-1 border border-gray-300 rounded-md text-sm leading-4 font-medium text-gray-700 bg-white hover:bg-gray-50" target="_blank">
                    <i class="fas fa-eye mr-1"></i> Voir
                </a>
                <a href="<?php echo h($documentUrl); ?>" class="inline-flex items-center px-3 py-1 border border-transparent rounded-md text-sm leading-4 font-medium text-white bg-blue-600 hover:bg-blue-700" download>
                    <i class="fas fa-download mr-1"></i> Télécharger
                </a>
                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $document['user_id']): ?>
                <button type="button" 
                        class="inline-flex items-center px-3 py-1 border border-transparent rounded-md text-sm leading-4 font-medium text-white bg-red-600 hover:bg-red-700"
                        data-bs-toggle="modal" 
                        data-bs-target="#deleteDocumentModal" 
                        data-document-id="<?php echo $document['id']; ?>">
                    <i class="fas fa-trash mr-1"></i> Supprimer
                </button>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>