/**
 * API Interceptor
 * Intercepte et transforme les requêtes et réponses HTTP
 */

const API_ENDPOINT = '/tutoring/api';

/**
 * Configure l'intercepteur pour les requêtes fetch globales
 */
export function setupApiInterceptor() {
    // Sauvegarder la fonction fetch d'origine
    const originalFetch = window.fetch;
    
    // Redéfinir la fonction fetch
    window.fetch = async function(url, options = {}) {
        // Vérifier si la requête est destinée à notre API
        if (typeof url === 'string' && url.includes(API_ENDPOINT)) {
            return interceptApiRequest(url, options, originalFetch);
        }
        
        // Pour les autres requêtes, utiliser fetch normal
        return originalFetch(url, options);
    };
}

/**
 * Intercepte une requête API
 * @param {string|Request} url URL ou objet Request
 * @param {Object} options Options de la requête fetch
 * @param {Function} originalFetch Fonction fetch d'origine
 * @returns {Promise<Response>} Réponse de la requête
 */
async function interceptApiRequest(url, options, originalFetch) {
    // Préparer les options avec les en-têtes par défaut
    const modifiedOptions = {
        ...options,
        headers: {
            ...options.headers,
            'X-Requested-With': 'XMLHttpRequest'
        }
    };
    
    // Ajouter le jeton CSRF pour les requêtes mutatives
    const method = options.method?.toUpperCase() || 'GET';
    if (method !== 'GET' && method !== 'HEAD') {
        const csrfToken = getCsrfToken();
        if (csrfToken) {
            // Ajouter à l'en-tête si requis par l'API
            modifiedOptions.headers['X-CSRF-Token'] = csrfToken;
            
            // Pour les requêtes avec corps JSON, ajouter le jeton au corps
            if (options.body && 
                options.headers && 
                options.headers['Content-Type'] === 'application/json') {
                try {
                    const bodyData = JSON.parse(options.body);
                    bodyData.csrf_token = csrfToken;
                    modifiedOptions.body = JSON.stringify(bodyData);
                } catch (e) {
                    console.warn('Impossible d\'ajouter le jeton CSRF au corps JSON', e);
                }
            }
            
            // Pour les requêtes FormData
            if (options.body instanceof FormData) {
                options.body.append('csrf_token', csrfToken);
            }
        }
    }
    
    // Ajouter des écouteurs d'événements pour le chargement
    const startTime = performance.now();
    
    // Notification de début de requête
    document.dispatchEvent(new CustomEvent('api:requestStart', {
        detail: { url, method }
    }));
    
    try {
        // Effectuer la requête
        const response = await originalFetch(url, modifiedOptions);
        
        // Calculer la durée
        const duration = Math.round(performance.now() - startTime);
        
        // Notification de fin de requête réussie
        document.dispatchEvent(new CustomEvent('api:requestEnd', {
            detail: { url, method, status: response.status, duration }
        }));
        
        // Gérer les réponses d'erreur
        if (!response.ok) {
            const errorEvent = new CustomEvent('api:requestError', {
                detail: {
                    url,
                    method,
                    status: response.status,
                    statusText: response.statusText,
                    duration
                }
            });
            document.dispatchEvent(errorEvent);
            
            // Gérer la session expirée
            if (response.status === 401) {
                document.dispatchEvent(new Event('api:unauthorized'));
            }
        }
        
        return response;
    } catch (error) {
        // Notification d'erreur réseau
        document.dispatchEvent(new CustomEvent('api:networkError', {
            detail: { url, method, error, duration: Math.round(performance.now() - startTime) }
        }));
        
        throw error;
    }
}

/**
 * Récupère le jeton CSRF depuis les métadonnées de la page
 * @returns {string|null} Jeton CSRF ou null si non trouvé
 */
function getCsrfToken() {
    // Chercher le jeton dans les méta tags
    const metaTag = document.querySelector('meta[name="csrf-token"]');
    if (metaTag) {
        return metaTag.getAttribute('content');
    }
    
    // Chercher dans un champ caché
    const inputField = document.querySelector('input[name="csrf_token"]');
    if (inputField) {
        return inputField.value;
    }
    
    // Chercher dans les données JavaScript
    if (window.CSRF_TOKEN) {
        return window.CSRF_TOKEN;
    }
    
    return null;
}

/**
 * Initialise l'intercepteur API et les écouteurs d'événements
 */
export function initializeApiInterceptor() {
    setupApiInterceptor();
    
    // Écouteur pour la gestion des sessions expirées
    document.addEventListener('api:unauthorized', () => {
        // Vérifier que nous ne sommes pas déjà sur la page de connexion
        if (!window.location.pathname.includes('/login.php')) {
            // Rediriger vers la page de connexion avec un paramètre d'expiration de session
            window.location.href = '/tutoring/login.php?session_expired=1';
        }
    });
    
    // Initialiser le compteur de requêtes actives
    let activeRequests = 0;
    const loadingIndicator = document.getElementById('global-loading-indicator');
    
    document.addEventListener('api:requestStart', () => {
        activeRequests++;
        if (loadingIndicator) {
            loadingIndicator.classList.remove('hidden');
        }
    });
    
    const hideLoaderIfDone = () => {
        activeRequests = Math.max(0, activeRequests - 1);
        if (activeRequests === 0 && loadingIndicator) {
            loadingIndicator.classList.add('hidden');
        }
    };
    
    document.addEventListener('api:requestEnd', hideLoaderIfDone);
    document.addEventListener('api:requestError', hideLoaderIfDone);
    document.addEventListener('api:networkError', hideLoaderIfDone);
}

// Initialiser l'intercepteur automatiquement si nous sommes dans un navigateur
if (typeof window !== 'undefined') {
    document.addEventListener('DOMContentLoaded', initializeApiInterceptor);
}