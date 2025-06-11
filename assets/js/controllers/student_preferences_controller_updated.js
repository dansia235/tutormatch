/**
 * Student Preferences Controller
 * Manages student internship preferences and settings
 */
import { Controller } from "@hotwired/stimulus";
import ApiClient from '../api-client';

export default class extends Controller {
  static targets = [
    "preferencesList", "emptyState", "loadingIndicator", 
    "internshipSearch", "searchResults", "selectedPreferences",
    "saveButton", "maxPreferencesAlert"
  ];
  
  static values = {
    studentId: Number,
    apiUrl: { type: String, default: "/tutoring/api/students" },
    maxPreferences: { type: Number, default: 5 },
    searchDelay: { type: Number, default: 300 } // réduit à 300ms pour une recherche plus réactive
  };
  
  connect() {
    // Initialize the API client
    this.api = new ApiClient();
    
    // Initialize state
    this.preferences = [];
    this.searchTimeout = null;
    this.isLoading = false;
    
    // Load current preferences
    this.loadPreferences();
  }
  
  async loadPreferences() {
    if (this.hasLoadingIndicatorTarget) {
      this.loadingIndicatorTarget.classList.remove("hidden");
    }
    
    try {
      // Fetch student preferences
      const studentId = this.studentIdValue || null;
      
      let response;
      if (studentId) {
        // Fetch specific student preferences
        response = await this.api.get(`students/${studentId}/preferences`);
      } else {
        // Fetch current student preferences
        const studentData = await this.api.get('students/show.php');
        if (studentData && studentData.data) {
          const currentStudentId = studentData.data.id;
          response = await this.api.get(`students/preferences.php?student_id=${currentStudentId}`);
        } else {
          throw new Error("Impossible de récupérer l'ID de l'étudiant courant");
        }
      }
      
      // Store and display preferences
      this.preferences = response.data || [];
      this.updatePreferencesList();
      
    } catch (error) {
      console.error("Error loading preferences:", error);
      if (this.hasSelectedPreferencesTarget) {
        this.selectedPreferencesTarget.innerHTML = `
          <div class="alert alert-danger">
            Erreur lors du chargement des préférences. Veuillez rafraîchir la page.
          </div>
        `;
      }
    } finally {
      if (this.hasLoadingIndicatorTarget) {
        this.loadingIndicatorTarget.classList.add("hidden");
      }
    }
  }
  
  updatePreferencesList() {
    if (!this.hasSelectedPreferencesTarget) return;
    
    const selectedPreferences = this.selectedPreferencesTarget;
    
    // Show empty state if no preferences
    if (this.preferences.length === 0) {
      selectedPreferences.innerHTML = '';
      if (this.hasEmptyStateTarget) {
        this.emptyStateTarget.classList.remove("hidden");
      }
      return;
    }
    
    // Hide empty state if there are preferences
    if (this.hasEmptyStateTarget) {
      this.emptyStateTarget.classList.add("hidden");
    }
    
    // Sort preferences by rank
    this.preferences.sort((a, b) => a.rank - b.rank);
    
    // Clear preferences list
    selectedPreferences.innerHTML = '';
    
    // Add each preference to the list
    this.preferences.forEach((preference, index) => {
      const preferenceElement = this.createPreferenceElement(preference, index + 1);
      selectedPreferences.appendChild(preferenceElement);
    });
    
    // Update save button state
    this.updateSaveButtonState();
  }
  
  createPreferenceElement(preference, rank) {
    const element = document.createElement('div');
    element.className = "d-flex align-items-center p-3 border rounded mb-2 bg-white position-relative preference-item";
    element.dataset.preferenceId = preference.internship_id;
    
    element.innerHTML = `
      <div class="d-flex align-items-center justify-content-center bg-primary text-white rounded-circle me-3" style="width: 32px; height: 32px;">
        ${rank}
      </div>
      <div class="flex-grow-1">
        <h5 class="mb-0">${preference.title || preference.internship_title}</h5>
        <p class="text-muted mb-0">${preference.company || preference.company_name}</p>
      </div>
      <div class="preference-actions">
        ${rank > 1 ? `
        <button type="button" class="btn btn-sm btn-outline-secondary me-1" data-action="student-preferences#movePreferenceUp" data-index="${rank - 1}">
          <i class="bi bi-arrow-up"></i>
        </button>
        ` : ''}
        ${rank < this.preferences.length ? `
        <button type="button" class="btn btn-sm btn-outline-secondary me-1" data-action="student-preferences#movePreferenceDown" data-index="${rank - 1}">
          <i class="bi bi-arrow-down"></i>
        </button>
        ` : ''}
        <button type="button" class="btn btn-sm btn-outline-danger" data-action="student-preferences#removePreference" data-index="${rank - 1}">
          <i class="bi bi-x"></i>
        </button>
      </div>
    `;
    
    return element;
  }
  
