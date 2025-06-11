/**
 * Initialisation de l'API Client
 * Ce fichier initialise une instance globale de l'API Client
 */
import ApiClient from './api-client';

// Créer une instance globale de l'API Client
window.apiClient = new ApiClient();

// Rendre ApiClient disponible globalement pour les autres scripts
window.ApiClient = ApiClient;

// Exporter l'instance pour les modules ES6
export default window.apiClient;