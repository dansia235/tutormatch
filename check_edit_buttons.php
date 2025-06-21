<?php
/**
 * Script pour vérifier les boutons sur la page d'édition des stages
 */

// Initialisation
require_once __DIR__ . '/includes/init.php';

// Vérifier si l'utilisateur est connecté et a les droits
if (!isset($_SESSION['user_id']) || !hasRole(['admin', 'coordinator'])) {
    echo "Vous devez être connecté avec des droits d'administration pour accéder à cette page.";
    exit;
}

// ID du stage à vérifier
$internshipId = 1; // Utiliser l'ID spécifié dans votre URL

// Instancier le modèle
$internshipModel = new Internship($db);

// Récupérer le stage
$internship = $internshipModel->getById($internshipId);

if (!$internship) {
    echo "Stage non trouvé avec l'ID: " . $internshipId;
    exit;
}

// Vérifier que les boutons sont présents dans le HTML
echo "<h1>Vérification des boutons sur la page d'édition des stages</h1>";
echo "<p>Stage testé: " . htmlspecialchars($internship['title']) . " (ID: " . $internshipId . ")</p>";

echo "<h2>HTML des boutons</h2>";
echo "<pre>";
echo htmlspecialchars('
<div class="d-flex justify-content-between mt-4">
    <a href="/tutoring/views/admin/internships.php" class="btn btn-secondary">Annuler</a>
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-save me-2"></i>Enregistrer les modifications
    </button>
</div>
');
echo "</pre>";

echo "<h2>Styles CSS</h2>";
echo "<p>Vérifiez s'il y a des styles CSS qui pourraient masquer ces boutons:</p>";
echo "<ul>";
echo "<li>Recherchez des règles CSS qui pourraient affecter .btn-primary ou .btn-secondary</li>";
echo "<li>Vérifiez si la classe .d-flex ou .justify-content-between est modifiée</li>";
echo "<li>Vérifiez si d'autres classes Bootstrap sont surchargées</li>";
echo "</ul>";

echo "<h2>Test de rendu des boutons</h2>";
echo "<div class='d-flex justify-content-between mt-4'>";
echo "<a href='#' class='btn btn-secondary'>Test Annuler</a>";
echo "<button type='button' class='btn btn-primary'><i class='bi bi-save me-2'></i>Test Enregistrer</button>";
echo "</div>";

echo "<h2>Vérifiez également</h2>";
echo "<ul>";
echo "<li>Inspectez les éléments avec les outils de développement du navigateur</li>";
echo "<li>Vérifiez si les boutons sont présents dans le DOM mais masqués par CSS</li>";
echo "<li>Assurez-vous que le formulaire n'est pas fermé prématurément</li>";
echo "</ul>";
?>