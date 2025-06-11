<?php
/**
 * Outil de visualisation des résultats de benchmark
 */

/**
 * Génère un graphique ASCII à partir des données de benchmark
 * 
 * @param array $data Les données à afficher (clé => valeur)
 * @param string $title Titre du graphique
 * @param string $xLabel Label de l'axe X
 * @param string $yLabel Label de l'axe Y
 * @param int $width Largeur du graphique
 * @param int $height Hauteur du graphique
 */
function generateAsciiGraph(array $data, string $title, string $xLabel, string $yLabel, int $width = 60, int $height = 15)
{
    // Vérifier qu'il y a des données
    if (empty($data)) {
        echo "Aucune donnée à afficher.\n";
        return;
    }
    
    // Trouver la valeur maximale
    $maxValue = max(array_values($data));
    
    // Calculer l'échelle
    $scale = $maxValue > 0 ? $height / $maxValue : 0;
    
    // Afficher le titre
    echo str_repeat(' ', ($width - strlen($title)) / 2) . $title . "\n";
    echo str_repeat('-', $width) . "\n";
    
    // Afficher l'axe Y et le graphique
    for ($i = $height; $i > 0; $i--) {
        $yValue = ($i / $height) * $maxValue;
        $yLabel = sprintf("%-8.2f |", $yValue);
        echo $yLabel;
        
        foreach ($data as $key => $value) {
            $barHeight = $value * $scale;
            echo ($barHeight >= $i) ? '#' : ' ';
        }
        
        echo "\n";
    }
    
    // Afficher l'axe X
    echo str_repeat(' ', 9) . '+' . str_repeat('-', count($data)) . "\n";
    
    // Afficher les labels de l'axe X
    echo str_repeat(' ', 10);
    $xLabels = array_keys($data);
    $maxLabelLength = max(array_map('strlen', $xLabels));
    
    // Afficher les labels verticalement si nécessaire
    for ($i = 0; $i < $maxLabelLength; $i++) {
        echo str_repeat(' ', 10);
        foreach ($xLabels as $label) {
            echo isset($label[$i]) ? $label[$i] : ' ';
        }
        echo "\n";
    }
    
    // Afficher le label de l'axe Y
    echo str_repeat(' ', ($width - strlen($yLabel)) / 2) . $yLabel . "\n";
    
    // Afficher le label de l'axe X
    echo str_repeat(' ', ($width - strlen($xLabel)) / 2) . $xLabel . "\n";
}

/**
 * Génère un graphique ASCII pour comparer deux séries de données
 * 
 * @param array $data1 Première série (clé => valeur)
 * @param array $data2 Deuxième série (clé => valeur)
 * @param string $title Titre du graphique
 * @param string $legend1 Légende pour la première série
 * @param string $legend2 Légende pour la deuxième série
 * @param string $xLabel Label de l'axe X
 * @param string $yLabel Label de l'axe Y
 * @param int $width Largeur du graphique
 * @param int $height Hauteur du graphique
 */
function generateComparisonGraph(array $data1, array $data2, string $title, string $legend1, string $legend2, string $xLabel, string $yLabel, int $width = 60, int $height = 15)
{
    // Vérifier qu'il y a des données
    if (empty($data1) || empty($data2)) {
        echo "Données insuffisantes pour la comparaison.\n";
        return;
    }
    
    // S'assurer que les deux séries ont les mêmes clés
    $keys = array_unique(array_merge(array_keys($data1), array_keys($data2)));
    foreach ($keys as $key) {
        if (!isset($data1[$key])) $data1[$key] = 0;
        if (!isset($data2[$key])) $data2[$key] = 0;
    }
    
    // Trouver la valeur maximale
    $maxValue = max(max(array_values($data1)), max(array_values($data2)));
    
    // Calculer l'échelle
    $scale = $maxValue > 0 ? $height / $maxValue : 0;
    
    // Afficher le titre
    echo str_repeat(' ', ($width - strlen($title)) / 2) . $title . "\n";
    echo str_repeat('-', $width) . "\n";
    
    // Afficher la légende
    echo $legend1 . ": #  " . $legend2 . ": *\n\n";
    
    // Afficher l'axe Y et le graphique
    for ($i = $height; $i > 0; $i--) {
        $yValue = ($i / $height) * $maxValue;
        $yLabel = sprintf("%-8.2f |", $yValue);
        echo $yLabel;
        
        foreach ($keys as $key) {
            $barHeight1 = $data1[$key] * $scale;
            $barHeight2 = $data2[$key] * $scale;
            
            if ($barHeight1 >= $i && $barHeight2 >= $i) {
                echo "@"; // Les deux séries atteignent cette hauteur
            } elseif ($barHeight1 >= $i) {
                echo "#"; // Série 1 seulement
            } elseif ($barHeight2 >= $i) {
                echo "*"; // Série 2 seulement
            } else {
                echo " "; // Aucune série n'atteint cette hauteur
            }
        }
        
        echo "\n";
    }
    
    // Afficher l'axe X
    echo str_repeat(' ', 9) . '+' . str_repeat('-', count($keys)) . "\n";
    
    // Afficher les labels de l'axe X
    echo str_repeat(' ', 10);
    $maxLabelLength = max(array_map('strlen', $keys));
    
    // Afficher les labels verticalement si nécessaire
    for ($i = 0; $i < $maxLabelLength; $i++) {
        echo str_repeat(' ', 10);
        foreach ($keys as $label) {
            echo isset($label[$i]) ? $label[$i] : ' ';
        }
        echo "\n";
    }
    
    // Afficher le label de l'axe Y
    echo str_repeat(' ', ($width - strlen($yLabel)) / 2) . $yLabel . "\n";
    
    // Afficher le label de l'axe X
    echo str_repeat(' ', ($width - strlen($xLabel)) / 2) . $xLabel . "\n";
}

