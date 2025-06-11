/**
 * JavaScript commun pour les interfaces de messagerie
 */

// Fonction pour charger une conversation
function loadConversation(conversationId, currentUserId) {
    console.log('Loading conversation:', conversationId, 'for user:', currentUserId);
    
    // Ajouter un log détaillé pour aider au débogage
    console.log('DEBUG - loadConversation called with:', {
        conversationId: conversationId,
        currentUserId: currentUserId,
        timestamp: new Date().toISOString(),
        url: window.location.href,
        availableElements: {
            conversationPlaceholder: !!document.getElementById('conversation-placeholder'),
            conversationContent: !!document.getElementById('conversation-content'),
            conversationMessages: !!document.getElementById('conversation-messages'),
            messageList: !!document.getElementById('message-list'),
            replyButton: !!document.getElementById('reply-button'),
            replySection: !!document.getElementById('reply-section')
        }
    });
    
    // Récupérer les éléments de l'interface
    const conversationPlaceholder = document.getElementById('conversation-placeholder');
    const conversationContent = document.getElementById('conversation-content');
    const conversationMessages = document.getElementById('conversation-messages') || document.getElementById('message-list');
    const replyButton = document.getElementById('reply-button');
    const deleteButton = document.getElementById('delete-button');
    const replySection = document.getElementById('reply-section');
    
    // Afficher l'interface de conversation
    if (conversationPlaceholder) conversationPlaceholder.style.display = 'none';
    if (conversationContent) conversationContent.style.display = 'block';
    if (replyButton) replyButton.style.display = 'inline-block';
    if (deleteButton) deleteButton.style.display = 'inline-block';
    
    // Obtenir la carte de conversation
    const selectedCard = document.querySelector(`.message-card[data-conversation-id="${conversationId}"]`);
    
    if (!selectedCard) {
        console.error('Conversation card not found for ID:', conversationId);
        return;
    }
    
    console.log('Selected card data attributes:', selectedCard.dataset);
    
    // Marquer la conversation comme sélectionnée
    const conversationCards = document.querySelectorAll('.message-card');
    conversationCards.forEach(card => {
        card.classList.remove('active');
    });
    
    selectedCard.classList.add('active');
    
    // Récupérer les détails de la conversation depuis les attributs data
    const participantName = selectedCard.querySelector('strong')?.textContent.trim().replace(/\d+/g, '').trim() || 'Contact';
    const avatarUrl = selectedCard.querySelector('.message-avatar')?.src || "https://ui-avatars.com/api/?name=Contact&background=3498db&color=fff";
    const badge = selectedCard.querySelector('.badge');
    
    // Si la conversation a des messages non lus, les marquer comme lus
    if (selectedCard.classList.contains('unread') || (badge && badge.textContent > 0)) {
        // Marquer visuellement comme lu
        selectedCard.classList.remove('unread');
        
        // Supprimer le badge ou mettre son contenu à zéro
        if (badge) {
            badge.style.display = 'none';
        }
        
        // Envoyer une requête AJAX pour marquer les messages comme lus
        const messageIds = selectedCard.dataset.messageIds;
        if (messageIds) {
            const ids = messageIds.split(',').filter(id => id.trim() !== '');
            ids.forEach(id => {
                fetch('/tutoring/api/messages/mark-read.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: `message_id=${id}&mark_as_read=1`
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Message marked as read:', data);
                })
                .catch(error => {
                    console.error('Error marking message as read:', error);
                });
            });
        }
    }
    
    // Mettre à jour les détails de la conversation dans l'interface
    const conversationTitleEl = document.getElementById('conversation-title');
    const conversationParticipantEl = document.getElementById('conversation-participant');
    const conversationAvatarEl = document.getElementById('conversation-avatar');
    const conversationParticipantRoleEl = document.getElementById('conversation-participant-role');
    const replyReceiverIdEl = document.getElementById('reply-receiver-id');
    const replyConversationIdEl = document.getElementById('reply-conversation-id');
    
    if (conversationTitleEl) conversationTitleEl.textContent = 'Conversation avec ' + participantName;
    if (conversationParticipantEl) conversationParticipantEl.textContent = participantName;
    if (conversationAvatarEl) conversationAvatarEl.src = avatarUrl;
    
    // Extraire et définir le rôle du participant et l'ID du destinataire
    const participantRole = selectedCard.dataset.participantRole || 'Contact';
    const receiverId = selectedCard.dataset.participantId || '1';
    
    if (conversationParticipantRoleEl) conversationParticipantRoleEl.textContent = participantRole;
    if (replyReceiverIdEl) replyReceiverIdEl.value = receiverId;
    
    // Activer le formulaire de réponse
    if (replySection) replySection.style.display = 'block';
    if (replyConversationIdEl) replyConversationIdEl.value = conversationId;
    
    // Récupérer les messages de la conversation via AJAX
    const timestamp = new Date().getTime(); // Ajouter un timestamp pour éviter le cache
    fetch(`/tutoring/api/messages/conversation-by-id.php?id=${conversationId}&_=${timestamp}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Cache-Control': 'no-cache'
        }
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('API response:', data);
        console.log('API response raw:', JSON.stringify(data));
        
        if (data.success && (data.messages || data.data)) {
            const messages = data.messages || data.data;
            console.log(`Displaying ${messages.length} messages`);
            
            // Vérifier que nous avons des messages valides
            if (messages && Array.isArray(messages) && messages.length > 0) {
                // Vérifier le format des messages pour le débogage
                console.log('First message format:', messages[0]);
                displayMessages(messages, currentUserId);
                
                // Mettre à jour le titre de la conversation si nécessaire
                if (data.conversation && data.conversation.title && conversationTitleEl) {
                    conversationTitleEl.textContent = data.conversation.title;
                }
            } else {
                console.warn('Messages array is empty or invalid');
                displayFallbackMessages();
            }
        } else {
            console.warn('No messages in response or success is false');
            // Fallback - Afficher un message d'exemple si les données ne sont pas disponibles
            displayFallbackMessages();
        }
        
        // Si nous avons des informations de debug, les afficher dans la console
        if (data.debug) {
            console.log('Debug info:', data.debug);
        }
    })
    .catch(error => {
        console.error('Error fetching conversation messages:', error);
        displayFallbackMessages();
    });
}

// Fonction pour afficher les messages d'une conversation
function displayMessages(messages, currentUserId) {
    console.log('DisplayMessages called with', messages?.length || 0, 'messages and currentUserId:', currentUserId);
    
    const messageList = document.getElementById('message-list') || document.getElementById('conversation-messages');
    if (!messageList) {
        console.error('Message list container not found, searched for IDs: message-list, conversation-messages');
        // Essayer de trouver une alternative
        messageList = document.querySelector('.messages-container') || document.createElement('div');
        console.log('Alternative container found:', messageList);
    }
    
    messageList.innerHTML = '';
    
    if (!messages || !Array.isArray(messages) || messages.length === 0) {
        messageList.innerHTML = `
            <div class="text-center py-4 text-muted">
                <i class="bi bi-chat-dots display-4"></i>
                <p class="mt-3">Aucun message dans cette conversation</p>
            </div>
        `;
        return;
    }
    
    console.log(`Processing ${messages.length} messages to display`);
    
    // Trier les messages par date
    try {
        messages.sort((a, b) => {
            const dateA = a.sent_at ? new Date(a.sent_at) : (a.created_at ? new Date(a.created_at) : new Date(0));
            const dateB = b.sent_at ? new Date(b.sent_at) : (b.created_at ? new Date(b.created_at) : new Date(0));
            return dateA - dateB;
        });
    } catch (error) {
        console.error('Error sorting messages:', error);
    }
    
    // Fonction pour échapper les caractères HTML
    const escapeHtml = (unsafe) => {
        if (typeof unsafe !== 'string') {
            return '';
        }
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    };
    
    // Regrouper les messages par date
    let currentDate = '';
    
    // Afficher chaque message
    messages.forEach((message, index) => {
        // Vérifier si le message contient les données nécessaires
        if (!message.sender_id || !message.content) {
            console.warn(`Skipping message ${index} due to missing data:`, message);
            return;
        }
        
        try {
            const isCurrentUser = message.sender_id == currentUserId;
            const messageType = isCurrentUser ? 'sent' : 'received';
            const userName = isCurrentUser ? 'Vous' : (message.sender_name || message.sender_first_name + ' ' + message.sender_last_name || 'Contact');
            const userAvatar = message.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(userName)}&background=${isCurrentUser ? '2ecc71' : '3498db'}&color=fff`;
            
            const messageDate = new Date(message.sent_at || message.created_at || new Date());
            
            // Vérifier si nous devons afficher un séparateur de date
            const messageDay = messageDate.toLocaleDateString('fr-FR');
            if (messageDay !== currentDate) {
                currentDate = messageDay;
                
                const today = new Date().toLocaleDateString('fr-FR');
                const yesterday = new Date(Date.now() - 86400000).toLocaleDateString('fr-FR');
                
                let dateLabel = messageDay;
                if (messageDay === today) {
                    dateLabel = 'Aujourd\'hui';
                } else if (messageDay === yesterday) {
                    dateLabel = 'Hier';
                }
                
                const dateSeparator = document.createElement('div');
                dateSeparator.className = 'message-date-separator text-center my-3';
                dateSeparator.innerHTML = `<span class="px-3 bg-light rounded">${dateLabel}</span>`;
                messageList.appendChild(dateSeparator);
            }
            
            const formattedDate = messageDate.toLocaleString('fr-FR', {
                hour: '2-digit',
                minute: '2-digit'
            });
            
            const messageElement = document.createElement('div');
            messageElement.className = `message message-${messageType} mb-3`;
            messageElement.dataset.messageId = message.id;
            
            // Nettoyer le contenu du message
            const safeContent = escapeHtml(message.content || '');
            const formattedContent = safeContent.replace(/\n/g, '<br>');
            
            if (isCurrentUser) {
                messageElement.innerHTML = `
                    <div class="d-flex justify-content-end">
                        <div class="message-bubble bg-primary text-white">
                            <div class="message-header">
                                <strong>${escapeHtml(userName)}</strong>
                                <small class="message-time">${formattedDate}</small>
                            </div>
                            <div class="message-body">
                                ${formattedContent}
                            </div>
                        </div>
                        <img src="${userAvatar}" alt="Avatar" class="message-avatar-small ms-2">
                    </div>
                `;
            } else {
                messageElement.innerHTML = `
                    <div class="d-flex justify-content-start">
                        <img src="${userAvatar}" alt="Avatar" class="message-avatar-small me-2">
                        <div class="message-bubble bg-light">
                            <div class="message-header">
                                <strong>${escapeHtml(userName)}</strong>
                                <small class="message-time">${formattedDate}</small>
                            </div>
                            <div class="message-body">
                                ${formattedContent}
                            </div>
                        </div>
                    </div>
                `;
            }
            
            messageList.appendChild(messageElement);
        } catch (error) {
            console.error(`Error processing message ${index}:`, error, message);
        }
    });
    
    // Faire défiler jusqu'au dernier message
    messageList.scrollTop = messageList.scrollHeight;
}

