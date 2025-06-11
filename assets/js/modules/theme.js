/**
 * Theme module - Gestion des thèmes (clair, sombre, système)
 */

// Fonctions principales pour la gestion de thème
const ThemeManager = {
  // Thèmes disponibles
  themes: ['light', 'dark', 'system'],
  
  // Récupérer le thème actuel depuis le localStorage ou le thème par défaut
  getCurrentTheme() {
    return localStorage.getItem('theme') || 'light';
  },
  
  // Sauvegarder le thème sélectionné
  saveTheme(theme) {
    if (this.themes.includes(theme)) {
      localStorage.setItem('theme', theme);
    }
  },
  
  // Appliquer le thème
  applyTheme(theme) {
    // Supprimer toutes les classes de thème
    document.documentElement.classList.remove('light-theme', 'dark-theme');
    
    // Vérifier si c'est le thème système
    if (theme === 'system') {
      const prefersDarkMode = window.matchMedia('(prefers-color-scheme: dark)').matches;
      document.documentElement.classList.add(prefersDarkMode ? 'dark-theme' : 'light-theme');
    } else {
      // Appliquer la classe de thème directement
      document.documentElement.classList.add(`${theme}-theme`);
    }
    
    // Activer les cartes de thème dans l'interface
    document.querySelectorAll('.theme-card').forEach(card => {
      const radioInput = card.querySelector('input[type="radio"]');
      if (radioInput && radioInput.value === theme) {
        card.classList.add('active');
        radioInput.checked = true;
      } else {
        card.classList.remove('active');
        if (radioInput) radioInput.checked = false;
      }
    });
  },
  
  // Écouter les changements de préférence de thème du système
  listenForSystemThemeChanges() {
    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
    
    // Appliquer le thème si le thème actuel est 'system'
    const handleSystemThemeChange = (e) => {
      if (this.getCurrentTheme() === 'system') {
        document.documentElement.classList.remove('light-theme', 'dark-theme');
        document.documentElement.classList.add(e.matches ? 'dark-theme' : 'light-theme');
      }
    };
    
    // Écouter les changements
    try {
      // Chrome & Firefox
      mediaQuery.addEventListener('change', handleSystemThemeChange);
    } catch (error1) {
      try {
        // Safari
        mediaQuery.addListener(handleSystemThemeChange);
      } catch (error2) {
        console.error('Impossible de détecter les changements de thème système', error2);
      }
    }
  },
  
  // Initialiser le gestionnaire de thème
  init() {
    const currentTheme = this.getCurrentTheme();
    this.applyTheme(currentTheme);
    this.listenForSystemThemeChanges();
    this.initThemeSelectionUI();
    
    // Charger les feuilles de style de thèmes
    this.loadThemeStylesheets();
  },
  
  // Charger les feuilles de style
  loadThemeStylesheets() {
    // Charger la feuille de style du thème sombre si elle n'est pas déjà chargée
    if (!document.querySelector('link[href*="theme-dark.css"]')) {
      const darkTheme = document.createElement('link');
      darkTheme.rel = 'stylesheet';
      darkTheme.href = '/tutoring/assets/css/theme-dark.css';
      document.head.appendChild(darkTheme);
    }
    
    // Charger la feuille de style du thème clair si elle n'est pas déjà chargée
    if (!document.querySelector('link[href*="theme-light.css"]')) {
      const lightTheme = document.createElement('link');
      lightTheme.rel = 'stylesheet';
      lightTheme.href = '/tutoring/assets/css/theme-light.css';
      document.head.appendChild(lightTheme);
    }
  },
  
  // Initialiser l'interface utilisateur de sélection de thème
  initThemeSelectionUI() {
    // Gestion du changement de thème via les cartes de thème
    document.querySelectorAll('.theme-card').forEach(card => {
      card.addEventListener('click', () => {
        const radio = card.querySelector('input[type="radio"]');
        if (radio) {
          const theme = radio.value;
          this.saveTheme(theme);
          this.applyTheme(theme);
          
          // Envoyer les préférences au serveur si connecté
          this.savePreferencesToServer({
            theme: theme
          });
        }
      });
    });
    
    // Initialiser les radios de thème en fonction du thème actuel
    const currentTheme = this.getCurrentTheme();
    const themeRadio = document.querySelector(`input[name="theme"][value="${currentTheme}"]`);
    if (themeRadio) {
      themeRadio.checked = true;
      const card = themeRadio.closest('.theme-card');
      if (card) {
        document.querySelectorAll('.theme-card').forEach(c => c.classList.remove('active'));
        card.classList.add('active');
      }
    }
  },
  
  // Enregistrer les préférences au serveur
  savePreferencesToServer(preferences) {
    // Si l'API est disponible, mettre à jour les préférences
    if (window.api && window.api.settings && window.api.settings.updatePreferences) {
      window.api.settings.updatePreferences(preferences)
        .catch(error => {
          console.error('Erreur lors de l\'enregistrement des préférences:', error);
        });
    }
  }
};

// Initialize theme module
document.addEventListener('DOMContentLoaded', () => {
  // Initialiser le gestionnaire de thème
  ThemeManager.init();
  
  // Toggle sidebar on mobile
  const sidebarToggle = document.getElementById('sidebar-toggle');
  const sidebar = document.querySelector('.sidebar');
  const mainContent = document.querySelector('.main-content');
  
  if (sidebarToggle && sidebar && mainContent) {
    sidebarToggle.addEventListener('click', () => {
      sidebar.classList.toggle('collapsed');
      mainContent.classList.toggle('expanded');
    });
  }
  
  // Auto-hide alerts after 5 seconds
  setTimeout(() => {
    document.querySelectorAll('.alert').forEach(alert => {
      alert.classList.remove('show');
      
      // Remove from DOM after animation
      setTimeout(() => {
        if (alert.parentNode) {
          alert.parentNode.removeChild(alert);
        }
      }, 300);
    });
  }, 5000);
});
