<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-chat-dots me-2"></i>Messagerie</h2>
            <p class="text-muted">Gérez vos conversations avec les étudiants, tuteurs et autres utilisateurs</p>
        </div>
    </div>
    
    <!-- Définir l'ID de l'utilisateur pour le JavaScript -->
    <meta name="user-id" content="<?php echo $_SESSION['user_id']; ?>">
    
    <!-- Main Content Row -->
    <div class="row">
        <!-- Liste des conversations -->
        <div class="col-lg-4">
            <div class="card mb-4 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Conversations</span>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newMessageModal">
                        <i class="bi bi-pencil-square me-1"></i>Nouveau
                    </button>
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
                    
                    <?php if (empty($contacts)): ?>
                    <div class="alert alert-info m-3">
                        <i class="bi bi-info-circle me-2"></i> Aucun contact disponible. Ajoutez des utilisateurs au système pour démarrer des conversations.
                    </div>
                    <?php else: ?>
                    <div class="list-group list-group-flush contact-list">
                        <?php foreach ($contacts as $contact): ?>
                        <li class="list-group-item contact-item message-card <?php echo ($currentContact && $currentContact['id'] == $contact['id'] && $currentContact['type'] == $contact['type']) ? 'active' : ''; ?>" 
                             data-conversation-id="<?php echo 'conv_' . $_SESSION['user_id'] . '_' . h($contact['id']); ?>"
                             data-participant-id="<?php echo h($contact['id']); ?>"
                             data-participant-role="<?php echo h($contact['role']); ?>">
                            <a href="#" class="d-flex align-items-center text-decoration-none text-dark">
                                <div class="position-relative me-3">
                                    <img src="<?php echo h($contact['avatar']); ?>" alt="<?php echo h($contact['name']); ?>" class="message-avatar rounded-circle" width="48" height="48">
                                    <span class="position-absolute bottom-0 end-0 badge rounded-pill <?php echo $contact['status'] === 'online' ? 'bg-success' : 'bg-secondary'; ?>">
                                        <span class="visually-hidden"><?php echo h($contact['status']); ?></span>
                                    </span>
                                </div>
                                <div class="flex-grow-1 min-width-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0 text-truncate"><?php echo h($contact['name']); ?></h6>
                                        <small class="text-muted ms-2">
                                            <?php echo isset($contact['last_message_time']) ? $contact['last_message_time'] : ''; ?>
                                        </small>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <p class="mb-0 small text-truncate text-muted"><?php echo h($contact['last_message']); ?></p>
                                        <?php if (isset($contact['unread_count']) && $contact['unread_count'] > 0): ?>
                                        <span class="badge bg-primary rounded-pill ms-2"><?php echo $contact['unread_count']; ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-muted"><?php echo h($contact['role']); ?></small>
                                </div>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Zone de conversation -->
        <div class="col-lg-8">
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0" id="conversation-title">Messages</h5>
                    <div>
                        <button class="btn btn-sm btn-outline-secondary me-1" id="reply-button" style="display: none;">
                            <i class="bi bi-reply"></i> Répondre
                        </button>
                        <button class="btn btn-sm btn-outline-danger" id="delete-button" style="display: none;">
                            <i class="bi bi-trash"></i> Supprimer
                        </button>
                    </div>
                </div>
                
                <div id="conversation-placeholder" class="card-body text-center py-5">
                    <div class="empty-state">
                        <i class="bi bi-chat-text text-muted display-1 mb-3"></i>
                        <h4>Sélectionnez un contact pour démarrer une conversation</h4>
                        <p class="text-muted">Choisissez un contact dans la liste ou démarrez une nouvelle conversation.</p>
                        <button class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#newMessageModal">
                            <i class="bi bi-plus-lg me-2"></i>Nouveau message
                        </button>
                    </div>
                </div>
                
                <div id="conversation-content" style="display: none;">
                    <div class="card-body pb-0">
                        <div class="d-flex align-items-center mb-4" id="conversation-header">
                            <img src="" alt="Avatar" class="rounded-circle me-3" id="conversation-avatar" width="48" height="48">
                            <div>
                                <h5 class="mb-0" id="conversation-participant"></h5>
                                <small class="text-muted" id="conversation-participant-role"></small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body conversation-body pt-0" id="conversation-messages">
                        <!-- Les messages seront chargés dynamiquement ici -->
                    </div>
                    
                    <div class="card-footer bg-white">
                        <div class="reply-section" id="reply-section" style="display: none;">
                            <form id="reply-form" method="POST" action="">
                                <input type="hidden" name="recipient_id" id="reply-receiver-id">
                                <input type="hidden" name="recipient_type" id="reply-recipient-type" value="student">
                                <input type="hidden" name="conversation_id" id="reply-conversation-id">
                                
                                <div class="input-group">
                                    <button type="button" class="btn btn-outline-secondary" id="attachment-btn">
                                        <i class="bi bi-paperclip"></i>
                                    </button>
                                    <textarea class="form-control" name="message_content" id="message-input" placeholder="Votre message..." rows="1" required></textarea>
                                    <button type="submit" name="send_message" class="btn btn-primary">
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
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- New Message Modal -->
<div class="modal fade" id="newMessageModal" tabindex="-1" aria-labelledby="newMessageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newMessageModalLabel">Nouveau message</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Destinataire</label>
                        <select class="form-select" name="recipient_id" required>
                            <option value="">Sélectionner un destinataire...</option>
                            <?php foreach ($contacts as $contact): ?>
                            <option value="<?php echo $contact['id']; ?>" data-type="<?php echo h($contact['type']); ?>">
                                <?php echo h($contact['name']) . ' (' . h($contact['role']) . ')'; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sujet</label>
                        <input type="text" class="form-control" name="subject" placeholder="Objet du message" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Message</label>
                        <textarea class="form-control" name="message_content" rows="8" placeholder="Écrivez votre message ici..." required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Pièces jointes (optionnel)</label>
                        <input type="file" class="form-control" name="attachment" multiple>
                        <div class="form-text">Max. 5 Mo par fichier</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" name="send_message" class="btn btn-primary">Envoyer le message</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Message Confirmation Modal -->
