# Instructions pour nettoyer et régénérer les évaluations

Ce document fournit les instructions pour résoudre les problèmes d'affichage en double des évaluations et les incohérences de scores dans les vues tuteur et étudiant.

## Problèmes identifiés

1. **Duplications dans la base de données**: Plusieurs évaluations existent avec le même `assignment_id` et le même `type`.
2. **Double source de données**: Les évaluations sont récupérées à la fois depuis la table `evaluations` et la table `documents`.
3. **Incohérence dans les échelles de notation**: Les scores sont stockés sur une échelle de 0-20 mais affichés sur une échelle de 0-5.
4. **Conversion inconsistante**: Les différentes vues convertissent les scores différemment, créant des affichages incohérents.

## Solution

Pour résoudre ces problèmes, nous avons:

1. Corrigé les vues pour uniformiser la conversion des scores avec une limite à 5 sur 5.
2. Créé un script SQL pour nettoyer toutes les évaluations existantes et générer des données cohérentes avec des scores directement sur l'échelle 0-5 (plus besoin de conversion).

## Instructions d'utilisation

1. **Importez le script SQL via phpMyAdmin**:
   - Connectez-vous à phpMyAdmin.
   - Sélectionnez la base de données `tutoring_system`.
   - Allez dans l'onglet "Importer".
   - Sélectionnez le fichier `database/clean_and_reset_evaluations.sql`.
   - Cliquez sur "Exécuter".
   
   **Note**: Le script a été corrigé pour utiliser les jointures correctes vers la table `users` afin d'obtenir les noms d'étudiants et tuteurs, car ces champs (`first_name`, `last_name`) ne sont pas directement dans les tables students/teachers.

2. **Vérifiez les résultats**:
   - Accédez à la vue tuteur: `/tutoring/views/tutor/evaluations.php`
   - Accédez à la vue étudiant: `/tutoring/views/student/evaluations.php`
   - Vérifiez que les évaluations s'affichent correctement sans duplication.

## Modifications apportées au code

1. **Script SQL** (`/tutoring/database/clean_and_reset_evaluations.sql`):
   - Utilisation directe de scores sur l'échelle 0-5 (au lieu de 0-20)
   - Génération de scores cohérents: 
     - Évaluations mi-parcours: 0-5
     - Auto-évaluations: 1-5 (légèrement plus positives)
     - Évaluations finales: 3-5 (montrant une progression)

2. **Vue tuteur** (`/tutoring/views/tutor/evaluations.php`):
   - Ajout de la limite `min(5, ...)` pour tous les scores affichés.
   - Utilisation cohérente de `submission_date` pour les dates d'évaluation.
   - Suppression des conversions inutiles avec le nouveau système de notation.

3. **Vue étudiant** (`/tutoring/views/student/evaluations.php`):
   - Limitation des scores à un maximum de 5 sur 5.
   - Amélioration de la gestion des sources de données pour éviter les doublons.
   - Tri des évaluations par date.
   - Suppression des conversions inutiles avec le nouveau système de notation.

## Bonus

Pour maintenir la cohérence à l'avenir, nous recommandons:

1. Ajouter une contrainte d'unicité sur `(assignment_id, type)` dans la table `evaluations`.
2. Utiliser uniquement la table `evaluations` pour stocker les données d'évaluation.
3. Synchroniser les documents d'évaluation uniquement lors de l'export/téléchargement.

Le script SQL fourni peut être exécuté à nouveau si nécessaire pour réinitialiser les données d'évaluation.