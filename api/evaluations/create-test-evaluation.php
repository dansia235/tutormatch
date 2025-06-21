<?php
/**
 * Script utilitaire pour créer un document d'évaluation à des fins de test
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/document-adapter.php';

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    die("Vous devez être connecté pour utiliser cet outil");
}

// Récupérer le modèle étudiant
$studentModel = new Student($db);
$student = $studentModel->getByUserId($_SESSION['user_id']);

if (!$student) {
    die("Profil étudiant non trouvé");
}

// Récupérer le modèle document
$documentModel = new Document($db);

// Créer un document d'évaluation de test
$evaluationData = [
    'title' => 'Évaluation mi-parcours',
    'description' => 'Évaluation du tuteur pour la période mi-parcours du stage',
    'type' => 'evaluation',
    'file_path' => 'evaluations/test_' . uniqid() . '.json',
    'file_type' => 'application/json',
    'file_size' => 1024,
    'user_id' => $student['user_id'],
    'status' => 'submitted',
    'metadata' => [
        'score' => 4.2,
        'evaluator_id' => 1,
        'evaluator_name' => 'Prof. Dupont',
        'comments' => "L'étudiant montre une bonne progression dans ses compétences techniques. Il s'intègre bien dans l'équipe et fait preuve d'initiative. Points à améliorer: documentation du code et communication des problèmes rencontrés.",
        'criteria' => [
            [
                'name' => 'Compétences techniques',
                'score' => 4.5,
                'comments' => 'Excellente maîtrise des technologies utilisées'
            ],
            [
                'name' => 'Autonomie',
                'score' => 4.0,
                'comments' => 'Capable de travailler de manière indépendante'
            ],
            [
                'name' => 'Communication',
                'score' => 3.5,
                'comments' => 'Communique bien mais pourrait être plus proactif'
            ],
            [
                'name' => 'Intégration dans l\'équipe',
                'score' => 4.8,
                'comments' => 'Très bonne intégration, collabore efficacement'
            ]
        ]
    ]
];

// Créer le document
$documentId = $documentModel->create($evaluationData);

if ($documentId) {
    echo "Document d'évaluation créé avec succès. ID: " . $documentId;
    
    // Créer également une auto-évaluation
    $selfEvaluationData = [
        'title' => 'Auto-évaluation',
        'description' => 'Auto-évaluation pour la période mi-parcours du stage',
        'type' => 'self_evaluation',
        'file_path' => 'evaluations/self_' . uniqid() . '.json',
        'file_type' => 'application/json',
        'file_size' => 1024,
        'user_id' => $student['user_id'],
        'status' => 'submitted',
        'metadata' => [
            'score' => 3.8,
            'evaluator_id' => $student['user_id'],
            'evaluator_name' => $student['first_name'] . ' ' . $student['last_name'],
            'comments' => "Je pense avoir bien progressé dans mes compétences techniques. Je me sens à l'aise avec l'équipe mais je dois améliorer ma communication.",
            'criteria' => [
                [
                    'name' => 'Compétences techniques',
                    'score' => 4.0,
                    'comments' => 'Je maîtrise bien les technologies mais il me reste des points à approfondir'
                ],
                [
                    'name' => 'Autonomie',
                    'score' => 3.5,
                    'comments' => 'Je travaille de manière autonome mais je dois poser moins de questions'
                ],
                [
                    'name' => 'Communication',
                    'score' => 3.0,
                    'comments' => 'Je dois améliorer ma communication avec l\'équipe'
                ],
                [
                    'name' => 'Intégration dans l\'équipe',
                    'score' => 4.5,
                    'comments' => 'Je m\'entends bien avec l\'équipe et participe aux réunions'
                ]
            ]
        ]
    ];
    
    $selfDocumentId = $documentModel->create($selfEvaluationData);
    
    if ($selfDocumentId) {
        echo "<br>Document d'auto-évaluation créé avec succès. ID: " . $selfDocumentId;
    } else {
        echo "<br>Erreur lors de la création du document d'auto-évaluation";
    }
} else {
    echo "Erreur lors de la création du document d'évaluation";
}