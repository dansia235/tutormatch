<?php
/**
 * Script de test pour l'upload de documents
 */

// Inclure le fichier d'initialisation
require_once __DIR__ . '/includes/init.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    die("Vous devez être connecté pour utiliser ce formulaire.");
}

// Traitement du formulaire
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_document'])) {
    // Vérifier si un fichier a été envoyé
    if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] === UPLOAD_ERR_OK) {
        // Définir les messages d'erreur pour les codes d'erreur d'upload
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'Le fichier dépasse la taille maximale définie dans php.ini',
            UPLOAD_ERR_FORM_SIZE => 'Le fichier dépasse la taille maximale définie dans le formulaire',
            UPLOAD_ERR_PARTIAL => 'Le fichier n\'a été que partiellement téléchargé',
            UPLOAD_ERR_NO_FILE => 'Aucun fichier n\'a été téléchargé',
            UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant',
            UPLOAD_ERR_CANT_WRITE => 'Échec de l\'écriture du fichier sur le disque',
            UPLOAD_ERR_EXTENSION => 'Une extension PHP a arrêté le téléchargement du fichier'
        ];
        
        // Activer l'affichage des erreurs pour le débogage
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
        
        // Utiliser le chemin absolu pour le dossier d'upload
        $uploadDir = ROOT_PATH . '/uploads/documents/';
        
        // Debug - Afficher des informations sur le dossier de destination
        error_log("Tentative de création du dossier : " . $uploadDir);
        error_log("Le dossier existe : " . (is_dir($uploadDir) ? 'Oui' : 'Non'));
        error_log("Permissions du dossier parent : " . substr(sprintf('%o', fileperms(dirname($uploadDir))), -4));
        
        // Créer le répertoire s'il n'existe pas - avec permissions plus permissives
        if (!is_dir($uploadDir)) {
            $mkdirResult = mkdir($uploadDir, 0777, true);
            error_log("Résultat de mkdir : " . ($mkdirResult ? 'Succès' : 'Échec'));
            if (!$mkdirResult) {
                error_log("Erreur lors de la création du dossier : " . $uploadDir);
                error_log("Message d'erreur : " . error_get_last()['message']);
                $message = 'Erreur lors de la création du dossier de destination';
                $messageType = 'danger';
            }
            
            // Vérifier si le dossier a été créé
            if (!is_dir($uploadDir)) {
                error_log("Le dossier n'a pas été créé malgré le succès de mkdir");
                $message = 'Le dossier de destination n\'a pas pu être créé';
                $messageType = 'danger';
            }
            
            // Essayer de changer les permissions explicitement
            chmod($uploadDir, 0777);
        }
        
        // Générer un nom de fichier unique
        $fileName = time() . '_' . basename($_FILES['document_file']['name']);
        $filePath = $uploadDir . $fileName;
        
        error_log("Tentative de déplacement du fichier vers : " . $filePath);
        error_log("Fichier temporaire existe : " . (file_exists($_FILES['document_file']['tmp_name']) ? 'Oui' : 'Non'));
        
        // Déplacer le fichier téléchargé
        if (move_uploaded_file($_FILES['document_file']['tmp_name'], $filePath)) {
            error_log("Fichier déplacé avec succès");
            
            // Vérifier que le fichier a bien été déplacé
            if (!file_exists($filePath)) {
                error_log("Le fichier n'existe pas après déplacement : " . $filePath);
                $message = 'Le fichier a été déplacé mais n\'est pas accessible';
                $messageType = 'danger';
            } else {
                // Préparer les données pour l'enregistrement en BDD
                $documentData = [
                    'title' => $_POST['document_title'],
                    'description' => $_POST['document_description'] ?? null,
                    'file_path' => 'uploads/documents/' . $fileName,
                    'file_type' => $_FILES['document_file']['type'],
                    'file_size' => $_FILES['document_file']['size'],
                    'type' => $_POST['document_type'],
                    'user_id' => $_SESSION['user_id'],
                    'status' => 'submitted',
                    'version' => '1.0'
                ];
                
                error_log("Données du document à créer : " . json_encode($documentData));
                
                // Créer le document dans la base de données
                $documentModel = new Document($db);
                
                // Activer le mode exception pour PDO
                $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                $documentId = $documentModel->create($documentData);
                if ($documentId) {
                    error_log("Document créé avec succès, ID : " . $documentId);
                    $message = 'Document téléversé avec succès (ID: ' . $documentId . ')';
                    $messageType = 'success';
                    
                    // Récupérer et afficher le document créé
                    $document = $documentModel->getById($documentId);
                    error_log("Document récupéré : " . json_encode($document));
                } else {
                    error_log("Échec de la création du document en BDD");
                    $message = 'Erreur lors de l\'enregistrement du document dans la base de données';
                    $messageType = 'danger';
                    
                    // Supprimer le fichier si l'enregistrement en BDD a échoué
                    if (file_exists($filePath)) {
                        unlink($filePath);
                        error_log("Fichier supprimé après échec BDD : " . $filePath);
                    }
                }
            }
        } else {
            error_log("Échec du déplacement du fichier");
            error_log("Message d'erreur : " . error_get_last()['message']);
            $message = 'Erreur lors du déplacement du fichier téléchargé';
            $messageType = 'danger';
        }
    } else {
        // Détail de l'erreur d'upload
        $errorCode = isset($_FILES['document_file']) ? $_FILES['document_file']['error'] : 'No file';
        error_log("Erreur d'upload de fichier, code : " . $errorCode);
        
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'Le fichier dépasse la taille maximale définie dans php.ini',
            UPLOAD_ERR_FORM_SIZE => 'Le fichier dépasse la taille maximale définie dans le formulaire',
            UPLOAD_ERR_PARTIAL => 'Le fichier n\'a été que partiellement téléchargé',
            UPLOAD_ERR_NO_FILE => 'Aucun fichier n\'a été téléchargé',
            UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant',
            UPLOAD_ERR_CANT_WRITE => 'Échec de l\'écriture du fichier sur le disque',
            UPLOAD_ERR_EXTENSION => 'Une extension PHP a arrêté le téléchargement du fichier'
        ];
        
        $errorMessage = isset($_FILES['document_file']) ? 
            ($errorMessages[$_FILES['document_file']['error']] ?? 'Erreur inconnue lors du téléchargement') : 
            'Aucun fichier n\'a été envoyé';
            
        $message = $errorMessage;
        $messageType = 'danger';
    }
}

