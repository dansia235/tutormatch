# TutorMatch - Système de Gestion d'Attribution des Stages

<p align="center">
  <img src="assets/img/logo.svg" alt="TutorMatch Logo" width="120" height="120">
  <br>
  <h2 align="center">TutorMatch</h2>
</p>

TutorMatch est une application web complète pour la gestion des stages académiques et l'attribution de tuteurs aux étudiants. Ce système permet aux établissements d'enseignement de gérer efficacement l'ensemble du processus de stage, depuis la publication des offres jusqu'au suivi et à l'évaluation.

## 🌟 Fonctionnalités principales

### Gestion des utilisateurs et des rôles
- **Administrateurs** : Configuration du système et supervision générale
- **Coordinateurs** : Gestion des affectations et supervision des stages
- **Tuteurs** : Suivi et évaluation des stages des étudiants
- **Étudiants** : Choix des stages, communication avec les tuteurs

### Gestion des stages et entreprises
- Catalogue d'offres de stage avec filtres avancés
- Base de données des entreprises partenaires
- Publication et modification des offres
- Système de recherche optimisé
- **NOUVEAU** : Interface complète de gestion des entreprises (CRUD)
- **NOUVEAU** : Support pour les logos d'entreprise et avatars automatiques

### Système de préférences et d'affectation
- Les étudiants peuvent classer leurs stages préférés
- Les tuteurs peuvent définir leurs préférences (domaines, entreprises)
- Plusieurs algorithmes d'affectation optimisés (glouton, hongrois, etc.)
- Tableau de compatibilité et matrice d'affectation

### Suivi et évaluation
- Gestion des documents relatifs aux stages
- Planification et suivi des réunions
- Évaluations à mi-parcours et finales
- Génération de rapports et statistiques

### Communication
- Messagerie interne entre tuteurs et étudiants
- Système de notifications pour les événements importants
- Fil d'activité sur les tableaux de bord

## 🛠️ Architecture technique

### Stack principal
- **Backend** : PHP 8+ avec architecture MVC
- **Base de données** : MySQL/MariaDB avec requêtes optimisées
- **Cache** : Redis pour optimisation des performances et rate limiting
- **Frontend** : HTML5, CSS3, JavaScript (ES6+)
- **API** : REST API complète avec documentation Swagger/OpenAPI 3.0
- **Monitoring** : Métriques Prometheus, interfaces de monitoring visuelles
- **CI/CD** : Pipeline GitHub Actions avec tests automatisés

### Frameworks et librairies
- **UI** : Bootstrap 5 pour le design responsive
- **Interactions** : Stimulus.js pour les comportements dynamiques
- **Visualisations** : Chart.js pour graphiques et statistiques
- **Calendriers** : Flatpickr pour la sélection de dates
- **Animations** : CSS transitions pour une UX fluide

### Sécurité
- **Authentification** : JWT tokens et sessions PHP sécurisées
- **Protection** : CSRF tokens, validation des entrées
- **Permissions** : RBAC (Role-Based Access Control)
- **Données** : Requêtes préparées, échappement XSS

## 📋 Prérequis

- PHP 8.0 ou supérieur
- MySQL 5.7 ou supérieur (ou MariaDB équivalent)
- Redis 6.0+ (optionnel mais recommandé pour les performances)
- Serveur web (Apache, Nginx)
- Extensions PHP : PDO, PDO_MySQL, mbstring, json, redis
- Navigateur web moderne (Chrome, Firefox, Safari, Edge)

## 🚀 Installation

### Installation automatique (recommandée)

1. Clonez le dépôt dans votre environnement web (par exemple, dans le dossier `htdocs` de XAMPP)
   ```bash
   git clone https://github.com/votre-utilisateur/tutormatch.git tutoring
   cd tutoring
   ```

2. Accédez à l'installateur via votre navigateur :
   ```
   http://localhost/tutoring/database/install.php
   ```

3. Suivez les instructions à l'écran pour configurer la base de données

### Installation manuelle

