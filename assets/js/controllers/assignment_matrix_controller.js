import { Controller } from '@hotwired/stimulus';

/**
 * Assignment Matrix controller
 * Handles the interactive assignment matrix for student-teacher matching
 */
export default class extends Controller {
  static values = {
    students: Array,
    teachers: Array,
    assignments: Object,
    weights: Object,
    editable: Boolean,
    updateUrl: String
  };

  connect() {
    this.initialize();
  }

  initialize() {
    // Initialize any additional setup for the matrix
    this.updateCapacityIndicators();
  }

  /**
   * Toggle an assignment when a cell is clicked
   */
  toggleAssignment(event) {
    if (!this.editableValue) return;

    const cell = event.currentTarget;
    const studentId = cell.dataset.studentId;
    const teacherId = cell.dataset.teacherId;
    
    // Get current assignments
    const assignments = {...this.assignmentsValue};
    
    // Check if already assigned
    const isAssigned = assignments[studentId] === teacherId;
    
    if (isAssigned) {
      // Remove assignment
      delete assignments[studentId];
    } else {
      // Add assignment (removing any existing assignment for this student)
      assignments[studentId] = teacherId;
    }
    
    // Update assignments
    this.assignmentsValue = assignments;
    
    // Update the UI
    this.updateAssignmentUI();
    
    // Send update to server if URL is provided
    if (this.hasUpdateUrlValue) {
      this.saveAssignments();
    }
  }

  /**
   * Update the UI to reflect current assignments
   */
  updateAssignmentUI() {
    // Clear all assigned indicators
    this.element.querySelectorAll('.assigned-cell').forEach(cell => {
      cell.classList.remove('assigned-cell');
      cell.querySelector('svg')?.remove();
    });
    
    // Add assigned indicators
    const assignments = this.assignmentsValue;
    Object.entries(assignments).forEach(([studentId, teacherId]) => {
      const cell = this.element.querySelector(`td[data-student-id="${studentId}"][data-teacher-id="${teacherId}"]`);
      if (cell) {
        cell.classList.add('assigned-cell');
        
        // Add checkmark if it doesn't exist
        if (!cell.querySelector('svg')) {
          const checkmark = document.createElement('div');
          checkmark.className = 'flex items-center justify-center';
          checkmark.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-success-500" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
          `;
          
          // Insert at beginning of cell
          cell.insertBefore(checkmark, cell.firstChild);
        }
      }
    });
    
    this.updateCapacityIndicators();
  }

  /**
   * Update capacity indicators in the footer row
   */
  updateCapacityIndicators() {
    const assignments = this.assignmentsValue;
    const teachers = this.teachersValue;
    
    // Count assignments per teacher
    const assignmentCounts = {};
    Object.values(assignments).forEach(teacherId => {
      assignmentCounts[teacherId] = (assignmentCounts[teacherId] || 0) + 1;
    });
    
    // Update footer cells
    const footerRow = this.element.querySelector('tfoot tr');
    if (footerRow) {
      const cells = footerRow.querySelectorAll('th:not(:first-child)');
      cells.forEach((cell, index) => {
        const teacher = teachers[index];
        if (teacher) {
          const teacherId = teacher.id;
          const assignedCount = assignmentCounts[teacherId] || 0;
          const capacity = teacher.capacity || 0;
          
          // Update count
          cell.textContent = capacity ? `${assignedCount} / ${capacity}` : assignedCount;
          
          // Update styling
          cell.className = 'border border-gray-200 px-4 py-2 text-center text-xs font-medium';
          if (capacity > 0) {
            if (assignedCount > capacity) {
              cell.classList.add('text-danger-500', 'font-bold');
            } else if (assignedCount === capacity) {
              cell.classList.add('text-success-500', 'font-bold');
            }
          }
        }
      });
    }
  }

  /**
   * Save assignments to the server
   */
  saveAssignments() {
    if (!this.hasUpdateUrlValue) return;
    
    const assignments = this.assignmentsValue;
    
    fetch(this.updateUrlValue, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({ assignments })
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Show success notification if needed
        this.showNotification('Affectations mises à jour avec succès', 'success');
      } else {
        // Show error notification
        this.showNotification(data.message || 'Erreur lors de la mise à jour des affectations', 'error');
      }
    })
    .catch(error => {
      console.error('Error saving assignments:', error);
      this.showNotification('Erreur lors de la mise à jour des affectations', 'error');
    });
  }

  /**
   * Show a notification message
   */
  showNotification(message, type = 'info') {
    // Dispatch a custom event that can be caught by a notification controller
    const event = new CustomEvent('notification', {
      bubbles: true,
      detail: { message, type }
    });
    this.element.dispatchEvent(event);
    
    // Fallback alert if we're in development
    if (process.env.NODE_ENV === 'development') {
      if (type === 'error') {
        console.error(message);
      } else {
        console.log(message);
      }
    }
  }
}