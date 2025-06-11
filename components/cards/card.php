<?php
/**
 * Card component
 * 
 * @param string $title      Card title
 * @param string $content    Card content
 * @param string $footer     Card footer
 * @param string $id         Card ID
 * @param string $class      Additional CSS classes
 * @param bool   $collapsible Whether the card is collapsible
 * @param bool   $collapsed   Whether the card is initially collapsed
 * @param array  $attributes Additional HTML attributes
 */

// Extract variables
$id = $id ?? 'card-' . uniqid();
$class = $class ?? '';
$collapsible = $collapsible ?? false;
$collapsed = $collapsed ?? false;
$attributes = $attributes ?? [];

// Build additional attributes string
$attributesStr = '';
foreach ($attributes as $attr => $val) {
    $attributesStr .= ' ' . $attr . '="' . htmlspecialchars($val) . '"';
}

// Set up data attributes for collapsible functionality
$dataAttributes = '';
if ($collapsible) {
    $dataAttributes = ' data-controller="collapse"';
    if ($collapsed) {
        $dataAttributes .= ' data-collapse-collapsed-value="true"';
    }
}
?>

<div 
    id="<?php echo htmlspecialchars($id); ?>"
    class="card <?php echo htmlspecialchars($class); ?>"
    <?php echo $dataAttributes; ?>
    <?php echo $attributesStr; ?>
>
    <?php if (isset($title)): ?>
    <div class="card-header">
        <h3 class="text-lg font-medium"><?php echo $title; ?></h3>
        
        <?php if ($collapsible): ?>
        <button 
            type="button" 
            class="text-gray-500 hover:text-gray-700 focus:outline-none"
            data-action="collapse#toggle"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transform transition-transform duration-300 <?php echo $collapsed ? '' : 'rotate-180'; ?>" viewBox="0 0 20 20" fill="currentColor" data-collapse-target="icon">
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
        </button>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <div class="card-body <?php echo ($collapsible && $collapsed) ? 'hidden' : ''; ?>" <?php echo $collapsible ? 'data-collapse-target="content"' : ''; ?>>
        <?php echo $content ?? ''; ?>
    </div>
    
    <?php if (isset($footer)): ?>
    <div class="card-footer border-t border-gray-200 px-4 py-3 bg-gray-50 <?php echo ($collapsible && $collapsed) ? 'hidden' : ''; ?>" <?php echo $collapsible ? 'data-collapse-target="footer"' : ''; ?>>
        <?php echo $footer; ?>
    </div>
    <?php endif; ?>
</div>