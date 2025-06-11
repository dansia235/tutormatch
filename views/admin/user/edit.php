<?php
/**
 * Vue pour l'édition d'un utilisateur
 */

// Initialiser les variables
$pageTitle = 'Modifier un utilisateur';
$currentPage = 'users';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../../includes/init.php';

// Vérifier les permissions
requireRole(['admin', 'coordinator']);

// Vérifier si l'ID est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setFlashMessage('error', 'ID utilisateur non spécifié');
    redirect('/tutoring/views/admin/users.php');
}

$userId = $_GET['id'];

// Instancier le modèle utilisateur
$userModel = new User($db);

// Récupérer les données de l'utilisateur
$user = $userModel->getById($userId);

// Vérifier si l'utilisateur existe
if (!$user) {
    setFlashMessage('error', 'Utilisateur non trouvé');
    redirect('/tutoring/views/admin/users.php');
}

// Vérifier les permissions spécifiques pour les coordinateurs
if (hasRole(['coordinator']) && $user['role'] === 'admin') {
    setFlashMessage('error', 'Vous n\'êtes pas autorisé à modifier un administrateur');
    redirect('/tutoring/views/admin/users.php');
}

// Récupérer les erreurs et les données du formulaire précédent s'il y en a
$errors = $_SESSION['form_errors'] ?? [];
$formData = $_SESSION['form_data'] ?? $user;

// Nettoyer les erreurs et les données de session
unset($_SESSION['form_errors']);
unset($_SESSION['form_data']);

// Récupérer les informations spécifiques au rôle
$studentInfo = null;
$teacherInfo = null;

if ($user['role'] === 'student') {
    // Instancier le modèle étudiant
    $studentModel = new Student($db);
    $studentInfo = $studentModel->getByUserId($userId);
} elseif ($user['role'] === 'teacher') {
    // Instancier le modèle tuteur
    $teacherModel = new Teacher($db);
    $teacherInfo = $teacherModel->getByUserId($userId);
}

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
                <h2 class="mb-0"><i class="bi bi-person-gear me-2"></i>Modifier l'utilisateur</h2>
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
            
            <!-- Formulaire d'édition d'utilisateur -->
            <div class="card">
                <div class="card-body p-4">
                    <form action="/tutoring/admin/user/update.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        <input type="hidden" name="id" value="<?php echo h($user['id']); ?>">
                        
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
                                    <select class="form-select" id="role" name="role" required <?php echo !hasRole(['admin']) ? 'disabled' : ''; ?>>
                                        <option value="student" <?php echo $user['role'] === 'student' ? 'selected' : ''; ?>>Étudiant</option>
                                        <option value="teacher" <?php echo $user['role'] === 'teacher' ? 'selected' : ''; ?>>Tuteur</option>
                                        <option value="coordinator" <?php echo $user['role'] === 'coordinator' ? 'selected' : ''; ?>>Coordinateur</option>
                                        <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Administrateur</option>
                                    </select>
                                    <?php if (!hasRole(['admin'])): ?>
                                    <input type="hidden" name="role" value="<?php echo h($user['role']); ?>">
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <label for="password" class="form-label">Nouveau mot de passe</label>
                                    <input type="password" class="form-control" id="password" name="password">
                                    <div class="form-text">Laissez vide pour conserver le mot de passe actuel.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="confirm_password" class="form-label">Confirmer le nouveau mot de passe</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Photo de profil -->
                        <div class="mb-4">
                            <h5 class="card-title mb-3">Photo de profil</h5>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="text-center mb-3">
                                        <?php if (!empty($user['profile_image'])): ?>
                                        <img src="<?php echo h($user['profile_image']); ?>" alt="Photo de profil" class="img-thumbnail rounded-circle" style="width: 150px; height: 150px; object-fit: cover;">
                                        <?php else: ?>
                                        <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center text-white" style="width: 150px; height: 150px; margin: 0 auto; font-size: 3rem;">
                                            <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <label for="profile_image" class="form-label">Nouvelle photo de profil (JPG, PNG, max. 2Mo)</label>
                                    <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/jpeg, image/png">
                                    <div class="form-text">Laissez vide pour conserver la photo actuelle.</div>
                                    
                                    <?php if (!empty($user['profile_image'])): ?>
                                    <div class="form-check mt-3">
                                        <input class="form-check-input" type="checkbox" id="remove_profile_image" name="remove_profile_image" value="1">
                                        <label class="form-check-label" for="remove_profile_image">
                                            Supprimer la photo de profil actuelle
                                        </label>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Paramètres spécifiques au rôle -->
                        <?php if ($user['role'] === 'student' && $studentInfo): ?>
                        <div id="student-fields" class="mb-4">
                            <h5 class="card-title mb-3">Informations de l'étudiant</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="program" class="form-label">Programme d'études</label>
                                    <input type="text" class="form-control" id="program" name="student_fields[program]" value="<?php echo h($studentInfo['program'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="year" class="form-label">Année d'études</label>
                                    <select class="form-select" id="year" name="student_fields[year]">
                                        <option value="" disabled <?php echo empty($studentInfo['year']) ? 'selected' : ''; ?>>Sélectionner une année</option>
                                        <option value="1" <?php echo isset($studentInfo['year']) && $studentInfo['year'] == 1 ? 'selected' : ''; ?>>1ère année</option>
                                        <option value="2" <?php echo isset($studentInfo['year']) && $studentInfo['year'] == 2 ? 'selected' : ''; ?>>2ème année</option>
                                        <option value="3" <?php echo isset($studentInfo['year']) && $studentInfo['year'] == 3 ? 'selected' : ''; ?>>3ème année</option>
                                        <option value="4" <?php echo isset($studentInfo['year']) && $studentInfo['year'] == 4 ? 'selected' : ''; ?>>4ème année</option>
                                        <option value="5" <?php echo isset($studentInfo['year']) && $studentInfo['year'] == 5 ? 'selected' : ''; ?>>5ème année</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($user['role'] === 'teacher' && $teacherInfo): ?>
                        <div id="teacher-fields" class="mb-4">
                            <h5 class="card-title mb-3">Informations du tuteur</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="specialty" class="form-label">Spécialité</label>
                                    <input type="text" class="form-control" id="specialty" name="teacher_fields[specialty]" value="<?php echo h($teacherInfo['specialty'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="max_students" class="form-label">Nombre maximum d'étudiants</label>
                                    <input type="number" class="form-control" id="max_students" name="teacher_fields[max_students]" value="<?php echo h($teacherInfo['max_students'] ?? '5'); ?>" min="1" max="20">
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Boutons d'action -->
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="/tutoring/views/admin/users.php" class="btn btn-outline-secondary">Annuler</a>
                            <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Validation de confirmation du mot de passe
        const passwordField = document.getElementById('password');
        const confirmPasswordField = document.getElementById('confirm_password');
        const form = document.querySelector('form');
        
        form.addEventListener('submit', function(e) {
            if (passwordField.value !== '' && passwordField.value !== confirmPasswordField.value) {
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