/**
 * Internship module
 * Handles internship search, applications, and management
 */

document.addEventListener('DOMContentLoaded', () => {
  initializeInternshipFilters();
  initializeInternshipForms();
  initializeInternshipDetails();
});

/**
 * Initialize internship filtering functionality
 */
function initializeInternshipFilters() {
  const filterForm = document.getElementById('internship-filters');
  if (!filterForm) return;
  
  const filterInputs = filterForm.querySelectorAll('select, input');
  const internshipList = document.getElementById('internship-list');
  
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
  
  // Reset filters button
  const resetButton = filterForm.querySelector('.reset-filters');
  if (resetButton) {
    resetButton.addEventListener('click', (e) => {
      e.preventDefault();
      
      // Reset all inputs to default values
      filterInputs.forEach(input => {
        if (input.type === 'search' || input.type === 'text') {
          input.value = '';
        } else if (input.type === 'checkbox' || input.type === 'radio') {
          input.checked = input.defaultChecked;
        } else {
          input.value = input.defaultValue;
        }
      });
      
      // Apply the reset filters
      applyFilters();
    });
  }
  
  /**
   * Apply all active filters to the internship list
   */
  function applyFilters() {
    if (!internshipList) return;
    
    // Get filter values
    const filters = {};
    filterInputs.forEach(input => {
      if (input.type === 'checkbox') {
        if (input.checked) {
          if (!filters[input.name]) {
            filters[input.name] = [];
          }
          filters[input.name].push(input.value.toLowerCase());
        }
      } else if (input.value) {
        filters[input.name] = input.value.toLowerCase();
      }
    });
    
    // Apply filters to internship items
    const internshipItems = internshipList.querySelectorAll('.internship-item');
    let visibleCount = 0;
    
    internshipItems.forEach(item => {
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
        // Special handling for multi-select filters (checkboxes)
        else if (Array.isArray(filterValue)) {
          if (filterValue.length > 0) {
            const itemValues = itemValue.split(',').map(v => v.trim().toLowerCase());
            const hasMatch = filterValue.some(value => itemValues.includes(value));
            if (!hasMatch) {
              visible = false;
            }
          }
        }
        // Regular filter
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
    
    // Show empty state if no internships match filters
    const emptyState = document.getElementById('internships-empty-state');
    if (emptyState) {
      if (visibleCount === 0) {
        emptyState.classList.remove('hidden');
      } else {
        emptyState.classList.add('hidden');
      }
    }
    
    // Update count
    const resultCount = document.getElementById('internship-count');
    if (resultCount) {
      resultCount.textContent = visibleCount;
    }
  }
}

/**
 * Initialize internship application forms
 */
function initializeInternshipForms() {
  const applicationForm = document.querySelector('form.internship-application');
  if (!applicationForm) return;
  
  // Form validation
  applicationForm.addEventListener('submit', (e) => {
    if (!validateInternshipForm(applicationForm)) {
      e.preventDefault();
    } else {
      // Disable form to prevent multiple submissions
      const submitButton = applicationForm.querySelector('[type="submit"]');
      if (submitButton) {
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm mr-2"></span>Soumission en cours...';
      }
    }
  });
  
  // Live validation on input change
  applicationForm.querySelectorAll('input, textarea, select').forEach(input => {
    input.addEventListener('change', () => {
      validateInput(input);
    });
  });
}

/**
 * Initialize internship details page functionality
 */
function initializeInternshipDetails() {
  // Handle application button
  const applyButton = document.getElementById('apply-internship');
  if (applyButton) {
    applyButton.addEventListener('click', (e) => {
      e.preventDefault();
      const internshipId = applyButton.dataset.internshipId;
      
      // Show application form or modal
      const applicationForm = document.getElementById('application-form');
      if (applicationForm) {
        applicationForm.classList.remove('hidden');
        applicationForm.scrollIntoView({ behavior: 'smooth' });
      }
    });
  }
  
  // Handle save/bookmark button
  const saveButton = document.getElementById('save-internship');
  if (saveButton) {
    saveButton.addEventListener('click', (e) => {
      e.preventDefault();
      const internshipId = saveButton.dataset.internshipId;
      
      // Toggle saved state
      const isSaved = saveButton.classList.contains('saved');
      
      if (isSaved) {
        saveButton.classList.remove('saved');
        saveButton.querySelector('i').classList.remove('bi-bookmark-fill');
        saveButton.querySelector('i').classList.add('bi-bookmark');
        saveButton.querySelector('span').textContent = 'Sauvegarder';
      } else {
        saveButton.classList.add('saved');
        saveButton.querySelector('i').classList.remove('bi-bookmark');
        saveButton.querySelector('i').classList.add('bi-bookmark-fill');
        saveButton.querySelector('span').textContent = 'Sauvegardé';
      }
      
      // In a real application, this would make an AJAX call to save/unsave
      console.log(`${isSaved ? 'Unsaving' : 'Saving'} internship ${internshipId}`);
    });
  }
  
  // Handle share button
  const shareButton = document.getElementById('share-internship');
  if (shareButton) {
    shareButton.addEventListener('click', (e) => {
      e.preventDefault();
      
      // Use Web Share API if available
      if (navigator.share) {
        navigator.share({
          title: document.title,
          url: window.location.href
        }).catch(console.error);
      } else {
        // Fallback: copy link to clipboard
        const dummy = document.createElement('input');
        document.body.appendChild(dummy);
        dummy.value = window.location.href;
        dummy.select();
        document.execCommand('copy');
        document.body.removeChild(dummy);
        
        // Show success message
        const message = document.createElement('div');
        message.className = 'fixed bottom-4 right-4 bg-dark text-white px-4 py-2 rounded shadow-lg z-50 transition-opacity duration-300';
        message.textContent = 'Lien copié dans le presse-papiers!';
        
        document.body.appendChild(message);
        
        setTimeout(() => {
          message.style.opacity = '0';
          setTimeout(() => message.remove(), 300);
        }, 3000);
      }
    });
  }
}

/**
 * Validate an internship application form
 * @param {HTMLFormElement} form - The form to validate
 * @returns {boolean} True if form is valid, false otherwise
 */
function validateInternshipForm(form) {
  let isValid = true;
  
  // Validate all required inputs
  form.querySelectorAll('[required]').forEach(input => {
    if (!validateInput(input)) {
      isValid = false;
    }
  });
  
  return isValid;
}

/**
 * Validate a single input
 * @param {HTMLElement} input - The input to validate
 * @returns {boolean} True if input is valid, false otherwise
 */
function validateInput(input) {
  const errorContainer = input.parentElement.querySelector('.error-message') || 
                        document.createElement('div');
  errorContainer.className = 'error-message text-red-500 text-sm mt-1';
  
  // Check if input is required and empty
  if (input.hasAttribute('required') && !input.value.trim()) {
    errorContainer.textContent = 'Ce champ est obligatoire';
    input.classList.add('border-red-500');
    
    if (!input.parentElement.querySelector('.error-message')) {
      input.parentElement.appendChild(errorContainer);
    }
    
    return false;
  }
  
  // Check email format for email inputs
  if (input.type === 'email' && input.value.trim()) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(input.value.trim())) {
      errorContainer.textContent = 'Veuillez entrer une adresse email valide';
      input.classList.add('border-red-500');
      
      if (!input.parentElement.querySelector('.error-message')) {
        input.parentElement.appendChild(errorContainer);
      }
      
      return false;
    }
  }
  
  // Input is valid, remove error styling
  input.classList.remove('border-red-500');
  if (input.parentElement.querySelector('.error-message')) {
    input.parentElement.querySelector('.error-message').remove();
  }
  
  return true;
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
