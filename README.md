# TutorMatch - Système de Gestion d'Attribution des Stages

TutorMatch est une application web complète pour la gestion des stages et l'attribution de tuteurs aux étudiants. Ce système permet aux établissements d'enseignement de gérer efficacement le processus d'affectation des tuteurs aux stages des étudiants, avec des algorithmes d'optimisation pour garantir les meilleures correspondances possibles.

## Fonctionnalités principales

- **Gestion des utilisateurs** : Administrateurs, coordinateurs, tuteurs et étudiants
- **Gestion des stages** : Ajout, modification et suivi des offres de stage
- **Gestion des entreprises** : Base de données des entreprises partenaires
- **Système de préférences** : Les étudiants peuvent classer leurs stages préférés, les tuteurs peuvent définir leurs préférences
- **Algorithmes d'affectation** : Différents algorithmes pour optimiser l'attribution des tuteurs
- **Suivi des stages** : Documents, réunions, évaluations et rapports
- **Messagerie intégrée** : Communication entre tuteurs et étudiants
- **Tableaux de bord** : Statistiques et indicateurs de performance
- **Génération de rapports** : Rapports d'affectation, statistiques, etc.

## Architecture technique

- **Backend** : PHP 8+
- **Base de données** : MySQL
- **Frontend** : HTML5, CSS3, JavaScript
- **Frameworks** : Bootstrap 5
- **Bibliothèques JS** : Chart.js, Flatpickr, etc.
- **Sécurité** : Authentification, autorisations basées sur les rôles, protection CSRF

## Structure du projet

```
tutoring/
├── assets/
│   ├── css/
│   ├── js/
│   └── img/
├── config/
├── controllers/
├── database/
├── includes/
├── models/
├── uploads/
└── views/
    ├── admin/
    ├── common/
    ├── student/
    └── tutor/
```

## Installation

1. Cloner le dépôt dans votre dossier web (par exemple, htdocs pour XAMPP)
2. Créer une base de données MySQL
3. Importer le fichier SQL de structure : `database/create_database.sql`
4. Configurer les informations de connexion dans `config/database.php`
5. Lancer l'application dans votre navigateur

### Mise à jour de la base de données

Si vous mettez à jour une installation existante, suivez ces étapes :

1. Assurez-vous d'avoir une sauvegarde de votre base de données actuelle
2. Accédez à la page `/update_database.php` depuis votre navigateur
3. Vérifiez les modifications qui seront appliquées à votre base de données
4. Confirmez pour exécuter les mises à jour

Pour plus de détails sur les changements de structure de la base de données, consultez le guide de migration :
`docs/DATABASE_MIGRATION_GUIDE.md`

### Prérequis

- PHP 8.0 ou supérieur
- MySQL 5.7 ou supérieur
- Serveur web (Apache, Nginx)
- Extensions PHP : PDO, PDO_MySQL, mbstring, json

### Utilisateurs par défaut

- **Administrateur** : admin / admin123
- **Coordinateur** : test / test123
- **Tuteur** : marie / password123
- **Étudiant** : lucas / password123

## Algorithmes d'affectation

Le système propose différents algorithmes d'affectation :

1. **Algorithme Glouton** : Rapide mais sous-optimal, attribue les étudiants dans l'ordre
2. **Algorithme Hongrois** : Trouve la solution optimale globale
3. **Algorithme Génétique** : Optimise une solution existante avec une approche évolutive
4. **Algorithme Hybride** : Combine plusieurs approches pour un équilibre performance/qualité

## Contribution

Les contributions à ce projet sont les bienvenues. Veuillez suivre ces étapes :

1. Forker le dépôt
2. Créer une branche pour votre fonctionnalité (`git checkout -b feature/amazing-feature`)
3. Effectuer vos modifications
4. Commit vos changements (`git commit -m 'Add some amazing feature'`)
5. Push vers la branche (`git push origin feature/amazing-feature`)
6. Ouvrir une Pull Request

## Licence

Ce projet est sous licence MIT. Voir le fichier `LICENSE` pour plus de détails.

## Contact

Pour toute question ou suggestion, veuillez contacter l'équipe de développement.