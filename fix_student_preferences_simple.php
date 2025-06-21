<?php
/**
 * Solution simple pour le problème d'affichage des préférences étudiantes
 * Cette solution crée et injecte un script JavaScript directement dans la page des préférences
 */

// Définir le chemin racine du système
define('ROOT_PATH', dirname(__FILE__));

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Solution directe pour les préférences étudiantes</h1>";

// Chemin du fichier cible
$preferencesPath = ROOT_PATH . '/views/student/preferences.php';

// Vérifier si le fichier existe
if (!file_exists($preferencesPath)) {
    echo "<p style='color:red;'>Erreur : Le fichier preferences.php n'a pas été trouvé au chemin : $preferencesPath</p>";
    
    // Chercher le fichier
    echo "<h2>Recherche du fichier preferences.php</h2>";
    
    $results = [];
    exec('find ' . ROOT_PATH . ' -name "preferences.php" 2>/dev/null', $results);
    
    if (!empty($results)) {
        echo "<p>Fichiers trouvés :</p><ul>";
        foreach ($results as $file) {
            echo "<li>$file</li>";
        }
        echo "</ul>";
        
        // Utiliser le premier résultat
        $preferencesPath = $results[0];
        echo "<p style='color:green;'>Utilisation du fichier : $preferencesPath</p>";
    } else {
        echo "<p style='color:red;'>Aucun fichier preferences.php trouvé dans le répertoire.</p>";
        exit;
    }
}

// Lire le contenu du fichier
$originalContent = file_get_contents($preferencesPath);

if ($originalContent === false) {
    echo "<p style='color:red;'>Impossible de lire le fichier preferences.php</p>";
    exit;
}

// Créer une sauvegarde
$backupPath = $preferencesPath . '.backup.' . date('Ymd_His');
file_put_contents($backupPath, $originalContent);
echo "<p style='color:green;'>Sauvegarde créée : $backupPath</p>";

// Script JavaScript pour corriger le problème d'affichage des préférences
$fixScript = <<<'JAVASCRIPT'

<script>
// Script de correction pour l'affichage des préférences
document.addEventListener('DOMContentLoaded', function() {
    console.log("Script de correction des préférences chargé");
    
    // Fonction pour vérifier si les préférences sont chargées
    function checkPreferencesLoaded() {
        const loadingIndicator = document.querySelector('[data-student-preferences-target="loadingIndicator"]');
        const emptyState = document.querySelector('[data-student-preferences-target="emptyState"]');
        
        if (loadingIndicator && !loadingIndicator.classList.contains('hidden')) {
            console.log("Indicateur de chargement toujours visible, tentative de correction");
            return false;
        }
        
        return true;
    }
    
    // Fonction pour appliquer les préférences de secours
    function applyFallbackPreferences() {
        console.log("Tentative d'application des préférences de secours");
        
        // Vérifier si l'indicateur de chargement est toujours visible
        const loadingIndicator = document.querySelector('[data-student-preferences-target="loadingIndicator"]');
        if (loadingIndicator) {
            loadingIndicator.classList.add('hidden');
            console.log("Indicateur de chargement masqué");
        }
        
        // Vérifier si nous avons des préférences de secours
        if (window.fallbackPreferencesData && Array.isArray(window.fallbackPreferencesData) && window.fallbackPreferencesData.length > 0) {
            console.log("Données de secours disponibles:", window.fallbackPreferencesData);
            
            // Trouver l'élément du contrôleur
            const controllerElement = document.querySelector('[data-controller="student-preferences"]');
            if (controllerElement) {
                console.log("Élément du contrôleur trouvé");
                
                // Trouver le contrôleur Stimulus
                if (window.Stimulus && typeof window.Stimulus.getControllerForElementAndIdentifier === "function") {
                    try {
                        const controller = window.Stimulus.getControllerForElementAndIdentifier(
                            controllerElement, 
                            "student-preferences"
                        );
                        
                        if (controller) {
                            console.log("Contrôleur trouvé, application des préférences");
                            
                            // Préparer les préférences formatées
                            const formattedPrefs = window.fallbackPreferencesData.map(pref => ({
                                internship_id: pref.internship_id,
                                title: pref.title || "Stage sans titre",
                                company: pref.company_name || "Entreprise non spécifiée",
                                rank: pref.preference_order || pref.rank || 1,
                                reason: pref.reason || null
                            }));
                            
                            // Appliquer les préférences
                            controller.preferences = formattedPrefs;
                            
                            // Mettre à jour l'interface
                            controller.updatePreferencesList();
                            
                            // Masquer l'état vide si nécessaire
                            const emptyState = document.querySelector('[data-student-preferences-target="emptyState"]');
                            if (emptyState && !emptyState.classList.contains('hidden')) {
                                emptyState.classList.add('hidden');
                                console.log("État vide masqué");
                            }
                            
                            // Afficher les préférences
                            const selectedPreferences = document.querySelector('[data-student-preferences-target="selectedPreferences"]');
                            if (selectedPreferences) {
                                console.log("Liste des préférences affichée");
                            }
                            
                            console.log("Préférences appliquées avec succès");
                            return true;
                        } else {
                            console.log("Contrôleur non trouvé via l'API Stimulus");
                        }
                    } catch (e) {
                        console.error("Erreur lors de l'accès au contrôleur:", e);
                    }
                } else {
                    console.log("API Stimulus non disponible");
                }
                
                // Si nous n'avons pas pu appliquer les préférences via le contrôleur, créer une liste manuelle
                if (!checkPreferencesLoaded()) {
                    console.log("Création manuelle de la liste des préférences");
                    
                    // Masquer l'indicateur de chargement
                    if (loadingIndicator) {
                        loadingIndicator.classList.add('hidden');
                    }
                    
                    // Masquer l'état vide
                    const emptyState = document.querySelector('[data-student-preferences-target="emptyState"]');
                    if (emptyState) {
                        emptyState.classList.add('hidden');
                    }
                    
                    // Créer la liste des préférences
                    const selectedPreferences = document.querySelector('[data-student-preferences-target="selectedPreferences"]');
                    if (selectedPreferences) {
                        selectedPreferences.innerHTML = '';
                        
                        window.fallbackPreferencesData.forEach((pref, index) => {
                            const prefElement = document.createElement('div');
                            prefElement.className = "d-flex align-items-center p-3 border rounded mb-2 bg-white position-relative preference-item";
                            prefElement.dataset.preferenceId = pref.internship_id;
                            
                            prefElement.innerHTML = `
                                <div class="d-flex align-items-center justify-content-center bg-primary text-white rounded-circle me-3" style="width: 32px; height: 32px;">
                                    ${index + 1}
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="mb-0">${pref.title || "Stage sans titre"}</h5>
                                    <p class="text-muted mb-0">${pref.company_name || "Entreprise non spécifiée"}</p>
                                    ${pref.reason ? `<p class="small text-muted mt-1"><em>Raison: ${pref.reason}</em></p>` : ''}
                                </div>
                            `;
                            
                            selectedPreferences.appendChild(prefElement);
                        });
                        
                        console.log("Liste des préférences créée manuellement");
                        return true;
                    }
                }
            } else {
                console.log("Élément du contrôleur non trouvé");
            }
        } else {
            console.log("Aucune préférence de secours disponible");
        }
        
        return false;
    }
    
    // Vérifier après 1 seconde
    setTimeout(() => {
        if (!checkPreferencesLoaded()) {
            applyFallbackPreferences();
        }
    }, 1000);
    
    // Vérifier après 3 secondes (dernier recours)
    setTimeout(() => {
        if (!checkPreferencesLoaded()) {
            applyFallbackPreferences();
        }
    }, 3000);
});
</script>
JAVASCRIPT;

