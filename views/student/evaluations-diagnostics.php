<?php
/**
 * Script de diagnostic pour la page d'évaluations des étudiants
 * Ce script vérifie les composants nécessaires au bon fonctionnement
 */

// Activer le débogage
define('DEBUG', true);

// Titre de la page
$pageTitle = 'Diagnostic des évaluations';
$currentPage = 'diagnostics';

// Inclure le fichier d'initialisation
require_once __DIR__ . '/../../includes/init.php';

// Vérifier que l'utilisateur est étudiant
requireRole('student');

echo '<!DOCTYPE html>
<html>
<head>
    <title>Diagnostic des évaluations</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .diagnostic-item { margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 15px; }
        .status-ok { color: green; }
        .status-warning { color: orange; }
        .status-error { color: red; }
    </style>
</head>
<body class="bg-light">
    <div class="container py-4">
        <h1 class="mb-4">Diagnostic de la page d\'évaluations</h1>
        <div class="card">
            <div class="card-body">
                <h2 class="card-title">Résultats du diagnostic</h2>';

// Récupérer l'utilisateur
$user_id = $_SESSION['user_id'] ?? null;

// Fonction pour afficher un élément de diagnostic
function showDiagnostic($title, $result, $details = null, $status = 'ok') {
    $statusClass = 'status-' . $status;
    echo '<div class="diagnostic-item">';
    echo '<h5>' . htmlspecialchars($title) . ' <span class="' . $statusClass . '">' . ($status == 'ok' ? '✓' : ($status == 'warning' ? '⚠️' : '✗')) . '</span></h5>';
    
    if ($details !== null) {
        if (is_array($details) || is_object($details)) {
            echo '<pre>' . htmlspecialchars(print_r($details, true)) . '</pre>';
        } else {
            echo '<p>' . htmlspecialchars($details) . '</p>';
        }
    }
    
    echo '</div>';
}

// 1. Vérifier la connexion à la base de données
try {
    $testQuery = $db->query("SELECT 1");
    showDiagnostic("Connexion à la base de données", true, "La connexion est établie avec succès");
} catch (PDOException $e) {
    showDiagnostic("Connexion à la base de données", false, "Erreur: " . $e->getMessage(), 'error');
}

// 2. Vérifier que les classes nécessaires existent
$requiredClasses = ['Student', 'Evaluation', 'Document'];
foreach ($requiredClasses as $class) {
    if (class_exists($class)) {
        showDiagnostic("Classe $class", true, "La classe existe et est chargée");
    } else {
        $classFile = ROOT_PATH . '/models/' . $class . '.php';
        if (file_exists($classFile)) {
            showDiagnostic("Classe $class", false, "Le fichier existe mais la classe n'est pas chargée: $classFile", 'warning');
        } else {
            showDiagnostic("Classe $class", false, "Fichier inexistant: $classFile", 'error');
        }
    }
}

// 3. Vérifier que les tables nécessaires existent
$requiredTables = ['students', 'users', 'evaluations', 'documents', 'assignments'];
foreach ($requiredTables as $table) {
    try {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->rowCount() > 0;
        
        if ($exists) {
            $countStmt = $db->query("SELECT COUNT(*) FROM $table");
            $count = $countStmt->fetchColumn();
            showDiagnostic("Table $table", true, "La table existe et contient $count enregistrements");
        } else {
            showDiagnostic("Table $table", false, "La table n'existe pas", 'error');
        }
    } catch (PDOException $e) {
        showDiagnostic("Table $table", false, "Erreur: " . $e->getMessage(), 'error');
    }
}

// 4. Vérifier l'utilisateur étudiant
if ($user_id) {
    try {
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            showDiagnostic("Utilisateur", true, "Utilisateur trouvé: " . $user['username']);
            
            // Vérifier le profil étudiant
            $stmt = $db->prepare("SELECT * FROM students WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($student) {
                showDiagnostic("Profil étudiant", true, "Étudiant trouvé: ID=" . $student['id']);
                
                // Vérifier les affectations
                $stmt = $db->prepare("SELECT * FROM assignments WHERE student_id = ?");
                $stmt->execute([$student['id']]);
                $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($assignments)) {
                    showDiagnostic("Affectations", true, $assignments);
                    
                    // Vérifier les évaluations
                    foreach ($assignments as $assignment) {
                        $stmt = $db->prepare("SELECT * FROM evaluations WHERE assignment_id = ?");
                        $stmt->execute([$assignment['id']]);
                        $evaluations = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        if (!empty($evaluations)) {
                            showDiagnostic("Évaluations pour l'affectation " . $assignment['id'], true, $evaluations);
                        } else {
                            showDiagnostic("Évaluations pour l'affectation " . $assignment['id'], false, "Aucune évaluation trouvée", 'warning');
                        }
                    }
                } else {
                    showDiagnostic("Affectations", false, "Aucune affectation trouvée pour l'étudiant", 'warning');
                }
                
                // Vérifier les documents
                $stmt = $db->prepare("SELECT * FROM documents WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($documents)) {
                    $evaluationDocs = array_filter($documents, function($doc) {
                        return isset($doc['type']) && in_array($doc['type'], ['evaluation', 'self_evaluation', 'mid_term', 'final']);
                    });
                    
                    if (!empty($evaluationDocs)) {
                        showDiagnostic("Documents d'évaluation", true, $evaluationDocs);
                    } else {
                        showDiagnostic("Documents d'évaluation", false, "Aucun document d'évaluation trouvé", 'warning');
                    }
                } else {
                    showDiagnostic("Documents", false, "Aucun document trouvé pour l'étudiant", 'warning');
                }
                
            } else {
                showDiagnostic("Profil étudiant", false, "Aucun profil étudiant trouvé pour cet utilisateur", 'error');
            }
        } else {
            showDiagnostic("Utilisateur", false, "Utilisateur non trouvé", 'error');
        }
    } catch (PDOException $e) {
        showDiagnostic("Requêtes de diagnostic", false, "Erreur: " . $e->getMessage(), 'error');
    }
} else {
    showDiagnostic("Utilisateur", false, "Aucun ID utilisateur en session", 'error');
}

echo '
            </div>
            <div class="card-footer">
                <a href="evaluations.php" class="btn btn-primary">Retourner à la page d\'évaluations</a>
                <button onclick="window.location.reload()" class="btn btn-secondary">Actualiser le diagnostic</button>
            </div>
        </div>
        
        <div class="mt-4">
            <div class="alert alert-info">
                <h4>Que faire en cas d\'erreur?</h4>
                <ol>
                    <li>Vérifiez que toutes les tables nécessaires existent dans la base de données</li>
                    <li>Assurez-vous que les modèles sont correctement définis et accessibles</li>
                    <li>Vérifiez les permissions sur les fichiers et dossiers</li>
                    <li>Consultez les logs PHP pour plus de détails sur les erreurs</li>
                </ol>
            </div>
        </div>
    </div>
</body>
</html>';