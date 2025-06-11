<?php
/**
 * Filter bar component
 * 
 * @param string $id             Filter bar ID
 * @param string $action         Form action URL
 * @param string $method         Form method (GET, POST)
 * @param array  $filters        Filter definitions array
 * @param array  $currentFilters Current filter values
 * @param bool   $inline         Display filters inline or stacked
 * @param bool   $collapsible    Whether the filter bar can be collapsed on mobile
 * @param string $class          Additional CSS classes
 * @param array  $attributes     Additional HTML attributes
 */

// Extract variables
$id = $id ?? 'filter-bar-' . uniqid();
$method = $method ?? 'GET';
$inline = $inline ?? true;
$collapsible = $collapsible ?? true;
$class = $class ?? '';
$attributes = $attributes ?? [];
$filters = $filters ?? [];
$currentFilters = $currentFilters ?? [];

// Build additional attributes string
$attributesStr = '';
foreach ($attributes as $attr => $val) {
    $attributesStr .= ' ' . $attr . '="' . htmlspecialchars($val) . '"';
}

// Class for filters container
$filtersClass = $inline ? 'sm:flex sm:flex-wrap sm:items-end sm:space-x-4' : 'space-y-4';
?>

<div 
    id="<?php echo htmlspecialchars($id); ?>"
    class="filter-bar bg-white rounded-lg border border-gray-200 overflow-hidden mb-6 <?php echo htmlspecialchars($class); ?>"
    data-controller="filter"
    <?php echo $attributesStr; ?>
