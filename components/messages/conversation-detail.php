<?php
/**
 * Conversation Detail Component
 * Displays the messages in a conversation and the message composer
 * 
 * Props:
 * @param array $conversation Conversation data (optional, if not provided an empty state is shown)
 * @param array $messages Messages in the conversation (optional)
 * @param string $currentUserId ID of the current user
 * @param string $apiEndpoint API endpoint for sending messages
 * @param string $class Additional CSS classes
 */

// Set defaults
$class = isset($class) ? $class : '';
$apiEndpoint = isset($apiEndpoint) ? $apiEndpoint : '/tutoring/api/messages/send.php';
$hasConversation = isset($conversation) && !empty($conversation);
$currentUserId = isset($currentUserId) ? $currentUserId : $_SESSION['user_id'];
$messages = isset($messages) ? $messages : [];

// Get current participant if this is a one-on-one conversation
$otherParticipant = null;
if ($hasConversation && isset($conversation['participants']) && is_array($conversation['participants'])) {
    foreach ($conversation['participants'] as $participant) {
        if ($participant['id'] != $currentUserId) {
            $otherParticipant = $participant;
            break;
        }
    }
}

// Generate avatar URL
$avatarUrl = '';
if ($otherParticipant) {
    $avatarUrl = isset($otherParticipant['avatar']) && !empty($otherParticipant['avatar']) 
        ? $otherParticipant['avatar'] 
        : "https://ui-avatars.com/api/?name=" . urlencode($otherParticipant['name'] ?? 'User') . "&background=4f46e5&color=fff";
}

// Helper function to escape HTML
if (!function_exists('h')) {
    function h($str) {
        return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
    }
}
?>

