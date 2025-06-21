/**
 * Student Evaluations Controller
 * Manages student evaluations interface, viewing and submitting self-evaluations
 */
import { Controller } from "@hotwired/stimulus";
import ApiClient from '../api-client';

export default class extends Controller {
  static targets = [
    "evaluationsList", "emptyState", "loadingIndicator", 
    "modal", "modalContent", "modalTitle", "modalFooter",
    "evaluationForm", "evaluationDetail", "submitButton"
  ];
  
  static values = {
    internshipId: Number,
    apiUrl: { type: String, default: "/tutoring/api/evaluations" },
    useDocuments: { type: Boolean, default: false }
  };
  
  connect() {
    // Initialize the API client
    this.api = new ApiClient();
    
    // Load the evaluations
    this.loadEvaluations();
  }
  
  async loadEvaluations() {
    if (this.hasLoadingIndicatorTarget) {
      this.loadingIndicatorTarget.classList.remove("hidden");
    }
    
    try {
      // Fetch evaluations for the student
      console.log("Chargement des évaluations avec useDocuments:", this.useDocumentsValue);
      
      const endpoint = 'evaluations/student-evaluations.php';
      console.log("Endpoint utilisé:", endpoint);
      
      const response = await this.api.get(endpoint);
      console.log("Réponse de l'API:", response);
      
      // Check if we have evaluations in the response
      if (response && response.evaluations) {
        console.log("Nombre d'évaluations reçues:", response.evaluations.length);
        this.displayEvaluations(response.evaluations);
      } else if (response && response.data) {
        console.log("Format de réponse alternatif, utilisation de data:", response.data);
        this.displayEvaluations(response.data);
      } else {
        console.warn("Format de réponse inattendu:", response);
        this.displayEvaluations([]);
      }
      
    } catch (error) {
      console.error("Error loading evaluations:", error);
      if (this.hasEvaluationsListTarget) {
        this.evaluationsListTarget.innerHTML = `
          <div class="alert alert-danger">
            Erreur lors du chargement des évaluations: ${error.message || 'Erreur inconnue'}
            <br><small>Veuillez rafraîchir la page ou contacter l'administrateur.</small>
          </div>
        `;
      }
    } finally {
      if (this.hasLoadingIndicatorTarget) {
        this.loadingIndicatorTarget.classList.add("hidden");
      }
    }
  }
  
  displayEvaluations(evaluations) {
    if (!this.hasEvaluationsListTarget) return;
    
    const evaluationsList = this.evaluationsListTarget;
    
    // Show empty state if no evaluations
    if (evaluations.length === 0) {
      evaluationsList.innerHTML = '';
      if (this.hasEmptyStateTarget) {
        this.emptyStateTarget.classList.remove("hidden");
      }
      return;
    }
    
    // Hide empty state if there are evaluations
    if (this.hasEmptyStateTarget) {
      this.emptyStateTarget.classList.add("hidden");
    }
    
    // Sort evaluations by due date (upcoming first)
    evaluations.sort((a, b) => {
      // Self evaluations first
      if (a.type === 'self' && b.type !== 'self') return -1;
      if (a.type !== 'self' && b.type === 'self') return 1;
      
      // Then sort by due date
      const dateA = a.due_date ? new Date(a.due_date) : new Date(0);
      const dateB = b.due_date ? new Date(b.due_date) : new Date(0);
      return dateA - dateB;
    });
    
    // Clear evaluations list
    evaluationsList.innerHTML = '';
    
    // Render all evaluations
    evaluations.forEach(evaluation => {
      evaluationsList.appendChild(this.createEvaluationCard(evaluation));
    });
  }
  
