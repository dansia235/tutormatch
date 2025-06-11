<?php
/**
 * API pour exporter la liste des affectations
 * Endpoint: /api/export/assignments.php
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

// Vérifier si on exporte toutes les affectations ou seulement celles filtrées
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
$assignmentController = new AssignmentController($db);

// Récupérer les données
if ($exportAll) {
    $assignments = $assignmentController->getAll();
} else {
    // Récupérer les mêmes filtres que ceux appliqués dans la vue
    $term = isset($_GET['term']) ? $_GET['term'] : '';
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    
    $assignments = $assignmentController->searchAssignments($term, $status);
}

// Définir toutes les colonnes disponibles
$allColumns = [
    'id' => 'ID',
    'student_first_name' => 'Prénom étudiant',
    'student_last_name' => 'Nom étudiant',
    'student_number' => 'Numéro étudiant',
    'teacher_first_name' => 'Prénom tuteur',
    'teacher_last_name' => 'Nom tuteur',
    'internship_title' => 'Titre du stage',
    'company_name' => 'Entreprise',
    'assignment_date' => 'Date d\'affectation',
    'confirmation_date' => 'Date de confirmation',
    'status' => 'Statut',
    'compatibility_score' => 'Score de compatibilité',
    'satisfaction_score' => 'Score de satisfaction',
    'notes' => 'Notes'
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
foreach ($assignments as $assignment) {
    $row = [];
    foreach ($columns as $column) {
        if (isset($assignment[$column])) {
            // Traduction des valeurs pour certains champs
            if ($column === 'status') {
                $statusMap = [
                    'pending' => 'En attente',
                    'confirmed' => 'Confirmée',
                    'rejected' => 'Rejetée',
                    'completed' => 'Terminée'
                ];
                $row[] = $statusMap[$assignment[$column]] ?? $assignment[$column];
            } elseif ($column === 'assignment_date' || $column === 'confirmation_date') {
                $row[] = !empty($assignment[$column]) ? date('d/m/Y H:i', strtotime($assignment[$column])) : '';
            } elseif ($column === 'compatibility_score' || $column === 'satisfaction_score') {
                $row[] = !empty($assignment[$column]) ? number_format((float)$assignment[$column], 1) : '';
            } else {
                $row[] = $assignment[$column];
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
        exportToCsv($exportData, 'affectations');
        break;
        
    case 'excel':
        exportToExcel($exportData, 'affectations');
        break;
        
    case 'pdf':
        exportToPdf($exportData, 'affectations', $header);
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
        <h1>Liste des affectations</h1>
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
            <p>Document généré par le système TutorMatch. Total: ' . count($data) . ' affectations.</p>
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