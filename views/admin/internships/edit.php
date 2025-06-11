<?php
/**
 * Vue pour modifier un stage
 */

// Initialiser les variables
$pageTitle = 'Modifier un stage';
$currentPage = 'internships';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../../includes/init.php';

// Vérifier les permissions
requireRole(['admin', 'coordinator']);

// Vérifier l'ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage('error', 'ID de stage invalide');
    redirect('/tutoring/views/admin/internships.php');
}

// Instancier le contrôleur
$internshipController = new InternshipController($db);

// Instancier les modèles
$internshipModel = new Internship($db);
$companyModel = new Company($db);

// Récupérer le stage
$internship = $internshipModel->getById($_GET['id']);

if (!$internship) {
    setFlashMessage('error', 'Stage non trouvé');
    redirect('/tutoring/views/admin/internships.php');
}

// Récupérer les compétences
$skills = $internshipModel->getSkills($internship['id']);

// Récupérer les entreprises pour le formulaire
$companies = $companyModel->getAll(true);

// Récupérer les anciennes données du formulaire en cas d'erreur
$formData = $_SESSION['form_data'] ?? $internship;
unset($_SESSION['form_data']);

// Récupérer les erreurs du formulaire
$formErrors = $_SESSION['form_errors'] ?? [];
unset($_SESSION['form_errors']);

// Liste des domaines (à remplacer par une vraie liste)
$domains = [
    'Informatique',
    'Réseaux',
    'Intelligence Artificielle',
    'Développement Web',
    'Développement Mobile',
    'Base de données',
    'Sécurité Informatique',
    'Cloud Computing',
    'IoT',
    'Robotique',
    'Électronique',
    'Automatique',
    'Mécanique',
    'Génie Civil',
    'Génie Industriel',
    'Logistique',
    'Marketing',
    'Finance',
    'Ressources Humaines',
    'Communication'
];

// Liste des compétences courantes (pour l'autocomplétion)
$commonSkills = [
    'PHP', 'JavaScript', 'HTML', 'CSS', 'React', 'Angular', 'Vue.js', 'Node.js', 
    'Python', 'Java', 'C++', 'C#', 'Swift', 'Kotlin', 'Flutter', 'React Native',
    'SQL', 'NoSQL', 'MongoDB', 'MySQL', 'PostgreSQL', 'Oracle', 'Firebase',
    'AWS', 'Azure', 'Google Cloud', 'Docker', 'Kubernetes', 'Git', 'GitHub',
    'GitLab', 'CI/CD', 'DevOps', 'Linux', 'Windows', 'MacOS', 'Android', 'iOS',
    'Machine Learning', 'Deep Learning', 'Data Science', 'Big Data', 'Hadoop',
    'Spark', 'TensorFlow', 'PyTorch', 'OpenCV', 'Raspberry Pi', 'Arduino',
    'Réseau', 'Sécurité', 'Cybersécurité', 'Pentest', 'Firewall', 'VPN',
    'Virtualisation', 'VMware', 'VirtualBox', 'Proxmox', 'Ansible', 'Terraform',
    'Scrum', 'Agile', 'Kanban', 'Jira', 'Confluence', 'Trello', 'Microsoft Office',
    'Excel', 'Word', 'PowerPoint', 'Photoshop', 'Illustrator', 'Figma', 'Sketch',
    'Adobe XD', 'UX/UI Design', 'SEO', 'Marketing digital', 'Analytics', 'CRM',
    'SAP', 'ERP', 'PRINCE2', 'ITIL', 'ISO 27001', 'RGPD', 'Communication',
    'Gestion de projet', 'Leadership', 'Travail en équipe', 'Français', 'Anglais',
    'Espagnol', 'Allemand', 'Italien', 'Chinois', 'Arabe', 'Russe', 'Japonais'
];

?>

<?php require_once __DIR__ . '/../../common/header.php'; ?>

