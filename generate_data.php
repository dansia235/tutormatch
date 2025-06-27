<?php
/**
 * Génération des données de test pour le système de tutorat
 * 1 admin, 52 tuteurs, 205 étudiants, 315 stages
 * Affectations cohérentes par département
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Génération des Données - Système de Tutorat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .data-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .data-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .data-header {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .data-header h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: 300;
        }
        
        .data-header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
        }
        
        .data-body {
            padding: 2rem;
        }
        
        .log-container {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1.5rem;
            max-height: 500px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
        }
        
        .log-entry {
            margin: 8px 0;
            padding: 10px;
            border-left: 4px solid #28a745;
            background: rgba(40, 167, 69, 0.1);
            border-radius: 4px;
        }
        
        .progress-section {
            margin: 2rem 0;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 2rem 0;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .btn-gradient {
            background: linear-gradient(135deg, #3498db, #2980b9);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-gradient:hover {
            background: linear-gradient(135deg, #2980b9, #1f5582);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            color: white;
        }
    </style>
</head>
<body>
    <div class="data-container">
        <div class="data-card">
            <div class="data-header">
                <h1><i class="bi bi-database-fill-add me-3"></i>Génération des Données</h1>
                <p>Création des utilisateurs, entreprises, stages et affectations de test</p>
            </div>
            
            <div class="data-body">
                <div class="alert alert-info">
                    <h5><i class="bi bi-info-circle me-2"></i>Données qui seront générées</h5>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-number">1</div>
                            <div class="stat-label">Administrateur</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">52</div>
                            <div class="stat-label">Tuteurs</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">205</div>
                            <div class="stat-label">Étudiants</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">25</div>
                            <div class="stat-label">Entreprises</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">315</div>
                            <div class="stat-label">Stages</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">205</div>
                            <div class="stat-label">Affectations</div>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mb-4">
                    <?php if (!isset($_GET['start'])): ?>
                    <a href="?start=1" class="btn btn-gradient btn-lg">
                        <i class="bi bi-play-fill me-2"></i>Démarrer la génération
                    </a>
                    <div class="mt-3">
                        <a href="install.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-2"></i>Retour à l'installation
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if (isset($_GET['start'])): ?>
                <div class="log-container" id="logContainer">
                    <?php
                    echo "<div class='log-entry'><strong>" . date('H:i:s') . "</strong> 🚀 Début de la génération des données...</div>";
                    flush();
                    ob_flush();

set_time_limit(600); // 10 minutes

function logProgress($message) {
    echo "<div class='log-entry'><strong>" . date('H:i:s') . "</strong> - " . $message . "</div>";
    flush();
    ob_flush();
}

try {
    $db = new PDO("mysql:host=localhost;dbname=tutoring_system;charset=utf8", 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    logProgress("✅ Connexion à la base de données réussie");
    
    // Désactiver contraintes
    $db->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    logProgress("🗑️ Suppression des données existantes...");
    
    // Tables dans l'ordre de suppression
    $tables = [
        'algorithm_executions', 'algorithm_parameters', 'conversation_participants', 
        'conversations', 'documents', 'evaluations', 'meeting_participants', 
        'meetings', 'messages', 'notifications', 'student_internship_preferences',
        'student_preferences', 'teacher_preferences', 'assignments', 'internships', 
        'companies', 'students', 'teachers', 'users'
    ];
    
    foreach ($tables as $table) {
        try {
            $stmt = $db->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                $db->exec("DELETE FROM $table");
                $db->exec("ALTER TABLE $table AUTO_INCREMENT = 1");
                logProgress("✅ Table $table vidée");
            }
        } catch (Exception $e) {
            logProgress("⚠️ $table: " . $e->getMessage());
        }
    }
    
    $db->exec("SET FOREIGN_KEY_CHECKS = 1");
    $db->beginTransaction();
    
    // Détection structure tables
    $stmt = $db->query("DESCRIBE users");
    $userColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $hasUsername = in_array('username', $userColumns);
    
    logProgress("👤 Structure détectée - Username: " . ($hasUsername ? "OUI" : "NON"));
    
    // Mot de passe universel
    $hashedPassword = password_hash('12345678', PASSWORD_DEFAULT);
    
    // === DÉPARTEMENTS ET COHÉRENCE ===
    $departments = [
        'Informatique' => [
            'tutors' => 18, 'students' => 72,
            'specialties' => ['Développement Web', 'Intelligence Artificielle', 'Cybersécurité', 'Data Science', 'DevOps']
        ],
        'Génie Civil' => [
            'tutors' => 12, 'students' => 48,
            'specialties' => ['Construction', 'BTP', 'Urbanisme', 'Environnement']
        ],
        'Électronique' => [
            'tutors' => 10, 'students' => 40,
            'specialties' => ['Systèmes Embarqués', 'Télécommunications', 'Automatique', 'IoT']
        ],
        'Mécanique' => [
            'tutors' => 8, 'students' => 32,
            'specialties' => ['Conception', 'Production', 'Maintenance', 'Robotique']
        ],
        'Mathématiques' => [
            'tutors' => 4, 'students' => 13,
            'specialties' => ['Statistiques', 'Modélisation', 'Recherche Opérationnelle']
        ]
    ];
    
    logProgress("🎯 Création de 1 admin + 52 tuteurs + 205 étudiants avec cohérence départementale...");
    
    // === 1. ADMINISTRATEUR ===
    if ($hasUsername) {
        $stmt = $db->prepare("INSERT INTO users (first_name, last_name, email, username, password, role, department) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute(['Administrateur', 'Système', 'admin@tutoring.fr', 'admin', $hashedPassword, 'admin', 'Administration']);
    } else {
        $stmt = $db->prepare("INSERT INTO users (first_name, last_name, email, password, role, department) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute(['Administrateur', 'Système', 'admin@tutoring.fr', $hashedPassword, 'admin', 'Administration']);
    }
    $adminId = $db->lastInsertId();
    logProgress("✅ Administrateur créé: admin@tutoring.fr");
    
    // === 2. TUTEURS PAR DÉPARTEMENT ===
    $tutorNames = [
        'Informatique' => [
            ['Thomas', 'Martin'], ['Claire', 'Bernard'], ['Antoine', 'Dubois'], ['Julie', 'Robert'],
            ['Marc', 'Petit'], ['Laura', 'Richard'], ['Pierre', 'Durand'], ['Sophie', 'Moreau'],
            ['Nicolas', 'Laurent'], ['Marie', 'Simon'], ['David', 'Michel'], ['Emma', 'Lefebvre'],
            ['Julien', 'Leroy'], ['Caroline', 'Roux'], ['Olivier', 'David'], ['Sarah', 'Bertrand'],
            ['François', 'Morel'], ['Amélie', 'Fournier']
        ],
        'Génie Civil' => [
            ['Vincent', 'Girard'], ['Camille', 'Bonnet'], ['Alexandre', 'Dupont'], ['Léa', 'Lambert'],
            ['Maxime', 'Fontaine'], ['Chloé', 'Rousseau'], ['Romain', 'Vincent'], ['Alice', 'Muller'],
            ['Lucas', 'Lefevre'], ['Manon', 'Moreau'], ['Théo', 'Garcia'], ['Zoé', 'Boyer']
        ],
        'Électronique' => [
            ['Gabriel', 'Lopez'], ['Jade', 'Gonzalez'], ['Arthur', 'Wilson'], ['Sarah', 'Anderson'],
            ['Raphaël', 'Taylor'], ['Lola', 'Moore'], ['Tom', 'Jackson'], ['Clara', 'Lee'],
            ['Noah', 'Perez'], ['Inès', 'Thompson']
        ],
        'Mécanique' => [
            ['Enzo', 'White'], ['Anaïs', 'Harris'], ['Ethan', 'Sanchez'], ['Margot', 'Clark'],
            ['Paul', 'Ramirez'], ['Romane', 'Lewis'], ['Adam', 'Robinson'], ['Juliette', 'Walker']
        ],
        'Mathématiques' => [
            ['Victor', 'Young'], ['Louise', 'Allen'], ['Maxime', 'King'], ['Océane', 'Wright']
        ]
    ];
    
    $tutorIds = [];
    $tutorUserIds = [];
    $tutorsByDept = [];
    $usedUsernames = ['admin']; // Commencer avec admin déjà utilisé
    
    foreach ($departments as $dept => $config) {
        $tutorsByDept[$dept] = [];
        $names = $tutorNames[$dept];
        
        for ($i = 0; $i < $config['tutors']; $i++) {
            $firstName = $names[$i][0];
            $lastName = $names[$i][1];
            $email = strtolower($firstName . '.' . $lastName . '@universite.fr');
            $username = strtolower($firstName . '.' . $lastName);
            
            // Assurer l'unicité du username
            $originalUsername = $username;
            $counter = 1;
            while (in_array($username, $usedUsernames)) {
                $username = $originalUsername . $counter;
                $counter++;
            }
            $usedUsernames[] = $username;
            
            if ($hasUsername) {
                $stmt = $db->prepare("INSERT INTO users (first_name, last_name, email, username, password, role, department) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$firstName, $lastName, $email, $username, $hashedPassword, 'teacher', $dept]);
            } else {
                $stmt = $db->prepare("INSERT INTO users (first_name, last_name, email, password, role, department) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$firstName, $lastName, $email, $hashedPassword, 'teacher', $dept]);
            }
            $userId = $db->lastInsertId();
            $tutorUserIds[] = $userId;
            
            // Créer l'entrée teacher
            $stmt = $db->prepare("INSERT INTO teachers (user_id) VALUES (?)");
            $stmt->execute([$userId]);
            $teacherId = $db->lastInsertId();
            $tutorIds[] = $teacherId;
            $tutorsByDept[$dept][] = $teacherId;
        }
        
        logProgress("✅ {$config['tutors']} tuteurs créés pour $dept");
    }
    
    // === 3. ÉTUDIANTS PAR DÉPARTEMENT ===
    // Générer des noms uniques pour éviter les conflits avec les tuteurs
    $studentFirstNames = [
        'Alexandre', 'Emma', 'Lucas', 'Chloé', 'Hugo', 'Léa', 'Nathan', 'Manon', 
        'Baptiste', 'Pauline', 'Valentin', 'Maëlle', 'Maxence', 'Justine', 'Dylan', 'Morgane',
        'Kevin', 'Audrey', 'Bastien', 'Céleste', 'Damien', 'Elodie', 'Florian', 'Gaëlle',
        'Hadrien', 'Iris', 'Johan', 'Karine', 'Léon', 'Mélanie', 'Oscar', 'Nathalie',
        'Quentin', 'Rachel', 'Sébastien', 'Tatiana', 'Ulysse', 'Valérie', 'William', 'Yasmine',
        'Zacharie', 'Ambre', 'Benjamin', 'Coralie', 'Dorian', 'Estelle', 'Fabien', 'Gwendoline',
        'Harold', 'Isabelle', 'Jérémy', 'Kelly', 'Loïc', 'Mathilde', 'Nicolas', 'Ophélie'
    ];
    
    $studentLastNames = [
        'Blanchard', 'Chevallier', 'Deschamps', 'Fabre', 'Garnier', 'Humbert', 'Joly', 'Klein',
        'Legrand', 'Marchand', 'Noel', 'Olivier', 'Perrin', 'Quantin', 'Renaud', 'Saunier',
        'Tessier', 'Vidal', 'Weber', 'Xavier', 'Yvon', 'Ziegler', 'Aubry', 'Berger',
        'Colin', 'Delacroix', 'Etienne', 'Francois', 'Guillot', 'Henry', 'Ibert', 'Jacquet',
        'Keller', 'Lemaire', 'Meunier', 'Nicolas', 'Perrier', 'Roussel', 'Schmitt', 'Thibault',
        'Vanier', 'Wagner', 'Yves', 'Zimmerman', 'Arnaud', 'Boucher', 'Carpentier', 'Dufour',
        'Evrard', 'Fischer', 'Germain', 'Herve', 'Imbert', 'Janvier'
    ];
    
    // Générer toutes les combinaisons possibles de prénoms et noms
    $studentNames = [];
    $usedCombinations = [];
    
    // Créer des combinaisons uniques prénom/nom sans numéros
    for ($i = 0; $i < 205; $i++) {
        $attempts = 0;
        do {
            $firstNameIndex = rand(0, count($studentFirstNames) - 1);
            $lastNameIndex = rand(0, count($studentLastNames) - 1);
            $firstName = $studentFirstNames[$firstNameIndex];
            $lastName = $studentLastNames[$lastNameIndex];
            $combination = $firstName . '|' . $lastName;
            $attempts++;
            
            // Si on ne trouve pas de combinaison unique après 50 tentatives, 
            // utiliser une combinaison séquentielle
            if ($attempts > 50) {
                $firstName = $studentFirstNames[$i % count($studentFirstNames)];
                $lastName = $studentLastNames[($i + count($studentFirstNames)) % count($studentLastNames)];
                $combination = $firstName . '|' . $lastName;
                break;
            }
        } while (in_array($combination, $usedCombinations));
        
        $usedCombinations[] = $combination;
        $studentNames[] = [$firstName, $lastName];
    }
    
    $studentIds = [];
    $studentUserIds = [];
    $studentsByDept = [];
    $studentIndex = 0;
    // $usedUsernames déjà défini et contient admin + tous les tuteurs
    
    $levels = ['L1', 'L2', 'L3', 'M1', 'M2'];
    
    foreach ($departments as $dept => $config) {
        $studentsByDept[$dept] = [];
        
        for ($i = 0; $i < $config['students']; $i++) {
            $firstName = $studentNames[$studentIndex][0];
            $lastName = $studentNames[$studentIndex][1];
            $email = strtolower($firstName . '.' . $lastName . '@etudiant.fr');
            $username = strtolower($firstName . '.' . $lastName);
            
            // Assurer l'unicité du username
            $originalUsername = $username;
            $counter = 1;
            while (in_array($username, $usedUsernames)) {
                $username = $originalUsername . $counter;
                $counter++;
            }
            $usedUsernames[] = $username;
            
            $level = $levels[rand(0, count($levels) - 1)];
            
            if ($hasUsername) {
                $stmt = $db->prepare("INSERT INTO users (first_name, last_name, email, username, password, role, department) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$firstName, $lastName, $email, $username, $hashedPassword, 'student', $dept]);
            } else {
                $stmt = $db->prepare("INSERT INTO users (first_name, last_name, email, password, role, department) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$firstName, $lastName, $email, $hashedPassword, 'student', $dept]);
            }
            $userId = $db->lastInsertId();
            $studentUserIds[] = $userId;
            
            // Créer l'entrée student
            $studentNumber = 'ET' . str_pad($studentIndex + 1, 4, '0', STR_PAD_LEFT);
            $stmt = $db->prepare("INSERT INTO students (user_id, student_number, program, level) VALUES (?, ?, ?, ?)");
            $stmt->execute([$userId, $studentNumber, $dept, $level]);
            $studentId = $db->lastInsertId();
            $studentIds[] = $studentId;
            $studentsByDept[$dept][] = $studentId;
            
            $studentIndex++;
        }
        
        logProgress("✅ {$config['students']} étudiants créés pour $dept");
    }
    
    // === 4. ENTREPRISES PAR SECTEUR ===
    logProgress("🏢 Création de 25 entreprises spécialisées par secteur...");
    
    $companiesBySector = [
        'Informatique' => [
            ['TechCorp Solutions', 'Développement logiciel et consulting IT'],
            ['InnovSoft', 'Intelligence artificielle et machine learning'],
            ['DataSystems Pro', 'Big Data et analytics avancés'],
            ['WebDev France', 'Développement web et applications mobiles'],
            ['CyberSec Expert', 'Cybersécurité et audit informatique'],
            ['CloudTech', 'Services cloud et infrastructure'],
            ['AI Dynamics', 'Intelligence artificielle industrielle']
        ],
        'Génie Civil' => [
            ['ConstructPlus', 'Construction et rénovation urbaine'],
            ['BTP Solutions', 'Bâtiment et travaux publics'],
            ['UrbanTech', 'Urbanisme et aménagement territorial'],
            ['EcoBuild', 'Construction écologique et durable'],
            ['InfraTech', 'Infrastructure et génie civil']
        ],
        'Électronique' => [
            ['ElectroDev', 'Systèmes embarqués et IoT'],
            ['TelecomPlus', 'Télécommunications et réseaux'],
            ['AutomaticSys', 'Automatisation industrielle'],
            ['SmartDevices', 'Appareils électroniques intelligents'],
            ['IoT Solutions', 'Internet des objets industriel']
        ],
        'Mécanique' => [
            ['MecaTech', 'Conception mécanique automobile'],
            ['RoboMeca', 'Robotique industrielle'],
            ['ProductionPro', 'Systèmes de production automatisés'],
            ['MaintenancePlus', 'Maintenance industrielle avancée']
        ],
        'Mathématiques' => [
            ['StatAnalytics', 'Analyse statistique et modélisation'],
            ['MathConsult', 'Consulting mathématique et recherche opérationnelle']
        ]
    ];
    
    $companyIds = [];
    $companiesByDept = [];
    
    foreach ($companiesBySector as $sector => $companies) {
        $companiesByDept[$sector] = [];
        
        foreach ($companies as $company) {
            $stmt = $db->prepare("INSERT INTO companies (name, description) VALUES (?, ?)");
            $stmt->execute([$company[0], $company[1]]);
            $companyId = $db->lastInsertId();
            $companyIds[] = $companyId;
            $companiesByDept[$sector][] = $companyId;
        }
    }
    
    logProgress("✅ " . count($companyIds) . " entreprises créées");
    
    // === 5. STAGES COHÉRENTS PAR DOMAINE ===
    logProgress("💼 Création de 315 stages cohérents...");
    
    // Stages professionnels uniques avec informations complètes
    $internshipDataByDept = [
        'Informatique' => [
            [
                'title' => 'Développeur Web Frontend',
                'requirements' => 'HTML/CSS, JavaScript, React ou Vue.js, Git, Responsive Design',
                'location' => 'Paris',
                'compensation' => 1200,
                'domain' => 'Développement Web'
            ],
            [
                'title' => 'Développeur Web Backend',
                'requirements' => 'PHP/Python/Node.js, MySQL/PostgreSQL, API REST, Docker',
                'location' => 'Lyon',
                'compensation' => 1300,
                'domain' => 'Développement Web'
            ],
            [
                'title' => 'Développeur Full Stack',
                'requirements' => 'React/Angular, Node.js, MongoDB, TypeScript, DevOps notions',
                'location' => 'Toulouse',
                'compensation' => 1400,
                'domain' => 'Développement Web'
            ],
            [
                'title' => 'Data Scientist Junior',
                'requirements' => 'Python, Pandas, Scikit-learn, SQL, Machine Learning, Statistiques',
                'location' => 'Paris',
                'compensation' => 1500,
                'domain' => 'Data Science'
            ],
            [
                'title' => 'Analyste de Données',
                'requirements' => 'SQL, Power BI/Tableau, Excel avancé, Statistiques descriptives',
                'location' => 'Marseille',
                'compensation' => 1100,
                'domain' => 'Data Analytics'
            ],
            [
                'title' => 'Ingénieur Big Data',
                'requirements' => 'Hadoop, Spark, Kafka, Python/Scala, NoSQL, Cloud (AWS/Azure)',
                'location' => 'Paris',
                'compensation' => 1600,
                'domain' => 'Big Data'
            ],
            [
                'title' => 'Développeur Mobile iOS',
                'requirements' => 'Swift, Xcode, UIKit/SwiftUI, Core Data, iOS SDK',
                'location' => 'Nantes',
                'compensation' => 1350,
                'domain' => 'Développement Mobile'
            ],
            [
                'title' => 'Développeur Mobile Android',
                'requirements' => 'Kotlin/Java, Android Studio, API REST, SQLite, Material Design',
                'location' => 'Lille',
                'compensation' => 1350,
                'domain' => 'Développement Mobile'
            ],
            [
                'title' => 'Développeur React Native',
                'requirements' => 'React Native, JavaScript/TypeScript, Redux, Firebase',
                'location' => 'Bordeaux',
                'compensation' => 1300,
                'domain' => 'Développement Mobile'
            ],
            [
                'title' => 'Ingénieur DevOps',
                'requirements' => 'Docker, Kubernetes, CI/CD, AWS/Azure, Linux, Infrastructure as Code',
                'location' => 'Paris',
                'compensation' => 1700,
                'domain' => 'Infrastructure'
            ],
            [
                'title' => 'Administrateur Systèmes',
                'requirements' => 'Linux/Windows Server, VMware, Active Directory, Monitoring, Scripting',
                'location' => 'Strasbourg',
                'compensation' => 1250,
                'domain' => 'Infrastructure'
            ],
            [
                'title' => 'Architecte Cloud',
                'requirements' => 'AWS/Azure/GCP, Terraform, Microservices, Sécurité cloud',
                'location' => 'Paris',
                'compensation' => 1800,
                'domain' => 'Cloud Computing'
            ],
            [
                'title' => 'Spécialiste Cybersécurité',
                'requirements' => 'Pentesting, OWASP, Cryptographie, ISO 27001, Réseaux, SIEM',
                'location' => 'Lyon',
                'compensation' => 1600,
                'domain' => 'Sécurité Informatique'
            ],
            [
                'title' => 'Auditeur Sécurité IT',
                'requirements' => 'Audit informatique, EBIOS, Compliance, Gestion des risques',
                'location' => 'Paris',
                'compensation' => 1400,
                'domain' => 'Audit Sécurité'
            ],
            [
                'title' => 'Consultant IT',
                'requirements' => 'Gestion de projet, UML, Business Analysis, Communication client',
                'location' => 'Nice',
                'compensation' => 1300,
                'domain' => 'Conseil IT'
            ],
            [
                'title' => 'Développeur Intelligence Artificielle',
                'requirements' => 'Python, TensorFlow/PyTorch, Deep Learning, Computer Vision, NLP',
                'location' => 'Paris',
                'compensation' => 1700,
                'domain' => 'Intelligence Artificielle'
            ],
            [
                'title' => 'Ingénieur Machine Learning',
                'requirements' => 'Python, Scikit-learn, MLOps, Feature Engineering, Modélisation prédictive',
                'location' => 'Grenoble',
                'compensation' => 1650,
                'domain' => 'Machine Learning'
            ],
            [
                'title' => 'UX/UI Designer',
                'requirements' => 'Figma/Adobe XD, Design Thinking, Prototypage, Tests utilisateurs',
                'location' => 'Paris',
                'compensation' => 1200,
                'domain' => 'Design UX/UI'
            ],
            [
                'title' => 'Ingénieur Blockchain',
                'requirements' => 'Solidity, Ethereum, Smart Contracts, Cryptographie, DeFi',
                'location' => 'Paris',
                'compensation' => 1750,
                'domain' => 'Blockchain'
            ],
            [
                'title' => 'Développeur Game Developer',
                'requirements' => 'Unity/Unreal Engine, C#/C++, Game Design, 3D, Physics Engine',
                'location' => 'Lyon',
                'compensation' => 1400,
                'domain' => 'Jeux Vidéo'
            ]
        ],
        'Génie Civil' => [
            [
                'title' => 'Ingénieur Bureau d\'Études',
                'requirements' => 'AutoCAD, Revit, Calcul de structures, Béton armé, Eurocode',
                'location' => 'Paris',
                'compensation' => 1300,
                'domain' => 'Bureau d\'Études'
            ],
            [
                'title' => 'Chef de Chantier Junior',
                'requirements' => 'Gestion de chantier, Sécurité BTP, Planning, Management équipes',
                'location' => 'Lyon',
                'compensation' => 1400,
                'domain' => 'Conduite de Travaux'
            ],
            [
                'title' => 'Conducteur de Travaux',
                'requirements' => 'Gestion de projet BTP, Budget, Planning, Réglementation',
                'location' => 'Marseille',
                'compensation' => 1500,
                'domain' => 'Conduite de Travaux'
            ],
            [
                'title' => 'Ingénieur Structure',
                'requirements' => 'RDM, Calcul béton/acier, Robot Structural, Note de calcul',
                'location' => 'Toulouse',
                'compensation' => 1350,
                'domain' => 'Structures'
            ],
            [
                'title' => 'Chargé d\'Affaires BTP',
                'requirements' => 'Commercial BTP, Chiffrage, Négociation, Relation client',
                'location' => 'Nantes',
                'compensation' => 1450,
                'domain' => 'Commercial BTP'
            ],
            [
                'title' => 'Technicien Géotechnique',
                'requirements' => 'Mécanique des sols, Essais laboratoire, Fondations, Terrassement',
                'location' => 'Lille',
                'compensation' => 1150,
                'domain' => 'Géotechnique'
            ],
            [
                'title' => 'Ingénieur Environnement',
                'requirements' => 'Études d\'impact, HQE, Développement durable, Réglementation environnementale',
                'location' => 'Bordeaux',
                'compensation' => 1300,
                'domain' => 'Environnement'
            ],
            [
                'title' => 'Urbaniste Junior',
                'requirements' => 'Aménagement du territoire, SIG, Plans locaux d\'urbanisme, Concertation',
                'location' => 'Strasbourg',
                'compensation' => 1250,
                'domain' => 'Urbanisme'
            ],
            [
                'title' => 'Dessinateur-Projeteur',
                'requirements' => 'AutoCAD, SketchUp, Plans d\'exécution, Métré, Lecture de plans',
                'location' => 'Rennes',
                'compensation' => 1100,
                'domain' => 'Dessin Technique'
            ],
            [
                'title' => 'Ingénieur Travaux Publics',
                'requirements' => 'VRD, Routes, Ouvrages d\'art, Topographie, Enrobés',
                'location' => 'Clermont-Ferrand',
                'compensation' => 1350,
                'domain' => 'Travaux Publics'
            ],
            [
                'title' => 'Responsable QSE',
                'requirements' => 'Qualité, Sécurité, Environnement, ISO 9001/14001, Document unique',
                'location' => 'Montpellier',
                'compensation' => 1400,
                'domain' => 'Qualité Sécurité'
            ],
            [
                'title' => 'Métreur-Vérificateur',
                'requirements' => 'Métré BTP, Avant-métré, Facturation, Suivi budgétaire, Quantitatif',
                'location' => 'Rouen',
                'compensation' => 1200,
                'domain' => 'Métré'
            ]
        ],
        'Électronique' => [
            [
                'title' => 'Ingénieur Systèmes Embarqués',
                'requirements' => 'C/C++, Microcontrôleurs, RTOS, Protocoles communication, PCB',
                'location' => 'Grenoble',
                'compensation' => 1450,
                'domain' => 'Systèmes Embarqués'
            ],
            [
                'title' => 'Développeur IoT',
                'requirements' => 'Arduino/Raspberry Pi, Capteurs, WiFi/Bluetooth, Cloud IoT, Python/C',
                'location' => 'Toulouse',
                'compensation' => 1350,
                'domain' => 'Internet des Objets'
            ],
            [
                'title' => 'Technicien Électronique',
                'requirements' => 'Électronique analogique/numérique, Mesures, Oscilloscope, Soudure CMS',
                'location' => 'Lyon',
                'compensation' => 1000,
                'domain' => 'Électronique Générale'
            ],
            [
                'title' => 'Ingénieur Télécommunications',
                'requirements' => 'Réseaux télécom, Antennes, RF, Protocoles 4G/5G, Traitement signal',
                'location' => 'Paris',
                'compensation' => 1500,
                'domain' => 'Télécommunications'
            ],
            [
                'title' => 'Automaticien',
                'requirements' => 'Automates programmables, SCADA, Supervision industrielle, Grafcet',
                'location' => 'Lille',
                'compensation' => 1300,
                'domain' => 'Automatisation'
            ],
            [
                'title' => 'Ingénieur Signal',
                'requirements' => 'Traitement signal numérique, MATLAB/Simulink, DSP, Filtrage',
                'location' => 'Nice',
                'compensation' => 1400,
                'domain' => 'Traitement Signal'
            ],
            [
                'title' => 'Technicien Réseau',
                'requirements' => 'Cisco, Configuration switches/routeurs, TCP/IP, VLAN, Sécurité réseau',
                'location' => 'Nantes',
                'compensation' => 1150,
                'domain' => 'Réseaux'
            ],
            [
                'title' => 'Ingénieur Test Électronique',
                'requirements' => 'Bancs de test, LabVIEW, Validation produits, Métrologie',
                'location' => 'Bordeaux',
                'compensation' => 1350,
                'domain' => 'Test & Validation'
            ],
            [
                'title' => 'Concepteur Circuits',
                'requirements' => 'KiCad/Altium, Conception PCB, Routage, Compatibilité électromagnétique',
                'location' => 'Marseille',
                'compensation' => 1300,
                'domain' => 'Conception Circuits'
            ],
            [
                'title' => 'Ingénieur Radiofréquence',
                'requirements' => 'RF/Hyperfréquences, Antennes, Amplificateurs, ADS/CST',
                'location' => 'Sophia Antipolis',
                'compensation' => 1550,
                'domain' => 'Radiofréquence'
            ],
            [
                'title' => 'Spécialiste FPGA',
                'requirements' => 'VHDL/Verilog, Xilinx/Intel FPGA, Synthèse logique, Timing',
                'location' => 'Grenoble',
                'compensation' => 1500,
                'domain' => 'FPGA'
            ],
            [
                'title' => 'Ingénieur Microélectronique',
                'requirements' => 'Conception circuits intégrés, Analog IC Design, Layout, SPICE',
                'location' => 'Grenoble',
                'compensation' => 1600,
                'domain' => 'Microélectronique'
            ]
        ],
        'Mécanique' => [
            [
                'title' => 'Ingénieur Conception Mécanique',
                'requirements' => 'SolidWorks/CATIA, Calcul mécanique, Cotation fonctionnelle, Matériaux',
                'location' => 'Lyon',
                'compensation' => 1350,
                'domain' => 'Conception Mécanique'
            ],
            [
                'title' => 'Dessinateur Industriel',
                'requirements' => 'AutoCAD/SolidWorks, Plans d\'ensemble, Nomenclatures, Tolérancement',
                'location' => 'Toulouse',
                'compensation' => 1100,
                'domain' => 'Dessin Industriel'
            ],
            [
                'title' => 'Technicien Maintenance',
                'requirements' => 'Maintenance préventive/curative, GMAO, Hydraulique/Pneumatique, Électromécanique',
                'location' => 'Marseille',
                'compensation' => 1200,
                'domain' => 'Maintenance Industrielle'
            ],
            [
                'title' => 'Ingénieur Production',
                'requirements' => 'Lean Manufacturing, 6 Sigma, Planification production, Amélioration continue',
                'location' => 'Paris',
                'compensation' => 1400,
                'domain' => 'Production'
            ],
            [
                'title' => 'Roboticien',
                'requirements' => 'Robotique industrielle, Programmation robots, Vision artificielle, Automatisation',
                'location' => 'Grenoble',
                'compensation' => 1500,
                'domain' => 'Robotique'
            ],
            [
                'title' => 'Ingénieur Qualité',
                'requirements' => 'ISO 9001, MSP, Plans d\'expérience, Métrologie, Audit qualité',
                'location' => 'Lille',
                'compensation' => 1300,
                'domain' => 'Qualité'
            ],
            [
                'title' => 'Technicien Usinage',
                'requirements' => 'Machines-outils CN, Programmation ISO, Métrologie dimensionnelle, Matériaux',
                'location' => 'Nancy',
                'compensation' => 1050,
                'domain' => 'Usinage'
            ],
            [
                'title' => 'Ingénieur Procédés',
                'requirements' => 'Génie des procédés, Simulation, Optimisation, Transferts thermiques',
                'location' => 'Nantes',
                'compensation' => 1400,
                'domain' => 'Procédés'
            ],
            [
                'title' => 'Chargé d\'Industrialisation',
                'requirements' => 'Industrialisation produits, DFM, Processus de fabrication, Coûts',
                'location' => 'Bordeaux',
                'compensation' => 1350,
                'domain' => 'Industrialisation'
            ],
            [
                'title' => 'Ingénieur R&D Mécanique',
                'requirements' => 'Innovation produit, Prototypage, Simulations numériques, Recherche appliquée',
                'location' => 'Paris',
                'compensation' => 1450,
                'domain' => 'Recherche & Développement'
            ],
            [
                'title' => 'Technicien Métrologie',
                'requirements' => 'Machines à mesurer tridimensionnelles, Étalonnage, Incertitudes, COFRAC',
                'location' => 'Strasbourg',
                'compensation' => 1150,
                'domain' => 'Métrologie'
            ],
            [
                'title' => 'Pilote d\'Îlot de Production',
                'requirements' => 'Gestion flux production, Kanban, Management équipe, Indicateurs performance',
                'location' => 'Rennes',
                'compensation' => 1250,
                'domain' => 'Production'
            ]
        ],
        'Mathématiques' => [
            [
                'title' => 'Analyste Quantitatif',
                'requirements' => 'Mathématiques financières, Modélisation stochastique, Python/R, Dérivés',
                'location' => 'Paris',
                'compensation' => 1600,
                'domain' => 'Finance Quantitative'
            ],
            [
                'title' => 'Data Analyst',
                'requirements' => 'SQL, Python/R, Statistiques, Visualisation données, Business Intelligence',
                'location' => 'Lyon',
                'compensation' => 1300,
                'domain' => 'Analyse de Données'
            ],
            [
                'title' => 'Statisticien',
                'requirements' => 'Statistiques avancées, Tests d\'hypothèses, Plans d\'expérience, SAS/R',
                'location' => 'Toulouse',
                'compensation' => 1350,
                'domain' => 'Statistiques'
            ],
            [
                'title' => 'Consultant Actuariel',
                'requirements' => 'Sciences actuarielles, Solvabilité II, Tarification, Gestion risques',
                'location' => 'Paris',
                'compensation' => 1500,
                'domain' => 'Actuariat'
            ],
            [
                'title' => 'Modélisateur Financier',
                'requirements' => 'Modèles financiers, VBA/Python, Gestion de portefeuille, Risque de marché',
                'location' => 'Paris',
                'compensation' => 1550,
                'domain' => 'Modélisation Financière'
            ],
            [
                'title' => 'Chercheur en Mathématiques Appliquées',
                'requirements' => 'Mathématiques appliquées, Recherche opérationnelle, Optimisation, Publications',
                'location' => 'Grenoble',
                'compensation' => 1400,
                'domain' => 'Recherche Mathématique'
            ]
        ]
    ];
    
    $internshipIds = [];
    $internshipsByDept = [];
    $internshipCount = 0;
    $targetCounts = [
        'Informatique' => 126, // 315 * 0.4
        'Génie Civil' => 95,   // 315 * 0.3
        'Électronique' => 63,  // 315 * 0.2
        'Mécanique' => 32,     // 315 * 0.1
        'Mathématiques' => 19  // Le reste
    ];
    
    foreach ($departments as $dept => $config) {
        $internshipsByDept[$dept] = [];
        $internshipData = $internshipDataByDept[$dept];
        $companies = $companiesByDept[$dept];
        $targetCount = $targetCounts[$dept];
        
        // Créer chaque stage unique une fois
        foreach ($internshipData as $stageInfo) {
            $companyId = $companies[rand(0, count($companies) - 1)];
            
            // Dates variables
            $startDates = ['2025-02-01', '2025-03-01', '2025-04-01', '2025-05-01', '2025-06-01', '2025-07-01'];
            $startDate = $startDates[rand(0, count($startDates) - 1)];
            $endDate = date('Y-m-d', strtotime($startDate . ' +4 months'));
            
            $description = "Stage de 4 mois en " . $stageInfo['domain'] . " - " . $stageInfo['title'] . ". Mission formatrice dans un environnement professionnel stimulant avec possibilité d'intégration.";
            
            $stmt = $db->prepare("INSERT INTO internships (title, company_id, start_date, end_date, description, requirements, location, compensation, domain, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'available')");
            $stmt->execute([
                $stageInfo['title'],
                $companyId,
                $startDate,
                $endDate,
                $description,
                $stageInfo['requirements'],
                $stageInfo['location'],
                $stageInfo['compensation'],
                $stageInfo['domain'],
            ]);
            
            $internshipId = $db->lastInsertId();
            $internshipIds[] = $internshipId;
            $internshipsByDept[$dept][] = $internshipId;
            $internshipCount++;
        }
        
        // Si on a besoin de plus de stages, répéter en cycle avec des variations
        $remainingCount = $targetCount - count($internshipData);
        if ($remainingCount > 0) {
            for ($i = 0; $i < $remainingCount; $i++) {
                $baseStage = $internshipData[$i % count($internshipData)];
                $companyId = $companies[rand(0, count($companies) - 1)];
                
                // Variations pour rendre uniques
                $variations = [
                    'Senior' => ['compensation' => $baseStage['compensation'] + 200, 'suffix' => ' Senior'],
                    'Junior' => ['compensation' => $baseStage['compensation'] - 100, 'suffix' => ' Junior'],
                    'Alternance' => ['compensation' => $baseStage['compensation'] - 200, 'suffix' => ' (Alternance)'],
                    'R&D' => ['compensation' => $baseStage['compensation'] + 150, 'suffix' => ' R&D'],
                    'Projet' => ['compensation' => $baseStage['compensation'] + 100, 'suffix' => ' - Projet spécial']
                ];
                
                $variationKey = array_keys($variations)[rand(0, count($variations) - 1)];
                $variation = $variations[$variationKey];
                
                $title = $baseStage['title'] . $variation['suffix'];
                
                // Dates variables
                $startDates = ['2025-02-01', '2025-03-01', '2025-04-01', '2025-05-01', '2025-06-01', '2025-07-01'];
                $startDate = $startDates[rand(0, count($startDates) - 1)];
                $endDate = date('Y-m-d', strtotime($startDate . ' +4 months'));
                
                $description = "Stage de 4 mois en " . $baseStage['domain'] . " - " . $title . ". Mission formatrice dans un environnement professionnel stimulant avec possibilité d'intégration.";
                
                $stmt = $db->prepare("INSERT INTO internships (title, company_id, start_date, end_date, description, requirements, location, compensation, domain, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'available')");
                $stmt->execute([
                    $title,
                    $companyId,
                    $startDate,
                    $endDate,
                    $description,
                    $baseStage['requirements'],
                    $baseStage['location'],
                    $variation['compensation'],
                    $baseStage['domain'],
                ]);
                
                $internshipId = $db->lastInsertId();
                $internshipIds[] = $internshipId;
                $internshipsByDept[$dept][] = $internshipId;
                $internshipCount++;
            }
        }
        
        logProgress("✅ " . count($internshipsByDept[$dept]) . " stages créés pour $dept (uniques et professionnels)");
    }
    
    // === 6. AFFECTATIONS COHÉRENTES ===
    logProgress("📋 Création des affectations cohérentes (1-5 étudiants par tuteur)...");
    
    $assignmentCount = 0;
    $usedStudents = [];
    $usedInternships = [];
    
    foreach ($departments as $dept => $config) {
        $deptTutors = $tutorsByDept[$dept];
        $deptStudents = $studentsByDept[$dept];
        $deptInternships = $internshipsByDept[$dept];
        
        foreach ($deptTutors as $tutorId) {
            $studentsPerTutor = rand(1, 5); // 1 à 5 étudiants par tuteur
            $assignedToThisTutor = 0;
            
            for ($s = 0; $s < $studentsPerTutor && $assignedToThisTutor < 5; $s++) {
                // Trouver un étudiant non affecté du même département
                $availableStudents = array_diff($deptStudents, $usedStudents);
                if (empty($availableStudents)) break;
                
                $studentId = $availableStudents[array_rand($availableStudents)];
                
                // Trouver un stage non affecté du même département
                $availableInternships = array_diff($deptInternships, $usedInternships);
                if (empty($availableInternships)) break;
                
                $internshipId = $availableInternships[array_rand($availableInternships)];
                
                $statuses = ['pending', 'confirmed', 'confirmed', 'confirmed']; // 75% confirmé
                $status = $statuses[rand(0, count($statuses) - 1)];
                
                $stmt = $db->prepare("INSERT INTO assignments (student_id, teacher_id, internship_id, status, compatibility_score, satisfaction_score, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $studentId,
                    $tutorId,
                    $internshipId,
                    $status,
                    rand(75, 98), // Score compatibilité élevé (même département)
                    rand(3, 5),   // Score satisfaction
                    "Affectation cohérente en $dept. Profil étudiant adapté aux exigences du stage."
                ]);
                
                // Mettre à jour le statut du stage en 'assigned' si l'affectation est confirmée
                if ($status === 'confirmed') {
                    $updateStmt = $db->prepare("UPDATE internships SET status = 'assigned' WHERE id = ?");
                    $updateStmt->execute([$internshipId]);
                }
                
                $usedStudents[] = $studentId;
                $usedInternships[] = $internshipId;
                $assignmentCount++;
                $assignedToThisTutor++;
            }
        }
        
        logProgress("✅ Affectations créées pour $dept");
    }
    
    logProgress("✅ Total: $assignmentCount affectations cohérentes créées");
    
    // === 7. ÉVALUATIONS COMPLÈTES (3 PAR ÉTUDIANT AFFECTÉ) ===
    logProgress("📊 Création des évaluations complètes (final, mid-term, auto-évaluation)...");
    
    try {
        $stmt = $db->query("DESCRIBE evaluations");
        $evalColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $evaluationCount = 0;
        
        // Récupérer TOUTES les affectations (pas seulement confirmées)
        $stmt = $db->query("SELECT a.id as assignment_id, a.student_id, a.teacher_id, a.status FROM assignments a");
        $allAssignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        logProgress("📋 Trouvé " . count($allAssignments) . " affectations pour évaluations");
        
        foreach ($allAssignments as $assignment) {
            // 1. ÉVALUATION MI-PARCOURS (par le tuteur)
            $midTermScore = rand(12, 18);
            $midTermComments = [
                "Excellent travail, très motivé et impliqué dans les tâches",
                "Bon niveau technique, continue sur cette voie",
                "Progrès remarquables depuis le début du stage",
                "Très bonne intégration dans l'équipe de travail",
                "Travail de qualité, ponctuel et rigoureux dans l'exécution",
                "Bonne compréhension des objectifs du stage",
                "Autonomie croissante dans la réalisation des tâches",
                "Esprit d'équipe et communication efficace"
            ];
            
            if (in_array('assignment_id', $evalColumns)) {
                $stmt = $db->prepare("INSERT INTO evaluations (assignment_id, evaluator_id, evaluatee_id, type, score, comments) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$assignment['assignment_id'], $assignment['teacher_id'], $assignment['student_id'], 'mid_term', $midTermScore, $midTermComments[rand(0, count($midTermComments) - 1)]]);
            } else {
                $stmt = $db->prepare("INSERT INTO evaluations (evaluator_id, evaluatee_id, type, score, comments) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$assignment['teacher_id'], $assignment['student_id'], 'mid_term', $midTermScore, $midTermComments[rand(0, count($midTermComments) - 1)]]);
            }
            $evaluationCount++;
            
            // 2. ÉVALUATION FINALE (par le tuteur)
            $finalScore = rand(14, 20);
            $finalComments = [
                "Stage réussi avec brio, objectifs largement atteints",
                "Excellente progression tout au long du stage",
                "Recommandé pour un poste permanent dans l'entreprise",
                "Stage exemplaire, félicitations pour ce parcours",
                "Compétences acquises remarquables, très bon potentiel",
                "Mission accomplie avec succès et professionnalisme",
                "Dépassement des attentes, résultats excellents",
                "Adaptation rapide et contribution significative à l'équipe"
            ];
            
            if (in_array('assignment_id', $evalColumns)) {
                $stmt = $db->prepare("INSERT INTO evaluations (assignment_id, evaluator_id, evaluatee_id, type, score, comments) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$assignment['assignment_id'], $assignment['teacher_id'], $assignment['student_id'], 'final', $finalScore, $finalComments[rand(0, count($finalComments) - 1)]]);
            } else {
                $stmt = $db->prepare("INSERT INTO evaluations (evaluator_id, evaluatee_id, type, score, comments) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$assignment['teacher_id'], $assignment['student_id'], 'final', $finalScore, $finalComments[rand(0, count($finalComments) - 1)]]);
            }
            $evaluationCount++;
            
            // 3. AUTO-ÉVALUATION (par l'étudiant)
            $selfScore = rand(13, 17); // Les étudiants sont souvent plus modestes
            $selfComments = [
                "J'ai beaucoup appris durant ce stage, les objectifs ont été atteints",
                "Stage très enrichissant, j'ai pu développer mes compétences techniques",
                "Excellente expérience professionnelle, équipe très accueillante",
                "J'ai pu mettre en pratique mes connaissances théoriques",
                "Stage formateur qui m'a permis de gagner en autonomie",
                "Très satisfait de cette expérience, recommande cette entreprise",
                "Apprentissage intensif, stage conforme à mes attentes",
                "Bonne intégration dans l'équipe, missions variées et intéressantes"
            ];
            
            if (in_array('assignment_id', $evalColumns)) {
                $stmt = $db->prepare("INSERT INTO evaluations (assignment_id, evaluator_id, evaluatee_id, type, score, comments) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$assignment['assignment_id'], $assignment['student_id'], $assignment['student_id'], 'student', $selfScore, $selfComments[rand(0, count($selfComments) - 1)]]);
            } else {
                $stmt = $db->prepare("INSERT INTO evaluations (evaluator_id, evaluatee_id, type, score, comments) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$assignment['student_id'], $assignment['student_id'], 'student', $selfScore, $selfComments[rand(0, count($selfComments) - 1)]]);
            }
            $evaluationCount++;
        }
        
        logProgress("✅ $evaluationCount évaluations créées (3 par étudiant affecté)");
        
    } catch (Exception $e) {
        logProgress("⚠️ Evaluations: " . $e->getMessage());
    }
    
    // === 8. DOCUMENTS COMPLETS UPLOADÉS PAR DIFFÉRENTS UTILISATEURS ===
    logProgress("📄 Création des documents et fichiers (.md et .txt)...");
    
    // Créer le dossier documents
    $docsDir = __DIR__ . '/uploads/documents/';
    if (!file_exists($docsDir)) {
        if (mkdir($docsDir, 0777, true)) {
            logProgress("✅ Dossier créé: " . $docsDir);
        } else {
            logProgress("❌ Impossible de créer le dossier: " . $docsDir);
        }
    } else {
        logProgress("✅ Dossier existe: " . $docsDir);
    }
    
    $documentCount = 0;
    
    try {
        $stmt = $db->query("DESCRIBE documents");
        $docColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Types de documents complets et innovants avec uploaders
        $docTypesWithUploaders = [
            // === DOCUMENTS ÉTUDIANTS ===
            ['CV Professionnel', 'cv_professionnel', 'md', 'student'],
            ['Lettre de Motivation Personnalisée', 'lettre_motivation', 'md', 'student'],
            ['Portfolio Projets', 'portfolio', 'md', 'student'],
            ['Rapport de Stage Détaillé', 'rapport_stage', 'md', 'student'],
            ['Journal de Bord Hebdomadaire', 'journal_bord', 'md', 'student'],
            ['Auto-évaluation des Compétences', 'auto_evaluation', 'md', 'student'],
            ['Projet de Fin de Stage', 'projet_fin_stage', 'md', 'student'],
            ['Mémoire Technique', 'memoire_technique', 'md', 'student'],
            ['Présentation de Soutenance', 'presentation_soutenance', 'md', 'student'],
            ['Analyse Critique du Stage', 'analyse_critique', 'md', 'student'],
            
            // === DOCUMENTS TUTEURS ===
            ['Fiche d\'Évaluation Mi-Parcours', 'eval_mi_parcours', 'md', 'teacher'],
            ['Fiche d\'Évaluation Finale', 'eval_finale', 'md', 'teacher'],
            ['Grille de Compétences Techniques', 'grille_competences', 'md', 'teacher'],
            ['Suivi Pédagogique Mensuel', 'suivi_pedagogique', 'md', 'teacher'],
            ['Rapport de Visite Entreprise', 'visite_entreprise', 'md', 'teacher'],
            ['Évaluation Comportementale', 'eval_comportementale', 'md', 'teacher'],
            ['Plan de Formation Individualisé', 'plan_formation', 'md', 'teacher'],
            ['Bilan de Progression', 'bilan_progression', 'md', 'teacher'],
            ['Recommandations Pédagogiques', 'recommandations', 'md', 'teacher'],
            
            // === DOCUMENTS ADMINISTRATIFS ===
            ['Convention de Stage Tripartite', 'convention_tripartite', 'md', 'admin'],
            ['Contrat de Stage Officiel', 'contrat_stage', 'md', 'admin'],
            ['Attestation de Stage Certifiée', 'attestation_certifiee', 'md', 'admin'],
            ['Certificat de Compétences', 'certificat_competences', 'md', 'admin'],
            ['Fiche d\'Entreprise Partenaire', 'fiche_entreprise', 'md', 'admin'],
            ['Dossier d\'Inscription Stage', 'dossier_inscription', 'md', 'admin'],
            ['Autorisation Parentale', 'autorisation_parentale', 'md', 'admin'],
            ['Assurance Responsabilité Civile', 'assurance_rc', 'md', 'admin'],
            ['Règlement Intérieur Stage', 'reglement_stage', 'md', 'admin'],
            
            // === DOCUMENTS COORDINATEURS ===
            ['Validation Académique', 'validation_academique', 'md', 'coordinator'],
            ['Suivi Administratif Global', 'suivi_global', 'md', 'coordinator'],
            ['Rapport de Coordination', 'rapport_coordination', 'md', 'coordinator'],
            ['Planning de Soutenances', 'planning_soutenances', 'md', 'coordinator'],
            ['Bilan Statistique Stages', 'bilan_statistique', 'md', 'coordinator'],
            
            // === DOCUMENTS ENTREPRISE (simulés par admin) ===
            ['Fiche de Poste Détaillée', 'fiche_poste', 'md', 'admin'],
            ['Évaluation Entreprise', 'eval_entreprise', 'md', 'admin'],
            ['Certificat de Travail', 'certificat_travail', 'md', 'admin'],
            ['Bilan des Missions', 'bilan_missions', 'md', 'admin'],
            ['Offre de Recrutement', 'offre_recrutement', 'md', 'admin']
        ];
        
        // Récupérer les affectations pour les documents (réutiliser la même requête)
        if (!isset($allAssignments) || empty($allAssignments)) {
            $stmt = $db->query("SELECT a.id as assignment_id, a.student_id, a.teacher_id, a.status FROM assignments a");
            $allAssignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        logProgress("📋 Génération documents pour " . count($allAssignments) . " affectations");
        
        // Créer documents obligatoires + documents aléatoires pour chaque affectation
        foreach ($allAssignments as $assignment) {
            logProgress("📄 Génération documents pour affectation " . $assignment['assignment_id']);
            
            // === DOCUMENTS OBLIGATOIRES POUR CHAQUE ÉTUDIANT ===
            $mandatoryDocs = [
                ['Convention de Stage Tripartite', 'convention_tripartite', 'md', 'admin'],
                ['Contrat de Stage Officiel', 'contrat_stage', 'md', 'admin'],
                ['Rapport de Stage Détaillé', 'rapport_stage', 'md', 'student'],
                ['Fiche d\'Évaluation Finale', 'eval_finale', 'md', 'teacher'],
                ['Attestation de Stage Certifiée', 'attestation_certifiee', 'md', 'admin'],
                ['Certificat de Compétences', 'certificat_competences', 'md', 'admin']
            ];
            
            // === DOCUMENTS ALÉATOIRES SUPPLÉMENTAIRES ===
            $additionalDocs = rand(6, 10);
            $selectedDocs = $mandatoryDocs;
            
            // Ajouter des documents aléatoires
            for ($i = 0; $i < $additionalDocs; $i++) {
                $randomDoc = $docTypesWithUploaders[rand(0, count($docTypesWithUploaders) - 1)];
                $selectedDocs[] = $randomDoc;
            }
            
            foreach ($selectedDocs as $docInfo) {
                $docTitle = $docInfo[0];
                $docType = $docInfo[1];
                $extension = $docInfo[2];
                $uploaderRole = $docInfo[3];
                
                // Déterminer qui a uploadé le document (user_id)
                $uploaderId = null;
                $uploaderName = '';
                
                switch ($uploaderRole) {
                    case 'student':
                        // Récupérer le user_id de l'étudiant
                        $stmt = $db->prepare("SELECT user_id FROM students WHERE id = ?");
                        $stmt->execute([$assignment['student_id']]);
                        $uploaderId = $stmt->fetchColumn();
                        $uploaderName = 'Étudiant';
                        break;
                    case 'teacher':
                        // Récupérer le user_id du tuteur
                        $stmt = $db->prepare("SELECT user_id FROM teachers WHERE id = ?");
                        $stmt->execute([$assignment['teacher_id']]);
                        $uploaderId = $stmt->fetchColumn();
                        $uploaderName = 'Tuteur';
                        break;
                    case 'admin':
                    case 'coordinator':
                        $uploaderId = $adminId; // L'admin user_id est déjà correct
                        $uploaderName = ($uploaderRole == 'admin') ? 'Administrateur' : 'Coordinateur';
                        break;
                }
                
                if (!$uploaderId) {
                    logProgress("⚠️ Impossible de trouver user_id pour le rôle $uploaderRole");
                    continue;
                }
                
                $fileName = strtolower(str_replace([' ', '\'', 'à', 'é', 'è'], ['_', '', 'a', 'e', 'e'], $docTitle)) . '_' . $assignment['student_id'] . '_' . time() . '_' . rand(100, 999) . '.' . $extension;
                $filePath = $docsDir . $fileName;
                
                // === GÉNÉRATION DE CONTENU INNOVANT ET COMPLET ===
                $content = '';
                
                // En-tête commun pour tous les documents
                $content .= "# " . $docTitle . "\n\n";
                $content .= "---\n\n";
                $content .= "**📋 Informations du Document**\n\n";
                $content .= "- **Étudiant:** ID " . $assignment['student_id'] . "\n";
                $content .= "- **Tuteur:** ID " . $assignment['teacher_id'] . "\n";
                $content .= "- **Uploadé par:** " . $uploaderName . "\n";
                $content .= "- **Date de création:** " . date('Y-m-d H:i:s') . "\n";
                $content .= "- **Type:** " . $docType . "\n";
                $content .= "- **Statut:** ✅ Validé\n\n";
                $content .= "---\n\n";
                
                // === CONTENU SPÉCIALISÉ PAR TYPE DE DOCUMENT ===
                switch ($docType) {
                    
                    // === DOCUMENTS ÉTUDIANTS ===
                    case 'cv_professionnel':
                        $content .= "## 👤 CURRICULUM VITAE PROFESSIONNEL\n\n";
                        $content .= "### 🎓 Formation Académique\n\n";
                        $content .= "| Année | Diplôme | Établissement | Mention |\n";
                        $content .= "|-------|---------|---------------|----------|\n";
                        $content .= "| 2024 | Master 2 Informatique | Université Tech | Très Bien |\n";
                        $content .= "| 2022 | Master 1 Informatique | Université Tech | Bien |\n";
                        $content .= "| 2021 | Licence Informatique | Université Tech | Assez Bien |\n";
                        $content .= "| 2018 | Baccalauréat S | Lycée Einstein | Mention Bien |\n\n";
                        $content .= "### 💼 Expérience Professionnelle\n\n";
                        $content .= "**Stage en cours** - " . date('Y') . "\n";
                        $content .= "- 🏢 Entreprise partenaire\n";
                        $content .= "- 🎯 Développement d'applications métier\n";
                        $content .= "- 🔧 Technologies: PHP, JavaScript, MySQL\n\n";
                        $content .= "**Projets Universitaires** - 2023\n";
                        $content .= "- 📱 Application mobile de gestion\n";
                        $content .= "- 🌐 Site web e-commerce\n";
                        $content .= "- 🤖 Bot de traitement automatique\n\n";
                        $content .= "### 🛠️ Compétences Techniques\n\n";
                        $content .= "**Langages de programmation:**\n";
                        $content .= "- Java ⭐⭐⭐⭐⭐\n";
                        $content .= "- Python ⭐⭐⭐⭐⭐\n";
                        $content .= "- JavaScript ⭐⭐⭐⭐\n";
                        $content .= "- PHP ⭐⭐⭐⭐\n";
                        $content .= "- C++ ⭐⭐⭐\n\n";
                        $content .= "**Frameworks & Technologies:**\n";
                        $content .= "- React/Vue.js ⭐⭐⭐⭐\n";
                        $content .= "- Node.js ⭐⭐⭐⭐\n";
                        $content .= "- Django/Flask ⭐⭐⭐⭐\n";
                        $content .= "- MySQL/PostgreSQL ⭐⭐⭐⭐\n";
                        $content .= "- Git/Docker ⭐⭐⭐⭐\n\n";
                        $content .= "### 🗣️ Langues\n\n";
                        $content .= "- **Français:** Langue maternelle\n";
                        $content .= "- **Anglais:** Niveau B2 (TOEIC 850)\n";
                        $content .= "- **Espagnol:** Niveau A2\n\n";
                        break;
                        
                    case 'rapport_stage':
                        $content .= "## 📊 RAPPORT DE STAGE DÉTAILLÉ\n\n";
                        $content .= "### 🏢 Présentation de l'Entreprise\n\n";
                        $content .= "**Nom:** Entreprise d'accueil\n";
                        $content .= "**Secteur:** " . ['Informatique', 'Génie Civil', 'Électronique', 'Mécanique'][rand(0, 3)] . "\n";
                        $content .= "**Effectif:** " . rand(50, 500) . " employés\n";
                        $content .= "**Chiffre d'affaires:** " . rand(5, 50) . "M€\n\n";
                        $content .= "### 🎯 Missions et Objectifs\n\n";
                        $content .= "#### Mission principale\n";
                        $content .= "Développement d'une application de gestion intégrée pour optimiser les processus métier.\n\n";
                        $content .= "#### Objectifs spécifiques\n";
                        $content .= "1. **Analyse des besoins** - Étude des processus existants\n";
                        $content .= "2. **Conception technique** - Architecture de la solution\n";
                        $content .= "3. **Développement** - Implémentation des fonctionnalités\n";
                        $content .= "4. **Tests et validation** - Assurance qualité\n";
                        $content .= "5. **Documentation** - Guide utilisateur et technique\n\n";
                        $content .= "### 📈 Réalisations\n\n";
                        $content .= "| Semaine | Réalisation | Avancement |\n";
                        $content .= "|---------|-------------|------------|\n";
                        $content .= "| 1-2 | Analyse des besoins | 100% |\n";
                        $content .= "| 3-4 | Conception UML | 100% |\n";
                        $content .= "| 5-8 | Développement Backend | 95% |\n";
                        $content .= "| 9-10 | Interface utilisateur | 90% |\n";
                        $content .= "| 11-12 | Tests et débogage | 85% |\n\n";
                        $content .= "### 🧠 Compétences Développées\n\n";
                        $content .= "**Compétences techniques:**\n";
                        $content .= "- Maîtrise avancée des frameworks modernes\n";
                        $content .= "- Optimisation de bases de données\n";
                        $content .= "- Méthodologies de tests automatisés\n";
                        $content .= "- Intégration continue (CI/CD)\n\n";
                        $content .= "**Compétences transversales:**\n";
                        $content .= "- Gestion de projet agile (Scrum)\n";
                        $content .= "- Communication client\n";
                        $content .= "- Travail en équipe multiculturelle\n";
                        $content .= "- Résolution de problèmes complexes\n\n";
                        $content .= "### 🎓 Bilan et Perspectives\n\n";
                        $content .= "Ce stage a été une expérience enrichissante qui m'a permis de consolider mes acquis théoriques et de développer une expertise pratique dans le domaine. Les défis rencontrés ont stimulé ma créativité et renforcé ma motivation pour une carrière dans l'innovation technologique.\n\n";
                        $content .= "**Perspectives d'avenir:**\n";
                        $content .= "- Poursuite en thèse CIFRE avec l'entreprise\n";
                        $content .= "- Spécialisation en intelligence artificielle\n";
                        $content .= "- Création d'une startup technologique\n\n";
                        break;
                        
                    case 'convention_tripartite':
                        $content .= "## 📄 CONVENTION DE STAGE TRIPARTITE\n\n";
                        $content .= "### 📋 Parties Contractantes\n\n";
                        $content .= "**🎓 ÉTABLISSEMENT D'ENSEIGNEMENT**\n";
                        $content .= "- Nom: Université de Technologie\n";
                        $content .= "- Adresse: Campus Universitaire, 12345 Ville\n";
                        $content .= "- Représentant: Directeur des Stages\n";
                        $content .= "- Téléphone: 01.23.45.67.89\n";
                        $content .= "- Email: stages@universite.fr\n\n";
                        $content .= "**🏢 ENTREPRISE D'ACCUEIL**\n";
                        $content .= "- Nom: Entreprise Partenaire SARL\n";
                        $content .= "- Secteur: " . ['Informatique', 'Génie Civil', 'Électronique'][rand(0, 2)] . "\n";
                        $content .= "- Siret: " . rand(10000000000000, 99999999999999) . "\n";
                        $content .= "- Adresse: Zone d'activité, 67890 Ville\n";
                        $content .= "- Maître de stage: Responsable Technique\n\n";
                        $content .= "**👤 ÉTUDIANT STAGIAIRE**\n";
                        $content .= "- Étudiant ID: " . $assignment['student_id'] . "\n";
                        $content .= "- Formation: Master " . ['Informatique', 'Génie Civil', 'Électronique'][rand(0, 2)] . "\n";
                        $content .= "- Niveau: M2\n";
                        $content .= "- Tuteur pédagogique: ID " . $assignment['teacher_id'] . "\n\n";
                        $content .= "### 📅 Modalités du Stage\n\n";
                        $content .= "- **Durée:** 12 semaines\n";
                        $content .= "- **Début:** " . date('Y-m-d', strtotime('+1 month')) . "\n";
                        $content .= "- **Fin:** " . date('Y-m-d', strtotime('+4 months')) . "\n";
                        $content .= "- **Horaires:** 35h/semaine, du lundi au vendredi\n";
                        $content .= "- **Gratification:** " . rand(400, 700) . "€/mois\n";
                        $content .= "- **Congés:** 2,5 jours ouvrables par mois\n\n";
                        $content .= "### 🎯 Objectifs Pédagogiques\n\n";
                        $content .= "1. **Mise en pratique** des connaissances théoriques\n";
                        $content .= "2. **Découverte** du monde professionnel\n";
                        $content .= "3. **Développement** de compétences transversales\n";
                        $content .= "4. **Réalisation** d'un projet concret\n";
                        $content .= "5. **Préparation** à l'insertion professionnelle\n\n";
                        $content .= "### ⚖️ Obligations des Parties\n\n";
                        $content .= "**Obligations de l'entreprise:**\n";
                        $content .= "- Accueillir l'étudiant dans de bonnes conditions\n";
                        $content .= "- Désigner un maître de stage compétent\n";
                        $content .= "- Fournir les moyens nécessaires à la mission\n";
                        $content .= "- Respecter la législation du travail\n\n";
                        $content .= "**Obligations de l'étudiant:**\n";
                        $content .= "- Respecter le règlement intérieur\n";
                        $content .= "- Faire preuve d'assiduité et de ponctualité\n";
                        $content .= "- Respecter la confidentialité\n";
                        $content .= "- Rédiger un rapport de stage\n\n";
                        $content .= "### ✍️ Signatures\n\n";
                        $content .= "**Université:** _[Signature du Directeur]_ - Date: " . date('Y-m-d') . "\n\n";
                        $content .= "**Entreprise:** _[Signature du Responsable]_ - Date: " . date('Y-m-d') . "\n\n";
                        $content .= "**Étudiant:** _[Signature de l'étudiant]_ - Date: " . date('Y-m-d') . "\n\n";
                        break;
                        
                    case 'eval_finale':
                        $content .= "## 📊 FICHE D'ÉVALUATION FINALE\n\n";
                        $content .= "### 📋 Informations Générales\n\n";
                        $content .= "- **Période d'évaluation:** " . date('Y-m-d', strtotime('-1 month')) . " au " . date('Y-m-d') . "\n";
                        $content .= "- **Durée du stage:** 12 semaines\n";
                        $content .= "- **Évaluateur:** Tuteur pédagogique (ID " . $assignment['teacher_id'] . ")\n\n";
                        $content .= "### 🎯 Grille d'Évaluation Détaillée\n\n";
                        $content .= "| Critère | Coefficient | Note /20 | Note Pondérée | Commentaire |\n";
                        $content .= "|---------|-------------|----------|---------------|-------------|\n";
                        $content .= "| **Compétences Techniques** | 30% | " . rand(14, 18) . " | " . number_format(rand(14, 18) * 0.3, 1) . " | Excellent niveau |\n";
                        $content .= "| **Autonomie et Initiative** | 20% | " . rand(13, 17) . " | " . number_format(rand(13, 17) * 0.2, 1) . " | Progresse bien |\n";
                        $content .= "| **Communication** | 15% | " . rand(15, 19) . " | " . number_format(rand(15, 19) * 0.15, 1) . " | Très bonne |\n";
                        $content .= "| **Travail en équipe** | 15% | " . rand(16, 20) . " | " . number_format(rand(16, 20) * 0.15, 1) . " | Excellent |\n";
                        $content .= "| **Respect des délais** | 10% | " . rand(17, 20) . " | " . number_format(rand(17, 20) * 0.1, 1) . " | Parfait |\n";
                        $content .= "| **Adaptation** | 10% | " . rand(14, 18) . " | " . number_format(rand(14, 18) * 0.1, 1) . " | Très bonne |\n\n";
                        $totalScore = rand(15, 18);
                        $content .= "**🏆 NOTE GLOBALE: " . $totalScore . "/20**\n\n";
                        $content .= "### 📝 Évaluation Qualitative\n\n";
                        $content .= "#### Points Forts\n";
                        $content .= "- ✅ Excellente maîtrise des technologies utilisées\n";
                        $content .= "- ✅ Capacité d'analyse et de synthèse remarquable\n";
                        $content .= "- ✅ Très bonne intégration dans l'équipe projet\n";
                        $content .= "- ✅ Proactivité et force de proposition\n";
                        $content .= "- ✅ Qualité de la documentation produite\n\n";
                        $content .= "#### Axes d'Amélioration\n";
                        $content .= "- 🔄 Approfondir les connaissances en architecture système\n";
                        $content .= "- 🔄 Développer les compétences en gestion de projet\n";
                        $content .= "- 🔄 Renforcer l'expression orale en public\n\n";
                        $content .= "#### Commentaires Détaillés\n\n";
                        $content .= "L'étudiant a fait preuve d'un engagement exemplaire tout au long du stage. Sa progression technique est remarquable et son adaptation au contexte professionnel est excellente. Les objectifs pédagogiques sont largement atteints.\n\n";
                        $content .= "### 🚀 Perspectives\n\n";
                        if ($totalScore >= 16) {
                            $content .= "**Recommandation:** Poursuite en thèse ou recrutement immédiat\n";
                            $content .= "**Potentiel:** Très élevé pour des postes d'ingénieur senior\n";
                        } else {
                            $content .= "**Recommandation:** Poursuite d'études ou stage complémentaire\n";
                            $content .= "**Potentiel:** Bon niveau pour des postes d'ingénieur junior\n";
                        }
                        $content .= "**Secteurs recommandés:** R&D, Innovation, Management technique\n\n";
                        break;
                        
                    case 'certificat_competences':
                        $content .= "## 🏆 CERTIFICAT DE COMPÉTENCES\n\n";
                        $content .= "### 🎖️ Certification Officielle\n\n";
                        $content .= "**La Direction des Études certifie que:**\n\n";
                        $content .= "L'étudiant référencé ID " . $assignment['student_id'] . " a démontré, au cours de son stage pratique, la maîtrise des compétences suivantes:\n\n";
                        $content .= "### 💻 Compétences Techniques Certifiées\n\n";
                        $content .= "#### Développement Logiciel ⭐⭐⭐⭐⭐\n";
                        $content .= "- Maîtrise des langages: Java, Python, JavaScript\n";
                        $content .= "- Frameworks modernes: React, Spring, Django\n";
                        $content .= "- Méthodologies: Agile, DevOps, TDD\n\n";
                        $content .= "#### Gestion de Données ⭐⭐⭐⭐\n";
                        $content .= "- Conception de bases de données relationnelles\n";
                        $content .= "- Optimisation de requêtes SQL avancées\n";
                        $content .= "- Technologies NoSQL (MongoDB, Redis)\n\n";
                        $content .= "#### Architecture Système ⭐⭐⭐⭐\n";
                        $content .= "- Microservices et APIs REST\n";
                        $content .= "- Conteneurisation (Docker, Kubernetes)\n";
                        $content .= "- Services cloud (AWS, Azure)\n\n";
                        $content .= "### 🤝 Compétences Transversales\n\n";
                        $content .= "| Compétence | Niveau | Validation |\n";
                        $content .= "|------------|--------|------------|\n";
                        $content .= "| Leadership | Expert | ✅ Certifié |\n";
                        $content .= "| Communication | Expert | ✅ Certifié |\n";
                        $content .= "| Gestion de projet | Avancé | ✅ Certifié |\n";
                        $content .= "| Innovation | Expert | ✅ Certifié |\n";
                        $content .= "| Adaptabilité | Expert | ✅ Certifié |\n\n";
                        $content .= "### 🎯 Projets Réalisés\n\n";
                        $content .= "1. **Système de gestion intégré** - Développement complet\n";
                        $content .= "2. **API de traitement de données** - Architecture microservices\n";
                        $content .= "3. **Interface utilisateur responsive** - UX/UI moderne\n";
                        $content .= "4. **Pipeline CI/CD** - Automatisation des déploiements\n\n";
                        $content .= "### 📜 Validation Officielle\n\n";
                        $content .= "**Date de certification:** " . date('Y-m-d') . "\n";
                        $content .= "**Numéro de certificat:** CERT-" . date('Y') . "-" . str_pad($assignment['student_id'], 4, '0', STR_PAD_LEFT) . "\n";
                        $content .= "**Validité:** 5 ans\n\n";
                        $content .= "**Signature électronique:** _[Directeur des Études]_\n";
                        $content .= "**Cachet de l'établissement:** 🏛️ Université de Technologie\n\n";
                        break;
                        
                    case 'portfolio':
                        $content .= "## 🎨 PORTFOLIO PROJETS\n\n";
                        $content .= "### 🚀 Présentation\n\n";
                        $content .= "Ce portfolio présente les réalisations marquantes développées durant le cursus universitaire et le stage professionnel.\n\n";
                        $content .= "### 💼 Projets Universitaires\n\n";
                        $content .= "#### 📱 Application Mobile \"EcoTrack\"\n";
                        $content .= "**Contexte:** Projet de fin d'études L3\n";
                        $content .= "**Objectif:** Application de suivi écologique personnel\n";
                        $content .= "**Technologies:** React Native, Node.js, MongoDB\n";
                        $content .= "**Fonctionnalités:**\n";
                        $content .= "- Calcul d'empreinte carbone personnalisée\n";
                        $content .= "- Challenges écologiques gamifiés\n";
                        $content .= "- Communauté d'utilisateurs engagés\n";
                        $content .= "- Géolocalisation des points de recyclage\n\n";
                        $content .= "**Résultats:** 1er prix du concours d'innovation étudiante\n\n";
                        $content .= "#### 🌐 Plateforme E-learning \"StudyMate\"\n";
                        $content .= "**Contexte:** Projet collectif M1\n";
                        $content .= "**Objectif:** Plateforme collaborative d'apprentissage\n";
                        $content .= "**Technologies:** Vue.js, Laravel, MySQL, Socket.io\n";
                        $content .= "**Fonctionnalités:**\n";
                        $content .= "- Cours interactifs avec vidéos\n";
                        $content .= "- Système de quiz adaptatifs\n";
                        $content .= "- Chat en temps réel\n";
                        $content .= "- Suivi de progression personnalisé\n\n";
                        $content .= "#### 🤖 Bot Intelligent \"AssistantPro\"\n";
                        $content .= "**Contexte:** Projet personnel\n";
                        $content .= "**Objectif:** Assistant virtuel pour professionnels\n";
                        $content .= "**Technologies:** Python, TensorFlow, NLP, Discord API\n";
                        $content .= "**Fonctionnalités:**\n";
                        $content .= "- Traitement du langage naturel\n";
                        $content .= "- Planification automatique de réunions\n";
                        $content .= "- Analyse de sentiment des emails\n";
                        $content .= "- Intégration multi-plateforme\n\n";
                        $content .= "### 🏢 Projets de Stage\n\n";
                        $content .= "#### 🏗️ Système de Gestion Intégré\n";
                        $content .= "**Mission principale du stage**\n";
                        $content .= "**Impact:** Optimisation de 40% des processus métier\n";
                        $content .= "**Innovation:** Architecture microservices évolutive\n\n";
                        $content .= "### 🏆 Reconnaissances\n\n";
                        $content .= "- 🥇 **1er Prix** - Concours d'innovation étudiante 2023\n";
                        $content .= "- 🏅 **Mention Très Bien** - Projet de fin d'études\n";
                        $content .= "- 🎖️ **Certification** - Développeur Full Stack\n";
                        $content .= "- 📜 **Publication** - Article dans revue technique\n\n";
                        break;
                        
                    default:
                        // Contenu générique pour les autres types
                        $content .= "## 📋 Document Professionnel\n\n";
                        $content .= "### 📄 Contenu du Document\n\n";
                        $content .= "Ce document fait partie intégrante du dossier de stage et contient les informations essentielles relatives au suivi pédagogique et administratif.\n\n";
                        $content .= "### ✅ Validation\n\n";
                        $content .= "- **Statut:** Document validé et conforme\n";
                        $content .= "- **Conformité:** Respecte les standards qualité\n";
                        $content .= "- **Archivage:** Conservation réglementaire assurée\n\n";
                        break;
                }
                
                // Pied de page commun
                $content .= "\n---\n\n";
                $content .= "### 📞 Contact et Informations\n\n";
                $content .= "- **Service des Stages:** stages@universite.fr\n";
                $content .= "- **Urgences:** 01.23.45.67.89\n";
                $content .= "- **Portal étudiant:** www.universite.fr/stages\n\n";
                $content .= "---\n\n";
                $content .= "*🔒 Document confidentiel - Usage interne uniquement*\n\n";
                $content .= "*📅 Généré automatiquement le " . date('d/m/Y à H:i') . "*\n\n";
                $content .= "*🏛️ Université de Technologie - Système de Gestion des Stages*\n";
                
                file_put_contents($filePath, $content);
                
                // Insérer en base - Structure correcte: user_id, assignment_id, type (enum)
                // Déterminer le type enum basé sur le type de document
                $enumType = 'other'; // Par défaut
                if (strpos($docType, 'contrat') !== false || strpos($docType, 'convention') !== false) {
                    $enumType = 'contract';
                } elseif (strpos($docType, 'rapport') !== false) {
                    $enumType = 'report';
                } elseif (strpos($docType, 'eval') !== false) {
                    $enumType = 'evaluation';
                } elseif (strpos($docType, 'certificat') !== false || strpos($docType, 'attestation') !== false) {
                    $enumType = 'certificate';
                }
                
                try {
                    $stmt = $db->prepare("INSERT INTO documents (user_id, assignment_id, title, description, type, file_path, file_type, status, visibility) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $uploaderId, // user_id de celui qui upload
                        $assignment['assignment_id'],
                        $docTitle,
                        "Document généré automatiquement pour l'étudiant ID " . $assignment['student_id'],
                        $enumType,
                        'uploads/documents/' . $fileName,
                        $extension, // file_type
                        'approved',
                        'private'
                    ]);
                } catch (Exception $insertError) {
                    logProgress("⚠️ Erreur insertion document: " . $insertError->getMessage());
                    continue;
                }
                $documentCount++;
            }
        }
        
        logProgress("✅ $documentCount documents innovants créés avec contenu détaillé (.md)");
        
    } catch (Exception $e) {
        logProgress("⚠️ Documents: " . $e->getMessage());
    }
    
    // === 9. CONVERSATIONS ET MESSAGES INNOVANTS ===
    logProgress("💬 Création des conversations réalistes et cohérentes...");
    
    try {
        $stmt = $db->query("DESCRIBE messages");
        $msgColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Vérifier si la table conversations existe
        $hasConversations = false;
        try {
            $stmt = $db->query("SHOW TABLES LIKE 'conversations'");
            $hasConversations = $stmt->rowCount() > 0;
        } catch (Exception $e) {
            // Table n'existe pas
        }
        
        $messageCount = 0;
        $conversationCount = 0;
        
        // === SCÉNARIOS DE CONVERSATIONS RÉALISTES ===
        $conversationScenarios = [
            // SCÉNARIO 1: Première prise de contact
            'first_contact' => [
                'teacher' => [
                    "Bonjour {student_name},\n\nJe suis {teacher_name}, votre tuteur pour ce stage. Je voulais prendre contact avec vous avant le début de votre stage chez {company}.\n\nPouvez-vous me confirmer votre date de début et me dire si vous avez des questions concernant l'organisation ?\n\nBien cordialement,\n{teacher_name}",
                    
                    "Parfait ! Je vous remercie pour ces informations.\n\nVoici quelques conseils pour bien démarrer :\n- Arrivez 10-15 minutes en avance le premier jour\n- Préparez une liste de questions sur l'entreprise et vos missions\n- N'hésitez pas à prendre des notes\n\nJe vous propose qu'on se voit après votre première semaine pour faire un point.\n\nBon courage !",
                    
                    "Excellent ! Je vois que vous êtes bien préparé(e).\n\nPour notre suivi, je vous propose :\n- Un point hebdomadaire par email\n- Une visite en entreprise mi-parcours\n- Une réunion avant la rédaction du rapport\n\nEst-ce que ce planning vous convient ?"
                ],
                'student' => [
                    "Bonjour {teacher_name},\n\nMerci pour votre message. Je commence effectivement le {start_date} chez {company}.\n\nJ'ai déjà eu un premier contact téléphonique avec mon maître de stage, M./Mme {supervisor}. J'ai quelques questions sur les livrables attendus pour l'université.\n\nPourrions-nous en discuter ?\n\nCordialement,\n{student_name}",
                    
                    "Merci beaucoup pour ces conseils précieux !\n\nJ'ai déjà préparé :\n- Mon CV et lettre de motivation imprimés\n- Un carnet pour prendre des notes\n- Les documents administratifs demandés\n\nJe suis un peu stressé(e) mais très motivé(e) ! Une réunion après la première semaine serait parfaite.\n\nÀ bientôt,\n{student_name}",
                    
                    "Ce planning me convient parfaitement.\n\nJe prendrai soin de vous envoyer un compte-rendu chaque vendredi.\n\nMerci pour votre accompagnement !\n\n{student_name}"
                ]
            ],
            
            // SCÉNARIO 2: Problème technique
            'technical_issue' => [
                'student' => [
                    "Bonjour {teacher_name},\n\nJ'espère que vous allez bien. Je rencontre une difficulté technique sur mon projet actuel.\n\nJe travaille sur {technical_detail} et je bloque sur {problem_detail}. J'ai essayé plusieurs approches mais sans succès.\n\nAuriez-vous des pistes à me suggérer ?\n\nMerci d'avance,\n{student_name}",
                    
                    "Merci pour votre réponse rapide !\n\nJ'ai essayé votre suggestion et cela m'a permis d'avancer. Cependant, j'ai maintenant un nouveau problème :\n{new_problem}\n\nJ'ai fait des recherches et je pense que {proposed_solution} pourrait fonctionner. Qu'en pensez-vous ?\n\nCordialement,\n{student_name}",
                    
                    "Génial ! Ça fonctionne maintenant !\n\nVotre aide m'a été très précieuse. J'ai documenté la solution pour m'en souvenir.\n\nJe peux maintenant continuer sur la suite du projet. Mon maître de stage est impressionné par la rapidité de résolution.\n\nMerci encore !\n{student_name}"
                ],
                'teacher' => [
                    "Bonjour {student_name},\n\nJe comprends votre difficulté. {technical_detail} peut effectivement être complexe.\n\nVoici quelques pistes :\n1. Avez-vous vérifié {suggestion_1} ?\n2. Essayez {suggestion_2}\n3. La documentation officielle mentionne {suggestion_3}\n\nSi cela ne fonctionne pas, nous pouvons organiser une visio pour regarder ensemble.\n\nCourage !\n{teacher_name}",
                    
                    "Très bonne analyse !\n\nVotre proposition concernant {proposed_solution} est pertinente. Je vous suggère de :\n- Faire un backup avant d'implémenter\n- Tester sur un environnement de développement d'abord\n- Documenter vos changements\n\nCela développe exactement les compétences attendues dans votre formation.\n\nTenez-moi au courant !",
                    
                    "Félicitations ! 🎉\n\nJe suis ravi que vous ayez résolu ce problème. Votre démarche méthodique est exactement ce qu'on attend d'un bon développeur.\n\nN'oubliez pas d'inclure cette expérience dans votre rapport de stage, c'est un excellent exemple de résolution de problème.\n\nContinuez ainsi !\n{teacher_name}"
                ]
            ],
            
            // SCÉNARIO 3: Suivi mi-parcours
            'midterm_review' => [
                'teacher' => [
                    "Bonjour {student_name},\n\nNous sommes à mi-parcours de votre stage. Il est temps de faire un bilan.\n\nPouvez-vous me faire un retour sur :\n- Vos missions actuelles\n- Les compétences que vous développez\n- Les difficultés éventuelles\n- Votre intégration dans l'équipe\n\nJe suis disponible pour une visio cette semaine si vous préférez.\n\nBien cordialement,\n{teacher_name}",
                    
                    "Merci pour ce retour détaillé.\n\nJe suis impressionné par {achievement}. Cela montre une vraie progression.\n\nConcernant {difficulty}, c'est normal à ce stade. Voici mes conseils :\n{advice}\n\nPour la suite du stage, quels sont vos objectifs ?\n\nContinuez cet excellent travail !",
                    
                    "Parfait ! Vos objectifs sont clairs et atteignables.\n\nJe vais contacter votre maître de stage pour avoir son retour également.\n\nN'oubliez pas de commencer à réfléchir à votre rapport de stage. Je vous envoie le template par email séparé.\n\nÀ bientôt,\n{teacher_name}"
                ],
                'student' => [
                    "Bonjour {teacher_name},\n\nMerci pour votre message. Voici mon bilan à mi-parcours :\n\n**Missions actuelles :**\n- {mission_1}\n- {mission_2}\n- {mission_3}\n\n**Compétences développées :**\n- {skill_1}\n- {skill_2}\n- {skill_3}\n\n**Difficultés :**\n- {difficulty} mais je commence à mieux comprendre\n\n**Intégration :**\nTrès bonne ! L'équipe est bienveillante et j'ai un mentor qui m'aide beaucoup.\n\nUne visio serait super pour approfondir !\n\n{student_name}",
                    
                    "Merci pour vos encouragements et conseils !\n\n{achievement} a effectivement été un challenge intéressant. J'ai beaucoup appris.\n\nPour la suite, j'aimerais :\n1. Approfondir {skill_area}\n2. Participer à {project_type}\n3. Peut-être proposer {innovation}\n\nMon maître de stage semble ouvert à ces idées.\n\nQu'en pensez-vous ?\n\n{student_name}",
                    
                    "Super, merci !\n\nJ'attends le template avec impatience. J'ai déjà commencé à prendre des notes régulières pour le rapport.\n\nMon maître de stage a mentionné la possibilité d'une offre d'emploi après le stage. C'est motivant !\n\nJe vous tiens au courant de l'évolution.\n\nBonne journée,\n{student_name}"
                ]
            ],
            
            // SCÉNARIO 4: Préparation du rapport
            'report_preparation' => [
                'student' => [
                    "Bonjour {teacher_name},\n\nJ'ai commencé la rédaction de mon rapport de stage et j'aurais besoin de vos conseils.\n\nJ'ai structuré mon rapport ainsi :\n1. Introduction\n2. Présentation de l'entreprise\n3. Missions réalisées\n4. Compétences acquises\n5. Analyse critique\n6. Conclusion\n\nEst-ce que cette structure vous convient ? Avez-vous des recommandations spécifiques ?\n\nMerci,\n{student_name}",
                    
                    "Merci pour ces précisions !\n\nJ'ai une question concernant la partie technique : jusqu'à quel niveau de détail dois-je aller ? \n\nPar exemple, pour {technical_project}, dois-je inclure :\n- Le code source ?\n- Les diagrammes d'architecture ?\n- Seulement une description fonctionnelle ?\n\nJe ne veux pas que ce soit trop technique ni trop superficiel.\n\n{student_name}",
                    
                    "Parfait, c'est très clair maintenant.\n\nJ'ai presque terminé la première version. Pourrais-je vous l'envoyer pour une relecture d'ici la fin de la semaine ?\n\nJ'ai aussi préparé ma présentation PowerPoint pour la soutenance.\n\nMerci pour votre accompagnement tout au long de ce stage !\n\n{student_name}"
                ],
                'teacher' => [
                    "Bonjour {student_name},\n\nVotre structure est un bon point de départ. Je suggère d'ajouter :\n\n- Une partie sur les apports du stage par rapport à votre formation\n- Un chapitre sur les perspectives (professionnelles et personnelles)\n- Des annexes avec vos réalisations concrètes\n\nPensez à :\n- Illustrer avec des captures d'écran/schémas\n- Citer vos sources\n- Faire relire pour les fautes\n\nLe rapport doit faire entre 30 et 40 pages hors annexes.\n\nBon courage !",
                    
                    "Excellente question !\n\nPour la partie technique, visez un équilibre :\n\n✅ À inclure :\n- Diagrammes d'architecture (obligatoire)\n- Extraits de code commentés pour les parties clés\n- Descriptions des choix techniques et justifications\n- Captures d'écran de l'application\n\n❌ À éviter :\n- Code source complet (mettre en annexe si nécessaire)\n- Détails d'implémentation triviaux\n\nL'objectif est qu'un lecteur technique comprenne votre travail sans s'enliser dans les détails.\n\n{teacher_name}",
                    
                    "Avec plaisir !\n\nEnvoyez-moi votre rapport dès qu'il est prêt. Je vous ferai un retour détaillé sous 48h.\n\nPour la soutenance :\n- 20 minutes de présentation\n- 10 minutes de questions\n- Préparez une démo si possible\n\nJe suis fier du travail que vous avez accompli. Votre progression est remarquable !\n\nÀ très bientôt pour la soutenance,\n{teacher_name}"
                ]
            ],
            
            // SCÉNARIO 5: Urgence/Problème
            'urgent_issue' => [
                'student' => [
                    "URGENT - Bonjour {teacher_name},\n\nJ'ai un problème urgent : {urgent_issue}.\n\nCela impacte {impact} et mon maître de stage attend une solution rapidement.\n\nPuis-je vous appeler ?\n\n{student_name}",
                    
                    "Merci infiniment pour votre aide rapide !\n\nJ'ai appliqué vos conseils et la situation est maintenant sous contrôle.\n\n{resolution_detail}\n\nMon maître de stage a apprécié la réactivité.\n\n{student_name}",
                    
                    "Leçon apprise !\n\nJ'ai documenté l'incident et la solution pour l'équipe.\n\nCela m'a fait réaliser l'importance de {lesson_learned}.\n\nMerci encore pour votre disponibilité.\n\n{student_name}"
                ],
                'teacher' => [
                    "Bonjour {student_name},\n\nJe viens de voir votre message. Je suis disponible maintenant au {phone_number}.\n\nEn attendant, essayez de :\n1. {immediate_action_1}\n2. {immediate_action_2}\n3. Gardez votre calme, ces situations arrivent\n\nJ'attends votre appel.\n\n{teacher_name}",
                    
                    "Excellent travail sous pression !\n\nVotre gestion de cette crise montre votre professionnalisme.\n\nPour éviter que cela se reproduise :\n- {prevention_tip_1}\n- {prevention_tip_2}\n- {prevention_tip_3}\n\nC'est en gérant ce type de situation qu'on progresse le plus.\n\nBravo !",
                    
                    "C'est exactement la bonne attitude !\n\nDocumenter les incidents et leurs résolutions est une excellente pratique professionnelle.\n\n{lesson_learned} est effectivement crucial dans notre métier.\n\nJe noterai cette expérience positivement dans mon évaluation.\n\nContinuez ainsi !\n{teacher_name}"
                ]
            ]
        ];
        
        // === DÉTAILS TECHNIQUES PAR DÉPARTEMENT ===
        $technicalDetails = [
            'Informatique' => [
                'details' => ['une API REST', 'un système de cache Redis', 'une interface React', 'un pipeline CI/CD', 'une base de données PostgreSQL'],
                'problems' => ['problème de CORS', 'fuite mémoire', 'problème de performance', 'erreur de déploiement', 'conflit de merge Git'],
                'solutions' => ['configurer les headers CORS', 'utiliser un profiler', 'implémenter la pagination', 'vérifier les variables d\'environnement', 'faire un rebase interactif'],
                'missions' => ['Développement d\'une nouvelle fonctionnalité', 'Refactoring du code legacy', 'Mise en place de tests unitaires', 'Optimisation des performances', 'Documentation technique'],
                'skills' => ['React/Vue.js avancé', 'Architecture microservices', 'DevOps et conteneurisation', 'Méthodologies Agile', 'Clean Code principles'],
                'achievements' => ['l\'implémentation du système de notification temps réel', 'la réduction de 40% du temps de chargement', 'la mise en place de l\'intégration continue'],
                'urgent_issues' => ['le serveur de production est down', 'une faille de sécurité a été détectée', 'la base de données est corrompue'],
                'projects' => ['un hackathon interne', 'la refonte complète de l\'architecture', 'un POC sur une nouvelle technologie']
            ],
            'Génie Civil' => [
                'details' => ['un calcul de structure', 'une modélisation BIM', 'un planning de chantier', 'une étude géotechnique', 'un cahier des charges'],
                'problems' => ['erreur de calcul RDM', 'conflit dans le modèle 3D', 'retard de livraison matériaux', 'non-conformité détectée', 'problème de coordination'],
                'solutions' => ['revoir les hypothèses de calcul', 'utiliser la détection de clashs', 'réorganiser le planning', 'proposer une solution alternative', 'organiser une réunion de coordination'],
                'missions' => ['Suivi de chantier hebdomadaire', 'Calculs de dimensionnement', 'Réalisation de plans AutoCAD', 'Gestion des sous-traitants', 'Contrôle qualité'],
                'skills' => ['Maîtrise d\'AutoCAD/Revit', 'Gestion de projet construction', 'Normes et réglementations', 'Lecture de plans techniques', 'Communication chantier'],
                'achievements' => ['la validation des plans par le bureau de contrôle', 'l\'optimisation du phasage chantier', 'la résolution d\'un problème technique complexe'],
                'urgent_issues' => ['un problème de sécurité sur le chantier', 'une non-conformité majeure détectée', 'un retard critique sur le planning'],
                'projects' => ['la certification environnementale', 'un nouveau procédé constructif', 'l\'amélioration de la sécurité chantier']
            ],
            'Électronique' => [
                'details' => ['un circuit imprimé', 'un programme embarqué', 'un protocole de communication', 'un système de capteurs', 'une carte de développement'],
                'problems' => ['problème de soudure', 'bug dans le firmware', 'interférences électromagnétiques', 'surchauffe du composant', 'problème de timing'],
                'solutions' => ['refaire les soudures', 'debugger avec un analyseur logique', 'ajouter un blindage', 'revoir la dissipation thermique', 'ajuster les délais'],
                'missions' => ['Conception de PCB', 'Programmation microcontrôleur', 'Tests et validation', 'Rédaction de documentation', 'Support production'],
                'skills' => ['Conception électronique', 'Programmation C embarqué', 'Protocoles de communication', 'Instrumentation et mesure', 'Gestion de projet électronique'],
                'achievements' => ['la miniaturisation du circuit de 30%', 'l\'amélioration de l\'autonomie batterie', 'la certification CE du produit'],
                'urgent_issues' => ['un lot de production défectueux', 'un bug critique dans le firmware', 'une panne sur le banc de test'],
                'projects' => ['le développement d\'un nouveau produit IoT', 'l\'amélioration du process de test', 'la migration vers une nouvelle plateforme']
            ],
            'Mécanique' => [
                'details' => ['une conception CAO', 'une simulation éléments finis', 'un processus de fabrication', 'un assemblage complexe', 'une gamme d\'usinage'],
                'problems' => ['contrainte dépassée en simulation', 'tolérance non respectée', 'problème d\'assemblage', 'vibration excessive', 'usure prématurée'],
                'solutions' => ['optimiser la géométrie', 'revoir les tolérances', 'modifier la séquence d\'assemblage', 'ajouter un amortisseur', 'changer le matériau'],
                'missions' => ['Conception 3D SolidWorks', 'Calculs de résistance', 'Suivi de fabrication', 'Tests et essais', 'Amélioration continue'],
                'skills' => ['CAO/DAO avancé', 'Simulation numérique', 'Métrologie et contrôle', 'Gestion de production', 'Lean Manufacturing'],
                'achievements' => ['la réduction de 20% du coût de fabrication', 'la validation du prototype', 'l\'amélioration de la fiabilité'],
                'urgent_issues' => ['une casse machine en production', 'un défaut critique sur une série', 'un problème de sécurité machine'],
                'projects' => ['l\'industrialisation d\'un nouveau produit', 'l\'optimisation de la chaîne de production', 'la mise en place du Lean']
            ],
            'Mathématiques' => [
                'details' => ['un modèle statistique', 'un algorithme d\'optimisation', 'une analyse de données', 'un modèle prédictif', 'une étude actuarielle'],
                'problems' => ['convergence de l\'algorithme', 'overfitting du modèle', 'données manquantes', 'résultats incohérents', 'complexité calculatoire'],
                'solutions' => ['ajuster les hyperparamètres', 'utiliser la régularisation', 'implémenter une imputation', 'vérifier les hypothèses', 'paralléliser les calculs'],
                'missions' => ['Développement de modèles', 'Analyse statistique', 'Création de dashboards', 'Recherche opérationnelle', 'Rédaction de rapports'],
                'skills' => ['Python/R avancé', 'Machine Learning', 'Statistiques avancées', 'Visualisation de données', 'Communication scientifique'],
                'achievements' => ['l\'amélioration de 15% de la précision du modèle', 'l\'automatisation du reporting', 'la découverte d\'insights business clés'],
                'urgent_issues' => ['le modèle de production donne des résultats aberrants', 'une deadline critique pour une analyse', 'une erreur dans les calculs financiers'],
                'projects' => ['un nouveau modèle de scoring', 'l\'optimisation des processus métier', 'une étude d\'impact économique']
            ]
        ];
        
        // Générer les user_ids pour les messages
        $teacherUserIds = [];
        $studentUserIds = [];
        
        // Récupérer les user_ids des tuteurs
        foreach ($tutorIds as $tutorId) {
            $stmt = $db->prepare("SELECT user_id FROM teachers WHERE id = ?");
            $stmt->execute([$tutorId]);
            $userIdResult = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($userIdResult) {
                $teacherUserIds[$tutorId] = $userIdResult['user_id'];
            }
        }
        
        // Récupérer les user_ids des étudiants
        foreach ($studentIds as $studentId) {
            $stmt = $db->prepare("SELECT user_id FROM students WHERE id = ?");
            $stmt->execute([$studentId]);
            $userIdResult = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($userIdResult) {
                $studentUserIds[$studentId] = $userIdResult['user_id'];
            }
        }
        
        // === GÉNÉRER DES CONVERSATIONS POUR CHAQUE AFFECTATION ===
        foreach ($allAssignments as $assignment) {
            $teacherId = $assignment['teacher_id'];
            $studentId = $assignment['student_id'];
            
            // Récupérer les infos nécessaires
            $teacherUserId = $teacherUserIds[$teacherId] ?? null;
            $studentUserId = $studentUserIds[$studentId] ?? null;
            
            if (!$teacherUserId || !$studentUserId) {
                continue;
            }
            
            // Déterminer le département de l'étudiant
            $stmt = $db->prepare("SELECT u.first_name, u.last_name, u.department FROM users u JOIN students s ON u.id = s.user_id WHERE s.id = ?");
            $stmt->execute([$studentId]);
            $studentInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $stmt = $db->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
            $stmt->execute([$teacherUserId]);
            $teacherInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$studentInfo || !$teacherInfo) {
                continue;
            }
            
            $dept = $studentInfo['department'];
            $techDetails = $technicalDetails[$dept] ?? $technicalDetails['Informatique'];
            
            // Variables de remplacement
            $replacements = [
                '{student_name}' => $studentInfo['first_name'],
                '{teacher_name}' => $teacherInfo['first_name'] . ' ' . $teacherInfo['last_name'],
                '{company}' => 'TechCorp Solutions', // Pourrait être amélioré avec vraie entreprise
                '{start_date}' => date('d/m/Y', strtotime('+' . rand(1, 30) . ' days')),
                '{supervisor}' => ['Martin', 'Dubois', 'Laurent', 'Bernard'][rand(0, 3)],
                '{phone_number}' => '06 ' . rand(10, 99) . ' ' . rand(10, 99) . ' ' . rand(10, 99) . ' ' . rand(10, 99),
                '{technical_detail}' => $techDetails['details'][rand(0, count($techDetails['details']) - 1)],
                '{problem_detail}' => $techDetails['problems'][rand(0, count($techDetails['problems']) - 1)],
                '{new_problem}' => $techDetails['problems'][rand(0, count($techDetails['problems']) - 1)],
                '{proposed_solution}' => $techDetails['solutions'][rand(0, count($techDetails['solutions']) - 1)],
                '{suggestion_1}' => $techDetails['solutions'][rand(0, count($techDetails['solutions']) - 1)],
                '{suggestion_2}' => $techDetails['solutions'][rand(0, count($techDetails['solutions']) - 1)],
                '{suggestion_3}' => $techDetails['solutions'][rand(0, count($techDetails['solutions']) - 1)],
                '{mission_1}' => $techDetails['missions'][0],
                '{mission_2}' => $techDetails['missions'][1],
                '{mission_3}' => $techDetails['missions'][2],
                '{skill_1}' => $techDetails['skills'][0],
                '{skill_2}' => $techDetails['skills'][1],
                '{skill_3}' => $techDetails['skills'][2],
                '{skill_area}' => $techDetails['skills'][rand(0, count($techDetails['skills']) - 1)],
                '{achievement}' => $techDetails['achievements'][rand(0, count($techDetails['achievements']) - 1)],
                '{difficulty}' => $techDetails['problems'][rand(0, count($techDetails['problems']) - 1)],
                '{advice}' => "- " . implode("\n- ", array_slice($techDetails['solutions'], 0, 3)),
                '{project_type}' => $techDetails['projects'][rand(0, count($techDetails['projects']) - 1)],
                '{innovation}' => "une amélioration du processus de " . strtolower($techDetails['missions'][rand(0, count($techDetails['missions']) - 1)]),
                '{technical_project}' => $techDetails['details'][rand(0, count($techDetails['details']) - 1)],
                '{urgent_issue}' => $techDetails['urgent_issues'][rand(0, count($techDetails['urgent_issues']) - 1)],
                '{impact}' => "le projet en cours et la livraison client",
                '{immediate_action_1}' => "Faire un backup immédiat",
                '{immediate_action_2}' => "Identifier la source exacte du problème",
                '{resolution_detail}' => "Le problème venait de " . $techDetails['problems'][rand(0, count($techDetails['problems']) - 1)] . ". J'ai appliqué la solution et tout fonctionne maintenant.",
                '{lesson_learned}' => "toujours avoir un plan B et documenter les procédures d'urgence",
                '{prevention_tip_1}' => "Mettre en place un monitoring",
                '{prevention_tip_2}' => "Faire des sauvegardes régulières",
                '{prevention_tip_3}' => "Documenter les procédures critiques"
            ];
            
            // Choisir 1-3 scénarios de conversation pour cette affectation
            $scenarioKeys = array_keys($conversationScenarios);
            $numScenarios = rand(1, 3);
            shuffle($scenarioKeys);
            $selectedScenarios = array_slice($scenarioKeys, 0, $numScenarios);
            
            // Pour chaque scénario sélectionné
            foreach ($selectedScenarios as $scenarioKey) {
                $scenario = $conversationScenarios[$scenarioKey];
                
                // Créer une conversation si la table existe
                $conversationId = null;
                if ($hasConversations) {
                    $conversationTitle = match($scenarioKey) {
                        'first_contact' => 'Premier contact - Stage',
                        'technical_issue' => 'Aide technique - ' . $replacements['{technical_detail}'],
                        'midterm_review' => 'Bilan mi-parcours',
                        'report_preparation' => 'Préparation rapport de stage',
                        'urgent_issue' => 'URGENT - Assistance requise',
                        default => 'Discussion stage'
                    };
                    
                    $stmt = $db->prepare("INSERT INTO conversations (title, created_by, created_at) VALUES (?, ?, NOW())");
                    $stmt->execute([$conversationTitle, $teacherUserId]);
                    $conversationId = $db->lastInsertId();
                    
                    // Ajouter les participants
                    $stmt = $db->prepare("INSERT INTO conversation_participants (conversation_id, user_id) VALUES (?, ?), (?, ?)");
                    $stmt->execute([$conversationId, $teacherUserId, $conversationId, $studentUserId]);
                    
                    $conversationCount++;
                }
                
                // Générer les messages de la conversation
                $messageDate = new DateTime();
                $messageDate->modify('-' . rand(5, 25) . ' days');
                
                // Alterner entre messages du tuteur et de l'étudiant
                $isTeacherFirst = ($scenarioKey == 'first_contact' || $scenarioKey == 'midterm_review');
                $teacherMessages = $scenario['teacher'];
                $studentMessages = $scenario['student'];
                
                for ($i = 0; $i < min(count($teacherMessages), count($studentMessages)); $i++) {
                    // Message 1 (tuteur ou étudiant selon le scénario)
                    if ($isTeacherFirst) {
                        $message1 = str_replace(array_keys($replacements), array_values($replacements), $teacherMessages[$i]);
                        $sender1 = $teacherUserId;
                        $receiver1 = $studentUserId;
                        
                        $message2 = str_replace(array_keys($replacements), array_values($replacements), $studentMessages[$i]);
                        $sender2 = $studentUserId;
                        $receiver2 = $teacherUserId;
                    } else {
                        $message1 = str_replace(array_keys($replacements), array_values($replacements), $studentMessages[$i]);
                        $sender1 = $studentUserId;
                        $receiver1 = $teacherUserId;
                        
                        $message2 = str_replace(array_keys($replacements), array_values($replacements), $teacherMessages[$i]);
                        $sender2 = $teacherUserId;
                        $receiver2 = $studentUserId;
                    }
                    
                    // Insérer le premier message - adapter aux colonnes existantes
                    $subject = match($scenarioKey) {
                        'first_contact' => 'Premier contact - Début de stage',
                        'technical_issue' => 'Question technique',
                        'midterm_review' => 'Bilan mi-parcours',
                        'report_preparation' => 'Rapport de stage',
                        'urgent_issue' => 'URGENT - Besoin d\'aide',
                        default => 'Suivi de stage'
                    };
                    
                    // Adapter la requête selon les colonnes disponibles
                    if (in_array('sent_date', $msgColumns)) {
                        if (in_array('conversation_id', $msgColumns) && in_array('is_read', $msgColumns)) {
                            $stmt = $db->prepare("INSERT INTO messages (sender_id, receiver_id, subject, content, sent_date, is_read, conversation_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                            $stmt->execute([$sender1, $receiver1, $subject, $message1, $messageDate->format('Y-m-d H:i:s'), 1, $conversationId]);
                        } else {
                            $stmt = $db->prepare("INSERT INTO messages (sender_id, receiver_id, subject, content, sent_date) VALUES (?, ?, ?, ?, ?)");
                            $stmt->execute([$sender1, $receiver1, $subject, $message1, $messageDate->format('Y-m-d H:i:s')]);
                        }
                    } elseif (in_array('created_at', $msgColumns)) {
                        if (in_array('conversation_id', $msgColumns) && in_array('is_read', $msgColumns)) {
                            $stmt = $db->prepare("INSERT INTO messages (sender_id, receiver_id, subject, content, created_at, is_read, conversation_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                            $stmt->execute([$sender1, $receiver1, $subject, $message1, $messageDate->format('Y-m-d H:i:s'), 1, $conversationId]);
                        } else {
                            $stmt = $db->prepare("INSERT INTO messages (sender_id, receiver_id, subject, content, created_at) VALUES (?, ?, ?, ?, ?)");
                            $stmt->execute([$sender1, $receiver1, $subject, $message1, $messageDate->format('Y-m-d H:i:s')]);
                        }
                    } else {
                        // Version basique sans date
                        $stmt = $db->prepare("INSERT INTO messages (sender_id, receiver_id, subject, content) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$sender1, $receiver1, $subject, $message1]);
                    }
                    $messageCount++;
                    
                    // Attendre quelques heures/jours
                    $messageDate->modify('+' . rand(2, 24) . ' hours');
                    
                    // Insérer la réponse avec la même logique
                    if (in_array('sent_date', $msgColumns)) {
                        if (in_array('conversation_id', $msgColumns) && in_array('is_read', $msgColumns)) {
                            $stmt = $db->prepare("INSERT INTO messages (sender_id, receiver_id, subject, content, sent_date, is_read, conversation_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                            $stmt->execute([$sender2, $receiver2, 'Re: ' . $subject, $message2, $messageDate->format('Y-m-d H:i:s'), rand(0, 1), $conversationId]);
                        } else {
                            $stmt = $db->prepare("INSERT INTO messages (sender_id, receiver_id, subject, content, sent_date) VALUES (?, ?, ?, ?, ?)");
                            $stmt->execute([$sender2, $receiver2, 'Re: ' . $subject, $message2, $messageDate->format('Y-m-d H:i:s')]);
                        }
                    } elseif (in_array('created_at', $msgColumns)) {
                        if (in_array('conversation_id', $msgColumns) && in_array('is_read', $msgColumns)) {
                            $stmt = $db->prepare("INSERT INTO messages (sender_id, receiver_id, subject, content, created_at, is_read, conversation_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                            $stmt->execute([$sender2, $receiver2, 'Re: ' . $subject, $message2, $messageDate->format('Y-m-d H:i:s'), rand(0, 1), $conversationId]);
                        } else {
                            $stmt = $db->prepare("INSERT INTO messages (sender_id, receiver_id, subject, content, created_at) VALUES (?, ?, ?, ?, ?)");
                            $stmt->execute([$sender2, $receiver2, 'Re: ' . $subject, $message2, $messageDate->format('Y-m-d H:i:s')]);
                        }
                    } else {
                        // Version basique sans date
                        $stmt = $db->prepare("INSERT INTO messages (sender_id, receiver_id, subject, content) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$sender2, $receiver2, 'Re: ' . $subject, $message2]);
                    }
                    $messageCount++;
                    
                    // Attendre avant le prochain échange
                    $messageDate->modify('+' . rand(1, 3) . ' days');
                }
            }
        }
        
        // === AJOUTER QUELQUES MESSAGES ADMINISTRATIFS ===
        $adminMessages = [
            [
                'subject' => '📢 Rappel - Documents de stage',
                'content' => "Bonjour à tous,\n\nJe vous rappelle que les documents suivants doivent être téléversés avant le début de votre stage :\n\n✅ Convention de stage signée\n✅ Attestation d'assurance\n✅ Fiche d'information entreprise\n\nMerci de respecter ces délais pour éviter tout retard administratif.\n\nCordialement,\nLe service des stages"
            ],
            [
                'subject' => '🎓 Soutenance de stage - Informations importantes',
                'content' => "Chers étudiants,\n\nLes soutenances de stage auront lieu du 15 au 30 septembre.\n\n📋 **À préparer :**\n- Rapport de stage (30-40 pages)\n- Présentation PowerPoint (20 min)\n- Poster récapitulatif (optionnel)\n\n📅 **Calendrier :**\n- Dépôt du rapport : 1 semaine avant la soutenance\n- Durée : 20 min présentation + 10 min questions\n\n💡 **Conseils :**\n- Structurez bien votre présentation\n- Préparez une démo si pertinent\n- Anticipez les questions du jury\n\nBonne préparation !\nLa direction des études"
            ],
            [
                'subject' => '🏆 Félicitations - Évaluation de stage',
                'content' => "Bonjour,\n\nJ'ai le plaisir de vous informer que votre évaluation de stage est excellente !\n\nVotre maître de stage a particulièrement apprécié :\n- Votre professionnalisme\n- Votre capacité d'adaptation\n- La qualité de votre travail\n- Votre esprit d'équipe\n\nCette évaluation positive sera un atout pour votre dossier.\n\nContinuez ainsi !\n\nCordialement,\nVotre tuteur académique"
            ]
        ];
        
        // Envoyer quelques messages admin à des étudiants aléatoires
        foreach ($adminMessages as $adminMsg) {
            $randomStudents = array_rand($studentUserIds, min(5, count($studentUserIds)));
            if (!is_array($randomStudents)) {
                $randomStudents = [$randomStudents];
            }
            
            foreach ($randomStudents as $studentId) {
                // Adapter selon les colonnes disponibles
                if (in_array('sent_date', $msgColumns) && in_array('is_read', $msgColumns)) {
                    $stmt = $db->prepare("INSERT INTO messages (sender_id, receiver_id, subject, content, sent_date, is_read) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $adminId,
                        $studentUserIds[$studentId],
                        $adminMsg['subject'],
                        $adminMsg['content'],
                        date('Y-m-d H:i:s', strtotime('-' . rand(1, 20) . ' days')),
                        rand(0, 1)
                    ]);
                } elseif (in_array('created_at', $msgColumns) && in_array('is_read', $msgColumns)) {
                    $stmt = $db->prepare("INSERT INTO messages (sender_id, receiver_id, subject, content, created_at, is_read) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $adminId,
                        $studentUserIds[$studentId],
                        $adminMsg['subject'],
                        $adminMsg['content'],
                        date('Y-m-d H:i:s', strtotime('-' . rand(1, 20) . ' days')),
                        rand(0, 1)
                    ]);
                } elseif (in_array('sent_date', $msgColumns)) {
                    $stmt = $db->prepare("INSERT INTO messages (sender_id, receiver_id, subject, content, sent_date) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $adminId,
                        $studentUserIds[$studentId],
                        $adminMsg['subject'],
                        $adminMsg['content'],
                        date('Y-m-d H:i:s', strtotime('-' . rand(1, 20) . ' days'))
                    ]);
                } elseif (in_array('created_at', $msgColumns)) {
                    $stmt = $db->prepare("INSERT INTO messages (sender_id, receiver_id, subject, content, created_at) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $adminId,
                        $studentUserIds[$studentId],
                        $adminMsg['subject'],
                        $adminMsg['content'],
                        date('Y-m-d H:i:s', strtotime('-' . rand(1, 20) . ' days'))
                    ]);
                } else {
                    // Version basique
                    $stmt = $db->prepare("INSERT INTO messages (sender_id, receiver_id, subject, content) VALUES (?, ?, ?, ?)");
                    $stmt->execute([
                        $adminId,
                        $studentUserIds[$studentId],
                        $adminMsg['subject'],
                        $adminMsg['content']
                    ]);
                }
                $messageCount++;
            }
        }
        
        logProgress("✅ $messageCount messages créés dans $conversationCount conversations");
        
    } catch (Exception $e) {
        logProgress("⚠️ Messages: " . $e->getMessage());
    }
    
    // === 10. ÉVALUATIONS COMPLÈTES ET INNOVANTES ===
    logProgress("📊 Création des évaluations complètes avec scores détaillés...");
    
    try {
        // Vérifier la structure de la table evaluations
        $stmt = $db->query("DESCRIBE evaluations");
        $evalColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        logProgress("🔍 Colonnes table evaluations: " . implode(", ", $evalColumns));
        
        // Définir les colonnes de base et optionnelles
        $baseColumns = ['assignment_id', 'evaluator_id', 'evaluatee_id', 'type', 'score'];
        $optionalColumns = [
            'feedback' => 'text',
            'strengths' => 'text', 
            'areas_to_improve' => 'text',
            'weaknesses' => 'text',
            'comments' => 'text',
            'notes' => 'text',
            'status' => 'varchar',
            'criteria_scores' => 'json',
            'submission_date' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime'
        ];
        
        // Identifier les colonnes texte disponibles pour le feedback
        $feedbackColumn = null;
        $strengthsColumn = null;
        $improvementsColumn = null;
        
        if (in_array('feedback', $evalColumns)) {
            $feedbackColumn = 'feedback';
        } elseif (in_array('comments', $evalColumns)) {
            $feedbackColumn = 'comments';
        } elseif (in_array('notes', $evalColumns)) {
            $feedbackColumn = 'notes';
        }
        
        if (in_array('strengths', $evalColumns)) {
            $strengthsColumn = 'strengths';
        } elseif (in_array('strong_points', $evalColumns)) {
            $strengthsColumn = 'strong_points';
        }
        
        if (in_array('areas_to_improve', $evalColumns)) {
            $improvementsColumn = 'areas_to_improve';
        } elseif (in_array('weaknesses', $evalColumns)) {
            $improvementsColumn = 'weaknesses';
        } elseif (in_array('improvements', $evalColumns)) {
            $improvementsColumn = 'improvements';
        }
        
        logProgress("🔍 Colonnes mappées - Feedback: " . ($feedbackColumn ?? 'NONE') . 
                   ", Strengths: " . ($strengthsColumn ?? 'NONE') . 
                   ", Improvements: " . ($improvementsColumn ?? 'NONE'));
        
        $evaluationCount = 0;
        
        // === CRITÈRES D'ÉVALUATION DÉTAILLÉS ===
        $criteriaStructure = [
            // Critères techniques
            'technical_mastery' => [
                'name' => 'Maîtrise des technologies',
                'category' => 'technical',
                'description' => 'Capacité à utiliser les technologies et outils liés au stage',
                'weight' => 1.2
            ],
            'work_quality' => [
                'name' => 'Qualité du travail',
                'category' => 'technical', 
                'description' => 'Précision, clarté et fiabilité des livrables produits',
                'weight' => 1.3
            ],
            'problem_solving' => [
                'name' => 'Résolution de problèmes',
                'category' => 'technical',
                'description' => 'Capacité à analyser et résoudre des problèmes techniques',
                'weight' => 1.4
            ],
            'documentation' => [
                'name' => 'Documentation',
                'category' => 'technical',
                'description' => 'Qualité de la documentation produite et des commentaires',
                'weight' => 1.0
            ],
            
            // Critères professionnels
            'autonomy' => [
                'name' => 'Autonomie',
                'category' => 'professional',
                'description' => 'Capacité à travailler de manière indépendante',
                'weight' => 1.2
            ],
            'communication' => [
                'name' => 'Communication',
                'category' => 'professional',
                'description' => 'Clarté et efficacité de la communication écrite et orale',
                'weight' => 1.3
            ],
            'team_integration' => [
                'name' => 'Intégration dans l\'équipe',
                'category' => 'professional',
                'description' => 'Collaboration et interactions avec les membres de l\'équipe',
                'weight' => 1.1
            ],
            'deadline_respect' => [
                'name' => 'Respect des délais',
                'category' => 'professional',
                'description' => 'Ponctualité et respect des échéances fixées',
                'weight' => 1.2
            ],
            
            // Critères personnels
            'initiative' => [
                'name' => 'Prise d\'initiative',
                'category' => 'personal',
                'description' => 'Capacité à proposer des améliorations et innovations',
                'weight' => 1.1
            ],
            'adaptability' => [
                'name' => 'Adaptabilité',
                'category' => 'personal',
                'description' => 'Capacité à s\'adapter aux changements et nouvelles situations',
                'weight' => 1.0
            ]
        ];
        
        // === TYPES D'ÉVALUATIONS ===
        $evaluationTypes = [
            'mid_term' => [
                'name' => 'Évaluation mi-parcours',
                'weight' => 0.4,
                'typical_score_range' => [2.5, 4.2],
                'status_options' => ['draft', 'submitted', 'validated']
            ],
            'final' => [
                'name' => 'Évaluation finale',
                'weight' => 0.6,
                'typical_score_range' => [3.0, 4.8],
                'status_options' => ['submitted', 'validated', 'approved']
            ],
            'student' => [
                'name' => 'Auto-évaluation étudiant',
                'weight' => 0.2,
                'typical_score_range' => [3.2, 4.5],
                'status_options' => ['draft', 'submitted']
            ],
            'company' => [
                'name' => 'Évaluation entreprise',
                'weight' => 0.3,
                'typical_score_range' => [3.0, 4.6],
                'status_options' => ['pending', 'submitted', 'validated']
            ]
        ];
        
        // === DÉPARTEMENTS SPÉCIALISÉS AVEC CRITÈRES SPÉCIFIQUES ===
        $departmentSpecifics = [
            'Informatique' => [
                'bonus_criteria' => ['code_review', 'version_control', 'testing'],
                'common_strengths' => [
                    'Maîtrise des langages de programmation',
                    'Compréhension des architectures logicielles',
                    'Capacité d\'apprentissage des nouvelles technologies',
                    'Résolution créative des problèmes techniques'
                ],
                'common_improvements' => [
                    'Documentation du code plus détaillée',
                    'Tests unitaires plus complets', 
                    'Communication technique avec les non-développeurs',
                    'Estimation des temps de développement'
                ]
            ],
            'Génie Civil' => [
                'bonus_criteria' => ['safety_awareness', 'regulation_compliance', 'site_management'],
                'common_strengths' => [
                    'Respect des normes de sécurité',
                    'Précision dans les calculs techniques',
                    'Compréhension des matériaux et structures',
                    'Vision spatiale et lecture de plans'
                ],
                'common_improvements' => [
                    'Gestion des imprévus sur chantier',
                    'Communication avec les équipes terrain',
                    'Optimisation des coûts',
                    'Veille réglementaire'
                ]
            ],
            'Électronique' => [
                'bonus_criteria' => ['circuit_design', 'signal_processing', 'embedded_systems'],
                'common_strengths' => [
                    'Analyse des circuits électroniques',
                    'Maîtrise des outils de simulation',
                    'Compréhension des protocoles de communication',
                    'Précision dans les mesures'
                ],
                'common_improvements' => [
                    'Optimisation énergétique des systèmes',
                    'Intégration hardware-software',
                    'Documentation technique standardisée',
                    'Débogage des systèmes complexes'
                ]
            ],
            'Mécanique' => [
                'bonus_criteria' => ['cad_mastery', 'manufacturing_process', 'quality_control'],
                'common_strengths' => [
                    'Conception assistée par ordinateur',
                    'Compréhension des procédés de fabrication',
                    'Analyse des contraintes mécaniques',
                    'Respect des tolérances dimensionnelles'
                ],
                'common_improvements' => [
                    'Optimisation des processus de production',
                    'Innovation dans les matériaux',
                    'Maintenance prédictive',
                    'Lean manufacturing'
                ]
            ],
            'Mathématiques' => [
                'bonus_criteria' => ['statistical_analysis', 'modeling', 'data_interpretation'],
                'common_strengths' => [
                    'Modélisation mathématique avancée',
                    'Analyse statistique rigoureuse',
                    'Résolution de problèmes complexes',
                    'Utilisation d\'outils de calcul scientifique'
                ],
                'common_improvements' => [
                    'Vulgarisation des concepts mathématiques',
                    'Applications pratiques des modèles',
                    'Visualisation des données',
                    'Communication des résultats'
                ]
            ]
        ];
        
        // === GÉNÉRER DES ÉVALUATIONS POUR CHAQUE AFFECTATION ===
        foreach ($allAssignments as $assignment) {
            $assignmentId = $assignment['assignment_id'];
            $studentId = $assignment['student_id'];
            $teacherId = $assignment['teacher_id'];
            
            // Récupérer les informations de l'étudiant et du département
            $stmt = $db->prepare("SELECT u.first_name, u.last_name, u.department FROM users u JOIN students s ON u.id = s.user_id WHERE s.id = ?");
            $stmt->execute([$studentId]);
            $studentInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $stmt = $db->prepare("SELECT user_id FROM teachers WHERE id = ?");
            $stmt->execute([$teacherId]);
            $teacherUserId = $stmt->fetchColumn();
            
            $stmt = $db->prepare("SELECT user_id FROM students WHERE id = ?");
            $stmt->execute([$studentId]);
            $studentUserId = $stmt->fetchColumn();
            
            if (!$studentInfo || !$teacherUserId || !$studentUserId) {
                continue;
            }
            
            $department = $studentInfo['department'];
            $deptSpecs = $departmentSpecifics[$department] ?? $departmentSpecifics['Informatique'];
            
            // === CRÉER DIFFÉRENTS TYPES D'ÉVALUATIONS ===
            
            // 1. ÉVALUATION MI-PARCOURS PAR LE TUTEUR
            if (rand(1, 100) <= 85) { // 85% ont une évaluation mi-parcours
                $midTermScores = [];
                $totalWeightedScore = 0;
                $totalWeight = 0;
                
                foreach ($criteriaStructure as $key => $criteria) {
                    // Score basé sur la courbe normale centrée sur 3.5
                    $baseScore = max(1, min(5, rand(250, 450) / 100));
                    
                    // Ajustement selon le département
                    if ($criteria['category'] === 'technical' && in_array($department, ['Informatique', 'Électronique'])) {
                        $baseScore += rand(-20, 30) / 100;
                    }
                    
                    $score = round(max(1, min(5, $baseScore)), 1);
                    $midTermScores[$key] = $score;
                    $totalWeightedScore += $score * $criteria['weight'];
                    $totalWeight += $criteria['weight'];
                }
                
                $overallScore = round($totalWeightedScore / $totalWeight, 1);
                
                $strengths = implode("\n• ", array_slice($deptSpecs['common_strengths'], 0, rand(2, 3)));
                $improvements = implode("\n• ", array_slice($deptSpecs['common_improvements'], 0, rand(2, 3)));
                
                $feedback = "ÉVALUATION MI-PARCOURS - " . $studentInfo['first_name'] . " " . $studentInfo['last_name'] . "\n\n";
                $feedback .= "L'étudiant(e) démontre une progression satisfaisante dans son apprentissage. ";
                $feedback .= ($overallScore >= 4.0) ? "Les compétences techniques se développent de manière très positive. " : 
                            (($overallScore >= 3.0) ? "Les compétences techniques évoluent de façon correcte avec des axes d'amélioration identifiés. " : 
                            "Des efforts supplémentaires sont nécessaires pour atteindre les objectifs. ");
                $feedback .= "L'intégration dans l'équipe est " . (rand(1, 10) > 3 ? "réussie" : "en cours") . ". ";
                $feedback .= "Points spécifiques au département " . $department . " : maîtrise des outils techniques adaptée au niveau attendu.";
                
                $status = $evaluationTypes['mid_term']['status_options'][rand(0, count($evaluationTypes['mid_term']['status_options']) - 1)];
                
                // Construire la requête d'insertion adaptée aux colonnes disponibles
                $insertColumns = ['assignment_id', 'evaluator_id', 'evaluatee_id', 'type', 'score'];
                $insertValues = [$assignmentId, $teacherUserId, $studentUserId, 'mid_term', $overallScore];
                
                // Ajouter les colonnes de feedback si elles existent
                if ($feedbackColumn) {
                    $insertColumns[] = $feedbackColumn;
                    $insertValues[] = $feedback;
                }
                
                if ($strengthsColumn) {
                    $insertColumns[] = $strengthsColumn;
                    $insertValues[] = $strengths;
                }
                
                if ($improvementsColumn) {
                    $insertColumns[] = $improvementsColumn;
                    $insertValues[] = $improvements;
                }
                
                // Ajouter colonnes optionnelles si elles existent
                if (in_array('status', $evalColumns)) {
                    $insertColumns[] = 'status';
                    $insertValues[] = $status;
                }
                
                if (in_array('criteria_scores', $evalColumns)) {
                    $insertColumns[] = 'criteria_scores';
                    $insertValues[] = json_encode($midTermScores);
                }
                
                if (in_array('submission_date', $evalColumns)) {
                    $insertColumns[] = 'submission_date';
                    $insertValues[] = date('Y-m-d H:i:s', strtotime('-' . rand(10, 45) . ' days'));
                } elseif (in_array('created_at', $evalColumns)) {
                    $insertColumns[] = 'created_at';
                    $insertValues[] = date('Y-m-d H:i:s', strtotime('-' . rand(10, 45) . ' days'));
                }
                
                $insertPlaceholders = array_fill(0, count($insertColumns), '?');
                
                $sql = "INSERT INTO evaluations (" . implode(', ', $insertColumns) . ") VALUES (" . implode(', ', $insertPlaceholders) . ")";
                $stmt = $db->prepare($sql);
                $stmt->execute($insertValues);
                $evaluationCount++;
            }
            
            // 2. AUTO-ÉVALUATION DE L'ÉTUDIANT
            if (rand(1, 100) <= 70) { // 70% font une auto-évaluation
                $selfScores = [];
                $totalWeightedScore = 0;
                $totalWeight = 0;
                
                foreach ($criteriaStructure as $key => $criteria) {
                    // Les étudiants s'auto-évaluent généralement un peu plus positivement
                    $baseScore = max(1, min(5, rand(320, 480) / 100));
                    $score = round(max(1, min(5, $baseScore)), 1);
                    $selfScores[$key] = $score;
                    $totalWeightedScore += $score * $criteria['weight'];
                    $totalWeight += $criteria['weight'];
                }
                
                $overallScore = round($totalWeightedScore / $totalWeight, 1);
                
                $feedback = "AUTO-ÉVALUATION - Bilan personnel de mon stage\n\n";
                $feedback .= "Je pense avoir bien progressé durant ce stage. J'ai acquis de nouvelles compétences en " . strtolower($department) . " et j'ai pu contribuer aux projets de l'équipe. ";
                $feedback .= ($overallScore >= 4.0) ? "Je suis satisfait(e) de mes performances et de mon apprentissage. " : 
                            "Je dois encore améliorer certains aspects techniques mais je progresse. ";
                $feedback .= "L'environnement de travail était stimulant et j'ai apprécié l'encadrement reçu.";
                
                $strengths = "Motivation et implication forte\n• " . $deptSpecs['common_strengths'][rand(0, count($deptSpecs['common_strengths']) - 1)];
                $improvements = "Organisation personnelle\n• " . $deptSpecs['common_improvements'][rand(0, count($deptSpecs['common_improvements']) - 1)];
                
                $status = $evaluationTypes['student']['status_options'][rand(0, count($evaluationTypes['student']['status_options']) - 1)];
                
                $insertColumns = ['assignment_id', 'evaluator_id', 'evaluatee_id', 'type', 'score'];
                $insertValues = [$assignmentId, $studentUserId, $studentUserId, 'student', $overallScore];
                
                // Ajouter les colonnes de feedback si elles existent
                if ($feedbackColumn) {
                    $insertColumns[] = $feedbackColumn;
                    $insertValues[] = $feedback;
                }
                
                if ($strengthsColumn) {
                    $insertColumns[] = $strengthsColumn;
                    $insertValues[] = $strengths;
                }
                
                if ($improvementsColumn) {
                    $insertColumns[] = $improvementsColumn;
                    $insertValues[] = $improvements;
                }
                
                if (in_array('status', $evalColumns)) {
                    $insertColumns[] = 'status';
                    $insertValues[] = $status;
                }
                
                if (in_array('criteria_scores', $evalColumns)) {
                    $insertColumns[] = 'criteria_scores';
                    $insertValues[] = json_encode($selfScores);
                }
                
                if (in_array('submission_date', $evalColumns)) {
                    $insertColumns[] = 'submission_date';
                    $insertValues[] = date('Y-m-d H:i:s', strtotime('-' . rand(5, 30) . ' days'));
                } elseif (in_array('created_at', $evalColumns)) {
                    $insertColumns[] = 'created_at';
                    $insertValues[] = date('Y-m-d H:i:s', strtotime('-' . rand(5, 30) . ' days'));
                }
                
                $insertPlaceholders = array_fill(0, count($insertColumns), '?');
                
                $sql = "INSERT INTO evaluations (" . implode(', ', $insertColumns) . ") VALUES (" . implode(', ', $insertPlaceholders) . ")";
                $stmt = $db->prepare($sql);
                $stmt->execute($insertValues);
                $evaluationCount++;
            }
            
            // 3. ÉVALUATION FINALE PAR LE TUTEUR (pour les stages avancés)
            if (rand(1, 100) <= 60) { // 60% ont une évaluation finale
                $finalScores = [];
                $totalWeightedScore = 0;
                $totalWeight = 0;
                
                foreach ($criteriaStructure as $key => $criteria) {
                    // Évaluation finale généralement meilleure que mi-parcours
                    $baseScore = max(1, min(5, rand(300, 480) / 100));
                    $score = round(max(1, min(5, $baseScore)), 1);
                    $finalScores[$key] = $score;
                    $totalWeightedScore += $score * $criteria['weight'];
                    $totalWeight += $criteria['weight'];
                }
                
                $overallScore = round($totalWeightedScore / $totalWeight, 1);
                
                $feedback = "ÉVALUATION FINALE - Bilan complet du stage\n\n";
                $feedback .= $studentInfo['first_name'] . " a réalisé un stage ";
                $feedback .= ($overallScore >= 4.5) ? "exceptionnel" : (($overallScore >= 4.0) ? "très satisfaisant" : (($overallScore >= 3.5) ? "satisfaisant" : "correcte avec des améliorations nécessaires"));
                $feedback .= ". L'évolution depuis le début du stage est ";
                $feedback .= ($overallScore >= 4.0) ? "remarquable" : "positive";
                $feedback .= ". Les objectifs pédagogiques ont été ";
                $feedback .= ($overallScore >= 3.5) ? "largement atteints" : "partiellement atteints";
                $feedback .= ". L'étudiant(e) est prêt(e) pour intégrer une équipe professionnelle en " . $department . ".";
                
                $strengths = implode("\n• ", array_slice($deptSpecs['common_strengths'], 0, 3));
                $improvements = "Points d'amélioration pour la suite :\n• " . implode("\n• ", array_slice($deptSpecs['common_improvements'], 0, 2));
                
                $status = $evaluationTypes['final']['status_options'][rand(0, count($evaluationTypes['final']['status_options']) - 1)];
                
                $insertColumns = ['assignment_id', 'evaluator_id', 'evaluatee_id', 'type', 'score'];
                $insertValues = [$assignmentId, $teacherUserId, $studentUserId, 'final', $overallScore];
                
                // Ajouter les colonnes de feedback si elles existent
                if ($feedbackColumn) {
                    $insertColumns[] = $feedbackColumn;
                    $insertValues[] = $feedback;
                }
                
                if ($strengthsColumn) {
                    $insertColumns[] = $strengthsColumn;
                    $insertValues[] = $strengths;
                }
                
                if ($improvementsColumn) {
                    $insertColumns[] = $improvementsColumn;
                    $insertValues[] = $improvements;
                }
                
                if (in_array('status', $evalColumns)) {
                    $insertColumns[] = 'status';
                    $insertValues[] = $status;
                }
                
                if (in_array('criteria_scores', $evalColumns)) {
                    $insertColumns[] = 'criteria_scores';
                    $insertValues[] = json_encode($finalScores);
                }
                
                if (in_array('submission_date', $evalColumns)) {
                    $insertColumns[] = 'submission_date';
                    $insertValues[] = date('Y-m-d H:i:s', strtotime('-' . rand(1, 15) . ' days'));
                } elseif (in_array('created_at', $evalColumns)) {
                    $insertColumns[] = 'created_at';
                    $insertValues[] = date('Y-m-d H:i:s', strtotime('-' . rand(1, 15) . ' days'));
                }
                
                $insertPlaceholders = array_fill(0, count($insertColumns), '?');
                
                $sql = "INSERT INTO evaluations (" . implode(', ', $insertColumns) . ") VALUES (" . implode(', ', $insertPlaceholders) . ")";
                $stmt = $db->prepare($sql);
                $stmt->execute($insertValues);
                $evaluationCount++;
            }
            
            // 4. ÉVALUATION ENTREPRISE (simulée par admin)
            if (rand(1, 100) <= 45) { // 45% ont une évaluation entreprise
                $companyScores = [];
                $totalWeightedScore = 0;
                $totalWeight = 0;
                
                foreach ($criteriaStructure as $key => $criteria) {
                    // L'entreprise évalue souvent les aspects professionnels plus strictement
                    $baseScore = $criteria['category'] === 'professional' ? 
                                rand(280, 440) / 100 : rand(300, 460) / 100;
                    $score = round(max(1, min(5, $baseScore)), 1);
                    $companyScores[$key] = $score;
                    $totalWeightedScore += $score * $criteria['weight'];
                    $totalWeight += $criteria['weight'];
                }
                
                $overallScore = round($totalWeightedScore / $totalWeight, 1);
                
                $feedback = "ÉVALUATION ENTREPRISE - Retour du maître de stage\n\n";
                $feedback .= "L'étudiant(e) " . $studentInfo['first_name'] . " s'est bien intégré(e) dans notre équipe. ";
                $feedback .= "Son niveau technique est ";
                $feedback .= ($overallScore >= 4.0) ? "très satisfaisant pour un stagiaire" : "correct et en progression";
                $feedback .= ". Nous apprécions sa ";
                $feedback .= ["motivation", "ponctualité", "curiosité", "adaptabilité"][rand(0, 3)];
                $feedback .= ". Les missions confiées ont été ";
                $feedback .= ($overallScore >= 3.5) ? "menées à bien" : "réalisées avec accompagnement";
                $feedback .= ". Nous recommandons cet(te) étudiant(e) pour une future collaboration.";
                
                $strengths = "Vision entreprise :\n• " . implode("\n• ", array_slice([
                    "Respect du cadre professionnel",
                    "Capacité d'écoute et d'apprentissage",
                    "Initiative dans les tâches confiées",
                    "Collaboration avec les équipes"
                ], 0, 2));
                
                $improvements = "Suggestions d'amélioration :\n• " . implode("\n• ", array_slice([
                    "Développer l'assertivité professionnelle",
                    "Améliorer la gestion du stress",
                    "Renforcer la vision business des projets",
                    "Élargir la compréhension des enjeux métier"
                ], 0, 2));
                
                $status = $evaluationTypes['company']['status_options'][rand(0, count($evaluationTypes['company']['status_options']) - 1)];
                
                $insertColumns = ['assignment_id', 'evaluator_id', 'evaluatee_id', 'type', 'score'];
                $insertValues = [$assignmentId, $adminId, $studentUserId, 'company', $overallScore];
                
                // Ajouter les colonnes de feedback si elles existent
                if ($feedbackColumn) {
                    $insertColumns[] = $feedbackColumn;
                    $insertValues[] = $feedback;
                }
                
                if ($strengthsColumn) {
                    $insertColumns[] = $strengthsColumn;
                    $insertValues[] = $strengths;
                }
                
                if ($improvementsColumn) {
                    $insertColumns[] = $improvementsColumn;
                    $insertValues[] = $improvements;
                }
                
                if (in_array('status', $evalColumns)) {
                    $insertColumns[] = 'status';
                    $insertValues[] = $status;
                }
                
                if (in_array('criteria_scores', $evalColumns)) {
                    $insertColumns[] = 'criteria_scores';
                    $insertValues[] = json_encode($companyScores);
                }
                
                if (in_array('submission_date', $evalColumns)) {
                    $insertColumns[] = 'submission_date';
                    $insertValues[] = date('Y-m-d H:i:s', strtotime('-' . rand(1, 20) . ' days'));
                } elseif (in_array('created_at', $evalColumns)) {
                    $insertColumns[] = 'created_at';
                    $insertValues[] = date('Y-m-d H:i:s', strtotime('-' . rand(1, 20) . ' days'));
                }
                
                $insertPlaceholders = array_fill(0, count($insertColumns), '?');
                
                $sql = "INSERT INTO evaluations (" . implode(', ', $insertColumns) . ") VALUES (" . implode(', ', $insertPlaceholders) . ")";
                $stmt = $db->prepare($sql);
                $stmt->execute($insertValues);
                $evaluationCount++;
            }
        }
        
        logProgress("✅ $evaluationCount évaluations créées avec scores détaillés et critères spécialisés");
        
    } catch (Exception $e) {
        logProgress("⚠️ Évaluations: " . $e->getMessage());
    }
    
    // === 11. RÉUNIONS PROFESSIONNELLES ET INNOVANTES ===
    logProgress("🤝 Création des réunions et suivis pédagogiques...");
    
    try {
        $meetingCount = 0;
        $participantCount = 0;
        
        // Vérifier si les tables existent
        $stmt = $db->query("SHOW TABLES LIKE 'meetings'");
        $hasMeetings = $stmt->rowCount() > 0;
        
        $stmt = $db->query("SHOW TABLES LIKE 'meeting_participants'");
        $hasParticipants = $stmt->rowCount() > 0;
        
        if ($hasMeetings) {
            logProgress("📅 Génération des réunions pour chaque affectation...");
            
            // Types de réunions avec contenus spécialisés
            $meetingTypes = [
                'initial_meeting' => [
                    'title_template' => 'Réunion de lancement - {student_name}',
                    'description_template' => 'Première rencontre avec {student_name} pour définir les objectifs du stage en {department}. Points à aborder : présentation du projet, planning, attentes mutuelles, et définition des livrables.',
                    'duration' => 60,
                    'status' => 'completed',
                    'probability' => 95 // 95% des affectations ont cette réunion
                ],
                'progress_review' => [
                    'title_template' => 'Point d\'avancement - {student_name}',
                    'description_template' => 'Suivi hebdomadaire avec {student_name}. Révision des tâches accomplies, difficultés rencontrées, ajustements nécessaires du planning et prochaines étapes.',
                    'duration' => 45,
                    'status' => ['completed', 'completed', 'scheduled'][rand(0, 2)],
                    'probability' => 80
                ],
                'technical_support' => [
                    'title_template' => 'Support technique - {student_name}',
                    'description_template' => 'Session d\'aide technique avec {student_name} sur {technical_topic}. Résolution de blocages, orientation méthodologique et partage d\'expertise.',
                    'duration' => 30,
                    'status' => ['completed', 'scheduled'][rand(0, 1)],
                    'probability' => 60
                ],
                'mid_evaluation' => [
                    'title_template' => 'Évaluation mi-parcours - {student_name}',
                    'description_template' => 'Bilan à mi-parcours avec {student_name}. Évaluation des compétences acquises, feedback sur la progression, et définition des objectifs pour la seconde moitié du stage.',
                    'duration' => 75,
                    'status' => 'completed',
                    'probability' => 90
                ],
                'final_presentation' => [
                    'title_template' => 'Soutenance finale - {student_name}',
                    'description_template' => 'Présentation finale du travail de {student_name}. Démonstration des réalisations, bilan des apprentissages et évaluation finale du stage.',
                    'duration' => 90,
                    'status' => ['scheduled', 'completed'][rand(0, 1)],
                    'probability' => 85
                ],
                'company_visit' => [
                    'title_template' => 'Visite en entreprise - {student_name}',
                    'description_template' => 'Visite de {student_name} sur son lieu de stage. Rencontre avec le maître de stage, observation du contexte professionnel et validation de l\'intégration.',
                    'duration' => 120,
                    'status' => ['completed', 'scheduled'][rand(0, 1)],
                    'probability' => 40
                ],
                'group_workshop' => [
                    'title_template' => 'Atelier collectif - {department}',
                    'description_template' => 'Session de travail en groupe pour les étudiants de {department}. Partage d\'expériences, résolution collaborative de problèmes et enrichissement mutuel.',
                    'duration' => 180,
                    'status' => ['scheduled', 'completed'][rand(0, 1)],
                    'probability' => 25
                ]
            ];
            
            // Sujets techniques par département
            $technicalTopics = [
                'Informatique' => [
                    'architecture microservices', 'optimisation des bases de données', 'sécurité applicative',
                    'DevOps et intégration continue', 'tests automatisés', 'refactoring de code legacy',
                    'performance des applications web', 'migration cloud', 'API RESTful'
                ],
                'Génie Civil' => [
                    'calculs de structures', 'normes de construction', 'gestion de projet BTP',
                    'matériaux innovants', 'techniques de fondation', 'modélisation 3D',
                    'développement durable', 'réglementation thermique', 'pathologies du bâtiment'
                ],
                'Électronique' => [
                    'conception de circuits', 'programmation embarquée', 'traitement du signal',
                    'compatibilité électromagnétique', 'systèmes de communication', 'IoT industriel',
                    'optimisation énergétique', 'capteurs intelligents', 'réseaux de terrain'
                ],
                'Mécanique' => [
                    'conception assistée par ordinateur', 'simulation numérique', 'fabrication additive',
                    'maintenance prédictive', 'automatisation industrielle', 'optimisation des procédés',
                    'contrôle qualité', 'usinage CNC', 'matériaux composites'
                ],
                'Mathématiques' => [
                    'modélisation statistique', 'optimisation numérique', 'analyse de données',
                    'apprentissage automatique', 'recherche opérationnelle', 'simulation Monte Carlo',
                    'théorie des graphes', 'analyse de séries temporelles', 'statistiques bayésiennes'
                ]
            ];
            
            // Générer des réunions pour chaque affectation
            foreach ($allAssignments as $assignment) {
                $assignmentId = $assignment['assignment_id'];
                $studentId = $assignment['student_id'];
                $teacherId = $assignment['teacher_id'];
                
                // Récupérer les informations de l'étudiant et du tuteur
                $stmt = $db->prepare("SELECT u.first_name, u.last_name, u.department FROM users u JOIN students s ON u.id = s.user_id WHERE s.id = ?");
                $stmt->execute([$studentId]);
                $studentInfo = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $stmt = $db->prepare("SELECT u.first_name, u.last_name, user_id FROM users u JOIN teachers t ON u.id = t.user_id WHERE t.id = ?");
                $stmt->execute([$teacherId]);
                $teacherInfo = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$studentInfo || !$teacherInfo) {
                    continue;
                }
                
                $department = $studentInfo['department'];
                $studentName = $studentInfo['first_name'] . ' ' . $studentInfo['last_name'];
                $teacherUserId = $teacherInfo['user_id'];
                
                $stmt = $db->prepare("SELECT user_id FROM students WHERE id = ?");
                $stmt->execute([$studentId]);
                $studentUserId = $stmt->fetchColumn();
                
                // Générer différents types de réunions selon les probabilités
                foreach ($meetingTypes as $type => $config) {
                    if (rand(1, 100) <= $config['probability']) {
                        
                        // Préparer les variables de remplacement
                        $replacements = [
                            '{student_name}' => $studentName,
                            '{department}' => $department,
                            '{technical_topic}' => $technicalTopics[$department][rand(0, count($technicalTopics[$department]) - 1)]
                        ];
                        
                        $title = str_replace(array_keys($replacements), array_values($replacements), $config['title_template']);
                        $description = str_replace(array_keys($replacements), array_values($replacements), $config['description_template']);
                        
                        // Dates intelligentes selon le type
                        $meetingDate = match($type) {
                            'initial_meeting' => date('Y-m-d H:i:s', strtotime('-' . rand(45, 90) . ' days')),
                            'progress_review' => date('Y-m-d H:i:s', strtotime('-' . rand(15, 60) . ' days')),
                            'technical_support' => date('Y-m-d H:i:s', strtotime('-' . rand(5, 45) . ' days')),
                            'mid_evaluation' => date('Y-m-d H:i:s', strtotime('-' . rand(20, 40) . ' days')),
                            'final_presentation' => rand(0, 1) ? date('Y-m-d H:i:s', strtotime('-' . rand(1, 15) . ' days')) : date('Y-m-d H:i:s', strtotime('+' . rand(5, 30) . ' days')),
                            'company_visit' => date('Y-m-d H:i:s', strtotime('-' . rand(10, 50) . ' days')),
                            'group_workshop' => date('Y-m-d H:i:s', strtotime('-' . rand(7, 21) . ' days')),
                            default => date('Y-m-d H:i:s', strtotime('-' . rand(1, 60) . ' days'))
                        };
                        
                        // Lieux réalistes selon le type
                        $location = match($type) {
                            'initial_meeting' => 'Bureau ' . chr(65 + rand(0, 5)) . rand(100, 300),
                            'progress_review' => 'Salle de réunion ' . rand(1, 15),
                            'technical_support' => 'Laboratoire ' . $department,
                            'mid_evaluation' => 'Bureau du tuteur',
                            'final_presentation' => 'Amphithéâtre ' . chr(65 + rand(0, 3)),
                            'company_visit' => 'Entreprise - Site client',
                            'group_workshop' => 'Salle de formation ' . rand(20, 35),
                            default => 'Salle ' . rand(100, 999)
                        };
                        
                        // Notes spécialisées selon le département
                        $notes = match($department) {
                            'Informatique' => 'Prévoir laptop + accès réseau. Documents techniques requis.',
                            'Génie Civil' => 'Plans et documentation technique à apporter. Casque obligatoire si visite chantier.',
                            'Électronique' => 'Matériel de mesure disponible. Schémas électroniques nécessaires.',
                            'Mécanique' => 'Accès atelier selon disponibilité. EPI requis pour démonstrations.',
                            'Mathématiques' => 'Tableau et support de projection disponibles. Données de test fournies.',
                            default => 'Matériel standard disponible sur demande.'
                        };
                        
                        // Détection des colonnes disponibles
                        $stmt = $db->query("DESCRIBE meetings");
                        $meetingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                        
                        // Construction dynamique de la requête
                        $insertColumns = ['title', 'description', 'date', 'duration', 'status', 'location'];
                        $insertValues = [$title, $description, $meetingDate, $config['duration'], $config['status'], $location];
                        
                        // Check for organizer_id or created_by column
                        if (in_array('organizer_id', $meetingColumns)) {
                            $insertColumns[] = 'organizer_id';
                            $insertValues[] = $teacherUserId;
                        } elseif (in_array('created_by', $meetingColumns)) {
                            $insertColumns[] = 'created_by';
                            $insertValues[] = $teacherUserId;
                        }
                        
                        if (in_array('assignment_id', $meetingColumns)) {
                            $insertColumns[] = 'assignment_id';
                            $insertValues[] = $assignmentId;
                        }
                        
                        if (in_array('type', $meetingColumns)) {
                            $insertColumns[] = 'type';
                            $insertValues[] = $type;
                        }
                        
                        if (in_array('notes', $meetingColumns)) {
                            $insertColumns[] = 'notes';
                            $insertValues[] = $notes;
                        }
                        
                        if (in_array('created_at', $meetingColumns)) {
                            $insertColumns[] = 'created_at';
                            $insertValues[] = date('Y-m-d H:i:s');
                        }
                        
                        $placeholders = array_fill(0, count($insertColumns), '?');
                        $sql = "INSERT INTO meetings (" . implode(', ', $insertColumns) . ") VALUES (" . implode(', ', $placeholders) . ")";
                        
                        $stmt = $db->prepare($sql);
                        $stmt->execute($insertValues);
                        $meetingId = $db->lastInsertId();
                        $meetingCount++;
                        
                        // Ajouter les participants si la table existe
                        if ($hasParticipants && $meetingId) {
                            // Vérifier les colonnes disponibles
                            $stmt = $db->query("DESCRIBE meeting_participants");
                            $participantColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                            
                            // Construire la requête adaptative
                            $participantInsertColumns = ['meeting_id', 'user_id'];
                            $participantInsertValues = [$meetingId, $teacherUserId];
                            
                            if (in_array('role', $participantColumns)) {
                                $participantInsertColumns[] = 'role';
                                $participantInsertValues[] = 'organizer';
                            }
                            
                            if (in_array('status', $participantColumns)) {
                                $participantInsertColumns[] = 'status';
                                $participantInsertValues[] = 'accepted';
                            }
                            
                            if (in_array('participant_type', $participantColumns)) {
                                $participantInsertColumns[] = 'participant_type';
                                $participantInsertValues[] = 'organizer';
                            }
                            
                            $participantPlaceholders = array_fill(0, count($participantInsertColumns), '?');
                            $participantSql = "INSERT INTO meeting_participants (" . implode(', ', $participantInsertColumns) . ") VALUES (" . implode(', ', $participantPlaceholders) . ")";
                            
                            // Tuteur (organisateur)
                            $stmt = $db->prepare($participantSql);
                            $stmt->execute($participantInsertValues);
                            $participantCount++;
                            
                            // Étudiant (participant principal)
                            $studentInsertValues = [$meetingId, $studentUserId];
                            $valueIndex = 2;
                            if (in_array('role', $participantColumns)) {
                                $studentInsertValues[] = 'participant';
                                $valueIndex++;
                            }
                            if (in_array('status', $participantColumns)) {
                                $studentInsertValues[] = 'accepted';
                                $valueIndex++;
                            }
                            if (in_array('participant_type', $participantColumns)) {
                                $studentInsertValues[] = 'participant';
                                $valueIndex++;
                            }
                            
                            $stmt->execute($studentInsertValues);
                            $participantCount++;
                            
                            // Pour les ateliers de groupe, ajouter d'autres étudiants du même département
                            if ($type === 'group_workshop' && rand(1, 100) <= 70) {
                                $stmt = $db->prepare("
                                    SELECT DISTINCT s.user_id 
                                    FROM students s 
                                    JOIN users u ON s.user_id = u.id 
                                    WHERE u.department = ? AND s.user_id != ? 
                                    LIMIT " . rand(3, 8)
                                );
                                $stmt->execute([$department, $studentUserId]);
                                $otherStudents = $stmt->fetchAll(PDO::FETCH_COLUMN);
                                
                                foreach ($otherStudents as $otherStudentId) {
                                    $otherInsertValues = [$meetingId, $otherStudentId];
                                    if (in_array('role', $participantColumns)) {
                                        $otherInsertValues[] = 'participant';
                                    }
                                    if (in_array('status', $participantColumns)) {
                                        $otherInsertValues[] = ['accepted', 'pending', 'declined'][rand(0, 2)];
                                    }
                                    if (in_array('participant_type', $participantColumns)) {
                                        $otherInsertValues[] = 'participant';
                                    }
                                    
                                    $stmt = $db->prepare($participantSql);
                                    $stmt->execute($otherInsertValues);
                                    $participantCount++;
                                }
                            }
                        }
                    }
                }
            }
            
            logProgress("✅ $meetingCount réunions créées avec $participantCount participants");
        } else {
            logProgress("⚠️ Table meetings non trouvée - réunions ignorées");
        }
    } catch (Exception $e) {
        logProgress("⚠️ Réunions: " . $e->getMessage());
    }
    
    // === 12. PRÉFÉRENCES ÉTUDIANTS RÉALISTES ===
    logProgress("🎯 Génération des préférences étudiants...");
    
    try {
        $studentPrefCount = 0;
        
        // Vérifier les tables de préférences
        $stmt = $db->query("SHOW TABLES LIKE 'student_preferences'");
        $hasStudentPrefs = $stmt->rowCount() > 0;
        
        $stmt = $db->query("SHOW TABLES LIKE 'student_internship_preferences'");
        $hasInternshipPrefs = $stmt->rowCount() > 0;
        
        if ($hasStudentPrefs) {
            // Préférences par département avec réalisme
            $departmentPreferences = [
                'Informatique' => [
                    'company_types' => ['startup_tech', 'grande_entreprise', 'pme_innovative', 'service_public'],
                    'technical_interests' => ['web_development', 'mobile_apps', 'data_science', 'cybersecurity', 'devops', 'ai_ml'],
                    'work_environments' => ['remote_friendly', 'collaborative', 'autonomous', 'structured'],
                    'project_types' => ['innovation', 'maintenance', 'development', 'research']
                ],
                'Génie Civil' => [
                    'company_types' => ['bureau_etudes', 'entreprise_btp', 'collectivite', 'industrie'],
                    'technical_interests' => ['structural_design', 'project_management', 'sustainable_construction', 'urban_planning'],
                    'work_environments' => ['office_site_mix', 'team_oriented', 'fieldwork', 'technical_office'],
                    'project_types' => ['construction', 'renovation', 'infrastructure', 'environmental']
                ],
                'Électronique' => [
                    'company_types' => ['industrie_electronique', 'telecommunications', 'automobile', 'aeronautique'],
                    'technical_interests' => ['embedded_systems', 'signal_processing', 'iot', 'automation'],
                    'work_environments' => ['laboratory', 'production', 'r_and_d', 'quality_control'],
                    'project_types' => ['conception', 'prototyping', 'testing', 'optimization']
                ],
                'Mécanique' => [
                    'company_types' => ['industrie_manufacturiere', 'automobile', 'aeronautique', 'energie'],
                    'technical_interests' => ['cad_design', 'manufacturing', 'automation', 'maintenance'],
                    'work_environments' => ['workshop', 'design_office', 'production_line', 'laboratory'],
                    'project_types' => ['design', 'manufacturing', 'improvement', 'maintenance']
                ],
                'Mathématiques' => [
                    'company_types' => ['finance', 'consulting', 'research', 'tech_company'],
                    'technical_interests' => ['data_analysis', 'statistical_modeling', 'optimization', 'machine_learning'],
                    'work_environments' => ['analytical', 'research_oriented', 'collaborative', 'independent'],
                    'project_types' => ['analysis', 'modeling', 'optimization', 'research']
                ]
            ];
            
            foreach ($studentUserIds as $studentUserId) {
                // Récupérer l'ID étudiant, le département et l'internship_id s'il existe
                $stmt = $db->prepare("
                    SELECT u.department, s.id as student_id, a.internship_id 
                    FROM users u 
                    JOIN students s ON u.id = s.user_id 
                    LEFT JOIN assignments a ON s.id = a.student_id
                    WHERE u.id = ?
                ");
                $stmt->execute([$studentUserId]);
                $studentData = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$studentData || !isset($departmentPreferences[$studentData['department']])) {
                    continue;
                }
                
                // Si la table student_preferences exige un internship_id et qu'on n'en a pas, ignorer cet étudiant
                $stmt = $db->query("DESCRIBE student_preferences");
                $prefColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                if (in_array('internship_id', $prefColumns)) {
                    // Vérifier si la colonne internship_id est NOT NULL
                    $stmt = $db->query("SHOW COLUMNS FROM student_preferences WHERE Field = 'internship_id'");
                    $columnInfo = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($columnInfo && $columnInfo['Null'] === 'NO' && (!$studentData['internship_id'] || !is_numeric($studentData['internship_id']))) {
                        // Cette table exige un internship_id et on n'en a pas de valide, ignorer
                        continue;
                    }
                }
                
                $department = $studentData['department'];
                $studentId = $studentData['student_id'];
                $internshipId = $studentData['internship_id'];
                $deptPrefs = $departmentPreferences[$department];
                
                // Générer des préférences cohérentes
                $preferences = [
                    'company_type' => $deptPrefs['company_types'][rand(0, count($deptPrefs['company_types']) - 1)],
                    'technical_interest' => $deptPrefs['technical_interests'][rand(0, count($deptPrefs['technical_interests']) - 1)],
                    'work_environment' => $deptPrefs['work_environments'][rand(0, count($deptPrefs['work_environments']) - 1)],
                    'project_type' => $deptPrefs['project_types'][rand(0, count($deptPrefs['project_types']) - 1)],
                    'location_preference' => ['local', 'regional', 'national', 'international'][rand(0, 3)],
                    'duration_preference' => [8, 12, 16, 20, 24][rand(0, 4)], // semaines
                    'salary_expectation' => rand(800, 1500), // euros/mois
                    'transport_mode' => ['public_transport', 'personal_vehicle', 'walking', 'cycling'][rand(0, 3)],
                    'schedule_preference' => ['standard', 'flexible', 'early_bird', 'afternoon'][rand(0, 3)]
                ];
                
                // Construction adaptative de la requête (prefColumns déjà récupérées plus haut)
                $insertColumns = ['student_id'];
                $insertValues = [$studentId];
                
                foreach ($preferences as $key => $value) {
                    if (in_array($key, $prefColumns)) {
                        $insertColumns[] = $key;
                        $insertValues[] = is_string($value) ? $value : $value;
                    }
                }
                
                // Ajouter l'internship_id si la colonne existe et qu'on a un internship_id valide
                if (in_array('internship_id', $prefColumns) && $internshipId && is_numeric($internshipId) && $internshipId > 0) {
                    // Vérifier que l'internship existe réellement
                    $checkStmt = $db->prepare("SELECT COUNT(*) FROM internships WHERE id = ?");
                    $checkStmt->execute([$internshipId]);
                    if ($checkStmt->fetchColumn() > 0) {
                        $insertColumns[] = 'internship_id';
                        $insertValues[] = $internshipId;
                    }
                }
                
                // Ajouter des colonnes communes si elles existent
                if (in_array('preferences_json', $prefColumns)) {
                    $insertColumns[] = 'preferences_json';
                    $insertValues[] = json_encode($preferences);
                }
                
                if (in_array('created_at', $prefColumns)) {
                    $insertColumns[] = 'created_at';
                    $insertValues[] = date('Y-m-d H:i:s');
                }
                
                if (in_array('priority_level', $prefColumns)) {
                    $insertColumns[] = 'priority_level';
                    $insertValues[] = rand(1, 5);
                }
                
                $placeholders = array_fill(0, count($insertColumns), '?');
                $sql = "INSERT INTO student_preferences (" . implode(', ', $insertColumns) . ") VALUES (" . implode(', ', $placeholders) . ")";
                
                $stmt = $db->prepare($sql);
                $stmt->execute($insertValues);
                $studentPrefCount++;
            }
            
            logProgress("✅ $studentPrefCount préférences étudiants créées");
        } else {
            logProgress("⚠️ Table student_preferences non trouvée");
        }
    } catch (Exception $e) {
        logProgress("⚠️ Préférences étudiants: " . $e->getMessage());
    }
    
    // === 13. PRÉFÉRENCES TUTEURS PROFESSIONNELLES ===
    logProgress("👨‍🏫 Génération des préférences tuteurs...");
    
    try {
        $teacherPrefCount = 0;
        
        $stmt = $db->query("SHOW TABLES LIKE 'teacher_preferences'");
        $hasTeacherPrefs = $stmt->rowCount() > 0;
        
        if ($hasTeacherPrefs) {
            foreach ($tutorUserIds as $tutorUserId) {
                // Récupérer l'ID teacher et le département
                $stmt = $db->prepare("SELECT u.department, t.id as teacher_id FROM users u JOIN teachers t ON u.id = t.user_id WHERE u.id = ?");
                $stmt->execute([$tutorUserId]);
                $teacherData = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$teacherData) {
                    continue;
                }
                
                $department = $teacherData['department'];
                $teacherId = $teacherData['teacher_id'];
                
                // Préférences professionnelles du tuteur
                $teacherPreferences = [
                    'max_students' => rand(3, 8), // Nombre max d'étudiants encadrés
                    'experience_level' => ['beginner', 'intermediate', 'advanced'][rand(0, 2)],
                    'supervision_style' => ['hands_on', 'autonomous', 'collaborative', 'structured'][rand(0, 3)],
                    'meeting_frequency' => ['weekly', 'biweekly', 'monthly', 'on_demand'][rand(0, 3)],
                    'feedback_style' => ['detailed', 'concise', 'constructive', 'encouraging'][rand(0, 3)],
                    'availability_days' => json_encode(array_slice(['monday', 'tuesday', 'wednesday', 'thursday', 'friday'], 0, rand(3, 5))),
                    'preferred_communication' => ['email', 'video_call', 'in_person', 'chat'][rand(0, 3)],
                    'specialization_areas' => match($department) {
                        'Informatique' => json_encode(array_slice(['web_dev', 'mobile', 'data_science', 'ai', 'cybersecurity'], 0, rand(2, 3))),
                        'Génie Civil' => json_encode(array_slice(['structures', 'btp', 'urban_planning', 'environment'], 0, rand(2, 3))),
                        'Électronique' => json_encode(array_slice(['embedded', 'telecom', 'automation', 'iot'], 0, rand(2, 3))),
                        'Mécanique' => json_encode(array_slice(['design', 'manufacturing', 'maintenance', 'automation'], 0, rand(2, 3))),
                        'Mathématiques' => json_encode(array_slice(['statistics', 'modeling', 'optimization', 'data_analysis'], 0, rand(2, 3))),
                        default => json_encode(['general'])
                    }
                ];
                
                // Construction adaptative
                $stmt = $db->query("DESCRIBE teacher_preferences");
                $teacherPrefColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                $insertColumns = ['teacher_id'];
                $insertValues = [$teacherId];
                
                foreach ($teacherPreferences as $key => $value) {
                    if (in_array($key, $teacherPrefColumns)) {
                        $insertColumns[] = $key;
                        $insertValues[] = $value;
                    }
                }
                
                if (in_array('preferences_json', $teacherPrefColumns)) {
                    $insertColumns[] = 'preferences_json';
                    $insertValues[] = json_encode($teacherPreferences);
                }
                
                if (in_array('created_at', $teacherPrefColumns)) {
                    $insertColumns[] = 'created_at';
                    $insertValues[] = date('Y-m-d H:i:s');
                }
                
                $placeholders = array_fill(0, count($insertColumns), '?');
                $sql = "INSERT INTO teacher_preferences (" . implode(', ', $insertColumns) . ") VALUES (" . implode(', ', $placeholders) . ")";
                
                $stmt = $db->prepare($sql);
                $stmt->execute($insertValues);
                $teacherPrefCount++;
            }
            
            logProgress("✅ $teacherPrefCount préférences tuteurs créées");
        } else {
            logProgress("⚠️ Table teacher_preferences non trouvée");
        }
    } catch (Exception $e) {
        logProgress("⚠️ Préférences tuteurs: " . $e->getMessage());
    }
    
    // === 14. PARAMÈTRES D'ALGORITHMES AVANCÉS ===
    logProgress("⚙️ Génération des paramètres d'algorithmes d'affectation...");
    
    try {
        $algorithmCount = 0;
        
        $stmt = $db->query("SHOW TABLES LIKE 'algorithm_parameters'");
        $hasAlgorithmParams = $stmt->rowCount() > 0;
        
        if ($hasAlgorithmParams) {
            // Configurations d'algorithmes professionnelles
            $algorithmConfigs = [
                [
                    'name' => 'Affectation Équilibrée Département',
                    'description' => 'Algorithme optimisant la répartition par département avec pondération des compétences techniques',
                    'algorithm_type' => 'weighted_matching',
                    'department_weight' => 70,
                    'preference_weight' => 20,
                    'capacity_weight' => 10,
                    'allow_cross_department' => 0,
                    'prioritize_preferences' => 1,
                    'balance_workload' => 1
                ],
                [
                    'name' => 'Optimisation Préférences Étudiants',
                    'description' => 'Maximise la satisfaction des préférences étudiants tout en respectant les contraintes pédagogiques',
                    'algorithm_type' => 'preference_optimizer',
                    'department_weight' => 30,
                    'preference_weight' => 60,
                    'capacity_weight' => 10,
                    'allow_cross_department' => 1,
                    'prioritize_preferences' => 1,
                    'balance_workload' => 0
                ],
                [
                    'name' => 'Équilibrage Charge Tuteurs',
                    'description' => 'Répartit équitablement la charge de travail entre tuteurs selon leurs capacités et spécialités',
                    'algorithm_type' => 'load_balancer',
                    'department_weight' => 40,
                    'preference_weight' => 15,
                    'capacity_weight' => 45,
                    'allow_cross_department' => 0,
                    'prioritize_preferences' => 0,
                    'balance_workload' => 1
                ],
                [
                    'name' => 'Hybride Adaptatif Intelligent',
                    'description' => 'Combinaison dynamique de plusieurs algorithmes selon les contraintes du semestre',
                    'algorithm_type' => 'adaptive_hybrid',
                    'department_weight' => 50,
                    'preference_weight' => 35,
                    'capacity_weight' => 15,
                    'allow_cross_department' => 1,
                    'prioritize_preferences' => 1,
                    'balance_workload' => 1
                ],
                [
                    'name' => 'Spécialisation Technique',
                    'description' => 'Privilégie l\'adéquation entre spécialités techniques des tuteurs et besoins des projets',
                    'algorithm_type' => 'skill_matching',
                    'department_weight' => 80,
                    'preference_weight' => 10,
                    'capacity_weight' => 10,
                    'allow_cross_department' => 0,
                    'prioritize_preferences' => 0,
                    'balance_workload' => 0
                ],
                [
                    'name' => 'Machine Learning Prédictif',
                    'description' => 'Utilise l\'historique des affectations réussies pour optimiser les nouvelles assignations',
                    'algorithm_type' => 'ml_predictive',
                    'department_weight' => 45,
                    'preference_weight' => 40,
                    'capacity_weight' => 15,
                    'allow_cross_department' => 1,
                    'prioritize_preferences' => 1,
                    'balance_workload' => 1
                ]
            ];
            
            foreach ($algorithmConfigs as $config) {
                // Vérifier les colonnes disponibles
                $stmt = $db->query("DESCRIBE algorithm_parameters");
                $algoColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                $insertColumns = [];
                $insertValues = [];
                
                foreach ($config as $key => $value) {
                    if (in_array($key, $algoColumns)) {
                        $insertColumns[] = $key;
                        $insertValues[] = $value;
                    }
                }
                
                if (in_array('created_at', $algoColumns)) {
                    $insertColumns[] = 'created_at';
                    $insertValues[] = date('Y-m-d H:i:s');
                }
                
                if (in_array('is_default', $algoColumns)) {
                    $insertColumns[] = 'is_default';
                    $insertValues[] = $algorithmCount === 0 ? 1 : 0; // Premier comme défaut
                }
                
                $placeholders = array_fill(0, count($insertColumns), '?');
                $sql = "INSERT INTO algorithm_parameters (" . implode(', ', $insertColumns) . ") VALUES (" . implode(', ', $placeholders) . ")";
                
                $stmt = $db->prepare($sql);
                $stmt->execute($insertValues);
                $algorithmCount++;
            }
            
            logProgress("✅ $algorithmCount configurations d'algorithmes créées");
        } else {
            logProgress("⚠️ Table algorithm_parameters non trouvée");
        }
    } catch (Exception $e) {
        logProgress("⚠️ Paramètres algorithmes: " . $e->getMessage());
    }
    
    // Valider toutes les transactions
    $db->commit();
    
    // === RAPPORT FINAL ===
    echo "<div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 15px; margin: 30px 0;'>";
    echo "<h2 style='margin: 0 0 20px 0; font-size: 2.5em;'>🎉 INSTALLATION COMPLÈTE TERMINÉE !</h2>";
    echo "</div>";
    
    echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin: 20px 0;'>";
    
    // Statistiques Utilisateurs
    echo "<div style='background: #e8f5e8; padding: 20px; border-radius: 10px; border-left: 5px solid #28a745;'>";
    echo "<h3 style='color: #28a745; margin-top: 0;'>👥 Utilisateurs Créés</h3>";
    echo "<ul style='list-style: none; padding: 0;'>";
    echo "<li>📊 <strong>1 Administrateur</strong></li>";
    echo "<li>🎓 <strong>52 Tuteurs</strong> (répartis par département)</li>";
    echo "<li>🎒 <strong>205 Étudiants</strong> (répartis par département)</li>";
    echo "<li>📈 <strong>Total: 258 utilisateurs</strong></li>";
    echo "</ul>";
    echo "</div>";
    
    // Statistiques Académiques
    echo "<div style='background: #e3f2fd; padding: 20px; border-radius: 10px; border-left: 5px solid #2196f3;'>";
    echo "<h3 style='color: #2196f3; margin-top: 0;'>🏫 Données Académiques</h3>";
    echo "<ul style='list-style: none; padding: 0;'>";
    echo "<li>🏢 <strong>" . count($companyIds) . " Entreprises</strong> spécialisées</li>";
    echo "<li>💼 <strong>$internshipCount Stages</strong> cohérents</li>";
    echo "<li>📋 <strong>$assignmentCount Affectations</strong> par département</li>";
    echo "<li>📊 <strong>$evaluationCount Évaluations</strong> pédagogiques</li>";
    echo "</ul>";
    echo "</div>";
    
    // Statistiques Techniques
    echo "<div style='background: #fff3e0; padding: 20px; border-radius: 10px; border-left: 5px solid #ff9800;'>";
    echo "<h3 style='color: #ff9800; margin-top: 0;'>📁 Fichiers & Communication</h3>";
    echo "<ul style='list-style: none; padding: 0;'>";
    echo "<li>📄 <strong>$documentCount Documents</strong> (.md et .txt)</li>";
    echo "<li>💬 <strong>$messageCount Messages</strong> échangés</li>";
    echo "<li>📂 <strong>Dossier:</strong> uploads/documents/</li>";
    echo "<li>📊 <strong>3 Évaluations</strong> par étudiant affecté</li>";
    echo "<li>🔐 <strong>Connexion:</strong> Username / 12345678</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "</div>";
    
    // Répartition par département
    echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h3 style='color: #495057;'>📊 Répartition par Département</h3>";
    echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;'>";
    
    foreach ($departments as $dept => $config) {
        echo "<div style='background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>";
        echo "<h4 style='margin: 0 0 10px 0; color: #343a40;'>$dept</h4>";
        echo "<p style='margin: 5px 0; font-size: 0.9em;'><strong>Tuteurs:</strong> {$config['tutors']}</p>";
        echo "<p style='margin: 5px 0; font-size: 0.9em;'><strong>Étudiants:</strong> {$config['students']}</p>";
        echo "<p style='margin: 5px 0; font-size: 0.9em;'><strong>Stages:</strong> " . count($internshipsByDept[$dept]) . "</p>";
        echo "</div>";
    }
    
    echo "</div></div>";
    
    // Comptes de test
    echo "<div style='background: #2c3e50; color: white; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h3 style='color: white; margin-top: 0;'>🔑 Comptes de Test - CONNEXION PAR USERNAME</h3>";
    echo "<div style='background: rgba(255,255,255,0.1); padding: 15px; border-radius: 8px;'>";
    echo "<p style='color: #f39c12; font-weight: bold;'>⚠️ IMPORTANT: Utilisez le USERNAME pour vous connecter (pas l'email)</p>";
    echo "<p><strong>👑 Admin:</strong> Username: <code>admin</code> / Mot de passe: <code>12345678</code></p>";
    echo "<p><strong>🎓 Tuteur Info:</strong> Username: <code>thomas.martin</code> / Mot de passe: <code>12345678</code></p>";
    echo "<p><strong>🎓 Tuteur Génie Civil:</strong> Username: <code>vincent.girard</code> / Mot de passe: <code>12345678</code></p>";
    echo "<p><strong>🎒 Étudiant Info:</strong> Username: <code>alexandre</code> / Mot de passe: <code>12345678</code></p>";
    echo "<p><strong>🎒 Étudiant Génie Civil:</strong> Username: <code>lucas</code> / Mot de passe: <code>12345678</code></p>";
    echo "<p style='font-size: 0.9em; color: #bdc3c7;'>Tous les comptes utilisent le mot de passe: <strong>12345678</strong></p>";
    echo "</div>";
    echo "</div>";
    
    // Prochaines étapes
    echo "<div style='background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h3 style='color: white; margin-top: 0;'>🚀 Prochaines Étapes</h3>";
    echo "<ol style='font-size: 1.1em; line-height: 1.6;'>";
    echo "<li><strong>Connectez-vous</strong> avec un compte tuteur pour vérifier les affectations multiples</li>";
    echo "<li><strong>Testez les départements</strong> - un tuteur en Informatique ne supervise que des étudiants en Informatique</li>";
    echo "<li><strong>Vérifiez les corrections LEFT JOIN</strong> - tous les étudiants d'un tuteur doivent s'afficher</li>";
    echo "<li><strong>Explorez les évaluations et documents</strong> - données réalistes et cohérentes</li>";
    echo "<li><strong>Testez la messagerie</strong> - communications entre tuteurs et étudiants</li>";
    echo "</ol>";
    echo "</div>";
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo "<div class='alert alert-danger'>";
    echo "<h4><i class='bi bi-exclamation-triangle me-2'></i>Erreur durant la génération</h4>";
    echo "<p><strong>Message:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Ligne:</strong> " . $e->getLine() . "</p>";
    echo "<p><strong>Fichier:</strong> " . $e->getFile() . "</p>";
    echo "<details class='mt-3'>";
    echo "<summary>Détails de l'erreur</summary>";
    echo "<pre class='mt-2'>" . $e->getTraceAsString() . "</pre>";
    echo "</details>";
    echo "</div>";
}
?>
                </div>
                
                <div class="text-center mt-4">
                    <a href="index.php" class="btn btn-gradient me-3">
                        <i class="bi bi-house-door me-2"></i>Aller au système
                    </a>
                    <a href="install.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Retour à l'installation
                    </a>
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
                setInterval(function() {
                    logContainer.scrollTop = logContainer.scrollHeight;
                }, 1000);
            }
        });
    </script>
</body>
</html>