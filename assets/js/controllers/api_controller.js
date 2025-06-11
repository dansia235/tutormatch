import { Controller } from '@hotwired/stimulus';
import ApiClient from '../api-client';

/**
 * API Controller
 * Gère les interactions avec l'API REST
 */
export default class extends Controller {
  static values = {
    endpoint: String,
    method: { type: String, default: 'GET' },
    params: Object,
    autoload: Boolean,
    target: String
  };
  
  static targets = ['loading', 'error', 'content'];
  
  connect() {
    // Initialiser le client API
    this.api = new ApiClient();
    
    // Charger les données automatiquement si configuré
    if (this.hasAutoloadValue && this.autoloadValue) {
      this.load();
    }
  }
  
  /**
   * Charge les données depuis l'API
   * @param {Event} event Événement déclencheur (optionnel)
   */
  async load(event) {
    if (event) event.preventDefault();
    
    if (!this.hasEndpointValue) return;
    
    this.showLoading();
    this.hideError();
    
    try {
      const params = this.hasParamsValue ? this.paramsValue : {};
      let data;
      
      switch (this.methodValue.toUpperCase()) {
        case 'GET':
          data = await this.api.get(this.endpointValue, params);
          break;
        case 'POST':
          data = await this.api.post(this.endpointValue, params);
          break;
        case 'PUT':
          data = await this.api.put(this.endpointValue, params);
          break;
        case 'DELETE':
          data = await this.api.delete(this.endpointValue);
          break;
        default:
          throw new Error(`Méthode HTTP non supportée: ${this.methodValue}`);
      }
      
      this.processResponse(data);
      
    } catch (error) {
      this.handleError(error);
    } finally {
      this.hideLoading();
    }
  }
  
  /**
   * Traite la réponse de l'API
   * @param {Object} data Données de réponse
   */
  processResponse(data) {
    // Déclencher un événement avec les données
    const event = new CustomEvent('api:loaded', {
      bubbles: true,
      detail: { data }
    });
    this.element.dispatchEvent(event);
    
    // Si un élément cible est spécifié, mettre à jour son contenu
    if (this.hasTargetValue && this.targetValue) {
      const targetElement = document.getElementById(this.targetValue);
      if (targetElement) {
        // Si les données sont un tableau ou un objet, tenter de le convertir en HTML
        if (typeof data === 'object') {
          // Si une fonction de rendu est définie, l'utiliser
          if (typeof this.renderData === 'function') {
            targetElement.innerHTML = this.renderData(data);
          } else {
            // Sinon, afficher les données en JSON
            targetElement.innerHTML = `<pre>${JSON.stringify(data, null, 2)}</pre>`;
          }
        } else {
          // Sinon, afficher les données telles quelles
          targetElement.textContent = data;
        }
      }
    }
  }
  
  /**
   * Gère les erreurs de l'API
   * @param {Error} error Erreur survenue
   */
  handleError(error) {
    console.error('Erreur API:', error);
    
    // Afficher le message d'erreur
    if (this.hasErrorTarget) {
      this.errorTarget.textContent = error.message || 'Une erreur est survenue';
      this.errorTarget.classList.remove('hidden');
    }
    
    // Déclencher un événement d'erreur
    const event = new CustomEvent('api:error', {
      bubbles: true,
      detail: { error }
    });
    this.element.dispatchEvent(event);
    
    // Gérer les erreurs d'authentification
    if (error.status === 401 || error.name === 'AuthenticationError') {
      // Rediriger vers la page de connexion
      window.location.href = '/tutoring/login.php?session_expired=1';
    }
  }
  
  /**
   * Affiche l'indicateur de chargement
   */
  showLoading() {
    if (this.hasLoadingTarget) {
      this.loadingTarget.classList.remove('hidden');
    }
    
    // Ajouter une classe au conteneur principal
    this.element.classList.add('api-loading');
  }
  
