/**
 * Extensions pour le client API
 * Ce fichier contient les extensions pour le client API existant dans api-client.js
 * Il ajoute les nouveaux endpoints créés pour remplacer les données statiques
 */

// Définir les nouveaux modules pour l'API client
export default function extendApiClient(apiClient) {
    // API pour le tableau de bord
    apiClient.dashboard = {
        /**
         * Récupère les statistiques générales du tableau de bord
         * @returns {Promise<Object>} Statistiques du tableau de bord
         */
        async getStats() {
            return apiClient.get('dashboard/stats');
        },
        
        /**
         * Récupère les données pour les graphiques du tableau de bord
         * @param {string} chartType Type de graphique (optionnel)
         * @param {string} period Période (day, week, month, year)
         * @returns {Promise<Object>} Données des graphiques
         */
        async getCharts(chartType = null, period = 'month') {
            const params = { period };
            if (chartType) {
                params.type = chartType;
            }
            return apiClient.get('dashboard/charts', params);
        },
        
        /**
         * Récupère l'état du système
         * @returns {Promise<Object>} État du système
         */
        async getSystemStatus() {
            return apiClient.get('dashboard/system-status');
        },
        
        /**
         * Récupère le flux d'activité
         * @param {Object} params Paramètres de filtrage
         * @returns {Promise<Object>} Flux d'activité
         */
        async getActivity(params = {}) {
            return apiClient.get('dashboard/activity', params);
        }
    };
    
    // API pour les paramètres système
    apiClient.settings = {
        /**
         * Récupère les paramètres système
         * @param {string} category Catégorie (optionnel)
         * @returns {Promise<Object>} Paramètres système
         */
        async getSystemSettings(category = null) {
            const params = {};
            if (category) {
                params.category = category;
            }
            return apiClient.get('settings/system', params);
        },
        
        /**
         * Met à jour les paramètres système
         * @param {Object} settings Paramètres à mettre à jour
         * @returns {Promise<Object>} Résultat de la mise à jour
         */
        async updateSystemSettings(settings) {
            return apiClient.put('settings/system', settings);
        },
        
        /**
         * Récupère les préférences utilisateur
         * @param {string} key Clé de préférence (optionnel)
         * @returns {Promise<Object>} Préférences utilisateur
         */
        async getPreferences(key = null) {
            const params = {};
            if (key) {
                params.key = key;
            }
            return apiClient.get('settings/preferences', params);
        },
        
        /**
         * Met à jour les préférences utilisateur
         * @param {Object} preferences Préférences à mettre à jour
         * @returns {Promise<Object>} Résultat de la mise à jour
         */
        async updatePreferences(preferences) {
            return apiClient.put('settings/preferences', preferences);
        }
    };
    
    // Extension de l'API des affectations
    apiClient.assignments = apiClient.assignments || {};
    
    /**
     * Récupère les données de la matrice d'affectation
     * @param {Object} params Paramètres de filtrage
     * @returns {Promise<Object>} Données de la matrice
     */
    apiClient.assignments.getMatrix = async function(params = {}) {
        return apiClient.get('assignments/matrix', params);
    };
    
    // Extension de l'API des utilisateurs
    apiClient.users = apiClient.users || {};
    
    /**
     * Récupère l'historique de connexion d'un utilisateur
     * @param {number} userId ID de l'utilisateur (optionnel)
     * @param {Object} params Paramètres de pagination
     * @returns {Promise<Object>} Historique de connexion
     */
    apiClient.users.getLoginHistory = async function(userId = null, params = {}) {
        const requestParams = { ...params };
        if (userId) {
            requestParams.user_id = userId;
        }
        return apiClient.get('users/login-history', requestParams);
    };
    
    return apiClient;
}