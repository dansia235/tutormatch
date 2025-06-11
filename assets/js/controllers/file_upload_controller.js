import { Controller } from '@hotwired/stimulus';

/**
 * File Upload Controller
 * Gère l'interface utilisateur pour le téléversement de fichiers
 * avec des fonctionnalités comme le drag & drop, la prévisualisation et la validation
 */
export default class extends Controller {
  static targets = [
    'input', 
    'preview', 
    'dropzone', 
    'filename', 
    'removeBtn',
    'progress',
    'progressBar',
    'error',
    'size',
    'type'
  ];
  
  static values = {
    maxSize: { type: Number, default: 10485760 },  // 10MB par défaut
    allowedTypes: String,
    previewTypes: { type: String, default: 'image/jpeg,image/png,image/gif,image/svg+xml' },
    multiple: { type: Boolean, default: false },
    crop: { type: Boolean, default: false }
  };

  connect() {
    // Initialiser les événements pour le drag & drop
    if (this.hasDropzoneTarget) {
      this.initDropzone();
    }
    
    // Initialiser les événements pour l'input file
    if (this.hasInputTarget) {
      this.inputTarget.addEventListener('change', this.handleFileSelection.bind(this));
    }
    
    // Initialiser le bouton de suppression
    if (this.hasRemoveBtnTarget) {
      this.removeBtnTarget.addEventListener('click', this.removeFile.bind(this));
    }
  }

  disconnect() {
    // Nettoyer les événements
    if (this.hasDropzoneTarget) {
      this.cleanupDropzone();
    }
  }

  /**
   * Initialise la zone de drag & drop
   */
  initDropzone() {
    this.dropzoneTarget.addEventListener('dragover', this.handleDragOver.bind(this));
    this.dropzoneTarget.addEventListener('dragleave', this.handleDragLeave.bind(this));
    this.dropzoneTarget.addEventListener('drop', this.handleDrop.bind(this));
    this.dropzoneTarget.addEventListener('click', this.triggerFileInput.bind(this));
  }

  /**
   * Nettoie les événements de la zone de drag & drop
   */
  cleanupDropzone() {
    this.dropzoneTarget.removeEventListener('dragover', this.handleDragOver);
    this.dropzoneTarget.removeEventListener('dragleave', this.handleDragLeave);
    this.dropzoneTarget.removeEventListener('drop', this.handleDrop);
    this.dropzoneTarget.removeEventListener('click', this.triggerFileInput);
  }

  /**
   * Gère l'événement dragover
   */
  handleDragOver(event) {
    event.preventDefault();
    event.stopPropagation();
    
    this.dropzoneTarget.classList.add('border-blue-500', 'bg-blue-50');
    this.dropzoneTarget.classList.remove('border-gray-300', 'bg-gray-50');
  }

  /**
   * Gère l'événement dragleave
   */
  handleDragLeave(event) {
    event.preventDefault();
    event.stopPropagation();
    
    this.dropzoneTarget.classList.remove('border-blue-500', 'bg-blue-50');
    this.dropzoneTarget.classList.add('border-gray-300', 'bg-gray-50');
  }

  /**
   * Gère l'événement drop
   */
  handleDrop(event) {
    event.preventDefault();
    event.stopPropagation();
    
    this.dropzoneTarget.classList.remove('border-blue-500', 'bg-blue-50');
    this.dropzoneTarget.classList.add('border-gray-300', 'bg-gray-50');
    
    const files = event.dataTransfer.files;
    this.handleFiles(files);
  }

  /**
   * Déclenche le clic sur l'input file
   */
  triggerFileInput(event) {
    // Ne rien faire si on clique sur le bouton de suppression
    if (event.target === this.removeBtnTarget) {
      return;
    }
    
    this.inputTarget.click();
  }

  /**
   * Gère la sélection de fichier via l'input
   */
  handleFileSelection(event) {
    const files = this.inputTarget.files;
    this.handleFiles(files);
  }

