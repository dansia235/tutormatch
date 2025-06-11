<?php
/**
 * API pour exporter la liste des étudiants
 * Endpoint: /api/export/students.php
 * Méthode: GET
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../includes/init.php';

// Vérifier que l'utilisateur est connecté et a les droits
if (!isLoggedIn() || !hasRole(['admin', 'coordinator'])) {
    header("HTTP/1.1 403 Forbidden");
    echo "Accès non autorisé";
    exit;
}

// Récupérer le format d'exportation
$format = isset($_GET['format']) ? strtolower($_GET['format']) : 'csv';

// Vérifier si c'est un rapport spécifique
$reportType = isset($_GET['report']) ? $_GET['report'] : '';

// Vérifier si on exporte tous les étudiants ou seulement ceux filtrés
$exportAll = isset($_GET['exportAll']) && $_GET['exportAll'] === 'true';

// Récupérer les colonnes à exporter
$columns = [];
if (isset($_GET['columns'])) {
    // Vérifier si c'est déjà un tableau (envoyé via un form avec checkboxes multiples)
    if (is_array($_GET['columns'])) {
        $columns = $_GET['columns'];
    } 
    // Sinon, si c'est une chaîne, la diviser
    elseif (is_string($_GET['columns']) && !empty($_GET['columns'])) {
        $columns = explode(',', $_GET['columns']);
    }
}

// Instancier le contrôleur
$studentController = new StudentController($db);

// Récupérer les données
if ($reportType) {
    // Si c'est un rapport, récupérer tous les étudiants
    $students = $studentController->getStudents();
    
    // Pour les rapports, forcer l'export PDF
    if ($reportType === 'full' || $reportType === 'charts') {
        $format = 'pdf';
    } elseif ($reportType === 'stats') {
        $format = 'excel';
    }
} else if ($exportAll) {
    $students = $studentController->getStudents();
} else {
    // Récupérer les mêmes filtres que ceux appliqués dans la vue
    $term = isset($_GET['term']) ? $_GET['term'] : '';
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    $program = isset($_GET['program']) ? $_GET['program'] : null;
    $level = isset($_GET['level']) ? $_GET['level'] : null;
    
    $students = $studentController->search($term, $status, true);  // Passer true pour retourner les résultats
    
    // Filtrage supplémentaire côté PHP si nécessaire
    if ($program || $level) {
        $students = array_filter($students, function($student) use ($program, $level) {
            $matchProgram = !$program || $student['program'] === $program;
            $matchLevel = !$level || $student['level'] === $level;
            return $matchProgram && $matchLevel;
        });
    }
}

// Définir toutes les colonnes disponibles
$allColumns = [
    'id' => 'ID',
    'first_name' => 'Prénom',
    'last_name' => 'Nom',
    'email' => 'Email',
    'student_number' => 'Numéro d\'étudiant',
    'program' => 'Programme',
    'level' => 'Niveau',
    'department' => 'Département',
    'status' => 'Statut'
];

// Si aucune colonne spécifiée, utiliser toutes les colonnes
if (empty($columns)) {
    $columns = array_keys($allColumns);
}

// Préparer les données pour l'exportation
$exportData = [];

// Ajouter l'en-tête
$header = [];
foreach ($columns as $column) {
    if (isset($allColumns[$column])) {
        $header[] = $allColumns[$column];
    }
}
$exportData[] = $header;

// Ajouter les données
foreach ($students as $student) {
    $row = [];
    foreach ($columns as $column) {
        if (isset($student[$column])) {
            // Traduction des valeurs pour certains champs
            if ($column === 'status') {
                $statusMap = [
                    'active' => 'Actif',
                    'graduated' => 'Diplômé',
                    'suspended' => 'Suspendu'
                ];
                $row[] = $statusMap[$student[$column]] ?? $student[$column];
            } else {
                $row[] = $student[$column];
            }
        } else {
            $row[] = '';
        }
    }
    $exportData[] = $row;
}

// Exporter selon le format demandé
switch ($format) {
    case 'csv':
        exportToCsv($exportData, 'etudiants');
        break;
        
    case 'excel':
        exportToExcel($exportData, 'etudiants');
        break;
        
    case 'pdf':
        exportToPdf($exportData, 'etudiants', $header);
        break;
        
    default:
        header("HTTP/1.1 400 Bad Request");
        echo "Format d'exportation non pris en charge";
        exit;
}

/**
 * Exporte des données au format CSV
 * @param array $data Données à exporter
 * @param string $filename Nom du fichier (sans extension)
 */
