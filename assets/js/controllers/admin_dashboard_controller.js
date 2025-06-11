/**
 * Admin Dashboard Controller
 * Handles admin-specific dashboard functionality and data refreshing
 */
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
  static targets = [
    "statCard", 
    "feedItem", 
    "notificationBadge", 
    "assignmentStatusChart",
    "tutorWorkloadChart", 
    "internshipTypeChart",
    "documentSubmissionChart"
  ];
  
  static values = {
    refreshInterval: { type: Number, default: 60000 }, // Default: 1 minute
    apiEndpoint: String
  }
  
  connect() {
    // Initialize all charts
    this.initializeCharts();
    
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
  
  initializeCharts() {
    // Initialize charts if Chart.js is loaded and chart targets exist
    if (typeof Chart === 'undefined') {
      console.error('Chart.js is not loaded');
      return;
    }
    
    // Assignment Status Chart
    if (this.hasAssignmentStatusChartTarget) {
      const chartData = JSON.parse(this.assignmentStatusChartTarget.dataset.chartData || '{}');
      if (chartData.labels && chartData.data) {
        this.assignmentStatusChart = new Chart(this.assignmentStatusChartTarget, {
          type: 'doughnut',
          data: {
            labels: chartData.labels,
            datasets: [{
              data: chartData.data,
              backgroundColor: [
                'rgba(59, 130, 246, 0.8)',  // blue
                'rgba(16, 185, 129, 0.8)',  // green
                'rgba(245, 158, 11, 0.8)',  // yellow
                'rgba(239, 68, 68, 0.8)'    // red
              ],
              borderWidth: 0
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                position: 'right'
              }
            }
          }
        });
      }
    }
    
    // Tutor Workload Chart
    if (this.hasTutorWorkloadChartTarget) {
      const chartData = JSON.parse(this.tutorWorkloadChartTarget.dataset.chartData || '{}');
      if (chartData.labels && chartData.data) {
        this.tutorWorkloadChart = new Chart(this.tutorWorkloadChartTarget, {
          type: 'bar',
          data: {
            labels: chartData.labels,
            datasets: [{
              label: 'Étudiants affectés',
              data: chartData.data,
              backgroundColor: 'rgba(99, 102, 241, 0.8)',
              borderWidth: 0
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
              y: {
                beginAtZero: true,
                ticks: {
                  precision: 0
                }
              }
            }
          }
        });
      }
    }
    
    // Internship Type Chart
    if (this.hasInternshipTypeChartTarget) {
      const chartData = JSON.parse(this.internshipTypeChartTarget.dataset.chartData || '{}');
      if (chartData.labels && chartData.data) {
        this.internshipTypeChart = new Chart(this.internshipTypeChartTarget, {
          type: 'pie',
          data: {
            labels: chartData.labels,
            datasets: [{
              data: chartData.data,
              backgroundColor: [
                'rgba(59, 130, 246, 0.8)',  // blue
                'rgba(139, 92, 246, 0.8)',  // purple
                'rgba(16, 185, 129, 0.8)',  // green
                'rgba(245, 158, 11, 0.8)'   // yellow
              ],
              borderWidth: 0
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                position: 'right'
              }
            }
          }
        });
      }
    }
    
    // Document Submission Chart
    if (this.hasDocumentSubmissionChartTarget) {
      const chartData = JSON.parse(this.documentSubmissionChartTarget.dataset.chartData || '{}');
      if (chartData.labels && chartData.data) {
        this.documentSubmissionChart = new Chart(this.documentSubmissionChartTarget, {
          type: 'bar',
          data: {
            labels: chartData.labels,
            datasets: [{
              label: 'Documents soumis',
              data: chartData.data,
              backgroundColor: 'rgba(16, 185, 129, 0.8)',  // green
              borderWidth: 0
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
              y: {
                beginAtZero: true,
                ticks: {
                  precision: 0
                }
              }
            }
          }
        });
      }
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
    
    // Update notification count
    if (data.notifications) {
      this.updateNotifications(data.notifications);
    }
    
    // Update charts
    if (data.chartData) {
      this.updateCharts(data.chartData);
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
    li.className = 'relative';
    li.dataset.feedItemTarget = 'item';
    
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
  
  updateCharts(chartData) {
    // Update Assignment Status Chart
    if (this.assignmentStatusChart && chartData.assignmentStatus) {
      this.assignmentStatusChart.data.labels = chartData.assignmentStatus.labels;
      this.assignmentStatusChart.data.datasets[0].data = chartData.assignmentStatus.data;
      this.assignmentStatusChart.update();
    }
    
    // Update Tutor Workload Chart
    if (this.tutorWorkloadChart && chartData.tutorWorkload) {
      this.tutorWorkloadChart.data.labels = chartData.tutorWorkload.labels;
      this.tutorWorkloadChart.data.datasets[0].data = chartData.tutorWorkload.data;
      this.tutorWorkloadChart.update();
    }
    
    // Update Internship Type Chart
    if (this.internshipTypeChart && chartData.internshipTypes) {
      this.internshipTypeChart.data.labels = chartData.internshipTypes.labels;
      this.internshipTypeChart.data.datasets[0].data = chartData.internshipTypes.data;
      this.internshipTypeChart.update();
    }
    
    // Update Document Submission Chart
    if (this.documentSubmissionChart && chartData.documentSubmissions) {
      this.documentSubmissionChart.data.labels = chartData.documentSubmissions.labels;
      this.documentSubmissionChart.data.datasets[0].data = chartData.documentSubmissions.data;
      this.documentSubmissionChart.update();
    }
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