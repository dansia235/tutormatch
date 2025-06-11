import { Controller } from '@hotwired/stimulus';
import Chart from 'chart.js/auto';

/**
 * Chart controller for managing Chart.js instances
 */
export default class extends Controller {
  static targets = ['canvas'];
  static values = {
    type: String,
    data: Object,
    options: Object,
    config: Object
  };

  connect() {
    this.initializeChart();
  }

  disconnect() {
    // Destroy chart when controller is disconnected to prevent memory leaks
    if (this.chart) {
      this.chart.destroy();
      this.chart = null;
    }
  }

  initializeChart() {
    if (!this.hasCanvasTarget) return;

    // Use either the new values approach or the legacy config approach
    if (this.hasConfigValue) {
      // Legacy approach
      const config = this.configValue;
      const data = this.hasDataValue ? this.dataValue : {};

      // Create chart configuration by merging data and config
      const chartConfig = {
        ...config,
        data: data
      };

      // Create chart instance
      this.chart = new Chart(this.canvasTarget, chartConfig);
    } else {
      // New approach with separate type, data, and options
      // Default chart options
      const defaultOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'top',
          },
          tooltip: {
            mode: 'index',
            intersect: false,
          },
        }
      };
      
      // Merge default options with provided options
      const options = {...defaultOptions, ...this.optionsValue};
      
      // Create chart instance
      this.chart = new Chart(this.canvasTarget, {
        type: this.typeValue || 'bar',
        data: this.dataValue || {
          labels: [],
          datasets: []
        },
        options: options
      });
    }
  }

  // Update chart data
  updateData(event) {
    if (!this.chart) return;

    let newData;
    if (event && (event.detail?.data || event.detail)) {
      // Handle event-based updates
      newData = event.detail?.data || event.detail;
      newData = typeof newData === 'object' ? newData : JSON.parse(newData);
    } else if (typeof event === 'object') {
      // Handle direct object updates
      newData = event;
    }

    if (!newData) return;

    // Update chart datasets
    this.chart.data = newData;
    this.chart.update();
  }

  // Update chart options
  updateOptions(options) {
    if (!this.chart) return;
    
    this.optionsValue = options;
    
    // Update chart options
    this.chart.options = {...this.chart.options, ...options};
    this.chart.update();
  }

  // Refresh the chart (e.g., after container resize)
  refresh() {
    if (this.chart) {
      this.chart.resize();
      this.chart.update();
    }
  }

  // Update chart type
  updateType(type) {
    if (!this.chart) return;
    
    this.typeValue = type;
    
    // Destroy existing chart and create a new one with the new type
    this.chart.destroy();
    this.initializeChart();
  }

  // Generate chart image data URL
  toImage() {
    if (!this.chart) return null;
    return this.chart.toBase64Image();
  }

  // Download chart as image
  download(filename = 'chart.png') {
    const image = this.toImage();
    if (!image) return;
    
    const link = document.createElement('a');
    link.download = filename;
    link.href = image;
    link.click();
  }
}