/**
 * Génère un rapport HTML avec des graphiques pour les résultats de benchmark
 * 
 * @param array $timeResults Données de temps d'exécution
 * @param array $memoryResults Données d'utilisation mémoire
 * @param array $scoreResults Données de scores
 * @param string $outputFile Chemin du fichier de sortie
 */
function generateHtmlReport(array $timeResults, array $memoryResults, array $scoreResults, string $outputFile)
{
    $html = '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport de Benchmark - Algorithme Glouton</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1, h2 { color: #333; }
        .chart-container { margin: 20px 0; border: 1px solid #ddd; padding: 15px; border-radius: 5px; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .summary { background-color: #f0f8ff; padding: 15px; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <h1>Rapport de Benchmark - Algorithme Glouton</h1>
    
    <div class="summary">
        <h2>Résumé</h2>
        <p>Date d\'exécution: ' . date('Y-m-d H:i:s') . '</p>
        <p>Ce rapport présente les résultats des tests de performance de l\'algorithme glouton d\'affectation étudiant-enseignant.</p>
    </div>
    
    <h2>Temps d\'exécution</h2>
    <div class="chart-container">
        <canvas id="timeChart" width="800" height="400"></canvas>
    </div>
    
    <h2>Utilisation mémoire</h2>
    <div class="chart-container">
        <canvas id="memoryChart" width="800" height="400"></canvas>
    </div>
    
    <h2>Scores moyens</h2>
    <div class="chart-container">
        <canvas id="scoreChart" width="800" height="400"></canvas>
    </div>
    
    <h2>Données brutes</h2>
    <table>
        <tr>
            <th>Configuration</th>
            <th>Temps (s)</th>
            <th>Mémoire (MB)</th>
            <th>Score moyen</th>
        </tr>';
    
    foreach ($timeResults as $config => $time) {
        $memory = isset($memoryResults[$config]) ? $memoryResults[$config] : 0;
        $score = isset($scoreResults[$config]) ? $scoreResults[$config] : 0;
        
        $html .= '<tr>
            <td>' . $config . '</td>
            <td>' . number_format($time, 4) . '</td>
            <td>' . number_format($memory, 2) . '</td>
            <td>' . number_format($score, 2) . '</td>
        </tr>';
    }
    
    $html .= '</table>
    
    <h2>Analyse</h2>
    <div class="summary">
        <h3>Complexité</h3>
        <p>L\'algorithme glouton présente une complexité temporelle de O(n² log n) où n est le nombre d\'étudiants.</p>
        <p>La complexité spatiale est de O(n*m) où n est le nombre d\'étudiants et m le nombre d\'enseignants.</p>
        
        <h3>Recommandations d\'optimisation</h3>
        <ol>
            <li>Implémentation de la mise en cache pour les calculs de compatibilité</li>
            <li>Parallélisation des calculs pour les grands ensembles de données</li>
            <li>Optimisation de la structure de données pour les grandes quantités de données</li>
            <li>Utilisation d\'une stratégie de filtrage pour réduire le nombre de combinaisons évaluées</li>
            <li>Implémentation d\'un algorithme de correspondance plus efficace pour les très grands ensembles</li>
        </ol>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Données pour les graphiques
        const timeData = ' . json_encode($timeResults) . ';
        const memoryData = ' . json_encode($memoryResults) . ';
        const scoreData = ' . json_encode($scoreResults) . ';
        
        // Création des graphiques
        const ctx1 = document.getElementById("timeChart").getContext("2d");
        new Chart(ctx1, {
            type: "bar",
            data: {
                labels: Object.keys(timeData),
                datasets: [{
                    label: "Temps d\'exécution (secondes)",
                    data: Object.values(timeData),
                    backgroundColor: "rgba(54, 162, 235, 0.5)",
                    borderColor: "rgba(54, 162, 235, 1)",
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: "Secondes"
                        }
                    }
                }
            }
        });
        
        const ctx2 = document.getElementById("memoryChart").getContext("2d");
        new Chart(ctx2, {
            type: "bar",
            data: {
                labels: Object.keys(memoryData),
                datasets: [{
                    label: "Utilisation mémoire (MB)",
                    data: Object.values(memoryData),
                    backgroundColor: "rgba(255, 99, 132, 0.5)",
                    borderColor: "rgba(255, 99, 132, 1)",
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: "Mégaoctets (MB)"
                        }
                    }
                }
            }
        });
        
        const ctx3 = document.getElementById("scoreChart").getContext("2d");
        new Chart(ctx3, {
            type: "bar",
            data: {
                labels: Object.keys(scoreData),
                datasets: [{
                    label: "Score moyen (/100)",
                    data: Object.values(scoreData),
                    backgroundColor: "rgba(75, 192, 192, 0.5)",
                    borderColor: "rgba(75, 192, 192, 1)",
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: "Score"
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>';
    
    // Écrire le fichier HTML
    file_put_contents($outputFile, $html);
    echo "Rapport HTML généré: $outputFile\n";
}