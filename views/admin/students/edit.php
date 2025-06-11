<?php
/**
 * Vue pour modifier un étudiant
 */

// Initialiser les variables
$pageTitle = 'Modifier un étudiant';
$currentPage = 'students';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../../includes/init.php';

// Vérifier les permissions
requireRole(['admin', 'coordinator']);

// Vérifier l'ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage('error', 'ID d\'étudiant invalide');
    redirect('/tutoring/views/admin/students.php');
}

// Instancier le contrôleur
$studentController = new StudentController($db);

// Instancier le modèle
$studentModel = new Student($db);

// Récupérer l'étudiant
$student = $studentModel->getById($_GET['id']);

if (!$student) {
    setFlashMessage('error', 'Étudiant non trouvé');
    redirect('/tutoring/views/admin/students.php');
}

// Récupérer les anciennes données du formulaire en cas d'erreur
$formData = $_SESSION['form_data'] ?? $student;
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

// Récupérer la liste des programmes (à remplacer par une vraie liste)
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

// Récupérer la liste des niveaux d'études
$levels = [
    'L1', 'L2', 'L3', 'M1', 'M2', 'Doctorat', 'BTS1', 'BTS2', 'DUT1', 'DUT2'
];
?>

<?php require_once __DIR__ . '/../../common/header.php'; ?>

<div class="container-fluid">
    <!-- En-tête de page avec actions -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-0">
                <i class="bi bi-mortarboard me-2"></i>Modifier un étudiant
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/tutoring/views/admin/dashboard.php">Tableau de bord</a></li>
                    <li class="breadcrumb-item"><a href="/tutoring/views/admin/students.php">Étudiants</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Modifier</li>
                </ol>
            </nav>
        </div>
        
        <div class="btn-group" role="group">
            <a href="/tutoring/views/admin/students/show.php?id=<?php echo $student['id']; ?>" class="btn btn-outline-primary">
                <i class="bi bi-eye me-2"></i>Voir
            </a>
            <a href="/tutoring/views/admin/students.php" class="btn btn-outline-secondary">
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
    
    <!-- Formulaire de modification d'étudiant -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Informations de l'étudiant</h5>
        </div>
        <div class="card-body">
            <form action="/tutoring/views/admin/students/update.php" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="id" value="<?php echo $student['id']; ?>">
                
                <div class="row mb-4">
                    <div class="col-12 mb-3">
                        <h5>Compte utilisateur</h5>
                        <hr>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="username" class="form-label">Nom d'utilisateur <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo h($formData['username']); ?>" required readonly>
                        <div class="form-text">Le nom d'utilisateur ne peut pas être modifié.</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo h($formData['email']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="password" class="form-label">Mot de passe</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password" minlength="8">
                            <button class="btn btn-outline-secondary" type="button" id="toggle-password">
                                <i class="bi bi-eye"></i>
                            </button>
                            <button class="btn btn-outline-secondary" type="button" id="generate-password">
                                <i class="bi bi-magic"></i>
                            </button>
                        </div>
                        <div class="form-text">Laissez vide pour conserver le mot de passe actuel. Sinon, le nouveau mot de passe doit contenir au moins 8 caractères.</div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-12 mb-3">
                        <h5>Informations personnelles</h5>
                        <hr>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="first_name" class="form-label">Prénom <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo h($formData['first_name']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="last_name" class="form-label">Nom <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo h($formData['last_name']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="profile_image" class="form-label">Photo de profil</label>
                        <?php if (!empty($student['profile_image'])): ?>
                        <div class="mb-2">
                            <img src="<?php echo h($student['profile_image']); ?>" alt="Profile" class="rounded" width="100">
                        </div>
                        <?php endif; ?>
                        <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/jpeg,image/png,image/gif">
                        <div class="form-text">Formats acceptés : JPG, PNG, GIF. Taille max : 5 Mo. Laissez vide pour conserver l'image actuelle.</div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-12 mb-3">
                        <h5>Informations académiques</h5>
                        <hr>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="student_number" class="form-label">Numéro d'étudiant <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="student_number" name="student_number" value="<?php echo h($formData['student_number']); ?>" required>
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
                        <label for="program" class="form-label">Programme d'études <span class="text-danger">*</span></label>
                        <select class="form-select" id="program" name="program" required>
                            <option value="">-- Sélectionner --</option>
                            <?php foreach ($programs as $program): ?>
                            <option value="<?php echo h($program); ?>" <?php echo (isset($formData['program']) && $formData['program'] === $program) ? 'selected' : ''; ?>>
                                <?php echo h($program); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="level" class="form-label">Niveau d'études <span class="text-danger">*</span></label>
                        <select class="form-select" id="level" name="level" required>
                            <option value="">-- Sélectionner --</option>
                            <?php foreach ($levels as $level): ?>
                            <option value="<?php echo h($level); ?>" <?php echo (isset($formData['level']) && $formData['level'] === $level) ? 'selected' : ''; ?>>
                                <?php echo h($level); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="average_grade" class="form-label">Moyenne générale</label>
                        <input type="number" class="form-control" id="average_grade" name="average_grade" value="<?php echo h($formData['average_grade'] ?? ''); ?>" min="0" max="20" step="0.01">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="graduation_year" class="form-label">Année de diplôme prévue</label>
                        <input type="number" class="form-control" id="graduation_year" name="graduation_year" value="<?php echo h($formData['graduation_year'] ?? ''); ?>" min="<?php echo date('Y'); ?>" max="<?php echo date('Y') + 10; ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="status" class="form-label">Statut <span class="text-danger">*</span></label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="active" <?php echo (isset($formData['status']) && $formData['status'] === 'active') ? 'selected' : ''; ?>>Actif</option>
                            <option value="graduated" <?php echo (isset($formData['status']) && $formData['status'] === 'graduated') ? 'selected' : ''; ?>>Diplômé</option>
                            <option value="suspended" <?php echo (isset($formData['status']) && $formData['status'] === 'suspended') ? 'selected' : ''; ?>>Suspendu</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="cv" class="form-label">CV</label>
                        <?php if (!empty($student['cv_path'])): ?>
                        <div class="mb-2">
                            <a href="<?php echo h($student['cv_path']); ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                <i class="bi bi-file-earmark-text me-2"></i>Voir le CV actuel
                            </a>
                        </div>
                        <?php endif; ?>
                        <input type="file" class="form-control" id="cv" name="cv" accept="application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document">
                        <div class="form-text">Formats acceptés : PDF, DOC, DOCX. Taille max : 5 Mo. Laissez vide pour conserver le CV actuel.</div>
                    </div>
                    <div class="col-12 mb-3">
                        <label for="skills" class="form-label">Compétences</label>
                        <textarea class="form-control" id="skills" name="skills" rows="3"><?php echo h($formData['skills'] ?? ''); ?></textarea>
                        <div class="form-text">Listez les compétences pertinentes pour les stages (technologies, langues, etc.).</div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between mt-4">
                    <a href="/tutoring/views/admin/students.php" class="btn btn-secondary">Annuler</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Enregistrer les modifications
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

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