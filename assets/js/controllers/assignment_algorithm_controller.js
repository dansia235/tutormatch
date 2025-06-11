import { Controller } from '@hotwired/stimulus';
import { runAssignment } from '../modules/assignment-algorithms';

/**
 * Assignment Algorithm controller
 * Handles the execution of assignment algorithms and displaying results
 */
export default class extends Controller {
  static targets = ['matrix', 'stats', 'capacityChart'];
  static values = {
    students: Array,
    teachers: Array,
    weights: Object,
    algorithm: { type: String, default: 'greedy' },
    preferenceWeight: { type: Number, default: 0.7 },
    capacityWeight: { type: Number, default: 0.3 }
  };

  connect() {
    this.initialize();
  }

  initialize() {
    // Set up any listeners for parameter changes
    this.setupEventListeners();
  }

  /**
   * Set up event listeners for parameter changes
   */
  setupEventListeners() {
    // Listen for algorithm type changes
    const algorithmSelect = document.getElementById('algorithm-type');
    if (algorithmSelect) {
      algorithmSelect.addEventListener('change', (e) => {
        this.algorithmValue = e.target.value;
      });
    }
    
    // Listen for preference weight changes
    const preferenceWeightInput = document.getElementById('weight-preference');
    const preferenceWeightValue = document.getElementById('weight-preference-value');
    
    if (preferenceWeightInput) {
      preferenceWeightInput.addEventListener('input', (e) => {
        const value = parseFloat(e.target.value);
        this.preferenceWeightValue = value;
        
        // Update display value if it exists
        if (preferenceWeightValue) {
          preferenceWeightValue.textContent = value.toFixed(1);
        }
      });
    }
    
    // Listen for capacity weight changes
    const capacityWeightInput = document.getElementById('weight-capacity');
    const capacityWeightValue = document.getElementById('weight-capacity-value');
    
    if (capacityWeightInput) {
      capacityWeightInput.addEventListener('input', (e) => {
        const value = parseFloat(e.target.value);
        this.capacityWeightValue = value;
        
        // Update display value if it exists
        if (capacityWeightValue) {
          capacityWeightValue.textContent = value.toFixed(1);
        }
      });
    }
    
    // Listen for run algorithm button
    const runButton = document.getElementById('run-algorithm');
    if (runButton) {
      runButton.addEventListener('click', () => {
        this.runAlgorithm();
      });
    }
    
    // Listen for reset button
    const resetButton = document.getElementById('reset-assignments');
    if (resetButton) {
      resetButton.addEventListener('click', () => {
        this.resetAssignments();
      });
    }
    
    // Listen for save button
    const saveButton = document.getElementById('save-assignments');
    if (saveButton) {
      saveButton.addEventListener('click', () => {
        this.saveAssignments();
      });
    }
  }

  /**
   * Run the selected assignment algorithm
   */
  runAlgorithm() {
    try {
      // Get current parameter values
      const algorithm = this.algorithmValue;
      const preferenceWeight = this.preferenceWeightValue;
      const capacityWeight = this.capacityWeightValue;
      
      // Run the algorithm
      const result = runAssignment(
        algorithm,
        this.studentsValue,
        this.teachersValue,
        this.weightsValue,
        {
          preferenceWeight,
          capacityWeight
        }
      );
      
      // Update UI with results
      this.updateAssignmentMatrix(result.assignments);
      this.updateStatistics(result.stats);
      
      // Show success notification
      this.showNotification('Algorithme exécuté avec succès', 'success');
      
    } catch (error) {
      console.error('Error running assignment algorithm:', error);
      this.showNotification('Erreur lors de l\'exécution de l\'algorithme: ' + error.message, 'error');
    }
  }

  /**
   * Update assignment matrix with algorithm results
   */
  updateAssignmentMatrix(assignments) {
    if (!this.hasMatrixTarget) return;
    
    // Get the matrix controller if it exists
    const matrixController = this.application.getControllerForElementAndIdentifier(
      this.matrixTarget,
      'assignment-matrix'
    );
    
    if (matrixController) {
      // Update assignments via the matrix controller
      matrixController.assignmentsValue = assignments;
      matrixController.updateAssignmentUI();
    }
  }

  /**
   * Update statistics display with algorithm results
   */
  updateStatistics(stats) {
    if (!this.hasStatsTarget) return;
    
    // Update statistics text
    this.statsTarget.querySelector('.assigned-count').textContent = stats.assignedCount;
    this.statsTarget.querySelector('.unassigned-count').textContent = stats.unassignedCount;
    this.statsTarget.querySelector('.assignment-rate').textContent = `${(stats.assignmentRate * 100).toFixed(0)}%`;
    this.statsTarget.querySelector('.average-score').textContent = stats.averageScore.toFixed(2);
    
    // Update capacity chart if it exists
    this.updateCapacityChart(stats.teacherLoads);
  }

  /**
   * Update capacity chart with new data
   */
  updateCapacityChart(teacherLoads) {
    if (!this.hasCapacityChartTarget) return;
    
    // Get chart.js instance
    const chartCanvas = this.capacityChartTarget;
    const chartController = this.application.getControllerForElementAndIdentifier(
      chartCanvas.closest('[data-controller="chart"]'),
      'chart'
    );
    
    if (!chartController || !chartController.chart) return;
    
    // Prepare new data for the chart
    const chart = chartController.chart;
    const teachers = this.teachersValue;
    
    // Update the assigned students dataset
    chart.data.datasets[0].data = teachers.map(teacher => 
      teacherLoads[teacher.id] || 0
    );
    
    // Update the chart
    chart.update();
  }

  /**
   * Reset all assignments
   */
  resetAssignments() {
    if (!this.hasMatrixTarget) return;
    
    // Get the matrix controller
    const matrixController = this.application.getControllerForElementAndIdentifier(
      this.matrixTarget,
      'assignment-matrix'
    );
    
    if (matrixController) {
      // Clear assignments
      matrixController.assignmentsValue = {};
      matrixController.updateAssignmentUI();
      
      // Update statistics
      const stats = {
        assignedCount: 0,
        unassignedCount: this.studentsValue.length,
        assignmentRate: 0,
        averageScore: 0,
        teacherLoads: {}
      };
      
      this.updateStatistics(stats);
      
      // Show notification
      this.showNotification('Affectations réinitialisées', 'info');
    }
  }

  /**
   * Save current assignments
   */
  saveAssignments() {
    if (!this.hasMatrixTarget) return;
    
    // Get the matrix controller
    const matrixController = this.application.getControllerForElementAndIdentifier(
      this.matrixTarget,
      'assignment-matrix'
    );
    
    if (matrixController) {
      // Get current assignments
      const assignments = matrixController.assignmentsValue;
      
      // Save assignments (would normally be via API)
      if (matrixController.hasUpdateUrlValue) {
        matrixController.saveAssignments();
      } else {
        // Show success notification
        this.showNotification('Affectations enregistrées avec succès', 'success');
        
        // Log assignments to console in development
        if (process.env.NODE_ENV === 'development') {
          console.log('Saved assignments:', assignments);
        }
      }
    }
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