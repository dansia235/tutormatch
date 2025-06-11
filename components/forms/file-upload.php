<?php
/**
 * File upload component
 * 
 * @param string $name          Input name
 * @param string $label         Input label
 * @param string $accept        Accepted file types (e.g. ".pdf,.docx,image/*")
 * @param string $maxSize       Maximum file size in MB (default: 10)
 * @param bool   $required      Whether the file is required (default: false)
 * @param string $allowedFormats Text to display for allowed formats (default: "")
 * @param string $id            Input ID (default: name)
 * @param string $class         Additional CSS class
 * @param array  $attributes    Additional HTML attributes
 */

// Extract variables
$id = $id ?? $name;
$required = $required ?? false;
$accept = $accept ?? '';
$maxSize = $maxSize ?? 10;
$allowedFormats = $allowedFormats ?? '';
$class = $class ?? '';

// Build additional attributes string
$attributesStr = '';
foreach ($attributes ?? [] as $attr => $val) {
    $attributesStr .= ' ' . $attr . '="' . htmlspecialchars($val) . '"';
}

// Determine allowed types value for controller
$allowedTypesValue = '';
if ($accept) {
    $acceptValues = explode(',', $accept);
    $allowedTypes = [];
    foreach ($acceptValues as $val) {
        $val = trim($val);
        // Convert file extensions to MIME types
        if (substr($val, 0, 1) === '.') {
            switch ($val) {
                case '.pdf':
                    $allowedTypes[] = 'application/pdf';
                    break;
                case '.doc':
                case '.docx':
                    $allowedTypes[] = 'application/msword';
                    $allowedTypes[] = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
                    break;
                case '.xls':
                case '.xlsx':
                    $allowedTypes[] = 'application/vnd.ms-excel';
                    $allowedTypes[] = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
                    break;
                case '.ppt':
                case '.pptx':
                    $allowedTypes[] = 'application/vnd.ms-powerpoint';
                    $allowedTypes[] = 'application/vnd.openxmlformats-officedocument.presentationml.presentation';
                    break;
                case '.jpg':
                case '.jpeg':
                    $allowedTypes[] = 'image/jpeg';
                    break;
                case '.png':
                    $allowedTypes[] = 'image/png';
                    break;
                case '.gif':
                    $allowedTypes[] = 'image/gif';
                    break;
                default:
                    $allowedTypes[] = $val;
                    break;
            }
        } else {
            $allowedTypes[] = $val;
        }
    }
    $allowedTypesValue = implode(',', $allowedTypes);
}

// Convert MB to bytes for JS controller
$maxSizeBytes = $maxSize * 1024 * 1024;
?>

<div class="space-y-2 mb-4" data-controller="file-upload" data-file-upload-allowed-types-value="<?php echo h($allowedTypesValue); ?>" data-file-upload-max-size-value="<?php echo $maxSizeBytes; ?>">
    <?php if (isset($label)): ?>
    <label for="<?php echo h($id); ?>" class="block text-sm font-medium text-gray-700">
        <?php echo h($label); ?>
        <?php if ($required): ?>
        <span class="text-red-500">*</span>
        <?php endif; ?>
    </label>
    <?php endif; ?>
    
    <div class="relative">
        <!-- Hidden file input -->
        <input 
            type="file" 
            id="<?php echo h($id); ?>" 
            name="<?php echo h($name); ?>" 
            class="sr-only" 
            <?php echo $required ? 'required' : ''; ?> 
            <?php echo $accept ? 'accept="' . h($accept) . '"' : ''; ?> 
            <?php echo $attributesStr; ?> 
            data-file-upload-target="input"
        >
        
        <!-- Custom dropzone -->
        <div 
            class="border-2 border-dashed border-gray-300 rounded-lg bg-gray-50 hover:bg-gray-100 px-6 pt-5 pb-6 flex flex-col items-center justify-center cursor-pointer transition-colors duration-300 <?php echo h($class); ?>"
            data-file-upload-target="dropzone"
        >
            <div class="text-center">
                <!-- Preview container (hidden by default) -->
                <div class="hidden mb-3 h-24 w-24 mx-auto rounded-md overflow-hidden bg-gray-100" data-file-upload-target="preview"></div>
                
                <!-- Default icon and text -->
                <div class="mb-3 text-gray-400">
                    <i class="fas fa-cloud-upload-alt text-3xl"></i>
                </div>
                <p class="text-sm text-gray-600">
                    <span class="font-medium text-blue-600 hover:text-blue-500">Cliquez pour sélectionner un fichier</span> ou glissez-déposez
                </p>
                <p class="mt-1 text-xs text-gray-500" id="file-upload-description">
                    <?php if ($allowedFormats): ?>
                    <span class="block">Formats acceptés : <?php echo h($allowedFormats); ?></span>
                    <?php endif; ?>
                    <span class="block">Taille maximale : <?php echo h($maxSize); ?> Mo</span>
                </p>
            </div>
            
            <!-- File info (shown when file is selected) -->
            <div class="hidden mt-3 w-full bg-white rounded-md px-3 py-2 border border-gray-200" data-file-upload-target="fileinfo">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <i class="fas fa-file-alt text-gray-400 mr-2"></i>
                        <div>
                            <div class="text-sm font-medium text-gray-900" data-file-upload-target="filename"></div>
                            <div class="flex text-xs text-gray-500">
                                <span data-file-upload-target="size"></span>
                                <span class="mx-1" data-file-upload-target="separator">•</span>
                                <span data-file-upload-target="type"></span>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="text-gray-400 hover:text-red-500" data-file-upload-target="removeBtn">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <!-- Progress bar -->
                <div class="hidden mt-2 w-full bg-gray-200 rounded-full h-1.5" data-file-upload-target="progress">
                    <div class="bg-blue-600 h-1.5 rounded-full" style="width: 0%" data-file-upload-target="progressBar"></div>
                </div>
            </div>
            
            <!-- Error message -->
            <div class="hidden mt-2 text-sm text-red-600" data-file-upload-target="error"></div>
        </div>
    </div>
    
    <p class="mt-1 text-xs text-gray-500">
        <?php if (!empty($description)): ?>
        <?php echo h($description); ?>
        <?php endif; ?>
    </p>
</div>