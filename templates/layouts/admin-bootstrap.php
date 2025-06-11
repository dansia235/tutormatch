<?php
/**
 * Admin layout template (Bootstrap version)
 * Used for admin and coordinator roles
 */

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    redirect('/tutoring/login.php');
}

// Titre de la page par défaut
$pageTitle = $pageTitle ?? 'TutorMatch';

// Classe CSS active pour la navigation
if (!function_exists('isActive')) {
    function isActive($page) {
        global $currentPage;
        return $currentPage === $page ? 'active' : '';
    }
}

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

$avatarUrl = "https://ui-avatars.com/api/?name=" . urlencode($_SESSION['user_name']) . "&background=" . $avatarBg . "&color=fff";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($pageTitle); ?> | TutorMatch</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- Flatpickr for date picker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/tutoring/assets/css/style.css">
    <link rel="stylesheet" href="/tutoring/assets/css/bootstrap.css">
    
    <!-- Favicon -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎓</text></svg>">
    
    <!-- Chart.js (pour les graphiques) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
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
                    <a href="/tutoring/views/admin/tutors.php" class="nav-link <?php echo isActive('teachers'); ?>">
                        <i class="bi bi-person-badge"></i>
                        Tuteurs
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/tutoring/views/admin/internships/index.php" class="nav-link <?php echo isActive('internships'); ?>">
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
                    <a href="/tutoring/views/admin/reports.php" class="nav-link <?php echo isActive('reports'); ?>">
                        <i class="bi bi-file-earmark-text"></i>
                        Rapports
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
            </ul>
            <hr>
            <div class="user-profile">
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
                                    // TODO: Récupérer le nombre de notifications
                                    $notificationCount = 0;
                                    if ($notificationCount > 0) {
                                        echo '<span class="notification-badge">' . $notificationCount . '</span>';
                                    }
                                    ?>
                                </i>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationsDropdown">
                                <li><h6 class="dropdown-header">Notifications</h6></li>
                                <?php
                                // TODO: Récupérer les notifications
                                ?>
                                <li><a class="dropdown-item" href="#"><i class="bi bi-calendar-check text-primary me-2"></i>Nouvelle réunion planifiée</a></li>
                                <li><a class="dropdown-item" href="#"><i class="bi bi-file-earmark-text text-info me-2"></i>Document à signer</a></li>
                                <li><a class="dropdown-item" href="#"><i class="bi bi-chat-square-text text-success me-2"></i>Nouveau message</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#"><i class="bi bi-bell-fill me-2"></i>Voir toutes les notifications</a></li>
                            </ul>
                        </div>
                        <div class="dropdown">
                            <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="d-none d-sm-inline mx-1"><?php echo h($_SESSION['user_name']); ?></span>
                                <img src="<?php echo h($avatarUrl); ?>" alt="User" width="32" height="32" class="rounded-circle">
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="/tutoring/views/common/profile.php"><i class="bi bi-person me-2"></i>Profil</a></li>
                                <li><a class="dropdown-item" href="/tutoring/views/admin/settings.php"><i class="bi bi-gear me-2"></i>Paramètres</a></li>
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
            <?php include $content; ?>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/fr.js"></script>

    <!-- JavaScript for mobile sidebar toggle -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Flatpickr for date inputs
        flatpickr(".datepicker", {
            locale: "fr",
            dateFormat: "d/m/Y",
            allowInput: true
        });
        
        // Toggle sidebar on mobile
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');
        
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('d-none');
                sidebar.classList.toggle('d-flex');
                if (window.innerWidth < 992) {
                    if (sidebar.classList.contains('d-none')) {
                        mainContent.style.marginLeft = '0';
                    } else {
                        mainContent.style.marginLeft = '250px';
                    }
                }
            });
        }
        
        // Responsive adjustments on window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth < 992) {
                sidebar.classList.add('d-none');
                sidebar.classList.remove('d-flex');
                mainContent.style.marginLeft = '0';
            } else {
                sidebar.classList.remove('d-none');
                sidebar.classList.add('d-flex');
                mainContent.style.marginLeft = '250px';
            }
        });
        
        // Initial check for screen size
        if (window.innerWidth < 992) {
            sidebar.classList.add('d-none');
            sidebar.classList.remove('d-flex');
            mainContent.style.marginLeft = '0';
        }
    });
    </script>
</body>
</html>