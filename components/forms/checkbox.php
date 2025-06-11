<?php
/**
 * Checkbox component
 * 
 * @param string $name       Field name
 * @param string $label      Field label
 * @param bool   $checked    Whether the checkbox is checked
 * @param string $value      Checkbox value
 * @param bool   $required   Whether the field is required
 * @param string $error      Error message
 * @param string $id         Field ID (defaults to name if not provided)
 * @param string $class      Additional CSS classes
 * @param array  $attributes Additional HTML attributes
 */

// Extract variables
$id = $id ?? $name;
$required = $required ?? false;
$error = $error ?? '';
$checked = $checked ?? false;
$value = $value ?? '1';
$class = $class ?? '';
$attributes = $attributes ?? [];

// Build additional attributes string
$attributesStr = '';
foreach ($attributes as $attr => $val) {
    $attributesStr .= ' ' . $attr . '="' . htmlspecialchars($val) . '"';
}
?>

<div class="form-group mb-3" data-controller="form-field" data-form-field-name-value="<?php echo htmlspecialchars($name); ?>">
    <div class="flex items-center">
        <input
            type="checkbox"
            id="<?php echo htmlspecialchars($id); ?>"
            name="<?php echo htmlspecialchars($name); ?>"
            value="<?php echo htmlspecialchars($value); ?>"
            class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded <?php echo $error ? 'border-red-500' : ''; ?> <?php echo htmlspecialchars($class); ?>"
            <?php echo $checked ? 'checked' : ''; ?>
            <?php echo $required ? 'required' : ''; ?>
            <?php echo $attributesStr; ?>
            data-form-field-target="input"
        >
        
        <?php if (isset($label)): ?>
        <label for="<?php echo htmlspecialchars($id); ?>" class="ml-2 block text-sm text-gray-700">
            <?php echo htmlspecialchars($label); ?>
            <?php if ($required): ?>
            <span class="text-red-500">*</span>
            <?php endif; ?>
        </label>
        <?php endif; ?>
    </div>
    
    <?php if ($error): ?>
    <div class="error-message text-red-500 text-sm mt-1" data-form-field-target="error">
        <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>
</div>