<?php
/**
 * Message Composer Component
 * Allows users to compose and send messages
 * 
 * Props:
 * @param string $conversationId The ID of the current conversation (optional for new conversations)
 * @param array $recipients Array of recipient IDs for new conversations (optional)
 * @param string $apiEndpoint API endpoint for sending messages
 * @param string $placeholder Placeholder text for the message input
 * @param bool $withAttachments Whether to include attachment functionality (default: true)
 * @param bool $withEmojis Whether to include emoji picker (default: false)
 * @param string $submitText Text for the submit button (default: "Envoyer")
 * @param string $class Additional CSS classes
 */

// Set defaults avec vérification d'existence
$withAttachments = isset($withAttachments) ? $withAttachments : true;
$withEmojis = isset($withEmojis) ? $withEmojis : false;
$placeholder = isset($placeholder) ? $placeholder : 'Écrivez votre message...';
$submitText = isset($submitText) ? $submitText : 'Envoyer';
$class = isset($class) ? $class : '';
$apiEndpoint = isset($apiEndpoint) ? $apiEndpoint : '/tutoring/api/messages/send.php';
$conversationId = isset($conversationId) ? $conversationId : null;
$recipients = isset($recipients) ? $recipients : [];

// Generate a unique ID for this component
$composerId = 'message-composer-' . uniqid();

// Helper function to escape HTML
if (!function_exists('h')) {
    function h($str) {
        return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
    }
}
?>

<div 
    class="bg-white border-t border-gray-200 p-4 <?php echo h($class); ?>"
    data-controller="message-composer"
    data-message-composer-api-endpoint-value="<?php echo h($apiEndpoint); ?>"
    <?php if ($conversationId): ?>
    data-message-composer-conversation-id-value="<?php echo h($conversationId); ?>"
    <?php endif; ?>
    id="<?php echo $composerId; ?>"
>
    <form data-action="submit->message-composer#sendMessage">
        <?php if ($conversationId): ?>
        <input type="hidden" name="conversation_id" value="<?php echo h($conversationId); ?>">
        <?php endif; ?>
        
        <?php if (!empty($recipients) && is_array($recipients)): ?>
        <?php foreach($recipients as $recipient): ?>
        <input type="hidden" name="recipients[]" value="<?php echo h($recipient); ?>">
        <?php endforeach; ?>
        <?php endif; ?>
        
        <div class="relative">
            <textarea 
                class="block w-full border-0 focus:ring-0 p-3 resize-none bg-gray-50 rounded-md text-sm min-h-[100px]"
                placeholder="<?php echo h($placeholder); ?>"
                data-message-composer-target="textarea"
                data-action="input->message-composer#adjustTextareaHeight keydown->message-composer#handleKeydown"
                name="content"
                required
            ></textarea>
            
            <div class="absolute right-3 bottom-3 flex space-x-1">
                <!-- Loading spinner (hidden by default) -->
                <div class="hidden" data-message-composer-target="spinner">
                    <svg class="animate-spin h-5 w-5 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
            </div>
        </div>
        
        <div class="mt-2 flex justify-between items-center">
            <div class="flex space-x-1">
                <?php if ($withAttachments): ?>
                <button 
                    type="button"
                    class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    data-action="message-composer#toggleAttachments"
                    title="Ajouter une pièce jointe"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                    </svg>
                    <span class="ml-1 hidden sm:inline">Joindre</span>
                </button>
                <?php endif; ?>
                
                <?php if ($withEmojis): ?>
                <button 
                    type="button"
                    class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    data-action="message-composer#toggleEmojis"
                    title="Ajouter un emoji"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="ml-1 hidden sm:inline">Emoji</span>
                </button>
                <?php endif; ?>
            </div>
            
            <button 
                type="submit"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                data-message-composer-target="submitButton"
            >
                <span class="mr-2"><?php echo h($submitText); ?></span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                </svg>
            </button>
        </div>
        
        <?php if ($withAttachments): ?>
        <!-- Attachment upload section (hidden by default) -->
        <div class="mt-3 p-3 bg-gray-50 rounded-md border border-gray-200 hidden" data-message-composer-target="attachments">
            <h3 class="text-sm font-medium mb-2">Ajouter des pièces jointes</h3>
            
            <div class="flex flex-wrap gap-2" data-message-composer-target="filePreview"></div>
            
            <div class="mt-2 flex items-center">
                <label class="block">
                    <span class="sr-only">Choisir des fichiers</span>
                    <input 
                        type="file" 
                        multiple
                        class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                        data-message-composer-target="fileInput"
                        data-action="change->message-composer#handleFileSelect"
                        accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.xls,.xlsx,.ppt,.pptx"
                    >
                </label>
            </div>
            
            <div class="mt-2 text-xs text-gray-500">
                <p>Types de fichiers acceptés: PDF, Word, Images, Excel, PowerPoint (Max: 10 MB par fichier)</p>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Error message area -->
        <div class="mt-2 text-sm text-red-600 hidden" data-message-composer-target="error"></div>
        
        <!-- Success message area -->
        <div class="mt-2 text-sm text-green-600 hidden" data-message-composer-target="success"></div>
    </form>
</div>