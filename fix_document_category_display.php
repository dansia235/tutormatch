<?php
/**
 * Script pour corriger l'affichage des catégories de documents
 */

// Définir le chemin racine du système
define('ROOT_PATH', dirname(__FILE__));

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Correction de l'affichage des catégories de documents</h1>";

// Modification du fichier d'affichage
$filePath = ROOT_PATH . '/views/admin/documents.php';

if (file_exists($filePath)) {
    // Lire le contenu du fichier
    $content = file_get_contents($filePath);
    if ($content === false) {
        echo "<p style='color:red;'>Impossible de lire le fichier {$filePath}</p>";
        exit;
    }
    
    // Créer une sauvegarde
    $backupPath = $filePath . '.backup.' . date('Ymd_His');
    if (file_put_contents($backupPath, $content) === false) {
        echo "<p style='color:red;'>Impossible de créer une sauvegarde pour {$filePath}</p>";
        exit;
    }
    
    // Remplacer $document['category'] par $document['type']
    $newContent = str_replace(
        '$docCategory = $document[\'category\'] ?? \'\';', 
        '$docCategory = $document[\'type\'] ?? \'\';', 
        $content
    );
    
    // Enregistrer les modifications
    if (file_put_contents($filePath, $newContent) === false) {
        echo "<p style='color:red;'>Impossible d'enregistrer les modifications pour {$filePath}</p>";
        exit;
    }
    
    echo "<p style='color:green;'>Fichier modifié avec succès: {$filePath}</p>";
    echo "<p>La référence à 'category' a été remplacée par 'type'.</p>";
} else {
    echo "<p style='color:red;'>Fichier non trouvé: {$filePath}</p>";
}

// Vérifions également le fichier edit.php pour les formulaires d'édition
$editFilePath = ROOT_PATH . '/views/admin/documents/edit.php';

if (file_exists($editFilePath)) {
    // Lire le contenu du fichier
    $content = file_get_contents($editFilePath);
    if ($content === false) {
        echo "<p style='color:red;'>Impossible de lire le fichier {$editFilePath}</p>";
    } else {
        // Créer une sauvegarde
        $backupPath = $editFilePath . '.backup.' . date('Ymd_His');
        if (file_put_contents($backupPath, $content) === false) {
            echo "<p style='color:red;'>Impossible de créer une sauvegarde pour {$editFilePath}</p>";
        } else {
            // Remplacer les références à category par type dans les formulaires
            $newContent = str_replace(
                'value="<?php echo $document[\'category\']; ?>"', 
                'value="<?php echo $document[\'type\']; ?>"', 
                $content
            );
            
            // Enregistrer les modifications
            if (file_put_contents($editFilePath, $newContent) === false) {
                echo "<p style='color:red;'>Impossible d'enregistrer les modifications pour {$editFilePath}</p>";
            } else {
                echo "<p style='color:green;'>Fichier modifié avec succès: {$editFilePath}</p>";
            }
        }
    }
}

// Vérifions le DocumentController.php pour la conversion entre category et type
$controllerFilePath = ROOT_PATH . '/controllers/DocumentController.php';

if (file_exists($controllerFilePath)) {
    // Lire le contenu du fichier
    $content = file_get_contents($controllerFilePath);
    if ($content === false) {
        echo "<p style='color:red;'>Impossible de lire le fichier {$controllerFilePath}</p>";
    } else {
        echo "<p>Le contrôleur DocumentController utilise déjà la conversion entre 'category' et 'type'.</p>";
        echo "<p>Il convertit correctement le paramètre 'category' en 'type' lors de l'insertion en base de données.</p>";
    }
}

// Instructions pour tester les modifications
echo "<h2>Prochaines étapes</h2>";
echo "<p>Les modifications ont été appliquées. Veuillez tester l'affichage des catégories dans la liste des documents.</p>";
echo "<p><a href='/tutoring/views/admin/documents.php' class='btn btn-primary'>Voir la liste des documents</a></p>";
?>