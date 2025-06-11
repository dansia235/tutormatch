<?php
/**
 * Notification container component
 * 
 * @param string $position   Position of the notifications (top-right, top-left, top-center, bottom-right, bottom-left, bottom-center)
 * @param int    $duration   Duration in milliseconds to show each notification
 * @param int    $maxCount   Maximum number of notifications to show at once
 * @param string $id         Container ID
 * @param array  $attributes Additional HTML attributes
 */

// Extract variables
$position = $position ?? 'top-right';
$duration = $duration ?? 5000;
$maxCount = $maxCount ?? 5;
$id = $id ?? 'notification-container';
$attributes = $attributes ?? [];

// Build additional attributes string
$attributesStr = '';
foreach ($attributes as $attr => $val) {
    $attributesStr .= ' ' . $attr . '="' . htmlspecialchars($val) . '"';
}
?>

<div
    id="<?php echo htmlspecialchars($id); ?>"
    data-controller="notification"
    data-notification-position-value="<?php echo htmlspecialchars($position); ?>"
    data-notification-duration-value="<?php echo (int)$duration; ?>"
    data-notification-max-count-value="<?php echo (int)$maxCount; ?>"
    <?php echo $attributesStr; ?>
>
    <!-- Notification template -->
    <template data-notification-target="template">
        <div class="notification transform transition-all duration-300 max-w-sm w-full bg-white shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden">
            <div class="p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0 notification-icon"></div>
                    <div class="ml-3 w-0 flex-1 pt-0.5 notification-content"></div>
                    <div class="ml-4 flex-shrink-0 flex">
                        <button type="button" class="bg-white rounded-md inline-flex text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500" data-action="notification#close">
                            <span class="sr-only">Fermer</span>
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <!-- Container will be created dynamically -->
</div>