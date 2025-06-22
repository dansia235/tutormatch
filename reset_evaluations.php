<?php
/**
 * Script pour vider et régénérer les données d'évaluation dans la base de données
 * Ce script supprime toutes les évaluations existantes et crée de nouvelles évaluations cohérentes
 */

// Inclure le fichier d'initialisation
// Configuration d'encodage UTF-8
ini_set('default_charset', 'UTF-8');

// Ajouter une fonction d'échappement similaire à celle de init.php
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Connexion directe à la base de données sans utiliser init.php
try {
    // Charger les informations de connexion à la base de données
    if (file_exists(__DIR__ . '/config/database.php')) {
        $config = include __DIR__ . '/config/database.php';
    } else if (file_exists(__DIR__ . '/config/database.example.php')) {
        $config = include __DIR__ . '/config/database.example.php';
    } else {
        throw new Exception("Fichier de configuration de la base de données introuvable");
    }
    
    // Établir la connexion à la base de données
    $db = new PDO(
        "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}", 
        $config['username'], 
        $config['password']
    );
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

// Commencer la régénération des évaluations
try {
    
    if (!isset($db) || !($db instanceof PDO)) {
        die("Erreur: Base de données non disponible. Vérifiez la connexion.");
    }
    
    // Début de transaction
    $db->beginTransaction();
    
    // 1. Supprimer toutes les évaluations existantes
    $db->exec("DELETE FROM evaluations");
    echo "<p>Toutes les évaluations ont été supprimées.</p>";
    
    // 1.1 Supprimer également les documents d'évaluation qui pourraient créer des conflits
    $db->exec("DELETE FROM documents WHERE type IN ('evaluation', 'self_evaluation')");
    echo "<p>Tous les documents d'évaluation ont été supprimés.</p>";
    
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
        WHERE a.status IN ('active', 'confirmed')
    ");
    
    $assignments = $assignmentsQuery->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Nombre d'affectations trouvées: " . count($assignments) . "</p>";
    
    // 3. Pour chaque affectation, créer des évaluations cohérentes
    $today = new DateTime();
    $evaluationsCreated = 0;
    
    foreach ($assignments as $assignment) {
        // Calculer les dates d'évaluation en fonction des dates de stage
        $startDate = new DateTime($assignment['start_date']);
        $endDate = new DateTime($assignment['end_date']);
        
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
        
        // Générer des scores basés sur une progression réaliste
        $midTermBaseScore = rand(60, 75); // 12-15 sur 20
        $finalBaseScore = $midTermBaseScore + rand(5, 15); // Amélioration entre mi-parcours et finale
        
        // Critères d'évaluation communs
        $criteriaScores = [
            'mid_term' => [
                'technical_skills' => $midTermBaseScore + rand(-5, 5),
                'professional_behavior' => $midTermBaseScore + rand(-5, 5),
                'communication' => $midTermBaseScore + rand(-5, 5),
                'initiative' => $midTermBaseScore + rand(-5, 5),
                'teamwork' => $midTermBaseScore + rand(-5, 5),
                'punctuality' => $midTermBaseScore + rand(-5, 5)
            ],
            'final' => [
                'technical_skills' => $finalBaseScore + rand(-5, 5),
                'professional_behavior' => $finalBaseScore + rand(-5, 5),
                'communication' => $finalBaseScore + rand(-5, 5),
                'initiative' => $finalBaseScore + rand(-5, 5),
                'teamwork' => $finalBaseScore + rand(-5, 5),
                'punctuality' => $finalBaseScore + rand(-5, 5)
            ]
        ];
        
        // S'assurer que les scores sont entre 0 et 100 (0-20 sur échelle de 5)
        foreach ($criteriaScores as $type => $criteria) {
            foreach ($criteria as $key => $value) {
                $criteriaScores[$type][$key] = max(0, min(100, $value));
            }
        }
        
        // 1. Créer l'évaluation de mi-parcours par le tuteur
        if ($stageHalfway) {
            $midTermScore = round(array_sum($criteriaScores['mid_term']) / count($criteriaScores['mid_term']));
            
            $stmt = $db->prepare("
                INSERT INTO evaluations (
                    assignment_id, evaluator_id, evaluatee_id, type, score, 
                    feedback, strengths, areas_to_improve, submission_date
                ) VALUES (
                    :assignment_id, :evaluator_id, :evaluatee_id, :type, :score,
                    :feedback, :strengths, :areas_to_improve, :submission_date
                )
            ");
            
            $midTermFeedback = "L'étudiant montre une progression satisfaisante. Ses compétences techniques sont en développement et son intégration dans l'équipe est bonne. Il doit améliorer sa communication et sa documentation.";
            $midTermStrengths = "Bonne maîtrise technique, autonomie dans les tâches assignées";
            $midTermAreasToImprove = "Documentation du code\nCommunication proactive des problèmes\nParticipation aux réunions";
            
            $submissionDate = $midTermDate->format('Y-m-d H:i:s');
            
            // Assurer que le score est dans l'échelle 0-20 et pas plus de 20
            $midTermScoreCapped = min(20, $midTermScore);
            
            $stmt->execute([
                'assignment_id' => $assignmentId,
                'evaluator_id' => $teacherUserId,
                'evaluatee_id' => $studentUserId,
                'type' => 'mid_term',
                'score' => $midTermScoreCapped,
                'feedback' => $midTermFeedback,
                'strengths' => $midTermStrengths,
                'areas_to_improve' => $midTermAreasToImprove,
                'submission_date' => $submissionDate
            ]);
            
            $evaluationsCreated++;
        }
        
        // 2. Créer l'auto-évaluation de mi-parcours par l'étudiant
        if ($stageHalfway) {
            // L'auto-évaluation est légèrement plus positive que celle du tuteur
            $selfMidTermCriteria = [];
            foreach ($criteriaScores['mid_term'] as $key => $value) {
                $selfMidTermCriteria[$key] = min(100, $value + rand(0, 10));
            }
            
            $selfMidTermScore = round(array_sum($selfMidTermCriteria) / count($selfMidTermCriteria));
            
            $stmt = $db->prepare("
                INSERT INTO evaluations (
                    assignment_id, evaluator_id, evaluatee_id, type, score, 
                    feedback, strengths, areas_to_improve, submission_date
                ) VALUES (
                    :assignment_id, :evaluator_id, :evaluatee_id, :type, :score,
                    :feedback, :strengths, :areas_to_improve, :submission_date
                )
            ");
            
            $selfFeedback = "Je pense avoir bien progressé dans mon stage. J'ai acquis de nouvelles compétences techniques et j'ai pu contribuer à plusieurs projets. Je dois améliorer ma communication avec l'équipe.";
            $selfStrengths = "Apprentissage rapide des technologies, implication dans les projets";
            $selfAreasToImprove = "Communication plus régulière\nMeilleure organisation du temps";
            
            $selfSubmissionDate = (clone $midTermDate)->add(new DateInterval('P3D'))->format('Y-m-d H:i:s');
            
            // Assurer que le score est dans l'échelle 0-20 et pas plus de 20
            $selfMidTermScoreCapped = min(20, $selfMidTermScore);
            
            $stmt->execute([
                'assignment_id' => $assignmentId,
                'evaluator_id' => $studentUserId,
                'evaluatee_id' => $studentUserId,
                'type' => 'student',
                'score' => $selfMidTermScoreCapped,
                'feedback' => $selfFeedback,
                'strengths' => $selfStrengths,
                'areas_to_improve' => $selfAreasToImprove,
                'submission_date' => $selfSubmissionDate
            ]);
            
            $evaluationsCreated++;
        }
        
        // 3. Créer l'évaluation finale par le tuteur
        if ($stageFinished) {
            $finalScore = round(array_sum($criteriaScores['final']) / count($criteriaScores['final']));
            
            $stmt = $db->prepare("
                INSERT INTO evaluations (
                    assignment_id, evaluator_id, evaluatee_id, type, score, 
                    feedback, strengths, areas_to_improve, submission_date
                ) VALUES (
                    :assignment_id, :evaluator_id, :evaluatee_id, :type, :score,
                    :feedback, :strengths, :areas_to_improve, :submission_date
                )
            ");
            
            $finalFeedback = "L'étudiant a réalisé d'excellents progrès tout au long de son stage. Il a su s'adapter aux défis techniques et a démontré une bonne capacité d'intégration dans l'équipe. Ses compétences techniques se sont nettement améliorées.";
            $finalStrengths = "Maîtrise technique approfondie, autonomie, capacité d'analyse et résolution de problèmes";
            $finalAreasToImprove = "Communication technique avec les équipes non-techniques\nPrise de recul sur les solutions implémentées";
            
            $finalDate = (clone $endDate)->sub(new DateInterval('P5D'));
            $finalSubmissionDate = $finalDate->format('Y-m-d H:i:s');
            
            // Assurer que le score est dans l'échelle 0-20 et pas plus de 20
            $finalScoreCapped = min(20, $finalScore);
            
            $stmt->execute([
                'assignment_id' => $assignmentId,
                'evaluator_id' => $teacherUserId,
                'evaluatee_id' => $studentUserId,
                'type' => 'final',
                'score' => $finalScoreCapped,
                'feedback' => $finalFeedback,
                'strengths' => $finalStrengths,
                'areas_to_improve' => $finalAreasToImprove,
                'submission_date' => $finalSubmissionDate
            ]);
            
            $evaluationsCreated++;
        }
    }
    
    // Valider la transaction
    $db->commit();
    
    echo "<p>Opération réussie! $evaluationsCreated évaluations ont été créées.</p>";
    echo "<p><a href='/tutoring/views/tutor/evaluations.php'>Voir les évaluations (tuteur)</a></p>";
    echo "<p><a href='/tutoring/views/student/evaluations.php'>Voir les évaluations (étudiant)</a></p>";
    
} catch (PDOException $e) {
    // Annuler la transaction en cas d'erreur
    if (isset($db)) {
        $db->rollBack();
    }
    
    echo "<p>Erreur lors de la réinitialisation des évaluations: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>