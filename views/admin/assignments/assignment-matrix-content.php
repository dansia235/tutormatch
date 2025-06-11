<?php
/**
 * Assignment Matrix Content Template
 * Uses components to display assignment matrix interface with Tailwind CSS
 */
?>

<div class="container mx-auto">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Matrice d'affectations</h1>
            <nav class="flex mt-1" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="/tutoring/views/admin/dashboard.php" class="text-sm text-gray-500 hover:text-gray-700">
                            Tableau de bord
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <a href="/tutoring/views/admin/assignments/index.php" class="ml-1 text-sm text-gray-500 hover:text-gray-700 md:ml-2">
                                Affectations
                            </a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-1 text-sm font-medium text-primary-600 md:ml-2">Matrice d'affectations</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>
        
        <div class="flex mt-4 md:mt-0 space-x-3">
            <a href="/tutoring/views/admin/assignments/generate.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                </svg>
                Génération automatique
            </a>
            <a href="/tutoring/views/admin/assignments/index.php" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="-ml-1 mr-2 h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Retour
            </a>
        </div>
    </div>
    
    <!-- Information Card -->
    <?php
    include_with_vars(__DIR__ . '/../../../components/cards/card.php', [
        'title' => 'À propos de la matrice d\'affectations',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                   </svg>',
        'content' => '
            <p class="mb-3">
                Cette matrice vous permet de visualiser et gérer les affectations étudiants-tuteurs. 
                Les couleurs indiquent le niveau de compatibilité calculé entre un étudiant et un tuteur.
            </p>
            <div class="grid grid-cols-1 md:grid-cols-5 gap-2 mb-3">
                <div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-success-100 text-success-800">
                        Excellente
                    </span>
                    <span class="text-xs text-gray-500 ml-1">80-100%</span>
                </div>
                <div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-success-50 text-success-800">
                        Bonne
                    </span>
                    <span class="text-xs text-gray-500 ml-1">60-79%</span>
                </div>
                <div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-warning-50 text-warning-800">
                        Moyenne
                    </span>
                    <span class="text-xs text-gray-500 ml-1">40-59%</span>
                </div>
                <div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-danger-50 text-danger-800">
                        Faible
                    </span>
                    <span class="text-xs text-gray-500 ml-1">20-39%</span>
                </div>
                <div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-danger-100 text-danger-800">
                        Très faible
                    </span>
                    <span class="text-xs text-gray-500 ml-1">0-19%</span>
                </div>
            </div>
            <p class="text-sm text-gray-500">
                Cliquez sur une cellule pour affecter un étudiant à un tuteur. Vous pouvez également 
                utiliser la génération automatique pour créer des affectations optimales.
            </p>
        '
    ]);
    ?>
    
    <!-- Statistics and Controls -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <!-- Statistics Card -->
        <div data-controller="assignment-algorithm" 
             data-assignment-algorithm-students-value='<?php echo json_encode($students); ?>' 
             data-assignment-algorithm-teachers-value='<?php echo json_encode($teachers); ?>' 
             data-assignment-algorithm-weights-value='<?php echo json_encode($weights); ?>'
             data-assignment-algorithm-algorithm-value="greedy"
             data-assignment-algorithm-preference-weight-value="0.7"
             data-assignment-algorithm-capacity-weight-value="0.3"
             class="statistics-container">
            <?php
            include_with_vars(__DIR__ . '/../../../components/cards/card.php', [
                'title' => 'Statistiques d\'affectation',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                           </svg>',
                'content' => '
                    <!-- Assigned Students -->
                    <div class="mb-4">
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm font-medium text-gray-700">Étudiants affectés</span>
                            <span class="text-sm font-semibold text-gray-900 assigned-count">'.count($assignments).'</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-success-500 h-2 rounded-full" 
                                 style="width: '.(count($students) > 0 ? (count($assignments) / count($students) * 100) : 0).'%"></div>
                        </div>
                    </div>
                    
                    <!-- Unassigned Students -->
                    <div class="mb-4">
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm font-medium text-gray-700">Étudiants sans affectation</span>
                            <span class="text-sm font-semibold text-gray-900 unassigned-count">'.(count($students) - count($assignments)).'</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-warning-500 h-2 rounded-full" 
                                 style="width: '.(count($students) > 0 ? ((count($students) - count($assignments)) / count($students) * 100) : 0).'%"></div>
                        </div>
                    </div>
                    
                    <!-- Assignment Rate -->
                    <div class="mb-4">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-700">Taux d\'affectation</span>
                            <span class="text-sm font-semibold text-gray-900 assignment-rate">
                                '.(count($students) > 0 ? round(count($assignments) / count($students) * 100) : 0).'%
                            </span>
                        </div>
                    </div>
                    
                    <!-- Average Compatibility Score -->
                    <div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-700">Score moyen de compatibilité</span>
                            <span class="text-sm font-semibold text-gray-900 average-score">
                                '.
                                (function() use ($assignments, $weights) {
                                    $totalScore = 0;
                                    $count = 0;
                                    foreach ($assignments as $studentId => $teacherId) {
                                        if (isset($weights[$studentId][$teacherId])) {
                                            $totalScore += $weights[$studentId][$teacherId];
                                            $count++;
                                        }
                                    }
                                    return $count > 0 ? number_format($totalScore / $count, 2) : 'N/A';
                                })()
                                .'
                            </span>
                        </div>
                    </div>
                '
            ]);
            ?>
        </div>
        
        <!-- Capacity Chart -->
        <div>
            <?php
            include_with_vars(__DIR__ . '/../../../components/cards/card.php', [
                'title' => 'Répartition des charges',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                           </svg>',
                'content' => '
                    <div data-controller="chart" data-chart-type-value="bar" data-chart-height-value="250">
                        <canvas 
                            id="teacherCapacityChart" 
                            data-assignment-algorithm-capacity-chart-target="capacityChart"
                            data-chart-target="canvas"
                            data-chart-data-value=\'
                            {
                                "labels": '.json_encode(array_map(function($teacher) { 
                                    return $teacher['name'] ?? $teacher['id']; 
                                }, $teachers)).',
                                "datasets": [
                                    {
                                        "label": "Étudiants affectés",
                                        "data": '.json_encode(array_map(function($teacher) use ($assignments) {
                                            return array_count_values($assignments)[$teacher['id']] ?? 0;
                                        }, $teachers)).',
                                        "backgroundColor": "rgba(54, 162, 235, 0.5)",
                                        "borderColor": "rgba(54, 162, 235, 1)",
                                        "borderWidth": 1
                                    },
                                    {
                                        "label": "Capacité maximale",
                                        "data": '.json_encode(array_map(function($teacher) {
                                            return $teacher['max_students'] ?? 0;
                                        }, $teachers)).',
                                        "type": "line",
                                        "backgroundColor": "rgba(255, 99, 132, 0.2)",
                                        "borderColor": "rgba(255, 99, 132, 1)",
                                        "borderWidth": 2,
                                        "fill": false,
                                        "pointStyle": "rectRot",
                                        "pointRadius": 5,
                                        "pointBorderColor": "rgba(255, 99, 132, 1)"
                                    }
                                ]
                            }
                            \'
                            data-chart-options-value=\'
                            {
                                "scales": {
                                    "y": {
                                        "beginAtZero": true,
                                        "title": {
                                            "display": true,
                                            "text": "Nombre d\'étudiants"
                                        },
                                        "ticks": {
                                            "stepSize": 1
                                        }
                                    },
                                    "x": {
                                        "title": {
                                            "display": true,
                                            "text": "Tuteurs"
                                        }
                                    }
                                },
                                "plugins": {
                                    "title": {
                                        "display": true,
                                        "text": "Charge des tuteurs",
                                        "font": {
                                            "size": 14
                                        }
                                    }
                                }
                            }
                            \'
                        ></canvas>
                    </div>
                '
            ]);
            ?>
        </div>
        
        <!-- Actions Card -->
        <div>
            <?php
            include_with_vars(__DIR__ . '/../../../components/cards/card.php', [
                'title' => 'Actions',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                           </svg>',
                'content' => '
                    <div class="space-y-4">
                        <button id="run-algorithm" type="button" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="-ml-1 mr-2 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M13.105 4.142l.9.9-7.398 7.398-.9-.9 7.398-7.398zm7.859 11.45c-.084 1.563-1.313 2.816-2.875 2.908a58.676 58.676 0 01-15.674 0c-1.563-.092-2.793-1.345-2.876-2.908a58.676 58.676 0 010-11.186c.083-1.563 1.313-2.816 2.876-2.908a58.676 58.676 0 0115.674 0c1.562.092 2.791 1.345 2.875 2.908a58.676 58.676 0 010 11.186zM17.98 7.11l-6.75 6.747-2.546-2.546a.75.75 0 00-1.06 1.06l3.04 3.044a.76.76 0 001.096 0l7.246-7.244a.75.75 0 00-1.06-1.06l.034-.001z" />
                            </svg>
                            Exécuter l\'algorithme
                        </button>
                        
                        <div class="pt-3 border-t border-gray-200">
                            <label for="algorithm-type" class="block text-sm font-medium text-gray-700 mb-1">
                                Algorithme d\'affectation
                            </label>
                            <select 
                                id="algorithm-type"
                                class="block w-full shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm border-gray-300 rounded-md"
                            >
                                <option value="greedy" selected>Algorithme glouton</option>
                                <option value="hungarian">Algorithme hongrois</option>
                                <option value="genetic">Algorithme génétique</option>
                                <option value="hybrid">Algorithme hybride</option>
                            </select>
                        </div>
                        
                        <div class="pt-3 border-t border-gray-200 space-y-4">
                            <button id="save-assignments" type="button" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-success-600 hover:bg-success-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-success-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="-ml-1 mr-2 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M7.707 10.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V6h5a2 2 0 012 2v7a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2h5v5.586l-1.293-1.293zM9 4a1 1 0 012 0v2H9V4z" />
                                </svg>
                                Enregistrer les affectations
                            </button>
                            
                            <button id="reset-assignments" type="button" class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-danger-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="-ml-1 mr-2 h-5 w-5 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                                Réinitialiser
                            </button>
                        </div>
                    </div>
                '
            ]);
            ?>
        </div>
    </div>
    
    <!-- Assignment Matrix -->
    <?php
    include_with_vars(__DIR__ . '/../../../components/cards/card.php', [
        'title' => 'Matrice d\'affectations',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                   </svg>',
        'noPadding' => true,
        'content' => '
            <div class="overflow-auto" data-controller="assignment-algorithm" 
                data-assignment-algorithm-students-value=\''.json_encode($students).'\' 
                data-assignment-algorithm-teachers-value=\''.json_encode($teachers).'\' 
                data-assignment-algorithm-weights-value=\''.json_encode($weights).'\' 
                data-assignment-algorithm-matrix-target="matrix" 
                data-assignment-algorithm-stats-target=".statistics-container" 
                data-assignment-algorithm-capacity-chart-target="#teacherCapacityChart">
                
                '.include_with_vars(__DIR__ . '/../../../components/charts/assignment-matrix.php', [
                    'students' => $students,
                    'teachers' => $teachers,
                    'assignments' => $assignments,
                    'weights' => $weights,
                    'editable' => true,
                    'updateUrl' => '/tutoring/api/assignments/update.php',
                    'class' => 'max-h-[calc(100vh-300px)]'
                ]).'
            </div>
        '
    ]);
    ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Additional JavaScript if needed
});
</script>