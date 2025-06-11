<?php
/**
 * Admin Dashboard Content Template
 * Uses components to display dashboard statistics
 */
?>

<div class="container mx-auto">
    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-900">Tableau de bord</h1>
        <p class="mt-1 text-sm text-gray-600">Aperçu des statistiques du système de tutorat</p>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <?php foreach ($statCards as $card): ?>
            <?php 
            // Include stat card component with variables
            include_with_vars(__DIR__ . '/../../components/cards/stat-card.php', $card);
            ?>
        <?php endforeach; ?>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Assignments Status Chart -->
        <div class="card shadow-sm">
            <div class="card-header border-b border-gray-200 px-5 py-4">
                <h2 class="font-semibold text-lg text-gray-800">Statut des affectations</h2>
            </div>
            <div class="card-body p-5">
                <?php 
                include_with_vars(__DIR__ . '/../../components/charts/chart.php', [
                    'id' => 'chart-assignment-status',
                    'type' => $chartData['assignmentStatus']['type'],
                    'data' => $chartData['assignmentStatus']['data'],
                    'options' => $chartData['assignmentStatus']['options'],
                    'height' => '240px'
                ]);
                ?>
            </div>
        </div>

        <!-- Internships by Status Chart -->
        <div class="card shadow-sm">
            <div class="card-header border-b border-gray-200 px-5 py-4">
                <h2 class="font-semibold text-lg text-gray-800">Stages par statut</h2>
            </div>
            <div class="card-body p-5">
                <?php 
                include_with_vars(__DIR__ . '/../../components/charts/chart.php', [
                    'id' => 'chart-internship-status',
                    'type' => $chartData['internshipStatus']['type'],
                    'data' => $chartData['internshipStatus']['data'],
                    'options' => $chartData['internshipStatus']['options'],
                    'height' => '240px'
                ]);
                ?>
            </div>
        </div>

        <!-- Tutor Workload Chart -->
        <div class="card shadow-sm">
            <div class="card-header border-b border-gray-200 px-5 py-4">
                <h2 class="font-semibold text-lg text-gray-800">Charge de travail des tuteurs</h2>
            </div>
            <div class="card-body p-5">
                <?php 
                include_with_vars(__DIR__ . '/../../components/charts/chart.php', [
                    'id' => 'chart-tutor-workload',
                    'type' => $chartData['tutorWorkload']['type'],
                    'data' => $chartData['tutorWorkload']['data'],
                    'options' => $chartData['tutorWorkload']['options'],
                    'height' => '240px'
                ]);
                ?>
            </div>
        </div>

        <!-- Document Categories Chart -->
        <div class="card shadow-sm">
            <div class="card-header border-b border-gray-200 px-5 py-4">
                <h2 class="font-semibold text-lg text-gray-800">Documents par catégorie</h2>
            </div>
            <div class="card-body p-5">
                <?php 
                include_with_vars(__DIR__ . '/../../components/charts/chart.php', [
                    'id' => 'chart-document-categories',
                    'type' => $chartData['documentCategories']['type'],
                    'data' => $chartData['documentCategories']['data'],
                    'options' => $chartData['documentCategories']['options'],
                    'height' => '240px'
                ]);
                ?>
            </div>
        </div>
    </div>

    <!-- Recent Assignments Table -->
    <div class="card shadow-sm mb-6">
        <div class="card-header border-b border-gray-200 px-5 py-4">
            <h2 class="font-semibold text-lg text-gray-800">Affectations récentes</h2>
        </div>
        <div class="card-body p-0">
            <?php 
            // Define table actions
            $actions = [
                [
                    'label' => 'Voir',
                    'url' => function($row) {
                        // This is a placeholder - would need real student/teacher IDs
                        return '/tutoring/views/admin/assignments/show.php?id=' . rand(1, 100);
                    },
                    'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                        <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                    </svg>',
                    'class' => 'text-info-600 hover:text-info-800'
                ],
                [
                    'label' => 'Modifier',
                    'url' => function($row) {
                        // This is a placeholder - would need real student/teacher IDs
                        return '/tutoring/views/admin/assignments/edit.php?id=' . rand(1, 100);
                    },
                    'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                    </svg>',
                    'class' => 'text-warning-600 hover:text-warning-800'
                ]
            ];

            // Include table component with assignment data
            include_with_vars(__DIR__ . '/../../components/tables/table.php', [
                'id' => 'recent-assignments-table',
                'headers' => $assignmentHeaders,
                'data' => $assignmentsData,
                'actions' => $actions,
                'emptyText' => 'Aucune affectation récente'
            ]);
            ?>
        </div>
        <div class="card-footer border-t border-gray-200 px-5 py-4">
            <a href="/tutoring/views/admin/assignments.php" class="text-primary-600 hover:text-primary-800 font-medium flex items-center">
                Voir toutes les affectations
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L12.586 11H5a1 1 0 110-2h7.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </a>
        </div>
    </div>

    <!-- Assignments by Department Chart -->
    <div class="card shadow-sm mb-6">
        <div class="card-header border-b border-gray-200 px-5 py-4">
            <h2 class="font-semibold text-lg text-gray-800">Affectations par département</h2>
        </div>
        <div class="card-body p-5">
            <?php 
            include_with_vars(__DIR__ . '/../../components/charts/chart.php', [
                'id' => 'chart-assignments-by-dept',
                'type' => $chartData['assignmentsByDepartment']['type'],
                'data' => $chartData['assignmentsByDepartment']['data'],
                'options' => $chartData['assignmentsByDepartment']['options'],
                'height' => '240px'
            ]);
            ?>
        </div>
    </div>
</div>