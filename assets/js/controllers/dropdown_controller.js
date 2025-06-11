import { Controller } from '@hotwired/stimulus';

/**
 * Dropdown controller for dropdown menus
 */
export default class extends Controller {
  static targets = ['button', 'menu'];
  static values = {
    open: Boolean
  };

  connect() {
    // Close when clicking outside
    this.clickOutsideHandler = this.clickOutside.bind(this);
    document.addEventListener('click', this.clickOutsideHandler);

    // Close when pressing escape
    this.escapeHandler = this.escapeClose.bind(this);
    document.addEventListener('keydown', this.escapeHandler);

    // Initial state
    this.openValue = false;
  }

  disconnect() {
    // Clean up event listeners
    document.removeEventListener('click', this.clickOutsideHandler);
    document.removeEventListener('keydown', this.escapeHandler);
  }

  // Toggle dropdown visibility
  toggle() {
    this.openValue = !this.openValue;
  }

  // Open dropdown
  open() {
    this.openValue = true;
  }

  // Close dropdown
  close() {
    this.openValue = false;
  }

  // Handle escape key
  escapeClose(event) {
    if (event.key === 'Escape' && this.openValue) {
      this.close();
    }
  }

  // Handle click outside
  clickOutside(event) {
    if (this.openValue && !this.element.contains(event.target)) {
      this.close();
    }
  }

  // Watch for changes to open value
  openValueChanged() {
    if (this.hasMenuTarget) {
      if (this.openValue) {
        this.showDropdown();
      } else {
        this.hideDropdown();
      }
    }
  }

  // Show dropdown with animation
  showDropdown() {
    // First remove hidden class
    this.menuTarget.classList.remove('hidden');
    
    // Then animate in
    setTimeout(() => {
      this.menuTarget.classList.add('opacity-100', 'translate-y-0');
      this.menuTarget.classList.remove('opacity-0', 'translate-y-2');
    }, 10);
    
    // Update button state
    if (this.hasButtonTarget) {
      this.buttonTarget.setAttribute('aria-expanded', 'true');
    }
    
    // Dispatch event
    this.dispatch('shown');
  }

  // Hide dropdown with animation
  hideDropdown() {
    // First animate out
    this.menuTarget.classList.remove('opacity-100', 'translate-y-0');
    this.menuTarget.classList.add('opacity-0', 'translate-y-2');
    
    // Then add hidden class after animation completes
    setTimeout(() => {
      this.menuTarget.classList.add('hidden');
    }, 300);
    
    // Update button state
    if (this.hasButtonTarget) {
      this.buttonTarget.setAttribute('aria-expanded', 'false');
    }
    
    // Dispatch event
    this.dispatch('hidden');
  }

  // Method to select an item from the dropdown
  select(event) {
    const selectedValue = event.currentTarget.dataset.value;
    const selectedText = event.currentTarget.textContent.trim();
    
    // Dispatch selected event with details
    this.dispatch('selected', { 
      detail: { value: selectedValue, text: selectedText }
    });
    
    // Close dropdown
    this.close();
  }
}
