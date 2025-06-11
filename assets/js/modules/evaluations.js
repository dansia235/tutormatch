/**
 * Evaluations module
 * Handles evaluation forms and rating systems
 */

document.addEventListener('DOMContentLoaded', () => {
  initializeRatingSystems();
  initializeEvaluationForms();
});

/**
 * Initialize rating systems (star ratings, sliders, etc.)
 */
function initializeRatingSystems() {
  // Star rating system
  initializeStarRatings();
  
  // Slider rating system
  initializeSliderRatings();
  
  // Numeric rating system
  initializeNumericRatings();
}

/**
 * Initialize star rating inputs
 */
function initializeStarRatings() {
  const starRatings = document.querySelectorAll('.star-rating');
  
  starRatings.forEach(container => {
    const stars = container.querySelectorAll('.star');
    const input = container.querySelector('input[type="hidden"]');
    const readOnly = container.dataset.readonly === 'true';
    
    if (readOnly) return;
    
    stars.forEach((star, index) => {
      // Set initial state based on input value
      if (input && parseInt(input.value) > index) {
        star.classList.add('active');
      }
      
      star.addEventListener('click', () => {
        // Update stars display
        stars.forEach((s, i) => {
          if (i <= index) {
            s.classList.add('active');
          } else {
            s.classList.remove('active');
          }
        });
        
        // Update hidden input value
        if (input) {
          input.value = index + 1;
        }
      });
      
      // Hover effects
      star.addEventListener('mouseenter', () => {
        stars.forEach((s, i) => {
          if (i <= index) {
            s.classList.add('hover');
          } else {
            s.classList.remove('hover');
          }
        });
      });
    });
    
    container.addEventListener('mouseleave', () => {
      stars.forEach(s => s.classList.remove('hover'));
    });
  });
}

/**
 * Initialize slider rating inputs
 */
function initializeSliderRatings() {
  const sliderRatings = document.querySelectorAll('.slider-rating');
  
  sliderRatings.forEach(container => {
    const slider = container.querySelector('input[type="range"]');
    const valueDisplay = container.querySelector('.slider-value');
    
    if (!slider) return;
    
    // Set initial value display
    if (valueDisplay) {
      valueDisplay.textContent = slider.value;
    }
    
    // Update value display on change
    slider.addEventListener('input', () => {
      if (valueDisplay) {
        valueDisplay.textContent = slider.value;
      }
    });
  });
}

/**
 * Initialize numeric rating inputs
 */
function initializeNumericRatings() {
  const numericRatings = document.querySelectorAll('.numeric-rating');
  
  numericRatings.forEach(container => {
    const input = container.querySelector('input[type="number"]');
    const decrement = container.querySelector('.decrement');
    const increment = container.querySelector('.increment');
    
    if (!input) return;
    
    // Decrement button
    if (decrement) {
      decrement.addEventListener('click', () => {
        const min = parseInt(input.min) || 0;
        if (input.value > min) {
          input.value = parseInt(input.value) - 1;
          input.dispatchEvent(new Event('change'));
        }
      });
    }
    
    // Increment button
    if (increment) {
      increment.addEventListener('click', () => {
        const max = parseInt(input.max) || 10;
        if (parseInt(input.value) < max) {
          input.value = parseInt(input.value) + 1;
          input.dispatchEvent(new Event('change'));
        }
      });
    }
  });
}

/**
 * Initialize evaluation forms (validation, submission)
 */
function initializeEvaluationForms() {
  const evaluationForms = document.querySelectorAll('form.evaluation-form');
  
  evaluationForms.forEach(form => {
    form.addEventListener('submit', (e) => {
      // Validate form before submission
      if (!validateEvaluationForm(form)) {
        e.preventDefault();
      } else {
        // Disable form to prevent multiple submissions
        const submitButton = form.querySelector('[type="submit"]');
        if (submitButton) {
          submitButton.disabled = true;
          submitButton.innerHTML = '<span class="spinner-border spinner-border-sm mr-2"></span>Soumission en cours...';
        }
      }
    });
    
    // Live validation on input change
    form.querySelectorAll('input, textarea, select').forEach(input => {
      input.addEventListener('change', () => {
        validateInput(input);
      });
    });
  });
}

/**
 * Validate an evaluation form
 * @param {HTMLFormElement} form - The form to validate
 * @returns {boolean} True if form is valid, false otherwise
 */
function validateEvaluationForm(form) {
  let isValid = true;
  
  // Validate all inputs
  form.querySelectorAll('[required]').forEach(input => {
    if (!validateInput(input)) {
      isValid = false;
    }
  });
  
  // Check if at least one rating is provided
  const ratingInputs = form.querySelectorAll('.star-rating input, .slider-rating input, .numeric-rating input');
  let hasRating = false;
  
  ratingInputs.forEach(input => {
    if (input.value && input.value !== '0') {
      hasRating = true;
    }
  });
  
  if (!hasRating && ratingInputs.length > 0) {
    isValid = false;
    const errorMessage = form.querySelector('.rating-error') || document.createElement('div');
    errorMessage.className = 'rating-error text-red-500 text-sm mt-2';
    errorMessage.textContent = 'Veuillez fournir au moins une évaluation';
    
    if (!form.querySelector('.rating-error')) {
      form.appendChild(errorMessage);
    }
  } else {
    const errorMessage = form.querySelector('.rating-error');
    if (errorMessage) {
      errorMessage.remove();
    }
  }
  
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
  
  // Check min/max for number inputs
  if (input.type === 'number') {
    const value = parseInt(input.value);
    const min = parseInt(input.min);
    const max = parseInt(input.max);
    
    if (!isNaN(min) && value < min) {
      errorContainer.textContent = `La valeur minimale est ${min}`;
      input.classList.add('border-red-500');
      
      if (!input.parentElement.querySelector('.error-message')) {
        input.parentElement.appendChild(errorContainer);
      }
      
      return false;
    }
    
    if (!isNaN(max) && value > max) {
      errorContainer.textContent = `La valeur maximale est ${max}`;
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
