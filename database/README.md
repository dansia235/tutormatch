# Installation de la base de données

Ce dossier contient les fichiers nécessaires pour installer et configurer la base de données du système de tutorat.

## Contenu

- `tutoring_system.sql` : Fichier SQL principal contenant la structure de la base de données et les données initiales
- `install.php` : Script d'installation automatisée
- `migrations/` : Dossier contenant les scripts de migration pour les mises à jour de la base de données

## Installation automatique

La méthode recommandée pour installer la base de données est d'utiliser le script d'installation automatique :

1. Assurez-vous que votre serveur web (XAMPP, WAMP, etc.) est en cours d'exécution
2. Ouvrez votre navigateur et accédez à l'URL : `http://localhost/tutoring/database/install.php`
3. Remplissez les informations de connexion à la base de données :
   - Hôte : généralement `localhost`
   - Nom d'utilisateur : généralement `root`
   - Mot de passe : laissez vide si vous utilisez XAMPP par défaut
   - Nom de la base de données : par défaut `tutoring_system`
4. Cliquez sur le bouton "Installer"
5. Le script va créer la base de données, importer les tables et générer le fichier de configuration

## Installation manuelle

Si vous préférez installer la base de données manuellement, suivez ces étapes :

1. Créez une nouvelle base de données dans phpMyAdmin ou via la ligne de commande MySQL :
   ```sql
   CREATE DATABASE tutoring_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. Importez le fichier SQL principal :
   - Via phpMyAdmin : Sélectionnez la base de données créée, cliquez sur "Importer" et sélectionnez le fichier `tutoring_system.sql`
   - Via la ligne de commande :
     ```bash
     mysql -u root -p tutoring_system < tutoring_system.sql
     ```

3. Créez un fichier de configuration dans `config/database.php` :
   ```php
   <?php
   // Fichier de configuration de la base de données
   return [
       'host' => 'localhost',
       'username' => 'root',
       'password' => '',
       'database' => 'tutoring_system'
   ];
   ```

## Structure de la base de données

La base de données comprend les tables principales suivantes :

- `users` : Informations des utilisateurs (étudiants, enseignants, administrateurs)
- `students` : Informations spécifiques aux étudiants
- `teachers` : Informations spécifiques aux enseignants
- `internships` : Offres de stages disponibles
- `companies` : Entreprises proposant des stages
- `assignments` : Affectations des étudiants aux stages et aux tuteurs
- `documents` : Documents liés aux stages et aux étudiants
- `evaluations` : Évaluations des étudiants et des tuteurs
- `messages` : Système de messagerie interne
- `notifications` : Notifications système pour les utilisateurs
- `algorithm_parameters` : Paramètres pour l'algorithme d'affectation
- `algorithm_executions` : Suivi des exécutions de l'algorithme d'affectation

## Maintenance et mise à jour

Pour mettre à jour la structure de la base de données lors des mises à jour du système, utilisez les scripts de migration dans le dossier `migrations/`.

## Dépannage

Si vous rencontrez des problèmes lors de l'installation :

1. Vérifiez que le serveur MySQL/MariaDB est en cours d'exécution
2. Assurez-vous que l'utilisateur a les droits suffisants pour créer des bases de données
3. Vérifiez que le fichier `tutoring_system.sql` n'est pas corrompu
4. Consultez les journaux d'erreurs PHP et MySQL pour plus d'informations