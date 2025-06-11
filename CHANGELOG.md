# Changelog

Toutes les modifications notables apportées à ce projet seront documentées dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/),
et ce projet adhère au [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Ajouté
- Script d'installation automatique de la base de données (`database/install.php`)
- Documentation détaillée de l'architecture du système
- Interface d'exportation PDF pour les rapports
- Interface de recherche améliorée pour les stages

### Corrigé
- Résolution du problème de méthode manquante `getAll()` dans AssignmentController
- Correction de l'accès à la propriété privée dans l'exportation d'affectations
- Correction des problèmes de syntaxe dans le script de génération des graphiques du tableau de bord

### Modifié
- Refactorisation de la méthode de recherche dans le modèle Assignment
- Amélioration de la documentation des algorithmes d'affectation

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