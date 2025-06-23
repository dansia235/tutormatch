<?php
/**
 * Script pour synchroniser les métadonnées des documents d'évaluation avec la table evaluations
 * Ce script recherche les documents de type évaluation et met à jour leurs métadonnées
 * pour qu'elles correspondent aux évaluations dans la base de données
 */

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../utils.php';

// Vérifier l'authentification
if (!isset($_SESSION['user_id'])) {
    sendJsonResponse([
        'success' => false,
        'message' => 'Non autorisé - Utilisateur non connecté'
    ], 401);
    exit;
}

// Vérifier le rôle d'administrateur
if ($_SESSION['user_role'] !== 'admin') {
    sendJsonResponse([
        'success' => false,
        'message' => 'Accès non autorisé - Nécessite des droits d\'administrateur'
    ], 403);
    exit;
}

try {
    // Supprimer d'abord tous les documents d'évaluation problématiques
    $deleteQuery = $db->prepare("
        DELETE FROM documents 
        WHERE description = :description AND type = 'evaluation'
    ");
    $description = "Évaluation du tuteur pour la période mi-parcours du stage";
    $deleteQuery->bindParam(':description', $description, PDO::PARAM_STR);
    $deleteQuery->execute();
    
    $documentsDeleted = $deleteQuery->rowCount();
    
    // Récupérer tous les documents de type évaluation
    $documentQuery = $db->query("
        SELECT d.*, u.first_name, u.last_name
        FROM documents d
        JOIN users u ON d.user_id = u.id
        WHERE d.type IN ('evaluation', 'self_evaluation', 'mid_term', 'final')
    ");
    
    $documents = $documentQuery->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer toutes les évaluations
    $evaluationQuery = $db->query("
        SELECT e.*, a.student_id, a.teacher_id
        FROM evaluations e
        JOIN assignments a ON e.assignment_id = a.id
    ");
    
    $evaluations = $evaluationQuery->fetchAll(PDO::FETCH_ASSOC);
    
    // Indexer les évaluations par student_id et type pour faciliter la recherche
    $evaluationsByStudent = [];
    foreach ($evaluations as $eval) {
        $studentId = $eval['student_id'];
        $type = $eval['type'];
        
        if (!isset($evaluationsByStudent[$studentId])) {
            $evaluationsByStudent[$studentId] = [];
        }
        
        if (!isset($evaluationsByStudent[$studentId][$type])) {
            $evaluationsByStudent[$studentId][$type] = [];
        }
        
        $evaluationsByStudent[$studentId][$type][] = $eval;
    }
    
    // Compter les mises à jour
    $updatedDocuments = 0;
    $skippedDocuments = 0;
    
    // Parcourir tous les documents et mettre à jour leurs métadonnées
    foreach ($documents as $document) {
        $userId = $document['user_id'];
        $documentId = $document['id'];
        $documentType = $document['type'];
        
        // Récupérer l'étudiant associé à ce document
        $studentQuery = $db->prepare("SELECT * FROM students WHERE user_id = ?");
        $studentQuery->execute([$userId]);
        $student = $studentQuery->fetch(PDO::FETCH_ASSOC);
        
        if (!$student) {
            // Essayer de trouver l'étudiant si l'utilisateur est un tuteur
            $teacherQuery = $db->prepare("SELECT * FROM teachers WHERE user_id = ?");
            $teacherQuery->execute([$userId]);
            $teacher = $teacherQuery->fetch(PDO::FETCH_ASSOC);
            
            if ($teacher) {
                // Récupérer le premier étudiant associé à ce tuteur
                $assignmentQuery = $db->prepare("
                    SELECT s.* FROM assignments a
                    JOIN students s ON a.student_id = s.id
                    WHERE a.teacher_id = ?
                    LIMIT 1
                ");
                $assignmentQuery->execute([$teacher['id']]);
                $student = $assignmentQuery->fetch(PDO::FETCH_ASSOC);
            }
        }
        
        if (!$student) {
            $skippedDocuments++;
            continue; // Passer au document suivant si pas d'étudiant trouvé
        }
        
        $studentId = $student['id'];
        
        // Mapper le type de document au type d'évaluation
        $evalType = 'mid_term'; // Par défaut
        if ($documentType === 'self_evaluation') {
            $evalType = 'student';
        } else if ($documentType === 'final') {
            $evalType = 'final';
        }
        
        // Trouver l'évaluation correspondante
        if (isset($evaluationsByStudent[$studentId]) && isset($evaluationsByStudent[$studentId][$evalType])) {
            $matchingEvals = $evaluationsByStudent[$studentId][$evalType];
            
            if (count($matchingEvals) > 0) {
                // Prendre la première évaluation correspondante
                $evaluation = $matchingEvals[0];
                
                // Préparer les métadonnées mises à jour
                $metadata = json_decode($document['metadata'] ?? '{}', true) ?: [];
                
                // Convertir le score de 0-100 à 0-5
                $score = isset($evaluation['score']) ? $evaluation['score'] : 0;
                if ($score > 5) {
                    $score = round($score / 4, 1); // Convertir de 0-20 à 0-5
                }
                
                // Extraire les critères d'évaluation
                $criteria = [];
                if (isset($evaluation['criteria_scores']) && !empty($evaluation['criteria_scores'])) {
                    $criteriaScores = json_decode($evaluation['criteria_scores'], true) ?: [];
                    
                    $criteriaLabels = [
                        // Compétences techniques
                        'technical_skills' => 'Compétences techniques',
                        'technical_mastery' => 'Maîtrise des technologies',
                        'work_quality' => 'Qualité du travail',
                        'problem_solving' => 'Résolution de problèmes',
                        'documentation' => 'Documentation',
                        
                        // Compétences professionnelles
                        'professional_behavior' => 'Comportement professionnel',
                        'communication' => 'Communication',
                        'initiative' => 'Initiative et autonomie',
                        'autonomy' => 'Autonomie',
                        'teamwork' => 'Travail en équipe',
                        'team_integration' => 'Intégration dans l\'équipe',
                        'punctuality' => 'Ponctualité et assiduité',
                        'deadline_respect' => 'Respect des délais'
                    ];
                    
                    foreach ($criteriaScores as $key => $value) {
                        // Convertir le score de 0-100 à 0-5
                        $criterionScore = $value > 5 ? round($value / 4, 1) : $value;
                        
                        $criteria[] = [
                            'name' => $criteriaLabels[$key] ?? ucfirst(str_replace('_', ' ', $key)),
                            'score' => $criterionScore
                        ];
                    }
                }
                
                // Construire le tableau de métadonnées mis à jour
                $updatedMetadata = [
                    'evaluator_name' => $document['first_name'] . ' ' . $document['last_name'],
                    'score' => $score,
                    'comments' => $evaluation['feedback'] ?? '',
                    'areas_for_improvement' => $evaluation['areas_to_improve'] ?? '',
                    'recommendations' => $evaluation['next_steps'] ?? '',
                    'criteria' => $criteria,
                    'evaluation_id' => $evaluation['id'],
                    'evaluation_type' => $evalType,
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                // Fusionner avec les métadonnées existantes
                $newMetadata = array_merge($metadata, $updatedMetadata);
                
                // Mettre à jour le document
                $updateQuery = $db->prepare("
                    UPDATE documents 
                    SET metadata = :metadata,
                        description = :description
                    WHERE id = :id
                ");
                
                $updateQuery->execute([
                    'metadata' => json_encode($newMetadata),
                    'description' => $evaluation['feedback'] ?? 'Évaluation',
                    'id' => $documentId
                ]);
                
                $updatedDocuments++;
            }
        } else {
            $skippedDocuments++;
        }
    }
    
    // Envoyer la réponse
    sendJsonResponse([
        'success' => true,
        'message' => "Synchronisation terminée. $documentsDeleted documents problématiques supprimés. $updatedDocuments documents mis à jour, $skippedDocuments documents ignorés.",
        'deleted' => $documentsDeleted,
        'updated' => $updatedDocuments,
        'skipped' => $skippedDocuments
    ]);
    
} catch (Exception $e) {
    sendJsonResponse([
        'success' => false,
        'message' => 'Erreur lors de la synchronisation: ' . $e->getMessage(),
        'error' => $e->getTraceAsString()
    ], 500);
}
?>