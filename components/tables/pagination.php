<?php
/**
 * Pagination component
 * 
 * @param int    $currentPage Current page number
 * @param int    $totalPages  Total number of pages
 * @param string $urlPattern  URL pattern for page links (use {page} as placeholder)
 * @param int    $range       Number of pages to show before and after current page
 * @param string $id          Pagination ID
 * @param string $class       Additional CSS classes
 * @param array  $attributes  Additional HTML attributes
 */

// Extract variables
$currentPage = $currentPage ?? 1;
$totalPages = $totalPages ?? 1;
$urlPattern = $urlPattern ?? '?page={page}';
$range = $range ?? 2;
$id = $id ?? 'pagination-' . uniqid();
$class = $class ?? '';
$attributes = $attributes ?? [];

// Ensure valid values
$currentPage = max(1, min($currentPage, $totalPages));

// Calculate range of pages to display
$startPage = max(1, $currentPage - $range);
$endPage = min($totalPages, $currentPage + $range);

// Build additional attributes string
$attributesStr = '';
foreach ($attributes as $attr => $val) {
    $attributesStr .= ' ' . $attr . '="' . htmlspecialchars($val) . '"';
}

// Helper function to generate page URL
function getPageUrl($page, $pattern) {
    return str_replace('{page}', $page, $pattern);
}
?>

<?php if ($totalPages > 1): ?>
<nav 
    id="<?php echo htmlspecialchars($id); ?>"
    class="flex items-center justify-between py-3 <?php echo htmlspecialchars($class); ?>"
    aria-label="Pagination"
    <?php echo $attributesStr; ?>
>
    <div class="flex-1 flex justify-between sm:justify-end">
        <!-- Previous page link -->
        <?php if ($currentPage > 1): ?>
            <a href="<?php echo htmlspecialchars(getPageUrl($currentPage - 1, $urlPattern)); ?>" 
               class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                Précédent
            </a>
        <?php else: ?>
            <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-300 bg-white cursor-not-allowed">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                Précédent
            </span>
        <?php endif; ?>

        <!-- Page number links -->
        <div class="hidden md:flex mx-2">
            <?php if ($startPage > 1): ?>
                <!-- First page -->
                <a href="<?php echo htmlspecialchars(getPageUrl(1, $urlPattern)); ?>" 
                   class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 mx-1"
                >
                    1
                </a>
                
                <?php if ($startPage > 2): ?>
                    <!-- Ellipsis after first page -->
                    <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700">
                        ...
                    </span>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                <?php if ($i === $currentPage): ?>
                    <!-- Current page -->
                    <span class="relative inline-flex items-center px-4 py-2 border border-primary-500 text-sm font-medium rounded-md text-white bg-primary-500 mx-1">
                        <?php echo $i; ?>
                    </span>
                <?php else: ?>
                    <!-- Other pages -->
                    <a href="<?php echo htmlspecialchars(getPageUrl($i, $urlPattern)); ?>" 
                       class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 mx-1"
                    >
                        <?php echo $i; ?>
                    </a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($endPage < $totalPages): ?>
                <?php if ($endPage < $totalPages - 1): ?>
                    <!-- Ellipsis before last page -->
                    <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700">
                        ...
                    </span>
                <?php endif; ?>
                
                <!-- Last page -->
                <a href="<?php echo htmlspecialchars(getPageUrl($totalPages, $urlPattern)); ?>" 
                   class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 mx-1"
                >
                    <?php echo $totalPages; ?>
                </a>
            <?php endif; ?>
        </div>
        
        <!-- Next page link -->
        <?php if ($currentPage < $totalPages): ?>
            <a href="<?php echo htmlspecialchars(getPageUrl($currentPage + 1, $urlPattern)); ?>" 
               class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
            >
                Suivant
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                </svg>
            </a>
        <?php else: ?>
            <span class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-300 bg-white cursor-not-allowed">
                Suivant
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                </svg>
            </span>
        <?php endif; ?>
    </div>
</nav>
<?php endif; ?>