<?php
/**
 * Export de toutes les évaluations - Version simplifiée
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../includes/init.php';

// Vérifier les permissions
requireRole('teacher');

// Initialiser les modèles nécessaires
$evaluationModel = new Evaluation($db);
$teacherModel = new Teacher($db);
$studentModel = new Student($db);

// Récupérer le tuteur connecté
$teacher = $teacherModel->getByUserId($_SESSION['user_id']);
if (!$teacher) {
    setFlashMessage('error', 'Profil de tuteur non trouvé');
    redirect('/tutoring/views/tutor/evaluations.php');
    exit;
}

// Récupérer toutes les évaluations faites par ce tuteur
$evaluations = $evaluationModel->getByTeacherId($teacher['id']);

// Types d'évaluation
$evaluationTypes = [
    'mid_term' => 'Mi-parcours',
    'final' => 'Finale',
    'technical' => 'Technique',
    'soft_skills' => 'Compétences personnelles'
];

// Construire le HTML pour l'export
$html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Liste des évaluations</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        h1 {
            color: #2c3e50;
            font-size: 24px;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
            text-align: left;
            padding: 10px;
        }
        td {
            padding: 10px;
        }
        .print-note {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            text-align: center;
        }
        @media print {
            .print-note { display: none; }
            body { font-size: 12pt; }
            thead { display: table-header-group; }
            tfoot { display: table-footer-group; }
        }
    </style>
</head>
<body>
    <div class="print-note">
        <strong>Note:</strong> Pour imprimer ce document ou l\'enregistrer en PDF, utilisez la fonction d\'impression de votre navigateur.
    </div>

    <div class="header">
        <h1>Liste des évaluations</h1>
        <p>Tuteur: ' . h($teacher['first_name'] . ' ' . $teacher['last_name']) . '</p>
        <p>Généré le: ' . date('d/m/Y à H:i') . '</p>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Étudiant</th>
                <th>Type</th>
                <th>Date</th>
                <th>Score</th>
                <th>Commentaires</th>
            </tr>
        </thead>
        <tbody>';

foreach ($evaluations as $evaluation) {
    $student = $studentModel->getById($evaluation['student_id']);
    $studentName = $student ? $student['first_name'] . ' ' . $student['last_name'] : 'Inconnu';
    
    $html .= '
            <tr>
                <td>' . h($evaluation['id']) . '</td>
                <td>' . h($studentName) . '</td>
                <td>' . h($evaluationTypes[$evaluation['type']] ?? ucfirst(str_replace('_', ' ', $evaluation['type']))) . '</td>
                <td>' . date('d/m/Y', strtotime($evaluation['created_at'] ?? date('Y-m-d'))) . '</td>
                <td>' . h(number_format($evaluation['score'] / 4, 1)) . '/5</td>
                <td>' . nl2br(h(substr($evaluation['feedback'] ?? 'Aucun commentaire', 0, 200))) . '</td>
            </tr>';
}

$html .= '
        </tbody>
    </table>
    
    <div class="footer" style="margin-top: 50px; text-align: center; font-size: 12px; color: #666;">
        <p>Document généré le ' . date('d/m/Y à H:i') . ' - TutorMatch</p>
    </div>
</body>
</html>';

// Nom du fichier pour le téléchargement
$filename = "liste_evaluations_" . date('Y-m-d') . ".html";

// Nettoyer tout buffer existant
while (ob_get_level()) {
    ob_end_clean();
}

// En-têtes HTTP pour forcer le téléchargement
header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Envoyer le contenu
echo $html;
exit;