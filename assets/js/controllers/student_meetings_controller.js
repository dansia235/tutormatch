/**
 * Student Meetings Controller - Version Bootstrap
 * Gère l'interface des réunions pour les étudiants
 */
import { Controller } from "@hotwired/stimulus";
import apiClient from '../api-client';

export default class extends Controller {
  static targets = [
    "meetingsList", "emptyState", "loadingIndicator", 
    "modal", "modalContent", "modalTitle",
    "confirmCancelDialog", "meetingIdField"
  ];
  
  static values = {
    assignmentId: Number,
    apiUrl: { type: String, default: "/tutoring/api/meetings" }
  };
  
  connect() {
    // Initialiser le client API
    this.api = new ApiClient();
    
    // Charger les réunions
    this.loadMeetings();
  }
  
  async loadMeetings() {
    if (this.hasLoadingIndicatorTarget) {
      this.loadingIndicatorTarget.classList.remove("hidden");
    }
    
    try {
      // Récupérer les réunions et statistiques de l'étudiant
      const response = await this.api.meetings.getStudentMeetings();
      
      // Afficher les réunions
      this.displayMeetings(response.meetings || { upcoming: [], past: [], cancelled: [] });
      
    } catch (error) {
      console.error("Erreur lors du chargement des réunions:", error);
      if (this.hasMeetingsListTarget) {
        this.meetingsListTarget.innerHTML = `
          <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            Erreur lors du chargement des réunions. Veuillez rafraîchir la page.
          </div>
        `;
      }
    } finally {
      if (this.hasLoadingIndicatorTarget) {
        this.loadingIndicatorTarget.classList.add("hidden");
      }
    }
  }
  
  displayMeetings(meetings) {
    if (!this.hasMeetingsListTarget) return;
    
    const meetingsList = this.meetingsListTarget;
    const allMeetings = [...(meetings.upcoming || []), ...(meetings.past || []), ...(meetings.cancelled || [])];
    
    // Afficher l'état vide si aucune réunion
    if (allMeetings.length === 0) {
      meetingsList.innerHTML = '';
      if (this.hasEmptyStateTarget) {
        this.emptyStateTarget.classList.remove("hidden");
      }
      return;
    }
    
    // Cacher l'état vide s'il y a des réunions
    if (this.hasEmptyStateTarget) {
      this.emptyStateTarget.classList.add("hidden");
    }
    
    // Vider la liste des réunions
    meetingsList.innerHTML = '';
    
    // Afficher les réunions à venir
    if (meetings.upcoming && meetings.upcoming.length > 0) {
      const upcomingSection = document.createElement('div');
      upcomingSection.innerHTML = `
        <h3 class="h5 mb-3">
          <i class="bi bi-calendar-check text-primary me-2"></i>
          Réunions à venir
        </h3>
        <div class="mb-4" id="upcoming-meetings"></div>
      `;
      meetingsList.appendChild(upcomingSection);
      
      const upcomingContainer = upcomingSection.querySelector('#upcoming-meetings');
      meetings.upcoming.forEach(meeting => {
        upcomingContainer.appendChild(this.createMeetingCard(meeting, true));
      });
    }
    
    // Afficher les réunions passées
    if (meetings.past && meetings.past.length > 0) {
      const pastSection = document.createElement('div');
      pastSection.innerHTML = `
        <h3 class="h5 mb-3">
          <i class="bi bi-calendar-x text-secondary me-2"></i>
          Réunions passées
        </h3>
        <div class="mb-4" id="past-meetings"></div>
      `;
      meetingsList.appendChild(pastSection);
      
      const pastContainer = pastSection.querySelector('#past-meetings');
      meetings.past.forEach(meeting => {
        pastContainer.appendChild(this.createMeetingCard(meeting, false));
      });
    }
    
    // Afficher les réunions annulées
    if (meetings.cancelled && meetings.cancelled.length > 0) {
      const cancelledSection = document.createElement('div');
      cancelledSection.innerHTML = `
        <h3 class="h5 mb-3">
          <i class="bi bi-calendar-minus text-danger me-2"></i>
          Réunions annulées
        </h3>
        <div class="mb-4" id="cancelled-meetings"></div>
      `;
      meetingsList.appendChild(cancelledSection);
      
      const cancelledContainer = cancelledSection.querySelector('#cancelled-meetings');
      meetings.cancelled.forEach(meeting => {
        cancelledContainer.appendChild(this.createMeetingCard(meeting, false, true));
      });
    }
  }
  
