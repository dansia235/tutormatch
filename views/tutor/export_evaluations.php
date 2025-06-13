<?php
/**
 * Export de toutes les évaluations - Version améliorée
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
        @import url("https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap");
        
        :root {
            --primary-color: #3a5fe5;
            --secondary-color: #4dd0e1;
            --dark-color: #2c3e50;
            --light-color: #ecf0f1;
            --success-color: #4caf50;
            --warning-color: #ff9800;
            --danger-color: #f44336;
            --gray-color: #95a5a6;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: "Roboto", Arial, sans-serif;
            line-height: 1.6;
            background-color: white;
            color: var(--dark-color);
            margin: 0;
            padding: 0;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            background-color: white;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
            padding-bottom: 20px;
        }
        
        .header:after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 3px;
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
        }
        
        .header h1 {
            color: var(--primary-color);
            font-size: 28px;
            font-weight: 500;
            margin-bottom: 15px;
        }
        
        .header p {
            color: var(--gray-color);
            font-size: 16px;
            font-weight: 300;
        }
        
        .info-card {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 20px 25px;
            margin-bottom: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border-left: 4px solid var(--primary-color);
        }
        
        .info-card h2 {
            color: var(--primary-color);
            font-size: 20px;
            margin-bottom: 15px;
            font-weight: 500;
            display: flex;
            align-items: center;
        }
        
        .info-card h2:before {
            content: "•";
            margin-right: 10px;
            color: var(--primary-color);
            font-size: 24px;
        }
        
        .evaluations-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 10px;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .evaluations-table th {
            background-color: var(--primary-color);
            color: white;
            text-align: left;
            padding: 12px 15px;
            font-weight: 500;
            border: none;
        }
        
        .evaluations-table tr:nth-child(even) {
            background-color: #f7f9fc;
        }
        
        .evaluations-table td {
            padding: 12px 15px;
            border: none;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .evaluations-table tr:last-child td {
            border-bottom: none;
        }
        
        .evaluations-table tr:hover {
            background-color: #f1f5fd;
        }
        
        .star-rating {
            unicode-bidi: bidi-override;
            direction: rtl;
            text-align: left;
            display: inline-block;
            font-size: 16px;
        }
        
        .star-rating > span {
            display: inline-block;
            position: relative;
            width: 1em;
            color: var(--warning-color);
        }
        
        .score-bar {
            display: inline-block;
            width: 100px;
            height: 6px;
            background-color: #e9ecef;
            border-radius: 10px;
            overflow: hidden;
            margin-left: 10px;
            vertical-align: middle;
        }
        
        .score-fill {
            height: 100%;
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            border-radius: 10px;
        }
        
        .print-note {
            background-color: #fff3cd;
            color: #856404;
            padding: 12px 15px;
            margin-bottom: 25px;
            border-radius: 6px;
            text-align: center;
            border-left: 4px solid #ffc107;
            font-size: 14px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .logo span {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .logo span.highlight {
            color: var(--secondary-color);
        }
        
        .footer {
            text-align: center;
            margin-top: 50px;
            color: var(--gray-color);
            font-size: 12px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .score-display {
            display: flex;
            align-items: center;
        }
        
        .score-number {
            font-weight: 600;
            margin-left: 5px;
            margin-right: 2px;
        }
        
        @media print {
            .print-note { display: none; }
            body {
                font-size: 12pt;
                color: black;
                background-color: white;
            }
            .container {
                box-shadow: none;
                max-width: 100%;
            }
            .info-card {
                break-inside: avoid;
                box-shadow: none;
                border-left-color: #888;
            }
            .evaluations-table th {
                background-color: #ddd !important;
                color: black;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .evaluations-table tr:nth-child(even) {
                background-color: #f5f5f5 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .star-rating > span {
                color: #ff9800 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .score-fill {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="print-note">
            <strong>Note:</strong> Pour imprimer ce document ou l\'enregistrer en PDF, utilisez la fonction d\'impression de votre navigateur.
        </div>

        <div class="logo">
            <span>Tutor<span class="highlight">Match</span></span>
        </div>

        <div class="header">
            <h1>Liste des évaluations</h1>
            <p>Tuteur: ' . h($teacher['first_name'] . ' ' . $teacher['last_name']) . '</p>
            <p>Généré le: ' . date('d/m/Y à H:i') . '</p>
        </div>
        
        <div class="info-card">
            <h2>Évaluations réalisées</h2>
            
            <table class="evaluations-table">
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
    
    // Calculer la note sur 5
    $scoreOn5 = number_format($evaluation['score'] / 4, 1);
    $scorePercentage = ($scoreOn5 / 5) * 100;
    $stars = round($scoreOn5);
    
    // Générer les étoiles
    $starsHtml = '<div class="star-rating">';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $stars) {
            $starsHtml .= '<span>★</span>';
        } else {
            $starsHtml .= '<span>☆</span>';
        }
    }
    $starsHtml .= '</div>';
    
    // Générer la barre de score
    $scoreBar = '<div class="score-bar"><div class="score-fill" style="width: ' . $scorePercentage . '%;"></div></div>';
    
    $html .= '
                <tr>
                    <td>' . h($evaluation['id']) . '</td>
                    <td>' . h($studentName) . '</td>
                    <td>' . h($evaluationTypes[$evaluation['type']] ?? ucfirst(str_replace('_', ' ', $evaluation['type']))) . '</td>
                    <td>' . date('d/m/Y', strtotime($evaluation['created_at'] ?? date('Y-m-d'))) . '</td>
                    <td>
                        <div class="score-display">
                            ' . $starsHtml . '
                            <span class="score-number">' . $scoreOn5 . '</span>/5
                            ' . $scoreBar . '
                        </div>
                    </td>
                    <td>' . nl2br(h(substr($evaluation['feedback'] ?? 'Aucun commentaire', 0, 200))) . '</td>
                </tr>';
}

$html .= '
                </tbody>
            </table>
        </div>
        
        <div class="info-card">
            <h2>Statistiques</h2>
            <div style="display: flex; justify-content: space-around; text-align: center; margin-top: 20px;">';

// Calculer quelques statistiques
$totalEvaluations = count($evaluations);
$averageScore = 0;
$typeStats = [];

foreach ($evaluationTypes as $key => $label) {
    $typeStats[$key] = 0;
}

if ($totalEvaluations > 0) {
    $scoreSum = 0;
    foreach ($evaluations as $evaluation) {
        $scoreSum += $evaluation['score'] / 4; // Convertir en note /5
        if (isset($evaluation['type']) && isset($typeStats[$evaluation['type']])) {
            $typeStats[$evaluation['type']]++;
        }
    }
    $averageScore = $scoreSum / $totalEvaluations;
}

// Afficher le nombre total d'évaluations
$html .= '
            <div style="flex: 1;">
                <div style="font-size: 36px; font-weight: bold; color: var(--primary-color);">' . $totalEvaluations . '</div>
                <div style="color: var(--gray-color);">Évaluations</div>
            </div>';

// Afficher le score moyen
$avgScorePercentage = ($averageScore / 5) * 100;
$html .= '
            <div style="flex: 1;">
                <div style="font-size: 36px; font-weight: bold; color: var(--primary-color);">' . number_format($averageScore, 1) . '</div>
                <div style="color: var(--gray-color);">Score moyen /5</div>
                <div class="score-bar" style="width: 80%; margin: 10px auto;">
                    <div class="score-fill" style="width: ' . $avgScorePercentage . '%;"></div>
                </div>
            </div>';

// Afficher la répartition par type
$html .= '
            <div style="flex: 1;">
                <div style="margin-top: 10px;">';

foreach ($typeStats as $type => $count) {
    if ($count > 0) {
        $percentage = ($count / $totalEvaluations) * 100;
        $html .= '
                <div style="text-align: left; margin-bottom: 8px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 2px;">
                        <span>' . h($evaluationTypes[$type]) . '</span>
                        <span>' . $count . '</span>
                    </div>
                    <div class="score-bar" style="width: 100%;">
                        <div class="score-fill" style="width: ' . $percentage . '%;"></div>
                    </div>
                </div>';
    }
}

$html .= '
                </div>
            </div>';

$html .= '
            </div>
        </div>
        
        <div class="footer">
            <p>Document généré le ' . date('d/m/Y à H:i') . '</p>
            <p>TutorMatch - Plateforme de gestion de stages et tutorat</p>
        </div>
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