  /**
   * Masque l'indicateur de chargement
   */
  hideLoading() {
    if (this.hasLoadingTarget) {
      this.loadingTarget.classList.add('hidden');
    }
    
    // Retirer la classe du conteneur principal
    this.element.classList.remove('api-loading');
  }
  
  /**
   * Masque le message d'erreur
   */
  hideError() {
    if (this.hasErrorTarget) {
      this.errorTarget.classList.add('hidden');
    }
  }
  
  /**
   * Soumet un formulaire via l'API
   * @param {Event} event Événement de soumission du formulaire
   */
  async submitForm(event) {
    event.preventDefault();
    
    const form = event.currentTarget;
    const formData = new FormData(form);
    const data = {};
    
    // Convertir FormData en objet
    for (const [key, value] of formData.entries()) {
      // Gérer les tableaux et les valeurs multiples
      if (key.endsWith('[]')) {
        const arrayKey = key.slice(0, -2);
        if (!data[arrayKey]) data[arrayKey] = [];
        data[arrayKey].push(value);
      } else {
        data[key] = value;
      }
    }
    
    // Déterminer l'endpoint et la méthode
    const endpoint = form.dataset.endpoint || this.endpointValue;
    const method = form.dataset.method || 'POST';
    
    if (!endpoint) {
      this.handleError(new Error('Endpoint manquant pour la soumission du formulaire'));
      return;
    }
    
    this.showLoading();
    this.hideError();
    
    try {
      let response;
      
      switch (method.toUpperCase()) {
        case 'POST':
          response = await this.api.post(endpoint, data);
          break;
        case 'PUT':
          response = await this.api.put(endpoint, data);
          break;
        case 'PATCH':
          response = await this.api.patch(endpoint, data);
          break;
        default:
          throw new Error(`Méthode HTTP non supportée pour les formulaires: ${method}`);
      }
      
      // Déclencher un événement de succès
      const event = new CustomEvent('form:success', {
        bubbles: true,
        detail: { response, form }
      });
      form.dispatchEvent(event);
      
      // Rediriger si l'attribut data-redirect est présent
      if (form.dataset.redirect) {
        window.location.href = form.dataset.redirect.replace(':id', response.data?.id || '');
      }
      
    } catch (error) {
      this.handleError(error);
      
      // Déclencher un événement d'erreur spécifique au formulaire
      const errorEvent = new CustomEvent('form:error', {
        bubbles: true,
        detail: { error, form }
      });
      form.dispatchEvent(errorEvent);
      
    } finally {
      this.hideLoading();
    }
  }
  
  /**
   * Effectue un appel API lors d'un clic sur un élément
   * @param {Event} event Événement de clic
   */
  async fetchOnClick(event) {
    event.preventDefault();
    
    const element = event.currentTarget;
    const endpoint = element.dataset.endpoint;
    const method = element.dataset.method || 'GET';
    const params = element.dataset.params ? JSON.parse(element.dataset.params) : {};
    
    if (!endpoint) {
      console.error('Endpoint manquant pour fetchOnClick');
      return;
    }
    
    this.showLoading();
    this.hideError();
    
    try {
      let data;
      
      switch (method.toUpperCase()) {
        case 'GET':
          data = await this.api.get(endpoint, params);
          break;
        case 'POST':
          data = await this.api.post(endpoint, params);
          break;
        case 'PUT':
          data = await this.api.put(endpoint, params);
          break;
        case 'DELETE':
          data = await this.api.delete(endpoint);
          break;
        default:
          throw new Error(`Méthode HTTP non supportée: ${method}`);
      }
      
      this.processResponse(data);
      
      // Déclencher un événement de clic spécifique
      const clickEvent = new CustomEvent('api:click', {
        bubbles: true,
        detail: { data, element }
      });
      element.dispatchEvent(clickEvent);
      
    } catch (error) {
      this.handleError(error);
    } finally {
      this.hideLoading();
    }
  }
}