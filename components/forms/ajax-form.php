<?php
/**
 * Composant AJAX Form
 * Formulaire qui utilise l'API pour soumettre les données
 * 
 * @param string $endpoint Point de terminaison API
 * @param string $method Méthode HTTP (POST, PUT, etc.)
 * @param string $redirect URL de redirection après soumission (optionnel)
 * @param string $submitText Texte du bouton de soumission
 * @param string $submitClass Classes CSS pour le bouton (optionnel)
 * @param string $loadingText Texte pendant le chargement
 * @param string $errorClass Classes CSS pour les messages d'erreur (optionnel)
 * @param string $successMessage Message de succès (optionnel)
 * @param boolean $resetOnSuccess Réinitialiser le formulaire après succès (optionnel)
 * @param string $formClass Classes CSS pour le formulaire (optionnel)
 * @param string $formId ID du formulaire (optionnel)
 * @param array $attributes Attributs HTML supplémentaires (optionnel)
 * @param string $slot Contenu du formulaire
 */

// Valeurs par défaut
$endpoint = $endpoint ?? '';
$method = $method ?? 'POST';
$redirect = $redirect ?? '';
$submitText = $submitText ?? 'Soumettre';
$submitClass = $submitClass ?? 'inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500';
$loadingText = $loadingText ?? 'Envoi en cours...';
$errorClass = $errorClass ?? 'text-danger-600 text-sm mt-1';
$successMessage = $successMessage ?? 'Opération réalisée avec succès';
$resetOnSuccess = $resetOnSuccess ?? true;
$formClass = $formClass ?? '';
$formId = $formId ?? 'ajax-form-' . uniqid();

// Préparer les attributs supplémentaires
$attributesHtml = '';
if (!empty($attributes) && is_array($attributes)) {
    foreach ($attributes as $key => $value) {
        $attributesHtml .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
    }
}

?>

<div 
    data-controller="api" 
    class="ajax-form-container"
>
    <form 
        id="<?php echo htmlspecialchars($formId); ?>"
        class="ajax-form <?php echo htmlspecialchars($formClass); ?>"
        data-action="submit->api#submitForm"
        data-endpoint="<?php echo htmlspecialchars($endpoint); ?>"
        data-method="<?php echo htmlspecialchars($method); ?>"
        <?php if ($redirect): ?>
        data-redirect="<?php echo htmlspecialchars($redirect); ?>"
        <?php endif; ?>
        <?php if ($resetOnSuccess): ?>
        data-reset-on-success="true"
        <?php endif; ?>
        <?php echo $attributesHtml; ?>
    >
        <!-- Champ CSRF Token caché -->
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        
        <!-- Contenu du formulaire -->
        <?php if (isset($slot)) echo $slot; ?>
        
        <!-- Message d'erreur -->
        <div data-api-target="error" class="form-error hidden <?php echo htmlspecialchars($errorClass); ?>"></div>
        
        <!-- Message de succès -->
        <div data-form-target="success" class="form-success hidden bg-success-50 border border-success-200 text-success-800 p-4 rounded-md mt-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-success-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium"><?php echo htmlspecialchars($successMessage); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Bouton de soumission -->
        <div class="form-actions flex items-center mt-4">
            <button type="submit" class="<?php echo htmlspecialchars($submitClass); ?>">
                <span class="submit-text"><?php echo htmlspecialchars($submitText); ?></span>
                <span data-api-target="loading" class="loading-text hidden flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <?php echo htmlspecialchars($loadingText); ?>
                </span>
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Écouter les événements de formulaire
    const form = document.getElementById('<?php echo htmlspecialchars($formId); ?>');
    
    if (form) {
        // Événement de succès du formulaire
        form.addEventListener('form:success', function(e) {
            // Afficher le message de succès
            const successElement = form.querySelector('[data-form-target="success"]');
            if (successElement) {
                successElement.classList.remove('hidden');
                
                // Cacher le message après 5 secondes
                setTimeout(() => {
                    successElement.classList.add('hidden');
                }, 5000);
            }
            
            // Réinitialiser le formulaire si configuré
            if (form.dataset.resetOnSuccess === 'true') {
                form.reset();
            }
        });
        
        // Événement d'erreur du formulaire
        form.addEventListener('form:error', function(e) {
            // L'erreur est déjà gérée par le contrôleur API
        });
    }
});
</script>