<div class="flex flex-col h-full <?php echo h($class); ?>">
    <?php if ($hasConversation): ?>
    <!-- Conversation Header -->
    <div 
        class="flex items-center p-4 border-b border-gray-200 bg-white" 
        data-message-interface-target="header"
    >
        <div class="flex items-center">
            <?php if ($otherParticipant): ?>
            <img 
                src="<?php echo h($avatarUrl); ?>" 
                alt="<?php echo h($otherParticipant['name'] ?? 'Utilisateur'); ?>" 
                class="h-10 w-10 rounded-full object-cover mr-3"
                data-message-interface-target="contactAvatar"
            >
            <div>
                <h2 class="text-base font-medium text-gray-900" data-message-interface-target="contactName">
                    <?php echo $conversation['is_group'] ? h($conversation['title'] ?? 'Groupe') : h($otherParticipant['name'] ?? 'Utilisateur'); ?>
                </h2>
                <p class="text-sm text-gray-500" data-message-interface-target="contactRole">
                    <?php echo h($otherParticipant['role'] ?? ''); ?>
                </p>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="ml-auto">
            <div class="flex space-x-1">
                <button 
                    type="button" 
                    class="inline-flex items-center p-1.5 border border-transparent rounded-full text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    title="Options"
                >
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Messages List -->
    <div 
        class="flex-grow overflow-y-auto p-4 space-y-4"
        data-message-interface-target="messagesList"
    >
        <?php if (empty($messages)): ?>
        <div class="flex items-center justify-center h-full">
            <div class="text-center">
                <div class="inline-flex items-center justify-center h-16 w-16 rounded-full bg-indigo-100 mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                </div>
                <p class="text-gray-500">Cette conversation ne contient pas encore de messages.</p>
            </div>
        </div>
        <?php else: ?>
            <?php 
            // Group messages by date for display
            $messagesByDate = [];
            $currentDate = null;
            
            foreach ($messages as $message) {
                // Utiliser sent_at au lieu de created_at
                $messageDate = date('Y-m-d', strtotime($message['sent_at'] ?? $message['created_at'] ?? 'now'));
                
                if (!isset($messagesByDate[$messageDate])) {
                    $messagesByDate[$messageDate] = [];
                }
                
                $messagesByDate[$messageDate][] = $message;
            }
            
            // Display messages grouped by date
            foreach ($messagesByDate as $date => $dateMessages):
                // Format the date
                $formattedDate = formatDateHeader($date);
            ?>
                <!-- Date separator -->
                <div class="flex items-center my-6">
                    <div class="flex-grow border-t border-gray-200"></div>
                    <div class="mx-4">
                        <span class="px-2 py-1 bg-gray-100 text-gray-500 text-xs font-medium rounded-full">
                            <?php echo h($formattedDate); ?>
                        </span>
                    </div>
                    <div class="flex-grow border-t border-gray-200"></div>
                </div>
                
                <?php foreach ($dateMessages as $message): 
                    $isOutgoing = $message['sender_id'] == $currentUserId;
                    
                    // Préparer les données du sender
                    $senderName = $isOutgoing ? 'Vous' : 
                        (isset($message['sender_first_name']) && isset($message['sender_last_name']) ? 
                            $message['sender_first_name'] . ' ' . $message['sender_last_name'] : 
                            'Utilisateur');
                    
                    $senderAvatar = $isOutgoing 
                        ? "https://ui-avatars.com/api/?name=Vous&background=4f46e5&color=fff"
                        : $avatarUrl;
                    
                    // Vérifier si le message est lu
                    $isRead = isset($message['status']) && $message['status'] === 'read';
                    
                    // Utiliser message-bubble.php avec les bonnes données
                    $avatar = $senderAvatar;
                    include(__DIR__ . '/message-bubble.php');
                ?>
                <?php endforeach; ?>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <!-- Loading indicator (hidden by default) -->
        <div class="text-center py-4 hidden" data-message-interface-target="loadingIndicator">
            <svg class="inline-block animate-spin h-6 w-6 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>
    </div>
    
    <!-- Message Composer -->
    <div data-message-interface-target="composer">
        <?php
        // Include the message composer component
        $composerData = [
            'apiEndpoint' => $apiEndpoint,
            'placeholder' => 'Écrivez votre message...',
            'withAttachments' => true,
            'withEmojis' => true,
        ];
        
        if ($hasConversation) {
            $composerData['conversationId'] = $conversation['id'];
        }
        
        if (isset($recipients) && !empty($recipients)) {
            $composerData['recipients'] = $recipients;
        }
        
        extract($composerData);
        include(__DIR__ . '/message-composer.php');
        ?>
    </div>
    <?php else: ?>
    <!-- Empty State (No conversation selected) -->
    <div 
        class="flex items-center justify-center h-full bg-gray-50" 
        data-message-interface-target="emptyState"
    >
        <div class="text-center px-4 py-12">
            <div class="inline-flex items-center justify-center h-20 w-20 rounded-full bg-indigo-100 mb-6">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Messagerie</h3>
            <p class="text-gray-500 mb-6 max-w-md">
                Sélectionnez une conversation dans la liste ou démarrez une nouvelle conversation pour commencer à échanger des messages.
            </p>
            <button 
                type="button" 
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                data-action="message-interface#showNewMessageModal"
            >
                <svg class="h-4 w-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Nouveau message
            </button>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
/**
 * Helper function to format the date header for message groups
 * Remplace strftime() qui est déprécié
 */
function formatDateHeader($date) {
    $timestamp = strtotime($date);
    $today = strtotime('today');
    $yesterday = strtotime('yesterday');
    
    if ($timestamp >= $today) {
        return "Aujourd'hui";
    } elseif ($timestamp >= $yesterday) {
        return "Hier";
    } else {
        // Utiliser DateTime au lieu de strftime
        $dateObj = new DateTime($date);
        $formatter = new IntlDateFormatter(
            'fr_FR',
            IntlDateFormatter::FULL,
            IntlDateFormatter::NONE,
            'Europe/Paris',
            IntlDateFormatter::GREGORIAN,
            'EEEE d MMMM yyyy'
        );
        return ucfirst($formatter->format($dateObj));
    }
}
?>