import { Controller } from '@hotwired/stimulus';

/**
 * Modal controller for managing modal dialogs
 */
export default class extends Controller {
  static targets = ['dialog', 'backdrop', 'closeButton', 'content'];
  static values = {
    backdropClose: { type: Boolean, default: true },
    escClose: { type: Boolean, default: true },
    removeOnClose: { type: Boolean, default: false }
  };

  connect() {
    // Setup close button handlers
    if (this.hasCloseButtonTarget) {
      this.closeButtonTargets.forEach(button => {
        button.addEventListener('click', () => this.close());
      });
    }

    // Setup backdrop click handler
    if (this.hasBackdropTarget && this.backdropCloseValue) {
      this.backdropTarget.addEventListener('click', (event) => {
        if (event.target === this.backdropTarget) {
          this.close();
        }
      });
    }

    // Setup ESC key handler
    if (this.escCloseValue) {
      this.escapeHandler = (event) => {
        if (event.key === 'Escape') {
          this.close();
        }
      };
      document.addEventListener('keydown', this.escapeHandler);
    }

    // Initialize focus trap
    this.setupFocusTrap();
  }

  disconnect() {
    // Remove event listeners
    if (this.escapeHandler) {
      document.removeEventListener('keydown', this.escapeHandler);
    }
  }

  // Open the modal
  open() {
    if (this.hasDialogTarget) {
      // Show modal and backdrop
      this.dialogTarget.classList.remove('hidden');
      if (this.hasBackdropTarget) {
        this.backdropTarget.classList.remove('hidden');
      }

      // Add animation classes
      setTimeout(() => {
        this.dialogTarget.classList.add('opacity-100', 'translate-y-0');
        this.dialogTarget.classList.remove('opacity-0', 'translate-y-4');
        
        if (this.hasBackdropTarget) {
          this.backdropTarget.classList.add('opacity-50');
          this.backdropTarget.classList.remove('opacity-0');
        }
      }, 10);

      // Prevent body scrolling
      document.body.classList.add('overflow-hidden');

      // Set focus on first focusable element
      this.trapFocus();

      // Dispatch open event
      this.dispatch('open');
    }
  }

  // Close the modal
  close() {
    if (this.hasDialogTarget) {
      // Add animation classes for closing
      this.dialogTarget.classList.remove('opacity-100', 'translate-y-0');
      this.dialogTarget.classList.add('opacity-0', 'translate-y-4');
      
      if (this.hasBackdropTarget) {
        this.backdropTarget.classList.remove('opacity-50');
        this.backdropTarget.classList.add('opacity-0');
      }

      // Hide after animation completes
      setTimeout(() => {
        this.dialogTarget.classList.add('hidden');
        if (this.hasBackdropTarget) {
          this.backdropTarget.classList.add('hidden');
        }

        // Allow body scrolling again
        document.body.classList.remove('overflow-hidden');

        // Remove from DOM if configured
        if (this.removeOnCloseValue) {
          this.element.remove();
        }

        // Dispatch close event
        this.dispatch('close');
      }, 300);
    }
  }

  // Set content dynamically
  setContent(content) {
    if (this.hasContentTarget) {
      this.contentTarget.innerHTML = content;
      this.setupFocusTrap(); // Re-setup focus trap after content change
    }
  }

  // Setup focus trap for accessibility
  setupFocusTrap() {
    if (!this.hasDialogTarget) return;

    // Get all focusable elements
    this.focusableElements = this.dialogTarget.querySelectorAll(
      'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
    );

    if (this.focusableElements.length > 0) {
      this.firstFocusable = this.focusableElements[0];
      this.lastFocusable = this.focusableElements[this.focusableElements.length - 1];

      // Setup tab key trap
      this.dialogTarget.addEventListener('keydown', (e) => {
        if (e.key === 'Tab') {
          if (e.shiftKey && document.activeElement === this.firstFocusable) {
            e.preventDefault();
            this.lastFocusable.focus();
          } else if (!e.shiftKey && document.activeElement === this.lastFocusable) {
            e.preventDefault();
            this.firstFocusable.focus();
          }
        }
      });
    }
  }

  // Trap focus inside modal
  trapFocus() {
    if (this.firstFocusable) {
      this.previouslyFocused = document.activeElement;
      this.firstFocusable.focus();
    }
  }

  // Restore focus when modal closes
  restoreFocus() {
    if (this.previouslyFocused) {
      this.previouslyFocused.focus();
    }
  }
}
