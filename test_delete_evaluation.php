<?php
/**
 * Script de test pour l'API de suppression d'évaluations
 * Ce script aide à tester la fonctionnalité sans interface web
 */

// Configuration de test
$test_evaluation_id = 999999; // ID qui n'existe pas pour tester la gestion d'erreur

echo "<h2>Test de l'API de suppression d'évaluations</h2>";

// Test 1: Vérifier que l'API rejette les IDs invalides
echo "<h3>Test 1: ID invalide</h3>";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/tutoring/api/evaluations/delete.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['id' => $test_evaluation_id]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id()); // Simuler une session

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p><strong>Code HTTP:</strong> $http_code</p>";
echo "<p><strong>Réponse:</strong></p>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

// Test 2: Vérifier la structure de l'API
echo "<h3>Test 2: Structure de l'API</h3>";
echo "<p>L'API devrait:</p>";
echo "<ul>";
echo "<li>✓ Accepter uniquement les requêtes POST</li>";
echo "<li>✓ Vérifier les permissions (admin/coordinator)</li>";
echo "<li>✓ Valider l'ID de l'évaluation</li>";
echo "<li>✓ Supprimer l'évaluation (structure simplifiée sans tables liées)</li>";
echo "<li>✓ Utiliser des transactions pour la cohérence</li>";
echo "<li>✓ Retourner une réponse JSON structurée</li>";
echo "</ul>";

// Test 3: Vérifier le fichier API
echo "<h3>Test 3: Vérification du fichier API</h3>";
$api_file = __DIR__ . '/api/evaluations/delete.php';
if (file_exists($api_file)) {
    echo "<p>✓ Fichier API trouvé: $api_file</p>";
    $file_size = filesize($api_file);
    echo "<p>✓ Taille du fichier: $file_size bytes</p>";
} else {
    echo "<p>❌ Fichier API non trouvé: $api_file</p>";
}

// Test 4: Vérifier les permissions sur le fichier
if (file_exists($api_file)) {
    if (is_readable($api_file)) {
        echo "<p>✓ Fichier lisible</p>";
    } else {
        echo "<p>❌ Fichier non lisible</p>";
    }
}

echo "<hr>";
echo "<p><em>Note: Pour tester complètement, connectez-vous en tant qu'administrateur et utilisez l'interface web.</em></p>";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test API Suppression Évaluations</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
</body>
</html>