  createEvaluationCard(evaluation) {
    const evaluationCard = document.createElement('div');
    evaluationCard.className = "bg-white rounded-lg shadow p-4 border-l-4 mb-4";
    
    // Set border color based on status
    if (evaluation.type === 'self' && !evaluation.completed) {
      evaluationCard.classList.add("border-yellow-500"); // Pending self-evaluation
    } else if (evaluation.type === 'self' && evaluation.completed) {
      evaluationCard.classList.add("border-green-500"); // Completed self-evaluation
    } else if (evaluation.type === 'teacher') {
      evaluationCard.classList.add("border-blue-500"); // Teacher evaluation
    } else {
      evaluationCard.classList.add("border-gray-300"); // Other evaluations
    }
    
    // Format date
    let dueDateText = '';
    if (evaluation.due_date) {
      const dueDate = new Date(evaluation.due_date);
      dueDateText = dueDate.toLocaleDateString('fr-FR', { 
        day: 'numeric', 
        month: 'long',
        year: 'numeric'
      });
    }
    
    // Determine evaluation status and actions
    let statusText = '';
    let actionButton = '';
    
    if (evaluation.type === 'self') {
      if (evaluation.completed) {
        statusText = `<span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">Complétée</span>`;
        actionButton = `
          <button type="button" 
                  class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-xs font-medium rounded shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                  data-action="student-evaluations#viewEvaluation"
                  data-evaluation-id="${evaluation.id}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
            Voir
          </button>
        `;
      } else {
        statusText = `<span class="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">En attente</span>`;
        actionButton = `
          <button type="button" 
                  class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                  data-action="student-evaluations#showEvaluationForm"
                  data-evaluation-id="${evaluation.id}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            Compléter
          </button>
        `;
      }
    } else if (evaluation.type === 'teacher') {
      if (evaluation.completed) {
        statusText = `<span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">Évaluation reçue</span>`;
        actionButton = `
          <button type="button" 
                  class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-xs font-medium rounded shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                  data-action="student-evaluations#viewEvaluation"
                  data-evaluation-id="${evaluation.id}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
            Voir
          </button>
        `;
      } else {
        statusText = `<span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800">En attente du tuteur</span>`;
        actionButton = '';
      }
    }
    
    evaluationCard.innerHTML = `
      <div class="flex flex-col sm:flex-row justify-between">
        <div>
          <div class="flex items-center mb-2">
            <h4 class="text-base font-medium text-gray-900">${evaluation.title || (evaluation.type === 'self' ? 'Auto-évaluation' : 'Évaluation du tuteur')}</h4>
            <div class="ml-3">${statusText}</div>
          </div>
          
          <p class="text-sm text-gray-600 mt-1">
            <span class="inline-flex items-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              ${evaluation.type === 'self' ? 'Auto-évaluation' : 'Évaluation par le tuteur'}
            </span>
          </p>
          
          ${evaluation.due_date ? `
          <p class="text-sm text-gray-600 mt-1">
            <span class="inline-flex items-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              Date limite: ${dueDateText}
            </span>
          </p>
          ` : ''}
          
          ${evaluation.evaluator ? `
          <p class="text-sm text-gray-600 mt-1">
            <span class="inline-flex items-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
              ${evaluation.evaluator.name || 'Non assigné'}
            </span>
          </p>
          ` : ''}
        </div>
        
        <div class="mt-3 sm:mt-0 sm:ml-4">
          ${actionButton}
        </div>
      </div>
    `;
    
    return evaluationCard;
  }
  