  /**
   * Traite les fichiers sélectionnés
   */
  handleFiles(files) {
    // Réinitialiser les erreurs
    this.hideError();
    
    // Vérifier si des fichiers sont sélectionnés
    if (!files || files.length === 0) {
      return;
    }
    
    // Si le mode multiple n'est pas activé, ne traiter que le premier fichier
    const selectedFiles = this.multipleValue ? files : [files[0]];
    
    // Valider les fichiers
    for (let i = 0; i < selectedFiles.length; i++) {
      const file = selectedFiles[i];
      
      // Vérifier la taille du fichier
      if (file.size > this.maxSizeValue) {
        this.showError(`Le fichier "${file.name}" dépasse la taille maximale autorisée (${this.formatFileSize(this.maxSizeValue)}).`);
        return;
      }
      
      // Vérifier le type du fichier
      if (this.hasAllowedTypesValue && this.allowedTypesValue) {
        const allowedTypes = this.allowedTypesValue.split(',');
        let isTypeAllowed = false;
        
        for (let j = 0; j < allowedTypes.length; j++) {
          const allowedType = allowedTypes[j].trim();
          if (file.type === allowedType || 
              (allowedType.endsWith('/*') && file.type.startsWith(allowedType.replace('/*', '/')))) {
            isTypeAllowed = true;
            break;
          }
          
          // Vérifier l'extension pour les types spéciaux comme .pdf, .doc, etc.
          const extension = '.' + file.name.split('.').pop().toLowerCase();
          if (allowedType.startsWith('.') && extension === allowedType) {
            isTypeAllowed = true;
            break;
          }
        }
        
        if (!isTypeAllowed) {
          this.showError(`Le type de fichier "${file.name}" n'est pas autorisé. Types autorisés : ${this.allowedTypesValue}`);
          return;
        }
      }
    }
    
    // Mise à jour de l'interface pour le fichier sélectionné
    this.updateUI(selectedFiles);
  }

  /**
   * Met à jour l'interface pour les fichiers sélectionnés
   */
  updateUI(files) {
    // Afficher le nom du fichier
    if (this.hasFilenameTarget) {
      if (files.length === 1) {
        this.filenameTarget.textContent = files[0].name;
        
        // Afficher la taille du fichier
        if (this.hasSizeTarget) {
          this.sizeTarget.textContent = this.formatFileSize(files[0].size);
        }
        
        // Afficher le type du fichier
        if (this.hasTypeTarget) {
          this.typeTarget.textContent = this.formatFileType(files[0]);
        }
      } else {
        this.filenameTarget.textContent = `${files.length} fichiers sélectionnés`;
      }
    }
    
    // Afficher la prévisualisation pour les images
    if (this.hasPreviewTarget) {
      this.updatePreview(files);
    }
    
    // Afficher le bouton de suppression
    if (this.hasRemoveBtnTarget) {
      this.removeBtnTarget.classList.remove('hidden');
    }
    
    // Mettre à jour le style de la dropzone
    if (this.hasDropzoneTarget) {
      this.dropzoneTarget.classList.add('has-files');
    }
    
    // Émettre un événement personnalisé
    this.dispatch('fileSelected', {
      detail: {
        files: files,
        fileCount: files.length
      }
    });
  }

  /**
   * Met à jour la prévisualisation des fichiers (images)
   */
  updatePreview(files) {
    this.previewTarget.innerHTML = '';
    
    for (let i = 0; i < files.length; i++) {
      const file = files[i];
      const isPreviewable = this.previewTypesValue.split(',').some(type => {
        return file.type === type.trim() || 
               (type.trim().endsWith('/*') && file.type.startsWith(type.trim().replace('/*', '/')));
      });
      
      if (isPreviewable) {
        const reader = new FileReader();
        
        reader.onload = (e) => {
          const img = document.createElement('img');
          img.src = e.target.result;
          img.classList.add('object-cover', 'h-full', 'w-full', 'rounded-md');
          
          this.previewTarget.appendChild(img);
        };
        
        reader.readAsDataURL(file);
      } else {
        // Afficher une icône pour les types non prévisualisables
        this.previewTarget.innerHTML = this.getFileIconHTML(file);
      }
    }
    
    this.previewTarget.classList.remove('hidden');
  }

