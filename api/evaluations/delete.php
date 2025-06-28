<?php
/**
 * API pour supprimer une évaluation
 * DELETE /api/evaluations/delete.php
 */

require_once __DIR__ . '/../../includes/init.php';

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

// Vérifier les permissions (admin et coordinateur uniquement)
requireRole(['admin', 'coordinator']);

try {
    // Récupérer l'ID de l'évaluation
    $evaluationId = null;
    
    if (isset($_POST['id'])) {
        $evaluationId = (int)$_POST['id'];
    } else {
        // Essayer de récupérer depuis JSON
        $input = json_decode(file_get_contents('php://input'), true);
        if (isset($input['id'])) {
            $evaluationId = (int)$input['id'];
        }
    }
    
    if (!$evaluationId || $evaluationId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID d\'évaluation manquant ou invalide']);
        exit;
    }
    
    // Vérifier que l'évaluation existe et récupérer ses informations
    $checkQuery = "
        SELECT e.*, 
               u_evaluator.first_name as evaluator_first_name, 
               u_evaluator.last_name as evaluator_last_name,
               u_evaluatee.first_name as evaluatee_first_name, 
               u_evaluatee.last_name as evaluatee_last_name
        FROM evaluations e
        LEFT JOIN users u_evaluator ON e.evaluator_id = u_evaluator.id
        LEFT JOIN users u_evaluatee ON e.evaluatee_id = u_evaluatee.id
        WHERE e.id = :id
    ";
    
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindValue(':id', $evaluationId, PDO::PARAM_INT);
    $checkStmt->execute();
    $evaluation = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$evaluation) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Évaluation non trouvée']);
        exit;
    }
    
    // Vérifier les contraintes de suppression
    $canDelete = true;
    $warnings = [];
    
    // Vérifier si l'évaluation est complétée
    if ($evaluation['status'] === 'completed') {
        $warnings[] = 'Cette évaluation est complétée et sa suppression pourrait affecter les statistiques';
    }
    
    // Vérifier s'il y a des données importantes dans l'évaluation
    if (!empty($evaluation['criteria_scores'])) {
        $warnings[] = 'Cette évaluation contient des scores détaillés par critères qui seront perdus';
    }
    
    if (!empty($evaluation['comments'])) {
        $warnings[] = 'Cette évaluation contient des commentaires qui seront perdus';
    }
    
    // Commencer la transaction pour assurer la cohérence
    $db->beginTransaction();
    
    try {
        // Supprimer l'évaluation directement (structure simple sans tables liées)
        $deleteEvaluationQuery = "DELETE FROM evaluations WHERE id = :id";
        $deleteEvaluationStmt = $db->prepare($deleteEvaluationQuery);
        $deleteEvaluationStmt->bindValue(':id', $evaluationId, PDO::PARAM_INT);
        $result = $deleteEvaluationStmt->execute();
        
        // Vérifier que la suppression a bien eu lieu
        if ($deleteEvaluationStmt->rowCount() === 0) {
            throw new Exception('Aucune évaluation supprimée - ID peut-être déjà supprimé');
        }
        
        // Valider la transaction
        $db->commit();
        
        // Retourner la réponse de succès
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Évaluation supprimée avec succès',
            'data' => [
                'id' => $evaluationId,
                'evaluator_name' => trim($evaluation['evaluator_first_name'] . ' ' . $evaluation['evaluator_last_name']),
                'evaluatee_name' => trim($evaluation['evaluatee_first_name'] . ' ' . $evaluation['evaluatee_last_name']),
                'type' => $evaluation['type'],
                'score' => $evaluation['score'],
                'status' => $evaluation['status'],
                'warnings' => $warnings
            ]
        ]);
        
    } catch (Exception $e) {
        // Annuler la transaction en cas d'erreur
        $db->rollback();
        throw $e;
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur de base de données : ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de la suppression : ' . $e->getMessage()
    ]);
}
?>