  async viewEvaluation(event) {
    const evaluationId = event.currentTarget.dataset.evaluationId;
    
    if (this.hasModalTarget && this.hasModalTitleTarget && this.hasModalContentTarget) {
      // Show loading state
      this.modalTitleTarget.textContent = "Chargement de l'évaluation...";
      this.modalContentTarget.innerHTML = `
        <div class="flex justify-center items-center py-10">
          <svg class="animate-spin h-8 w-8 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
        </div>
      `;
      
      // Show the modal
      this.modalTarget.classList.remove("hidden");
      
      try {
        // Fetch evaluation details
        const response = await this.api.get(`evaluations/show.php?id=${evaluationId}`);
        const evaluation = response.data;
        
        // Update modal title
        this.modalTitleTarget.textContent = evaluation.title || (evaluation.type === 'self' ? 'Auto-évaluation' : 'Évaluation du tuteur');
        
        // Format the evaluation details
        this.modalContentTarget.innerHTML = this.formatEvaluationDetails(evaluation);
        
      } catch (error) {
        console.error("Error loading evaluation details:", error);
        this.modalContentTarget.innerHTML = `
          <div class="text-red-600 py-4">
            Erreur lors du chargement des détails de l'évaluation. Veuillez réessayer.
          </div>
        `;
      }
    }
  }
  
  formatEvaluationDetails(evaluation) {
    // Format date
    const completedDate = evaluation.completed_at 
      ? new Date(evaluation.completed_at).toLocaleDateString('fr-FR', { 
          day: 'numeric', 
          month: 'long',
          year: 'numeric'
        })
      : 'Non complétée';
    
    // Start HTML content
    let html = `
      <div class="space-y-4 py-2">
        <div class="flex justify-between items-start">
          <div>
            <p class="text-sm text-gray-500">Type: ${evaluation.type === 'self' ? 'Auto-évaluation' : 'Évaluation par le tuteur'}</p>
            <p class="text-sm text-gray-500">Date de complétion: ${completedDate}</p>
          </div>
          ${evaluation.score ? `
          <div class="text-lg font-bold ${
            evaluation.score >= 4 ? 'text-green-600' : 
            evaluation.score >= 3 ? 'text-blue-600' : 
            evaluation.score >= 2 ? 'text-yellow-600' : 
            'text-red-600'
          }">
            Score: ${evaluation.score}/5
          </div>
          ` : ''}
        </div>
    `;
    
    // Add criteria sections if they exist
    if (evaluation.criteria && evaluation.criteria.length > 0) {
      html += `<div class="mt-6 border-t border-gray-200 pt-4">`;
      
      evaluation.criteria.forEach(criterion => {
        const score = criterion.score || 0;
        
        html += `
          <div class="mb-6">
            <h4 class="text-base font-medium text-gray-900 mb-2">${criterion.name}</h4>
            ${criterion.description ? `<p class="text-sm text-gray-600 mb-3">${criterion.description}</p>` : ''}
            
            <div class="flex items-center mb-2">
              <div class="w-full bg-gray-200 rounded-full h-2.5">
                <div class="h-2.5 rounded-full ${
                  score >= 4 ? 'bg-green-600' : 
                  score >= 3 ? 'bg-blue-600' : 
                  score >= 2 ? 'bg-yellow-600' : 
                  'bg-red-600'
                }" style="width: ${(score / 5) * 100}%"></div>
              </div>
              <span class="ml-3 text-sm font-medium text-gray-700">${score}/5</span>
            </div>
            
            ${criterion.comments ? `
            <div class="mt-2 text-sm text-gray-700">
              <p class="font-medium">Commentaires:</p>
              <p>${criterion.comments}</p>
            </div>
            ` : ''}
          </div>
        `;
      });
      
      html += `</div>`;
    }
    
    // Add general comments if they exist
    if (evaluation.comments) {
      html += `
        <div class="mt-6 border-t border-gray-200 pt-4">
          <h4 class="text-base font-medium text-gray-900 mb-2">Commentaires généraux</h4>
          <p class="text-sm text-gray-700">${evaluation.comments}</p>
        </div>
      `;
    }
    
    // Add action buttons
    html += `
      <div class="mt-6 pt-4 border-t border-gray-200 flex justify-end">
        <button type="button" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" data-action="student-evaluations#closeModal">
          Fermer
        </button>
      </div>
    `;
    
    // Close main container
    html += `</div>`;
    
