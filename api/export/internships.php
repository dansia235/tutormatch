<?php
/**
 * API pour exporter la liste des stages
 * Endpoint: /api/export/internships.php
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

// Vérifier si on exporte tous les stages ou seulement ceux filtrés
$exportAll = isset($_GET['exportAll']) && $_GET['exportAll'] === 'true';

// Récupérer les colonnes à exporter
$columns = [];
// Vérifier d'abord fields[] qui est utilisé dans le formulaire d'exportation
if (isset($_GET['fields']) && is_array($_GET['fields'])) {
    $columns = $_GET['fields'];
} 
// Maintenir la compatibilité avec columns[]
elseif (isset($_GET['columns'])) {
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
$internshipController = new InternshipController($db);
$internshipModel = new Internship($db);

// Récupérer les données
if ($reportType) {
    // Si c'est un rapport, récupérer tous les stages
    $internships = $internshipController->getAll();
    
    // Pour les rapports, forcer l'export PDF
    if ($reportType === 'full' || $reportType === 'charts') {
        $format = 'pdf';
    } elseif ($reportType === 'stats') {
        $format = 'excel';
    }
} elseif ($exportAll) {
    $internships = $internshipController->getAll();
} else {
    // Récupérer les mêmes filtres que ceux appliqués dans la vue
    $term = isset($_GET['term']) ? $_GET['term'] : '';
    
    // Support pour les paramètres export_* du formulaire d'exportation
    $status = isset($_GET['export_status']) ? $_GET['export_status'] : 
              (isset($_GET['status']) ? $_GET['status'] : null);
    
    $domain = isset($_GET['export_domain']) ? $_GET['export_domain'] : 
              (isset($_GET['domain']) ? $_GET['domain'] : null);
              
    $companyId = isset($_GET['company_id']) ? $_GET['company_id'] : null;
    
    // Appliquer d'abord la recherche par terme et statut
    $internships = $internshipController->search($term, $status);
    
    // Filtrage supplémentaire côté PHP si nécessaire
    if ($domain || $companyId) {
        $internships = array_filter($internships, function($internship) use ($domain, $companyId) {
            $matchDomain = !$domain || $internship['domain'] === $domain;
            $matchCompany = !$companyId || $internship['company_id'] == $companyId;
            return $matchDomain && $matchCompany;
        });
    }
    
    // Filtrage par timeline si spécifié
    $timeline = isset($_GET['export_timeline']) ? $_GET['export_timeline'] : null;
    if ($timeline) {
        $currentDate = date('Y-m-d');
        $internships = array_filter($internships, function($internship) use ($timeline, $currentDate) {
            $startDate = $internship['start_date'] ?? '';
            $endDate = $internship['end_date'] ?? '';
            
            if ($timeline === 'upcoming' && !empty($startDate)) {
                return $startDate > $currentDate;
            } elseif ($timeline === 'current' && !empty($startDate) && !empty($endDate)) {
                return $startDate <= $currentDate && $endDate >= $currentDate;
            } elseif ($timeline === 'past' && !empty($endDate)) {
                return $endDate < $currentDate;
            }
            
            return true;
        });
    }
}

// Définir toutes les colonnes disponibles
$allColumns = [
    'id' => 'ID',
    'title' => 'Titre',
    'company_name' => 'Entreprise',
    'description' => 'Description',
    'requirements' => 'Prérequis',
    'start_date' => 'Date de début',
    'end_date' => 'Date de fin',
    'location' => 'Lieu',
    'work_mode' => 'Mode de travail',
    'compensation' => 'Rémunération',
    'domain' => 'Domaine',
    'status' => 'Statut',
    'created_at' => 'Créé le'
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
foreach ($internships as $internship) {
    $row = [];
    foreach ($columns as $column) {
        if (isset($internship[$column])) {
            // Traduction des valeurs pour certains champs
            if ($column === 'status') {
                $statusMap = [
                    'available' => 'Disponible',
                    'assigned' => 'Affecté',
                    'completed' => 'Terminé',
                    'cancelled' => 'Annulé'
                ];
                $row[] = $statusMap[$internship[$column]] ?? $internship[$column];
            } elseif ($column === 'work_mode') {
                $workModeMap = [
                    'on_site' => 'Sur site',
                    'remote' => 'Télétravail',
                    'hybrid' => 'Hybride'
                ];
                $row[] = $workModeMap[$internship[$column]] ?? $internship[$column];
            } elseif ($column === 'start_date' || $column === 'end_date' || $column === 'created_at') {
                $row[] = !empty($internship[$column]) ? date('d/m/Y', strtotime($internship[$column])) : '';
            } else {
                $row[] = $internship[$column];
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
        exportToCsv($exportData, 'stages');
        break;
        
    case 'excel':
    case 'xlsx': // Ajout de la prise en charge du format xlsx
        exportToExcel($exportData, 'stages');
        break;
        
    case 'pdf':
        exportToPdf($exportData, 'stages', $header, $reportType);
        break;
        
    default:
        header("HTTP/1.1 400 Bad Request");
        echo "Format d'exportation non pris en charge: " . htmlspecialchars($format);
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
    global $internshipModel;
    
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
        <h1>Rapport sur les stages</h1>
        <p>Date d\'exportation: ' . date('d/m/Y H:i') . '</p>
        
        <button onclick="window.print()" style="padding: 8px 15px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">Imprimer / Enregistrer en PDF</button>';
    
    // Traitement différent selon le type de rapport
    if ($reportType === 'full' || $reportType === 'charts') {
        // Préparer les données pour les graphiques
        $internships = array_shift($data); // Enlever l'en-tête et récupérer les stages
        
        // Calculer les statistiques
        $totalInternships = count($data);
        $statusStats = [];
        $domainsStats = [];
        $timelineStats = [];
        $companyStats = [];
        $currentDate = date('Y-m-d');
        
        // Statuts en français et leurs classes
        $statusLabels = [
            'available' => 'Disponible',
            'assigned' => 'Affecté',
            'completed' => 'Terminé',
            'cancelled' => 'Annulé',
            'draft' => 'Brouillon',
            'pending' => 'En attente'
        ];
        
        // Calculer les statistiques par statut
        foreach ($data as $row) {
            $status = $row[array_search('Statut', $header)] ?? 'unknown';
            if (!isset($statusStats[$status])) {
                $statusStats[$status] = 0;
            }
            $statusStats[$status]++;
            
            // Statistiques par domaine
            $domain = $row[array_search('Domaine', $header)] ?? 'unknown';
            if (!empty($domain)) {
                if (!isset($domainsStats[$domain])) {
                    $domainsStats[$domain] = 0;
                }
                $domainsStats[$domain]++;
            }
            
            // Statistiques par entreprise
            $company = $row[array_search('Entreprise', $header)] ?? 'unknown';
            if (!empty($company)) {
                if (!isset($companyStats[$company])) {
                    $companyStats[$company] = 0;
                }
                $companyStats[$company]++;
            }
            
            // Statistiques temporelles
            $startDate = $row[array_search('Date de début', $header)] ?? null;
            $endDate = $row[array_search('Date de fin', $header)] ?? null;
            
            if ($startDate && $endDate) {
                if ($startDate > $currentDate) {
                    if (!isset($timelineStats['upcoming'])) {
                        $timelineStats['upcoming'] = 0;
                    }
                    $timelineStats['upcoming']++;
                } elseif ($endDate < $currentDate) {
                    if (!isset($timelineStats['past'])) {
                        $timelineStats['past'] = 0;
                    }
                    $timelineStats['past']++;
                } else {
                    if (!isset($timelineStats['current'])) {
                        $timelineStats['current'] = 0;
                    }
                    $timelineStats['current']++;
                }
            }
        }
        
        // Trier par nombre décroissant
        arsort($domainsStats);
        arsort($companyStats);
        
        // Afficher les cartes de statistiques
        echo '
        <h2>Statistiques générales</h2>
        <div class="stat-row">
            <div class="stat-card">
                <div class="value">' . $totalInternships . '</div>
                <div class="label">Stages au total</div>
                <div class="progress">
                    <div class="progress-bar" style="width: 100%;"></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="value">' . ($statusStats['available'] ?? 0) . '</div>
                <div class="label">Stages disponibles</div>
                <div class="progress">
                    <div class="progress-bar bg-success" style="width: ' . ($totalInternships > 0 ? (($statusStats['available'] ?? 0) / $totalInternships * 100) : 0) . '%;"></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="value">' . ($statusStats['assigned'] ?? 0) . '</div>
                <div class="label">Stages affectés</div>
                <div class="progress">
                    <div class="progress-bar bg-primary" style="width: ' . ($totalInternships > 0 ? (($statusStats['assigned'] ?? 0) / $totalInternships * 100) : 0) . '%;"></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="value">' . ($statusStats['completed'] ?? 0) . '</div>
                <div class="label">Stages terminés</div>
                <div class="progress">
                    <div class="progress-bar bg-info" style="width: ' . ($totalInternships > 0 ? (($statusStats['completed'] ?? 0) / $totalInternships * 100) : 0) . '%;"></div>
                </div>
            </div>
        </div>';
        
        // Afficher les graphiques
        echo '
        <div class="page-break"></div>
        <h2>Graphiques</h2>
        
        <!-- Tableau pour les graphiques de répartition -->
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 30px; border: 1px solid #ddd;">
            <thead>
                <tr>
                    <th style="padding: 10px; text-align: center; border: 1px solid #ddd; background-color: #f5f5f5;">Répartition par statut</th>
                    <th style="padding: 10px; text-align: center; border: 1px solid #ddd; background-color: #f5f5f5;">Répartition temporelle</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="width: 50%; padding: 15px; border: 1px solid #ddd; vertical-align: top;">
                        <div style="height: 250px; position: relative;">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </td>
                    <td style="width: 50%; padding: 15px; border: 1px solid #ddd; vertical-align: top;">
                        <div style="height: 250px; position: relative;">
                            <canvas id="timelineChart"></canvas>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <!-- Graphiques de domaines et entreprises -->
        <div class="row" style="margin-top: 30px;">
            <div style="width: 100%; margin-bottom: 30px;">
                <h3 style="margin-bottom: 15px; padding: 10px; background-color: #f5f5f5; border-radius: 5px;">Top domaines</h3>
                <div style="height: 350px; position: relative;">
                    <canvas id="domainChart"></canvas>
                </div>
            </div>
            
            <div style="width: 100%; margin-top: 20px; margin-bottom: 30px;">
                <h3 style="margin-bottom: 15px; padding: 10px; background-color: #f5f5f5; border-radius: 5px;">Top entreprises</h3>
                <div style="height: 350px; position: relative;">
                    <canvas id="companyChart"></canvas>
                </div>
            </div>
        </div>
        
        <div style="clear: both;"></div>';
        
        // Prepare the data first
        $timelineUpcoming = (int)($timelineStats['upcoming'] ?? 0);
        $timelineCurrent = (int)($timelineStats['current'] ?? 0);
        $timelinePast = (int)($timelineStats['past'] ?? 0);
        
        // Status labels for the chart
        $statusChartLabels = [];
        $statusChartData = [];
        foreach ($statusStats as $status => $count) {
            $statusChartLabels[] = ($statusLabels[$status] ?? ucfirst($status));
            $statusChartData[] = $count;
        }

        // Use the fixed chart script generator to avoid syntax issues
        require_once __DIR__ . '/tmp/export_chart_fix.php';
        echo generateInternshipChartScript($statusStats, $statusLabels, $timelineStats, $domainsStats, $companyStats);
        
        // Original script commented out
        /*
        echo '
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                // Graphique de statut
                new Chart(
                    document.getElementById("statusChart"),
                    {
                        type: "pie",
                        data: {
                            labels: ["' . implode('", "', $statusChartLabels) . '"],
                            datasets: [{
                                label: "Nombre de stages",
                                data: [' . implode(', ', $statusChartData) . '],
                                backgroundColor: [
                                    "rgba(40, 167, 69, 0.7)",
                                    "rgba(0, 123, 255, 0.7)",
                                    "rgba(23, 162, 184, 0.7)",
                                    "rgba(255, 193, 7, 0.7)",
                                    "rgba(108, 117, 125, 0.7)",
                                    "rgba(220, 53, 69, 0.7)"
                                ],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: "right",
                                    labels: {
                                        boxWidth: 10,
                                        font: {
                                            size: 9
                                        },
                                        padding: 3
                                    }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            const label = context.label || "";
                                            const value = context.raw || 0;
                                            const total = context.dataset.data.reduce((acc, val) => acc + val, 0);
                                            const percentage = Math.round((value / total) * 100);
                                            return `${label}: ${value} (${percentage}%)`;
                                        }
                                    }
                                }
                            }
                        }
                    }
                );
                
                // Graphique de timeline
                new Chart(
                    document.getElementById("timelineChart"),
                    {
                        type: "pie",
                        data: {
                            labels: ["À venir", "En cours", "Terminés"],
                            datasets: [{
                                label: "Nombre de stages",
                                data: [' . $timelineUpcoming . ', ' . $timelineCurrent . ', ' . $timelinePast . '],
                                backgroundColor: [
                                    "rgba(52, 152, 219, 0.7)",
                                    "rgba(46, 204, 113, 0.7)",
                                    "rgba(149, 165, 166, 0.7)"
                                ],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: "right",
                                    labels: {
                                        boxWidth: 10,
                                        font: {
                                            size: 9
                                        },
                                        padding: 3
                                    }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            const label = context.label || '';
                                            const value = context.raw || 0;
                                            const total = context.dataset.data.reduce((acc, val) => acc + val, 0);
                                            const percentage = Math.round((value / total) * 100);
                                            return `${label}: ${value} (${percentage}%)`;
                                        }
                                    }
                                }
                            }
                        }
                    }
                );
                
                // Graphique des domaines
                ';
                
                // Prepare domain data
                $domainLabels = [];
                $domainData = [];
                foreach (array_slice($domainsStats, 0, 10) as $domain => $count) {
                    $domainLabels[] = addslashes($domain);
                    $domainData[] = $count;
                }
                
                echo '
                new Chart(
                    document.getElementById("domainChart"),
                    {
                        type: "bar",
                        data: {
                            labels: ["' . implode('", "', $domainLabels) . '"],
                            datasets: [{
                                label: "Nombre de stages",
                                data: [' . implode(', ', $domainData) . '],
                                backgroundColor: "rgba(54, 162, 235, 0.7)",
                                borderColor: "rgb(54, 162, 235)",
                                borderWidth: 1
                            }]
                        },
                        options: {
                            indexAxis: "y",
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false,
                                },
                                title: {
                                    display: true,
                                    text: "Top domaines",
                                    padding: {
                                        top: 10,
                                        bottom: 10
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    beginAtZero: true,
                                    ticks: {
                                        precision: 0
                                    }
                                },
                                y: {
                                    ticks: {
                                        font: {
                                            size: 10
                                        }
                                    }
                                }
                            }
                        }
                    }
                );
                
                // Graphique des entreprises
                ';
                
                // Prepare company data
                $companyLabels = [];
                $companyData = [];
                foreach (array_slice($companyStats, 0, 10) as $company => $count) {
                    $companyLabels[] = addslashes($company);
                    $companyData[] = $count;
                }
                
                echo '
                new Chart(
                    document.getElementById("companyChart"),
                    {
                        type: "bar",
                        data: {
                            labels: ["' . implode('", "', $companyLabels) . '"],
                            datasets: [{
                                label: "Nombre de stages",
                                data: [' . implode(', ', $companyData) . '],
                                backgroundColor: "rgba(153, 102, 255, 0.7)",
                                borderColor: "rgb(153, 102, 255)",
                                borderWidth: 1
                            }]
                        },
                        options: {
                            indexAxis: "y",
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false,
                                },
                                title: {
                                    display: true,
                                    text: "Top entreprises",
                                    padding: {
                                        top: 10,
                                        bottom: 10
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    beginAtZero: true,
                                    ticks: {
                                        precision: 0
                                    }
                                },
                                y: {
                                    ticks: {
                                        font: {
                                            size: 10
                                        }
                                    }
                                }
                            }
                        }
                    }
                );
            });
        </script>';
        */
    }
    
    // Si ce n'est pas uniquement un rapport de graphiques, afficher le tableau
    if ($reportType !== 'charts') {
        echo '
        <div class="page-break"></div>
        <h2>Liste des stages</h2>
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
            <p>Document généré par le système TutorMatch. Total: ' . count($data) . ' stages.</p>
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