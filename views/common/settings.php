<?php
/**
 * Vue pour les paramètres du compte utilisateur
 */

// Titre de la page
$pageTitle = 'Paramètres du compte';

// Page actuelle pour le menu
$currentPage = 'settings';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../includes/init.php';

// Vérifier que l'utilisateur est connecté
requireLogin();

// Instancier le contrôleur utilisateur
$userController = new UserController($db);
$user = $userController->getById($_SESSION['user_id']);

// Récupérer les erreurs de formulaire
$formErrors = $_SESSION['form_errors'] ?? [];
unset($_SESSION['form_errors']);

// Récupérer les données de formulaire
$formData = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']);

// Récupérer le message flash
$flashMessage = getFlashMessage();

// Inclure l'en-tête
include_once __DIR__ . '/../common/header.php';
?>

<div class="container-fluid py-4">
    <!-- En-tête de page -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h2 mb-2">
                <i class="bi bi-gear me-2"></i>Paramètres du compte
            </h1>
            <p class="text-muted">Gérez vos informations personnelles et vos préférences</p>
        </div>
    </div>

    <!-- Message flash -->
    <?php if ($flashMessage): ?>
    <div class="alert alert-<?php echo $flashMessage['type'] === 'error' ? 'danger' : $flashMessage['type']; ?> alert-dismissible fade show" role="alert">
        <?php if ($flashMessage['type'] === 'success'): ?>
        <i class="bi bi-check-circle me-2"></i>
        <?php elseif ($flashMessage['type'] === 'error'): ?>
        <i class="bi bi-exclamation-triangle me-2"></i>
        <?php elseif ($flashMessage['type'] === 'info'): ?>
        <i class="bi bi-info-circle me-2"></i>
        <?php endif; ?>
        <?php echo $flashMessage['message']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

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

    <!-- Contenu principal -->
    <div class="row">
        <!-- Menu de navigation des paramètres -->
        <div class="col-md-3 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Catégories</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="#profile-section" class="list-group-item list-group-item-action active" id="profile-tab" data-bs-toggle="list" role="tab">
                        <i class="bi bi-person me-2"></i>Profil
                    </a>
                    <a href="#notifications-section" class="list-group-item list-group-item-action" id="notifications-tab" data-bs-toggle="list" role="tab">
                        <i class="bi bi-bell me-2"></i>Notifications
                    </a>
                    <a href="#appearance-section" class="list-group-item list-group-item-action" id="appearance-tab" data-bs-toggle="list" role="tab">
                        <i class="bi bi-palette me-2"></i>Apparence
                    </a>
                    <a href="#security-section" class="list-group-item list-group-item-action" id="security-tab" data-bs-toggle="list" role="tab">
                        <i class="bi bi-shield-lock me-2"></i>Sécurité
                    </a>
                </div>
            </div>

            <div class="card mt-4 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <?php if (!empty($user['profile_image'])): ?>
                        <img src="<?php echo h($user['profile_image']); ?>" alt="Photo de profil" class="rounded-circle me-3" width="64" height="64">
                        <?php else: ?>
                        <div class="bg-primary text-white rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                            <span class="h3 mb-0"><?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?></span>
                        </div>
                        <?php endif; ?>
                        <div>
                            <h5 class="mb-0"><?php echo h($user['first_name'] . ' ' . $user['last_name']); ?></h5>
                            <span class="badge bg-<?php 
                                switch($user['role']) {
                                    case 'admin': echo 'danger'; break;
                                    case 'coordinator': echo 'warning'; break;
                                    case 'teacher': echo 'info'; break;
                                    case 'student': echo 'success'; break;
                                    default: echo 'secondary';
                                }
                            ?> mt-1">
                                <?php 
                                    switch($user['role']) {
                                        case 'admin': echo 'Administrateur'; break;
                                        case 'coordinator': echo 'Coordinateur'; break;
                                        case 'teacher': echo 'Tuteur'; break;
                                        case 'student': echo 'Étudiant'; break;
                                        default: echo ucfirst($user['role']);
                                    }
                                ?>
                            </span>
                        </div>
                    </div>
                    <div class="mb-2 small">
                        <i class="bi bi-envelope me-2 text-muted"></i><?php echo h($user['email']); ?>
                    </div>
                    <?php if (!empty($user['department'])): ?>
                    <div class="mb-2 small">
                        <i class="bi bi-building me-2 text-muted"></i><?php echo h($user['department']); ?>
                    </div>
                    <?php endif; ?>
                    <div class="small text-muted">
                        <i class="bi bi-clock-history me-2"></i>Compte créé le <?php echo formatDate($user['created_at'], 'd/m/Y'); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contenu des paramètres -->
        <div class="col-md-9">
            <div class="tab-content">
                <!-- Paramètres du profil -->
                <div class="tab-pane fade show active" id="profile-section" role="tabpanel" aria-labelledby="profile-tab">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Informations de profil</h5>
                        </div>
                        <div class="card-body">
                            <form action="/tutoring/api/users/update-profile.php" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                
                                <div class="row mb-3">
                                    <label for="first_name" class="col-sm-3 col-form-label">Prénom</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo h($formData['first_name'] ?? $user['first_name']); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <label for="last_name" class="col-sm-3 col-form-label">Nom</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo h($formData['last_name'] ?? $user['last_name']); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <label for="email" class="col-sm-3 col-form-label">Email</label>
                                    <div class="col-sm-9">
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo h($formData['email'] ?? $user['email']); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <label for="username" class="col-sm-3 col-form-label">Nom d'utilisateur</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" id="username" value="<?php echo h($user['username']); ?>" readonly>
                                        <div class="form-text">Le nom d'utilisateur ne peut pas être modifié.</div>
                                    </div>
                                </div>
                                
                                <div class="row mb-4">
                                    <label for="profile_image" class="col-sm-3 col-form-label">Photo de profil</label>
                                    <div class="col-sm-9">
                                        <div class="mb-3">
                                            <?php if (!empty($user['profile_image'])): ?>
                                            <div class="d-flex align-items-center mb-2">
                                                <img src="<?php echo h($user['profile_image']); ?>" alt="Photo de profil" class="rounded me-3" style="width: 80px; height: 80px; object-fit: cover;">
                                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteImageModal">
                                                    <i class="bi bi-trash me-1"></i>Supprimer
                                                </button>
                                            </div>
                                            <?php endif; ?>
                                            <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/jpeg,image/png,image/gif">
                                            <div class="form-text">Formats acceptés : JPG, PNG, GIF. Taille max : 5 Mo.</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if ($user['role'] === 'student' && class_exists('Student')): ?>
                                <?php 
                                    $studentModel = new Student($db);
                                    $student = $studentModel->getByUserId($user['id']);
                                ?>
                                <?php if ($student): ?>
                                <hr>
                                <h5 class="mb-3">Informations étudiant</h5>
                                
                                <div class="row mb-3">
                                    <label for="student_number" class="col-sm-3 col-form-label">Numéro étudiant</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" id="student_number" value="<?php echo h($student['student_number']); ?>" readonly>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <label for="program" class="col-sm-3 col-form-label">Programme</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" id="program" name="program" value="<?php echo h($student['program']); ?>">
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <label for="level" class="col-sm-3 col-form-label">Niveau</label>
                                    <div class="col-sm-9">
                                        <select class="form-select" id="level" name="level">
                                            <?php 
                                                $levels = ['L1', 'L2', 'L3', 'M1', 'M2', 'Doctorat', 'BTS1', 'BTS2', 'DUT1', 'DUT2'];
                                                foreach ($levels as $level):
                                            ?>
                                            <option value="<?php echo h($level); ?>" <?php echo $level === $student['level'] ? 'selected' : ''; ?>>
                                                <?php echo h($level); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <label for="skills" class="col-sm-3 col-form-label">Compétences</label>
                                    <div class="col-sm-9">
                                        <textarea class="form-control" id="skills" name="skills" rows="3"><?php echo h($student['skills']); ?></textarea>
                                        <div class="form-text">Listez vos compétences, séparées par des virgules.</div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php if ($user['role'] === 'teacher' && class_exists('Teacher')): ?>
                                <?php 
                                    $teacherModel = new Teacher($db);
                                    $teacher = $teacherModel->getByUserId($user['id']);
                                ?>
                                <?php if ($teacher): ?>
                                <hr>
                                <h5 class="mb-3">Informations tuteur</h5>
                                
                                <div class="row mb-3">
                                    <label for="title" class="col-sm-3 col-form-label">Titre</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" id="title" name="title" value="<?php echo h($teacher['title']); ?>">
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <label for="specialty" class="col-sm-3 col-form-label">Spécialité</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" id="specialty" name="specialty" value="<?php echo h($teacher['specialty']); ?>">
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <label for="office_location" class="col-sm-3 col-form-label">Bureau</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" id="office_location" name="office_location" value="<?php echo h($teacher['office_location']); ?>">
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <label for="max_students" class="col-sm-3 col-form-label">Max. étudiants</label>
                                    <div class="col-sm-9">
                                        <input type="number" class="form-control" id="max_students" name="max_students" value="<?php echo h($teacher['max_students']); ?>" min="1" max="20">
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <label for="expertise" class="col-sm-3 col-form-label">Expertises</label>
                                    <div class="col-sm-9">
                                        <textarea class="form-control" id="expertise" name="expertise" rows="3"><?php echo h($teacher['expertise']); ?></textarea>
                                        <div class="form-text">Listez vos domaines d'expertise, séparés par des virgules.</div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <?php endif; ?>
                                
                                <div class="d-flex justify-content-end">
                                    <button type="reset" class="btn btn-light me-2">Annuler</button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save me-1"></i>Enregistrer les modifications
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Paramètres des notifications -->
                <div class="tab-pane fade" id="notifications-section" role="tabpanel" aria-labelledby="notifications-tab">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Préférences de notifications</h5>
                        </div>
                        <div class="card-body">
                            <form action="/tutoring/api/users/update-notifications.php" method="POST" class="needs-validation" novalidate>
                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                
                                <div class="mb-4">
                                    <h6>Notifications par email</h6>
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="email_messages" name="email_notifications[]" value="messages" checked>
                                        <label class="form-check-label" for="email_messages">Nouveaux messages</label>
                                    </div>
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="email_assignments" name="email_notifications[]" value="assignments" checked>
                                        <label class="form-check-label" for="email_assignments">Affectations de stages</label>
                                    </div>
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="email_meetings" name="email_notifications[]" value="meetings" checked>
                                        <label class="form-check-label" for="email_meetings">Réunions planifiées</label>
                                    </div>
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="email_documents" name="email_notifications[]" value="documents" checked>
                                        <label class="form-check-label" for="email_documents">Nouveaux documents</label>
                                    </div>
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="email_announcements" name="email_notifications[]" value="announcements" checked>
                                        <label class="form-check-label" for="email_announcements">Annonces importantes</label>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <h6>Notifications système</h6>
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="system_messages" name="system_notifications[]" value="messages" checked>
                                        <label class="form-check-label" for="system_messages">Nouveaux messages</label>
                                    </div>
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="system_assignments" name="system_notifications[]" value="assignments" checked>
                                        <label class="form-check-label" for="system_assignments">Affectations de stages</label>
                                    </div>
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="system_meetings" name="system_notifications[]" value="meetings" checked>
                                        <label class="form-check-label" for="system_meetings">Réunions planifiées</label>
                                    </div>
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="system_documents" name="system_notifications[]" value="documents" checked>
                                        <label class="form-check-label" for="system_documents">Nouveaux documents</label>
                                    </div>
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="system_announcements" name="system_notifications[]" value="announcements" checked>
                                        <label class="form-check-label" for="system_announcements">Annonces importantes</label>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <h6>Fréquence des notifications</h6>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="notification_frequency" id="frequency_realtime" value="realtime" checked>
                                        <label class="form-check-label" for="frequency_realtime">
                                            Temps réel
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="notification_frequency" id="frequency_daily" value="daily">
                                        <label class="form-check-label" for="frequency_daily">
                                            Résumé quotidien
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="notification_frequency" id="frequency_weekly" value="weekly">
                                        <label class="form-check-label" for="frequency_weekly">
                                            Résumé hebdomadaire
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-end">
                                    <button type="reset" class="btn btn-light me-2">Annuler</button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save me-1"></i>Enregistrer les préférences
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Paramètres d'apparence -->
                <div class="tab-pane fade" id="appearance-section" role="tabpanel" aria-labelledby="appearance-tab">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Préférences d'apparence</h5>
                        </div>
                        <div class="card-body">
                            <form action="/tutoring/api/users/update-appearance.php" method="POST" class="needs-validation" novalidate>
                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                
                                <div class="mb-4">
                                    <h6>Thème</h6>
                                    <div class="row g-3 mt-2">
                                        <div class="col-md-4">
                                            <div class="card theme-card active">
                                                <div class="card-body p-2">
                                                    <div class="theme-preview light-theme mb-2"></div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="theme" id="theme_light" value="light" checked>
                                                        <label class="form-check-label" for="theme_light">Clair</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="card theme-card">
                                                <div class="card-body p-2">
                                                    <div class="theme-preview dark-theme mb-2"></div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="theme" id="theme_dark" value="dark">
                                                        <label class="form-check-label" for="theme_dark">Sombre</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="card theme-card">
                                                <div class="card-body p-2">
                                                    <div class="theme-preview system-theme mb-2"></div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="theme" id="theme_system" value="system">
                                                        <label class="form-check-label" for="theme_system">Système</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <h6>Couleur principale</h6>
                                    <div class="d-flex flex-wrap gap-2 mt-2">
                                        <div class="form-check form-check-inline color-option">
                                            <input class="form-check-input" type="radio" name="primary_color" id="color_blue" value="blue" checked>
                                            <label class="form-check-label color-preview blue" for="color_blue"></label>
                                        </div>
                                        <div class="form-check form-check-inline color-option">
                                            <input class="form-check-input" type="radio" name="primary_color" id="color_green" value="green">
                                            <label class="form-check-label color-preview green" for="color_green"></label>
                                        </div>
                                        <div class="form-check form-check-inline color-option">
                                            <input class="form-check-input" type="radio" name="primary_color" id="color_purple" value="purple">
                                            <label class="form-check-label color-preview purple" for="color_purple"></label>
                                        </div>
                                        <div class="form-check form-check-inline color-option">
                                            <input class="form-check-input" type="radio" name="primary_color" id="color_orange" value="orange">
                                            <label class="form-check-label color-preview orange" for="color_orange"></label>
                                        </div>
                                        <div class="form-check form-check-inline color-option">
                                            <input class="form-check-input" type="radio" name="primary_color" id="color_red" value="red">
                                            <label class="form-check-label color-preview red" for="color_red"></label>
                                        </div>
                                        <div class="form-check form-check-inline color-option">
                                            <input class="form-check-input" type="radio" name="primary_color" id="color_teal" value="teal">
                                            <label class="form-check-label color-preview teal" for="color_teal"></label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <h6>Taille de texte</h6>
                                    <div class="d-flex align-items-center mt-2">
                                        <span class="me-2 small">A</span>
                                        <input type="range" class="form-range" min="80" max="120" step="5" value="100" id="fontSize" name="font_size">
                                        <span class="ms-2 h5">A</span>
                                    </div>
                                    <div class="text-center mt-2">
                                        <span id="fontSizeValue">100%</span>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <h6>Animations</h6>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="animations_enabled" name="animations_enabled" checked>
                                        <label class="form-check-label" for="animations_enabled">Activer les animations</label>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-end">
                                    <button type="reset" class="btn btn-light me-2">Réinitialiser</button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save me-1"></i>Enregistrer les préférences
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Paramètres de sécurité -->
                <div class="tab-pane fade" id="security-section" role="tabpanel" aria-labelledby="security-tab">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Paramètres de sécurité</h5>
                        </div>
                        <div class="card-body">
                            <form action="/tutoring/api/users/update-password.php" method="POST" class="needs-validation" novalidate id="password-form">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                
                                <div class="mb-4">
                                    <h6>Changer de mot de passe</h6>
                                    
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Mot de passe actuel <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                                            <button class="btn btn-outline-secondary toggle-password" type="button" tabindex="-1" data-target="current_password">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">Nouveau mot de passe <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="new_password" name="new_password" required minlength="8">
                                            <button class="btn btn-outline-secondary toggle-password" type="button" tabindex="-1" data-target="new_password">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                        <div class="form-text">Le mot de passe doit contenir au moins 8 caractères.</div>
                                        <div class="password-strength mt-2 d-none">
                                            <div class="progress">
                                                <div class="progress-bar" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                            <div class="mt-1 small password-feedback"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirmer le mot de passe <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8">
                                            <button class="btn btn-outline-secondary toggle-password" type="button" tabindex="-1" data-target="confirm_password">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                        <div class="invalid-feedback">Les mots de passe ne correspondent pas.</div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-end mt-4">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-shield-check me-1"></i>Changer de mot de passe
                                        </button>
                                    </div>
                                </div>
                            </form>
                            
                            <hr>
                            
                            <div class="mb-4">
                                <h6>Historique des connexions</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-striped">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Appareil</th>
                                                <th>Adresse IP</th>
                                                <th>Statut</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><?php echo date('d/m/Y H:i', strtotime('-5 minutes')); ?></td>
                                                <td>Chrome / Windows 10</td>
                                                <td>127.0.0.1</td>
                                                <td><span class="badge bg-success">Succès</span></td>
                                            </tr>
                                            <tr>
                                                <td><?php echo date('d/m/Y H:i', strtotime('-1 day')); ?></td>
                                                <td>Firefox / Windows 10</td>
                                                <td>127.0.0.1</td>
                                                <td><span class="badge bg-success">Succès</span></td>
                                            </tr>
                                            <tr>
                                                <td><?php echo date('d/m/Y H:i', strtotime('-3 day')); ?></td>
                                                <td>Chrome / Windows 10</td>
                                                <td>127.0.0.1</td>
                                                <td><span class="badge bg-success">Succès</span></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="mb-4">
                                <h6 class="text-danger">Zone dangereuse</h6>
                                <p class="text-muted small">Ces actions sont irréversibles et peuvent affecter l'accès à votre compte.</p>
                                
                                <div class="d-flex flex-column flex-sm-row gap-2 mt-3">
                                    <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#resetSessionModal">
                                        <i class="bi bi-arrow-counterclockwise me-1"></i>Réinitialiser la session
                                    </button>
                                    <?php if (hasRole('admin')): ?>
                                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                                        <i class="bi bi-trash me-1"></i>Supprimer le compte
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modales -->
<!-- Modal de suppression d'image -->
<div class="modal fade" id="deleteImageModal" tabindex="-1" aria-labelledby="deleteImageModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteImageModalLabel">Supprimer la photo de profil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer votre photo de profil ? Cette action est irréversible.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form action="/tutoring/api/users/delete-profile-image.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <button type="submit" class="btn btn-danger">Supprimer</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal de réinitialisation de session -->
<div class="modal fade" id="resetSessionModal" tabindex="-1" aria-labelledby="resetSessionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="resetSessionModalLabel">Réinitialiser la session</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir réinitialiser votre session ? Vous serez déconnecté de tous les appareils.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form action="/tutoring/api/auth/reset-session.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <button type="submit" class="btn btn-warning">Réinitialiser la session</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal de suppression de compte -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteAccountModalLabel">Supprimer le compte</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>Attention :</strong> Cette action est irréversible et supprimera définitivement votre compte ainsi que toutes les données associées.
                </div>
                <p>Pour confirmer la suppression, veuillez saisir votre mot de passe :</p>
                <div class="mb-3">
                    <input type="password" class="form-control" id="delete_confirmation_password" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form action="/tutoring/api/users/delete-account.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="confirmation_password" id="confirmation_password_hidden">
                    <button type="submit" class="btn btn-danger" id="deleteAccountButton" disabled>Supprimer définitivement</button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    /* Styles pour les thèmes */
    .theme-card {
        cursor: pointer;
        border: 2px solid transparent;
        transition: all 0.2s ease;
    }
    
    .theme-card.active {
        border-color: var(--bs-primary);
    }
    
    .theme-preview {
        height: 100px;
        border-radius: 0.25rem;
        border: 1px solid #dee2e6;
    }
    
    .light-theme {
        background-color: #ffffff;
    }
    
    .dark-theme {
        background-color: #212529;
    }
    
    .system-theme {
        background: linear-gradient(to right, #ffffff 50%, #212529 50%);
    }
    
    /* Styles pour les options de couleur */
    .color-option {
        margin-right: 0.5rem;
    }
    
    .color-preview {
        display: block;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        cursor: pointer;
        border: 2px solid #dee2e6;
    }
    
    input[type="radio"]:checked + .color-preview {
        border-color: #000;
    }
    
    .blue { background-color: #0d6efd; }
    .green { background-color: #198754; }
    .purple { background-color: #6f42c1; }
    .orange { background-color: #fd7e14; }
    .red { background-color: #dc3545; }
    .teal { background-color: #20c997; }
</style>

<script>
    // Gestion du changement de thème
    document.querySelectorAll('.theme-card').forEach(card => {
        card.addEventListener('click', function() {
            const radio = this.querySelector('input[type="radio"]');
            radio.checked = true;
            
            document.querySelectorAll('.theme-card').forEach(c => c.classList.remove('active'));
            this.classList.add('active');
        });
    });
    
    // Gestion du slider de taille de texte
    const fontSizeSlider = document.getElementById('fontSize');
    const fontSizeValue = document.getElementById('fontSizeValue');
    
    fontSizeSlider.addEventListener('input', function() {
        fontSizeValue.textContent = this.value + '%';
    });
    
    // Gestion de la visibilité des mots de passe
    document.querySelectorAll('.toggle-password').forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const passwordInput = document.getElementById(targetId);
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });
    });
    
    // Gestion de la force du mot de passe
    const newPasswordInput = document.getElementById('new_password');
    const passwordStrengthProgress = document.querySelector('.password-strength .progress-bar');
    const passwordFeedback = document.querySelector('.password-feedback');
    
    newPasswordInput.addEventListener('input', function() {
        const password = this.value;
        const passwordStrength = document.querySelector('.password-strength');
        
        if (password.length > 0) {
            passwordStrength.classList.remove('d-none');
            
            // Évaluer la force du mot de passe
            let strength = 0;
            let feedback = [];
            
            // Longueur
            if (password.length >= 8) {
                strength += 20;
            } else {
                feedback.push('Le mot de passe devrait contenir au moins 8 caractères.');
            }
            
            // Présence de lettres minuscules
            if (password.match(/[a-z]/)) {
                strength += 20;
            } else {
                feedback.push('Ajoutez des lettres minuscules.');
            }
            
            // Présence de lettres majuscules
            if (password.match(/[A-Z]/)) {
                strength += 20;
            } else {
                feedback.push('Ajoutez des lettres majuscules.');
            }
            
            // Présence de chiffres
            if (password.match(/[0-9]/)) {
                strength += 20;
            } else {
                feedback.push('Ajoutez des chiffres.');
            }
            
            // Présence de caractères spéciaux
            if (password.match(/[^a-zA-Z0-9]/)) {
                strength += 20;
            } else {
                feedback.push('Ajoutez des caractères spéciaux.');
            }
            
            // Mettre à jour la barre de progression
            passwordStrengthProgress.style.width = strength + '%';
            passwordStrengthProgress.setAttribute('aria-valuenow', strength);
            
            // Définir la couleur de la barre
            passwordStrengthProgress.className = 'progress-bar';
            if (strength < 40) {
                passwordStrengthProgress.classList.add('bg-danger');
                passwordFeedback.textContent = 'Faible - ' + feedback[0];
            } else if (strength < 80) {
                passwordStrengthProgress.classList.add('bg-warning');
                passwordFeedback.textContent = 'Moyen - ' + (feedback.length > 0 ? feedback[0] : 'Bon mot de passe.');
            } else {
                passwordStrengthProgress.classList.add('bg-success');
                passwordFeedback.textContent = 'Fort - Excellent mot de passe!';
            }
        } else {
            passwordStrength.classList.add('d-none');
        }
    });
    
    // Validation des mots de passe correspondants
    const confirmPasswordInput = document.getElementById('confirm_password');
    const passwordForm = document.getElementById('password-form');
    
    passwordForm.addEventListener('submit', function(e) {
        if (newPasswordInput.value !== confirmPasswordInput.value) {
            e.preventDefault();
            confirmPasswordInput.setCustomValidity('Les mots de passe ne correspondent pas.');
        } else {
            confirmPasswordInput.setCustomValidity('');
        }
    });
    
    confirmPasswordInput.addEventListener('input', function() {
        if (newPasswordInput.value !== this.value) {
            this.setCustomValidity('Les mots de passe ne correspondent pas.');
        } else {
            this.setCustomValidity('');
        }
    });
    
    // Activation du bouton de suppression de compte
    const deleteConfirmationPassword = document.getElementById('delete_confirmation_password');
    const confirmationPasswordHidden = document.getElementById('confirmation_password_hidden');
    const deleteAccountButton = document.getElementById('deleteAccountButton');
    
    deleteConfirmationPassword.addEventListener('input', function() {
        if (this.value.length > 0) {
            deleteAccountButton.disabled = false;
            confirmationPasswordHidden.value = this.value;
        } else {
            deleteAccountButton.disabled = true;
            confirmationPasswordHidden.value = '';
        }
    });
</script>

<?php require_once __DIR__ . '/../common/footer.php'; ?>