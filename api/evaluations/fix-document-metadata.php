<?php
/**
 * Script pour corriger les métadonnées des documents d'évaluation
 */

require_once __DIR__ . '/../../includes/init.php';

// Fonction pour générer des métadonnées pour les documents d'évaluation qui n'en ont pas
function generateDefaultMetadata($document) {
    return [
        'score' => 4.0,
        'evaluator_id' => isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1,
        'evaluator_name' => 'Système',
        'comments' => $document['description'] ?? 'Évaluation automatique',
        'criteria' => [
            [
                'name' => 'Compétences techniques',
                'score' => 4.0,
                'comments' => 'Évaluation générée automatiquement'
            ],
            [
                'name' => 'Autonomie',
                'score' => 4.0,
                'comments' => 'Évaluation générée automatiquement'
            ],
            [
                'name' => 'Communication',
                'score' => 4.0,
                'comments' => 'Évaluation générée automatiquement'
            ],
            [
                'name' => 'Intégration dans l\'équipe',
                'score' => 4.0,
                'comments' => 'Évaluation générée automatiquement'
            ]
        ]
    ];
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo "Vous devez être connecté pour utiliser cet outil";
    exit;
}

// Récupérer l'ID étudiant depuis les paramètres
$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : null;

try {
    // Initialiser les modèles nécessaires
    $studentModel = new Student($db);
    $documentModel = new Document($db);
    
    // Si aucun ID étudiant n'est fourni, utiliser l'utilisateur connecté
    if (!$student_id) {
        $student = $studentModel->getByUserId($_SESSION['user_id']);
        if ($student) {
            $student_id = $student['id'];
        }
    }
    
    if (!$student_id) {
        echo "Impossible de déterminer l'ID étudiant";
        exit;
    }
    
    // Récupérer les documents de l'étudiant
    $documents = $studentModel->getDocuments($student_id);
    
    echo "<h2>Correction des métadonnées pour l'étudiant ID: $student_id</h2>";
    echo "<p>Nombre total de documents: " . count($documents) . "</p>";
    
    // Compter les types de documents
    $docTypes = [];
    foreach ($documents as $doc) {
        $type = $doc['type'] ?? 'unknown';
        $docTypes[$type] = ($docTypes[$type] ?? 0) + 1;
    }
    
    echo "<h3>Types de documents trouvés:</h3>";
    echo "<ul>";
    foreach ($docTypes as $type => $count) {
        echo "<li>$type: $count document(s)</li>";
    }
    echo "</ul>";
    
    // Filtrer pour ne garder que les documents d'évaluation
    $evaluationDocs = [];
    foreach ($documents as $doc) {
        if (isset($doc['type']) && ($doc['type'] === 'evaluation' || $doc['type'] === 'self_evaluation')) {
            $evaluationDocs[] = $doc;
        }
    }
    
    echo "<h3>Documents d'évaluation trouvés: " . count($evaluationDocs) . "</h3>";
    
    // Pour chaque document d'évaluation, vérifier et corriger les métadonnées
    $updatedCount = 0;
    
    foreach ($evaluationDocs as $doc) {
        echo "<h4>Document ID: " . $doc['id'] . " - Type: " . $doc['type'] . "</h4>";
        
        $needsUpdate = false;
        $metadata = $doc['metadata'] ?? null;
        
        if (!$metadata || !is_array($metadata) || empty($metadata)) {
            echo "<p>Pas de métadonnées, création de métadonnées par défaut</p>";
            $metadata = generateDefaultMetadata($doc);
            $needsUpdate = true;
        } else {
            echo "<p>Métadonnées existantes: " . json_encode($metadata) . "</p>";
            
            // Vérifier si les champs importants existent
            if (!isset($metadata['score'])) {
                $metadata['score'] = 4.0;
                $needsUpdate = true;
                echo "<p>Ajout du score par défaut</p>";
            }
            
            if (!isset($metadata['criteria']) || !is_array($metadata['criteria']) || empty($metadata['criteria'])) {
                $metadata['criteria'] = generateDefaultMetadata($doc)['criteria'];
                $needsUpdate = true;
                echo "<p>Ajout de critères par défaut</p>";
            }
        }
        
        if ($needsUpdate) {
            // Mettre à jour les métadonnées du document
            $updateData = [
                'metadata' => $metadata
            ];
            
            $result = $documentModel->update($doc['id'], $updateData);
            
            if ($result) {
                echo "<p class='success'>Document mis à jour avec succès</p>";
                $updatedCount++;
            } else {
                echo "<p class='error'>Erreur lors de la mise à jour du document</p>";
            }
        } else {
            echo "<p>Aucune mise à jour nécessaire</p>";
        }
        
        echo "<hr>";
    }
    
    echo "<h3>Résumé:</h3>";
    echo "<p>$updatedCount document(s) mis à jour sur " . count($evaluationDocs) . " documents d'évaluation</p>";
    
    // Créer des documents d'évaluation si nécessaire
    if (count($evaluationDocs) == 0) {
        echo "<h3>Création de documents d'évaluation de test</h3>";
        
        // Récupérer l'utilisateur associé à l'étudiant
        $query = "SELECT user_id FROM students WHERE id = :student_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->execute();
        $userId = $stmt->fetchColumn();
        
        if ($userId) {
            // Créer un document d'évaluation de test
            $evaluationData = [
                'title' => 'Évaluation mi-parcours',
                'description' => 'Évaluation du tuteur pour la période mi-parcours du stage',
                'type' => 'evaluation',
                'file_path' => 'evaluations/test_' . uniqid() . '.json',
                'file_type' => 'application/json',
                'file_size' => 1024,
                'user_id' => $userId,
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
            
            $documentId = $documentModel->create($evaluationData);
            
            if ($documentId) {
                echo "<p class='success'>Document d'évaluation créé avec succès. ID: $documentId</p>";
                
                // Créer également une auto-évaluation
                $selfEvaluationData = [
                    'title' => 'Auto-évaluation',
                    'description' => 'Auto-évaluation pour la période mi-parcours du stage',
                    'type' => 'self_evaluation',
                    'file_path' => 'evaluations/self_' . uniqid() . '.json',
                    'file_type' => 'application/json',
                    'file_size' => 1024,
                    'user_id' => $userId,
                    'status' => 'submitted',
                    'metadata' => [
                        'score' => 3.8,
                        'evaluator_id' => $userId,
                        'evaluator_name' => 'Étudiant',
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
                    echo "<p class='success'>Document d'auto-évaluation créé avec succès. ID: $selfDocumentId</p>";
                } else {
                    echo "<p class='error'>Erreur lors de la création du document d'auto-évaluation</p>";
                }
            } else {
                echo "<p class='error'>Erreur lors de la création du document d'évaluation</p>";
            }
        } else {
            echo "<p class='error'>Impossible de trouver l'utilisateur associé à l'étudiant ID: $student_id</p>";
        }
    }
    
    echo "<style>
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        hr { border: 1px solid #ccc; margin: 20px 0; }
    </style>";
    
} catch (Exception $e) {
    echo "<h2>Erreur:</h2>";
    echo "<p class='error'>" . $e->getMessage() . "</p>";
}