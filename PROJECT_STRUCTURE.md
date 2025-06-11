# Structure du Projet TutorMatch

Ce document présente la structure des fichiers et dossiers du projet TutorMatch, avec une explication de leur rôle.

## Structure Principale

```
tutoring/
│
├── admin/                             # Administration (interface backend)
│   └── user/                          # Gestion des utilisateurs admin
│
├── api/                               # API REST
│   ├── assignments/                   # Endpoints pour les affectations
│   ├── auth/                          # Authentification API
│   ├── companies/                     # Gestion des entreprises
│   ├── dashboard/                     # Données pour tableaux de bord
│   ├── documents/                     # Gestion des documents
│   ├── evaluations/                   # Système d'évaluation
│   ├── export/                        # Exportation de données (PDF, Excel...)
│   ├── internships/                   # Gestion des stages
│   ├── meetings/                      # Gestion des réunions
│   ├── messages/                      # Système de messagerie
│   ├── notifications/                 # Système de notifications
│   ├── settings/                      # Paramètres du système
│   ├── students/                      # Gestion des étudiants
│   ├── teachers/                      # Gestion des tuteurs
│   └── users/                         # Gestion des utilisateurs
│
├── assets/                            # Ressources front-end
│   ├── components/                    # Composants JS réutilisables
│   ├── css/                           # Feuilles de style CSS
│   ├── img/                           # Images
│   └── js/                            # Scripts JavaScript
│       ├── controllers/               # Contrôleurs Stimulus.js
│       ├── modules/                   # Modules fonctionnels
│       └── services/                  # Services JavaScript
│
├── backups/                           # Sauvegardes
│   ├── message_files_20250609_173418/ # Sauvegarde des fichiers de message
│   ├── sample_data_files_20250609_173750/ # Sauvegarde des données d'exemple
│   └── temp_files_20250609_172421/    # Fichiers temporaires
│
├── benchmarks/                        # Tests de performance
│
├── components/                        # Composants PHP réutilisables
│   ├── cards/                         # Éléments de type carte
│   ├── charts/                        # Graphiques
│   ├── common/                        # Éléments communs
│   ├── dashboard/                     # Éléments du tableau de bord
│   ├── documents/                     # Gestion des documents UI
│   ├── examples/                      # Exemples de composants
│   ├── filters/                       # Filtres et recherche
│   ├── forms/                         # Éléments de formulaire
│   ├── messages/                      # Composants de messagerie
│   ├── modals/                        # Fenêtres modales
│   ├── notifications/                 # Notifications UI
│   └── tables/                        # Tableaux de données
│
├── config/                            # Configuration du système
│   ├── database.php                   # Configuration de la base de données
│   └── database.example.php           # Exemple de configuration
│
├── controllers/                       # Contrôleurs MVC
│   ├── AssignmentController.php       # Contrôleur des affectations
│   ├── DocumentController.php         # Contrôleur des documents
│   ├── InternshipController.php       # Contrôleur des stages
│   ├── StatisticsController.php       # Contrôleur des statistiques
│   ├── StudentController.php          # Contrôleur des étudiants
│   ├── TeacherController.php          # Contrôleur des tuteurs
│   └── UserController.php             # Contrôleur des utilisateurs
│
├── database/                          # Gestion de la base de données
│   ├── migrations/                    # Scripts de migration
│   ├── install.php                    # Script d'installation de la BDD
│   ├── README.md                      # Documentation de la BDD
│   └── tutoring_system.sql            # Structure complète de la BDD
│
├── docs/                              # Documentation
│   ├── Dashboard/                     # Documentation du tableau de bord
│   ├── Etudiant/                      # Documentation de l'interface étudiant
│   ├── API.md                         # Documentation de l'API
│   ├── DATABASE_MIGRATION_GUIDE.md    # Guide de migration de la BDD
│   └── README_INSTALLATION.md         # Guide d'installation
│
├── includes/                          # Fichiers inclus et utilitaires
│   ├── auth.php                       # Fonctions d'authentification
│   ├── init.php                       # Initialisation de l'application
│   ├── JwtUtils.php                   # Utilitaires JWT
│   └── RedirectInterceptor.php        # Gestion des redirections
│
├── models/                            # Modèles de données
│   ├── AlgorithmExecution.php         # Modèle d'exécution d'algorithme
│   ├── AlgorithmParameters.php        # Modèle de paramètres d'algorithme
│   ├── Assignment.php                 # Modèle d'affectation
│   ├── Company.php                    # Modèle d'entreprise
│   ├── Document.php                   # Modèle de document
│   ├── Internship.php                 # Modèle de stage
│   ├── Meeting.php                    # Modèle de réunion
│   ├── Message.php                    # Modèle de message
│   ├── Notification.php               # Modèle de notification
│   ├── Student.php                    # Modèle d'étudiant
│   ├── Teacher.php                    # Modèle de tuteur
│   └── User.php                       # Modèle utilisateur
│
├── public/                            # Fichiers publics
│   └── build/                         # Fichiers compilés
│
├── src/                               # Code source avancé
│   ├── Algorithm/                     # Algorithmes d'affectation
│   │   ├── AssignmentAlgorithmInterface.php  # Interface d'algorithme
│   │   ├── GreedyAlgorithm.php               # Algorithme glouton
│   │   ├── HungarianAlgorithm.php            # Algorithme hongrois
│   │   └── README.md                          # Documentation des algorithmes
│   ├── DTO/                           # Objets de transfert de données
│   │   ├── AssignmentParameters.php           # Paramètres d'affectation
│   │   └── AssignmentResult.php               # Résultats d'affectation
│   └── Service/                       # Services métier
│       └── AssignmentService.php              # Service d'affectation
│
├── templates/                         # Templates généraux
│   ├── components/                    # Composants de template
│   └── layouts/                       # Layouts de page
│       ├── admin.php                  # Layout administrateur
│       ├── admin-bootstrap.php        # Layout admin avec Bootstrap
│       ├── student.php                # Layout étudiant
│       └── tutor.php                  # Layout tuteur
│
├── test_data/                         # Données de test
│
├── tests/                             # Tests unitaires et fonctionnels
│   └── Algorithm/                     # Tests des algorithmes
│       └── GreedyAlgorithmTest.php    # Tests de l'algorithme glouton
│
├── uploads/                           # Fichiers téléchargés
│
├── views/                             # Vues de l'application
│   ├── admin/                         # Interface administrateur
│   │   ├── assignments/               # Gestion des affectations
│   │   ├── documents/                 # Gestion des documents
│   │   ├── internships/               # Gestion des stages
│   │   ├── students/                  # Gestion des étudiants
│   │   ├── teachers/                  # Gestion des tuteurs
│   │   └── user/                      # Gestion des utilisateurs
│   ├── auth/                          # Pages d'authentification
│   ├── common/                        # Éléments communs (header, footer)
│   ├── coordinator/                   # Interface coordinateur
│   ├── student/                       # Interface étudiant
│   └── tutor/                         # Interface tuteur
│
├── .htaccess                          # Configuration Apache
├── CHANGELOG.md                       # Journal des modifications
├── CODE_OF_CONDUCT.md                 # Code de conduite
├── CONTRIBUTING.md                    # Guide de contribution
├── LICENSE                            # Licence MIT
├── README.md                          # Documentation principale
├── TODO-SEARCH-IMPROVEMENTS.md        # Améliorations prévues pour la recherche
├── algorithm_implementation_summary.md # Résumé des algorithmes d'affectation
├── architecture_documentation.md      # Documentation de l'architecture
├── index.php                          # Point d'entrée principal
├── login.php                          # Page de connexion
├── logout.php                         # Déconnexion
├── package.json                       # Dépendances npm
├── phpunit.xml                        # Configuration PHPUnit
├── postcss.config.js                  # Configuration PostCSS
└── webpack.config.js                  # Configuration Webpack
```

