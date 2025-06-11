<?php
/**
 * Search box component
 * 
 * @param string $id            Search box ID
 * @param string $action        Form action URL
 * @param string $method        Form method (GET, POST)
 * @param string $name          Input name
 * @param string $value         Current search value
 * @param string $placeholder   Placeholder text
 * @param bool   $liveSearch    Whether to enable live search
 * @param string $liveSearchUrl URL for live search API
 * @param string $class         Additional CSS classes
 * @param array  $attributes    Additional HTML attributes
 */

// Extract variables
$id = $id ?? 'search-box-' . uniqid();
$method = $method ?? 'GET';
$name = $name ?? 'q';
$value = $value ?? '';
$placeholder = $placeholder ?? 'Rechercher...';
$liveSearch = $liveSearch ?? false;
$liveSearchUrl = $liveSearchUrl ?? '';
$class = $class ?? '';
$attributes = $attributes ?? [];

// Build additional attributes string
$attributesStr = '';
foreach ($attributes as $attr => $val) {
    $attributesStr .= ' ' . $attr . '="' . htmlspecialchars($val) . '"';
}

// Data attributes for live search
$liveSearchAttrs = '';
if ($liveSearch) {
    $liveSearchAttrs = ' data-controller="live-search"';
    $liveSearchAttrs .= ' data-live-search-url-value="' . htmlspecialchars($liveSearchUrl) . '"';
}
?>

<div 
    id="<?php echo htmlspecialchars($id); ?>"
    class="search-box <?php echo htmlspecialchars($class); ?>"
    <?php echo $liveSearchAttrs; ?>
    <?php echo $attributesStr; ?>
>
    <form action="<?php echo isset($action) ? htmlspecialchars($action) : ''; ?>" method="<?php echo htmlspecialchars(strtoupper($method)); ?>" class="relative" data-action="<?php echo $liveSearch ? 'submit->live-search#preventSubmit' : ''; ?>">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
            </svg>
        </div>
        
        <input
            type="search"
            name="<?php echo htmlspecialchars($name); ?>"
            id="<?php echo htmlspecialchars($id); ?>-input"
            value="<?php echo htmlspecialchars($value); ?>"
            placeholder="<?php echo htmlspecialchars($placeholder); ?>"
            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
            <?php echo $liveSearch ? 'data-action="input->live-search#search" data-live-search-target="input"' : ''; ?>
        />
        
        <?php if ($value): ?>
        <button type="button" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-500" data-action="<?php echo $liveSearch ? 'click->live-search#clear' : ''; ?>">
            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-11.707a1 1 0 00-1.414 0L10 8.586 7.707 6.293a1 1 0 00-1.414 1.414L8.586 10l-2.293 2.293a1 1 0 001.414 1.414L10 11.414l2.293 2.293a1 1 0 001.414-1.414L11.414 10l2.293-2.293a1 1 0 000-1.414z" clip-rule="evenodd" />
            </svg>
        </button>
        <?php endif; ?>
    </form>
    
    <?php if ($liveSearch): ?>
    <div class="relative">
        <div class="absolute z-10 mt-1 w-full bg-white shadow-lg rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm hidden" data-live-search-target="results">
            <!-- Search results will be inserted here via JavaScript -->
            <div class="search-results-container max-h-60 overflow-y-auto">
                <!-- Placeholder for no results -->
                <div class="search-no-results px-4 py-3 text-gray-500 italic hidden" data-live-search-target="noResults">
                    Aucun résultat trouvé
                </div>
                
                <!-- Loading indicator -->
                <div class="search-loading px-4 py-3 text-gray-500 hidden" data-live-search-target="loading">
                    <div class="flex items-center justify-center space-x-2">
                        <svg class="animate-spin h-4 w-4 text-primary-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Recherche en cours...</span>
                    </div>
                </div>
                
                <!-- Results list -->
                <div class="search-results" data-live-search-target="list">
                    <!-- Results will be inserted here -->
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>