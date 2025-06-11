/**
 * Gestionnaire spécifique pour les messages des étudiants
 */

document.addEventListener('DOMContentLoaded', function() {
    // Vérifier si nous sommes sur la page de messagerie
    if (!document.getElementById('message-list')) {
        return;
    }

    console.log('Initialisation du gestionnaire de messages pour les étudiants');

    // Récupérer l'ID de l'utilisateur
    const currentUserId = window.currentUserId || document.querySelector('meta[name="user-id"]')?.content;
    
    if (!currentUserId) {
        console.error('ID utilisateur non trouvé dans le DOM');
        return;
    }
    
    // Gérer le formulaire d'envoi de message
    const replyForm = document.getElementById('reply-form');
    if (replyForm) {
        replyForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Récupérer les données du formulaire
            const conversationId = document.getElementById('reply-conversation-id').value;
            const receiverId = document.getElementById('reply-receiver-id').value;
            const messageContent = this.querySelector('textarea[name="message_content"]').value;
            
            if (!messageContent.trim()) {
                console.warn('Message vide, envoi annulé');
                return;
            }
            
            console.log('Envoi du message en cours... Destinataire:', receiverId);
            
            // Envoyer le message via XHR
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '/tutoring/views/student/messages.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            
            xhr.onload = function() {
                if (xhr.status === 200) {
                    // Réinitialiser le formulaire
                    replyForm.reset();
                    
                    // Recharger la conversation
                    console.log('Message envoyé avec succès, rechargement de la conversation:', conversationId);
                    loadConversation(conversationId, currentUserId);
                } else {
                    console.error('Erreur lors de l\'envoi du message:', xhr.statusText);
                    alert('Erreur lors de l\'envoi du message. Veuillez réessayer.');
                }
            };
            
            xhr.onerror = function() {
                console.error('Erreur réseau lors de l\'envoi du message');
                alert('Erreur réseau lors de l\'envoi du message. Veuillez vérifier votre connexion.');
            };
            
            // Préparer les données d'envoi
            const data = 'send_message=1' + 
                         '&receiver_id=' + encodeURIComponent(receiverId) + 
                         '&message_content=' + encodeURIComponent(messageContent) + 
                         '&conversation_id=' + encodeURIComponent(conversationId);
            
            xhr.send(data);
        });
    }

    // Gérer le nouveau message modal
    const newMessageForm = document.querySelector('#newMessageModal form');
    if (newMessageForm) {
        newMessageForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Récupérer les données du formulaire
            const formData = new FormData(this);
            
            // Envoyer le message via fetch API
            fetch('/tutoring/views/student/messages.php', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => {
                // Fermer le modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('newMessageModal'));
                if (modal) {
                    modal.hide();
                }
                
                // Recharger la page pour voir le nouveau message
                window.location.reload();
            })
            .catch(error => {
                console.error('Erreur lors de l\'envoi du message:', error);
                alert('Erreur lors de l\'envoi du message. Veuillez réessayer.');
            });
        });
    }

    // Automatiquement charger la première conversation si disponible
    const conversationCards = document.querySelectorAll('.message-card');
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
            loadConversation(firstConversation.dataset.conversationId, currentUserId);
        }
    }
});