<?php
/**
 * Table component
 * 
 * @param array  $headers    Table headers (key => label)
 * @param array  $data       Table data (array of row objects)
 * @param array  $actions    Row action buttons
 * @param string $id         Table ID
 * @param string $class      Additional CSS classes
 * @param bool   $striped    Whether to use striped rows
 * @param bool   $hover      Whether to use hover effect
 * @param bool   $bordered   Whether to use borders
 * @param string $emptyText  Text to display when the table is empty
 * @param array  $attributes Additional HTML attributes
 */

// Extract variables
$id = $id ?? 'table-' . uniqid();
$class = $class ?? '';
$striped = $striped ?? true;
$hover = $hover ?? true;
$bordered = $bordered ?? false;
$emptyText = $emptyText ?? 'Aucune donnée disponible';
$attributes = $attributes ?? [];
$data = $data ?? [];
$headers = $headers ?? [];
$actions = $actions ?? [];

// Build class string based on options
$tableClasses = 'table min-w-full divide-y divide-gray-200';
if ($striped) {
    $tableClasses .= ' table-striped';
}
if ($hover) {
    $tableClasses .= ' table-hover';
}
if ($bordered) {
    $tableClasses .= ' table-bordered';
}

// Build additional attributes string
$attributesStr = '';
foreach ($attributes as $attr => $val) {
    $attributesStr .= ' ' . $attr . '="' . htmlspecialchars($val) . '"';
}

// Check if we have actions
$hasActions = !empty($actions);
?>

<div class="overflow-x-auto rounded-lg shadow">
    <table 
        id="<?php echo htmlspecialchars($id); ?>"
        class="<?php echo htmlspecialchars($tableClasses . ' ' . $class); ?>"
        <?php echo $attributesStr; ?>
    >
        <thead class="bg-gray-50">
            <tr>
                <?php foreach ($headers as $key => $label): ?>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <?php echo htmlspecialchars($label); ?>
                </th>
                <?php endforeach; ?>
                
                <?php if ($hasActions): ?>
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Actions
                </th>
                <?php endif; ?>
            </tr>
        </thead>
        
        <tbody class="bg-white divide-y divide-gray-200">
            <?php if (empty($data)): ?>
            <tr>
                <td colspan="<?php echo count($headers) + ($hasActions ? 1 : 0); ?>" class="px-6 py-4 text-center text-sm text-gray-500">
                    <?php echo htmlspecialchars($emptyText); ?>
                </td>
            </tr>
            <?php else: ?>
                <?php foreach ($data as $index => $row): ?>
                <tr class="<?php echo $striped && $index % 2 === 1 ? 'bg-gray-50' : ''; ?> <?php echo $hover ? 'hover:bg-gray-100 transition-colors duration-150' : ''; ?>">
                    <?php foreach ($headers as $key => $label): ?>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?php echo isset($row[$key]) ? $row[$key] : ''; ?>
                    </td>
                    <?php endforeach; ?>
                    
                    <?php if ($hasActions): ?>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex justify-end space-x-2">
                            <?php foreach ($actions as $action): ?>
                                <?php
                                // Process action data
                                $actionUrl = is_callable($action['url']) ? $action['url']($row) : $action['url'];
                                $actionLabel = $action['label'] ?? '';
                                $actionClass = $action['class'] ?? 'text-primary-600 hover:text-primary-900';
                                $actionIcon = $action['icon'] ?? '';
                                $actionAttrs = '';
                                
                                if (isset($action['attributes']) && is_array($action['attributes'])) {
                                    foreach ($action['attributes'] as $attr => $val) {
                                        $attrValue = is_callable($val) ? $val($row) : $val;
                                        $actionAttrs .= ' ' . $attr . '="' . htmlspecialchars($attrValue) . '"';
                                    }
                                }
                                ?>
                                <a 
                                    href="<?php echo htmlspecialchars($actionUrl); ?>"
                                    class="<?php echo htmlspecialchars($actionClass); ?>"
                                    title="<?php echo htmlspecialchars($actionLabel); ?>"
                                    <?php echo $actionAttrs; ?>
                                >
                                    <?php if ($actionIcon): ?>
                                        <?php echo $actionIcon; ?>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($actionLabel); ?>
                                    <?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>