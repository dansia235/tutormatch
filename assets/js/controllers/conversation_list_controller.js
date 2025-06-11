/**
 * Conversation List Controller
 * Handles conversation selection, search, and filtering
 */
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
  static targets = ["list", "conversation", "search", "empty"];
  
  static values = {
    activeConversationId: String,
    apiUrl: { type: String, default: "/tutoring/api/messages/conversations.php" },
    refreshInterval: { type: Number, default: 30000 } // 30 seconds
  };
  
  connect() {
    // Set up polling for new messages if refreshInterval is positive
    if (this.refreshIntervalValue > 0) {
      this.startRefreshing();
    }
    
    // Check if we have an active conversation on load
    if (this.hasActiveConversationIdValue) {
      this.selectConversationById(this.activeConversationIdValue);
    }
  }
  
  disconnect() {
    // Clean up interval when component is disconnected
    if (this.refreshTimer) {
      clearInterval(this.refreshTimer);
    }
  }
  
  startRefreshing() {
    this.refreshTimer = setInterval(() => {
      this.refreshConversations();
    }, this.refreshIntervalValue);
  }
  
  refreshConversations() {
    fetch(this.apiUrlValue)
      .then(response => response.json())
      .then(data => {
        if (data.data) {
          this.updateConversationsList(data.data);
        }
      })
      .catch(error => {
        console.error("Error refreshing conversations:", error);
      });
  }
  
  updateConversationsList(conversations) {
    // This method would update the conversation list with new data
    // Here we would need to update the DOM with the new conversation data
    // For now, let's just trigger a full page refresh if there are new messages
    // In a real implementation, we would update just the affected conversations
    
    const currentActive = this.hasActiveConversationIdValue ? this.activeConversationIdValue : null;
    
    // Check if there are any new messages in the conversations
    const hasNewMessages = conversations.some(conv => {
      // Logic to determine if there are new messages that the user hasn't seen
      return conv.unread_count > 0;
    });
    
    if (hasNewMessages) {
      // In a real implementation, we would update just the affected conversations
      // For now, we'll just reload the page
      window.location.reload();
    }
  }
  
  selectConversation(event) {
    const conversationElement = event.currentTarget;
    const conversationId = conversationElement.dataset.conversationId;
    
    // Update active conversation
    this.activeConversationIdValue = conversationId;
    
    // Update UI to show the selected conversation
    this.conversationTargets.forEach(conv => {
      conv.classList.remove("bg-indigo-50", "border-l-4", "border-indigo-500");
      
      if (conv.dataset.conversationId === conversationId) {
        conv.classList.add("bg-indigo-50", "border-l-4", "border-indigo-500");
      }
    });
    
    // Dispatch a custom event that the parent component can listen for
    const event = new CustomEvent("conversation:selected", {
      detail: { conversationId: conversationId },
      bubbles: true
    });
    this.element.dispatchEvent(event);
  }
  
  selectConversationById(conversationId) {
    const conversation = this.conversationTargets.find(
      c => c.dataset.conversationId === conversationId
    );
    
    if (conversation) {
      // Simulate a click on the conversation
      conversation.click();
    }
  }
  
  search() {
    const query = this.searchTarget.value.toLowerCase().trim();
    
    if (query === "") {
      // Show all conversations
      this.conversationTargets.forEach(conv => {
        conv.classList.remove("hidden");
      });
      
      // Hide the empty message if we have conversations
      if (this.conversationTargets.length > 0 && this.hasEmptyTarget) {
        this.emptyTarget.classList.add("hidden");
      }
      return;
    }
    
    let visibleCount = 0;
    
    // Filter conversations based on search query
    this.conversationTargets.forEach(conv => {
      const content = conv.textContent.toLowerCase();
      if (content.includes(query)) {
        conv.classList.remove("hidden");
        visibleCount++;
      } else {
        conv.classList.add("hidden");
      }
    });
    
    // Show/hide empty message based on results
    if (this.hasEmptyTarget) {
      if (visibleCount === 0) {
        this.emptyTarget.classList.remove("hidden");
      } else {
        this.emptyTarget.classList.add("hidden");
      }
    }
  }
  
  showNewMessageModal() {
    // Find the new message modal and show it
    const modal = document.getElementById('newMessageModal');
    if (modal) {
      const bsModal = new bootstrap.Modal(modal);
      bsModal.show();
    }
  }
}