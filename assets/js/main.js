/**
 * Script principal pour l'application TutorMatch
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Toggle sidebar on mobile
    const sidebarToggle = document.getElementById('sidebar-toggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
    }
    
    // Initialize date pickers
    if (typeof flatpickr !== 'undefined') {
        flatpickr(".datepicker", {
            locale: "fr",
            dateFormat: "d/m/Y",
            allowInput: true
        });
        
        flatpickr(".datetimepicker", {
            locale: "fr",
            dateFormat: "d/m/Y H:i",
            enableTime: true,
            time_24hr: true,
            allowInput: true
        });
    }
    
    // Initialize select2 if available
    if (typeof $.fn.select2 !== 'undefined') {
        $('.select2').select2({
            theme: 'bootstrap4',
            width: '100%'
        });
    }
    
    // Initialize tooltips
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    if (tooltipTriggerList.length > 0) {
        const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    }
    
    // Initialize popovers
    const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
    if (popoverTriggerList.length > 0) {
        const popoverList = [...popoverTriggerList].map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl));
    }
    
    // Animation on scroll
    const animateOnScroll = () => {
        const elements = document.querySelectorAll('.fade-in');
        elements.forEach(element => {
            const elementPosition = element.getBoundingClientRect().top;
            const windowHeight = window.innerHeight;
            
            if (elementPosition < windowHeight - 100) {
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            }
        });
    };

    window.addEventListener('load', animateOnScroll);
    window.addEventListener('scroll', animateOnScroll);
    
    // Simulate progress animation for progress bars
    document.querySelectorAll('.progress-bar').forEach(bar => {
        const targetWidth = bar.style.width;
        bar.style.width = '0';
        setTimeout(() => {
            bar.style.width = targetWidth;
        }, 500);
    });
    
    // Auto-hide alerts after 5 seconds
    setTimeout(() => {
        document.querySelectorAll('.alert.alert-dismissible').forEach(alert => {
            const closeButton = alert.querySelector('.btn-close');
            if (closeButton) {
                closeButton.click();
            }
        });
    }, 5000);
    
    // Confirm delete actions
    document.querySelectorAll('.confirm-delete').forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer cet élément ? Cette action est irréversible.')) {
                e.preventDefault();
            }
        });
    });
    
    // Handle file uploads with preview
    document.querySelectorAll('.custom-file-input').forEach(input => {
        input.addEventListener('change', function() {
            const fileName = this.files[0]?.name;
            const label = this.nextElementSibling;
            
            if (label) {
                if (fileName) {
                    label.textContent = fileName;
                } else {
                    label.textContent = label.dataset.defaultText || 'Choisir un fichier';
                }
            }
            
            // Preview for images
            const preview = document.querySelector(`#${this.id}-preview`);
            if (preview && this.files && this.files[0]) {
                const file = this.files[0];
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                
                reader.readAsDataURL(file);
            }
        });
    });
    
    // Initialize charts if available
    if (typeof Chart !== 'undefined') {
        // Set default options
        Chart.defaults.font.family = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
        Chart.defaults.color = '#7f8c8d';
        Chart.defaults.responsive = true;
        
        // Load charts with data-chart attribute
        document.querySelectorAll('[data-chart]').forEach(canvas => {
            try {
                const chartData = JSON.parse(canvas.dataset.chart);
                new Chart(canvas, chartData);
            } catch (error) {
                console.error('Error initializing chart:', error);
            }
        });
    }
    
    // Toggle password visibility
    document.querySelectorAll('.toggle-password').forEach(toggle => {
        toggle.addEventListener('click', function() {
            const target = document.querySelector(this.dataset.target);
            if (target) {
                const type = target.getAttribute('type') === 'password' ? 'text' : 'password';
                target.setAttribute('type', type);
                this.querySelector('i').classList.toggle('bi-eye');
                this.querySelector('i').classList.toggle('bi-eye-slash');
            }
        });
    });
    
    // Dynamic form fields
    document.querySelectorAll('.add-field').forEach(button => {
        button.addEventListener('click', function() {
            const container = document.querySelector(this.dataset.container);
            const template = document.querySelector(this.dataset.template);
            
            if (container && template) {
                const clone = template.content.cloneNode(true);
                container.appendChild(clone);
                
                // Initialize new field components if needed
                const newField = container.lastElementChild;
                
                // Initialize datepicker if needed
                if (newField.querySelector('.datepicker')) {
                    flatpickr(newField.querySelector('.datepicker'), {
                        locale: "fr",
                        dateFormat: "d/m/Y",
                        allowInput: true
                    });
                }
                
                // Initialize select2 if needed
                if (typeof $.fn.select2 !== 'undefined' && newField.querySelector('.select2')) {
                    $(newField.querySelector('.select2')).select2({
                        theme: 'bootstrap4',
                        width: '100%'
                    });
                }
            }
        });
    });
    
    // Remove dynamic form field
    document.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('remove-field')) {
            const field = e.target.closest('.dynamic-field');
            if (field) {
                field.remove();
            }
        }
    });
});