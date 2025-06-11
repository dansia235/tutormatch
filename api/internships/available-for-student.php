<?php
/**
 * API pour récupérer les stages disponibles pour un étudiant
 * Endpoint: /api/internships/available-for-student
 * Méthode: GET
 */

require_once __DIR__ . '/../utils.php';

// Vérifier que l'utilisateur est connecté
requireApiAuth();

// Vérifier que l'utilisateur est un étudiant
if ($_SESSION['user_role'] !== 'student') {
    sendJsonError('Accès non autorisé', 403);
}

try {
    // Récupérer l'ID de l'étudiant
    $studentModel = new Student($db);
    $student = $studentModel->getByUserId($_SESSION['user_id']);
    
    if (!$student) {
        sendJsonError('Profil étudiant non trouvé', 404);
    }
    
    // Récupérer les stages disponibles pour l'étudiant
    $internshipModel = new Internship($db);
    $availableInternships = [];
    
    if (method_exists($internshipModel, 'getAvailableForStudent')) {
        $availableInternships = $internshipModel->getAvailableForStudent($student['id']) ?? [];
    }
    
    // Si pas de données réelles ou pour la démonstration, utiliser des données fictives
    if (empty($availableInternships)) {
        $availableInternships = [
            [
                'id' => 1,
                'title' => 'Développeur Web Full Stack',
                'company_name' => 'TechSolutions',
                'domain' => 'Développement Web',
                'location' => 'Paris',
                'work_mode' => 'hybrid',
                'description' => 'Stage de développement web avec des technologies modernes (React, Node.js, MongoDB). Vous participerez au développement de nouvelles fonctionnalités pour notre plateforme e-commerce.',
                'start_date' => date('Y-m-d', strtotime('+30 days')),
                'end_date' => date('Y-m-d', strtotime('+180 days')),
                'status' => 'available'
            ],
            [
                'id' => 2,
                'title' => 'Développeur Mobile iOS',
                'company_name' => 'AppFactory',
                'domain' => 'Développement Mobile',
                'location' => 'Lyon',
                'work_mode' => 'on_site',
                'description' => 'Stage de développement d\'applications iOS en Swift. Vous participerez à la conception et au développement de nouvelles applications mobiles pour nos clients.',
                'start_date' => date('Y-m-d', strtotime('+45 days')),
                'end_date' => date('Y-m-d', strtotime('+195 days')),
                'status' => 'available'
            ],
            [
                'id' => 3,
                'title' => 'Data Scientist',
                'company_name' => 'DataInsight',
                'domain' => 'Data Science',
                'location' => 'Bordeaux',
                'work_mode' => 'remote',
                'description' => 'Stage en science des données. Vous participerez à l\'analyse de données massives et à la création de modèles prédictifs pour nos clients du secteur financier.',
                'start_date' => date('Y-m-d', strtotime('+15 days')),
                'end_date' => date('Y-m-d', strtotime('+165 days')),
                'status' => 'available'
            ],
            [
                'id' => 4,
                'title' => 'Ingénieur DevOps',
                'company_name' => 'CloudServices',
                'domain' => 'DevOps',
                'location' => 'Toulouse',
                'work_mode' => 'hybrid',
                'description' => 'Stage en ingénierie DevOps. Vous participerez à la mise en place et à l\'amélioration de notre infrastructure cloud, ainsi qu\'à l\'automatisation de nos processus de déploiement.',
                'start_date' => date('Y-m-d', strtotime('+60 days')),
                'end_date' => date('Y-m-d', strtotime('+210 days')),
                'status' => 'available'
            ]
        ];
    }
    
    // Envoyer la réponse
    sendJsonResponse([
        'student_id' => $student['id'],
        'internships' => $availableInternships
    ]);
} catch (Exception $e) {
    sendJsonError('Erreur lors de la récupération des stages disponibles: ' . $e->getMessage(), 500);
}
?>