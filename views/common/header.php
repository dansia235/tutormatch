<?php
/**
 * En-tête commun pour toutes les pages
 */

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    redirect('/tutoring/login.php');
}

// Titre de la page par défaut
$pageTitle = $pageTitle ?? 'TutorMatch';

// Classe CSS active pour la navigation
function isActive($page) {
    global $currentPage;
    return $currentPage === $page ? 'active' : '';
}
?>
<?php
// Récupérer le thème préféré de l'utilisateur depuis la base de données ou cookie
function getUserTheme() {
    global $db;
    
    // Si l'utilisateur est connecté, essayer de récupérer depuis la base de données
    if (isLoggedIn() && isset($_SESSION['user_id'])) {
        try {
            $stmt = $db->prepare("
                SELECT preference_value 
                FROM user_preferences 
                WHERE user_id = :user_id AND preference_key = 'theme'
                LIMIT 1
            ");
            $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                return $result['preference_value'];
            }
        } catch (PDOException $e) {
            // En cas d'erreur, utiliser la valeur du cookie ou la valeur par défaut
        }
    }
    
    // Sinon, utiliser la valeur du cookie
    return isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light';
}

// Récupérer le thème de l'utilisateur
$userTheme = getUserTheme();

// Déterminer la classe de thème à appliquer
$themeClass = 'light-theme';
if ($userTheme === 'dark') {
    $themeClass = 'dark-theme';
} elseif ($userTheme === 'system') {
    // Pour le thème "système", on utilise light par défaut,
    // le JavaScript se chargera de détecter la préférence du système
    $themeClass = 'light-theme';
}
?>
<!DOCTYPE html>
<html lang="fr" class="<?php echo $themeClass; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($pageTitle); ?> | TutorMatch</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="/tutoring/assets/css/style.css">
    <link rel="stylesheet" href="/tutoring/assets/css/theme-light.css">
    <link rel="stylesheet" href="/tutoring/assets/css/theme-dark.css">
    <link rel="stylesheet" href="/tutoring/assets/css/message-fixes.css">
    
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎓</text></svg>">
    <link rel="stylesheet" href="/tutoring/assets/css/messages.css">
    <link rel="stylesheet" href="/tutoring/assets/css/modal-fixes.css">
    <?php if (isset($extraStyles)) echo $extraStyles; ?>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar d-flex flex-column flex-shrink-0 p-3" style="width: 250px;">
            <div class="logo">
                <div class="graduation-cap-logo">
                    <svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                        <path d="M4.285 9.567a.5.5 0 0 1 .683.183A3.498 3.498 0 0 0 8 11.5a3.498 3.498 0 0 0 3.032-1.75.5.5 0 1 1 .866.5A4.498 4.498 0 0 1 8 12.5a4.498 4.498 0 0 1-3.898-2.25.5.5 0 0 1 .183-.683zM7 6.5C7 7.328 6.552 8 6 8s-1-.672-1-1.5S5.448 5 6 5s1 .672 1 1.5zm4 0c0 .828-.448 1.5-1 1.5s-1-.672-1-1.5S9.448 5 10 5s1 .672 1 1.5z"/>
                    </svg>
                    <span class="graduation-cap">🎓</span>
                </div>
                <div class="logo-text">TutorMatch</div>
            </div>
            <hr>
            <ul class="nav nav-pills flex-column mb-auto">
                <?php if (hasRole(['admin', 'coordinator'])): ?>
                <!-- Menu Admin/Coordinateur -->
                <li class="nav-item">
                    <a href="/tutoring/views/admin/dashboard.php" class="nav-link <?php echo isActive('dashboard'); ?>">
                        <i class="bi bi-speedometer2"></i>
                        Tableau de bord
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/tutoring/views/admin/students.php" class="nav-link <?php echo isActive('students'); ?>">
                        <i class="bi bi-mortarboard"></i>
                        Étudiants
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/tutoring/views/admin/tutors.php" class="nav-link <?php echo isActive('tutors'); ?>">
                        <i class="bi bi-person-badge"></i>
                        Tuteurs
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/tutoring/views/admin/internships.php" class="nav-link <?php echo isActive('internships'); ?>">
                        <i class="bi bi-briefcase"></i>
                        Stages
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/tutoring/views/admin/assignments.php" class="nav-link <?php echo isActive('assignments'); ?>">
                        <i class="bi bi-diagram-3"></i>
                        Affectations
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/tutoring/views/admin/companies.php" class="nav-link <?php echo isActive('companies'); ?>">
                        <i class="bi bi-building"></i>
                        Entreprises
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/tutoring/views/admin/documents.php" class="nav-link <?php echo isActive('documents'); ?>">
                        <i class="bi bi-folder"></i>
                        Documents
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/tutoring/views/admin/evaluations.php" class="nav-link <?php echo isActive('evaluations'); ?>">
                        <i class="bi bi-clipboard-check"></i>
                        Évaluations
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/tutoring/views/admin/messages.php" class="nav-link <?php echo isActive('messages'); ?>">
                        <i class="bi bi-chat-left-text"></i>
                        Messagerie
                        <?php
                        // TODO: Afficher le nombre de messages non lus
                        ?>
                    </a>
                </li>
                <?php if (hasRole('admin')): ?>
                <li class="nav-item">
                    <a href="/tutoring/views/admin/users.php" class="nav-link <?php echo isActive('users'); ?>">
                        <i class="bi bi-people"></i>
                        Utilisateurs
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/tutoring/views/admin/settings.php" class="nav-link <?php echo isActive('settings'); ?>">
                        <i class="bi bi-gear"></i>
                        Paramètres
                    </a>
                </li>
                <?php endif; ?>
                
                <?php elseif (hasRole('teacher')): ?>
                <!-- Menu Tuteur -->
                <li class="nav-item">
                    <a href="/tutoring/views/tutor/dashboard.php" class="nav-link <?php echo isActive('dashboard'); ?>">
                        <i class="bi bi-speedometer2"></i>
                        Tableau de bord
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/tutoring/views/tutor/students.php" class="nav-link <?php echo isActive('students'); ?>">
                        <i class="bi bi-mortarboard"></i>
                        Mes Étudiants
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/tutoring/views/tutor/meetings.php" class="nav-link <?php echo isActive('meetings'); ?>">
                        <i class="bi bi-calendar-event"></i>
                        Réunions
                        <?php
                        // TODO: Afficher le nombre de réunions à venir
                        ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/tutoring/views/tutor/documents.php" class="nav-link <?php echo isActive('documents'); ?>">
                        <i class="bi bi-folder"></i>
                        Documents
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/tutoring/views/tutor/evaluations.php" class="nav-link <?php echo isActive('evaluations'); ?>">
                        <i class="bi bi-clipboard-check"></i>
                        Évaluations
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/tutoring/views/tutor/preferences.php" class="nav-link <?php echo isActive('preferences'); ?>">
                        <i class="bi bi-sliders"></i>
                        Préférences
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/tutoring/views/tutor/messages.php" class="nav-link <?php echo isActive('messages'); ?>">
                        <i class="bi bi-chat-left-text"></i>
                        Messagerie
                        <?php
                        // TODO: Afficher le nombre de messages non lus
                        ?>
                    </a>
                </li>
                
                <?php elseif (hasRole('student')): ?>
                <!-- Menu Étudiant -->
                <li class="nav-item">
                    <a href="/tutoring/views/student/dashboard.php" class="nav-link <?php echo isActive('dashboard'); ?>">
                        <i class="bi bi-speedometer2"></i>
                        Tableau de bord
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/tutoring/views/student/internship.php" class="nav-link <?php echo isActive('internship'); ?>">
                        <i class="bi bi-briefcase"></i>
                        Mon Stage
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/tutoring/views/student/tutor.php" class="nav-link <?php echo isActive('tutor'); ?>">
                        <i class="bi bi-person-badge"></i>
                        Mon Tuteur
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/tutoring/views/student/documents.php" class="nav-link <?php echo isActive('documents'); ?>">
                        <i class="bi bi-folder"></i>
                        Documents
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/tutoring/views/student/meetings.php" class="nav-link <?php echo isActive('meetings'); ?>">
                        <i class="bi bi-calendar-event"></i>
                        Réunions
                        <?php
                        // TODO: Afficher le nombre de réunions à venir
                        ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/tutoring/views/student/messages.php" class="nav-link <?php echo isActive('messages'); ?>">
                        <i class="bi bi-chat-left-text"></i>
                        Messagerie
                        <?php
                        // TODO: Afficher le nombre de messages non lus
                        ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/tutoring/views/student/evaluations.php" class="nav-link <?php echo isActive('evaluations'); ?>">
                        <i class="bi bi-clipboard-check"></i>
                        Évaluations
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/tutoring/views/student/preferences.php" class="nav-link <?php echo isActive('preferences'); ?>">
                        <i class="bi bi-sliders"></i>
                        Préférences
                    </a>
                </li>
                <?php endif; ?>
            </ul>
            <hr>
            <div class="user-profile">
                <?php
                // Récupérer les initiales de l'utilisateur pour l'avatar
                $initials = '';
                if (isset($_SESSION['user_name'])) {
                    $nameParts = explode(' ', $_SESSION['user_name']);
                    if (count($nameParts) >= 2) {
                        $initials = mb_substr($nameParts[0], 0, 1) . mb_substr($nameParts[1], 0, 1);
                    } else {
                        $initials = mb_substr($_SESSION['user_name'], 0, 2);
                    }
                }
                $initials = strtoupper($initials);
                
                // Couleur de fond de l'avatar selon le rôle
                $avatarBg = [
                    'admin' => '3498db',
                    'coordinator' => 'e74c3c',
                    'teacher' => '2ecc71',
                    'student' => 'f39c12'
                ][$_SESSION['user_role']] ?? '95a5a6';
                
                $userName = $_SESSION['user_name'] ?? '';
                $avatarUrl = "https://ui-avatars.com/api/?name=" . urlencode($userName) . "&background=" . $avatarBg . "&color=fff";
                ?>
                <img src="<?php echo h($avatarUrl); ?>" alt="User" class="rounded-circle me-3" width="40" height="40">
                <div class="user-info">
                    <div class="user-name"><?php echo h($_SESSION['user_name']); ?></div>
                    <div class="user-role">
                        <?php
                        // Afficher le rôle en français
                        $roles = [
                            'admin' => 'Administrateur',
                            'coordinator' => 'Coordinateur',
                            'teacher' => 'Tuteur',
                            'student' => 'Étudiant'
                        ];
                        echo h($roles[$_SESSION['user_role']] ?? $_SESSION['user_role']);
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Navbar -->
            <nav class="navbar navbar-expand-lg navbar-light bg-light rounded mb-4">
                <div class="container-fluid">
                    <button class="navbar-toggler" type="button" id="sidebar-toggle">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="search-box me-auto ms-3">
                        <i class="bi bi-search"></i>
                        <input type="text" class="form-control" placeholder="Rechercher...">
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="dropdown me-3">
                            <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="notificationsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-bell fs-5 position-relative">
                                    <?php
                                    // Récupérer le nombre de notifications non lues
                                    try {
                                        if (isset($db) && isLoggedIn()) {
                                            $notificationModel = new Notification($db);
                                            $notificationCount = $notificationModel->countUnread($_SESSION['user_id']);
                                            if ($notificationCount > 0) {
                                                echo '<span class="notification-badge">' . $notificationCount . '</span>';
                                            }
                                        }
                                    } catch (Exception $e) {
                                        // En cas d'erreur, ne pas afficher de badge
                                    }
                                    ?>
                                </i>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationsDropdown">
                                <li><h6 class="dropdown-header">Notifications</h6></li>
                                <?php
                                // Récupérer les 3 dernières notifications
                                try {
                                    if (isset($db) && isLoggedIn()) {
                                        $notificationModel = new Notification($db);
                                        $recentNotifications = $notificationModel->getAll([
                                            'user_id' => $_SESSION['user_id'],
                                            'page' => 1,
                                            'limit' => 3
                                        ]);
                                        
                                        if (!empty($recentNotifications)) {
                                            foreach ($recentNotifications as $notification) {
                                                // Déterminer l'icône en fonction du type
                                                $icon = 'bi-info-circle';
                                                $colorClass = 'text-info';
                                                
                                                switch ($notification['type']) {
                                                    case 'success':
                                                        $icon = 'bi-check-circle';
                                                        $colorClass = 'text-success';
                                                        break;
                                                    case 'error':
                                                        $icon = 'bi-exclamation-circle';
                                                        $colorClass = 'text-danger';
                                                        break;
                                                    case 'warning':
                                                        $icon = 'bi-exclamation-triangle';
                                                        $colorClass = 'text-warning';
                                                        break;
                                                }
                                                
                                                // Déterminer l'URL en fonction du rôle et du type d'élément
                                                $url = '#';
                                                if ($notification['related_type'] && $notification['related_id']) {
                                                    $relatedType = $notification['related_type'];
                                                    $relatedId = $notification['related_id'];
                                                    $userRole = $_SESSION['user_role'];
                                                    
                                                    $rolePrefix = 'admin';
                                                    if ($userRole === 'teacher') {
                                                        $rolePrefix = 'tutor';
                                                    } elseif ($userRole === 'student') {
                                                        $rolePrefix = 'student';
                                                    }
                                                    
                                                    switch ($relatedType) {
                                                        case 'assignment':
                                                            $url = "/tutoring/views/{$rolePrefix}/assignments.php?id=$relatedId";
                                                            break;
                                                        case 'internship':
                                                            $url = "/tutoring/views/{$rolePrefix}/internships.php?id=$relatedId";
                                                            break;
                                                        case 'document':
                                                            $url = "/tutoring/views/{$rolePrefix}/documents.php?id=$relatedId";
                                                            break;
                                                        case 'meeting':
                                                            $url = "/tutoring/views/{$rolePrefix}/meetings.php?id=$relatedId";
                                                            break;
                                                        case 'message':
                                                            $url = "/tutoring/views/{$rolePrefix}/messages.php?conversation=$relatedId";
                                                            break;
                                                        case 'evaluation':
                                                            $url = "/tutoring/views/{$rolePrefix}/evaluations.php?id=$relatedId";
                                                            break;
                                                        case 'company':
                                                            $url = "/tutoring/views/{$rolePrefix}/companies.php?id=$relatedId";
                                                            break;
                                                    }
                                                }
                                                
                                                echo '<li><a class="dropdown-item" href="' . $url . '"><i class="bi ' . $icon . ' ' . $colorClass . ' me-2"></i>' . h($notification['title']) . '</a></li>';
                                            }
                                        } else {
                                            echo '<li><span class="dropdown-item-text">Aucune notification récente</span></li>';
                                        }
                                    } else {
                                        echo '<li><span class="dropdown-item-text">Aucune notification</span></li>';
                                    }
                                } catch (Exception $e) {
                                    echo '<li><span class="dropdown-item-text">Erreur lors du chargement des notifications</span></li>';
                                }
                                ?>
                                <li><hr class="dropdown-divider"></li>
                                <?php if (hasRole(['admin', 'coordinator'])): ?>
                                <li><a class="dropdown-item" href="/tutoring/views/admin/notifications.php"><i class="bi bi-bell-fill me-2"></i>Voir toutes les notifications</a></li>
                                <?php elseif (hasRole('teacher')): ?>
                                <li><a class="dropdown-item" href="/tutoring/views/tutor/notifications.php"><i class="bi bi-bell-fill me-2"></i>Voir toutes les notifications</a></li>
                                <?php elseif (hasRole('student')): ?>
                                <li><a class="dropdown-item" href="/tutoring/views/student/notifications.php"><i class="bi bi-bell-fill me-2"></i>Voir toutes les notifications</a></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        <div class="dropdown">
                            <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="d-none d-sm-inline mx-1"><?php echo h($_SESSION['user_name']); ?></span>
                                <img src="<?php echo h($avatarUrl); ?>" alt="User" width="32" height="32" class="rounded-circle">
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="/tutoring/views/common/profile.php"><i class="bi bi-person me-2"></i>Profil</a></li>
                                <?php if (hasRole('admin')): ?>
                                <li><a class="dropdown-item" href="/tutoring/views/admin/settings.php"><i class="bi bi-gear me-2"></i>Paramètres</a></li>
                                <?php else: ?>
                                <li><a class="dropdown-item" href="/tutoring/views/common/settings.php"><i class="bi bi-gear me-2"></i>Paramètres</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/tutoring/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Déconnexion</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Flash Messages -->
            <?php
            $flashMessage = getFlashMessage();
            if ($flashMessage) {
                $alertClass = [
                    'success' => 'alert-success',
                    'error' => 'alert-danger',
                    'warning' => 'alert-warning',
                    'info' => 'alert-info'
                ][$flashMessage['type']] ?? 'alert-info';
                
                $icon = [
                    'success' => '<i class="bi bi-check-circle-fill me-2"></i>',
                    'error' => '<i class="bi bi-exclamation-triangle-fill me-2"></i>',
                    'warning' => '<i class="bi bi-exclamation-circle-fill me-2"></i>',
                    'info' => '<i class="bi bi-info-circle-fill me-2"></i>'
                ][$flashMessage['type']] ?? '<i class="bi bi-info-circle-fill me-2"></i>';
            ?>
            <div class="container-fluid">
                <div class="alert <?php echo $alertClass; ?> alert-dismissible fade show" role="alert">
                    <?php echo $icon . h($flashMessage['message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
            <?php } ?>

            <!-- Main Content Container -->