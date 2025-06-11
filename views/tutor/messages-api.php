<?php
/**
 * Page de messagerie pour les tuteurs - Version API
 */

// Titre de la page
$pageTitle = 'Messagerie';
$currentPage = 'messages';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../includes/init.php';

// Vérifier que l'utilisateur est tuteur
requireRole('teacher');

// Inclure l'en-tête
include_once __DIR__ . '/../common/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-chat-dots me-2"></i>Messagerie</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/tutoring/views/tutor/dashboard.php">Tableau de bord</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Messagerie</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <div class="row">
        <!-- Liste des contacts -->
        <div class="col-md-4 col-lg-3">
            <div class="card border-0 shadow-sm mb-4 messages-contacts">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Contacts</h5>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-primary" type="button" id="newMessageDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-plus-lg"></i>
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="newMessageDropdown">
                            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#newMessageModal">Nouveau message</a></li>
                            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#newGroupModal">Nouveau groupe</a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="p-3">
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="Rechercher..." id="contact-search">
                            <button class="btn btn-outline-secondary" type="button">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                    
                    <ul class="list-group list-group-flush contact-list" id="contacts-container">
                        <!-- Les contacts seront chargés dynamiquement ici -->
                        <li class="list-group-item text-center py-4 loading-placeholder">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Chargement...</span>
                            </div>
                            <p class="text-muted mt-2">Chargement des contacts...</p>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Zone de conversation -->
        <div class="col-md-8 col-lg-9">
            <div class="card border-0 shadow-sm mb-4 messages-conversation">
                <div id="conversation-container">
                    <!-- Le contenu de la conversation sera chargé dynamiquement ici -->
                    <!-- État initial : aucun contact sélectionné -->
                    <div class="card-body text-center py-5" id="empty-conversation-state">
                        <div class="empty-state">
                            <i class="bi bi-chat-text text-muted display-1 mb-3"></i>
                            <h4>Sélectionnez un contact pour démarrer une conversation</h4>
                            <p class="text-muted">Choisissez un contact dans la liste ou démarrez une nouvelle conversation.</p>
                            <button class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#newMessageModal">
                                <i class="bi bi-plus-lg me-2"></i>Nouveau message
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour nouveau message -->
<div class="modal fade" id="newMessageModal" tabindex="-1" aria-labelledby="newMessageModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newMessageModalLabel">Nouveau message</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="new-message-form">
                    <div class="mb-3">
                        <label for="recipient" class="form-label">Destinataire</label>
                        <select class="form-select" id="recipient" required>
                            <option value="" disabled selected>Sélectionner un destinataire...</option>
                            <!-- Options chargées dynamiquement -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="new-message-content" class="form-label">Message</label>
                        <textarea class="form-control" id="new-message-content" rows="5" placeholder="Votre message..." required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="send-new-message">Envoyer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour nouveau groupe -->
<div class="modal fade" id="newGroupModal" tabindex="-1" aria-labelledby="newGroupModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newGroupModalLabel">Nouveau groupe</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="new-group-form">
                    <div class="mb-3">
                        <label for="group-name" class="form-label">Nom du groupe</label>
                        <input type="text" class="form-control" id="group-name" placeholder="Nom du groupe" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Membres</label>
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            <span class="badge bg-primary p-2">Vous</span>
                        </div>
                        <div class="group-members" id="group-members-container">
                            <!-- Options chargées dynamiquement -->
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="create-group">Créer le groupe</button>
            </div>
        </div>
    </div>
</div>

<style>
/* Styles pour la messagerie */
.messages-contacts {
    height: calc(100vh - 200px);
    max-height: 700px;
    display: flex;
    flex-direction: column;
}

.contact-list {
    overflow-y: auto;
    flex-grow: 1;
}

.contact-item {
    padding: 10px 15px;
    transition: background-color 0.2s;
    cursor: pointer;
}

.contact-item:hover {
    background-color: rgba(0, 0, 0, 0.05);
}

.contact-item.active {
    background-color: rgba(13, 110, 253, 0.1);
    border-left: 3px solid #0d6efd;
}

.messages-conversation {
    height: calc(100vh - 200px);
    max-height: 700px;
    display: flex;
    flex-direction: column;
}

