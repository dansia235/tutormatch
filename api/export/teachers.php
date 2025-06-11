<?php
/**
 * API pour exporter la liste des tuteurs
 * Endpoint: /api/export/teachers.php
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

// Vérifier s'il s'agit d'un rapport
$reportType = isset($_GET['report']) ? $_GET['report'] : null;

// Vérifier si on exporte tous les tuteurs ou seulement ceux filtrés
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

// Instancier le contrôleur et les modèles nécessaires
$teacherController = new TeacherController($db);
$teacherModel = new Teacher($db);

// Récupérer les données
if ($reportType) {
    // Si c'est un rapport, récupérer tous les tuteurs
    $teachers = $teacherController->getTeachers();
    
    // Pour les rapports, forcer l'export PDF
    if ($reportType === 'full' || $reportType === 'charts') {
        $format = 'pdf';
    } elseif ($reportType === 'stats') {
        $format = 'excel';
    }
} elseif ($exportAll) {
    $teachers = $teacherController->getTeachers();
} else {
    // Récupérer les mêmes filtres que ceux appliqués dans la vue
    $term = isset($_GET['term']) ? $_GET['term'] : '';
    $available = isset($_GET['available']) ? (bool)$_GET['available'] : null;
    
    $teachers = $teacherController->search($term, $available, true);
}

// Définir toutes les colonnes disponibles
$allColumns = [
    'id' => 'ID',
    'first_name' => 'Prénom',
    'last_name' => 'Nom',
    'email' => 'Email',
    'title' => 'Titre',
    'department' => 'Département',
    'specialty' => 'Spécialité',
    'office_location' => 'Bureau',
    'max_students' => 'Capacité maximale',
    'available' => 'Disponible',
    'expertise' => 'Expertise'
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
foreach ($teachers as $teacher) {
    $row = [];
    foreach ($columns as $column) {
        if (isset($teacher[$column])) {
            // Traduction des valeurs pour certains champs
            if ($column === 'available') {
                $row[] = $teacher[$column] ? 'Oui' : 'Non';
            } else {
                $row[] = $teacher[$column];
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
        exportToCsv($exportData, 'tuteurs');
        break;
        
    case 'excel':
        exportToExcel($exportData, 'tuteurs');
        break;
        
    case 'pdf':
        exportToPdf($exportData, 'tuteurs', $header, $reportType);
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
 * @param string $reportType Type de rapport (full, charts, null)
 */
