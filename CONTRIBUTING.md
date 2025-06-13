# Guide de contribution à TutorMatch

## ⚠️ AVIS IMPORTANT : CONTRIBUTIONS TEMPORAIREMENT SUSPENDUES

**Ce projet est actuellement un travail académique universitaire en cours d'évaluation.**

Dans cette phase du développement, nous ne pouvons pas accepter de contributions externes. Ce choix est nécessaire pour :
- Assurer l'évaluation équitable du travail des étudiants impliqués
- Maintenir l'intégrité du projet pendant son évaluation académique
- Respecter les exigences pédagogiques de notre établissement

Nous vous remercions pour votre compréhension et votre intérêt pour ce projet. Si vous avez identifié des problèmes ou avez des suggestions, vous pouvez toujours créer une issue pour documentation, mais veuillez noter qu'aucune pull request ne sera fusionnée pendant cette période.

Ce document reste à titre informatif pour illustrer les bonnes pratiques de gestion de projet et pourrait être activé ultérieurement si le projet s'ouvre aux contributions.

---

## Guide de contribution (pour référence future)

Merci de considérer une contribution au projet TutorMatch ! Ce document présente les lignes directrices pour contribuer au projet.

## Comment contribuer (suspendu temporairement)

### Signaler des bugs

Si vous trouvez un bug, veuillez créer une issue en utilisant le modèle de rapport de bug. Incluez autant de détails que possible :

- Une description claire et concise du bug
- Les étapes pour reproduire le problème
- Le comportement attendu et celui observé
- Des captures d'écran si possible
- Votre environnement (navigateur, OS, version PHP, etc.)

### Suggestions de fonctionnalités

Pour proposer de nouvelles fonctionnalités, créez une issue en utilisant le modèle de suggestion de fonctionnalité. Décrivez clairement :

- Le problème que la fonctionnalité résoudrait
- Comment elle s'intégrerait dans le projet existant
- Pourquoi cette fonctionnalité serait bénéfique pour la majorité des utilisateurs

### Pull Requests

1. Fork le dépôt
2. Créez une branche pour votre fonctionnalité (`git checkout -b feature/amazing-feature`)
3. Committez vos changements (`git commit -m 'Add some amazing feature'`)
4. Push vers la branche (`git push origin feature/amazing-feature`)
5. Ouvrez une Pull Request

### Standards de code

- Suivez les standards PSR-12 pour le code PHP
- Indentez avec 4 espaces (pas de tabulations)
- Utilisez des noms de variables et de fonctions explicites
- Commentez votre code lorsque nécessaire
- Assurez-vous que votre code est compatible avec PHP 8.0+

## Structure du projet

Avant de contribuer, familiarisez-vous avec la structure du projet :

- `api/` - Points d'entrée de l'API REST
- `controllers/` - Contrôleurs MVC
- `models/` - Modèles de données
- `views/` - Vues organisées par rôle utilisateur
- `components/` - Composants UI réutilisables
- `assets/` - Ressources frontend (JS, CSS)
- `src/` - Code source avancé (algorithmes, services)
- `database/` - Scripts et migrations de base de données
- `includes/` - Utilitaires et fonctions partagées

## Processus de développement

### Tests

Avant de soumettre une Pull Request, assurez-vous que :

1. Tous les tests existants passent
2. Vous avez écrit des tests pour vos nouvelles fonctionnalités
3. Le code fonctionne sur les navigateurs modernes (Chrome, Firefox, Safari, Edge)

Pour exécuter les tests :

```bash
vendor/bin/phpunit
```

### Documentation

- Commentez les méthodes et classes avec des commentaires PHPDoc
- Mettez à jour la documentation du README si nécessaire
- Ajoutez des commentaires dans le code pour les parties complexes

## Communication

- Pour les questions générales, utilisez les Discussions GitHub
- Pour les problèmes spécifiques, utilisez les Issues

## Remarques finales

En participant à ce projet, vous acceptez de respecter notre [Code de Conduite](CODE_OF_CONDUCT.md).

Merci de contribuer à améliorer TutorMatch !