<?php
/**
 * Document list component
 * 
 * @param array  $documents     Array of documents
 * @param string $emptyMessage  Message to display when no documents (default: "Aucun document")
 * @param bool   $showFilters   Whether to show filtering options (default: true)
 * @param bool   $showSearch    Whether to show search input (default: true)
 * @param string $class         Additional CSS classes
 */

// Extract variables
$emptyMessage = $emptyMessage ?? "Aucun document disponible.";
$showFilters = $showFilters ?? true;
$showSearch = $showSearch ?? true;
$class = $class ?? '';

// Category translations
$categoryLabels = [
    'cv' => 'CV',
    'report' => 'Rapport',
    'agreement' => 'Convention',
    'evaluation' => 'Évaluation',
    'contract' => 'Contrat',
    'administrative' => 'Administratif',
    'image' => 'Image',
    'presentation' => 'Présentation',
    'other' => 'Autre'
];

// Count documents by category
$categoryCount = [];
foreach ($documents as $document) {
    $category = $document['type'] ?? 'other';
    if (!isset($categoryCount[$category])) {
        $categoryCount[$category] = 0;
    }
    $categoryCount[$category]++;
}

// Generate a unique ID for the component
$componentId = 'document-list-' . uniqid();
?>

<div class="space-y-4 <?php echo h($class); ?>" data-controller="document-list" id="<?php echo $componentId; ?>">
    <!-- Filters and Search -->
    <?php if ($showFilters || $showSearch): ?>
    <div class="bg-white p-4 rounded-lg border shadow-sm">
        <div class="flex flex-col md:flex-row space-y-3 md:space-y-0 md:space-x-4">
            <?php if ($showSearch): ?>
            <!-- Search input -->
            <div class="flex-grow">
                <label for="<?php echo $componentId; ?>-search" class="sr-only">Rechercher des documents</label>
                <div class="relative rounded-md shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400"></i>
                    </div>
                    <input
                        type="text"
                        id="<?php echo $componentId; ?>-search"
                        class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 sm:text-sm border-gray-300 rounded-md"
                        placeholder="Rechercher un document..."
                        data-document-list-target="search"
                        data-action="input->document-list#search"
                    >
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($showFilters): ?>
            <!-- Filter dropdown -->
            <div class="flex-shrink-0">
                <div class="relative inline-block text-left w-full md:w-auto" data-controller="dropdown">
                    <button 
                        type="button" 
                        class="inline-flex justify-between w-full md:w-auto rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        data-action="dropdown#toggle"
                    >
                        <span data-document-list-target="filterLabel">Toutes les catégories</span>
                        <i class="fas fa-chevron-down ml-2"></i>
                    </button>

                    <div 
                        class="hidden origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-10"
                        data-dropdown-target="menu"
                    >
                        <div class="py-1" role="menu" aria-orientation="vertical" aria-labelledby="options-menu">
                            <button 
                                type="button" 
                                class="text-left w-full px-4 py-2 text-sm hover:bg-gray-100 active-filter" 
                                data-filter="all" 
                                data-action="document-list#filter"
                            >
                                <span class="flex items-center justify-between">
                                    <span>Toutes les catégories</span>
                                    <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2 py-0.5 rounded-full">
                                        <?php echo count($documents); ?>
                                    </span>
                                </span>
                            </button>
                            
                            <div class="border-t border-gray-100 my-1"></div>
                            
                            <?php foreach ($categoryCount as $category => $count): ?>
                            <button 
                                type="button" 
                                class="text-left w-full px-4 py-2 text-sm hover:bg-gray-100" 
                                data-filter="<?php echo h($category); ?>"
                                data-action="document-list#filter"
                            >
                                <span class="flex items-center justify-between">
                                    <span><?php echo h($categoryLabels[$category] ?? ucfirst($category)); ?></span>
                                    <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2 py-0.5 rounded-full">
                                        <?php echo $count; ?>
                                    </span>
                                </span>
                            </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Documents grid -->
    <?php if (empty($documents)): ?>
    <div class="bg-white p-8 rounded-lg border shadow-sm text-center">
        <div class="text-gray-400 mb-3">
            <i class="fas fa-folder-open text-4xl"></i>
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-1">Aucun document</h3>
        <p class="text-sm text-gray-500"><?php echo h($emptyMessage); ?></p>
    </div>
    <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" data-document-list-target="container">
        <?php foreach ($documents as $document): ?>
        <div class="document-item" data-category="<?php echo h($document['type'] ?? 'other'); ?>">
            <?php 
            // Include document card component
            include __DIR__ . '/../cards/document-card.php';
            ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Document list controller script -->
<script>
// This script will be replaced by the Stimulus controller
// It's included here for fallback and to illustrate the functionality
document.addEventListener('DOMContentLoaded', function() {
    const containerEl = document.getElementById('<?php echo $componentId; ?>');
    if (!containerEl) return;
    
    // Search functionality
    const searchInput = containerEl.querySelector('[data-document-list-target="search"]');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const items = containerEl.querySelectorAll('.document-item');
            
            items.forEach(item => {
                const title = item.querySelector('h4, h5')?.textContent.toLowerCase() || '';
                const description = item.querySelector('.text-gray-600')?.textContent.toLowerCase() || '';
                const content = title + ' ' + description;
                
                if (content.includes(searchTerm) || searchTerm === '') {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }
    
    // Filter functionality
    const filterButtons = containerEl.querySelectorAll('[data-filter]');
    const filterLabel = containerEl.querySelector('[data-document-list-target="filterLabel"]');
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Update active state
            filterButtons.forEach(b => b.classList.remove('active-filter', 'bg-gray-100', 'text-blue-600'));
            this.classList.add('active-filter', 'bg-gray-100', 'text-blue-600');
            
            // Update filter label
            if (filterLabel) {
                filterLabel.textContent = this.querySelector('span').firstChild.textContent;
            }
            
            // Apply filter
            const filter = this.dataset.filter;
            const items = containerEl.querySelectorAll('.document-item');
            
            items.forEach(item => {
                if (filter === 'all' || item.dataset.category === filter) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });
});
</script>