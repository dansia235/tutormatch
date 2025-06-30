# Changelog

Toutes les modifications notables apportées à ce projet seront documentées dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/),
et ce projet adhère au [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Ajouté
- **Cache Redis** : Système de mise en cache complet avec fallback automatique
- **Monitoring et métriques** : Interfaces visuelles pour health check et métriques système
- **Documentation API Swagger** : Interface interactive OpenAPI 3.0 avec navigation optimisée  
- **Pipeline CI/CD** : GitHub Actions avec tests automatisés et déploiement
- **Algorithme Hongrois** : Implémentation complète pour affectation optimale
- **Algorithme Génétique** : Implémentation complète et optimisée avec configuration adaptative
  - Configuration automatique selon la taille du problème (small/medium/large/extra_large)
  - Stratégies d'initialisation diversifiées (aléatoire, département, glouton)
  - Logging structuré et métriques Prometheus complètes
  - Benchmarks de performance comparatifs avec autres algorithmes
  - Interface web intégrée avec fallback automatique
- **Rate limiting** : Protection API avec seuils configurables
- **Logging structuré** : Système PSR-3 avec rotation automatique
- Script d'installation automatique de la base de données (`database/install.php`)
- Documentation détaillée de l'architecture du système
- Interface d'exportation PDF pour les rapports
- Interface de recherche améliorée pour les stages
- Création de `dashboard_data.php` pour accès direct aux données du tableau de bord
- Documentation complète du projet dans `PROJECT_DOCUMENTATION.md`

### Corrigé
- **Variables d'environnement** : Erreurs undefined $_ENV dans metrics.php et health.php
- **Navigation Swagger** : Liens rapides et filtrage des tags non fonctionnels  
- **Interface monitoring** : Alignement des cardes Application, Système et Dépendances
- **Configuration Redis** : Utilisation des constantes PHP au lieu des variables d'environnement
- **Bug algorithme génétique** : Correction de la fonction mutate utilisant incorrect count($fitnessScores)
- Résolution du problème de méthode manquante `getAll()` dans AssignmentController
- Correction de l'accès à la propriété privée dans l'exportation d'affectations
- Correction des problèmes de syntaxe dans le script de génération des graphiques du tableau de bord
- Correction de l'erreur "Unknown column 'type'" dans la création de réunions
- Résolution des avertissements "Undefined array key 'created_at'" et "Undefined array key 'student_id'"
- Correction du problème d'affichage des réunions et messages non lus sur le tableau de bord tuteur
- Mise à jour de la page de notifications du tuteur pour correspondre à celle de l'étudiant

### Modifié
- **Interfaces monitoring** : Ajout de thèmes, graphiques Chart.js et auto-refresh
- **Documentation** : Mise à jour PROJECT_DOCUMENTATION.md avec nouvelles fonctionnalités
- **Performance** : Optimisation des requêtes avec cache Redis et TTL configurables
- **Tests** : Scripts de vérification Redis et diagnostics automatisés
- **Algorithme génétique** : Configuration fichier config/genetic_algorithm.php avec paramètres adaptatifs
- **Benchmarks** : Système de comparaison de performances entre algorithmes (genetic vs greedy vs hungarian)
- Refactorisation de la méthode de recherche dans le modèle Assignment
- Amélioration de la documentation des algorithmes d'affectation
- Optimisation du modèle Meeting avec support robuste de différents formats de date
- Implémentation de valeurs par défaut pour les champs optionnels dans le modèle Meeting
- Amélioration de la gestion des erreurs avec systèmes de secours

## [1.0.0] - 2025-06-10

### Ajouté
- Première version stable du système de tutorat
- Gestion complète des utilisateurs (administrateurs, coordinateurs, tuteurs, étudiants)
- Système de gestion des stages et des entreprises
- Module d'affectation avec algorithme glouton
- Messagerie interne entre utilisateurs
- Tableaux de bord pour chaque type d'utilisateur
- Système d'évaluation des stages et des tuteurs
- API REST pour les opérations principales
- Interface responsive avec Bootstrap 5

### Sécurité
- Authentification basée sur les sessions et JWT pour l'API
- Protection CSRF pour tous les formulaires
- Contrôle d'accès basé sur les rôles
- Validation des entrées utilisateur