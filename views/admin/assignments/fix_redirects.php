<?php
/**
 * Script pour corriger les chemins de redirection dans le contrôleur d'affectations
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../../includes/init.php';

// Chemin du fichier à modifier
$filePath = __DIR__ . '/../../../controllers/AssignmentController.php';

// Lire le contenu du fichier
$content = file_get_contents($filePath);

// Effectuer les remplacements
$content = str_replace(
    "redirect('/tutoring/admin/assignments/", 
    "redirect('/tutoring/views/admin/assignments/", 
    $content
);

// Sauvegarder le fichier modifié
$success = file_put_contents($filePath, $content) !== false;

// Compter le nombre de remplacements
$count = substr_count($content, "redirect('/tutoring/views/admin/assignments/");

// Préparer les liens de redirection
$backUrl = "/tutoring/views/admin/assignments/generate.php";
$continueUrl = "/tutoring/views/admin/assignments/generate.php";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Correction des chemins de redirection</title>
    <link rel="stylesheet" href="/tutoring/assets/css/bootstrap.css">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Correction des chemins de redirection</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <strong>Succès!</strong> Les chemins de redirection ont été corrigés.
                        </div>
                        <p><?php echo $count; ?> redirections ont été mises à jour dans le fichier AssignmentController.php.</p>
                        <?php else: ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <strong>Erreur!</strong> Impossible de modifier le fichier.
                        </div>
                        <?php endif; ?>
                        
                        <div class="d-flex justify-content-center mt-4">
                            <a href="<?php echo $backUrl; ?>" class="btn btn-secondary me-2">
                                <i class="bi bi-arrow-left me-1"></i> Retour
                            </a>
                            <a href="<?php echo $continueUrl; ?>" class="btn btn-primary">
                                <i class="bi bi-check-circle me-1"></i> Continuer
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>