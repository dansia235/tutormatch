<?php
/**
 * Script pour corriger les redirections vers documents.php
 */

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Définir le chemin racine du système
define('ROOT_PATH', dirname(__FILE__));

echo "<h1>Correction des redirections vers documents.php</h1>";

// Vérifier si les fichiers existent
$docsPath = ROOT_PATH . '/views/admin/documents.php';
if (!file_exists($docsPath)) {
    echo "<div style='color: red; margin-bottom: 20px;'>ATTENTION: Le fichier cible '/views/admin/documents.php' n'existe pas!</div>";
    echo "<p>Ce script va remplacer les redirections, mais vous devez créer le fichier cible.</p>";
}

// Les fichiers à modifier
$filesToModify = [
    // DocumentController.php a déjà été modifié par fix_document_controller.php
    ROOT_PATH . '/views/admin/documents/show.php',
    ROOT_PATH . '/views/tutor/documents/show.php',
    ROOT_PATH . '/views/student/documents/show.php',
    ROOT_PATH . '/views/admin/documents/download.php',
    ROOT_PATH . '/views/tutor/documents/download.php',
    ROOT_PATH . '/views/student/documents/download.php',
    ROOT_PATH . '/views/admin/documents/delete.php',
    ROOT_PATH . '/views/tutor/documents/delete.php',
    ROOT_PATH . '/views/student/documents/delete.php'
];

// Recherche et remplacement
$replacements = [
    '/tutoring/views/admin/documents/index.php' => '/tutoring/views/admin/documents.php',
];

$modifiedFiles = [];
$errorFiles = [];

foreach ($filesToModify as $filePath) {
    if (!file_exists($filePath)) {
        echo "<p style='color: orange;'>Le fichier {$filePath} n'existe pas.</p>";
        continue;
    }

    try {
        // Lire le contenu du fichier
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new Exception("Impossible de lire le fichier {$filePath}");
        }

        // Créer une sauvegarde
        $backupPath = $filePath . '.backup.' . date('Ymd_His');
        if (file_put_contents($backupPath, $content) === false) {
            throw new Exception("Impossible de créer une sauvegarde pour {$filePath}");
        }

        // Effectuer les remplacements
        $modifiedContent = $content;
        $replaced = false;

        foreach ($replacements as $search => $replace) {
            $count = 0;
            $newContent = str_replace($search, $replace, $modifiedContent, $count);
            if ($count > 0) {
                $modifiedContent = $newContent;
                $replaced = true;
            }
        }

        // Si des modifications ont été effectuées, enregistrer le fichier
        if ($replaced) {
            if (file_put_contents($filePath, $modifiedContent) === false) {
                throw new Exception("Impossible d'enregistrer les modifications pour {$filePath}");
            }
            $modifiedFiles[] = $filePath;
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Erreur: " . $e->getMessage() . "</p>";
        $errorFiles[] = $filePath;
    }
}

// Modification directe du DocumentController.php
$controllerPath = ROOT_PATH . '/controllers/DocumentController.php';
if (file_exists($controllerPath)) {
    try {
        // Lire le contenu du fichier
        $content = file_get_contents($controllerPath);
        if ($content === false) {
            throw new Exception("Impossible de lire le fichier {$controllerPath}");
        }

        // Créer une sauvegarde
        $backupPath = $controllerPath . '.backup.' . date('Ymd_His');
        if (file_put_contents($backupPath, $content) === false) {
            throw new Exception("Impossible de créer une sauvegarde pour {$controllerPath}");
        }

        // Effectuer les remplacements
        $modifiedContent = $content;
        $replaced = false;

        foreach ($replacements as $search => $replace) {
            $count = 0;
            $newContent = str_replace($search, $replace, $modifiedContent, $count);
            if ($count > 0) {
                $modifiedContent = $newContent;
                $replaced = true;
            }
        }

        // Si des modifications ont été effectuées, enregistrer le fichier
        if ($replaced) {
            if (file_put_contents($controllerPath, $modifiedContent) === false) {
                throw new Exception("Impossible d'enregistrer les modifications pour {$controllerPath}");
            }
            $modifiedFiles[] = $controllerPath;
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Erreur: " . $e->getMessage() . "</p>";
        $errorFiles[] = $controllerPath;
    }
}

// Créer un fichier simple de redirection pour faciliter la transition
$redirectorPath = ROOT_PATH . '/views/admin/documents/index.php';
if (file_exists($redirectorPath)) {
    $redirectorContent = '<?php
/**
 * Redirection vers la nouvelle page de documents
 */
require_once __DIR__ . "/../../../includes/init.php";
redirect("/tutoring/views/admin/documents.php");
';

    try {
        if (file_put_contents($redirectorPath, $redirectorContent) === false) {
            throw new Exception("Impossible de créer le fichier de redirection {$redirectorPath}");
        }
        echo "<p style='color: green;'>Fichier de redirection créé: {$redirectorPath}</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>Erreur: " . $e->getMessage() . "</p>";
    }
}

// Résultats
echo "<h2>Résultats</h2>";
if (count($modifiedFiles) > 0) {
    echo "<p style='color: green;'>Fichiers modifiés avec succès (" . count($modifiedFiles) . "):</p>";
    echo "<ul>";
    foreach ($modifiedFiles as $file) {
        echo "<li>" . $file . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: orange;'>Aucun fichier n'a été modifié.</p>";
}

if (count($errorFiles) > 0) {
    echo "<p style='color: red;'>Fichiers avec erreurs (" . count($errorFiles) . "):</p>";
    echo "<ul>";
    foreach ($errorFiles as $file) {
        echo "<li>" . $file . "</li>";
    }
    echo "</ul>";
}

echo "<h2>Prochaines étapes</h2>";
echo "<p>Vérifiez que le fichier <code>/views/admin/documents.php</code> existe et fonctionne correctement.</p>";
echo "<p>Si ce fichier n'existe pas, vous devez le créer. Voici un exemple de contenu:</p>";

$exampleContent = '<?php
/**
 * Vue principale pour la gestion des documents
 */

// Initialiser les variables
$pageTitle = "Gestion des documents";
$currentPage = "documents";

// Inclure le fichier d\'initialisation
require_once __DIR__ . "/../../includes/init.php";

// Vérifier les permissions
requireRole([\'admin\', \'coordinator\']);

// Instancier le contrôleur
$documentController = new DocumentController($db);

// Récupérer le paramètre de catégorie s\'il existe
$category = isset($_GET[\'category\']) ? $_GET[\'category\'] : null;

// Afficher la liste des documents
$documentController->index($category);
?>';

echo "<pre style='background-color: #f8f9fa; padding: 10px; border: 1px solid #dee2e6;'>" . htmlspecialchars($exampleContent) . "</pre>";

echo "<p><a href='/tutoring/views/admin/documents.php' class='btn btn-primary'>Tester la redirection</a></p>";
?>