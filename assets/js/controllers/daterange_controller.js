import { Controller } from '@hotwired/stimulus';

/**
 * Date Range controller for managing related date inputs
 */
export default class extends Controller {
  static targets = ['start', 'end'];

  connect() {
    this.initialize();
  }

  initialize() {
    // Set min/max constraints between start and end dates
    if (this.hasStartTarget && this.hasEndTarget) {
      this.startTarget.addEventListener('change', () => this.updateEndMin());
      this.endTarget.addEventListener('change', () => this.updateStartMax());
      
      // Initial setup
      this.updateEndMin();
      this.updateStartMax();
    }
  }

  /**
   * Update end date min attribute based on start date
   */
  updateEndMin() {
    const startDate = this.startTarget.value;
    if (startDate && this.hasEndTarget) {
      this.endTarget.min = startDate;
      
      // If end date is now before start date, update it
      if (this.endTarget.value && this.endTarget.value < startDate) {
        this.endTarget.value = startDate;
      }
    }
  }

  /**
   * Update start date max attribute based on end date
   */
  updateStartMax() {
    const endDate = this.endTarget.value;
    if (endDate && this.hasStartTarget) {
      this.startTarget.max = endDate;
      
      // If start date is now after end date, update it
      if (this.startTarget.value && this.startTarget.value > endDate) {
        this.startTarget.value = endDate;
      }
    }
  }

  /**
   * Set a predefined date range
   * @param {Event} event - Event with dataset containing range info
   */
  setRange(event) {
    if (!this.hasStartTarget || !this.hasEndTarget) return;
    
    const range = event.currentTarget.dataset.range;
    const today = new Date();
    let startDate, endDate;
    
    // Calculate date range based on preset
    switch(range) {
      case 'today':
        startDate = endDate = this.formatDate(today);
        break;
        
      case 'yesterday':
        const yesterday = new Date(today);
        yesterday.setDate(today.getDate() - 1);
        startDate = endDate = this.formatDate(yesterday);
        break;
        
      case 'week':
        endDate = this.formatDate(today);
        const weekAgo = new Date(today);
        weekAgo.setDate(today.getDate() - 7);
        startDate = this.formatDate(weekAgo);
        break;
        
      case 'month':
        endDate = this.formatDate(today);
        const monthAgo = new Date(today);
        monthAgo.setMonth(today.getMonth() - 1);
        startDate = this.formatDate(monthAgo);
        break;
        
      case 'quarter':
        endDate = this.formatDate(today);
        const quarterAgo = new Date(today);
        quarterAgo.setMonth(today.getMonth() - 3);
        startDate = this.formatDate(quarterAgo);
        break;
        
      case 'year':
        endDate = this.formatDate(today);
        const yearAgo = new Date(today);
        yearAgo.setFullYear(today.getFullYear() - 1);
        startDate = this.formatDate(yearAgo);
        break;
    }
    
    // Update inputs
    if (startDate) this.startTarget.value = startDate;
    if (endDate) this.endTarget.value = endDate;
    
    // Trigger change events
    this.startTarget.dispatchEvent(new Event('change', { bubbles: true }));
    this.endTarget.dispatchEvent(new Event('change', { bubbles: true }));
  }

  /**
   * Format Date object as YYYY-MM-DD for input fields
   */
  formatDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
  }

  /**
   * Clear date range
   */
  clear() {
    if (this.hasStartTarget) this.startTarget.value = '';
    if (this.hasEndTarget) this.endTarget.value = '';
    
    // Trigger change events
    this.startTarget.dispatchEvent(new Event('change', { bubbles: true }));
    this.endTarget.dispatchEvent(new Event('change', { bubbles: true }));
  }
}