/**
 * Meetings module
 * Handles meeting scheduling, calendar views, and attendance tracking
 */

document.addEventListener('DOMContentLoaded', () => {
  initializeCalendarView();
  initializeMeetingForms();
  initializeMeetingActions();
});

/**
 * Initialize calendar view for meetings
 */
function initializeCalendarView() {
  const calendarEl = document.getElementById('meetings-calendar');
  if (!calendarEl) return;
  
  // Check if we have meeting data as JSON
  if (!calendarEl.dataset.meetings) return;
  
  try {
    const meetingsData = JSON.parse(calendarEl.dataset.meetings);
    
    // Format meetings for calendar display
    const events = meetingsData.map(meeting => ({
      id: meeting.id,
      title: meeting.title,
      start: meeting.start_time,
      end: meeting.end_time,
      allDay: false,
      url: meeting.url || null,
      backgroundColor: getStatusColor(meeting.status),
      borderColor: getStatusColor(meeting.status),
      extendedProps: {
        description: meeting.description,
        location: meeting.location,
        status: meeting.status,
        participants: meeting.participants
      }
    }));
    
    // Initialize calendar
    const calendar = new FullCalendar.Calendar(calendarEl, {
      initialView: 'timeGridWeek',
      headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
      },
      locale: 'fr',
      events: events,
      eventTimeFormat: {
        hour: '2-digit',
        minute: '2-digit',
        hour12: false
      },
      eventClick: function(info) {
        if (info.event.url) {
          info.jsEvent.preventDefault();
          window.location.href = info.event.url;
        }
      },
      eventDidMount: function(info) {
        // Add tooltip with meeting details
        const tooltip = document.createElement('div');
        tooltip.className = 'meeting-tooltip bg-white p-3 rounded shadow-lg z-50 max-w-xs';
        tooltip.innerHTML = `
          <h4 class="font-semibold">${info.event.title}</h4>
          <p class="text-sm text-gray-600">${formatEventTime(info.event.start, info.event.end)}</p>
          ${info.event.extendedProps.location ? `<p class="text-sm"><span class="font-medium">Lieu:</span> ${info.event.extendedProps.location}</p>` : ''}
          ${info.event.extendedProps.description ? `<p class="text-sm mt-2">${info.event.extendedProps.description}</p>` : ''}
          <div class="mt-2">
            <span class="inline-block px-2 py-1 text-xs rounded-full ${getStatusClass(info.event.extendedProps.status)}">
              ${getStatusLabel(info.event.extendedProps.status)}
            </span>
          </div>
        `;
        
        // Show/hide tooltip on hover
        info.el.addEventListener('mouseenter', () => {
          document.body.appendChild(tooltip);
          const rect = info.el.getBoundingClientRect();
          tooltip.style.position = 'absolute';
          tooltip.style.top = `${rect.bottom + window.scrollY + 10}px`;
          tooltip.style.left = `${rect.left + window.scrollX}px`;
        });
        
        info.el.addEventListener('mouseleave', () => {
          if (tooltip.parentNode) {
            tooltip.parentNode.removeChild(tooltip);
          }
        });
      }
    });
    
    calendar.render();
    
  } catch (error) {
    console.error('Error initializing meetings calendar:', error);
    calendarEl.innerHTML = '<div class="alert alert-danger">Error loading calendar data</div>';
  }
}

/**
 * Initialize meeting form functionality
 */
