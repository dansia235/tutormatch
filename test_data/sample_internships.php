<?php
/**
 * Script pour insérer des données d'exemple de stages pour tester la recherche
 */

// Inclure les fichiers nécessaires
require_once __DIR__ . '/../includes/init.php';

// Vérifier si on est en mode développement
if (!defined('DEV_MODE') || DEV_MODE !== true) {
    die('Ce script ne peut être exécuté qu\'en mode développement');
}

// Données d'exemple de stages
$sampleInternships = [
    [
        'title' => 'Développeur Full Stack',
        'company_id' => 1,
        'description' => 'Rejoignez notre équipe pour travailler sur des applications web innovantes.',
        'requirements' => 'Connaissance de JavaScript, PHP, React, Node.js.',
        'start_date' => '2025-07-01',
        'end_date' => '2025-08-31',
        'location' => 'Paris',
        'work_mode' => 'hybrid',
        'compensation' => 800,
        'domain' => 'Développement Web',
        'status' => 'available',
        'skills' => ['JavaScript', 'PHP', 'React', 'Node.js']
    ],
    [
        'title' => 'Développeur Android',
        'company_id' => 2,
        'description' => 'Développement d\'applications mobiles pour Android.',
        'requirements' => 'Connaissance de Kotlin, Java, Android SDK.',
        'start_date' => '2025-07-15',
        'end_date' => '2025-09-15',
        'location' => 'Lyon',
        'work_mode' => 'on_site',
        'compensation' => 750,
        'domain' => 'Développement Mobile',
        'status' => 'available',
        'skills' => ['Kotlin', 'Java', 'Android SDK']
    ],
    [
        'title' => 'Analyste de données',
        'company_id' => 3,
        'description' => 'Analyse de données pour aider à la prise de décision.',
        'requirements' => 'Connaissance de Python, R, SQL, Tableau.',
        'start_date' => '2025-06-15',
        'end_date' => '2025-08-15',
        'location' => 'Bordeaux',
        'work_mode' => 'remote',
        'compensation' => 900,
        'domain' => 'Data Science',
        'status' => 'available',
        'skills' => ['Python', 'R', 'SQL', 'Tableau']
    ],
    [
        'title' => 'Assistant marketing digital',
        'company_id' => 1,
        'description' => 'Aide à la gestion des campagnes marketing digital.',
        'requirements' => 'Connaissance des réseaux sociaux, Google Analytics.',
        'start_date' => '2025-06-01',
        'end_date' => '2025-08-31',
        'location' => 'Paris',
        'work_mode' => 'hybrid',
        'compensation' => 700,
        'domain' => 'Marketing',
        'status' => 'available',
        'skills' => ['SEO', 'SEM', 'Réseaux sociaux', 'Google Analytics']
    ],
    [
        'title' => 'Architecte cloud',
        'company_id' => 4,
        'description' => 'Conception et mise en œuvre d\'architectures cloud.',
        'requirements' => 'Connaissance d\'AWS, Azure, Docker, Kubernetes.',
        'start_date' => '2025-07-01',
        'end_date' => '2025-09-30',
        'location' => 'Toulouse',
        'work_mode' => 'hybrid',
        'compensation' => 1000,
        'domain' => 'Cloud Computing',
        'status' => 'available',
        'skills' => ['AWS', 'Azure', 'Docker', 'Kubernetes']
    ],
    [
        'title' => 'Ingénieur DevOps',
        'company_id' => 2,
        'description' => 'Mise en place et maintenance de pipelines CI/CD.',
        'requirements' => 'Connaissance de Jenkins, GitLab CI, Terraform.',
        'start_date' => '2025-06-15',
        'end_date' => '2025-09-15',
        'location' => 'Lyon',
        'work_mode' => 'on_site',
        'compensation' => 850,
        'domain' => 'DevOps',
        'status' => 'available',
        'skills' => ['Jenkins', 'GitLab CI', 'Terraform', 'Ansible']
    ],
    [
        'title' => 'Développeur iOS',
        'company_id' => 3,
        'description' => 'Développement d\'applications mobiles pour iOS.',
        'requirements' => 'Connaissance de Swift, Objective-C, iOS SDK.',
        'start_date' => '2025-07-01',
        'end_date' => '2025-09-30',
        'location' => 'Bordeaux',
        'work_mode' => 'hybrid',
        'compensation' => 800,
        'domain' => 'Développement Mobile',
        'status' => 'available',
        'skills' => ['Swift', 'Objective-C', 'iOS SDK']
    ]
];

// Insérer les données d'exemple
$internshipModel = new Internship($db);
$countInserted = 0;

foreach ($sampleInternships as $internshipData) {
    $skills = $internshipData['skills'] ?? [];
    unset($internshipData['skills']);
    
    $internshipId = $internshipModel->create($internshipData);
    
    if ($internshipId) {
        $countInserted++;
        
        // Ajouter les compétences
        foreach ($skills as $skill) {
            $internshipModel->addSkill($internshipId, $skill);
        }
        
        echo "Stage inséré avec succès: {$internshipData['title']} (ID: $internshipId)\n";
    } else {
        echo "Erreur lors de l'insertion du stage: {$internshipData['title']}\n";
    }
}

echo "\nInsertion terminée. $countInserted stages insérés sur " . count($sampleInternships) . " tentatives.\n";
?>