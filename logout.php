<?php
/**
 * Page de déconnexion
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/includes/init.php';

// Supprimer les cookies liés aux API tokens
setcookie('api_tokens_available', '', time() - 3600, '/', '', false, false);

// Détruire la session
session_destroy();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Déconnexion | TutorMatch</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎓</text></svg>">
    
    <style>
        /* Variables CSS */
        :root {
            --primary-color: #007bff;
            --primary-hover: #0056b3;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-600: #6c757d;
            --gray-900: #212529;
        }

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
            color: var(--gray-900);
        }

        /* Container principal */
        .logout-container {
            width: 100%;
            max-width: 500px;
            padding: 2rem;
            animation: fadeIn 0.6s ease-out;
        }

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

        /* Carte de déconnexion */
        .logout-card {
            background: #ffffff;
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        /* Logo animé */
        .success-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            font-size: 2.5rem;
            color: white;
            animation: checkmark 0.8s ease-out;
            position: relative;
        }

        @keyframes checkmark {
            0% {
                transform: scale(0) rotate(-45deg);
                opacity: 0;
            }
            50% {
                transform: scale(1.2) rotate(-45deg);
            }
            100% {
                transform: scale(1) rotate(0deg);
                opacity: 1;
            }
        }

        /* Effet de pulsation */
        .success-icon::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            opacity: 0.3;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 0.3;
            }
            50% {
                transform: scale(1.3);
                opacity: 0;
            }
            100% {
                transform: scale(1);
                opacity: 0.3;
            }
        }

        /* Titres et textes */
        .logout-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 1rem;
        }

        .logout-subtitle {
            font-size: 1.125rem;
            color: var(--gray-600);
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        /* Message de redirection */
        .redirect-message {
            font-size: 0.875rem;
            color: var(--gray-600);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        /* Spinner de chargement */
        .spinner {
            border: 2px solid var(--gray-200);
            border-top: 2px solid var(--primary-color);
            border-radius: 50%;
            width: 16px;
            height: 16px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Boutons */
        .btn-group {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-primary-custom {
            padding: 0.75rem 2rem;
            font-size: 1rem;
            font-weight: 600;
            color: #ffffff;
            background-color: var(--primary-color);
            border: none;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .btn-primary-custom:hover {
            color: #ffffff;
            background-color: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.3);
        }

        .btn-secondary-custom {
            padding: 0.75rem 2rem;
            font-size: 1rem;
            font-weight: 600;
            color: var(--gray-600);
            background-color: transparent;
            border: 2px solid var(--gray-200);
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .btn-secondary-custom:hover {
            color: var(--gray-900);
            border-color: var(--gray-600);
            background-color: var(--gray-100);
        }

        /* Footer */
        .logout-footer {
            margin-top: 3rem;
            text-align: center;
            color: var(--gray-600);
            font-size: 0.875rem;
        }

        /* Progress bar */
        .progress-container {
            margin-top: 2rem;
            margin-bottom: 1rem;
        }

        .progress {
            height: 4px;
            background-color: var(--gray-200);
            border-radius: 2px;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            background-color: var(--primary-color);
            border-radius: 2px;
            animation: progress 1.5s ease-out;
            width: 0%;
        }

        @keyframes progress {
            0% { width: 0%; }
            100% { width: 100%; }
        }

        /* Responsive */
        @media (max-width: 576px) {
            .logout-container {
                padding: 1rem;
            }

            .logout-card {
                padding: 2rem;
            }

            .logout-title {
                font-size: 1.5rem;
            }

            .btn-group {
                flex-direction: column;
                width: 100%;
            }

            .btn-primary-custom,
            .btn-secondary-custom {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <div class="logout-card">
            <!-- Icône de succès animée -->
            <div class="success-icon">
                <i class="fas fa-check"></i>
            </div>
            
            <!-- Message principal -->
            <h1 class="logout-title">Déconnexion réussie</h1>
            <p class="logout-subtitle">
                Vous avez été déconnecté de votre compte en toute sécurité.<br>
                Merci d'avoir utilisé TutorMatch.
            </p>
            
            <!-- Barre de progression -->
            <div class="progress-container">
                <div class="progress">
                    <div class="progress-bar"></div>
                </div>
            </div>
            
            <!-- Message de redirection -->
            <div class="redirect-message">
                <div class="spinner"></div>
                <span>Redirection vers la page de connexion...</span>
            </div>
            
            <!-- Boutons d'action -->
            <div class="btn-group">
                <a href="/tutoring/login.php" class="btn-primary-custom">
                    <i class="fas fa-sign-in-alt"></i>
                    Se reconnecter
                </a>
                <a href="/tutoring/index.php" class="btn-secondary-custom">
                    <i class="fas fa-home"></i>
                    Page d'accueil
                </a>
            </div>
            
            <!-- Footer -->
            <div class="logout-footer">
                <p>© <?php echo date('Y'); ?> TutorMatch - Tous droits réservés</p>
            </div>
        </div>
    </div>

    <!-- Scripts Bootstrap et jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Script pour supprimer les tokens JWT et rediriger -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Supprimer les tokens du localStorage
        localStorage.removeItem('tutoring_auth_token');
        localStorage.removeItem('tutoring_refresh_token');
        localStorage.removeItem('token_expiry');
        localStorage.removeItem('tutoring_user');
        
        // Effacer toutes les données de session stockées
        sessionStorage.clear();
        
        // Supprimer les cookies (si applicable)
        document.cookie.split(";").forEach(function(c) { 
            document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/");
        });
        
        // Animer la barre de progression
        setTimeout(function() {
            document.querySelector('.progress-bar').style.width = '100%';
        }, 100);
        
        // Rediriger vers la page de connexion après un délai
        setTimeout(function() {
            window.location.href = '/tutoring/login.php';
        }, 1500);
        
        // Alternative : permettre de cliquer pour accélérer la redirection
        document.addEventListener('click', function(e) {
            if (e.target.tagName !== 'A') {
                window.location.href = '/tutoring/login.php';
            }
        });
    });
    </script>
</body>
</html>