<?php
/**
 * Script pour vider et régénérer les données d'évaluation dans la base de données
 * Ce script supprime toutes les évaluations existantes et crée de nouvelles évaluations
 * avec la structure standardisée des critères.
 */

// Configuration d'encodage UTF-8
ini_set('default_charset', 'UTF-8');

// Ajouter une fonction d'échappement
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

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
    
    $averages['overall_avg'] = round(($averages['technical_avg'] + $averages['professional_avg']) / 2, 1);
    
    return $averages;
}

// Fonction pour générer des commentaires aléatoires
function getRandomComment($score, $criterionKey) {
    $comments = [
        'technical_mastery' => [
            1 => ['Maîtrise très limitée des technologies', 'Connaissances techniques insuffisantes', 'Difficulté à utiliser les outils de base'],
            2 => ['Connaissances de base présentes mais limitées', 'Besoin d\'améliorer la maîtrise technique', 'Utilisation correcte des outils simples'],
            3 => ['Bonne maîtrise des technologies principales', 'Compétences techniques satisfaisantes', 'Utilise correctement les outils du projet'],
            4 => ['Très bonne maîtrise technique', 'Compétences techniques avancées', 'Utilisation efficace de tous les outils'],
            5 => ['Excellente maîtrise technique', 'Expertise technique remarquable', 'Maîtrise parfaite de l\'environnement technique']
        ],
        'work_quality' => [
            1 => ['Qualité de travail insuffisante', 'Nombreuses erreurs dans les livrables', 'Travail nécessitant des révisions importantes'],
            2 => ['Qualité correcte mais irrégulière', 'Plusieurs corrections nécessaires', 'Travail de base acceptable'],
            3 => ['Bonne qualité de travail', 'Peu d\'erreurs dans les livrables', 'Travail conforme aux attentes'],
            4 => ['Très bonne qualité', 'Livrables précis et fiables', 'Travail soigné et rigoureux'],
            5 => ['Excellente qualité de travail', 'Livrables impeccables', 'Travail d\'une grande précision']
        ],
        'problem_solving' => [
            1 => ['Difficulté à identifier les problèmes', 'Solutions inappropriées', 'Besoin d\'assistance constante'],
            2 => ['Résout des problèmes simples', 'A besoin d\'aide pour les cas complexes', 'Analyse parfois incorrecte'],
            3 => ['Bonne capacité d\'analyse', 'Résout la plupart des problèmes', 'Approche méthodique'],
            4 => ['Très bonne capacité à résoudre des problèmes', 'Solutions efficaces et innovantes', 'Analyse pertinente'],
            5 => ['Excellente capacité d\'analyse et résolution', 'Solutions optimales', 'Grande autonomie face aux problèmes']
        ],
        'documentation' => [
            1 => ['Documentation inexistante ou très insuffisante', 'Commentaires absents', 'Documentation inutilisable'],
            2 => ['Documentation minimale', 'Commentaires basiques', 'Documentation incomplète'],
            3 => ['Documentation correcte', 'Commentaires utiles', 'Documentation fonctionnelle'],
            4 => ['Très bonne documentation', 'Commentaires détaillés', 'Documentation claire et précise'],
            5 => ['Documentation excellente', 'Commentaires exhaustifs', 'Documentation exemplaire']
        ],
        'autonomy' => [
            1 => ['Manque d\'autonomie', 'Besoin de supervision constante', 'Difficulté à travailler seul'],
            2 => ['Autonomie limitée', 'Demande souvent de l\'aide', 'Progresse sur ce point'],
            3 => ['Autonomie satisfaisante', 'Travaille seul sur des tâches définies', 'Demande de l\'aide à bon escient'],
            4 => ['Très bonne autonomie', 'Prend des initiatives pertinentes', 'Gère bien son temps de travail'],
            5 => ['Excellente autonomie', 'Gère parfaitement ses missions', 'Fait preuve d\'initiative et d\'anticipation']
        ],
        'communication' => [
            1 => ['Communication difficile', 'Expression confuse', 'Partage insuffisant d\'informations'],
            2 => ['Communication basique', 'Expression à améliorer', 'Informations parfois incomplètes'],
            3 => ['Bonne communication', 'Expression claire', 'Partage adéquat d\'informations'],
            4 => ['Très bonne communication', 'Expression précise', 'Partage proactif d\'informations'],
            5 => ['Communication excellente', 'Grande clarté d\'expression', 'Partage optimal des informations']
        ],
        'team_integration' => [
            1 => ['Difficulté à s\'intégrer', 'Peu de collaboration', 'Interaction limitée avec l\'équipe'],
            2 => ['Intégration partielle', 'Collaboration à améliorer', 'Interactions basiques'],
            3 => ['Bonne intégration', 'Collaboration satisfaisante', 'Interactions positives'],
            4 => ['Très bonne intégration', 'Collaboration active', 'Apprécié par l\'équipe'],
            5 => ['Excellente intégration', 'Collaboration exemplaire', 'Élément moteur de l\'équipe']
        ],
        'deadline_respect' => [
            1 => ['Non-respect fréquent des délais', 'Retards importants', 'Difficulté à planifier son travail'],
            2 => ['Respect irrégulier des délais', 'Quelques retards', 'Planification à améliorer'],
            3 => ['Respect satisfaisant des délais', 'Retards rares', 'Bonne planification'],
            4 => ['Très bon respect des délais', 'Ponctualité constante', 'Planification efficace'],
            5 => ['Respect parfait des délais', 'Anticipation des échéances', 'Planification exemplaire']
        ]
    ];
    
    // Arrondir le score pour correspondre aux commentaires disponibles
    $scoreIndex = max(1, min(5, round($score)));
    
    // Récupérer les commentaires disponibles pour ce critère et ce score
    $availableComments = $comments[$criterionKey][$scoreIndex] ?? [''];
    
    // Retourner un commentaire aléatoire
    return $availableComments[array_rand($availableComments)];
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

// Connexion directe à la base de données
try {
    // Inclure le fichier de configuration de la base de données
    require_once __DIR__ . '/config/database.php';
    
    // Établir la connexion à la base de données en utilisant la fonction existante (avec utf8 au lieu de utf8mb4)
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8";
    $db = new PDO($dsn, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Créer une session si elle n'existe pas déjà
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
} catch (Exception $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

// Simuler les droits d'administration pour permettre l'exécution sans session
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'admin';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Réinitialisation des évaluations</title>
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
        .progress-container { margin: 20px 0; background-color: #f5f5f5; border-radius: 5px; }
        .progress-bar { height: 20px; background-color: #4CAF50; border-radius: 5px; text-align: center; color: white; font-weight: bold; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Réinitialisation et génération de données d'évaluation</h1>";

// Confirmation par GET parameter
if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'yes') {
    echo "<div class='warning'>
            <h2>⚠️ Attention !</h2>
            <p>Ce script va supprimer <strong>TOUTES</strong> les données d'évaluations existantes et générer de nouvelles données de test.</p>
            <p>Cette action est irréversible. Assurez-vous d'avoir une sauvegarde si nécessaire.</p>
            <p><a href='?confirm=yes' class='btn btn-danger'>Confirmer et procéder à la réinitialisation</a></p>
            <p><a href='/tutoring/' class='btn'>Annuler et retourner à l'accueil</a></p>
          </div>";
    echo "</div></body></html>";
    exit;
}

// Fonction pour vérifier si une colonne existe dans une table
function columnExists($db, $table, $column) {
    $stmt = $db->query("SHOW COLUMNS FROM $table LIKE '$column'");
    return $stmt->rowCount() > 0;
}

// Commencer la régénération des évaluations
try {
    if (!isset($db) || !($db instanceof PDO)) {
        die("Erreur: Base de données non disponible. Vérifiez la connexion.");
    }
    
    // Récupérer la structure de la table pour savoir quelles colonnes sont disponibles
    $columns = $db->query("SHOW COLUMNS FROM evaluations")->fetchAll(PDO::FETCH_COLUMN);
    
    // Début de transaction
    $db->beginTransaction();
    
    echo "<h2>Suppression des données existantes</h2>";
    
    // 1. Supprimer toutes les évaluations existantes
    $stmt = $db->query("SELECT COUNT(*) FROM evaluations");
    $countEvaluations = $stmt->fetchColumn();
    $db->exec("DELETE FROM evaluations");
    echo "<p>$countEvaluations évaluations ont été supprimées.</p>";
    
    // 1.1 Supprimer également les documents d'évaluation qui pourraient créer des conflits
    $stmt = $db->query("SELECT COUNT(*) FROM documents WHERE type IN ('evaluation', 'self_evaluation', 'mid_term', 'final')");
    $countDocs = $stmt->fetchColumn();
    if ($countDocs > 0) {
        $db->exec("DELETE FROM documents WHERE type IN ('evaluation', 'self_evaluation', 'mid_term', 'final')");
        echo "<p>$countDocs documents d'évaluation ont été supprimés.</p>";
    } else {
        echo "<p>Aucun document d'évaluation à supprimer.</p>";
    }
    
    // 2. Récupérer toutes les affectations d'étudiants aux tuteurs
    $assignmentsQuery = $db->query("
        SELECT a.id, a.student_id, a.teacher_id, a.internship_id, 
               s.user_id as student_user_id, 
               t.user_id as teacher_user_id,
               i.start_date, i.end_date
        FROM assignments a
        JOIN students s ON a.student_id = s.id
        JOIN teachers t ON a.teacher_id = t.id
        JOIN internships i ON a.internship_id = i.id
        WHERE a.status IN ('active', 'confirmed', 'completed', 'pending')
    ");
    
    $assignments = $assignmentsQuery->fetchAll(PDO::FETCH_ASSOC);
    echo "<h2>Génération de nouvelles évaluations</h2>";
    echo "<p>Nombre d'affectations trouvées: " . count($assignments) . "</p>";
    
    if (count($assignments) == 0) {
        echo "<p class='warning'>Aucune affectation trouvée. Impossible de générer des évaluations.</p>";
        $db->rollBack();
        echo "</div></body></html>";
        exit;
    }
    
    // 3. Pour chaque affectation, créer des évaluations cohérentes
    $today = new DateTime();
    $evaluationsCreated = 0;
    $evalTypes = ['mid_term', 'final', 'student'];
    
    echo "<div class='progress-container'>
            <div class='progress-bar' style='width: 0%'>0%</div>
          </div>";
    
    foreach ($assignments as $index => $assignment) {
        // Calculer les dates d'évaluation en fonction des dates de stage
        $startDate = new DateTime($assignment['start_date']);
        $endDate = new DateTime($assignment['end_date']);
        
        // Vérifier si les dates sont valides
        if ($startDate > $endDate || $startDate > $today) {
            continue; // Ignorer cette affectation
        }
        
        // Calculer la date de mi-parcours (à mi-chemin entre début et fin)
        $midTermInterval = $startDate->diff($endDate);
        $midTermDays = floor($midTermInterval->days / 2);
        $midTermDate = clone $startDate;
        $midTermDate->add(new DateInterval("P{$midTermDays}D"));
        
        // Déterminer si le stage est commencé, en cours ou terminé
        $stageStarted = $today >= $startDate;
        $stageHalfway = $today >= $midTermDate;
        $stageFinished = $today >= $endDate;
        
        // Paramètres communs pour les évaluations
        $studentId = $assignment['student_id'];
        $studentUserId = $assignment['student_user_id'];
        $teacherId = $assignment['teacher_id'];
        $teacherUserId = $assignment['teacher_user_id'];
        $assignmentId = $assignment['id'];
        
        // 1. Créer l'évaluation de mi-parcours par le tuteur
        if ($stageHalfway) {
            // Générer des scores aléatoires pour les critères (mi-parcours: 2.0-4.0)
            $midTermCriteria = initEmptyCriteriaScores();
            
            foreach ($midTermCriteria as $key => $value) {
                $score = round(mt_rand(20, 40) / 10, 1); // Score entre 2.0 et 4.0
                $midTermCriteria[$key]['score'] = $score;
                $midTermCriteria[$key]['comment'] = getRandomComment($score, $key);
            }
            
            // Calculer les moyennes
            $averages = calculateAverages($midTermCriteria);
            
            // Commentaires généraux
            $midTermFeedback = "L'étudiant montre une progression satisfaisante. Ses compétences techniques sont en développement et son intégration dans l'équipe est bonne. Il doit améliorer sa communication et sa documentation.";
            $midTermStrengths = "Bonne maîtrise technique, autonomie dans les tâches assignées";
            $midTermAreasToImprove = "Documentation du code\nCommunication proactive des problèmes\nParticipation aux réunions";
            $midTermNextSteps = "Améliorer la documentation\nParticiper plus activement aux réunions d'équipe";
            
            $submissionDate = $midTermDate->format('Y-m-d H:i:s');
            
            // Préparer les colonnes à insérer en fonction de la structure réelle de la table
            $fields = [];
            $placeholders = [];
            $params = [];
            
            // Colonnes obligatoires
            $fields[] = 'assignment_id';
            $placeholders[] = ':assignment_id';
            $params['assignment_id'] = $assignmentId;
            
            $fields[] = 'evaluator_id';
            $placeholders[] = ':evaluator_id';
            $params['evaluator_id'] = $teacherUserId;
            
            $fields[] = 'evaluatee_id';
            $placeholders[] = ':evaluatee_id';
            $params['evaluatee_id'] = $studentUserId;
            
            $fields[] = 'type';
            $placeholders[] = ':type';
            $params['type'] = 'mid_term';
            
            // Colonnes facultatives
            if (in_array('score', $columns)) {
                $fields[] = 'score';
                $placeholders[] = ':score';
                $params['score'] = $averages['overall_avg'];
            }
            
            if (in_array('technical_avg', $columns)) {
                $fields[] = 'technical_avg';
                $placeholders[] = ':technical_avg';
                $params['technical_avg'] = $averages['technical_avg'];
            }
            
            if (in_array('professional_avg', $columns)) {
                $fields[] = 'professional_avg';
                $placeholders[] = ':professional_avg';
                $params['professional_avg'] = $averages['professional_avg'];
            }
            
            if (in_array('criteria_scores', $columns)) {
                $fields[] = 'criteria_scores';
                $placeholders[] = ':criteria_scores';
                $params['criteria_scores'] = json_encode($midTermCriteria);
            }
            
            // Gestion des champs de commentaires (plusieurs noms possibles)
            if (in_array('comments', $columns)) {
                $fields[] = 'comments';
                $placeholders[] = ':comments';
                $params['comments'] = $midTermFeedback;
            } else if (in_array('feedback', $columns)) {
                $fields[] = 'feedback';
                $placeholders[] = ':feedback';
                $params['feedback'] = $midTermFeedback;
            }
            
            if (in_array('strengths', $columns)) {
                $fields[] = 'strengths';
                $placeholders[] = ':strengths';
                $params['strengths'] = $midTermStrengths;
            }
            
            if (in_array('areas_for_improvement', $columns)) {
                $fields[] = 'areas_for_improvement';
                $placeholders[] = ':areas_for_improvement';
                $params['areas_for_improvement'] = $midTermAreasToImprove;
            } else if (in_array('areas_to_improve', $columns)) {
                $fields[] = 'areas_to_improve';
                $placeholders[] = ':areas_to_improve';
                $params['areas_to_improve'] = $midTermAreasToImprove;
            }
            
            if (in_array('next_steps', $columns)) {
                $fields[] = 'next_steps';
                $placeholders[] = ':next_steps';
                $params['next_steps'] = $midTermNextSteps;
            }
            
            if (in_array('status', $columns)) {
                $fields[] = 'status';
                $placeholders[] = ':status';
                $params['status'] = 'submitted';
            }
            
            if (in_array('submission_date', $columns)) {
                $fields[] = 'submission_date';
                $placeholders[] = ':submission_date';
                $params['submission_date'] = $submissionDate;
            }
            
            if (in_array('updated_at', $columns)) {
                $fields[] = 'updated_at';
                $placeholders[] = ':updated_at';
                $params['updated_at'] = $submissionDate;
            }
            
            // Construire et exécuter la requête
            $sql = "INSERT INTO evaluations (" . implode(", ", $fields) . ") VALUES (" . implode(", ", $placeholders) . ")";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            
            $evaluationsCreated++;
        }
        
        // 2. Créer l'auto-évaluation de mi-parcours par l'étudiant
        if ($stageHalfway) {
            // L'auto-évaluation est légèrement plus positive que celle du tuteur
            $selfMidTermCriteria = initEmptyCriteriaScores();
            
            foreach ($selfMidTermCriteria as $key => $value) {
                $score = round(mt_rand(25, 45) / 10, 1); // Score entre 2.5 et 4.5
                $selfMidTermCriteria[$key]['score'] = $score;
                $selfMidTermCriteria[$key]['comment'] = getRandomComment($score, $key);
            }
            
            // Calculer les moyennes
            $selfAverages = calculateAverages($selfMidTermCriteria);
            
            $selfFeedback = "Je pense avoir bien progressé dans mon stage. J'ai acquis de nouvelles compétences techniques et j'ai pu contribuer à plusieurs projets. Je dois améliorer ma communication avec l'équipe.";
            $selfStrengths = "Apprentissage rapide des technologies, implication dans les projets";
            $selfAreasToImprove = "Communication plus régulière\nMeilleure organisation du temps";
            $selfNextSteps = "Proposer des points d'avancement hebdomadaires\nTenir un journal des tâches accomplies";
            
            $selfSubmissionDate = (clone $midTermDate)->add(new DateInterval('P3D'))->format('Y-m-d H:i:s');
            
            // Préparer les colonnes à insérer
            $fields = [];
            $placeholders = [];
            $params = [];
            
            // Colonnes obligatoires
            $fields[] = 'assignment_id';
            $placeholders[] = ':assignment_id';
            $params['assignment_id'] = $assignmentId;
            
            $fields[] = 'evaluator_id';
            $placeholders[] = ':evaluator_id';
            $params['evaluator_id'] = $studentUserId;
            
            $fields[] = 'evaluatee_id';
            $placeholders[] = ':evaluatee_id';
            $params['evaluatee_id'] = $studentUserId;
            
            $fields[] = 'type';
            $placeholders[] = ':type';
            $params['type'] = 'student';
            
            // Colonnes facultatives
            if (in_array('score', $columns)) {
                $fields[] = 'score';
                $placeholders[] = ':score';
                $params['score'] = $selfAverages['overall_avg'];
            }
            
            if (in_array('technical_avg', $columns)) {
                $fields[] = 'technical_avg';
                $placeholders[] = ':technical_avg';
                $params['technical_avg'] = $selfAverages['technical_avg'];
            }
            
            if (in_array('professional_avg', $columns)) {
                $fields[] = 'professional_avg';
                $placeholders[] = ':professional_avg';
                $params['professional_avg'] = $selfAverages['professional_avg'];
            }
            
            if (in_array('criteria_scores', $columns)) {
                $fields[] = 'criteria_scores';
                $placeholders[] = ':criteria_scores';
                $params['criteria_scores'] = json_encode($selfMidTermCriteria);
            }
            
            // Gestion des champs de commentaires
            if (in_array('comments', $columns)) {
                $fields[] = 'comments';
                $placeholders[] = ':comments';
                $params['comments'] = $selfFeedback;
            } else if (in_array('feedback', $columns)) {
                $fields[] = 'feedback';
                $placeholders[] = ':feedback';
                $params['feedback'] = $selfFeedback;
            }
            
            if (in_array('strengths', $columns)) {
                $fields[] = 'strengths';
                $placeholders[] = ':strengths';
                $params['strengths'] = $selfStrengths;
            }
            
            if (in_array('areas_for_improvement', $columns)) {
                $fields[] = 'areas_for_improvement';
                $placeholders[] = ':areas_for_improvement';
                $params['areas_for_improvement'] = $selfAreasToImprove;
            } else if (in_array('areas_to_improve', $columns)) {
                $fields[] = 'areas_to_improve';
                $placeholders[] = ':areas_to_improve';
                $params['areas_to_improve'] = $selfAreasToImprove;
            }
            
            if (in_array('next_steps', $columns)) {
                $fields[] = 'next_steps';
                $placeholders[] = ':next_steps';
                $params['next_steps'] = $selfNextSteps;
            }
            
            if (in_array('status', $columns)) {
                $fields[] = 'status';
                $placeholders[] = ':status';
                $params['status'] = 'submitted';
            }
            
            if (in_array('submission_date', $columns)) {
                $fields[] = 'submission_date';
                $placeholders[] = ':submission_date';
                $params['submission_date'] = $selfSubmissionDate;
            }
            
            if (in_array('updated_at', $columns)) {
                $fields[] = 'updated_at';
                $placeholders[] = ':updated_at';
                $params['updated_at'] = $selfSubmissionDate;
            }
            
            // Construire et exécuter la requête
            $sql = "INSERT INTO evaluations (" . implode(", ", $fields) . ") VALUES (" . implode(", ", $placeholders) . ")";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            
            $evaluationsCreated++;
        }
        
        // 3. Créer l'évaluation finale par le tuteur
        if ($stageFinished) {
            // Générer des scores aléatoires pour les critères (finale: 3.0-5.0)
            $finalCriteria = initEmptyCriteriaScores();
            
            foreach ($finalCriteria as $key => $value) {
                $score = round(mt_rand(30, 50) / 10, 1); // Score entre 3.0 et 5.0
                $finalCriteria[$key]['score'] = $score;
                $finalCriteria[$key]['comment'] = getRandomComment($score, $key);
            }
            
            // Calculer les moyennes
            $finalAverages = calculateAverages($finalCriteria);
            
            $finalFeedback = "L'étudiant a réalisé d'excellents progrès tout au long de son stage. Il a su s'adapter aux défis techniques et a démontré une bonne capacité d'intégration dans l'équipe. Ses compétences techniques se sont nettement améliorées.";
            $finalStrengths = "Maîtrise technique approfondie, autonomie, capacité d'analyse et résolution de problèmes";
            $finalAreasToImprove = "Communication technique avec les équipes non-techniques\nPrise de recul sur les solutions implémentées";
            $finalNextSteps = "Continuer à développer ses compétences en communication\nExplorer des domaines techniques complémentaires";
            
            $finalDate = (clone $endDate)->sub(new DateInterval('P5D'));
            $finalSubmissionDate = $finalDate->format('Y-m-d H:i:s');
            
            // Préparer les colonnes à insérer
            $fields = [];
            $placeholders = [];
            $params = [];
            
            // Colonnes obligatoires
            $fields[] = 'assignment_id';
            $placeholders[] = ':assignment_id';
            $params['assignment_id'] = $assignmentId;
            
            $fields[] = 'evaluator_id';
            $placeholders[] = ':evaluator_id';
            $params['evaluator_id'] = $teacherUserId;
            
            $fields[] = 'evaluatee_id';
            $placeholders[] = ':evaluatee_id';
            $params['evaluatee_id'] = $studentUserId;
            
            $fields[] = 'type';
            $placeholders[] = ':type';
            $params['type'] = 'final';
            
            // Colonnes facultatives
            if (in_array('score', $columns)) {
                $fields[] = 'score';
                $placeholders[] = ':score';
                $params['score'] = $finalAverages['overall_avg'];
            }
            
            if (in_array('technical_avg', $columns)) {
                $fields[] = 'technical_avg';
                $placeholders[] = ':technical_avg';
                $params['technical_avg'] = $finalAverages['technical_avg'];
            }
            
            if (in_array('professional_avg', $columns)) {
                $fields[] = 'professional_avg';
                $placeholders[] = ':professional_avg';
                $params['professional_avg'] = $finalAverages['professional_avg'];
            }
            
            if (in_array('criteria_scores', $columns)) {
                $fields[] = 'criteria_scores';
                $placeholders[] = ':criteria_scores';
                $params['criteria_scores'] = json_encode($finalCriteria);
            }
            
            // Gestion des champs de commentaires
            if (in_array('comments', $columns)) {
                $fields[] = 'comments';
                $placeholders[] = ':comments';
                $params['comments'] = $finalFeedback;
            } else if (in_array('feedback', $columns)) {
                $fields[] = 'feedback';
                $placeholders[] = ':feedback';
                $params['feedback'] = $finalFeedback;
            }
            
            if (in_array('strengths', $columns)) {
                $fields[] = 'strengths';
                $placeholders[] = ':strengths';
                $params['strengths'] = $finalStrengths;
            }
            
            if (in_array('areas_for_improvement', $columns)) {
                $fields[] = 'areas_for_improvement';
                $placeholders[] = ':areas_for_improvement';
                $params['areas_for_improvement'] = $finalAreasToImprove;
            } else if (in_array('areas_to_improve', $columns)) {
                $fields[] = 'areas_to_improve';
                $placeholders[] = ':areas_to_improve';
                $params['areas_to_improve'] = $finalAreasToImprove;
            }
            
            if (in_array('next_steps', $columns)) {
                $fields[] = 'next_steps';
                $placeholders[] = ':next_steps';
                $params['next_steps'] = $finalNextSteps;
            }
            
            if (in_array('status', $columns)) {
                $fields[] = 'status';
                $placeholders[] = ':status';
                $params['status'] = 'submitted';
            }
            
            if (in_array('submission_date', $columns)) {
                $fields[] = 'submission_date';
                $placeholders[] = ':submission_date';
                $params['submission_date'] = $finalSubmissionDate;
            }
            
            if (in_array('updated_at', $columns)) {
                $fields[] = 'updated_at';
                $placeholders[] = ':updated_at';
                $params['updated_at'] = $finalSubmissionDate;
            }
            
            // Construire et exécuter la requête
            $sql = "INSERT INTO evaluations (" . implode(", ", $fields) . ") VALUES (" . implode(", ", $placeholders) . ")";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            
            $evaluationsCreated++;
        }
        
        // Mettre à jour la barre de progression
        $progress = round(($index + 1) / count($assignments) * 100);
        echo "<script>
                document.querySelector('.progress-bar').style.width = '{$progress}%';
                document.querySelector('.progress-bar').textContent = '{$progress}%';
              </script>";
        echo str_pad('', 4096); // Padding pour forcer le flush
        ob_flush();
        flush();
    }
    
    // Valider la transaction
    $db->commit();
    
    echo "<h2>✅ Opération réussie!</h2>";
    echo "<p class='success'>$evaluationsCreated évaluations ont été créées.</p>";
    
    // Résumé des évaluations par type
    $typeQuery = $db->query("SELECT type, COUNT(*) as count FROM evaluations GROUP BY type");
    $typeStats = $typeQuery->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($typeStats) > 0) {
        echo "<h3>Répartition par type d'évaluation</h3>";
        echo "<table>
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Nombre</th>
                    </tr>
                </thead>
                <tbody>";
        
        foreach ($typeStats as $stat) {
            $typeName = '';
            switch ($stat['type']) {
                case 'mid_term': $typeName = 'Mi-parcours'; break;
                case 'final': $typeName = 'Finale'; break;
                case 'student': $typeName = 'Auto-évaluation'; break;
                default: $typeName = $stat['type']; break;
            }
            
            echo "<tr>
                    <td>$typeName</td>
                    <td>{$stat['count']}</td>
                  </tr>";
        }
        
        echo "</tbody></table>";
    }
    
} catch (PDOException $e) {
    // Annuler la transaction en cas d'erreur
    if (isset($db)) {
        $db->rollBack();
    }
    
    echo "<div class='error'>
            <h2>❌ Erreur</h2>
            <p>Erreur lors de la réinitialisation des évaluations: " . h($e->getMessage()) . "</p>
          </div>";
}

echo "    <div class='actions'>
            <a href='/tutoring/views/tutor/evaluations.php' class='btn'>Voir les évaluations (tuteur)</a>
            <a href='/tutoring/views/student/evaluations.php' class='btn'>Voir les évaluations (étudiant)</a>
            <a href='/tutoring/' class='btn'>Retour à l'accueil</a>
        </div>
    </div>
</body>
</html>";
?>