function initializeMeetingForms() {
  const meetingForm = document.querySelector('form.meeting-form');
  if (!meetingForm) return;
  
  // Initialize date/time pickers
  const dateTimeInputs = meetingForm.querySelectorAll('.datetime-picker');
  dateTimeInputs.forEach(input => {
    flatpickr(input, {
      enableTime: true,
      dateFormat: "Y-m-d H:i",
      time_24hr: true,
      minDate: "today",
      minuteIncrement: 15
    });
  });
  
  // Handle participant selection
  const participantSelect = meetingForm.querySelector('.participant-select');
  if (participantSelect) {
    // If we have select2 available, enhance the select
    if (typeof jQuery !== 'undefined' && typeof jQuery.fn.select2 !== 'undefined') {
      jQuery(participantSelect).select2({
        placeholder: 'Sélectionner des participants',
        allowClear: true,
        width: '100%'
      });
    }
  }
  
  // Form validation
  meetingForm.addEventListener('submit', (e) => {
    if (!validateMeetingForm(meetingForm)) {
      e.preventDefault();
    }
  });
}

/**
 * Initialize meeting action buttons (accept, reject, reschedule)
 */
function initializeMeetingActions() {
  // Accept meeting buttons
  document.querySelectorAll('.accept-meeting').forEach(button => {
    button.addEventListener('click', (e) => {
      e.preventDefault();
      const meetingId = button.dataset.meetingId;
      updateMeetingStatus(meetingId, 'accepted', button);
    });
  });
  
  // Reject meeting buttons
  document.querySelectorAll('.reject-meeting').forEach(button => {
    button.addEventListener('click', (e) => {
      e.preventDefault();
      const meetingId = button.dataset.meetingId;
      updateMeetingStatus(meetingId, 'rejected', button);
    });
  });
  
  // Reschedule meeting buttons
  document.querySelectorAll('.reschedule-meeting').forEach(button => {
    button.addEventListener('click', (e) => {
      e.preventDefault();
      const meetingId = button.dataset.meetingId;
      showRescheduleForm(meetingId);
    });
  });
}

/**
 * Validate a meeting form
 * @param {HTMLFormElement} form - The form to validate
 * @returns {boolean} True if form is valid, false otherwise
 */
function validateMeetingForm(form) {
  let isValid = true;
  
  // Required fields
  const requiredFields = form.querySelectorAll('[required]');
  requiredFields.forEach(field => {
    if (!field.value.trim()) {
      markFieldAsInvalid(field, 'Ce champ est obligatoire');
      isValid = false;
    } else {
      markFieldAsValid(field);
    }
  });
  
  // Date validation (start time before end time)
  const startTime = form.querySelector('[name="start_time"]');
  const endTime = form.querySelector('[name="end_time"]');
  
  if (startTime && endTime && startTime.value && endTime.value) {
    const startDate = new Date(startTime.value);
    const endDate = new Date(endTime.value);
    
    if (startDate >= endDate) {
      markFieldAsInvalid(endTime, 'L\'heure de fin doit être postérieure à l\'heure de début');
      isValid = false;
    }
  }
  
  return isValid;
}

/**
 * Mark a form field as invalid with an error message
 * @param {HTMLElement} field - The field to mark as invalid
 * @param {string} message - The error message to display
 */
function markFieldAsInvalid(field, message) {
  field.classList.add('border-red-500');
  
  // Create or update error message
  let errorElement = field.parentElement.querySelector('.error-message');
  if (!errorElement) {
    errorElement = document.createElement('div');
    errorElement.className = 'error-message text-red-500 text-sm mt-1';
    field.parentElement.appendChild(errorElement);
  }
  
  errorElement.textContent = message;
}

/**
 * Mark a form field as valid
 * @param {HTMLElement} field - The field to mark as valid
 */
function markFieldAsValid(field) {
  field.classList.remove('border-red-500');
  
  // Remove error message if it exists
  const errorElement = field.parentElement.querySelector('.error-message');
  if (errorElement) {
    errorElement.remove();
  }
}

/**
 * Update the status of a meeting
 * @param {string|number} meetingId - The ID of the meeting to update
 * @param {string} status - The new status (accepted, rejected, etc.)
 * @param {HTMLElement} button - The button that triggered the update
 */
