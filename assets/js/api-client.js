/**
 * API Client Module
 * Gère les appels à l'API REST avec gestion de l'authentification et des tokens
 */

const API_URL = '/tutoring/api';
const TOKEN_KEY = 'tutoring_auth_token';
const REFRESH_TOKEN_KEY = 'tutoring_refresh_token';

/**
 * Classe principale pour les appels API
 */
export default class ApiClient {
    /**
     * Initialise le client API
     */
    constructor() {
        this.accessToken = localStorage.getItem(TOKEN_KEY);
        this.refreshToken = localStorage.getItem(REFRESH_TOKEN_KEY);
        this.tokenExpiry = localStorage.getItem('token_expiry') || 0;
        this.isRefreshing = false;
        this.refreshQueue = [];
    }

    /**
     * Vérifie si l'utilisateur est authentifié
     * @returns {boolean}
     */
    isAuthenticated() {
        return !!this.accessToken;
    }

    /**
     * Définit les tokens d'authentification
     * @param {Object} authData Données d'authentification (access_token, refresh_token, expires_in)
     */
    setAuth(authData) {
        if (authData.access_token) {
            this.accessToken = authData.access_token;
            localStorage.setItem(TOKEN_KEY, authData.access_token);
        }
        
        if (authData.refresh_token) {
            this.refreshToken = authData.refresh_token;
            localStorage.setItem(REFRESH_TOKEN_KEY, authData.refresh_token);
        }
        
        if (authData.expires_in) {
            const expiresAt = Date.now() + (authData.expires_in * 1000);
            this.tokenExpiry = expiresAt;
            localStorage.setItem('token_expiry', expiresAt);
        }
    }

    /**
     * Efface les tokens d'authentification
     */
    clearAuth() {
        this.accessToken = null;
        this.refreshToken = null;
        this.tokenExpiry = 0;
        localStorage.removeItem(TOKEN_KEY);
        localStorage.removeItem(REFRESH_TOKEN_KEY);
        localStorage.removeItem('token_expiry');
    }

    /**
     * Vérifie si le token d'accès est expiré
     * @returns {boolean}
     */
    isTokenExpired() {
        return Date.now() >= this.tokenExpiry;
    }

