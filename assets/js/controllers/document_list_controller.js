import { Controller } from '@hotwired/stimulus';

/**
 * Document List Controller
 * Gère les fonctionnalités de liste de documents (recherche, filtrage, tri)
 */
export default class extends Controller {
  static targets = ['container', 'search', 'filterLabel', 'sortLabel', 'emptyMessage'];
  
  static values = {
    defaultFilter: { type: String, default: 'all' },
    defaultSort: { type: String, default: 'date-desc' }
  };

  connect() {
    // Initialiser avec le filtre par défaut
    this.currentFilter = this.defaultFilterValue;
    this.currentSort = this.defaultSortValue;
    
    // Appliquer les filtres initiaux
    this.applyFilters();
  }
  
  /**
   * Gère la recherche de documents
   */
  search() {
    this.applyFilters();
  }
  
  /**
   * Filtre les documents par catégorie
   * @param {Event} event Événement de clic
   */
  filter(event) {
    // Mise à jour de l'état des boutons de filtre
    const filterButtons = this.element.querySelectorAll('[data-filter]');
    filterButtons.forEach(button => {
      button.classList.remove('active-filter', 'bg-gray-100', 'text-blue-600');
    });
    
    event.currentTarget.classList.add('active-filter', 'bg-gray-100', 'text-blue-600');
    
    // Mettre à jour le filtre actif
    this.currentFilter = event.currentTarget.dataset.filter;
    
    // Mettre à jour le libellé du filtre
    if (this.hasFilterLabelTarget) {
      this.filterLabelTarget.textContent = event.currentTarget.querySelector('span').firstChild.textContent;
    }
    
    // Appliquer les filtres
    this.applyFilters();
    
    // Si un menu dropdown est utilisé, fermer le menu
    const dropdown = event.currentTarget.closest('[data-controller="dropdown"]');
    if (dropdown) {
      const dropdownController = this.application.getControllerForElementAndIdentifier(dropdown, 'dropdown');
      if (dropdownController) {
        dropdownController.hide();
      }
    }
  }
  
  /**
   * Trie les documents
   * @param {Event} event Événement de clic
   */
  sort(event) {
    // Mise à jour de l'état des boutons de tri
    const sortButtons = this.element.querySelectorAll('[data-sort]');
    sortButtons.forEach(button => {
      button.classList.remove('active-sort', 'bg-gray-100', 'text-blue-600');
    });
    
    event.currentTarget.classList.add('active-sort', 'bg-gray-100', 'text-blue-600');
    
    // Mettre à jour le tri actif
    this.currentSort = event.currentTarget.dataset.sort;
    
    // Mettre à jour le libellé du tri
    if (this.hasSortLabelTarget) {
      this.sortLabelTarget.textContent = event.currentTarget.textContent.trim();
    }
    
    // Appliquer les filtres
    this.applyFilters();
    
    // Si un menu dropdown est utilisé, fermer le menu
    const dropdown = event.currentTarget.closest('[data-controller="dropdown"]');
    if (dropdown) {
      const dropdownController = this.application.getControllerForElementAndIdentifier(dropdown, 'dropdown');
      if (dropdownController) {
        dropdownController.hide();
      }
    }
  }
  
  /**
   * Applique les filtres de recherche, catégorie et tri
   */
  applyFilters() {
    const searchTerm = this.hasSearchTarget ? this.searchTarget.value.toLowerCase() : '';
    const items = this.containerTarget.querySelectorAll('.document-item');
    let visibleCount = 0;
    
    // Appliquer filtres
    items.forEach(item => {
      // Filtrer par catégorie
      const categoryMatch = this.currentFilter === 'all' || item.dataset.category === this.currentFilter;
      
      // Filtrer par recherche
      const title = item.querySelector('h4, h5')?.textContent.toLowerCase() || '';
      const description = item.querySelector('.text-gray-600, [class*="text-gray-"]')?.textContent.toLowerCase() || '';
      const content = title + ' ' + description;
      const searchMatch = searchTerm === '' || content.includes(searchTerm);
      
      // Appliquer le filtre combiné
      if (categoryMatch && searchMatch) {
        item.classList.remove('hidden');
        visibleCount++;
      } else {
        item.classList.add('hidden');
      }
    });
    
    // Vérifier s'il faut afficher le message "aucun résultat"
    this.toggleEmptyMessage(visibleCount === 0);
    
    // Trier les éléments
    this.sortItems();
  }
  
