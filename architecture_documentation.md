# Architecture du Système de Tutorat

## 1. Vue d'ensemble

Le projet est structuré selon un modèle MVC (Modèle-Vue-Contrôleur) avec une API REST, combinant des approches traditionnelles PHP avec des fonctionnalités JavaScript modernes. Il s'agit d'une application web de gestion d'un système de tutorat académique permettant de gérer les relations entre tuteurs, étudiants, stages et diverses fonctionnalités associées.

## 2. Structure des répertoires principaux

### `/models`
Contient les classes de modèles représentant les entités métier du système :
- `User.php` - Gestion des utilisateurs
- `Student.php` - Informations sur les étudiants
- `Teacher.php` - Informations sur les tuteurs
- `Assignment.php` - Affectations tuteurs-étudiants
- `Message.php` - Système de messagerie
- `Document.php` - Gestion des documents
- `Internship.php` - Gestion des stages
- `Company.php` - Informations sur les entreprises

### `/views`
Organisé par rôle utilisateur :
- `/views/admin` - Interface pour les administrateurs
- `/views/student` - Interface pour les étudiants
- `/views/tutor` - Interface pour les tuteurs
- `/views/common` - Éléments communs (header, footer)
- `/views/auth` - Pages liées à l'authentification

### `/controllers`
Implémente la logique métier pour chaque entité :
- `UserController.php`
- `StudentController.php`
- `TeacherController.php`
- `AssignmentController.php`
- `StatisticsController.php`
- `DocumentController.php`
- `InternshipController.php`

### `/api`
API REST organisée par ressources :
- `/api/assignments` - CRUD pour les affectations
- `/api/auth` - Authentification (login, logout, refresh)
- `/api/students` - Gestion des étudiants
- `/api/teachers` - Gestion des tuteurs
- `/api/messages` - Système de messagerie
- `/api/documents` - Gestion de documents
- `/api/evaluations` - Système d'évaluation
- `/api/internships` - Gestion des stages
- `/api/dashboard` - Données pour tableaux de bord

### `/assets`
Ressources frontend :
- `/assets/js` - Scripts JavaScript
  - `/assets/js/controllers` - Contrôleurs Stimulus.js
  - `/assets/js/modules` - Modules JavaScript fonctionnels
  - `/assets/js/services` - Services JavaScript
- `/assets/css` - Feuilles de style CSS
- `/assets/img` - Images

### `/components`
Composants UI réutilisables :
- `/components/cards` - Composants de type carte
- `/components/charts` - Graphiques et visualisations
- `/components/tables` - Tableaux de données
- `/components/forms` - Éléments de formulaire
- `/components/modals` - Fenêtres modales
- `/components/messages` - Composants de messagerie

### `/includes`
Utilitaires et fonctions partagées :
- `init.php` - Initialisation de l'application
- `auth.php` - Fonctions d'authentification
- `JwtUtils.php` - Utilitaires JWT
- `RedirectInterceptor.php` - Gestion des redirections

### `/src`
Code source avancé :
- `/src/Algorithm` - Algorithmes spécifiques
  - `GreedyAlgorithm.php` - Algorithme glouton d'affectation
  - `HungarianAlgorithm.php` - Algorithme hongrois d'affectation
- `/src/DTO` - Objets de transfert de données
- `/src/Service` - Services métier

### `/database`
Gestion de la base de données :
- `create_database.sql` - Script de création de la BDD
- `tutoring_system.sql` - Structure complète de la BDD
- `/database/migrations` - Scripts de migration

### `/config`
Configuration du système :
- `database.php` - Configuration de la base de données

## 3. Flux d'exécution typique

1. **Initialisation** : Chaque requête commence par `/includes/init.php` qui initialise l'environnement
2. **Authentification** : Vérification des droits utilisateur via `auth.php`
3. **Routage** : Dispatching vers le contrôleur approprié
4. **Traitement** : Le contrôleur utilise les modèles pour interagir avec la BDD
5. **Rendu** : Les vues génèrent le HTML, souvent en utilisant des composants réutilisables
6. **Interaction cliente** : JavaScript enrichit l'UI et communique avec l'API

## 4. Modèles de conception utilisés

1. **MVC** (Modèle-Vue-Contrôleur) : Séparation claire des responsabilités
2. **Repository Pattern** : Encapsulation de la logique d'accès aux données dans les modèles
3. **Service Layer** : Services spécialisés (`AssignmentService`)
4. **Strategy Pattern** : Interface commune pour les algorithmes d'affectation
5. **Component-Based UI** : Interface utilisateur construite avec des composants réutilisables
6. **API REST** : Interface HTTP pour l'interaction client-serveur

## 5. Base de données

