import { Controller } from '@hotwired/stimulus';

/**
 * Tabs controller for tabbed interfaces
 */
export default class extends Controller {
  static targets = ['tab', 'panel'];
  static values = {
    activeTab: { type: Number, default: 0 },
    activeClass: { type: String, default: 'active' },
    rememberTab: { type: Boolean, default: false }
  };

  connect() {
    // Initialize tabs
    this.initializeTabs();
  }

  initializeTabs() {
    // Check if we should restore tab from storage
    if (this.rememberTabValue) {
      const storedTab = localStorage.getItem(`tab-${this.element.id}`);
      if (storedTab !== null) {
        this.activeTabValue = parseInt(storedTab, 10);
      }
    }

    // Show the active tab
    this.showTab(this.activeTabValue);

    // Add click handlers to tabs
    this.tabTargets.forEach((tab, index) => {
      tab.addEventListener('click', (event) => {
        event.preventDefault();
        this.showTab(index);
      });
    });
  }

  // Show a specific tab by index
  showTab(index) {
    if (!this.hasTabTarget || !this.hasPanelTarget) return;
    if (index < 0 || index >= this.tabTargets.length) return;

    // Update active tab value
    this.activeTabValue = index;

    // Store selected tab if remembering is enabled
    if (this.rememberTabValue && this.element.id) {
      localStorage.setItem(`tab-${this.element.id}`, index);
    }

    // Update tab states
    this.tabTargets.forEach((tab, i) => {
      if (i === index) {
        tab.classList.add(this.activeClassValue);
        tab.setAttribute('aria-selected', 'true');
      } else {
        tab.classList.remove(this.activeClassValue);
        tab.setAttribute('aria-selected', 'false');
      }
    });

    // Update panel visibility
    this.panelTargets.forEach((panel, i) => {
      if (i === index) {
        panel.classList.remove('hidden');
      } else {
        panel.classList.add('hidden');
      }
    });

    // Dispatch event with active tab index
    this.dispatch('tabChange', { detail: { index } });
  }

  // Show a tab by its id or name
  showTabById(event) {
    const id = event.detail?.id || event.target.dataset.tabId;
    if (!id) return;

    const index = this.tabTargets.findIndex(tab => 
      tab.id === id || tab.dataset.tabName === id
    );

    if (index !== -1) {
      this.showTab(index);
    }
  }

  // Get the active tab index
  get activeTabIndex() {
    return this.activeTabValue;
  }

  // Get the active tab element
  get activeTab() {
    return this.tabTargets[this.activeTabValue];
  }

  // Get the active panel element
  get activePanel() {
    return this.panelTargets[this.activeTabValue];
  }
}
