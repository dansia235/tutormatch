<?php
/**
 * Search result item component
 * 
 * @param array  $result    Result data with keys: id, title, subtitle, image, url
 * @param string $class     Additional CSS classes
 * @param array  $attributes Additional HTML attributes
 */

// Extract variables
$result = $result ?? [];
$class = $class ?? '';
$attributes = $attributes ?? [];

// Required fields
$id = $result['id'] ?? '';
$title = $result['title'] ?? '';
$subtitle = $result['subtitle'] ?? '';
$image = $result['image'] ?? '';
$url = $result['url'] ?? '#';

// Build additional attributes string
$attributesStr = '';
foreach ($attributes as $attr => $val) {
    $attributesStr .= ' ' . $attr . '="' . htmlspecialchars($val) . '"';
}
?>

<a 
    href="<?php echo htmlspecialchars($url); ?>"
    class="search-result block px-4 py-3 hover:bg-gray-50 transition-colors duration-200 <?php echo htmlspecialchars($class); ?>"
    data-id="<?php echo htmlspecialchars($id); ?>"
    <?php echo $attributesStr; ?>
>
    <div class="flex items-center">
        <?php if ($image): ?>
        <div class="flex-shrink-0">
            <img src="<?php echo htmlspecialchars($image); ?>" alt="" class="h-10 w-10 rounded-full object-cover">
        </div>
        <?php endif; ?>
        
        <div class="<?php echo $image ? 'ml-3' : ''; ?>">
            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($title); ?></div>
            <?php if ($subtitle): ?>
            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($subtitle); ?></div>
            <?php endif; ?>
        </div>
    </div>
</a>