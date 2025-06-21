<?php
/**
 * Script pour modifier le contrôleur Stimulus des préférences
 */

// Définir les entêtes pour une sortie HTML
header('Content-Type: text/html; charset=utf-8');

echo '<!DOCTYPE html>
<html>
<head>
    <title>Correction du contrôleur de préférences</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .info { color: blue; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; overflow: auto; }
        .action-button { padding: 10px 15px; background: #4CAF50; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Correction du contrôleur de préférences</h1>';

// Chemin du fichier contrôleur
$controller_file = '/mnt/c/xampp/htdocs/tutoring/assets/js/controllers/student_preferences_controller.js';

echo '<h2>Vérification du fichier contrôleur</h2>';

// Vérifier si le fichier existe
if (file_exists($controller_file)) {
    echo '<p class="success">Le fichier contrôleur existe : ' . $controller_file . '</p>';
    
    // Créer une sauvegarde
    $backup_file = $controller_file . '.backup.' . date('Ymd_His');
    if (copy($controller_file, $backup_file)) {
        echo '<p class="success">Sauvegarde créée : ' . $backup_file . '</p>';
        
        // Lire le contenu du fichier
        $content = file_get_contents($controller_file);
        
        if ($content !== false) {
            echo '<p class="success">Lecture du contenu réussie.</p>';
            
            // Modifications à apliquer au contrôleur
            
            // 1. Améliorer la méthode connect pour mieux gérer les données de secours
            $old_connect = '  connect() {
    // Initialize the API client
    this.api = new ApiClient();
    
    // Initialize state
    this.preferences = [];
    this.searchTimeout = null;
    this.isLoading = false;
    
    // Load current preferences
    this.loadPreferences();
    
    // Debug log
    console.log("Student Preferences Controller connected");
  }';
  
            $new_connect = '  connect() {
    // Initialize the API client
    this.api = new ApiClient();
    
    // Initialize state
    this.preferences = [];
    this.searchTimeout = null;
    this.isLoading = false;
    
    // Check for fallback data immediately
    if (window.fallbackPreferencesData && Array.isArray(window.fallbackPreferencesData) && window.fallbackPreferencesData.length > 0) {
      console.log("Initializing with fallback data:", window.fallbackPreferencesData);
      this.preferences = window.fallbackPreferencesData.map(pref => ({
        internship_id: pref.internship_id,
        title: pref.title || "Stage sans titre",
        company: pref.company_name || "Entreprise non spécifiée",
        rank: pref.preference_order || pref.rank || 1,
        reason: pref.reason || null
      }));
      
      // Update the UI after a short delay to ensure the DOM is ready
      setTimeout(() => {
        this.updatePreferencesList();
      }, 100);
    }
    
    // Load current preferences
    this.loadPreferences();
    
    // Debug log
    console.log("Student Preferences Controller connected");
  }';
            
            // 2. Améliorer la méthode updatePreferencesList pour mieux détecter les préférences
            $old_update = '  updatePreferencesList() {
    if (!this.hasSelectedPreferencesTarget) return;
    
    const selectedPreferences = this.selectedPreferencesTarget;
    
    // Debug log
    console.log("Updating preferences list with", (this.preferences?.length || 0), "items");
    
    // Show empty state if no preferences
    if (!Array.isArray(this.preferences) || this.preferences.length === 0) {
      console.log("No preferences found, showing empty state");
      selectedPreferences.innerHTML = \'\';
      if (this.hasEmptyStateTarget) {
        this.emptyStateTarget.classList.remove("hidden");
      }
      return;
    }
    
    // Check if preferences have required properties
    let validPreferences = this.preferences.filter(p => 
      p && typeof p === \'object\' && p.internship_id && (p.title || p.internship_title)
    );
    
    console.log("Valid preferences count:", validPreferences.length);
    
    // Si nous avons des préférences mais qu\'aucune n\'est valide, essayer de les réparer
    if (this.preferences.length > 0 && validPreferences.length === 0) {
      console.log("Trying to repair invalid preferences");
      validPreferences = this.preferences.map(p => {
        if (!p) return null;
        
        // Créer un nouvel objet pour éviter la modification de l\'original
        const fixed = {
          internship_id: p.internship_id || p.id || "unknown",
          title: p.title || p.internship_title || "Stage sans titre",
          company: p.company_name || p.company || "Entreprise non spécifiée",
          rank: p.rank || p.preference_order || 1
        };
        
        return fixed;
      }).filter(p => p !== null);
      
      console.log("Repaired preferences:", validPreferences);
    }
    
    // If no valid preferences, show empty state
    if (validPreferences.length === 0) {
      console.log("No valid preferences found, showing empty state");
      selectedPreferences.innerHTML = \'\';
      if (this.hasEmptyStateTarget) {
        this.emptyStateTarget.classList.remove("hidden");
      }
      return;
    }
    
    // We have valid preferences, hide empty state
    if (this.hasEmptyStateTarget) {
      this.emptyStateTarget.classList.add("hidden");
    }
    
    // Sort preferences by rank
    validPreferences.sort((a, b) => {
      const rankA = a.rank || a.preference_order || 1;
      const rankB = b.rank || b.preference_order || 1;
      return rankA - rankB;
    });
    
    // Clear preferences list
    selectedPreferences.innerHTML = \'\';
    
    // Add each preference to the list
    validPreferences.forEach((preference, index) => {
      const preferenceElement = this.createPreferenceElement(preference, index + 1);
      selectedPreferences.appendChild(preferenceElement);
    });
    
    // Update save button state
    this.updateSaveButtonState();
  }';
  
            $new_update = '  updatePreferencesList() {
    if (!this.hasSelectedPreferencesTarget) return;
    
    const selectedPreferences = this.selectedPreferencesTarget;
    
    // Debug log
    console.log("Updating preferences list with", (this.preferences?.length || 0), "items");
    
    // If we have window.fallbackPreferencesData and no preferences, use fallback data
    if ((!Array.isArray(this.preferences) || this.preferences.length === 0) && 
        window.fallbackPreferencesData && Array.isArray(window.fallbackPreferencesData) && 
        window.fallbackPreferencesData.length > 0) {
      console.log("Using fallback preferences data because no preferences are loaded");
      this.preferences = window.fallbackPreferencesData.map(pref => ({
        internship_id: pref.internship_id,
        title: pref.title || "Stage sans titre",
        company: pref.company_name || "Entreprise non spécifiée",
        rank: pref.preference_order || pref.rank || 1,
        reason: pref.reason || null
      }));
    }
    
    // Show empty state if no preferences
    if (!Array.isArray(this.preferences) || this.preferences.length === 0) {
      console.log("No preferences found, showing empty state");
      selectedPreferences.innerHTML = \'\';
      if (this.hasEmptyStateTarget) {
        this.emptyStateTarget.classList.remove("hidden");
      }
      return;
    }
    
    // Check if preferences have required properties
    let validPreferences = this.preferences.filter(p => 
      p && typeof p === \'object\' && p.internship_id && (p.title || p.internship_title)
    );
    
    console.log("Valid preferences count:", validPreferences.length);
    
    // Si nous avons des préférences mais qu\'aucune n\'est valide, essayer de les réparer
    if (this.preferences.length > 0 && validPreferences.length === 0) {
      console.log("Trying to repair invalid preferences");
      validPreferences = this.preferences.map(p => {
        if (!p) return null;
        
        // Créer un nouvel objet pour éviter la modification de l\'original
        const fixed = {
          internship_id: p.internship_id || p.id || "unknown",
          title: p.title || p.internship_title || "Stage sans titre",
          company: p.company_name || p.company || "Entreprise non spécifiée",
          rank: p.rank || p.preference_order || 1
        };
        
        return fixed;
      }).filter(p => p !== null);
      
      console.log("Repaired preferences:", validPreferences);
    }
    
    // If no valid preferences, try fallback again or show empty state
    if (validPreferences.length === 0) {
      // Try fallback data one more time as a last resort
      if (window.fallbackPreferencesData && Array.isArray(window.fallbackPreferencesData) && 
          window.fallbackPreferencesData.length > 0) {
        console.log("Using fallback preferences as last resort");
        validPreferences = window.fallbackPreferencesData.map(pref => ({
          internship_id: pref.internship_id,
          title: pref.title || "Stage sans titre",
          company: pref.company_name || "Entreprise non spécifiée",
          rank: pref.preference_order || pref.rank || 1,
          reason: pref.reason || null
        }));
      }
      
      // If still no valid preferences, show empty state
      if (validPreferences.length === 0) {
        console.log("No valid preferences found, showing empty state");
        selectedPreferences.innerHTML = \'\';
        if (this.hasEmptyStateTarget) {
          this.emptyStateTarget.classList.remove("hidden");
        }
        return;
      }
    }
    
    // We have valid preferences, hide empty state
    if (this.hasEmptyStateTarget) {
      this.emptyStateTarget.classList.add("hidden");
    }
    
    // Sort preferences by rank
    validPreferences.sort((a, b) => {
      const rankA = a.rank || a.preference_order || 1;
      const rankB = b.rank || b.preference_order || 1;
      return rankA - rankB;
    });
    
    // Clear preferences list
    selectedPreferences.innerHTML = \'\';
    
    // Add each preference to the list
    validPreferences.forEach((preference, index) => {
      const preferenceElement = this.createPreferenceElement(preference, index + 1);
      selectedPreferences.appendChild(preferenceElement);
    });
    
    // Update save button state
    this.updateSaveButtonState();
    
    // Always hide loading indicator when updating preferences list
    if (this.hasLoadingIndicatorTarget) {
      this.loadingIndicatorTarget.classList.add("hidden");
    }
  }';
            
            // Appliquer les modifications
            $modified_content = $content;
            
            // Remplacer la méthode connect
            $modified_content = str_replace($old_connect, $new_connect, $modified_content);
            
            // Remplacer la méthode updatePreferencesList
            $modified_content = str_replace($old_update, $new_update, $modified_content);
            
            // Vérifier si les modifications ont été appliquées
            if ($modified_content !== $content) {
                // Écrire les modifications dans le fichier
                if (file_put_contents($controller_file, $modified_content) !== false) {
                    echo '<p class="success">Modifications appliquées avec succès au contrôleur !</p>';
                    echo '<h3>Modifications appliquées :</h3>';
                    echo '<ol>';
                    echo '<li>Amélioration de la méthode <code>connect()</code> pour utiliser immédiatement les données de secours</li>';
                    echo '<li>Amélioration de la méthode <code>updatePreferencesList()</code> pour mieux détecter et réparer les préférences</li>';
                    echo '<li>Ajout d\'une vérification supplémentaire des données de secours</li>';
                    echo '<li>Masquage forcé de l\'indicateur de chargement</li>';
                    echo '</ol>';
                    
                    echo '<p>Vous pouvez maintenant <a href="/tutoring/views/student/preferences.php" class="action-button">accéder à la page des préférences</a> pour vérifier si le problème est résolu.</p>';
                } else {
                    echo '<p class="error">Erreur lors de l\'écriture des modifications dans le fichier.</p>';
                }
            } else {
                echo '<p class="warning">Aucune modification n\'a été appliquée. Les motifs à remplacer n\'ont pas été trouvés.</p>';
                
                // Créer un nouveau fichier avec les modifications
                $new_controller_file = '/mnt/c/xampp/htdocs/tutoring/assets/js/controllers/student_preferences_controller_updated.js';
                if (file_put_contents($new_controller_file, $modified_content) !== false) {
                    echo '<p class="success">Un nouveau fichier a été créé avec les modifications : ' . $new_controller_file . '</p>';
                    echo '<p>Vous pouvez remplacer manuellement le fichier original par cette version mise à jour.</p>';
                } else {
                    echo '<p class="error">Erreur lors de la création du nouveau fichier.</p>';
                }
            }
        } else {
            echo '<p class="error">Impossible de lire le contenu du fichier.</p>';
        }
    } else {
        echo '<p class="error">Impossible de créer une sauvegarde du fichier.</p>';
    }
} else {
    echo '<p class="error">Le fichier contrôleur n\'existe pas : ' . $controller_file . '</p>';
    
    // Rechercher le fichier dans d'autres emplacements possibles
    echo '<h3>Recherche du fichier dans d\'autres emplacements</h3>';
    
    $possible_paths = [
        '/mnt/c/xampp/htdocs/tutoring/assets/js/controllers/',
        '/mnt/c/xampp/htdocs/tutoring/assets/js/',
        '/mnt/c/xampp/htdocs/tutoring/public/js/controllers/'
    ];
    
    foreach ($possible_paths as $path) {
        echo '<p>Recherche dans : ' . $path . '</p>';
        $files = glob($path . '*.js');
        
        if (!empty($files)) {
            echo '<ul>';
            foreach ($files as $file) {
                echo '<li>' . $file . '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>Aucun fichier JavaScript trouvé dans ce répertoire.</p>';
        }
    }
}

// Solution directe - JavaScript à injecter
echo '<h2>Solution JavaScript directe</h2>';
echo '<p>Si vous ne pouvez pas modifier les fichiers, vous pouvez exécuter ce code JavaScript dans la console du navigateur lorsque vous êtes sur la page des préférences :</p>';

$direct_js = "// Script de correction directe pour les préférences
(function() {
    console.log('Exécution du script de correction directe');
    
    // Récupérer les préférences de secours
    const fallbackPrefs = window.fallbackPreferencesData || [];
    console.log('Préférences de secours :', fallbackPrefs);
    
    if (fallbackPrefs.length > 0) {
        // Trouver l'élément du contrôleur
        const controllerElement = document.querySelector('[data-controller=\"student-preferences\"]');
        if (controllerElement) {
            console.log('Élément du contrôleur trouvé');
            
            // Trouver le contrôleur Stimulus
            if (window.Stimulus && typeof window.Stimulus.getControllerForElementAndIdentifier === 'function') {
                const controller = window.Stimulus.getControllerForElementAndIdentifier(
                    controllerElement, 
                    'student-preferences'
                );
                
                if (controller) {
                    console.log('Contrôleur trouvé, application des préférences');
                    
                    // Appliquer les préférences
                    controller.preferences = fallbackPrefs.map(pref => ({
                        internship_id: pref.internship_id,
                        title: pref.title || 'Stage sans titre',
                        company: pref.company_name || 'Entreprise non spécifiée',
                        rank: pref.preference_order || 1,
                        reason: pref.reason || null
                    }));
                    
                    // Mettre à jour l'interface
                    controller.updatePreferencesList();
                    
                    // Masquer l'indicateur de chargement
                    const loadingIndicator = document.querySelector('[data-student-preferences-target=\"loadingIndicator\"]');
                    if (loadingIndicator) {
                        loadingIndicator.classList.add('hidden');
                    }
                    
                    console.log('Préférences appliquées avec succès');
                } else {
                    console.log('Contrôleur non trouvé');
                }
            } else {
                console.log('API Stimulus non disponible');
            }
        } else {
            console.log('Élément du contrôleur non trouvé');
        }
    } else {
        console.log('Aucune préférence de secours disponible');
    }
})();";

echo '<pre>' . htmlspecialchars($direct_js) . '</pre>';

echo '<h2>Procédure manuelle</h2>';
echo '<p>Si aucune des solutions automatiques ne fonctionne, suivez ces étapes manuelles :</p>';
echo '<ol>';
echo '<li>Ajoutez la colonne "reason" à la table student_preferences si elle n\'existe pas :';
echo '<pre>ALTER TABLE `student_preferences` ADD COLUMN `reason` TEXT NULL AFTER `preference_order`;</pre></li>';
echo '<li>Vérifiez que vous avez des préférences dans la base de données :';
echo '<pre>SELECT * FROM student_preferences LIMIT 10;</pre></li>';
echo '<li>Modifiez le contrôleur Stimulus comme indiqué ci-dessus</li>';
echo '<li>Ou injectez le script JavaScript directement dans la console du navigateur</li>';
echo '</ol>';

echo '</body></html>';