<div class="modal fade" id="deleteMessageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer cette conversation ? Cette action est irréversible.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form action="" method="POST" id="deleteMessageForm">
                    <input type="hidden" name="conversation_id" id="conversation_id_to_delete">
                    <button type="submit" name="delete_conversation" class="btn btn-danger">Supprimer</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Définir l'ID de l'utilisateur pour le script commun
    window.currentUserId = <?php echo $_SESSION['user_id']; ?>;
</script>
<script src="/tutoring/assets/js/messages.js"></script>
<script src="/tutoring/views/admin/message-handler.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Gérer le bouton de pièce jointe
        const attachmentBtn = document.getElementById('attachment-btn');
        const messageAttachments = document.getElementById('message-attachments');
        if (attachmentBtn && messageAttachments) {
            attachmentBtn.addEventListener('click', function() {
                messageAttachments.classList.toggle('d-none');
            });
        }
        
        // Recherche de contacts
        const contactSearch = document.getElementById('contact-search');
        if (contactSearch) {
            contactSearch.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const contactItems = document.querySelectorAll('.contact-item');
                
                contactItems.forEach(item => {
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
        const newMessageForm = document.querySelector('#newMessageModal form');
        if (newMessageForm) {
            newMessageForm.addEventListener('submit', function(e) {
                const recipientSelect = this.querySelector('select[name="recipient_id"]');
                const recipientId = recipientSelect.value;
                const recipientType = recipientSelect.options[recipientSelect.selectedIndex].dataset.type;
                
                // Ajouter le type de destinataire si ce n'est pas déjà fait
                if (!this.querySelector('input[name="recipient_type"]')) {
                    const hiddenField = document.createElement('input');
                    hiddenField.type = 'hidden';
                    hiddenField.name = 'recipient_type';
                    hiddenField.value = recipientType;
                    this.appendChild(hiddenField);
                }
            });
        }
    });
</script>

<style>
/* Styles pour la messagerie */
.contact-list {
    max-height: calc(100vh - 300px);
    overflow-y: auto;
}

.contact-item {
    padding: 10px 15px;
    transition: background-color 0.2s;
    cursor: pointer;
    border-left: 3px solid transparent;
}

.contact-item:hover {
    background-color: rgba(0, 0, 0, 0.05);
}

.contact-item.active {
    background-color: rgba(13, 110, 253, 0.1);
    border-left-color: #0d6efd;
}

.conversation-body {
    height: 400px;
    max-height: calc(100vh - 400px);
    overflow-y: auto;
    display: flex;
    flex-direction: column;
}

.message {
    display: flex;
    margin-bottom: 10px;
    max-width: 80%;
}

.message.message-sent {
    align-self: flex-end;
}

.message.message-received {
    align-self: flex-start;
}

.message-bubble {
    padding: 12px 15px;
    border-radius: 18px;
    position: relative;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.message-sent .message-bubble {
    background-color: #0d6efd;
    color: white;
    border-bottom-right-radius: 4px;
}

.message-received .message-bubble {
    background-color: #f0f2f5;
    border-bottom-left-radius: 4px;
}

.message-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 5px;
    align-items: center;
}

.message-time {
    font-size: 0.7rem;
    opacity: 0.8;
}

.message-avatar-small {
    width: 32px;
    height: 32px;
    border-radius: 50%;
}

.message-date-separator {
    position: relative;
    text-align: center;
    margin: 1.5rem 0;
}

.message-date-separator::before {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    width: 100%;
    height: 1px;
    background-color: #e9ecef;
}

.message-date-separator span {
    position: relative;
    background-color: #fff;
    padding: 0 1rem;
    font-size: 0.75rem;
    color: #6c757d;
}

#message-input {
    resize: none;
    overflow: hidden;
    min-height: 38px;
    max-height: 120px;
}

.empty-state {
    padding: 2rem;
    text-align: center;
    color: #6c757d;
}

.empty-state i {
    margin-bottom: 1rem;
    opacity: 0.5;
}
</style>