    /**
     * Rafraîchit le token d'accès
     * @returns {Promise<boolean>} Indique si le rafraîchissement a réussi
     */
    async refreshAccessToken() {
        if (!this.refreshToken) return false;
        
        try {
            const response = await fetch(`${API_URL}/auth/refresh`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    refresh_token: this.refreshToken
                })
            });
            
            if (!response.ok) {
                this.clearAuth();
                return false;
            }
            
            const data = await response.json();
            this.setAuth(data);
            return true;
        } catch (error) {
            console.error('Erreur lors du rafraîchissement du token:', error);
            this.clearAuth();
            return false;
        }
    }

    /**
     * Ajoute des en-têtes d'authentification à une requête
     * @param {Object} headers En-têtes existants
     * @returns {Object} En-têtes avec authentification
     */
    async getAuthHeaders(headers = {}) {
        // Vérifier si le token est expiré et le rafraîchir si nécessaire
        if (this.isTokenExpired() && this.refreshToken) {
            // Si un rafraîchissement est déjà en cours, attendre qu'il termine
            if (this.isRefreshing) {
                return new Promise((resolve, reject) => {
                    this.refreshQueue.push({ resolve, reject });
                }).then(() => this.getAuthHeaders(headers));
            }
            
            this.isRefreshing = true;
            
            try {
                const refreshSuccess = await this.refreshAccessToken();
                
                if (!refreshSuccess) {
                    // Échec du rafraîchissement, l'utilisateur doit se reconnecter
                    this.refreshQueue.forEach(p => p.reject(new Error('Échec de rafraîchissement du token')));
                    this.refreshQueue = [];
                    throw new Error('Session expirée, veuillez vous reconnecter');
                }
                
                // Succès du rafraîchissement
                this.refreshQueue.forEach(p => p.resolve());
                this.refreshQueue = [];
            } finally {
                this.isRefreshing = false;
            }
        }
        
        // Ajouter le token d'accès aux en-têtes
        if (this.accessToken) {
            return {
                ...headers,
                'Authorization': `Bearer ${this.accessToken}`
            };
        }
        
        return headers;
    }

    /**
     * Effectue une requête HTTP à l'API
     * @param {string} endpoint Point de terminaison de l'API
     * @param {Object} options Options de la requête fetch
     * @param {boolean} requiresAuth Indique si la requête nécessite une authentification
     * @returns {Promise<Object>} Réponse de l'API
     */
    async request(endpoint, options = {}, requiresAuth = true) {
        const url = `${API_URL}/${endpoint}`;
        
        // Préparer les en-têtes par défaut
        const defaultHeaders = {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        };
        
        // Fusionner les en-têtes par défaut et ceux fournis
        const headers = { ...defaultHeaders, ...(options.headers || {}) };
        
        // Ajouter les en-têtes d'authentification si nécessaire
        const finalHeaders = requiresAuth 
            ? await this.getAuthHeaders(headers) 
            : headers;
        
        // Préparer les options de la requête
        const requestOptions = {
            ...options,
            headers: finalHeaders
        };
        
        try {
            const response = await fetch(url, requestOptions);
            
            // Gérer les erreurs HTTP
            if (!response.ok) {
                // Si l'erreur est 401 et qu'on est authentifié, la session est peut-être expirée
                if (response.status === 401 && requiresAuth) {
                    // Tenter un rafraîchissement du token
                    const refreshSuccess = await this.refreshAccessToken();
                    
                    if (refreshSuccess) {
                        // Réessayer la requête avec le nouveau token
                        const newHeaders = await this.getAuthHeaders(headers);
                        return this.request(endpoint, {
                            ...options,
                            headers: newHeaders
                        }, requiresAuth);
                    }
                    
                    // Le rafraîchissement a échoué, l'utilisateur doit se reconnecter
                    this.clearAuth();
                    const error = new Error('Session expirée, veuillez vous reconnecter');
                    error.status = 401;
                    error.name = 'AuthenticationError';
                    throw error;
                }
                
                // Autres erreurs HTTP
                const error = new Error(response.statusText || `Erreur HTTP ${response.status}`);
                error.status = response.status;
                
                // Tenter d'extraire le message d'erreur du JSON
                try {
                    const errorData = await response.json();
                    if (errorData && errorData.message) {
                        error.message = errorData.message;
                    }
                    error.data = errorData;
                } catch (e) {
                    // Ignorer les erreurs de parsing JSON
                }
                
                throw error;
            }
            
            // Vérifier si la réponse est vide ou pas du JSON
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return response.json();
            }
            
            return { success: true, status: response.status };
            
        } catch (error) {
            // Gérer les erreurs réseau
            if (!error.status) {
                error.message = 'Erreur réseau, veuillez vérifier votre connexion';
                error.name = 'NetworkError';
            }
            throw error;
        }
    }

    /**
     * Effectue une requête GET
     * @param {string} endpoint Point de terminaison de l'API
     * @param {Object} params Paramètres de requête (query string)
     * @param {boolean} requiresAuth Indique si la requête nécessite une authentification
     * @returns {Promise<Object>} Réponse de l'API
     */
    async get(endpoint, params = {}, requiresAuth = true) {
        // Ajouter les paramètres à l'URL
        const url = this.buildUrlWithParams(endpoint, params);
        
        return this.request(url, {
            method: 'GET'
        }, requiresAuth);
    }

    /**
     * Effectue une requête POST
     * @param {string} endpoint Point de terminaison de l'API
     * @param {Object} data Données à envoyer
     * @param {boolean} requiresAuth Indique si la requête nécessite une authentification
     * @returns {Promise<Object>} Réponse de l'API
     */
    async post(endpoint, data = {}, requiresAuth = true) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(data)
        }, requiresAuth);
    }

    /**
     * Effectue une requête PUT
     * @param {string} endpoint Point de terminaison de l'API
     * @param {Object} data Données à envoyer
     * @param {boolean} requiresAuth Indique si la requête nécessite une authentification
     * @returns {Promise<Object>} Réponse de l'API
     */
    async put(endpoint, data = {}, requiresAuth = true) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(data)
        }, requiresAuth);
    }

    /**
     * Effectue une requête PATCH
     * @param {string} endpoint Point de terminaison de l'API
     * @param {Object} data Données à envoyer
     * @param {boolean} requiresAuth Indique si la requête nécessite une authentification
     * @returns {Promise<Object>} Réponse de l'API
     */
    async patch(endpoint, data = {}, requiresAuth = true) {
        return this.request(endpoint, {
            method: 'PATCH',
            body: JSON.stringify(data)
        }, requiresAuth);
    }

    /**
     * Effectue une requête DELETE
     * @param {string} endpoint Point de terminaison de l'API
     * @param {boolean} requiresAuth Indique si la requête nécessite une authentification
     * @returns {Promise<Object>} Réponse de l'API
     */
    async delete(endpoint, requiresAuth = true) {
        return this.request(endpoint, {
            method: 'DELETE'
        }, requiresAuth);
    }

    /**
     * Construit une URL avec des paramètres de requête
     * @param {string} endpoint Point de terminaison de l'API
     * @param {Object} params Paramètres de requête
     * @returns {string} URL avec paramètres
     */
    buildUrlWithParams(endpoint, params = {}) {
        const url = new URL(`${window.location.origin}${API_URL}/${endpoint}`);
        
        Object.entries(params).forEach(([key, value]) => {
            if (value !== undefined && value !== null) {
                url.searchParams.append(key, value);
            }
        });
        
        return url.pathname.substring(API_URL.length + 1) + url.search;
    }

    /**
     * API d'authentification
     */
    auth = {
        /**
         * Connexion utilisateur
         * @param {string} username Nom d'utilisateur
         * @param {string} password Mot de passe
         * @returns {Promise<Object>} Données utilisateur et tokens
         */
        async login(username, password) {
            try {
                const data = await this.post('auth/login', { username, password }, false);
                this.setAuth(data);
                return data;
            } catch (error) {
                throw error;
            }
        },

        /**
         * Déconnexion utilisateur
         */
        async logout() {
            try {
                await this.post('auth/logout');
            } finally {
                this.clearAuth();
            }
        },

        /**
         * Rafraîchissement du token
         * @returns {Promise<boolean>} Indique si le rafraîchissement a réussi
         */
        async refreshToken() {
            return this.refreshAccessToken();
        }
    };

    /**
     * API des étudiants
     */
    students = {
        /**
         * Récupère la liste des étudiants
         * @param {Object} params Paramètres de filtrage
         * @returns {Promise<Array>} Liste des étudiants
         */
        async getAll(params = {}) {
            return this.get('students', params);
        },

        /**
         * Récupère un étudiant par son ID
         * @param {number} id ID de l'étudiant
         * @returns {Promise<Object>} Données de l'étudiant
         */
        async getById(id) {
            return this.get(`students/${id}`);
        },

        /**
         * Récupère les stages d'un étudiant
         * @param {number} id ID de l'étudiant
         * @returns {Promise<Array>} Liste des stages
         */
        async getInternships(id) {
            return this.get(`students/${id}/internships`);
        },

        /**
         * Récupère les affectations d'un étudiant
         * @param {number} id ID de l'étudiant
         * @returns {Promise<Array>} Liste des affectations
         */
        async getAssignments(id) {
            return this.get(`students/${id}/assignments`);
        },

        /**
         * Récupère les préférences d'un étudiant
         * @param {number} id ID de l'étudiant
         * @returns {Promise<Object>} Préférences de l'étudiant
         */
        async getPreferences(id) {
            return this.get(`students/${id}/preferences`);
        }
    };

    /**
     * API des tuteurs
     */
    teachers = {
        /**
         * Récupère la liste des tuteurs
         * @param {Object} params Paramètres de filtrage
         * @returns {Promise<Array>} Liste des tuteurs
         */
        async getAll(params = {}) {
            return this.get('teachers', params);
        },

        /**
         * Récupère un tuteur par son ID
         * @param {number} id ID du tuteur
         * @returns {Promise<Object>} Données du tuteur
         */
        async getById(id) {
            return this.get(`teachers/${id}`);
        },

        /**
         * Récupère les étudiants assignés à un tuteur
         * @param {number} id ID du tuteur
         * @returns {Promise<Array>} Liste des étudiants
         */
        async getStudents(id) {
            return this.get(`teachers/${id}/students`);
        },

        /**
         * Récupère les disponibilités d'un tuteur
         * @param {number} id ID du tuteur
         * @returns {Promise<Object>} Disponibilités du tuteur
         */
        async getAvailability(id) {
            return this.get(`teachers/${id}/availability`);
        }
    };

    /**
     * API des affectations
     */
    assignments = {
        /**
         * Récupère la liste des affectations
         * @param {Object} params Paramètres de filtrage
         * @returns {Promise<Array>} Liste des affectations
         */
        async getAll(params = {}) {
            return this.get('assignments', params);
        },

        /**
         * Récupère une affectation par son ID
         * @param {number} id ID de l'affectation
         * @returns {Promise<Object>} Données de l'affectation
         */
        async getById(id) {
            return this.get(`assignments/${id}`);
        },

        /**
         * Crée une nouvelle affectation
         * @param {Object} data Données de l'affectation
         * @returns {Promise<Object>} Affectation créée
         */
        async create(data) {
            return this.post('assignments/create', data);
        },

        /**
         * Met à jour une affectation
         * @param {number} id ID de l'affectation
         * @param {Object} data Données de mise à jour
         * @returns {Promise<Object>} Affectation mise à jour
         */
        async update(id, data) {
            return this.put(`assignments/${id}`, data);
        },

        /**
         * Met à jour le statut d'une affectation
         * @param {number} id ID de l'affectation
         * @param {string} status Nouveau statut
         * @returns {Promise<Object>} Résultat de la mise à jour
         */
        async updateStatus(id, status) {
            return this.put(`assignments/status/${id}`, { status });
        },

        /**
         * Met à jour plusieurs affectations à la fois
         * @param {Object} assignments Mapping étudiant => tuteur
         * @returns {Promise<Object>} Résultat des mises à jour
         */
        async batchUpdate(assignments) {
            return this.post('assignments/batch-update', { assignments });
        }
    };

    /**
     * API des stages
     */
    internships = {
        /**
         * Récupère la liste des stages
         * @param {Object} params Paramètres de filtrage
         * @returns {Promise<Array>} Liste des stages
         */
        async getAll(params = {}) {
            return this.get('internships', params);
        },

        /**
         * Récupère un stage par son ID
         * @param {number} id ID du stage
         * @returns {Promise<Object>} Données du stage
         */
        async getById(id) {
            return this.get(`internships/${id}`);
        },

        /**
         * Récupère les stages disponibles
         * @returns {Promise<Array>} Liste des stages disponibles
         */
        async getAvailable() {
            return this.get('internships/available');
        },

        /**
         * Crée un nouveau stage
         * @param {Object} data Données du stage
         * @returns {Promise<Object>} Stage créé
         */
        async create(data) {
            return this.post('internships/create', data);
        },

        /**
         * Met à jour un stage
         * @param {number} id ID du stage
         * @param {Object} data Données de mise à jour
         * @returns {Promise<Object>} Stage mis à jour
         */
        async update(id, data) {
            return this.put(`internships/${id}`, data);
        },

        /**
         * Supprime un stage
         * @param {number} id ID du stage
         * @returns {Promise<Object>} Résultat de la suppression
         */
        async delete(id) {
            return this.delete(`internships/${id}`);
        }
    };

    /**
     * API des documents
     */
    documents = {
        /**
         * Récupère la liste des documents
         * @param {Object} params Paramètres de filtrage
         * @returns {Promise<Array>} Liste des documents
         */
        async getAll(params = {}) {
            return this.get('documents', params);
        },

        /**
         * Récupère un document par son ID
         * @param {number} id ID du document
         * @returns {Promise<Object>} Données du document
         */
        async getById(id) {
            return this.get(`documents/${id}`);
        },

        /**
         * Télécharge un document
         * @param {number} id ID du document
         * @returns {Promise<Blob>} Fichier du document
         */
        async download(id) {
            const url = `${API_URL}/documents/download/${id}`;
            const headers = await this.getAuthHeaders();
            
            const response = await fetch(url, {
                method: 'GET',
                headers
            });
            
            if (!response.ok) {
                throw new Error(`Erreur HTTP ${response.status}`);
            }
            
            return response.blob();
        },

        /**
         * Télécharge un document et ouvre dans une nouvelle fenêtre
         * @param {number} id ID du document
         */
        async openDocument(id) {
            try {
                const blob = await this.download(id);
                const url = window.URL.createObjectURL(blob);
                window.open(url, '_blank');
            } catch (error) {
                console.error('Erreur lors de l\'ouverture du document:', error);
                throw error;
            }
        },

        /**
         * Supprime un document
         * @param {number} id ID du document
         * @returns {Promise<Object>} Résultat de la suppression
         */
        async delete(id) {
            return this.delete(`documents/${id}`);
        },

        /**
         * Téléverse un document
         * @param {FormData} formData Données du formulaire avec le fichier
         * @returns {Promise<Object>} Document créé
         */
        async upload(formData) {
            const url = `${API_URL}/documents/upload`;
            const headers = await this.getAuthHeaders();
            
            // Ne pas ajouter Content-Type pour FormData (sera défini automatiquement)
            delete headers['Content-Type'];
            
            const response = await fetch(url, {
                method: 'POST',
                headers,
                body: formData
            });
            
            if (!response.ok) {
                const errorData = await response.json();
                const error = new Error(errorData.message || `Erreur HTTP ${response.status}`);
                error.status = response.status;
                error.data = errorData;
                throw error;
            }
            
            return response.json();
        }
    };

    /**
     * API des messages
     */
    messages = {
        /**
         * Récupère les conversations de l'utilisateur
         * @returns {Promise<Array>} Liste des conversations
         */
        async getConversations() {
            return this.get('messages/conversations');
        },

        /**
         * Récupère les messages d'une conversation
         * @param {number} conversationId ID de la conversation
         * @returns {Promise<Array>} Liste des messages
         */
        async getMessages(conversationId) {
            return this.get(`messages?conversation_id=${conversationId}`);
        },

        /**
         * Récupère un message par son ID
         * @param {number} id ID du message
         * @returns {Promise<Object>} Données du message
         */
        async getById(id) {
            return this.get(`messages/show?id=${id}`);
        },

        /**
         * Envoie un nouveau message
         * @param {Object} data Données du message
         * @returns {Promise<Object>} Message envoyé
         */
        async send(data) {
            return this.post('messages/send', data);
        },

        /**
         * Marque un message comme lu
         * @param {number} id ID du message
         * @returns {Promise<Object>} Résultat de la mise à jour
         */
        async markAsRead(id) {
            return this.post('messages/mark-read', { message_id: id });
        }
    };

    /**
     * API des réunions
     */
    meetings = {
        /**
         * Récupère la liste des réunions
         * @param {Object} params Paramètres de filtrage
         * @returns {Promise<Array>} Liste des réunions
         */
        async getAll(params = {}) {
            return this.get('meetings', params);
        },

        /**
         * Récupère une réunion par son ID
         * @param {number} id ID de la réunion
         * @returns {Promise<Object>} Données de la réunion
         */
        async getById(id) {
            return this.get(`meetings/${id}`);
        },

        /**
         * Crée une nouvelle réunion
         * @param {Object} data Données de la réunion
         * @returns {Promise<Object>} Réunion créée
         */
        async create(data) {
            return this.post('meetings/create', data);
        },

        /**
         * Met à jour une réunion
         * @param {number} id ID de la réunion
         * @param {Object} data Données de mise à jour
         * @returns {Promise<Object>} Réunion mise à jour
         */
        async update(id, data) {
            return this.put(`meetings/${id}`, data);
        },

        /**
         * Supprime une réunion
         * @param {number} id ID de la réunion
         * @returns {Promise<Object>} Résultat de la suppression
         */
        async delete(id) {
            return this.delete(`meetings/${id}`);
        },

        /**
         * Récupère les participants d'une réunion
         * @param {number} id ID de la réunion
         * @returns {Promise<Array>} Liste des participants
         */
        async getParticipants(id) {
            return this.get(`meetings/participants?meeting_id=${id}`);
        }
    };

    /**
     * API des utilisateurs
     */
    users = {
        /**
         * Récupère la liste des utilisateurs
         * @param {Object} params Paramètres de filtrage
         * @returns {Promise<Array>} Liste des utilisateurs
         */
        async getAll(params = {}) {
            return this.get('users', params);
        },

        /**
         * Récupère un utilisateur par son ID
         * @param {number} id ID de l'utilisateur
         * @returns {Promise<Object>} Données de l'utilisateur
         */
        async getById(id) {
            return this.get(`users/${id}`);
        },

        /**
         * Crée un nouvel utilisateur
         * @param {Object} data Données de l'utilisateur
         * @returns {Promise<Object>} Utilisateur créé
         */
        async create(data) {
            return this.post('users/create', data);
        },

        /**
         * Met à jour un utilisateur
         * @param {number} id ID de l'utilisateur
         * @param {Object} data Données de mise à jour
         * @returns {Promise<Object>} Utilisateur mis à jour
         */
        async update(id, data) {
            return this.put(`users/${id}`, data);
        },

        /**
         * Supprime un utilisateur
         * @param {number} id ID de l'utilisateur
         * @returns {Promise<Object>} Résultat de la suppression
         */
        async delete(id) {
            return this.delete(`users/${id}`);
        },

        /**
         * Récupère le profil de l'utilisateur connecté
         * @returns {Promise<Object>} Profil utilisateur
         */
        async getProfile() {
            return this.get('users/profile');
        },

        /**
         * Met à jour le profil de l'utilisateur connecté
         * @param {Object} data Données de mise à jour
         * @returns {Promise<Object>} Profil mis à jour
         */
        async updateProfile(data) {
            return this.put('users/profile', data);
        },

        /**
         * Change le mot de passe de l'utilisateur connecté
         * @param {string} currentPassword Mot de passe actuel
         * @param {string} newPassword Nouveau mot de passe
         * @returns {Promise<Object>} Résultat du changement
         */
        async changePassword(currentPassword, newPassword) {
            return this.post('users/change-password', {
                current_password: currentPassword,
                new_password: newPassword,
                confirm_password: newPassword
            });
        }
    };

    /**
     * API des notifications
     */
    notifications = {
        /**
         * Récupère les notifications de l'utilisateur
         * @param {Object} params Paramètres de filtrage
         * @returns {Promise<Array>} Liste des notifications
         */
        async getAll(params = {}) {
            return this.get('notifications', params);
        },

        /**
         * Récupère les notifications non lues
         * @returns {Promise<Array>} Liste des notifications non lues
         */
        async getUnread() {
            return this.get('notifications/unread');
        },

        /**
         * Marque une notification comme lue
         * @param {number} id ID de la notification
         * @returns {Promise<Object>} Résultat de la mise à jour
         */
        async markAsRead(id) {
            return this.post('notifications/mark-read', { notification_id: id });
        },

        /**
         * Marque toutes les notifications comme lues
         * @returns {Promise<Object>} Résultat de la mise à jour
         */
        async markAllAsRead() {
            return this.post('notifications/mark-read-all');
        }
    };
}