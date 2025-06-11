<?php
/**
 * Script pour créer les tables nécessaires à la génération d'affectations
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../../includes/init.php';

// Vérifier si les tables existent déjà
function tableExists($db, $tableName) {
    try {
        $result = $db->query("SHOW TABLES LIKE '$tableName'");
        return $result->rowCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}

// Créer les tables si elles n'existent pas
$messages = [];
$success = true;

try {
    // Vérifier et créer la table algorithm_parameters
    if (!tableExists($db, 'algorithm_parameters')) {
        $sql = "CREATE TABLE IF NOT EXISTS `algorithm_parameters` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL,
            `description` text,
            `algorithm_type` varchar(50) NOT NULL,
            `department_weight` int(11) NOT NULL DEFAULT '50',
            `preference_weight` int(11) NOT NULL DEFAULT '30',
            `capacity_weight` int(11) NOT NULL DEFAULT '20',
            `allow_cross_department` tinyint(1) NOT NULL DEFAULT '0',
            `prioritize_preferences` tinyint(1) NOT NULL DEFAULT '1',
            `balance_workload` tinyint(1) NOT NULL DEFAULT '1',
            `is_default` tinyint(1) NOT NULL DEFAULT '0',
            `created_at` datetime NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $db->exec($sql);
        $messages[] = "Table algorithm_parameters créée avec succès.";
        
        // Insérer des données par défaut
        $sql = "INSERT INTO algorithm_parameters 
            (name, description, algorithm_type, department_weight, preference_weight, capacity_weight, 
            allow_cross_department, prioritize_preferences, balance_workload, is_default, created_at) 
            VALUES 
            ('Paramètres par défaut', 'Paramètres initiaux', 'greedy', 50, 30, 20, 0, 1, 1, 1, NOW())";
        $db->exec($sql);
        $messages[] = "Paramètres par défaut ajoutés.";
    } else {
        $messages[] = "La table algorithm_parameters existe déjà.";
    }
    
    // Vérifier et créer la table algorithm_executions
    if (!tableExists($db, 'algorithm_executions')) {
        $sql = "CREATE TABLE IF NOT EXISTS `algorithm_executions` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `parameters_id` int(11) NOT NULL,
            `executed_by` int(11) NOT NULL,
            `execution_time` float NOT NULL,
            `students_count` int(11) NOT NULL,
            `teachers_count` int(11) NOT NULL,
            `assignments_count` int(11) NOT NULL,
            `unassigned_count` int(11) NOT NULL,
            `average_satisfaction` float NOT NULL,
            `notes` text,
            `executed_at` datetime NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $db->exec($sql);
        $messages[] = "Table algorithm_executions créée avec succès.";
    } else {
        $messages[] = "La table algorithm_executions existe déjà.";
    }
} catch (PDOException $e) {
    $success = false;
    $messages[] = "Erreur : " . $e->getMessage();
}

// Préparer les liens de redirection
$backUrl = "/tutoring/views/admin/assignments/generate.php";
$continueUrl = "/tutoring/views/admin/assignments/generate.php";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuration des tables d'affectation</title>
    <link rel="stylesheet" href="/tutoring/assets/css/bootstrap.css">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Configuration des tables d'affectation</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <strong>Succès!</strong> Les tables ont été vérifiées et créées si nécessaire.
                        </div>
                        <?php else: ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <strong>Erreur!</strong> Un problème est survenu lors de la création des tables.
                        </div>
                        <?php endif; ?>
                        
                        <h5 class="mt-3 mb-2">Détails:</h5>
                        <ul class="list-group mb-4">
                            <?php foreach ($messages as $message): ?>
                            <li class="list-group-item"><?php echo htmlspecialchars($message); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        
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