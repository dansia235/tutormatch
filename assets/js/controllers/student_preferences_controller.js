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
    searchDelay: { type: Number, default: 100 } // réduit à 100ms pour une recherche encore plus réactive
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
    
    // Debug log
    console.log("Student Preferences Controller connected");
  }
  
  async loadPreferences() {
    if (this.hasLoadingIndicatorTarget) {
      this.loadingIndicatorTarget.classList.remove("hidden");
    }
    
    try {
      // Fetch student preferences
      const studentId = this.studentIdValue || null;
      console.log("Loading preferences for student ID:", studentId);
      
      let response;
      try {
        if (studentId) {
          // Fetch specific student preferences
          response = await this.api.get(`students/${studentId}/preferences`);
        } else {
          // Fetch current student preferences
          const studentData = await this.api.get('students/show.php');
          if (studentData && studentData.data) {
            const currentStudentId = studentData.data.id;
            // Utiliser le endpoint direct avec fetch pour éviter les problèmes
            const preferenceResponse = await fetch(`/tutoring/api/students/preferences.php?student_id=${currentStudentId}`);
            if (!preferenceResponse.ok) {
              throw new Error(`Error fetching preferences: ${preferenceResponse.status}`);
            }
            response = await preferenceResponse.json();
            console.log("Raw preference response:", response);
          } else {
            throw new Error("Impossible de récupérer l'ID de l'étudiant courant");
          }
        }
      } catch (fetchError) {
        console.error("Fetch error:", fetchError);
        throw new Error(`Erreur lors de la récupération des préférences: ${fetchError.message}`);
      }
      
      // Check if response is valid
      if (response && typeof response === 'object') {
        console.log("Preferences response:", response);
        
        // Map to correct format if needed
        let preferences = [];
        if (Array.isArray(response.data)) {
          // Direct data array
          preferences = response.data;
        } else if (Array.isArray(response)) {
          // Response is directly an array
          preferences = response;
        }
        
        // Normalize preferences data to ensure required fields
        this.preferences = preferences.map(pref => {
          return {
            internship_id: pref.internship_id,
            title: pref.title || pref.internship_title || "Stage sans titre",
            company: pref.company_name || pref.company || "Entreprise non spécifiée",
            rank: pref.rank || pref.preference_order || 1
          };
        });
        
        console.log("Normalized preferences:", this.preferences);
        this.updatePreferencesList();
      } else {
        console.error("Invalid preferences response:", response);
        throw new Error("Format de réponse invalide pour les préférences");
      }
      
    } catch (error) {
      console.error("Error loading preferences:", error);
      if (this.hasSelectedPreferencesTarget) {
        this.selectedPreferencesTarget.innerHTML = `
          <div class="alert alert-danger">
            <strong>Erreur lors du chargement des préférences:</strong> ${error.message}
            <p class="small mt-2">Veuillez rafraîchir la page ou contacter le support.</p>
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
    
    // Debug log
    console.log("Updating preferences list with", this.preferences.length, "items");
    
    // Show empty state if no preferences
    if (!Array.isArray(this.preferences) || this.preferences.length === 0) {
      console.log("No preferences found, showing empty state");
      selectedPreferences.innerHTML = '';
      if (this.hasEmptyStateTarget) {
        this.emptyStateTarget.classList.remove("hidden");
      }
      return;
    }
    
    // Check if preferences have required properties
    let validPreferences = this.preferences.filter(p => 
      p && typeof p === 'object' && p.internship_id && (p.title || p.internship_title)
    );
    
    // If no valid preferences, show empty state
    if (validPreferences.length === 0) {
      console.log("No valid preferences found, showing empty state");
      selectedPreferences.innerHTML = '';
      if (this.hasEmptyStateTarget) {
        this.emptyStateTarget.classList.remove("hidden");
      }
      return;
    }
    
    // We have valid preferences, hide empty state
    if (this.hasEmptyStateTarget) {
      this.emptyStateTarget.classList.add("hidden");
    }
    
    // Sort preferences by rank
    validPreferences.sort((a, b) => {
      const rankA = a.rank || a.preference_order || 1;
      const rankB = b.rank || b.preference_order || 1;
      return rankA - rankB;
    });
    
    // Clear preferences list
    selectedPreferences.innerHTML = '';
    
    // Add each preference to the list
    validPreferences.forEach((preference, index) => {
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
    
    // Ensure valid preference properties
    const title = preference.title || preference.internship_title || "Stage sans titre";
    const company = preference.company || preference.company_name || "Entreprise non spécifiée";
    const reason = preference.reason || "";
    
    element.innerHTML = `
      <div class="d-flex align-items-center justify-content-center bg-primary text-white rounded-circle me-3" style="width: 32px; height: 32px;">
        ${rank}
      </div>
      <div class="flex-grow-1">
        <h5 class="mb-0">${title}</h5>
        <p class="text-muted mb-0">${company}</p>
        ${reason ? `<p class="small text-muted mt-1"><em>Raison: ${reason}</em></p>` : ''}
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
        <button type="button" class="btn btn-sm btn-outline-secondary me-1" data-action="student-preferences#editReason" data-index="${rank - 1}">
          <i class="bi bi-pencil"></i>
        </button>
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
    console.log("Search query:", query);
    
    // Clear previous timeout
    if (this.searchTimeout) {
      clearTimeout(this.searchTimeout);
    }
    
    // Pour les recherches vides, on affiche les stages récents au lieu de vider
    if (query.length === 0) {
      // Set a timeout to avoid too many API calls while typing
      this.searchTimeout = setTimeout(() => {
        // Recherche avec terme vide = récupérer quelques stages récents
        this.performSearch('');
      }, this.searchDelayValue);
      return;
    }
    
    // Toujours effectuer la recherche, même avec un seul caractère
    // Réduire le délai pour une recherche plus réactive
    const delay = query.length === 1 ? this.searchDelayValue : Math.max(50, this.searchDelayValue - 50);
    
    this.searchTimeout = setTimeout(() => {
      this.performSearch(query);
    }, delay);
  }
  
  async performSearch(query) {
    if (this.isLoading) return;
    this.isLoading = true;
    
    // Pour des recherches rapides, on utilise un loader plus discret si la recherche est courte
    if (this.hasSearchResultsTarget) {
      if (query.length <= 2) {
        // Mini loader pour les recherches courtes
        this.searchResultsTarget.innerHTML = `
          <div class="p-2 text-center">
            <div class="spinner-border spinner-border-sm text-primary" role="status">
              <span class="visually-hidden">Recherche en cours...</span>
            </div>
            <span class="ms-2 text-muted small">Recherche en cours...</span>
          </div>
        `;
      } else {
        this.searchResultsTarget.innerHTML = `
          <div class="p-3 text-center">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Recherche en cours...</span>
            </div>
            <p class="mt-2 text-muted">Recherche en cours pour "${query}"...</p>
          </div>
        `;
      }
    }
    
    try {
      // Enregistrement détaillé pour le débogage
      console.log("Performing search with query:", query);
      
      // Ajouter un paramètre limit pour les recherches par préfixe
      const limit = query.length < 3 ? 15 : 20; // Réduire le nombre de résultats pour les recherches courtes
      console.log("Search URL:", `/tutoring/api/internships/search.php?term=${encodeURIComponent(query)}&limit=${limit}`);
      
      // Utilisation directe de fetch pour éviter les problèmes avec l'API client
      const response = await fetch(`/tutoring/api/internships/search.php?term=${encodeURIComponent(query)}&limit=${limit}`);
      console.log("Search response status:", response.status);
      
      if (!response.ok) {
        throw new Error(`Erreur HTTP: ${response.status} - ${response.statusText}`);
      }
      
      // Récupérer la réponse JSON directement
      const data = await response.json();
      console.log("Search found", data.count, "results");
      
      if (data.success) {
        // Mettre à jour l'URL de la dernière recherche pour référence
        this.lastSearchQuery = query;
        
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
            <strong>Erreur lors de la recherche:</strong> ${error.message}
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
    console.log("Displaying search results:", internships.length);
    
    // No results
    if (internships.length === 0) {
      // Personnaliser le message en fonction de la requête
      const query = this.lastSearchQuery || '';
      
      if (query.length === 1) {
        searchResults.innerHTML = `
          <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>Aucun stage trouvé commençant par "${query}".
            <p class="small mt-2 mb-0">Continuez à taper pour affiner votre recherche.</p>
          </div>
        `;
      } else if (query.length > 1) {
        searchResults.innerHTML = `
          <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>Aucun stage trouvé pour "${query}".
            <p class="small mt-2 mb-0">Essayez d'autres termes de recherche ou consultez tous les stages disponibles.</p>
          </div>
        `;
      } else {
        searchResults.innerHTML = `
          <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>Aucun stage trouvé.
            <p class="small mt-2 mb-0">Commencez à taper pour rechercher un stage.</p>
          </div>
        `;
      }
      return;
    }
    
    // Get IDs of already selected preferences
    const selectedIds = this.preferences.map(p => p.internship_id.toString());
    
    // Clear previous results
    searchResults.innerHTML = '';
    
    // Add header to results
    const resultsHeader = document.createElement('div');
    resultsHeader.className = 'p-2 bg-light border-bottom';
    
    // Personnaliser le message d'en-tête en fonction de la requête
    const query = this.lastSearchQuery || '';
    let headerMessage = '';
    
    if (query.length === 0) {
      headerMessage = 'Stages récents';
    } else if (query.length === 1) {
      headerMessage = `Stages commençant par "${query}"`;
    } else {
      headerMessage = `Résultats pour "${query}"`;
    }
    
    resultsHeader.innerHTML = `
      <div class="d-flex justify-content-between align-items-center">
        <span><strong>${internships.length}</strong> ${headerMessage}</span>
        <small class="text-muted">Cliquez pour ajouter aux préférences</small>
      </div>
    `;
    searchResults.appendChild(resultsHeader);
    
    // Add each internship to the results
    internships.forEach(internship => {
      try {
        const isSelected = selectedIds.includes(internship.id.toString());
        const element = document.createElement('div');
        element.className = `p-3 border-bottom ${isSelected ? 'bg-light' : 'hover-bg-light'} cursor-pointer`;
        
        if (!isSelected) {
          element.dataset.action = "click->student-preferences#addPreference";
          element.dataset.internshipId = internship.id;
          element.dataset.internshipTitle = internship.title;
          element.dataset.internshipCompany = internship.company.name;
        }
        
        // Check for missing properties and provide defaults
        const domain = internship.domain || 'Non spécifié';
        const location = internship.location || 'Non spécifié';
        const companyName = internship.company?.name || 'Entreprise non spécifiée';
        
        // Mise en évidence du terme recherché dans le titre
        let highlightedTitle = internship.title;
        if (query && query.length > 0) {
          // Échapper les caractères spéciaux pour éviter des problèmes avec RegExp
          const escapedQuery = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
          const regex = new RegExp(`^(${escapedQuery})`, 'i');
          highlightedTitle = internship.title.replace(regex, '<span class="highlight-search">$1</span>');
        }
        
        element.innerHTML = `
          <div class="d-flex align-items-center">
            <div class="flex-grow-1">
              <h5 class="mb-0">${highlightedTitle}</h5>
              <p class="text-muted mb-0">${companyName}</p>
              <div class="small text-muted mt-1">${domain} • ${location}</div>
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
      } catch (error) {
        console.error("Error displaying internship:", error, internship);
      }
    });
    
    // Ajouter du style pour la mise en évidence
    const style = document.createElement('style');
    style.textContent = `
      .highlight-search {
        background-color: rgba(255, 243, 205, 0.7);
        font-weight: bold;
        padding: 0 2px;
        border-radius: 2px;
      }
    `;
    searchResults.appendChild(style);
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
    
    console.log("Adding preference:", internshipId, internshipTitle, internshipCompany);
    
    // Check if already in preferences
    const existingIndex = this.preferences.findIndex(p => p.internship_id.toString() === internshipId.toString());
    if (existingIndex !== -1) return;
    
    // Add to preferences
    this.preferences.push({
      internship_id: internshipId,
      title: internshipTitle,
      company: internshipCompany,
      rank: this.preferences.length + 1,
      reason: null // Raison sera ajoutée plus tard
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
  
  editReason(event) {
    const index = parseInt(event.currentTarget.dataset.index);
    if (index < 0 || index >= this.preferences.length) return;
    
    const preference = this.preferences[index];
    const currentReason = preference.reason || "";
    
    // Demander la raison à l'utilisateur
    const reason = prompt("Pourquoi avez-vous choisi ce stage? (optionnel)", currentReason);
    
    // Si l'utilisateur annule, on ne fait rien
    if (reason === null) return;
    
    // Mettre à jour la raison
    preference.reason = reason;
    
    // Mettre à jour l'interface
    this.updatePreferencesList();
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