<div class="container-fluid">
    <!-- En-tête de page avec actions -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-0">
                <i class="bi bi-briefcase me-2"></i>Modifier un stage
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/tutoring/views/admin/dashboard.php">Tableau de bord</a></li>
                    <li class="breadcrumb-item"><a href="/tutoring/views/admin/internships.php">Stages</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Modifier</li>
                </ol>
            </nav>
        </div>
        
        <div class="btn-group" role="group">
            <a href="/tutoring/views/admin/internships/show.php?id=<?php echo $internship['id']; ?>" class="btn btn-outline-primary">
                <i class="bi bi-eye me-2"></i>Voir
            </a>
            <a href="/tutoring/views/admin/internships.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Retour
            </a>
        </div>
    </div>
    
    <!-- Affichage des erreurs du formulaire -->
    <?php if (!empty($formErrors)): ?>
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <strong>Erreurs dans le formulaire :</strong>
        <ul class="mb-0 mt-2">
            <?php foreach ($formErrors as $error): ?>
            <li><?php echo h($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <!-- Formulaire de modification de stage -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Informations du stage</h5>
        </div>
        <div class="card-body">
            <form action="/tutoring/views/admin/internships/update.php" method="POST" class="needs-validation" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="id" value="<?php echo $internship['id']; ?>">
                
                <div class="row mb-4">
                    <div class="col-12 mb-3">
                        <h5>Informations générales</h5>
                        <hr>
                    </div>
                    <div class="col-md-8 mb-3">
                        <label for="title" class="form-label">Titre du stage <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" value="<?php echo h($formData['title']); ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="company_id" class="form-label">Entreprise <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <select class="form-select" id="company_id" name="company_id" required>
                                <option value="">-- Sélectionner --</option>
                                <?php foreach ($companies as $company): ?>
                                <option value="<?php echo h($company['id']); ?>" <?php echo ($formData['company_id'] == $company['id']) ? 'selected' : ''; ?>>
                                    <?php echo h($company['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <a href="/tutoring/views/admin/companies/create.php" class="btn btn-outline-secondary" title="Ajouter une entreprise">
                                <i class="bi bi-plus-lg"></i>
                            </a>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="domain" class="form-label">Domaine <span class="text-danger">*</span></label>
                        <select class="form-select" id="domain" name="domain" required>
                            <option value="">-- Sélectionner --</option>
                            <?php foreach ($domains as $domain): ?>
                            <option value="<?php echo h($domain); ?>" <?php echo ($formData['domain'] === $domain) ? 'selected' : ''; ?>>
                                <?php echo h($domain); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="status" class="form-label">Statut <span class="text-danger">*</span></label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="available" <?php echo ($formData['status'] === 'available') ? 'selected' : ''; ?>>Disponible</option>
                            <option value="assigned" <?php echo ($formData['status'] === 'assigned') ? 'selected' : ''; ?>>Affecté</option>
                            <option value="completed" <?php echo ($formData['status'] === 'completed') ? 'selected' : ''; ?>>Terminé</option>
                            <option value="cancelled" <?php echo ($formData['status'] === 'cancelled') ? 'selected' : ''; ?>>Annulé</option>
                        </select>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-12 mb-3">
                        <h5>Période et localisation</h5>
                        <hr>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="start_date" class="form-label">Date de début <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo h($formData['start_date']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="end_date" class="form-label">Date de fin <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo h($formData['end_date']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="location" class="form-label">Localisation</label>
                        <input type="text" class="form-control" id="location" name="location" value="<?php echo h($formData['location'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="work_mode" class="form-label">Mode de travail <span class="text-danger">*</span></label>
                        <select class="form-select" id="work_mode" name="work_mode" required>
                            <option value="on_site" <?php echo ($formData['work_mode'] === 'on_site') ? 'selected' : ''; ?>>Sur site</option>
                            <option value="remote" <?php echo ($formData['work_mode'] === 'remote') ? 'selected' : ''; ?>>À distance</option>
                            <option value="hybrid" <?php echo ($formData['work_mode'] === 'hybrid') ? 'selected' : ''; ?>>Hybride</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="compensation" class="form-label">Compensation (€/mois)</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="compensation" name="compensation" value="<?php echo h($formData['compensation'] ?? ''); ?>" step="0.01" min="0">
                            <span class="input-group-text">€</span>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-12 mb-3">
                        <h5>Description et prérequis</h5>
                        <hr>
                    </div>
                    <div class="col-12 mb-3">
                        <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="description" name="description" rows="5" required><?php echo h($formData['description']); ?></textarea>
                        <div class="form-text">Décrivez les missions et objectifs du stage.</div>
                    </div>
                    <div class="col-12 mb-3">
                        <label for="requirements" class="form-label">Prérequis</label>
                        <textarea class="form-control" id="requirements" name="requirements" rows="3"><?php echo h($formData['requirements'] ?? ''); ?></textarea>
                        <div class="form-text">Décrivez les prérequis nécessaires pour ce stage (formation, expérience, etc.).</div>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">Compétences requises</label>
                        <div class="skills-container mb-2" id="skills-container">
                            <?php if (!empty($skills)): ?>
                                <?php foreach ($skills as $skill): ?>
                                <div class="input-group mb-2 skill-item">
                                    <input type="text" class="form-control" name="skills[]" value="<?php echo h($skill['skill_name']); ?>" placeholder="Compétence">
                                    <button type="button" class="btn btn-outline-danger remove-skill">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                            <div class="input-group mb-2 skill-item">
                                <input type="text" class="form-control" name="skills[]" placeholder="Compétence">
                                <button type="button" class="btn btn-outline-danger remove-skill">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="add-skill">
                            <i class="bi bi-plus-circle me-2"></i>Ajouter une compétence
                        </button>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between mt-4">
                    <a href="/tutoring/views/admin/internships.php" class="btn btn-secondary">Annuler</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Enregistrer les modifications
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Template pour les compétences -->
<template id="skill-template">
    <div class="input-group mb-2 skill-item">
        <input type="text" class="form-control" name="skills[]" placeholder="Compétence">
        <button type="button" class="btn btn-outline-danger remove-skill">
            <i class="bi bi-x"></i>
        </button>
    </div>
</template>

<!-- Scripts spécifiques -->
<script>
    // Datepicker: Set min date for end_date based on start_date
    document.getElementById('start_date').addEventListener('change', function() {
        document.getElementById('end_date').min = this.value;
        
        // If end_date is before start_date, update it
        const endDate = document.getElementById('end_date');
        if (endDate.value && endDate.value < this.value) {
            endDate.value = this.value;
        }
    });
    
    // Add skill
    document.getElementById('add-skill').addEventListener('click', function() {
        const container = document.getElementById('skills-container');
        const template = document.getElementById('skill-template');
        
        if (container && template) {
            // Clone template content
            const clone = template.content.cloneNode(true);
            
            // Add remove event listener
            clone.querySelector('.remove-skill').addEventListener('click', function() {
                this.closest('.skill-item').remove();
            });
            
            container.appendChild(clone);
        }
    });
    
    // Add remove event listeners to existing skill items
    document.querySelectorAll('.remove-skill').forEach(button => {
        button.addEventListener('click', function() {
            this.closest('.skill-item').remove();
        });
    });
    
    // Initialize datalists for skills (for autocomplete)
    const skillsList = <?php echo json_encode($commonSkills); ?>;
    
    // Create datalist
    const datalist = document.createElement('datalist');
    datalist.id = 'skills-list';
    
    // Add options
    skillsList.forEach(skill => {
        const option = document.createElement('option');
        option.value = skill;
        datalist.appendChild(option);
    });
    
    // Add datalist to the document
    document.body.appendChild(datalist);
    
    // Attach datalist to all skill inputs
    function attachDatalistToSkillInputs() {
        document.querySelectorAll('input[name="skills[]"]').forEach(input => {
            input.setAttribute('list', 'skills-list');
        });
    }
    
    // Attach datalist to initial inputs
    attachDatalistToSkillInputs();
    
    // Attach datalist to new inputs
    const observer = new MutationObserver(mutations => {
        mutations.forEach(mutation => {
            if (mutation.addedNodes.length) {
                attachDatalistToSkillInputs();
            }
        });
    });
    
    observer.observe(document.getElementById('skills-container'), { childList: true });
    
    // Form validation
    (function() {
        'use strict';
        
        // Fetch all forms we want to apply custom validation styles to
        const forms = document.querySelectorAll('.needs-validation');
        
        // Loop over them and prevent submission
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                
                form.classList.add('was-validated');
            }, false);
        });
    })();
</script>

<?php require_once __DIR__ . '/../../common/footer.php'; ?>