>
    <?php if ($collapsible): ?>
    <div class="border-b border-gray-200 px-4 py-3 flex items-center justify-between sm:hidden">
        <h3 class="text-lg font-medium">Filtres</h3>
        <button
            type="button"
            class="text-gray-500 hover:text-gray-700 focus:outline-none"
            data-action="filter#toggle"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" data-filter-target="toggleIcon">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>
    </div>
    <?php endif; ?>
    
    <div class="px-4 py-4 <?php echo $collapsible ? 'hidden sm:block' : ''; ?>" data-filter-target="container">
        <form action="<?php echo isset($action) ? htmlspecialchars($action) : ''; ?>" method="<?php echo htmlspecialchars(strtoupper($method)); ?>" data-action="filter#submit">
            <div class="<?php echo $filtersClass; ?>">
                <?php foreach ($filters as $filter): ?>
                    <?php 
                    // Determine filter type and render appropriate input
                    $filterType = $filter['type'] ?? 'text';
                    $filterName = $filter['name'] ?? '';
                    $filterValue = $currentFilters[$filterName] ?? '';
                    
                    switch ($filterType):
                        case 'text':
                            ?>
                            <div class="form-group mb-4 sm:mb-0 <?php echo $inline ? 'flex-1' : ''; ?>">
                                <label for="filter_<?php echo htmlspecialchars($filterName); ?>" class="form-label">
                                    <?php echo htmlspecialchars($filter['label'] ?? ''); ?>
                                </label>
                                <input
                                    type="text"
                                    id="filter_<?php echo htmlspecialchars($filterName); ?>"
                                    name="<?php echo htmlspecialchars($filterName); ?>"
                                    value="<?php echo htmlspecialchars($filterValue); ?>"
                                    placeholder="<?php echo htmlspecialchars($filter['placeholder'] ?? ''); ?>"
                                    class="form-control"
                                >
                            </div>
                            <?php
                            break;
                            
                        case 'select':
                            ?>
                            <div class="form-group mb-4 sm:mb-0 <?php echo $inline ? 'flex-1' : ''; ?>">
                                <label for="filter_<?php echo htmlspecialchars($filterName); ?>" class="form-label">
                                    <?php echo htmlspecialchars($filter['label'] ?? ''); ?>
                                </label>
                                <select
                                    id="filter_<?php echo htmlspecialchars($filterName); ?>"
                                    name="<?php echo htmlspecialchars($filterName); ?>"
                                    class="form-control"
                                >
                                    <option value=""><?php echo htmlspecialchars($filter['placeholder'] ?? 'Tous'); ?></option>
                                    <?php foreach ($filter['options'] ?? [] as $optionValue => $optionLabel): ?>
                                        <option 
                                            value="<?php echo htmlspecialchars($optionValue); ?>"
                                            <?php echo $filterValue == $optionValue ? 'selected' : ''; ?>
                                        >
                                            <?php echo htmlspecialchars($optionLabel); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php
                            break;
                            
                        case 'date':
                            ?>
                            <div class="form-group mb-4 sm:mb-0 <?php echo $inline ? 'flex-1' : ''; ?>">
                                <label for="filter_<?php echo htmlspecialchars($filterName); ?>" class="form-label">
                                    <?php echo htmlspecialchars($filter['label'] ?? ''); ?>
                                </label>
                                <input
                                    type="date"
                                    id="filter_<?php echo htmlspecialchars($filterName); ?>"
                                    name="<?php echo htmlspecialchars($filterName); ?>"
                                    value="<?php echo htmlspecialchars($filterValue); ?>"
                                    class="form-control"
                                >
                            </div>
                            <?php
                            break;
                            
                        case 'daterange':
                            $startValue = $currentFilters[$filterName . '_start'] ?? '';
                            $endValue = $currentFilters[$filterName . '_end'] ?? '';
                            ?>
                            <div class="form-group mb-4 sm:mb-0 <?php echo $inline ? 'flex-1' : ''; ?>" data-controller="daterange">
                                <label class="form-label">
                                    <?php echo htmlspecialchars($filter['label'] ?? ''); ?>
                                </label>
                                <div class="flex space-x-2">
                                    <div class="flex-1">
                                        <input
                                            type="date"
                                            id="filter_<?php echo htmlspecialchars($filterName); ?>_start"
                                            name="<?php echo htmlspecialchars($filterName); ?>_start"
                                            value="<?php echo htmlspecialchars($startValue); ?>"
                                            class="form-control"
                                            placeholder="Début"
                                            data-daterange-target="start"
                                        >
                                    </div>
                                    <div class="flex-1">
                                        <input
                                            type="date"
                                            id="filter_<?php echo htmlspecialchars($filterName); ?>_end"
                                            name="<?php echo htmlspecialchars($filterName); ?>_end"
                                            value="<?php echo htmlspecialchars($endValue); ?>"
                                            class="form-control"
                                            placeholder="Fin"
                                            data-daterange-target="end"
                                        >
                                    </div>
                                </div>
                            </div>
                            <?php
                            break;
                            
                        case 'checkbox':
                            $checkedValue = $filterValue ? 'checked' : '';
                            ?>
                            <div class="form-group mb-4 sm:mb-0 <?php echo $inline ? 'flex-1' : ''; ?>">
                                <div class="flex items-center h-full pt-6">
                                    <input
                                        type="checkbox"
                                        id="filter_<?php echo htmlspecialchars($filterName); ?>"
                                        name="<?php echo htmlspecialchars($filterName); ?>"
                                        value="1"
                                        <?php echo $checkedValue; ?>
                                        class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                                    >
                                    <label for="filter_<?php echo htmlspecialchars($filterName); ?>" class="ml-2 block text-sm text-gray-700">
                                        <?php echo htmlspecialchars($filter['label'] ?? ''); ?>
                                    </label>
                                </div>
                            </div>
                            <?php
                            break;
                            
                        case 'radio':
                            ?>
                            <div class="form-group mb-4 sm:mb-0 <?php echo $inline ? 'flex-1' : ''; ?>">
                                <div class="form-label mb-2">
                                    <?php echo htmlspecialchars($filter['label'] ?? ''); ?>
                                </div>
                                <div class="flex space-x-4">
                                    <?php foreach ($filter['options'] ?? [] as $optionValue => $optionLabel): ?>
                                        <div class="flex items-center">
                                            <input
                                                type="radio"
                                                id="filter_<?php echo htmlspecialchars($filterName . '_' . $optionValue); ?>"
                                                name="<?php echo htmlspecialchars($filterName); ?>"
                                                value="<?php echo htmlspecialchars($optionValue); ?>"
                                                <?php echo $filterValue == $optionValue ? 'checked' : ''; ?>
                                                class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300"
                                            >
                                            <label for="filter_<?php echo htmlspecialchars($filterName . '_' . $optionValue); ?>" class="ml-2 block text-sm text-gray-700">
                                                <?php echo htmlspecialchars($optionLabel); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php
                            break;
                    endswitch;
                    ?>
                <?php endforeach; ?>
                
                <div class="flex space-x-2 mt-4 sm:mt-0">
                    <button type="submit" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                        </svg>
                        Filtrer
                    </button>
                    
                    <button type="button" class="btn btn-secondary" data-action="filter#reset">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
                        </svg>
                        Réinitialiser
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>