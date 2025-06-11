# Améliorations de la recherche de stages

Ce document décrit les améliorations apportées à la fonctionnalité de recherche de stages dans le système de tutorat.

## Modifications apportées

### 1. Modèle de données (`models/Internship.php`)

- **Méthode `search()` améliorée** :
  - Support pour des filtres avancés (domaine, localisation, mode de travail, compétences, dates)
  - Recherche dans les compétences requises
  - Pagination intégrée (limit/offset)
  - Requêtes SQL optimisées avec préparation de requêtes paramétrées
  - Élimination des requêtes redondantes

- **Nouvelle méthode `countSearch()`** :
  - Compte le nombre total de résultats pour la pagination
  - Utilise les mêmes critères de filtrage que la méthode `search()`

### 2. API de recherche (`api/internships/search.php`)

- Support complet pour tous les paramètres de filtrage
- Pagination avec page et limit
- Format de réponse JSON enrichi avec méta-données (total, pages, etc.)
- Compatibilité avec le contrôleur Stimulus `live-search`
- Formatage des résultats pour un affichage riche (URL, sous-titres, images)

### 3. Interface utilisateur

- **Nouveau composant de filtre** (`components/filters/internship-filter.php`) :
  - Filtrage par domaine, localisation, mode de travail
  - Filtrage par compétences requises
  - Sélection de plage de dates
  - Interface collapsible pour économiser l'espace

- **Contrôleur Stimulus amélioré** (`assets/js/controllers/filter_controller.js`) :
  - Support pour le nouveau composant de filtre
  - Gestion de l'état d'ouverture/fermeture
  - Détection automatique des filtres appliqués

- **Page de démonstration** (`internship-search.php`) :
  - Interface responsive avec filtres et résultats
  - Affichage des stages avec toutes leurs informations
  - Pagination complète
  - Affichage du nombre de résultats

## Comment utiliser la recherche améliorée

### Recherche simple

```php
// Initialiser le modèle
$internshipModel = new Internship($db);

// Recherche simple avec terme et statut
$internships = $internshipModel->search('développement', 'available');
```

### Recherche avec filtres avancés

```php
// Préparer les filtres
$filters = [
    'domain' => 'Informatique',
    'location' => 'Paris',
    'work_mode' => 'hybrid',
    'skills' => ['PHP', 'JavaScript'],
    'start_date' => [
        'from' => '2025-06-01',
        'to' => '2025-09-30'
    ]
];

// Recherche avec filtres, pagination et tri
$internships = $internshipModel->search('', 'available', $filters, 10, 0);

// Compter le nombre total pour pagination
$total = $internshipModel->countSearch('', 'available', $filters);
```

### Intégration dans l'API

Exemples d'appels API:

- Recherche simple:
  ```
  /api/internships/search.php?term=développement
  ```

- Recherche avec filtres:
  ```
  /api/internships/search.php?term=développement&domain=Informatique&location=Paris&work_mode=hybrid&skills=PHP,JavaScript&start_date_from=2025-06-01&start_date_to=2025-09-30
  ```

- Pagination:
  ```
  /api/internships/search.php?term=développement&page=2&limit=10
  ```

## Améliorations futures

D'autres améliorations sont prévues comme indiqué dans `TODO-SEARCH-IMPROVEMENTS.md`, notamment:

- Mise en cache des résultats fréquents
- Suggestions de recherche et auto-complétion
- Recherche par proximité géographique
- Interface de comparaison des stages
- Recommandations personnalisées basées sur le profil de l'étudiant

## Comment tester

1. Accédez à la page de démonstration: `/tutoring/internship-search.php`
2. Entrez un terme de recherche dans la barre de recherche
3. Utilisez les filtres avancés pour affiner les résultats
4. Naviguez à travers les pages de résultats

## Documentation technique

Pour plus de détails sur l'implémentation, consultez:
- Les commentaires dans le code source (`models/Internship.php`, `api/internships/search.php`)
- La documentation de l'API dans `docs/API.md`
- Le fichier `TODO-SEARCH-IMPROVEMENTS.md` pour les améliorations planifiées