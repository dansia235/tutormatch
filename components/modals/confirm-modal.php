<?php
/**
 * Confirmation modal component
 * 
 * @param string $id           Modal ID
 * @param string $title        Modal title
 * @param string $message      Confirmation message
 * @param string $confirmBtnText Text for the confirm button
 * @param string $cancelBtnText Text for the cancel button
 * @param string $confirmBtnClass CSS class for the confirm button
 * @param string $cancelBtnClass CSS class for the cancel button
 * @param string $confirmAction JavaScript action for confirm
 * @param string $cancelAction JavaScript action for cancel
 * @param string $size         Modal size (sm, md, lg, xl, full)
 * @param string $triggerElement HTML for the trigger element (button/link)
 * @param array  $attributes   Additional HTML attributes
 */

// Extract variables
$id = $id ?? 'confirm-modal-' . uniqid();
$title = $title ?? 'Confirmation';
$message = $message ?? 'Êtes-vous sûr de vouloir effectuer cette action ?';
$confirmBtnText = $confirmBtnText ?? 'Confirmer';
$cancelBtnText = $cancelBtnText ?? 'Annuler';
$confirmBtnClass = $confirmBtnClass ?? 'btn btn-danger';
$cancelBtnClass = $cancelBtnClass ?? 'btn btn-outline-secondary';
$confirmAction = $confirmAction ?? '';
$cancelAction = $cancelAction ?? '';
$size = $size ?? 'sm';
$triggerElement = $triggerElement ?? '';
$attributes = $attributes ?? [];

// Prepare footer content
$footer = '
<div class="flex justify-end space-x-3">
    <button type="button" class="' . htmlspecialchars($cancelBtnClass) . '" data-modal-target="closeButton" data-action="click->modal#close ' . htmlspecialchars($cancelAction) . '">
        ' . htmlspecialchars($cancelBtnText) . '
    </button>
    <button type="button" class="' . htmlspecialchars($confirmBtnClass) . '" data-action="click->modal#close ' . htmlspecialchars($confirmAction) . '">
        ' . htmlspecialchars($confirmBtnText) . '
    </button>
</div>
';

// Include the main modal component
include __DIR__ . '/modal.php';
?>