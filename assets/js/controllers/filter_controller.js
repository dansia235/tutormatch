import { Controller } from '@hotwired/stimulus';

/**
 * Filter controller for handling filter forms
 */
export default class extends Controller {
  static targets = ['container', 'toggleIcon', 'content', 'collapseIcon'];

  connect() {
    // Initialize any filter-specific behavior
    this.setupResponsiveHandling();
    
    // Check URL parameters to automatically expand filters if any are set
    const urlParams = new URLSearchParams(window.location.search);
    const filterParams = ['domain', 'location', 'work_mode', 'start_date_from', 'start_date_to', 'skills'];
    
    // Check if any filter is applied
    const hasFilters = filterParams.some(param => 
      urlParams.has(param) && urlParams.get(param) !== ''
    );
    
    // Expand filters if any are applied
    if (hasFilters) {
      if (this.hasContentTarget) {
        this.contentTarget.classList.remove('hidden');
        this.updateCollapseIcon(true);
      }
      if (this.hasContainerTarget) {
        this.containerTarget.classList.remove('hidden');
      }
    }
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
   * Update collapse icon based on visibility state
   */
  updateCollapseIcon(isVisible) {
    if (this.hasCollapseIconTarget) {
      if (isVisible) {
        this.collapseIconTarget.innerHTML = `
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd" />
          </svg>
        `;
      } else {
        this.collapseIconTarget.innerHTML = `
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
          </svg>
        `;
      }
    }
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