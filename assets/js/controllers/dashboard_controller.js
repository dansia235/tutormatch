/**
 * Dashboard Controller
 * Handles dashboard-specific functionality and data refreshing
 */
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
  static targets = ["statCard", "feedItem", "meetingList", "notificationBadge"];
  
  static values = {
    refreshInterval: { type: Number, default: 60000 }, // Default: 1 minute
    apiEndpoint: String
  }
  
  connect() {
    // Initialize refresh timer if an interval is specified
    if (this.hasRefreshIntervalValue && this.refreshIntervalValue > 0) {
      this.startRefreshTimer();
    }
    
    // Initialize notification count
    this.updateNotificationCount();
  }
  
  disconnect() {
    // Clean up timer when controller is disconnected
    this.stopRefreshTimer();
  }
  
  startRefreshTimer() {
    this.refreshTimer = setInterval(() => {
      this.refreshDashboard();
    }, this.refreshIntervalValue);
  }
  
  stopRefreshTimer() {
    if (this.refreshTimer) {
      clearInterval(this.refreshTimer);
      this.refreshTimer = null;
    }
  }
  
  refreshDashboard() {
    // Only refresh if we have an API endpoint
    if (!this.hasApiEndpointValue) {
      return;
    }
    
    // Make API call to refresh dashboard data
    fetch(this.apiEndpointValue, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
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
      this.updateDashboard(data);
    })
    .catch(error => {
      console.error('Error refreshing dashboard:', error);
    });
  }
  
  updateDashboard(data) {
    // Update stat cards
    if (data.stats && this.hasStatCardTarget) {
      this.updateStatCards(data.stats);
    }
    
    // Update activity feed
    if (data.activities && this.hasFeedItemTarget) {
      this.updateActivityFeed(data.activities);
    }
    
    // Update meetings
    if (data.meetings && this.hasMeetingListTarget) {
      this.updateMeetings(data.meetings);
    }
    
    // Update notification count
    if (data.notifications) {
      this.updateNotifications(data.notifications);
    }
  }
  
  updateStatCards(stats) {
    this.statCardTargets.forEach((card, index) => {
      const cardData = stats[index];
      if (!cardData) return;
      
      const valueElement = card.querySelector('.stat-value');
      const changeElement = card.querySelector('.stat-change');
      
      if (valueElement && cardData.value) {
        valueElement.textContent = cardData.value;
      }
      
      if (changeElement && cardData.change) {
        changeElement.textContent = cardData.change;
        
        // Update change type classes
        changeElement.classList.remove('text-green-600', 'text-red-600', 'text-gray-500');
        
        if (cardData.changeType === 'positive') {
          changeElement.classList.add('text-green-600');
        } else if (cardData.changeType === 'negative') {
          changeElement.classList.add('text-red-600');
        } else {
          changeElement.classList.add('text-gray-500');
        }
      }
    });
  }
  
  updateActivityFeed(activities) {
    const feedContainer = this.feedItemTargets[0]?.parentElement;
    if (!feedContainer) return;
    
    // Only update if there are new activities
    if (activities.length > 0) {
      // Clear existing feed items
      feedContainer.innerHTML = '';
      
      // Add new feed items
      activities.forEach(activity => {
        const itemElement = this.createActivityElement(activity);
        feedContainer.appendChild(itemElement);
      });
    }
  }
  
  createActivityElement(activity) {
    const li = document.createElement('li');
    li.className = 'relative pb-6';
    li.innerHTML = `
      <div class="relative flex items-start space-x-3">
        <div class="relative">
          <span class="h-8 w-8 rounded-full flex items-center justify-center ${activity.iconBg || 'bg-indigo-500'}">
            ${activity.icon || '<svg class="h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>'}
          </span>
        </div>
        <div class="min-w-0 flex-1">
          <div>
            <div class="text-sm">
              <a href="${activity.url || '#'}" class="font-medium text-gray-900">${activity.title || 'Activité'}</a>
            </div>
            <p class="mt-0.5 text-sm text-gray-500">
              ${activity.description || ''}
            </p>
          </div>
          <div class="mt-2 text-sm text-gray-500">
            <p>${this.formatDate(activity.date) || ''}</p>
          </div>
        </div>
      </div>
    `;
    return li;
  }
  
  updateMeetings(meetings) {
    const meetingList = this.meetingListTarget;
    if (!meetingList) return;
    
    // Only update if there are meetings
    if (meetings.length > 0) {
      // Clear existing meetings
      meetingList.innerHTML = '';
      
      // Add new meetings
      meetings.forEach(meeting => {
        const meetingElement = this.createMeetingElement(meeting);
        meetingList.appendChild(meetingElement);
      });
    } else {
      meetingList.innerHTML = `
        <div class="text-center py-6">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          <p class="mt-2 text-gray-500">
            Aucune réunion planifiée.
          </p>
        </div>
      `;
    }
  }
  
  createMeetingElement(meeting) {
    const li = document.createElement('li');
    li.className = 'px-4 py-4 sm:px-6';
    
    // Format the meeting date
    const formattedDate = meeting.date ? this.formatDate(meeting.date) : '';
    
    li.innerHTML = `
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm font-medium text-indigo-600 truncate">${meeting.title || 'Réunion'}</p>
          <p class="mt-1 text-sm text-gray-500">
            ${meeting.type === 'online' ? 'En ligne' : (meeting.type === 'in_person' ? 'En personne' : 'Téléphone')}
            ${meeting.participants ? ` · ${meeting.participants.length} participant${meeting.participants.length > 1 ? 's' : ''}` : ''}
          </p>
        </div>
        <div class="ml-2 flex-shrink-0 flex">
          <p class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${meeting.status === 'completed' ? 'bg-green-100 text-green-800' : (meeting.status === 'cancelled' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800')}">
            ${meeting.status === 'completed' ? 'Terminée' : (meeting.status === 'cancelled' ? 'Annulée' : 'Planifiée')}
          </p>
        </div>
      </div>
      <div class="mt-2 sm:flex sm:justify-between">
        <div class="sm:flex">
          <p class="flex items-center text-sm text-gray-500">
            <svg class="flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
            </svg>
            ${formattedDate}
          </p>
        </div>
      </div>
    `;
    
    return li;
  }
  
  updateNotificationCount() {
    // Only update if we have a notification badge target
    if (!this.hasNotificationBadgeTarget) {
      return;
    }
    
    // Make API call to get notification count
    fetch('/tutoring/api/notifications/unread.php', {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
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
      const count = data.count || 0;
      this.notificationBadgeTargets.forEach(badge => {
        badge.textContent = count;
        badge.classList.toggle('hidden', count === 0);
      });
    })
    .catch(error => {
      console.error('Error fetching notification count:', error);
    });
  }
  
  updateNotifications(notifications) {
    // Update the notification count badge
    const count = notifications.length;
    if (this.hasNotificationBadgeTarget) {
      this.notificationBadgeTargets.forEach(badge => {
        badge.textContent = count;
        badge.classList.toggle('hidden', count === 0);
      });
    }
  }
  
  formatDate(dateString) {
    if (!dateString) return '';
    
    const date = new Date(dateString);
    const now = new Date();
    const yesterday = new Date();
    yesterday.setDate(yesterday.getDate() - 1);
    
    // Check if date is today
    if (date.toDateString() === now.toDateString()) {
      return `Aujourd'hui à ${date.getHours().toString().padStart(2, '0')}:${date.getMinutes().toString().padStart(2, '0')}`;
    }
    
    // Check if date is yesterday
    if (date.toDateString() === yesterday.toDateString()) {
      return `Hier à ${date.getHours().toString().padStart(2, '0')}:${date.getMinutes().toString().padStart(2, '0')}`;
    }
    
    // Return formatted date
    return date.toLocaleDateString('fr-FR', { 
      year: 'numeric', 
      month: 'short', 
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  }
}