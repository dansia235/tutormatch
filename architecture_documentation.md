# Architecture du Système de Tutorat TutorMatch

## 1. Vue d'ensemble

TutorMatch est une application web moderne de gestion des stages académiques, construite avec une architecture MVC robuste enrichie d'une API REST complète. Le système combine les bonnes pratiques PHP avec des technologies front-end modernes pour offrir une expérience utilisateur fluide et une maintenance facilitée.

### Caractéristiques architecturales principales
- **Architecture MVC** avec séparation claire des responsabilités
- **API REST** complète pour toutes les opérations CRUD
- **Composants réutilisables** pour l'interface utilisateur
- **Système de permissions** basé sur les rôles (RBAC)
- **Performance optimisée** avec requêtes SQL avancées
- **Interface responsive** adaptée à tous les appareils

## 2. Structure des répertoires principaux

### `/models` - Couche de données
Architecture basée sur le pattern Active Record avec modèle de base commun :

#### Modèles principaux
- `BaseModel.php` - Classe parent avec fonctionnalités communes
- `User.php` - Authentification et gestion des utilisateurs
- `Student.php` - Profils étudiants avec préférences
- `Teacher.php` - Profils tuteurs avec capacités
- `Assignment.php` - Affectations avec algorithmes d'optimisation
- `Evaluation.php` - Système d'évaluation avec contraintes d'unicité

#### Modèles de contenu
- `Message.php` - Messagerie avec conversations threadées
- `Document.php` - Gestion de fichiers avec metadata
- `Internship.php` - Offres de stage avec filtrage avancé
- `Company.php` - Entreprises partenaires avec logos
- `Meeting.php` - Planification et suivi des réunions
- `Notification.php` - Système de notifications temps réel

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

### `/api` - Interface REST complète
Architecture RESTful avec endpoints standardisés et fonctionnalités avancées :

#### Ressources principales (CRUD complet)
- `/api/assignments/` - Affectations avec matrice d'optimisation
- `/api/students/` - Gestion étudiants avec préférences
- `/api/teachers/` - Gestion tuteurs avec disponibilités
- `/api/internships/` - Stages avec recherche avancée
- `/api/companies/` - Entreprises avec gestion de logos
- `/api/documents/` - Fichiers avec upload sécurisé
- `/api/evaluations/` - Évaluations avec contraintes métier

#### Services spécialisés
- `/api/auth/` - Authentification JWT et sessions
- `/api/messages/` - Messagerie temps réel
- `/api/notifications/` - Système de notifications
- `/api/dashboard/` - Données agrégées pour tableaux de bord
- `/api/export/` - Export de données (PDF, Excel, CSV)

#### Fonctionnalités transversales
- **Endpoints admin** : `admin-list.php` avec recherche/tri/pagination
- **Sécurité** : Vérification des permissions par rôle
- **Performance** : Requêtes optimisées avec indices
- **Standards** : Réponses JSON uniformes avec codes HTTP appropriés

### `/assets` - Ressources frontend modernes
Architecture modulaire pour performance et maintenabilité :

#### JavaScript organisé
- **Scripts principaux** : `app.js`, `main.js`, `api-client.js`
- **Modules spécialisés** : `messages.js`, `admin.js`, `student.js`, `tutor.js`
- **Optimisations** : `performance-optimizations.js`, `browser-compatibility.js`
- **Framework** : `stimulus-main.js` pour interactions réactives

#### CSS structuré
- **Base** : `style.css` avec styles principaux
- **Framework** : `bootstrap.css` pour responsive design  
- **Thèmes** : `theme-light.css`, `theme-dark.css`
- **Composants** : `messages.css` (récemment amélioré), `modal-fixes.css`

#### Ressources visuelles
- **Logo** : `logo.svg` vectoriel scalable
- **Icônes** : Support Font Awesome et custom
- **Avatars** : Génération automatique pour entreprises

### `/components` - Architecture atomique UI
Bibliothèque complète de composants réutilisables avec variantes Bootstrap :

