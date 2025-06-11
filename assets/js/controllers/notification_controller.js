import { Controller } from '@hotwired/stimulus';

/**
 * Notification controller for displaying toast notifications
 */
export default class extends Controller {
  static targets = ['container', 'template'];
  static values = {
    position: { type: String, default: 'top-right' },
    duration: { type: Number, default: 5000 }, // Duration in ms
    maxCount: { type: Number, default: 5 } // Maximum number of notifications shown at once
  };

  connect() {
    this.notifications = [];
    this.setupEventListener();
  }

  disconnect() {
    this.removeEventListener();
  }

  /**
   * Set up event listener for notification events
   */
  setupEventListener() {
    this.notificationHandler = this.handleNotificationEvent.bind(this);
    document.addEventListener('notification', this.notificationHandler);
  }

  /**
   * Remove event listener
   */
  removeEventListener() {
    document.removeEventListener('notification', this.notificationHandler);
  }

  /**
   * Handle notification events from other controllers
   */
  handleNotificationEvent(event) {
    const { message, type, duration } = event.detail;
    this.show(message, type, duration);
  }

  /**
   * Show a notification
   * 
   * @param {String} message - Notification message
   * @param {String} type - Notification type (success, error, warning, info)
   * @param {Number} duration - Display duration in ms, overrides default duration
   */
  show(message, type = 'info', duration = null) {
    // Use template if available, otherwise create notification element
    let notificationElement;
    
    if (this.hasTemplateTarget) {
      notificationElement = this.templateTarget.content.cloneNode(true).firstElementChild;
    } else {
      notificationElement = this.createNotificationElement();
    }
    
    // Set notification content and style
    this.setNotificationContent(notificationElement, message, type);
    
    // Add to container
    this.addNotification(notificationElement, duration || this.durationValue);
  }

  /**
   * Create a notification element
   */
  createNotificationElement() {
    const element = document.createElement('div');
    element.className = 'notification transform transition-all duration-300 max-w-sm w-full bg-white shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden';
    element.innerHTML = `
      <div class="p-4">
        <div class="flex items-start">
          <div class="flex-shrink-0 notification-icon"></div>
          <div class="ml-3 w-0 flex-1 pt-0.5 notification-content"></div>
          <div class="ml-4 flex-shrink-0 flex">
            <button type="button" class="bg-white rounded-md inline-flex text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500" data-action="notification#close">
              <span class="sr-only">Close</span>
              <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
              </svg>
            </button>
          </div>
        </div>
      </div>
    `;
    
    return element;
  }

  /**
   * Set notification content and style based on type
   */
  setNotificationContent(element, message, type) {
    // Set content
    const contentElement = element.querySelector('.notification-content');
    if (contentElement) {
      contentElement.textContent = message;
    }
    
    // Set icon based on type
    const iconElement = element.querySelector('.notification-icon');
    if (iconElement) {
      let iconSvg, iconClass;
      
      switch (type) {
        case 'success':
          iconSvg = `<svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>`;
          iconClass = 'text-success-500';
          break;
          
        case 'error':
          iconSvg = `<svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>`;
          iconClass = 'text-danger-500';
          break;
          
        case 'warning':
          iconSvg = `<svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>`;
          iconClass = 'text-warning-500';
          break;
          
        case 'info':
        default:
          iconSvg = `<svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>`;
          iconClass = 'text-info-500';
          break;
      }
      
      iconElement.innerHTML = iconSvg;
      iconElement.className = `flex-shrink-0 ${iconClass}`;
    }
    
    // Add type class to notification
    element.setAttribute('data-type', type);
  }

  /**
   * Add a notification to the container
   */
  addNotification(element, duration) {
    // Create container if it doesn't exist
    if (!this.hasContainerTarget) {
      this.createContainer();
    }
    
    // Add notification to the list
    this.notifications.push(element);
    
    // Limit number of notifications
    while (this.notifications.length > this.maxCountValue) {
      const oldNotification = this.notifications.shift();
      oldNotification.remove();
    }
    
    // Add to DOM with animation
    element.style.opacity = '0';
    element.style.transform = 'translateX(25px)';
    this.containerTarget.appendChild(element);
    
    // Trigger animation
    setTimeout(() => {
      element.style.opacity = '1';
      element.style.transform = 'translateX(0)';
    }, 10);
    
    // Set up auto-removal
    const removeTimeout = setTimeout(() => {
      this.removeNotification(element);
    }, duration);
    
    // Store timeout ID on the element
    element.dataset.timeoutId = removeTimeout;
    
    return element;
  }

  /**
   * Create the notifications container
   */
  createContainer() {
    const container = document.createElement('div');
    container.setAttribute('data-notification-target', 'container');
    
    // Set position classes
    let positionClass;
    switch (this.positionValue) {
      case 'top-left':
        positionClass = 'top-0 left-0';
        break;
      case 'top-center':
        positionClass = 'top-0 left-1/2 transform -translate-x-1/2';
        break;
      case 'bottom-right':
        positionClass = 'bottom-0 right-0';
        break;
      case 'bottom-left':
        positionClass = 'bottom-0 left-0';
        break;
      case 'bottom-center':
        positionClass = 'bottom-0 left-1/2 transform -translate-x-1/2';
        break;
      case 'top-right':
      default:
        positionClass = 'top-0 right-0';
        break;
    }
    
    container.className = `fixed ${positionClass} p-4 space-y-4 z-50 pointer-events-none max-h-screen overflow-hidden`;
    
    document.body.appendChild(container);
  }

  /**
   * Close and remove a notification
   */
  close(event) {
    const notification = event.currentTarget.closest('.notification');
    if (notification) {
      this.removeNotification(notification);
    }
  }

  /**
   * Remove a notification with animation
   */
  removeNotification(notification) {
    // Clear any existing timeout
    if (notification.dataset.timeoutId) {
      clearTimeout(parseInt(notification.dataset.timeoutId, 10));
    }
    
    // Remove from our list
    const index = this.notifications.indexOf(notification);
    if (index !== -1) {
      this.notifications.splice(index, 1);
    }
    
    // Animate out
    notification.style.opacity = '0';
    notification.style.transform = 'translateX(25px)';
    
    // Remove after animation
    setTimeout(() => {
      notification.remove();
    }, 300);
  }

  /**
   * Clear all notifications
   */
  clearAll() {
    this.notifications.forEach(notification => {
      this.removeNotification(notification);
    });
    this.notifications = [];
  }
}