1. Clonez le dépôt comme indiqué ci-dessus

2. Créez une base de données MySQL
   ```sql
   CREATE DATABASE tutoring_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

3. Importez le schéma de base de données
   ```bash
   mysql -u votre_utilisateur -p tutoring_system < database/tutoring_system.sql
   ```

4. Créez ou modifiez le fichier de configuration
   ```bash
   cp config/database.example.php config/database.php
   # Puis modifiez config/database.php avec vos informations de connexion
   ```

5. Assurez-vous que les dossiers suivants sont accessibles en écriture
   ```bash
   chmod -R 755 uploads/
   chmod -R 755 temp/
   ```

### Utilisateurs par défaut

- **Administrateur** : admin / admin123
- **Coordinateur** : coordinator / coord123
- **Tuteur** : tutor / tutor123
- **Étudiant** : student / student123

## 📐 Algorithmes d'affectation

Le système propose plusieurs algorithmes pour optimiser l'affectation des tuteurs aux étudiants :

### Algorithme Glouton (implémenté)
- Approche rapide attribuant les étudiants par ordre de priorité
- Complexité : O(n² log n) où n est le nombre d'étudiants
- Idéal pour les ensembles de données moyens

### Algorithme Hongrois (implémenté)
- Solution d'optimisation globale garantissant le meilleur appariement
- Complexité : O(n³)
- Idéal quand l'optimalité est critique

### Algorithme Génétique (implémenté et optimisé)
- Approche évolutive avec configuration adaptative selon la taille
- Optimisation multi-critères avec stratégies d'initialisation diversifiées
- Monitoring complet et benchmarks de performance intégrés
- Idéal pour grandes instances (200+ étudiants) avec +25% qualité vs glouton

## 📊 Tableaux de bord et rapports

- **Tableau de bord administrateur** : Vue d'ensemble complète du système
- **Tableau de bord coordinateur** : Gestion des affectations et statistiques
- **Tableau de bord tuteur** : Suivi des étudiants assignés
- **Tableau de bord étudiant** : Suivi du stage et communication

Rapports disponibles :
- Statistiques d'affectation par département
- Taux de satisfaction des étudiants et tuteurs
- Rapports d'évaluation des stages
- Métriques de performance du système

## 🔍 Fonctionnalités avancées

### Recherche et filtrage
- **Recherche temps réel** : Avec debouncing pour performances optimales
- **Filtres multicritères** : Combinaison de multiples filtres
- **Tri dynamique** : Sur toutes les colonnes importantes
- **Pagination avancée** : Navigation fluide dans les grandes listes
- **Export des résultats** : Export des recherches filtrées

### Visualisations et rapports
- **Matrice d'affectation** : Vue interactive des compatibilités tuteur-étudiant
- **Tableaux de bord** : Graphiques en temps réel avec Chart.js
- **Rapports personnalisés** : Génération de rapports PDF/Excel
- **Statistiques avancées** : Analyses par département, période, performance

### Interface utilisateur
- **Thèmes** : Mode clair/sombre avec transition fluide
- **Responsive design** : Optimisé pour tous les écrans
- **Accessibilité** : Conformité WCAG pour inclusivité
- **Performance** : Chargement asynchrone et mise en cache

## 🧪 Tests

Le projet inclut des tests unitaires et fonctionnels pour les composants principaux :

```bash
# Exécuter tous les tests
vendor/bin/phpunit

# Exécuter les tests d'algorithmes d'affectation
vendor/bin/phpunit tests/Algorithm/

# Tester l'algorithme génétique spécifiquement
php test_genetic_algorithm.php

# Benchmarks de performance des algorithmes
php tests/Algorithm/GeneticAlgorithmBenchmark.php

