<?php
/**
 * Adaptateur pour convertir les documents de type évaluation en format compatible
 * avec l'API d'évaluations
 */

/**
 * Convertit un document de type évaluation en format d'évaluation compatible avec l'API
 * 
 * @param array $document Document de type évaluation
 * @return array Document formaté comme une évaluation
 */
function convertDocumentToEvaluation($document) {
    // Journaliser les informations du document
    error_log("Conversion du document ID: " . $document['id'] . " en évaluation");
    
    // Vérifier si le document a des métadonnées
    if (!isset($document['metadata']) || !is_array($document['metadata'])) {
        $document['metadata'] = [];
        error_log("Document sans métadonnées: " . $document['id']);
    }
    
    // Extraire les informations de base du document
    $evaluation = [
        'id' => $document['id'],
        'student_id' => $document['user_id'], // L'ID d'utilisateur du document est l'ID étudiant
        'evaluator_id' => $document['created_by'] ?? null,
        'evaluator_name' => isset($document['created_by_name']) ? $document['created_by_name'] : 'Système',
        'evaluator_role' => $document['type'] === 'self_evaluation' ? 'student' : 'teacher',
        'type' => mapDocumentTypeToEvaluationType($document['type']),
        'date' => $document['upload_date'] ?? date('Y-m-d H:i:s'),
        'score' => isset($document['metadata']['score']) ? $document['metadata']['score'] : 0,
        'comments' => $document['description'] ?? ($document['metadata']['comments'] ?? ''),
        'criteria' => extractCriteriaFromDocument($document)
    ];
    
    // Journaliser les informations de l'évaluation convertie
    error_log("Évaluation convertie: Type=" . $evaluation['type'] . ", Score=" . $evaluation['score']);
    
    return $evaluation;
}

/**
 * Mappe le type de document au type d'évaluation
 * 
 * @param string $documentType Type de document
 * @return string Type d'évaluation correspondant
 */
function mapDocumentTypeToEvaluationType($documentType) {
    $typeMapping = [
        'evaluation' => 'teacher', 
        'self_evaluation' => 'self',
        'mid_term' => 'mid_term',
        'final' => 'final'
    ];
    
    return isset($typeMapping[$documentType]) ? $typeMapping[$documentType] : 'teacher';
}

/**
 * Extrait les critères d'évaluation à partir des métadonnées d'un document
 * 
 * @param array $document Document contenant des métadonnées d'évaluation
 * @return array Liste des critères d'évaluation
 */
function extractCriteriaFromDocument($document) {
    $criteria = [];
    
    // Si le document a des métadonnées structurées
    if (isset($document['metadata']) && is_array($document['metadata'])) {
        $metadata = $document['metadata'];
        
        // Extraire les critères s'ils existent
        if (isset($metadata['criteria']) && is_array($metadata['criteria'])) {
            return $metadata['criteria'];
        }
        
        // Sinon, essayer de créer des critères à partir d'autres champs
        $standardCriteria = [
            'technical' => ['name' => 'Compétences techniques', 'score' => $metadata['technical_score'] ?? 0],
            'communication' => ['name' => 'Communication', 'score' => $metadata['communication_score'] ?? 0],
            'teamwork' => ['name' => 'Travail en équipe', 'score' => $metadata['teamwork_score'] ?? 0],
            'autonomy' => ['name' => 'Autonomie', 'score' => $metadata['autonomy_score'] ?? 0]
        ];
        
        foreach ($standardCriteria as $key => $criterion) {
            if (isset($metadata[$key . '_score']) || isset($metadata[$key]) || $criterion['score'] > 0) {
                $score = $metadata[$key . '_score'] ?? $metadata[$key] ?? $criterion['score'];
                $comments = $metadata[$key . '_comments'] ?? '';
                
                $criteria[] = [
                    'name' => $criterion['name'],
                    'score' => (float)$score,
                    'comments' => $comments
                ];
            }
        }
    } else {
        // Si pas de métadonnées, créer un critère générique basé sur le score global
        $criteria[] = [
            'name' => 'Évaluation globale',
            'score' => $document['score'] ?? 0,
            'comments' => $document['description'] ?? ''
        ];
    }
    
    return $criteria;
}

/**
 * Filtre les documents pour ne garder que ceux de type évaluation
 * 
 * @param array $documents Liste de documents
 * @return array Documents filtrés de type évaluation
 */
function filterEvaluationDocuments($documents) {
    return array_filter($documents, function($doc) {
        $evaluationTypes = ['evaluation', 'self_evaluation', 'mid_term', 'final'];
        return isset($doc['type']) && in_array($doc['type'], $evaluationTypes);
    });
}

/**
 * Génère des métadonnées pour le document d'évaluation
 * 
 * @param array $evaluationData Données d'évaluation
 * @return array Métadonnées formatées
 */
function generateEvaluationMetadata($evaluationData) {
    $metadata = [
        'score' => $evaluationData['score'] ?? 0,
        'comments' => $evaluationData['comments'] ?? '',
        'evaluator_id' => $evaluationData['evaluator_id'] ?? null,
        'evaluator_name' => $evaluationData['evaluator_name'] ?? null,
        'criteria' => []
    ];
    
    // Ajouter les critères s'ils existent
    if (isset($evaluationData['criteria']) && is_array($evaluationData['criteria'])) {
        $metadata['criteria'] = $evaluationData['criteria'];
        
        // Calculer les scores par catégorie
        foreach ($evaluationData['criteria'] as $criterion) {
            $name = strtolower($criterion['name']);
            
            if (stripos($name, 'technique') !== false) {
                $metadata['technical_score'] = $criterion['score'];
            } else if (stripos($name, 'communication') !== false) {
                $metadata['communication_score'] = $criterion['score'];
            } else if (stripos($name, 'équipe') !== false || stripos($name, 'team') !== false) {
                $metadata['teamwork_score'] = $criterion['score'];
            } else if (stripos($name, 'autonomie') !== false || stripos($name, 'autonomy') !== false) {
                $metadata['autonomy_score'] = $criterion['score'];
            }
        }
    }
    
    return $metadata;
}

/**
 * Crée un document d'évaluation à partir des données d'évaluation
 * 
 * @param array $evaluationData Données d'évaluation
 * @param array $userData Données utilisateur
 * @param Document $documentModel Modèle de document
 * @return int|false ID du document créé ou false si échec
 */
function createEvaluationDocument($evaluationData, $userData, $documentModel) {
    $metadata = generateEvaluationMetadata($evaluationData);
    
    $documentData = [
        'title' => 'Évaluation ' . date('Y-m-d'),
        'description' => $evaluationData['comments'] ?? '',
        'type' => $evaluationData['type'] === 'self' ? 'self_evaluation' : 'evaluation',
        'file_path' => 'evaluations/' . uniqid() . '.json',
        'file_type' => 'application/json',
        'file_size' => strlen(json_encode($metadata)),
        'user_id' => $userData['id'],
        'status' => 'submitted',
        'metadata' => $metadata
    ];
    
    return $documentModel->create($documentData);
}