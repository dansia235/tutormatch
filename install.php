<?php
/**
 * Page d'installation du système de tutorat
 * Création des tables et initialisation des données
 */

set_time_limit(600); // 10 minutes
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Fonction pour afficher les messages de progression
function logProgress($message, $type = 'info') {
    $icons = [
        'info' => '📋',
        'success' => '✅',
        'warning' => '⚠️',
        'error' => '❌',
        'database' => '🗄️',
        'table' => '📊',
        'data' => '📝'
    ];
    
    $colors = [
        'info' => '#17a2b8',
        'success' => '#28a745',
        'warning' => '#ffc107',
        'error' => '#dc3545',
        'database' => '#6f42c1',
        'table' => '#fd7e14',
        'data' => '#20c997'
    ];
    
    $icon = $icons[$type] ?? '📋';
    $color = $colors[$type] ?? '#17a2b8';
    
    echo "<div class='log-entry' style='color: $color; margin: 5px 0; padding: 8px; border-left: 4px solid $color; background: rgba(" . hexdec(substr($color, 1, 2)) . "," . hexdec(substr($color, 3, 2)) . "," . hexdec(substr($color, 5, 2)) . ", 0.1);'>";
    echo "<strong>" . date('H:i:s') . "</strong> $icon $message";
    echo "</div>";
    flush();
    ob_flush();
}

// Variables de configuration
$host = 'localhost';
$dbname = 'tutoring_system';
$username = 'root';
$password = '';

