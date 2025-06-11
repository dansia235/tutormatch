<?php
/**
 * Data Table Component for Dashboard
 * Displays tabular data with headers and optional actions
 * 
 * @param array $headers Associative array of column keys and header labels
 * @param array $rows Array of associative arrays with column data
 * @param string $title Optional table title
 * @param string $emptyMessage Message to show when there's no data
 * @param string $viewAllUrl URL for "View all" link (optional)
 * @param array $actions Array of action objects (optional)
 * @param string $class Additional CSS classes
 */

// Set defaults
$title = $title ?? 'Données';
$emptyMessage = $emptyMessage ?? 'Aucune donnée disponible.';
$class = $class ?? '';
$headers = $headers ?? [];
$rows = $rows ?? [];
$viewAllUrl = $viewAllUrl ?? '';
$actions = $actions ?? [];

// Generate a unique ID for this component
$id = 'data-table-' . uniqid();
?>

<div class="bg-white shadow-sm rounded-lg overflow-hidden <?php echo h($class); ?>" id="<?php echo $id; ?>">
    <?php if ($title || $viewAllUrl): ?>
    <div class="px-4 py-5 sm:px-6 border-b border-gray-200 flex justify-between items-center">
        <?php if ($title): ?>
        <h3 class="text-lg leading-6 font-medium text-gray-900">
            <?php echo h($title); ?>
        </h3>
        <?php endif; ?>
        
        <?php if ($viewAllUrl): ?>
        <a href="<?php echo h($viewAllUrl); ?>" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
            Voir tout
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <?php if (empty($rows)): ?>
    <div class="p-6 text-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
        </svg>
        <p class="mt-2 text-gray-500">
            <?php echo h($emptyMessage); ?>
        </p>
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <?php foreach ($headers as $key => $label): ?>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <?php echo h($label); ?>
                    </th>
                    <?php endforeach; ?>
                    
                    <?php if (!empty($actions)): ?>
                    <th scope="col" class="relative px-6 py-3">
                        <span class="sr-only">Actions</span>
                    </th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($rows as $row): ?>
                <tr class="hover:bg-gray-50">
                    <?php foreach ($headers as $key => $label): ?>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php if (strpos($row[$key] ?? '', '<') === 0): ?>
                            <?php echo $row[$key]; ?>
                        <?php else: ?>
                            <div class="text-sm <?php echo $key === array_key_first($headers) ? 'font-medium text-gray-900' : 'text-gray-500'; ?>">
                                <?php echo h($row[$key] ?? ''); ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <?php endforeach; ?>
                    
                    <?php if (!empty($actions)): ?>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <?php foreach ($actions as $action): 
                            $actionUrl = str_replace('{id}', $row['id'] ?? '', $action['url']);
                            $actionClass = $action['class'] ?? 'text-indigo-600 hover:text-indigo-900';
                        ?>
                            <a href="<?php echo h($actionUrl); ?>" class="<?php echo h($actionClass); ?> <?php echo !$loop->first ? 'ml-3' : ''; ?>">
                                <?php echo h($action['label']); ?>
                            </a>
                        <?php endforeach; ?>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>