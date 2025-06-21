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
            <h1 class="h2 mb-2 settings-title">
                <i class="bi bi-gear me-2"></i>Paramètres du compte
            </h1>
            <p class="text-muted settings-subtitle">Gérez vos informations personnelles et vos préférences</p>
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
                    <h5 class="mb-0 categories-header">Catégories</h5>
                </div>
                <div class="list-group list-group-flush categories-menu">
                    <a href="#profile-section" class="list-group-item list-group-item-action active" id="profile-tab" data-bs-toggle="list" role="tab">
                        <i class="bi bi-person me-2"></i><span class="category-label">Profil</span>
                    </a>
                    <a href="#notifications-section" class="list-group-item list-group-item-action" id="notifications-tab" data-bs-toggle="list" role="tab">
                        <i class="bi bi-bell me-2"></i><span class="category-label">Notifications</span>
                    </a>
                    <a href="#appearance-section" class="list-group-item list-group-item-action" id="appearance-tab" data-bs-toggle="list" role="tab">
                        <i class="bi bi-palette me-2"></i><span class="category-label">Apparence</span>
                    </a>
                    <a href="#security-section" class="list-group-item list-group-item-action" id="security-tab" data-bs-toggle="list" role="tab">
                        <i class="bi bi-shield-lock me-2"></i><span class="category-label">Sécurité</span>
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
                    <div class="mb-2 small user-email">
                        <i class="bi bi-envelope me-2 text-muted"></i><span class="email-text"><?php echo h($user['email']); ?></span>
                    </div>
                    <?php if (!empty($user['department'])): ?>
                    <div class="mb-2 small user-department">
                        <i class="bi bi-building me-2 text-muted"></i><span class="department-text"><?php echo h($user['department']); ?></span>
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
                    <div class="card shadow-sm profile-card">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Informations de profil</h5>
                        </div>
                        <div class="card-body content-area">
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
                                        <input type="text" class="form-control" id="specialty" name="specialty" value="<?php echo h(cleanSpecialty($teacher['specialty'])); ?>">
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
                    <div class="card shadow-sm appearance-card">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0 preferences-title">Préférences d'apparence</h5>
                        </div>
                        <div class="card-body content-area">
                            <form action="/tutoring/api/users/update-appearance.php" method="POST" class="needs-validation" novalidate>
                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                
                                <div class="mb-4">
                                    <h6>Thème</h6>
                                    <div class="row g-3 mt-2">
                                        <div class="col-md-4">
                                            <div class="card theme-card">
                                                <div class="card-body p-3">
                                                    <div class="theme-preview light-theme mb-3">
                                                        <div class="theme-preview-content">
                                                            <div class="theme-preview-header"></div>
                                                            <div class="theme-preview-body"></div>
                                                        </div>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="theme" id="theme_light" value="light">
                                                        <label class="form-check-label fw-medium theme-label-light" for="theme_light">Mode Clair</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="card theme-card">
                                                <div class="card-body p-3">
                                                    <div class="theme-preview dark-theme mb-3">
                                                        <div class="theme-preview-content">
                                                            <div class="theme-preview-header"></div>
                                                            <div class="theme-preview-body"></div>
                                                        </div>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="theme" id="theme_dark" value="dark">
                                                        <label class="form-check-label fw-medium theme-label-dark" for="theme_dark">Mode Sombre</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="card theme-card">
                                                <div class="card-body p-3">
                                                    <div class="theme-preview system-theme mb-3">
                                                        <div class="theme-preview-sun">☀️</div>
                                                        <div class="theme-preview-moon">🌙</div>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="theme" id="theme_system" value="system">
                                                        <label class="form-check-label fw-medium theme-label-system" for="theme_system">Système</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <h6>Couleur principale</h6>
                                    <div class="color-palette mt-3">
                                        <div class="form-check form-check-inline color-option">
                                            <input class="form-check-input visually-hidden" type="radio" name="primary_color" id="color_blue" value="blue">
                                            <label class="form-check-label color-preview blue" for="color_blue" title="Bleu">
                                                <span class="color-check"><i class="bi bi-check2"></i></span>
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline color-option">
                                            <input class="form-check-input visually-hidden" type="radio" name="primary_color" id="color_indigo" value="indigo">
                                            <label class="form-check-label color-preview indigo" for="color_indigo" title="Indigo">
                                                <span class="color-check"><i class="bi bi-check2"></i></span>
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline color-option">
                                            <input class="form-check-input visually-hidden" type="radio" name="primary_color" id="color_purple" value="purple">
                                            <label class="form-check-label color-preview purple" for="color_purple" title="Violet">
                                                <span class="color-check"><i class="bi bi-check2"></i></span>
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline color-option">
                                            <input class="form-check-input visually-hidden" type="radio" name="primary_color" id="color_pink" value="pink">
                                            <label class="form-check-label color-preview pink" for="color_pink" title="Rose">
                                                <span class="color-check"><i class="bi bi-check2"></i></span>
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline color-option">
                                            <input class="form-check-input visually-hidden" type="radio" name="primary_color" id="color_red" value="red">
                                            <label class="form-check-label color-preview red" for="color_red" title="Rouge">
                                                <span class="color-check"><i class="bi bi-check2"></i></span>
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline color-option">
                                            <input class="form-check-input visually-hidden" type="radio" name="primary_color" id="color_orange" value="orange">
                                            <label class="form-check-label color-preview orange" for="color_orange" title="Orange">
                                                <span class="color-check"><i class="bi bi-check2"></i></span>
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline color-option">
                                            <input class="form-check-input visually-hidden" type="radio" name="primary_color" id="color_yellow" value="yellow">
                                            <label class="form-check-label color-preview yellow" for="color_yellow" title="Jaune">
                                                <span class="color-check"><i class="bi bi-check2"></i></span>
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline color-option">
                                            <input class="form-check-input visually-hidden" type="radio" name="primary_color" id="color_green" value="green">
                                            <label class="form-check-label color-preview green" for="color_green" title="Vert">
                                                <span class="color-check"><i class="bi bi-check2"></i></span>
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline color-option">
                                            <input class="form-check-input visually-hidden" type="radio" name="primary_color" id="color_teal" value="teal">
                                            <label class="form-check-label color-preview teal" for="color_teal" title="Bleu-vert">
                                                <span class="color-check"><i class="bi bi-check2"></i></span>
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline color-option">
                                            <input class="form-check-input visually-hidden" type="radio" name="primary_color" id="color_cyan" value="cyan">
                                            <label class="form-check-label color-preview cyan" for="color_cyan" title="Cyan">
                                                <span class="color-check"><i class="bi bi-check2"></i></span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <h6>Taille de texte</h6>
                                    <div class="font-size-control mt-3">
                                        <div class="d-flex align-items-center">
                                            <span class="me-3 small text-size-icon">A</span>
                                            <div class="flex-grow-1">
                                                <input type="range" class="form-range" min="80" max="120" step="5" value="100" id="fontSize" name="font_size">
                                            </div>
                                            <span class="ms-3 h4 text-size-icon">A</span>
                                        </div>
                                        <div class="text-center mt-2">
                                            <span id="fontSizeValue" class="badge rounded-pill bg-white text-dark font-size-badge">100%</span>
                                        </div>
                                        <div class="font-size-example mt-3 p-3 rounded border readable-card">
                                            <p class="mb-1 font-size-sample">Exemple de texte avec la taille sélectionnée</p>
                                            <p class="mb-0 small text-muted">Cette option définit la taille de base du texte dans l'application</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <h6>Animations et effets</h6>
                                    <div class="animation-options mt-3">
                                        <div class="card mb-2 readable-card">
                                            <div class="card-body">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="animations_enabled" name="animations_enabled" checked>
                                                    <label class="form-check-label fw-medium" for="animations_enabled">Activer les animations</label>
                                                    <div class="form-text mt-1">Les transitions et animations rendent l'interface plus fluide</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card readable-card">
                                            <div class="card-body">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="reduce_motion" name="reduce_motion">
                                                    <label class="form-check-label fw-medium" for="reduce_motion">Réduire les animations</label>
                                                    <div class="form-text mt-1">Option d'accessibilité pour limiter les mouvements à l'écran</div>
                                                </div>
                                            </div>
                                        </div>
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
                    <div class="card shadow-sm security-card">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Paramètres de sécurité</h5>
                        </div>
                        <div class="card-body content-area">
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
                                <div class="login-history-container readable-card">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-striped login-history-table">
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
                                                    <td><i class="bi bi-globe me-1"></i> Chrome / Windows 10</td>
                                                    <td>127.0.0.1</td>
                                                    <td><span class="badge bg-success text-on-light">Succès</span></td>
                                                </tr>
                                                <tr>
                                                    <td><?php echo date('d/m/Y H:i', strtotime('-1 day')); ?></td>
                                                    <td><i class="bi bi-globe me-1"></i> Firefox / Windows 10</td>
                                                    <td>127.0.0.1</td>
                                                    <td><span class="badge bg-success text-on-light">Succès</span></td>
                                                </tr>
                                                <tr>
                                                    <td><?php echo date('d/m/Y H:i', strtotime('-3 day')); ?></td>
                                                    <td><i class="bi bi-globe me-1"></i> Chrome / Windows 10</td>
                                                    <td>127.0.0.1</td>
                                                    <td><span class="badge bg-success text-on-light">Succès</span></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="mb-4">
                                <h6 class="text-danger">Zone dangereuse</h6>
                                <div class="critical-section">
                                    <div class="alert alert-danger mb-3">
                                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                        <strong>Attention :</strong> Ces actions sont irréversibles et peuvent affecter l'accès à votre compte.
                                    </div>
                                    
                                    <p class="text-on-dark small mb-3">Utilisez ces options uniquement si vous êtes certain(e) de ce que vous faites.</p>
                                    
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
    /* Styles communs */
    .settings-title {
        font-weight: 700;
        color: var(--bs-primary);
        margin-bottom: 0.5rem;
    }
    
    .settings-subtitle {
        font-size: 1.1rem;
    }

    /* Styles pour les sections en mode sombre */
    :root.dark-theme .content-area {
        color: white;
    }
    
    /* Style spécifique pour certains contenus en mode sombre */
    :root.dark-theme .form-control,
    :root.dark-theme .form-select {
        background-color: #2a3440;
        color: #ffffff;
        border-color: #34495e;
    }
    
    /* Style pour rendre les labels plus visibles */
    :root.dark-theme .col-form-label {
        color: #ffffff;
        font-weight: 500;
    }
    
    /* Amélioration des textes spécifiques */
    :root.dark-theme .category-label {
        color: #ffffff;
        font-weight: 600;
    }
    
    :root.dark-theme .categories-header {
        color: #333333;
        font-weight: 700;
    }
    
    :root.dark-theme .list-group-item-action:not(.active) .category-label {
        color: #ffffff;
    }
    
    :root.dark-theme .user-email .email-text,
    :root.dark-theme .user-department .department-text {
        color: #ffffff;
    }
    
    :root.dark-theme .preferences-title {
        color: #ffffff;
        font-weight: 600;
    }
    
    /* Style pour les labels de thème */
    :root.dark-theme .theme-label-light {
        color: #121212;
        background-color: #ffffff;
        padding: 2px 8px;
        border-radius: 4px;
    }
    
    :root.dark-theme .theme-label-dark {
        color: #ffffff;
        font-weight: 600;
    }
    
    :root.dark-theme .theme-label-system {
        background: linear-gradient(90deg, #121212 50%, #ffffff 50%);
        background-clip: text;
        -webkit-background-clip: text;
        color: transparent;
        font-weight: 600;
        padding: 2px 8px;
        position: relative;
    }
    
    /* Texte d'exemple pour taille de police */
    :root.dark-theme .font-text-example {
        color: #ffffff;
        font-weight: 500;
        background-color: rgba(255, 255, 255, 0.1);
        padding: 8px;
        border-radius: 4px;
    }
    
    :root.dark-theme .font-text-description {
        color: #ffffff;
        opacity: 0.8;
    }
    
    /* Style pour les textes sur les options de formulaire */
    :root.dark-theme .text-white-75 {
        color: rgba(255, 255, 255, 0.75) !important;
    }
    
    :root.dark-theme .readable-card {
        background-color: white;
        color: #333;
        border-radius: 0.5rem;
    }
    
    :root.dark-theme .readable-card .table {
        color: #333;
    }
    
    :root.dark-theme .form-label {
        font-weight: 500;
    }
    
    :root.dark-theme .profile-card,
    :root.dark-theme .appearance-card,
    :root.dark-theme .security-card {
        border: none;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }
    
    /* Styles pour les prévisualisations des thèmes */
    :root.dark-theme .light-theme.theme-preview {
        background-color: #f8f9fa;
        position: relative;
    }
    
    :root.dark-theme .light-theme.theme-preview::before {
        content: '☀️';
        position: absolute;
        top: 10px;
        left: 10px;
        font-size: 18px;
    }
    
    :root.dark-theme .light-theme.theme-preview::after {
        content: '';
        position: absolute;
        bottom: 20px;
        right: 20px;
        left: 10px;
        height: 10px;
        width: 60%;
        border-radius: 5px;
        background-color: rgba(0, 0, 0, 0.1);
    }
    
    :root.dark-theme .dark-theme.theme-preview {
        background-color: #121212;
        position: relative;
    }
    
    :root.dark-theme .dark-theme.theme-preview::before {
        content: '🌙';
        position: absolute;
        top: 10px;
        left: 10px;
        font-size: 18px;
    }
    
    :root.dark-theme .dark-theme.theme-preview::after {
        content: '';
        position: absolute;
        bottom: 20px;
        right: 20px;
        left: 10px;
        height: 10px;
        width: 60%;
        border-radius: 5px;
        background-color: rgba(255, 255, 255, 0.2);
    }
    
    :root.dark-theme .system-theme.theme-preview {
        background: linear-gradient(110deg, #f8f9fa 50%, #121212 50%);
    }
    
    :root.dark-theme .theme-card.active {
        border-color: #4d96f0;
        box-shadow: 0 0 0 2px #4d96f0, 0 4px 10px rgba(0, 0, 0, 0.25);
    }
    
    /* Styles pour les thèmes */
    .theme-card {
        cursor: pointer;
        border: 2px solid transparent;
        transition: all 0.3s ease;
        overflow: hidden;
        margin-bottom: 0.5rem;
    }
    
    .theme-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }
    
    .theme-card.active {
        border-color: var(--bs-primary);
    }
    
    .theme-preview {
        height: 120px;
        border-radius: 0.5rem;
        border: 1px solid #dee2e6;
        overflow: hidden;
        position: relative;
    }
    
    .theme-preview-content {
        position: relative;
        height: 100%;
    }
    
    .theme-preview-header {
        height: 20px;
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        border-bottom: 1px solid rgba(0,0,0,0.1);
    }
    
    .theme-preview-body {
        position: absolute;
        top: 25px;
        left: 5px;
        right: 5px;
        bottom: 5px;
    }
    
    .theme-preview-body::before,
    .theme-preview-body::after {
        content: '';
        position: absolute;
        border-radius: 4px;
        background-color: rgba(0,0,0,0.1);
    }
    
    .theme-preview-body::before {
        left: 0;
        top: 0;
        width: 70%;
        height: 20px;
    }
    
    .theme-preview-body::after {
        left: 0;
        top: 30px;
        width: 100%;
        height: 60px;
    }
    
    .light-theme {
        background-color: #f8f9fa;
    }
    
    .light-theme .theme-preview-header {
        background-color: #ffffff;
    }
    
    .light-theme .theme-preview-body::before,
    .light-theme .theme-preview-body::after {
        background-color: #e9ecef;
    }
    
    .dark-theme {
        background-color: #212529;
    }
    
    .dark-theme .theme-preview-header {
        background-color: #343a40;
        border-color: #495057;
    }
    
    .dark-theme .theme-preview-body::before,
    .dark-theme .theme-preview-body::after {
        background-color: #495057;
    }
    
    .system-theme {
        background: linear-gradient(to right, #f8f9fa 50%, #212529 50%);
        position: relative;
    }
    
    .theme-preview-sun,
    .theme-preview-moon {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        font-size: 2rem;
    }
    
    .theme-preview-sun {
        left: 25%;
        transform: translate(-50%, -50%);
    }
    
    .theme-preview-moon {
        right: 25%;
        transform: translate(50%, -50%);
    }
    
    /* Styles pour les options de couleur */
    .color-palette {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 0.75rem;
        padding: 0.5rem;
    }
    
    .color-option {
        margin: 0;
    }
    
    .color-preview {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        cursor: pointer;
        border: 2px solid #dee2e6;
        transition: all 0.2s ease;
        position: relative;
        overflow: hidden;
    }
    
    .color-preview:hover {
        transform: scale(1.1);
        box-shadow: 0 0 10px rgba(0,0,0,0.2);
    }
    
    .color-check {
        opacity: 0;
        color: white;
        font-size: 1.2rem;
        text-shadow: 0 0 3px rgba(0,0,0,0.5);
        transition: all 0.2s ease;
    }
    
    input[type="radio"]:checked + .color-preview {
        border-color: #fff;
        box-shadow: 0 0 0 2px var(--bs-primary), 0 0 10px rgba(0,0,0,0.3);
        transform: scale(1.1);
    }
    
    input[type="radio"]:checked + .color-preview .color-check {
        opacity: 1;
    }
    
    /* Couleurs principales */
    .blue { background-color: #0d6efd; }
    .indigo { background-color: #6610f2; }
    .purple { background-color: #6f42c1; }
    .pink { background-color: #d63384; }
    .red { background-color: #dc3545; }
    .orange { background-color: #fd7e14; }
    .yellow { background-color: #ffc107; }
    .green { background-color: #198754; }
    .teal { background-color: #20c997; }
    .cyan { background-color: #0dcaf0; }
    
    /* Style pour la section taille de texte */
    .font-size-control {
        padding: 0.5rem;
    }
    
    .text-size-icon {
        font-weight: bold;
    }
    
    .font-size-badge {
        font-weight: 600;
        padding: 0.5rem 1rem;
    }
    
    .font-size-example {
        background-color: var(--bs-light);
    }
    
    .font-size-sample {
        font-weight: 500;
    }
    
    /* Style pour les animations */
    .animation-options .card {
        border-radius: 0.5rem;
        transition: all 0.3s ease;
    }
    
    .animation-options .card:hover {
        box-shadow: 0 0 15px rgba(0,0,0,0.1);
    }
    
    /* Media queries pour responsive */
    @media (max-width: 768px) {
        .theme-preview {
            height: 100px;
        }
        
        .color-preview {
            width: 35px;
            height: 35px;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialisation des variables
        const currentTheme = localStorage.getItem('theme') || 'light';
        const fontSizeSlider = document.getElementById('fontSize');
        const fontSizeValue = document.getElementById('fontSizeValue');
        const fontSizeSample = document.querySelector('.font-size-sample');
        const newPasswordInput = document.getElementById('new_password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const passwordForm = document.getElementById('password-form');
        const passwordStrengthProgress = document.querySelector('.password-strength .progress-bar');
        const passwordFeedback = document.querySelector('.password-feedback');
        const deleteConfirmationPassword = document.getElementById('delete_confirmation_password');
        const confirmationPasswordHidden = document.getElementById('confirmation_password_hidden');
        const deleteAccountButton = document.getElementById('deleteAccountButton');
        
        // Initialiser le thème actif
        initTheme(currentTheme);
        
        // Gestion du changement de thème
        document.querySelectorAll('.theme-card').forEach(card => {
            card.addEventListener('click', function() {
                const radio = this.querySelector('input[type="radio"]');
                radio.checked = true;
                
                // Mettre à jour les cartes
                document.querySelectorAll('.theme-card').forEach(c => c.classList.remove('active'));
                this.classList.add('active');
                
                // Appliquer le thème
                applyTheme(radio.value);
                
                // Animation de feedback
                this.classList.add('theme-selected');
                setTimeout(() => this.classList.remove('theme-selected'), 500);
            });
        });
        
        // Gestion du slider de taille de texte avec effet en temps réel
        if (fontSizeSlider && fontSizeValue) {
            // Initialiser la valeur du slider depuis le localStorage si disponible
            const savedFontSize = localStorage.getItem('fontSize') || '100';
            fontSizeSlider.value = savedFontSize;
            updateFontSizeDisplay(savedFontSize);
            
            fontSizeSlider.addEventListener('input', function() {
                const size = this.value;
                updateFontSizeDisplay(size);
                
                // Prévisualisation en temps réel
                if (fontSizeSample) {
                    fontSizeSample.style.fontSize = `${size/100}em`;
                }
                
                // Sauvegarder la préférence
                localStorage.setItem('fontSize', size);
            });
        }
        
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
                    
                    // Auto-masquer après 3 secondes pour la sécurité
                    setTimeout(() => {
                        passwordInput.type = 'password';
                        icon.classList.remove('bi-eye-slash');
                        icon.classList.add('bi-eye');
                    }, 3000);
                } else {
                    passwordInput.type = 'password';
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                }
            });
        });
        
        // Gestion de la force du mot de passe
        if (newPasswordInput && passwordStrengthProgress && passwordFeedback) {
            newPasswordInput.addEventListener('input', function() {
                const password = this.value;
                const passwordStrength = document.querySelector('.password-strength');
                
                if (password.length > 0) {
                    passwordStrength.classList.remove('d-none');
                    
                    // Évaluer la force du mot de passe
                    const strengthResult = evaluatePasswordStrength(password);
                    
                    // Mettre à jour la barre de progression
                    passwordStrengthProgress.style.width = strengthResult.score + '%';
                    passwordStrengthProgress.setAttribute('aria-valuenow', strengthResult.score);
                    
                    // Définir la couleur de la barre
                    passwordStrengthProgress.className = 'progress-bar';
                    passwordStrengthProgress.classList.add(strengthResult.colorClass);
                    passwordFeedback.textContent = strengthResult.feedback;
                } else {
                    passwordStrength.classList.add('d-none');
                }
            });
        }
        
        // Validation des mots de passe correspondants
        if (confirmPasswordInput && passwordForm) {
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
                    this.classList.add('is-invalid');
                } else {
                    this.setCustomValidity('');
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                }
            });
        }
        
        // Activation du bouton de suppression de compte
        if (deleteConfirmationPassword && confirmationPasswordHidden && deleteAccountButton) {
            deleteConfirmationPassword.addEventListener('input', function() {
                if (this.value.length > 0) {
                    deleteAccountButton.disabled = false;
                    confirmationPasswordHidden.value = this.value;
                } else {
                    deleteAccountButton.disabled = true;
                    confirmationPasswordHidden.value = '';
                }
            });
        }
        
        // Options de couleur avec sélection améliorée
        document.querySelectorAll('.color-preview').forEach(colorPreview => {
            colorPreview.addEventListener('click', function() {
                const colorRadio = document.getElementById(this.getAttribute('for'));
                if (colorRadio) {
                    colorRadio.checked = true;
                    
                    // Animation de sélection
                    this.classList.add('color-selected');
                    setTimeout(() => this.classList.remove('color-selected'), 300);
                    
                    // Sauvegarder la préférence
                    localStorage.setItem('primaryColor', colorRadio.value);
                }
            });
        });
        
        // Interactions des cartes animation
        document.querySelectorAll('.animation-options .card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
        
        // Réduction de mouvement
        document.getElementById('reduce_motion').addEventListener('change', function() {
            if (this.checked) {
                document.documentElement.classList.add('reduce-motion');
                localStorage.setItem('reduceMotion', 'true');
            } else {
                document.documentElement.classList.remove('reduce-motion');
                localStorage.setItem('reduceMotion', 'false');
            }
        });
        
        // Initialiser l'option de réduction de mouvement
        if (localStorage.getItem('reduceMotion') === 'true') {
            document.getElementById('reduce_motion').checked = true;
            document.documentElement.classList.add('reduce-motion');
        }
        
        // Fonctions utilitaires
        
        // Initialiser le thème
        function initTheme(theme) {
            // Cocher le bon radio
            const themeRadio = document.querySelector(`input[name="theme"][value="${theme}"]`);
            if (themeRadio) {
                themeRadio.checked = true;
                const card = themeRadio.closest('.theme-card');
                if (card) {
                    document.querySelectorAll('.theme-card').forEach(c => c.classList.remove('active'));
                    card.classList.add('active');
                }
            }
            
            // Appliquer le thème
            applyTheme(theme);
        }
        
        // Appliquer un thème
        function applyTheme(theme) {
            // Sauvegarder la préférence
            localStorage.setItem('theme', theme);
            
            // Notifier le système de thème global si disponible
            if (window.ThemeManager && typeof window.ThemeManager.applyTheme === 'function') {
                window.ThemeManager.applyTheme(theme);
            }
        }
        
        // Mettre à jour l'affichage de la taille de police
        function updateFontSizeDisplay(size) {
            if (fontSizeValue) {
                fontSizeValue.textContent = size + '%';
                
                // Mettre à jour la classe du badge selon la taille
                fontSizeValue.className = 'badge rounded-pill font-size-badge';
                if (size < 90) {
                    fontSizeValue.classList.add('bg-info', 'text-white');
                } else if (size > 110) {
                    fontSizeValue.classList.add('bg-warning', 'text-dark');
                } else {
                    fontSizeValue.classList.add('bg-light', 'text-dark');
                }
            }
        }
        
        // Évaluer la force du mot de passe
        function evaluatePasswordStrength(password) {
            let score = 0;
            let feedback = [];
            
            // Longueur
            if (password.length >= 12) {
                score += 25;
            } else if (password.length >= 8) {
                score += 15;
            } else {
                feedback.push('Le mot de passe devrait contenir au moins 8 caractères.');
            }
            
            // Présence de lettres minuscules
            if (password.match(/[a-z]/)) {
                score += 15;
            } else {
                feedback.push('Ajoutez des lettres minuscules.');
            }
            
            // Présence de lettres majuscules
            if (password.match(/[A-Z]/)) {
                score += 15;
            } else {
                feedback.push('Ajoutez des lettres majuscules.');
            }
            
            // Présence de chiffres
            if (password.match(/[0-9]/)) {
                score += 15;
            } else {
                feedback.push('Ajoutez des chiffres.');
            }
            
            // Présence de caractères spéciaux
            if (password.match(/[^a-zA-Z0-9]/)) {
                score += 20;
            } else {
                feedback.push('Ajoutez des caractères spéciaux.');
            }
            
            // Variété de caractères
            const variety = new Set(password.split('')).size;
            score += Math.min(10, variety / 2);
            
            // Déterminer la couleur et le message
            let colorClass, feedbackMsg;
            
            if (score < 40) {
                colorClass = 'bg-danger';
                feedbackMsg = 'Faible - ' + (feedback[0] || 'Mot de passe trop simple');
            } else if (score < 70) {
                colorClass = 'bg-warning';
                feedbackMsg = 'Moyen - ' + (feedback[0] || 'Pourrait être plus fort');
            } else {
                colorClass = 'bg-success';
                feedbackMsg = feedback.length > 0 
                    ? 'Fort - ' + feedback[0] 
                    : 'Excellent - Mot de passe sécurisé';
            }
            
            return {
                score: Math.min(100, score),
                colorClass: colorClass,
                feedback: feedbackMsg
            };
        }
    });
</script>

<?php require_once __DIR__ . '/../common/footer.php'; ?>