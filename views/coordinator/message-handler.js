/**
 * Gestionnaire spécifique pour les messages des coordinateurs
 */

document.addEventListener('DOMContentLoaded', function() {
    // Vérifier si nous sommes sur la page de messagerie
    if (!document.getElementById('message-list') && !document.getElementById('conversation-messages')) {
        return;
    }

    console.log('Initialisation du gestionnaire de messages pour les coordinateurs');

    // Récupérer l'ID de l'utilisateur
    const currentUserId = window.currentUserId || document.querySelector('meta[name="user-id"]')?.content;
    
    if (!currentUserId) {
        console.error('ID utilisateur non trouvé dans le DOM');
        return;
    }
    
    // Récupérer les éléments du DOM
    const conversationCards = document.querySelectorAll('.message-card');
    const replyButton = document.getElementById('reply-button');
    const replySection = document.getElementById('reply-section');
    const replyForm = document.getElementById('reply-form');
    const deleteButton = document.getElementById('delete-button');
    
    // Ajouter des événements de clic aux cartes de conversation
    conversationCards.forEach(card => {
        card.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Marquer la carte comme active
            conversationCards.forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            
            // Récupérer les données de la conversation
            const conversationId = this.dataset.conversationId;
            const participantId = this.dataset.participantId;
            const participantRole = this.dataset.participantRole;
            const messageIds = this.dataset.messageIds;
            
            // Charger la conversation
            loadConversation(conversationId, currentUserId);
            
            // Marquer les messages comme lus si nécessaire
            if (messageIds && messageIds.length > 0) {
                const messageIdArray = messageIds.split(',');
                messageIdArray.forEach(messageId => {
                    if (messageId) {
                        markMessageAsRead(messageId);
                    }
                });
                
                // Supprimer le badge non lu
                const badge = this.querySelector('.badge');
                if (badge) {
                    badge.remove();
                }
            }
            
            // Mettre à jour le formulaire de réponse
            if (replyForm) {
                document.getElementById('reply-conversation-id').value = conversationId;
                document.getElementById('reply-receiver-id').value = participantId;
                if (document.getElementById('reply-receiver-type')) {
                    document.getElementById('reply-receiver-type').value = participantRole.toLowerCase();
                }
            }
        });
    });
    
    // Gérer le bouton de réponse
    if (replyButton) {
        replyButton.addEventListener('click', function() {
            replySection.style.display = 'block';
            replyButton.style.display = 'none';
            document.getElementById('reply-form').querySelector('textarea').focus();
        });
    }
    
    // Gérer le formulaire d'envoi de message
    if (replyForm) {
        replyForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Récupérer les données du formulaire
            const formData = new FormData(this);
            
            // Ajouter l'action
            formData.append('send_message', '1');
            
            // Envoyer le message via fetch API
            fetch('/tutoring/views/coordinator/messages.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (response.ok) {
                    // Réinitialiser le formulaire
                    replyForm.reset();
                    
                    // Recharger la conversation
                    const conversationId = document.getElementById('reply-conversation-id').value;
                    loadConversation(conversationId, currentUserId);
                    
                    // Ajouter message temporaire pour affichage immédiat
                    const messageContent = formData.get('message_content');
                    addTemporaryMessage(messageContent);
                } else {
                    console.error('Erreur lors de l\'envoi du message:', response.statusText);
                    alert('Erreur lors de l\'envoi du message. Veuillez réessayer.');
                }
            })
            .catch(error => {
                console.error('Erreur lors de l\'envoi du message:', error);
                alert('Erreur lors de l\'envoi du message. Veuillez réessayer.');
            });
        });
    }
    
    // Fonction pour ajouter un message temporaire
    function addTemporaryMessage(content) {
        const messageList = document.getElementById('message-list');
        if (!messageList) return;
        
        const now = new Date();
        const timeString = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        
        const tempMessage = document.createElement('div');
        tempMessage.className = 'message message-sent d-flex justify-content-end mb-3';
        tempMessage.innerHTML = `
            <div>
                <div class="message-bubble bg-primary text-white">
                    <div class="message-content">${content}</div>
                </div>
                <div class="text-end mt-1">
                    <small class="text-muted">${timeString} (envoyé)</small>
                </div>
            </div>
        `;
        
        messageList.appendChild(tempMessage);
        messageList.scrollTop = messageList.scrollHeight;
    }
    
    // Fonction pour marquer un message comme lu
    function markMessageAsRead(messageId) {
        fetch('/tutoring/api/messages/mark-read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: `message_id=${messageId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Message marqué comme lu:', messageId);
            } else {
                console.warn('Échec du marquage du message comme lu:', messageId);
            }
        })
        .catch(error => {
            console.error('Erreur lors du marquage du message:', error);
        });
    }
    
    // Gérer le nouveau message modal
    const newMessageForm = document.querySelector('#newMessageModal form');
    if (newMessageForm) {
        newMessageForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Récupérer les données du formulaire
            const formData = new FormData(this);
            
            // Ajouter l'action
            formData.append('send_message', '1');
            
            // Envoyer le message via fetch API
            fetch('/tutoring/views/coordinator/messages.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (response.ok) {
                    // Fermer le modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('newMessageModal'));
                    if (modal) {
                        modal.hide();
                    }
                    
                    // Recharger la page pour voir le nouveau message
                    window.location.reload();
                } else {
                    console.error('Erreur lors de l\'envoi du message:', response.statusText);
                    alert('Erreur lors de l\'envoi du message. Veuillez réessayer.');
                }
            })
            .catch(error => {
                console.error('Erreur lors de l\'envoi du message:', error);
                alert('Erreur lors de l\'envoi du message. Veuillez réessayer.');
            });
        });
    }

    // Automatiquement charger la première conversation si disponible
    if (conversationCards.length > 0) {
        // Vérifier d'abord s'il y a une conversation active
        const activeConversation = document.querySelector('.message-card.active');
        
        if (activeConversation) {
            console.log('Chargement de la conversation active...');
            loadConversation(activeConversation.dataset.conversationId, currentUserId);
        } else {
            // Sinon charger la première conversation
            console.log('Chargement de la première conversation...');
            const firstConversation = conversationCards[0];
            firstConversation.classList.add('active');
            loadConversation(firstConversation.dataset.conversationId, currentUserId);
            
            // Mettre à jour le formulaire de réponse
            if (replyForm && firstConversation.dataset.participantId) {
                document.getElementById('reply-conversation-id').value = firstConversation.dataset.conversationId;
                document.getElementById('reply-receiver-id').value = firstConversation.dataset.participantId;
                if (document.getElementById('reply-receiver-type') && firstConversation.dataset.participantRole) {
                    document.getElementById('reply-receiver-type').value = firstConversation.dataset.participantRole.toLowerCase();
                }
            }
        }
    }
});