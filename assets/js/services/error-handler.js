/**
 * Error Handler Service
 * Gère la validation, l'affichage et la journalisation des erreurs
 */

/**
 * Types d'erreurs
 */
const ErrorType = {
    VALIDATION: 'validation',
    API: 'api',
    NETWORK: 'network',
    AUTH: 'auth',
    GENERAL: 'general'
};

/**
 * Classe principale de gestion des erreurs
 */
class ErrorHandler {
    constructor() {
        this.errorListeners = [];
    }

    /**
     * Gère une erreur
     * @param {Error} error Erreur à traiter
     * @param {string} context Contexte où l'erreur s'est produite
     * @returns {Object} Informations sur l'erreur traitée
     */
    handleError(error, context = '') {
        // Déterminer le type d'erreur
        const errorType = this.getErrorType(error);
        
        // Formater le message d'erreur
        const message = this.formatErrorMessage(error, errorType);
        
        // Journaliser l'erreur
        this.logError(error, errorType, context);
        
        // Notifier les écouteurs
        this.notifyErrorListeners(error, errorType, context, message);
        
        return {
            type: errorType,
            message,
            original: error,
            context
        };
    }

    /**
     * Détermine le type d'erreur
     * @param {Error} error Erreur à analyser
     * @returns {string} Type d'erreur
     */
    getErrorType(error) {
        if (error.name === 'ValidationError' || error.validation) {
            return ErrorType.VALIDATION;
        }
        
        if (error.name === 'AuthenticationError' || error.status === 401) {
            return ErrorType.AUTH;
        }
        
        if (error.name === 'NetworkError' || error.message.includes('network') || error.message.includes('fetch')) {
            return ErrorType.NETWORK;
        }
        
        if (error.status || error.response || error.statusCode) {
            return ErrorType.API;
        }
        
        return ErrorType.GENERAL;
    }

    /**
     * Formate le message d'erreur pour l'affichage
     * @param {Error} error Erreur à formater
     * @param {string} errorType Type d'erreur
     * @returns {string} Message formaté
     */
    formatErrorMessage(error, errorType) {
        // Pour les erreurs de validation, formatter un message spécifique
        if (errorType === ErrorType.VALIDATION && error.validation) {
            const validationErrors = error.validation;
            if (Array.isArray(validationErrors)) {
                return validationErrors.join('<br>');
            } else if (typeof validationErrors === 'object') {
                return Object.values(validationErrors).join('<br>');
            }
        }
        
        // Messages spécifiques selon le type d'erreur
        switch (errorType) {
            case ErrorType.AUTH:
                return "Votre session a expiré ou vous n'êtes pas autorisé à accéder à cette ressource.";
            case ErrorType.NETWORK:
                return "Erreur de connexion au serveur. Veuillez vérifier votre connexion internet et réessayer.";
            case ErrorType.API:
                // Essayer d'extraire un message d'erreur spécifique de la réponse API
                if (error.data && error.data.message) {
                    return error.data.message;
                }
                return `Erreur de serveur (${error.status || 500}). Veuillez réessayer ultérieurement.`;
            default:
                // Utiliser le message d'origine ou un message par défaut
                return error.message || "Une erreur inattendue s'est produite.";
        }
    }

    /**
     * Journalise l'erreur
     * @param {Error} error Erreur originale
     * @param {string} errorType Type d'erreur
     * @param {string} context Contexte de l'erreur
     */
    logError(error, errorType, context) {
        // En développement, afficher l'erreur dans la console
        if (process.env.NODE_ENV !== 'production') {
            console.group(`Erreur (${errorType}): ${context}`);
            console.error(error);
            console.groupEnd();
        } else {
            // En production, on pourrait envoyer l'erreur à un service de suivi d'erreurs
            // Par exemple, Sentry, LogRocket, etc.
            console.error(`[${errorType}] ${context}: ${error.message}`);
        }
    }

