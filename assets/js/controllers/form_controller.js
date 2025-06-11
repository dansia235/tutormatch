import { Controller } from '@hotwired/stimulus';
import errorHandler from '../services/error-handler';

/**
 * Form controller for form validation and submission handling
 */
export default class extends Controller {
  static targets = ['field', 'input', 'submit', 'error', 'feedback', 'success', 'form'];
  
  static values = {
    submitUrl: String,
    redirectUrl: String,
    validateUrl: String,
    rules: Object,
    submitDisabled: Boolean
  };

  connect() {
    this.initializeValidation();
    
    // Disable submit button if requested
    if (this.hasSubmitDisabledValue && this.submitDisabledValue && this.hasSubmitTarget) {
      this.submitTarget.disabled = true;
    }
    
    // Check URL params for error messages
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('error') && this.hasErrorTarget) {
      this.errorTarget.textContent = decodeURIComponent(urlParams.get('error'));
      this.errorTarget.classList.remove('hidden');
    }
    
    if (urlParams.has('success') && this.hasSuccessTarget) {
      this.successTarget.textContent = decodeURIComponent(urlParams.get('success'));
      this.successTarget.classList.remove('hidden');
      
      // Hide success message after 5 seconds
      setTimeout(() => {
        this.successTarget.classList.add('hidden');
      }, 5000);
    }
  }

  initializeValidation() {
    // First check if we're using the new validation approach with rules
    if (this.hasRulesValue) {
      this.initializeRulesValidation();
      return;
    }
    
    // Legacy validation - Add validation listeners to all fields
    const fieldsToValidate = this.hasFieldTargets ? this.fieldTargets : this.inputTargets;
    
    fieldsToValidate.forEach(field => {
      field.addEventListener('blur', () => this.validateField(field));
      field.addEventListener('change', () => this.validateField(field));
    });
  }
  
  /**
   * Initialize validation with rules object
   */
  initializeRulesValidation() {
    if (!this.hasFormTarget && !this.element.tagName === 'FORM') return;
    
    const form = this.hasFormTarget ? this.formTarget : this.element;
    
    // Add listeners to fields with rules
    const inputFields = this.hasInputTargets ? this.inputTargets : 
                        form.querySelectorAll('input, select, textarea');
    
    inputFields.forEach(input => {
      const fieldName = input.name;
      
      // Check if this field has validation rules
      if (this.rulesValue[fieldName]) {
        input.addEventListener('blur', () => {
          this.validateFieldWithRules(input);
        });
        
        input.addEventListener('input', () => {
          this.clearFieldError(input);
        });
      }
    });
    
    // Intercept form submission for validation
    form.addEventListener('submit', (e) => {
      if (!this.validateFormWithRules()) {
        e.preventDefault();
        e.stopPropagation();
      }
    });
  }
  
  /**
   * Validate a field using the rules object
   */
  validateFieldWithRules(field) {
    const fieldName = field.name;
    const fieldRules = this.rulesValue[fieldName];
    
    if (!fieldRules) return true;
    
    const form = this.hasFormTarget ? this.formTarget : this.element;
    const value = field.value;
    const data = { [fieldName]: value };
    
    // If the field has a match rule, add the target field to the data
    if (fieldRules.match) {
      const matchField = form.querySelector(`[name="${fieldRules.match}"]`);
      if (matchField) {
        data[fieldRules.match] = matchField.value;
      }
    }
    
    // Validate with error handler service
    const errors = errorHandler.validateData(data, { [fieldName]: fieldRules });
    
    if (errors && errors[fieldName]) {
      this.setFieldInvalid(field, errors[fieldName]);
      return false;
    } else {
      this.setFieldValid(field);
      return true;
    }
  }
  
  /**
   * Validate the entire form using the rules object
   */
  validateFormWithRules() {
    const form = this.hasFormTarget ? this.formTarget : this.element;
    const inputs = form.querySelectorAll('input, select, textarea');
    let isValid = true;
    
    // Collect all form data
    const formData = {};
    inputs.forEach(input => {
      if (input.name) {
        formData[input.name] = input.value;
      }
    });
    
    // Validate all fields with rules
    Object.keys(this.rulesValue).forEach(fieldName => {
      const input = form.querySelector(`[name="${fieldName}"]`);
      if (input) {
        const fieldValid = this.validateFieldWithRules(input);
        isValid = isValid && fieldValid;
      }
    });
    
    // Show global error if needed
    if (!isValid && this.hasErrorTarget) {
      this.errorTarget.textContent = 'Veuillez corriger les erreurs dans le formulaire';
      this.errorTarget.classList.remove('hidden');
    }
    
    return isValid;
  }
  
  /**
   * Mark a field as valid
   */
  setFieldValid(field) {
    // Remove error classes
    field.classList.remove('border-danger-500', 'focus:border-danger-500', 'focus:ring-danger-500');
    field.classList.add('border-gray-300', 'focus:border-primary-500', 'focus:ring-primary-500');
    
    // Hide error message
    const errorContainer = this.getFieldErrorContainer(field);
    if (errorContainer) {
      errorContainer.classList.add('hidden');
      errorContainer.textContent = '';
    }
  }
  
  /**
   * Mark a field as invalid
   */
  setFieldInvalid(field, message) {
    // Add error classes
    field.classList.remove('border-gray-300', 'focus:border-primary-500', 'focus:ring-primary-500');
    field.classList.add('border-danger-500', 'focus:border-danger-500', 'focus:ring-danger-500');
    
    // Show error message
    const errorContainer = this.getFieldErrorContainer(field);
    if (errorContainer) {
      errorContainer.textContent = message;
      errorContainer.classList.remove('hidden');
    }
  }
  
  /**
   * Get the error container for a field
   */
  getFieldErrorContainer(field) {
    // Try to find element with data-error-for="field-name"
    const errorForField = this.element.querySelector(`[data-error-for="${field.name}"]`);
    if (errorForField) return errorForField;
    
    // Or find element with ID "field-name-error"
    const errorById = document.getElementById(`${field.name}-error`);
    if (errorById) return errorById;
    
    // Or look in the field's parent
    const parent = field.closest('.form-group');
    if (parent) {
      const errorInParent = parent.querySelector('.form-error, .field-error');
      if (errorInParent) return errorInParent;
    }
    
    return null;
  }
  
  /**
   * Clear error state for a field
   */
  clearFieldError(field) {
    // Remove error classes
    field.classList.remove('border-danger-500', 'focus:border-danger-500', 'focus:ring-danger-500', 'border-red-500');
    field.classList.add('border-gray-300', 'focus:border-primary-500', 'focus:ring-primary-500');
    
    // Hide error message
    const errorContainer = this.getFieldErrorContainer(field);
    if (errorContainer) {
      errorContainer.classList.add('hidden');
    }
    
    // Legacy error elements
    const legacyError = field.parentElement.querySelector('.error-message');
    if (legacyError) {
      legacyError.remove();
    }
  }

  // Legacy methods - Kept for backward compatibility

  /**
   * Validate a single field (legacy method)
   */
  validateField(field) {
    // If using new validation, redirect to that method
    if (this.hasRulesValue && this.rulesValue[field.name]) {
      return this.validateFieldWithRules(field);
    }
    
    // Clear existing errors
    this.clearFieldErrors(field);

    // Check required fields
    if (field.hasAttribute('required') && !field.value.trim()) {
      this.showFieldError(field, 'Ce champ est obligatoire');
      return false;
    }

    // Validate email fields
    if (field.type === 'email' && field.value.trim()) {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(field.value.trim())) {
        this.showFieldError(field, 'Veuillez entrer une adresse email valide');
        return false;
      }
    }

    // Validate number fields
    if (field.type === 'number' && field.value.trim()) {
      const value = parseFloat(field.value);
      const min = parseFloat(field.min);
      const max = parseFloat(field.max);

      if (!isNaN(min) && value < min) {
        this.showFieldError(field, `La valeur minimale est ${min}`);
        return false;
      }

      if (!isNaN(max) && value > max) {
        this.showFieldError(field, `La valeur maximale est ${max}`);
        return false;
      }
    }

    // Server-side validation if URL is provided
    if (this.hasValidateUrlValue && field.name) {
      this.validateFieldWithServer(field);
    }

    return true;
  }

  /**
   * Validate field with server-side validation (legacy method)
   */
  validateFieldWithServer(field) {
    const formData = new FormData();
    formData.append('field', field.name);
    formData.append('value', field.value);

    fetch(this.validateUrlValue, {
      method: 'POST',
      body: formData,
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    .then(response => response.json())
    .then(data => {
      if (!data.valid) {
        this.showFieldError(field, data.message || 'Validation error');
      }
    })
    .catch(error => {
      console.error('Validation error:', error);
    });
  }

  /**
   * Validate the entire form (legacy method)
   */
  validateForm() {
    // If using new validation, redirect to that method
    if (this.hasRulesValue) {
      return this.validateFormWithRules();
    }
    
    let isValid = true;
    const fieldsToValidate = this.hasFieldTargets ? this.fieldTargets : this.inputTargets;

    fieldsToValidate.forEach(field => {
      if (!this.validateField(field)) {
        isValid = false;
      }
    });

    return isValid;
  }

  /**
   * Show validation error for a field (legacy method)
   */
  showFieldError(field, message) {
    field.classList.add('border-red-500');
    
    // Find or create error element
    let errorElement = field.parentElement.querySelector('.error-message');
    if (!errorElement) {
      errorElement = document.createElement('div');
      errorElement.className = 'error-message text-red-500 text-sm mt-1';
      field.parentElement.appendChild(errorElement);
    }
    
    errorElement.textContent = message;
  }

  /**
   * Clear validation errors for a field (legacy method)
   */
  clearFieldErrors(field) {
    field.classList.remove('border-red-500');
    
    const errorElement = field.parentElement.querySelector('.error-message');
    if (errorElement) {
      errorElement.remove();
    }
  }

  /**
   * Submit form handler
   */
  submitForm(event) {
    if (!this.validateForm()) {
      event.preventDefault();
      return;
    }

    // If submitUrl is provided, handle form submission via AJAX
    if (this.hasSubmitUrlValue) {
      event.preventDefault();
      this.submitFormWithAjax();
    }
  }

  /**
   * Submit form with AJAX
   */
  submitFormWithAjax() {
    const form = this.hasFormTarget ? this.formTarget : this.element;
    const formData = new FormData(form);

    // Disable submit button and show loading state
    if (this.hasSubmitTarget) {
      this.submitTarget.disabled = true;
      this.submitTarget.innerHTML = '<span class="spinner-border spinner-border-sm mr-2"></span>Soumission en cours...';
    }

    fetch(this.submitUrlValue, {
      method: 'POST',
      body: formData,
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        this.handleSuccessResponse(data);
      } else {
        this.handleErrorResponse(data);
      }
    })
    .catch(error => {
      console.error('Submission error:', error);
      this.handleErrorResponse({ message: 'Une erreur est survenue lors de la soumission du formulaire.' });
    })
    .finally(() => {
      // Re-enable submit button
      if (this.hasSubmitTarget) {
        this.submitTarget.disabled = false;
        this.submitTarget.innerHTML = 'Soumettre';
      }
    });
  }

  /**
   * Handle successful form submission
   */
  handleSuccessResponse(data) {
    // Show success message if feedback target exists
    if (this.hasFeedbackTarget) {
      this.feedbackTarget.textContent = data.message || 'Formulaire soumis avec succès';
      this.feedbackTarget.classList.remove('hidden', 'text-red-500');
      this.feedbackTarget.classList.add('text-green-500');
    }
    
    // Or use new success target
    if (this.hasSuccessTarget) {
      this.successTarget.textContent = data.message || 'Formulaire soumis avec succès';
      this.successTarget.classList.remove('hidden');
    }

    // Dispatch success event
    const successEvent = new CustomEvent('form:success', {
      bubbles: true,
      detail: { response: data }
    });
    this.element.dispatchEvent(successEvent);

    // Redirect if URL provided
    if (this.hasRedirectUrlValue) {
      window.location.href = this.redirectUrlValue.replace(':id', data.id || data.data?.id || '');
    }

    // Reset form
    this.element.reset();
  }

  /**
   * Handle form submission error
   */
  handleErrorResponse(data) {
    // Use error handler service if available
    const processedError = errorHandler.handleError(
      data, 
      'form-submission'
    );
    
    // Show error messages for fields
    if (data.errors) {
      Object.entries(data.errors).forEach(([field, message]) => {
        const fieldElement = this.element.querySelector(`[name="${field}"]`);
        if (fieldElement) {
          this.setFieldInvalid(fieldElement, message);
        }
      });
    }

    // Show general error message
    if (this.hasErrorTarget) {
      this.errorTarget.innerHTML = processedError.message || data.message || 'Une erreur est survenue';
      this.errorTarget.classList.remove('hidden');
    }

    // Show in feedback target as well if it exists (legacy)
    if (this.hasFeedbackTarget) {
      this.feedbackTarget.textContent = data.message || 'Une erreur est survenue';
      this.feedbackTarget.classList.remove('hidden', 'text-green-500');
      this.feedbackTarget.classList.add('text-red-500');
    }
    
    // Dispatch error event
    const errorEvent = new CustomEvent('form:error', {
      bubbles: true,
      detail: { error: data }
    });
    this.element.dispatchEvent(errorEvent);
  }
}