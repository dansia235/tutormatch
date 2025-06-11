<?php
/**
 * Message Bubble Component
 * Displays a single message in a conversation
 * 
 * Props:
 * @param array $message Message data to display
 * @param bool $isOutgoing Whether this message was sent by the current user
 * @param string $avatar URL for the avatar image
 * @param string $senderName Name of the sender
 * @param bool $showDate Whether to display the date (default: true)
 * @param bool $showAvatar Whether to display the avatar (default: true) 
 * @param string $class Additional CSS classes
 */

// Set defaults
$isOutgoing = isset($isOutgoing) ? $isOutgoing : false;
$showDate = isset($showDate) ? $showDate : true;
$showAvatar = isset($showAvatar) ? $showAvatar : true;
$class = isset($class) ? $class : '';
$senderName = isset($senderName) ? $senderName : 'Utilisateur';
$avatar = isset($avatar) ? $avatar : "https://ui-avatars.com/api/?name=" . urlencode($senderName) . "&background=4f46e5&color=fff";

// Déterminer le statut de lecture
$isRead = false;
if (isset($message['status'])) {
    $isRead = $message['status'] === 'read';
} elseif (isset($message['read_at'])) {
    $isRead = !empty($message['read_at']);
} elseif (isset($message['is_read'])) {
    $isRead = $message['is_read'];
}

// Récupérer la date d'envoi
$sentAt = null;
if (isset($message['sent_at'])) {
    $sentAt = $message['sent_at'];
} elseif (isset($message['created_at'])) {
    $sentAt = $message['created_at'];
}

$isRecent = $sentAt ? (time() - strtotime($sentAt)) < 86400 : false;

// Format the message timestamp
$formattedTime = '';
if ($sentAt) {
    $formattedTime = $isRecent 
        ? date('H:i', strtotime($sentAt)) 
        : date('d/m H:i', strtotime($sentAt));
}

// Helper function to escape HTML
if (!function_exists('h')) {
    function h($str) {
        return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// Récupérer le contenu du message
$messageContent = '';
if (isset($message['content'])) {
    $messageContent = $message['content'];
} elseif (isset($message['message'])) {
    $messageContent = $message['message'];
} elseif (isset($message['text'])) {
    $messageContent = $message['text'];
}
?>

<div class="<?php echo h($class); ?>">
    <div class="flex items-start <?php echo $isOutgoing ? 'justify-end' : 'justify-start'; ?> group">
        <?php if (!$isOutgoing && $showAvatar): ?>
        <div class="flex-shrink-0 mr-2">
            <img 
                src="<?php echo h($avatar); ?>" 
                alt="<?php echo h($senderName); ?>" 
                class="h-8 w-8 rounded-full object-cover"
            >
        </div>
        <?php endif; ?>
        
        <div class="relative max-w-md lg:max-w-lg <?php echo $isOutgoing ? 'order-1' : 'order-2'; ?>">
            <?php if (!$isOutgoing && $showDate): ?>
            <p class="text-xs text-gray-500 mb-1"><?php echo h($senderName); ?></p>
            <?php endif; ?>
            
            <div class="inline-block px-4 py-3 rounded-xl shadow-sm break-words text-sm
                <?php echo $isOutgoing 
                    ? 'bg-indigo-600 text-white rounded-br-none' 
                    : 'bg-white border border-gray-200 rounded-bl-none'; ?>">
                <?php echo nl2br(h($messageContent)); ?>
            </div>
            
            <div class="flex items-center mt-1 text-xs <?php echo $isOutgoing ? 'justify-end' : ''; ?>">
                <span class="text-gray-500">
                    <?php echo h($formattedTime); ?>
                </span>
                
                <?php if ($isOutgoing): ?>
                <span class="ml-1">
                    <?php if ($isRead): ?>
                    <!-- Double check mark for read -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <?php else: ?>
                    <!-- Single check mark for sent -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    <?php endif; ?>
                </span>
                <?php endif; ?>
            </div>
            
            <!-- Options menu (visible on hover) -->
            <div class="absolute top-0 <?php echo $isOutgoing ? '-left-6' : '-right-6'; ?> hidden group-hover:block">
                <button class="text-gray-400 hover:text-gray-600 focus:outline-none" type="button">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                    </svg>
                </button>
            </div>
        </div>
        
        <?php if ($isOutgoing && $showAvatar): ?>
        <div class="flex-shrink-0 ml-2 order-3">
            <img 
                src="<?php echo h($avatar); ?>" 
                alt="<?php echo h($senderName); ?>" 
                class="h-8 w-8 rounded-full object-cover"
            >
        </div>
        <?php endif; ?>
    </div>
</div>