#### Composants de base
- **Forms** : `input.php`, `select.php`, `textarea.php`, `file-upload.php`
- **Actions** : `submit-button.php`, `modal.php`, `confirm-modal.php`
- **Layout** : `card.php`, `table.php`, `pagination.php`

#### Composants complexes  
- **Dashboard** : `chart-card.php`, `summary-card.php`, `activity-feed.php`
- **Messagerie** : `conversation-list.php`, `message-bubble.php`, `message-composer.php`
- **Recherche** : `search-box.php`, `filter-bar.php`, `search-result-item.php`

#### Composants métier
- **Affectations** : `assignment-matrix.php` (visualisation interactive)
- **Documents** : `document-list.php` avec pagination
- **Statistiques** : `stat-card.php` avec variantes Bootstrap
- **Notifications** : `notification-container.php`

#### Exemples et documentation
- **Dossier examples/** : Cas d'usage et intégration
- **Variantes Bootstrap** : Version responsive pour chaque composant

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

## 9. Évolutions récentes et améliorations

### Évolutions majeures récentes (Décembre 2024)

#### 1. Système de recherche et tri avancé
- **API standardisée** : Endpoints `admin-list.php` pour toutes les entités
- **Performance** : Requêtes SQL optimisées avec LEFT JOIN
- **UX** : Recherche temps réel avec debouncing 500ms
- **Pagination** : Support 10/20/50/100 éléments par page

#### 2. Messagerie modernisée
- **Interface** : Design épuré avec animations CSS
- **Performance** : Chargement asynchrone des conversations
- **Thèmes** : Support complet mode sombre/clair
- **UX** : Indicateurs visuels pour messages non lus

#### 3. Corrections architecturales importantes
- **Requêtes robustes** : LEFT JOIN pour éviter perte de données
- **Gestion d'erreurs** : Mécanismes de fallback
- **Contraintes métier** : Évaluations avec unicité stricte
- **Dashboard** : Endpoints dédiés pour performances

### Améliorations futures planifiées

#### Court terme (Q1 2025)
1. **Tests automatisés** : Intégration continue avec PHPUnit
2. **Documentation API** : Swagger/OpenAPI complète
3. **Performance** : Mise en cache Redis
4. **Monitoring** : Logs structurés et métriques

#### Moyen terme (2025)
1. **Architecture microservices** : Découplage des domaines métier
2. **Containerisation** : Docker + Kubernetes
3. **API GraphQL** : Alternative REST pour mobile
4. **Temps réel** : WebSockets pour notifications

#### Long terme (2026+)
1. **Architecture serverless** : Migration cloud-native
2. **Machine learning** : IA pour optimisation d'affectations
3. **Blockchain** : Certification des diplômes
4. **PWA** : Application web progressive

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

## Conclusion

### Maturité architecturale actuelle

TutorMatch présente une architecture solide et évolutive qui combine harmonieusement :

1. **Bases solides** : MVC bien structuré avec séparation claire des responsabilités
2. **API moderne** : REST complet avec endpoints standardisés  
3. **Frontend réactif** : Composants réutilisables et interface responsive
4. **Performance** : Requêtes optimisées et chargement asynchrone
5. **Sécurité** : RBAC, validation des données, protection CSRF/XSS
6. **Maintenabilité** : Code modulaire, tests automatisés, documentation

### Points forts de l'architecture

- **Extensibilité** : Facile d'ajouter de nouvelles fonctionnalités
- **Performance** : Optimisations continues (recherche, pagination, cache)
- **UX/UI** : Interface moderne avec thèmes et animations
- **Robustesse** : Gestion d'erreurs et mécanismes de fallback
- **Standards** : Respect des bonnes pratiques de développement

### Perspectives d'évolution

L'architecture actuelle offre une base solide pour les évolutions futures vers :
- **Architecture distribuée** (microservices)
- **Technologies cloud-native** (serverless, conteneurs)
- **Intelligence artificielle** (ML pour affectations)
- **Applications mobiles** (API-first approach)

Cette combinaison d'approches traditionnelles éprouvées et de technologies modernes fait de TutorMatch une plateforme robuste, performante et prête pour l'avenir.