    return html;
  }
  
  showEvaluationForm(event) {
    const evaluationId = event.currentTarget.dataset.evaluationId;
    
    if (this.hasModalTarget && this.hasModalTitleTarget && this.hasModalContentTarget) {
      // Set modal title
      this.modalTitleTarget.textContent = "Compléter l'auto-évaluation";
      
      // Show loading state
      this.modalContentTarget.innerHTML = `
        <div class="flex justify-center items-center py-10">
          <svg class="animate-spin h-8 w-8 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
        </div>
      `;
      
      // Show the modal
      this.modalTarget.classList.remove("hidden");
      
      // Fetch evaluation criteria and generate form
      this.loadEvaluationForm(evaluationId);
    }
  }
  
  async loadEvaluationForm(evaluationId) {
    try {
      // Fetch evaluation details to get criteria
      const response = await this.api.get(`evaluations/show.php?id=${evaluationId}`);
      const evaluation = response.data;
      
      // Generate form HTML
      const formHtml = this.generateEvaluationForm(evaluation);
      
      // Update modal content
      this.modalContentTarget.innerHTML = formHtml;
      
      // Initialize form event listeners
      const form = this.modalContentTarget.querySelector('form');
      form.addEventListener('submit', (e) => this.submitEvaluation(e, evaluationId));
      
    } catch (error) {
      console.error("Error loading evaluation form:", error);
      this.modalContentTarget.innerHTML = `
        <div class="text-red-600 py-4">
          Erreur lors du chargement du formulaire d'évaluation. Veuillez réessayer.
        </div>
        <div class="mt-6 flex justify-end">
          <button type="button" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" data-action="student-evaluations#closeModal">
            Fermer
          </button>
        </div>
      `;
    }
  }
  
  generateEvaluationForm(evaluation) {
    let html = `
      <form data-student-evaluations-target="evaluationForm" class="space-y-4">
        <input type="hidden" name="evaluation_id" value="${evaluation.id}">
    `;
    
    // Add criteria sections
    if (evaluation.criteria && evaluation.criteria.length > 0) {
      evaluation.criteria.forEach((criterion, index) => {
        html += `
          <div class="border-t border-gray-200 pt-4 ${index === 0 ? 'border-t-0 pt-2' : ''}">
            <h4 class="text-base font-medium text-gray-900 mb-2">${criterion.name}</h4>
            ${criterion.description ? `<p class="text-sm text-gray-600 mb-3">${criterion.description}</p>` : ''}
            
            <input type="hidden" name="criteria[${index}][id]" value="${criterion.id}">
            <input type="hidden" name="criteria[${index}][name]" value="${criterion.name}">
            
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">Évaluation</label>
              <div class="flex items-center space-x-2">
                ${[1, 2, 3, 4, 5].map(score => `
                  <label class="rating-label">
                    <input type="radio" name="criteria[${index}][score]" value="${score}" class="sr-only peer" required>
                    <div class="flex items-center justify-center w-10 h-10 rounded-full border-2 border-gray-300 cursor-pointer peer-checked:border-indigo-500 peer-checked:bg-indigo-50 hover:bg-gray-50">
                      <span class="text-gray-700 peer-checked:text-indigo-600 font-medium">${score}</span>
                    </div>
                  </label>
                `).join('')}
              </div>
              <div class="flex justify-between text-xs text-gray-500 mt-1">
                <span>Insuffisant</span>
                <span>Excellent</span>
              </div>
            </div>
            
            <div class="mb-2">
              <label for="criteria-${index}-comments" class="block text-sm font-medium text-gray-700 mb-1">Commentaires (optionnel)</label>
              <textarea id="criteria-${index}-comments" name="criteria[${index}][comments]" rows="2" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"></textarea>
            </div>
          </div>
        `;
      });
    }
    
    // Add general comments
    html += `
      <div class="border-t border-gray-200 pt-4">
        <label for="general-comments" class="block text-sm font-medium text-gray-700 mb-1">Commentaires généraux</label>
        <textarea id="general-comments" name="comments" rows="3" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"></textarea>
      </div>
      
      <div class="pt-5 border-t border-gray-200 flex justify-end">
        <div class="text-red-600 hidden" data-student-evaluations-target="submitButton">
          Veuillez remplir tous les champs obligatoires.
        </div>
        <div class="ml-3 inline-flex">
          <button type="button" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" data-action="student-evaluations#closeModal">
            Annuler
          </button>
          <button type="submit" class="ml-3 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            Soumettre l'évaluation
          </button>
        </div>
      </div>
    `;
    
    // Close form
    html += `</form>`;
    
    return html;
  }
  
  async submitEvaluation(event, evaluationId) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    // Prepare data for API
    const evaluationData = {
      evaluation_id: evaluationId,
      comments: formData.get('comments'),
      criteria: []
    };
    
    // Get criteria data
    let criteriaCount = 0;
    let criteriaIndex = 0;
    
    while (formData.has(`criteria[${criteriaIndex}][id]`)) {
      const criterionId = formData.get(`criteria[${criteriaIndex}][id]`);
      const score = formData.get(`criteria[${criteriaIndex}][score]`);
      const comments = formData.get(`criteria[${criteriaIndex}][comments]`);
      
      if (criterionId) {
        evaluationData.criteria.push({
          id: criterionId,
          score: score,
          comments: comments
        });
        criteriaCount++;
      }
      
      criteriaIndex++;
    }
    
    // Check if all criteria have scores
    if (evaluationData.criteria.length !== criteriaCount || 
        evaluationData.criteria.some(c => !c.score)) {
      if (this.hasSubmitButtonTarget) {
        this.submitButtonTarget.classList.remove('hidden');
      }
      return;
    }
    
    // Disable form and show loading state
    const submitButton = form.querySelector('button[type="submit"]');
    const originalText = submitButton.innerHTML;
    submitButton.disabled = true;
    submitButton.innerHTML = `
      <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
      Envoi...
    `;
    
    try {
      // Submit the evaluation
      await this.api.post('evaluations/submit-self-evaluation.php', evaluationData);
      
      // Close the modal
      this.closeModal();
      
      // Reload evaluations to show updated status
      this.loadEvaluations();
      
      // Show success message
      this.showNotification("Auto-évaluation soumise avec succès", "success");
      
    } catch (error) {
      console.error("Error submitting evaluation:", error);
      
      // Show error in form
      const errorElement = document.createElement('div');
      errorElement.className = "mt-3 text-sm text-red-600";
      errorElement.textContent = error.message || "Erreur lors de la soumission de l'évaluation. Veuillez réessayer.";
      
      // Append error to form
      form.appendChild(errorElement);
      
      // Reset submit button
      submitButton.disabled = false;
      submitButton.innerHTML = originalText;
    }
  }
  
  closeModal() {
    if (this.hasModalTarget) {
      this.modalTarget.classList.add("hidden");
    }
  }
  
  showNotification(message, type = "info") {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `fixed bottom-4 right-4 px-4 py-3 rounded-lg shadow-lg ${
      type === 'success' ? 'bg-green-500 text-white' : 
      type === 'error' ? 'bg-red-500 text-white' : 
      'bg-blue-500 text-white'
    }`;
    notification.innerHTML = `
      <div class="flex items-center">
        <div class="flex-shrink-0">
          ${type === 'success' ? `
            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
          ` : type === 'error' ? `
            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          ` : `
            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          `}
        </div>
        <div class="ml-3">
          <p class="text-sm font-medium">${message}</p>
        </div>
      </div>
    `;
    
    // Add to document
    document.body.appendChild(notification);
    
    // Remove after 5 seconds
    setTimeout(() => {
      notification.classList.add('opacity-0', 'transition-opacity', 'duration-500');
      setTimeout(() => {
        notification.remove();
      }, 500);
    }, 5000);
  }
}