/**
 * Service d'authentification
 * Gère l'authentification et la gestion des sessions utilisateur
 */
import ApiClient from '../api-client';

class AuthService {
    constructor() {
        this.api = new ApiClient();
        this.user = null;
        this.listeners = [];
        
        // Charger l'utilisateur depuis le localStorage si disponible
        this.loadUserFromStorage();
        
        // Mettre en place des écouteurs pour les événements de déconnexion
        window.addEventListener('storage', (event) => {
            if (event.key === 'tutoring_auth_token' && !event.newValue) {
                this.logout(true);
            }
        });
    }
    
    /**
     * Charge les données utilisateur depuis le stockage local
     */
    loadUserFromStorage() {
        const userJson = localStorage.getItem('tutoring_user');
        if (userJson) {
            try {
                this.user = JSON.parse(userJson);
            } catch (e) {
                this.user = null;
                localStorage.removeItem('tutoring_user');
            }
        }
    }
    
    /**
     * Sauvegarde les données utilisateur dans le stockage local
     */
    saveUserToStorage() {
        if (this.user) {
            localStorage.setItem('tutoring_user', JSON.stringify(this.user));
        } else {
            localStorage.removeItem('tutoring_user');
        }
    }
    
    /**
     * Vérifie si l'utilisateur est connecté
     * @returns {boolean}
     */
    isAuthenticated() {
        return this.api.isAuthenticated() && !!this.user;
    }
    
    /**
     * Récupère le rôle de l'utilisateur connecté
     * @returns {string|null}
     */
    getUserRole() {
        return this.user ? this.user.role : null;
    }
    
    /**
     * Récupère l'ID de l'utilisateur connecté
     * @returns {number|null}
     */
    getUserId() {
        return this.user ? this.user.id : null;
    }
    
    /**
     * Récupère le nom complet de l'utilisateur connecté
     * @returns {string|null}
     */
    getUserName() {
        return this.user ? `${this.user.first_name} ${this.user.last_name}` : null;
    }
    
    /**
     * Récupère les données complètes de l'utilisateur connecté
     * @returns {Object|null}
     */
    getUser() {
        return this.user;
    }
    
    /**
     * Authentifie un utilisateur avec ses identifiants
     * @param {string} username Nom d'utilisateur
     * @param {string} password Mot de passe
     * @returns {Promise<Object>} Données utilisateur
     */
    async login(username, password) {
        try {
            const response = await this.api.auth.login(username, password);
            
            if (response.success) {
                this.user = response.user;
                this.saveUserToStorage();
                this.notifyListeners('login', this.user);
                return this.user;
            }
            
            throw new Error(response.message || 'Échec de connexion');
        } catch (error) {
            console.error('Erreur de connexion:', error);
            throw error;
        }
    }
    
    /**
     * Déconnecte l'utilisateur
     * @param {boolean} silent Ne pas effectuer de requête API (pour la déconnexion passive)
     */
    async logout(silent = false) {
        try {
            if (!silent) {
                await this.api.auth.logout();
            }
        } catch (error) {
            console.error('Erreur lors de la déconnexion:', error);
        } finally {
            // Toujours nettoyer les données locales
            this.user = null;
            this.api.clearAuth();
            this.saveUserToStorage();
            this.notifyListeners('logout');
        }
    }
    
    /**
     * Vérifie et rafraîchit la session si nécessaire
     * @returns {Promise<boolean>} Indique si la session est valide
     */
    async checkSession() {
        try {
            // Si aucun token n'est présent, la session n'est pas valide
            if (!this.api.accessToken) {
                return false;
            }
            
            // Si le token est expiré, tenter de le rafraîchir
            if (this.api.isTokenExpired()) {
                const refreshed = await this.api.auth.refreshToken();
                if (!refreshed) {
                    this.logout(true);
                    return false;
                }
            }
            
            // Si l'utilisateur n'est pas chargé, récupérer son profil
            if (!this.user) {
                const profile = await this.api.users.getProfile();
                if (profile.success) {
                    this.user = profile.data;
                    this.saveUserToStorage();
                    this.notifyListeners('refresh', this.user);
                } else {
                    this.logout(true);
                    return false;
                }
            }
            
            return true;
        } catch (error) {
            console.error('Erreur lors de la vérification de session:', error);
            this.logout(true);
            return false;
        }
    }
    
    /**
     * Vérifie si l'utilisateur a un rôle spécifique
     * @param {string|Array} roles Rôle(s) à vérifier
     * @returns {boolean}
     */
    hasRole(roles) {
        if (!this.user) return false;
        
        const userRole = this.user.role;
        
        if (Array.isArray(roles)) {
            return roles.includes(userRole);
        }
        
        return userRole === roles;
    }
    
    /**
     * Ajoute un écouteur d'événements d'authentification
     * @param {Function} callback Fonction à appeler lors des changements d'authentification
     * @returns {Function} Fonction pour supprimer l'écouteur
     */
    addListener(callback) {
        if (typeof callback !== 'function') return () => {};
        
        this.listeners.push(callback);
        
        return () => {
            this.listeners = this.listeners.filter(listener => listener !== callback);
        };
    }
    
    /**
     * Notifie tous les écouteurs d'un événement d'authentification
     * @param {string} event Nom de l'événement ('login', 'logout', 'refresh')
     * @param {Object} data Données associées à l'événement
     */
    notifyListeners(event, data = null) {
        this.listeners.forEach(listener => {
            try {
                listener(event, data);
            } catch (error) {
                console.error('Erreur dans un écouteur d\'authentification:', error);
            }
        });
    }
}

// Créer et exporter une instance unique
const authService = new AuthService();
export default authService;