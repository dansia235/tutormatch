import { Controller } from '@hotwired/stimulus';

/**
 * Filter controller for handling filter forms
 */
export default class extends Controller {
  static targets = ['container', 'toggleIcon'];

  connect() {
    // Initialize any filter-specific behavior
    this.setupResponsiveHandling();
  }

  /**
   * Set up responsive behavior for filter container
   */
  setupResponsiveHandling() {
    // Check if we have the necessary targets
    if (!this.hasContainerTarget) return;
    
    // Add resize listener to show/hide filter container based on screen size
    this.resizeObserver = new ResizeObserver(entries => {
      for (let entry of entries) {
        // If window width is >= 640px (sm breakpoint), always show the filter container
        if (window.innerWidth >= 640) {
          this.containerTarget.classList.remove('hidden');
          
          // Update toggle icon if it exists
          if (this.hasToggleIconTarget) {
            this.toggleIconTarget.classList.remove('rotate-180');
          }
        }
      }
    });
    
    // Start observing
    this.resizeObserver.observe(document.body);
  }

  /**
   * Toggle filter container visibility on mobile
   */
  toggle() {
    if (!this.hasContainerTarget) return;
    
    // Toggle visibility
    this.containerTarget.classList.toggle('hidden');
    
    // Toggle icon rotation if it exists
    if (this.hasToggleIconTarget) {
      this.toggleIconTarget.classList.toggle('rotate-180');
    }
  }

  /**
   * Reset filter form to default values
   */
  reset(event) {
    event.preventDefault();
    
    // Get the form element
    const form = this.element.querySelector('form');
    if (!form) return;
    
    // Reset all inputs
    const inputs = form.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
      // Handle different input types
      switch (input.type) {
        case 'checkbox':
        case 'radio':
          input.checked = input.defaultChecked;
          break;
        
        case 'range':
          input.value = input.defaultValue;
          // Update range value display if any
          const valueDisplay = document.getElementById(`${input.id}-value`);
          if (valueDisplay) {
            valueDisplay.textContent = input.value;
          }
          break;
          
        default:
          input.value = input.defaultValue;
      }
      
      // Trigger change event to update any dependent elements
      input.dispatchEvent(new Event('change', { bubbles: true }));
    });
    
    // Submit the form to update results
    this.submit();
  }

  /**
   * Submit the filter form
   */
  submit() {
    // Get the form element
    const form = this.element.querySelector('form');
    if (!form) return;
    
    // Check if we should use AJAX or traditional form submission
    const useAjax = form.hasAttribute('data-ajax');
    
    if (useAjax) {
      this.submitWithAjax(form);
    } else {
      form.submit();
    }
  }

  /**
   * Submit filter form with AJAX
   */
  submitWithAjax(form) {
    // Create FormData object
    const formData = new FormData(form);
    
    // Get URL from form action
    const url = form.action;
    
    // Create query string
    const params = new URLSearchParams(formData);
    
    // Update URL without page reload
    const newUrl = new URL(url);
    newUrl.search = params.toString();
    window.history.pushState({}, '', newUrl);
    
    // Show loading indicator if any
    const loadingElement = document.querySelector('[data-filter-loading]');
    if (loadingElement) {
      loadingElement.classList.remove('hidden');
    }
    
    // Fetch results
    fetch(url + '?' + params.toString(), {
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    .then(response => response.text())
    .then(html => {
      // Update results container
      const resultsContainer = document.querySelector('[data-filter-results]');
      if (resultsContainer) {
        resultsContainer.innerHTML = html;
      }
      
      // Dispatch custom event to notify other components
      const event = new CustomEvent('filter:updated', {
        bubbles: true,
        detail: { 
          url: newUrl.toString(),
          params: Object.fromEntries(params)
        }
      });
      this.element.dispatchEvent(event);
    })
    .catch(error => {
      console.error('Error updating filter results:', error);
    })
    .finally(() => {
      // Hide loading indicator
      if (loadingElement) {
        loadingElement.classList.add('hidden');
      }
    });
  }
}