<?php
/**
 * Page d'accès refusé
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/includes/init.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    redirect('/tutoring/login.php');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accès Refusé | TutorMatch</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --accent-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
            --success-color: #2ecc71;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: var(--dark-color);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-image: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            margin: 0;
            padding: 0;
        }
        
        .error-container {
            width: 100%;
            max-width: 600px;
            padding: 20px;
            text-align: center;
        }
        
        .error-icon {
            font-size: 100px;
            color: var(--accent-color);
            margin-bottom: 20px;
        }
        
        .error-title {
            font-size: 36px;
            font-weight: 700;
            color: var(--accent-color);
            margin-bottom: 10px;
        }
        
        .error-message {
            font-size: 18px;
            color: var(--dark-color);
            margin-bottom: 30px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 12px 25px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }
        
        .footer {
            margin-top: 50px;
            color: #7f8c8d;
            font-size: 0.9rem;
        }
    </style>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎓</text></svg>">
</head>
<body>
    <div class="error-container">
        <div class="error-icon">
            <i class="bi bi-shield-lock-fill"></i>
        </div>
        <h1 class="error-title">Accès Refusé</h1>
        <p class="error-message">
            Vous n'avez pas les permissions nécessaires pour accéder à cette page.<br>
            Veuillez contacter l'administrateur si vous pensez qu'il s'agit d'une erreur.
        </p>
        <div class="d-flex justify-content-center gap-3">
            <a href="index.php" class="btn btn-primary">
                <i class="bi bi-house-door me-2"></i>Retour à l'accueil
            </a>
            <a href="logout.php" class="btn btn-outline-secondary">
                <i class="bi bi-box-arrow-right me-2"></i>Déconnexion
            </a>
        </div>
        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> TutorMatch - Système de Gestion des Stages</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>