// Vérifier la structure de la table documents
$tableStructure = '';
try {
    $query = "DESCRIBE documents";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $tableStructure .= "<table class='table table-striped'>";
    $tableStructure .= "<thead><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr></thead>";
    $tableStructure .= "<tbody>";
    
    foreach ($columns as $column) {
        $tableStructure .= "<tr>";
        foreach ($column as $key => $value) {
            $tableStructure .= "<td>" . htmlspecialchars($value ?? '') . "</td>";
        }
        $tableStructure .= "</tr>";
    }
    
    $tableStructure .= "</tbody></table>";
} catch (Exception $e) {
    $tableStructure = "<div class='alert alert-danger'>Erreur lors de la récupération de la structure de la table: " . $e->getMessage() . "</div>";
}

// Vérifier si AUTO_INCREMENT est activé
$autoIncrementStatus = '';
try {
    $query = "SHOW CREATE TABLE documents";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result && isset($result['Create Table'])) {
        $isAutoIncrement = strpos($result['Create Table'], 'AUTO_INCREMENT') !== false;
        if ($isAutoIncrement) {
            $autoIncrementStatus = "<div class='alert alert-success'>La colonne ID est configurée en AUTO_INCREMENT.</div>";
        } else {
            $autoIncrementStatus = "<div class='alert alert-danger'>La colonne ID n'est PAS configurée en AUTO_INCREMENT. <a href='fix_documents_table.php' class='alert-link'>Cliquez ici pour corriger</a></div>";
        }
    }
} catch (Exception $e) {
    $autoIncrementStatus = "<div class='alert alert-danger'>Erreur lors de la vérification de l'auto-incrémentation: " . $e->getMessage() . "</div>";
}

// Afficher la page HTML
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test d'upload de document</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-4">
        <h1 class="mb-4">Test d'upload de document</h1>
        
        <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $messageType; ?>" role="alert">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Formulaire d'upload</h5>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="document_title" class="form-label">Titre du document</label>
                                <input type="text" class="form-control" id="document_title" name="document_title" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="document_type" class="form-label">Type de document</label>
                                <select class="form-select" id="document_type" name="document_type" required>
                                    <option value="report">Rapport</option>
                                    <option value="contract">Contrat</option>
                                    <option value="evaluation">Évaluation</option>
                                    <option value="certificate">Certificat</option>
                                    <option value="other">Autre</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="document_file" class="form-label">Fichier</label>
                                <input type="file" class="form-control" id="document_file" name="document_file" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="document_description" class="form-label">Description (optionnelle)</label>
                                <textarea class="form-control" id="document_description" name="document_description" rows="3"></textarea>
                            </div>
                            
                            <button type="submit" name="upload_document" class="btn btn-primary">Téléverser</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Structure de la table documents</h5>
                    </div>
                    <div class="card-body">
                        <?php echo $autoIncrementStatus; ?>
                        <?php echo $tableStructure; ?>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Liens utiles</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group">
                            <li class="list-group-item">
                                <a href="check_errors.php" target="_blank">Vérifier les erreurs PHP</a>
                            </li>
                            <li class="list-group-item">
                                <a href="fix_documents_table.php" target="_blank">Corriger la structure de la table documents</a>
                            </li>
                            <li class="list-group-item">
                                <a href="views/student/documents.php">Page de documents étudiant normale</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>