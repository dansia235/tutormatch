import { Controller } from '@hotwired/stimulus';
import authService from '../services/auth-service';

/**
 * Auth Controller
 * Gère les interactions d'authentification de l'utilisateur
 */
export default class extends Controller {
  static targets = ['form', 'error', 'loading', 'username', 'password', 'status', 'profile'];
  static values = {
    redirectUrl: String,
    sessionExpiredMessage: String
  };
  
  connect() {
    // Vérifier si l'URL contient un paramètre d'expiration de session
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('session_expired') && this.hasSessionExpiredMessageValue) {
      this.showError(this.sessionExpiredMessageValue || 'Votre session a expiré. Veuillez vous reconnecter.');
    }
    
    // Mettre à jour l'interface selon l'état d'authentification
    this.updateAuthUI();
    
    // Ajouter un écouteur pour les changements d'authentification
    this.authListener = authService.addListener((event) => {
      this.updateAuthUI();
    });
  }
  
  disconnect() {
    // Supprimer l'écouteur d'authentification
    if (this.authListener) {
      this.authListener();
    }
  }
  
  /**
   * Met à jour l'interface utilisateur selon l'état d'authentification
   */
  updateAuthUI() {
    const isAuthenticated = authService.isAuthenticated();
    
    // Mettre à jour le statut si l'élément existe
    if (this.hasStatusTarget) {
      this.statusTarget.textContent = isAuthenticated 
        ? `Connecté en tant que ${authService.getUserName()}`
        : 'Non connecté';
    }
    
    // Mettre à jour le profil si l'élément existe
    if (this.hasProfileTarget) {
      const user = authService.getUser();
      
      if (user) {
        this.profileTarget.innerHTML = `
          <div class="text-sm font-medium">${user.first_name} ${user.last_name}</div>
          <div class="text-xs text-gray-500">${user.email}</div>
          <div class="text-xs text-gray-500 mt-1">Rôle: ${this.formatRole(user.role)}</div>
        `;
      } else {
        this.profileTarget.innerHTML = `<div class="text-sm">Non connecté</div>`;
      }
    }
    
    // Déclencher un événement personnalisé pour informer les autres composants
    this.dispatch('statusChanged', { detail: { isAuthenticated, user: authService.getUser() } });
  }
  
  /**
   * Gère la soumission du formulaire de connexion
   * @param {Event} event L'événement de soumission
   */
  async login(event) {
    event.preventDefault();
    
    if (!this.hasFormTarget || !this.hasUsernameTarget || !this.hasPasswordTarget) {
      return;
    }
    
    // Récupérer les identifiants
    const username = this.usernameTarget.value;
    const password = this.passwordTarget.value;
    
    // Valider les entrées
    if (!username || !password) {
      this.showError('Veuillez saisir votre nom d\'utilisateur et votre mot de passe');
      return;
    }
    
    this.showLoading();
    this.hideError();
    
    try {
      // Tenter de se connecter
      await authService.login(username, password);
      
      // Succès, rediriger si une URL est définie
      if (this.hasRedirectUrlValue && this.redirectUrlValue) {
        window.location.href = this.redirectUrlValue;
      } else {
        // Sinon, mettre simplement à jour l'UI
        this.updateAuthUI();
      }
    } catch (error) {
      // Afficher l'erreur
      const message = error.message || 'Échec de connexion. Veuillez vérifier vos identifiants.';
      this.showError(message);
    } finally {
      this.hideLoading();
    }
  }
  
  /**
   * Gère la déconnexion
   * @param {Event} event L'événement de clic
   */
  async logout(event) {
    if (event) event.preventDefault();
    
    this.showLoading();
    
    try {
      await authService.logout();
      
      // Rediriger vers la page de connexion
      window.location.href = '/tutoring/login.php';
    } catch (error) {
      console.error('Erreur lors de la déconnexion:', error);
      
      // En cas d'erreur, forcer la déconnexion
      await authService.logout(true);
      window.location.href = '/tutoring/login.php';
    } finally {
      this.hideLoading();
    }
  }
  
  /**
   * Affiche un message d'erreur
   * @param {string} message Message d'erreur à afficher
   */
  showError(message) {
    if (this.hasErrorTarget) {
      this.errorTarget.textContent = message;
      this.errorTarget.classList.remove('hidden');
    }
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
   * Affiche l'indicateur de chargement
   */
  showLoading() {
    if (this.hasLoadingTarget) {
      this.loadingTarget.classList.remove('hidden');
    }
    
    // Désactiver le formulaire
    if (this.hasFormTarget) {
      this.formTarget.querySelectorAll('input, button').forEach(el => {
        el.disabled = true;
      });
    }
  }
  
  /**
   * Masque l'indicateur de chargement
   */
  hideLoading() {
    if (this.hasLoadingTarget) {
      this.loadingTarget.classList.add('hidden');
    }
    
    // Réactiver le formulaire
    if (this.hasFormTarget) {
      this.formTarget.querySelectorAll('input, button').forEach(el => {
        el.disabled = false;
      });
    }
  }
  
  /**
   * Formate un rôle pour l'affichage
   * @param {string} role Code du rôle
   * @returns {string} Libellé du rôle formaté
   */
  formatRole(role) {
    const roles = {
      'admin': 'Administrateur',
      'coordinator': 'Coordinateur',
      'teacher': 'Tuteur',
      'student': 'Étudiant',
      'company': 'Entreprise'
    };
    
    return roles[role] || role;
  }
}