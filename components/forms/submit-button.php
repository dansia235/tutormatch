<?php
/**
 * Submit button component
 * 
 * @param string $text       Button text
 * @param string $type       Button type (submit, button, reset)
 * @param string $variant    Button variant (primary, secondary, success, danger, warning, info)
 * @param string $size       Button size (sm, md, lg)
 * @param string $id         Button ID
 * @param string $class      Additional CSS classes
 * @param array  $attributes Additional HTML attributes
 */

// Extract variables
$text = $text ?? 'Soumettre';
$type = $type ?? 'submit';
$variant = $variant ?? 'primary';
$size = $size ?? 'md';
$id = $id ?? 'submit-' . uniqid();
$attributes = $attributes ?? [];

// Determine button classes based on variant and size
$variantClass = 'btn-' . $variant;
$sizeClass = '';
if ($size === 'sm') {
    $sizeClass = 'btn-sm';
} elseif ($size === 'lg') {
    $sizeClass = 'btn-lg';
}

$class = 'btn ' . $variantClass . ' ' . $sizeClass . ' ' . ($class ?? '');

// Build additional attributes string
$attributesStr = '';
foreach ($attributes as $attr => $val) {
    $attributesStr .= ' ' . $attr . '="' . htmlspecialchars($val) . '"';
}
?>

<div class="form-group mt-6">
    <button
        type="<?php echo htmlspecialchars($type); ?>"
        id="<?php echo htmlspecialchars($id); ?>"
        class="<?php echo htmlspecialchars(trim($class)); ?>"
        data-form-target="submit"
        <?php echo $attributesStr; ?>
    >
        <?php echo htmlspecialchars($text); ?>
    </button>
</div>