<?php
/**
 * Admin layout template
 * Used for admin and coordinator roles
 */

// Function to check if a menu item is active
if (!function_exists('isActive')) {
    function isActive($page) {
        global $currentPage;
        return $currentPage === $page ? 'active' : '';
    }
}

// Get user data for profile display
$userName = $_SESSION['user_name'] ?? 'Admin';
$userRole = $_SESSION['user_role'] === 'admin' ? 'Administrateur' : 'Coordinateur';
$userInitials = '';

// Get user initials for avatar
if (isset($_SESSION['user_name'])) {
    $nameParts = explode(' ', $_SESSION['user_name']);
    if (count($nameParts) >= 2) {
        $userInitials = mb_substr($nameParts[0], 0, 1) . mb_substr($nameParts[1], 0, 1);
    } else {
        $userInitials = mb_substr($_SESSION['user_name'], 0, 2);
    }
}
$userInitials = strtoupper($userInitials);

// Avatar color based on role
$avatarBg = $_SESSION['user_role'] === 'admin' ? 'bg-primary-600' : 'bg-accent-600';
?>

<div class="flex h-full" data-controller="sidebar">
    <!-- Sidebar -->
    <div class="sidebar bg-secondary-800 text-white w-64 h-screen fixed pt-5 transition-all duration-300 z-50" data-sidebar-target="sidebar">
        <!-- Logo -->
        <div class="px-6">
            <div class="flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-primary-400" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z" />
                </svg>
            </div>
            <div class="mt-2 text-center text-xl font-bold text-white">TutorMatch</div>
        </div>
        
        <!-- Navigation -->
        <nav class="mt-8 px-4">
            <div class="space-y-1">
                <a href="/tutoring/views/admin/dashboard.php" class="nav-link <?php echo isActive('dashboard'); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M2 10a8 8 0 018-8v8h8a8 8 0 11-16 0z" />
                        <path d="M12 2.252A8.014 8.014 0 0117.748 8H12V2.252z" />
                    </svg>
                    <span>Tableau de bord</span>
                </a>
                <a href="/tutoring/views/admin/students.php" class="nav-link <?php echo isActive('students'); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
                    </svg>
                    <span>Étudiants</span>
                </a>
                <a href="/tutoring/views/admin/tutors.php" class="nav-link <?php echo isActive('tutors'); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z" clip-rule="evenodd" />
                    </svg>
                    <span>Tuteurs</span>
                </a>
                <a href="/tutoring/views/admin/internships.php" class="nav-link <?php echo isActive('internships'); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M6 6V5a3 3 0 013-3h2a3 3 0 013 3v1h2a2 2 0 012 2v3.57A22.952 22.952 0 0110 13a22.95 22.95 0 01-8-1.43V8a2 2 0 012-2h2zm2-1a1 1 0 011-1h2a1 1 0 011 1v1H8V5zm1 5a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1z" clip-rule="evenodd" />
                        <path d="M2 13.692V16a2 2 0 002 2h12a2 2 0 002-2v-2.308A24.974 24.974 0 0110 15c-2.796 0-5.487-.46-8-1.308z" />
                    </svg>
                    <span>Stages</span>
                </a>
                <a href="/tutoring/views/admin/assignments.php" class="nav-link <?php echo isActive('assignments'); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z" />
                    </svg>
                    <span>Affectations</span>
                </a>
                <a href="/tutoring/views/admin/companies.php" class="nav-link <?php echo isActive('companies'); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd" />
                    </svg>
                    <span>Entreprises</span>
                </a>
                <a href="/tutoring/views/admin/documents.php" class="nav-link <?php echo isActive('documents'); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" />
                        <path fill-rule="evenodd" d="M8 11a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1zm0-3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z" clip-rule="evenodd" />
                    </svg>
                    <span>Documents</span>
                </a>

                <a href="/tutoring/views/admin/messages.php" class="nav-link <?php echo isActive('messages'); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M2 5a2 2 0 012-2h7a2 2 0 012 2v4a2 2 0 01-2 2H9l-3 3v-3H4a2 2 0 01-2-2V5z" />
                        <path d="M15 7v2a4 4 0 01-4 4H9.828l-1.766 1.767c.28.149.599.233.938.233h2l3 3v-3h2a2 2 0 002-2V9a2 2 0 00-2-2h-1z" />
                    </svg>
                    <span>Messagerie</span>
                </a>
                
                <a href="/tutoring/views/admin/reports.php" class="nav-link <?php echo isActive('reports'); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm2 10a1 1 0 10-2 0v3a1 1 0 102 0v-3zm2-3a1 1 0 011 1v5a1 1 0 11-2 0v-5a1 1 0 011-1zm4-1a1 1 0 10-2 0v7a1 1 0 102 0V8z" clip-rule="evenodd" />
                    </svg>
                    <span>Rapports</span>
                </a>
                
                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                <a href="/tutoring/views/admin/users.php" class="nav-link <?php echo isActive('users'); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v1h8v-1zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-1a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v1H1v-3a3 3 0 013.75-2.906z" />
                    </svg>
                    <span>Utilisateurs</span>
                </a>
                <a href="/tutoring/views/admin/settings.php" class="nav-link <?php echo isActive('settings'); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" />
                    </svg>
                    <span>Paramètres</span>
                </a>
                <?php endif; ?>
            </div>
        </nav>
        
        <!-- User profile -->
        <div class="mt-auto px-4 pb-5">
            <div class="user-profile">
                <div class="flex items-center p-3 rounded-md bg-white/10">
                    <div class="flex-shrink-0">
                        <div class="flex items-center justify-center <?php echo $avatarBg; ?> h-10 w-10 rounded-full">
                            <span class="text-white font-medium"><?php echo $userInitials; ?></span>
                        </div>
                    </div>
                    <div class="ml-3">
                        <div class="user-name font-semibold text-sm"><?php echo h($userName); ?></div>
                        <div class="user-role text-xs opacity-80"><?php echo h($userRole); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <div class="main-content ml-64 transition-all duration-300 flex-1" data-sidebar-target="content">
        <!-- Top navigation -->
        <div class="bg-white shadow">
            <div class="flex items-center justify-between px-4 py-3">
                <div class="flex items-center">
                    <!-- Mobile menu button -->
                    <button type="button" class="text-gray-500 focus:outline-none lg:hidden" data-action="sidebar#toggle">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    
                    <!-- Search -->
                    <div class="search-box ml-3">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <input type="text" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-primary-500 focus:border-primary-500 sm:text-sm" placeholder="Rechercher...">
                        </div>
                    </div>
                </div>
                
                <!-- Right navigation -->
                <div class="flex items-center space-x-4">
                    <!-- Notifications dropdown -->
                    <div class="relative" data-controller="dropdown" id="notification-dropdown">
                        <button type="button" class="relative p-1 text-gray-400 rounded-full hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500" data-action="dropdown#toggle" data-dropdown-target="button" aria-expanded="false">
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <span id="notification-badge" class="notification-badge absolute -top-1 -right-1 bg-accent-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs">0</span>
                        </button>
                        
                        <!-- Dropdown menu -->
                        <div class="hidden origin-top-right absolute right-0 mt-2 w-80 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 opacity-0 translate-y-2 transition-all duration-300" data-dropdown-target="menu">
                            <div class="py-1" role="menu" aria-orientation="vertical">
                                <div class="px-4 py-2 border-b border-gray-100 flex justify-between items-center">
                                    <h3 class="text-sm font-semibold text-gray-900">Notifications</h3>
                                    <button id="mark-all-read" class="text-xs text-primary-600 hover:text-primary-500">Tout marquer comme lu</button>
                                </div>
                                
                                <div id="notification-list" class="max-h-72 overflow-y-auto">
                                    <!-- Les notifications seront chargées ici dynamiquement -->
                                    <div class="py-8 text-center text-gray-500 text-sm">
                                        <p>Chargement des notifications...</p>
                                    </div>
                                </div>
                                
                                <div class="px-4 py-2 border-t border-gray-100">
                                    <a href="/tutoring/views/admin/notifications.php" class="text-sm font-medium text-primary-600 hover:text-primary-500">Voir toutes les notifications</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <script>
                    // Fonction pour charger les notifications non lues
                    function loadUnreadNotifications() {
                        fetch('/tutoring/api/notifications/unread.php')
                            .then(response => response.json())
                            .then(data => {
                                // Mettre à jour le badge
                                const badge = document.getElementById('notification-badge');
                                const count = data.meta.total_unread;
                                badge.textContent = count;
                                
                                // Afficher ou masquer le badge en fonction du nombre de notifications
                                if (count > 0) {
                                    badge.style.display = 'flex';
                                } else {
                                    badge.style.display = 'none';
                                }
                                
                                // Mettre à jour la liste des notifications
                                const notificationList = document.getElementById('notification-list');
                                
                                if (data.data.length === 0) {
                                    notificationList.innerHTML = `
                                        <div class="py-8 text-center text-gray-500 text-sm">
                                            <p>Aucune notification non lue</p>
                                        </div>
                                    `;
                                    return;
                                }
                                
                                let html = '';
                                
                                data.data.forEach(notification => {
                                    // Déterminer l'icône et la couleur en fonction du type
                                    let iconHtml, bgColor, textColor;
                                    
                                    switch (notification.type) {
                                        case 'success':
                                            iconHtml = `<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                            </svg>`;
                                            bgColor = 'bg-success-100';
                                            textColor = 'text-success-600';
                                            break;
                                        case 'error':
                                            iconHtml = `<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                            </svg>`;
                                            bgColor = 'bg-danger-100';
                                            textColor = 'text-danger-600';
                                            break;
                                        case 'warning':
                                            iconHtml = `<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                            </svg>`;
                                            bgColor = 'bg-warning-100';
                                            textColor = 'text-warning-600';
                                            break;
                                        case 'info':
                                        default:
                                            iconHtml = `<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2h2a1 1 0 100-2H9z" clip-rule="evenodd" />
                                            </svg>`;
                                            bgColor = 'bg-info-100';
                                            textColor = 'text-info-600';
                                            break;
                                    }
                                    
                                    // Formater la date
                                    const date = new Date(notification.created_at);
                                    const now = new Date();
                                    const diff = now - date;
                                    
                                    let formattedDate;
                                    if (diff < 60000) { // moins d'une minute
                                        formattedDate = 'À l\'instant';
                                    } else if (diff < 3600000) { // moins d'une heure
                                        const minutes = Math.floor(diff / 60000);
                                        formattedDate = `Il y a ${minutes} minute${minutes > 1 ? 's' : ''}`;
                                    } else if (diff < 86400000) { // moins d'un jour
                                        const hours = Math.floor(diff / 3600000);
                                        formattedDate = `Il y a ${hours} heure${hours > 1 ? 's' : ''}`;
                                    } else if (diff < 604800000) { // moins d'une semaine
                                        const days = Math.floor(diff / 86400000);
                                        formattedDate = `Il y a ${days} jour${days > 1 ? 's' : ''}`;
                                    } else {
                                        formattedDate = date.toLocaleDateString('fr-FR');
                                    }
                                    
                                    // Créer l'élément HTML
                                    html += `
                                        <div class="flex px-4 py-3 hover:bg-gray-50 transition-colors duration-200 border-b border-gray-100 notification-item">
                                            <div class="flex-shrink-0">
                                                <div class="flex items-center justify-center h-10 w-10 rounded-md ${bgColor} ${textColor}">
                                                    ${iconHtml}
                                                </div>
                                            </div>
                                            <div class="ml-3 w-0 flex-1">
                                                <p class="text-sm font-medium text-gray-900">${notification.title}</p>
                                                <p class="text-sm text-gray-500">${notification.message}</p>
                                                <p class="mt-1 text-xs text-gray-400">${formattedDate}</p>
                                                <div class="mt-2">
                                                    <a href="${notification.action_url || '#'}" class="text-xs text-primary-600 hover:text-primary-500 mr-3">
                                                        <i class="bi bi-eye-fill mr-1"></i> Voir détails
                                                    </a>
                                                    <button class="text-xs text-success-600 hover:text-success-500 mark-read-btn" onclick="markAsRead(${notification.id}); event.preventDefault(); event.stopPropagation();">
                                                        <i class="bi bi-check-circle-fill mr-1"></i> Marquer comme lu
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    `;
                                });
                                
                                notificationList.innerHTML = html;
                            })
                            .catch(error => {
                                console.error('Erreur lors du chargement des notifications:', error);
                            });
                    }
                    
                    // Fonction pour marquer une notification comme lue
                    function markAsRead(id) {
                        console.log("Exécution de markAsRead avec ID:", id);
                        // Utiliser la version directe qui ne vérifie pas la méthode HTTP
                        fetch(`/tutoring/api/notifications/direct-mark-read.php?id=${id}`, {
                            method: 'GET'
                        })
                        .then(response => response.json())
                        .then(data => {
                            console.log("Réponse de l'API:", data);
                            if (data.success === false) {
                                console.error("Erreur:", data.message, data.debug);
                            } else {
                                // Recharger les notifications
                                loadUnreadNotifications();
                                
                                // Afficher une notification de confirmation
                                const event = new CustomEvent('notification', {
                                    detail: {
                                        message: 'Notification marquée comme lue',
                                        type: 'success',
                                        duration: 3000
                                    }
                                });
                                document.dispatchEvent(event);
                            }
                        })
                        .catch(error => {
                            console.error("Erreur lors de la requête:", error);
                        });
                    }
                    
                    // Fonction pour marquer toutes les notifications comme lues
                    function markAllAsRead() {
                        // Utiliser la version directe qui ne vérifie pas la méthode HTTP
                        fetch('/tutoring/api/notifications/direct-mark-all-read.php', {
                            method: 'GET'
                        })
                        .then(response => {
                            if (response.ok) {
                                // Recharger les notifications
                                loadUnreadNotifications();
                                
                                // Afficher une notification de confirmation
                                const event = new CustomEvent('notification', {
                                    detail: {
                                        message: 'Toutes les notifications ont été marquées comme lues',
                                        type: 'success',
                                        duration: 3000
                                    }
                                });
                                document.dispatchEvent(event);
                            }
                        })
                        .catch(error => {
                            console.error('Erreur lors du marquage de toutes les notifications:', error);
                        });
                    }
                    
                    // Ajouter le gestionnaire d'événements pour le bouton "Tout marquer comme lu"
                    document.addEventListener('DOMContentLoaded', function() {
                        document.getElementById('mark-all-read').addEventListener('click', function(e) {
                            e.preventDefault();
                            markAllAsRead();
                        });
                        
                        // Charger les notifications non lues au chargement de la page
                        loadUnreadNotifications();
                        
                        // Actualiser les notifications toutes les 60 secondes
                        setInterval(loadUnreadNotifications, 60000);
                    });
                    </script>
                    
                    <!-- Profile dropdown -->
                    <div class="relative" data-controller="dropdown">
                        <button type="button" class="flex items-center max-w-xs rounded-full text-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500" data-action="dropdown#toggle" data-dropdown-target="button" aria-expanded="false">
                            <span class="sr-only">Open user menu</span>
                            <div class="flex items-center justify-center <?php echo $avatarBg; ?> h-8 w-8 rounded-full">
                                <span class="text-white font-medium"><?php echo $userInitials; ?></span>
                            </div>
                        </button>
                        
                        <!-- Dropdown menu -->
                        <div class="hidden origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 opacity-0 translate-y-2 transition-all duration-300" data-dropdown-target="menu">
                            <div class="py-1" role="menu" aria-orientation="vertical">
                                <div class="px-4 py-2 border-b border-gray-100">
                                    <p class="text-sm font-medium text-gray-900"><?php echo h($userName); ?></p>
                                    <p class="text-sm text-gray-500"><?php echo h($userRole); ?></p>
                                </div>
                                
                                <a href="/tutoring/views/common/profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" role="menuitem">
                                    <div class="flex items-center">
                                        <svg class="mr-3 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                        </svg>
                                        <span>Profil</span>
                                    </div>
                                </a>
                                
                                <a href="/tutoring/views/admin/settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" role="menuitem">
                                    <div class="flex items-center">
                                        <svg class="mr-3 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" />
                                        </svg>
                                        <span>Paramètres</span>
                                    </div>
                                </a>
                                
                                <div class="border-t border-gray-100"></div>
                                
                                <a href="/tutoring/logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" role="menuitem">
                                    <div class="flex items-center">
                                        <svg class="mr-3 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clip-rule="evenodd" />
                                        </svg>
                                        <span>Déconnexion</span>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Flash Messages -->
        <?php $flashMessage = getFlashMessage(); ?>
        <?php if ($flashMessage): ?>
            <?php
            $alertClass = '';
            $icon = '';
            
            switch ($flashMessage['type']) {
                case 'success':
                    $alertClass = 'bg-success-100 text-success-800 border-success-300';
                    $icon = '<svg class="h-5 w-5 text-success-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>';
                    break;
                case 'error':
                    $alertClass = 'bg-danger-100 text-danger-800 border-danger-300';
                    $icon = '<svg class="h-5 w-5 text-danger-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>';
                    break;
                case 'warning':
                    $alertClass = 'bg-warning-100 text-warning-800 border-warning-300';
                    $icon = '<svg class="h-5 w-5 text-warning-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>';
                    break;
                case 'info':
                default:
                    $alertClass = 'bg-info-100 text-info-800 border-info-300';
                    $icon = '<svg class="h-5 w-5 text-info-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2h2a1 1 0 100-2H9z" clip-rule="evenodd" /></svg>';
            }
            ?>
            <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8 py-4">
                <div class="rounded-md p-4 border <?php echo $alertClass; ?> fade-in show" data-controller="alert">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <?php echo $icon; ?>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium"><?php echo h($flashMessage['message']); ?></p>
                        </div>
                        <div class="ml-auto pl-3">
                            <div class="-mx-1.5 -my-1.5">
                                <button type="button" class="inline-flex rounded-md p-1.5 focus:outline-none focus:ring-2 focus:ring-offset-2 <?php echo str_replace('bg-', 'focus:ring-', $alertClass); ?>" data-action="alert#close">
                                    <span class="sr-only">Dismiss</span>
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Main content -->
        <main class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">
                <?php include $content; ?>
            </div>
        </main>
    </div>
</div>