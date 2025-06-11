<?php
/**
 * Progress Tracker Component
 * Displays a visual representation of progress through multiple steps
 * 
 * @param array $steps Array of step objects with keys: label, description, status, date
 * @param int $currentStep Current active step (0-based index)
 * @param float $percent Overall percentage complete (0-100)
 * @param string $title Component title (optional)
 * @param string $class Additional CSS classes
 */

// Set defaults
$steps = $steps ?? [];
$currentStep = $currentStep ?? 0;
$percent = $percent ?? 0;
$class = $class ?? '';

// Generate a unique ID for this component
$id = 'progress-tracker-' . uniqid();
?>

<div class="bg-white shadow-sm rounded-lg overflow-hidden <?php echo h($class); ?>" id="<?php echo $id; ?>">
    <?php if (isset($title)): ?>
    <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
        <h3 class="text-lg leading-6 font-medium text-gray-900">
            <?php echo h($title); ?>
        </h3>
    </div>
    <?php endif; ?>
    
    <div class="px-4 py-5 sm:p-6">
        <!-- Overall progress bar -->
        <div class="mb-6">
            <div class="flex justify-between items-center mb-1">
                <span class="text-sm font-medium text-gray-700">Progression générale</span>
                <span class="text-sm font-medium text-gray-700"><?php echo round($percent); ?>%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2.5">
                <div class="bg-indigo-600 h-2.5 rounded-full" style="width: <?php echo h($percent); ?>%"></div>
            </div>
        </div>
        
        <!-- Steps -->
        <div class="flow-root">
            <ul role="list" class="-mb-8">
                <?php foreach ($steps as $index => $step): 
                    // Set defaults for step properties
                    $label = $step['label'] ?? '';
                    $description = $step['description'] ?? '';
                    $date = $step['date'] ?? '';
                    $status = $step['status'] ?? 'pending';
                    
                    // Determine status styling
                    $statusClass = '';
                    $statusIcon = '';
                    switch ($status) {
                        case 'completed':
                            $statusClass = 'bg-green-500';
                            $statusIcon = '<svg class="h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>';
                            break;
                        case 'current':
                            $statusClass = 'bg-indigo-500';
                            $statusIcon = '<svg class="h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3zm-3.707 9.293a1 1 0 011.414 0L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>';
                            break;
                        case 'warning':
                            $statusClass = 'bg-yellow-500';
                            $statusIcon = '<svg class="h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>';
                            break;
                        default:
                            $statusClass = 'bg-gray-400';
                            $statusIcon = '<svg class="h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" /></svg>';
                    }
                    
                    // Is this the last step?
                    $isLastStep = $index === count($steps) - 1;
                ?>
                <li>
                    <div class="relative pb-8">
                        <?php if (!$isLastStep): ?>
                        <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                        <?php endif; ?>
                        <div class="relative flex items-start space-x-3">
                            <div>
                                <div class="relative px-1">
                                    <div class="h-8 w-8 rounded-full flex items-center justify-center <?php echo $statusClass; ?>">
                                        <?php echo $statusIcon; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="min-w-0 flex-1 py-1.5">
                                <div class="text-sm text-gray-500">
                                    <div class="font-medium text-gray-900"><?php echo h($label); ?></div>
                                    <?php if ($description): ?>
                                    <div class="mt-1"><?php echo h($description); ?></div>
                                    <?php endif; ?>
                                    <?php if ($date): ?>
                                    <div class="mt-1 text-xs text-gray-400">
                                        <time datetime="<?php echo h($date); ?>"><?php echo date('d/m/Y', strtotime($date)); ?></time>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>