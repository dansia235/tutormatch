/**
 * Dashboard module
 * Responsible for initializing and managing dashboard charts and stats
 */

// Import Chart.js if not already available globally
import Chart from 'chart.js/auto';

// Dashboard initialization
document.addEventListener('DOMContentLoaded', () => {
  // Initialize charts if their containers exist
  initializeDashboardCharts();
  
  // Initialize stat counters with animation
  initializeStatCounters();
});

/**
 * Initialize dashboard charts
 */
function initializeDashboardCharts() {
  // Chart containers to check for
  const chartContainers = [
    'assignmentStatusChart',
    'internshipStatusChart',
    'tutorWorkloadChart',
    'documentCategoriesChart',
    'assignmentsByDepartmentChart'
  ];
  
  // Check and initialize each chart if its container exists
  chartContainers.forEach(containerId => {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    // If the container has data attributes, use them to initialize the chart
    if (container.dataset.chart) {
      try {
        const chartConfig = JSON.parse(container.dataset.chart);
        new Chart(container, chartConfig);
      } catch (error) {
        console.error(`Error initializing chart ${containerId}:`, error);
      }
    }
  });
}

/**
 * Initialize stat counters with animation
 */
function initializeStatCounters() {
  const statValues = document.querySelectorAll('.stat-card .value');
  
  statValues.forEach(statValue => {
    const targetValue = parseFloat(statValue.textContent);
    const isPercentage = statValue.textContent.includes('%');
    
    // Reset to zero for animation
    statValue.textContent = '0' + (isPercentage ? '%' : '');
    
    // Animate count up
    let startValue = 0;
    const duration = 1500;
    const frameDuration = 1000 / 60;
    const totalFrames = Math.round(duration / frameDuration);
    const valueIncrement = targetValue / totalFrames;
    
    const counter = setInterval(() => {
      startValue += valueIncrement;
      
      if (startValue >= targetValue) {
        statValue.textContent = targetValue.toFixed(isPercentage ? 1 : 0) + (isPercentage ? '%' : '');
        clearInterval(counter);
      } else {
        statValue.textContent = Math.floor(startValue).toFixed(0) + (isPercentage ? '%' : '');
      }
    }, frameDuration);
  });
  
  // Animate progress bars
  document.querySelectorAll('.progress-bar').forEach(bar => {
    const targetWidth = bar.style.width;
    bar.style.width = '0%';
    
    setTimeout(() => {
      bar.style.width = targetWidth;
    }, 300);
  });
}
