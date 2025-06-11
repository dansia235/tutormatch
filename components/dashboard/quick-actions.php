<?php
/**
 * Quick Actions Component for Dashboard
 * Displays a collection of action buttons for quick access
 * 
 * @param array $actions Array of action objects with keys: label, url, icon, isPrimary, class
 * @param string $title Component title (default: "Actions rapides")
 * @param string $class Additional CSS classes
 */

// Set defaults
$title = $title ?? 'Actions rapides';
$class = $class ?? '';
$actions = $actions ?? [];

// Generate a unique ID for this component
$id = 'quick-actions-' . uniqid();
?>

<div class="bg-white overflow-hidden shadow-sm rounded-lg <?php echo h($class); ?>" id="<?php echo $id; ?>">
    <div class="px-4 py-5 sm:p-6">
        <?php if ($title): ?>
        <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">
            <?php echo h($title); ?>
        </h3>
        <?php endif; ?>
        
        <div class="space-y-3">
            <?php foreach ($actions as $action): 
                // Set defaults for each action
                $isPrimary = isset($action['isPrimary']) ? $action['isPrimary'] : false;
                $icon = isset($action['icon']) ? $action['icon'] : '';
                $label = isset($action['label']) ? $action['label'] : 'Action';
                $url = isset($action['url']) ? $action['url'] : '#';
                $class = isset($action['class']) ? $action['class'] : '';
                
                // Determine button style based on isPrimary flag
                $btnClass = $isPrimary 
                    ? 'bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-500 text-white' 
                    : 'bg-white hover:bg-gray-50 border-gray-300 text-gray-700 focus:ring-indigo-500';
            ?>
            <a 
                href="<?php echo h($url); ?>" 
                class="w-full inline-flex items-center justify-center px-4 py-2 border rounded-md shadow-sm text-sm font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 <?php echo h($btnClass); ?> <?php echo h($class); ?>"
                <?php if (isset($action['attributes'])): 
                    foreach ($action['attributes'] as $attr => $value): ?>
                        <?php echo h($attr); ?>="<?php echo h($value); ?>"
                    <?php endforeach;
                endif; ?>
            >
                <?php if ($icon): ?>
                <span class="mr-2">
                    <?php echo $icon; ?>
                </span>
                <?php endif; ?>
                <?php echo h($label); ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>