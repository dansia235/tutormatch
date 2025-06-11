# Gestion des connexions à la base de données

Ce document explique comment résoudre les erreurs de type "Too many connections" dans l'application de tutorat.

## Problème identifié

L'erreur "SQLSTATE[08004] [1040] Too many connections" se produit lorsque le serveur MySQL atteint sa limite maximale de connexions simultanées. Cela peut être dû à :

1. Un nombre trop important de connexions ouvertes non fermées
2. Une limite de connexions trop basse sur le serveur MySQL
3. Des fuites de connexions dans le code

## Solutions implémentées

### 1. Pattern Singleton pour la connexion

Le fichier `config/database.php` a été modifié pour implémenter un pattern Singleton qui assure qu'une seule connexion PDO est créée et réutilisée pour toute la durée d'exécution d'un script.

```php
function getDBConnection() {
    static $pdo = null;
    
    if ($pdo !== null) {
        return $pdo;
    }
    
    // Création de la connexion...
}
```

### 2. Connexions persistantes

Les connexions persistantes ont été activées pour réduire les frais de création/fermeture de connexions :

```php
$options = [
    // ...
    PDO::ATTR_PERSISTENT => true,
];
```

### 3. Fermeture automatique des connexions

Un mécanisme de fermeture automatique a été ajouté à la fin de chaque script via `register_shutdown_function()` :

```php
register_shutdown_function(function() {
    global $db;
    if ($db) {
        $db = null; // Libérer la connexion
    }
});
```

### 4. Meilleure gestion des transactions

Une classe `BaseModel` a été créée pour implémenter des méthodes sécurisées de gestion des transactions :

```php
protected function beginTransactionSafe() {
    if (!$this->db->inTransaction()) {
        $this->db->beginTransaction();
        $this->transactionStartedHere = true;
        return true;
    }
    return false;
}

protected function commitSafe() {
    if ($this->transactionStartedHere && $this->db->inTransaction()) {
        $this->db->commit();
        $this->transactionStartedHere = false;
        return true;
    }
    return false;
}
```

### 5. Vérification de la connexion dans les scripts de vues

Tous les scripts de vues qui utilisent la base de données vérifient maintenant si la connexion est disponible :

```php
// S'assurer que la connexion à la base de données est disponible
if (!isset($db) || $db === null) {
    $db = getDBConnection();
}
```

## Configuration du serveur MySQL

Si vous continuez à rencontrer des erreurs "Too many connections", vous pouvez augmenter la limite de connexions sur le serveur MySQL :

1. Un script SQL a été créé pour vérifier et augmenter temporairement la limite :
   ```
   /mnt/c/xampp/htdocs/tutoring/database/increase_max_connections.sql
   ```

2. Pour une modification permanente, modifiez le fichier de configuration MySQL (`my.cnf` ou `my.ini`) :
   ```
   [mysqld]
   max_connections = 200
   ```

3. Redémarrez ensuite le serveur MySQL pour appliquer les changements.

## Bonnes pratiques à suivre

1. **Toujours fermer les connexions** à la fin des scripts
2. **Éviter les connexions multiples** dans un même script
3. **Utiliser les transactions avec précaution** et s'assurer qu'elles sont toujours soit validées, soit annulées
4. **Préférer la classe BaseModel** pour les opérations de base de données

## Surveillance et monitoring

Si les problèmes persistent, envisagez de mettre en place une surveillance des connexions actives :

```sql
SHOW STATUS WHERE Variable_name = 'Threads_connected';
SHOW PROCESSLIST;
```

Ces commandes vous aideront à identifier les scripts qui consomment trop de connexions.