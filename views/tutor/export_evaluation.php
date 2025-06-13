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
            max-width: 800px;
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
        
        .info-row {
            display: flex;
            margin-bottom: 12px;
            border-bottom: 1px dashed #eee;
            padding-bottom: 12px;
        }
        
        .info-row:last-child {
            margin-bottom: 0;
            border-bottom: none;
            padding-bottom: 0;
        }
        
        .info-label {
            font-weight: 500;
            width: 150px;
            color: var(--dark-color);
        }
        
        .info-value {
            flex: 1;
            color: #444;
        }
        
        .score-display {
            display: flex;
            align-items: center;
            margin-top: 10px;
        }
        
        .score-bar {
            flex: 1;
            height: 10px;
            background-color: #e9ecef;
            border-radius: 10px;
            overflow: hidden;
            margin: 0 15px;
        }
        
        .score-fill {
            height: 100%;
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            border-radius: 10px;
        }
        
        .score-number {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .comments {
            line-height: 1.8;
            color: #444;
        }
        
        .footer {
            text-align: center;
            margin-top: 50px;
            color: var(--gray-color);
            font-size: 12px;
            padding-top: 20px;
            border-top: 1px solid #eee;
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
        
        .star-rating {
            unicode-bidi: bidi-override;
            direction: rtl;
            text-align: left;
            display: inline-block;
            font-size: 22px;
            margin-right: 10px;
        }
        
        .star-rating > span {
            display: inline-block;
            position: relative;
            width: 1.1em;
            color: var(--warning-color);
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
            <h1>Évaluation de stage</h1>
            <p>' . h($student['first_name'] . ' ' . $student['last_name']) . ' - ' . h($evaluationTypes[$evaluation['type']] ?? ucfirst(str_replace('_', ' ', $evaluation['type']))) . '</p>
            <p>' . date('d/m/Y', strtotime($evaluation['created_at'] ?? date('Y-m-d'))) . '</p>
        </div>
        
        <div class="info-card">
            <h2>Informations générales</h2>
            <div class="info-row">
                <div class="info-label">Étudiant</div>
                <div class="info-value">' . h($student['first_name'] . ' ' . $student['last_name']) . '</div>
            </div>
            <div class="info-row">
                <div class="info-label">Email</div>
                <div class="info-value">' . h($studentUser['email'] ?? 'Non disponible') . '</div>
            </div>
            <div class="info-row">
                <div class="info-label">Programme</div>
                <div class="info-value">' . h($student['program'] ?? 'Non spécifié') . '</div>
            </div>
            <div class="info-row">
                <div class="info-label">Tuteur</div>
                <div class="info-value">' . h($teacher['first_name'] . ' ' . $teacher['last_name']) . '</div>
            </div>
            <div class="info-row">
                <div class="info-label">Date d\'évaluation</div>
                <div class="info-value">' . date('d/m/Y', strtotime($evaluation['created_at'] ?? date('Y-m-d'))) . '</div>
            </div>
        </div>
        
        <div class="info-card">
            <h2>Évaluation globale</h2>';
            
// Calculer la note sur 5
$scoreOn5 = number_format($evaluation['score'] / 4, 1);
$scorePercentage = ($scoreOn5 / 5) * 100;
$stars = round($scoreOn5);

$html .= '
            <div class="info-row">
                <div class="info-label">Note globale</div>
                <div class="info-value">
                    <div class="score-display">
                        <div class="star-rating">';

// Ajouter les étoiles
for ($i = 1; $i <= 5; $i++) {
    if ($i <= $stars) {
        $html .= '<span>★</span>';
    } else {
        $html .= '<span>☆</span>';
    }
}

$html .= '
                        </div>
                        <div class="score-number">' . $scoreOn5 . '</div>
                        <div style="font-size: 18px; color: #888;">/5</div>
                    </div>
                    <div class="score-bar">
                        <div class="score-fill" style="width: ' . $scorePercentage . '%;"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="info-card">
            <h2>Commentaires et observations</h2>
            <div class="comments">
                ' . nl2br(h($evaluation['feedback'] ?? 'Aucun commentaire')) . '
            </div>
        </div>';
    
if (!empty($evaluation['areas_to_improve'])) {
    $html .= '
        <div class="info-card">
            <h2>Points à améliorer</h2>
            <div class="comments">
                ' . nl2br(h($evaluation['areas_to_improve'])) . '
            </div>
        </div>';
}

// Récupérer l'internship associé à cet étudiant
$internshipModel = new Internship($db);
$internship = null;
$company = null;

if ($assignment['internship_id']) {
    $internship = $internshipModel->getById($assignment['internship_id']);
    
    // Récupérer l'entreprise associée au stage
    if ($internship && !empty($internship['company_id'])) {
        $companyModel = new Company($db);
        $company = $companyModel->getById($internship['company_id']);
    }
}

// Ajouter les informations du stage si disponibles
if ($internship) {
    $html .= '
        <div class="info-card">
            <h2>Informations sur le stage</h2>
            <div class="info-row">
                <div class="info-label">Titre du stage</div>
                <div class="info-value">' . h($internship['title'] ?? 'Non spécifié') . '</div>
            </div>';
            
    if ($company) {
        $html .= '
            <div class="info-row">
                <div class="info-label">Entreprise</div>
                <div class="info-value">' . h($company['name'] ?? 'Non spécifiée') . '</div>
            </div>';
    }
    
    if (!empty($internship['start_date']) && !empty($internship['end_date'])) {
        $html .= '
            <div class="info-row">
                <div class="info-label">Période</div>
                <div class="info-value">Du ' . date('d/m/Y', strtotime($internship['start_date'])) . ' au ' . date('d/m/Y', strtotime($internship['end_date'])) . '</div>
            </div>';
            
        // Calculer la progression du stage
        $startDate = new DateTime($internship['start_date']);
        $endDate = new DateTime($internship['end_date']);
        $today = new DateTime();
        
        $totalDays = $startDate->diff($endDate)->days;
        $daysElapsed = ($today > $startDate) ? $startDate->diff($today)->days : 0;
        $progress = min(100, max(0, ($daysElapsed / max(1, $totalDays)) * 100));
        
        $html .= '
            <div class="info-row">
                <div class="info-label">Progression</div>
                <div class="info-value">
                    <div style="margin-top: 10px;">
                        <div class="score-bar">
                            <div class="score-fill" style="width: ' . $progress . '%; background: linear-gradient(to right, #4caf50, #8bc34a);"></div>
                        </div>
                        <div style="text-align: right; font-size: 13px; color: #888; margin-top: 5px;">' . round($progress) . '% complété</div>
                    </div>
                </div>
            </div>';
    }
    
    $html .= '
        </div>';
}

$html .= '
        <div class="footer">
            <p>Document généré le ' . date('d/m/Y à H:i') . '</p>
            <p>TutorMatch - Plateforme de gestion de stages et tutorat</p>
        </div>
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