.conversation-body {
    overflow-y: auto;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.message {
    display: flex;
    margin-bottom: 10px;
    max-width: 80%;
}

.message.incoming {
    align-self: flex-start;
}

.message.outgoing {
    align-self: flex-end;
    flex-direction: row-reverse;
}

.message-avatar {
    margin-right: 10px;
    align-self: flex-end;
}

.message-bubble {
    padding: 10px 15px;
    border-radius: 18px;
    word-break: break-word;
}

.incoming .message-bubble {
    background-color: #f0f2f5;
}

.outgoing .message-bubble {
    background-color: #e7f3ff;
}

.message-info {
    margin-top: 2px;
    font-size: 12px;
}

.incoming .message-info {
    text-align: left;
}

.outgoing .message-info {
    text-align: right;
}

.message-date-separator {
    text-align: center;
    margin: 15px 0;
    position: relative;
}

.message-date-separator::before {
    content: "";
    position: absolute;
    left: 0;
    top: 50%;
    width: 100%;
    height: 1px;
    background-color: #dee2e6;
    z-index: 1;
}

.message-date-separator span {
    display: inline-block;
    background-color: #fff;
    padding: 0 10px;
    position: relative;
    z-index: 2;
    font-size: 12px;
    color: #6c757d;
}

#message-input {
    resize: none;
    overflow: hidden;
    min-height: 40px;
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    color: #6c757d;
}

