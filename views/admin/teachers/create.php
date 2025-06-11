<?php
/**
 * Vue pour créer un nouvel enseignant (tuteur)
 */

// Initialiser les variables
$pageTitle = 'Ajouter un tuteur';
$currentPage = 'tutors';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../../includes/init.php';

// Vérifier les permissions
requireRole(['admin', 'coordinator']);

// Instancier le contrôleur
$teacherController = new TeacherController($db);

// Récupérer les anciennes données du formulaire en cas d'erreur
$formData = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']);

// Récupérer les erreurs du formulaire
$formErrors = $_SESSION['form_errors'] ?? [];
unset($_SESSION['form_errors']);

// Récupérer la liste des départements (à remplacer par une vraie liste)
$departments = [
    'Informatique', 
    'Génie Électrique', 
    'Génie Mécanique', 
    'Génie Civil', 
    'Génie Industriel',
    'Commerce et Gestion',
    'Sciences Humaines',
    'Droit'
];

// Récupérer la liste des titres (à remplacer par une vraie liste)
$titles = [
    'Dr.', 
    'Prof.', 
    'M.', 
    'Mme.',
    'Ing.'
];

// Récupérer la liste des domaines (à remplacer par une vraie liste)
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

// Récupérer la liste des niveaux d'études
$levels = [
    'L1', 'L2', 'L3', 'M1', 'M2', 'Doctorat', 'BTS1', 'BTS2', 'DUT1', 'DUT2'
];

// Récupérer la liste des programmes
$programs = [
    'Licence Informatique',
    'Master Informatique',
    'Licence Génie Électrique',
    'Master Génie Électrique',
    'Licence Génie Mécanique',
    'Master Génie Mécanique',
    'Licence Génie Civil',
    'Master Génie Civil',
    'Licence Génie Industriel',
    'Master Génie Industriel',
    'BTS Informatique',
    'DUT Informatique',
    'BTS Électronique',
    'DUT Électronique'
];

// Simuler les données des entreprises (à remplacer par une vraie liste)
$companies = [
    ['id' => 1, 'name' => 'Acme Corp'],
    ['id' => 2, 'name' => 'TechSolutions'],
    ['id' => 3, 'name' => 'InnovateTech'],
    ['id' => 4, 'name' => 'GlobalSoft'],
    ['id' => 5, 'name' => 'DataPro']
];
?>

<?php require_once __DIR__ . '/../../common/header.php'; ?>

