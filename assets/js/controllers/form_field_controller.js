import { Controller } from '@hotwired/stimulus';

/**
 * Form Field controller for individual field validation
 */
export default class extends Controller {
  static targets = ['input', 'error'];
  static values = {
    name: String,
    validateUrl: String
  };

  connect() {
    this.initialize();
  }

  initialize() {
    if (this.hasInputTarget) {
      // Add event listeners for validation
      this.inputTarget.addEventListener('blur', this.validate.bind(this));
      this.inputTarget.addEventListener('change', this.validate.bind(this));
      
      // Special handling for different input types
      if (this.inputTarget.type === 'file') {
        this.setupFilePreview();
      }
    }
  }

  /**
   * Validate the field
   */
  validate() {
    if (!this.hasInputTarget) return true;
    
    const input = this.inputTarget;
    
    // Clear existing errors
    this.clearError();
    
    // Skip validation for empty optional fields
    if (!input.hasAttribute('required') && !input.value.trim()) {
      return true;
    }
    
    // Required field validation
    if (input.hasAttribute('required') && !input.value.trim()) {
      this.showError('Ce champ est obligatoire');
      return false;
    }
    
    // Specific validations based on type
    switch (input.type) {
      case 'email':
        return this.validateEmail();
      case 'number':
      case 'range':
        return this.validateNumber();
      case 'tel':
        return this.validatePhone();
      case 'url':
        return this.validateUrl();
      case 'password':
        return this.validatePassword();
      case 'file':
        return this.validateFile();
      default:
        return this.validateGeneral();
    }
  }
  
  /**
   * Email validation
   */
  validateEmail() {
    const input = this.inputTarget;
    if (!input.value.trim()) return true;
    
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(input.value.trim())) {
      this.showError('Veuillez entrer une adresse email valide');
      return false;
    }
    
