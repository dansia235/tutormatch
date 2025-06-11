// Simple Modal functionality
document.addEventListener('DOMContentLoaded', function() {
    // Close modal when clicking on the close button
    document.querySelectorAll('[data-action="modal#close"]').forEach(button => {
        button.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                modal.classList.add('hidden');
            } else {
                // Fallback for other modal implementations
                document.querySelectorAll('.modal, [id$="Modal"]').forEach(modal => {
                    modal.classList.add('hidden');
                });
            }
        });
    });

    // Close modal when clicking outside the modal content
    document.querySelectorAll('[data-modal-target="overlay"]').forEach(overlay => {
        overlay.addEventListener('click', function() {
            const modal = this.closest('.modal, [id$="Modal"]');
            if (modal) {
                modal.classList.add('hidden');
            }
        });
    });

    // Close modal when pressing Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            document.querySelectorAll('.modal:not(.hidden), [id$="Modal"]:not(.hidden)').forEach(modal => {
                modal.classList.add('hidden');
            });
        }
    });
});