<?php
/**
 * Script pour corriger les redirections dans le DocumentController
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/includes/init.php';

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Correction du DocumentController</h1>";

// Chemin vers le fichier du contrôleur
$controllerPath = ROOT_PATH . '/controllers/DocumentController.php';

try {
    // Vérifier si le fichier existe
    if (!file_exists($controllerPath)) {
        throw new Exception("Le fichier du contrôleur n'existe pas: {$controllerPath}");
    }
    
    // Lire le contenu du fichier
    $content = file_get_contents($controllerPath);
    if ($content === false) {
        throw new Exception("Impossible de lire le fichier: {$controllerPath}");
    }
    
    echo "<p>Fichier original trouvé et lu.</p>";
    
    // Faire une sauvegarde du fichier
    $backupPath = $controllerPath . '.backup.' . date('Ymd_His');
    if (file_put_contents($backupPath, $content) === false) {
        throw new Exception("Impossible de créer une sauvegarde: {$backupPath}");
    }
    
    echo "<p>Sauvegarde créée: {$backupPath}</p>";
    
    // Remplacer les redirections incorrectes
    $searchStr1 = "redirect('/tutoring/documents/show.php?id=' . \$id);";
    $replaceStr1 = "if (hasRole(['admin', 'coordinator'])) {
                redirect('/tutoring/views/admin/documents/show.php?id=' . \$id);
            } elseif (hasRole('teacher')) {
                redirect('/tutoring/views/tutor/documents/show.php?id=' . \$id);
            } else {
                redirect('/tutoring/views/student/documents/show.php?id=' . \$id);
            }";
    
    $searchStr2 = "redirect('/tutoring/documents/edit.php?id=' . \$id);";
    $replaceStr2 = "if (hasRole(['admin', 'coordinator'])) {
                redirect('/tutoring/views/admin/documents/edit.php?id=' . \$id);
            } elseif (hasRole('teacher')) {
                redirect('/tutoring/views/tutor/documents/edit.php?id=' . \$id);
            } else {
                redirect('/tutoring/views/student/documents/edit.php?id=' . \$id);
            }";
    
    $modifiedContent = str_replace($searchStr1, $replaceStr1, $content);
    $modifiedContent = str_replace($searchStr2, $replaceStr2, $modifiedContent);
    
    // Vérifier si des modifications ont été effectuées
    if ($modifiedContent === $content) {
        echo "<p style='color: orange;'>Aucune modification nécessaire dans le code du contrôleur.</p>";
    } else {
        // Écrire le contenu modifié
        if (file_put_contents($controllerPath, $modifiedContent) === false) {
            throw new Exception("Impossible d'écrire le fichier modifié: {$controllerPath}");
        }
        
        echo "<p style='color: green;'>Contrôleur mis à jour avec succès!</p>";
    }
    
    // Vérifier si les fichiers show.php existent pour chaque rôle
    $showPaths = [
        'admin' => ROOT_PATH . '/views/admin/documents/show.php',
        'tutor' => ROOT_PATH . '/views/tutor/documents/show.php',
        'student' => ROOT_PATH . '/views/student/documents/show.php'
    ];
    
    echo "<h2>Vérification des fichiers de vue</h2>";
    echo "<ul>";
    
    foreach ($showPaths as $role => $path) {
        if (file_exists($path)) {
            echo "<li style='color: green;'>Le fichier pour {$role} existe: {$path}</li>";
        } else {
            echo "<li style='color: red;'>Le fichier pour {$role} est MANQUANT: {$path}</li>";
            
            // Si le dossier parent existe, suggérer de créer le fichier
            $dir = dirname($path);
            if (file_exists($dir)) {
                echo "<p style='margin-left: 20px;'>Le dossier existe, vous devriez créer le fichier manquant.</p>";
            } else {
                echo "<p style='margin-left: 20px;'>Le dossier n'existe pas, vous devriez créer le dossier et le fichier.</p>";
                echo "<pre>mkdir -p {$dir}</pre>";
            }
        }
    }
    
    echo "</ul>";
    
    // Créer les dossiers manquants si nécessaire
    foreach ($showPaths as $role => $path) {
        $dir = dirname($path);
        if (!file_exists($dir)) {
            if (mkdir($dir, 0755, true)) {
                echo "<p style='color: green;'>Dossier créé pour {$role}: {$dir}</p>";
            } else {
                echo "<p style='color: red;'>Impossible de créer le dossier pour {$role}: {$dir}</p>";
            }
        }
    }
    
    // Suggérer de créer des symlinks pour simplifier l'accès
    echo "<h2>Création de symlinks pour simplifier la structure</h2>";
    echo "<p>Pour simplifier la structure, vous pouvez créer des symlinks depuis '/tutoring/documents/' vers les chemins appropriés.</p>";
    echo "<pre>
// Exemple pour Linux/Mac:
ln -s /tutoring/views/admin/documents /tutoring/documents

// Ou en PHP:
<?php
symlink('/tutoring/views/admin/documents', '/tutoring/documents');
?>
</pre>";
    
    // Ajouter un lien pour tester
    echo "<p><a href='/tutoring/views/admin/documents/index.php' class='btn btn-primary'>Tester la liste des documents</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Erreur: " . $e->getMessage() . "</p>";
}
?>