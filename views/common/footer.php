<!-- End of Main Content Container -->
            </div> <!-- End of Main Content -->
        </div> <!-- End of d-flex wrapper -->
    </div>

    <!-- Scripts communs -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/fr.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Stimulus JS -->
    <script src="https://unpkg.com/@hotwired/stimulus@3.2.2/dist/stimulus.js"></script>
    
    <!-- Application modules -->
    <script src="/tutoring/assets/js/modules/theme.js"></script>
    <script src="/tutoring/assets/js/fix-links.js"></script>
    
    <!-- Main application script (après Stimulus) -->
    <script type="module" src="/tutoring/assets/js/app.js"></script>
    <script src="/tutoring/assets/js/main.js"></script>
    
    <?php if (isset($extraScripts)) echo $extraScripts; ?>

    <script>
        // Toggle sidebar on mobile
        document.getElementById('sidebar-toggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });

        // Initialize date pickers
        flatpickr(".datepicker", {
            locale: "fr",
            dateFormat: "d/m/Y",
            allowInput: true
        });

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

        // Initialize tooltips
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = bootstrap.Alert.getInstance(alert);
                if (bsAlert) {
                    bsAlert.close();
                }
            });
        }, 5000);
    </script>
</body>
</html>