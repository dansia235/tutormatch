<?php
/**
 * API pour mettre à jour les scores d'un étudiant
 * Endpoint: /api/evaluations/update-student-scores.php
 * Méthode: POST
 * 
 * Cette API calcule et stocke les scores de compétences d'un étudiant
 * pour assurer la cohérence entre les vues étudiant et tuteur
 */

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../utils.php';

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    sendJsonResponse([
        'success' => false,
        'message' => 'Non autorisé - Utilisateur non connecté'
    ], 401);
    exit;
}

// Vérifier que l'utilisateur a le rôle approprié (admin ou tuteur)
if ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'teacher') {
    sendJsonResponse([
        'success' => false,
        'message' => 'Accès non autorisé - Rôle administrateur ou tuteur requis'
    ], 403);
    exit;
}

// Récupérer les données envoyées
$inputData = json_decode(file_get_contents('php://input'), true);

// Vérifier les données requises
if (!isset($inputData['student_id']) || !is_numeric($inputData['student_id'])) {
    sendJsonResponse([
        'success' => false,
        'message' => 'ID étudiant invalide ou manquant'
    ], 400);
    exit;
}

$student_id = (int)$inputData['student_id'];

try {
    // Récupérer l'affectation de l'étudiant
    $studentModel = new Student($db);
    $assignment = $studentModel->getAssignment($student_id);
    
    if (!$assignment) {
        sendJsonResponse([
            'success' => false,
            'message' => 'Aucune affectation trouvée pour cet étudiant'
        ], 404);
        exit;
    }
    
    $assignment_id = $assignment['id'];
    
    // Récupérer les évaluations de l'étudiant
    $query = "SELECT e.*, u.first_name, u.last_name 
              FROM evaluations e 
              JOIN users u ON e.evaluator_id = u.id 
              WHERE e.assignment_id = :assignment_id";
    $stmt = $db->prepare($query);
    $stmt->execute(['assignment_id' => $assignment_id]);
    $evaluations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Récupération des évaluations pour l'étudiant ID $student_id: " . count($evaluations) . " trouvées");
    
    // Initialiser les statistiques
    $stats = [
        'technical_score' => 0,
        'communication_score' => 0,
        'teamwork_score' => 0,
        'autonomy_score' => 0,
        'average_score' => 0,
        'completed_evaluations' => count($evaluations),
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
    
    // Initialiser les scores des compétences spécifiques
    $technicalMasteryScore = 0;
    $workQualityScore = 0;
    $problemSolvingScore = 0;
    $documentationScore = 0;
    $deadlineRespectScore = 0;
    
    $technicalMasteryCount = 0;
    $workQualityCount = 0;
    $problemSolvingCount = 0;
    $documentationCount = 0;
    $deadlineRespectCount = 0;
    
    // Mappings spécifiques pour les compétences détaillées
    $technicalMasteryMappings = ['maîtrise des technologies', 'technical mastery', 'compétence technique', 'technical skill'];
    $workQualityMappings = ['qualité du travail', 'work quality', 'qualité', 'quality'];
    $problemSolvingMappings = ['résolution de problèmes', 'problem solving', 'problème', 'problem'];
    $documentationMappings = ['documentation', 'docs', 'document', 'documentation technique'];
    $deadlineRespectMappings = ['respect des délais', 'deadline respect', 'délai', 'deadline', 'ponctualité', 'punctuality'];
    
    // Parcourir à nouveau les critères pour les compétences spécifiques
    foreach ($evaluations as $eval) {
        // Ne prendre en compte que les évaluations de type mid_term et final
        if (!isset($eval['type']) || !in_array($eval['type'], ['mid_term', 'final'])) {
            continue;
        }
        
        // Récupérer les critères
        $criteria = [];
        if (isset($eval['criteria_scores']) && !empty($eval['criteria_scores'])) {
            $criteria = json_decode($eval['criteria_scores'], true) ?: [];
        }
        
        // Traiter chaque critère
        foreach ($criteria as $criterion) {
            if (!isset($criterion['name']) || !isset($criterion['score'])) {
                continue;
            }
            
            $name = strtolower(trim($criterion['name']));
            $score = floatval($criterion['score']);
            if ($score > 5) {
                $score = min(5, round($score / 4, 1));
            }
            
            // Vérifier pour chaque compétence spécifique
            $found = false;
            
            // Maîtrise des technologies
            foreach ($technicalMasteryMappings as $keyword) {
                if (strpos($name, $keyword) !== false) {
                    $technicalMasteryScore += $score;
                    $technicalMasteryCount++;
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                // Qualité du travail
                foreach ($workQualityMappings as $keyword) {
                    if (strpos($name, $keyword) !== false) {
                        $workQualityScore += $score;
                        $workQualityCount++;
                        $found = true;
                        break;
                    }
                }
            }
            
            if (!$found) {
                // Résolution de problèmes
                foreach ($problemSolvingMappings as $keyword) {
                    if (strpos($name, $keyword) !== false) {
                        $problemSolvingScore += $score;
                        $problemSolvingCount++;
                        $found = true;
                        break;
                    }
                }
            }
            
            if (!$found) {
                // Documentation
                foreach ($documentationMappings as $keyword) {
                    if (strpos($name, $keyword) !== false) {
                        $documentationScore += $score;
                        $documentationCount++;
                        $found = true;
                        break;
                    }
                }
            }
            
            if (!$found) {
                // Respect des délais
                foreach ($deadlineRespectMappings as $keyword) {
                    if (strpos($name, $keyword) !== false) {
                        $deadlineRespectScore += $score;
                        $deadlineRespectCount++;
                        break;
                    }
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
    
    // Calculer les moyennes des compétences spécifiques
    $stats['technical_mastery'] = $technicalMasteryCount > 0 ? 
        round($technicalMasteryScore / $technicalMasteryCount, 1) : 0;
        
    $stats['work_quality'] = $workQualityCount > 0 ? 
        round($workQualityScore / $workQualityCount, 1) : 0;
        
    $stats['problem_solving'] = $problemSolvingCount > 0 ? 
        round($problemSolvingScore / $problemSolvingCount, 1) : 0;
        
    $stats['documentation'] = $documentationCount > 0 ? 
        round($documentationScore / $documentationCount, 1) : 0;
        
    $stats['deadline_respect'] = $deadlineRespectCount > 0 ? 
        round($deadlineRespectScore / $deadlineRespectCount, 1) : 0;
    
    // Si aucune donnée n'a été trouvée mais qu'il y a des évaluations, utiliser un score par défaut
    if ($evaluationCount > 0) {
        // Utiliser le score moyen comme score par défaut pour toutes les catégories
        $defaultScore = $stats['average_score'];
        
        // Utiliser ce score par défaut pour les catégories sans données
        if ($categoryCounts['technical'] == 0) {
            $stats['technical_score'] = $defaultScore;
        }
        
        if ($categoryCounts['communication'] == 0) {
            $stats['communication_score'] = $defaultScore;
        }
        
        if ($categoryCounts['teamwork'] == 0) {
            $stats['teamwork_score'] = $defaultScore;
        }
        
        if ($categoryCounts['autonomy'] == 0) {
            $stats['autonomy_score'] = $defaultScore;
        }
        
        // Compétences spécifiques
        if ($technicalMasteryCount == 0) {
            $stats['technical_mastery'] = $defaultScore;
        }
        
        if ($workQualityCount == 0) {
            $stats['work_quality'] = $defaultScore;
        }
        
        if ($problemSolvingCount == 0) {
            $stats['problem_solving'] = $defaultScore;
        }
        
        if ($documentationCount == 0) {
            $stats['documentation'] = $defaultScore;
        }
        
        if ($deadlineRespectCount == 0) {
            $stats['deadline_respect'] = $defaultScore;
        }
    }
    
    // Assurer que tous les scores sont bien entre 0 et 5
    $stats['technical_score'] = max(0, min(5, $stats['technical_score']));
    $stats['communication_score'] = max(0, min(5, $stats['communication_score']));
    $stats['teamwork_score'] = max(0, min(5, $stats['teamwork_score']));
    $stats['autonomy_score'] = max(0, min(5, $stats['autonomy_score']));
    $stats['average_score'] = max(0, min(5, $stats['average_score']));
    
    // Limiter les scores des compétences spécifiques
    $stats['technical_mastery'] = max(0, min(5, $stats['technical_mastery']));
    $stats['work_quality'] = max(0, min(5, $stats['work_quality']));
    $stats['problem_solving'] = max(0, min(5, $stats['problem_solving']));
    $stats['documentation'] = max(0, min(5, $stats['documentation']));
    $stats['deadline_respect'] = max(0, min(5, $stats['deadline_respect']));
    
    // Mettre à jour ou insérer les scores dans la base de données
    $query = "INSERT INTO student_scores 
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
    
    $stmt = $db->prepare($query);
    $stmt->execute([
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
    
    // Journaliser les résultats
    error_log("Mise à jour des scores pour l'étudiant ID $student_id: " . 
              "technique=" . $stats['technical_score'] . ", " .
              "communication=" . $stats['communication_score'] . ", " .
              "travail d'équipe=" . $stats['teamwork_score'] . ", " .
              "autonomie=" . $stats['autonomy_score'] . ", " .
              "moyenne=" . $stats['average_score']);
    
    // Retourner les scores mis à jour
    sendJsonResponse([
        'success' => true,
        'message' => 'Scores mis à jour avec succès',
        'stats' => $stats
    ]);
    
} catch (Exception $e) {
    error_log("Erreur lors de la mise à jour des scores: " . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'message' => 'Erreur lors de la mise à jour des scores: ' . $e->getMessage()
    ], 500);
}
?>