// Fonction de fallback pour afficher des messages d'exemple
function displayFallbackMessages() {
    const messageList = document.getElementById('message-list') || document.getElementById('conversation-messages');
    if (!messageList) {
        console.error('Message list container not found');
        return;
    }
    
    messageList.innerHTML = `
        <div class="message message-received mb-3">
            <div class="d-flex justify-content-start">
                <img src="https://ui-avatars.com/api/?name=Contact&background=3498db&color=fff" alt="Avatar" class="message-avatar-small me-2">
                <div class="message-bubble bg-light">
                    <div class="message-header">
                        <strong>Contact</strong>
                        <small class="message-time">Aujourd'hui</small>
                    </div>
                    <div class="message-body">
                        Aucun message disponible pour le moment.
                    </div>
                </div>
            </div>
        </div>
    `;
}

// Initialisation à l'exécution du document
document.addEventListener('DOMContentLoaded', function() {
    // Récupérer l'ID de l'utilisateur actuel (défini dans le HTML par PHP)
    const currentUserId = window.currentUserId || document.querySelector('meta[name="user-id"]')?.content;
    
    if (!currentUserId) {
        console.warn('Current user ID not found, message functions may not work correctly');
    }
    
    // Gestion des cartes de conversation
    const conversationCards = document.querySelectorAll('.message-card');
    console.log('Found', conversationCards.length, 'conversation cards');
    
    // Gérer le clic sur une conversation
    conversationCards.forEach(card => {
        card.addEventListener('click', function(e) {
            e.preventDefault();
            const conversationId = this.dataset.conversationId;
            if (conversationId) {
                console.log('Conversation card clicked, ID:', conversationId);
                loadConversation(conversationId, currentUserId);
            } else {
                console.error('No conversation ID found on card:', this);
            }
        });
    });
    
    // Gestion du bouton de réponse
    const replyButton = document.getElementById('reply-button');
    const replySection = document.getElementById('reply-section');
    
    if (replyButton && replySection) {
        replyButton.addEventListener('click', function() {
            replySection.style.display = 'block';
            const textarea = replySection.querySelector('textarea');
            if (textarea) {
                textarea.focus();
            }
        });
    }
    
    // Gestion du bouton de suppression
    const deleteButton = document.getElementById('delete-button');
    
    if (deleteButton) {
        deleteButton.addEventListener('click', function() {
            const activeCard = document.querySelector('.message-card.active');
            if (activeCard) {
                const conversationId = activeCard.dataset.conversationId;
                const deleteForm = document.getElementById('deleteMessageForm');
                if (deleteForm) {
                    const idField = document.getElementById('conversation_id_to_delete');
                    if (idField) {
                        idField.value = conversationId;
                    }
                    
                    const modal = new bootstrap.Modal(document.getElementById('deleteMessageModal'));
                    modal.show();
                }
            }
        });
    }
    
    // Ajuster la hauteur du textarea lors de la saisie
    const messageInputs = document.querySelectorAll('textarea[name="message_content"]');
    messageInputs.forEach(input => {
        input.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    });
    
    // Chargement automatique de la première conversation si elle existe
    if (conversationCards.length > 0) {
        // Vérifier d'abord s'il y a une conversation active
        const activeConversation = document.querySelector('.message-card.active');
        
        if (activeConversation) {
            console.log('Found active conversation, loading it');
            loadConversation(activeConversation.dataset.conversationId, currentUserId);
        } else {
            // Sinon charger la première conversation
            console.log('No active conversation, loading first one');
            const firstConversation = conversationCards[0];
            loadConversation(firstConversation.dataset.conversationId, currentUserId);
        }
    }
});