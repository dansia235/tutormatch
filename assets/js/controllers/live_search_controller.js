import { Controller } from '@hotwired/stimulus';

/**
 * Live Search controller for implementing real-time search functionality
 */
export default class extends Controller {
  static targets = ['input', 'results', 'list', 'noResults', 'loading'];
  static values = {
    url: String,
    minChars: { type: Number, default: 2 },
    debounce: { type: Number, default: 300 }
  };

  connect() {
    this.resultsVisible = false;
    this.searchTimeout = null;
    this.clickOutsideHandler = this.handleClickOutside.bind(this);
    
    // Add click outside listener
    document.addEventListener('click', this.clickOutsideHandler);
  }
  
  disconnect() {
    // Remove click outside listener
    document.removeEventListener('click', this.clickOutsideHandler);
    
    // Clear any pending timeout
    if (this.searchTimeout) {
      clearTimeout(this.searchTimeout);
    }
  }

  /**
   * Handle search input
   */
  search() {
    // Clear any existing timeout
    if (this.searchTimeout) {
      clearTimeout(this.searchTimeout);
    }
    
    const query = this.inputTarget.value.trim();
    
    // Hide results if query is too short
    if (query.length < this.minCharsValue) {
      this.hideResults();
      return;
    }
    
    // Set timeout for debouncing
    this.searchTimeout = setTimeout(() => {
      this.performSearch(query);
    }, this.debounceValue);
  }
  
  /**
   * Perform the actual search request
   */
  performSearch(query) {
    if (!this.hasUrlValue) return;
    
    // Show loading state
    this.showLoading();
    
    // Prepare URL with query parameter
    const url = new URL(this.urlValue, window.location.origin);
    url.searchParams.append('q', query);
    
    // Add current page context if available
    const contextInput = this.element.querySelector('[data-search-context]');
    if (contextInput) {
      url.searchParams.append('context', contextInput.value);
    }
    
    // Fetch search results
    fetch(url.toString(), {
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    .then(response => response.json())
    .then(data => {
      this.updateResults(data);
    })
    .catch(error => {
      console.error('Search error:', error);
      this.showNoResults();
    })
    .finally(() => {
      // Hide loading state
      this.hideLoading();
    });
  }
  
  /**
   * Update search results
   */
  updateResults(data) {
    if (!this.hasResultsTarget || !this.hasListTarget) return;
    
    // Check if we have results
    if (!data.results || data.results.length === 0) {
      this.showNoResults();
      return;
    }
    
    // Clear existing results
    this.listTarget.innerHTML = '';
    
    // Add results to the list
    data.results.forEach(result => {
      const resultElement = this.createResultElement(result);
      this.listTarget.appendChild(resultElement);
    });
    
    // Show results
    this.showResults();
    
    // Hide no results message
    if (this.hasNoResultsTarget) {
      this.noResultsTarget.classList.add('hidden');
    }
  }
  
  /**
   * Create a search result element
   */
  createResultElement(result) {
    const resultElement = document.createElement('div');
    resultElement.className = 'search-result px-4 py-2 hover:bg-gray-100 cursor-pointer';
    resultElement.setAttribute('data-action', 'click->live-search#selectResult');
    resultElement.setAttribute('data-url', result.url || '#');
    resultElement.setAttribute('data-id', result.id || '');
    
    let resultContent = '';
    
    if (result.image) {
      resultContent += `
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <img src="${result.image}" alt="" class="h-8 w-8 rounded-full">
          </div>
          <div class="ml-3">
      `;
    }
    
    resultContent += `
      <div class="font-medium text-gray-900">${result.title}</div>
    `;
    
    if (result.subtitle) {
      resultContent += `<div class="text-sm text-gray-500">${result.subtitle}</div>`;
    }
    
    if (result.image) {
      resultContent += `
          </div>
        </div>
      `;
    }
    
    resultElement.innerHTML = resultContent;
    return resultElement;
  }
  
  /**
   * Select a search result
   */
  selectResult(event) {
    const resultElement = event.currentTarget;
    const url = resultElement.getAttribute('data-url');
    const id = resultElement.getAttribute('data-id');
    
    // Dispatch custom event for result selection
    const selectEvent = new CustomEvent('search:select', {
      bubbles: true,
      detail: {
        id,
        url,
        element: resultElement
      }
    });
    this.element.dispatchEvent(selectEvent);
    
    // Navigate to result URL if provided and no event default prevented
    if (url && url !== '#' && !selectEvent.defaultPrevented) {
      window.location.href = url;
    }
    
    // Hide results
    this.hideResults();
  }
  
  /**
   * Show search results
   */
  showResults() {
    if (!this.hasResultsTarget) return;
    
    this.resultsTarget.classList.remove('hidden');
    this.resultsVisible = true;
    
    // Add some animation classes
    this.resultsTarget.classList.add('fade-in', 'show');
  }
  
  /**
   * Hide search results
   */
  hideResults() {
    if (!this.hasResultsTarget) return;
    
    this.resultsTarget.classList.add('hidden');
    this.resultsTarget.classList.remove('fade-in', 'show');
    this.resultsVisible = false;
  }
  
  /**
   * Show no results message
   */
  showNoResults() {
    if (!this.hasNoResultsTarget || !this.hasResultsTarget || !this.hasListTarget) return;
    
    this.listTarget.innerHTML = '';
    this.noResultsTarget.classList.remove('hidden');
    this.showResults();
  }
  
  /**
   * Show loading indicator
   */
  showLoading() {
    if (!this.hasLoadingTarget) return;
    
    this.loadingTarget.classList.remove('hidden');
    
    // Show results container as well
    if (this.hasResultsTarget) {
      this.resultsTarget.classList.remove('hidden');
    }
  }
  
  /**
   * Hide loading indicator
   */
  hideLoading() {
    if (!this.hasLoadingTarget) return;
    
    this.loadingTarget.classList.add('hidden');
  }
  
  /**
   * Clear the search input and results
   */
  clear() {
    if (!this.hasInputTarget) return;
    
    // Clear input
    this.inputTarget.value = '';
    
    // Hide results
    this.hideResults();
    
    // Focus input
    this.inputTarget.focus();
  }
  
  /**
   * Prevent form submission on enter key
   */
  preventSubmit(event) {
    if (event.key === 'Enter') {
      event.preventDefault();
    }
  }
  
  /**
   * Handle clicks outside the search component to hide results
   */
  handleClickOutside(event) {
    if (!this.resultsVisible) return;
    
    // Check if click is outside the search box
    if (!this.element.contains(event.target)) {
      this.hideResults();
    }
  }
}