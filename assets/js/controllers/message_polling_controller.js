/**
 * Message Polling Controller
 * Provides real-time updates for messaging functionality through polling
 */
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
  static targets = ["unreadBadge", "notificationBell", "unreadIndicator"];
  
  static values = {
    apiUrl: { type: String, default: "/tutoring/api/messages" },
    userId: String,
    pollInterval: { type: Number, default: 10000 }, // 10 seconds
    unreadCount: { type: Number, default: 0 }
  };
  
  connect() {
    // Start polling when the controller connects
    this.startPolling();
    
    // Initialize unread count
    this.fetchUnreadCount();
  }
  
  disconnect() {
    // Clean up polling when the controller disconnects
    this.stopPolling();
  }
  
  startPolling() {
    // Clear any existing polling interval
    this.stopPolling();
    
    // Set up a new polling interval
    this.pollingTimer = setInterval(() => {
      this.fetchUnreadCount();
    }, this.pollIntervalValue);
  }
  
  stopPolling() {
    if (this.pollingTimer) {
      clearInterval(this.pollingTimer);
      this.pollingTimer = null;
    }
  }
  
  fetchUnreadCount() {
    // Fetch unread message count from API
    fetch(`${this.apiUrlValue}/conversations.php?unread=true`)
      .then(response => {
        if (!response.ok) {
          throw new Error(`HTTP error! Status: ${response.status}`);
        }
        return response.json();
      })
      .then(data => {
        // Calculate total unread messages across all conversations
        let totalUnread = 0;
        
        if (data.data && Array.isArray(data.data)) {
          data.data.forEach(conversation => {
            if (conversation.unread_count) {
              totalUnread += parseInt(conversation.unread_count, 10);
            }
          });
        }
        
        // Update the unread count value
        this.unreadCountValue = totalUnread;
        
        // Update the UI with the new unread count
        this.updateUnreadIndicators();
      })
      .catch(error => {
        console.error("Error fetching unread messages:", error);
      });
  }
  
  updateUnreadIndicators() {
    const unreadCount = this.unreadCountValue;
    
    // Update unread badges if they exist
    if (this.hasUnreadBadgeTarget) {
      this.unreadBadgeTargets.forEach(badge => {
        if (unreadCount > 0) {
          badge.textContent = unreadCount > 99 ? '99+' : unreadCount;
          badge.classList.remove('hidden');
        } else {
          badge.classList.add('hidden');
        }
      });
    }
    
    // Update notification bell if it exists
    if (this.hasNotificationBellTarget) {
      if (unreadCount > 0) {
        this.notificationBellTarget.classList.add('animate-bounce');
      } else {
        this.notificationBellTarget.classList.remove('animate-bounce');
      }
    }
    
    // Update unread indicator dots if they exist
    if (this.hasUnreadIndicatorTarget) {
      if (unreadCount > 0) {
        this.unreadIndicatorTargets.forEach(indicator => {
          indicator.classList.remove('hidden');
        });
      } else {
        this.unreadIndicatorTargets.forEach(indicator => {
          indicator.classList.add('hidden');
        });
      }
    }
    
    // Update page title with unread count
    if (unreadCount > 0) {
      document.title = `(${unreadCount}) Messagerie | Tutoring`;
    } else {
      document.title = 'Messagerie | Tutoring';
    }
    
    // Dispatch a custom event that other controllers can listen for
    const event = new CustomEvent('messages:unread-updated', {
      detail: { count: unreadCount },
      bubbles: true
    });
    this.element.dispatchEvent(event);
  }
  
  // Manual refresh button handler
  refreshMessages() {
    this.fetchUnreadCount();
  }
}