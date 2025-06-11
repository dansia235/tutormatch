<?php
/**
 * Textarea component
 * 
 * @param string $name        Field name
 * @param string $label       Field label
 * @param string $value       Textarea value
 * @param bool   $required    Whether the field is required
 * @param string $placeholder Placeholder text
 * @param string $error       Error message
 * @param string $id          Field ID (defaults to name if not provided)
 * @param string $class       Additional CSS classes
 * @param int    $rows        Number of rows
 * @param array  $attributes  Additional HTML attributes
 */

// Extract variables
$id = $id ?? $name;
$required = $required ?? false;
$error = $error ?? '';
$value = isset($value) ? htmlspecialchars($value) : '';
$placeholder = $placeholder ?? '';
$class = $class ?? '';
$rows = $rows ?? 4;
$attributes = $attributes ?? [];

// Build additional attributes string
$attributesStr = '';
foreach ($attributes as $attr => $val) {
    $attributesStr .= ' ' . $attr . '="' . htmlspecialchars($val) . '"';
}
?>

<div class="form-group mb-4" data-controller="form-field" data-form-field-name-value="<?php echo htmlspecialchars($name); ?>">
    <?php if (isset($label)): ?>
    <label for="<?php echo htmlspecialchars($id); ?>" class="form-label">
        <?php echo htmlspecialchars($label); ?>
        <?php if ($required): ?>
        <span class="text-red-500">*</span>
        <?php endif; ?>
    </label>
    <?php endif; ?>
    
    <textarea
        id="<?php echo htmlspecialchars($id); ?>"
        name="<?php echo htmlspecialchars($name); ?>"
        rows="<?php echo (int)$rows; ?>"
        placeholder="<?php echo htmlspecialchars($placeholder); ?>"
        class="form-control <?php echo $error ? 'border-red-500' : ''; ?> <?php echo htmlspecialchars($class); ?>"
        <?php echo $required ? 'required' : ''; ?>
        <?php echo $attributesStr; ?>
        data-form-field-target="input"
    ><?php echo $value; ?></textarea>
    
    <?php if ($error): ?>
    <div class="error-message text-red-500 text-sm mt-1" data-form-field-target="error">
        <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>
</div>