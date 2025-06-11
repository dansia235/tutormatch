<?php
/**
 * Meeting Schedule Component
 * Displays upcoming meetings in a timeline format
 * 
 * @param array $meetings Array of meeting objects
 * @param string $title Component title (default: "Réunions à venir")
 * @param string $emptyMessage Message to show when there are no meetings
 * @param string $viewAllUrl URL for "View all" link (optional)
 * @param string $class Additional CSS classes
 */

// Set defaults
$title = $title ?? 'Réunions à venir';
$emptyMessage = $emptyMessage ?? 'Aucune réunion planifiée pour le moment.';
$class = $class ?? '';
$meetings = $meetings ?? [];

// Generate a unique ID for this component
$id = 'meeting-schedule-' . uniqid();
?>

<div class="bg-white shadow-sm rounded-lg overflow-hidden <?php echo h($class); ?>" id="<?php echo $id; ?>">
    <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
        <h3 class="text-lg leading-6 font-medium text-gray-900">
            <?php echo h($title); ?>
        </h3>
    </div>
    
    <?php if (empty($meetings)): ?>
    <div class="p-6 text-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        <p class="mt-2 text-gray-500">
            <?php echo h($emptyMessage); ?>
        </p>
        <?php if (isset($addMeetingUrl)): ?>
        <div class="mt-3">
            <a href="<?php echo h($addMeetingUrl); ?>" class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <svg class="h-4 w-4 mr-1.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                </svg>
                Planifier une réunion
            </a>
        </div>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="bg-white overflow-hidden">
        <ul class="divide-y divide-gray-200">
            <?php foreach ($meetings as $meeting): 
                // Set defaults for meeting properties
                $title = $meeting['title'] ?? 'Réunion';
                $date = $meeting['date'] ?? '';
                $time = $meeting['time'] ?? '';
                $type = $meeting['type'] ?? 'online';
                $participants = $meeting['participants'] ?? [];
                $status = $meeting['status'] ?? 'scheduled';
                $url = $meeting['url'] ?? '';
                
                // Determine status styling
                $statusClass = '';
                $statusLabel = '';
                switch ($status) {
                    case 'scheduled':
                        $statusClass = 'bg-blue-100 text-blue-800';
                        $statusLabel = 'Planifiée';
                        break;
                    case 'completed':
                        $statusClass = 'bg-green-100 text-green-800';
                        $statusLabel = 'Terminée';
                        break;
                    case 'cancelled':
                        $statusClass = 'bg-red-100 text-red-800';
                        $statusLabel = 'Annulée';
                        break;
                    case 'in_progress':
                        $statusClass = 'bg-yellow-100 text-yellow-800';
                        $statusLabel = 'En cours';
                        break;
                    default:
                        $statusClass = 'bg-gray-100 text-gray-800';
                        $statusLabel = 'Non défini';
                }
                
                // Format the date for display
                $formattedDate = '';
                if ($date) {
                    $today = date('Y-m-d');
                    $tomorrow = date('Y-m-d', strtotime('+1 day'));
                    
                    if ($date === $today) {
                        $formattedDate = 'Aujourd\'hui';
                    } elseif ($date === $tomorrow) {
                        $formattedDate = 'Demain';
                    } else {
                        $formattedDate = date('d/m/Y', strtotime($date));
                    }
                    
                    if ($time) {
                        $formattedDate .= ' à ' . $time;
                    }
                }
                
                // Determine meeting type icon
                $typeIcon = '';
                switch ($type) {
                    case 'in_person':
                        $typeIcon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>';
                        break;
                    case 'phone':
                        $typeIcon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>';
                        break;
                    default: // online
                        $typeIcon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>';
                }
            ?>
            <li>
                <?php if ($url): ?><a href="<?php echo h($url); ?>" class="block hover:bg-gray-50"><?php endif; ?>
                <div class="px-4 py-4 sm:px-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <p class="text-sm font-medium text-indigo-600 truncate">
                                <?php echo h($title); ?>
                            </p>
                            <div class="ml-2 flex-shrink-0 flex">
                                <p class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $statusClass; ?>">
                                    <?php echo h($statusLabel); ?>
                                </p>
                            </div>
                        </div>
                        <div class="ml-2 flex-shrink-0 flex">
                            <svg class="flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                            </svg>
                            <p class="text-sm text-gray-500">
                                <?php echo h($formattedDate); ?>
                            </p>
                        </div>
                    </div>
                    <div class="mt-2 sm:flex sm:justify-between">
                        <div class="sm:flex">
                            <p class="flex items-center text-sm text-gray-500">
                                <?php echo $typeIcon; ?>
                                <?php echo h(ucfirst($type) === 'In_person' ? 'En personne' : (ucfirst($type) === 'Phone' ? 'Téléphone' : 'En ligne')); ?>
                            </p>
                            <?php if (!empty($participants)): ?>
                            <p class="mt-2 flex items-center text-sm text-gray-500 sm:mt-0 sm:ml-6">
                                <svg class="flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
                                </svg>
                                <?php 
                                    $participantCount = count($participants);
                                    echo $participantCount . ' participant' . ($participantCount > 1 ? 's' : '');
                                ?>
                            </p>
                            <?php endif; ?>
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
                Voir toutes les réunions
                <span aria-hidden="true">&rarr;</span>
            </a>
        </div>
    </div>
    <?php endif; ?>
</div>