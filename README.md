# TutorMatch - Système de Gestion d'Attribution des Stages

<p align="center">
  <img src="assets/img/logo.png" alt="TutorMatch Logo" width="200" height="auto">
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

- **Backend** : PHP 8+ avec architecture MVC
- **Base de données** : MySQL/MariaDB
- **Frontend** : HTML5, CSS3, JavaScript (ES6+)
- **Frameworks** : Bootstrap 5 pour l'UI, Stimulus.js pour les interactions
- **Librairies** : Chart.js pour les visualisations, Flatpickr pour les calendriers
- **API** : REST API pour les opérations côté client
- **Sécurité** : Authentification JWT, sessions sécurisées, protection CSRF

## 📋 Prérequis

- PHP 8.0 ou supérieur
- MySQL 5.7 ou supérieur (ou MariaDB équivalent)
- Serveur web (Apache, Nginx)
- Extensions PHP : PDO, PDO_MySQL, mbstring, json
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

### Algorithme Hongrois (en développement)
- Solution d'optimisation globale garantissant le meilleur appariement
- Complexité : O(n³)
- Idéal quand l'optimalité est critique

### Algorithme Génétique (planifié)
- Approche évolutive pour des contraintes complexes
- Adaptatif à des critères multiples et variables
- Idéal pour de grands ensembles de données

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

- **Recherche avancée** : Filtres multiples, suggestions, tri des résultats
- **Matrice d'affectation** : Visualisation interactive des compatibilités
- **Système de thèmes** : Support des modes clair et sombre
- **Exportation de données** : Formats PDF, Excel et CSV pour les rapports
- **Responsive design** : Adapté à tous les appareils (desktop, tablette, mobile)

## 🧪 Tests

Le projet inclut des tests unitaires et fonctionnels pour les composants principaux :

```bash
# Exécuter tous les tests
vendor/bin/phpunit

# Exécuter les tests d'algorithmes d'affectation
vendor/bin/phpunit tests/Algorithm/

# Tester l'interface de gestion des entreprises
php test_companies.php
```

## 📚 Documentation

- [Architecture détaillée](architecture_documentation.md)
- [Guide d'installation](docs/README_INSTALLATION.md)
- [Documentation de l'API](docs/API.md)
- [Migration de la base de données](docs/DATABASE_MIGRATION_GUIDE.md)
- [Algorithmes d'affectation](src/Algorithm/README.md)

## 📈 Feuille de route

- Implémentation des algorithmes d'affectation avancés
- Amélioration du système de recherche (voir [TODO-SEARCH-IMPROVEMENTS.md](TODO-SEARCH-IMPROVEMENTS.md))
- Développement d'une application mobile
- Internationalisation (support multilingue)
- Intégration avec des systèmes de gestion académique externes

## 🆕 Améliorations récentes

- Interface complète de gestion des entreprises
  - Ajout, modification et suppression d'entreprises
  - Support pour les logos d'entreprise
  - Avatars générés automatiquement pour les entreprises sans logo
  - Visualisation des stages par entreprise
  - Confirmation sécurisée pour la suppression
- Correction des problèmes d'affichage dans la liste des tuteurs
- Correction des bugs dans l'algorithme d'affectation
- Amélioration des performances de recherche d'internships

## 👥 Statut du projet

Ce système a été développé dans le cadre d'un projet académique. Il s'agit d'un environnement d'apprentissage et de démonstration conçu pour illustrer les principes de développement d'applications web et d'algorithmes d'affectation.

**Note importante** : Ce projet n'est pas destiné à recevoir des contributions externes. Les fichiers [CONTRIBUTING.md](CONTRIBUTING.md) et [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md) sont inclus à titre éducatif pour démontrer les bonnes pratiques de gestion de projet.

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