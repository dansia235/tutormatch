<?php
/**
 * Form component
 * 
 * @param string $action     Form action URL
 * @param string $method     Form method (GET, POST)
 * @param string $id         Form ID
 * @param string $class      Additional CSS classes
 * @param bool   $multipart  Whether the form supports file uploads
 * @param string $submitUrl  URL for AJAX submission (if using AJAX)
 * @param string $redirectUrl URL to redirect after successful submission (if using AJAX)
 * @param string $validateUrl URL for field validation (if using AJAX validation)
 * @param array  $attributes Additional HTML attributes
 */

// Extract variables
$method = $method ?? 'POST';
$id = $id ?? 'form-' . uniqid();
$class = $class ?? '';
$multipart = $multipart ?? false;
$enctype = $multipart ? 'multipart/form-data' : '';
$submitUrl = $submitUrl ?? '';
$redirectUrl = $redirectUrl ?? '';
$validateUrl = $validateUrl ?? '';
$attributes = $attributes ?? [];

// Build data attributes for Stimulus controller
$dataAttributes = '';
if ($submitUrl) {
    $dataAttributes .= ' data-form-submit-url-value="' . htmlspecialchars($submitUrl) . '"';
}
if ($redirectUrl) {
    $dataAttributes .= ' data-form-redirect-url-value="' . htmlspecialchars($redirectUrl) . '"';
}
if ($validateUrl) {
    $dataAttributes .= ' data-form-validate-url-value="' . htmlspecialchars($validateUrl) . '"';
}

// Build additional attributes string
$attributesStr = '';
foreach ($attributes as $attr => $val) {
    $attributesStr .= ' ' . $attr . '="' . htmlspecialchars($val) . '"';
}
?>

<form 
    id="<?php echo htmlspecialchars($id); ?>"
    action="<?php echo isset($action) ? htmlspecialchars($action) : ''; ?>"
    method="<?php echo htmlspecialchars(strtoupper($method)); ?>"
    class="<?php echo htmlspecialchars($class); ?>"
    <?php echo $enctype ? 'enctype="' . $enctype . '"' : ''; ?>
    data-controller="form"
    data-action="submit->form#submitForm"
    <?php echo $dataAttributes; ?>
    <?php echo $attributesStr; ?>
>
    <?php if (strtoupper($method) === 'POST'): ?>
        <!-- CSRF token - Replace with your CSRF implementation -->
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken()); ?>">
    <?php endif; ?>
    
    <!-- General error message area -->
    <div class="alert alert-danger mb-4 hidden" data-form-target="error"></div>
    
    <!-- Form content will be placed here -->
    <?php echo $content ?? ''; ?>
    
    <!-- Form feedback message area -->
    <div class="form-feedback hidden mt-4 p-4 rounded-md" data-form-target="feedback"></div>
</form>