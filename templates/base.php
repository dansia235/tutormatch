<?php
/**
 * Base template for the TutorMatch application
 * This template should be included in all pages.
 */

// Check if the title is set, otherwise use a default
$pageTitle = $pageTitle ?? 'TutorMatch';

// Check if the current page is set
$currentPage = $currentPage ?? '';

// Check if user is logged in and redirect if not
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
}

if (!function_exists('hasRole')) {
    function hasRole($roles) {
        if (!isset($_SESSION['user_role'])) {
            return false;
        }
        if (is_array($roles)) {
            return in_array($_SESSION['user_role'], $roles);
        }
        return $_SESSION['user_role'] === $roles;
    }
}

if (!function_exists('requireRole')) {
    function requireRole($roles) {
        if (!isLoggedIn()) {
            redirect('/tutoring/login.php');
        }
        if (!hasRole($roles)) {
            redirect('/tutoring/access-denied.php');
        }
    }
}

if (!function_exists('h')) {
    function h($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('redirect')) {
    function redirect($url) {
        header('Location: ' . $url);
        exit;
    }
}

if (!function_exists('getFlashMessage')) {
    function getFlashMessage() {
        if (isset($_SESSION['flash_message'])) {
            $message = $_SESSION['flash_message'];
            unset($_SESSION['flash_message']);
            return $message;
        }
        return null;
    }
}

if (!isLoggedIn() && !in_array(basename($_SERVER['PHP_SELF']), ['login.php', 'register.php', 'reset-password.php'])) {
    redirect('/tutoring/login.php');
}

// Get user role for choosing the right layout
$userRole = $_SESSION['user_role'] ?? 'guest';

// Determine which layout to include based on user role
switch ($userRole) {
    case 'admin':
    case 'coordinator':
        $layout = 'admin';
        break;
    case 'teacher':
        $layout = 'tutor';
        break;
    case 'student':
        $layout = 'student';
        break;
    default:
        $layout = 'guest';
}

// Extra CSS and JS can be included by setting these variables before including this template
$extraStyles = $extraStyles ?? '';
$extraScripts = $extraScripts ?? '';
?>
<!DOCTYPE html>
<html lang="fr" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?php echo h($pageTitle); ?> | TutorMatch</title>
    
    <!-- Favicon -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎓</text></svg>">
    
    <!-- Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
    
    <!-- CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/tutoring/public/build/app.css">
    
    <!-- Role-specific CSS -->
    <?php if ($layout !== 'guest'): ?>
    <link rel="stylesheet" href="/tutoring/public/build/<?php echo $layout; ?>.css">
    <?php endif; ?>
    
    <!-- Extra styles -->
    <?php echo $extraStyles; ?>
</head>
<body class="h-full font-sans antialiased text-gray-900 bg-gray-50">
    <?php
    // Include the appropriate layout based on user role
    if ($layout !== 'guest') {
        include __DIR__ . "/layouts/$layout.php";
    } else {
        // For guests, just include content without layout
        // The content will be included where this file is included
    }
    ?>

    <!-- Core JS -->
    <script src="/tutoring/public/build/runtime.js"></script>
    <script src="/tutoring/public/build/app.js"></script>
    
    <!-- Role-specific JS -->
    <?php if ($layout !== 'guest'): ?>
    <script src="/tutoring/public/build/<?php echo $layout; ?>.js"></script>
    <?php endif; ?>
    
    <!-- Extra scripts -->
    <?php echo $extraScripts; ?>
</body>
</html>