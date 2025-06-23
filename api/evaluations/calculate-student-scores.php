<?php
/**
 * Script pour calculer et stocker les scores des étudiants
 * Ce script peut être exécuté par un cron job pour mettre à jour les scores de tous les étudiants
 */

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../utils.php';

// Vérifier que l'utilisateur est administrateur, sauf si exécuté en ligne de commande
if (php_sapi_name() !== 'cli' && (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin')) {
    die("Accès non autorisé. Vous devez être administrateur pour exécuter ce script.");
}

// Récupérer tous les étudiants
try {
    $db->beginTransaction();
    
    // Vérifier que la table student_scores existe
    try {
        $db->query("SELECT 1 FROM student_scores LIMIT 1");
    } catch (PDOException $e) {
        // Si la table n'existe pas, la créer
        $createTableSQL = "
            CREATE TABLE IF NOT EXISTS student_scores (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_id INT NOT NULL,
                assignment_id INT NOT NULL,
                technical_score DECIMAL(3,1) DEFAULT 0,
                communication_score DECIMAL(3,1) DEFAULT 0,
                teamwork_score DECIMAL(3,1) DEFAULT 0,
                autonomy_score DECIMAL(3,1) DEFAULT 0,
                average_score DECIMAL(3,1) DEFAULT 0,
                completed_evaluations INT DEFAULT 0,
                total_evaluations INT DEFAULT 5,
                last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_student_assignment (student_id, assignment_id)
            )";
        
        $db->exec($createTableSQL);
        echo "Table student_scores créée avec succès.\n";
    }
    
    // Récupérer tous les étudiants ayant une affectation
    $query = "SELECT s.id as student_id, a.id as assignment_id 
              FROM students s 
              JOIN assignments a ON s.id = a.student_id
              WHERE a.status IN ('active', 'confirmed')";
    
    $stmt = $db->query($query);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Nombre d'étudiants trouvés: " . count($students) . "\n";
    
    // Pour chaque étudiant, calculer et stocker les scores
    foreach ($students as $student) {
        $student_id = $student['student_id'];
        $assignment_id = $student['assignment_id'];
        
        echo "Traitement de l'étudiant ID $student_id (Affectation ID $assignment_id)...\n";
        
        // Récupérer les évaluations de cet étudiant
        $evalQuery = "SELECT e.*, u.first_name, u.last_name 
                     FROM evaluations e 
                     JOIN users u ON e.evaluator_id = u.id 
                     WHERE e.assignment_id = :assignment_id";
        
        $evalStmt = $db->prepare($evalQuery);
        $evalStmt->execute(['assignment_id' => $assignment_id]);
        $evaluations = $evalStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "  - Nombre d'évaluations trouvées: " . count($evaluations) . "\n";
        
        // Calculer les scores (même logique que dans l'API update-student-scores.php)
        $stats = [
            'technical_score' => 0,
            'communication_score' => 0,
            'teamwork_score' => 0,
            'autonomy_score' => 0,
            'average_score' => 0,
            'completed_evaluations' => 0,
            'total_evaluations' => 5
        ];
        
        // Définir des mappings pour les critères
        $technicalMappings = [
            'compétences techniques' => true,
            'technical skills' => true,
            'maîtrise des technologies' => true,
            'quality of work' => true,
            'qualité du travail' => true,
            'problem solving' => true,
            'résolution de problèmes' => true,
            'documentation' => true,
            'technical mastery' => true,
            'work quality' => true
        ];
        
        $communicationMappings = [
            'communication' => true,
            'présentation' => true,
            'presentation' => true,
            'expression' => true,
            'clarté' => true,
            'clarity' => true
        ];
        
        $teamworkMappings = [
            'travail en équipe' => true,
            'teamwork' => true,
            'intégration dans l\'équipe' => true,
            'team integration' => true,
            'collaboration' => true,
            'esprit d\'équipe' => true,
            'team spirit' => true
        ];
        
        $autonomyMappings = [
            'autonomie' => true,
            'autonomy' => true,
            'initiative' => true,
            'initiative et autonomie' => true,
            'indépendance' => true,
            'independence' => true,
            'self-management' => true,
            'gestion autonome' => true
        ];
        
        // Mots clés pour la recherche
        $technicalKeywords = ['technique', 'technical', 'maîtrise', 'qualité', 'problème', 'documentation', 'quality', 'problem', 'tech', 'code', 'programming', 'développement', 'development'];
        $communicationKeywords = ['communication', 'expression', 'présentation', 'clarté', 'clarity', 'parler', 'speaking', 'écoute', 'listening', 'échange', 'exchange'];
        $teamworkKeywords = ['équipe', 'team', 'collaboration', 'collègues', 'colleagues', 'groupe', 'group', 'collectif', 'collective', 'coopération', 'cooperation'];
        $autonomyKeywords = ['autonomie', 'autonomy', 'initiative', 'indépendance', 'independence', 'self', 'responsabilité', 'responsibility', 'décision', 'decision'];
        
        // Fonction utilitaire pour vérifier si une chaîne contient un des mots-clés
        function containsAnyKeyword($string, $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($string, $keyword) !== false) {
                    return true;
                }
            }
            return false;
        }
        
        // Compteurs pour chaque catégorie
        $categoryCounts = [
            'technical' => 0,
            'communication' => 0,
            'teamwork' => 0,
            'autonomy' => 0
        ];
        
        // Calculer les scores à partir des critères d'évaluation
        $totalScore = 0;
        $evaluationCount = 0;
        
        foreach ($evaluations as $eval) {
            // Ne prendre en compte que les évaluations de type mid_term et final (pas les auto-évaluations)
            if (!isset($eval['type']) || !in_array($eval['type'], ['mid_term', 'final'])) {
                continue;
            }
            
            $evaluationCount++;
            $stats['completed_evaluations']++;
            
            // Ajouter le score global à la moyenne
            $score = isset($eval['score']) ? $eval['score'] : 0;
            // Convertir le score si nécessaire (de 0-20 à 0-5)
            if ($score > 5) {
                $score = min(5, round($score / 4, 1));
            }
            $totalScore += $score;
            
            // Récupérer les critères s'ils existent
            $criteria = [];
            if (isset($eval['criteria_scores']) && !empty($eval['criteria_scores'])) {
                $criteria = json_decode($eval['criteria_scores'], true) ?: [];
                
                // Transformer le format des critères si nécessaire
                if (is_array($criteria)) {
                    $formattedCriteria = [];
                    foreach ($criteria as $key => $value) {
                        // Si c'est déjà un tableau avec name et score, l'utiliser tel quel
                        if (isset($value['name']) && isset($value['score'])) {
                            $formattedCriteria[] = $value;
                        } 
                        // Sinon, créer un tableau avec name et score
                        else {
                            $formattedCriteria[] = [
                                'name' => $key,
                                'score' => $value
                            ];
                        }
                    }
                    $criteria = $formattedCriteria;
                }
            }
            
            // Traiter chaque critère
            foreach ($criteria as $criterion) {
                if (!isset($criterion['name']) || !isset($criterion['score'])) {
                    continue;
                }
                
                $name = strtolower(trim($criterion['name']));
                
                // Valider et normaliser le score
                $criterionScore = floatval($criterion['score']);
                if ($criterionScore > 5) {
                    $criterionScore = min(5, round($criterionScore / 4, 1)); // Convertir de 0-20 à 0-5 si nécessaire
                }
                
                // Vérifier d'abord dans les mappings spécifiques
                $categoryFound = false;
                
                if (isset($technicalMappings[$name])) {
                    $stats['technical_score'] += $criterionScore;
                    $categoryCounts['technical']++;
                    $categoryFound = true;
                } elseif (isset($communicationMappings[$name])) {
                    $stats['communication_score'] += $criterionScore;
                    $categoryCounts['communication']++;
                    $categoryFound = true;
                } elseif (isset($teamworkMappings[$name])) {
                    $stats['teamwork_score'] += $criterionScore;
                    $categoryCounts['teamwork']++;
                    $categoryFound = true;
                } elseif (isset($autonomyMappings[$name])) {
                    $stats['autonomy_score'] += $criterionScore;
                    $categoryCounts['autonomy']++;
                    $categoryFound = true;
                }
                
                // Si pas trouvé dans les mappings spécifiques, rechercher par mots-clés
                if (!$categoryFound) {
                    if (containsAnyKeyword($name, $technicalKeywords)) {
                        $stats['technical_score'] += $criterionScore;
                        $categoryCounts['technical']++;
                    } elseif (containsAnyKeyword($name, $communicationKeywords)) {
                        $stats['communication_score'] += $criterionScore;
                        $categoryCounts['communication']++;
                    } elseif (containsAnyKeyword($name, $teamworkKeywords)) {
                        $stats['teamwork_score'] += $criterionScore;
                        $categoryCounts['teamwork']++;
                    } elseif (containsAnyKeyword($name, $autonomyKeywords)) {
                        $stats['autonomy_score'] += $criterionScore;
                        $categoryCounts['autonomy']++;
                    } else {
                        // Si on ne peut pas classifier, on met dans la catégorie technique par défaut
                        $stats['technical_score'] += $criterionScore;
                        $categoryCounts['technical']++;
                    }
                }
            }
        }
        
        // Calculer les moyennes
        $stats['average_score'] = $evaluationCount > 0 ? round($totalScore / $evaluationCount, 1) : 0;
        
        $stats['technical_score'] = $categoryCounts['technical'] > 0 ? 
            round($stats['technical_score'] / $categoryCounts['technical'], 1) : 0;
            
        $stats['communication_score'] = $categoryCounts['communication'] > 0 ? 
            round($stats['communication_score'] / $categoryCounts['communication'], 1) : 0;
            
        $stats['teamwork_score'] = $categoryCounts['teamwork'] > 0 ? 
            round($stats['teamwork_score'] / $categoryCounts['teamwork'], 1) : 0;
            
        $stats['autonomy_score'] = $categoryCounts['autonomy'] > 0 ? 
            round($stats['autonomy_score'] / $categoryCounts['autonomy'], 1) : 0;
        
        // Si aucune donnée n'a été trouvée mais qu'il y a des évaluations, utiliser un score par défaut
        if ($evaluationCount > 0 && 
            $categoryCounts['technical'] == 0 && 
            $categoryCounts['communication'] == 0 && 
            $categoryCounts['teamwork'] == 0 && 
            $categoryCounts['autonomy'] == 0) {
            
            // Utiliser le score moyen comme score par défaut pour toutes les catégories
            $defaultScore = $stats['average_score'];
            
            // Utiliser ce score par défaut pour toutes les catégories
            $stats['technical_score'] = $defaultScore;
            $stats['communication_score'] = $defaultScore;
            $stats['teamwork_score'] = $defaultScore;
            $stats['autonomy_score'] = $defaultScore;
        }
        
        // Assurer que tous les scores sont bien entre 0 et 5
        $stats['technical_score'] = max(0, min(5, $stats['technical_score']));
        $stats['communication_score'] = max(0, min(5, $stats['communication_score']));
        $stats['teamwork_score'] = max(0, min(5, $stats['teamwork_score']));
        $stats['autonomy_score'] = max(0, min(5, $stats['autonomy_score']));
        $stats['average_score'] = max(0, min(5, $stats['average_score']));
        
        echo "  - Scores calculés: technique=" . $stats['technical_score'] . 
             ", communication=" . $stats['communication_score'] . 
             ", travail d'équipe=" . $stats['teamwork_score'] . 
             ", autonomie=" . $stats['autonomy_score'] . 
             ", moyenne=" . $stats['average_score'] . "\n";
        
        // Mettre à jour ou insérer les scores dans la base de données
        $insertQuery = "INSERT INTO student_scores 
                  (student_id, assignment_id, technical_score, communication_score, teamwork_score, autonomy_score, 
                   average_score, completed_evaluations, total_evaluations) 
                  VALUES 
                  (:student_id, :assignment_id, :technical_score, :communication_score, :teamwork_score, :autonomy_score,
                   :average_score, :completed_evaluations, :total_evaluations)
                  ON DUPLICATE KEY UPDATE 
                  technical_score = :technical_score, 
                  communication_score = :communication_score,
                  teamwork_score = :teamwork_score,
                  autonomy_score = :autonomy_score,
                  average_score = :average_score,
                  completed_evaluations = :completed_evaluations,
                  total_evaluations = :total_evaluations";
        
        $insertStmt = $db->prepare($insertQuery);
        $insertStmt->execute([
            'student_id' => $student_id,
            'assignment_id' => $assignment_id,
            'technical_score' => $stats['technical_score'],
            'communication_score' => $stats['communication_score'],
            'teamwork_score' => $stats['teamwork_score'],
            'autonomy_score' => $stats['autonomy_score'],
            'average_score' => $stats['average_score'],
            'completed_evaluations' => $stats['completed_evaluations'],
            'total_evaluations' => $stats['total_evaluations']
        ]);
        
        echo "  - Scores enregistrés pour l'étudiant ID $student_id\n";
    }
    
    $db->commit();
    echo "Tous les scores ont été calculés et enregistrés avec succès.\n";
    
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    echo "Erreur: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
?>