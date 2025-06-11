/**
 * Admin Dashboard Controller with API Integration
 * Handles admin-specific dashboard functionality and data refreshing using the new APIs
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
    "documentSubmissionChart",
    "systemStatus",
    "systemMetric"
  ];
  
  static values = {
    refreshInterval: { type: Number, default: 60000 }, // Default: 1 minute
  }
  
  connect() {
    // Load initial dashboard data
    this.loadDashboardData();
    
    // Initialize refresh timer if an interval is specified
    if (this.hasRefreshIntervalValue && this.refreshIntervalValue > 0) {
      this.startRefreshTimer();
    }
  }
  
  disconnect() {
    // Clean up timer when controller is disconnected
    this.stopRefreshTimer();
  }
  
  startRefreshTimer() {
    this.refreshTimer = setInterval(() => {
      this.loadDashboardData();
    }, this.refreshIntervalValue);
  }
  
  stopRefreshTimer() {
    if (this.refreshTimer) {
      clearInterval(this.refreshTimer);
      this.refreshTimer = null;
    }
  }
  
  async loadDashboardData() {
    try {
      // Load dashboard stats
      const statsData = await window.api.dashboard.getStats();
      this.updateStatCards(statsData.stat_cards);
      
      // Load dashboard charts
      const chartsData = await window.api.dashboard.getCharts();
      this.initializeCharts(chartsData);
      
      // Load system status
      if (this.hasSystemStatusTarget) {
        const systemStatus = await window.api.dashboard.getSystemStatus();
        this.updateSystemStatus(systemStatus);
      }
      
      // Load activity feed
      const activityData = await window.api.dashboard.getActivity({limit: 5});
      this.updateActivityFeed(activityData.activities);
      
      // Load notification count
      const notificationsData = await window.api.notifications.getUnread();
      this.updateNotificationCount(notificationsData.count);
    } catch (error) {
      console.error('Error loading dashboard data:', error);
    }
  }
  
  updateStatCards(statCards) {
    if (!statCards || !this.hasStatCardTarget) {
      return;
    }
    
    this.statCardTargets.forEach((cardElement, index) => {
      const cardData = statCards[index];
      if (!cardData) return;
      
      const valueElement = cardElement.querySelector('.stat-value');
      const changeElement = cardElement.querySelector('.stat-change');
      
      if (valueElement) {
        valueElement.textContent = cardData.value;
      }
      
      if (changeElement && cardData.change) {
        changeElement.textContent = cardData.change;
        
        // Update change type classes
        changeElement.classList.remove('text-green-600', 'text-red-600', 'text-gray-500');
        
        switch (cardData.changeType) {
          case 'positive':
            changeElement.classList.add('text-green-600');
            break;
          case 'negative':
            changeElement.classList.add('text-red-600');
            break;
          case 'warning':
            changeElement.classList.add('text-yellow-600');
            break;
          case 'info':
            changeElement.classList.add('text-blue-600');
            break;
          default:
            changeElement.classList.add('text-gray-500');
        }
      }
    });
  }
  
  updateActivityFeed(activities) {
    if (!activities || !this.hasFeedItemTarget) {
      return;
    }
    
    const feedContainer = this.feedItemTargets[0]?.parentElement;
    if (!feedContainer) return;
    
    // Clear existing feed items
    feedContainer.innerHTML = '';
    
    // Add new feed items
    activities.forEach(activity => {
      const itemElement = this.createActivityElement(activity);
      feedContainer.appendChild(itemElement);
    });
  }
  
  createActivityElement(activity) {
    const li = document.createElement('li');
    li.className = 'relative';
    li.dataset.feedItemTarget = 'item';
    
    // Determine icon and background based on activity type
    let icon = '';
    let iconBg = '';
    
    switch (activity.activity_type) {
      case 'assignment':
        icon = '<i class="bi bi-diagram-3 text-white"></i>';
        iconBg = 'bg-blue-500';
        break;
      case 'document':
        icon = '<i class="bi bi-file-earmark-text text-white"></i>';
        iconBg = 'bg-green-500';
        break;
      case 'meeting':
        icon = '<i class="bi bi-calendar-event text-white"></i>';
        iconBg = 'bg-purple-500';
        break;
      case 'user':
        icon = '<i class="bi bi-person text-white"></i>';
        iconBg = 'bg-yellow-500';
        break;
      case 'internship':
        icon = '<i class="bi bi-briefcase text-white"></i>';
        iconBg = 'bg-red-500';
        break;
      default:
        icon = '<i class="bi bi-bell text-white"></i>';
        iconBg = 'bg-gray-500';
    }
    
    li.innerHTML = `
      <div class="relative flex items-start space-x-3">
        <div class="relative">
          <span class="h-8 w-8 rounded-full flex items-center justify-center ${iconBg}">
            ${icon}
          </span>
        </div>
        <div class="min-w-0 flex-1">
          <div>
            <div class="text-sm">
              <a href="${activity.activity_link || '#'}" class="font-medium text-gray-900">${activity.primary_subject || 'Activité'}</a>
            </div>
            <p class="mt-0.5 text-sm text-gray-500">
              ${activity.activity_description || ''} ${activity.secondary_subject ? `- ${activity.secondary_subject}` : ''}
            </p>
          </div>
          <div class="mt-2 text-sm text-gray-500">
            <p>${activity.relative_time || activity.formatted_date || ''}</p>
          </div>
        </div>
      </div>
    `;
    return li;
  }
  
  updateSystemStatus(statusData) {
    if (!statusData || !this.hasSystemStatusTarget) {
      return;
    }
    
    // Update service status indicators
    const services = statusData.services;
    const statusContainer = this.systemStatusTarget;
    
    // Clear existing items
    statusContainer.innerHTML = '';
    
    // Add service status items
    Object.entries(services).forEach(([key, service]) => {
      const statusClass = this.getStatusClass(service.status);
      const statusItem = document.createElement('div');
      statusItem.className = 'flex items-center justify-between p-3 border-b border-gray-200';
      statusItem.innerHTML = `
        <div class="flex items-center">
          <span class="w-3 h-3 rounded-full ${statusClass} mr-3"></span>
          <span class="font-medium">${service.name}</span>
        </div>
        <span class="text-sm text-gray-500">${this.formatStatusText(service.status)}</span>
      `;
      statusContainer.appendChild(statusItem);
    });
    
    // Update system metrics
    if (this.hasSystemMetricTarget && statusData.metrics) {
      this.updateSystemMetrics(statusData.metrics);
    }
  }
  
  updateSystemMetrics(metrics) {
    this.systemMetricTargets.forEach(metricElement => {
      const metricType = metricElement.dataset.metricType;
      
      if (!metricType || !metrics[metricType]) {
        return;
      }
      
      const metricData = metrics[metricType];
      
      switch (metricType) {
        case 'disk':
          this.updateProgressMetric(metricElement, 'Espace disque', metricData.percent_used, 
            `${this.formatFileSize(metricData.used)} / ${this.formatFileSize(metricData.total)}`);
          break;
        case 'database':
          this.updateTextMetric(metricElement, 'Base de données', 
            `${metricData.size_mb.toFixed(2)} MB - ${metricData.tables} tables`);
          break;
        case 'memory':
          this.updateProgressMetric(metricElement, 'Mémoire', metricData.percent_used, 
            `${this.formatFileSize(metricData.used)} / ${this.formatFileSize(metricData.limit)}`);
          break;
        case 'system_load':
          this.updateTextMetric(metricElement, 'Charge système', 
            `${metricData.load_1min.toFixed(2)} / ${metricData.load_5min.toFixed(2)} / ${metricData.load_15min.toFixed(2)}`);
          break;
      }
    });
  }
  
  updateProgressMetric(element, label, percent, details) {
    element.innerHTML = `
      <div class="flex justify-between mb-1">
        <span class="text-sm font-medium">${label}</span>
        <span class="text-sm text-gray-500">${percent}%</span>
      </div>
      <div class="w-full bg-gray-200 rounded-full h-2">
        <div class="bg-blue-600 h-2 rounded-full" style="width: ${percent}%"></div>
      </div>
      <div class="text-xs text-gray-500 mt-1">${details}</div>
    `;
  }
  
  updateTextMetric(element, label, value) {
    element.innerHTML = `
      <div class="flex justify-between">
        <span class="text-sm font-medium">${label}</span>
        <span class="text-sm text-gray-500">${value}</span>
      </div>
    `;
  }
  
  getStatusClass(status) {
    switch (status) {
      case 'operational':
        return 'bg-green-500';
      case 'degraded':
        return 'bg-yellow-500';
      case 'down':
        return 'bg-red-500';
      default:
        return 'bg-gray-500';
    }
  }
  
  formatStatusText(status) {
    switch (status) {
      case 'operational':
        return 'Opérationnel';
      case 'degraded':
        return 'Dégradé';
      case 'down':
        return 'Indisponible';
      default:
        return 'Inconnu';
    }
  }
  
  formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  }
  
  initializeCharts(chartsData) {
    // Only initialize if Chart.js is loaded
    if (typeof Chart === 'undefined') {
      console.error('Chart.js is not loaded');
      return;
    }
    
    // Initialize Assignment Status Chart
    if (this.hasAssignmentStatusChartTarget && chartsData.assignment_status) {
      this.initializeChart(
        this.assignmentStatusChartTarget, 
        'assignmentStatusChart',
        chartsData.assignment_status
      );
    }
    
    // Initialize Tutor Workload Chart
    if (this.hasTutorWorkloadChartTarget && chartsData.tutor_workload) {
      this.initializeChart(
        this.tutorWorkloadChartTarget, 
        'tutorWorkloadChart',
        chartsData.tutor_workload
      );
    }
    
    // Initialize Internship Type Chart
    if (this.hasInternshipTypeChartTarget && chartsData.internship_type) {
      this.initializeChart(
        this.internshipTypeChartTarget, 
        'internshipTypeChart',
        chartsData.internship_type
      );
    }
    
    // Initialize Document Submission Chart
    if (this.hasDocumentSubmissionChartTarget && chartsData.document_submission) {
      this.initializeChart(
        this.documentSubmissionChartTarget, 
        'documentSubmissionChart',
        chartsData.document_submission
      );
    }
  }
  
  initializeChart(element, chartProperty, chartData) {
    // If chart already exists, update it
    if (this[chartProperty]) {
      this[chartProperty].data.labels = chartData.labels;
      this[chartProperty].data.datasets = chartData.datasets;
      this[chartProperty].options.plugins.title = { 
        display: true, 
        text: chartData.title 
      };
      this[chartProperty].update();
      return;
    }
    
    // Create new chart
    this[chartProperty] = new Chart(element, {
      type: chartData.type,
      data: {
        labels: chartData.labels,
        datasets: chartData.datasets
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          title: {
            display: true,
            text: chartData.title
          },
          legend: {
            position: chartData.type === 'bar' || chartData.type === 'line' ? 'top' : 'right'
          }
        },
        scales: chartData.type === 'bar' || chartData.type === 'line' ? {
          y: {
            beginAtZero: true,
            ticks: {
              precision: 0
            }
          }
        } : undefined
      }
    });
  }
  
  updateNotificationCount(count) {
    if (!this.hasNotificationBadgeTarget) {
      return;
    }
    
    this.notificationBadgeTargets.forEach(badge => {
      badge.textContent = count;
      badge.classList.toggle('hidden', count === 0);
    });
  }
}