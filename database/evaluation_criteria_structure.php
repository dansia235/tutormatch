<?php
/**
 * Script pour définir et documenter la structure des critères d'évaluation
 * 
 * Ce script définit la structure standard des critères d'évaluation stockés 
 * dans le champ JSON criteria_scores de la table evaluations.
 */

// Configuration d'encodage UTF-8
ini_set('default_charset', 'UTF-8');

// Ajouter une fonction d'échappement
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Connexion directe à la base de données
try {
    // Charger les informations de connexion à la base de données
    if (file_exists(__DIR__ . '/../config/database.php')) {
        $config = include __DIR__ . '/../config/database.php';
    } else if (file_exists(__DIR__ . '/../config/database.example.php')) {
        $config = include __DIR__ . '/../config/database.example.php';
    } else {
        throw new Exception("Fichier de configuration de la base de données introuvable");
    }
    
    // Établir la connexion à la base de données
    $db = new PDO("mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}", $config['username'], $config['password']);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Créer une session si elle n'existe pas déjà
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
} catch (Exception $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

// Structure standard des critères d'évaluation
$criteriaStructure = [
    // Critères techniques
    'technical' => [
        'technical_mastery' => [
            'name' => 'Maîtrise des technologies',
            'category' => 'technical',
            'description' => 'Capacité à utiliser les technologies et outils liés au stage',
            'weight' => 1.0
        ],
        'work_quality' => [
            'name' => 'Qualité du travail',
            'category' => 'technical',
            'description' => 'Précision, clarté et fiabilité des livrables produits',
            'weight' => 1.0
        ],
        'problem_solving' => [
            'name' => 'Résolution de problèmes',
            'category' => 'technical',
            'description' => 'Capacité à analyser et résoudre des problèmes techniques',
            'weight' => 1.0
        ],
        'documentation' => [
            'name' => 'Documentation',
            'category' => 'technical',
            'description' => 'Qualité de la documentation produite et des commentaires',
            'weight' => 1.0
        ]
    ],
    
    // Critères professionnels
    'professional' => [
        'autonomy' => [
            'name' => 'Autonomie',
            'category' => 'professional',
            'description' => 'Capacité à travailler de manière indépendante',
            'weight' => 1.0
        ],
        'communication' => [
            'name' => 'Communication',
            'category' => 'professional',
            'description' => 'Clarté et efficacité de la communication écrite et orale',
            'weight' => 1.0
        ],
        'team_integration' => [
            'name' => 'Intégration dans l\'équipe',
            'category' => 'professional',
            'description' => 'Collaboration et interactions avec les membres de l\'équipe',
            'weight' => 1.0
        ],
        'deadline_respect' => [
            'name' => 'Respect des délais',
            'category' => 'professional',
            'description' => 'Ponctualité et respect des échéances fixées',
            'weight' => 1.0
        ]
    ]
];

// Créer une structure de compatibilité pour les anciennes données
$compatibilityMapping = [
    'technical_skills' => 'technical_mastery',
    'professional_behavior' => 'team_integration',
    'communication' => 'communication',
    'initiative' => 'autonomy',
    'teamwork' => 'team_integration',
    'punctuality' => 'deadline_respect'
];

// Fonction pour initialiser un ensemble de critères d'évaluation vides
function initEmptyCriteriaScores() {
    global $criteriaStructure;
    
    $criteriaScores = [];
    
    foreach ($criteriaStructure as $category => $criteria) {
        foreach ($criteria as $key => $criterion) {
            $criteriaScores[$key] = [
                'score' => 0,
                'comment' => ''
            ];
        }
    }
    
    return $criteriaScores;
}

// Fonction pour convertir d'anciens formats de critères vers le nouveau format
function convertLegacyCriteria($oldCriteria) {
    global $criteriaStructure, $compatibilityMapping;
    
    $newCriteria = initEmptyCriteriaScores();
    
    // Si les anciens critères sont un tableau simple (clé => valeur)
    if (is_array($oldCriteria)) {
        foreach ($oldCriteria as $oldKey => $value) {
            // Si c'est un nombre, c'est probablement un score
            if (is_numeric($value)) {
                $score = floatval($value);
                
                // Convertir le score de 0-100 ou 0-20 à 0-5
                if ($score > 5) {
                    $score = $score > 20 ? round($score / 20, 1) : round($score / 4, 1);
                }
                
                // Trouver la clé correspondante dans le nouveau format
                $newKey = $compatibilityMapping[$oldKey] ?? $oldKey;
                
                // Si la clé existe dans le nouveau format, mettre à jour le score
                if (isset($newCriteria[$newKey])) {
                    $newCriteria[$newKey]['score'] = $score;
                }
            }
            // Si c'est un tableau, c'est peut-être déjà au nouveau format
            else if (is_array($value) && isset($value['score'])) {
                $newKey = $compatibilityMapping[$oldKey] ?? $oldKey;
                if (isset($newCriteria[$newKey])) {
                    $newCriteria[$newKey] = $value;
                }
            }
        }
    }
    
    return $newCriteria;
}

// Fonction pour calculer les moyennes techniques et professionnelles
function calculateAverages($criteriaScores) {
    global $criteriaStructure;
    
    $technicalSum = 0;
    $technicalCount = 0;
    $professionalSum = 0;
    $professionalCount = 0;
    
    foreach ($criteriaScores as $key => $criterion) {
        $score = floatval($criterion['score']);
        
        // Déterminer la catégorie du critère
        $category = null;
        foreach ($criteriaStructure as $cat => $criteria) {
            if (isset($criteria[$key])) {
                $category = $cat;
                break;
            }
        }
        
        if ($category === 'technical') {
            $technicalSum += $score;
            $technicalCount++;
        } else if ($category === 'professional') {
            $professionalSum += $score;
            $professionalCount++;
        }
    }
    
    $averages = [
        'technical_avg' => $technicalCount > 0 ? round($technicalSum / $technicalCount, 1) : 0,
        'professional_avg' => $professionalCount > 0 ? round($professionalSum / $professionalCount, 1) : 0
    ];
    
    $averages['overall_avg'] = ($averages['technical_avg'] + $averages['professional_avg']) / 2;
    
    return $averages;
}

// Début de l'affichage
echo "<!DOCTYPE html>
<html>
<head>
    <title>Structure des critères d'évaluation</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 900px; margin: 0 auto; }
        h1, h2, h3 { color: #333; }
        p { line-height: 1.5; }
        .success { color: green; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .actions { margin-top: 20px; }
        .btn { display: inline-block; padding: 8px 16px; margin-right: 10px; background-color: #4CAF50; color: white; 
               text-decoration: none; border-radius: 4px; border: none; cursor: pointer; }
        .btn-danger { background-color: #f44336; }
        .btn-info { background-color: #2196F3; }
        code { background: #f5f5f5; padding: 2px 5px; border-radius: 3px; font-family: monospace; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow: auto; }
        .category-technical { color: #2196F3; }
        .category-professional { color: #4CAF50; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Structure standardisée des critères d'évaluation</h1>
        
        <p>Ce document définit la structure standard des critères d'évaluation stockés dans le champ JSON <code>criteria_scores</code> de la table <code>evaluations</code>.</p>";

// Afficher la structure standard des critères
echo "<h2>Définition des critères</h2>";

echo "<table>
        <thead>
            <tr>
                <th>Clé</th>
                <th>Nom</th>
                <th>Catégorie</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>";

foreach ($criteriaStructure as $category => $criteria) {
    foreach ($criteria as $key => $criterion) {
        echo "<tr>
                <td><code>$key</code></td>
                <td>{$criterion['name']}</td>
                <td class='category-$category'>" . ucfirst($category) . "</td>
                <td>{$criterion['description']}</td>
              </tr>";
    }
}

echo "</tbody></table>";

// Afficher un exemple de structure JSON
$exampleCriteria = initEmptyCriteriaScores();
$exampleCriteria['technical_mastery']['score'] = 4.5;
$exampleCriteria['technical_mastery']['comment'] = 'Excellente maîtrise des technologies utilisées';
$exampleCriteria['work_quality']['score'] = 4.0;
$exampleCriteria['communication']['score'] = 3.5;
$exampleCriteria['communication']['comment'] = 'Bonne communication, mais peut être améliorée';

echo "<h2>Exemple de structure JSON</h2>";
echo "<pre>" . h(json_encode($exampleCriteria, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";

// Afficher le code PHP pour travailler avec cette structure
echo "<h2>Utilisation dans le code PHP</h2>";

echo "<h3>Initialisation des critères vides</h3>";
echo "<pre>
// Initialiser un ensemble de critères vides
\$criteriaScores = " . h(var_export(initEmptyCriteriaScores(), true)) . ";
</pre>";

echo "<h3>Calcul des moyennes</h3>";
echo "<pre>
// Données d'exemple
\$criteriaScores = " . h(var_export($exampleCriteria, true)) . ";

// Calculer les moyennes
\$averages = " . h(var_export(calculateAverages($exampleCriteria), true)) . ";

// Mettre à jour les champs dans la base de données
\$stmt = \$db->prepare(\"
    UPDATE evaluations 
    SET technical_avg = :technical_avg,
        professional_avg = :professional_avg,
        score = :overall_avg
    WHERE id = :evaluation_id
\");

\$stmt->execute([
    'technical_avg' => \$averages['technical_avg'],
    'professional_avg' => \$averages['professional_avg'],
    'overall_avg' => \$averages['overall_avg'],
    'evaluation_id' => \$evaluationId
]);
</pre>";

// Vérifier si l'utilisateur souhaite mettre à jour les données existantes
if (isset($_GET['update']) && $_GET['update'] === 'yes') {
    echo "<h2>Mise à jour des données existantes</h2>";
    
    try {
        // Compter le nombre d'évaluations à mettre à jour
        $stmt = $db->query("SELECT COUNT(*) FROM evaluations WHERE criteria_scores IS NOT NULL");
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            // Récupérer toutes les évaluations avec des critères
            $stmt = $db->query("SELECT id, criteria_scores FROM evaluations WHERE criteria_scores IS NOT NULL");
            $updated = 0;
            $errors = 0;
            
            echo "<p>Traitement de $count évaluations avec des critères existants...</p>";
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                try {
                    $id = $row['id'];
                    $oldCriteria = json_decode($row['criteria_scores'], true);
                    
                    if (is_array($oldCriteria)) {
                        // Convertir au nouveau format
                        $newCriteria = convertLegacyCriteria($oldCriteria);
                        
                        // Calculer les moyennes
                        $averages = calculateAverages($newCriteria);
                        
                        // Mettre à jour la base de données
                        $updateStmt = $db->prepare("
                            UPDATE evaluations 
                            SET criteria_scores = :criteria_scores,
                                technical_avg = :technical_avg,
                                professional_avg = :professional_avg,
                                score = :overall_avg
                            WHERE id = :id
                        ");
                        
                        $updateStmt->execute([
                            'criteria_scores' => json_encode($newCriteria),
                            'technical_avg' => $averages['technical_avg'],
                            'professional_avg' => $averages['professional_avg'],
                            'overall_avg' => $averages['overall_avg'],
                            'id' => $id
                        ]);
                        
                        $updated++;
                    }
                } catch (Exception $e) {
                    $errors++;
                    error_log("Erreur lors de la mise à jour de l'évaluation ID $id: " . $e->getMessage());
                }
            }
            
            echo "<p class='success'>$updated évaluations mises à jour avec succès.</p>";
            
            if ($errors > 0) {
                echo "<p class='warning'>$errors évaluations n'ont pas pu être mises à jour.</p>";
            }
        } else {
            echo "<p>Aucune évaluation avec des critères à mettre à jour.</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>Erreur lors de la mise à jour des données: " . h($e->getMessage()) . "</p>";
    }
} else {
    echo "<div class='actions'>
            <p>Si vous souhaitez mettre à jour les critères existants dans la base de données pour les conformer à cette structure, cliquez sur le bouton ci-dessous :</p>
            <a href='?update=yes' class='btn btn-info'>Mettre à jour les critères existants</a>
          </div>";
}

echo "    <div class='actions' style='margin-top: 40px;'>
            <a href='/tutoring/database/standardize_evaluation_fields.php' class='btn'>Retour à la standardisation des champs</a>
            <a href='/tutoring/reset_evaluations.php' class='btn btn-info'>Réinitialiser les évaluations</a>
            <a href='/tutoring/' class='btn'>Retour à l'accueil</a>
        </div>
    </div>
</body>
</html>";
?>