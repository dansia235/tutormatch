<?php
/**
 * Script corrigé pour appliquer les correctifs à la page des préférences
 */

// Définir les entêtes pour une sortie HTML
header('Content-Type: text/html; charset=utf-8');

echo '<!DOCTYPE html>
<html>
<head>
    <title>Correctif des préférences étudiantes</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .info { color: blue; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; }
        .action-button { padding: 10px 15px; background: #4CAF50; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Correctif des préférences étudiantes</h1>';

// Chemin du fichier cible
$preferences_file = '/mnt/c/xampp/htdocs/tutoring/views/student/preferences.php';

echo '<h2>Vérification du fichier cible</h2>';

// Afficher le chemin complet
echo '<p>Chemin complet du fichier : ' . $preferences_file . '</p>';

// Vérifier si le fichier existe
if (file_exists($preferences_file)) {
    echo '<p class="success">Le fichier existe.</p>';
    
    // Vérifier les permissions
    $perms = fileperms($preferences_file);
    $readable = is_readable($preferences_file);
    $writable = is_writable($preferences_file);
    
    echo '<p>Permissions : ' . decoct($perms & 0777) . '</p>';
    echo '<p>Lecture : ' . ($readable ? 'Oui' : 'Non') . '</p>';
    echo '<p>Écriture : ' . ($writable ? 'Oui' : 'Non') . '</p>';
    
    // Tester le contenu
    $content_test = file_get_contents($preferences_file);
    if ($content_test !== false) {
        echo '<p class="success">Lecture du contenu réussie. Taille : ' . strlen($content_test) . ' octets.</p>';
        
        // Créer une sauvegarde
        $backup_file = $preferences_file . '.backup.' . date('Ymd_His');
        if (copy($preferences_file, $backup_file)) {
            echo '<p class="success">Sauvegarde créée avec succès : ' . $backup_file . '</p>';
            
            // Appliquer les modifications
            
            // 1. Script de correction à ajouter
            $correction_script = '
<script>
// Script de correction pour l\'affichage des préférences
document.addEventListener("DOMContentLoaded", function() {
    console.log("Script de correction des préférences chargé");
    
    // Fonction pour appliquer les données de secours au contrôleur
    const applyFallbackPreferences = () => {
        console.log("Tentative d\'application des données de secours");
        
        // Vérifier si nous avons des données de secours
        if (window.fallbackPreferencesData && window.fallbackPreferencesData.length > 0) {
            console.log("Données de secours disponibles:", window.fallbackPreferencesData);
            
            // Trouver l\'élément du contrôleur
            const preferencesElement = document.querySelector(\'[data-controller="student-preferences"]\');
            if (preferencesElement) {
                // Tenter de récupérer le contrôleur
                setTimeout(() => {
                    try {
                        // Récupérer le contrôleur via l\'API Stimulus
                        if (window.Stimulus && typeof window.Stimulus.getControllerForElementAndIdentifier === "function") {
                            const controller = window.Stimulus.getControllerForElementAndIdentifier(
                                preferencesElement, 
                                "student-preferences"
                            );
                            
                            if (controller) {
                                console.log("Contrôleur trouvé, application des préférences");
                                
                                // Appliquer directement les préférences
                                controller.preferences = window.fallbackPreferencesData.map(pref => ({
                                    internship_id: pref.internship_id,
                                    title: pref.title || "Stage sans titre",
                                    company: pref.company_name || "Entreprise non spécifiée",
                                    rank: pref.preference_order || 1,
                                    reason: pref.reason || null
                                }));
                                
                                // Mettre à jour l\'interface
                                controller.updatePreferencesList();
                                
                                // Masquer l\'indicateur de chargement
                                const loadingIndicator = document.querySelector(\'[data-student-preferences-target="loadingIndicator"]\');
                                if (loadingIndicator) {
                                    loadingIndicator.classList.add("hidden");
                                }
                                
                                // Mettre à jour le compteur de préférences
                                document.getElementById("preferences-count").textContent = 
                                    controller.preferences.length + "/5";
                                document.getElementById("preferences-progress").style.width = 
                                    (controller.preferences.length / 5 * 100) + "%";
                                
                                return true;
                            } else {
                                console.log("Contrôleur non trouvé dans l\'API Stimulus");
                            }
                        } else {
                            console.log("API Stimulus non disponible");
                        }
                    } catch (e) {
                        console.error("Erreur lors de l\'application des préférences:", e);
                    }
                }, 500);
            } else {
                console.log("Élément du contrôleur non trouvé");
            }
        } else {
            console.log("Aucune donnée de secours disponible");
        }
        
        return false;
    };
    
    // Première tentative immédiate
    applyFallbackPreferences();
    
    // Deuxième tentative après un délai
    setTimeout(applyFallbackPreferences, 1000);
    
    // Troisième tentative avec un délai plus long
    setTimeout(applyFallbackPreferences, 3000);
});
</script>';
            
            // Chercher le motif et appliquer le correctif
            $modified_content = $content_test;
            
            // Ajouter le script de correction avant la fermeture body
            $modified_content = str_replace('</body>', $correction_script . "\n</body>", $modified_content);
            
            // Écrire le contenu modifié dans le fichier
            $write_result = file_put_contents($preferences_file, $modified_content);
            
            if ($write_result !== false) {
                echo '<p class="success">Modifications appliquées avec succès.</p>';
                echo '<p>Le script de correction a été ajouté à la page des préférences.</p>';
                echo '<p>Vous pouvez maintenant <a href="/tutoring/views/student/preferences.php" class="action-button">accéder à la page des préférences</a> pour vérifier si le problème est résolu.</p>';
            } else {
                echo '<p class="error">Erreur lors de l\'écriture des modifications. Code d\'erreur : ' . error_get_last()['message'] . '</p>';
                echo '<p>Vous pouvez essayer d\'appliquer les modifications manuellement :</p>';
                echo '<ol>';
                echo '<li>Ouvrez le fichier ' . $preferences_file . ' dans un éditeur de texte</li>';
                echo '<li>Ajoutez le script suivant juste avant la balise de fermeture &lt;/body&gt; :</li>';
                echo '<pre>' . htmlspecialchars($correction_script) . '</pre>';
                echo '</ol>';
            }
        } else {
            echo '<p class="error">Impossible de créer une sauvegarde du fichier.</p>';
        }
    } else {
        echo '<p class="error">Impossible de lire le contenu du fichier.</p>';
    }
} else {
    echo '<p class="error">Le fichier n\'existe pas !</p>';
    
    // Rechercher le fichier dans d'autres emplacements possibles
    echo '<h3>Recherche du fichier dans d\'autres emplacements</h3>';
    
    $possible_paths = [
        '/mnt/c/xampp/htdocs/tutoring/views/student/',
        '/mnt/c/xampp/htdocs/tutoring/',
        '/var/www/html/tutoring/views/student/'
    ];
    
    foreach ($possible_paths as $path) {
        echo '<p>Recherche dans : ' . $path . '</p>';
        $files = glob($path . '*.php');
        
        if (!empty($files)) {
            echo '<ul>';
            foreach ($files as $file) {
                echo '<li>' . $file . '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>Aucun fichier PHP trouvé dans ce répertoire.</p>';
        }
    }
    
    // Proposer une solution manuelle
    echo '<h3>Solution manuelle</h3>';
    echo '<p>Comme le fichier n\'a pas été trouvé automatiquement, vous pouvez appliquer la correction manuellement :</p>';
    echo '<ol>';
    echo '<li>Localisez le fichier des préférences dans votre système</li>';
    echo '<li>Ajoutez le script suivant juste avant la balise de fermeture &lt;/body&gt; :</li>';
    echo '<pre>' . htmlspecialchars($correction_script) . '</pre>';
    echo '</ol>';
}

echo '</body></html>';