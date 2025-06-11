/**
 * Settings Controller
 * Gère les fonctionnalités de la page de paramètres en utilisant les API
 */
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
  static targets = [
    "form", 
    "themeRadio",
    "colorRadio",
    "fontSizeSlider",
    "fontSizeValue",
    "preferencesForm",
    "systemSettingsForm",
    "loginHistoryContainer",
    "rangeValue"
  ];
  
  static values = {
    userId: Number
  }
  
  connect() {
    // Charger les données initiales
    this.loadUserPreferences();
    this.loadSystemSettings();
    this.loadLoginHistory();
    
    // Initialiser les écouteurs d'événements pour les sliders
    this.initRangeInputs();
  }
  
  initRangeInputs() {
    // Pour chaque slider avec un affichage de valeur
    this.rangeValueTargets.forEach(valueSpan => {
      const sliderId = valueSpan.dataset.slider;
      const slider = document.getElementById(sliderId);
      
      if (slider) {
        // Mettre à jour la valeur initiale
        valueSpan.textContent = slider.value;
        
        // Ajouter un écouteur pour mettre à jour la valeur lors du déplacement
        slider.addEventListener('input', () => {
          valueSpan.textContent = slider.value;
        });
      }
    });
    
    // Cas spécial pour le slider de taille de police
    if (this.hasFontSizeSliderTarget && this.hasFontSizeValueTarget) {
      this.fontSizeSliderTarget.addEventListener('input', () => {
        this.fontSizeValueTarget.textContent = `${this.fontSizeSliderTarget.value}%`;
      });
    }
  }
  
  async loadUserPreferences() {
    if (!this.hasPreferencesFormTarget) {
      return;
    }
    
    try {
      const preferences = await window.api.settings.getPreferences();
      this.populatePreferencesForm(preferences);
    } catch (error) {
      console.error('Erreur lors du chargement des préférences:', error);
      this.showFormError(this.preferencesFormTarget, 'Erreur lors du chargement des préférences');
    }
  }
  
  async loadSystemSettings() {
    if (!this.hasSystemSettingsFormTarget) {
      return;
    }
    
    try {
      const settings = await window.api.settings.getSystemSettings();
      this.populateSystemSettingsForm(settings);
    } catch (error) {
      console.error('Erreur lors du chargement des paramètres système:', error);
      this.showFormError(this.systemSettingsFormTarget, 'Erreur lors du chargement des paramètres système');
    }
  }
  
  async loadLoginHistory() {
    if (!this.hasLoginHistoryContainerTarget) {
      return;
    }
    
    try {
      const history = await window.api.users.getLoginHistory(
        this.hasUserIdValue ? this.userIdValue : null,
        { limit: 5 }
      );
      
      this.populateLoginHistory(history.history);
    } catch (error) {
      console.error('Erreur lors du chargement de l\'historique de connexion:', error);
      this.loginHistoryContainerTarget.innerHTML = '<div class="alert alert-danger">Erreur lors du chargement de l\'historique de connexion</div>';
    }
  }
  
  populatePreferencesForm(preferences) {
    const form = this.preferencesFormTarget;
    
    // Parcourir tous les éléments du formulaire et définir leurs valeurs
    form.querySelectorAll('input, select, textarea').forEach(input => {
      const name = input.name;
      
      // Ignorer les éléments sans nom ou le token CSRF
      if (!name || name === 'csrf_token') {
        return;
      }
      
      // Traiter selon le type d'élément
      if (input.type === 'checkbox') {
        // Gestion des cases à cocher individuelles
        if (name.includes('[]')) {
          const baseName = name.replace('[]', '');
          const value = input.value;
          
          if (preferences[baseName] && Array.isArray(preferences[baseName])) {
            input.checked = preferences[baseName].includes(value);
          } else if (preferences[baseName] && typeof preferences[baseName] === 'object') {
            input.checked = !!preferences[baseName][value];
          }
        } else {
          // Case à cocher simple
          input.checked = !!preferences[name];
        }
      } else if (input.type === 'radio') {
        // Boutons radio
        input.checked = preferences[name] === input.value;
      } else if (input.tagName === 'SELECT') {
        // Listes déroulantes
        if (preferences[name]) {
          input.value = preferences[name];
        }
      } else {
        // Champs texte, nombre, etc.
        if (preferences[name] !== undefined) {
          input.value = preferences[name];
        }
      }
    });
    
    // Mise à jour spécifique pour les thèmes
    if (this.hasThemeRadioTarget && preferences.theme) {
      this.themeRadioTargets.forEach(radio => {
        if (radio.value === preferences.theme) {
          radio.checked = true;
          const card = radio.closest('.theme-card');
          if (card) {
            document.querySelectorAll('.theme-card').forEach(c => c.classList.remove('active'));
            card.classList.add('active');
          }
        }
      });
    }
    
    // Mise à jour spécifique pour les couleurs
    if (this.hasColorRadioTarget && preferences.primary_color) {
      this.colorRadioTargets.forEach(radio => {
        if (radio.value === preferences.primary_color) {
          radio.checked = true;
        }
      });
    }
    
    // Mise à jour spécifique pour la taille de police
    if (this.hasFontSizeSliderTarget && preferences.font_size) {
      this.fontSizeSliderTarget.value = preferences.font_size;
      if (this.hasFontSizeValueTarget) {
        this.fontSizeValueTarget.textContent = `${preferences.font_size}%`;
      }
    }
  }
  
  populateSystemSettingsForm(settings) {
    const form = this.systemSettingsFormTarget;
    
    // Aplatir l'objet des paramètres système
    const flatSettings = {};
    Object.entries(settings).forEach(([category, categorySettings]) => {
      Object.entries(categorySettings).forEach(([key, value]) => {
        flatSettings[key] = value;
      });
    });
    
    // Parcourir tous les éléments du formulaire et définir leurs valeurs
    form.querySelectorAll('input, select, textarea').forEach(input => {
      const name = input.name;
      
      // Ignorer les éléments sans nom ou le token CSRF
      if (!name || name === 'csrf_token') {
        return;
      }
      
      // Traiter selon le type d'élément
      if (input.type === 'checkbox') {
        input.checked = !!flatSettings[name];
      } else if (input.type === 'radio') {
        input.checked = flatSettings[name] === input.value;
      } else if (input.tagName === 'SELECT') {
        if (flatSettings[name] !== undefined) {
          input.value = flatSettings[name];
        }
      } else {
        if (flatSettings[name] !== undefined) {
          input.value = flatSettings[name];
        }
      }
    });
    
    // Mise à jour spécifique pour les sliders
    form.querySelectorAll('input[type="range"]').forEach(slider => {
      const name = slider.name;
      if (flatSettings[name] !== undefined) {
        slider.value = flatSettings[name];
        
        // Mettre à jour l'affichage de la valeur
        const valueSpan = document.getElementById(`${name}_value`);
        if (valueSpan) {
          valueSpan.textContent = flatSettings[name];
        }
      }
    });
  }
  
  populateLoginHistory(history) {
    const container = this.loginHistoryContainerTarget;
    
    // Vider le conteneur
    container.innerHTML = '';
    
    // Si aucun historique, afficher un message
    if (!history || history.length === 0) {
      container.innerHTML = '<tr><td colspan="4" class="text-center">Aucun historique de connexion disponible</td></tr>';
      return;
    }
    
    // Créer les lignes du tableau pour chaque entrée d'historique
    history.forEach(entry => {
      const row = document.createElement('tr');
      
      // Déterminer la classe de statut
      const statusClass = entry.status === 'success' ? 'bg-success' : 'bg-danger';
      
      row.innerHTML = `
        <td>${entry.formatted_date}</td>
        <td>${entry.device_info}</td>
        <td>${entry.ip_address}</td>
        <td><span class="badge ${statusClass}">${entry.status === 'success' ? 'Succès' : 'Échec'}</span></td>
      `;
      
      container.appendChild(row);
    });
  }
  
  showFormError(form, message) {
    // Créer un message d'erreur
    const errorDiv = document.createElement('div');
    errorDiv.className = 'alert alert-danger';
    errorDiv.innerHTML = `<i class="bi bi-exclamation-triangle-fill me-2"></i>${message}`;
    
    // Insérer au début du formulaire
    form.prepend(errorDiv);
    
    // Supprimer après 5 secondes
    setTimeout(() => {
      errorDiv.remove();
    }, 5000);
  }
  
  async savePreferences(event) {
    event.preventDefault();
    
    const form = event.currentTarget;
    const formData = new FormData(form);
    const preferences = {};
    
    // Collecter les valeurs du formulaire
    for (const [key, value] of formData.entries()) {
      // Ignorer le token CSRF
      if (key === 'csrf_token') {
        continue;
      }
      
      // Gérer les tableaux (checkboxes avec même nom)
      if (key.endsWith('[]')) {
        const baseName = key.slice(0, -2);
        if (!preferences[baseName]) {
          preferences[baseName] = [];
        }
        preferences[baseName].push(value);
      } else {
        preferences[key] = value;
      }
    }
    
    // Gérer les cases à cocher non cochées (elles ne sont pas incluses dans formData)
    form.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
      if (!checkbox.checked && !checkbox.name.endsWith('[]')) {
        preferences[checkbox.name] = false;
      }
    });
    
    try {
      await window.api.settings.updatePreferences(preferences);
      
      // Afficher un message de succès
      const successMessage = document.createElement('div');
      successMessage.className = 'alert alert-success';
      successMessage.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i>Préférences enregistrées avec succès';
      form.prepend(successMessage);
      
      // Supprimer le message après 3 secondes
      setTimeout(() => {
        successMessage.remove();
      }, 3000);
    } catch (error) {
      console.error('Erreur lors de l\'enregistrement des préférences:', error);
      this.showFormError(form, 'Erreur lors de l\'enregistrement des préférences');
    }
  }
  
  async saveSystemSettings(event) {
    event.preventDefault();
    
    const form = event.currentTarget;
    const formData = new FormData(form);
    const settings = {};
    
    // Collecter les valeurs du formulaire
    for (const [key, value] of formData.entries()) {
      // Ignorer le token CSRF
      if (key === 'csrf_token') {
        continue;
      }
      
      settings[key] = value;
    }
    
    // Gérer les cases à cocher non cochées (elles ne sont pas incluses dans formData)
    form.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
      if (!checkbox.checked) {
        settings[checkbox.name] = false;
      }
    });
    
    try {
      await window.api.settings.updateSystemSettings(settings);
      
      // Afficher un message de succès
      const successMessage = document.createElement('div');
      successMessage.className = 'alert alert-success';
      successMessage.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i>Paramètres système enregistrés avec succès';
      form.prepend(successMessage);
      
      // Supprimer le message après 3 secondes
      setTimeout(() => {
        successMessage.remove();
      }, 3000);
    } catch (error) {
      console.error('Erreur lors de l\'enregistrement des paramètres système:', error);
      this.showFormError(form, 'Erreur lors de l\'enregistrement des paramètres système');
    }
  }
  
  setTheme(event) {
    const radio = event.currentTarget;
    const theme = radio.value;
    
    // Mettre à jour la classe active sur la carte
    document.querySelectorAll('.theme-card').forEach(card => {
      card.classList.remove('active');
    });
    
    const card = radio.closest('.theme-card');
    if (card) {
      card.classList.add('active');
    }
  }
}