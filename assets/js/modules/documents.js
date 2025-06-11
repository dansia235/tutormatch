/**
 * Documents module
 * Handles document uploads, previews, and management
 */

document.addEventListener('DOMContentLoaded', () => {
  initializeFileUploads();
  initializeDocumentFilters();
});

/**
 * Initialize file upload inputs with preview functionality
 */
function initializeFileUploads() {
  // Find all file upload inputs
  const fileInputs = document.querySelectorAll('input[type="file"]');
  
  fileInputs.forEach(input => {
    // Get the file name element and preview container if they exist
    const fileNameElement = document.querySelector(`[data-file-name="${input.id}"]`);
    const previewContainer = document.querySelector(`[data-preview="${input.id}"]`);
    const previewImage = previewContainer?.querySelector('img');
    
    // Add change event listener
    input.addEventListener('change', () => {
      const file = input.files[0];
      
      // Update file name display
      if (fileNameElement && file) {
        fileNameElement.textContent = file.name;
      }
      
      // Handle preview for images
      if (previewContainer && previewImage && file) {
        // Check if the file is an image
        if (file.type.startsWith('image/')) {
          const reader = new FileReader();
          
          reader.onload = (e) => {
            previewImage.src = e.target.result;
            previewContainer.classList.remove('hidden');
          };
          
          reader.readAsDataURL(file);
        } else {
          // If not an image, hide the preview
          previewContainer.classList.add('hidden');
        }
      }
    });
  });
}

/**
 * Initialize document filtering functionality
 */
function initializeDocumentFilters() {
  const filterForm = document.getElementById('document-filters');
  if (!filterForm) return;
  
  // Get all filter inputs
  const filterInputs = filterForm.querySelectorAll('select, input');
  const documentList = document.getElementById('document-list');
  
  // Handle filter changes
  filterInputs.forEach(input => {
    input.addEventListener('change', () => {
      applyFilters();
    });
  });
  
  // Handle search input
  const searchInput = filterForm.querySelector('input[type="search"]');
  if (searchInput) {
    searchInput.addEventListener('input', debounce(() => {
      applyFilters();
    }, 300));
  }
  
  /**
   * Apply all active filters to the document list
   */
  function applyFilters() {
    if (!documentList) return;
    
    // Get filter values
    const filters = {};
    filterInputs.forEach(input => {
      if (input.value) {
        filters[input.name] = input.value.toLowerCase();
      }
    });
    
    // Apply filters to document items
    const documentItems = documentList.querySelectorAll('.document-item');
    let visibleCount = 0;
    
    documentItems.forEach(item => {
      let visible = true;
      
      // Check each filter
      Object.entries(filters).forEach(([filterName, filterValue]) => {
        const itemValue = item.dataset[filterName]?.toLowerCase() || '';
        
        // Special handling for search filter
        if (filterName === 'search') {
          const searchableText = item.textContent.toLowerCase();
          if (!searchableText.includes(filterValue)) {
            visible = false;
          }
        } 
        // Regular category filter
        else if (filterValue && itemValue !== filterValue) {
          visible = false;
        }
      });
      
      // Show or hide item based on filter results
      if (visible) {
        item.classList.remove('hidden');
        visibleCount++;
      } else {
        item.classList.add('hidden');
      }
    });
    
    // Show empty state if no documents match filters
    const emptyState = document.getElementById('documents-empty-state');
    if (emptyState) {
      if (visibleCount === 0) {
        emptyState.classList.remove('hidden');
      } else {
        emptyState.classList.add('hidden');
      }
    }
  }
}

/**
 * Debounce function to limit how often a function is called
 * @param {Function} func - The function to debounce
 * @param {number} wait - The debounce delay in milliseconds
 * @returns {Function} The debounced function
 */
function debounce(func, wait) {
  let timeout;
  return function(...args) {
    clearTimeout(timeout);
    timeout = setTimeout(() => func.apply(this, args), wait);
  };
}
