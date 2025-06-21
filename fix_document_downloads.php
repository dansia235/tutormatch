<?php
/**
 * Script pour créer les fichiers de téléchargement de documents pour chaque rôle
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/includes/init.php';

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Création des fichiers de téléchargement de documents</h1>";

// Contenu du fichier de téléchargement
$downloadFileContent = '<?php
/**
 * Script de téléchargement de document
 */

// Inclure le fichier d\'initialisation
require_once __DIR__ . "/../../../includes/init.php";

// Vérifier que l\'utilisateur est connecté
requireLogin();

// Vérifier l\'ID
if (!isset($_GET[\'id\']) || !is_numeric($_GET[\'id\'])) {
    setFlashMessage(\'error\', \'ID de document invalide\');
    redirect("/tutoring/views/ROLE_PLACEHOLDER/documents/index.php");
    exit;
}

// Instancier le contrôleur
$documentController = new DocumentController($db);

// Traiter le téléchargement
$documentController->download($_GET[\'id\']);
';

// Chemins des fichiers à créer
$downloadFiles = [
    'admin' => ROOT_PATH . '/views/admin/documents/download.php',
    'tutor' => ROOT_PATH . '/views/tutor/documents/download.php',
    'student' => ROOT_PATH . '/views/student/documents/download.php'
];

// Créer les fichiers
foreach ($downloadFiles as $role => $path) {
    $directory = dirname($path);
    
    // Créer le répertoire s'il n'existe pas
    if (!file_exists($directory)) {
        mkdir($directory, 0755, true);
        echo "<p>Répertoire créé : {$directory}</p>";
    }
    
    // Remplacer le placeholder par le rôle
    $content = str_replace('ROLE_PLACEHOLDER', $role, $downloadFileContent);
    
    // Écrire le fichier
    if (file_put_contents($path, $content) !== false) {
        echo "<p style='color: green;'>Fichier créé : {$path}</p>";
    } else {
        echo "<p style='color: red;'>Erreur lors de la création du fichier : {$path}</p>";
    }
}

// Contenu du fichier de suppression
$deleteFileContent = '<?php
/**
 * Script de suppression de document
 */

// Inclure le fichier d\'initialisation
require_once __DIR__ . "/../../../includes/init.php";

// Vérifier que l\'utilisateur est connecté
requireLogin();

// Vérifier que le formulaire a été soumis
if ($_SERVER[\'REQUEST_METHOD\'] !== \'POST\') {
    setFlashMessage(\'error\', \'Méthode non autorisée\');
    redirect("/tutoring/views/ROLE_PLACEHOLDER/documents/index.php");
    exit;
}

// Vérifier l\'ID
if (!isset($_POST[\'id\']) || !is_numeric($_POST[\'id\'])) {
    setFlashMessage(\'error\', \'ID de document invalide\');
    redirect("/tutoring/views/ROLE_PLACEHOLDER/documents/index.php");
    exit;
}

// Instancier le contrôleur
$documentController = new DocumentController($db);

// Traiter la suppression
$documentController->delete($_POST[\'id\']);
';

// Chemins des fichiers de suppression à créer
$deleteFiles = [
    'admin' => ROOT_PATH . '/views/admin/documents/delete.php',
    'tutor' => ROOT_PATH . '/views/tutor/documents/delete.php',
    'student' => ROOT_PATH . '/views/student/documents/delete.php'
];

// Créer les fichiers de suppression
foreach ($deleteFiles as $role => $path) {
    $directory = dirname($path);
    
    // Créer le répertoire s'il n'existe pas
    if (!file_exists($directory)) {
        mkdir($directory, 0755, true);
        echo "<p>Répertoire créé : {$directory}</p>";
    }
    
    // Remplacer le placeholder par le rôle
    $content = str_replace('ROLE_PLACEHOLDER', $role, $deleteFileContent);
    
    // Écrire le fichier
    if (file_put_contents($path, $content) !== false) {
        echo "<p style='color: green;'>Fichier créé : {$path}</p>";
    } else {
        echo "<p style='color: red;'>Erreur lors de la création du fichier : {$path}</p>";
    }
}

echo "<h2>Application du correctif au contrôleur DocumentController</h2>";

// Chemin vers le fichier du contrôleur
$controllerPath = ROOT_PATH . '/controllers/DocumentController.php';

// Lire le contenu du fichier
$content = file_get_contents($controllerPath);

// Faire une sauvegarde
$backupPath = $controllerPath . '.backup.' . date('Ymd_His');
file_put_contents($backupPath, $content);
echo "<p>Sauvegarde créée : {$backupPath}</p>";

// Rechercher et remplacer les redirections
$patterns = [
    // Redirection après mise à jour réussie
    '/redirect\(\'\/tutoring\/documents\/show\.php\?id=\' \. \$id\);/' => 
    'if (hasRole([\'admin\', \'coordinator\'])) {
                redirect(\'/tutoring/views/admin/documents/show.php?id=\' . $id);
            } elseif (hasRole(\'teacher\')) {
                redirect(\'/tutoring/views/tutor/documents/show.php?id=\' . $id);
            } else {
                redirect(\'/tutoring/views/student/documents/show.php?id=\' . $id);
            }',
    
    // Redirection après échec de mise à jour
    '/redirect\(\'\/tutoring\/documents\/edit\.php\?id=\' \. \$id\);/' => 
    'if (hasRole([\'admin\', \'coordinator\'])) {
                redirect(\'/tutoring/views/admin/documents/edit.php?id=\' . $id);
            } elseif (hasRole(\'teacher\')) {
                redirect(\'/tutoring/views/tutor/documents/edit.php?id=\' . $id);
            } else {
                redirect(\'/tutoring/views/student/documents/edit.php?id=\' . $id);
            }',
    
    // Redirection après suppression
    '/redirect\(\'\/tutoring\/documents\/index\.php\'\);/' =>
    'if (hasRole([\'admin\', \'coordinator\'])) {
                redirect(\'/tutoring/views/admin/documents/index.php\');
            } elseif (hasRole(\'teacher\')) {
                redirect(\'/tutoring/views/tutor/documents/index.php\');
            } else {
                redirect(\'/tutoring/views/student/documents.php\');
            }'
];

$modifiedContent = $content;

foreach ($patterns as $pattern => $replacement) {
    $modifiedContent = preg_replace($pattern, $replacement, $modifiedContent);
}

// Enregistrer le fichier modifié
if (file_put_contents($controllerPath, $modifiedContent)) {
    echo "<p style='color: green;'>Contrôleur mis à jour avec succès</p>";
} else {
    echo "<p style='color: red;'>Erreur lors de la mise à jour du contrôleur</p>";
}

// Vérifier s'il faut créer les fichiers d'édition
$editFiles = [
    'tutor' => ROOT_PATH . '/views/tutor/documents/edit.php',
    'student' => ROOT_PATH . '/views/student/documents/edit.php'
];

echo "<h2>Vérification des fichiers d'édition</h2>";

foreach ($editFiles as $role => $path) {
    if (!file_exists($path)) {
        echo "<p style='color: orange;'>Le fichier d'édition est manquant pour {$role} : {$path}</p>";
        echo "<p>Vous devrez créer ce fichier manuellement ou en utilisant un autre script.</p>";
    } else {
        echo "<p style='color: green;'>Le fichier d'édition existe pour {$role}</p>";
    }
}

// Vérifier s'il faut créer les fichiers d'index
$indexFiles = [
    'tutor' => ROOT_PATH . '/views/tutor/documents/index.php',
];

echo "<h2>Vérification des fichiers d'index</h2>";

foreach ($indexFiles as $role => $path) {
    if (!file_exists($path)) {
        echo "<p style='color: orange;'>Le fichier d'index est manquant pour {$role} : {$path}</p>";
        echo "<p>Vous devrez créer ce fichier manuellement ou en utilisant un autre script.</p>";
    } else {
        echo "<p style='color: green;'>Le fichier d'index existe pour {$role}</p>";
    }
}

echo "<h2>Test d'un document</h2>";

try {
    // Récupérer un document de test
    $query = "SELECT id FROM documents LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $docId = $stmt->fetchColumn();
    
    if ($docId) {
        $testUrls = [
            'admin' => "/tutoring/views/admin/documents/show.php?id={$docId}",
            'tutor' => "/tutoring/views/tutor/documents/show.php?id={$docId}",
            'student' => "/tutoring/views/student/documents/show.php?id={$docId}"
        ];
        
        echo "<p>Vous pouvez tester les URLs suivantes :</p>";
        echo "<ul>";
        foreach ($testUrls as $role => $url) {
            echo "<li><a href='{$url}' target='_blank'>{$role}: {$url}</a></li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: orange;'>Aucun document trouvé pour tester</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Erreur: " . $e->getMessage() . "</p>";
}

echo "<h2>Résumé</h2>";
echo "<p>Les modifications suivantes ont été effectuées :</p>";
echo "<ol>";
echo "<li>Création des fichiers download.php pour chaque rôle</li>";
echo "<li>Création des fichiers delete.php pour chaque rôle</li>";
echo "<li>Mise à jour des redirections dans le contrôleur DocumentController.php</li>";
echo "<li>Création des fichiers show.php manquants pour les rôles tutor et student</li>";
echo "</ol>";

echo "<p>Pour finaliser la correction :</p>";
echo "<ol>";
echo "<li>Créez les fichiers d'édition manquants pour chaque rôle si nécessaire</li>";
echo "<li>Créez les fichiers d'index manquants pour chaque rôle si nécessaire</li>";
echo "<li>Testez le fonctionnement de l'édition, de l'affichage et de la suppression</li>";
echo "</ol>";

echo "<p><a href='/tutoring/views/admin/documents/index.php' class='btn btn-primary'>Retour à la liste des documents</a></p>";
?>