# Tester l'interface de gestion des entreprises
php test_companies.php
```

## 📚 Documentation

- [Documentation complète du projet](PROJECT_DOCUMENTATION.md)
- [Architecture détaillée](architecture_documentation.md)
- [Guide d'installation](docs/README_INSTALLATION.md)
- [Documentation API Swagger](api/swagger.php) - Interface interactive
- [Monitoring et métriques](api/monitoring/) - Health check et métriques système
- [Migration de la base de données](docs/DATABASE_MIGRATION_GUIDE.md)
- [Algorithmes d'affectation](src/Algorithm/README.md)

## 📈 Feuille de route

### Court terme (Q1 2025)
- ✅ Système de recherche et tri avancé (COMPLÉTÉ)
- ✅ Amélioration de la messagerie (COMPLÉTÉ)
- ✅ Finalisation de l'algorithme hongrois (COMPLÉTÉ)
- ✅ Tests d'intégration automatisés (COMPLÉTÉ)
- ✅ Cache Redis et optimisation performances (COMPLÉTÉ)
- ✅ Documentation API Swagger (COMPLÉTÉ)
- ✅ Monitoring et métriques système (COMPLÉTÉ)

### Moyen terme (Q2-Q3 2025)
- 📱 Application mobile React Native
- 🌐 Internationalisation (FR/EN/ES)
- 🔗 API publique documentée (OpenAPI)
- 📊 Analytics avancées avec tableaux de bord personnalisables

### Long terme (2025-2026)
- 🤖 IA pour suggestions d'affectation
- 🎥 Système de vidéoconférence intégré
- 🔄 Intégration LMS (Moodle, Canvas)
- ☁️ Migration vers architecture microservices

## 🆕 Améliorations récentes

### Système de recherche et tri (Décembre 2024)
- **Recherche avancée** : Implémentation complète sur toutes les entités
- **Tri dynamique** : Sur toutes les colonnes avec ordre ASC/DESC
- **Pagination flexible** : 10, 20, 50 ou 100 éléments par page
- **Filtres multicritères** : Par statut, département, niveau, etc.
- **Performance optimisée** : Requêtes SQL avec indices appropriés

### Messagerie interne améliorée
- **Interface moderne** : Design épuré avec animations fluides
- **Thème sombre** : Support complet du mode sombre
- **Indicateurs visuels** : Badges pour messages non lus
- **Performance** : Chargement asynchrone des conversations

### Corrections importantes
- **Problème de chargement des stages** : Résolu avec LEFT JOIN
- **Dashboard tuteur** : Correction affichage réunions et messages
- **Modèle Meeting** : Gestion robuste des dates et champs
- **Évaluations** : Contraintes d'unicité implémentées

### Gestion des entreprises
- **Interface CRUD complète** : Création, lecture, mise à jour, suppression
- **Support logos** : Upload et affichage des logos d'entreprise
- **Avatars automatiques** : Génération pour entreprises sans logo
- **Vue stages** : Visualisation des stages par entreprise

## 👥 Statut du projet

Ce système a été développé dans le cadre d'un projet académique universitaire. Il s'agit d'un environnement d'apprentissage et de démonstration conçu pour illustrer les principes de développement d'applications web et d'algorithmes d'affectation.

**⚠️ Note importante** : Ce projet est actuellement fermé aux contributions externes car il fait partie d'une évaluation académique en cours. Nous apprécions votre intérêt, mais nous ne pouvons pas accepter de pull requests ou de modifications externes pour le moment.

Les fichiers [CONTRIBUTING.md](CONTRIBUTING.md) et [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md) sont inclus à titre éducatif pour démontrer les bonnes pratiques de gestion de projet et pourront être utilisés ultérieurement si le projet s'ouvre aux contributions.

## 📜 Licence

Ce projet est sous licence MIT. Voir le fichier [LICENSE](LICENSE) pour plus de détails.

## 📬 Contact

Pour toute question ou suggestion, veuillez contacter l'équipe de développement via :
- Email : 	toussaint.dansia@etud.u-picardie.fr & isaac.belle.belle@etud.u-picardie.fr
- Issues GitHub : [Créer une issue](https://github.com/dansia235/tutormatch/issues)

---

<p align="center">
  Développé avec ❤️ pour optimiser la gestion des stages académiques
</p>