function exportToCsv($data, $filename) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Ajouter BOM UTF-8 pour Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

/**
 * Exporte des données au format Excel (XLSX)
 * @param array $data Données à exporter
 * @param string $filename Nom du fichier (sans extension)
 */
function exportToExcel($data, $filename) {
    // Comme nous n'avons pas de bibliothèque pour Excel, nous utilisons CSV avec une extension .xlsx
    // Dans un environnement de production, utilisez une bibliothèque comme PhpSpreadsheet
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
    
    $output = fopen('php://output', 'w');
    
    // Ajouter BOM UTF-8 pour Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    foreach ($data as $row) {
        fputcsv($output, $row, ';'); // Utiliser le point-virgule comme délimiteur pour Excel
    }
    
    fclose($output);
    exit;
}

/**
 * Exporte des données au format PDF
 * @param array $data Données à exporter
 * @param string $filename Nom du fichier (sans extension)
 * @param array $header En-têtes des colonnes
 */
function exportToPdf($data, $filename, $header) {
    global $reportType, $students, $db;
    
    // Comme nous n'avons pas de bibliothèque PDF, nous générons un HTML simple
    // Dans un environnement de production, utilisez une bibliothèque comme TCPDF ou FPDF
    
    header('Content-Type: text/html; charset=utf-8');
    
    // Si c'est un rapport complet
    if ($reportType === 'full') {
        // Pour un rapport complet, nous générons un PDF plus élaboré avec des graphiques
        generateFullReport($students);
        exit;
    } 
    // Si c'est juste les graphiques
    else if ($reportType === 'charts') {
        // Générer un PDF avec uniquement les graphiques
        generateChartsReport($students);
        exit;
    }
    
    // Export PDF standard pour les données tabulaires
    echo '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Export - ' . htmlspecialchars($filename) . '</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            h1 { color: #333; }
            table { border-collapse: collapse; width: 100%; margin-top: 20px; }
            th, td { padding: 8px 12px; text-align: left; border-bottom: 1px solid #ddd; }
            th { background-color: #f2f2f2; font-weight: bold; }
            tr:hover { background-color: #f5f5f5; }
            .footer { margin-top: 30px; color: #777; font-size: 0.8em; }
            @media print {
                body { margin: 0; padding: 20px; }
                button { display: none; }
            }
        </style>
    </head>
    <body>
        <h1>Liste des étudiants</h1>
        <p>Date d\'exportation: ' . date('d/m/Y H:i') . '</p>
        
        <button onclick="window.print()" style="padding: 8px 15px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">Imprimer / Enregistrer en PDF</button>
        
        <table>
            <thead>
                <tr>';
    
    // En-têtes
    foreach ($header as $column) {
        echo '<th>' . htmlspecialchars($column) . '</th>';
    }
    
    echo '</tr>
            </thead>
            <tbody>';
    
    // Lignes de données (sans l'en-tête)
    array_shift($data); // Enlever l'en-tête
    foreach ($data as $row) {
        echo '<tr>';
        foreach ($row as $cell) {
            echo '<td>' . htmlspecialchars($cell) . '</td>';
        }
        echo '</tr>';
    }
    
    echo '</tbody>
        </table>
        
        <div class="footer">
            <p>Document généré par le système TutorMatch. Total: ' . count($data) . ' étudiants.</p>
        </div>
        
        <script>
            // Imprimer automatiquement (optionnel)
            //window.onload = function() { window.print(); }
        </script>
    </body>
    </html>';
    
    exit;
}

/**
 * Génère un rapport complet sur les étudiants avec graphiques
 * @param array $students Liste des étudiants
 */
function generateFullReport($students) {
    // Statistiques des étudiants
    $totalStudents = count($students);
    
    $activeStudents = count(array_filter($students, function($student) {
        return $student['status'] === 'active';
    }));
    
    $graduatedStudents = count(array_filter($students, function($student) {
        return $student['status'] === 'graduated';
    }));
    
    $suspendedStudents = count(array_filter($students, function($student) {
        return $student['status'] === 'suspended';
    }));
    
    // Programmes d'études
    $programs = array_count_values(array_column($students, 'program'));
    arsort($programs); // Trier par nombre décroissant
    
    // Niveaux d'études
    $levels = array_count_values(array_column($students, 'level'));
    arsort($levels); // Trier par nombre décroissant
    
    // Départements
    $departments = array_count_values(array_column($students, 'department'));
    arsort($departments); // Trier par nombre décroissant
    
    // Créer une instance du modèle Assignment directement
    global $db;
    $assignmentModel = new Assignment($db);
    
    // Récupérer les affectations des étudiants
    $assignments = $assignmentModel->getAll();
    $assignedStudents = [];
    foreach ($assignments as $assignment) {
        $assignedStudents[$assignment['student_id']] = $assignment;
    }
    
    // Compter les étudiants avec et sans affectation
    $studentsWithAssignment = count($assignedStudents);
    $studentsWithoutAssignment = $activeStudents - $studentsWithAssignment;
    
    // Répartition par statut d'affectation
    $assignmentStatusCounts = [];
    foreach ($assignments as $assignment) {
        $status = $assignment['status'];
        if (!isset($assignmentStatusCounts[$status])) {
            $assignmentStatusCounts[$status] = 0;
        }
        $assignmentStatusCounts[$status]++;
    }
    
    // Générer le HTML pour le rapport
    echo '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Rapport sur les étudiants</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            h1, h2, h3 { color: #333; }
            .report-section { margin-bottom: 30px; }
            .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 20px; }
            .stat-card { background-color: #f8f9fa; border-radius: 8px; padding: 15px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
            .stat-card .value { font-size: 2rem; font-weight: 700; color: #0d6efd; }
            .stat-card .label { color: #6c757d; font-size: 0.9rem; text-transform: uppercase; }
            table { border-collapse: collapse; width: 100%; margin: 15px 0; }
            th, td { padding: 8px 12px; text-align: left; border-bottom: 1px solid #ddd; }
            th { background-color: #f2f2f2; font-weight: bold; }
            tr:hover { background-color: #f5f5f5; }
            .charts-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin: 20px 0; }
            .chart-container { background-color: #fff; border-radius: 8px; padding: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
            .footer { margin-top: 30px; color: #777; font-size: 0.8em; text-align: center; }
            .badge { 
                display: inline-block;
                padding: 3px 8px;
                font-size: 0.75em;
                font-weight: 700;
                border-radius: 4px;
            }
            .badge-success { background-color: #28a745; color: white; }
            .badge-info { background-color: #17a2b8; color: white; }
            .badge-warning { background-color: #ffc107; color: #212529; }
            .badge-primary { background-color: #0d6efd; color: white; }
            .progress {
                height: 8px;
                background-color: #e9ecef;
                border-radius: 4px;
                overflow: hidden;
                margin-top: 8px;
            }
            .progress-bar {
                height: 100%;
                background-color: #0d6efd;
            }
            .progress-bar-success { background-color: #28a745; }
            .progress-bar-info { background-color: #17a2b8; }
            .progress-bar-warning { background-color: #ffc107; }
            .col-header { font-weight: bold; margin-bottom: 5px; }
            @media print {
                body { margin: 0; padding: 20px; }
                button { display: none; }
                .chart-container { break-inside: avoid; }
                .page-break { page-break-before: always; }
            }
        </style>
        <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    </head>
    <body>
        <h1>Rapport sur les étudiants</h1>
        <p>Date du rapport: ' . date('d/m/Y H:i') . '</p>
        
        <button onclick="window.print()" style="padding: 8px 15px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; margin-bottom: 20px;">
            Imprimer / Enregistrer en PDF
        </button>
        
        <div class="report-section">
            <h2>Résumé</h2>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="value">' . $totalStudents . '</div>
                    <div class="label">Étudiants total</div>
                    <div class="progress">
                        <div class="progress-bar" style="width: 100%;"></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="value">' . $activeStudents . '</div>
                    <div class="label">Étudiants actifs</div>
                    <div class="progress">
                        <div class="progress-bar progress-bar-success" style="width: ' . ($totalStudents > 0 ? ($activeStudents / $totalStudents * 100) : 0) . '%;"></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="value">' . $graduatedStudents . '</div>
                    <div class="label">Diplômés</div>
                    <div class="progress">
                        <div class="progress-bar progress-bar-info" style="width: ' . ($totalStudents > 0 ? ($graduatedStudents / $totalStudents * 100) : 0) . '%;"></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="value">' . $suspendedStudents . '</div>
                    <div class="label">Suspendus</div>
                    <div class="progress">
                        <div class="progress-bar progress-bar-warning" style="width: ' . ($totalStudents > 0 ? ($suspendedStudents / $totalStudents * 100) : 0) . '%;"></div>
                    </div>
                </div>
            </div>
            
            <h3>Affectations</h3>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="value">' . $studentsWithAssignment . '</div>
                    <div class="label">Étudiants avec tuteur</div>
                    <div class="progress">
                        <div class="progress-bar progress-bar-success" style="width: ' . ($activeStudents > 0 ? ($studentsWithAssignment / $activeStudents * 100) : 0) . '%;"></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="value">' . $studentsWithoutAssignment . '</div>
                    <div class="label">Étudiants sans tuteur</div>
                    <div class="progress">
                        <div class="progress-bar progress-bar-warning" style="width: ' . ($activeStudents > 0 ? ($studentsWithoutAssignment / $activeStudents * 100) : 0) . '%;"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="report-section charts-grid">
            <div class="chart-container">
                <h3>Répartition par statut</h3>
                <canvas id="statusChart" width="400" height="300"></canvas>
            </div>
            
            <div class="chart-container">
                <h3>Étudiants avec/sans tuteur</h3>
                <canvas id="assignmentChart" width="400" height="300"></canvas>
            </div>
            
            <div class="chart-container">
                <h3>Top 5 des programmes d\'études</h3>
                <canvas id="programChart" width="400" height="300"></canvas>
            </div>
            
            <div class="chart-container">
                <h3>Répartition par niveau d\'études</h3>
                <canvas id="levelChart" width="400" height="300"></canvas>
            </div>
        </div>
        
        <div class="page-break"></div>
        
        <div class="report-section">
            <h2>Statistiques détaillées</h2>
            
            <h3>Programmes d\'études</h3>
            <table>
                <thead>
                    <tr>
                        <th>Programme</th>
                        <th>Nombre d\'étudiants</th>
                        <th>Pourcentage</th>
                    </tr>
                </thead>
                <tbody>';
                
    // Programmes d'études
    foreach ($programs as $program => $count) {
        $percentage = $totalStudents > 0 ? number_format(($count / $totalStudents) * 100, 1) : 0;
        echo '<tr>
                <td>' . htmlspecialchars($program) . '</td>
                <td>' . $count . '</td>
                <td>' . $percentage . '%</td>
              </tr>';
    }
    
    echo '</tbody>
            </table>
            
            <h3>Niveaux d\'études</h3>
            <table>
                <thead>
                    <tr>
                        <th>Niveau</th>
                        <th>Nombre d\'étudiants</th>
                        <th>Pourcentage</th>
                    </tr>
                </thead>
                <tbody>';
                
    // Niveaux d'études
    foreach ($levels as $level => $count) {
        $percentage = $totalStudents > 0 ? number_format(($count / $totalStudents) * 100, 1) : 0;
        echo '<tr>
                <td>' . htmlspecialchars($level) . '</td>
                <td>' . $count . '</td>
                <td>' . $percentage . '%</td>
              </tr>';
    }
    
    echo '</tbody>
            </table>
            
            <h3>Départements</h3>
            <table>
                <thead>
                    <tr>
                        <th>Département</th>
                        <th>Nombre d\'étudiants</th>
                        <th>Pourcentage</th>
                    </tr>
                </thead>
                <tbody>';
                
    // Départements
    foreach ($departments as $department => $count) {
        $percentage = $totalStudents > 0 ? number_format(($count / $totalStudents) * 100, 1) : 0;
        echo '<tr>
                <td>' . htmlspecialchars($department) . '</td>
                <td>' . $count . '</td>
                <td>' . $percentage . '%</td>
              </tr>';
    }
    
    echo '</tbody>
            </table>
        </div>
        
        <div class="footer">
            <p>Document généré par le système TutorMatch. Total: ' . $totalStudents . ' étudiants.</p>
        </div>
        
        <script>
            // Données pour les graphiques
            const statusData = {
                labels: ["Actifs", "Diplômés", "Suspendus"],
                datasets: [{
                    label: "Nombre d\'étudiants",
                    data: [' . $activeStudents . ', ' . $graduatedStudents . ', ' . $suspendedStudents . '],
                    backgroundColor: ["rgba(40, 167, 69, 0.7)", "rgba(23, 162, 184, 0.7)", "rgba(255, 193, 7, 0.7)"],
                    borderColor: ["rgb(40, 167, 69)", "rgb(23, 162, 184)", "rgb(255, 193, 7)"],
                    borderWidth: 1
                }]
            };

            const assignmentData = {
                labels: ["Avec tuteur", "Sans tuteur"],
                datasets: [{
                    label: "Nombre d\'étudiants",
                    data: [' . $studentsWithAssignment . ', ' . $studentsWithoutAssignment . '],
                    backgroundColor: ["rgba(0, 123, 255, 0.7)", "rgba(108, 117, 125, 0.7)"],
                    borderColor: ["rgb(0, 123, 255)", "rgb(108, 117, 125)"],
                    borderWidth: 1
                }]
            };

            // Préparer les données pour les programmes (top 5)
            const programLabels = [];
            const programValues = [];
            let counter = 0;
            ';
            
    // Extraire les 5 premiers programmes
    $counter = 0;
    $programLabelsJS = [];
    $programValuesJS = [];
    
    foreach ($programs as $program => $count) {
        if ($counter < 5) {
            $programLabelsJS[] = '"' . addslashes($program) . '"';
            $programValuesJS[] = $count;
            $counter++;
        }
    }
    
    echo 'const programData = {
                labels: [' . implode(', ', $programLabelsJS) . '],
                datasets: [{
                    label: "Nombre d\'étudiants",
                    data: [' . implode(', ', $programValuesJS) . '],
                    backgroundColor: "rgba(54, 162, 235, 0.7)",
                    borderColor: "rgb(54, 162, 235)",
                    borderWidth: 1
                }]
            };';
            
    // Extraire les niveaux d'études
    $levelLabelsJS = [];
    $levelValuesJS = [];
    
    foreach ($levels as $level => $count) {
        $levelLabelsJS[] = '"' . addslashes($level) . '"';
        $levelValuesJS[] = $count;
    }
    
    echo 'const levelData = {
                labels: [' . implode(', ', $levelLabelsJS) . '],
                datasets: [{
                    label: "Nombre d\'étudiants",
                    data: [' . implode(', ', $levelValuesJS) . '],
                    backgroundColor: "rgba(153, 102, 255, 0.7)",
                    borderColor: "rgb(153, 102, 255)",
                    borderWidth: 1
                }]
            };

            // Créer les graphiques une fois que la page est chargée
            window.onload = function() {
                // Graphique de statut
                const statusChart = new Chart(
                    document.getElementById("statusChart"),
                    {
                        type: "pie",
                        data: statusData,
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    position: "right",
                                },
                                title: {
                                    display: true,
                                    text: "Répartition par statut"
                                }
                            }
                        }
                    }
                );

                // Graphique d\'affectation
                const assignmentChart = new Chart(
                    document.getElementById("assignmentChart"),
                    {
                        type: "pie",
                        data: assignmentData,
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    position: "right",
                                },
                                title: {
                                    display: true,
                                    text: "Étudiants avec/sans tuteur"
                                }
                            }
                        }
                    }
                );

                // Graphique des programmes
                const programChart = new Chart(
                    document.getElementById("programChart"),
                    {
                        type: "bar",
                        data: programData,
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    display: false,
                                },
                                title: {
                                    display: true,
                                    text: "Top 5 des programmes d\'études"
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    }
                );

                // Graphique des niveaux
                const levelChart = new Chart(
                    document.getElementById("levelChart"),
                    {
                        type: "bar",
                        data: levelData,
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    display: false,
                                },
                                title: {
                                    display: true,
                                    text: "Répartition par niveau d\'études"
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    }
                );
            };
        </script>
    </body>
    </html>';
    
    exit;
}

/**
 * Génère un rapport avec uniquement les graphiques
 * @param array $students Liste des étudiants
 */
function generateChartsReport($students) {
    // Statistiques des étudiants
    $totalStudents = count($students);
    
    $activeStudents = count(array_filter($students, function($student) {
        return $student['status'] === 'active';
    }));
    
    $graduatedStudents = count(array_filter($students, function($student) {
        return $student['status'] === 'graduated';
    }));
    
    $suspendedStudents = count(array_filter($students, function($student) {
        return $student['status'] === 'suspended';
    }));
    
    // Programmes d'études
    $programs = array_count_values(array_column($students, 'program'));
    arsort($programs); // Trier par nombre décroissant
    
    // Niveaux d'études
    $levels = array_count_values(array_column($students, 'level'));
    arsort($levels); // Trier par nombre décroissant
    
    // Créer une instance du modèle Assignment directement
    global $db;
    $assignmentModel = new Assignment($db);
    
    // Récupérer les affectations des étudiants
    $assignments = $assignmentModel->getAll();
    $assignedStudents = [];
    foreach ($assignments as $assignment) {
        $assignedStudents[$assignment['student_id']] = $assignment;
    }
    
    // Compter les étudiants avec et sans affectation
    $studentsWithAssignment = count($assignedStudents);
    $studentsWithoutAssignment = $activeStudents - $studentsWithAssignment;
    
    // Générer le HTML pour le rapport
    echo '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Graphiques - Étudiants</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            h1, h2 { color: #333; }
            .charts-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin: 20px 0; }
            .chart-container { background-color: #fff; border-radius: 8px; padding: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
            .footer { margin-top: 30px; color: #777; font-size: 0.8em; text-align: center; }
            @media print {
                body { margin: 0; padding: 20px; }
                button { display: none; }
            }
        </style>
        <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    </head>
    <body>
        <h1>Graphiques - Statistiques des étudiants</h1>
        <p>Date du rapport: ' . date('d/m/Y H:i') . '</p>
        
        <button onclick="window.print()" style="padding: 8px 15px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; margin-bottom: 20px;">
            Imprimer / Enregistrer en PDF
        </button>
        
        <div class="charts-grid">
            <div class="chart-container">
                <h2>Répartition par statut</h2>
                <canvas id="statusChart" width="400" height="300"></canvas>
            </div>
            
            <div class="chart-container">
                <h2>Étudiants avec/sans tuteur</h2>
                <canvas id="assignmentChart" width="400" height="300"></canvas>
            </div>
            
            <div class="chart-container">
                <h2>Top 5 des programmes d\'études</h2>
                <canvas id="programChart" width="400" height="300"></canvas>
            </div>
            
            <div class="chart-container">
                <h2>Répartition par niveau d\'études</h2>
                <canvas id="levelChart" width="400" height="300"></canvas>
            </div>
        </div>
        
        <div class="footer">
            <p>Document généré par le système TutorMatch. Total: ' . $totalStudents . ' étudiants.</p>
        </div>
        
        <script>
            // Données pour les graphiques
            const statusData = {
                labels: ["Actifs", "Diplômés", "Suspendus"],
                datasets: [{
                    label: "Nombre d\'étudiants",
                    data: [' . $activeStudents . ', ' . $graduatedStudents . ', ' . $suspendedStudents . '],
                    backgroundColor: ["rgba(40, 167, 69, 0.7)", "rgba(23, 162, 184, 0.7)", "rgba(255, 193, 7, 0.7)"],
                    borderColor: ["rgb(40, 167, 69)", "rgb(23, 162, 184)", "rgb(255, 193, 7)"],
                    borderWidth: 1
                }]
            };

            const assignmentData = {
                labels: ["Avec tuteur", "Sans tuteur"],
                datasets: [{
                    label: "Nombre d\'étudiants",
                    data: [' . $studentsWithAssignment . ', ' . $studentsWithoutAssignment . '],
                    backgroundColor: ["rgba(0, 123, 255, 0.7)", "rgba(108, 117, 125, 0.7)"],
                    borderColor: ["rgb(0, 123, 255)", "rgb(108, 117, 125)"],
                    borderWidth: 1
                }]
            };';
    
    // Extraire les 5 premiers programmes
    $counter = 0;
    $programLabelsJS = [];
    $programValuesJS = [];
    
    foreach ($programs as $program => $count) {
        if ($counter < 5) {
            $programLabelsJS[] = '"' . addslashes($program) . '"';
            $programValuesJS[] = $count;
            $counter++;
        }
    }
    
    echo 'const programData = {
                labels: [' . implode(', ', $programLabelsJS) . '],
                datasets: [{
                    label: "Nombre d\'étudiants",
                    data: [' . implode(', ', $programValuesJS) . '],
                    backgroundColor: "rgba(54, 162, 235, 0.7)",
                    borderColor: "rgb(54, 162, 235)",
                    borderWidth: 1
                }]
            };';
            
    // Extraire les niveaux d'études
    $levelLabelsJS = [];
    $levelValuesJS = [];
    
    foreach ($levels as $level => $count) {
        $levelLabelsJS[] = '"' . addslashes($level) . '"';
        $levelValuesJS[] = $count;
    }
    
    echo 'const levelData = {
                labels: [' . implode(', ', $levelLabelsJS) . '],
                datasets: [{
                    label: "Nombre d\'étudiants",
                    data: [' . implode(', ', $levelValuesJS) . '],
                    backgroundColor: "rgba(153, 102, 255, 0.7)",
                    borderColor: "rgb(153, 102, 255)",
                    borderWidth: 1
                }]
            };

            // Créer les graphiques une fois que la page est chargée
            window.onload = function() {
                // Graphique de statut
                const statusChart = new Chart(
                    document.getElementById("statusChart"),
                    {
                        type: "pie",
                        data: statusData,
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    position: "right",
                                },
                                title: {
                                    display: true,
                                    text: "Répartition par statut"
                                }
                            }
                        }
                    }
                );

                // Graphique d\'affectation
                const assignmentChart = new Chart(
                    document.getElementById("assignmentChart"),
                    {
                        type: "pie",
                        data: assignmentData,
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    position: "right",
                                },
                                title: {
                                    display: true,
                                    text: "Étudiants avec/sans tuteur"
                                }
                            }
                        }
                    }
                );

                // Graphique des programmes
                const programChart = new Chart(
                    document.getElementById("programChart"),
                    {
                        type: "bar",
                        data: programData,
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    display: false,
                                },
                                title: {
                                    display: true,
                                    text: "Top 5 des programmes d\'études"
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    }
                );

                // Graphique des niveaux
                const levelChart = new Chart(
                    document.getElementById("levelChart"),
                    {
                        type: "bar",
                        data: levelData,
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    display: false,
                                },
                                title: {
                                    display: true,
                                    text: "Répartition par niveau d\'études"
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    }
                );
            };
        </script>
    </body>
    </html>';
    
    exit;
}
?>