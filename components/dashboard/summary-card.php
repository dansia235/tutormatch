<?php
/**
 * Summary Card Component
 * Displays summary information with optional action links
 * 
 * @param string $title Card title
 * @param string $content Main content HTML
 * @param array $actions Array of action links (optional)
 * @param string $icon Optional icon HTML
 * @param string $class Additional CSS classes
 * @param string $badgeText Optional badge text
 * @param string $badgeColor Optional badge color (default, success, warning, danger, info)
 * @param bool $loading Whether to show a loading state
 * @param string $emptyMessage Message to show when there's no content
 */

// Set defaults
$title = $title ?? 'Summary';
$class = $class ?? '';
$actions = $actions ?? [];
$loading = $loading ?? false;
$badgeText = $badgeText ?? '';
$badgeColor = $badgeColor ?? 'default';

// Map badge colors to Tailwind classes
$badgeClasses = [
    'default' => 'bg-gray-100 text-gray-800',
    'success' => 'bg-green-100 text-green-800',
    'warning' => 'bg-yellow-100 text-yellow-800',
    'danger' => 'bg-red-100 text-red-800',
    'info' => 'bg-blue-100 text-blue-800'
];
$badgeClass = $badgeClasses[$badgeColor] ?? $badgeClasses['default'];

// Generate a unique ID for this component
$id = 'summary-card-' . uniqid();
?>

<div class="bg-white shadow-sm rounded-lg overflow-hidden <?php echo h($class); ?>" id="<?php echo $id; ?>">
    <div class="px-4 py-5 sm:px-6 flex justify-between items-center border-b border-gray-200">
        <div class="flex items-center">
            <?php if (isset($icon)): ?>
            <div class="flex-shrink-0 mr-3">
                <?php echo $icon; ?>
            </div>
            <?php endif; ?>
            
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                <?php echo h($title); ?>
            </h3>
            
            <?php if ($badgeText): ?>
            <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $badgeClass; ?>">
                <?php echo h($badgeText); ?>
            </span>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($actions)): ?>
        <div class="flex space-x-1">
            <?php foreach ($actions as $action): 
                $actionClass = $action['class'] ?? 'text-gray-400 hover:text-gray-500';
                $actionTitle = $action['title'] ?? '';
                $actionUrl = $action['url'] ?? '#';
                $actionIcon = $action['icon'] ?? '';
                $actionType = $action['type'] ?? 'link';
                $actionAttributes = $action['attributes'] ?? [];
                
                // Build attributes string
                $attributesStr = '';
                foreach ($actionAttributes as $attr => $val) {
                    $attributesStr .= ' ' . $attr . '="' . h($val) . '"';
                }
            ?>
                <?php if ($actionType === 'button'): ?>
                <button 
                    type="button" 
                    class="p-1 rounded-full <?php echo h($actionClass); ?> focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    title="<?php echo h($actionTitle); ?>"
                    <?php echo $attributesStr; ?>
                >
                    <?php echo $actionIcon; ?>
                </button>
                <?php else: ?>
                <a 
                    href="<?php echo h($actionUrl); ?>" 
                    class="p-1 rounded-full <?php echo h($actionClass); ?> focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    title="<?php echo h($actionTitle); ?>"
                    <?php echo $attributesStr; ?>
                >
                    <?php echo $actionIcon; ?>
                </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="px-4 py-5 sm:p-6">
        <?php if ($loading): ?>
        <div class="flex justify-center py-6">
            <svg class="animate-spin h-8 w-8 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>
        <?php elseif (empty($content) && isset($emptyMessage)): ?>
        <div class="text-center py-6">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Aucune donnée disponible</h3>
            <p class="mt-1 text-sm text-gray-500"><?php echo h($emptyMessage); ?></p>
        </div>
        <?php else: ?>
        <?php echo $content; ?>
        <?php endif; ?>
    </div>
</div>