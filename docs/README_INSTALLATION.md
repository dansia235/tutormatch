# Guide d'Installation - Système de Tutorat PHP

## Configuration de la Base de Données

### 1. Prérequis
- MySQL installé (via XAMPP ou autre)
- PHP 7.4 ou supérieur
- Serveur web (Apache/Nginx)

### 2. Installation de la base de données

#### Option 1: Si l'utilisateur MySQL 'dansia' n'existe pas encore
```bash
php setup_database_root.php
```

Ce script va :
- Se connecter avec l'utilisateur root MySQL
- Créer l'utilisateur 'dansia' avec le mot de passe 'dansia'
- Créer la base de données et toutes les tables
- Insérer des données de test

#### Option 2: Si l'utilisateur 'dansia' existe déjà
```bash
php setup_database.php
```

#### Option 3: Via phpMyAdmin
1. Ouvrez phpMyAdmin (http://localhost/phpmyadmin)
2. Exécutez le contenu du fichier `create_mysql_user.sql`
3. Puis exécutez `php setup_database.php`

### Solutions aux erreurs courantes

#### Erreur "Access denied for user 'dansia'"

#### Solution rapide (recommandée) :
```bash
php fix_database_access.php
```
Ce script corrige automatiquement tous les problèmes d'accès.

#### Créer le compte admin :
```bash
php create_admin_account.php
```

#### Via phpMyAdmin :
1. Ouvrez phpMyAdmin (http://localhost/phpmyadmin)
2. Exécutez le fichier `create_admin_sql.sql`

#### Manuellement :
```bash
php setup_database_root.php
```

#### Erreur "Access denied for user 'root'"
- Modifiez la ligne 9 dans `setup_database_root.php` avec votre mot de passe root
- Par défaut XAMPP: mot de passe vide ('')

#### Mise à jour vers authentification par username
Si vous avez déjà une base de données existante, exécutez :
```bash
php add_username_column.php
```

### 3. Comptes utilisateurs disponibles (authentification par username)

#### Administrateur
- Username: `admin`
- Mot de passe: `admin123`

#### Coordinateur
- Username: `test`
- Mot de passe: `test123`

#### Enseignants
- Marie Dupont: username `marie` / `password123`
- Jean Martin: username `jean` / `password123`
- Sophie Bernard: username `sophie` / `password123`

#### Étudiants
- Lucas Moreau: username `lucas` / `password123`
- Emma Petit: username `emma` / `password123`
- Thomas Robert: username `thomas` / `password123`

### 4. Configuration MySQL

Le système utilise **exclusivement MySQL** comme SGBD. Les identifiants configurés sont :
- **Host**: `127.0.0.1`
- **Port**: `3306`
- **Utilisateur**: `dansia`
- **Mot de passe**: `dansia`
- **Base de données**: `tutoring_system`
- **Charset**: `utf8mb4`

Les fichiers de configuration :
- `/backend-php/config/database.php` (configuration principale)
- `/api.php` (API simplifiée)
- Tous les scripts d'installation

**Note**: PostgreSQL et Redis ont été supprimés du système pour simplifier la configuration.

### 5. Accès à l'application

1. Assurez-vous que XAMPP est démarré
2. Accédez à : `http://localhost/tutoring-system-php/`
3. Utilisez la nouvelle page de connexion avec username : `http://localhost/tutoring-system-php/login.php`
4. Connectez-vous avec l'un des usernames ci-dessus

## Fonctionnalités de la page de connexion

La page de connexion a été améliorée avec :
- Design moderne avec effet glassmorphism
- Animations fluides et attractives
- Icônes intuitives pour les champs
- Messages d'erreur/succès stylisés
- Responsive design
- Effets visuels au survol et au focus

## Scripts de vérification

### Test de connexion MySQL
```bash
php test_mysql_connection.php
```
Ce script vérifie :
- Connexion au serveur MySQL
- Existence de l'utilisateur dansia
- Présence de la base de données
- Tables principales
- Fonctionnement de l'API

### Nettoyage de la configuration
```bash
php cleanup_database_config.php
```
Vérifie que le système utilise uniquement MySQL.

## Dépannage

### Erreur de connexion MySQL
1. Vérifiez que MySQL est démarré dans XAMPP
2. Testez avec : `php test_mysql_connection.php`
3. Créez l'utilisateur : `php setup_database_root.php`

### Page blanche
- Vérifiez les logs PHP dans XAMPP
- Assurez-vous que toutes les extensions PHP requises sont activées

### Erreur 404
- Vérifiez que le projet est bien dans le dossier htdocs de XAMPP
- L'URL doit être exactement : `http://localhost/tutoring-system-php/`