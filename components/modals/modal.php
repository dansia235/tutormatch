<?php
/**
 * Modal component
 * 
 * @param string $id           Modal ID
 * @param string $title        Modal title
 * @param string $content      Modal content
 * @param string $footer       Modal footer
 * @param string $size         Modal size (sm, md, lg, xl, full)
 * @param bool   $centered     Whether to center the modal vertically
 * @param bool   $closable     Whether the modal can be closed with X button
 * @param bool   $backdropClose Whether clicking the backdrop closes the modal
 * @param bool   $escClose     Whether pressing ESC closes the modal
 * @param string $triggerElement HTML for the trigger element (button/link)
 * @param array  $attributes   Additional HTML attributes
 */

// Extract variables
$id = $id ?? 'modal-' . uniqid();
$title = $title ?? '';
$size = $size ?? 'md';
$centered = $centered ?? true;
$closable = $closable ?? true;
$backdropClose = $backdropClose ?? true;
$escClose = $escClose ?? true;
$triggerElement = $triggerElement ?? '';
$attributes = $attributes ?? [];

// Determine modal size class
$sizeClass = '';
switch ($size) {
    case 'sm':
        $sizeClass = 'max-w-sm';
        break;
    case 'md':
        $sizeClass = 'max-w-md';
        break;
    case 'lg':
        $sizeClass = 'max-w-lg';
        break;
    case 'xl':
        $sizeClass = 'max-w-xl';
        break;
    case '2xl':
        $sizeClass = 'max-w-2xl';
        break;
    case '3xl':
        $sizeClass = 'max-w-3xl';
        break;
    case '4xl':
        $sizeClass = 'max-w-4xl';
        break;
    case '5xl':
        $sizeClass = 'max-w-5xl';
        break;
    case '6xl':
        $sizeClass = 'max-w-6xl';
        break;
    case '7xl':
        $sizeClass = 'max-w-7xl';
        break;
    case 'full':
        $sizeClass = 'max-w-full mx-4';
        break;
    default:
        $sizeClass = 'max-w-md';
}

// Build additional attributes string
$attributesStr = '';
foreach ($attributes as $attr => $val) {
    $attributesStr .= ' ' . $attr . '="' . htmlspecialchars($val) . '"';
}

// Build modal controller data attributes
$modalAttrs = ' data-controller="modal"';
$modalAttrs .= ' data-modal-backdrop-close-value="' . ($backdropClose ? 'true' : 'false') . '"';
$modalAttrs .= ' data-modal-esc-close-value="' . ($escClose ? 'true' : 'false') . '"';
?>

<!-- Modal trigger element -->
<?php if ($triggerElement): ?>
    <?php echo str_replace('{modalId}', $id, $triggerElement); ?>
<?php endif; ?>

<!-- Modal container -->
<div 
    id="<?php echo htmlspecialchars($id); ?>"
    class="fixed inset-0 z-50 hidden"
    <?php echo $modalAttrs; ?>
    <?php echo $attributesStr; ?>
>
    <!-- Backdrop -->
    <div 
        class="fixed inset-0 bg-black opacity-0 transition-opacity duration-300" 
        data-modal-target="backdrop"
    ></div>
    
    <!-- Modal dialog -->
    <div class="fixed inset-0 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 text-center <?php echo $centered ? 'items-center' : 'items-start pt-10'; ?>">
            <div 
                class="w-full <?php echo $sizeClass; ?> transform overflow-hidden rounded-lg bg-white text-left align-middle shadow-xl transition-all opacity-0 translate-y-4 duration-300" 
                data-modal-target="dialog"
            >
                <!-- Modal header -->
                <?php if ($title || $closable): ?>
                <div class="bg-white px-4 py-3 sm:px-6 border-b flex justify-between items-center">
                    <?php if ($title): ?>
                    <h3 class="text-lg font-medium text-gray-900"><?php echo $title; ?></h3>
                    <?php endif; ?>
                    
                    <?php if ($closable): ?>
                    <button 
                        type="button" 
                        class="text-gray-400 bg-white rounded-md hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500"
                        data-modal-target="closeButton"
                    >
                        <span class="sr-only">Fermer</span>
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <!-- Modal content -->
                <div class="px-4 py-3 sm:px-6" data-modal-target="content">
                    <?php echo $content ?? ''; ?>
                </div>
                
                <!-- Modal footer -->
                <?php if (isset($footer)): ?>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 border-t">
                    <?php echo $footer; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal trigger script -->
<?php if ($triggerElement): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const triggers = document.querySelectorAll('[data-modal-trigger="<?php echo htmlspecialchars($id); ?>"]');
        const modal = document.getElementById('<?php echo htmlspecialchars($id); ?>');
        if (modal) {
            const modalController = application.getControllerForElementAndIdentifier(modal, 'modal');
            if (modalController) {
                triggers.forEach(trigger => {
                    trigger.addEventListener('click', function(e) {
                        e.preventDefault();
                        modalController.open();
                    });
                });
            }
        }
    });
</script>
<?php endif; ?>