  /**
   * Trie les éléments de la liste
   */
  sortItems() {
    const container = this.containerTarget;
    const items = Array.from(container.querySelectorAll('.document-item:not(.hidden)'));
    
    // Trier les éléments selon le critère
    items.sort((a, b) => {
      const sortType = this.currentSort;
      
      switch (sortType) {
        case 'date-desc':
          // Trier par date (plus récente en premier)
          const dateA = this.getDocumentDate(a);
          const dateB = this.getDocumentDate(b);
          return dateB - dateA;
          
        case 'date-asc':
          // Trier par date (plus ancienne en premier)
          const dateAsc1 = this.getDocumentDate(a);
          const dateAsc2 = this.getDocumentDate(b);
          return dateAsc1 - dateAsc2;
          
        case 'name':
          // Trier par nom
          const nameA = this.getDocumentTitle(a).toLowerCase();
          const nameB = this.getDocumentTitle(b).toLowerCase();
          return nameA.localeCompare(nameB);
          
        case 'type':
          // Trier par type
          const typeA = a.dataset.category || '';
          const typeB = b.dataset.category || '';
          return typeA.localeCompare(typeB);
          
        case 'size':
          // Trier par taille
          const sizeA = this.getDocumentSize(a);
          const sizeB = this.getDocumentSize(b);
          return sizeB - sizeA;
          
        default:
          return 0;
      }
    });
    
    // Réorganiser les éléments dans le DOM
    items.forEach(item => {
      container.appendChild(item);
    });
  }
  
  /**
   * Récupère la date d'un document
   * @param {Element} element Élément de document
   * @returns {number} Timestamp
   */
  getDocumentDate(element) {
    // Chercher un élément avec la date dans le document
    const dateText = element.querySelector('.document-date')?.textContent || 
                    element.querySelector('.text-xs.text-gray-500')?.textContent || 
                    '';
    
    // Essayer d'extraire une date au format DD/MM/YYYY
    const dateMatch = dateText.match(/(\d{2}\/\d{2}\/\d{4})/);
    if (dateMatch) {
      const parts = dateMatch[0].split('/');
      return new Date(parts[2], parts[1] - 1, parts[0]).getTime();
    }
    
    return 0;
  }
  
  /**
   * Récupère le titre d'un document
   * @param {Element} element Élément de document
   * @returns {string} Titre
   */
  getDocumentTitle(element) {
    return element.querySelector('h4, h5')?.textContent || '';
  }
  
  /**
   * Récupère la taille d'un document
   * @param {Element} element Élément de document
   * @returns {number} Taille en octets (approximative)
   */
  getDocumentSize(element) {
    const sizeText = element.querySelector('.document-size')?.textContent || 
                    element.querySelector('.text-xs.text-gray-500')?.textContent || 
                    '';
    
    // Extraire la taille et l'unité (ex: "5.2 Mo")
    const sizeMatch = sizeText.match(/(\d+(\.\d+)?)\s*(o|Ko|Mo|Go)/i);
    if (sizeMatch) {
      const size = parseFloat(sizeMatch[1]);
      const unit = sizeMatch[3].toLowerCase();
      
      // Convertir en octets
      switch (unit) {
        case 'go':
          return size * 1024 * 1024 * 1024;
        case 'mo':
          return size * 1024 * 1024;
        case 'ko':
          return size * 1024;
        default:
          return size;
      }
    }
    
    return 0;
  }
  
  /**
   * Affiche ou masque le message "aucun résultat"
   * @param {boolean} isEmpty Indique si la liste est vide
   */
  toggleEmptyMessage(isEmpty) {
    if (this.hasEmptyMessageTarget) {
      this.emptyMessageTarget.classList.toggle('hidden', !isEmpty);
    }
  }
}