<?php
/**
 * Composant de filtre avancé pour les stages
 * 
 * @param string $id          ID du composant
 * @param string $action      URL d'action du formulaire
 * @param array  $domains     Liste des domaines disponibles
 * @param array  $skills      Liste des compétences disponibles
 * @param array  $filters     Filtres actuellement appliqués
 * @param bool   $collapsible Si les filtres peuvent être réduits
 * @param string $class       Classes CSS additionnelles
 */

// Extraire les variables
$id = $id ?? 'internship-filter-' . uniqid();
$action = $action ?? '';
$domains = $domains ?? [];
$skills = $skills ?? [];
$filters = $filters ?? [];
$collapsible = $collapsible ?? true;
$class = $class ?? '';

// Valeurs des filtres actuels
$domain = $filters['domain'] ?? '';
$location = $filters['location'] ?? '';
$work_mode = $filters['work_mode'] ?? '';
$start_date_from = $filters['start_date']['from'] ?? '';
$start_date_to = $filters['start_date']['to'] ?? '';
$selectedSkills = $filters['skills'] ?? [];

// Modes de travail
$workModes = [
    'onsite' => 'Sur site',
    'remote' => 'Télétravail',
    'hybrid' => 'Hybride'
];
?>

<div id="<?php echo htmlspecialchars($id); ?>" class="internship-filter <?php echo htmlspecialchars($class); ?>" data-controller="filter">
    <div class="filter-header mb-3 flex justify-between items-center">
        <h3 class="text-lg font-medium">Filtres avancés</h3>
        
        <?php if ($collapsible): ?>
        <button type="button" class="text-gray-500 hover:text-gray-700" data-action="filter#toggle">
            <span data-filter-target="collapseIcon">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </span>
        </button>
        <?php endif; ?>
    </div>
    
    <div class="filter-content <?php echo $collapsible ? 'hidden' : ''; ?>" data-filter-target="content">
        <form action="<?php echo htmlspecialchars($action); ?>" method="GET" class="space-y-4">
            <!-- Terme de recherche (caché) -->
            <?php if (isset($filters['term'])): ?>
            <input type="hidden" name="term" value="<?php echo htmlspecialchars($filters['term']); ?>">
            <?php endif; ?>
            
            <!-- Domaine -->
            <div class="form-group">
                <label for="<?php echo $id; ?>-domain" class="block text-sm font-medium text-gray-700 mb-1">Domaine</label>
                <select id="<?php echo $id; ?>-domain" name="domain" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                    <option value="">Tous les domaines</option>
                    <?php foreach ($domains as $domainValue): ?>
                    <option value="<?php echo htmlspecialchars($domainValue); ?>" <?php echo $domain === $domainValue ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($domainValue); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Localisation -->
            <div class="form-group">
                <label for="<?php echo $id; ?>-location" class="block text-sm font-medium text-gray-700 mb-1">Localisation</label>
                <input type="text" id="<?php echo $id; ?>-location" name="location" value="<?php echo htmlspecialchars($location); ?>" placeholder="Ville, pays..." class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
            </div>
            
            <!-- Mode de travail -->
            <div class="form-group">
                <label for="<?php echo $id; ?>-work-mode" class="block text-sm font-medium text-gray-700 mb-1">Mode de travail</label>
                <select id="<?php echo $id; ?>-work-mode" name="work_mode" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                    <option value="">Tous les modes</option>
                    <?php foreach ($workModes as $key => $label): ?>
                    <option value="<?php echo htmlspecialchars($key); ?>" <?php echo $work_mode === $key ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($label); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Période de début -->
            <div class="form-group">
                <label class="block text-sm font-medium text-gray-700 mb-1">Date de début</label>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label for="<?php echo $id; ?>-start-date-from" class="block text-xs text-gray-500 mb-1">De</label>
                        <input type="date" id="<?php echo $id; ?>-start-date-from" name="start_date_from" value="<?php echo htmlspecialchars($start_date_from); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                    </div>
                    <div>
                        <label for="<?php echo $id; ?>-start-date-to" class="block text-xs text-gray-500 mb-1">À</label>
                        <input type="date" id="<?php echo $id; ?>-start-date-to" name="start_date_to" value="<?php echo htmlspecialchars($start_date_to); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                    </div>
                </div>
            </div>
            
            <!-- Compétences -->
            <?php if (!empty($skills)): ?>
            <div class="form-group">
                <label class="block text-sm font-medium text-gray-700 mb-1">Compétences requises</label>
                <div class="max-h-40 overflow-y-auto border border-gray-300 rounded-md p-2">
                    <?php foreach ($skills as $skill): ?>
                    <div class="flex items-center mb-1">
                        <input type="checkbox" id="<?php echo $id; ?>-skill-<?php echo htmlspecialchars($skill); ?>" name="skills[]" value="<?php echo htmlspecialchars($skill); ?>" <?php echo in_array($skill, $selectedSkills) ? 'checked' : ''; ?> class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                        <label for="<?php echo $id; ?>-skill-<?php echo htmlspecialchars($skill); ?>" class="ml-2 block text-sm text-gray-700"><?php echo htmlspecialchars($skill); ?></label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Boutons d'action -->
            <div class="flex space-x-2 mt-4">
                <button type="submit" class="btn btn-primary flex-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd" />
                    </svg>
                    Appliquer
                </button>
                <a href="<?php echo htmlspecialchars($action); ?>" class="btn btn-secondary flex-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
                    </svg>
                    Réinitialiser
                </a>
            </div>
        </form>
    </div>
</div>