  createMeetingCard(meeting, isUpcoming, isCancelled = false) {
    const meetingCard = document.createElement('div');
    const borderClass = isCancelled ? 'border-danger' : (isUpcoming ? 'border-primary' : 'border-secondary');
    meetingCard.className = `card mb-3 border-start border-4 ${borderClass}`;
    
    // Parser la date et l'heure
    const meetingDate = new Date(meeting.meeting_date || meeting.date_time);
    const formattedDate = meetingDate.toLocaleDateString('fr-FR', { 
      weekday: 'long', 
      day: 'numeric', 
      month: 'long',
      year: 'numeric'
    });
    
    const formattedTime = meeting.meeting_time || meetingDate.toLocaleTimeString('fr-FR', {
      hour: '2-digit',
      minute: '2-digit'
    });
    
    meetingCard.innerHTML = `
      <div class="card-body">
        <div class="row">
          <div class="col-md-8">
            <h5 class="card-title">${meeting.title || 'Réunion avec tuteur'}</h5>
            <p class="card-text mb-2">
              <i class="bi bi-calendar-event text-muted me-2"></i>
              <span>${formattedDate} à ${formattedTime}</span>
            </p>
            ${meeting.tutor_name ? `
            <p class="card-text mb-2">
              <i class="bi bi-person-badge text-muted me-2"></i>
              <span>${meeting.tutor_name}</span>
            </p>
            ` : ''}
            ${meeting.location ? `
            <p class="card-text mb-2">
              <i class="bi bi-geo-alt text-muted me-2"></i>
              <span>${meeting.location}</span>
            </p>
            ` : ''}
            ${meeting.meeting_link ? `
            <p class="card-text mb-2">
              <i class="bi bi-camera-video text-muted me-2"></i>
              <a href="${meeting.meeting_link}" class="text-decoration-none" target="_blank">
                Rejoindre la réunion en ligne
              </a>
            </p>
            ` : ''}
          </div>
          
          ${isUpcoming && !isCancelled ? `
          <div class="col-md-4 text-end">
            <button type="button" 
                    class="btn btn-sm btn-outline-danger"
                    data-action="student-meetings#showCancelConfirmation"
                    data-meeting-id="${meeting.id}">
              <i class="bi bi-x-circle me-1"></i>
              Annuler
            </button>
          </div>
          ` : ''}
        </div>
        
        ${meeting.notes ? `
        <div class="mt-3 pt-3 border-top">
          <h6 class="text-muted">Notes:</h6>
          <p class="card-text small">${meeting.notes}</p>
        </div>
        ` : ''}
        
        ${isCancelled ? `
        <div class="mt-2">
          <span class="badge bg-danger">Annulée</span>
        </div>
        ` : ''}
      </div>
    `;
    
    return meetingCard;
  }
  
  showNewMeetingForm() {
    if (this.hasModalTarget && this.hasModalTitleTarget && this.hasModalContentTarget) {
      // Définir le titre du modal
      this.modalTitleTarget.textContent = "Planifier une nouvelle réunion";
      
      // Générer le contenu du formulaire
      this.modalContentTarget.innerHTML = this.generateMeetingForm();
      
      // Afficher le modal avec Bootstrap
      const modalElement = this.modalTarget;
      const bootstrapModal = new bootstrap.Modal(modalElement);
      bootstrapModal.show();
      
      // Stocker l'instance du modal
      this.bootstrapModal = bootstrapModal;
    }
  }
  
  generateMeetingForm() {
    // Obtenir la date et l'heure actuelles pour les valeurs par défaut
    const now = new Date();
    const tomorrow = new Date(now);
    tomorrow.setDate(tomorrow.getDate() + 1);
    
    const defaultDate = tomorrow.toISOString().split('T')[0];
    const defaultTime = '10:00';
    
    return `
      <form data-action="submit->student-meetings#submitMeetingForm">
        <div class="mb-3">
          <label for="meeting-title" class="form-label">Titre de la réunion</label>
          <input type="text" 
                 class="form-control" 
                 id="meeting-title" 
                 name="title" 
                 placeholder="Ex: Discussion sur le rapport de stage"
                 required>
        </div>
        
        <div class="mb-3">
          <label for="meeting-date" class="form-label">Date et heure</label>
          <input type="datetime-local" 
                 class="form-control" 
                 id="meeting-date" 
                 name="meeting_date" 
                 value="${defaultDate}T${defaultTime}"
                 min="${now.toISOString().split('.')[0]}"
                 required>
        </div>
        
        <div class="mb-3">
          <label for="meeting-location" class="form-label">Lieu (optionnel)</label>
          <input type="text" 
                 class="form-control" 
                 id="meeting-location" 
                 name="location"
                 placeholder="Ex: Salle A123 ou Bureau du tuteur">
        </div>
        
        <div class="mb-3">
          <label for="meeting-link" class="form-label">Lien de réunion virtuelle (optionnel)</label>
          <input type="url" 
                 class="form-control" 
                 id="meeting-link" 
                 name="meeting_link"
                 placeholder="https://meet.google.com/xxx-xxx-xxx">
          <div class="form-text">Si la réunion se fait en ligne, ajoutez le lien ici</div>
        </div>
        
        <div class="mb-3">
          <label for="meeting-notes" class="form-label">Notes (optionnel)</label>
          <textarea class="form-control" 
                    id="meeting-notes" 
                    name="notes" 
                    rows="3"
                    placeholder="Ajoutez des détails ou points à discuter..."></textarea>
        </div>
        
        <div class="d-flex justify-content-end gap-2">
          <button type="button" 
                  class="btn btn-secondary" 
                  data-action="student-meetings#closeModal">
            Annuler
          </button>
          <button type="submit" 
                  class="btn btn-primary">
            <i class="bi bi-calendar-plus me-2"></i>
            Planifier la réunion
          </button>
        </div>
      </form>
    `;
  }
  
