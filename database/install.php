<?php
/**
 * Script d'installation de la base de données
 * Ce script crée la base de données et les tables nécessaires pour le système de tutorat
 */

// Configuration de la base de données
$config = [
    'host' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'tutoring_system'
];

// Fonction pour afficher les messages
function displayMessage($message, $type = 'info') {
    $bgColor = ($type == 'error') ? '#f8d7da' : '#d4edda';
    $textColor = ($type == 'error') ? '#721c24' : '#155724';
    echo "<div style='background-color: {$bgColor}; color: {$textColor}; padding: 10px; margin: 10px 0; border-radius: 5px;'>{$message}</div>";
}

// Fonction pour exécuter un script SQL
function executeSqlScript($pdo, $sqlFilePath) {
    try {
        // Lire le fichier SQL
        $sql = file_get_contents($sqlFilePath);
        
        if (!$sql) {
            throw new Exception("Impossible de lire le fichier SQL: {$sqlFilePath}");
        }
        
        // Remplacer les délimiteurs de procédures stockées si nécessaire
        $sql = str_replace('DELIMITER $$', '', $sql);
        $sql = str_replace('DELIMITER ;', '', $sql);
        $sql = str_replace('$$', ';', $sql);
        
        // Diviser le script SQL en instructions individuelles
        $statements = explode(';', $sql);
        
        // Exécuter chaque instruction
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                $pdo->exec($statement);
            }
        }
        
        return true;
    } catch (Exception $e) {
        throw new Exception("Erreur lors de l'exécution du script SQL: " . $e->getMessage());
    }
}

// Vérifier si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les valeurs du formulaire
    $config['host'] = $_POST['host'] ?? 'localhost';
    $config['username'] = $_POST['username'] ?? 'root';
    $config['password'] = $_POST['password'] ?? '';
    $config['database'] = $_POST['database'] ?? 'tutoring_system';
    
    try {
        // Connexion au serveur MySQL (sans sélectionner de base de données)
        $pdo = new PDO("mysql:host={$config['host']}", $config['username'], $config['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Créer la base de données si elle n'existe pas
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$config['database']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        displayMessage("Base de données '{$config['database']}' créée ou déjà existante.");
        
        // Sélectionner la base de données
        $pdo->exec("USE `{$config['database']}`");
        
        // Chemin vers le fichier SQL principal
        $sqlFilePath = __DIR__ . '/tutoring_system.sql';
        
        // Exécuter le script SQL principal
        executeSqlScript($pdo, $sqlFilePath);
        displayMessage("Structure de la base de données et données initiales importées avec succès.");
        
        // Créer le fichier de configuration
        $configFilePath = __DIR__ . '/../config/database.php';
        $configContent = "<?php\n";
        $configContent .= "// Fichier de configuration de la base de données généré automatiquement\n";
        $configContent .= "return [\n";
        $configContent .= "    'host' => '{$config['host']}',\n";
        $configContent .= "    'username' => '{$config['username']}',\n";
        $configContent .= "    'password' => '{$config['password']}',\n";
        $configContent .= "    'database' => '{$config['database']}'\n";
        $configContent .= "];\n";
        
        // Vérifier si le dossier config existe, sinon le créer
        if (!is_dir(__DIR__ . '/../config')) {
            mkdir(__DIR__ . '/../config', 0755, true);
        }
        
        // Écrire le fichier de configuration
        file_put_contents($configFilePath, $configContent);
        displayMessage("Fichier de configuration généré: {$configFilePath}");
        
        // Succès de l'installation
        displayMessage("Installation terminée avec succès !");
        
    } catch (PDOException $e) {
        displayMessage("Erreur de connexion à la base de données: " . $e->getMessage(), 'error');
    } catch (Exception $e) {
        displayMessage($e->getMessage(), 'error');
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation du système de tutorat</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        h1, h2 {
            color: #333;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
        .info-box {
            background-color: #e7f3fe;
            border-left: 5px solid #2196F3;
            padding: 15px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <h1>Installation du système de tutorat</h1>
    
    <div class="info-box">
        <p>Ce script va créer la base de données et importer les tables nécessaires pour le système de tutorat.</p>
        <p>Assurez-vous que:</p>
        <ul>
            <li>Le serveur MySQL/MariaDB est en cours d'exécution</li>
            <li>L'utilisateur spécifié a les droits nécessaires pour créer une base de données</li>
            <li>Le fichier tutoring_system.sql est présent dans le dossier database</li>
        </ul>
    </div>
    
    <h2>Configuration de la base de données</h2>
    
    <form method="post" action="">
        <div class="form-group">
            <label for="host">Hôte:</label>
            <input type="text" id="host" name="host" value="<?php echo $config['host']; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="username">Nom d'utilisateur:</label>
            <input type="text" id="username" name="username" value="<?php echo $config['username']; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="password">Mot de passe:</label>
            <input type="password" id="password" name="password" value="<?php echo $config['password']; ?>">
        </div>
        
        <div class="form-group">
            <label for="database">Nom de la base de données:</label>
            <input type="text" id="database" name="database" value="<?php echo $config['database']; ?>" required>
        </div>
        
        <button type="submit">Installer</button>
    </form>
</body>
</html>