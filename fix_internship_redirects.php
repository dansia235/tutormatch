<?php
/**
 * Script de correction des redirections pour les stages
 * 
 * Ce script remplace les redirections de /views/admin/internships/index.php vers /views/admin/internships.php
 * et crée un fichier de redirection à l'ancien emplacement pour assurer la compatibilité.
 */

// Définir le chemin racine
define('ROOT_PATH', __DIR__);

// Fichier de redirection à créer
$redirectFile = ROOT_PATH . '/views/admin/internships/index.php';

// Contenu du fichier de redirection
$redirectContent = <<<'EOT'
<?php
/**
 * Redirection depuis l'ancien emplacement vers le nouveau
 */

// Rediriger vers la nouvelle page
header('Location: /tutoring/views/admin/internships.php');
exit;
EOT;

// Créer le fichier de redirection
if (file_put_contents($redirectFile, $redirectContent)) {
    echo "✓ Fichier de redirection créé avec succès à {$redirectFile}\n";
} else {
    echo "✗ Échec de la création du fichier de redirection à {$redirectFile}\n";
}

// Fichiers à modifier
$filesToSearch = [
    ROOT_PATH . '/controllers/InternshipController.php',
    ROOT_PATH . '/views/admin/internships/update.php',
    ROOT_PATH . '/views/admin/internships/create.php',
    ROOT_PATH . '/views/admin/internships/delete.php',
    ROOT_PATH . '/views/admin/internships/edit.php',
    ROOT_PATH . '/views/admin/internships/show.php',
];

// Compter les remplacements effectués
$totalReplacements = 0;

// Remplacer les redirections dans les fichiers
foreach ($filesToSearch as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        // Remplacer les redirections
        $newContent = str_replace(
            '/tutoring/views/admin/internships/index.php',
            '/tutoring/views/admin/internships.php',
            $content,
            $count
        );
        
        if ($count > 0) {
            if (file_put_contents($file, $newContent)) {
                echo "✓ {$count} redirections remplacées dans {$file}\n";
                $totalReplacements += $count;
            } else {
                echo "✗ Échec du remplacement des redirections dans {$file}\n";
            }
        } else {
            echo "- Aucune redirection à remplacer dans {$file}\n";
        }
    } else {
        echo "! Fichier non trouvé: {$file}\n";
    }
}

echo "\nOpérations terminées. {$totalReplacements} redirections ont été remplacées au total.\n";
echo "Le fichier de redirection a été créé à {$redirectFile}.\n";
echo "Vous pouvez maintenant supprimer l'ancien fichier index.php si nécessaire.\n";