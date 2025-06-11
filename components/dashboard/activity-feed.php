<?php
/**
 * Activity Feed Component
 * Displays a list of recent activities or events
 * 
 * @param array $activities Array of activity objects
 * @param string $title Component title (default: "Activité récente")
 * @param string $emptyMessage Message to show when there are no activities
 * @param string $viewAllUrl URL for "View all" link (optional)
 * @param string $class Additional CSS classes
 */

// Set defaults
$title = $title ?? 'Activité récente';
$emptyMessage = $emptyMessage ?? 'Aucune activité récente.';
$class = $class ?? '';
$activities = $activities ?? [];

// Generate a unique ID for this component
$id = 'activity-feed-' . uniqid();
?>

<div class="bg-white shadow-sm rounded-lg overflow-hidden <?php echo h($class); ?>" id="<?php echo $id; ?>">
    <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
        <h3 class="text-lg leading-6 font-medium text-gray-900">
            <?php echo h($title); ?>
        </h3>
    </div>
    
    <?php if (empty($activities)): ?>
    <div class="p-6 text-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <p class="mt-2 text-gray-500">
            <?php echo h($emptyMessage); ?>
        </p>
    </div>
    <?php else: ?>
    <div class="bg-white overflow-hidden">
        <ul class="divide-y divide-gray-200">
            <?php foreach ($activities as $activity): 
                // Set defaults for activity properties
                $icon = $activity['icon'] ?? '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>';
                $iconBg = $activity['iconBg'] ?? 'bg-blue-500';
                $title = $activity['title'] ?? '';
                $description = $activity['description'] ?? '';
                $date = $activity['date'] ?? '';
                $url = $activity['url'] ?? '';
            ?>
            <li>
                <?php if ($url): ?><a href="<?php echo h($url); ?>" class="block hover:bg-gray-50"><?php endif; ?>
                <div class="px-4 py-4 sm:px-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <span class="inline-flex items-center justify-center h-8 w-8 rounded-full <?php echo h($iconBg); ?>">
                                <?php echo str_replace('<svg', '<svg class="h-5 w-5 text-white"', $icon); ?>
                            </span>
                        </div>
                        <div class="min-w-0 flex-1 px-4">
                            <div>
                                <p class="text-sm font-medium text-indigo-600 truncate">
                                    <?php echo h($title); ?>
                                </p>
                                <p class="mt-1 text-sm text-gray-600">
                                    <?php echo h($description); ?>
                                </p>
                            </div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">
                                <time datetime="<?php echo h($date); ?>"><?php echo formatRelativeDate($date); ?></time>
                            </div>
                        </div>
                    </div>
                </div>
                <?php if ($url): ?></a><?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <?php if (isset($viewAllUrl)): ?>
    <div class="bg-gray-50 px-4 py-4 sm:px-6 border-t border-gray-200">
        <div class="text-sm">
            <a href="<?php echo h($viewAllUrl); ?>" class="font-medium text-indigo-600 hover:text-indigo-500">
                Voir toutes les activités
                <span aria-hidden="true">&rarr;</span>
            </a>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
/**
 * Format a date in a relative format (e.g., "2 hours ago", "3 days ago")
 * 
 * @param string $date Date string
 * @return string Formatted relative date
 */
function formatRelativeDate($date) {
    if (empty($date)) return '';
    
    $timestamp = strtotime($date);
    $now = time();
    $diff = $now - $timestamp;
    
    if ($diff < 60) {
        return 'À l\'instant';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return 'Il y a ' . $minutes . ' min' . ($minutes > 1 ? 's' : '');
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return 'Il y a ' . $hours . ' heure' . ($hours > 1 ? 's' : '');
    } elseif ($diff < 172800) {
        return 'Hier à ' . date('H:i', $timestamp);
    } elseif ($diff < 604800) {
        return date('l', $timestamp) . ' à ' . date('H:i', $timestamp);
    } elseif ($diff < 2592000) {
        $days = floor($diff / 86400);
        return 'Il y a ' . $days . ' jour' . ($days > 1 ? 's' : '');
    } else {
        return date('d/m/Y', $timestamp);
    }
}
?>