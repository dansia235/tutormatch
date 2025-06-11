<?php
/**
 * Chart component using Chart.js (Bootstrap version)
 * 
 * @param string $id          Chart canvas ID
 * @param string $type        Chart type (line, bar, pie, doughnut, etc.)
 * @param array  $data        Chart data
 * @param array  $options     Chart options
 * @param string $title       Chart title
 * @param string $width       Chart width
 * @param string $height      Chart height
 * @param string $class       Additional CSS classes
 * @param array  $attributes  Additional HTML attributes
 */

// Extract variables
$id = $id ?? 'chart-' . uniqid();
$type = $type ?? 'bar';
$title = $title ?? '';
$width = $width ?? '100%';
$height = $height ?? '300px';
$class = $class ?? '';
$attributes = $attributes ?? [];

// Convert data and options to JSON
$dataJson = isset($data) ? json_encode($data) : '{}';
$optionsJson = isset($options) ? json_encode($options) : '{}';

// Build additional attributes string
$attributesStr = '';
foreach ($attributes as $attr => $val) {
    $attributesStr .= ' ' . $attr . '="' . htmlspecialchars($val) . '"';
}
?>

<div class="chart-container" style="position: relative; width: <?php echo htmlspecialchars($width); ?>; height: <?php echo htmlspecialchars($height); ?>;" data-controller="chart" data-chart-type-value="<?php echo htmlspecialchars($type); ?>" data-chart-data-value='<?php echo $dataJson; ?>' data-chart-options-value='<?php echo $optionsJson; ?>'>
    <?php if ($title): ?>
    <h3 class="chart-title"><?php echo htmlspecialchars($title); ?></h3>
    <?php endif; ?>
    
    <canvas 
        id="<?php echo htmlspecialchars($id); ?>"
        class="<?php echo htmlspecialchars($class); ?>"
        <?php echo $attributesStr; ?>
        data-chart-target="canvas"
    ></canvas>
</div>