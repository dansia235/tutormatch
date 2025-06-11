<?php
/**
 * Radio button component
 * 
 * @param string $name       Field name
 * @param array  $options    Array of options (value => label)
 * @param string $selected   Selected option value
 * @param bool   $required   Whether the field is required
 * @param string $label      Group label
 * @param string $error      Error message
 * @param string $class      Additional CSS classes
 * @param array  $attributes Additional HTML attributes for the inputs
 */

// Extract variables
$required = $required ?? false;
$error = $error ?? '';
$options = $options ?? [];
$selected = $selected ?? '';
$class = $class ?? '';
$attributes = $attributes ?? [];

// Build additional attributes string
$attributesStr = '';
foreach ($attributes as $attr => $val) {
    $attributesStr .= ' ' . $attr . '="' . htmlspecialchars($val) . '"';
}
?>

<div class="form-group mb-4" data-controller="form-field" data-form-field-name-value="<?php echo htmlspecialchars($name); ?>">
    <?php if (isset($label)): ?>
    <div class="form-label mb-2">
        <?php echo htmlspecialchars($label); ?>
        <?php if ($required): ?>
        <span class="text-red-500">*</span>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <div class="space-y-2">
        <?php foreach ($options as $value => $optionLabel): ?>
            <?php $optionId = $name . '_' . $value; ?>
            <div class="flex items-center">
                <input
                    type="radio"
                    id="<?php echo htmlspecialchars($optionId); ?>"
                    name="<?php echo htmlspecialchars($name); ?>"
                    value="<?php echo htmlspecialchars($value); ?>"
                    class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 <?php echo $error ? 'border-red-500' : ''; ?> <?php echo htmlspecialchars($class); ?>"
                    <?php echo $selected == $value ? 'checked' : ''; ?>
                    <?php echo $required ? 'required' : ''; ?>
                    <?php echo $attributesStr; ?>
                    data-form-field-target="input"
                >
                <label for="<?php echo htmlspecialchars($optionId); ?>" class="ml-2 block text-sm text-gray-700">
                    <?php echo htmlspecialchars($optionLabel); ?>
                </label>
            </div>
        <?php endforeach; ?>
    </div>
    
    <?php if ($error): ?>
    <div class="error-message text-red-500 text-sm mt-1" data-form-field-target="error">
        <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>
</div>