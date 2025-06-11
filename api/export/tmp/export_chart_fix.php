<?php
/**
 * Fixed version of PDF report chart generation for internships
 */

// Function to generate chart script with proper formatting
function generateInternshipChartScript($statusStats, $statusLabels, $timelineStats, $domainsStats, $companyStats) {
    // Prepare all data for charts
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

    // Prepare domain data
    $domainLabels = [];
    $domainData = [];
    foreach (array_slice($domainsStats, 0, 10) as $domain => $count) {
        $domainLabels[] = addslashes($domain);
        $domainData[] = $count;
    }
    
    // Prepare company data
    $companyLabels = [];
    $companyData = [];
    foreach (array_slice($companyStats, 0, 10) as $company => $count) {
        $companyLabels[] = addslashes($company);
        $companyData[] = $count;
    }

    // Build the script
    $script = <<<SCRIPT
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Status chart
        new Chart(
            document.getElementById("statusChart"),
            {
                type: "pie",
                data: {
                    labels: ["STATUSLABELS"],
                    datasets: [{
                        label: "Nombre de stages",
                        data: [STATUSDATA],
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
                                    return `\${label}: \${value} (\${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            }
        );
        
        // Timeline chart
        new Chart(
            document.getElementById("timelineChart"),
            {
                type: "pie",
                data: {
                    labels: ["À venir", "En cours", "Terminés"],
                    datasets: [{
                        label: "Nombre de stages",
                        data: [TIMELINEDATA],
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
                                    const label = context.label || "";
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((acc, val) => acc + val, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `\${label}: \${value} (\${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            }
        );
        
        // Domain chart
        new Chart(
            document.getElementById("domainChart"),
            {
                type: "bar",
                data: {
                    labels: ["DOMAINLABELS"],
                    datasets: [{
                        label: "Nombre de stages",
                        data: [DOMAINDATA],
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
        
        // Company chart
        new Chart(
            document.getElementById("companyChart"),
            {
                type: "bar",
                data: {
                    labels: ["COMPANYLABELS"],
                    datasets: [{
                        label: "Nombre de stages",
                        data: [COMPANYDATA],
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
</script>
SCRIPT;

    // Replace placeholders with actual data
    $script = str_replace('STATUSLABELS', implode('", "', $statusChartLabels), $script);
    $script = str_replace('STATUSDATA', implode(', ', $statusChartData), $script);
    $script = str_replace('TIMELINEDATA', "$timelineUpcoming, $timelineCurrent, $timelinePast", $script);
    $script = str_replace('DOMAINLABELS', implode('", "', $domainLabels), $script);
    $script = str_replace('DOMAINDATA', implode(', ', $domainData), $script);
    $script = str_replace('COMPANYLABELS', implode('", "', $companyLabels), $script);
    $script = str_replace('COMPANYDATA', implode(', ', $companyData), $script);
    
    return $script;
}
?>