function exportToPdf($data, $filename, $header, $reportType = null) {
    global $teacherModel;
    
    // Comme nous n'avons pas de bibliothèque PDF, nous générons un HTML simple
    // Dans un environnement de production, utilisez une bibliothèque comme TCPDF ou FPDF
    
    header('Content-Type: text/html; charset=utf-8');
    
    echo '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Export - ' . htmlspecialchars($filename) . '</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            h1, h2 { color: #333; }
            table { border-collapse: collapse; width: 100%; margin-top: 20px; }
            th, td { padding: 8px 12px; text-align: left; border-bottom: 1px solid #ddd; }
            th { background-color: #f2f2f2; font-weight: bold; }
            tr:hover { background-color: #f5f5f5; }
            .footer { margin-top: 30px; color: #777; font-size: 0.8em; }
            .chart-container { height: 300px; margin-bottom: 30px; }
            .stat-card { 
                border: 1px solid #ddd; 
                border-radius: 8px; 
                padding: 15px; 
                margin-bottom: 20px; 
                text-align: center; 
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            }
            .stat-card .value { 
                font-size: 2.5rem; 
                font-weight: bold; 
                color: #333; 
            }
            .stat-card .label { 
                color: #777; 
                font-size: 1rem; 
            }
            .stat-row {
                display: flex;
                justify-content: space-between;
                margin-bottom: 20px;
            }
            .stat-card {
                flex: 1;
                margin: 0 10px;
            }
            .progress {
                height: 10px;
                background-color: #e9ecef;
                border-radius: 5px;
                margin-top: 10px;
            }
            .progress-bar {
                height: 100%;
                border-radius: 5px;
                background-color: #007bff;
            }
            .progress-bar.bg-success { background-color: #28a745; }
            .progress-bar.bg-warning { background-color: #ffc107; }
            .progress-bar.bg-info { background-color: #17a2b8; }
            .progress-bar.bg-danger { background-color: #dc3545; }
            @media print {
                body { margin: 0; padding: 20px; }
                button { display: none; }
                .chart-container { break-inside: avoid; }
                .page-break { page-break-after: always; }
            }
        </style>';
    
    // Si c'est un rapport avec des graphiques, inclure Chart.js
    if ($reportType === 'full' || $reportType === 'charts') {
        echo '<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>';
    }
    
    echo '</head>
    <body>
        <h1>Rapport sur les tuteurs</h1>
        <p>Date d\'exportation: ' . date('d/m/Y H:i') . '</p>
        
        <button onclick="window.print()" style="padding: 8px 15px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">Imprimer / Enregistrer en PDF</button>';
    
    // Traitement différent selon le type de rapport
    if ($reportType === 'full' || $reportType === 'charts') {
        // Préparer les données pour les graphiques
        $teachers = array_shift($data); // Enlever l'en-tête et récupérer les tuteurs
        
        // Récupérer les statistiques
        $totalTeachers = count($data);
        $availableTeachers = 0;
        $unavailableTeachers = 0;
        
        foreach ($data as $row) {
            if (isset($row[array_search('Disponible', $header)]) && $row[array_search('Disponible', $header)] === 'Oui') {
                $availableTeachers++;
            } else {
                $unavailableTeachers++;
            }
        }
        
        // Récupérer les départements et spécialités
        $departments = [];
        $specialties = [];
        
        foreach ($data as $row) {
            $department = $row[array_search('Département', $header)] ?? 'Non spécifié';
            $specialty = $row[array_search('Spécialité', $header)] ?? 'Non spécifié';
            
            if (!empty($department)) {
                if (!isset($departments[$department])) {
                    $departments[$department] = 0;
                }
                $departments[$department]++;
            }
            
            if (!empty($specialty)) {
                if (!isset($specialties[$specialty])) {
                    $specialties[$specialty] = 0;
                }
                $specialties[$specialty]++;
            }
        }
        
        // Trier par nombre décroissant
        arsort($departments);
        arsort($specialties);
        
        // Statistiques de charge de travail
        $workloadStats = $teacherModel->getWorkloadStats();
        $lowWorkload = 0;  // < 33%
        $mediumWorkload = 0; // 33-66%
        $highWorkload = 0;  // > 66%
        $fullWorkload = 0;  // 100%
        
        foreach ($workloadStats as $stat) {
            $percentage = $stat['workload_percentage'];
            if ($percentage >= 100) {
                $fullWorkload++;
            } elseif ($percentage > 66) {
                $highWorkload++;
            } elseif ($percentage >= 33) {
                $mediumWorkload++;
            } else {
                $lowWorkload++;
            }
        }
        
        // Afficher les cartes de statistiques
        echo '
        <h2>Statistiques générales</h2>
        <div class="stat-row">
            <div class="stat-card">
                <div class="value">' . $totalTeachers . '</div>
                <div class="label">Tuteurs total</div>
                <div class="progress">
                    <div class="progress-bar" style="width: 100%;"></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="value">' . $availableTeachers . '</div>
                <div class="label">Tuteurs disponibles</div>
                <div class="progress">
                    <div class="progress-bar bg-success" style="width: ' . ($totalTeachers > 0 ? ($availableTeachers / $totalTeachers * 100) : 0) . '%;"></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="value">' . $unavailableTeachers . '</div>
                <div class="label">Tuteurs non disponibles</div>
                <div class="progress">
                    <div class="progress-bar bg-warning" style="width: ' . ($totalTeachers > 0 ? ($unavailableTeachers / $totalTeachers * 100) : 0) . '%;"></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="value">' . array_sum(array_column($workloadStats, 'current_students')) . '</div>
                <div class="label">Étudiants encadrés</div>
                <div class="progress">
                    <div class="progress-bar bg-info" style="width: 100%;"></div>
                </div>
            </div>
        </div>';
        
        // Afficher les graphiques
        echo '
        <div class="page-break"></div>
        <h2>Graphiques</h2>
        
        <div class="row">
            <div class="chart-container" style="width: 45%; float: left;">
                <h3>Répartition par disponibilité</h3>
                <canvas id="availabilityChart"></canvas>
            </div>
            
            <div class="chart-container" style="width: 45%; float: right;">
                <h3>Répartition par charge de travail</h3>
                <canvas id="workloadChart"></canvas>
            </div>
        </div>
        
        <div style="clear: both;"></div>
        
        <div class="row">
            <div class="chart-container" style="width: 45%; float: left;">
                <h3>Top 5 des départements</h3>
                <canvas id="departmentChart"></canvas>
            </div>
            
            <div class="chart-container" style="width: 45%; float: right;">
                <h3>Top 5 des spécialités</h3>
                <canvas id="specialtyChart"></canvas>
            </div>
        </div>
        
        <div style="clear: both;"></div>';
        
        // Ajouter le script pour générer les graphiques
        echo '
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                // Graphique de disponibilité
                new Chart(
                    document.getElementById("availabilityChart"),
                    {
                        type: "pie",
                        data: {
                            labels: ["Disponibles", "Non disponibles"],
                            datasets: [{
                                label: "Nombre de tuteurs",
                                data: [' . $availableTeachers . ', ' . $unavailableTeachers . '],
                                backgroundColor: ["rgba(40, 167, 69, 0.7)", "rgba(255, 193, 7, 0.7)"],
                                borderColor: ["rgb(40, 167, 69)", "rgb(255, 193, 7)"],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    position: "right",
                                }
                            }
                        }
                    }
                );
                
                // Graphique de charge de travail
                new Chart(
                    document.getElementById("workloadChart"),
                    {
                        type: "pie",
                        data: {
                            labels: ["Faible (<33%)", "Moyenne (33-66%)", "Élevée (67-99%)", "Complète (100%)"],
                            datasets: [{
                                label: "Nombre de tuteurs",
                                data: [' . $lowWorkload . ', ' . $mediumWorkload . ', ' . $highWorkload . ', ' . $fullWorkload . '],
                                backgroundColor: [
                                    "rgba(40, 167, 69, 0.7)", 
                                    "rgba(23, 162, 184, 0.7)", 
                                    "rgba(255, 193, 7, 0.7)", 
                                    "rgba(220, 53, 69, 0.7)"
                                ],
                                borderColor: [
                                    "rgb(40, 167, 69)", 
                                    "rgb(23, 162, 184)", 
                                    "rgb(255, 193, 7)", 
                                    "rgb(220, 53, 69)"
                                ],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    position: "right",
                                }
                            }
                        }
                    }
                );
                
                // Graphique des départements
                const departmentLabels = [];
                const departmentValues = [];
                let counter = 0;
                ';
                
                // Générer le code JavaScript pour les départements
                foreach ($departments as $department => $count) {
                    if ($counter < 5) {
                        echo 'departmentLabels.push("' . addslashes($department) . '");';
                        echo 'departmentValues.push(' . $count . ');';
                        $counter++;
                    }
                }
                
                echo '
                new Chart(
                    document.getElementById("departmentChart"),
                    {
                        type: "bar",
                        data: {
                            labels: departmentLabels,
                            datasets: [{
                                label: "Nombre de tuteurs",
                                data: departmentValues,
                                backgroundColor: "rgba(54, 162, 235, 0.7)",
                                borderColor: "rgb(54, 162, 235)",
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    display: false,
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
                
                // Graphique des spécialités
                const specialtyLabels = [];
                const specialtyValues = [];
                counter = 0;
                ';
                
                // Générer le code JavaScript pour les spécialités
                foreach ($specialties as $specialty => $count) {
                    if ($counter < 5) {
                        echo 'specialtyLabels.push("' . addslashes($specialty) . '");';
                        echo 'specialtyValues.push(' . $count . ');';
                        $counter++;
                    }
                }
                
                echo '
                new Chart(
                    document.getElementById("specialtyChart"),
                    {
                        type: "bar",
                        data: {
                            labels: specialtyLabels,
                            datasets: [{
                                label: "Nombre de tuteurs",
                                data: specialtyValues,
                                backgroundColor: "rgba(153, 102, 255, 0.7)",
                                borderColor: "rgb(153, 102, 255)",
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    display: false,
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
            });
        </script>';
    }
    
    // Si ce n'est pas uniquement un rapport de graphiques, afficher le tableau
    if ($reportType !== 'charts') {
        echo '
        <div class="page-break"></div>
        <h2>Liste des tuteurs</h2>
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
        array_shift($data); // Enlever l'en-tête s'il est encore là
        foreach ($data as $row) {
            echo '<tr>';
            foreach ($row as $cell) {
                echo '<td>' . htmlspecialchars($cell) . '</td>';
            }
            echo '</tr>';
        }
        
        echo '</tbody>
        </table>';
    }
    
    echo '
        <div class="footer">
            <p>Document généré par le système TutorMatch. Total: ' . count($data) . ' tuteurs.</p>
        </div>
        
        <script>
            // Imprimer automatiquement (optionnel)
            //window.onload = function() { window.print(); }
        </script>
    </body>
    </html>';
    
    exit;
}
?>