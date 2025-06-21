<?php
/**
 * Correctif direct pour la page de préférences des étudiants
 * Ce script modifie directement la page des préférences pour résoudre le problème d'affichage
 */

// Définir les entêtes pour une sortie HTML
header('Content-Type: text/html; charset=utf-8');

echo '<!DOCTYPE html>
<html>
<head>
    <title>Correctif pour la page des préférences</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .info { color: blue; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; }
        .code { font-family: monospace; background: #f8f8f8; padding: 15px; border-left: 4px solid #4CAF50; }
        .action-button { padding: 10px 15px; background: #4CAF50; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Correctif direct pour la page des préférences</h1>';

$preferences_file = '/mnt/c/xampp/htdocs/tutoring/views/student/preferences.php';

echo '<h2>Fichier cible</h2>';
echo '<p>Le fichier à modifier est : <code>' . $preferences_file . '</code></p>';

// Vérifier si le fichier existe
if (!file_exists($preferences_file)) {
    echo '<p class="error">Le fichier n\'existe pas !</p>';
    exit;
}

// Sauvegarder le fichier original
$backup_file = $preferences_file . '.backup.' . date('Ymd_His');
if (copy($preferences_file, $backup_file)) {
    echo '<p class="success">Sauvegarde créée : ' . $backup_file . '</p>';
} else {
    echo '<p class="error">Impossible de créer une sauvegarde du fichier.</p>';
    exit;
}

// Lire le contenu du fichier
$content = file_get_contents($preferences_file);
if ($content === false) {
    echo '<p class="error">Impossible de lire le fichier.</p>';
    exit;
}

// Corrections à appliquer

// 1. Ajouter un script de débogage pour afficher les données
$debug_script = '
<script>
// Fonction pour déboguer les problèmes d\'affichage des préférences
function debugPreferences() {
    console.log("Debug des préférences :");
    
    // Vérifier les données de secours
    console.log("Données de secours:", window.fallbackPreferencesData);
    
    // Vérifier le contrôleur Stimulus
    const preferencesElement = document.querySelector(\'[data-controller="student-preferences"]\');
    if (preferencesElement) {
        console.log("Élément du contrôleur trouvé");
        
        // Essayer de récupérer le contrôleur
        setTimeout(() => {
            try {
                const controller = window.Stimulus.getControllerForElementAndIdentifier(preferencesElement, "student-preferences");
                console.log("Contrôleur:", controller);
                console.log("Préférences dans le contrôleur:", controller?.preferences);
                
                // Forcer l\'affichage des préférences si elles existent
                if (window.fallbackPreferencesData && window.fallbackPreferencesData.length > 0 && controller) {
                    console.log("Application forcée des préférences de secours");
                    controller.preferences = window.fallbackPreferencesData.map(pref => ({
                        internship_id: pref.internship_id,
                        title: pref.title || "Stage sans titre",
                        company: pref.company_name || "Entreprise non spécifiée", 
                        rank: pref.preference_order || 1,
                        reason: pref.reason || null
                    }));
                    
                    // Forcer la mise à jour de l\'interface
                    controller.updatePreferencesList();
                    
                    // Masquer l\'indicateur de chargement s\'il est encore visible
                    const loadingIndicator = document.querySelector(\'[data-student-preferences-target="loadingIndicator"]\');
                    if (loadingIndicator && !loadingIndicator.classList.contains("hidden")) {
                        console.log("Masquage de l\'indicateur de chargement");
                        loadingIndicator.classList.add("hidden");
                    }
                }
            } catch (e) {
                console.error("Erreur lors de l\'accès au contrôleur:", e);
            }
        }, 1000);
    } else {
        console.log("Élément du contrôleur non trouvé");
    }
}

// Exécuter le débogage après le chargement complet
window.addEventListener("load", function() {
    console.log("Page chargée, démarrage du débogage");
    debugPreferences();
    
    // Réessayer après quelques secondes au cas où le chargement asynchrone prend du temps
    setTimeout(debugPreferences, 2000);
});
</script>
';

// 2. Modifier le code pour utiliser le session user_id correct
$old_student_id = '$student_id = $_SESSION[\'user\'][\'id\'] ?? null;';
$new_student_id = '// Récupérer l\'ID de l\'étudiant de la session
$student_id = null;
if (isset($_SESSION[\'user\'][\'id\'])) {
    $student_id = $_SESSION[\'user\'][\'id\'];
} elseif (isset($_SESSION[\'user_id\'])) {
    // Récupérer l\'ID étudiant à partir de l\'ID utilisateur
    $studentModel = new Student($db);
    $student = $studentModel->getByUserId($_SESSION[\'user_id\']);
    if ($student) {
        $student_id = $student[\'id\'];
    }
}
';

// 3. Améliorer le chargement des données de secours
$old_fallback_load = '$currentStudentPreferences = [];

try {
    if (isset($_SESSION[\'user_id\'])) {
        $studentModel = new Student($db);
        $student = $studentModel->getByUserId($_SESSION[\'user_id\']);
        
        if ($student) {
            $preferences = $studentModel->getPreferences($student[\'id\']);
            
            // Formatter les préférences pour le JavaScript
            foreach ($preferences as $pref) {
                $currentStudentPreferences[] = [
                    \'internship_id\' => $pref[\'internship_id\'],
                    \'title\' => $pref[\'title\'] ?? \'Stage sans titre\',
                    \'company_name\' => $pref[\'company_name\'] ?? \'Entreprise non spécifiée\',
                    \'preference_order\' => $pref[\'preference_order\'] ?? 1,
                    \'rank\' => $pref[\'preference_order\'] ?? 1,
                    \'reason\' => $pref[\'reason\'] ?? null
                ];
            }
            
            error_log("Formatted " . count($currentStudentPreferences) . " preferences for JavaScript");
        } else {
            error_log("Student not found for user_id: " . $user_id);
        }
    } else {
        error_log("No user_id available in session");
    }
} catch (Exception $e) {
    // Log l\'erreur pour le débogage
    error_log("Error loading preferences in page: " . $e->getMessage());
}';

$new_fallback_load = '$currentStudentPreferences = [];

try {
    // Récupérer l\'ID utilisateur et l\'ID étudiant
    $user_id = $_SESSION[\'user_id\'] ?? null;
    $direct_student_id = $student_id; // Utiliser l\'ID étudiant défini plus haut
    
    error_log("Loading preferences with user_id: " . ($user_id ?: \'null\') . ", student_id: " . ($direct_student_id ?: \'null\'));
    
    // Si nous avons un ID étudiant direct, l\'utiliser
    if ($direct_student_id) {
        $studentModel = new Student($db);
        $preferences = $studentModel->getPreferences($direct_student_id);
        error_log("Direct lookup: Found " . count($preferences) . " preferences for student ID " . $direct_student_id);
    }
    // Sinon, essayer de le récupérer via l\'ID utilisateur
    elseif ($user_id) {
        $studentModel = new Student($db);
        $student = $studentModel->getByUserId($user_id);
        
        if ($student) {
            error_log("Found student with ID: " . $student[\'id\'] . " for user_id: " . $user_id);
            $preferences = $studentModel->getPreferences($student[\'id\']);
            error_log("Found " . count($preferences) . " preferences for student ID " . $student[\'id\']);
        } else {
            error_log("Student not found for user_id: " . $user_id);
            $preferences = [];
        }
    } else {
        error_log("No user_id or student_id available in session");
        $preferences = [];
    }
    
    // Formatter les préférences pour le JavaScript
    if (isset($preferences) && is_array($preferences)) {
        foreach ($preferences as $pref) {
            $currentStudentPreferences[] = [
                \'internship_id\' => $pref[\'internship_id\'],
                \'title\' => $pref[\'title\'] ?? \'Stage sans titre\',
                \'company_name\' => $pref[\'company_name\'] ?? \'Entreprise non spécifiée\',
                \'preference_order\' => $pref[\'preference_order\'] ?? 1,
                \'rank\' => $pref[\'preference_order\'] ?? 1,
                \'reason\' => $pref[\'reason\'] ?? null
            ];
        }
        
        error_log("Formatted " . count($currentStudentPreferences) . " preferences for JavaScript");
    }
} catch (Exception $e) {
    // Log l\'erreur pour le débogage
    error_log("Error loading preferences in page: " . $e->getMessage());
}';

// Appliquer les modifications
$modified_content = $content;

// Remplacer la définition de student_id
$modified_content = str_replace($old_student_id, $new_student_id, $modified_content);
if ($content === $modified_content) {
    echo '<p class="warning">La définition de student_id n\'a pas été trouvée dans le fichier. Vérifiez le contenu.</p>';
} else {
    echo '<p class="success">Définition de student_id modifiée avec succès.</p>';
}

// Remplacer le chargement des données de secours
$modified_content = str_replace($old_fallback_load, $new_fallback_load, $modified_content);
if ($content === $modified_content) {
    echo '<p class="warning">Le code de chargement des données de secours n\'a pas été trouvé dans le fichier. Vérifiez le contenu.</p>';
} else {
    echo '<p class="success">Code de chargement des données de secours modifié avec succès.</p>';
}

// Ajouter le script de débogage avant la fermeture body
$modified_content = str_replace('</body>', $debug_script . "\n</body>", $modified_content);
echo '<p class="success">Script de débogage ajouté avec succès.</p>';

// Écrire les modifications dans le fichier
if (file_put_contents($preferences_file, $modified_content) !== false) {
    echo '<p class="success">Modifications appliquées avec succès au fichier.</p>';
    echo '<h2>Récapitulatif des modifications</h2>';
    echo '<ul>';
    echo '<li>Amélioration de la récupération de l\'ID étudiant</li>';
    echo '<li>Optimisation du chargement des données de secours</li>';
    echo '<li>Ajout d\'un script de débogage pour forcer l\'affichage des préférences</li>';
    echo '</ul>';
    
    echo '<p>Vous pouvez maintenant <a href="/tutoring/views/student/preferences.php" class="action-button">accéder à la page des préférences</a> pour vérifier que le problème est résolu.</p>';
} else {
    echo '<p class="error">Impossible d\'écrire les modifications dans le fichier.</p>';
    echo '<p>Vous pouvez toujours restaurer la sauvegarde : <code>' . $backup_file . '</code></p>';
}

echo '</body></html>';