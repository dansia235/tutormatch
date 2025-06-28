<?php
/**
 * Script de réinitialisation et reconstruction de la base de données
 * - Supprime tous les emplois existants
 * - Exécute le script de génération des emplois
 * - Analyse, trie, filtre et catégorise les emplois
 * - Les ajoute dans la base de données pour chargement via API
 */

require_once __DIR__ . '/../config/database.php';

// Configuration
set_time_limit(300); // 5 minutes max
ini_set('memory_limit', '256M');

// Fonction de logging
function logMessage($message) {
    $timestamp = date('[Y-m-d H:i:s]');
    echo "<div class='log-entry'>{$timestamp} {$message}</div>\n";
    ob_flush();
    flush();
}

// Fonction de nettoyage de la base de données
function cleanDatabase($pdo) {
    logMessage("🗑️ Début du nettoyage de la base de données...");
    
    try {
        $pdo->beginTransaction();
        
        // Désactiver les contraintes de clés étrangères temporairement
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        
        // Supprimer les données liées aux emplois dans l'ordre des dépendances
        $tables = [
            'internship_skills',
            'assignments', 
            'student_internship_preferences',
            'internships'
        ];
        
        foreach ($tables as $table) {
            $count = $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
            if ($count > 0) {
                $pdo->exec("DELETE FROM {$table}");
                logMessage("✅ Table '{$table}': {$count} enregistrements supprimés");
            } else {
                logMessage("ℹ️ Table '{$table}': déjà vide");
            }
        }
        
        // Réinitialiser les compteurs auto-increment
        foreach ($tables as $table) {
            $pdo->exec("ALTER TABLE {$table} AUTO_INCREMENT = 1");
        }
        
        // Réactiver les contraintes de clés étrangères
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        
        $pdo->commit();
        logMessage("✅ Nettoyage de la base de données terminé avec succès");
        
    } catch (Exception $e) {
        $pdo->rollBack();
        logMessage("❌ Erreur lors du nettoyage: " . $e->getMessage());
        throw $e;
    }
}

