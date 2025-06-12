<?php
/**
 * Export d'une évaluation - Version simplifiée
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../includes/init.php';

// Vérifier les permissions
requireRole('teacher');

// Récupérer l'ID d'évaluation
$evaluationId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($evaluationId <= 0) {
    setFlashMessage('error', 'ID d\'évaluation invalide');
    redirect('/tutoring/views/tutor/evaluations.php');
    exit;
}

// Initialiser les modèles nécessaires
$evaluationModel = new Evaluation($db);
$studentModel = new Student($db);
$assignmentModel = new Assignment($db);
$teacherModel = new Teacher($db);
$userModel = new User($db);

// Récupérer l'évaluation
$evaluation = $evaluationModel->getById($evaluationId);

if (!$evaluation) {
    setFlashMessage('error', 'Évaluation non trouvée');
    redirect('/tutoring/views/tutor/evaluations.php');
    exit;
}

// Récupérer l'affectation
$assignment = $assignmentModel->getById($evaluation['assignment_id']);

if (!$assignment) {
    setFlashMessage('error', 'Affectation non trouvée');
    redirect('/tutoring/views/tutor/evaluations.php');
    exit;
}

// Récupérer les informations de l'étudiant
$student = $studentModel->getById($assignment['student_id']);
if (!$student) {
    setFlashMessage('error', 'Étudiant non trouvé');
    redirect('/tutoring/views/tutor/evaluations.php');
    exit;
}

// Récupérer les informations du tuteur
$teacher = $teacherModel->getById($assignment['teacher_id']);
if (!$teacher) {
    setFlashMessage('error', 'Tuteur non trouvé');
    redirect('/tutoring/views/tutor/evaluations.php');
    exit;
}

// Récupérer les données utilisateur
$studentUser = $userModel->getById($student['user_id']);
$teacherUser = $userModel->getById($teacher['user_id']);

// Types d'évaluation
$evaluationTypes = [
    'mid_term' => 'Mi-parcours',
    'final' => 'Finale',
    'technical' => 'Technique',
    'soft_skills' => 'Compétences personnelles'
];

// Préparer le HTML pour l'export
$html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Évaluation de ' . h($student['first_name'] . ' ' . $student['last_name']) . '</title>
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
        h2 {
            color: #3498db;
            font-size: 18px;
            margin-top: 20px;
            margin-bottom: 10px;
        }
        .info-section {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #eee;
            border-radius: 5px;
        }
        .info-row {
            margin-bottom: 10px;
        }
        .info-label {
            font-weight: bold;
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
        }
    </style>
</head>
<body>
    <div class="print-note">
        <strong>Note:</strong> Pour imprimer ce document ou l\'enregistrer en PDF, utilisez la fonction d\'impression de votre navigateur.
    </div>

    <div class="header">
        <h1>Évaluation de stage</h1>
        <p>' . h($evaluationTypes[$evaluation['type']] ?? ucfirst(str_replace('_', ' ', $evaluation['type']))) . ' - ' . date('d/m/Y', strtotime($evaluation['created_at'] ?? date('Y-m-d'))) . '</p>
    </div>
    
    <div class="info-section">
        <h2>Informations</h2>
        <div class="info-row">
            <div class="info-label">Étudiant:</div>
            <div>' . h($student['first_name'] . ' ' . $student['last_name']) . '</div>
        </div>
        <div class="info-row">
            <div class="info-label">Email:</div>
            <div>' . h($studentUser['email'] ?? 'Non disponible') . '</div>
        </div>
        <div class="info-row">
            <div class="info-label">Tuteur:</div>
            <div>' . h($teacher['first_name'] . ' ' . $teacher['last_name']) . '</div>
        </div>
        <div class="info-row">
            <div class="info-label">Date d\'évaluation:</div>
            <div>' . date('d/m/Y', strtotime($evaluation['created_at'] ?? date('Y-m-d'))) . '</div>
        </div>
    </div>
    
    <div class="info-section">
        <h2>Évaluation globale</h2>
        <div class="info-row">
            <div class="info-label">Note:</div>
            <div>' . h(number_format($evaluation['score'] / 4, 1)) . '/5</div>
        </div>
    </div>
    
    <div class="info-section">
        <h2>Commentaires</h2>
        <p>' . nl2br(h($evaluation['feedback'] ?? 'Aucun commentaire')) . '</p>
    </div>';
    
if (!empty($evaluation['areas_to_improve'])) {
    $html .= '
    <div class="info-section">
        <h2>Points à améliorer</h2>
        <p>' . nl2br(h($evaluation['areas_to_improve'])) . '</p>
    </div>';
}

$html .= '
    <div class="footer" style="margin-top: 50px; text-align: center; font-size: 12px; color: #666;">
        <p>Document généré le ' . date('d/m/Y à H:i') . ' - TutorMatch</p>
    </div>
</body>
</html>';

// Nom du fichier pour le téléchargement
$filename = "evaluation_" . $evaluationId . ".html";

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