Le système utilise MySQL/MariaDB avec :
- Table `users` - Utilisateurs du système avec rôles
- Table `students` - Informations spécifiques aux étudiants
- Table `teachers` - Informations spécifiques aux tuteurs
- Table `assignments` - Affectations entre tuteurs et étudiants
- Table `messages` - Système de messagerie
- Table `documents` - Gestion des documents
- Table `internships` - Stages
- Table `companies` - Entreprises

## 6. Sécurité et authentification

1. **Session PHP** : Gestion de session classique
2. **JWT** : Tokens d'authentification pour l'API
3. **RBAC** : Contrôle d'accès basé sur les rôles (admin, tuteur, étudiant)
4. **CSRF Protection** : Protection contre les attaques CSRF

## 7. JavaScript et interaction client

1. **Stimulus.js** : Framework JS léger pour le comportement UI
2. **Fetch API** : Communication avec le backend via AJAX
3. **Bootstrap** : Framework CSS pour le design responsive
4. **Modules spécialisés** : Fonctionnalités avancées (algorithmes d'affectation, matrix, etc.)

## 8. Points forts de l'architecture

1. **Séparation des préoccupations** : MVC bien implémenté
2. **Composants réutilisables** : Architecture modulaire pour l'UI
3. **API REST** : Interface unifiée pour les opérations CRUD
4. **Algorithmes spécialisés** : Support pour des algorithmes d'affectation avancés
5. **Multi-rôle** : Support natif pour différents types d'utilisateurs

## 9. Améliorations potentielles

1. **Architecture plus modulaire** : Structurer davantage en modules fonctionnels
2. **Utilisation d'un ORM** : Simplifier l'accès aux données
3. **Tests unitaires et d'intégration** : Améliorer la couverture de tests
4. **Documentation API** : Swagger/OpenAPI pour documenter l'API REST
5. **Conteneurisation** : Docker pour simplifier le déploiement
6. **Architecture SPA** : Évolution vers une SPA avec backend API-only
7. **Internationalisation** : Support multilingue

## 10. Arborescence des fichiers principaux

```
tutoring/
│
├── api/                           # API REST
│   ├── assignments/               # Gestion des affectations
│   │   ├── create.php             # Création d'affectation
│   │   ├── index.php              # Liste des affectations
│   │   ├── matrix.php             # Matrice d'affectation
│   │   ├── show.php               # Détails d'affectation
│   │   ├── status.php             # Statut d'affectation
│   │   └── update.php             # Mise à jour d'affectation
│   │
│   ├── auth/                      # Authentification
│   │   ├── login.php              # Connexion
│   │   ├── logout.php             # Déconnexion
│   │   ├── refresh.php            # Rafraîchissement de token
│   │   └── record-login.php       # Enregistrement des connexions
│   │
│   ├── dashboard/                 # Données de tableau de bord
│   │   ├── activity.php           # Activités récentes
│   │   ├── charts.php             # Données pour graphiques
│   │   └── stats.php              # Statistiques
│   │
│   ├── documents/                 # Gestion des documents
│   ├── evaluations/               # Système d'évaluation
│   ├── internships/               # Gestion des stages
│   ├── messages/                  # Système de messagerie
│   │   ├── contacts.php           # Liste des contacts
│   │   ├── conversation.php       # Conversation individuelle
│   │   ├── conversations.php      # Liste des conversations
│   │   ├── mark-read.php          # Marquer comme lu
│   │   └── send.php               # Envoi de message
│   │
│   ├── students/                  # Gestion des étudiants
│   ├── teachers/                  # Gestion des tuteurs
│   ├── users/                     # Gestion des utilisateurs
│   └── utils.php                  # Utilitaires API
│
├── assets/                        # Ressources frontend
│   ├── css/                       # Styles CSS
│   │   ├── app.css                # Styles principaux
│   │   ├── bootstrap.css          # Styles Bootstrap
│   │   └── style.css              # Styles personnalisés
│   │
│   ├── js/                        # Scripts JavaScript
│   │   ├── controllers/           # Contrôleurs Stimulus.js
│   │   │   ├── assignment_matrix_controller.js
│   │   │   ├── auth_controller.js
│   │   │   ├── dashboard_controller.js
│   │   │   ├── message_interface_controller.js
│   │   │   └── student_preferences_controller.js
│   │   │
│   │   ├── modules/               # Modules fonctionnels
│   │   │   ├── assignment-algorithms.js
│   │   │   ├── assignment-matrix.js
│   │   │   └── messages.js
│   │   │
│   │   ├── services/              # Services JavaScript
│   │   │   ├── api-interceptor.js
│   │   │   ├── auth-service.js
│   │   │   └── error-handler.js
│   │   │
│   │   ├── api-client.js          # Client API
│   │   ├── app.js                 # Application principale
│   │   └── main.js                # Point d'entrée JS
│   │
│   └── img/                       # Images
│
├── components/                    # Composants UI réutilisables
│   ├── cards/                     # Composants carte
│   ├── charts/                    # Graphiques
│   ├── common/                    # Éléments communs
│   ├── dashboard/                 # Éléments de tableau de bord
│   ├── documents/                 # Composants de documents
│   ├── forms/                     # Éléments de formulaire
│   ├── messages/                  # Composants de messagerie
│   │   ├── conversation-detail.php
│   │   ├── conversation-list.php
│   │   ├── message-bubble.php
│   │   └── message-composer.php
│   │
│   ├── modals/                    # Fenêtres modales
│   ├── notifications/             # Notifications
│   └── tables/                    # Tableaux
│
├── config/                        # Configuration
│   └── database.php               # Configuration BDD
│
├── controllers/                   # Contrôleurs MVC
│   ├── AssignmentController.php   # Gestion des affectations
│   ├── DocumentController.php     # Gestion des documents
│   ├── InternshipController.php   # Gestion des stages
│   ├── StatisticsController.php   # Statistiques
│   ├── StudentController.php      # Gestion des étudiants
│   ├── TeacherController.php      # Gestion des tuteurs
│   └── UserController.php         # Gestion des utilisateurs
│
├── database/                      # Gestion de la base de données
│   ├── migrations/                # Scripts de migration
│   ├── create_database.sql        # Création de la BDD
│   ├── setup_database.php         # Configuration de la BDD
│   └── tutoring_system.sql        # Structure complète
│
├── includes/                      # Utilitaires partagés
│   ├── JwtUtils.php               # Gestion des JWT
│   ├── RedirectInterceptor.php    # Interception des redirections
│   ├── auth.php                   # Fonctions d'authentification
│   └── init.php                   # Initialisation de l'application
│
├── models/                        # Modèles de données
│   ├── Assignment.php             # Modèle d'affectation
│   ├── Company.php                # Modèle d'entreprise
│   ├── Document.php               # Modèle de document
│   ├── Internship.php             # Modèle de stage
│   ├── Meeting.php                # Modèle de réunion
│   ├── Message.php                # Modèle de message
│   ├── Student.php                # Modèle d'étudiant
│   ├── Teacher.php                # Modèle de tuteur
│   └── User.php                   # Modèle utilisateur
│
├── src/                           # Code source avancé
│   ├── Algorithm/                 # Algorithmes spécifiques
│   │   ├── AssignmentAlgorithmInterface.php  # Interface
│   │   ├── GreedyAlgorithm.php               # Algo glouton
│   │   └── HungarianAlgorithm.php            # Algo hongrois
│   │
│   ├── DTO/                       # Objets de transfert de données
│   │   ├── AssignmentParameters.php
│   │   └── AssignmentResult.php
│   │
│   └── Service/                   # Services métier
│       └── AssignmentService.php  # Service d'affectation
│
├── templates/                     # Templates
│   ├── layouts/                   # Layouts principaux
│   │   ├── admin.php              # Layout admin
│   │   ├── student.php            # Layout étudiant
│   │   └── tutor.php              # Layout tuteur
│   │
│   └── base.php                   # Template de base
│
├── views/                         # Vues par rôle
│   ├── admin/                     # Interface administrateur
│   │   ├── assignments/           # Gestion des affectations
│   │   ├── dashboard.php          # Tableau de bord admin
│   │   ├── documents/             # Gestion des documents
│   │   ├── internships/           # Gestion des stages
│   │   ├── students/              # Gestion des étudiants
│   │   ├── teachers/              # Gestion des tuteurs
│   │   └── users.php              # Gestion des utilisateurs
│   │
│   ├── common/                    # Éléments communs
│   │   ├── footer.php             # Pied de page
│   │   ├── header.php             # En-tête
│   │   └── profile.php            # Page de profil
│   │
│   ├── student/                   # Interface étudiant
│   │   ├── dashboard.php          # Tableau de bord étudiant
│   │   ├── documents.php          # Documents étudiant
│   │   ├── evaluations.php        # Évaluations
│   │   ├── internship.php         # Stage
│   │   ├── meetings.php           # Réunions
│   │   ├── messages.php           # Messagerie étudiant
│   │   └── preferences.php        # Préférences étudiant
│   │
│   └── tutor/                     # Interface tuteur
│       ├── dashboard.php          # Tableau de bord tuteur
│       ├── documents.php          # Documents tuteur
│       ├── evaluations.php        # Évaluations
│       ├── meetings.php           # Réunions
│       ├── messages.php           # Messagerie tuteur
│       └── students.php           # Étudiants assignés
│
├── index.php                      # Point d'entrée principal
├── login.php                      # Page de connexion
└── logout.php                     # Déconnexion
```

Cette architecture combine une approche MVC traditionnelle PHP avec des éléments modernes de développement web, adaptée à la gestion d'un système de tutorat académique.