function updateMeetingStatus(meetingId, status, button) {
  // In a real application, this would be an AJAX call to update the status
  console.log(`Updating meeting ${meetingId} status to ${status}`);
  
  // Show loading state
  const originalText = button.innerHTML;
  button.disabled = true;
  button.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
  
  // Simulate API call
  setTimeout(() => {
    // Update UI
    const meetingItem = button.closest('.meeting-item');
    if (meetingItem) {
      // Update status badge
      const statusBadge = meetingItem.querySelector('.status-badge');
      if (statusBadge) {
        statusBadge.className = `status-badge ${getStatusClass(status)}`;
        statusBadge.textContent = getStatusLabel(status);
      }
      
      // Hide action buttons
      const actionButtons = meetingItem.querySelectorAll('.meeting-action');
      actionButtons.forEach(btn => {
        btn.classList.add('hidden');
      });
      
      // Show success message
      const message = document.createElement('div');
      message.className = 'text-green-500 text-sm mt-2';
      message.textContent = status === 'accepted' ? 
        'Vous avez accepté cette réunion' : 
        'Vous avez refusé cette réunion';
      
      meetingItem.appendChild(message);
    }
    
    // Reset button state
    button.disabled = false;
    button.innerHTML = originalText;
  }, 1000);
}

/**
 * Show the reschedule form for a meeting
 * @param {string|number} meetingId - The ID of the meeting to reschedule
 */
function showRescheduleForm(meetingId) {
  // In a real application, this would show a modal or expand a form
  console.log(`Showing reschedule form for meeting ${meetingId}`);
  
  // For demonstration, toggle a reschedule form if it exists
  const rescheduleForm = document.querySelector(`#reschedule-form-${meetingId}`);
  if (rescheduleForm) {
    rescheduleForm.classList.toggle('hidden');
    
    // Initialize date/time pickers in the form
    const dateTimeInputs = rescheduleForm.querySelectorAll('.datetime-picker');
    dateTimeInputs.forEach(input => {
      flatpickr(input, {
        enableTime: true,
        dateFormat: "Y-m-d H:i",
        time_24hr: true,
        minDate: "today",
        minuteIncrement: 15
      });
    });
  }
}

/**
 * Format event time for display
 * @param {Date} start - Start time
 * @param {Date} end - End time
 * @returns {string} Formatted time string
 */
function formatEventTime(start, end) {
  const startDate = new Date(start);
  const endDate = new Date(end);
  
  const options = {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    hour: '2-digit',
    minute: '2-digit'
  };
  
  return `${startDate.toLocaleDateString('fr-FR', options)} - ${endDate.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })}`;
}

/**
 * Get color for meeting status
 * @param {string} status - Meeting status
 * @returns {string} Color code
 */
function getStatusColor(status) {
  const colors = {
    'pending': '#f39c12',   // warning/orange
    'accepted': '#2ecc71',  // success/green
    'rejected': '#e74c3c',  // danger/red
    'cancelled': '#95a5a6', // muted/gray
    'completed': '#3498db'  // info/blue
  };
  
  return colors[status] || colors.pending;
}

/**
 * Get CSS class for meeting status
 * @param {string} status - Meeting status
 * @returns {string} CSS class
 */
function getStatusClass(status) {
  const classes = {
    'pending': 'bg-warning-100 text-warning-800',
    'accepted': 'bg-success-100 text-success-800',
    'rejected': 'bg-danger-100 text-danger-800',
    'cancelled': 'bg-gray-100 text-gray-800',
    'completed': 'bg-info-100 text-info-800'
  };
  
  return classes[status] || classes.pending;
}

/**
 * Get human-readable label for meeting status
 * @param {string} status - Meeting status
 * @returns {string} Status label
 */
function getStatusLabel(status) {
  const labels = {
    'pending': 'En attente',
    'accepted': 'Acceptée',
    'rejected': 'Refusée',
    'cancelled': 'Annulée',
    'completed': 'Terminée'
  };
  
  return labels[status] || labels.pending;
}
