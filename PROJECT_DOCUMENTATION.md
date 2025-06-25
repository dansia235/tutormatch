# TutorMatch - Documentation Complète du Système

## Table des matières

1. [Introduction](#introduction)
2. [Architecture du système](#architecture-du-système)
3. [Fonctionnalités principales](#fonctionnalités-principales)
4. [Algorithmes d'affectation](#algorithmes-daffectation)
   - [Vue d'ensemble](#vue-densemble)
   - [Algorithme Glouton](#algorithme-glouton-greedy)
   - [Algorithme Hongrois](#algorithme-hongrois-hungarian)
   - [Algorithme Génétique](#algorithme-génétique-genetic)
   - [Personnalisation et optimisation](#personnalisation-et-optimisation)
5. [Modèle de données](#modèle-de-données)
6. [Interface utilisateur](#interface-utilisateur)
7. [Gestion des stages](#gestion-des-stages)
8. [Système de notification](#système-de-notification)
9. [Messagerie interne](#messagerie-interne)
10. [API et intégrations](#api-et-intégrations)
11. [Sécurité](#sécurité)
12. [Tests et benchmarks](#tests-et-benchmarks)
13. [Maintenance et évolutions](#maintenance-et-évolutions)

## Introduction

TutorMatch est une application web complète pour la gestion des stages académiques et l'attribution de tuteurs aux étudiants. Ce système permet aux établissements d'enseignement de gérer efficacement l'ensemble du processus de stage, depuis la publication des offres jusqu'au suivi et à l'évaluation.

Le projet répond à plusieurs défis courants dans la gestion des stages académiques :

- **Affectation optimale** : Trouver le meilleur appariement entre étudiants et tuteurs en fonction de multiples critères
- **Gestion du processus** : Suivi du cycle de vie complet des stages, de la candidature à l'évaluation finale
- **Communication simplifiée** : Faciliter les échanges entre étudiants, tuteurs et coordinateurs
- **Suivi et reporting** : Générer des statistiques et indicateurs de performance pour optimiser le processus

## Architecture du système

TutorMatch est construit sur une architecture MVC (Modèle-Vue-Contrôleur) en PHP, avec une base de données MySQL. L'application utilise une approche hybride avec des rendus côté serveur pour la majorité des pages et des composants dynamiques enrichis par JavaScript.

### Technologies principales

- **Backend** : PHP 8+ avec architecture MVC personnalisée
- **Base de données** : MySQL/MariaDB
- **Frontend** : HTML5, CSS3, JavaScript (ES6+)
- **Frameworks** : Bootstrap 5 pour l'UI, Stimulus.js pour les interactions
- **Librairies** : Chart.js pour les visualisations, Flatpickr pour les calendriers
- **API** : REST API pour les opérations côté client
- **Sécurité** : Authentification JWT, sessions sécurisées, protection CSRF

### Structure du projet

```
/
├── api/                 # Points d'entrée de l'API REST
├── assets/              # Ressources statiques (CSS, JS, images)
├── components/          # Composants réutilisables de l'interface
├── config/              # Configuration de l'application
├── controllers/         # Contrôleurs pour la logique métier
├── database/            # Scripts SQL et migrations
├── docs/                # Documentation technique
├── includes/            # Utilitaires et fonctions partagées
├── models/              # Modèles de données
├── src/                 # Code source principal
│   ├── Algorithm/       # Implémentations des algorithmes d'affectation
│   ├── DTO/             # Objets de transfert de données
│   └── Service/         # Services métier
├── templates/           # Templates de vues
├── tests/               # Tests unitaires et fonctionnels
└── views/               # Vues de l'application par rôle
    ├── admin/           # Interface administrateur
    ├── common/          # Éléments communs
    ├── coordinator/     # Interface coordinateur
    ├── student/         # Interface étudiant
    └── tutor/           # Interface tuteur
```

## Fonctionnalités principales

### Gestion des utilisateurs et des rôles

Le système définit quatre rôles principaux avec des permissions et responsabilités spécifiques :

1. **Administrateurs**
   - Configuration globale du système
   - Gestion des utilisateurs et des rôles
   - Supervision des processus et rapports globaux
   - Paramétrage des algorithmes d'affectation

2. **Coordinateurs**
   - Gestion des entreprises et des offres de stage
   - Supervision des affectations tuteur-étudiant
   - Suivi des évaluations et du bon déroulement des stages
   - Résolution des conflits et gestion des exceptions

3. **Tuteurs (Enseignants)**
   - Suivi des étudiants qui leur sont assignés
   - Planification et tenue des réunions de suivi
   - Évaluation des performances des étudiants
   - Communication avec les étudiants et coordinateurs

4. **Étudiants**
   - Consultation des offres de stage disponibles
   - Expression de préférences pour les stages
   - Communication avec leur tuteur assigné
   - Soumission de documents et rapports de stage

### Système de préférences et d'affectation

L'une des fonctionnalités clés de TutorMatch est son système sophistiqué de gestion des préférences et d'affectation automatique :

- Les étudiants peuvent parcourir les offres de stage et classer leurs préférences
- Les tuteurs peuvent définir leurs préférences (domaines d'expertise, entreprises)
- Les algorithmes d'affectation prennent en compte ces préférences ainsi que d'autres critères (charge de travail, département, etc.)
- Une matrice d'affectation visuelle permet aux coordinateurs de superviser et ajuster manuellement les affectations si nécessaire

### Suivi et évaluation

Le système offre des outils complets pour le suivi et l'évaluation des stages :

- **Gestion documentaire** : Upload et partage de documents (conventions, rapports, etc.)
- **Planification de réunions** : Organisation et suivi des réunions de suivi
- **Évaluations structurées** : Formulaires d'évaluation à mi-parcours et finaux
- **Rapports statistiques** : Indicateurs de performance individuels et globaux

#### Restrictions d'évaluation

Pour garantir l'intégrité du processus d'évaluation, le système impose les restrictions suivantes :

- **Évaluation mi-parcours** : 1 seule évaluation mi-parcours par tuteur/étudiant
- **Évaluation finale** : 1 seule évaluation finale par tuteur/étudiant  
- **Auto-évaluation** : 1 seule auto-évaluation par étudiant

Ces restrictions assurent :
- La cohérence des évaluations
- L'équité du processus d'évaluation
- La simplicité du calcul des moyennes
- La prévention des doublons

Le maximum théorique d'évaluations par étudiant est donc de **3 évaluations** : 1 mi-parcours + 1 finale + 1 auto-évaluation.

### Communication

TutorMatch intègre des fonctionnalités de communication pour faciliter les échanges entre parties prenantes :

- **Messagerie interne** : Système de messagerie intégré entre utilisateurs
- **Notifications** : Alertes en temps réel pour les événements importants
- **Tableaux de bord** : Vue consolidée des activités récentes et à venir
- **Fils d'activité** : Historique chronologique des interactions et événements

## Algorithmes d'affectation

### Vue d'ensemble

Le système d'affectation est l'une des caractéristiques distinctives de TutorMatch. Il permet d'automatiser le processus d'appariement entre étudiants et tuteurs en tenant compte de multiples critères, avec différents algorithmes optimisés pour différents scénarios.

Tous les algorithmes implémentent l'interface commune `AssignmentAlgorithmInterface`, ce qui garantit une utilisation cohérente et permet de facilement ajouter de nouveaux algorithmes.

```php
interface AssignmentAlgorithmInterface {
    public function execute(
        array $students, 
        array $teachers,
        array $internships,
        AssignmentParameters $parameters
    ): AssignmentResult;
}
```

### Algorithme Glouton (Greedy)

#### Principe de fonctionnement

L'algorithme glouton suit une approche itérative simple mais efficace :

1. **Calcul des scores** : Pour chaque paire étudiant-tuteur possible, un score de compatibilité est calculé
2. **Tri** : Les paires sont triées par ordre décroissant de score de compatibilité
3. **Affectation séquentielle** : En parcourant la liste triée, chaque étudiant est affecté au meilleur tuteur disponible

#### Calcul du score de compatibilité

Le score de compatibilité est calculé en fonction de plusieurs critères pondérés :

```php
private function calculateCompatibilityScore(
    object $student, 
    object $teacher, 
    AssignmentParameters $parameters
): float {
    $score = 0;
    
    // 1. Score basé sur le département (même département = meilleur score)
    if ($student->getDepartment() === $teacher->getDepartment()) {
        $score += $parameters->getDepartmentWeight();
    }
    
    // 2. Score basé sur les préférences
    if ($parameters->isPrioritizePreferences()) {
        // Vérifier si l'étudiant a une préférence pour cet enseignant
        $studentPreferenceScore = $this->calculateStudentPreferenceScore($student, $teacher);
        
        // Vérifier si l'enseignant a une préférence pour cet étudiant
        $teacherPreferenceScore = $this->calculateTeacherPreferenceScore($teacher, $student);
        
        // Moyenne des deux scores de préférence
        $preferenceScore = ($studentPreferenceScore + $teacherPreferenceScore) / 2;
        $score += $preferenceScore * $parameters->getPreferenceWeight() / 100;
    }
    
    // 3. Score basé sur l'équilibrage de charge
    if ($parameters->isBalanceWorkload()) {
        // Plus la capacité restante est grande, plus le score est élevé
        $capacityScore = ($teacher->getRemainingCapacity() / $teacher->getMaxStudents()) * 100;
        $score += $capacityScore * $parameters->getCapacityWeight() / 100;
    }
    
    return $score;
}
```

#### Avantages et limites

**Avantages** :
- Performance : O(n² log n) où n est le nombre d'étudiants
- Simplicité d'implémentation et de compréhension
- Adaptation facile à différents critères d'affectation

**Limites** :
- Ne garantit pas la solution optimale globale
- Peut conduire à des affectations sous-optimales dans certains cas complexes
- Sensible à l'ordre de traitement des paires

#### Paramétrage

L'algorithme peut être paramétré via la classe `AssignmentParameters` pour ajuster les poids des différents critères et activer/désactiver certaines contraintes :

```php
$parameters = new AssignmentParameters();
$parameters->setDepartmentWeight(60);          // Importance du même département
$parameters->setPreferenceWeight(30);          // Importance des préférences
$parameters->setCapacityWeight(10);            // Importance de l'équilibrage de charge
$parameters->setAllowCrossDepartment(false);   // Autoriser les affectations inter-départements
$parameters->setPrioritizePreferences(true);   // Prioriser les préférences
$parameters->setBalanceWorkload(true);         // Équilibrer la charge de travail
```

### Algorithme Hongrois (Hungarian)

> Note : Cet algorithme est actuellement en développement et n'est pas encore pleinement implémenté dans le système.

#### Principe de fonctionnement

L'algorithme hongrois (ou algorithme de Kuhn-Munkres) est une méthode d'optimisation qui résout le problème d'affectation en temps polynomial. Contrairement à l'algorithme glouton, il garantit une solution optimale globale.

Les principales étapes sont :

1. **Construction d'une matrice de coûts** : Représente le coût (ou le gain inversé) de chaque affectation possible
2. **Réduction de la matrice** : Soustraire le minimum de chaque ligne et colonne
3. **Recherche d'une affectation optimale** : Trouver un ensemble maximal de zéros indépendants
4. **Mise à jour itérative** : Si l'affectation n'est pas complète, mettre à jour la matrice et répéter

#### Implémentation prévue

```php
class HungarianAlgorithm implements AssignmentAlgorithmInterface
{
    public function execute(
        array $students, 
        array $teachers,
        array $internships,
        AssignmentParameters $parameters
    ): AssignmentResult {
        // 1. Construire la matrice de coûts
        $costMatrix = $this->buildCostMatrix($students, $teachers, $parameters);
        
        // 2. Appliquer l'algorithme hongrois
        $assignments = $this->hungarianAlgorithm($costMatrix);
        
        // 3. Transformer les résultats en AssignmentResult
        return $this->createResult($assignments, $students, $teachers);
    }
    
    private function buildCostMatrix(array $students, array $teachers, AssignmentParameters $parameters): array
    {
        // Création d'une matrice où chaque élément [i][j] représente
        // le coût (inverse du score de compatibilité) d'affecter l'étudiant i au tuteur j
    }
    
    private function hungarianAlgorithm(array $costMatrix): array
    {
        // Implémentation de l'algorithme hongrois classique
        // 1. Réduction des lignes et colonnes
        // 2. Recherche d'affectation optimale
        // 3. Mise à jour itérative si nécessaire
    }
}
```

#### Avantages et limites

**Avantages** :
- Garantit une solution optimale globale
- Bien adapté aux problèmes où l'optimalité est critique
- Insensible à l'ordre de traitement des données

**Limites** :
- Complexité plus élevée : O(n³) où n est le nombre d'étudiants
- Implémentation plus complexe
- Moins flexible pour certaines contraintes spécifiques

### Algorithme Génétique (Genetic)

> Note : Cet algorithme est planifié pour les versions futures et n'est pas encore implémenté.

#### Principe de fonctionnement

L'algorithme génétique s'inspire des principes de l'évolution naturelle pour trouver progressivement une solution optimale :

1. **Initialisation** : Génération d'une population initiale d'affectations possibles
2. **Évaluation** : Calcul du score de fitness pour chaque solution
3. **Sélection** : Choix des meilleures solutions pour la reproduction
4. **Croisement** : Combinaison de solutions existantes pour en créer de nouvelles
5. **Mutation** : Introduction de variations aléatoires
6. **Itération** : Répétition du processus sur plusieurs générations

#### Conception envisagée

```php
class GeneticAlgorithm implements AssignmentAlgorithmInterface
{
    private $populationSize = 100;
    private $generations = 50;
    private $mutationRate = 0.1;
    
    public function execute(
        array $students, 
        array $teachers,
        array $internships,
        AssignmentParameters $parameters
    ): AssignmentResult {
        // 1. Générer une population initiale
        $population = $this->initializePopulation($students, $teachers);
        
        // 2. Évoluer la population sur plusieurs générations
        for ($i = 0; $i < $this->generations; $i++) {
            // Évaluer la fitness de chaque solution
            $fitness = $this->evaluatePopulation($population, $parameters);
            
            // Sélectionner les parents pour la reproduction
            $parents = $this->selectParents($population, $fitness);
            
            // Créer une nouvelle génération par croisement et mutation
            $population = $this->evolvePopulation($parents);
        }
        
        // 3. Sélectionner la meilleure solution
        $bestSolution = $this->getBestSolution($population);
        
        // 4. Transformer en AssignmentResult
        return $this->createResult($bestSolution, $students, $teachers);
    }
}
```

#### Avantages et limites

**Avantages** :
- Très adaptable à des contraintes complexes et changeantes
- Capable de gérer de grands ensembles de données
- Peut trouver des solutions proches de l'optimal dans des espaces de recherche vastes

**Limites** :
- Ne garantit pas toujours la solution optimale absolue
- Performance variable selon le paramétrage
- Temps d'exécution potentiellement plus long

### Personnalisation et optimisation

Les algorithmes d'affectation peuvent être personnalisés et optimisés de plusieurs façons :

#### 1. Ajustement des poids

Les paramètres de poids permettent d'ajuster l'importance relative des différents critères :

```php
// Priorité aux correspondances de département
$parameters->setDepartmentWeight(70);
$parameters->setPreferenceWeight(20);
$parameters->setCapacityWeight(10);

// Priorité à l'équilibrage de charge
$parameters->setDepartmentWeight(30);
$parameters->setPreferenceWeight(20);
$parameters->setCapacityWeight(50);
```

#### 2. Contraintes d'affectation

Des contraintes spécifiques peuvent être activées ou désactivées :

```php
// Strict - Pas d'affectation entre départements différents
$parameters->setAllowCrossDepartment(false);

// Flexible - Permettre les affectations inter-départements
$parameters->setAllowCrossDepartment(true);
```

#### 3. Extension des critères de compatibilité

Le calcul du score de compatibilité peut être étendu pour intégrer d'autres facteurs :

```php
// Dans une classe dérivée de GreedyAlgorithm
protected function calculateCompatibilityScore($student, $teacher, $parameters): float
{
    // Score de base de l'algorithme parent
    $score = parent::calculateCompatibilityScore($student, $teacher, $parameters);
    
    // Critères supplémentaires
    
    // Exemple 1: Correspondance des domaines d'expertise
    $expertiseMatch = $this->calculateExpertiseMatch($student, $teacher);
    $score += $expertiseMatch * $parameters->getExpertiseWeight();
    
    // Exemple 2: Historique de collaboration
    $collaborationScore = $this->getCollaborationHistory($student, $teacher);
    $score += $collaborationScore * $parameters->getHistoryWeight();
    
    return $score;
}
```

#### 4. Tests de benchmark

Des benchmarks sont disponibles pour comparer les performances et la qualité des résultats des différents algorithmes :

```bash
# Comparer les algorithmes sur un même jeu de données
php benchmarks/benchmark_runner.php compare

# Tester la performance de l'algorithme glouton avec différentes tailles de données
php benchmarks/benchmark_runner.php greedy --students=50,100,200
```

## Modèle de données

Le modèle de données de TutorMatch est centré autour de plusieurs entités principales :

### Utilisateurs et rôles

- `users` : Informations de base des utilisateurs (identifiants, informations personnelles)
- `students` : Profils des étudiants, liés aux utilisateurs
- `teachers` : Profils des enseignants/tuteurs, liés aux utilisateurs

### Stages et affectations

- `companies` : Entreprises offrant des stages
- `internships` : Offres de stage proposées par les entreprises
- `student_preferences` : Préférences des étudiants pour les stages
- `assignments` : Affectations finales étudiant-tuteur-stage

### Suivi et communication

- `meetings` : Réunions planifiées entre tuteurs et étudiants
- `documents` : Documents liés aux stages (conventions, rapports, etc.)
- `evaluations` : Évaluations des stages par les tuteurs (avec contraintes d'unicité)
- `messages` : Messages échangés entre utilisateurs
- `notifications` : Notifications système pour les événements importants

### Relations clés

- Un étudiant est associé à un utilisateur (relation 1:1)
- Un tuteur est associé à un utilisateur (relation 1:1)
- Un stage est associé à une entreprise (relation N:1)
- Un étudiant peut exprimer des préférences pour plusieurs stages (relation N:N)
- Une affectation lie un étudiant, un tuteur et un stage (relation ternaire)

## Interface utilisateur

TutorMatch propose des interfaces adaptées à chaque rôle d'utilisateur, avec une expérience cohérente et intuitive.

### Tableaux de bord personnalisés

Chaque rôle dispose d'un tableau de bord spécifique qui présente les informations pertinentes :

- **Tableau de bord administrateur** : Vue d'ensemble du système, statistiques globales
- **Tableau de bord coordinateur** : Suivi des affectations, alertes sur les problèmes
- **Tableau de bord tuteur** : Liste des étudiants supervisés, réunions à venir, évaluations à compléter
- **Tableau de bord étudiant** : Informations sur le stage, contacts, documents à soumettre

### Matrice d'affectation

Une fonctionnalité visuelle distinctive est la matrice d'affectation, qui présente sous forme de tableau croisé les correspondances entre étudiants et tuteurs :

- Les lignes représentent les étudiants
- Les colonnes représentent les tuteurs
- Chaque cellule montre le score de compatibilité et permet une affectation manuelle
- Un code couleur indique la qualité de la correspondance

Cette interface permet aux coordinateurs de visualiser et ajuster facilement les affectations proposées par les algorithmes.

### Thèmes et responsivité

L'interface utilisateur supporte :

- **Thèmes clair et sombre** : Adaptés aux préférences de l'utilisateur
- **Design responsive** : Fonctionnement optimal sur desktop, tablettes et mobiles
- **Accessibilité** : Conformité aux standards WCAG pour une meilleure inclusivité

## Gestion des stages

### Cycle de vie d'un stage

TutorMatch gère l'ensemble du cycle de vie d'un stage :

1. **Création** : Les entreprises ou coordinateurs créent des offres de stage
2. **Publication** : Les offres sont rendues visibles aux étudiants
3. **Candidature** : Les étudiants expriment leurs préférences
4. **Affectation** : Les algorithmes attribuent les tuteurs aux étudiants
5. **Confirmation** : Les affectations sont validées par les coordinateurs
6. **Suivi** : Les tuteurs organisent des réunions et suivent les progrès
7. **Évaluation** : Les tuteurs évaluent les stages à mi-parcours et en fin de période
8. **Clôture** : Le stage est marqué comme terminé et archivé

### Recherche et filtrage

Le système offre des fonctionnalités avancées de recherche pour les offres de stage :

- **Filtres multicritères** : Par entreprise, domaine, localisation, durée, etc.
- **Suggestions intelligentes** : Recommandations basées sur le profil de l'étudiant
- **Recherche plein texte** : Dans les descriptions et compétences requises
- **Tri personnalisable** : Par pertinence, date, popularité, etc.

## Système de notification

TutorMatch intègre un système de notification complet pour maintenir les utilisateurs informés des événements importants :

### Types de notifications

- **Informatives** : Annonces générales, mises à jour du système
- **Tâches** : Actions requises (ex: évaluation à compléter, document à soumettre)
- **Alertes** : Problèmes nécessitant une attention immédiate
- **Confirmations** : Validation d'actions importantes

### Canaux de diffusion

- **Notifications internes** : Visibles dans l'interface de l'application
- **Emails** : Pour les notifications importantes requérant une action
- **Récapitulatifs** : Résumés périodiques des activités récentes

### Personnalisation

Les utilisateurs peuvent personnaliser leurs préférences de notification :

- Activer/désactiver certains types de notifications
- Choisir les canaux de réception préférés
- Définir la fréquence des récapitulatifs

## Messagerie interne

Le système de messagerie interne facilite la communication entre les différents acteurs :

### Fonctionnalités principales

- **Conversations** : Échanges entre deux utilisateurs ou groupes
- **Pièces jointes** : Partage de documents dans les messages
- **Historique** : Conservation de l'historique des conversations
- **Statuts** : Indication de lecture/non-lecture des messages

### Intégration

La messagerie est intégrée au reste du système :

- Lien direct vers les discussions depuis les fiches de stage
- Notification des nouveaux messages
- Accès rapide aux conversations récentes depuis le tableau de bord

## API et intégrations

TutorMatch expose une API REST qui permet :

### Points d'entrée principaux

- `/api/students` : Gestion des profils étudiants
- `/api/teachers` : Gestion des profils enseignants
- `/api/internships` : Gestion des offres de stage
- `/api/assignments` : Gestion des affectations
- `/api/messages` : Gestion de la messagerie
- `/api/notifications` : Gestion des notifications

### Sécurité de l'API

- Authentification par tokens JWT
- Validation des permissions par rôle
- Limitation de débit pour prévenir les abus
- Journalisation des accès pour audit

## Sécurité

TutorMatch implémente plusieurs niveaux de sécurité :

### Authentification et autorisation

- Authentification sécurisée avec hachage des mots de passe
- Sessions sécurisées avec tokens JWT
- Contrôle d'accès basé sur les rôles (RBAC)
- Protection contre la force brute et le verrouillage de compte

### Protection des données

- Validation des entrées utilisateur
- Protection contre les injections SQL
- Protection CSRF pour les formulaires
- Échappement des sorties pour prévenir les XSS

### Confidentialité

- Accès restreint aux données sensibles
- Chiffrement des données confidentielles
- Conformité RGPD (journalisation des consentements, droit à l'oubli, etc.)

## Tests et benchmarks

TutorMatch inclut une suite de tests pour garantir la qualité et les performances :

### Tests unitaires

Tests des composants individuels, notamment :

- Algorithmes d'affectation
- Logique métier des modèles
- Validateurs de données
- Utilitaires

### Tests fonctionnels

Tests des fonctionnalités complètes :

- Processus d'affectation de bout en bout
- Flux de communication
- Génération de rapports

### Benchmarks

Outils pour évaluer les performances :

- Comparaison des algorithmes d'affectation
- Tests de charge pour simuler un grand nombre d'utilisateurs
- Tests de performance sur différentes tailles de jeux de données

## Maintenance et évolutions

### Corrections et optimisations récentes

1. **Tableau de bord du tuteur** :
   - Correction des problèmes d'affichage des réunions et messages non lus
   - Implémentation d'un fournisseur de données direct (`dashboard_data.php`) contournant les limitations de l'API
   - Gestion robuste des données manquantes ou incorrectes dans les modèles
   - Mise en place d'un système de données de secours pour garantir l'affichage en cas d'erreur

2. **Modèle Meeting** :
   - Correction du problème de la colonne `type` inexistante dans la méthode `create()`
   - Mise en place d'une nouvelle fonction `createMeeting()` respectant strictement le schéma de la base de données
   - Amélioration de la gestion des dates avec support de différents formats
   - Implémentation de valeurs par défaut pour les champs optionnels

3. **Interface des notifications** :
   - Harmonisation de l'interface de notifications du tuteur avec celle de l'étudiant
   - Amélioration du formatage des dates relatives (il y a X minutes, heures, jours)
   - Implémentation d'une gestion optimisée des appels API

4. **Gestion des erreurs** :
   - Amélioration de la gestion des erreurs avec des messages explicites
   - Implémentation de mécanismes de secours en cas d'échec des appels API
   - Journalisation détaillée des erreurs pour faciliter le débogage

5. **Restrictions d'évaluation** :
   - Implémentation de contraintes d'unicité dans le modèle `Evaluation`
   - Méthode `canCreateEvaluation()` pour vérifier l'éligibilité avant création
   - Méthode `getEvaluationStatus()` pour consulter l'état des évaluations
   - Mise à jour des interfaces utilisateur pour refléter le maximum de 3 évaluations
   - Harmonisation du calcul des moyennes entre les vues tuteur et étudiant

### Évolutions prévues

1. **Algorithmes avancés** :
   - Implémentation complète de l'algorithme hongrois
   - Développement de l'algorithme génétique
   - Intégration d'approches d'apprentissage automatique

2. **Fonctionnalités additionnelles** :
   - Application mobile pour les étudiants et tuteurs
   - Module de signature électronique pour les conventions
   - Intégration avec des systèmes de gestion académique

3. **Optimisations techniques** :
   - Amélioration des performances de recherche
   - Mise en cache avancée pour les données fréquemment accédées
   - Internationalisation complète de l'interface

### Contribution

Bien que ce projet soit principalement un environnement d'apprentissage et de démonstration, les contributions sont documentées dans le fichier CONTRIBUTING.md à titre éducatif.

## Conclusion

TutorMatch représente une solution complète et sophistiquée pour la gestion des stages académiques, avec un accent particulier sur l'optimisation des affectations étudiant-tuteur. Ses algorithmes avancés, son interface intuitive et ses fonctionnalités de communication en font un outil précieux pour les établissements d'enseignement supérieur souhaitant améliorer leur processus de gestion des stages.

La combinaison d'approches algorithmiques différentes (glouton, hongrois, génétique) offre une flexibilité unique pour s'adapter à différents contextes et priorités, tandis que l'architecture modulaire du système permet son extension et son évolution pour répondre à des besoins futurs.