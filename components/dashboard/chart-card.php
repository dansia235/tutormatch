<?php
/**
 * Chart Card Component for Dashboard
 * Displays a chart with a title and optional description
 * 
 * @param string $title Chart title
 * @param string $chartId ID for the canvas element
 * @param string $chartType Type of chart (pie, bar, line, etc.)
 * @param array $chartData Data for the chart (labels and datasets)
 * @param string $description Optional description text
 * @param string $emptyMessage Message to show when there's no data
 * @param string $class Additional CSS classes
 */

// Set defaults
$title = $title ?? 'Graphique';
$chartId = $chartId ?? 'chart-' . uniqid();
$chartType = $chartType ?? 'bar';
$chartData = $chartData ?? [];
$description = $description ?? '';
$emptyMessage = $emptyMessage ?? 'Aucune donnée disponible.';
$class = $class ?? '';
$height = $height ?? 'h-64';
?>

<div class="bg-white shadow-sm rounded-lg overflow-hidden <?php echo h($class); ?>">
    <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
        <h3 class="text-lg leading-6 font-medium text-gray-900">
            <?php echo h($title); ?>
        </h3>
        <?php if ($description): ?>
        <p class="mt-1 max-w-2xl text-sm text-gray-500">
            <?php echo h($description); ?>
        </p>
        <?php endif; ?>
    </div>
    
    <div class="p-4">
        <?php if (empty($chartData) || (isset($chartData['data']) && empty($chartData['data']))): ?>
        <div class="flex flex-col items-center justify-center <?php echo h($height); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
            </svg>
            <p class="text-gray-500"><?php echo h($emptyMessage); ?></p>
        </div>
        <?php else: ?>
        <div class="<?php echo h($height); ?>">
            <canvas id="<?php echo h($chartId); ?>"></canvas>
        </div>
        <?php endif; ?>
    </div>
</div>