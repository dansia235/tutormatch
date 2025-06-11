<?php
/**
 * Script pour corriger le problème de transaction dans AssignmentController
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/includes/init.php';

// Vérifier que l'utilisateur est connecté et admin
requireRole('admin');

// Lecture du fichier original
$originalFilePath = __DIR__ . '/controllers/AssignmentController.php';
$originalContent = file_get_contents($originalFilePath);

if ($originalContent === false) {
    die("Impossible de lire le fichier original.");
}

// Lecture du code corrigé
$fixFilePath = __DIR__ . '/controllers/AssignmentController_fix.php';
$fixContent = file_get_contents($fixFilePath);

if ($fixContent === false) {
    die("Impossible de lire le fichier de correction.");
}

// Extraction de la méthode generateAssignments corrigée
preg_match('/public function generateAssignments\(\).*?}$/s', $fixContent, $matches);
if (empty($matches)) {
    die("Impossible de trouver la méthode corrigée.");
}
$correctedMethod = $matches[0];

// Remplacement de la méthode dans le fichier original
$pattern = '/public function generateAssignments\(\).*?}(?=\s+\/\*\*|\s+private|\s+public|\s+}$)/s';
$result = preg_replace($pattern, $correctedMethod, $originalContent);

if ($result === null) {
    die("Erreur lors du remplacement de la méthode.");
}

// Sauvegarde du fichier original
$backupFilePath = $originalFilePath . '.bak.' . date('YmdHis');
if (!file_put_contents($backupFilePath, $originalContent)) {
    die("Impossible de créer une sauvegarde du fichier original.");
}

// Écriture du fichier corrigé
if (!file_put_contents($originalFilePath, $result)) {
    die("Impossible d'écrire le fichier corrigé.");
}

echo "<h2>Correction appliquée avec succès!</h2>";
echo "<p>Une sauvegarde du fichier original a été créée: " . basename($backupFilePath) . "</p>";
echo "<p>Vous pouvez maintenant <a href='/tutoring/views/admin/assignments/generate.php'>générer des affectations</a>.</p>";
?>