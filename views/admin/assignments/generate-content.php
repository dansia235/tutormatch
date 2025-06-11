<?php
/**
 * Assignment Generation Content Template
 * Uses components to display assignment generation interface
 */
?>

<div class="container mx-auto">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Génération automatique d'affectations</h1>
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
                            <a href="/tutoring/views/admin/assignments.php" class="ml-1 text-sm text-gray-500 hover:text-gray-700 md:ml-2">
                                Affectations
                            </a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-1 text-sm font-medium text-primary-600 md:ml-2">Génération automatique</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>
        
        <div class="mt-4 md:mt-0">
            <a href="/tutoring/views/admin/assignments.php" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="-ml-1 mr-2 h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Retour
            </a>
        </div>
    </div>
    
    <!-- Display form errors if any -->
    <?php if (!empty($formErrors)): ?>
    <div class="bg-danger-50 border border-danger-200 text-danger-800 px-4 py-3 rounded relative mb-6" role="alert">
        <div class="flex">
            <div class="py-1">
                <svg class="h-6 w-6 text-danger-800 mr-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
            </div>
            <div>
                <p class="font-bold">Des erreurs sont présentes dans le formulaire</p>
                <ul class="mt-2 list-disc list-inside text-sm">
                    <?php foreach ($formErrors as $error): ?>
                    <li><?php echo h($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <?php
        // Unassigned Students Stat Card
        include_with_vars(__DIR__ . '/../../../components/cards/stat-card.php', [
            'title' => 'Étudiants sans affectation',
            'value' => count($unassignedStudents),
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                      </svg>',
            'change' => $totalStudents > 0 ? 'sur ' . $totalStudents . ' étudiants actifs' : '',
            'changeType' => 'neutral'
        ]);
        
        // Teacher Capacity Stat Card
        include_with_vars(__DIR__ . '/../../../components/cards/stat-card.php', [
            'title' => 'Capacité des tuteurs',
            'value' => $teacherCapacity,
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                      </svg>',
            'change' => $teacherCapacity < count($unassignedStudents) ? 'Capacité insuffisante' : 'Capacité suffisante',
            'changeType' => $teacherCapacity < count($unassignedStudents) ? 'negative' : 'positive',
        ]);
        
        // Available Internships Stat Card
        include_with_vars(__DIR__ . '/../../../components/cards/stat-card.php', [
            'title' => 'Stages disponibles',
            'value' => count($availableInternships),
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                      </svg>',
            'change' => count($availableInternships) < count($unassignedStudents) ? 'Stages insuffisants' : 'Stages suffisants',
            'changeType' => count($availableInternships) < count($unassignedStudents) ? 'negative' : 'positive',
        ]);
        
        // Existing Assignments Stat Card
        include_with_vars(__DIR__ . '/../../../components/cards/stat-card.php', [
            'title' => 'Affectations existantes',
            'value' => $totalAssignments,
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                      </svg>',
            'change' => $totalStudents > 0 ? round(($totalAssignments / $totalStudents) * 100) . '% des étudiants affectés' : '',
            'changeType' => 'neutral'
        ]);
        ?>
    </div>
    
    <?php if (count($unassignedStudents) === 0): ?>
    <!-- No students to assign -->
    <div class="bg-info-50 border-l-4 border-info-400 p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-info-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2h2a1 1 0 100-2H9z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm text-info-800">
                    <span class="font-bold">Information :</span> Tous les étudiants actifs ont déjà été affectés. Aucune nouvelle affectation n'est nécessaire.
                </p>
            </div>
        </div>
    </div>
    <?php elseif (count($availableInternships) === 0): ?>
    <!-- No internships available -->
    <div class="bg-warning-50 border-l-4 border-warning-400 p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-warning-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm text-warning-800">
                    <span class="font-bold">Attention :</span> Aucun stage disponible. Veuillez ajouter des stages ou libérer des stages existants avant de générer des affectations.
                </p>
            </div>
        </div>
    </div>
    <?php elseif (count($availableTeachers) === 0 || $teacherCapacity === 0): ?>
    <!-- No teachers available -->
    <div class="bg-warning-50 border-l-4 border-warning-400 p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-warning-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm text-warning-800">
                    <span class="font-bold">Attention :</span> Aucun tuteur n'a de capacité disponible. Veuillez ajuster la capacité des tuteurs existants ou ajouter de nouveaux tuteurs.
                </p>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- Assignment Generation Form -->
    <div class="card shadow-sm">
        <div class="card-header border-b border-gray-200 px-5 py-4">
            <h2 class="font-semibold text-lg text-gray-800">Paramètres de génération</h2>
        </div>
        <div class="card-body p-5">
            <form action="/tutoring/views/admin/assignments/generate-process.php" method="POST" class="space-y-6" data-controller="generation-form">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                
                <!-- Basic Parameters -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                            Nom de l'exécution
                        </label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="<?php echo h($formData['name'] ?? 'Exécution du ' . date('Y-m-d H:i')); ?>"
                            class="block w-full shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm border-gray-300 rounded-md"
                        >
                        <p class="mt-1 text-sm text-gray-500">Un nom descriptif pour cette exécution d'algorithme (optionnel).</p>
                    </div>
                    
                    <div>
                        <label for="algorithm_type" class="block text-sm font-medium text-gray-700 mb-1">
                            Algorithme <span class="text-danger-500">*</span>
                        </label>
                        <select 
                            id="algorithm_type"
                            name="algorithm_type"
                            required
                            class="block w-full shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm border-gray-300 rounded-md"
                        >
                            <option value="">-- Sélectionner un algorithme --</option>
                            <option value="greedy" <?php echo (isset($formData['algorithm_type']) && $formData['algorithm_type'] === 'greedy') ? 'selected' : ''; ?>>Algorithme glouton (priorité aux préférences)</option>
                            <option value="hungarian" <?php echo (isset($formData['algorithm_type']) && $formData['algorithm_type'] === 'hungarian') ? 'selected' : ''; ?>>Algorithme hongrois (optimisation globale)</option>
                            <option value="genetic" <?php echo (isset($formData['algorithm_type']) && $formData['algorithm_type'] === 'genetic') ? 'selected' : ''; ?>>Algorithme génétique (optimisation itérative)</option>
                            <option value="hybrid" <?php echo (isset($formData['algorithm_type']) && $formData['algorithm_type'] === 'hybrid') ? 'selected' : ''; ?>>Algorithme hybride (combinaison des approches)</option>
                        </select>
                    </div>
                </div>
                
                <!-- Advanced Parameters -->
                <div class="card bg-gray-50 border border-gray-200 overflow-hidden">
                    <div class="card-header border-b border-gray-200 px-4 py-3 bg-gray-50">
                        <button type="button" class="text-primary-600 font-medium text-sm flex items-center focus:outline-none" data-action="click->generation-form#toggleAdvanced">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1.5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" />
                            </svg>
                            Paramètres avancés
                        </button>
                    </div>
                    <div class="p-4 hidden" data-generation-form-target="advancedPanel">
                        <!-- Weight Parameters -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                            <div>
                                <label for="department_weight" class="block text-sm font-medium text-gray-700 mb-1">
                                    Poids du département
                                </label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <input 
                                        type="number"
                                        id="department_weight"
                                        name="department_weight"
                                        value="<?php echo h($formData['department_weight'] ?? 50); ?>"
                                        min="0"
                                        max="100"
                                        class="block w-full pr-10 focus:ring-primary-500 focus:border-primary-500 sm:text-sm border-gray-300 rounded-md"
                                        data-action="input->generation-form#validateWeights"
                                        data-generation-form-target="weightInput"
                                    >
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">%</span>
                                    </div>
                                </div>
                                <p class="mt-1 text-sm text-gray-500">Importance du département dans le calcul de compatibilité.</p>
                            </div>
                            
                            <div>
                                <label for="preference_weight" class="block text-sm font-medium text-gray-700 mb-1">
                                    Poids des préférences
                                </label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <input 
                                        type="number"
                                        id="preference_weight"
                                        name="preference_weight"
                                        value="<?php echo h($formData['preference_weight'] ?? 30); ?>"
                                        min="0"
                                        max="100"
                                        class="block w-full pr-10 focus:ring-primary-500 focus:border-primary-500 sm:text-sm border-gray-300 rounded-md"
                                        data-action="input->generation-form#validateWeights"
                                        data-generation-form-target="weightInput"
                                    >
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">%</span>
                                    </div>
                                </div>
                                <p class="mt-1 text-sm text-gray-500">Importance des préférences de l'étudiant et du tuteur.</p>
                            </div>
                            
                            <div>
                                <label for="capacity_weight" class="block text-sm font-medium text-gray-700 mb-1">
                                    Poids de la charge
                                </label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <input 
                                        type="number"
                                        id="capacity_weight"
                                        name="capacity_weight"
                                        value="<?php echo h($formData['capacity_weight'] ?? 20); ?>"
                                        min="0"
                                        max="100"
                                        class="block w-full pr-10 focus:ring-primary-500 focus:border-primary-500 sm:text-sm border-gray-300 rounded-md"
                                        data-action="input->generation-form#validateWeights"
                                        data-generation-form-target="weightInput"
                                    >
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">%</span>
                                    </div>
                                </div>
                                <p class="mt-1 text-sm text-gray-500">Importance de l'équilibrage des charges entre tuteurs.</p>
                            </div>
                            
                            <!-- Weight warning message -->
                            <div class="md:col-span-3 hidden" data-generation-form-target="weightWarning">
                                <div class="bg-warning-50 border-l-4 border-warning-400 p-4">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5 text-warning-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm text-warning-800">
                                                La somme des poids doit être égale à 100%. Veuillez ajuster les valeurs.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Option switches -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                            <div>
                                <div class="flex items-center">
                                    <div class="form-switch">
                                        <input
                                            type="checkbox"
                                            id="allow_cross_department"
                                            name="allow_cross_department"
                                            value="1"
                                            class="form-switch-checkbox"
                                            <?php echo (isset($formData['allow_cross_department']) && $formData['allow_cross_department']) ? 'checked' : ''; ?>
                                        >
                                        <label for="allow_cross_department" class="form-switch-label"></label>
                                    </div>
                                    <label for="allow_cross_department" class="ml-2 block text-sm text-gray-900">
                                        Autoriser les affectations inter-départements
                                    </label>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Permet d'affecter un étudiant à un tuteur d'un autre département.</p>
                            </div>
                            
                            <div>
                                <div class="flex items-center">
                                    <div class="form-switch">
                                        <input
                                            type="checkbox"
                                            id="prioritize_preferences"
                                            name="prioritize_preferences"
                                            value="1"
                                            class="form-switch-checkbox"
                                            <?php echo (isset($formData['prioritize_preferences']) && $formData['prioritize_preferences']) ? 'checked' : ''; ?> checked
                                        >
                                        <label for="prioritize_preferences" class="form-switch-label"></label>
                                    </div>
                                    <label for="prioritize_preferences" class="ml-2 block text-sm text-gray-900">
                                        Prioriser les préférences des étudiants
                                    </label>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Donne la priorité aux choix des étudiants plutôt qu'aux préférences des tuteurs.</p>
                            </div>
                            
                            <div>
                                <div class="flex items-center">
                                    <div class="form-switch">
                                        <input
                                            type="checkbox"
                                            id="balance_workload"
                                            name="balance_workload"
                                            value="1"
                                            class="form-switch-checkbox"
                                            <?php echo (isset($formData['balance_workload']) && $formData['balance_workload']) ? 'checked' : ''; ?> checked
                                        >
                                        <label for="balance_workload" class="form-switch-label"></label>
                                    </div>
                                    <label for="balance_workload" class="ml-2 block text-sm text-gray-900">
                                        Équilibrer la charge des tuteurs
                                    </label>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Essaie de répartir les étudiants équitablement entre les tuteurs.</p>
                            </div>
                        </div>
                        
                        <div>
                            <div class="flex items-center">
                                <div class="form-switch">
                                    <input
                                        type="checkbox"
                                        id="set_as_default"
                                        name="set_as_default"
                                        value="1"
                                        class="form-switch-checkbox"
                                        <?php echo (isset($formData['set_as_default']) && $formData['set_as_default']) ? 'checked' : ''; ?>
                                    >
                                    <label for="set_as_default" class="form-switch-label"></label>
                                </div>
                                <label for="set_as_default" class="ml-2 block text-sm text-gray-900">
                                    Définir comme paramètres par défaut
                                </label>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Enregistre ces paramètres comme configuration par défaut pour les futures générations.</p>
                        </div>
                    </div>
                </div>
                
                <!-- Notes field -->
                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">
                        Notes
                    </label>
                    <textarea
                        id="notes"
                        name="notes"
                        rows="2"
                        class="block w-full shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm border-gray-300 rounded-md"
                    ><?php echo h($formData['notes'] ?? ''); ?></textarea>
                    <p class="mt-1 text-sm text-gray-500">Notes sur cette exécution d'algorithme (visible uniquement par les administrateurs).</p>
                </div>
                
                <!-- Summary and warnings -->
                <div class="bg-info-50 border border-info-200 rounded-md p-4 mb-6">
                    <h3 class="text-info-800 font-medium flex items-center text-base mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2h2a1 1 0 100-2H9z" clip-rule="evenodd" />
                        </svg>
                        Résumé de la génération
                    </h3>
                    <p class="text-info-800 mb-4">
                        Vous allez générer des affectations pour <span class="font-semibold"><?php echo count($unassignedStudents); ?> étudiants</span> 
                        parmi <span class="font-semibold"><?php echo count($availableInternships); ?> stages disponibles</span>, 
                        avec <span class="font-semibold"><?php echo $teacherCapacity; ?> places disponibles</span> réparties sur 
                        <span class="font-semibold"><?php echo count($availableTeachers); ?> tuteurs</span>.
                    </p>
                    
                    <?php if (count($availableInternships) < count($unassignedStudents)): ?>
                    <div class="bg-warning-50 border-l-4 border-warning-400 p-4 mb-2">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-warning-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-warning-800">
                                    <span class="font-bold">Attention :</span> Il n'y a pas assez de stages disponibles pour tous les étudiants. Certains étudiants ne recevront pas d'affectation.
                                </p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($teacherCapacity < count($unassignedStudents)): ?>
                    <div class="bg-warning-50 border-l-4 border-warning-400 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-warning-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-warning-800">
                                    <span class="font-bold">Attention :</span> La capacité totale des tuteurs est insuffisante. Certains étudiants ne recevront pas d'affectation.
                                </p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Form buttons -->
                <div class="flex justify-end space-x-3">
                    <a href="/tutoring/views/admin/assignments.php" class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        Annuler
                    </a>
                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10.293 15.707a1 1 0 010-1.414L14.586 10l-4.293-4.293a1 1 0 111.414-1.414l5 5a1 1 0 010 1.414l-5 5a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            <path fill-rule="evenodd" d="M4.293 15.707a1 1 0 010-1.414L8.586 10 4.293 5.707a1 1 0 011.414-1.414l5 5a1 1 0 010 1.414l-5 5a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                        </svg>
                        Générer les affectations
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add any additional JavaScript functionality if needed
});
</script>