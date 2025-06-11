<?php
/**
 * Table component (Bootstrap version)
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
$tableClasses = 'table';
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

<div class="table-responsive">
    <table 
        id="<?php echo htmlspecialchars($id); ?>"
        class="<?php echo htmlspecialchars($tableClasses . ' ' . $class); ?>"
        <?php echo $attributesStr; ?>
    >
        <thead>
            <tr>
                <?php foreach ($headers as $key => $label): ?>
                <th scope="col" class="small font-weight-bold text-uppercase">
                    <?php echo htmlspecialchars($label); ?>
                </th>
                <?php endforeach; ?>
                
                <?php if ($hasActions): ?>
                <th scope="col" class="text-right small font-weight-bold text-uppercase">
                    Actions
                </th>
                <?php endif; ?>
            </tr>
        </thead>
        
        <tbody>
            <?php if (empty($data)): ?>
            <tr>
                <td colspan="<?php echo count($headers) + ($hasActions ? 1 : 0); ?>" class="text-center text-muted">
                    <?php echo htmlspecialchars($emptyText); ?>
                </td>
            </tr>
            <?php else: ?>
                <?php foreach ($data as $index => $row): ?>
                <tr>
                    <?php foreach ($headers as $key => $label): ?>
                    <td>
                        <?php echo isset($row[$key]) ? $row[$key] : ''; ?>
                    </td>
                    <?php endforeach; ?>
                    
                    <?php if ($hasActions): ?>
                    <td class="text-right">
                        <div class="d-flex justify-content-end">
                            <?php foreach ($actions as $action): ?>
                                <?php
                                // Process action data
                                $actionUrl = is_callable($action['url']) ? $action['url']($row) : $action['url'];
                                $actionLabel = $action['label'] ?? '';
                                $actionClass = $action['class'] ?? 'text-primary';
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
                                    class="btn btn-sm <?php echo htmlspecialchars($actionClass); ?> mr-1"
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