## Détails des répertoires principaux

### `/api`
Contient les points d'accès de l'API REST pour permettre l'interaction avec le backend depuis JavaScript. Chaque sous-dossier correspond à une ressource spécifique.

### `/assets`
Ressources front-end (CSS, JavaScript, images). Contient notamment les contrôleurs Stimulus.js qui gèrent les interactions utilisateur.

### `/components`
Composants PHP réutilisables pour la construction de l'interface utilisateur. Ces composants sont utilisés par les différentes vues.

### `/controllers`
Implémente la logique métier selon le modèle MVC. Chaque contrôleur gère une entité spécifique du système.

### `/database`
Contient les scripts SQL pour la création et la migration de la base de données, ainsi qu'un script d'installation automatique.

### `/includes`
Utilitaires partagés et fonctions communes utilisés à travers l'application, notamment l'authentification.

### `/models`
Classes représentant les entités métier et encapsulant l'accès aux données. Suit le pattern Repository.

### `/src`
Code source avancé, notamment les algorithmes d'affectation et les services métier.

### `/views`
Interfaces utilisateur organisées par rôle (admin, tuteur, étudiant). Chaque vue utilise les composants réutilisables et appelle les contrôleurs appropriés.

## Flux d'exécution typique

1. L'utilisateur accède à une URL (ex: `/tutoring/views/admin/assignments.php`)
2. Le fichier `includes/init.php` est inclus, qui initialise l'environnement
3. L'authentification est vérifiée via `includes/auth.php`
4. Le contrôleur approprié est instancié (ex: `AssignmentController`)
5. Une action du contrôleur est appelée (ex: `index()`)
6. Le contrôleur récupère les données via les modèles (ex: `Assignment->getAll()`)
7. La vue est générée, en utilisant les composants réutilisables
8. Le résultat est affiché à l'utilisateur

## Architecture Technique

Le projet suit une architecture MVC (Modèle-Vue-Contrôleur) avec les caractéristiques suivantes :

- **Modèle** : Classes dans `/models` encapsulant l'accès aux données
- **Vue** : Templates PHP dans `/views` et composants dans `/components`
- **Contrôleur** : Classes dans `/controllers` gérant la logique métier

L'application utilise également une API REST (dans `/api`) pour permettre les interactions client-serveur asynchrones, notamment pour les tableaux de bord et les interfaces dynamiques.

Les algorithmes d'affectation sont implémentés sous forme de classes dans `/src/Algorithm`, suivant le pattern Strategy qui permet de changer facilement l'algorithme utilisé.

## Notes sur l'organisation du code

- La séparation claire entre les interfaces des différents rôles utilisateur (admin, tuteur, étudiant) facilite la maintenance et la gestion des permissions.
- L'utilisation de composants réutilisables réduit la duplication de code et assure la cohérence de l'interface.
- L'API REST permet une séparation nette entre le frontend et le backend, facilitant le développement parallèle.
- Le code spécifique aux algorithmes d'affectation est isolé dans `/src/Algorithm`, ce qui facilite les tests et l'implémentation de nouveaux algorithmes.