  movePreferenceUp(event) {
    const index = parseInt(event.currentTarget.dataset.index);
    if (index <= 0 || index >= this.preferences.length) return;
    
    // Swap preferences
    [this.preferences[index], this.preferences[index - 1]] = 
    [this.preferences[index - 1], this.preferences[index]];
    
    // Update ranks
    this.updatePreferenceRanks();
    
    // Update UI
    this.updatePreferencesList();
  }
  
  movePreferenceDown(event) {
    const index = parseInt(event.currentTarget.dataset.index);
    if (index < 0 || index >= this.preferences.length - 1) return;
    
    // Swap preferences
    [this.preferences[index], this.preferences[index + 1]] = 
    [this.preferences[index + 1], this.preferences[index]];
    
    // Update ranks
    this.updatePreferenceRanks();
    
    // Update UI
    this.updatePreferencesList();
  }
  
  removePreference(event) {
    const index = parseInt(event.currentTarget.dataset.index);
    if (index < 0 || index >= this.preferences.length) return;
    
    // Remove preference
    this.preferences.splice(index, 1);
    
    // Update ranks
    this.updatePreferenceRanks();
    
    // Update UI
    this.updatePreferencesList();
    
    // Hide max preferences alert if shown
    if (this.hasMaxPreferencesAlertTarget) {
      this.maxPreferencesAlertTarget.classList.add('hidden');
    }
  }
  
  updatePreferenceRanks() {
    // Update ranks based on array position
    this.preferences.forEach((preference, index) => {
      preference.rank = index + 1;
    });
  }
  
  search() {
    if (!this.hasInternshipSearchTarget) return;
    
    const query = this.internshipSearchTarget.value.trim();
    
    // Clear previous timeout
    if (this.searchTimeout) {
      clearTimeout(this.searchTimeout);
    }
    
    // Don't search if query is empty or too short
    if (query.length === 0) {
      if (this.hasSearchResultsTarget) {
        this.searchResultsTarget.innerHTML = '';
      }
      return;
    }
    
    // Si c'est une recherche par lettre, on commence dès le premier caractère
    if (query.length >= 1) {
      // Set a timeout to avoid too many API calls while typing
      this.searchTimeout = setTimeout(() => {
        this.performSearch(query);
      }, this.searchDelayValue);
    }
  }
  
  async performSearch(query) {
    if (this.isLoading) return;
    this.isLoading = true;
    
    if (this.hasSearchResultsTarget) {
      this.searchResultsTarget.innerHTML = `
        <div class="p-3 text-center">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Recherche en cours...</span>
          </div>
          <p class="mt-2 text-muted">Recherche en cours...</p>
        </div>
      `;
    }
    
    try {
      // Utilisation de l'API de recherche directe
      const response = await fetch(`/tutoring/api/internships/search.php?term=${encodeURIComponent(query)}`);
      if (!response.ok) {
        throw new Error(`Erreur HTTP: ${response.status}`);
      }
      
      const data = await response.json();
      if (data.success) {
        // Display search results
        this.displaySearchResults(data.data || []);
      } else {
        throw new Error(data.message || "Erreur lors de la recherche");
      }
      
    } catch (error) {
      console.error("Error searching internships:", error);
      if (this.hasSearchResultsTarget) {
        this.searchResultsTarget.innerHTML = `
          <div class="alert alert-danger">
            Erreur lors de la recherche. Veuillez réessayer.
          </div>
        `;
      }
    } finally {
      this.isLoading = false;
    }
  }
  
