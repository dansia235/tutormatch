<?php
/**
 * Script pour supprimer les documents de test
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/includes/init.php';

// Vérifier que l'utilisateur est connecté et a les droits d'admin
requireRole(['admin', 'coordinator']);

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Suppression des documents de test</h1>";

// Mots-clés pour identifier les documents de test
$testKeywords = [
    'test',
    'exemple',
    'demo',
    'sample',
    'temporaire'
];

// Créer une requête SQL avec les mots-clés
$keywordConditions = [];
foreach ($testKeywords as $keyword) {
    $keywordConditions[] = "title LIKE :keyword_$keyword OR description LIKE :keyword_$keyword";
}

$sql = "SELECT id, title, description, file_path FROM documents WHERE " . implode(' OR ', $keywordConditions);

try {
    $stmt = $db->prepare($sql);
    
    // Lier les paramètres
    foreach ($testKeywords as $keyword) {
        $param = "%$keyword%";
        $stmt->bindParam(":keyword_$keyword", $param);
    }
    
    $stmt->execute();
    $testDocuments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Documents de test trouvés: " . count($testDocuments) . "</p>";
    
    if (count($testDocuments) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Titre</th><th>Description</th><th>Chemin du fichier</th><th>Action</th></tr>";
        
        foreach ($testDocuments as $doc) {
            echo "<tr>";
            echo "<td>" . $doc['id'] . "</td>";
            echo "<td>" . htmlspecialchars($doc['title']) . "</td>";
            echo "<td>" . htmlspecialchars($doc['description'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($doc['file_path']) . "</td>";
            echo "<td>";
            echo "<form method='post' style='display:inline;'>";
            echo "<input type='hidden' name='action' value='delete'>";
            echo "<input type='hidden' name='id' value='" . $doc['id'] . "'>";
            echo "<input type='submit' value='Supprimer' style='color:red;'>";
            echo "</form>";
            echo "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        // Ajouter un formulaire pour supprimer tous les documents de test
        echo "<form method='post' style='margin-top: 20px;'>";
        echo "<input type='hidden' name='action' value='delete_all'>";
        echo "<input type='submit' value='Supprimer tous les documents de test' style='color:red; font-weight:bold;'>";
        echo "</form>";
    }
    
    // Traiter les actions de suppression
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        if ($_POST['action'] === 'delete' && isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            
            // Récupérer le chemin du fichier
            $stmt = $db->prepare("SELECT file_path FROM documents WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $document = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($document) {
                // Supprimer le fichier physique
                $filePath = ROOT_PATH . $document['file_path'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                    echo "<p style='color:green;'>Fichier physique supprimé: " . htmlspecialchars($document['file_path']) . "</p>";
                } else {
                    echo "<p style='color:orange;'>Fichier physique non trouvé: " . htmlspecialchars($document['file_path']) . "</p>";
                }
                
                // Supprimer l'enregistrement dans la base de données
                $stmt = $db->prepare("DELETE FROM documents WHERE id = :id");
                $stmt->bindParam(':id', $id);
                $result = $stmt->execute();
                
                if ($result) {
                    echo "<p style='color:green;'>Document #$id supprimé avec succès.</p>";
                    echo "<script>setTimeout(function() { window.location.reload(); }, 1500);</script>";
                } else {
                    echo "<p style='color:red;'>Erreur lors de la suppression du document #$id.</p>";
                }
            }
        } elseif ($_POST['action'] === 'delete_all') {
            $deletedCount = 0;
            
            foreach ($testDocuments as $doc) {
                // Supprimer le fichier physique
                $filePath = ROOT_PATH . $doc['file_path'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                
                // Supprimer l'enregistrement dans la base de données
                $stmt = $db->prepare("DELETE FROM documents WHERE id = :id");
                $stmt->bindParam(':id', $doc['id']);
                $result = $stmt->execute();
                
                if ($result) {
                    $deletedCount++;
                }
            }
            
            echo "<p style='color:green;'>$deletedCount documents de test supprimés avec succès.</p>";
            echo "<script>setTimeout(function() { window.location.reload(); }, 1500);</script>";
        }
    }
    
} catch (PDOException $e) {
    echo "<p style='color:red;'>Erreur de base de données: " . $e->getMessage() . "</p>";
}

// Ajouter un lien vers la page de gestion des documents
echo "<p><a href='/tutoring/views/admin/documents.php'>Retour à la gestion des documents</a></p>";
?>