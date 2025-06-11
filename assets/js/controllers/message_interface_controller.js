/**
 * Message Interface Controller
 * Manages the messaging interface, conversation selection, and message loading
 */
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
  static targets = [
    "conversationList", "messagesList", "composer", 
    "header", "emptyState", "conversationDetail",
    "contactName", "contactRole", "contactAvatar", "loadingIndicator"
  ];
  
  static values = {
    apiUrl: { type: String, default: "/tutoring/api/messages" },
    userId: String,
    refreshInterval: { type: Number, default: 30000 } // 30 seconds
  };
  
  connect() {
    // Get user ID from meta tag or session
    if (!this.userIdValue) {
      const userIdMeta = document.querySelector('meta[name="user-id"]');
      if (userIdMeta) {
        this.userIdValue = userIdMeta.content;
      }
    }
    
    // Listen for conversation selection events
    this.element.addEventListener("conversation:selected", this.handleConversationSelected.bind(this));
    
    // Listen for message sent events
    this.element.addEventListener("message:sent", this.handleMessageSent.bind(this));
    
    // Listen for conversation clicks
    this.element.querySelectorAll('.message-card').forEach(card => {
      card.addEventListener('click', (e) => {
        e.preventDefault();
        const conversationId = card.dataset.conversationId;
        const participantId = card.dataset.participantId;
        const participantRole = card.dataset.participantRole;
        
        if (conversationId && participantId) {
          this.selectConversation(conversationId, participantId, participantRole);
        }
      });
    });
    
    // Set up polling for new messages if refreshInterval is positive
    if (this.refreshIntervalValue > 0) {
      this.startMessagePolling();
    }
  }
  
  disconnect() {
    // Clean up interval when component is disconnected
    if (this.refreshTimer) {
      clearInterval(this.refreshTimer);
    }
  }
  
  startMessagePolling() {
    this.refreshTimer = setInterval(() => {
      if (this.currentConversationId && this.currentContactId) {
        this.loadMessages(this.currentConversationId, this.currentContactId, this.currentContactType);
      }
    }, this.refreshIntervalValue);
  }
  
  handleConversationSelected(event) {
    const { conversationId, contactId, contactType } = event.detail;
    this.selectConversation(conversationId, contactId, contactType);
  }
  
  selectConversation(conversationId, contactId, contactType) {
    this.currentConversationId = conversationId;
    this.currentContactId = contactId;
    this.currentContactType = contactType || 'user';
    
    // Update active state in conversation list
    this.element.querySelectorAll('.message-card').forEach(card => {
      card.classList.remove('active');
      if (card.dataset.conversationId === conversationId) {
        card.classList.add('active');
      }
    });
    
    // Show the conversation detail and hide empty state
    const placeholder = document.getElementById('conversation-placeholder');
    const content = document.getElementById('conversation-content');
    const replySection = document.getElementById('reply-section');
    const replyButton = document.getElementById('reply-button');
    const deleteButton = document.getElementById('delete-button');
    
    if (placeholder) placeholder.style.display = 'none';
    if (content) content.style.display = 'block';
    if (replySection) replySection.style.display = 'block';
    if (replyButton) replyButton.style.display = 'inline-flex';
    if (deleteButton) deleteButton.style.display = 'inline-flex';
    
    // Update reply form with recipient ID
    const replyReceiverId = document.getElementById('reply-receiver-id');
    const replyConversationId = document.getElementById('reply-conversation-id');
    const replyRecipientType = document.getElementById('reply-recipient-type');
    
    if (replyReceiverId) replyReceiverId.value = contactId;
    if (replyConversationId) replyConversationId.value = conversationId;
    if (replyRecipientType) replyRecipientType.value = contactType;
    
    // Load messages for this conversation
    this.loadMessages(conversationId, contactId, contactType);
  }
  
  handleMessageSent(event) {
    // After sending a message, reload the conversation to show the new message
    if (this.currentConversationId && this.currentContactId) {
      this.loadMessages(this.currentConversationId, this.currentContactId, this.currentContactType);
    }
  }
  
  loadMessages(conversationId, contactId, contactType) {
    // Show loading indicator
    if (this.hasLoadingIndicatorTarget) {
      this.loadingIndicatorTarget.classList.remove("hidden");
    }
    
    // Determine the actual API endpoint based on the contact type
    const apiEndpoint = `${this.apiUrlValue}/conversation.php?contact_id=${contactId}&contact_type=${contactType || 'user'}`;
    
    // Fetch messages for the selected conversation
    fetch(apiEndpoint, {
      method: 'GET',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      },
      credentials: 'same-origin'
    })
    .then(response => {
      if (!response.ok) {
        throw new Error(`HTTP error! Status: ${response.status}`);
      }
      return response.json();
    })
    .then(data => {
      if (data.contact) {
        this.updateConversationHeader(data.contact);
      }
      if (data.messages) {
        this.displayMessages(data.messages, conversationId);
      }
      
      // Mark messages as read
      this.markConversationAsRead(conversationId);
    })
    .catch(error => {
      console.error("Error loading messages:", error);
      this.showError("Erreur lors du chargement des messages");
    })
    .finally(() => {
      // Hide loading indicator
      if (this.hasLoadingIndicatorTarget) {
        this.loadingIndicatorTarget.classList.add("hidden");
      }
    });
  }
  
  displayMessages(messages, conversationId) {
    const messagesList = document.getElementById('message-list');
    if (!messagesList) return;
    
    // Clear existing messages
    messagesList.innerHTML = "";
    
    // Check if there are no messages
    if (!messages || messages.length === 0) {
      messagesList.innerHTML = `
        <div class="text-center py-8">
          <i class="bi bi-chat-text text-gray-300 display-4"></i>
          <p class="text-gray-500 mt-3">Aucun message dans cette conversation</p>
        </div>
      `;
      return;
    }
    
    // Sort messages by date (oldest first)
    messages.sort((a, b) => new Date(a.sent_at) - new Date(b.sent_at));
    
    // Group messages by date
    const messagesByDate = messages.reduce((groups, message) => {
      const date = new Date(message.sent_at).toLocaleDateString('fr-FR');
      if (!groups[date]) {
        groups[date] = [];
      }
      groups[date].push(message);
      return groups;
    }, {});
    
    // Create elements for each date group
    Object.entries(messagesByDate).forEach(([date, dateMessages]) => {
      // Add date separator
      const dateSeparator = document.createElement('div');
      dateSeparator.className = 'text-center my-4';
      dateSeparator.innerHTML = `
        <span class="px-3 py-1 bg-gray-100 text-gray-500 text-xs font-medium rounded-full">
          ${this.formatMessageDate(date)}
        </span>
      `;
      messagesList.appendChild(dateSeparator);
      
      // Add messages for this date
      dateMessages.forEach(message => {
        const isOutgoing = message.is_outgoing || (message.sender_id == this.userIdValue);
        const messageElement = document.createElement('div');
        messageElement.className = `message mb-4 ${isOutgoing ? 'text-right' : 'text-left'}`;
        
        const messageTime = message.time || new Date(message.sent_at).toLocaleTimeString('fr-FR', { 
          hour: '2-digit', 
          minute: '2-digit' 
        });
        
        const senderName = isOutgoing ? 'Vous' : 
          (message.sender_first_name && message.sender_last_name ? 
            `${message.sender_first_name} ${message.sender_last_name}` : 
            'Utilisateur');
        
        const avatar = message.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(senderName)}&background=${isOutgoing ? '4f46e5' : '3498db'}&color=fff`;
        
        messageElement.innerHTML = `
          <div class="inline-block max-w-sm lg:max-w-md">
            ${!isOutgoing ? `<p class="text-xs text-gray-500 mb-1">${this.escapeHtml(senderName)}</p>` : ''}
            <div class="inline-block px-4 py-3 rounded-lg ${isOutgoing ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-800'}">
              <p class="text-sm whitespace-pre-wrap break-words">${this.escapeHtml(message.content)}</p>
            </div>
            <p class="text-xs text-gray-500 mt-1">
              ${messageTime}
              ${isOutgoing && message.is_read ? ' <i class="bi bi-check-all text-blue-500"></i>' : ''}
              ${isOutgoing && !message.is_read ? ' <i class="bi bi-check"></i>' : ''}
            </p>
          </div>
        `;
        
        messagesList.appendChild(messageElement);
      });
    });
    
    // Scroll to the bottom of the messages list
    messagesList.scrollTop = messagesList.scrollHeight;
  }
  
  formatMessageDate(dateStr) {
    const date = new Date(dateStr);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    const yesterday = new Date(today);
    yesterday.setDate(today.getDate() - 1);
    
    if (date.toDateString() === today.toDateString()) {
      return "Aujourd'hui";
    } else if (date.toDateString() === yesterday.toDateString()) {
      return "Hier";
    } else {
      return date.toLocaleDateString('fr-FR', { 
        weekday: 'long', 
        day: 'numeric', 
        month: 'long' 
      });
    }
  }
  
  updateConversationHeader(contact) {
    // Update contact name
    const contactName = document.getElementById('conversation-participant');
    if (contactName) {
      contactName.textContent = contact.name || 'Utilisateur';
    }
    
    // Update contact role/status
    const contactRole = document.getElementById('conversation-participant-role');
    if (contactRole) {
      contactRole.textContent = contact.role || '';
    }
    
    // Update contact avatar
    const contactAvatar = document.getElementById('conversation-avatar');
    if (contactAvatar) {
      contactAvatar.src = contact.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(contact.name || 'U')}&background=4f46e5&color=fff`;
    }
  }
  
  markConversationAsRead(conversationId) {
    // Send API request to mark all messages in this conversation as read
    fetch(`${this.apiUrlValue}/mark-read.php`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({ conversation_id: conversationId }),
      credentials: 'same-origin'
    })
    .then(response => {
      if (!response.ok) {
        throw new Error(`HTTP error! Status: ${response.status}`);
      }
      return response.json();
    })
    .then(data => {
      if (data.success) {
        // Remove unread badge from the conversation in the list
        const conversationElement = this.element.querySelector(`[data-conversation-id="${conversationId}"]`);
        if (conversationElement) {
          const unreadBadge = conversationElement.querySelector('.badge');
          if (unreadBadge) {
            unreadBadge.remove();
          }
          conversationElement.classList.remove('unread');
        }
      }
    })
    .catch(error => {
      console.error("Error marking conversation as read:", error);
    });
  }
  
  showError(message) {
    // You can implement a toast notification here
    console.error(message);
  }
  
  escapeHtml(text) {
    const map = {
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
  }
}