<?php
/**
 * Script pour corriger les chemins vers les fichiers de documents
 */

// Définir le chemin racine du système
define('ROOT_PATH', dirname(__FILE__));

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Correction des chemins de documents</h1>";

// Créer une redirection à l'ancien emplacement vers le nouveau
function createRedirectionFile($oldPath, $newPath) {
    // Créer le répertoire parent si nécessaire
    $parentDir = dirname($oldPath);
    if (!file_exists($parentDir)) {
        if (!mkdir($parentDir, 0755, true)) {
            echo "<p style='color:red;'>Impossible de créer le répertoire: $parentDir</p>";
            return false;
        }
    }

    // Contenu du fichier de redirection
    $content = "<?php
/**
 * Redirection vers la nouvelle page de documents
 */
header('Location: $newPath');
exit;
";

    // Écrire le fichier
    if (file_put_contents($oldPath, $content) === false) {
        echo "<p style='color:red;'>Impossible de créer le fichier de redirection: $oldPath</p>";
        return false;
    }

    echo "<p style='color:green;'>Fichier de redirection créé: $oldPath</p>";
    return true;
}

// 1. Créer des redirections pour les fichiers manquants
$redirections = [
    ROOT_PATH . '/documents/create.php' => '/tutoring/views/admin/documents/create.php',
    ROOT_PATH . '/documents/store.php' => '/tutoring/views/admin/documents/store.php',
    ROOT_PATH . '/documents/edit.php' => '/tutoring/views/admin/documents/edit.php',
    ROOT_PATH . '/documents/update.php' => '/tutoring/views/admin/documents/update.php',
    ROOT_PATH . '/documents/show.php' => '/tutoring/views/admin/documents/show.php',
    ROOT_PATH . '/documents/delete.php' => '/tutoring/views/admin/documents/delete.php',
    ROOT_PATH . '/documents/download.php' => '/tutoring/views/admin/documents/download.php',
    ROOT_PATH . '/documents/my-documents.php' => '/tutoring/views/admin/documents/my-documents.php',
    ROOT_PATH . '/documents/index.php' => '/tutoring/views/admin/documents.php'
];

foreach ($redirections as $oldPath => $newPath) {
    createRedirectionFile($oldPath, $newPath);
}

// 2. Corriger les références dans le DocumentController
$controllerPath = ROOT_PATH . '/controllers/DocumentController.php';
if (file_exists($controllerPath)) {
    // Lire le contenu du fichier
    $content = file_get_contents($controllerPath);
    if ($content === false) {
        echo "<p style='color:red;'>Impossible de lire le fichier {$controllerPath}</p>";
    } else {
        // Créer une sauvegarde
        $backupPath = $controllerPath . '.backup.' . date('Ymd_His');
        if (file_put_contents($backupPath, $content) === false) {
            echo "<p style='color:red;'>Impossible de créer une sauvegarde pour {$controllerPath}</p>";
        } else {
            // Recherche et remplacements
            $replacements = [
                'redirect(\'/tutoring/documents/create.php\')' => 'redirect(\'/tutoring/views/admin/documents/create.php\')',
                'redirect(\'/tutoring/documents/create.php?related_id=' => 'redirect(\'/tutoring/views/admin/documents/create.php?related_id=',
                'redirect(\'/tutoring/documents/edit.php?id=' => 'redirect(\'/tutoring/views/admin/documents/edit.php?id=',
                'redirect(\'/tutoring/documents/my-documents.php\')' => 'redirect(\'/tutoring/views/admin/documents/my-documents.php\')'
            ];
            
            $newContent = $content;
            
            foreach ($replacements as $search => $replace) {
                $newContent = str_replace($search, $replace, $newContent);
            }
            
            // Si des modifications ont été effectuées
            if ($newContent !== $content) {
                // Enregistrer les modifications
                if (file_put_contents($controllerPath, $newContent) === false) {
                    echo "<p style='color:red;'>Impossible d'enregistrer les modifications pour {$controllerPath}</p>";
                } else {
                    echo "<p style='color:green;'>DocumentController.php mis à jour avec succès.</p>";
                }
            } else {
                echo "<p>Aucune modification nécessaire dans DocumentController.php</p>";
            }
        }
    }
}

// Instructions pour tester les modifications
echo "<h2>Prochaines étapes</h2>";
echo "<p>Les redirections et corrections ont été appliquées. Essayez d'ajouter un document en utilisant le lien suivant:</p>";
echo "<p><a href='/tutoring/views/admin/documents/create.php' class='btn btn-primary'>Ajouter un document</a></p>";
?>