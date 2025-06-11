<?php
/**
 * Vue pour la création d'un utilisateur
 */

// Initialiser les variables
$pageTitle = 'Ajouter un utilisateur';
$currentPage = 'users';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../../includes/init.php';

// Vérifier les permissions
requireRole(['admin']);

// Récupérer les erreurs et les données du formulaire précédent s'il y en a
$errors = $_SESSION['form_errors'] ?? [];
$formData = $_SESSION['form_data'] ?? [];

// Nettoyer les erreurs et les données de session
unset($_SESSION['form_errors']);
unset($_SESSION['form_data']);

// Inclure l'en-tête
include_once __DIR__ . '/../../common/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <!-- En-tête de page -->
            <div class="d-flex align-items-center mb-4">
                <a href="/tutoring/views/admin/users.php" class="btn btn-outline-secondary me-3">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h2 class="mb-0"><i class="bi bi-person-plus me-2"></i>Ajouter un utilisateur</h2>
            </div>
            
            <!-- Afficher les erreurs s'il y en a -->
            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger mb-4">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                    <li><?php echo h($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <!-- Formulaire de création d'utilisateur -->
            <div class="card">
                <div class="card-body p-4">
                    <form action="/tutoring/admin/user/store.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        
                        <!-- Informations personnelles -->
                        <div class="mb-4">
                            <h5 class="card-title mb-3">Informations personnelles</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="first_name" class="form-label">Prénom *</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo h($formData['first_name'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="last_name" class="form-label">Nom *</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo h($formData['last_name'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo h($formData['email'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="phone" class="form-label">Téléphone</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo h($formData['phone'] ?? ''); ?>">
                                </div>
                                <div class="col-md-12">
                                    <label for="department" class="form-label">Département</label>
                                    <input type="text" class="form-control" id="department" name="department" value="<?php echo h($formData['department'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Informations de compte -->
                        <div class="mb-4">
                            <h5 class="card-title mb-3">Informations de compte</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="username" class="form-label">Nom d'utilisateur *</label>
                                    <input type="text" class="form-control" id="username" name="username" value="<?php echo h($formData['username'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="role" class="form-label">Rôle *</label>
                                    <select class="form-select" id="role" name="role" required>
                                        <option value="" disabled <?php echo empty($formData['role']) ? 'selected' : ''; ?>>Sélectionner un rôle</option>
                                        <option value="student" <?php echo isset($formData['role']) && $formData['role'] === 'student' ? 'selected' : ''; ?>>Étudiant</option>
                                        <option value="teacher" <?php echo isset($formData['role']) && $formData['role'] === 'teacher' ? 'selected' : ''; ?>>Tuteur</option>
                                        <option value="coordinator" <?php echo isset($formData['role']) && $formData['role'] === 'coordinator' ? 'selected' : ''; ?>>Coordinateur</option>
                                        <option value="admin" <?php echo isset($formData['role']) && $formData['role'] === 'admin' ? 'selected' : ''; ?>>Administrateur</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="password" class="form-label">Mot de passe *</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="confirm_password" class="form-label">Confirmer le mot de passe *</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Photo de profil -->
                        <div class="mb-4">
                            <h5 class="card-title mb-3">Photo de profil</h5>
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label for="profile_image" class="form-label">Photo de profil (JPG, PNG, max. 2Mo)</label>
                                    <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/jpeg, image/png">
                                    <div class="form-text">Laissez vide pour utiliser l'avatar par défaut.</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Paramètres spécifiques au rôle (s'affiche dynamiquement) -->
                        <div id="role-specific-fields" class="mb-4" style="display: none;">
                            <!-- Pour les étudiants -->
                            <div id="student-fields" class="role-fields" style="display: none;">
                                <h5 class="card-title mb-3">Informations de l'étudiant</h5>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="program" class="form-label">Programme d'études</label>
                                        <input type="text" class="form-control" id="program" name="student_fields[program]" value="<?php echo h($formData['student_fields']['program'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="year" class="form-label">Année d'études</label>
                                        <select class="form-select" id="year" name="student_fields[year]">
                                            <option value="" disabled selected>Sélectionner une année</option>
                                            <option value="1">1ère année</option>
                                            <option value="2">2ème année</option>
                                            <option value="3">3ème année</option>
                                            <option value="4">4ème année</option>
                                            <option value="5">5ème année</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Pour les tuteurs -->
                            <div id="teacher-fields" class="role-fields" style="display: none;">
                                <h5 class="card-title mb-3">Informations du tuteur</h5>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="specialty" class="form-label">Spécialité</label>
                                        <input type="text" class="form-control" id="specialty" name="teacher_fields[specialty]" value="<?php echo h($formData['teacher_fields']['specialty'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="max_students" class="form-label">Nombre maximum d'étudiants</label>
                                        <input type="number" class="form-control" id="max_students" name="teacher_fields[max_students]" value="<?php echo h($formData['teacher_fields']['max_students'] ?? '5'); ?>" min="1" max="20">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Boutons d'action -->
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="/tutoring/views/admin/users.php" class="btn btn-outline-secondary">Annuler</a>
                            <button type="submit" class="btn btn-primary">Créer l'utilisateur</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Gestion de l'affichage des champs spécifiques au rôle
        const roleSelect = document.getElementById('role');
        const roleSpecificFields = document.getElementById('role-specific-fields');
        const studentFields = document.getElementById('student-fields');
        const teacherFields = document.getElementById('teacher-fields');
        
        // Fonction pour afficher les champs spécifiques au rôle sélectionné
        function showRoleFields() {
            const role = roleSelect.value;
            
            // Cacher d'abord tous les champs spécifiques
            roleSpecificFields.style.display = 'none';
            studentFields.style.display = 'none';
            teacherFields.style.display = 'none';
            
            // Afficher les champs spécifiques au rôle sélectionné
            if (role === 'student') {
                roleSpecificFields.style.display = 'block';
                studentFields.style.display = 'block';
            } else if (role === 'teacher') {
                roleSpecificFields.style.display = 'block';
                teacherFields.style.display = 'block';
            }
        }
        
        // Initialiser l'affichage des champs spécifiques au rôle
        showRoleFields();
        
        // Ajouter un écouteur d'événement pour le changement de rôle
        roleSelect.addEventListener('change', showRoleFields);
        
        // Validation de confirmation du mot de passe
        const passwordField = document.getElementById('password');
        const confirmPasswordField = document.getElementById('confirm_password');
        const form = document.querySelector('form');
        
        form.addEventListener('submit', function(e) {
            if (passwordField.value !== confirmPasswordField.value) {
                e.preventDefault();
                alert('Les mots de passe ne correspondent pas.');
                confirmPasswordField.focus();
            }
        });
    });
</script>

<?php
// Inclure le pied de page
include_once __DIR__ . '/../../common/footer.php';
?>