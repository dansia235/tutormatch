<?php
/**
 * Script pour corriger les redirections des documents
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/includes/init.php';

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Correction des redirections des documents</h1>";

// Créer une version modifiée du DocumentController
class DocumentControllerFixed extends DocumentController {
    
    /**
     * Version corrigée de la méthode update
     */
    public function updateFixed($id) {
        // Vérifier les permissions
        requireLogin();
        
        // Vérifier le jeton CSRF
        if (!verifyCsrfToken($_POST['csrf_token'])) {
            setFlashMessage('error', 'Erreur de sécurité. Veuillez réessayer.');
            redirect('/tutoring/views/admin/documents/edit.php?id=' . $id);
            return;
        }
        
        // Récupérer le document
        $document = $this->documentModel->getById($id);
        
        if (!$document) {
            setFlashMessage('error', 'Document non trouvé');
            redirect('/tutoring/views/admin/documents/index.php');
            return;
        }
        
        // Vérifier si l'utilisateur peut modifier ce document
        if ($document['user_id'] !== $_SESSION['user_id'] && !hasRole(['admin', 'coordinator'])) {
            setFlashMessage('error', "Vous n'êtes pas autorisé à modifier ce document");
            redirect('/tutoring/views/admin/documents/show.php?id=' . $id);
            return;
        }
        
        // Valider les données
        $errors = [];
        
        if (empty($_POST['title'])) {
            $errors[] = "Le titre est requis";
        }
        
        if (empty($_POST['category'])) {
            $errors[] = "La catégorie est requise";
        }
        
        // S'il y a des erreurs, rediriger avec les erreurs
        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_data'] = $_POST;
            redirect('/tutoring/views/admin/documents/edit.php?id=' . $id);
            return;
        }
        
        // Préparer les données
        $documentData = [
            'title' => $_POST['title'],
            'description' => $_POST['description'] ?? null,
            'type' => $_POST['category'], // Convertir 'category' en 'type' pour correspondre à la structure de la BDD
            'visibility' => $_POST['visibility'] ?? 'private',
            'status' => $_POST['status'] ?? 'submitted' // Utiliser un statut valide: 'draft','submitted','approved','rejected'
        ];
        
        // Traiter le nouveau fichier si fourni
        if (isset($_FILES['document']) && $_FILES['document']['error'] == 0) {
            // Définir les types de fichiers autorisés selon la catégorie
            $allowedTypes = [];
            
            switch ($_POST['category']) {
                case 'contract':
                    $allowedTypes = [
                        'application/pdf', 
                        'application/msword', 
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                    ];
                    break;
                    
                case 'report':
                    $allowedTypes = [
                        'application/pdf', 
                        'application/msword', 
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                    ];
                    break;
                    
                case 'evaluation':
                    $allowedTypes = [
                        'application/pdf',
                        'application/msword', 
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                    ];
                    break;
                    
                case 'certificate':
                    $allowedTypes = [
                        'application/pdf',
                        'image/jpeg', 
                        'image/png'
                    ];
                    break;
                    
                case 'other':
                default:
                    $allowedTypes = [
                        'application/pdf', 
                        'application/msword', 
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-powerpoint',
                        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                        'text/plain',
                        'application/zip',
                        'application/x-rar-compressed',
                        'image/jpeg', 
                        'image/png', 
                        'image/gif'
                    ];
                    break;
            }
            
            // Déterminer le sous-dossier selon la catégorie
            $destination = 'documents/' . $_POST['category'];
            
            // Télécharger le fichier
            $filePath = uploadFile($_FILES['document'], $destination, $allowedTypes);
            
            if (!$filePath) {
                setFlashMessage('error', "Erreur lors de l'upload du fichier. Vérifiez le format et la taille.");
                redirect('/tutoring/views/admin/documents/edit.php?id=' . $id);
                return;
            }
            
            // Supprimer l'ancien fichier
            deleteFile($document['file_path']);
            
            // Mettre à jour les informations du fichier
            $documentData['file_path'] = $filePath;
            $documentData['file_type'] = $_FILES['document']['type'];
            $documentData['file_size'] = $_FILES['document']['size'];
        }
        
        // Mettre à jour le document
        $success = $this->documentModel->update($id, $documentData);
        
        if ($success) {
            setFlashMessage('success', 'Document mis à jour avec succès');
            
            // Rediriger vers la page de détails (CORRIGER LE CHEMIN)
            if (hasRole(['admin', 'coordinator'])) {
                redirect('/tutoring/views/admin/documents/show.php?id=' . $id);
            } elseif (hasRole('teacher')) {
                redirect('/tutoring/views/tutor/documents/show.php?id=' . $id);
            } else {
                redirect('/tutoring/views/student/documents/show.php?id=' . $id);
            }
        } else {
            setFlashMessage('error', "Erreur lors de la mise à jour du document");
            redirect('/tutoring/views/admin/documents/edit.php?id=' . $id);
        }
    }
}