// Fonction de génération des emplois
function generateInternships($pdo) {
    logMessage("🏭 Début de la génération des emplois...");
    
    try {
        // Vérifier que nous avons des entreprises
        $companyCount = $pdo->query("SELECT COUNT(*) FROM companies")->fetchColumn();
        if ($companyCount == 0) {
            logMessage("⚠️ Aucune entreprise trouvée. Génération d'entreprises de base...");
            generateBasicCompanies($pdo);
        }
        
        // Configuration des domaines et des types d'emplois
        $domains = [
            'Informatique' => [
                'skills' => ['PHP', 'JavaScript', 'Python', 'Java', 'SQL', 'React', 'Node.js', 'Docker'],
                'types' => ['Développeur Web', 'Développeur Mobile', 'DevOps', 'Data Analyst', 'Cybersécurité']
            ],
            'Génie Civil' => [
                'skills' => ['AutoCAD', 'Béton armé', 'Gestion de projet', 'BIM', 'Géotechnique'],
                'types' => ['Assistant ingénieur', 'Chargé d\'études', 'Contrôleur technique', 'Chef de chantier']
            ],
            'Électronique' => [
                'skills' => ['Circuit électronique', 'Microcontrôleurs', 'PCB Design', 'VHDL', 'Matlab'],
                'types' => ['Technicien électronique', 'Ingénieur R&D', 'Testeur hardware', 'Designer PCB']
            ],
            'Mécanique' => [
                'skills' => ['SolidWorks', 'CAO', 'Usinage', 'Thermodynamique', 'Matériaux'],
                'types' => ['Dessinateur industriel', 'Technicien maintenance', 'Ingénieur produit', 'Qualité']
            ],
            'Mathématiques' => [
                'skills' => ['Statistiques', 'R', 'MATLAB', 'Machine Learning', 'Modélisation'],
                'types' => ['Analyste quantitatif', 'Data Scientist', 'Actuaire', 'Chercheur']
            ]
        ];
        
        $locations = ['Paris', 'Lyon', 'Toulouse', 'Marseille', 'Bordeaux', 'Nantes', 'Strasbourg', 'Lille', 'Rennes', 'Montpellier'];
        $workModes = ['on_site', 'remote', 'hybrid'];
        $statuses = ['available'];
        
        $companies = $pdo->query("SELECT id, name FROM companies ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
        
        $internshipCount = 0;
        $targetCount = 315; // Comme dans le script original
        
        logMessage("🎯 Objectif: {$targetCount} emplois à générer");
        
        $pdo->beginTransaction();
        
        while ($internshipCount < $targetCount) {
            foreach ($domains as $domain => $config) {
                if ($internshipCount >= $targetCount) break;
                
                foreach ($config['types'] as $jobType) {
                    if ($internshipCount >= $targetCount) break;
                    
                    $company = $companies[array_rand($companies)];
                    $location = $locations[array_rand($locations)];
                    $workMode = $workModes[array_rand($workModes)];
                    $status = $statuses[array_rand($statuses)];
                    
                    // Générer dates de stage
                    $startDate = date('Y-m-d', strtotime('+' . rand(1, 90) . ' days'));
                    $endDate = date('Y-m-d', strtotime($startDate . ' +' . rand(2, 6) . ' months'));
                    
                    // Générer titre et description
                    $seniority = rand(0, 1) ? 'Junior' : 'Senior';
                    $title = "Stage {$jobType} {$seniority} - {$company['name']}";
                    
                    $description = "Rejoignez notre équipe en tant que {$jobType} {$seniority} dans le domaine {$domain}. " .
                                 "Vous travaillerez sur des projets innovants et développerez vos compétences techniques. " .
                                 "Environnement dynamique et formateur avec encadrement professionnel.";
                    
                    // Générer exigences
                    $selectedSkills = array_rand(array_flip($config['skills']), min(rand(3, 5), count($config['skills'])));
                    if (!is_array($selectedSkills)) $selectedSkills = [$selectedSkills];
                    $requirements = "Compétences requises: " . implode(', ', $selectedSkills) . ". " .
                                  "Formation en cours dans le domaine {$domain}. Motivation et esprit d'équipe.";
                    
                    // Compensation
                    $compensation = rand(400, 1200) + (rand(0, 99) / 100);
                    
                    // Insérer l'emploi
                    $stmt = $pdo->prepare("
                        INSERT INTO internships (title, company_id, start_date, end_date, description, requirements, 
                                               location, work_mode, compensation, domain, status, created_at, updated_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                    ");
                    
                    $stmt->execute([
                        $title, $company['id'], $startDate, $endDate, $description, 
                        $requirements, $location, $workMode, $compensation, $domain, $status
                    ]);
                    
                    $internshipId = $pdo->lastInsertId();
                    
                    // Ajouter les compétences
                    foreach ($selectedSkills as $skill) {
                        $skillStmt = $pdo->prepare("INSERT INTO internship_skills (internship_id, skill_name) VALUES (?, ?)");
                        $skillStmt->execute([$internshipId, $skill]);
                    }
                    
                    $internshipCount++;
                    
                    if ($internshipCount % 50 == 0) {
                        logMessage("📊 Progression: {$internshipCount}/{$targetCount} emplois générés");
                    }
                }
            }
        }
        
        $pdo->commit();
        logMessage("✅ Génération terminée: {$internshipCount} emplois créés");
        
    } catch (Exception $e) {
        $pdo->rollBack();
        logMessage("❌ Erreur lors de la génération: " . $e->getMessage());
        throw $e;
    }
}

// Fonction pour générer des entreprises de base si nécessaire
function generateBasicCompanies($pdo) {
    $companies = [
        ['TechCorp Solutions', 'Développement logiciel et conseil en IT', 'Paris'],
        ['InnovateLab', 'Laboratoire de recherche et développement', 'Lyon'],
        ['BuildPro Engineering', 'Bureau d\'études en génie civil', 'Toulouse'],
        ['ElectroSystems', 'Conception de systèmes électroniques', 'Marseille'],
        ['MechanDesign', 'Design et fabrication mécanique', 'Bordeaux'],
        ['DataInsights Analytics', 'Analyse de données et BI', 'Nantes'],
        ['CloudMasters', 'Services cloud et infrastructure', 'Strasbourg'],
        ['GreenTech Industries', 'Technologies environnementales', 'Lille'],
        ['SmartFactory', 'Automatisation industrielle', 'Rennes'],
        ['FinTech Solutions', 'Solutions financières innovantes', 'Montpellier']
    ];
    
    foreach ($companies as $company) {
        $stmt = $pdo->prepare("INSERT INTO companies (name, description, location, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
        $stmt->execute($company);
    }
    
    logMessage("✅ " . count($companies) . " entreprises de base créées");
}

// Fonction d'analyse et catégorisation
function analyzeAndCategorizeJobs($pdo) {
    logMessage("🔍 Début de l'analyse et catégorisation des emplois...");
    
    try {
        // Statistiques par domaine
        $stmt = $pdo->query("
            SELECT domain, COUNT(*) as count, 
                   AVG(compensation) as avg_compensation,
                   MIN(compensation) as min_compensation,
                   MAX(compensation) as max_compensation
            FROM internships 
            GROUP BY domain 
            ORDER BY count DESC
        ");
        
        $domainStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        logMessage("📊 Analyse par domaine:");
        foreach ($domainStats as $stat) {
            logMessage("   • {$stat['domain']}: {$stat['count']} emplois (compensation: {$stat['min_compensation']}€-{$stat['max_compensation']}€, moy: " . round($stat['avg_compensation'], 2) . "€)");
        }
        
        // Analyse des compétences
        $skillStats = $pdo->query("
            SELECT skill_name, COUNT(*) as count 
            FROM internship_skills 
            GROUP BY skill_name 
            ORDER BY count DESC 
            LIMIT 10
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        logMessage("🏆 Top 10 des compétences recherchées:");
        foreach ($skillStats as $skill) {
            logMessage("   • {$skill['skill_name']}: {$skill['count']} fois");
        }
        
        // Analyse géographique
        $locationStats = $pdo->query("
            SELECT location, COUNT(*) as count 
            FROM internships 
            GROUP BY location 
            ORDER BY count DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        logMessage("🌍 Répartition géographique:");
        foreach ($locationStats as $location) {
            logMessage("   • {$location['location']}: {$location['count']} emplois");
        }
        
        logMessage("✅ Analyse et catégorisation terminées");
        
    } catch (Exception $e) {
        logMessage("❌ Erreur lors de l'analyse: " . $e->getMessage());
        throw $e;
    }
}

// Fonction de vérification finale
function finalVerification($pdo) {
    logMessage("🔍 Vérification finale du système...");
    
    try {
        // Compter les emplois par statut
        $statusCount = $pdo->query("
            SELECT status, COUNT(*) as count 
            FROM internships 
            GROUP BY status
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($statusCount as $status) {
            logMessage("   • Statut '{$status['status']}': {$status['count']} emplois");
        }
        
        // Vérifier l'intégrité des relations
        $orphanSkills = $pdo->query("
            SELECT COUNT(*) as count 
            FROM internship_skills s 
            LEFT JOIN internships i ON s.internship_id = i.id 
            WHERE i.id IS NULL
        ")->fetchColumn();
        
        if ($orphanSkills > 0) {
            logMessage("⚠️ {$orphanSkills} compétences orphelines détectées");
        } else {
            logMessage("✅ Intégrité des relations vérifiée");
        }
        
        // Test API simple
        $totalJobs = $pdo->query("SELECT COUNT(*) FROM internships")->fetchColumn();
        logMessage("📡 Total des emplois disponibles pour l'API: {$totalJobs}");
        
        logMessage("✅ Vérification finale terminée - Système prêt");
        
    } catch (Exception $e) {
        logMessage("❌ Erreur lors de la vérification: " . $e->getMessage());
        throw $e;
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialisation et Reconstruction - Système de Tutorat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .reset-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .reset-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .reset-header {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .reset-header h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: 300;
        }
        
        .reset-body {
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
            border-left: 4px solid #e74c3c;
            background: rgba(231, 76, 60, 0.1);
            border-radius: 4px;
        }
        
        .success-message {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
            padding: 1.5rem;
            border-radius: 8px;
            margin: 2rem 0;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-card">
            <div class="reset-header">
                <i class="bi bi-arrow-clockwise" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                <h1>Réinitialisation & Reconstruction</h1>
                <p>Système de Gestion des Emplois</p>
            </div>
            
            <div class="reset-body">
                <div class="log-container" id="log-container">
                    <?php
                    try {
                        $startTime = microtime(true);
                        
                        logMessage("🚀 Début du processus de réinitialisation et reconstruction");
                        
                        // Étape 1: Nettoyage de la base de données
                        cleanDatabase($pdo);
                        
                        // Étape 2: Génération des nouveaux emplois
                        generateInternships($pdo);
                        
                        // Étape 3: Analyse et catégorisation
                        analyzeAndCategorizeJobs($pdo);
                        
                        // Étape 4: Vérification finale
                        finalVerification($pdo);
                        
                        $endTime = microtime(true);
                        $executionTime = round($endTime - $startTime, 2);
                        
                        logMessage("🎉 Processus terminé avec succès en {$executionTime} secondes");
                        
                        echo "<div class='success-message'>";
                        echo "<h4><i class='bi bi-check-circle'></i> Reconstruction Terminée!</h4>";
                        echo "<p>La base de données a été réinitialisée et reconstruite avec succès.</p>";
                        echo "<p>Les emplois sont maintenant disponibles via les API du système.</p>";
                        echo "</div>";
                        
                    } catch (Exception $e) {
                        logMessage("❌ ERREUR CRITIQUE: " . $e->getMessage());
                        echo "<div class='alert alert-danger mt-3'>";
                        echo "<h5><i class='bi bi-exclamation-triangle'></i> Erreur</h5>";
                        echo "<p>Une erreur est survenue: " . htmlspecialchars($e->getMessage()) . "</p>";
                        echo "</div>";
                    }
                    ?>
                </div>
                
                <div class="mt-4 text-center">
                    <a href="../index.php" class="btn btn-primary btn-lg">
                        <i class="bi bi-house"></i> Retour à l'accueil
                    </a>
                    <a href="../api/internships/index.php" class="btn btn-info btn-lg ms-2">
                        <i class="bi bi-api"></i> Tester l'API
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-scroll du log
        const logContainer = document.getElementById('log-container');
        logContainer.scrollTop = logContainer.scrollHeight;
    </script>
</body>
</html>