  async submitMeetingForm(event) {
    event.preventDefault();
    
    const form = event.currentTarget;
    const formData = new FormData(form);
    const meetingData = {};
    
    // Convertir FormData en objet
    for (const [key, value] of formData.entries()) {
      meetingData[key] = value;
    }
    
    // Ajouter l'ID d'affectation si disponible
    if (this.hasAssignmentIdValue && this.assignmentIdValue) {
      meetingData.assignment_id = this.assignmentIdValue;
    }
    
    try {
      // Afficher l'état de chargement
      const submitButton = form.querySelector('button[type="submit"]');
      const originalText = submitButton.innerHTML;
      submitButton.disabled = true;
      submitButton.innerHTML = `
        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
        Création en cours...
      `;
      
      // Créer la réunion
      const response = await this.api.meetings.create(meetingData);
      
      // Fermer le modal
      this.closeModal();
      
      // Recharger les réunions
      this.loadMeetings();
      
      // Afficher le message de succès
      this.showNotification("Réunion planifiée avec succès", "success");
      
    } catch (error) {
      console.error("Erreur lors de la création de la réunion:", error);
      
      // Afficher le message d'erreur
      const alertDiv = document.createElement('div');
      alertDiv.className = "alert alert-danger alert-dismissible fade show mt-3";
      alertDiv.innerHTML = `
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        ${error.message || "Erreur lors de la création de la réunion. Veuillez réessayer."}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      `;
      
      form.appendChild(alertDiv);
      
      // Réinitialiser le bouton
      const submitButton = form.querySelector('button[type="submit"]');
      submitButton.disabled = false;
      submitButton.innerHTML = originalText;
    }
  }
  
  showCancelConfirmation(event) {
    const meetingId = event.currentTarget.dataset.meetingId;
    
    if (this.hasConfirmCancelDialogTarget && this.hasMeetingIdFieldTarget) {
      // Définir l'ID de la réunion
      this.meetingIdFieldTarget.value = meetingId;
      
      // Afficher le dialogue de confirmation
      const dialogElement = this.confirmCancelDialogTarget;
      const bootstrapDialog = new bootstrap.Modal(dialogElement);
      bootstrapDialog.show();
      
      // Stocker l'instance du dialogue
      this.bootstrapDialog = bootstrapDialog;
    }
  }
  
  async cancelMeeting() {
    if (!this.hasMeetingIdFieldTarget) return;
    
    const meetingId = this.meetingIdFieldTarget.value;
    if (!meetingId) return;
    
    try {
      // Afficher l'état de chargement
      const confirmButton = this.confirmCancelDialogTarget.querySelector('.btn-danger');
      const originalText = confirmButton.innerHTML;
      confirmButton.disabled = true;
      confirmButton.innerHTML = `
        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
        Annulation en cours...
      `;
      
      // Annuler la réunion
      await this.api.meetings.cancel(meetingId);
      
      // Fermer le dialogue
      this.closeConfirmCancelDialog();
      
      // Recharger les réunions
      this.loadMeetings();
      
      // Afficher le message de succès
      this.showNotification("Réunion annulée avec succès", "success");
      
    } catch (error) {
      console.error("Erreur lors de l'annulation:", error);
      
      // Afficher le message d'erreur
      this.showNotification(error.message || "Erreur lors de l'annulation de la réunion", "error");
      
      // Réinitialiser le bouton
      const confirmButton = this.confirmCancelDialogTarget.querySelector('.btn-danger');
      confirmButton.disabled = false;
      confirmButton.innerHTML = originalText;
    }
  }
  
  closeModal() {
    if (this.hasModalTarget && this.bootstrapModal) {
      this.bootstrapModal.hide();
    }
  }
  
  closeConfirmCancelDialog() {
    if (this.hasConfirmCancelDialogTarget && this.bootstrapDialog) {
      this.bootstrapDialog.hide();
    }
  }
  
  showNotification(message, type = "info") {
    // Créer l'élément de notification
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show position-fixed bottom-0 end-0 m-3`;
    notification.style.zIndex = '9999';
    notification.innerHTML = `
      <div class="d-flex align-items-center">
        ${type === 'success' ? '<i class="bi bi-check-circle-fill me-2"></i>' : 
          type === 'error' ? '<i class="bi bi-exclamation-triangle-fill me-2"></i>' : 
          '<i class="bi bi-info-circle-fill me-2"></i>'}
        <span>${message}</span>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Ajouter au document
    document.body.appendChild(notification);
    
    // Supprimer après 5 secondes
    setTimeout(() => {
      const bsAlert = bootstrap.Alert.getInstance(notification);
      if (bsAlert) {
        bsAlert.close();
      } else {
        notification.remove();
      }
    }, 5000);
  }
}