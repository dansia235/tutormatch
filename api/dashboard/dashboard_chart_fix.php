<?php
/**
 * Fixed helper for charts rendering in the dashboard
 * This file helps avoid syntax errors in chart generation
 */

/**
 * Generates a safe Chart.js initialization script for dashboard charts
 * Uses a template-based approach to avoid string concatenation issues
 * 
 * @return string The JavaScript code for chart initialization
 */
function generateDashboardChartScript() {
    // Nous allons éviter l'utilisation de HEREDOC avec des template strings JS pour éviter les avertissements de dépréciation
    // En utilisant des chaînes standard et des concaténations
    $chartScript = '<script>' . "\n";
    $chartScript .= 'document.addEventListener(\'DOMContentLoaded\', function() {' . "\n";
    $chartScript .= '    // Récupérer tous les canvas de graphiques' . "\n";
    $chartScript .= '    const chartCanvases = document.querySelectorAll(\'.dashboard-chart\');' . "\n";
    $chartScript .= '    ' . "\n";
    $chartScript .= '    // Pour chaque canvas, charger les données' . "\n";
    $chartScript .= '    chartCanvases.forEach(canvas => {' . "\n";
    $chartScript .= '        const url = canvas.dataset.url;' . "\n";
    $chartScript .= '        ' . "\n";
    $chartScript .= '        // Créer un indicateur de chargement' . "\n";
    $chartScript .= '        const container = canvas.closest(\'.chart-container\');' . "\n";
    $chartScript .= '        const loadingDiv = document.createElement(\'div\');' . "\n";
    $chartScript .= '        loadingDiv.className = \'chart-loading\';' . "\n";
    $chartScript .= '        loadingDiv.innerHTML = \'<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Chargement...</span></div>\';' . "\n";
    $chartScript .= '        container.appendChild(loadingDiv);' . "\n";
    $chartScript .= '        ' . "\n";
    $chartScript .= '        // Charger les données' . "\n";
    $chartScript .= '        fetch(url)' . "\n";
    $chartScript .= '            .then(response => response.json())' . "\n";
    $chartScript .= '            .then(data => {' . "\n";
    $chartScript .= '                // Créer le graphique' . "\n";
    $chartScript .= '                new Chart(canvas, {' . "\n";
    $chartScript .= '                    type: data.type,' . "\n";
    $chartScript .= '                    data: data.data,' . "\n";
    $chartScript .= '                    options: {' . "\n";
    $chartScript .= '                        responsive: true,' . "\n";
    $chartScript .= '                        maintainAspectRatio: false,' . "\n";
    $chartScript .= '                        plugins: {' . "\n";
    $chartScript .= '                            legend: {' . "\n";
    $chartScript .= '                                position: \'bottom\',' . "\n";
    $chartScript .= '                                labels: {' . "\n";
    $chartScript .= '                                    boxWidth: 12,' . "\n";
    $chartScript .= '                                    font: {' . "\n";
    $chartScript .= '                                        size: 10' . "\n";
    $chartScript .= '                                    },' . "\n";
    $chartScript .= '                                    padding: 5' . "\n";
    $chartScript .= '                                }' . "\n";
    $chartScript .= '                            },' . "\n";
    $chartScript .= '                            tooltip: {' . "\n";
    $chartScript .= '                                displayColors: true,' . "\n";
    $chartScript .= '                                callbacks: {' . "\n";
    $chartScript .= '                                    label: function(context) {' . "\n";
    $chartScript .= '                                        const label = context.label || \'\';' . "\n";
    $chartScript .= '                                        const value = context.raw || 0;' . "\n";
    $chartScript .= '                                        if (data.type === \'pie\' || data.type === \'doughnut\') {' . "\n";
    $chartScript .= '                                            const total = context.dataset.data.reduce((acc, val) => acc + val, 0);' . "\n";
    $chartScript .= '                                            const percentage = Math.round((value / total) * 100);' . "\n";
    $chartScript .= '                                            return label + ": " + value + " (" + percentage + "%)";' . "\n";
    $chartScript .= '                                        }' . "\n";
    $chartScript .= '                                        return label + ": " + value;' . "\n";
    $chartScript .= '                                    }' . "\n";
    $chartScript .= '                                }' . "\n";
    $chartScript .= '                            }' . "\n";
    $chartScript .= '                        },' . "\n";
    $chartScript .= '                        ...data.options' . "\n";
    $chartScript .= '                    }' . "\n";
    $chartScript .= '                });' . "\n";
    $chartScript .= '                ' . "\n";
    $chartScript .= '                // Supprimer l\'indicateur de chargement' . "\n";
    $chartScript .= '                container.removeChild(loadingDiv);' . "\n";
    $chartScript .= '            })' . "\n";
    $chartScript .= '            .catch(error => {' . "\n";
    $chartScript .= '                console.error(\'Erreur lors du chargement des données:\', error);' . "\n";
    $chartScript .= '                container.removeChild(loadingDiv);' . "\n";
    $chartScript .= '                container.innerHTML = \'<div class="alert alert-danger">Erreur lors du chargement des données</div>\';' . "\n";
    $chartScript .= '            });' . "\n";
    $chartScript .= '    });' . "\n";
    $chartScript .= '});' . "\n";
    $chartScript .= '' . "\n";
    $chartScript .= '// Fonction pour charger les métriques système' . "\n";
    $chartScript .= 'function loadSystemMetrics() {' . "\n";
    $chartScript .= '    const metricsList = document.getElementById(\'system-metrics-list\');' . "\n";
    $chartScript .= '    ' . "\n";
    $chartScript .= '    // Charger les données' . "\n";
    $chartScript .= '    fetch(\'/tutoring/api/dashboard/system-metrics.php\')' . "\n";
    $chartScript .= '        .then(response => response.json())' . "\n";
    $chartScript .= '        .then(data => {' . "\n";
    $chartScript .= '            // Vider la liste' . "\n";
    $chartScript .= '            metricsList.innerHTML = \'\';' . "\n";
    $chartScript .= '            ' . "\n";
    $chartScript .= '            // Ajouter les métriques' . "\n";
    $chartScript .= '            const metrics = data.data.metrics;' . "\n";
    $chartScript .= '            ' . "\n";
    $chartScript .= '            // Configuration des métriques à afficher' . "\n";
    $chartScript .= '            const metricsConfig = [' . "\n";
    $chartScript .= '                { key: \'students_without_tutor\', label: \'Étudiants sans tuteur\', badgeClass: \'bg-warning\' },' . "\n";
    $chartScript .= '                { key: \'internships_available\', label: \'Stages sans étudiant\', badgeClass: \'bg-info\' },' . "\n";
    $chartScript .= '                { key: \'pending_documents\', label: \'Documents en attente\', badgeClass: \'bg-danger\' },' . "\n";
    $chartScript .= '                { key: \'pending_evaluations\', label: \'Évaluations à valider\', badgeClass: \'bg-success\' },' . "\n";
    $chartScript .= '                { key: \'active_users_today\', label: \'Utilisateurs actifs aujourd\\\'hui\', badgeClass: \'bg-primary\' }' . "\n";
    $chartScript .= '            ];' . "\n";
    $chartScript .= '            ' . "\n";
    $chartScript .= '            // Créer les éléments de liste' . "\n";
    $chartScript .= '            metricsConfig.forEach(config => {' . "\n";
    $chartScript .= '                if (metrics[config.key] !== undefined) {' . "\n";
    $chartScript .= '                    const listItem = document.createElement(\'li\');' . "\n";
    $chartScript .= '                    listItem.className = \'list-group-item d-flex justify-content-between align-items-center\';' . "\n";
    $chartScript .= '                    ' . "\n";
    $chartScript .= '                    // Ajouter le label' . "\n";
    $chartScript .= '                    listItem.appendChild(document.createTextNode(config.label));' . "\n";
    $chartScript .= '                    ' . "\n";
    $chartScript .= '                    // Ajouter le badge' . "\n";
    $chartScript .= '                    const badge = document.createElement(\'span\');' . "\n";
    $chartScript .= '                    badge.className = "badge " + config.badgeClass + " rounded-pill";' . "\n";
    $chartScript .= '                    badge.textContent = metrics[config.key];' . "\n";
    $chartScript .= '                    listItem.appendChild(badge);' . "\n";
    $chartScript .= '                    ' . "\n";
    $chartScript .= '                    // Ajouter l\'élément à la liste' . "\n";
    $chartScript .= '                    metricsList.appendChild(listItem);' . "\n";
    $chartScript .= '                }' . "\n";
    $chartScript .= '            });' . "\n";
    $chartScript .= '            ' . "\n";
    $chartScript .= '            // Ajouter la date de mise à jour' . "\n";
    $chartScript .= '            const updateItem = document.createElement(\'li\');' . "\n";
    $chartScript .= '            updateItem.className = \'list-group-item text-muted small\';' . "\n";
    $chartScript .= '            updateItem.textContent = \'Dernière mise à jour: \' + data.data.updated_at;' . "\n";
    $chartScript .= '            metricsList.appendChild(updateItem);' . "\n";
    $chartScript .= '        })' . "\n";
    $chartScript .= '        .catch(error => {' . "\n";
    $chartScript .= '            console.error(\'Erreur lors du chargement des métriques:\', error);' . "\n";
    $chartScript .= '            metricsList.innerHTML = \'<li class="list-group-item text-danger">Erreur lors du chargement des métriques</li>\';' . "\n";
    $chartScript .= '        });' . "\n";
    $chartScript .= '}' . "\n";
    $chartScript .= '' . "\n";
    $chartScript .= '// Charger les métriques au chargement de la page' . "\n";
    $chartScript .= 'loadSystemMetrics();' . "\n";
    $chartScript .= '' . "\n";
    $chartScript .= '// Rafraîchir les métriques toutes les 5 minutes' . "\n";
    $chartScript .= 'setInterval(loadSystemMetrics, 5 * 60 * 1000);' . "\n";
    $chartScript .= '</script>';
    
    return $chartScript;
}
?>