# Structure du Projet TutorMatch

Ce document présente la structure des fichiers et dossiers du projet TutorMatch, avec une explication détaillée de leur rôle et des récentes évolutions.

## Vue d'ensemble

TutorMatch est une application web de gestion des stages académiques construite avec une architecture MVC en PHP, enrichie d'une API REST et de composants JavaScript modernes. Le projet suit une organisation modulaire facilitant la maintenance et l'évolution.

## Structure Principale

```
tutoring/
│
├── admin/                             # Administration (interface backend)
│   └── user/                          # Gestion des utilisateurs admin
│       ├── delete.php                 # Suppression d'utilisateurs
│       ├── store.php                  # Création d'utilisateurs
│       └── update.php                 # Mise à jour d'utilisateurs
│
├── api/                               # API REST complète
│   ├── assignments/                   # Endpoints pour les affectations
│   │   ├── admin-list.php            # Liste pour admin avec filtres
│   │   ├── batch-update.php          # Mise à jour en masse
│   │   ├── create.php                # Création d'affectation
│   │   ├── index.php                 # Liste des affectations
│   │   ├── matrix.php                # Matrice d'affectation
│   │   ├── show.php                  # Détails d'une affectation
│   │   ├── status.php                # Statut des affectations
│   │   └── update.php                # Mise à jour d'affectation
│   │
│   ├── auth/                          # Authentification API
│   │   ├── login.php                 # Connexion utilisateur
│   │   ├── logout.php                # Déconnexion
│   │   ├── record-login.php          # Enregistrement des connexions
│   │   └── refresh.php               # Rafraîchissement de token
│   │
│   ├── companies/                     # Gestion des entreprises (NOUVEAU)
│   │   ├── admin-list.php            # Liste avec recherche et tri
│   │   ├── create.php                # Création d'entreprise
│   │   ├── index.php                 # Liste publique
│   │   ├── show.php                  # Détails d'entreprise
│   │   └── update.php                # Mise à jour d'entreprise
│   │
│   ├── dashboard/                     # Données pour tableaux de bord
│   │   ├── activity.php              # Activités récentes
│   │   ├── api-init.php              # Initialisation API
│   │   ├── assignment-status.php     # Statut des affectations
│   │   ├── assignments-by-department.php # Stats par département
│   │   ├── charts.php                # Données pour graphiques
│   │   ├── internship-status.php     # Statut des stages
│   │   ├── stats.php                 # Statistiques générales
│   │   ├── system-metrics.php        # Métriques système
│   │   ├── system-status.php         # État du système
│   │   ├── tutor-dashboard.php       # Dashboard tuteur
│   │   └── tutor-workload.php        # Charge de travail tuteurs
│   │
│   ├── documents/                     # Gestion des documents
│   │   ├── admin-list.php            # Liste admin
│   │   ├── delete.php                # Suppression
│   │   ├── download.php              # Téléchargement
│   │   ├── index.php                 # Liste générale
│   │   ├── show.php                  # Affichage
│   │   ├── student-list.php          # Documents par étudiant
│   │   ├── tutor-list.php            # Documents par tuteur
│   │   └── upload.php                # Upload de fichiers
│   │
│   ├── evaluations/                   # Système d'évaluation complet
│   │   ├── admin-list.php            # Liste pour admin
│   │   ├── calculate-student-scores.php # Calcul des scores
│   │   ├── create.php                # Création d'évaluation
│   │   ├── get-criteria.php          # Récupération des critères
│   │   ├── get-student-evaluations.php # Évaluations par étudiant
│   │   ├── reports.php               # Rapports d'évaluation
│   │   ├── save-evaluation.php       # Sauvegarde
│   │   ├── stats.php                 # Statistiques
│   │   ├── submit-self-evaluation.php # Auto-évaluation
│   │   └── update.php                # Mise à jour
│   │
│   ├── export/                        # Exportation de données
│   │   ├── assignments.php           # Export des affectations
│   │   ├── internships.php           # Export des stages
│   │   ├── students.php              # Export des étudiants
│   │   └── teachers.php              # Export des tuteurs
│   │
│   ├── internships/                   # Gestion des stages
│   │   ├── admin-list.php            # Liste admin avec filtres
│   │   ├── available.php             # Stages disponibles
│   │   ├── create.php                # Création de stage
│   │   ├── delete.php                # Suppression
│   │   ├── export.php                # Export des données
│   │   ├── index.php                 # Liste générale
│   │   ├── search.php                # Recherche avancée
│   │   ├── show.php                  # Détails d'un stage
│   │   └── update.php                # Mise à jour
│   │
│   ├── meetings/                      # Gestion des réunions
│   │   ├── cancel.php                # Annulation
│   │   ├── create.php                # Création
│   │   ├── delete.php                # Suppression
│   │   ├── index.php                 # Liste
│   │   ├── participants.php          # Gestion des participants
│   │   ├── show.php                  # Détails
│   │   ├── student-meetings.php      # Réunions par étudiant
│   │   ├── tutor-list.php            # Réunions par tuteur
│   │   └── update.php                # Mise à jour
│   │
│   ├── messages/                      # Système de messagerie
│   │   ├── contacts.php              # Liste des contacts
│   │   ├── conversation-by-id.php    # Conversation par ID
│   │   ├── conversation.php          # Conversation active
│   │   ├── conversations.php         # Liste des conversations
│   │   ├── delete.php                # Suppression
│   │   ├── index.php                 # Page principale
│   │   ├── mark-read.php             # Marquer comme lu
│   │   ├── send.php                  # Envoi de message
│   │   └── show.php                  # Affichage message
│   │
│   ├── notifications/                 # Système de notifications
│   │   ├── direct-mark-all-read.php  # Marquer tout comme lu
│   │   ├── direct-mark-read.php      # Marquer comme lu
│   │   ├── index.php                 # Liste des notifications
│   │   ├── mark-all-read.php         # API marquer tout lu
│   │   ├── mark-read.php             # API marquer lu
│   │   └── unread.php                # Notifications non lues
│   │
│   ├── settings/                      # Paramètres système
│   │   ├── preferences.php           # Préférences utilisateur
│   │   └── system.php                # Paramètres système
│   │
│   ├── students/                      # Gestion des étudiants
│   │   ├── add-preference.php        # Ajout de préférence
│   │   ├── admin-list.php            # Liste admin avec recherche/tri
│   │   ├── assignments.php           # Affectations étudiant
│   │   ├── index.php                 # Liste générale
│   │   ├── internships.php           # Stages de l'étudiant
│   │   ├── preferences.php           # Préférences de stage
│   │   ├── remove-preference.php     # Suppression préférence
│   │   ├── show.php                  # Profil étudiant
│   │   ├── stats.php                 # Statistiques
│   │   └── update-preference-order.php # Ordre des préférences
│   │
│   ├── teachers/                      # Gestion des tuteurs
│   │   ├── admin-list.php            # Liste admin
│   │   ├── availability.php          # Disponibilités
│   │   ├── index.php                 # Liste générale
│   │   ├── search.php                # Recherche de tuteurs
│   │   ├── show.php                  # Profil tuteur
│   │   └── students.php              # Étudiants assignés
│   │
│   ├── users/                         # Gestion des utilisateurs
│   │   ├── admin-list.php            # Liste admin
│   │   ├── create.php                # Création
│   │   ├── delete.php                # Suppression
│   │   ├── index.php                 # Liste
│   │   ├── login-history.php         # Historique connexions
│   │   ├── profile.php               # Profil utilisateur
│   │   ├── show.php                  # Détails utilisateur
│   │   ├── update-appearance.php     # Préférences d'apparence
│   │   ├── update-profile.php        # Mise à jour profil
│   │   └── update.php                # Mise à jour générale
│   │
│   └── utils.php                      # Utilitaires API
│
├── assets/                            # Ressources front-end
│   ├── components/                    # Composants JS réutilisables
│   ├── controllers.json               # Configuration des contrôleurs
│   ├── css/                           # Feuilles de style CSS
│   │   ├── bootstrap.css             # Framework CSS Bootstrap
│   │   ├── message-fixes.css         # Corrections pour messagerie
│   │   ├── messages.css              # Styles de messagerie (MODIFIÉ)
│   │   ├── modal-fixes.css           # Corrections pour modales
│   │   ├── style.css                 # Styles principaux
│   │   ├── theme-dark.css            # Thème sombre
│   │   └── theme-light.css           # Thème clair
│   │
│   ├── img/                           # Images et ressources visuelles
│   │   └── logo.svg                  # Logo de l'application
│   │
│   └── js/                            # Scripts JavaScript
│       ├── admin-table.js            # Gestion des tables admin
│       ├── admin.js                  # Scripts admin
│       ├── api-client-extensions.js  # Extensions client API
│       ├── api-client.js             # Client API principal
│       ├── api-init.js               # Initialisation API
│       ├── app.js                    # Application principale
│       ├── browser-compatibility.js   # Compatibilité navigateur
│       ├── debug-student-preferences.js # Debug préférences
│       ├── fix-links.js              # Correction des liens
│       ├── main.js                   # Point d'entrée JS
│       ├── messages.js               # Système de messagerie
│       ├── modal.js                  # Gestion des modales
│       ├── performance-optimizations.js # Optimisations
│       ├── stimulus-main.js          # Initialisation Stimulus
│       ├── student.js                # Scripts étudiant
│       └── tutor.js                  # Scripts tuteur
│
├── backups/                           # Sauvegardes
│   ├── message_files_20250609_173418/ # Sauvegarde des fichiers de message
│   ├── sample_data_files_20250609_173750/ # Sauvegarde des données d'exemple
│   └── temp_files_20250609_172421/    # Fichiers temporaires
│
├── benchmarks/                        # Tests de performance
│   ├── BenchmarkVisualizer.php       # Visualisation des résultats
│   ├── GreedyAlgorithmBenchmark.php  # Benchmark algorithme glouton
│   └── benchmark_runner.php          # Lanceur de benchmarks
│
├── components/                        # Composants PHP réutilisables
│   ├── cards/                         # Éléments de type carte
│   │   ├── card.php                  # Carte générique
│   │   ├── document-card.php         # Carte de document
│   │   ├── stat-card-bootstrap.php   # Carte statistique Bootstrap
│   │   └── stat-card.php             # Carte statistique
│   │
│   ├── charts/                        # Graphiques et visualisations
│   │   ├── assignment-matrix.php     # Matrice d'affectation
│   │   ├── chart-bootstrap.php       # Graphique Bootstrap
│   │   └── chart.php                 # Graphique générique
│   │
│   ├── common/                        # Éléments communs
│   │   ├── api-loader.php            # Chargeur API
│   │   └── pagination.php            # Pagination
│   │
│   ├── dashboard/                     # Éléments du tableau de bord
│   │   ├── activity-feed.php         # Fil d'activité
│   │   ├── chart-card.php            # Carte avec graphique
│   │   ├── data-table.php            # Table de données
│   │   ├── meeting-schedule.php      # Planning des réunions
│   │   ├── progress-tracker.php      # Suivi de progression
│   │   ├── quick-actions.php         # Actions rapides
│   │   ├── summary-card.php          # Carte de résumé
│   │   └── system-status.php         # État du système
│   │
│   ├── documents/                     # Composants documents
│   │   ├── document-list-paginated.php # Liste paginée
│   │   └── document-list.php         # Liste simple
│   │
│   ├── examples/                      # Exemples de composants
│   │   ├── assignment-algorithm-example.php # Exemple algorithme
│   │   ├── assignment-matrix-example.php # Exemple matrice
│   │   ├── dashboard-example.php     # Exemple dashboard
│   │   ├── form-example.php          # Exemple formulaire
│   │   └── search-filter-example.php # Exemple recherche
│   │
│   ├── filters/                       # Filtres et recherche
│   │   ├── filter-bar.php            # Barre de filtres
│   │   ├── internship-filter.php     # Filtre de stages
│   │   ├── search-box.php            # Boîte de recherche
│   │   └── search-result-item.php    # Élément de résultat
│   │
│   ├── forms/                         # Éléments de formulaire
│   │   ├── ajax-form.php             # Formulaire AJAX
│   │   ├── checkbox.php              # Case à cocher
│   │   ├── file-upload.php           # Upload de fichier
│   │   ├── form.php                  # Formulaire générique
│   │   ├── input.php                 # Champ de saisie
│   │   ├── radio.php                 # Bouton radio
│   │   ├── select.php                # Liste déroulante
│   │   ├── submit-button.php         # Bouton submit
│   │   └── textarea.php              # Zone de texte
│   │
│   ├── messages/                      # Composants de messagerie
│   │   ├── conversation-detail.php   # Détail conversation
│   │   ├── conversation-list.php     # Liste conversations
│   │   ├── message-bubble.php        # Bulle de message
│   │   └── message-composer.php      # Compositeur message
│   │
│   ├── modals/                        # Fenêtres modales
│   │   ├── confirm-modal.php         # Modal de confirmation
│   │   └── modal.php                 # Modal générique
│   │
│   ├── notifications/                 # Notifications UI
│   │   └── notification-container.php # Conteneur notifications
│   │
│   └── tables/                        # Tableaux de données
│       ├── pagination.php            # Pagination table
│       ├── table-bootstrap.php       # Table Bootstrap
│       └── table.php                 # Table générique
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
│   ├── BaseModel.php                  # Modèle de base (héritage)
│   ├── Company.php                    # Modèle d'entreprise
│   ├── Document.php                   # Modèle de document
│   ├── Evaluation.php                 # Modèle d'évaluation (IMPORTANT)
│   ├── Evaluation.php.patch           # Patch pour évaluations
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
L'API REST complète du système, organisée par ressources :
- **Structure cohérente** : Chaque ressource suit le pattern CRUD (Create, Read, Update, Delete)
- **Endpoints spécialisés** : `admin-list.php` pour les listes avec recherche/tri/pagination
- **Sécurité** : Vérification des permissions selon les rôles
- **Format JSON** : Toutes les réponses en JSON pour faciliter l'intégration JavaScript

### `/assets`
Ressources front-end organisées de manière modulaire :
- **CSS** : Styles séparés par fonctionnalité (messages, modals, themes)
- **JavaScript** : Scripts modulaires avec séparation des responsabilités
- **Optimisations** : Scripts de performance et compatibilité navigateur
- **Messagerie améliorée** : CSS récemment mis à jour pour une meilleure UX

### `/components`
Bibliothèque de composants UI réutilisables :
- **Architecture atomique** : Des composants simples (input, button) aux complexes (dashboard widgets)
- **Bootstrap intégré** : Variantes Bootstrap pour une intégration facile
- **Exemples fournis** : Dossier `examples/` avec des cas d'usage
- **Composants métier** : Matrice d'affectation, filtres de recherche, etc.

### `/controllers`
Logique métier centralisée :
- **Pattern MVC** : Séparation claire entre présentation et logique
- **Méthodes CRUD** : Standards pour chaque entité
- **Validation** : Validation des données côté serveur
- **Gestion d'erreurs** : Try-catch et messages d'erreur appropriés

### `/database`
Gestion complète de la base de données :
- **Installation automatisée** : Script `install.php` pour setup initial
- **Migrations** : Support des migrations pour les évolutions
- **Documentation** : README détaillé sur la structure des tables
- **Scripts utilitaires** : Reset, nettoyage, standardisation des données

### `/includes`
Fonctions centrales et utilitaires :
- **Authentification** : Gestion des sessions et JWT
- **Initialisation** : Configuration de l'environnement PHP
- **Sécurité** : Protection CSRF, validation des entrées
- **Helpers** : Fonctions utilitaires (download, redirections)

### `/models`
Couche d'abstraction de données :
- **BaseModel** : Classe parent avec méthodes communes
- **Pattern Active Record** : Chaque modèle gère ses propres requêtes
- **Relations** : Gestion des relations entre entités
- **Validation** : Règles de validation intégrées
- **Évaluations** : Système complexe avec contraintes d'unicité

### `/src`
Code source avancé et algorithmes :
- **Algorithmes d'affectation** : Interface commune pour différentes stratégies
- **DTO** : Objects de transfert pour découpler les couches
- **Services** : Logique métier complexe extraite des contrôleurs
- **Extensibilité** : Architecture permettant l'ajout facile de nouveaux algorithmes

### `/views`
Interfaces utilisateur par rôle :
- **Séparation par rôle** : admin/, student/, tutor/, coordinator/
- **Composants partagés** : common/ pour header, footer, etc.
- **Templates réutilisables** : Utilisation des composants PHP
- **Responsive** : Toutes les vues adaptées mobile/desktop

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

## Évolutions récentes de la structure

### Nouvelles fonctionnalités
- **API Companies** : Gestion complète des entreprises avec CRUD
- **Système de recherche avancé** : Endpoints `admin-list.php` avec tri/filtres/pagination
- **Messagerie améliorée** : Interface modernisée avec CSS animations
- **Dashboard optimisé** : Nouveaux endpoints pour performances

### Améliorations techniques
- **Requêtes SQL optimisées** : LEFT JOIN au lieu d'INNER JOIN
- **Gestion d'erreurs robuste** : Mécanismes de fallback
- **Performance** : Scripts d'optimisation et mise en cache
- **Sécurité renforcée** : Validation côté serveur améliorée

## Notes sur l'organisation du code

### Points forts
- **Séparation des responsabilités** : MVC bien implémenté avec API REST
- **Composants réutilisables** : Réduction de la duplication de code
- **Modularité** : Facile d'ajouter de nouvelles fonctionnalités
- **Documentation inline** : Code bien commenté et organisé

### Conventions adoptées
- **Nommage cohérent** : snake_case pour les fichiers, PascalCase pour les classes
- **Structure prévisible** : Même organisation dans chaque module
- **API standardisée** : Réponses JSON uniformes
- **Sécurité par défaut** : Vérification des permissions à chaque niveau

### Maintenance facilitée
- **Logs détaillés** : Pour debug et monitoring
- **Migrations versionnées** : Évolution contrôlée de la BDD
- **Tests isolés** : Benchmarks et tests unitaires séparés
- **Documentation à jour** : README et commentaires maintenus