$step = $_GET['step'] ?? 'welcome';
$action = $_POST['action'] ?? '';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation - Système de Tutorat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .install-container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .install-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .install-header {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .install-header h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: 300;
        }
        
        .install-header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
        }
        
        .install-body {
            padding: 2rem;
        }
        
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            position: relative;
        }
        
        .step-indicator::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 10%;
            right: 10%;
            height: 2px;
            background: #e9ecef;
            z-index: 1;
        }
        
        .step {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            position: relative;
            z-index: 2;
        }
        
        .step.active {
            background: #3498db;
            border-color: #3498db;
            color: white;
        }
        
        .step.completed {
            background: #28a745;
            border-color: #28a745;
            color: white;
        }
        
        .log-container {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1rem;
            max-height: 400px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
        }
        
        .btn-gradient {
            background: linear-gradient(135deg, #3498db, #2980b9);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-gradient:hover {
            background: linear-gradient(135deg, #2980b9, #1f5582);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            color: white;
        }
        
        .btn-success-gradient {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            color: white;
        }
        
        .btn-success-gradient:hover {
            background: linear-gradient(135deg, #20c997, #17a2b8);
            color: white;
        }
        
        .alert-custom {
            border: none;
            border-radius: 10px;
            padding: 1.5rem;
        }
        
        .feature-card {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .feature-card:hover {
            border-color: #3498db;
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.1);
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="install-card">
            <div class="install-header">
                <h1><i class="bi bi-gear-fill me-3"></i>Installation</h1>
                <p>Système de Gestion de Tutorat</p>
            </div>
            
            <div class="install-body">
                <?php if ($step === 'welcome'): ?>
                <!-- Étape 1: Bienvenue -->
                <div class="step-indicator">
                    <div class="step active">1</div>
                    <div class="step">2</div>
                    <div class="step">3</div>
                    <div class="step">4</div>
                </div>
                
                <div class="text-center mb-4">
                    <h2><i class="bi bi-rocket-takeoff text-primary me-2"></i>Bienvenue</h2>
                    <p class="text-muted">Préparez-vous à installer le système de gestion de tutorat</p>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="feature-card">
                            <h5><i class="bi bi-people-fill text-primary me-2"></i>Gestion des utilisateurs</h5>
                            <p class="text-muted mb-0">Administration des étudiants, tuteurs et coordinateurs</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="feature-card">
                            <h5><i class="bi bi-clipboard-check text-success me-2"></i>Évaluations</h5>
                            <p class="text-muted mb-0">Système complet d'évaluation des stages</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="feature-card">
                            <h5><i class="bi bi-building text-warning me-2"></i>Stages et entreprises</h5>
                            <p class="text-muted mb-0">Gestion des stages et partenariats entreprises</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="feature-card">
                            <h5><i class="bi bi-graph-up text-info me-2"></i>Tableaux de bord</h5>
                            <p class="text-muted mb-0">Statistiques et suivi en temps réel</p>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-info alert-custom mt-4">
                    <h5><i class="bi bi-info-circle me-2"></i>Prérequis</h5>
                    <ul class="mb-0">
                        <li>PHP 7.4 ou supérieur</li>
                        <li>MySQL 5.7 ou supérieur</li>
                        <li>Extension PDO activée</li>
                        <li>Serveur web (Apache/Nginx)</li>
                    </ul>
                </div>
                
                <div class="text-center mt-4">
                    <a href="?step=config" class="btn btn-gradient btn-lg">
                        <i class="bi bi-arrow-right me-2"></i>Commencer l'installation
                    </a>
                </div>
                
                <?php elseif ($step === 'config'): ?>
                <!-- Étape 2: Configuration -->
                <div class="step-indicator">
                    <div class="step completed">1</div>
                    <div class="step active">2</div>
                    <div class="step">3</div>
                    <div class="step">4</div>
                </div>
                
                <div class="text-center mb-4">
                    <h2><i class="bi bi-database-gear text-primary me-2"></i>Configuration</h2>
                    <p class="text-muted">Vérification et configuration de la base de données</p>
                </div>
                
                <form method="post" action="?step=install">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="host" class="form-label">Serveur de base de données</label>
                                <input type="text" class="form-control" id="host" name="host" value="<?php echo htmlspecialchars($host); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="dbname" class="form-label">Nom de la base de données</label>
                                <input type="text" class="form-control" id="dbname" name="dbname" value="<?php echo htmlspecialchars($dbname); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="username" class="form-label">Nom d'utilisateur</label>
                                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="password" class="form-label">Mot de passe</label>
                                <input type="password" class="form-control" id="password" name="password" value="<?php echo htmlspecialchars($password); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-warning alert-custom">
                        <h5><i class="bi bi-exclamation-triangle me-2"></i>Attention</h5>
                        <p class="mb-0">L'installation va créer toutes les tables nécessaires. Si la base de données existe déjà, certaines données pourraient être écrasées.</p>
                    </div>
                    
                    <div class="text-center">
                        <a href="?step=welcome" class="btn btn-outline-secondary me-3">
                            <i class="bi bi-arrow-left me-2"></i>Retour
                        </a>
                        <button type="submit" name="action" value="test_connection" class="btn btn-outline-primary me-3">
                            <i class="bi bi-wifi me-2"></i>Tester la connexion
                        </button>
                        <button type="submit" name="action" value="install" class="btn btn-gradient">
                            <i class="bi bi-download me-2"></i>Installer les tables
                        </button>
                    </div>
                </form>
                
                <?php elseif ($step === 'install'): ?>
                <!-- Étape 3: Installation -->
                <div class="step-indicator">
                    <div class="step completed">1</div>
                    <div class="step completed">2</div>
                    <div class="step active">3</div>
                    <div class="step">4</div>
                </div>
                
                <div class="text-center mb-4">
                    <h2><i class="bi bi-download text-primary me-2"></i>Installation en cours</h2>
                    <p class="text-muted">Création des tables et configuration initiale</p>
                </div>
                
                <div class="log-container mb-4" id="logContainer">
                    <?php
                    if ($action === 'test_connection' || $action === 'install') {
                        $host = $_POST['host'] ?? 'localhost';
                        $dbname = $_POST['dbname'] ?? 'tutoring_system';
                        $username = $_POST['username'] ?? 'root';
                        $password = $_POST['password'] ?? '';
                        
                        try {
                            logProgress("Tentative de connexion à la base de données...", 'database');
                            $pdo = new PDO("mysql:host=$host;charset=utf8", $username, $password);
                            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                            logProgress("Connexion au serveur MySQL réussie", 'success');
                            
                            // Créer la base de données si elle n'existe pas
                            logProgress("Vérification/création de la base de données '$dbname'", 'database');
                            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8 COLLATE utf8_general_ci");
                            logProgress("Base de données '$dbname' prête", 'success');
                            
                            // Se connecter à la base de données
                            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
                            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                            
                            if ($action === 'test_connection') {
                                logProgress("Test de connexion réussi ✨", 'success');
                            } else {
                                // Installation des tables
                                logProgress("Début de l'installation des tables...", 'table');
                                
                                // Script SQL pour créer les tables
                                $sql = "
                                -- Table des utilisateurs
                                CREATE TABLE IF NOT EXISTS `users` (
                                    `id` int(11) NOT NULL AUTO_INCREMENT,
                                    `first_name` varchar(100) NOT NULL,
                                    `last_name` varchar(100) NOT NULL,
                                    `email` varchar(150) UNIQUE NOT NULL,
                                    `username` varchar(50) UNIQUE,
                                    `password` varchar(255) NOT NULL,
                                    `role` enum('admin','coordinator','teacher','student') NOT NULL DEFAULT 'student',
                                    `department` varchar(100),
                                    `phone` varchar(20),
                                    `address` text,
                                    `is_active` tinyint(1) DEFAULT 1,
                                    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                                    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                                    PRIMARY KEY (`id`)
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
                                
                                -- Table des enseignants/tuteurs
                                CREATE TABLE IF NOT EXISTS `teachers` (
                                    `id` int(11) NOT NULL AUTO_INCREMENT,
                                    `user_id` int(11) NOT NULL,
                                    `specialization` varchar(200),
                                    `experience_years` int(11),
                                    `max_students` int(11) DEFAULT 10,
                                    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                                    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                                    PRIMARY KEY (`id`),
                                    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
                                
                                -- Table des étudiants
                                CREATE TABLE IF NOT EXISTS `students` (
                                    `id` int(11) NOT NULL AUTO_INCREMENT,
                                    `user_id` int(11) NOT NULL,
                                    `student_number` varchar(50) UNIQUE,
                                    `program` varchar(200),
                                    `level` varchar(50),
                                    `enrollment_year` year,
                                    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                                    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                                    PRIMARY KEY (`id`),
                                    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
                                
                                -- Table des entreprises
                                CREATE TABLE IF NOT EXISTS `companies` (
                                    `id` int(11) NOT NULL AUTO_INCREMENT,
                                    `name` varchar(200) NOT NULL,
                                    `description` text,
                                    `sector` varchar(100),
                                    `size` enum('startup','small','medium','large') DEFAULT 'medium',
                                    `address` text,
                                    `city` varchar(100),
                                    `postal_code` varchar(20),
                                    `country` varchar(100) DEFAULT 'France',
                                    `phone` varchar(20),
                                    `email` varchar(150),
                                    `website` varchar(255),
                                    `contact_person` varchar(200),
                                    `contact_position` varchar(100),
                                    `contact_phone` varchar(20),
                                    `contact_email` varchar(150),
                                    `is_active` tinyint(1) DEFAULT 1,
                                    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                                    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                                    PRIMARY KEY (`id`)
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
                                
                                -- Table des stages
                                CREATE TABLE IF NOT EXISTS `internships` (
                                    `id` int(11) NOT NULL AUTO_INCREMENT,
                                    `title` varchar(200) NOT NULL,
                                    `description` text,
                                    `requirements` text,
                                    `company_id` int(11) NOT NULL,
                                    `location` varchar(200),
                                    `start_date` date,
                                    `end_date` date,
                                    `duration_weeks` int(11),
                                    `compensation` decimal(10,2),
                                    `domain` varchar(100),
                                    `skills_required` text,
                                    `status` enum('available','assigned','completed','cancelled') DEFAULT 'available',
                                    `max_students` int(11) DEFAULT 1,
                                    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                                    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                                    PRIMARY KEY (`id`),
                                    FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
                                
                                -- Table des affectations
                                CREATE TABLE IF NOT EXISTS `assignments` (
                                    `id` int(11) NOT NULL AUTO_INCREMENT,
                                    `student_id` int(11) NOT NULL,
                                    `teacher_id` int(11) NOT NULL,
                                    `internship_id` int(11),
                                    `status` enum('pending','confirmed','active','completed','cancelled') DEFAULT 'pending',
                                    `assigned_date` timestamp DEFAULT CURRENT_TIMESTAMP,
                                    `start_date` date,
                                    `end_date` date,
                                    `notes` text,
                                    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                                    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                                    PRIMARY KEY (`id`),
                                    FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
                                    FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
                                    FOREIGN KEY (`internship_id`) REFERENCES `internships` (`id`) ON DELETE SET NULL
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
                                
                                -- Table des évaluations
                                CREATE TABLE IF NOT EXISTS `evaluations` (
                                    `id` int(11) NOT NULL AUTO_INCREMENT,
                                    `assignment_id` int(11) NOT NULL,
                                    `evaluator_id` int(11) NOT NULL,
                                    `evaluatee_id` int(11) NOT NULL,
                                    `type` enum('mid_term','final','student','supervisor','teacher','company') NOT NULL,
                                    `score` decimal(5,2),
                                    `technical_avg` decimal(5,2),
                                    `professional_avg` decimal(5,2),
                                    `criteria_scores` json,
                                    `comments` text,
                                    `feedback` text,
                                    `strengths` text,
                                    `areas_for_improvement` text,
                                    `areas_to_improve` text,
                                    `next_steps` text,
                                    `status` enum('draft','submitted','completed','approved') DEFAULT 'draft',
                                    `submission_date` datetime,
                                    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                                    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                                    PRIMARY KEY (`id`),
                                    FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
                                
                                -- Table des préférences étudiants
                                CREATE TABLE IF NOT EXISTS `student_preferences` (
                                    `id` int(11) NOT NULL AUTO_INCREMENT,
                                    `student_id` int(11) NOT NULL,
                                    `preference_type` varchar(50) NOT NULL,
                                    `preference_value` text,
                                    `priority` int(11) DEFAULT 1,
                                    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                                    PRIMARY KEY (`id`),
                                    FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
                                
                                -- Table des préférences de stages par étudiant
                                CREATE TABLE IF NOT EXISTS `student_internship_preferences` (
                                    `id` int(11) NOT NULL AUTO_INCREMENT,
                                    `student_id` int(11) NOT NULL,
                                    `internship_id` int(11) NOT NULL,
                                    `preference_rank` int(11) NOT NULL,
                                    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                                    PRIMARY KEY (`id`),
                                    FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
                                    FOREIGN KEY (`internship_id`) REFERENCES `internships` (`id`) ON DELETE CASCADE
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
                                
                                -- Table des préférences enseignants
                                CREATE TABLE IF NOT EXISTS `teacher_preferences` (
                                    `id` int(11) NOT NULL AUTO_INCREMENT,
                                    `teacher_id` int(11) NOT NULL,
                                    `preference_type` varchar(50) NOT NULL,
                                    `preference_value` text,
                                    `priority` int(11) DEFAULT 1,
                                    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                                    PRIMARY KEY (`id`),
                                    FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
                                
                                -- Table des documents
                                CREATE TABLE IF NOT EXISTS `documents` (
                                    `id` int(11) NOT NULL AUTO_INCREMENT,
                                    `name` varchar(255) NOT NULL,
                                    `description` text,
                                    `file_path` varchar(500) NOT NULL,
                                    `file_size` bigint(20),
                                    `file_type` varchar(100),
                                    `uploaded_by` int(11) NOT NULL,
                                    `assignment_id` int(11),
                                    `document_type` enum('report','evaluation','contract','other') DEFAULT 'other',
                                    `is_public` tinyint(1) DEFAULT 0,
                                    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                                    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                                    PRIMARY KEY (`id`),
                                    FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
                                    FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE SET NULL
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
                                
                                -- Table des réunions
                                CREATE TABLE IF NOT EXISTS `meetings` (
                                    `id` int(11) NOT NULL AUTO_INCREMENT,
                                    `title` varchar(200) NOT NULL,
                                    `description` text,
                                    `start_datetime` datetime NOT NULL,
                                    `end_datetime` datetime NOT NULL,
                                    `location` varchar(200),
                                    `meeting_type` enum('individual','group','presentation') DEFAULT 'individual',
                                    `status` enum('scheduled','in_progress','completed','cancelled') DEFAULT 'scheduled',
                                    `created_by` int(11) NOT NULL,
                                    `assignment_id` int(11),
                                    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                                    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                                    PRIMARY KEY (`id`),
                                    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
                                    FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE SET NULL
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
                                
                                -- Table des participants aux réunions
                                CREATE TABLE IF NOT EXISTS `meeting_participants` (
                                    `id` int(11) NOT NULL AUTO_INCREMENT,
                                    `meeting_id` int(11) NOT NULL,
                                    `user_id` int(11) NOT NULL,
                                    `status` enum('invited','confirmed','declined','attended') DEFAULT 'invited',
                                    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                                    PRIMARY KEY (`id`),
                                    FOREIGN KEY (`meeting_id`) REFERENCES `meetings` (`id`) ON DELETE CASCADE,
                                    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
                                
                                -- Table des messages
                                CREATE TABLE IF NOT EXISTS `messages` (
                                    `id` int(11) NOT NULL AUTO_INCREMENT,
                                    `sender_id` int(11) NOT NULL,
                                    `receiver_id` int(11) NOT NULL,
                                    `subject` varchar(200),
                                    `content` text NOT NULL,
                                    `is_read` tinyint(1) DEFAULT 0,
                                    `conversation_id` varchar(100),
                                    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                                    PRIMARY KEY (`id`),
                                    FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
                                    FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
                                
                                -- Table des conversations
                                CREATE TABLE IF NOT EXISTS `conversations` (
                                    `id` int(11) NOT NULL AUTO_INCREMENT,
                                    `name` varchar(200),
                                    `description` text,
                                    `created_by` int(11) NOT NULL,
                                    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                                    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                                    PRIMARY KEY (`id`),
                                    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
                                
                                -- Table des participants aux conversations
                                CREATE TABLE IF NOT EXISTS `conversation_participants` (
                                    `id` int(11) NOT NULL AUTO_INCREMENT,
                                    `conversation_id` int(11) NOT NULL,
                                    `user_id` int(11) NOT NULL,
                                    `joined_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                                    PRIMARY KEY (`id`),
                                    FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
                                    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
                                
                                -- Table des notifications
                                CREATE TABLE IF NOT EXISTS `notifications` (
                                    `id` int(11) NOT NULL AUTO_INCREMENT,
                                    `user_id` int(11) NOT NULL,
                                    `title` varchar(200) NOT NULL,
                                    `message` text NOT NULL,
                                    `type` enum('info','success','warning','error') DEFAULT 'info',
                                    `is_read` tinyint(1) DEFAULT 0,
                                    `action_url` varchar(500),
                                    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                                    PRIMARY KEY (`id`),
                                    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
                                
                                -- Table des paramètres de l'algorithme
                                CREATE TABLE IF NOT EXISTS `algorithm_parameters` (
                                    `id` int(11) NOT NULL AUTO_INCREMENT,
                                    `parameter_name` varchar(100) NOT NULL,
                                    `parameter_value` decimal(10,4) NOT NULL,
                                    `description` text,
                                    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                                    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                                    PRIMARY KEY (`id`)
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
                                
                                -- Table des exécutions de l'algorithme
                                CREATE TABLE IF NOT EXISTS `algorithm_executions` (
                                    `id` int(11) NOT NULL AUTO_INCREMENT,
                                    `execution_date` datetime NOT NULL,
                                    `parameters_used` json,
                                    `results` json,
                                    `total_assignments` int(11),
                                    `success_rate` decimal(5,2),
                                    `execution_time` decimal(10,4),
                                    `notes` text,
                                    `created_by` int(11) NOT NULL,
                                    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                                    PRIMARY KEY (`id`),
                                    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
                                ";
                                
                                // Exécuter le script SQL
                                $statements = array_filter(array_map('trim', explode(';', $sql)));
                                
                                foreach ($statements as $statement) {
                                    if (!empty($statement)) {
                                        // Extraire le nom de la table de la requête CREATE TABLE
                                        if (preg_match('/CREATE TABLE IF NOT EXISTS `([^`]+)`/', $statement, $matches)) {
                                            $tableName = $matches[1];
                                            logProgress("Création de la table '$tableName'", 'table');
                                            $pdo->exec($statement);
                                            logProgress("Table '$tableName' créée avec succès", 'success');
                                        } else {
                                            $pdo->exec($statement);
                                        }
                                    }
                                }
                                
                                logProgress("Installation des tables terminée avec succès ! 🎉", 'success');
                                
                                // Sauvegarder les paramètres de connexion pour la prochaine étape
                                file_put_contents('install_config.tmp', json_encode([
                                    'host' => $host,
                                    'dbname' => $dbname,
                                    'username' => $username,
                                    'password' => $password
                                ]));
                            }
                            
                        } catch (PDOException $e) {
                            logProgress("Erreur de base de données: " . $e->getMessage(), 'error');
                        } catch (Exception $e) {
                            logProgress("Erreur: " . $e->getMessage(), 'error');
                        }
                    }
                    ?>
                </div>
                
                <?php if ($action === 'install'): ?>
                <div class="text-center">
                    <a href="?step=complete" class="btn btn-success-gradient btn-lg">
                        <i class="bi bi-check-circle me-2"></i>Continuer vers les données
                    </a>
                </div>
                <?php else: ?>
                <div class="text-center">
                    <a href="?step=config" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Retour à la configuration
                    </a>
                </div>
                <?php endif; ?>
                
                <?php elseif ($step === 'complete'): ?>
                <!-- Étape 4: Finalisation -->
                <div class="step-indicator">
                    <div class="step completed">1</div>
                    <div class="step completed">2</div>
                    <div class="step completed">3</div>
                    <div class="step active">4</div>
                </div>
                
                <div class="text-center mb-4">
                    <h2><i class="bi bi-check-circle text-success me-2"></i>Installation terminée</h2>
                    <p class="text-muted">Les tables ont été créées avec succès</p>
                </div>
                
                <div class="alert alert-success alert-custom text-center">
                    <h4><i class="bi bi-trophy me-2"></i>Félicitations !</h4>
                    <p class="mb-0">Le système de gestion de tutorat est maintenant installé et prêt à être utilisé.</p>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="feature-card text-center">
                            <h5><i class="bi bi-database-add text-primary me-2"></i>Générer des données de test</h5>
                            <p class="text-muted">Créer des utilisateurs, entreprises, stages et affectations pour tester le système</p>
                            <a href="generate_data.php" class="btn btn-gradient" target="_blank">
                                <i class="bi bi-plus-circle me-2"></i>Générer les données
                            </a>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="feature-card text-center">
                            <h5><i class="bi bi-house-door text-success me-2"></i>Accéder au système</h5>
                            <p class="text-muted">Commencer à utiliser le système de gestion de tutorat</p>
                            <a href="index.php" class="btn btn-success-gradient">
                                <i class="bi bi-arrow-right me-2"></i>Aller au système
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-info alert-custom mt-4">
                    <h5><i class="bi bi-lightbulb me-2"></i>Prochaines étapes</h5>
                    <ul class="mb-0">
                        <li><strong>Générer les données :</strong> Utilisez le bouton ci-dessus pour créer des données de test</li>
                        <li><strong>Connexion par défaut :</strong> admin@tutoring.fr / 12345678</li>
                        <li><strong>Configuration :</strong> Modifiez les paramètres dans le panneau d'administration</li>
                        <li><strong>Sécurité :</strong> Changez les mots de passe par défaut</li>
                    </ul>
                </div>
                
                <div class="text-center mt-4">
                    <small class="text-muted">
                        Vous pouvez supprimer ce fichier d'installation après avoir terminé la configuration.
                    </small>
                </div>
                
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-scroll pour les logs
        document.addEventListener('DOMContentLoaded', function() {
            const logContainer = document.getElementById('logContainer');
            if (logContainer) {
                logContainer.scrollTop = logContainer.scrollHeight;
            }
        });
        
        // Actualisation automatique des logs pendant l'installation
        <?php if ($step === 'install' && $action === 'install'): ?>
        setTimeout(function() {
            const logContainer = document.getElementById('logContainer');
            if (logContainer) {
                logContainer.scrollTop = logContainer.scrollHeight;
            }
        }, 1000);
        <?php endif; ?>
    </script>
</body>
</html>