.loading-indicator {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.conversation-loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 300px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Variables globales
    let currentContact = null;
    let contacts = [];
    let messages = [];
    let lastMessageDate = null;
    
    // Éléments DOM principaux
    const contactsContainer = document.getElementById('contacts-container');
    const conversationContainer = document.getElementById('conversation-container');
    const emptyConversationState = document.getElementById('empty-conversation-state');
    const contactSearch = document.getElementById('contact-search');
    const newMessageModal = document.getElementById('newMessageModal');
    const newMessageForm = document.getElementById('new-message-form');
    const recipientSelect = document.getElementById('recipient');
    const newMessageContent = document.getElementById('new-message-content');
    const sendNewMessageBtn = document.getElementById('send-new-message');
    const groupMembersContainer = document.getElementById('group-members-container');
    const createGroupBtn = document.getElementById('create-group');
    
    // Charger les contacts via l'API
    function loadContacts() {
        fetch('/tutoring/api/messages/contacts.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur lors de la récupération des contacts');
                }
                return response.json();
            })
            .then(data => {
                contacts = data.contacts;
                renderContacts();
                populateRecipientSelect();
                populateGroupMembers();
                
                // Vérifier si un contact est spécifié dans l'URL
                const urlParams = new URLSearchParams(window.location.search);
                const contactId = urlParams.get('contact_id');
                const contactType = urlParams.get('contact_type');
                
                if (contactId && contactType) {
                    // Trouver le contact dans la liste
                    const contact = contacts.find(c => c.id == contactId && c.type === contactType);
                    if (contact) {
                        selectContact(contact);
                    }
                    
                    // Vérifier s'il y a un nouveau message à envoyer
                    const newMessage = urlParams.get('new_message');
                    if (newMessage) {
                        // Attendre que la conversation soit chargée
                        setTimeout(() => {
                            sendMessage(contactId, contactType, newMessage);
                        }, 500);
                        
                        // Nettoyer l'URL
                        const url = new URL(window.location);
                        url.searchParams.delete('new_message');
                        window.history.replaceState({}, '', url);
                    }
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                contactsContainer.innerHTML = `
                    <li class="list-group-item text-center py-4">
                        <i class="bi bi-exclamation-triangle text-warning display-4"></i>
                        <p class="text-muted mt-2">Erreur lors du chargement des contacts</p>
                        <button class="btn btn-sm btn-primary mt-2" onclick="loadContacts()">Réessayer</button>
                    </li>
                `;
            });
    }
    
    // Afficher les contacts dans la liste
    function renderContacts() {
        if (contacts.length === 0) {
            contactsContainer.innerHTML = `
                <li class="list-group-item text-center py-4">
                    <i class="bi bi-people text-muted display-4"></i>
                    <p class="text-muted mt-2">Aucun contact disponible</p>
                </li>
            `;
            return;
        }
        
        let html = '';
        contacts.forEach(contact => {
            const isActive = currentContact && currentContact.id == contact.id && currentContact.type === contact.type;
            html += `
                <li class="list-group-item contact-item ${isActive ? 'active' : ''}" data-id="${contact.id}" data-type="${contact.type}">
                    <a href="javascript:void(0);" class="d-flex align-items-center text-decoration-none text-dark">
                        <div class="position-relative me-3">
                            <img src="${contact.avatar}" alt="${contact.name}" class="rounded-circle" width="48" height="48">
                            <span class="position-absolute bottom-0 end-0 badge rounded-pill ${contact.status === 'online' ? 'bg-success' : 'bg-secondary'}">
                                <span class="visually-hidden">${contact.status}</span>
                            </span>
                        </div>
                        <div class="flex-grow-1 min-width-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 text-truncate">${contact.name}</h6>
                                <small class="text-muted ms-2">${contact.last_message_time || ''}</small>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <p class="mb-0 small text-truncate text-muted">${contact.last_message || ''}</p>
                                ${contact.unread_count > 0 ? `<span class="badge bg-primary rounded-pill ms-2">${contact.unread_count}</span>` : ''}
                            </div>
                            <small class="text-muted">${contact.role}</small>
                        </div>
                    </a>
                </li>
            `;
        });
        
        contactsContainer.innerHTML = html;
        
        // Ajouter les événements de clic
        document.querySelectorAll('.contact-item').forEach(item => {
            item.addEventListener('click', function() {
                const contactId = this.dataset.id;
                const contactType = this.dataset.type;
                
                const contact = contacts.find(c => c.id == contactId && c.type === contactType);
                if (contact) {
                    selectContact(contact);
                    
                    // Mettre à jour l'URL sans recharger la page
                    const url = new URL(window.location);
                    url.searchParams.set('contact_id', contactId);
                    url.searchParams.set('contact_type', contactType);
                    window.history.pushState({}, '', url);
                }
            });
        });
    }
    
    // Sélectionner un contact et charger la conversation
    function selectContact(contact) {
        // Mettre à jour le contact actif
        currentContact = contact;
        
        // Mettre à jour l'interface
        document.querySelectorAll('.contact-item').forEach(item => {
            if (item.dataset.id == contact.id && item.dataset.type === contact.type) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });
        
        // Afficher l'indicateur de chargement
        conversationContainer.innerHTML = `
            <div class="conversation-loading">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
                <p class="text-muted mt-3">Chargement de la conversation...</p>
            </div>
        `;
        
        // Charger la conversation
        loadConversation(contact.id, contact.type);
    }
    
    // Charger les messages de la conversation
    function loadConversation(contactId, contactType) {
        fetch(`/tutoring/api/messages/conversation.php?contact_id=${contactId}&contact_type=${contactType}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur lors de la récupération des messages');
                }
                return response.json();
            })
            .then(data => {
                currentContact = data.contact;
                messages = data.messages;
                renderConversation();
                
                // Marquer les messages comme lus dans l'interface
                const contactItem = document.querySelector(`.contact-item[data-id="${contactId}"][data-type="${contactType}"]`);
                if (contactItem) {
                    const badge = contactItem.querySelector('.badge.bg-primary');
                    if (badge) {
                        badge.remove();
                    }
                }
                
                // Mettre à jour le contact dans la liste
                const contactIndex = contacts.findIndex(c => c.id == contactId && c.type === contactType);
                if (contactIndex !== -1) {
                    contacts[contactIndex].unread_count = 0;
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                conversationContainer.innerHTML = `
                    <div class="card-body text-center py-5">
                        <div class="empty-state">
                            <i class="bi bi-exclamation-triangle text-warning display-4"></i>
                            <h4>Erreur lors du chargement de la conversation</h4>
                            <p class="text-muted">${error.message}</p>
                            <button class="btn btn-primary mt-3" onclick="loadConversation(${contactId}, '${contactType}')">
                                <i class="bi bi-arrow-clockwise me-2"></i>Réessayer
                            </button>
                        </div>
                    </div>
                `;
            });
    }
    
    // Afficher la conversation
    function renderConversation() {
        let conversationHtml = '';
        
        // En-tête de la conversation
        conversationHtml += `
            <div class="card-header bg-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <img src="${currentContact.avatar}" alt="${currentContact.name}" class="rounded-circle me-3" width="48" height="48">
                        <div>
                            <h5 class="mb-0">${currentContact.name}</h5>
                            <small class="text-muted">${currentContact.role} · ${currentContact.status === 'online' ? 'En ligne' : 'Hors ligne'}</small>
                        </div>
                    </div>
                    <div>
                        <div class="btn-group">
                            <button type="button" class="btn btn-outline-secondary btn-sm" title="Appel vidéo">
                                <i class="bi bi-camera-video"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" title="Appel audio">
                                <i class="bi bi-telephone"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" title="Informations">
                                <i class="bi bi-info-circle"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Corps de la conversation
        conversationHtml += `<div class="card-body p-4 conversation-body" id="conversation-messages">`;
        
        if (messages.length === 0) {
            conversationHtml += `
                <div class="text-center py-4">
                    <div class="empty-state">
                        <i class="bi bi-chat text-muted h1 mb-3"></i>
                        <h5>Aucun message</h5>
                        <p class="text-muted">Démarrez la conversation en envoyant un message.</p>
                    </div>
                </div>
            `;
        } else {
            lastMessageDate = null;
            messages.forEach(message => {
                const messageDate = message.date;
                const showDateSeparator = lastMessageDate !== messageDate;
                lastMessageDate = messageDate;
                
                if (showDateSeparator) {
                    conversationHtml += `
                        <div class="message-date-separator">
                            <span>${message.date_text}</span>
                        </div>
                    `;
                }
                
                conversationHtml += `
                    <div class="message ${message.is_outgoing ? 'outgoing' : 'incoming'}" id="message-${message.id}">
                        ${!message.is_outgoing ? `
                            <div class="message-avatar">
                                <img src="${currentContact.avatar}" alt="Avatar" class="rounded-circle" width="36" height="36">
                            </div>
                        ` : ''}
                        <div class="message-content">
                            <div class="message-bubble">
                                ${message.content.replace(/\n/g, '<br>')}
                            </div>
                            <div class="message-info">
                                <small class="text-muted">
                                    ${message.time}
                                    ${message.is_outgoing ? `
                                        <i class="bi bi-check2-all ms-1 ${message.read ? 'text-primary' : ''}"></i>
                                    ` : ''}
                                </small>
                            </div>
                        </div>
                    </div>
                `;
            });
        }
        
        conversationHtml += `</div>`;
        
        // Formulaire d'envoi de message
        conversationHtml += `
            <div class="card-footer bg-white">
                <form id="message-form">
                    <input type="hidden" id="recipient-id" value="${currentContact.id}">
                    <input type="hidden" id="recipient-type" value="${currentContact.type}">
                    
                    <div class="input-group">
                        <button type="button" class="btn btn-outline-secondary" id="attachment-btn">
                            <i class="bi bi-paperclip"></i>
                        </button>
                        <textarea class="form-control" id="message-input" placeholder="Votre message..." rows="1" required></textarea>
                        <button type="submit" class="btn btn-primary" id="send-btn">
                            <i class="bi bi-send"></i>
                        </button>
                    </div>
                    
                    <div class="message-actions mt-2 d-none" id="message-attachments">
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-image me-1"></i>Image
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-file-earmark me-1"></i>Document
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-geo-alt me-1"></i>Localisation
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-calendar-event me-1"></i>Réunion
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        `;
        
        // Mettre à jour le conteneur
        conversationContainer.innerHTML = conversationHtml;
        
        // Faire défiler vers le bas
        const conversationMessages = document.getElementById('conversation-messages');
        if (conversationMessages) {
            conversationMessages.scrollTop = conversationMessages.scrollHeight;
        }
        
        // Gérer l'ajustement automatique de la hauteur du textarea
        const messageInput = document.getElementById('message-input');
        if (messageInput) {
            messageInput.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
        }
        
        // Gérer le bouton de pièce jointe
        const attachmentBtn = document.getElementById('attachment-btn');
        const messageAttachments = document.getElementById('message-attachments');
        if (attachmentBtn && messageAttachments) {
            attachmentBtn.addEventListener('click', function() {
                messageAttachments.classList.toggle('d-none');
            });
        }
        
        // Gérer l'envoi du message
        const messageForm = document.getElementById('message-form');
        if (messageForm) {
            messageForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const recipientId = document.getElementById('recipient-id').value;
                const recipientType = document.getElementById('recipient-type').value;
                const content = messageInput.value.trim();
                
                if (content) {
                    sendMessage(recipientId, recipientType, content);
                    messageInput.value = '';
                    messageInput.style.height = 'auto';
                }
            });
        }
    }
    
    // Envoyer un message
    function sendMessage(recipientId, recipientType, content) {
        // Désactiver le bouton d'envoi
        const sendBtn = document.getElementById('send-btn');
        if (sendBtn) {
            sendBtn.disabled = true;
            sendBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
        }
        
        // Données à envoyer
        const messageData = {
            recipient_id: recipientId,
            recipient_type: recipientType,
            content: content
        };
        
        // Envoyer la requête
        fetch('/tutoring/api/messages/send.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(messageData)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur lors de l\'envoi du message');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Ajouter le message à la liste
                if (data.data) {
                    messages.push(data.data);
                    
                    // Mettre à jour l'interface
                    renderConversation();
                } else {
                    // Recharger la conversation si le message n'est pas retourné
                    loadConversation(recipientId, recipientType);
                }
                
                // Mettre à jour le dernier message dans la liste des contacts
                const contactIndex = contacts.findIndex(c => c.id == recipientId && c.type === recipientType);
                if (contactIndex !== -1) {
                    contacts[contactIndex].last_message = content.length > 50 ? content.substring(0, 50) + '...' : content;
                    contacts[contactIndex].last_message_time = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                    renderContacts();
                }
            } else {
                alert('Erreur lors de l\'envoi du message');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors de l\'envoi du message: ' + error.message);
        })
        .finally(() => {
            // Réactiver le bouton d'envoi
            if (sendBtn) {
                sendBtn.disabled = false;
                sendBtn.innerHTML = '<i class="bi bi-send"></i>';
            }
        });
    }
    
    // Remplir le select des destinataires
    function populateRecipientSelect() {
        recipientSelect.innerHTML = '<option value="" disabled selected>Sélectionner un destinataire...</option>';
        
        contacts.forEach(contact => {
            const option = document.createElement('option');
            option.value = contact.id;
            option.textContent = `${contact.name} (${contact.role})`;
            option.dataset.type = contact.type;
            recipientSelect.appendChild(option);
        });
    }
    
    // Remplir la liste des membres du groupe
    function populateGroupMembers() {
        groupMembersContainer.innerHTML = '';
        
        contacts.forEach(contact => {
            const div = document.createElement('div');
            div.className = 'form-check';
            div.innerHTML = `
                <input class="form-check-input" type="checkbox" value="${contact.id}" id="member-${contact.id}" data-type="${contact.type}">
                <label class="form-check-label" for="member-${contact.id}">
                    ${contact.name} (${contact.role})
                </label>
            `;
            groupMembersContainer.appendChild(div);
        });
    }
    
    // Gérer la recherche de contacts
    if (contactSearch) {
        contactSearch.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            
            document.querySelectorAll('.contact-item').forEach(item => {
                const contactName = item.querySelector('h6').textContent.toLowerCase();
                if (contactName.includes(searchTerm)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }
    
    // Gérer l'envoi d'un nouveau message depuis le modal
    if (sendNewMessageBtn) {
        sendNewMessageBtn.addEventListener('click', function() {
            if (recipientSelect.value && newMessageContent.value.trim()) {
                const recipientId = recipientSelect.value;
                const recipientType = recipientSelect.options[recipientSelect.selectedIndex].dataset.type;
                const content = newMessageContent.value.trim();
                
                // Fermer le modal
                const modal = bootstrap.Modal.getInstance(newMessageModal);
                modal.hide();
                
                // Sélectionner le contact
                const contact = contacts.find(c => c.id == recipientId && c.type === recipientType);
                if (contact) {
                    selectContact(contact);
                    
                    // Mettre à jour l'URL
                    const url = new URL(window.location);
                    url.searchParams.set('contact_id', recipientId);
                    url.searchParams.set('contact_type', recipientType);
                    window.history.pushState({}, '', url);
                    
                    // Envoyer le message après un court délai
                    setTimeout(() => {
                        sendMessage(recipientId, recipientType, content);
                    }, 500);
                }
                
                // Réinitialiser le formulaire
                newMessageContent.value = '';
            } else {
                alert('Veuillez remplir tous les champs');
            }
        });
    }
    
    // Gérer la création d'un groupe
    if (createGroupBtn) {
        createGroupBtn.addEventListener('click', function() {
            const groupName = document.getElementById('group-name').value;
            const memberCheckboxes = document.querySelectorAll('.group-members input[type="checkbox"]:checked');
            
            if (groupName && memberCheckboxes.length > 0) {
                alert('Fonctionnalité de création de groupe à implémenter');
                
                // Fermer le modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('newGroupModal'));
                modal.hide();
                
                // Réinitialiser le formulaire
                document.getElementById('group-name').value = '';
                document.querySelectorAll('.group-members input[type="checkbox"]').forEach(checkbox => {
                    checkbox.checked = false;
                });
            } else {
                alert('Veuillez remplir tous les champs requis');
            }
        });
    }
    
    // Charger les contacts au chargement de la page
    loadContacts();
    
    // Rafraîchir les contacts toutes les 30 secondes pour les mises à jour
    setInterval(loadContacts, 30000);
    
    // Rafraîchir la conversation active toutes les 10 secondes
    setInterval(() => {
        if (currentContact) {
            loadConversation(currentContact.id, currentContact.type);
        }
    }, 10000);
});
</script>

<?php
// Inclure le pied de page
include_once __DIR__ . '/../common/footer.php';
?>