  /**
   * Obtient l'HTML pour l'icône de fichier
   */
  getFileIconHTML(file) {
    const extension = file.name.split('.').pop().toLowerCase();
    let iconClass = 'fa-file';
    let colorClass = 'text-gray-500';
    
    // Déterminer l'icône en fonction du type de fichier
    if (file.type.startsWith('image/')) {
      iconClass = 'fa-file-image';
      colorClass = 'text-blue-500';
    } else if (file.type === 'application/pdf') {
      iconClass = 'fa-file-pdf';
      colorClass = 'text-red-500';
    } else if (file.type.includes('word') || extension === 'doc' || extension === 'docx') {
      iconClass = 'fa-file-word';
      colorClass = 'text-blue-700';
    } else if (file.type.includes('spreadsheet') || file.type.includes('excel') || extension === 'xls' || extension === 'xlsx') {
      iconClass = 'fa-file-excel';
      colorClass = 'text-green-600';
    } else if (file.type.includes('presentation') || file.type.includes('powerpoint') || extension === 'ppt' || extension === 'pptx') {
      iconClass = 'fa-file-powerpoint';
      colorClass = 'text-orange-500';
    } else if (file.type === 'application/zip' || file.type === 'application/x-zip-compressed' || extension === 'zip') {
      iconClass = 'fa-file-archive';
      colorClass = 'text-amber-500';
    } else if (file.type.startsWith('text/')) {
      iconClass = 'fa-file-alt';
      colorClass = 'text-slate-600';
    }
    
    return `
      <div class="flex flex-col items-center justify-center h-full">
        <i class="fas ${iconClass} text-4xl ${colorClass} mb-2"></i>
        <span class="text-xs text-center text-gray-500 truncate max-w-full">${file.name}</span>
      </div>
    `;
  }

  /**
   * Supprime le fichier sélectionné
   */
  removeFile(event) {
    event.preventDefault();
    event.stopPropagation();
    
    // Réinitialiser l'input
    this.inputTarget.value = '';
    
    // Masquer la prévisualisation
    if (this.hasPreviewTarget) {
      this.previewTarget.innerHTML = '';
      this.previewTarget.classList.add('hidden');
    }
    
    // Masquer le nom du fichier
    if (this.hasFilenameTarget) {
      this.filenameTarget.textContent = '';
    }
    
    // Masquer la taille du fichier
    if (this.hasSizeTarget) {
      this.sizeTarget.textContent = '';
    }
    
    // Masquer le type du fichier
    if (this.hasTypeTarget) {
      this.typeTarget.textContent = '';
    }
    
    // Masquer le bouton de suppression
    if (this.hasRemoveBtnTarget) {
      this.removeBtnTarget.classList.add('hidden');
    }
    
    // Réinitialiser le style de la dropzone
    if (this.hasDropzoneTarget) {
      this.dropzoneTarget.classList.remove('has-files');
    }
    
    // Émettre un événement personnalisé
    this.dispatch('fileRemoved');
  }

  /**
   * Affiche un message d'erreur
   */
  showError(message) {
    if (this.hasErrorTarget) {
      this.errorTarget.textContent = message;
      this.errorTarget.classList.remove('hidden');
    } else {
      console.error(message);
    }
  }

  /**
   * Masque le message d'erreur
   */
  hideError() {
    if (this.hasErrorTarget) {
      this.errorTarget.textContent = '';
      this.errorTarget.classList.add('hidden');
    }
  }

  /**
   * Formate la taille du fichier
   */
  formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  }

  /**
   * Formate le type de fichier
   */
  formatFileType(file) {
    const extension = file.name.split('.').pop().toLowerCase();
    
    // Mapper les types MIME à des noms plus conviviaux
    const mimeTypeMap = {
      'application/pdf': 'PDF',
      'application/msword': 'Word',
      'application/vnd.openxmlformats-officedocument.wordprocessingml.document': 'Word',
      'application/vnd.ms-excel': 'Excel',
      'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': 'Excel',
      'application/vnd.ms-powerpoint': 'PowerPoint',
      'application/vnd.openxmlformats-officedocument.presentationml.presentation': 'PowerPoint',
      'image/jpeg': 'Image JPEG',
      'image/png': 'Image PNG',
      'image/gif': 'Image GIF',
      'image/svg+xml': 'Image SVG'
    };
    
    return mimeTypeMap[file.type] || `Fichier .${extension}`;
  }
}