echo "<h2>Installation du correctif de redirection des documents</h2>";

// Vérifier si les fichiers de vue existent
$adminShowPath = ROOT_PATH . '/views/admin/documents/show.php';
if (!file_exists($adminShowPath)) {
    echo "<p style='color: red;'>Le fichier '{$adminShowPath}' n'existe pas!</p>";
} else {
    echo "<p style='color: green;'>Le fichier '{$adminShowPath}' existe.</p>";
}

// Tester la mise à jour d'un document
try {
    // Récupérer un document existant pour le test
    $query = "SELECT id FROM documents LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $testDocId = $stmt->fetchColumn();
    
    if ($testDocId) {
        echo "<p>Document trouvé pour test: ID {$testDocId}</p>";
        
        // Instancier le contrôleur modifié
        $fixedController = new DocumentControllerFixed($db);
        
        // Simuler une requête POST pour la mise à jour
        $_POST = [
            'id' => $testDocId,
            'title' => 'Document test - correctif de redirection',
            'description' => 'Test de la redirection corrigée',
            'category' => 'other',
            'visibility' => 'private',
            'status' => 'submitted',
            'csrf_token' => generateCsrfToken()
        ];
        
        echo "<p>Voici les chemins de redirection possibles après la mise à jour :</p>";
        echo "<ul>";
        echo "<li>" . "/tutoring/views/admin/documents/show.php?id={$testDocId}" . "</li>";
        echo "<li>" . "/tutoring/views/tutor/documents/show.php?id={$testDocId}" . "</li>";
        echo "<li>" . "/tutoring/views/student/documents/show.php?id={$testDocId}" . "</li>";
        echo "</ul>";
        
        echo "<p>Pour corriger ce problème, vous avez besoin de :</p>";
        echo "<ol>";
        echo "<li>Remplacer la méthode update() dans DocumentController.php pour corriger les chemins de redirection</li>";
        echo "<li>Vérifier que les fichiers show.php existent dans les dossiers correspondants</li>";
        echo "<li>Créer les fichiers manquants si nécessaire</li>";
        echo "</ol>";
        
        echo "<p>Téléchargez et appliquez le correctif pour DocumentController.php :</p>";
        echo "<a href='fix_document_controller.php' class='btn btn-primary'>Appliquer le correctif</a>";
    } else {
        echo "<p style='color: orange;'>Aucun document trouvé pour le test.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Exception: " . $e->getMessage() . "</p>";
}

// Expliquer comment utiliser ce script pour les administrateurs
echo "<div style='margin-top: 20px; padding: 15px; border: 1px solid #ccc; background-color: #f8f9fa;'>";
echo "<h3>Instructions pour corriger le problème de redirection</h3>";
echo "<p>Ce script identifie le problème de redirection après la mise à jour d'un document.</p>";
echo "<p>Pour corriger le problème, vous avez deux options :</p>";
echo "<ol>";
echo "<li>Corriger les chemins de redirection dans le fichier DocumentController.php</li>";
echo "<li>Créer les fichiers de vue manquants dans les dossiers appropriés</li>";
echo "</ol>";

echo "<h4>Solution recommandée :</h4>";
echo "<p>Modifiez DocumentController.php, ligne 502 (méthode update) pour corriger le chemin de redirection :</p>";
echo "<pre>// Au lieu de :
redirect('/tutoring/documents/show.php?id=' . \$id);

// Utilisez :
if (hasRole(['admin', 'coordinator'])) {
    redirect('/tutoring/views/admin/documents/show.php?id=' . \$id);
} elseif (hasRole('teacher')) {
    redirect('/tutoring/views/tutor/documents/show.php?id=' . \$id);
} else {
    redirect('/tutoring/views/student/documents/show.php?id=' . \$id);
}</pre>";
echo "</div>";

// Fournir des liens utiles
echo "<div style='margin-top: 20px;'>";
echo "<a href='/tutoring/views/admin/documents/index.php' class='btn btn-primary'>Retour à la liste des documents</a> ";
echo "<a href='/tutoring/views/admin/dashboard.php' class='btn btn-secondary'>Tableau de bord administrateur</a>";
echo "</div>";
?>