<?php
/**
 * Stat card component for dashboard statistics
 * 
 * @param string $title      Stat title/label
 * @param string $value      Stat value
 * @param string $icon       Icon HTML
 * @param string $change     Change indicator (e.g., +5%, -10%)
 * @param string $changeType Change type (positive, negative, neutral)
 * @param string $link       Optional link
 * @param string $linkText   Optional link text
 * @param string $id         Card ID
 * @param string $class      Additional CSS classes
 * @param array  $attributes Additional HTML attributes
 */

// Extract variables
$id = $id ?? 'stat-card-' . uniqid();
$class = $class ?? '';
$icon = $icon ?? '';
$change = $change ?? '';
$changeType = $changeType ?? 'neutral';
$link = $link ?? '';
$linkText = $linkText ?? 'Voir plus';
$attributes = $attributes ?? [];

// Determine change class based on change type
$changeClass = '';
$changeIconHtml = '';
switch ($changeType) {
    case 'positive':
        $changeClass = 'text-success-500';
        $changeIconHtml = '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd" />
        </svg>';
        break;
    case 'negative':
        $changeClass = 'text-danger-500';
        $changeIconHtml = '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M12 13a1 1 0 100 2h5a1 1 0 001-1v-5a1 1 0 10-2 0v2.586l-4.293-4.293a1 1 0 00-1.414 0L8 9.586 3.707 5.293a1 1 0 00-1.414 1.414l5 5a1 1 0 001.414 0L11 9.414 14.586 13H12z" clip-rule="evenodd" />
        </svg>';
        break;
    default:
        $changeClass = 'text-gray-500';
        break;
}

// Build additional attributes string
$attributesStr = '';
foreach ($attributes as $attr => $val) {
    $attributesStr .= ' ' . $attr . '="' . htmlspecialchars($val) . '"';
}
?>

<div 
    id="<?php echo htmlspecialchars($id); ?>"
    class="card stat-card transition-all duration-300 hover:shadow-lg <?php echo htmlspecialchars($class); ?>"
    <?php echo $attributesStr; ?>
>
    <div class="card-body p-5">
        <div class="flex items-center justify-between mb-3">
            <div class="text-sm font-medium text-gray-500 uppercase tracking-wide">
                <?php echo htmlspecialchars($title); ?>
            </div>
            
            <?php if ($icon): ?>
            <div class="text-primary-500">
                <?php echo $icon; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="text-3xl font-bold text-secondary-700 mb-2">
            <?php echo $value; ?>
        </div>
        
        <?php if ($change): ?>
        <div class="text-sm <?php echo $changeClass; ?>">
            <?php echo $changeIconHtml; ?> <?php echo htmlspecialchars($change); ?>
        </div>
        <?php endif; ?>
        
        <?php if ($link): ?>
        <div class="mt-4 text-sm">
            <a href="<?php echo htmlspecialchars($link); ?>" class="text-primary-600 hover:text-primary-800 font-medium flex items-center">
                <?php echo htmlspecialchars($linkText); ?>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L12.586 11H5a1 1 0 110-2h7.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>