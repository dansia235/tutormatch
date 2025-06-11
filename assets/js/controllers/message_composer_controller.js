/**
 * Message Composer Controller
 * Handles message composition, sending, and attachments
 */
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
  static targets = [
    "textarea", "submitButton", "error", "success", 
    "attachments", "fileInput", "filePreview", "spinner"
  ];
  
  static values = {
    apiEndpoint: { type: String, default: "/tutoring/api/messages/send.php" },
    conversationId: String,
    maxFileSize: { type: Number, default: 10485760 }, // 10MB
    maxFiles: { type: Number, default: 5 }
  };
  
  connect() {
    // Initialize the component
    this.files = []; // Array to store selected files
    this.adjustTextareaHeight();
    
    // Get recipient info from hidden fields or data attributes
    this.recipientId = this.element.querySelector('input[name="receiver_id"]')?.value;
    this.recipientType = this.element.querySelector('input[name="recipient_type"]')?.value || 
                        this.element.querySelector('input[name="receiver_type"]')?.value;
  }
  
  adjustTextareaHeight() {
    const textarea = this.textareaTarget;
    textarea.style.height = "auto";
    textarea.style.height = `${textarea.scrollHeight}px`;
    
    // Limit the height to prevent overly large textareas
    const maxHeight = 200;
    if (textarea.scrollHeight > maxHeight) {
      textarea.style.height = `${maxHeight}px`;
      textarea.style.overflowY = "auto";
    } else {
      textarea.style.overflowY = "hidden";
    }
  }
  
  handleKeydown(event) {
    // Send message on Ctrl+Enter or Cmd+Enter
    if ((event.ctrlKey || event.metaKey) && event.key === "Enter") {
      event.preventDefault();
      this.sendMessage(event);
    }
    
    // Adjust textarea height
    this.adjustTextareaHeight();
  }
  
  toggleAttachments() {
    if (this.hasAttachmentsTarget) {
      this.attachmentsTarget.classList.toggle("hidden");
    }
  }
  
  toggleEmojis() {
    // This would be implemented if emoji picker functionality is added
    console.log("Emoji picker not implemented yet");
  }
  
  handleFileSelect() {
    const fileInput = this.fileInputTarget;
    const filePreview = this.filePreviewTarget;
    
    // Clear any existing error messages
    this.clearError();
    
    // Check if files were selected
    if (fileInput.files.length === 0) {
      return;
    }
    
    // Check number of files
    if (this.files.length + fileInput.files.length > this.maxFilesValue) {
      this.showError(`Vous ne pouvez pas ajouter plus de ${this.maxFilesValue} fichiers.`);
      return;
    }
    
    // Process each file
    Array.from(fileInput.files).forEach(file => {
      // Check file size
      if (file.size > this.maxFileSizeValue) {
        this.showError(`Le fichier "${file.name}" dépasse la taille maximale autorisée (10 MB).`);
        return;
      }
      
      // Store the file
      this.files.push(file);
      
      // Create preview element
      const previewElement = this.createFilePreviewElement(file);
      filePreview.appendChild(previewElement);
    });
    
    // Reset file input
    fileInput.value = "";
  }
  
  createFilePreviewElement(file) {
    const div = document.createElement("div");
    div.className = "relative bg-gray-100 rounded-md p-2 flex items-center";
    div.dataset.fileName = file.name;
    
    // File icon or thumbnail
    let fileIcon = "";
    if (file.type.startsWith("image/")) {
      const img = document.createElement("img");
      img.src = URL.createObjectURL(file);
      img.className = "h-10 w-10 object-cover rounded";
      div.appendChild(img);
    } else {
      fileIcon = `
        <svg class="h-10 w-10 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
        </svg>
      `;
      div.innerHTML = fileIcon;
    }
    
    // File details
    const fileDetails = document.createElement("div");
    fileDetails.className = "ml-3 flex-grow";
    fileDetails.innerHTML = `
      <p class="text-sm font-medium text-gray-900 truncate">${file.name}</p>
      <p class="text-xs text-gray-500">${this.formatFileSize(file.size)}</p>
    `;
    div.appendChild(fileDetails);
    
    // Remove button
    const removeButton = document.createElement("button");
    removeButton.type = "button";
    removeButton.className = "ml-2 text-gray-400 hover:text-red-500";
    removeButton.innerHTML = `
      <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
      </svg>
    `;
    removeButton.addEventListener("click", () => this.removeFile(file.name, div));
    div.appendChild(removeButton);
    
    return div;
  }
  
  removeFile(fileName, element) {
    // Remove file from array
    this.files = this.files.filter(file => file.name !== fileName);
    
    // Remove preview element
    element.remove();
  }
  
  formatFileSize(bytes) {
    if (bytes === 0) return "0 Bytes";
    const k = 1024;
    const sizes = ["Bytes", "KB", "MB", "GB"];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + " " + sizes[i];
  }
  
  showError(message) {
    if (this.hasErrorTarget) {
      const errorTarget = this.errorTarget;
      errorTarget.textContent = message;
      errorTarget.classList.remove("hidden");
      
      // Hide after 5 seconds
      setTimeout(() => {
        this.clearError();
      }, 5000);
    }
  }
  
  showSuccess(message) {
    if (this.hasSuccessTarget) {
      const successTarget = this.successTarget;
      successTarget.textContent = message;
      successTarget.classList.remove("hidden");
      
      // Hide after 5 seconds
      setTimeout(() => {
        this.clearSuccess();
      }, 5000);
    }
  }
  
  clearError() {
    if (this.hasErrorTarget) {
      this.errorTarget.textContent = "";
      this.errorTarget.classList.add("hidden");
    }
  }
  
  clearSuccess() {
    if (this.hasSuccessTarget) {
      this.successTarget.textContent = "";
      this.successTarget.classList.add("hidden");
    }
  }
  
  sendMessage(event) {
    event.preventDefault();
    
    // Get message content
    const content = this.textareaTarget.value.trim();
    
    // Validate content
    if (!content) {
      this.showError("Veuillez saisir un message.");
      return;
    }
    
    // Get recipient ID from form or use the one from connect()
    const form = event.target.closest('form');
    const recipientId = form.querySelector('input[name="receiver_id"]')?.value || 
                       form.querySelector('input[name="recipient_id"]')?.value || 
                       this.recipientId;
    
    const recipientType = form.querySelector('input[name="receiver_type"]')?.value || 
                         form.querySelector('input[name="recipient_type"]')?.value || 
                         this.recipientType || 'user';
    
    if (!recipientId) {
      this.showError("Destinataire non spécifié.");
      return;
    }
    
    // Disable submit button and show spinner
    this.submitButtonTarget.disabled = true;
    if (this.hasSpinnerTarget) {
      this.spinnerTarget.classList.remove("hidden");
    }
    
    // Prepare data based on whether we have files or not
    let fetchOptions;
    
    if (this.files.length > 0) {
      // Use FormData for file uploads
      const formData = new FormData();
      formData.append("content", content);
      formData.append("recipient_id", recipientId);
      formData.append("recipient_type", recipientType);
      
      if (this.conversationIdValue) {
        formData.append("conversation_id", this.conversationIdValue);
      }
      
      // Add files
      this.files.forEach(file => {
        formData.append("attachments[]", file);
      });
      
      fetchOptions = {
        method: "POST",
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
      };
    } else {
      // Use JSON for simple messages
      const messageData = {
        content: content,
        recipient_id: recipientId,
        recipient_type: recipientType
      };
      
      if (this.conversationIdValue) {
        messageData.conversation_id = this.conversationIdValue;
      }
      
      fetchOptions = {
        method: "POST",
        body: JSON.stringify(messageData),
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
      };
    }
    
    // Send the message
    fetch(this.apiEndpointValue, fetchOptions)
    .then(response => {
      if (!response.ok) {
        throw new Error(`HTTP error! Status: ${response.status}`);
      }
      return response.json();
    })
    .then(data => {
      if (data.success) {
        // Clear the form
        this.textareaTarget.value = "";
        this.adjustTextareaHeight();
        
        // Clear attachments
        this.files = [];
        if (this.hasFilePreviewTarget) {
          this.filePreviewTarget.innerHTML = "";
        }
        
        // Hide attachments section
        if (this.hasAttachmentsTarget) {
          this.attachmentsTarget.classList.add("hidden");
        }
        
        // Show success message
        this.showSuccess(data.message || "Message envoyé avec succès.");
        
        // Dispatch a custom event for the message being sent
        const event = new CustomEvent("message:sent", {
          detail: { 
            message: data.data,
            recipientId: recipientId,
            recipientType: recipientType
          },
          bubbles: true
        });
        this.element.dispatchEvent(event);
        
        // If in a modal, close it
        const modal = this.element.closest('.modal');
        if (modal) {
          const bootstrapModal = bootstrap.Modal.getInstance(modal);
          if (bootstrapModal) {
            bootstrapModal.hide();
          }
        }
      } else {
        this.showError(data.message || "Erreur lors de l'envoi du message.");
      }
    })
    .catch(error => {
      console.error("Error sending message:", error);
      this.showError("Erreur lors de l'envoi du message. Veuillez réessayer.");
    })
    .finally(() => {
      // Re-enable submit button and hide spinner
      this.submitButtonTarget.disabled = false;
      if (this.hasSpinnerTarget) {
        this.spinnerTarget.classList.add("hidden");
      }
    });
  }
}