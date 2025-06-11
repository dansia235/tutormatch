<?php
/**
 * API pour la recherche de tuteurs avec autocomplétion
 * Retourne les tuteurs correspondant au terme de recherche
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../includes/init.php';

// Vérifier que l'utilisateur est connecté
requireLogin();

// Vérifier que la demande est une requête GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

// Récupérer le terme de recherche
$term = isset($_GET['term']) ? trim($_GET['term']) : '';
$availableOnly = isset($_GET['available']) && $_GET['available'] === '1';

// Limiter le nombre de résultats pour les suggestions
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

// Instancier le contrôleur
$teacherController = new TeacherController($db);

// Rechercher les tuteurs
$teachers = $teacherController->search($term, $availableOnly, true);

// Limiter les résultats
$teachers = array_slice($teachers, 0, $limit);

// Formater les résultats pour l'autocomplétion
$results = [];
foreach ($teachers as $teacher) {
    $fullName = ($teacher['title'] ? $teacher['title'] . ' ' : '') . 
                $teacher['first_name'] . ' ' . 
                $teacher['last_name'];
    
    $results[] = [
        'id' => $teacher['id'],
        'value' => $fullName, // Valeur à afficher
        'label' => $fullName . (isset($teacher['department']) ? ' (' . $teacher['department'] . ')' : ''),
        'email' => $teacher['email'],
        'department' => $teacher['department'],
        'specialty' => $teacher['specialty'],
        'url' => '/tutoring/views/admin/teachers/show.php?id=' . $teacher['id']
    ];
}

// Retourner les résultats au format JSON
header('Content-Type: application/json');
echo json_encode($results);
exit;
?>