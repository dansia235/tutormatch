<?php
/**
 * System Status Component
 * Displays system health metrics and information
 * 
 * @param array $statusItems Array of status items with keys: label, value, icon, status
 * @param array $metrics Array of metric items with keys: label, value, icon, percentage
 * @param string $title Component title (default: "État du système")
 * @param string $class Additional CSS classes
 */

// Set defaults
$title = $title ?? 'État du système';
$class = $class ?? '';
$statusItems = $statusItems ?? [];
$metrics = $metrics ?? [];

// Generate a unique ID for this component
$id = 'system-status-' . uniqid();

// Status color classes
$statusColors = [
    'operational' => 'bg-green-100 text-green-800',
    'warning' => 'bg-yellow-100 text-yellow-800',
    'error' => 'bg-red-100 text-red-800',
    'maintenance' => 'bg-blue-100 text-blue-800',
];
?>

<div class="bg-white shadow-sm rounded-lg overflow-hidden <?php echo h($class); ?>" id="<?php echo $id; ?>">
    <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
        <h3 class="text-lg leading-6 font-medium text-gray-900">
            <?php echo h($title); ?>
        </h3>
    </div>
    
    <div class="px-4 py-5 sm:p-6">
        <div class="space-y-4">
            <?php if (!empty($statusItems)): ?>
                <?php foreach ($statusItems as $item): 
                    $statusClass = $statusColors[$item['status']] ?? 'bg-gray-100 text-gray-800';
                ?>
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <?php if (!empty($item['icon'])): ?>
                        <span class="text-gray-400 mr-2">
                            <?php echo $item['icon']; ?>
                        </span>
                        <?php endif; ?>
                        <span class="text-sm font-medium text-gray-500"><?php echo h($item['label']); ?></span>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo h($statusClass); ?>">
                        <?php echo h($item['value']); ?>
                    </span>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <?php if (!empty($metrics)): ?>
                <?php foreach ($metrics as $metric): ?>
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <div class="flex items-center">
                            <?php if (!empty($metric['icon'])): ?>
                            <span class="text-gray-400 mr-2">
                                <?php echo $metric['icon']; ?>
                            </span>
                            <?php endif; ?>
                            <span class="text-sm font-medium text-gray-500"><?php echo h($metric['label']); ?></span>
                        </div>
                        <span class="text-xs text-gray-500"><?php echo h($metric['value']); ?></span>
                    </div>
                    <?php if (isset($metric['percentage'])): ?>
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <?php 
                            $percentage = min(100, max(0, $metric['percentage']));
                            $barColor = 'bg-green-500';
                            
                            if ($percentage > 80) {
                                $barColor = 'bg-red-500';
                            } elseif ($percentage > 60) {
                                $barColor = 'bg-yellow-500';
                            } elseif ($percentage > 40) {
                                $barColor = 'bg-blue-500';
                            }
                        ?>
                        <div class="<?php echo $barColor; ?> h-2.5 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if (isset($footerLink) && isset($footerText)): ?>
    <div class="bg-gray-50 px-4 py-4 sm:px-6 border-t border-gray-200">
        <div class="text-sm">
            <a href="<?php echo h($footerLink); ?>" class="font-medium text-indigo-600 hover:text-indigo-500">
                <?php echo h($footerText); ?>
                <span aria-hidden="true">&rarr;</span>
            </a>
        </div>
    </div>
    <?php endif; ?>
</div>