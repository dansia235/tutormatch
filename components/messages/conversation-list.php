<?php
/**
 * Conversation List Component
 * Displays a list of conversations with search and filtering capabilities
 * 
 * Props:
 * @param array $conversations Array of conversation objects
 * @param string $class Additional CSS classes for the component
 * @param bool $withSearch Include search functionality (default: true)
 * @param string $activeConversationId The ID of the currently active conversation (optional)
 * @param string $emptyMessage Message to display when no conversations are available (optional)
 */

// Set default values
$withSearch = isset($withSearch) ? $withSearch : true;
$emptyMessage = isset($emptyMessage) ? $emptyMessage : 'Aucune conversation disponible.';
$class = isset($class) ? $class : '';
$componentId = 'conversation-list-' . uniqid();
$conversations = isset($conversations) ? $conversations : [];
$activeConversationId = isset($activeConversationId) ? $activeConversationId : null;

// Helper function to escape HTML
if (!function_exists('h')) {
    function h($str) {
        return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
    }
}
?>

<div class="<?php echo h($class); ?>" data-controller="conversation-list" id="<?php echo $componentId; ?>">
    <?php if ($withSearch): ?>
    <div class="relative mb-4">
        <div class="flex items-center p-2 bg-gray-50 rounded-lg border border-gray-200">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input 
                type="text" 
                class="w-full bg-transparent border-0 focus:ring-0 text-gray-600 text-sm placeholder-gray-400" 
                placeholder="Rechercher dans les conversations..." 
                data-conversation-list-target="search"
                data-action="input->conversation-list#search"
            >
        </div>
    </div>
    <?php endif; ?>

    <div data-conversation-list-target="list">
        <?php if (empty($conversations)): ?>
        <div class="py-10 px-4 text-center" data-conversation-list-target="empty">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-indigo-50 mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
            </div>
            <p class="text-gray-500"><?php echo h($emptyMessage); ?></p>
            <button 
                class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                data-action="conversation-list#showNewMessageModal"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Nouveau message
            </button>
        </div>
        <?php else: ?>
        <div class="space-y-1">
            <?php foreach ($conversations as $conversation): ?>
                <?php
                    // Détermine si cette conversation est active
                    $isActive = $activeConversationId && $activeConversationId === $conversation['id'];
                    
                    // Récupérer l'autre participant
                    $otherParticipant = null;
                    $currentUserId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
                    
                    if (isset($conversation['participants']) && is_array($conversation['participants'])) {
                        foreach ($conversation['participants'] as $participant) {
                            if (!$currentUserId || $participant['id'] != $currentUserId) {
                                $otherParticipant = $participant;
                                break;
                            }
                        }
                    }
                    
                    // Générer une URL d'avatar
                    $avatarUrl = '';
                    if ($otherParticipant) {
                        $avatarUrl = isset($otherParticipant['avatar']) && !empty($otherParticipant['avatar']) 
                            ? $otherParticipant['avatar'] 
                            : "https://ui-avatars.com/api/?name=" . urlencode($otherParticipant['name'] ?? 'U') . "&background=4f46e5&color=fff";
                    }
                    
                    // Formatter la date du dernier message
                    $lastMessageDate = '';
                    if (isset($conversation['last_message']['sent_at'])) {
                        $lastMessageDate = formatRelativeTime($conversation['last_message']['sent_at']);
                    } elseif (isset($conversation['updated_at'])) {
                        $lastMessageDate = formatRelativeTime($conversation['updated_at']);
                    }

                    // Vérifier s'il y a des messages non lus
                    $hasUnread = isset($conversation['unread_count']) && $conversation['unread_count'] > 0;
                ?>
                
                <div 
                    class="conversation-item p-3 rounded-lg cursor-pointer transition-all duration-200 <?php echo $isActive ? 'bg-indigo-50 border-l-4 border-indigo-500' : 'hover:bg-gray-50'; ?> <?php echo $hasUnread ? 'font-semibold' : ''; ?>"
                    data-action="click->conversation-list#selectConversation"
                    data-conversation-id="<?php echo h($conversation['id']); ?>"
                    data-conversation-list-target="conversation"
                >
                    <div class="flex items-start">
                        <?php if ($otherParticipant && $avatarUrl): ?>
                        <div class="relative mr-3 flex-shrink-0">
                            <img 
                                class="h-10 w-10 rounded-full object-cover" 
                                src="<?php echo h($avatarUrl); ?>" 
                                alt="<?php echo h($otherParticipant['name'] ?? ''); ?>"
                            >
                            <?php if (isset($otherParticipant['status']) && $otherParticipant['status'] === 'online'): ?>
                            <span class="absolute bottom-0 right-0 block h-2.5 w-2.5 rounded-full bg-green-400 ring-2 ring-white"></span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="min-w-0 flex-1">
                            <div class="flex justify-between items-center mb-1">
                                <h4 class="text-sm font-medium text-gray-900 truncate">
                                    <?php if (isset($conversation['is_group']) && $conversation['is_group']): ?>
                                        <?php echo h($conversation['title'] ?? 'Groupe'); ?>
                                    <?php else: ?>
                                        <?php echo h($otherParticipant['name'] ?? 'Utilisateur'); ?>
                                    <?php endif; ?>
                                </h4>
                                <span class="text-xs text-gray-500">
                                    <?php echo h($lastMessageDate); ?>
                                </span>
                            </div>
                            
                            <div class="flex justify-between">
                                <p class="text-sm text-gray-500 truncate">
                                    <?php if (isset($conversation['last_message'])): ?>
                                        <?php 
                                        $lastMessageContent = isset($conversation['last_message']['content']) ? 
                                            $conversation['last_message']['content'] : '';
                                        $isSender = isset($conversation['last_message']['sender']) && 
                                                   $conversation['last_message']['sender'] === 'you';
                                        ?>
                                        <?php if ($isSender): ?>
                                            <span class="text-gray-400">Vous: </span>
                                        <?php endif; ?>
                                        <?php echo h(substr($lastMessageContent, 0, 50)) . (strlen($lastMessageContent) > 50 ? '...' : ''); ?>
                                    <?php else: ?>
                                        <span class="text-gray-400 italic">Aucun message</span>
                                    <?php endif; ?>
                                </p>
                                
                                <?php if ($hasUnread): ?>
                                <span class="inline-flex items-center justify-center h-5 w-5 rounded-full bg-indigo-600 text-xs font-medium text-white ml-2">
                                    <?php echo min($conversation['unread_count'], 99); ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($otherParticipant && isset($otherParticipant['role'])): ?>
                            <div class="mt-1">
                                <span class="inline-block text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">
                                    <?php echo h($otherParticipant['role']); ?>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
/**
 * Format a timestamp into a relative time string (e.g., "2 hours ago")
 * 
 * @param string $dateTime The datetime string to format
 * @return string Formatted relative time
 */
function formatRelativeTime($dateTime) {
    if (empty($dateTime)) return '';
    
    $timestamp = strtotime($dateTime);
    $now = time();
    $diff = $now - $timestamp;
    
    if ($diff < 60) {
        return 'À l\'instant';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return 'Il y a ' . $minutes . ' min';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return 'Il y a ' . $hours . ' h';
    } elseif (date('Y-m-d', $timestamp) === date('Y-m-d', strtotime('yesterday'))) {
        return 'Hier';
    } elseif ($diff < 604800) { // Moins d'une semaine
        // Utiliser DateTime au lieu de strftime
        $dateObj = new DateTime($dateTime);
        $formatter = new IntlDateFormatter(
            'fr_FR',
            IntlDateFormatter::FULL,
            IntlDateFormatter::NONE,
            'Europe/Paris',
            IntlDateFormatter::GREGORIAN,
            'EEEE'
        );
        return ucfirst($formatter->format($dateObj));
    } else {
        return date('d/m/y', $timestamp);
    }
}
?>