    return true;
  }
  
  /**
   * Number validation
   */
  validateNumber() {
    const input = this.inputTarget;
    if (!input.value.trim()) return true;
    
    const value = parseFloat(input.value);
    const min = parseFloat(input.min);
    const max = parseFloat(input.max);
    
    if (isNaN(value)) {
      this.showError('Veuillez entrer un nombre valide');
      return false;
    }
    
    if (!isNaN(min) && value < min) {
      this.showError(`La valeur minimale est ${min}`);
      return false;
    }
    
    if (!isNaN(max) && value > max) {
      this.showError(`La valeur maximale est ${max}`);
      return false;
    }
    
    return true;
  }
  
  /**
   * Phone number validation
   */
  validatePhone() {
    const input = this.inputTarget;
    if (!input.value.trim()) return true;
    
    // Simple phone validation - adjust regex for your country format
    const phoneRegex = /^[+]?[(]?[0-9]{3}[)]?[-\s.]?[0-9]{3}[-\s.]?[0-9]{4,6}$/;
    if (!phoneRegex.test(input.value.trim())) {
      this.showError('Veuillez entrer un numéro de téléphone valide');
      return false;
    }
    
    return true;
  }
  
  /**
   * URL validation
   */
  validateUrl() {
    const input = this.inputTarget;
    if (!input.value.trim()) return true;
    
    try {
      new URL(input.value);
      return true;
    } catch (e) {
      this.showError('Veuillez entrer une URL valide');
      return false;
    }
  }
  
  /**
   * Password validation
   */
  validatePassword() {
    const input = this.inputTarget;
    if (!input.value) return true;
    
    // Check for password strength attributes
    const minLength = input.getAttribute('data-min-length') || 8;
    const requireUppercase = input.hasAttribute('data-require-uppercase');
    const requireLowercase = input.hasAttribute('data-require-lowercase');
    const requireNumbers = input.hasAttribute('data-require-numbers');
    const requireSpecial = input.hasAttribute('data-require-special');
    
    // Length check
    if (input.value.length < minLength) {
      this.showError(`Le mot de passe doit contenir au moins ${minLength} caractères`);
      return false;
    }
    
    // Uppercase check
    if (requireUppercase && !/[A-Z]/.test(input.value)) {
      this.showError('Le mot de passe doit contenir au moins une lettre majuscule');
      return false;
    }
    
    // Lowercase check
    if (requireLowercase && !/[a-z]/.test(input.value)) {
      this.showError('Le mot de passe doit contenir au moins une lettre minuscule');
      return false;
    }
    
    // Numbers check
    if (requireNumbers && !/[0-9]/.test(input.value)) {
      this.showError('Le mot de passe doit contenir au moins un chiffre');
      return false;
    }
    
    // Special character check
    if (requireSpecial && !/[^A-Za-z0-9]/.test(input.value)) {
      this.showError('Le mot de passe doit contenir au moins un caractère spécial');
      return false;
    }
    
    return true;
  }
  
  /**
   * File validation
   */
  validateFile() {
    const input = this.inputTarget;
    if (!input.files || input.files.length === 0) {
      if (input.hasAttribute('required')) {
        this.showError('Veuillez sélectionner un fichier');
        return false;
      }
      return true;
    }
    
    const file = input.files[0];
    
    // Check file size
    const maxSize = parseInt(input.getAttribute('data-max-size') || '5242880'); // 5MB default
    if (file.size > maxSize) {
      const maxSizeMB = Math.round(maxSize / 1048576);
      this.showError(`Le fichier est trop volumineux. Taille maximale: ${maxSizeMB}MB`);
      return false;
    }
    
    // Check file type
    const acceptAttr = input.getAttribute('accept');
    if (acceptAttr) {
      const acceptedTypes = acceptAttr.split(',').map(type => type.trim());
      const fileType = file.type;
      const fileExt = '.' + file.name.split('.').pop().toLowerCase();
      
      const isAccepted = acceptedTypes.some(type => {
        if (type.startsWith('.')) {
          // Extension check
          return type.toLowerCase() === fileExt;
        } else if (type.includes('*')) {
          // MIME type with wildcard
          const typeRegex = new RegExp('^' + type.replace('*', '.*') + '$');
          return typeRegex.test(fileType);
        } else {
          // Exact MIME type
          return type === fileType;
        }
      });
      
      if (!isAccepted) {
        this.showError('Type de fichier non supporté');
        return false;
      }
    }
    
    return true;
  }
  
  /**
   * General validation (pattern, minlength, maxlength)
   */
  validateGeneral() {
    const input = this.inputTarget;
    
    // Pattern validation
    if (input.pattern && input.value.trim()) {
      const pattern = new RegExp('^' + input.pattern + '$');
      if (!pattern.test(input.value)) {
        this.showError(input.title || 'Format invalide');
        return false;
      }
    }
    
    // Minlength validation
    if (input.minLength && input.value.length < input.minLength && input.value.trim()) {
      this.showError(`Ce champ doit contenir au moins ${input.minLength} caractères`);
      return false;
    }
    
    // Maxlength validation
    if (input.maxLength && input.value.length > input.maxLength) {
      this.showError(`Ce champ ne peut pas dépasser ${input.maxLength} caractères`);
      return false;
    }
    
    return true;
  }
  
  /**
   * Show validation error
   */
  showError(message) {
    const input = this.inputTarget;
    input.classList.add('border-red-500');
    
    // Use error target if available
    if (this.hasErrorTarget) {
      this.errorTarget.textContent = message;
      this.errorTarget.classList.remove('hidden');
    } else {
      // Create error element if not exists
      let errorElement = input.parentElement.querySelector('.error-message');
      if (!errorElement) {
        errorElement = document.createElement('div');
        errorElement.className = 'error-message text-red-500 text-sm mt-1';
        input.parentElement.appendChild(errorElement);
      }
      
      errorElement.textContent = message;
    }
    
    // Dispatch validation error event
    this.element.dispatchEvent(new CustomEvent('validation:error', { 
      bubbles: true,
      detail: { field: this.nameValue, message }
    }));
  }
  
  /**
   * Clear validation error
   */
  clearError() {
    const input = this.inputTarget;
    input.classList.remove('border-red-500');
    
    // Clear error target if available
    if (this.hasErrorTarget) {
      this.errorTarget.textContent = '';
      this.errorTarget.classList.add('hidden');
    } else {
      // Remove error element if exists
      const errorElement = input.parentElement.querySelector('.error-message');
      if (errorElement) {
        errorElement.remove();
      }
    }
    
    // Dispatch validation success event
    this.element.dispatchEvent(new CustomEvent('validation:success', { 
      bubbles: true,
      detail: { field: this.nameValue }
    }));
  }
  
  /**
   * Setup file preview for file inputs
   */
  setupFilePreview() {
    const input = this.inputTarget;
    if (input.type !== 'file') return;
    
    // Check for preview container
    const previewContainerId = input.getAttribute('data-preview');
    if (!previewContainerId) return;
    
    const previewContainer = document.getElementById(previewContainerId);
    if (!previewContainer) return;
    
    // Add change event to show preview
    input.addEventListener('change', () => {
      // Clear existing preview
      previewContainer.innerHTML = '';
      
      if (!input.files || input.files.length === 0) return;
      
      const file = input.files[0];
      
      // Image preview
      if (file.type.match('image.*')) {
        const reader = new FileReader();
        reader.onload = (e) => {
          const img = document.createElement('img');
          img.src = e.target.result;
          img.className = 'max-h-48 max-w-full object-contain mt-2 rounded';
          previewContainer.appendChild(img);
        };
        reader.readAsDataURL(file);
      } else {
        // Generic file info
        const fileInfo = document.createElement('div');
        fileInfo.className = 'text-sm mt-2 p-2 border rounded bg-gray-50';
        fileInfo.innerHTML = `
          <div class="font-medium">${file.name}</div>
          <div class="text-gray-500">${(file.size / 1024).toFixed(2)} KB</div>
        `;
        previewContainer.appendChild(fileInfo);
      }
    });
  }
}