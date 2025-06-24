<?php
/**
 * Script de débogage pour les évaluations - utilisé pour identifier pourquoi les évaluations mi-parcours ne s'affichent pas
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../includes/init.php';

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo "Erreur: Utilisateur non connecté";
    exit;
}

// Récupérer l'ID de l'étudiant à partir de l'URL
$studentId = isset($_GET['student_id']) ? intval($_GET['student_id']) : null;

// Si aucun ID d'étudiant n'est fourni, afficher un message d'erreur
if (!$studentId) {
    echo "Erreur: ID étudiant manquant. Utilisez ?student_id=X dans l'URL.";
    exit;
}

// Récupérer les modèles nécessaires
$evaluationModel = new Evaluation($db);
$studentModel = new Student($db);
$teacherModel = new Teacher($db);

// Récupérer l'étudiant
$student = $studentModel->getById($studentId);
if (!$student) {
    echo "Erreur: Étudiant non trouvé avec ID $studentId";
    exit;
}

// Récupérer les affectations
$assignmentQuery = $db->prepare("SELECT * FROM assignments WHERE student_id = :student_id");
$assignmentQuery->bindParam(':student_id', $studentId, PDO::PARAM_INT);
$assignmentQuery->execute();
$assignments = $assignmentQuery->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les évaluations via les différentes méthodes
$evaluationsByStudentId = $evaluationModel->getByStudentId($studentId);

// Récupérer les évaluations par assignmentId pour chaque affectation
$evaluationsByAssignmentId = [];
foreach ($assignments as $assignment) {
    $assignmentId = $assignment['id'];
    $evaluationsByAssignmentId[$assignmentId] = $evaluationModel->getByAssignmentId($assignmentId);
}

// Interroger directement la base de données pour les évaluations mid_term
$query = $db->prepare("
    SELECT e.*, a.student_id, a.teacher_id 
    FROM evaluations e 
    JOIN assignments a ON e.assignment_id = a.id 
    WHERE a.student_id = :student_id AND e.type IN ('mid_term', 'mid-term', 'midterm')
");
$query->bindParam(':student_id', $studentId, PDO::PARAM_INT);
$query->execute();
$midTermEvaluations = $query->fetchAll(PDO::FETCH_ASSOC);

// Affichage des résultats
echo "<!DOCTYPE html>
<html>
<head>
    <title>Débogage des évaluations</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1, h2, h3 { color: #333; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .card { border: 1px solid #ddd; border-radius: 5px; margin-bottom: 20px; }
        .card-header { background-color: #f2f2f2; padding: 10px; border-bottom: 1px solid #ddd; }
        .card-body { padding: 15px; }
        .highlight { background-color: yellow; font-weight: bold; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Débogage des évaluations</h1>
        
        <div class='card'>
            <div class='card-header'>
                <h2>Informations de l'étudiant</h2>
            </div>
            <div class='card-body'>
                <p><strong>ID:</strong> " . htmlspecialchars($student['id']) . "</p>
                <p><strong>Nom:</strong> " . htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) . "</p>
                <p><strong>Département:</strong> " . htmlspecialchars($student['department'] ?? 'Non spécifié') . "</p>
            </div>
        </div>
        
        <div class='card'>
            <div class='card-header'>
                <h2>Affectations trouvées (" . count($assignments) . ")</h2>
            </div>
            <div class='card-body'>";
        
if (empty($assignments)) {
    echo "<p>Aucune affectation trouvée pour cet étudiant.</p>";
} else {
    echo "<table>
            <tr>
                <th>ID</th>
                <th>Étudiant ID</th>
                <th>Tuteur ID</th>
                <th>Stage ID</th>
                <th>Statut</th>
                <th>Créé le</th>
            </tr>";
    
    foreach ($assignments as $assignment) {
        echo "<tr>
                <td>" . htmlspecialchars($assignment['id']) . "</td>
                <td>" . htmlspecialchars($assignment['student_id']) . "</td>
                <td>" . htmlspecialchars($assignment['teacher_id']) . "</td>
                <td>" . htmlspecialchars($assignment['internship_id'] ?? 'N/A') . "</td>
                <td>" . htmlspecialchars($assignment['status'] ?? 'N/A') . "</td>
                <td>" . htmlspecialchars($assignment['created_at'] ?? 'N/A') . "</td>
              </tr>";
    }
    
    echo "</table>";
}

echo "</div>
        </div>
        
        <div class='card'>
            <div class='card-header'>
                <h2>Évaluations via getByStudentId (" . count($evaluationsByStudentId) . ")</h2>
            </div>
            <div class='card-body'>";

if (empty($evaluationsByStudentId)) {
    echo "<p>Aucune évaluation trouvée via getByStudentId.</p>";
} else {
    echo "<table>
            <tr>
                <th>ID</th>
                <th>Assignment ID</th>
                <th>Type</th>
                <th>Score</th>
                <th>Date</th>
                <th>Statut</th>
            </tr>";
    
    foreach ($evaluationsByStudentId as $eval) {
        $isMidTerm = in_array($eval['type'], ['mid_term', 'mid-term', 'midterm']);
        $rowClass = $isMidTerm ? 'class="highlight"' : '';
        
        echo "<tr $rowClass>
                <td>" . htmlspecialchars($eval['id']) . "</td>
                <td>" . htmlspecialchars($eval['assignment_id']) . "</td>
                <td>" . htmlspecialchars($eval['type']) . "</td>
                <td>" . htmlspecialchars($eval['score'] ?? 'N/A') . "</td>
                <td>" . htmlspecialchars($eval['submission_date'] ?? $eval['created_at'] ?? 'N/A') . "</td>
                <td>" . htmlspecialchars($eval['status'] ?? 'N/A') . "</td>
              </tr>";
    }
    
    echo "</table>";
}

echo "</div>
        </div>";

// Afficher les évaluations par assignment
foreach ($evaluationsByAssignmentId as $assignmentId => $evaluations) {
    echo "<div class='card'>
            <div class='card-header'>
                <h2>Évaluations via getByAssignmentId - Assignment #$assignmentId (" . count($evaluations) . ")</h2>
            </div>
            <div class='card-body'>";
    
    if (empty($evaluations)) {
        echo "<p>Aucune évaluation trouvée pour cette affectation.</p>";
    } else {
        echo "<table>
                <tr>
                    <th>ID</th>
                    <th>Assignment ID</th>
                    <th>Type</th>
                    <th>Score</th>
                    <th>Date</th>
                    <th>Statut</th>
                </tr>";
        
        foreach ($evaluations as $eval) {
            $isMidTerm = in_array($eval['type'], ['mid_term', 'mid-term', 'midterm']);
            $rowClass = $isMidTerm ? 'class="highlight"' : '';
            
            echo "<tr $rowClass>
                    <td>" . htmlspecialchars($eval['id']) . "</td>
                    <td>" . htmlspecialchars($eval['assignment_id']) . "</td>
                    <td>" . htmlspecialchars($eval['type']) . "</td>
                    <td>" . htmlspecialchars($eval['score'] ?? 'N/A') . "</td>
                    <td>" . htmlspecialchars($eval['submission_date'] ?? $eval['created_at'] ?? 'N/A') . "</td>
                    <td>" . htmlspecialchars($eval['status'] ?? 'N/A') . "</td>
                  </tr>";
        }
        
        echo "</table>";
    }
    
    echo "</div>
          </div>";
}

echo "<div class='card'>
        <div class='card-header'>
            <h2>Requête directe pour les évaluations mi-parcours (" . count($midTermEvaluations) . ")</h2>
        </div>
        <div class='card-body'>";

if (empty($midTermEvaluations)) {
    echo "<p>Aucune évaluation mi-parcours trouvée par requête directe.</p>";
} else {
    echo "<table>
            <tr>
                <th>ID</th>
                <th>Assignment ID</th>
                <th>Type</th>
                <th>Score</th>
                <th>Date</th>
                <th>Statut</th>
            </tr>";
    
    foreach ($midTermEvaluations as $eval) {
        echo "<tr class='highlight'>
                <td>" . htmlspecialchars($eval['id']) . "</td>
                <td>" . htmlspecialchars($eval['assignment_id']) . "</td>
                <td>" . htmlspecialchars($eval['type']) . "</td>
                <td>" . htmlspecialchars($eval['score'] ?? 'N/A') . "</td>
                <td>" . htmlspecialchars($eval['submission_date'] ?? $eval['created_at'] ?? 'N/A') . "</td>
                <td>" . htmlspecialchars($eval['status'] ?? 'N/A') . "</td>
              </tr>";
    }
    
    echo "</table>";
}

echo "</div>
      </div>";

// Vérifier les documents
echo "<div class='card'>
        <div class='card-header'>
            <h2>Documents de type évaluation</h2>
        </div>
        <div class='card-body'>";

$documents = $studentModel->getDocuments($studentId);
$evalDocuments = array_filter($documents, function($doc) {
    return in_array($doc['type'], ['evaluation', 'self_evaluation', 'mid_term', 'final']);
});

if (empty($evalDocuments)) {
    echo "<p>Aucun document de type évaluation trouvé.</p>";
} else {
    echo "<table>
            <tr>
                <th>ID</th>
                <th>Type</th>
                <th>Nom du fichier</th>
                <th>Description</th>
                <th>Date</th>
                <th>Métadonnées</th>
            </tr>";
    
    foreach ($evalDocuments as $doc) {
        $isMidTerm = $doc['type'] == 'mid_term';
        $rowClass = $isMidTerm ? 'class="highlight"' : '';
        
        echo "<tr $rowClass>
                <td>" . htmlspecialchars($doc['id']) . "</td>
                <td>" . htmlspecialchars($doc['type']) . "</td>
                <td>" . htmlspecialchars($doc['filename'] ?? 'N/A') . "</td>
                <td>" . htmlspecialchars($doc['description'] ?? 'N/A') . "</td>
                <td>" . htmlspecialchars($doc['upload_date'] ?? 'N/A') . "</td>
                <td><pre>" . htmlspecialchars(json_encode($doc['metadata'] ?? [], JSON_PRETTY_PRINT)) . "</pre></td>
              </tr>";
    }
    
    echo "</table>";
}

echo "</div>
      </div>";

// Vérifier tous les types d'évaluation dans la base
echo "<div class='card'>
        <div class='card-header'>
            <h2>Tous les types d'évaluation dans la base</h2>
        </div>
        <div class='card-body'>";

$typeQuery = $db->prepare("SELECT DISTINCT type, COUNT(*) as count FROM evaluations GROUP BY type");
$typeQuery->execute();
$types = $typeQuery->fetchAll(PDO::FETCH_ASSOC);

echo "<table>
        <tr>
            <th>Type</th>
            <th>Nombre</th>
        </tr>";

foreach ($types as $type) {
    echo "<tr>
            <td>" . htmlspecialchars($type['type']) . "</td>
            <td>" . htmlspecialchars($type['count']) . "</td>
          </tr>";
}

echo "</table>
      </div>
      </div>";

// Test de l'API
echo "<div class='card'>
        <div class='card-header'>
            <h2>Test de l'API get-student-evaluations.php</h2>
        </div>
        <div class='card-body'>";

// Appel à l'API via cURL
$apiUrl = "http://localhost/tutoring/api/evaluations/get-student-evaluations.php?student_id=" . $studentId;
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
$apiResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p><strong>URL de l'API:</strong> " . htmlspecialchars($apiUrl) . "</p>";
echo "<p><strong>Code HTTP:</strong> " . htmlspecialchars($httpCode) . "</p>";

if ($apiResponse) {
    $responseData = json_decode($apiResponse, true);
    if ($responseData) {
        echo "<h3>Réponse de l'API:</h3>";
        echo "<pre>" . htmlspecialchars(json_encode($responseData, JSON_PRETTY_PRINT)) . "</pre>";
        
        if (isset($responseData['evaluations'])) {
            echo "<h3>Évaluations retournées par l'API (" . count($responseData['evaluations']) . "):</h3>";
            echo "<table>
                    <tr>
                        <th>ID</th>
                        <th>Type</th>
                        <th>Score</th>
                        <th>Date</th>
                    </tr>";
            
            foreach ($responseData['evaluations'] as $eval) {
                $isMidTerm = in_array($eval['type'], ['mid_term', 'mid-term', 'midterm']);
                $rowClass = $isMidTerm ? 'class="highlight"' : '';
                
                echo "<tr $rowClass>
                        <td>" . htmlspecialchars($eval['id']) . "</td>
                        <td>" . htmlspecialchars($eval['type']) . "</td>
                        <td>" . htmlspecialchars($eval['score'] ?? 'N/A') . "</td>
                        <td>" . htmlspecialchars($eval['submission_date'] ?? 'N/A') . "</td>
                      </tr>";
            }
            
            echo "</table>";
        }
    } else {
        echo "<p>Erreur de décodage JSON</p>";
        echo "<pre>" . htmlspecialchars($apiResponse) . "</pre>";
    }
} else {
    echo "<p>Aucune réponse de l'API</p>";
}

echo "</div>
      </div>
      
      <div class='card'>
        <div class='card-header'>
            <h2>Requête SQL directe</h2>
        </div>
        <div class='card-body'>
            <pre>
SELECT e.*, a.student_id, a.teacher_id 
FROM evaluations e 
JOIN assignments a ON e.assignment_id = a.id 
WHERE a.student_id = $studentId AND e.type IN ('mid_term', 'mid-term', 'midterm')
            </pre>
        </div>
      </div>
      
    </div>
</body>
</html>";