// Ajouter le script de correction juste avant la fermeture du body
$fixedContent = str_replace('</body>', $fixScript . "\n</body>", $originalContent);

// Enregistrer les modifications
if (file_put_contents($preferencesPath, $fixedContent) === false) {
    echo "<p style='color:red;'>Impossible d'enregistrer les modifications</p>";
    exit;
}

echo "<p style='color:green;'>Solution implémentée avec succès !</p>";

// Instructions pour tester
echo "<h2>Prochaines étapes</h2>";
echo "<p>Un script JavaScript a été ajouté pour corriger l'affichage des préférences :</p>";
echo "<ul>";
echo "<li>Le script vérifie si l'indicateur de chargement est visible trop longtemps</li>";
echo "<li>Il utilise les données de secours (fallbackPreferencesData) pour afficher les préférences</li>";
echo "<li>Il tente d'appliquer les préférences via le contrôleur Stimulus</li>";
echo "<li>Si cela ne fonctionne pas, il crée manuellement la liste des préférences</li>";
echo "</ul>";

echo "<p>Pour tester cette solution :</p>";
echo "<ol>";
echo "<li><a href='/tutoring/views/student/preferences.php' style='color: blue;'>Accédez à la page des préférences</a></li>";
echo "<li>Vérifiez que les préférences sont maintenant visibles</li>";
echo "<li>Si elles ne sont pas visibles, ouvrez la console du navigateur (F12) pour voir les messages de débogage</li>";
echo "</ol>";

echo "<p>Si cette solution ne fonctionne pas, vous pouvez restaurer la sauvegarde du fichier original :</p>";
echo "<pre>cp $backupPath $preferencesPath</pre>";

// Proposer d'ajouter la colonne reason à la base de données
echo "<h2>Correction de la base de données</h2>";
echo "<p>Si les préférences s'affichent mais que vous rencontrez des erreurs lors de l'ajout/modification, vous devrez peut-être ajouter la colonne 'reason' à la table 'student_preferences' :</p>";
echo "<pre>ALTER TABLE `student_preferences` ADD COLUMN `reason` TEXT NULL AFTER `preference_order`;</pre>";
echo "<p>Vous pouvez exécuter cette requête directement dans votre base de données.</p>";

echo "<p>Pour exécuter automatiquement cette requête, <a href='/tutoring/add_reason_column.php' style='color: blue;'>cliquez ici</a>.</p>";
?>