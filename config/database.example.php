<?php
/**
 * Configuration de la base de données
 * Copiez ce fichier vers database.php et modifiez les paramètres selon votre environnement
 */

return [
    // Hôte de la base de données (généralement localhost pour les environnements locaux)
    'host' => 'localhost',
    
    // Nom d'utilisateur de la base de données
    'username' => 'root',
    
    // Mot de passe de la base de données (souvent vide pour les installations XAMPP locales)
    'password' => '',
    
    // Nom de la base de données
    'database' => 'tutoring_system',
    
    // Port de la base de données (optionnel, par défaut 3306 pour MySQL)
    'port' => 3306,
    
    // Charset à utiliser (utf8mb4 est recommandé pour le support complet des caractères Unicode)
    'charset' => 'utf8mb4',
    
    // Options PDO supplémentaires
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
];
?>