<div class="container-fluid">
    <!-- En-tête de page avec actions -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-0">
                <i class="bi bi-person-badge me-2"></i>Ajouter un tuteur
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/tutoring/views/admin/dashboard.php">Tableau de bord</a></li>
                    <li class="breadcrumb-item"><a href="/tutoring/views/admin/tutors.php">Tuteurs</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Ajouter</li>
                </ol>
            </nav>
        </div>
        
        <a href="/tutoring/views/admin/tutors.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Retour
        </a>
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
    
    <!-- Formulaire d'ajout de tuteur -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Informations du tuteur</h5>
        </div>
        <div class="card-body">
            <form action="/tutoring/views/admin/teachers/store.php" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                
                <div class="row mb-4">
                    <div class="col-12 mb-3">
                        <h5>Compte utilisateur</h5>
                        <hr>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="username" class="form-label">Nom d'utilisateur <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo h($formData['username'] ?? ''); ?>" required>
                        <div class="form-text">Le nom d'utilisateur doit être unique et ne contenir que des lettres, chiffres et tirets.</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo h($formData['email'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="password" class="form-label">Mot de passe <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password" required minlength="8">
                            <button class="btn btn-outline-secondary" type="button" id="toggle-password">
                                <i class="bi bi-eye"></i>
                            </button>
                            <button class="btn btn-outline-secondary" type="button" id="generate-password">
                                <i class="bi bi-magic"></i>
                            </button>
                        </div>
                        <div class="form-text">Le mot de passe doit contenir au moins 8 caractères.</div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-12 mb-3">
                        <h5>Informations personnelles</h5>
                        <hr>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label for="title" class="form-label">Titre</label>
                        <select class="form-select" id="title" name="title">
                            <option value="">-- Aucun --</option>
                            <?php foreach ($titles as $title): ?>
                            <option value="<?php echo h($title); ?>" <?php echo (isset($formData['title']) && $formData['title'] === $title) ? 'selected' : ''; ?>>
                                <?php echo h($title); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-5 mb-3">
                        <label for="first_name" class="form-label">Prénom <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo h($formData['first_name'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-5 mb-3">
                        <label for="last_name" class="form-label">Nom <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo h($formData['last_name'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="profile_image" class="form-label">Photo de profil</label>
                        <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/jpeg,image/png,image/gif">
                        <div class="form-text">Formats acceptés : JPG, PNG, GIF. Taille max : 5 Mo.</div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-12 mb-3">
                        <h5>Informations professionnelles</h5>
                        <hr>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="department" class="form-label">Département <span class="text-danger">*</span></label>
                        <select class="form-select" id="department" name="department" required>
                            <option value="">-- Sélectionner --</option>
                            <?php foreach ($departments as $department): ?>
                            <option value="<?php echo h($department); ?>" <?php echo (isset($formData['department']) && $formData['department'] === $department) ? 'selected' : ''; ?>>
                                <?php echo h($department); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="specialty" class="form-label">Spécialité <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="specialty" name="specialty" value="<?php echo h($formData['specialty'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="office_location" class="form-label">Bureau</label>
                        <input type="text" class="form-control" id="office_location" name="office_location" value="<?php echo h($formData['office_location'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="max_students" class="form-label">Nombre max d'étudiants <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="max_students" name="max_students" value="<?php echo h($formData['max_students'] ?? 5); ?>" min="1" max="20" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="form-check form-switch mt-4">
                            <input class="form-check-input" type="checkbox" id="available" name="available" value="1" <?php echo (!isset($formData['available']) || $formData['available']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="available">Disponible pour de nouvelles affectations</label>
                        </div>
                    </div>
                    <div class="col-12 mb-3">
                        <label for="expertise" class="form-label">Expertise</label>
                        <textarea class="form-control" id="expertise" name="expertise" rows="3"><?php echo h($formData['expertise'] ?? ''); ?></textarea>
                        <div class="form-text">Décrivez les domaines d'expertise du tuteur, ses compétences spécifiques, etc.</div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-12 mb-3">
                        <h5>Préférences d'affectation</h5>
                        <hr>
                    </div>
                    
                    <!-- Préférences de domaine -->
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h6 class="card-title mb-0">Domaines préférés</h6>
                            </div>
                            <div class="card-body">
                                <div class="preferences-container" id="domain-preferences">
                                    <div class="row mb-2 preference-item">
                                        <div class="col-8">
                                            <select class="form-select" name="preferences[DOMAIN][0]">
                                                <option value="">-- Sélectionner un domaine --</option>
                                                <?php foreach ($domains as $domain): ?>
                                                <option value="<?php echo h($domain); ?>"><?php echo h($domain); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-4">
                                            <select class="form-select" name="preferences[DOMAIN][priority][0]">
                                                <?php for ($i = 1; $i <= 10; $i++): ?>
                                                <option value="<?php echo $i; ?>" <?php echo $i == 5 ? 'selected' : ''; ?>><?php echo $i; ?>/10</option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-outline-secondary btn-sm mt-2 add-preference" data-target="domain-preferences" data-type="DOMAIN">
                                    <i class="bi bi-plus-circle me-2"></i>Ajouter un domaine
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Préférences de département -->
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h6 class="card-title mb-0">Départements préférés</h6>
                            </div>
                            <div class="card-body">
                                <div class="preferences-container" id="department-preferences">
                                    <div class="row mb-2 preference-item">
                                        <div class="col-8">
                                            <select class="form-select" name="preferences[DEPARTMENT][0]">
                                                <option value="">-- Sélectionner un département --</option>
                                                <?php foreach ($departments as $department): ?>
                                                <option value="<?php echo h($department); ?>"><?php echo h($department); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-4">
                                            <select class="form-select" name="preferences[DEPARTMENT][priority][0]">
                                                <?php for ($i = 1; $i <= 10; $i++): ?>
                                                <option value="<?php echo $i; ?>" <?php echo $i == 5 ? 'selected' : ''; ?>><?php echo $i; ?>/10</option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-outline-secondary btn-sm mt-2 add-preference" data-target="department-preferences" data-type="DEPARTMENT">
                                    <i class="bi bi-plus-circle me-2"></i>Ajouter un département
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Préférences de niveau -->
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h6 class="card-title mb-0">Niveaux préférés</h6>
                            </div>
                            <div class="card-body">
                                <div class="preferences-container" id="level-preferences">
                                    <div class="row mb-2 preference-item">
                                        <div class="col-8">
                                            <select class="form-select" name="preferences[LEVEL][0]">
                                                <option value="">-- Sélectionner un niveau --</option>
                                                <?php foreach ($levels as $level): ?>
                                                <option value="<?php echo h($level); ?>"><?php echo h($level); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-4">
                                            <select class="form-select" name="preferences[LEVEL][priority][0]">
                                                <?php for ($i = 1; $i <= 10; $i++): ?>
                                                <option value="<?php echo $i; ?>" <?php echo $i == 5 ? 'selected' : ''; ?>><?php echo $i; ?>/10</option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-outline-secondary btn-sm mt-2 add-preference" data-target="level-preferences" data-type="LEVEL">
                                    <i class="bi bi-plus-circle me-2"></i>Ajouter un niveau
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Préférences de programme -->
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h6 class="card-title mb-0">Programmes préférés</h6>
                            </div>
                            <div class="card-body">
                                <div class="preferences-container" id="program-preferences">
                                    <div class="row mb-2 preference-item">
                                        <div class="col-8">
                                            <select class="form-select" name="preferences[PROGRAM][0]">
                                                <option value="">-- Sélectionner un programme --</option>
                                                <?php foreach ($programs as $program): ?>
                                                <option value="<?php echo h($program); ?>"><?php echo h($program); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-4">
                                            <select class="form-select" name="preferences[PROGRAM][priority][0]">
                                                <?php for ($i = 1; $i <= 10; $i++): ?>
                                                <option value="<?php echo $i; ?>" <?php echo $i == 5 ? 'selected' : ''; ?>><?php echo $i; ?>/10</option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-outline-secondary btn-sm mt-2 add-preference" data-target="program-preferences" data-type="PROGRAM">
                                    <i class="bi bi-plus-circle me-2"></i>Ajouter un programme
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Préférences d'entreprise -->
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h6 class="card-title mb-0">Entreprises préférées</h6>
                            </div>
                            <div class="card-body">
                                <div class="preferences-container" id="company-preferences">
                                    <div class="row mb-2 preference-item">
                                        <div class="col-8">
                                            <select class="form-select" name="preferences[COMPANY][0]">
                                                <option value="">-- Sélectionner une entreprise --</option>
                                                <?php foreach ($companies as $company): ?>
                                                <option value="<?php echo h($company['id']); ?>"><?php echo h($company['name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-4">
                                            <select class="form-select" name="preferences[COMPANY][priority][0]">
                                                <?php for ($i = 1; $i <= 10; $i++): ?>
                                                <option value="<?php echo $i; ?>" <?php echo $i == 5 ? 'selected' : ''; ?>><?php echo $i; ?>/10</option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-outline-secondary btn-sm mt-2 add-preference" data-target="company-preferences" data-type="COMPANY">
                                    <i class="bi bi-plus-circle me-2"></i>Ajouter une entreprise
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-end mt-4">
                    <button type="reset" class="btn btn-secondary me-2">Réinitialiser</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Templates pour les préférences -->
<template id="domain-template">
    <div class="row mb-2 preference-item">
        <div class="col-8">
            <select class="form-select" name="preferences[DOMAIN][$index]">
                <option value="">-- Sélectionner un domaine --</option>
                <?php foreach ($domains as $domain): ?>
                <option value="<?php echo h($domain); ?>"><?php echo h($domain); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-3">
            <select class="form-select" name="preferences[DOMAIN][priority][$index]">
                <?php for ($i = 1; $i <= 10; $i++): ?>
                <option value="<?php echo $i; ?>" <?php echo $i == 5 ? 'selected' : ''; ?>><?php echo $i; ?>/10</option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-1">
            <button type="button" class="btn btn-outline-danger btn-sm remove-preference">
                <i class="bi bi-x"></i>
            </button>
        </div>
    </div>
</template>

<template id="department-template">
    <div class="row mb-2 preference-item">
        <div class="col-8">
            <select class="form-select" name="preferences[DEPARTMENT][$index]">
                <option value="">-- Sélectionner un département --</option>
                <?php foreach ($departments as $department): ?>
                <option value="<?php echo h($department); ?>"><?php echo h($department); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-3">
            <select class="form-select" name="preferences[DEPARTMENT][priority][$index]">
                <?php for ($i = 1; $i <= 10; $i++): ?>
                <option value="<?php echo $i; ?>" <?php echo $i == 5 ? 'selected' : ''; ?>><?php echo $i; ?>/10</option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-1">
            <button type="button" class="btn btn-outline-danger btn-sm remove-preference">
                <i class="bi bi-x"></i>
            </button>
        </div>
    </div>
</template>

<template id="level-template">
    <div class="row mb-2 preference-item">
        <div class="col-8">
            <select class="form-select" name="preferences[LEVEL][$index]">
                <option value="">-- Sélectionner un niveau --</option>
                <?php foreach ($levels as $level): ?>
                <option value="<?php echo h($level); ?>"><?php echo h($level); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-3">
            <select class="form-select" name="preferences[LEVEL][priority][$index]">
                <?php for ($i = 1; $i <= 10; $i++): ?>
                <option value="<?php echo $i; ?>" <?php echo $i == 5 ? 'selected' : ''; ?>><?php echo $i; ?>/10</option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-1">
            <button type="button" class="btn btn-outline-danger btn-sm remove-preference">
                <i class="bi bi-x"></i>
            </button>
        </div>
    </div>
</template>

<template id="program-template">
    <div class="row mb-2 preference-item">
        <div class="col-8">
            <select class="form-select" name="preferences[PROGRAM][$index]">
                <option value="">-- Sélectionner un programme --</option>
                <?php foreach ($programs as $program): ?>
                <option value="<?php echo h($program); ?>"><?php echo h($program); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-3">
            <select class="form-select" name="preferences[PROGRAM][priority][$index]">
                <?php for ($i = 1; $i <= 10; $i++): ?>
                <option value="<?php echo $i; ?>" <?php echo $i == 5 ? 'selected' : ''; ?>><?php echo $i; ?>/10</option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-1">
            <button type="button" class="btn btn-outline-danger btn-sm remove-preference">
                <i class="bi bi-x"></i>
            </button>
        </div>
    </div>
</template>

<template id="company-template">
    <div class="row mb-2 preference-item">
        <div class="col-8">
            <select class="form-select" name="preferences[COMPANY][$index]">
                <option value="">-- Sélectionner une entreprise --</option>
                <?php foreach ($companies as $company): ?>
                <option value="<?php echo h($company['id']); ?>"><?php echo h($company['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-3">
            <select class="form-select" name="preferences[COMPANY][priority][$index]">
                <?php for ($i = 1; $i <= 10; $i++): ?>
                <option value="<?php echo $i; ?>" <?php echo $i == 5 ? 'selected' : ''; ?>><?php echo $i; ?>/10</option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-1">
            <button type="button" class="btn btn-outline-danger btn-sm remove-preference">
                <i class="bi bi-x"></i>
            </button>
        </div>
    </div>
</template>

<!-- Scripts spécifiques -->
<script>
    // Toggle password visibility
    document.getElementById('toggle-password').addEventListener('click', function() {
        const passwordField = document.getElementById('password');
        const icon = this.querySelector('i');
        
        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        } else {
            passwordField.type = 'password';
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
    });
    
    // Generate random password
    document.getElementById('generate-password').addEventListener('click', function() {
        const passwordField = document.getElementById('password');
        const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-+=';
        let password = '';
        
        for (let i = 0; i < 12; i++) {
            password += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        
        passwordField.type = 'text';
        passwordField.value = password;
        
        // Update toggle button icon
        const icon = document.querySelector('#toggle-password i');
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    });
    
    // Add preference item
    document.querySelectorAll('.add-preference').forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const type = this.getAttribute('data-type');
            const container = document.getElementById(targetId);
            const template = document.getElementById(type.toLowerCase() + '-template');
            
            if (container && template) {
                const preferenceItems = container.querySelectorAll('.preference-item');
                const index = preferenceItems.length;
                
                // Clone template content
                const clone = template.content.cloneNode(true);
                
                // Update index in name attributes
                clone.querySelectorAll('[name]').forEach(element => {
                    const name = element.getAttribute('name');
                    element.setAttribute('name', name.replace('$index', index));
                });
                
                // Add remove event listener
                clone.querySelector('.remove-preference').addEventListener('click', function() {
                    this.closest('.preference-item').remove();
                });
                
                container.appendChild(clone);
            }
        });
    });
    
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