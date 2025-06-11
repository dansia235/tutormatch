<?php
/**
 * Page de connexion
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/includes/init.php';

// Si l'utilisateur est déjà connecté, le rediriger
if (isLoggedIn()) {
    redirect('/tutoring/index.php');
}

$error = '';
$fieldErrors = [];

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier le jeton CSRF
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = 'Session expirée. Veuillez réessayer.';
    } else {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        
        // Validation des champs
        if (empty($username)) {
            $fieldErrors['username'] = 'Ce champ est requis';
        }
        if (empty($password)) {
            $fieldErrors['password'] = 'Ce champ est requis';
        }
        
        if (empty($fieldErrors)) {
            // Créer une instance du modèle User
            $userModel = new User($db);
            
            // Tenter l'authentification
            $user = $userModel->authenticate($username, $password);
            
            if ($user) {
                // Connexion réussie
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                
                // Générer les tokens JWT pour l'API (seulement si la classe existe)
                if (class_exists('JwtUtils')) {
                    $accessToken = JwtUtils::generateToken($user);
                    $refreshToken = JwtUtils::generateToken($user, true);
                    
                    // Stocker les tokens dans un cookie sécurisé (pour la démo)
                    setcookie('api_tokens_available', '1', 0, '/', '', false, false);
                    
                    // Ces données seront récupérées par le JavaScript
                    $_SESSION['api_tokens'] = [
                        'access_token' => $accessToken,
                        'refresh_token' => $refreshToken,
                        'expires_in' => 3600
                    ];
                }
                
                // Rediriger vers la page demandée ou la page d'accueil
                if (isset($_SESSION['redirect_after_login'])) {
                    $redirect = $_SESSION['redirect_after_login'];
                    unset($_SESSION['redirect_after_login']);
                    redirect($redirect);
                } else {
                    redirect('/tutoring/index.php');
                }
            } else {
                // Échec de l'authentification
                $error = 'Nom d\'utilisateur ou mot de passe incorrect.';
            }
        }
    }
}

// Générer un nouveau jeton CSRF
$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion | TutorMatch</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎓</text></svg>">
    
    <style>
        /* Reset et styles de base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #e6e9f0 0%, #d5dae1 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #212529;
            position: relative;
        }

        /* Container principal centré */
        .main-container {
            width: 100%;
            max-width: 450px;
            padding: 2rem;
            margin: 0 auto;
        }

        /* Section logo et titre groupés */
        .branding-section {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .logo-title-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
        }

        .logo {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3.5rem;
            flex-shrink: 0;
            line-height: 1;
        }

        .brand-title {
            font-size: 2.75rem;
            font-weight: 700;
            color: #212529;
            line-height: 1;
        }

        .brand-subtitle {
            font-size: 0.95rem;
            color: #6c757d;
            line-height: 1.4;
            text-align: center;
            font-weight: 400;
        }

        /* Carte de connexion */
        .login-card {
            background: #ffffff;
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            width: 100%;
            animation: fadeIn 0.6s ease-out;
        }

        .login-title {
            font-size: 1.75rem;
            font-weight: 600;
            text-align: center;
            margin-bottom: 1.5rem;
            color: #212529;
        }

        /* Groupes de formulaire */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #495057;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .input-wrapper {
            position: relative;
        }

        .form-input {
            width: 100%;
            padding: 0.875rem 1rem 0.875rem 2.75rem;
            font-size: 1rem;
            color: #495057;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            transition: all 0.3s ease;
            outline: none;
        }

        .form-input:focus {
            background-color: #ffffff;
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .form-input::placeholder {
            color: #adb5bd;
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6b5b95;
            font-size: 1.125rem;
        }

        /* Icônes personnalisées avec des emojis */
        .icon-user::before {
            content: "👤";
        }

        .icon-lock::before {
            content: "🔒";
        }

        /* Messages d'erreur */
        .field-error {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        .general-error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        /* Bouton de connexion */
        .login-button {
            width: 100%;
            padding: 0.875rem;
            font-size: 1rem;
            font-weight: 600;
            color: #ffffff;
            background-color: #007bff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 1.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .login-button:hover {
            background-color: #0056b3;
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.3);
        }

        .login-button:active {
            transform: translateY(0);
        }

        .login-button::before {
            content: "➜";
            font-size: 1.25rem;
        }

        /* Lien d'aide */
        .help-link {
            display: block;
            text-align: center;
            margin-top: 1.5rem;
            color: #007bff;
            text-decoration: none;
            font-size: 0.875rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .help-link:hover {
            color: #0056b3;
            text-decoration: underline;
        }

        /* Footer / Copyright */
        .copyright {
            color: #6c757d;
            font-size: 0.875rem;
            text-align: center;
            margin-top: 2rem;
        }

        /* Modal d'aide */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .modal.show {
            opacity: 1;
            visibility: visible;
        }

        .modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            position: relative;
            background: #ffffff;
            border-radius: 20px;
            max-width: 500px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            transform: scale(0.9);
            transition: transform 0.3s ease;
        }

        .modal.show .modal-content {
            transform: scale(1);
        }

        .modal-header {
            padding: 2rem;
            text-align: center;
            border-bottom: 1px solid #dee2e6;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #212529;
            margin: 0;
        }

        .modal-body {
            padding: 2rem;
        }

        .account-card {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .account-type {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
        }

        .credentials {
            font-family: 'Courier New', monospace;
            font-size: 0.875rem;
            color: #6c757d;
        }

        .modal-footer {
            padding: 1.5rem 2rem;
            border-top: 1px solid #dee2e6;
            text-align: center;
        }

        .close-button {
            padding: 0.5rem 2rem;
            font-size: 1rem;
            font-weight: 500;
            color: #ffffff;
            background-color: #6c757d;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .close-button:hover {
            background-color: #5a6268;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .main-container {
                padding: 1rem;
            }

            .login-card {
                padding: 1.5rem;
            }

            .brand-title {
                font-size: 2.25rem;
            }

            .logo {
                width: 50px;
                height: 50px;
                font-size: 3rem;
            }
        }

        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Loader */
        .loader {
            display: none;
            text-align: center;
            padding: 2rem;
        }

        .loader.show {
            display: block;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #007bff;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Logo et titre groupés -->
        <div class="branding-section">
            <div class="logo-title-row">
                <div class="logo">🎓</div>
                <h1 class="brand-title">TutorMatch</h1>
            </div>
            <p class="brand-subtitle">Système de gestion des stages et du tutorat</p>
        </div>
        
        <!-- Carte de connexion -->
        <div class="login-card">
            <h2 class="login-title">Connexion</h2>
            
            <!-- Message d'erreur général -->
            <?php if (!empty($error)): ?>
            <div class="general-error">
                <?php echo h($error); ?>
            </div>
            <?php endif; ?>
            
            <!-- Loader -->
            <div class="loader" id="loader">
                <div class="spinner"></div>
                <p>Connexion en cours...</p>
            </div>
            
            <!-- Formulaire de connexion -->
            <form id="login-form" action="<?php echo h($_SERVER['PHP_SELF']); ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo h($csrfToken); ?>">
                
                <div class="form-group">
                    <label for="username" class="form-label">Nom d'utilisateur</label>
                    <div class="input-wrapper">
                        <span class="input-icon icon-user"></span>
                        <input 
                            id="username" 
                            name="username" 
                            type="text" 
                            class="form-input" 
                            placeholder="Nom d'utilisateur"
                            value="<?php echo isset($_POST['username']) ? h($_POST['username']) : ''; ?>"
                            autocomplete="username"
                        >
                    </div>
                    <?php if (isset($fieldErrors['username'])): ?>
                    <div class="field-error"><?php echo h($fieldErrors['username']); ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Mot de passe</label>
                    <div class="input-wrapper">
                        <span class="input-icon icon-lock"></span>
                        <input 
                            id="password" 
                            name="password" 
                            type="password" 
                            class="form-input" 
                            placeholder="••••••"
                            autocomplete="current-password"
                        >
                    </div>
                    <?php if (isset($fieldErrors['password'])): ?>
                    <div class="field-error"><?php echo h($fieldErrors['password']); ?></div>
                    <?php endif; ?>
                </div>
                
                <button type="submit" class="login-button">
                    Se connecter
                </button>
            </form>
            
            <a href="#" class="help-link" id="help-link">
                Besoin d'aide pour vous connecter?
            </a>
        </div>
        
        <!-- Copyright -->
        <div class="copyright">
            © <?php echo date('Y'); ?> TutorMatch - Tous droits réservés
        </div>
    </div>
    
    <!-- Modal d'aide -->
    <div class="modal" id="help-modal">
        <div class="modal-overlay"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">🔑 Aide à la connexion</h3>
            </div>
            <div class="modal-body">
                <p style="margin-bottom: 1.5rem;">Voici les comptes de démonstration disponibles :</p>
                
                <div class="account-card">
                    <div class="account-type">👨‍💼 Administrateur</div>
                    <div class="credentials">
                        Nom d'utilisateur : admin<br>
                        Mot de passe : admin123
                    </div>
                </div>
                
                <div class="account-card">
                    <div class="account-type">👥 Coordinateur</div>
                    <div class="credentials">
                        Nom d'utilisateur : test<br>
                        Mot de passe : test123
                    </div>
                </div>
                
                <div class="account-card">
                    <div class="account-type">👨‍🏫 Enseignant</div>
                    <div class="credentials">
                        Nom d'utilisateur : marie<br>
                        Mot de passe : password123
                    </div>
                </div>
                
                <div class="account-card">
                    <div class="account-type">👨‍🎓 Étudiant</div>
                    <div class="credentials">
                        Nom d'utilisateur : lucas<br>
                        Mot de passe : password123
                    </div>
                </div>
                
                <p style="margin-top: 1.5rem; text-align: center; color: #6c757d;">
                    Si vous rencontrez des problèmes de connexion, veuillez contacter l'administrateur système.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="close-button" id="close-modal">
                    Fermer
                </button>
            </div>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Références aux éléments
        const form = document.getElementById('login-form');
        const helpLink = document.getElementById('help-link');
        const helpModal = document.getElementById('help-modal');
        const closeModal = document.getElementById('close-modal');
        const modalOverlay = helpModal.querySelector('.modal-overlay');
        const loader = document.getElementById('loader');
        const usernameInput = document.getElementById('username');
        const passwordInput = document.getElementById('password');
        
        // Gestion du modal
        helpLink.addEventListener('click', function(e) {
            e.preventDefault();
            helpModal.classList.add('show');
        });
        
        function closeHelpModal() {
            helpModal.classList.remove('show');
        }
        
        closeModal.addEventListener('click', closeHelpModal);
        modalOverlay.addEventListener('click', closeHelpModal);
        
        // Fermer avec Échap
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && helpModal.classList.contains('show')) {
                closeHelpModal();
            }
        });
        
        // Gestion du formulaire
        form.addEventListener('submit', function(e) {
            // Validation simple côté client
            let hasError = false;
            
            if (!usernameInput.value.trim()) {
                e.preventDefault();
                hasError = true;
            }
            
            if (!passwordInput.value) {
                e.preventDefault();
                hasError = true;
            }
            
            // Si pas d'erreur, afficher le loader
            if (!hasError) {
                loader.classList.add('show');
            }
        });
        
        // Auto-focus sur le premier champ vide
        if (!usernameInput.value) {
            usernameInput.focus();
        } else if (!passwordInput.value) {
            passwordInput.focus();
        }
        
        // Gestion des tokens JWT si présents
        <?php if (isset($_SESSION['api_tokens'])): ?>
        localStorage.setItem('tutoring_auth_token', <?php echo json_encode($_SESSION['api_tokens']['access_token']); ?>);
        localStorage.setItem('tutoring_refresh_token', <?php echo json_encode($_SESSION['api_tokens']['refresh_token']); ?>);
        localStorage.setItem('token_expiry', <?php echo time() * 1000 + ($_SESSION['api_tokens']['expires_in'] * 1000); ?>);
        <?php 
        unset($_SESSION['api_tokens']);
        endif; 
        ?>
    });
    </script>
</body>
</html>