  displaySearchResults(internships) {
    if (!this.hasSearchResultsTarget) return;
    
    const searchResults = this.searchResultsTarget;
    
    // No results
    if (internships.length === 0) {
      searchResults.innerHTML = `
        <div class="alert alert-info">
          <i class="bi bi-info-circle me-2"></i>Aucun stage trouvé.
        </div>
      `;
      return;
    }
    
    // Get IDs of already selected preferences
    const selectedIds = this.preferences.map(p => p.internship_id.toString());
    
    // Clear previous results
    searchResults.innerHTML = '';
    
    // Add each internship to the results
    internships.forEach(internship => {
      const isSelected = selectedIds.includes(internship.id.toString());
      const element = document.createElement('div');
      element.className = `p-3 border-bottom ${isSelected ? 'bg-light' : 'hover-bg-light'} cursor-pointer`;
      
      if (!isSelected) {
        element.dataset.action = "click->student-preferences#addPreference";
        element.dataset.internshipId = internship.id;
        element.dataset.internshipTitle = internship.title;
        element.dataset.internshipCompany = internship.company.name;
      }
      
      element.innerHTML = `
        <div class="d-flex align-items-center">
          <div class="flex-grow-1">
            <h5 class="mb-0">${internship.title}</h5>
            <p class="text-muted mb-0">${internship.company.name}</p>
            <div class="small text-muted mt-1">${internship.domain} • ${internship.location}</div>
          </div>
          <div class="ms-3">
            ${isSelected ? `
              <span class="badge bg-success">Sélectionné</span>
            ` : `
              <button type="button" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-plus-lg"></i> Ajouter
              </button>
            `}
          </div>
        </div>
      `;
      
      searchResults.appendChild(element);
    });
  }
  
  addPreference(event) {
    // Check if maximum preferences reached
    if (this.preferences.length >= this.maxPreferencesValue) {
      if (this.hasMaxPreferencesAlertTarget) {
        this.maxPreferencesAlertTarget.classList.remove('hidden');
      }
      return;
    }
    
    const element = event.currentTarget;
    const internshipId = element.dataset.internshipId;
    const internshipTitle = element.dataset.internshipTitle;
    const internshipCompany = element.dataset.internshipCompany;
    
    // Check if already in preferences
    const existingIndex = this.preferences.findIndex(p => p.internship_id.toString() === internshipId.toString());
    if (existingIndex !== -1) return;
    
    // Add to preferences
    this.preferences.push({
      internship_id: internshipId,
      title: internshipTitle,
      company: internshipCompany,
      rank: this.preferences.length + 1
    });
    
    // Update UI
    this.updatePreferencesList();
    
    // Clear search
    if (this.hasInternshipSearchTarget) {
      this.internshipSearchTarget.value = '';
    }
    if (this.hasSearchResultsTarget) {
      this.searchResultsTarget.innerHTML = '';
    }
  }
  
  updateSaveButtonState() {
    if (!this.hasSaveButtonTarget) return;
    
    // Enable save button if there are preferences
    this.saveButtonTarget.disabled = this.preferences.length === 0;
  }
  
  async savePreferences() {
    if (this.isLoading || this.preferences.length === 0) return;
    this.isLoading = true;
    
    // Disable save button and show loading state
    if (this.hasSaveButtonTarget) {
      const saveButton = this.saveButtonTarget;
      const originalText = saveButton.innerHTML;
      saveButton.disabled = true;
      saveButton.innerHTML = `
        <div class="spinner-border spinner-border-sm me-2" role="status">
          <span class="visually-hidden">Enregistrement...</span>
        </div>
        Enregistrement...
      `;
      
      try {
        // Prepare data for API
        const preferencesData = this.preferences.map(p => ({
          internship_id: p.internship_id,
          rank: p.rank
        }));
        
        // Save preferences
        const studentId = this.studentIdValue || 'current';
        await this.api.post(`students/preferences.php?student_id=${studentId}`, { preferences: preferencesData });
        
        // Show success message
        this.showNotification("Préférences enregistrées avec succès", "success");
        
      } catch (error) {
        console.error("Error saving preferences:", error);
        this.showNotification(error.message || "Erreur lors de l'enregistrement des préférences", "error");
      } finally {
        // Reset save button
        saveButton.disabled = false;
        saveButton.innerHTML = originalText;
        this.isLoading = false;
      }
    }
  }
  
  showNotification(message, type = "info") {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `position-fixed bottom-0 end-0 p-3`;
    notification.style.zIndex = 1050;
    
    // Set the appropriate bootstrap class based on type
    const alertClass = type === 'success' ? 'alert-success' : 
                       type === 'error' ? 'alert-danger' : 
                       'alert-info';
    
    notification.innerHTML = `
      <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'} text-white">
          <strong class="me-auto">${type === 'success' ? 'Succès' : type === 'error' ? 'Erreur' : 'Information'}</strong>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
          ${message}
        </div>
      </div>
    `;
    
    // Add to document
    document.body.appendChild(notification);
    
    // Remove after 5 seconds
    setTimeout(() => {
      notification.remove();
    }, 5000);
  }
}