    /**
     * Valide des données selon des règles spécifiées
     * @param {Object} data Données à valider
     * @param {Object} rules Règles de validation
     * @returns {Object|null} Erreurs de validation ou null si valide
     */
    validateData(data, rules) {
        const errors = {};
        
        for (const [field, rule] of Object.entries(rules)) {
            const value = data[field];
            
            // Règle requise
            if (rule.required && (value === undefined || value === null || value === '')) {
                errors[field] = rule.message || `Le champ ${field} est requis`;
                continue;
            }
            
            // Si le champ n'est pas présent et n'est pas requis, passer à la suite
            if (value === undefined || value === null || value === '') {
                continue;
            }
            
            // Validation de type
            if (rule.type === 'number' && isNaN(Number(value))) {
                errors[field] = rule.message || `Le champ ${field} doit être un nombre`;
            }
            
            if (rule.type === 'email' && !this.isValidEmail(value)) {
                errors[field] = rule.message || `Le champ ${field} doit être une adresse email valide`;
            }
            
            // Validation de longueur
            if (rule.minLength && String(value).length < rule.minLength) {
                errors[field] = rule.message || `Le champ ${field} doit contenir au moins ${rule.minLength} caractères`;
            }
            
            if (rule.maxLength && String(value).length > rule.maxLength) {
                errors[field] = rule.message || `Le champ ${field} ne doit pas dépasser ${rule.maxLength} caractères`;
            }
            
            // Validation de valeur
            if (rule.min && Number(value) < rule.min) {
                errors[field] = rule.message || `Le champ ${field} doit être supérieur ou égal à ${rule.min}`;
            }
            
            if (rule.max && Number(value) > rule.max) {
                errors[field] = rule.message || `Le champ ${field} doit être inférieur ou égal à ${rule.max}`;
            }
            
            // Validation de correspondance de mots de passe
            if (rule.match && data[rule.match] !== value) {
                errors[field] = rule.message || `Le champ ${field} doit correspondre à ${rule.match}`;
            }
            
            // Validation avec une fonction personnalisée
            if (rule.validator && typeof rule.validator === 'function') {
                const isValid = rule.validator(value, data);
                if (!isValid) {
                    errors[field] = rule.message || `Le champ ${field} est invalide`;
                }
            }
        }
        
        return Object.keys(errors).length > 0 ? errors : null;
    }

    /**
     * Valide une adresse e-mail
     * @param {string} email Adresse e-mail à valider
     * @returns {boolean} Vrai si l'adresse est valide
     */
    isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(email).toLowerCase());
    }

    /**
     * Ajoute un écouteur d'erreurs
     * @param {Function} listener Fonction de rappel qui reçoit l'erreur
     * @returns {Function} Fonction pour supprimer l'écouteur
     */
    addErrorListener(listener) {
        if (typeof listener !== 'function') return () => {};
        
        this.errorListeners.push(listener);
        
        return () => {
            this.errorListeners = this.errorListeners.filter(l => l !== listener);
        };
    }

    /**
     * Notifie tous les écouteurs d'une erreur
     * @param {Error} error Erreur originale
     * @param {string} errorType Type d'erreur
     * @param {string} context Contexte de l'erreur
     * @param {string} message Message formaté
     */
    notifyErrorListeners(error, errorType, context, message) {
        this.errorListeners.forEach(listener => {
            try {
                listener({
                    error,
                    type: errorType,
                    context,
                    message
                });
            } catch (err) {
                console.error('Erreur dans un écouteur d\'erreurs:', err);
            }
        });
    }

    /**
     * Affiche un message d'erreur dans un élément HTML
     * @param {string} message Message d'erreur
     * @param {HTMLElement|string} container Élément ou ID de l'élément où afficher l'erreur
     */
    displayError(message, container) {
        let errorElement;
        
        if (typeof container === 'string') {
            errorElement = document.getElementById(container);
        } else {
            errorElement = container;
        }
        
        if (!errorElement) return;
        
        errorElement.innerHTML = message;
        errorElement.classList.remove('hidden');
        
        // Faire défiler jusqu'à l'erreur si elle n'est pas visible
        if (!this.isElementInViewport(errorElement)) {
            errorElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    /**
     * Vérifie si un élément est visible dans le viewport
     * @param {HTMLElement} el Élément à vérifier
     * @returns {boolean} Vrai si l'élément est visible
     */
    isElementInViewport(el) {
        const rect = el.getBoundingClientRect();
        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        );
    }
}

// Créer et exporter une instance unique
const errorHandler = new ErrorHandler();
export default errorHandler;

// Exporter également les constantes
export { ErrorType };